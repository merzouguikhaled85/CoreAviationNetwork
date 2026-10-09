<?php

namespace tests\unit\components;

use app\components\AogPushService;
use app\components\FirebaseMessagingSender;
use app\models\Requests;
use app\models\User;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\db\Query;
use yii\web\Application;
use yii\web\Cookie;
use yii\web\CookieCollection;

Yii::setAlias('@app', dirname(__DIR__, 3));
require_once dirname(__DIR__, 3) . '/migrations/m261009_200000_create_aog_push_tables.php';

class AogPushTest extends TestCase
{
    private $previous;
    private $service;
    private $request;

    protected function setUp(): void
    {
        $this->previous = Yii::$app;
        new Application([
            'id' => 'aog-push-tests', 'basePath' => dirname(__DIR__, 3),
            'components' => [
                'db' => ['class' => 'yii\db\Connection', 'dsn' => 'sqlite::memory:'],
                'request' => ['class' => PushTestRequest::class, 'cookieValidationKey' => 'test',
                    'scriptUrl' => '/index.php', 'baseUrl' => ''],
                'user' => ['identityClass' => User::class, 'enableSession' => false],
                'aogPush' => ['class' => AogPushService::class],
                'firebaseSender' => ['class' => RecordingPushSender::class],
            ],
            'params' => ['firebase' => ['enabled' => true, 'publicUrl' => 'https://example.test',
                'web' => ['projectId' => 'test-project']]],
        ]);
        $db = Yii::$app->db;
        ob_start();
        (new \m261009_200000_create_aog_push_tables(['db' => $db]))->safeUp();
        ob_end_clean();
        foreach ([
            'mro_profiles' => 'mro_id INTEGER PRIMARY KEY, password TEXT, auth_version INTEGER, status TEXT',
            'requests' => 'request_id INTEGER PRIMARY KEY, aircraft_id INTEGER, destination INTEGER,
                operational_priority TEXT, status TEXT, response_due_at_utc TEXT',
            'aircrafts' => 'aircraft_id INTEGER PRIMARY KEY, aircraft_model_id INTEGER, certificate_type_id INTEGER',
            'aircraft_model' => 'aircraft_model_id INTEGER PRIMARY KEY',
            'certificate_types' => 'certificate_type_id INTEGER PRIMARY KEY, type TEXT',
            'mro_notifications_preferences' => 'mro_id INTEGER PRIMARY KEY,
                notify_for_certified_aircraft INTEGER, notify_for_non_certified_aircraft INTEGER',
            'mroprofile_airport' => 'id INTEGER PRIMARY KEY, mro_id INTEGER, airport_id INTEGER',
            'certificates' => 'certificate_id INTEGER PRIMARY KEY, mro_id INTEGER, type TEXT',
            'mro_aircraft_certificate' => 'aircraft_certificate_id INTEGER PRIMARY KEY, mro_id INTEGER, aircraft_model_id INTEGER',
            'mro_insurance_documents' => 'id INTEGER PRIMARY KEY, mro_id INTEGER',
        ] as $table => $columns) {
            $db->createCommand('CREATE TABLE ' . $table . ' (' . $columns . ')')->execute();
        }
        $db->createCommand()->batchInsert('mro_profiles', ['mro_id', 'password', 'auth_version'],
            [[1, 'hashed-password-one', 1], [2, 'hashed-password-two', 1]])->execute();
        $db->createCommand()->insert('aircrafts', ['aircraft_id' => 1, 'aircraft_model_id' => 1, 'certificate_type_id' => 1])->execute();
        $db->createCommand()->insert('aircraft_model', ['aircraft_model_id' => 1])->execute();
        $db->createCommand()->insert('certificate_types', ['certificate_type_id' => 1, 'type' => 'EASA'])->execute();
        foreach ([1, 2] as $id) {
            $db->createCommand()->insert('mro_notifications_preferences', ['mro_id' => $id,
                'notify_for_certified_aircraft' => 1, 'notify_for_non_certified_aircraft' => 1])->execute();
            $db->createCommand()->insert('mroprofile_airport', ['mro_id' => $id, 'airport_id' => 9])->execute();
        }
        $db->createCommand()->insert('requests', ['request_id' => 1, 'aircraft_id' => 1,
            'destination' => 9, 'operational_priority' => 'aog', 'status' => 'created',
            'response_due_at_utc' => gmdate('Y-m-d H:i:s', time() + 1800)])->execute();
        $this->request = Requests::findOne(1);
        $this->service = Yii::$app->aogPush;
        $this->asMro(1);
    }

