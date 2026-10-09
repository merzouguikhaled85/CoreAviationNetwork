<?php

namespace app\components;

use app\models\Aircrafts;
use app\models\Certificates;
use app\models\MroAircraftCertificate;
use app\models\MroInsuranceDocuments;
use app\models\MroNotificationsPreferences;
use app\models\MroProfile;
use app\models\MroprofileAirport;
use app\models\Requests;
use Yii;
use yii\base\Component;
use yii\db\Query;
use yii\web\BadRequestHttpException;
use yii\web\Cookie;
use yii\web\ForbiddenHttpException;

class AogPushService extends Component
{
    const COOKIE = 'can_push_installation';
    const SUBSCRIPTIONS = '{{%push_subscription}}';
    const JOBS = '{{%aog_push_job}}';

    public function isAvailable()
    {
        if (empty(Yii::$app->params['firebase']['enabled'])) {
            return false;
        }
        try {
            return Yii::$app->db->schema->getTableSchema(self::SUBSCRIPTIONS) !== null
                && Yii::$app->db->schema->getTableSchema(self::JOBS) !== null;
        } catch (\Throwable $error) {
            return false;
        }
    }

    private function currentMroId()
    {
        $identity = Yii::$app->user->identity;
        if (!$identity || !preg_match('/^mro:([1-9][0-9]*)$/', (string) $identity->getId(), $match)) {
            throw new ForbiddenHttpException('A MRO account is required.');
        }
        return (int) $match[1];
    }

    private function installation($create = false)
    {
        $value = Yii::$app->request->cookies->getValue(self::COOKIE);
        if (is_string($value) && preg_match('/^[a-f0-9]{64}$/D', $value)) {
            return hash('sha256', $value);
        }
        if (!$create) {
            return null;
        }
        $value = bin2hex(random_bytes(32));
        Yii::$app->response->cookies->add(new Cookie([
            'name' => self::COOKIE, 'value' => $value,
            'httpOnly' => true, 'secure' => Yii::$app->request->isSecureConnection,
            'sameSite' => Cookie::SAME_SITE_LAX, 'path' => '/',
            'expire' => time() + 90 * 86400,
        ]));
        return hash('sha256', $value);
    }

    protected function accountHash($mroId)
    {
        $profile = MroProfile::findOne($mroId);
        if (!$profile || !$profile->password) {
            return null;
        }
        // Cette empreinte fonctionne aussi en console et révoque les anciens appareils.
        return hash('sha256', 'mro:' . $mroId . '|' . $profile->password . '|' . (int) $profile->auth_version);
    }

    public function register($token)
    {
        if (!$this->isAvailable()) {
            throw new BadRequestHttpException('AOG notifications are unavailable.');
        }
        $mroId = $this->currentMroId();
        if (!is_string($token) || strlen($token) < 20 || strlen($token) > 4096
            || !preg_match('/^[A-Za-z0-9:_-]+$/D', $token)) {
            throw new BadRequestHttpException('Invalid notification subscription.');
        }
        $accountHash = $this->accountHash($mroId);
        if ($accountHash === null) {
            throw new ForbiddenHttpException('A valid MRO account is required.');
        }
        $installation = $this->installation(true);
        $tokenHash = hash('sha256', $token);
        $other = (new Query())->from(self::SUBSCRIPTIONS)->where(['token_hash' => $tokenHash])->one();
        if ($other && !hash_equals($other['installation_hash'], $installation)) {
            throw new BadRequestHttpException('Please reset browser notifications and try again.');
        }
        $values = [
            'mro_id' => $mroId, 'token_hash' => $tokenHash, 'token' => $token,
            'account_key_hash' => $accountHash, 'active' => 1,
            'updated_at' => time(), 'expires_at' => time() + 60 * 86400,
        ];
        // Une ligne par navigateur : aucune inscription n'écrase les autres appareils.
        $existing = (new Query())->from(self::SUBSCRIPTIONS)->where(['installation_hash' => $installation])->one();
        if ($existing) {
            Yii::$app->db->createCommand()->update(self::SUBSCRIPTIONS, $values, [
                'id' => $existing['id'], 'installation_hash' => $installation,
            ])->execute();
        } else {
            Yii::$app->db->createCommand()->insert(self::SUBSCRIPTIONS,
                array_merge(['installation_hash' => $installation], $values))->execute();
        }
    }

    public function unregister()
    {
        $mroId = $this->currentMroId();
        $installation = $this->installation();
        if ($this->isAvailable() && $installation !== null) {
            Yii::$app->db->createCommand()->update(self::SUBSCRIPTIONS, ['active' => 0], [
                'installation_hash' => $installation, 'mro_id' => $mroId,
            ])->execute();
        }
    }

    public function disableCurrentBrowser()
    {
        // Un problème de push ne doit jamais empêcher la connexion ou la déconnexion.
        try {
            $installation = $this->installation();
            if ($installation !== null && $this->isAvailable()) {
                Yii::$app->db->createCommand()->update(self::SUBSCRIPTIONS, ['active' => 0], [
                    'installation_hash' => $installation,
                ])->execute();
            }
        } catch (\Throwable $error) {
            Yii::warning('AOG push browser revocation failed.', 'aogPush');
        }
    }

