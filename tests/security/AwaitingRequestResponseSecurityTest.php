<?php

// Standalone security regression test: no application DB, SMTP or production configuration.
defined('YII_ENV') or define('YII_ENV', 'test');
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', false);
$projectRoot = getenv('CAN_SECURITY_PROJECT_ROOT') ?: dirname(__DIR__, 2);
require_once $projectRoot . '/vendor/autoload.php';
require_once $projectRoot . '/vendor/yiisoft/yii2/Yii.php';
Yii::setAlias('@app', $projectRoot);
if (getenv('CAN_SECURITY_CONTROLLER_FILE')) {
    require_once getenv('CAN_SECURITY_CONTROLLER_FILE');
}

class AwaitingSecurityRequest extends yii\web\Request
{
    public $testMethod = 'GET';
    public function getMethod() { return $this->testMethod; }
    protected function loadCsrfToken() { return 'isolated-security-test-token'; }
}

class AwaitingSecuritySession extends yii\web\Session
{
    private $values = [];
    public function get($key, $defaultValue = null) { return $this->values[$key] ?? $defaultValue; }
    public function set($key, $value) { $this->values[$key] = $value; }
}

final class AwaitingRequestResponseSecurityTest extends PHPUnit\Framework\TestCase
{
    private const MUTATIONS = ['ban-ao', 'unban-ao', 'ban-mro', 'unban-mro', 'notify-ao', 'notify-mro'];
    private $previousApp;

    protected function setUp(): void
    {
        $this->previousApp = Yii::$app;
        new yii\web\Application([
            'id' => 'isolated-security-test',
            'basePath' => Yii::getAlias('@app'),
            'components' => [
                'request' => ['class' => AwaitingSecurityRequest::class, 'cookieValidationKey' => 'test-only', 'scriptFile' => __FILE__, 'scriptUrl' => '/index.php'],
                'response' => ['class' => yii\web\Response::class, 'charset' => 'UTF-8'],
                'session' => ['class' => AwaitingSecuritySession::class],
                'user' => ['identityClass' => app\models\User::class, 'enableSession' => false, 'enableAutoLogin' => false, 'loginUrl' => null],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Yii::$app = $this->previousApp;
    }

    public static function deniedRoles(): array
    {
        $cases = [];
        foreach (['index', ...self::MUTATIONS] as $action) {
            foreach (['guest', 'ao', 'mro'] as $role) {
                $cases["$action $role"] = [$action, $role];
            }
        }
        return $cases;
    }

    /** @dataProvider deniedRoles */
    public function testUnauthorizedIdentityCannotReachBusinessCode(string $action, string $role): void
    {
        $this->identity($role);
        // A spoofed/stale business session must not grant administrator rights.
        Yii::$app->session->set('user_type', 'admin');
        Yii::$app->request->testMethod = $action === 'index' ? 'GET' : 'POST';
        Yii::$app->request->setBodyParams(['_csrf' => Yii::$app->request->getCsrfToken()]);
        $this->expectException(yii\web\ForbiddenHttpException::class);
        $this->guard($action);
    }

    public static function mutations(): array
    {
        return array_map(static function ($action) { return [$action]; }, self::MUTATIONS);
    }

    /** @dataProvider mutations */
    public function testAdminPostWithRealValidCsrfPasses(string $action): void
    {
        $this->identity('admin');
        // Authorization comes from the identity, even if the business session is stale.
        Yii::$app->session->set('user_type', 'mro');
        Yii::$app->request->testMethod = 'POST';
        Yii::$app->request->setBodyParams(['_csrf' => Yii::$app->request->getCsrfToken()]);
        $this->assertTrue($this->guard($action));
    }

    public static function forbiddenMethods(): array
    {
        $cases = [];
        foreach (self::MUTATIONS as $action) {
            foreach (['GET', 'HEAD', 'PUT', 'DELETE'] as $method) {
                $cases["$action $method"] = [$action, $method];
            }
        }
        return $cases;
    }

    /** @dataProvider forbiddenMethods */
    public function testMutationsRejectNonPostBeforeBusinessCode(string $action, string $method): void
    {
        $this->identity('admin');
        Yii::$app->request->testMethod = $method;
        $this->expectException(yii\web\MethodNotAllowedHttpException::class);
        $this->guard($action);
    }

    public static function invalidTokens(): array
    {
        $cases = [];
        foreach (self::MUTATIONS as $action) {
            $cases["$action missing"] = [$action, []];
            $cases["$action invalid"] = [$action, ['_csrf' => 'invalid']];
        }
        return $cases;
    }

    /** @dataProvider invalidTokens */
    public function testAdminPostRequiresCsrf(string $action, array $body): void
    {
        $this->identity('admin');
        Yii::$app->request->testMethod = 'POST';
        Yii::$app->request->setBodyParams($body);
        $this->expectException(yii\web\BadRequestHttpException::class);
        $this->guard($action);
    }

    public function testAdminCanReadIndex(): void
    {
        $this->identity('admin');
        $this->assertTrue($this->guard('index'));
    }

    private function identity(string $role): void
    {
        Yii::$app->user->setIdentity($role === 'guest' ? null : new app\models\User([$role . '_id' => 17]));
    }

    private function guard(string $id): bool
    {
        $controller = new app\controllers\AwaitingRequestResponseController('awaiting-request-response', Yii::$app);
        $action = $controller->createAction($id);
        $this->assertNotNull($action);
        // This is the actual Yii dispatch gate; no business action/DB/email is invoked.
        return $controller->beforeAction($action);
    }
}