    protected function tearDown(): void
    {
        Yii::$app->db->close();
        Yii::$app = $this->previous;
    }

    private function asMro($id)
    {
        Yii::$app->user->setIdentity(new User(['mro_id' => $id]));
    }

    private function device($number, $account = 1)
    {
        $this->asMro($account);
        Yii::$app->request->testCookies = new CookieCollection([
            AogPushService::COOKIE => new Cookie(['name' => AogPushService::COOKIE,
                'value' => str_pad(dechex($number), 64, '0', STR_PAD_LEFT)]),
        ]);
        $this->service->register('test-token-for-device-' . $number);
    }

    private function jobs()
    {
        return (new Query())->from(AogPushService::JOBS)->orderBy('id')->all();
    }

    public function testEveryDeviceOfEachEligibleMroReceivesOneNotification(): void
    {
        $this->device(1);
        $this->device(2);
        $this->device(3, 2);
        $this->service->enqueueRequest($this->request);
        $this->service->enqueueRequest($this->request);
        $this->assertCount(3, $this->jobs());
        foreach ($this->jobs() as $job) {
            $this->assertSame('sent', $this->service->deliver($job)['status']);
        }
        $this->assertSame(['test-token-for-device-1', 'test-token-for-device-2', 'test-token-for-device-3'],
            array_column(Yii::$app->firebaseSender->messages, 'token'));
        $message = Yii::$app->firebaseSender->messages[0];
        $this->assertSame('New AOG Request', $message['notification']['title']);
        $this->assertSame('https://example.test/mro-requests', $message['webpush']['fcm_options']['link']);
    }

    public function testDisableOnlyAffectsTheCurrentDevice(): void
    {
        $this->device(1);
        $this->device(2);
        $this->service->unregister();
        $this->service->enqueueRequest($this->request);
        $this->assertCount(1, $this->jobs());
        $this->assertSame('sent', $this->service->deliver($this->jobs()[0])['status']);
        $this->assertSame('test-token-for-device-1', Yii::$app->firebaseSender->messages[0]['token']);
    }

    public function testRevokedDeviceIsSkippedAfterEnqueue(): void
    {
        $this->device(1);
        $this->service->enqueueRequest($this->request);
        $this->service->disableCurrentBrowser();
        $this->assertSame('discarded', $this->service->deliver($this->jobs()[0])['status']);
        $this->assertSame([], Yii::$app->firebaseSender->messages);
    }

    public function testAccountSwitchCannotReceivePreviousAccountJob(): void
    {
        $this->device(1);
        $this->service->enqueueRequest($this->request);
        $this->device(1, 2);
        $this->assertSame('discarded', $this->service->deliver($this->jobs()[0])['status']);
    }

    public function testPasswordAndSessionRevocationInvalidateDevices(): void
    {
        $this->device(1);
        $this->service->enqueueRequest($this->request);
        Yii::$app->db->createCommand()->update('mro_profiles', ['auth_version' => 2], ['mro_id' => 1])->execute();
        $this->assertSame('discarded', $this->service->deliver($this->jobs()[0])['status']);
        $this->device(1);
        Yii::$app->db->createCommand()->update('mro_profiles', ['password' => 'new-password'], ['mro_id' => 1])->execute();
        $this->assertSame('discarded', $this->service->deliver($this->jobs()[0])['status']);
    }

    public function testPreferenceChangeIsCheckedAgainBeforeDelivery(): void
    {
        $this->device(1);
        $this->service->enqueueRequest($this->request);
        Yii::$app->db->createCommand()->update('mro_notifications_preferences',
            ['notify_for_non_certified_aircraft' => 0], ['mro_id' => 1])->execute();
        $this->assertSame('discarded', $this->service->deliver($this->jobs()[0])['status']);
    }