    public function isEligible($mroId, Requests $request)
    {
        $preferences = MroNotificationsPreferences::findOne(['mro_id' => $mroId]);
        if (!$preferences || !MroprofileAirport::find()->where([
            'mro_id' => $mroId, 'airport_id' => $request->destination,
        ])->exists()) {
            return false;
        }
        $aircraft = Aircrafts::findOne($request->aircraft_id);
        $certificate = $aircraft ? $aircraft->getCertificateType()->one() : null;
        $model = $aircraft ? $aircraft->getAircraftModel()->one() : null;
        if (!$certificate || !$model) {
            return false;
        }
        $covered = Certificates::find()->where(['mro_id' => $mroId, 'type' => $certificate->type])->exists()
            && MroAircraftCertificate::find()->where([
                'mro_id' => $mroId, 'aircraft_model_id' => $model->aircraft_model_id,
            ])->exists()
            && MroInsuranceDocuments::find()->where(['mro_id' => $mroId])->exists();
        return $covered ? (bool) $preferences->notify_for_certified_aircraft
            : (bool) $preferences->notify_for_non_certified_aircraft;
    }

    private function deadline(Requests $request)
    {
        return $request->response_due_at_utc
            ? (int) strtotime($request->response_due_at_utc . ' UTC') : 0;
    }

    public function enqueueRequest(Requests $request)
    {
        if ($request->operational_priority !== Requests::PRIORITY_AOG || !$this->isAvailable()) {
            return;
        }
        try {
            $now = time();
            $expires = min($now + 3600, $this->deadline($request));
            if (!$request->request_id || $request->status !== Requests::STATUS_CREATED || $expires <= $now) {
                return;
            }
            $eligible = [];
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Chaque appareil actif du MRO reçoit son propre travail d'envoi.
                foreach ((new Query())->from(self::SUBSCRIPTIONS)->where(['active' => 1])
                    ->andWhere(['>', 'expires_at', $now])->each(50) as $subscription) {
                    $mroId = (int) $subscription['mro_id'];
                    if (!array_key_exists($mroId, $eligible)) {
                        $eligible[$mroId] = $this->isEligible($mroId, $request);
                    }
                    if (!$eligible[$mroId]) {
                        continue;
                    }
                    Yii::$app->db->createCommand()->upsert(self::JOBS, [
                        'request_id' => $request->request_id, 'mro_id' => $mroId,
                        'subscription_id' => $subscription['id'], 'token_hash' => $subscription['token_hash'],
                        'status' => 'pending', 'attempts' => 0, 'available_at' => $now,
                        'started_at' => 0, 'created_at' => $now, 'expires_at' => $expires,
                    ], false)->execute();
                }
                $transaction->commit();
            } catch (\Throwable $error) {
                $transaction->rollBack();
                throw $error;
            }
        } catch (\Throwable $error) {
            // Ne pas journaliser les jetons, les paramètres SQL ou les secrets.
            Yii::warning('AOG push enqueue failed.', 'aogPush');
        }
    }

    public function deliver(array $job)
    {
        $now = time();
        $subscription = (new Query())->from(self::SUBSCRIPTIONS)->where(['id' => $job['subscription_id']])->one();
        $request = Requests::findOne($job['request_id']);
        if (!$subscription || !$subscription['active'] || (int) $subscription['expires_at'] <= $now
            || (int) $subscription['mro_id'] !== (int) $job['mro_id']
            || !hash_equals($subscription['token_hash'], $job['token_hash'])
            || !hash_equals($subscription['account_key_hash'], (string) $this->accountHash($job['mro_id']))
            || !$request || $request->operational_priority !== Requests::PRIORITY_AOG
            || $request->status !== Requests::STATUS_CREATED || (int) $job['expires_at'] <= $now
            || $this->deadline($request) <= $now || !$this->isEligible($job['mro_id'], $request)) {
            return ['status' => 'discarded', 'error' => null];
        }
        $publicUrl = rtrim(Yii::$app->params['firebase']['publicUrl'] ?? '', '/');
        if (!filter_var($publicUrl, FILTER_VALIDATE_URL) || parse_url($publicUrl, PHP_URL_SCHEME) !== 'https'
            || parse_url($publicUrl, PHP_URL_QUERY) !== null || parse_url($publicUrl, PHP_URL_FRAGMENT) !== null) {
            return ['status' => 'failed', 'error' => 'INVALID_PUBLIC_URL'];
        }
        $ttl = max(1, min((int) $job['expires_at'], $this->deadline($request)) - $now);
        $result = Yii::$app->firebaseSender->send([
            'token' => $subscription['token'],
            'notification' => ['title' => 'New AOG Request',
                'body' => 'A new AOG request is available. View the request and response deadline.'],
            'webpush' => [
                'headers' => ['Urgency' => 'high', 'TTL' => (string) $ttl],
                'notification' => ['tag' => 'aog-request-' . $request->request_id],
                'fcm_options' => ['link' => $publicUrl . '/mro-requests'],
            ],
        ]);
        if (!empty($result['success'])) {
            return ['status' => 'sent', 'error' => null];
        }
        if (!empty($result['invalidToken'])) {
            Yii::$app->db->createCommand()->update(self::SUBSCRIPTIONS, ['active' => 0], [
                'id' => $subscription['id'], 'token_hash' => $job['token_hash'],
            ])->execute();
        }
        $retry = !empty($result['retryable']) && (int) $job['attempts'] < 5;
        return ['status' => $retry ? 'pending' : 'failed',
            'error' => $result['error'] ?? 'DELIVERY_FAILED',
            'delay' => max(60 * (2 ** min(5, (int) $job['attempts'])), (int) ($result['retryAfter'] ?? 0))];
    }
}
