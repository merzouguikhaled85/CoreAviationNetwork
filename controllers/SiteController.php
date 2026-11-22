<?php

namespace app\controllers;
use app\components\PrelaunchMode;
use app\models\Airports;
use app\models\Notification;
use app\models\UsernameResetRequestForm;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\Aircrafts;
use app\models\AircraftModel;
use app\models\AoNotificationsPreferences;
use app\models\AoProfile;
use app\models\AdminProfile;
use app\models\MroProfile;
use app\models\Certificates;
use app\models\Cities;
use app\models\Terms;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;
use app\models\Countries;
use app\models\MroAircraftCertificate;
use app\models\MroNotificationsPreferences;
use app\models\MroprofileAirport;
use yii\base\InvalidConfigException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Email;
use yii\web\BadRequestHttpException;
use app\models\ResetPasswordForm;
use app\models\PasswordResetRequestForm;
use yii\helpers\VarDumper;
use app\models\MroInsuranceDocuments;
use app\models\UserPasswordResetRequestForm;
use app\models\PrelaunchSubscriber;
use yii\db\IntegrityException;


class SiteController extends Controller
{

    public $layout = 'landing-main';
    public $token='';

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'logout-all-devices'],
                'rules' => [
                    [
                        'actions' => ['logout', 'logout-all-devices'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                    'logout-all-devices' => ['post'],
                    'join-network' => ['post'],
                    'confirm-prelaunch' => ['get'],
                    'resend-prelaunch' => ['post'],
                    'unsubscribe-prelaunch' => ['get', 'post'],
                ],
            ],
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('home', [
            'prelaunchSubscriber' => $this->newPrelaunchSubscriber(),
        ]);
    }

    /**
     * Registers a visitor and dispatches the double opt-in email.
     */
    public function actionJoinNetwork()
    {
        $model = $this->newPrelaunchSubscriber();
        if (!$model->load(Yii::$app->request->post())) {
            Yii::$app->session->setFlash('error', 'The registration could not be submitted. Please try again.');
            return $this->redirect(['/site/index', '#' => 'early-access']);
        }

        if (!$this->consumePrelaunchRateLimit()) {
            Yii::$app->session->setFlash('warning', 'Too many registration attempts. Please try again in a few minutes.');
            return $this->redirect(['/site/index', '#' => 'early-access']);
        }

        if (!$model->validate()) {
            Yii::$app->response->statusCode = 422;
            return $this->render('home', ['prelaunchSubscriber' => $model]);
        }

        /* Reject malformed or honeypot submissions before calling Turnstile. */
        $challenge = $this->validateTurnstileChallenge('early_access', 'early access registration');
        if (!$challenge['valid']) {
            Yii::$app->session->setFlash('error', $challenge['message']);
            Yii::$app->response->statusCode = $challenge['status'];
            return $this->render('home', ['prelaunchSubscriber' => $model]);
        }

        $now = date('Y-m-d H:i:s');
        $subscriber = PrelaunchSubscriber::findOne(['business_email' => $model->business_email]);

        /*
         * A confirmed address receives the same generic response as a new one.
         * This prevents the public form from becoming an email enumeration API.
         */
        if ($subscriber && $subscriber->subscription_status === PrelaunchSubscriber::STATUS_CONFIRMED) {
            $this->setPrelaunchGenericSuccess();
            return $this->redirect(['/site/index', '#' => 'early-access']);
        }

        /* A pending confirmation is resent at most once every ten minutes. */
        if (
            $subscriber
            && $subscriber->subscription_status === PrelaunchSubscriber::STATUS_PENDING_CONFIRMATION
            && $subscriber->confirmation_sent_at
            && strtotime($subscriber->confirmation_sent_at) > time() - 600
        ) {
            $this->setPrelaunchGenericSuccess();
            return $this->redirect(['/site/index', '#' => 'early-access']);
        }

        $confirmationToken = Yii::$app->security->generateRandomString(48);
        $unsubscribeToken = Yii::$app->security->generateRandomString(48);
        $transaction = Yii::$app->db->beginTransaction();

        try {
            if ($subscriber === null) {
                $subscriber = $model;
            } else {
                $subscriber->first_name = $model->first_name;
                $subscriber->last_name = $model->last_name;
                $subscriber->company_name = $model->company_name;
                $subscriber->company_website = $model->company_website;
                $subscriber->company_type = $model->company_type;
            }

            $subscriber->business_email = $model->business_email;
            $subscriber->subscription_status = PrelaunchSubscriber::STATUS_PENDING_CONFIRMATION;
            $subscriber->consent = 1;
            $subscriber->consent_at = $now;
            $subscriber->consent_text_version = PrelaunchSubscriber::CONSENT_TEXT_VERSION;
            $subscriber->source = 'homepage';
            $subscriber->confirmed_at = null;
            $subscriber->unsubscribed_at = null;
            $subscriber->confirmation_token_hash = hash('sha256', $confirmationToken);
            $subscriber->confirmation_token_expires_at = date('Y-m-d H:i:s', time() + 86400);
            $subscriber->confirmation_sent_at = null;
            $subscriber->confirmation_email_status = PrelaunchSubscriber::EMAIL_PENDING;
            $subscriber->confirmation_last_error = null;
            $subscriber->unsubscribe_token_hash = hash('sha256', $unsubscribeToken);
            $subscriber->ip_address = mb_substr((string) Yii::$app->request->userIP, 0, 45, 'UTF-8');
            $subscriber->user_agent = mb_substr(
                (string) Yii::$app->request->userAgent,
                0,
                500,
                'UTF-8'
            );

            if (!$subscriber->save(false)) {
                throw new \RuntimeException('The early access registration could not be persisted.');
            }

            $transaction->commit();
        } catch (IntegrityException $exception) {
            $transaction->rollBack();
            Yii::warning('Concurrent duplicate early access registration was ignored.', __METHOD__);
            $this->setPrelaunchGenericSuccess();
            return $this->redirect(['/site/index', '#' => 'early-access']);
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            Yii::error([
                'message' => 'Early access registration failed.',
                'exception' => $exception,
            ], __METHOD__);
            Yii::$app->session->setFlash('error', 'We could not save your registration. Please try again later.');
            return $this->redirect(['/site/index', '#' => 'early-access']);
        }

        $delivery = $this->sendPrelaunchConfirmationEmail(
            $subscriber,
            $confirmationToken,
            $unsubscribeToken
        );
        $deliveryAttributes = [
            'confirmation_attempt_count' => (int) $subscriber->confirmation_attempt_count + 1,
            'confirmation_attempted_at' => $now,
        ];

        if ($delivery['sent']) {
            $deliveryAttributes['confirmation_email_status'] = PrelaunchSubscriber::EMAIL_SENT;
            $deliveryAttributes['confirmation_sent_at'] = $now;
            $deliveryAttributes['confirmation_last_error'] = null;
            $subscriber->updateAttributes($deliveryAttributes);
            $this->setPrelaunchGenericSuccess();
        } else {
            $deliveryAttributes['confirmation_email_status'] = PrelaunchSubscriber::EMAIL_FAILED;
            $deliveryAttributes['confirmation_last_error'] = $delivery['error'];
            $subscriber->updateAttributes($deliveryAttributes);
            Yii::$app->session->setFlash(
                'warning',
                'Your registration was saved. Email delivery may take a few minutes. Please check your inbox and spam folder before trying again.'
            );
        }

        return $this->redirect(['/site/index', '#' => 'early-access']);
    }

    /**
     * Confirms a pending early-access address using a one-day token.
     */
    public function actionConfirmPrelaunch($token = '')
    {
        $subscriber = $this->findPrelaunchSubscriberByToken($token, 'confirmation_token_hash');
        if ($subscriber === null || $subscriber->subscription_status === PrelaunchSubscriber::STATUS_UNSUBSCRIBED) {
            return $this->renderPrelaunchConfirmationResult('invalid');
        }
        if ($subscriber->subscription_status === PrelaunchSubscriber::STATUS_CONFIRMED) {
            return $this->renderPrelaunchConfirmationResult('already-confirmed');
        }
        if (!$subscriber->confirmation_token_expires_at || strtotime($subscriber->confirmation_token_expires_at) < time()) {
            return $this->renderPrelaunchConfirmationResult('expired', $token);
        }
        // A simultaneous unsubscribe or token renewal must not be overwritten.
        $confirmed = PrelaunchSubscriber::updateAll([
            'subscription_status' => PrelaunchSubscriber::STATUS_CONFIRMED,
            'confirmed_at' => date('Y-m-d H:i:s'),
        ], [
            'id' => $subscriber->id,
            'subscription_status' => PrelaunchSubscriber::STATUS_PENDING_CONFIRMATION,
            'confirmation_token_hash' => hash('sha256', trim($token)),
        ]);
        if (!$confirmed) {
            $subscriber->refresh();
            return $this->renderPrelaunchConfirmationResult(
                $subscriber->subscription_status === PrelaunchSubscriber::STATUS_CONFIRMED ? 'already-confirmed' : 'invalid'
            );
        }
        return $this->renderPrelaunchConfirmationResult('success');
    }

    /** Request a fresh link from the expired-link page without re-entering registration details. */
    public function actionResendPrelaunch()
    {
        $token = Yii::$app->request->post('token', '');
        if (!is_string($token)) { return $this->renderPrelaunchConfirmationResult('invalid'); }
        $subscriber = $this->findPrelaunchSubscriberByToken($token, 'confirmation_token_hash');
        if ($subscriber === null || $subscriber->subscription_status === PrelaunchSubscriber::STATUS_UNSUBSCRIBED) {
            return $this->renderPrelaunchConfirmationResult('invalid');
        }
        if ($subscriber->subscription_status === PrelaunchSubscriber::STATUS_CONFIRMED) {
            return $this->renderPrelaunchConfirmationResult('already-confirmed');
        }
        if (!$this->consumePrelaunchRateLimit()) {
            return $this->renderPrelaunchConfirmationResult('rate-limited', $token);
        }
        $now = date('Y-m-d H:i:s');
        $cutoff = date('Y-m-d H:i:s', time() - 60);
        $confirmationToken = Yii::$app->security->generateRandomString(48);
        $unsubscribeToken = Yii::$app->security->generateRandomString(48);
        $previousExpiry = $subscriber->confirmation_token_expires_at;
        $previousUnsubscribeHash = $subscriber->unsubscribe_token_hash;
        $claimed = PrelaunchSubscriber::updateAll([
            'confirmation_token_hash' => hash('sha256', $confirmationToken),
            'confirmation_token_expires_at' => date('Y-m-d H:i:s', time() + 86400),
            'unsubscribe_token_hash' => hash('sha256', $unsubscribeToken),
            'confirmation_attempted_at' => $now,
            'confirmation_attempt_count' => new \yii\db\Expression('confirmation_attempt_count + 1'),
            'confirmation_email_status' => PrelaunchSubscriber::EMAIL_PENDING,
            'confirmation_last_error' => null,
        ], ['and',
            ['id' => $subscriber->id, 'subscription_status' => PrelaunchSubscriber::STATUS_PENDING_CONFIRMATION,
                'confirmation_token_hash' => hash('sha256', trim($token))],
            ['or', ['confirmation_attempted_at' => null], ['<=', 'confirmation_attempted_at', $cutoff]],
            ['or', ['confirmation_sent_at' => null], ['<=', 'confirmation_sent_at', $cutoff]],
        ]);
        if (!$claimed) { return $this->renderPrelaunchConfirmationResult('cooldown', $token); }
        $subscriber->refresh();
        $delivery = $this->sendPrelaunchConfirmationEmail($subscriber, $confirmationToken, $unsubscribeToken);
        $attributes = [
            'confirmation_email_status' => $delivery['sent'] ? PrelaunchSubscriber::EMAIL_SENT : PrelaunchSubscriber::EMAIL_FAILED,
            'confirmation_last_error' => $delivery['error'],
        ];
        if ($delivery['sent']) { $attributes['confirmation_sent_at'] = date('Y-m-d H:i:s'); }
        else {
            // Keep the expired token usable for a retry; never expose the fresh email token in HTML.
            $attributes['confirmation_token_hash'] = hash('sha256', trim($token));
            $attributes['confirmation_token_expires_at'] = $previousExpiry;
            $attributes['unsubscribe_token_hash'] = $previousUnsubscribeHash;
        }
        PrelaunchSubscriber::updateAll($attributes, ['id' => $subscriber->id, 'confirmation_token_hash' => hash('sha256', $confirmationToken)]);
        return $this->renderPrelaunchConfirmationResult($delivery['sent'] ? 'resent' : 'delivery-failed', $delivery['sent'] ? '' : $token);
    }

    private function renderPrelaunchConfirmationResult($state, $token = '')
    {
        $this->layout = 'prelaunch-confirmation';
        Yii::$app->response->headers->set('Cache-Control', 'no-store');
        Yii::$app->response->headers->set('Referrer-Policy', 'no-referrer');
        return $this->render('prelaunch-confirmation', ['state' => $state, 'token' => $token]);
    }

    /**
     * Uses a confirmation screen before removing an address from launch updates.
     */
    public function actionUnsubscribePrelaunch($token)
    {
        $subscriber = $this->findPrelaunchSubscriberByToken($token, 'unsubscribe_token_hash');
        if ($subscriber === null) {
            Yii::$app->session->setFlash('error', 'This preference link is invalid.');
            return $this->redirect(['/site/index']);
        }

        $alreadyUnsubscribed = $subscriber->subscription_status === PrelaunchSubscriber::STATUS_UNSUBSCRIBED;
        if (Yii::$app->request->isPost && !$alreadyUnsubscribed) {
            $subscriber->updateAttributes([
                'subscription_status' => PrelaunchSubscriber::STATUS_UNSUBSCRIBED,
                'unsubscribed_at' => date('Y-m-d H:i:s'),
                'confirmation_token_hash' => null,
                'confirmation_token_expires_at' => null,
            ]);
            Yii::$app->session->setFlash('success', 'Your address has been removed from the launch notification list.');
            return $this->redirect(['/site/index']);
        }

        return $this->render('prelaunch-unsubscribe', [
            'token' => $token,
            'alreadyUnsubscribed' => $alreadyUnsubscribed,
        ]);
    }

    private function newPrelaunchSubscriber()
    {
        $model = new PrelaunchSubscriber();
        $model->scenario = PrelaunchSubscriber::SCENARIO_PUBLIC_SIGNUP;
        return $model;
    }

    private function redirectToPrelaunchRegistration()
    {
        Yii::$app->session->setFlash(
            'info',
            'New AO and MRO registrations are currently available through Early Access.'
        );

        return $this->redirect(PrelaunchMode::earlyAccessRoute());
    }

    private function setPrelaunchGenericSuccess()
    {
        Yii::$app->session->setFlash(
            'success',
            'Thank you. If confirmation is required, an email has been sent to the address provided.'
        );
    }

    private function findPrelaunchSubscriberByToken($token, $attribute)
    {
        $token = trim((string) $token);
        if (!in_array($attribute, ['confirmation_token_hash', 'unsubscribe_token_hash'], true)) {
            return null;
        }
        if (strlen($token) < 32 || strlen($token) > 128 || !preg_match('/^[A-Za-z0-9_-]+$/', $token)) {
            return null;
        }

        return PrelaunchSubscriber::findOne([$attribute => hash('sha256', $token)]);
    }

    private function sendPrelaunchConfirmationEmail(
        PrelaunchSubscriber $subscriber,
        $confirmationToken,
        $unsubscribeToken
    ) {
        return \app\components\PrelaunchConfirmationMailer::send($subscriber, $confirmationToken, $unsubscribeToken);
    }

    /**
     * Five attempts per IP every ten minutes; the address is only used in cache.
     */
    private function consumePrelaunchRateLimit()
    {
        $key = 'prelaunch-rate-' . hash('sha256', (string) Yii::$app->request->userIP);
        $now = time();
        $window = Yii::$app->cache->get($key);

        if (!is_array($window) || ($now - (int) ($window['started'] ?? 0)) >= 600) {
            Yii::$app->cache->set($key, ['started' => $now, 'count' => 1], 600);
            return true;
        }
        if ((int) ($window['count'] ?? 0) >= 5) {
            return false;
        }

        $window['count'] = (int) ($window['count'] ?? 0) + 1;
        Yii::$app->cache->set($key, $window, 600);
        return true;
    }

    /**
     * Validates the Turnstile token without ever logging the token or secret.
     */
    private function validateTurnstileChallenge($expectedAction, $context)
    {
        if (YII_ENV_TEST) {
            return ['valid' => true, 'status' => 200, 'message' => ''];
        }

        $secret = trim((string) (Yii::$app->params['turnstileSecretKey'] ?? ''));
        $siteKey = trim((string) (Yii::$app->params['turnstileSiteKey'] ?? ''));
        $expectedHostname = strtolower(rtrim(trim((string) (
            Yii::$app->params['turnstileExpectedHostname'] ?? ''
        )), '.'));
        if ($secret === '' || $siteKey === '' || $expectedHostname === '') {
            Yii::error("Turnstile is not configured for {$context}.", __METHOD__);
            return [
                'valid' => false,
                'status' => 503,
                'message' => 'Visitor verification is temporarily unavailable. Please try again later.',
            ];
        }

        $token = trim((string) Yii::$app->request->post('cf-turnstile-response', ''));
        if ($token === '' || strlen($token) > 2048) {
            return [
                'valid' => false,
                'status' => 422,
                'message' => 'Please complete the visitor verification.',
            ];
        }

        if (!function_exists('curl_init')) {
            Yii::error("cURL is unavailable for {$context} Turnstile validation.", __METHOD__);
            return [
                'valid' => false,
                'status' => 503,
                'message' => 'Visitor verification is temporarily unavailable. Please try again later.',
            ];
        }

        $handle = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        $options = [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_POSTFIELDS => http_build_query([
                'secret' => $secret,
                'response' => $token,
                'remoteip' => (string) Yii::$app->request->userIP,
            ]),
        ];
        if (defined('CURLSSLOPT_NATIVE_CA')) {
            $options[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
        }
        curl_setopt_array($handle, $options);

        $rawResponse = curl_exec($handle);
        $httpCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $transportError = curl_error($handle);
        curl_close($handle);

        if ($rawResponse === false || $transportError !== '' || $httpCode !== 200) {
            Yii::warning([
                'message' => "Turnstile transport failed for {$context}.",
                'httpCode' => $httpCode,
                'transportError' => $transportError,
            ], __METHOD__);
            return [
                'valid' => false,
                'status' => 503,
                'message' => 'Visitor verification is temporarily unavailable. Please try again.',
            ];
        }

        $result = json_decode((string) $rawResponse, true);
        $verifiedAction = is_array($result) ? ($result['action'] ?? $expectedAction) : null;
        $verifiedHostname = is_array($result)
            ? strtolower(rtrim(trim((string) ($result['hostname'] ?? '')), '.'))
            : '';
        /*
         * La cle publique officielle 1x000...AA renvoie volontairement un
         * hostname factice. Cette exception est limitee au mode developpement ;
         * une vraie cle de production conserve la comparaison stricte ci-dessous.
         */
        $usesOfficialDevelopmentTestKey = YII_ENV_DEV
            && hash_equals('1x00000000000000000000AA', $siteKey);
        $actionIsValid = $verifiedAction === $expectedAction
            || (YII_ENV_DEV && $verifiedAction === 'test');
        $hostnameIsValid = $usesOfficialDevelopmentTestKey
            || ($verifiedHostname !== '' && hash_equals($expectedHostname, $verifiedHostname));

        if (
            !is_array($result)
            || empty($result['success'])
            || !$actionIsValid
            || !$hostnameIsValid
        ) {
            Yii::warning([
                'message' => "Turnstile rejected {$context}.",
                'errorCodes' => is_array($result) ? ($result['error-codes'] ?? []) : ['invalid-json'],
                'hostnameMatches' => $hostnameIsValid,
            ], __METHOD__);
            return [
                'valid' => false,
                'status' => 422,
                'message' => 'Visitor verification failed or expired. Please try again.',
            ];
        }

        return ['valid' => true, 'status' => 200, 'message' => ''];
    }

public function beforeAction($action)
{
    // Error pages use a small standalone layout so they still render when the
    // authenticated navigation or another application widget is unavailable.
    if ($action->id === 'error') {
        $this->layout = 'error';
        return parent::beforeAction($action);
    }

    /*
     * Initialise le jeton des visiteurs sans le remplacer avant un POST.
     * Une régénération forcée ici invaliderait le formulaire qui vient d'être soumis.
     */
    if (Yii::$app->user->isGuest) {
        Yii::$app->request->getCsrfToken();
    }

    if (!Yii::$app->user->isGuest && Yii::$app->session->get('user_type') === null) {
        Yii::$app->user->logout();
        Yii::$app->session->setFlash('error', 'Session expired. Please log in again.');
        Yii::$app->response->redirect(['site/login'])->send();
        return false;
    }

    return parent::beforeAction($action);
}


    

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLanding()
    {
        
        if (!Yii::$app->user->isGuest && Yii::$app->session->get('user_type') == null)
   {
        Yii::$app->user->logout();
        Yii::$app->response->redirect(['site/login'])->send();
        return false;
    }else
        return $this->render('landing-page');
    }

    /**
     * Public media-kit page for advertisers and aviation partners.
     *
     * @return string
     */
    public function actionAdvertising()
    {
        if (PrelaunchMode::isEnabled()) {
            Yii::$app->session->setFlash(
                'info',
                'Advertising will open with the network. Join Early Access to receive launch updates.'
            );
            return $this->redirect(PrelaunchMode::earlyAccessRoute());
        }

        return $this->render('advertising');
    }
    
    public function actionBecomeAo()
    {
        if (PrelaunchMode::isEnabled()) {
            return $this->redirectToPrelaunchRegistration();
        }

        // Écran d'inscription autonome : layout « auth », sans header ni footer du site.
        $this->layout = 'auth';

        $model = new AoProfile();
        $airplaneModel = new Aircrafts();
        $manufacturers = AircraftModel::find()->select('manufacturer')->distinct()->orderBy(['manufacturer' => SORT_ASC])->all();
        $models = AircraftModel::find()->orderBy(['manufacturer' => SORT_ASC, 'model' => SORT_ASC])->all();
        $countries = Countries::find()->orderBy('country_name')->all();
        $termsContent = Terms::find()->one()->content;
        // Les villes sont chargées par AJAX (site/get-cities) : plus de chargement de toutes les villes ici.

        /*
         * RETOUR D'ERREUR SANS PERTE DE SAISIE (2026-11-22), même logique que
         * l'inscription MRO : erreurs collectées, formulaire réaffiché avec les
         * valeurs, ouvert sur la première étape à corriger.
         */
        $formErrors = [];
        $errorStep = null;
        $addError = static function (int $step, string $message) use (&$formErrors, &$errorStep): void {
            $formErrors[] = $message;
            $errorStep = $errorStep === null ? $step : min($errorStep, $step);
        };

        // Photo de profil conservée côté serveur après une erreur (voir actionBecomeMro).
        $session = Yii::$app->session;
        $keptKey = 'aoSignupKeptFiles';
        $kept = (array) $session->get($keptKey, []);
        if (!Yii::$app->request->isPost) {
            $this->discardMroSignupFiles($kept, 'ao');
            $kept = [];
        }

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            $post = Yii::$app->request->post();
            $aircraftPost = (array) ($post['Aircrafts'] ?? []);
            $airplaneModel->manufacturer = trim((string) ($aircraftPost['manufacturer'] ?? ''));
            $airplaneModel->model = trim((string) ($aircraftPost['model'] ?? ''));
            $airplaneModel->serial_number = trim((string) ($aircraftPost['serial_number'] ?? ''));
            $airplaneModel->registration_number = trim((string) ($aircraftPost['registration_number'] ?? ''));
            $airplaneModel->certificate_type_id = $aircraftPost['certificate_type_id'] ?? null;

            $model->password = trim((string) ($post['AoProfile']['password'] ?? ''));
            $model->confirm_password = trim((string) ($post['AoProfile']['confirm_password'] ?? ''));

            /*
             * 1. VALIDATION COMPLÈTE AVANT TOUT ENREGISTREMENT : avant, le profil
             *    était créé puis l'avion pouvait être refusé, laissant un compte AO
             *    sans avion (et sans message d'erreur).
             */
            if ($model->password === '') {
                $addError(1, 'Password is required.');
            } elseif (mb_strlen($model->password) < 6) {
                $addError(1, 'Password must contain at least 6 characters.');
            } elseif ($model->password !== $model->confirm_password) {
                $addError(1, 'Passwords do not match.');
            }

            $accountFields = ['username', 'email'];
            $profileFields = ['username', 'email', 'first_name', 'last_name', 'contact_number', 'company_name', 'country_id', 'city_id', 'address', 'zip_code'];
            if (!$model->validate($profileFields)) {
                foreach ($model->getFirstErrors() as $attribute => $message) {
                    $addError(in_array($attribute, $accountFields, true) ? 1 : 2, $message);
                }
            }

            // Photo de profil (facultative) : image, 5 Mo au plus ; conservée si valide.
            $photo = UploadedFile::getInstance($model, 'profile_photo');
            $keptFlags = (array) ($post['kept_files'] ?? []);
            if ($photo) {
                $problem = $this->checkMroSignupFile($photo, 'image');
                if ($problem !== null) {
                    $addError(2, 'Profile photo ' . $problem);
                } else {
                    try {
                        $kept['profile_photo'] = $this->keepMroSignupFile($photo, 'profile_photo', $kept['profile_photo'] ?? null, 'ao');
                    } catch (\Throwable $e) {
                        Yii::error('AO signup temporary upload failed: ' . $e->getMessage(), __METHOD__);
                        $addError(2, 'Profile photo could not be uploaded. Please try again.');
                    }
                }
            } elseif (isset($kept['profile_photo']) && empty($keptFlags['profile_photo'])) {
                $this->deleteMroSignupFile($kept['profile_photo'], 'ao');
                unset($kept['profile_photo']);
            }
            if (isset($kept['profile_photo']) && !is_file($kept['profile_photo']['path'])) {
                unset($kept['profile_photo']);
            }
            $session->set($keptKey, $kept);

            // Étape 3 : avion
            if (!$airplaneModel->validate(['manufacturer', 'model', 'serial_number', 'registration_number', 'certificate_type_id'])) {
                foreach ($airplaneModel->getFirstErrors() as $message) {
                    $addError(3, $message);
                }
            }
            $airplaneModel->aircraft_model_id = AircraftModel::find()
                ->select('aircraft_model_id')
                ->where(['model' => $airplaneModel->model, 'manufacturer' => $airplaneModel->manufacturer])
                ->scalar() ?: null;
            if ($airplaneModel->model !== '' && $airplaneModel->aircraft_model_id === null) {
                $addError(3, 'Please select a valid aircraft model.');
            }
            if ((int) $airplaneModel->certificate_type_id > 0
                && !\app\models\CertificateTypes::find()->where(['certificate_type_id' => (int) $airplaneModel->certificate_type_id])->exists()) {
                $addError(3, 'Please select a valid certificate type.');
            }

            // Étape 4 : conditions
            if (empty($post['terms'])) {
                $addError(4, 'You must agree to the terms and conditions.');
            }

            /*
             * 2. ENREGISTREMENT ATOMIQUE : profil et avion ensemble ; au moindre
             *    échec, rien n'est gardé en base et la photo copiée est supprimée.
             */
            if ($formErrors === []) {
                $storedPhoto = null;
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    if (isset($kept['profile_photo'])) {
                        $storedPhoto = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . $kept['profile_photo']['ext'];
                        if (!copy($kept['profile_photo']['path'], $storedPhoto)) {
                            throw new \RuntimeException('Profile photo copy failed.');
                        }
                    }
                    $model->profile_photo = $storedPhoto;
                    $model->password = Yii::$app->security->generatePasswordHash($model->password);
                    $model->verification_token = Yii::$app->security->generateRandomString() . '_' . time();

                    // Champs déjà validés ci-dessus (la règle « image » ne s'applique pas à un chemin).
                    if (!$model->save(false)) {
                        throw new \RuntimeException('AO profile could not be saved.');
                    }

                    // Préférences « tout activé » : créées par AoProfile::afterSave (sans doublon).
                    \app\models\AoNotificationsPreferences::ensureDefaultsFor((int) $model->ao_id);

                    $airplaneModel->ao_id = $model->ao_id;
                    if (!$airplaneModel->save()) {
                        throw new \RuntimeException('Aircraft: ' . implode(' ', $airplaneModel->getFirstErrors()));
                    }

                    $transaction->commit();
                    $this->discardMroSignupFiles($kept, 'ao');
                    $kept = [];
                } catch (\Throwable $e) {
                    $transaction->rollBack();
                    if ($storedPhoto !== null && is_file($storedPhoto)) {
                        @unlink($storedPhoto);
                    }
                    Yii::error('AO signup failed: ' . $e->getMessage(), __METHOD__);

                    $model->isNewRecord = true;
                    $model->ao_id = null;
                    $model->password = $model->confirm_password;
                    $model->profile_photo = null;
                    $airplaneModel->isNewRecord = true;
                    $airplaneModel->ao_id = null;
                    $addError(4, 'Your application could not be saved. Please check your information and try again.');
                }

                if ($formErrors === []) {
                    try {
                        $isSent = $this->sendAoVerificationEmail($model);
                    } catch (InvalidConfigException $e) {
                        Yii::error($e, 'mail.configuration');
                        $isSent = false;
                    }

                    if ($isSent) {
                        Yii::$app->session->setFlash('success', 'Aircraft operator profile created successfully. A verification email has been sent.');
                    } else {
                        Yii::$app->session->setFlash(
                            'warning',
                            'Aircraft operator profile created successfully, but the verification email could not be sent. Please try again later or contact support.'
                        );
                    }

                    return $this->redirect(Yii::$app->request->baseUrl . '/'); // racine du site
                }
            }
        }

        return $this->render('become-ao', [
            'model' => $model,
            'countries' => $countries,
            'airplaneModel' => $airplaneModel,
            'terms' => $termsContent,
            'manufacturers' => $manufacturers,
            'models' => $models,
            'formErrors' => $formErrors,
            'errorStep' => $errorStep,
            'keptFiles' => array_map(static function (array $meta): array {
                return ['name' => $meta['name'], 'size' => $meta['size']];
            }, $kept),
        ]);
    }

    /**
     * E-mail de vérification envoyé après l'inscription AO (contenu inchangé).
     *
     * @throws InvalidConfigException si le mailer est mal configuré
     */
    private function sendAoVerificationEmail(AoProfile $model): bool
    {
        $verificationLink = Yii::$app->urlManager->createAbsoluteUrl(['site/verify-emailao', 'token' => $model->verification_token]);

        $fullName = trim(($model->first_name ?? '') . ' ' . ($model->last_name ?? ''));
        if ($fullName === '') {
            $fullName = $model->username ?: ($model->email ?: 'User');
        }

        $appName  = htmlspecialchars(Yii::$app->name, ENT_QUOTES, 'UTF-8');
        $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
        $safeLink = htmlspecialchars($verificationLink, ENT_QUOTES, 'UTF-8');

$htmlBody = "
<div style='margin:0; padding:0; background:#f4f6f8;'>
  <div style='max-width:640px; margin:0 auto; padding:24px; font-family:Arial, Helvetica, sans-serif; color:#1f2937;'>
    <div style='background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;'>

      <!-- En-tête -->
      <div style='padding:18px 22px; background:#0b1a2e; color:#ffffff;'>
        <div style='font-size:16px; font-weight:700; letter-spacing:.2px;'>
          {$appName}
        </div>
        <div style='font-size:13px; opacity:.9; margin-top:4px;'>
          Email verification
        </div>
      </div>

      <!-- Corps du mail -->
      <div style='padding:22px;'>
        <h2 style='margin:0 0 12px; font-size:20px; line-height:1.3; color:#111827;'>
          Verify your email address
        </h2>

        <p style='margin:0 0 12px; font-size:14px; line-height:1.7;'>
          Hello <strong>{$safeName}</strong>,
        </p>

        <p style='margin:0 0 12px; font-size:14px; line-height:1.7;'>
          Welcome to <strong>{$appName}</strong>! We're excited to have you on board.
        </p>

        <p style='margin:0 0 16px; font-size:14px; line-height:1.7;'>
          Please verify your email address to activate your account and get started:
        </p>

        <!-- Bouton d'action -->
        <div style='margin:18px 0;'>
          <a href='{$safeLink}'
             style='display:inline-block; background:#0b5ed7; color:#ffffff; text-decoration:none; padding:12px 18px; border-radius:8px; font-size:14px; font-weight:700;'>
            Verify my email address
          </a>
        </div>

        <p style='margin:0 0 10px; font-size:13px; line-height:1.7; color:#374151;'>
          If the button doesn't work, copy and paste this link into your browser:
        </p>

        <p style='margin:0 0 16px; font-size:13px; line-height:1.7; word-break:break-all;'>
          <a href='{$safeLink}' style='color:#0b5ed7; text-decoration:underline;'>
            {$safeLink}
          </a>
        </p>

        <div style='padding:12px 14px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px;'>
          <p style='margin:0; font-size:13px; line-height:1.7; color:#374151;'>
            For your security, this link will expire after <strong>24 hours</strong>.
          </p>
          <p style='margin:6px 0 0; font-size:13px; line-height:1.7; color:#374151;'>
            If you didn't create an account on {$appName}, you can safely ignore this email.
          </p>
        </div>

        <p style='margin:18px 0 0; font-size:14px; line-height:1.7;'>
          Best regards,<br>
          <strong>{$appName} Team</strong>
        </p>
      </div>

      <!-- Pied de page -->
      <div style='padding:14px 22px; background:#ffffff; border-top:1px solid #e5e7eb;'>
        <p style='margin:0; font-size:12px; line-height:1.6; color:#6b7280;'>
          This is an automated message. Please do not reply.
        </p>
      </div>

    </div>
  </div>
</div>
";

        // send() renvoie déjà true/false.
        return (bool) Yii::$app->mailer->compose()
            ->setTo($model->email)
            ->setFrom([
                Yii::$app->params['supportEmail'] ?? 'no-reply@coreaviationnetwork.com' => Yii::$app->name,
            ])
            ->setSubject('Verify your email address - ' . Yii::$app->name)
            ->setHtmlBody($htmlBody)
            ->send();
    }

    public function actionBecomeMro()
    {
        if (PrelaunchMode::isEnabled()) {
            return $this->redirectToPrelaunchRegistration();
        }

        // Écran d'inscription autonome : layout « auth », sans header ni footer du site.
        $this->layout = 'auth';

        $model = new MroProfile();
        $termsContent = Terms::find()->one()->content;
        $certificate = new Certificates();
        $mroairport = new MroprofileAirport();
        $mroaircraftcertificate = new MroAircraftCertificate();

        /*
         * PERFORMANCE DU FORMULAIRE : seule la liste des pays est nécessaire au
         * premier affichage. Les villes sont ensuite demandées par AJAX lorsque
         * l'utilisateur choisit un pays.
         */
        $countries = Countries::find()->orderBy('country_name')->all();

        /*
         * RETOUR D'ERREUR SANS PERTE DE SAISIE (2026-11-22) : avant, chaque erreur
         * faisait un refresh() et l'utilisateur perdait tout le formulaire. Les
         * erreurs sont maintenant collectées puis le formulaire est réaffiché avec
         * les valeurs saisies, ouvert sur la première étape à corriger.
         */
        $formErrors = [];
        $errorStep = null;
        $addError = static function (int $step, string $message) use (&$formErrors, &$errorStep): void {
            $formErrors[] = $message;
            $errorStep = $errorStep === null ? $step : min($errorStep, $step);
        };

        /*
         * PIÈCES JOINTES CONSERVÉES APRÈS ERREUR (2026-11-22) : un navigateur ne peut
         * pas re-remplir un champ fichier. Chaque fichier valide reçu est donc gardé
         * temporairement dans @runtime/mro-signup/<jeton> (hors du dossier web), lié à
         * la session. Le formulaire réaffiché le propose comme « conservé » : il peut
         * être gardé, remplacé ou retiré. Une nouvelle visite (GET) repart de zéro.
         */
        $session = Yii::$app->session;
        $kept = (array) $session->get(self::MRO_SIGNUP_KEPT_FILES, []);
        if (!Yii::$app->request->isPost) {
            $this->discardMroSignupFiles($kept);
            $kept = [];
        }

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            $post = Yii::$app->request->post();
            $certificate->load($post);
            $mroaircraftcertificate->load($post);
            $mroairport->load($post);
            $mroaircraftcertificate->manufacturer = $post['MroAircraftCertificate']['manufacturer'] ?? null;
            $certificate->type = $post['Certificates']['type'] ?? $certificate->type;
            $certificate->certificate_type_id = $post['Certificates']['certificate_type_id'] ?? $certificate->certificate_type_id;

            $model->password = trim((string) $model->password);
            $model->confirm_password = trim((string) $model->confirm_password);

            $files = [
                'profile_photo' => UploadedFile::getInstance($model, 'profile_photo'),
                'company_photo' => UploadedFile::getInstance($model, 'company_photo'),
                'insurance' => UploadedFile::getInstance($model, 'insurance_document'),
                'naa' => UploadedFile::getInstance($certificate, 'certificate'),
                'capability' => UploadedFile::getInstance($mroaircraftcertificate, 'certificate'),
            ];

            /*
             * 1. VALIDATION COMPLÈTE AVANT TOUT ENREGISTREMENT : avant, le profil
             *    était créé puis les certificats ou l'aéroport pouvaient échouer,
             *    laissant un compte MRO incomplet en base.
             */
            // Étape 1 : compte
            if ($model->password === '') {
                $addError(1, 'Password is required.');
            } elseif (mb_strlen($model->password) < 6) {
                $addError(1, 'Password must contain at least 6 characters.');
            } elseif ($model->password !== $model->confirm_password) {
                $addError(1, 'Passwords do not match.');
            }
            if ($model->username !== null && MroProfile::find()->where(['username' => $model->username])->exists()) {
                $addError(1, 'Username is already taken.');
            }

            // Étapes 1 et 2 : profil (les fichiers sont contrôlés à part)
            $accountFields = ['username', 'email'];
            $profileFields = ['username', 'email', 'first_name', 'last_name', 'contact_number', 'company_name', 'country_id', 'city_id', 'address', 'zip_code'];
            if (!$model->validate($profileFields)) {
                foreach ($model->getFirstErrors() as $attribute => $message) {
                    if ($attribute === 'username' && strpos($message, 'taken') !== false) {
                        continue; // déjà signalé ci-dessus
                    }
                    $addError(in_array($attribute, $accountFields, true) ? 1 : 2, $message);
                }
            }

            // Fichiers : nouveau fichier valide => conservé ; sinon fichier déjà conservé, sauf s'il a été retiré.
            $fileRules = [
                'profile_photo' => ['label' => 'Profile photo', 'step' => 2, 'type' => 'image', 'required' => false],
                'company_photo' => ['label' => 'Company / hangar photo', 'step' => 2, 'type' => 'image', 'required' => false],
                'naa' => ['label' => 'NAA approval certificate', 'step' => 3, 'type' => 'pdf', 'required' => true],
                'capability' => ['label' => 'Aircraft capability certificate', 'step' => 3, 'type' => 'pdf', 'required' => true],
                'insurance' => ['label' => 'Insurance document', 'step' => 3, 'type' => 'pdf', 'required' => true],
            ];
            $keptFlags = (array) ($post['kept_files'] ?? []);
            foreach ($fileRules as $slot => $rule) {
                $file = $files[$slot];
                if ($file) {
                    $problem = $this->checkMroSignupFile($file, $rule['type']);
                    if ($problem !== null) {
                        $addError($rule['step'], $rule['label'] . ' ' . $problem);
                        continue;
                    }
                    try {
                        $kept[$slot] = $this->keepMroSignupFile($file, $slot, $kept[$slot] ?? null);
                    } catch (\Throwable $e) {
                        Yii::error('MRO signup temporary upload failed: ' . $e->getMessage(), __METHOD__);
                        $addError($rule['step'], $rule['label'] . ' could not be uploaded. Please try again.');
                        continue;
                    }
                } elseif (isset($kept[$slot]) && empty($keptFlags[$slot])) {
                    // Retiré par l'utilisateur.
                    $this->deleteMroSignupFile($kept[$slot]);
                    unset($kept[$slot]);
                }

                if (isset($kept[$slot]) && !is_file($kept[$slot]['path'])) {
                    unset($kept[$slot]);
                }
                if ($rule['required'] && !isset($kept[$slot])) {
                    $addError($rule['step'], $rule['label'] . ' is required.');
                }
            }
            $session->set(self::MRO_SIGNUP_KEPT_FILES, $kept);

            // Étape 3 : agrément NAA et capacité avion
            if (trim((string) $certificate->type) === '' || (int) $certificate->certificate_type_id <= 0) {
                $addError(3, 'NAA authority is required.');
            }
            if ((int) $mroaircraftcertificate->aircraft_model_id <= 0) {
                $addError(3, 'Aircraft model is required.');
            } elseif (!$mroaircraftcertificate->validate(['aircraft_model_id'])) {
                $addError(3, 'Please select a valid aircraft model.');
            }

            // Étape 4 : aéroport et conditions
            if ((int) $mroairport->airport_id <= 0) {
                $addError(4, 'Airport / ICAO base is required.');
            } elseif (!$mroairport->validate(['airport_id'])) {
                $addError(4, 'Please select a valid airport.');
            }
            if (empty($post['terms'])) {
                $addError(4, 'You must agree to the partnership terms.');
            }

            /*
             * 2. ENREGISTREMENT ATOMIQUE : profil, documents, capacité et aéroport
             *    sont enregistrés ensemble. Au moindre échec, la base est remise en
             *    l'état et les fichiers déjà copiés sont supprimés.
             */
            if ($formErrors === []) {
                $storedFiles = [];
                // Copie (et non déplacement) : en cas d'échec, les pièces restent disponibles pour un nouvel essai.
                $storeFile = static function (string $slot) use (&$storedFiles, $kept): string {
                    $path = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . $kept[$slot]['ext'];
                    if (!copy($kept[$slot]['path'], $path)) {
                        throw new \RuntimeException('File copy failed.');
                    }
                    $storedFiles[] = $path;
                    return $path;
                };

                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $model->profile_photo = isset($kept['profile_photo']) ? $storeFile('profile_photo') : null;
                    $model->company_photo = isset($kept['company_photo']) ? $storeFile('company_photo') : null;
                    $model->password = Yii::$app->security->generatePasswordHash($model->password);
                    $model->verification_token = Yii::$app->security->generateRandomString() . '_' . time();

                    if (!$model->save()) {
                        throw new \RuntimeException('MRO profile: ' . implode(' ', $model->getFirstErrors()));
                    }
                    $mroId = (int) $model->mro_id;

                    // Préférences « tout activé » : créées par MroProfile::afterSave (sans doublon).
                    MroNotificationsPreferences::ensureDefaultsFor($mroId);

                    $insuranceDoc = new MroInsuranceDocuments();
                    $insuranceDoc->mro_id = $mroId;
                    $insuranceDoc->file_path = $storeFile('insurance');
                    $insuranceDoc->file_name = $kept['insurance']['name'];
                    $insuranceDoc->file_type = $kept['insurance']['type'];
                    $insuranceDoc->file_size = $kept['insurance']['size'];
                    if (!$insuranceDoc->save()) {
                        throw new \RuntimeException('Insurance document: ' . implode(' ', $insuranceDoc->getFirstErrors()));
                    }

                    $mroaircraftcertificate->mro_id = $mroId;
                    $mroaircraftcertificate->certificate = $storeFile('capability');
                    if (!$mroaircraftcertificate->save()) {
                        throw new \RuntimeException('Aircraft capability: ' . implode(' ', $mroaircraftcertificate->getFirstErrors()));
                    }

                    $certificate->mro_id = $mroId;
                    $certificate->certificate = $storeFile('naa');
                    if (!$certificate->save(false)) {
                        throw new \RuntimeException('NAA certificate could not be saved.');
                    }

                    $mroairport->mro_id = $mroId;
                    if (!$mroairport->save()) {
                        throw new \RuntimeException('Working airport: ' . implode(' ', $mroairport->getFirstErrors()));
                    }

                    $transaction->commit();

                    // Inscription enregistrée : les copies temporaires ne servent plus.
                    $this->discardMroSignupFiles($kept);
                    $kept = [];
                } catch (\Throwable $e) {
                    $transaction->rollBack();
                    foreach ($storedFiles as $path) {
                        if (is_file($path)) {
                            @unlink($path);
                        }
                    }
                    Yii::error('MRO signup failed: ' . $e->getMessage(), __METHOD__);

                    $model->isNewRecord = true;
                    $model->mro_id = null;
                    $model->password = $model->confirm_password;
                    $model->profile_photo = null;
                    $model->company_photo = null;
                    $addError(4, 'Your application could not be saved. Please check your information and try again.');
                }

                if ($formErrors === []) {
                    $isSent = false;
                    try {
                        $isSent = $this->sendMroVerificationEmail($model);
                    } catch (InvalidConfigException $e) {
                        /*
                         * Une erreur de configuration du mailer ne remet pas en cause
                         * les données déjà sauvegardées. Le détail technique reste hors
                         * de l'interface publique et l'utilisateur reçoit un avertissement.
                         */
                        Yii::error($e, 'mail.configuration');
                        Yii::$app->session->setFlash(
                            'warning',
                            'MRO profile created successfully, but the verification email service is currently unavailable. Please contact support.'
                        );
                        return $this->redirect(Yii::$app->request->baseUrl . '/'); // racine du site (http://localhost:8080/)
                    }

                    /*
                     * RETOUR UTILISATEUR COHÉRENT : le profil MRO et ses documents sont
                     * enregistrés ; un échec SMTP n'est pas un échec d'inscription.
                     */
                    if ($isSent) {
                        Yii::$app->session->setFlash('success', 'MRO profile created successfully. A verification email has been sent.');
                    } else {
                        Yii::$app->session->setFlash(
                            'warning',
                            'MRO profile created successfully, but the verification email could not be sent. Please try again later or contact support.'
                        );
                    }

                    return $this->redirect(Yii::$app->request->baseUrl . '/'); // racine du site (http://localhost:8080/)
                }
            }
        }

        return $this->render('become-mro', [
            'mroairport' => $mroairport,
            'countries' => $countries,
            'certificate' => $certificate,
            'model' => $model,
            'terms' => $termsContent,
            'mroaircraftcertificate' => $mroaircraftcertificate,
            'formErrors' => $formErrors,
            'errorStep' => $errorStep,
            'keptFiles' => array_map(static function (array $meta): array {
                return ['name' => $meta['name'], 'size' => $meta['size']];
            }, $kept),
        ]);
    }

    /** Clé de session des pièces jointes conservées pendant l'inscription MRO. */
    private const MRO_SIGNUP_KEPT_FILES = 'mroSignupKeptFiles';

    /**
     * Contrôle d'une pièce jointe d'inscription MRO : PDF (documents) ou image
     * (photos), 5 Mo au plus. Retourne le message d'erreur, ou null si valide.
     */
    private function checkMroSignupFile(UploadedFile $file, string $type): ?string
    {
        if ($file->hasError) {
            return 'could not be uploaded.';
        }
        $extension = strtolower((string) $file->extension);
        if ($type === 'pdf' && $extension !== 'pdf') {
            return 'must be a PDF file.';
        }
        if ($type === 'image' && !in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return 'must be a JPG, PNG, GIF or WEBP image.';
        }
        if ($file->size > 5 * 1024 * 1024) {
            return 'exceeds the 5 MB limit.';
        }

        return null;
    }

    /**
     * Garde une copie temporaire d'une pièce jointe dans @runtime (non public),
     * dans un dossier propre à la session. Remplace la copie précédente du même champ.
     */
    private function keepMroSignupFile(UploadedFile $file, string $slot, ?array $previous, string $scope = 'mro'): array
    {
        $session = Yii::$app->session;
        $tokenKey = $scope . 'SignupKeptToken';
        $token = $session->get($tokenKey);
        if (!is_string($token) || !preg_match('/^[A-Za-z0-9_-]{32}$/', $token)) {
            $token = Yii::$app->security->generateRandomString(32);
            $session->set($tokenKey, $token);
        }

        $directory = Yii::getAlias('@runtime/' . $scope . '-signup/' . $token);
        \yii\helpers\FileHelper::createDirectory($directory, 0700);

        if ($previous !== null) {
            $this->deleteMroSignupFile($previous, $scope);
        }

        $extension = strtolower((string) $file->extension);
        $path = $directory . '/' . $slot . '-' . Yii::$app->security->generateRandomString(12) . '.' . $extension;
        if (!$file->saveAs($path)) {
            throw new \RuntimeException('Temporary copy failed for ' . $slot . '.');
        }

        return [
            'path' => $path,
            'name' => (string) $file->name,
            'size' => (int) $file->size,
            'type' => (string) $file->type,
            'ext' => $extension,
        ];
    }

    /** Supprime une copie temporaire (uniquement sous @runtime/<scope>-signup). */
    private function deleteMroSignupFile(array $meta, string $scope = 'mro'): void
    {
        $root = realpath(Yii::getAlias('@runtime/' . $scope . '-signup'));
        $path = isset($meta['path']) ? realpath((string) $meta['path']) : false;
        if ($root !== false && $path !== false && strpos($path, $root . DIRECTORY_SEPARATOR) === 0 && is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Supprime les copies temporaires de la session et nettoie les dossiers
     * d'inscriptions abandonnées depuis plus de 24 h.
     */
    private function discardMroSignupFiles(array $kept, string $scope = 'mro'): void
    {
        foreach ($kept as $meta) {
            $this->deleteMroSignupFile((array) $meta, $scope);
        }
        Yii::$app->session->remove($scope . 'SignupKeptFiles');

        $root = Yii::getAlias('@runtime/' . $scope . '-signup');
        if (!is_dir($root)) {
            return;
        }
        foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $files = glob($directory . '/*') ?: [];
            $abandoned = filemtime($directory) < time() - 86400;
            if ($files === [] || $abandoned) {
                \yii\helpers\FileHelper::removeDirectory($directory);
            }
        }
    }

    /**
     * E-mail de vérification envoyé après l'inscription MRO (contenu inchangé).
     *
     * @throws InvalidConfigException si le mailer est mal configuré
     */
    private function sendMroVerificationEmail(MroProfile $model): bool
    {
        $verificationLink = Yii::$app->urlManager->createAbsoluteUrl(['site/verify-email', 'token' => $model->verification_token]);

        $fullName = trim(($model->first_name ?? '') . ' ' . ($model->last_name ?? ''));
        if ($fullName === '') {
            $fullName = $model->username ?: ($model->email ?: 'User');
        }

        $appName  = htmlspecialchars(Yii::$app->name, ENT_QUOTES, 'UTF-8');
        $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
        $safeLink = htmlspecialchars($verificationLink, ENT_QUOTES, 'UTF-8');

$htmlBody = "
<div style='margin:0; padding:0; background:#f4f6f8;'>
  <div style='max-width:640px; margin:0 auto; padding:24px; font-family:Arial, Helvetica, sans-serif; color:#1f2937;'>
    <div style='background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;'>

      <!-- En-tête -->
      <div style='padding:18px 22px; background:#0b1a2e; color:#ffffff;'>
        <div style='font-size:16px; font-weight:700; letter-spacing:.2px;'>
          {$appName}
        </div>
        <div style='font-size:13px; opacity:.9; margin-top:4px;'>
          Email verification
        </div>
      </div>

      <!-- Corps du mail -->
      <div style='padding:22px;'>
        <h2 style='margin:0 0 12px; font-size:20px; line-height:1.3; color:#111827;'>
          Verify your email address
        </h2>

        <p style='margin:0 0 12px; font-size:14px; line-height:1.7;'>
          Hello <strong>{$safeName}</strong>,
        </p>

        <p style='margin:0 0 12px; font-size:14px; line-height:1.7;'>
          Welcome to <strong>{$appName}</strong>! We're excited to have you on board.
        </p>

        <p style='margin:0 0 16px; font-size:14px; line-height:1.7;'>
          Please verify your email address to activate your account and get started:
        </p>

        <!-- Bouton d'action -->
        <div style='margin:18px 0;'>
          <a href='{$safeLink}'
             style='display:inline-block; background:#0b5ed7; color:#ffffff; text-decoration:none; padding:12px 18px; border-radius:8px; font-size:14px; font-weight:700;'>
            Verify my email address
          </a>
        </div>

        <p style='margin:0 0 10px; font-size:13px; line-height:1.7; color:#374151;'>
          If the button doesn't work, copy and paste this link into your browser:
        </p>

        <p style='margin:0 0 16px; font-size:13px; line-height:1.7; word-break:break-all;'>
          <a href='{$safeLink}' style='color:#0b5ed7; text-decoration:underline;'>
            {$safeLink}
          </a>
        </p>

        <div style='padding:12px 14px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px;'>
          <p style='margin:0; font-size:13px; line-height:1.7; color:#374151;'>
            For your security, this link will expire after <strong>24 hours</strong>.
          </p>
          <p style='margin:6px 0 0; font-size:13px; line-height:1.7; color:#374151;'>
            If you didn't create an account on {$appName}, you can safely ignore this email.
          </p>
        </div>

        <p style='margin:18px 0 0; font-size:14px; line-height:1.7;'>
          Best regards,<br>
          <strong>{$appName} Team</strong>
        </p>
      </div>

      <!-- Pied de page -->
      <div style='padding:14px 22px; background:#ffffff; border-top:1px solid #e5e7eb;'>
        <p style='margin:0; font-size:12px; line-height:1.6; color:#6b7280;'>
          This is an automated message. Please do not reply.
        </p>
      </div>

    </div>
  </div>
</div>
";

        // send() renvoie déjà true/false.
        return (bool) Yii::$app->mailer->compose()
            ->setTo($model->email)
            ->setFrom([
                Yii::$app->params['supportEmail'] ?? 'no-reply@coreaviationnetwork.com' => Yii::$app->name,
            ])
            ->setSubject('Verify your email address - ' . Yii::$app->name)
            ->setHtmlBody($htmlBody)
            ->send();
    }

    public function actionCitiesByCountry($country_id)
{
    $cities = cities::find()->where(['country_id' => $country_id])->all();
    return $this->renderPartial('_cities_dropdown', ['cities' => $cities]);
}



//get client ip location in json format
public function actionClientLocation()
{
    Yii::$app->response->format = Response::FORMAT_JSON;

    $fallback = [
        'success' => false,
        'city' => null,
        'country' => null,
        'timezone' => null,
        'provider' => null,
        'error' => null,
    ];

    /*
     * Multiple providers are used because one service can fail,
     * block localhost, or be temporarily unavailable.
     */
    $providers = [
        [
            'name' => 'ipapi',
            'url' => 'https://ipapi.co/json/',
            'map' => function ($data) {
                return [
                    'city' => $data['city'] ?? null,
                    'country' => $data['country_name'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                ];
            },
        ],
        [
            'name' => 'ipwhois',
            'url' => 'https://ipwho.is/',
            'map' => function ($data) {
                return [
                    'city' => $data['city'] ?? null,
                    'country' => $data['country'] ?? null,
                    'timezone' => $data['timezone']['id'] ?? null,
                ];
            },
        ],
        [
            'name' => 'ip-api',
            'url' => 'http://ip-api.com/json/?fields=status,message,country,city,timezone',
            'map' => function ($data) {
                return [
                    'city' => $data['city'] ?? null,
                    'country' => $data['country'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                ];
            },
        ],
    ];

    $lastError = null;

    foreach ($providers as $provider) {
        $response = $this->fetchLocationProvider($provider['url'], $lastError);

        if ($response === null) {
            continue;
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            $lastError = 'Invalid JSON from ' . $provider['name'];
            continue;
        }

        $mapped = $provider['map']($data);

        if (!empty($mapped['city']) || !empty($mapped['country']) || !empty($mapped['timezone'])) {
            return [
                'success' => true,
                'city' => $mapped['city'],
                'country' => $mapped['country'],
                'timezone' => $mapped['timezone'],
                'provider' => $provider['name'],
                'error' => null,
            ];
        }

        $lastError = 'No location data from ' . $provider['name'];
    }

    $fallback['error'] = YII_DEBUG ? $lastError : null;

    return $fallback;
}

/**
 * Fetch external location provider.
 * Uses cURL first, then file_get_contents as fallback.
 */
private function fetchLocationProvider($url, &$lastError = null)
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false, // Useful on local XAMPP/WAMP SSL issues
            CURLOPT_SSL_VERIFYHOST => false, // Useful on local XAMPP/WAMP SSL issues
            CURLOPT_USERAGENT => 'CAN-Dashboard/1.0',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        $body = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($body !== false && $httpCode >= 200 && $httpCode < 300) {
            return $body;
        }

        $lastError = 'cURL failed for ' . $url . ' | HTTP: ' . $httpCode . ' | Error: ' . $curlError;
    }

    if (ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 8,
                'header' => "Accept: application/json\r\nUser-Agent: CAN-Dashboard/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);

        if ($body !== false) {
            return $body;
        }

        $lastError = 'file_get_contents failed for ' . $url;
    } else {
        $lastError = 'cURL is disabled and allow_url_fopen is disabled.';
    }

    return null;
}


public function actionVerifyEmail($token)
{
    /*
     * SÉCURISATION DU LIEN MRO : le client de messagerie peut encoder le paramètre
     * ou ajouter des espaces lors d'un copier-coller. On normalise cette valeur,
     * puis on applique réellement la durée de validité de 24 heures annoncée dans
     * l'e-mail. Un jeton expiré ou mal formé n'est jamais utilisé pour une recherche.
     */
    $token = $this->normalizeEmailVerificationToken($token);
    $tokenState = $this->getEmailVerificationTokenState($token);

    if ($tokenState !== 'valid') {
        Yii::$app->session->setFlash(
            $tokenState === 'expired' ? 'warning' : 'error',
            $tokenState === 'expired'
                ? 'This verification link has expired. Please request a new verification email.'
                : 'This verification link is invalid or is no longer associated with an existing account.'
        );

        return $this->redirect(['site/login']);
    }

    $model = MroProfile::findOne(['verification_token' => $token]);
    if ($model) {
        /*
         * IDEMPOTENCE : un second clic sur le même lien ne produit ni erreur ni
         * nouvelle écriture inutile. Lors du premier clic, seule la colonne de
         * vérification est mise à jour afin de préserver toutes les autres données.
         */
        if ((int) $model->email_verified === 1) {
            Yii::$app->session->setFlash('info', 'Your email address is already verified. You can sign in.');
        } else {
            $model->email_verified = 1;
            $model->save(false, ['email_verified']);
            Yii::$app->session->setFlash('success', 'Your email has been verified successfully.');
        }
    } else {
        Yii::$app->session->setFlash(
            'error',
            'This verification link is no longer associated with an existing MRO account. The account may have been removed; please register again or contact support.'
        );
    }

    return $this->redirect(['site/login']);
}

public function actionVerifyEmailao($token)
{
    /*
     * MÊME CONTRÔLE POUR LES OPÉRATEURS : les deux parcours d'inscription doivent
     * traiter de manière identique l'encodage, l'expiration et les anciens liens.
     */
    $token = $this->normalizeEmailVerificationToken($token);
    $tokenState = $this->getEmailVerificationTokenState($token);

    if ($tokenState !== 'valid') {
        Yii::$app->session->setFlash(
            $tokenState === 'expired' ? 'warning' : 'error',
            $tokenState === 'expired'
                ? 'This verification link has expired. Please request a new verification email.'
                : 'This verification link is invalid or is no longer associated with an existing account.'
        );

        return $this->redirect(['site/login']);
    }

    $model = AoProfile::findOne(['verification_token' => $token]);
    if ($model) {
        if ((int) $model->email_verified === 1) {
            Yii::$app->session->setFlash('info', 'Your email address is already verified. You can sign in.');
        } else {
            $model->email_verified = 1;
            $model->save(false, ['email_verified']);
            Yii::$app->session->setFlash('success', 'Your email has been verified successfully.');
        }
    } else {
        Yii::$app->session->setFlash(
            'error',
            'This verification link is no longer associated with an existing operator account. The account may have been removed; please register again or contact support.'
        );
    }

    return $this->redirect(['site/login']);
}

/**
 * Normalise un jeton provenant d'un bouton HTML ou d'un copier-coller.
 * Aucun caractère n'est inventé : seuls l'encodage URL/HTML et les espaces
 * extérieurs sont retirés avant la comparaison exacte avec la base de données.
 */
private function normalizeEmailVerificationToken($token): string
{
    return trim(html_entity_decode(rawurldecode((string) $token), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

/**
 * Retourne l'état temporel et structurel du jeton sans consulter un compte.
 * Le suffixe généré après le dernier « _ » contient l'heure Unix de création ;
 * une petite tolérance de cinq minutes couvre un éventuel décalage d'horloge.
 */
private function getEmailVerificationTokenState(string $token): string
{
    $separatorPosition = strrpos($token, '_');
    if ($separatorPosition === false) {
        return 'invalid';
    }

    $timestamp = substr($token, $separatorPosition + 1);
    if ($timestamp === '' || !ctype_digit($timestamp)) {
        return 'invalid';
    }

    $issuedAt = (int) $timestamp;
    if ($issuedAt > time() + 300) {
        return 'invalid';
    }

    return $issuedAt < time() - 86400 ? 'expired' : 'valid';
}

public function actionHome()
{
    return $this->render('home', [
        'prelaunchSubscriber' => $this->newPrelaunchSubscriber(),
    ]);
}

    
public function actionLogin()
{
    // if (!Yii::$app->user->isGuest) {
    //     return $this->goHome();
    // }

$this->layout = 'login';
    if (!Yii::$app->user->isGuest) {
        return $this->goHome();
    }

    $model = new LoginForm();
    $loginSubmitted = $model->load(Yii::$app->request->post());
    $normalizedUsername = mb_strtolower(trim((string) $model->username), 'UTF-8');
    $loginRateKey = 'login-rate:' . hash('sha256', $normalizedUsername . '|' . Yii::$app->request->userIP);
    $cachedRateState = $loginSubmitted ? Yii::$app->cache->get($loginRateKey) : null;
    $loginRateState = is_array($cachedRateState)
        ? $cachedRateState
        : ['failures' => (int) $cachedRateState, 'nextAllowedAt' => 0];
    $loginFailures = (int) ($loginRateState['failures'] ?? 0);
    $retryAfter = max(0, (int) ($loginRateState['nextAllowedAt'] ?? 0) - time());
    $captchaRequired = $loginSubmitted && $loginFailures >= 3;
    $loginRateLimited = $loginSubmitted && $loginFailures >= 10;
    $restrictedAccountHandled = false;
    $captchaError = false;

    /*
     * Temporisation exponentielle non bloquante : aucun sleep() ne monopolise
     * un worker PHP. Le prochain POST est refuse jusqu'a nextAllowedAt.
     */
    $registerLoginFailure = static function () use ($loginRateKey, $loginFailures, &$captchaRequired) {
        $newFailureCount = $loginFailures + 1;
        $captchaRequired = $newFailureCount >= 3;
        $delaySeconds = min(60, 2 ** min(6, max(0, $newFailureCount - 1)));
        Yii::$app->cache->set($loginRateKey, [
            'failures' => $newFailureCount,
            'nextAllowedAt' => time() + $delaySeconds,
        ], 900);
    };

    $captchaPassed = true;
    if ($loginRateLimited) {
        Yii::$app->response->statusCode = 429;
        Yii::$app->response->headers->set('Retry-After', '900');
        $model->addError('password', 'Too many sign-in attempts. Please try again later.');
    } elseif ($retryAfter > 0) {
        Yii::$app->response->statusCode = 429;
        Yii::$app->response->headers->set('Retry-After', (string) $retryAfter);
        $model->addError('password', "Please wait {$retryAfter} seconds before trying again.");
    } elseif ($captchaRequired) {
        $challenge = $this->validateTurnstileChallenge('login', 'login');
        $captchaPassed = $challenge['valid'];
        if (!$captchaPassed) {
            $captchaError = true;
            Yii::$app->response->statusCode = $challenge['status'];
            $model->addError('password', $challenge['message']);
        }
    }

    if (
        $loginSubmitted
        && !$loginRateLimited
        && $retryAfter === 0
        && $captchaPassed
        && $model->login()
    ) {
        $user = Yii::$app->user->identity;
        if ($user) {
            if ($user->status == 'banned') {
                $registerLoginFailure();
                Yii::$app->auditService->record('LOGIN_FAILED', [
                    'username' => $model->username,
                    'new_values' => ['reason' => 'ACCOUNT_BANNED'],
                ]);
                Yii::$app->user->logout(); // Deconnecter immediatement un compte bloque.
                $restrictedAccountHandled = true;
                $model->addError(
                    'password',
                    'Incorrect username or password.'
                );
            } elseif ($user->getUserType() !== 'admin' && $user->email_verified == 0) {
                $registerLoginFailure();
                Yii::$app->auditService->record('LOGIN_FAILED', [
                    'username' => $model->username,
                    'new_values' => ['reason' => 'EMAIL_NOT_VERIFIED'],
                ]);
                Yii::$app->user->logout(); // Deconnecter un compte qui n'est pas encore verifie.
                $restrictedAccountHandled = true;
                $model->addError(
                    'password',
                    'Incorrect username or password.'
                );
            } else {
                $userType = $user->getUserType();
                if ($userType == 'mro') {
                    Yii::$app->session->set('mro_id', $user->mro_id);
                    Yii::$app->session->set('username', $user->username);
                    Yii::$app->session->set('isLoggedIn', true);
                } elseif ($userType == 'admin') {
                    Yii::$app->session->set('admin_id', $user->admin_id);
                    Yii::$app->session->set('username', $user->username);
                    Yii::$app->session->set('isLoggedIn', true);
                } elseif ($userType == 'ao') {
                    Yii::$app->session->set('ao_id', $user->ao_id);
                    Yii::$app->session->set('username', $user->username);
                    Yii::$app->session->set('isLoggedIn', true);
                }

                Yii::$app->cache->delete($loginRateKey);
                Yii::$app->session->set('auth_version', (int) ($user->auth_version ?? 1));
                Yii::$app->auditService->record('LOGIN');
                return $this->redirect(['dashboard/home']);
            }
        } else {
            Yii::$app->session->setFlash('error', 'Failed to set user identity.');
        }
    }

    if (
        $loginSubmitted
        && Yii::$app->user->isGuest
        && !$restrictedAccountHandled
        && !$loginRateLimited
        && $retryAfter === 0
        && $captchaPassed
    ) {
        if (!$loginRateLimited) {
            $registerLoginFailure();
        }
        /* Le mot de passe tente n'est jamais transmis au service d'audit. */
        Yii::$app->auditService->record('LOGIN_FAILED', [
            'username' => $model->username,
            'new_values' => [
                'reason' => $loginRateLimited ? 'RATE_LIMITED' : 'INVALID_CREDENTIALS',
            ],
        ]);
    }

    $model->password = '';
    return $this->render('login', [
        'model' => $model,
        'captchaRequired' => $captchaRequired,
        'captchaError' => $captchaError,
    ]);
}


    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        /* L'identite doit etre photographiee avant sa suppression de la session. */
        Yii::$app->auditService->record('LOGOUT');
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /** Revokes every active session and persistent identity cookie for this account. */
    public function actionLogoutAllDevices()
    {
        $identity = Yii::$app->user->identity;
        $profileClass = null;
        $profileId = null;

        if ($identity && $identity->admin_id !== null) {
            $profileClass = AdminProfile::class;
            $profileId = (int) $identity->admin_id;
        } elseif ($identity && $identity->mro_id !== null) {
            $profileClass = MroProfile::class;
            $profileId = (int) $identity->mro_id;
        } elseif ($identity && $identity->ao_id !== null) {
            $profileClass = AoProfile::class;
            $profileId = (int) $identity->ao_id;
        }

        if ($profileClass === null || $profileId < 1) {
            throw new BadRequestHttpException('Unable to revoke sessions for this account.');
        }

        $primaryKey = $profileClass::primaryKey()[0];
        $updated = $profileClass::updateAllCounters(
            ['auth_version' => 1],
            [$primaryKey => $profileId]
        );
        if ($updated !== 1) {
            throw new BadRequestHttpException('Unable to revoke sessions for this account.');
        }

        Yii::$app->auditService->record('LOGOUT_ALL_DEVICES');
        Yii::$app->user->logout(true);
        Yii::$app->session->setFlash('success', 'You have been signed out from all devices.');

        return $this->redirect(['site/login']);
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

   
    /**
     * Applies progressive IP throttling, adaptive Turnstile and a per-address
     * delivery cooldown without revealing whether the address exists.
     */
    private function consumeRecoveryRequest($purpose, $email)
    {
        $ipHash = hash('sha256', (string) Yii::$app->request->userIP);
        $rateKey = "recovery-rate:{$purpose}:{$ipHash}";
        $state = Yii::$app->cache->get($rateKey);
        $state = is_array($state) ? $state : ['attempts' => 0, 'nextAllowedAt' => 0];
        $attempts = (int) ($state['attempts'] ?? 0);
        $retryAfter = max(0, (int) ($state['nextAllowedAt'] ?? 0) - time());
        $captchaRequired = $attempts >= 3;

        if ($attempts >= 10) {
            Yii::$app->response->headers->set('Retry-After', '900');
            return [
                'allowed' => false,
                'deliver' => false,
                'captchaRequired' => true,
                'captchaError' => false,
                'status' => 429,
                'message' => 'Too many recovery requests. Please try again later.',
            ];
        }

        if ($retryAfter > 0) {
            Yii::$app->response->headers->set('Retry-After', (string) $retryAfter);
            return [
                'allowed' => false,
                'deliver' => false,
                'captchaRequired' => $captchaRequired,
                'captchaError' => false,
                'status' => 429,
                'message' => "Please wait {$retryAfter} seconds before trying again.",
            ];
        }

        if ($captchaRequired) {
            $challenge = $this->validateTurnstileChallenge(
                $purpose . '_recovery',
                $purpose . ' recovery'
            );
            if (!$challenge['valid']) {
                return [
                    'allowed' => false,
                    'deliver' => false,
                    'captchaRequired' => true,
                    'captchaError' => true,
                    'status' => $challenge['status'],
                    'message' => $challenge['message'],
                ];
            }
        }

        $newAttemptCount = $attempts + 1;
        $delaySeconds = min(60, 2 ** min(6, max(0, $newAttemptCount - 1)));
        Yii::$app->cache->set($rateKey, [
            'attempts' => $newAttemptCount,
            'nextAllowedAt' => time() + $delaySeconds,
        ], 900);

        $normalizedEmail = mb_strtolower(trim($email), 'UTF-8');
        $cooldownKey = 'recovery-mail:' . $purpose . ':' . hash('sha256', $normalizedEmail);
        $deliver = Yii::$app->cache->get($cooldownKey) === false;
        // The same cooldown is recorded for existing and unknown addresses.
        Yii::$app->cache->set($cooldownKey, true, 300);

        return [
            'allowed' => true,
            'deliver' => $deliver,
            'captchaRequired' => $newAttemptCount >= 3,
            'captchaError' => false,
            'status' => 200,
            'message' => '',
        ];
    }

    public function actionRequestPasswordReset()
    {
        $this->layout = 'auth';
        $model = new PasswordResetRequestForm();
        $captchaRequired = false;
        $captchaError = false;
    
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $protection = $this->consumeRecoveryRequest('password', (string) $model->email);
            $captchaRequired = $protection['captchaRequired'];
            $captchaError = $protection['captchaError'];

            if (!$protection['allowed']) {
                Yii::$app->response->statusCode = $protection['status'];
                $model->addError('email', $protection['message']);
            } elseif ($protection['deliver'] && !$model->sendEmail()) {
                Yii::warning('A password reset request could not be delivered.', __METHOD__);
            }

            if ($protection['allowed']) {
                Yii::$app->session->setFlash(
                    'success',
                    'If an eligible account matches those details, password reset instructions will be sent.'
                );
                return $this->redirect(['site/login']);
            }
        }
    
        return $this->render('requestPasswordResetToken', [
            'model' => $model,
            'captchaRequired' => $captchaRequired,
            'captchaError' => $captchaError,
        ]);
    }

    

    
 public function actionResetPassword($token)
    {
         $this->layout = 'auth';
        $aoProfile = AoProfile::findByPasswordResetToken($token);
        $mroProfile = MroProfile::findByPasswordResetToken($token);
        
        if ($aoProfile) {
            $profile = $aoProfile;
        } elseif ($mroProfile) {
            $profile = $mroProfile;
        } else {
            throw new BadRequestHttpException('Invalid token.');
        }
    
        $model = new ResetPasswordForm();
    
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            // Hash the new password
            $hashedPassword = Yii::$app->security->generatePasswordHash($model->password);
            
            if ($profile->resetPassword($hashedPassword)) {
                Yii::$app->session->setFlash('message', 'New password saved.');
                return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', 'Failed to reset password.');
            }
        }
    
        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }


 


 
    public function actionRequestUsernameReset()
    {
        $this->layout = 'auth';
        $model = new UsernameResetRequestForm();
        $captchaRequired = false;
        $captchaError = false;
        
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $protection = $this->consumeRecoveryRequest('username', (string) $model->email);
            $captchaRequired = $protection['captchaRequired'];
            $captchaError = $protection['captchaError'];

            if (!$protection['allowed']) {
                Yii::$app->response->statusCode = $protection['status'];
                $model->addError('email', $protection['message']);
            } elseif ($protection['deliver'] && !$model->sendEmail()) {
                Yii::warning('A username reminder request could not be delivered.', __METHOD__);
            }

            if ($protection['allowed']) {
                Yii::$app->session->setFlash(
                    'success',
                    'If an eligible account matches those details, a username reminder will be sent.'
                );
                return $this->redirect(['site/login']);
            }
        }

        return $this->render('requestUsernameReset', [
            'model' => $model,
            'captchaRequired' => $captchaRequired,
            'captchaError' => $captchaError,
        ]);
    }


    public function actionRequestUsernameReset1()
    {
        return $this->actionRequestUsernameReset();
    }
    public function actionLoadCities()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $countryIds = Yii::$app->request->post('country_ids', []);
        
        if (empty($countryIds)) {
            return ['cities' => []]; // Return an empty list if no country IDs are provided
        }
        
        $cities = Cities::find()
            ->where(['country_id' => $countryIds])
            ->all();
        return ['cities' => ArrayHelper::map($cities, 'city_id', 'city_name')];
    }
    

public function actionLoadAirports()
{
    Yii::$app->response->format = Response::FORMAT_JSON;
    $cityIds = Yii::$app->request->post('city_ids', []);
    $airports = Airports::find()->where(['city_id' => $cityIds])->orderBy('airport_name')->all();
    // Prepare the airports data, combining name and ICAO code in a single string
    $airportData = [];
    foreach ($airports as $airport) {
        $airportData[$airport->airport_id] = $airport->airport_name . " (" . $airport->icao . ")";
    }

    return ['airports' => $airportData];
}
public function actionAirportSearch($q = null) {
    $query = new \yii\db\Query();
    $query->select(['airport_id', 'airport_name', 'icao', 'country_name', 'city_name'])
          ->from('airports')
          ->andFilterWhere(['like', 'airport_name', $q])
          ->orFilterWhere(['like', 'icao', $q])
          ->orFilterWhere(['like', 'country_name', $q])
          ->orFilterWhere(['like', 'city_name', $q])
          ->limit(20);  // Limit the number of results for better performance
    
    $command = $query->createCommand();
    $data = $command->queryAll();

    $out = [];
    foreach ($data as $airport) {
        $out[] = ['id' => $airport['airport_id'], 'text' => "{$airport['airport_name']} ({$airport['icao']}) - {$airport['city_name']}, {$airport['country_name']}"];
    }

    return json_encode(['results' => $out]);
}
public function actionCookiePolicy()
{
    return $this->render('cookie-policy');
}
public function actionTerms()
{
    return $this->render('Terms-and-onditions');
}
public function actionPrivacyPolicy()
{
    return $this->render('privacy-policy');
}

/**
 * Returns aircraft models by manufacturer for the MRO signup AJAX dropdown.
 *
 * @param string|null $manufacturer
 * @return array
 */
public function actionGetModelsByManufacturer($manufacturer = null)
{
    Yii::$app->response->format = Response::FORMAT_JSON;

    $manufacturer = trim((string) $manufacturer);

    if ($manufacturer === '') {
        return [];
    }

    $models = AircraftModel::find()
        ->select(['aircraft_model_id', 'model'])
        ->where(['manufacturer' => $manufacturer])
        ->orderBy(['model' => SORT_ASC])
        ->asArray()
        ->all();

    $result = [];

    foreach ($models as $model) {
        if (empty($model['aircraft_model_id']) || empty($model['model'])) {
            continue;
        }

        $result[] = [
            'id' => $model['aircraft_model_id'],
            'name' => $model['model'],
        ];
    }

    return $result;
}

public function actionGetCities($countryId = null)
{
    Yii::$app->response->format = Response::FORMAT_JSON;

    if (empty($countryId)) {
        return [];
    }

    return Cities::find()
        ->select(['city_id', 'city_name'])
        ->where(['country_id' => $countryId])
        ->orderBy(['city_name' => SORT_ASC])
        ->asArray()
        ->all();
}







}