    public function testCertifiedAndNonCertifiedCoverageUseExistingPreferences(): void
    {
        Yii::$app->db->createCommand()->update('mro_notifications_preferences',
            ['notify_for_non_certified_aircraft' => 0], ['mro_id' => 1])->execute();
        $this->assertFalse($this->service->isEligible(1, $this->request));
        Yii::$app->db->createCommand()->insert('certificates', ['mro_id' => 1, 'type' => 'EASA'])->execute();
        Yii::$app->db->createCommand()->insert('mro_aircraft_certificate', ['mro_id' => 1, 'aircraft_model_id' => 1])->execute();
        $this->assertFalse($this->service->isEligible(1, $this->request));
        Yii::$app->db->createCommand()->insert('mro_insurance_documents', ['mro_id' => 1])->execute();
        $this->assertTrue($this->service->isEligible(1, $this->request));
        Yii::$app->db->createCommand()->delete('mroprofile_airport', ['mro_id' => 1])->execute();
        $this->assertFalse($this->service->isEligible(1, $this->request));
    }

    public function testRoutineOrExpiredRequestsNeverEnqueue(): void
    {
        $this->device(1);
        $this->request->operational_priority = 'routine';
        $this->service->enqueueRequest($this->request);
        $this->request->operational_priority = 'aog';
        $this->request->response_due_at_utc = gmdate('Y-m-d H:i:s', time() - 1);
        $this->service->enqueueRequest($this->request);
        $this->assertSame([], $this->jobs());
    }

    public function testClosedRequestNeverSendsQueuedNotification(): void
    {
        $this->device(1);
        $this->service->enqueueRequest($this->request);
        Yii::$app->db->createCommand()->update('requests', ['status' => 'closed'], ['request_id' => 1])->execute();
        $this->assertSame('discarded', $this->service->deliver($this->jobs()[0])['status']);
    }

    public function testTokenCannotBeTakenByAnotherBrowser(): void
    {
        $this->device(1);
        $this->device(2, 2);
        $this->expectException(\yii\web\BadRequestHttpException::class);
        $this->service->register('test-token-for-device-1');
    }

    public function testAoAccountCannotRegisterMroNotifications(): void
    {
        Yii::$app->user->setIdentity(new User(['ao_id' => 1]));
        $this->expectException(\yii\web\ForbiddenHttpException::class);
        $this->service->register('test-token-for-device-1');
    }

    public function testUnregisteredTokenDeactivatesOnlyItsDevice(): void
    {
        $this->device(1);
        $this->device(2);
        $this->service->enqueueRequest($this->request);
        Yii::$app->firebaseSender->result = ['success' => false, 'invalidToken' => true,
            'retryable' => false, 'error' => 'UNREGISTERED'];
        $this->assertSame('failed', $this->service->deliver($this->jobs()[0])['status']);
        $active = (new Query())->from(AogPushService::SUBSCRIPTIONS)->where(['active' => 1])->all();
        $this->assertCount(1, $active);
        $this->assertSame('test-token-for-device-2', $active[0]['token']);
    }

    public function testTransientFailureRetriesWithoutRemovingSubscription(): void
    {
        $this->device(1);
        $this->service->enqueueRequest($this->request);
        Yii::$app->firebaseSender->result = ['success' => false, 'retryable' => true,
            'retryAfter' => 300, 'error' => 'UNAVAILABLE'];
        $job = $this->jobs()[0];
        $job['attempts'] = 1;
        $result = $this->service->deliver($job);
        $this->assertSame('pending', $result['status']);
        $this->assertSame(300, $result['delay']);
        $job['attempts'] = 5;
        $this->assertSame('failed', $this->service->deliver($job)['status']);
        $this->assertSame('1', (string) (new Query())->from(AogPushService::SUBSCRIPTIONS)->select('active')->scalar());
    }

    public function testDisabledFeatureDoesNotRequireTables(): void
    {
        Yii::$app->params['firebase']['enabled'] = false;
        Yii::$app->db->createCommand()->dropTable(AogPushService::SUBSCRIPTIONS)->execute();
        $this->service->enqueueRequest($this->request);
        $this->assertFalse($this->service->isAvailable());
        $this->assertSame([], $this->jobs());
    }

    public function testCronSendsAllDevicesAndDoesNotResendCompletedJobs(): void
    {
        $this->device(1);
        $this->device(2);
        $this->service->enqueueRequest($this->request);
        $controller = new PushTestCommand('aog-push', Yii::$app);
        $this->assertSame(0, $controller->actionSend(20));
        $this->assertSame(['sent', 'sent'], array_column($this->jobs(), 'status'));
        $this->assertSame(0, $controller->actionSend(20));
        $this->assertCount(2, Yii::$app->firebaseSender->messages);
    }

    public function testCronRecoversInterruptedJobsAndPersistsRetries(): void
    {
        $this->device(1);
        $this->service->enqueueRequest($this->request);
        Yii::$app->db->createCommand()->update(AogPushService::JOBS,
            ['status' => 'processing', 'started_at' => time() - 400], ['request_id' => 1])->execute();
        Yii::$app->firebaseSender->result = ['success' => false, 'retryable' => true,
            'retryAfter' => 300, 'error' => 'UNAVAILABLE'];
        $controller = new PushTestCommand('aog-push', Yii::$app);
        $this->assertSame(0, $controller->actionSend());
        $job = $this->jobs()[0];
        $this->assertSame('pending', $job['status']);
        $this->assertSame(1, (int) $job['attempts']);
        $this->assertGreaterThanOrEqual(time() + 298, (int) $job['available_at']);
        $controller->actionSend();
        $this->assertCount(1, Yii::$app->firebaseSender->messages);
    }

    public function testWebRegistrationRequiresCsrfAndMroIdentity(): void
    {
        Yii::$app->request->testMethod = 'POST';
        Yii::$app->request->setBodyParams(['token' => 'test-token-for-device-1', '_csrf' => 'invalid']);
        $controller = new \app\controllers\PushController('push', Yii::$app);
        try {
            $controller->runAction('register');
            $this->fail('A subscription without valid CSRF must be rejected.');
        } catch (\yii\web\BadRequestHttpException $error) {
            $this->assertSame(400, $error->statusCode);
        }
        Yii::$app->user->setIdentity(new User(['ao_id' => 1]));
        $this->expectException(\yii\web\ForbiddenHttpException::class);
        $controller->runAction('register');
    }

    public function testMroMenuAndPublicWorkerContainOnlyPublicSettings(): void
    {
        $config = require dirname(__DIR__, 3) . '/config/firebase.php';
        Yii::$app->params['firebase'] = array_merge(Yii::$app->params['firebase'], $config);
        $html = Yii::$app->view->render('@app/views/layouts/_aog-push.php');
        $this->assertStringContainsString('Enable AOG notifications', $html);
        $this->assertStringNotContainsString('serviceAccountPath', $html);
        $controller = new \app\controllers\PushController('push', Yii::$app);
        $worker = $controller->runAction('worker');
        $this->assertStringContainsString('firebase.messaging();', $worker);
        $this->assertStringNotContainsString('private_key', $worker);
        $this->assertSame('/', Yii::$app->response->headers->get('Service-Worker-Allowed'));
        $manifest = json_decode($controller->runAction('manifest'), true);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/mro-requests', $manifest['start_url']);
        Yii::$app->user->setIdentity(new User(['ao_id' => 1]));
        $this->assertSame('', Yii::$app->view->render('@app/views/layouts/_aog-push.php'));
    }

    public function testTokenRotationDoesNotSendToOldOrNewTokenForAnOldJob(): void
    {
        $this->device(1);
        $this->service->enqueueRequest($this->request);
        $this->service->register('test-token-refreshed-device-1');
        $this->assertSame('discarded', $this->service->deliver($this->jobs()[0])['status']);
        $this->assertSame([], Yii::$app->firebaseSender->messages);
        $this->assertSame('1', (string) (new Query())->from(AogPushService::SUBSCRIPTIONS)->select('active')->scalar());
    }

    public function testStaleTabCannotRegisterForTheNewSessionAccount(): void
    {
        Yii::$app->request->setBodyParams(['token' => 'test-token-for-device-1', 'account' => 'mro:2']);
        $controller = new \app\controllers\PushController('push', Yii::$app);
        $this->expectException(\yii\web\ConflictHttpException::class);
        $controller->actionRegister();
    }
}

class PushTestRequest extends \yii\web\Request
{
    public $testCookies;
    public $testMethod = 'GET';
    public function getMethod()
    {
        return $this->testMethod;
    }
    public function getCookies()
    {
        return $this->testCookies ?: new CookieCollection();
    }
}

class RecordingPushSender extends FirebaseMessagingSender
{
    public $messages = [];
    public $result = ['success' => true];
    public function isConfigured()
    {
        return true;
    }
    public function send(array $message)
    {
        $this->messages[] = $message;
        return $this->result;
    }
}

class PushTestCommand extends \app\commands\AogPushController
{
    public function stdout($string)
    {
        return strlen($string);
    }
    public function stderr($string)
    {
        return strlen($string);
    }
}
