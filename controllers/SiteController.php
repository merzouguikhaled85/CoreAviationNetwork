<?php

namespace app\controllers;
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
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
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
        return $this->render('home');
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
        return $this->render('advertising');
    }
    
    public function actionBecomeAo()
    {
        $model = new AoProfile();
        $airplaneModel = new Aircrafts(); // Create an instance of the Airplane model
        $manufacturers = AircraftModel::find()->select('manufacturer')->distinct()->orderBy(['manufacturer' => SORT_ASC])
->all(); // Fetch all distinct manufacturers from the aircraft_model table
        $models = AircraftModel::find()
    ->orderBy([
        'manufacturer' => SORT_ASC,
        'model' => SORT_ASC
    ])
    ->all(); // Fetch all models from the aircraft_model table
        // Fetch countries, cities, and terms from your database or any other source
        $countries = Countries::find()->orderBy('country_name')->all(); // Assuming you have a Country model
        $cities = Cities::find()->orderBy('city_name')->all(); // Assuming you have a City model
        $termsContent = Terms::find()->one()->content; // Assuming you have a Terms model with a content field
        $ao_notifications = new AoNotificationsPreferences();

        // Prepare cities data in JSON format
        $citiesByCountry = [];
        foreach ($cities as $city) {
            $citiesByCountry[$city->country_id][] = $city;
        }
       // $citiesByCountryJson = Json::encode($citiesByCountry);
    $citiesByCountryJson = Json::encode([]); // no more bulk cities

        if ($model->load(Yii::$app->request->post())) {
            // Check if the username is unique
            if (AoProfile::find()->where(['username' => $model->username])->exists()) {
                Yii::$app->session->setFlash('error', 'Username is already taken');
                return $this->refresh(); // Refresh the page to display the flash message
            }
    
            // Get the instance of the uploaded profile photo
            $model->profile_photo = UploadedFile::getInstance($model, 'profile_photo');
    
            // Set the file path for the uploaded profile photo
            if ($model->profile_photo) {
                $uploadPath = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . $model->profile_photo->extension;
                $model->profile_photo->saveAs($uploadPath);
                $model->profile_photo = $uploadPath;
            }
            $model->password = Yii::$app->request->post('AoProfile')['password'];
            $model->confirm_password = Yii::$app->request->post('AoProfile')['confirm_password'];


            // Trim the passwords if they are not null to remove any whitespace
            if ($model->password !== null) {
                $model->password = trim($model->password);
            }
            if ($model->confirm_password !== null) {
                $model->confirm_password = trim($model->confirm_password);
            }
    
            // Check if password and confirm password match
            if ($model->password !== $model->confirm_password) {
                Yii::$app->session->setFlash('error', 'Passwords do not match');
                return $this->refresh(); // Refresh the page to display the flash message
            }
    
            // Validate the model before hashing the password
            if ($model->validate()) {
                // Hash the password before saving
                if (!empty($model->password)) {
                    $model->password = Yii::$app->security->generatePasswordHash($model->password);
                } else {
                    // Handle the case where the password is not provided
                    // For example, you might want to throw an exception or set an error message
                    throw new \InvalidArgumentException('Password cannot be empty.');
                }
                    
                // Generate and save verification token
                $model->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
    
                // Save the model
                if ($model->save()) {
                    $ao_notifications->ao_id= $model->ao_id;
                    $ao_notifications->notify_by_email= 1;
                    $ao_notifications->notify_by_platform= 1;
                    $ao_notifications->notify_mro_replies= 1;
                    $ao_notifications->notify_appointment_requests= 1;
                    $ao_notifications->notify_po_acceptance= 1;
                    $ao_notifications->notify_maintenance_proposals= 1;
                    $ao_notifications->notify_mro_recommendations= '1';
                    $ao_notifications->notify_work_start= 1;
                    $ao_notifications->notify_report_submissions= 1;
                    if (!$ao_notifications->validate()) {
                        VarDumper::dump($ao_notifications->errors);die();
                    }

                    $ao_notifications->save();


                    // Assign AO profile's id to aircraft's ao_id
                    $airplaneModel->ao_id = $model->ao_id;
                    // Set other attributes of the airplane model
                    $airplaneModel->manufacturer = Yii::$app->request->post('Aircrafts')['manufacturer'];
                    $airplaneModel->model = Yii::$app->request->post('Aircrafts')['model'];
                    $airplaneModel->serial_number = Yii::$app->request->post('Aircrafts')['serial_number'];
                    $airplaneModel->registration_number = Yii::$app->request->post('Aircrafts')['registration_number'];
                    $airplaneModel->aircraft_model_id = Yii::$app->request->post('Aircrafts')['aircraft_model_id'];
                    $airplaneModel->certificate_type_id = Yii::$app->request->post('Aircrafts')['certificate_type_id'];

                    $airplaneModel->aircraft_model_id = AircraftModel::find()
                    ->select('aircraft_model_id') // Select the aircraft_model_id
                    ->where(['model' => $airplaneModel->model]) // Match on model
                    ->andWhere(['manufacturer' => $airplaneModel->manufacturer]) // Match on manufacturer
                    ->scalar(); // Get the scalar value (aircraft_model_id)

                  //  VarDumper::dump($airplaneModel);die();
                    if ($airplaneModel->validate() && $airplaneModel->save()) {
                        // Send verification email
                        $verificationLink = Yii::$app->urlManager->createAbsoluteUrl(['site/verify-emailao', 'token' => $model->verification_token]);
                        try {
                         /*  $message = Yii::$app->mailer->compose()
                                ->setTo($model->email)
                                ->setSubject('Verify your email address')
                                ->setTextBody("Follow the link below to verify your email address:\n\n$verificationLink")
                                ->send();
    */
                        // $message = Yii::$app->mailer->compose()
                        //     ->setTo($model->email)
                        //     ->setSubject('Verify your email_address')
                        //     ->setTextBody("Follow the link below to verify your email address:\n\n$verificationLink")
                        //     ->send();


                        // Résolution du nom affiché
if (!empty($model->first_name) || !empty($model->last_name)) {
    $fullName = trim(($model->first_name ?? '') . ' ' . ($model->last_name ?? ''));
} elseif (!empty($model->name)) {
    $fullName = $model->name;
} elseif (!empty($model->username)) {
    $fullName = $model->username;
} elseif (!empty($model->email)) {
    $fullName = $model->email;
} else {
    $fullName = 'User';
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

$message = Yii::$app->mailer->compose()
    ->setTo($model->email)
    ->setFrom([
        Yii::$app->params['supportEmail'] ?? 'no-reply@coreaviationnetwork.com' => Yii::$app->name,
    ])
    ->setSubject('Verify your email address - ' . Yii::$app->name)
    ->setHtmlBody($htmlBody)
    ->send();

                            if ($message) {
                             
                                Yii::$app->session->setFlash('success', 'Verification email sent successfully.');
                            } else {
                                Yii::$app->session->setFlash('error', 'Failed to send verification email.');
                            }
                        } catch (InvalidConfigException $e) {
                            Yii::$app->session->setFlash('error', 'Error configuring mailer: ' . $e->getMessage());
                        }
    
                        // Save the aircraft model
                        Yii::$app->getSession()->setFlash('message', 'AO Profile and Airplane Added Successfully!');
                        return $this->redirect(['site/landing']);
                    }
                } else {
                    // Handle save errors
                    Yii::$app->session->setFlash('error', 'Failed to save AO Profile: ');
                }
            } else {
                // Handle validation errors
                Yii::$app->session->setFlash('error', 'Validation failed. Check your inputs ' );
            }
        }
    
        return $this->render('become-ao', [
            'model' => $model,
                        'countries' => $countries,

            'airplaneModel' => $airplaneModel, // Pass the airplane model to the view
            'terms' => $termsContent,
                        'citiesByCountryJson' => $citiesByCountryJson,
            'manufacturers' => $manufacturers, // Pass the manufacturers to the view
            'models' => $models, // Pass the models to the view
        ]);
    }
    
    
    public function actionBecomeMro()
    {

        $model = new MroProfile();
        $termsContent = Terms::find()->one()->content; // Assuming you have a Terms model with a content field
        $certificate = new Certificates();
        $mroairport = new MroprofileAirport();

        /*
         * PERFORMANCE DU FORMULAIRE : seule la liste des pays est nécessaire au
         * premier affichage. Les villes sont ensuite demandées par AJAX lorsque
         * l'utilisateur choisit un pays. On évite ainsi de charger et d'instancier
         * toutes les villes (ainsi que tous les MRO) pendant chaque GET ou POST.
         */
        $countries = Countries::find()->orderBy('country_name')->all(); // Assuming you have a Country model
        $mro_notifications = new MroNotificationsPreferences();
        $mroaircraftcertificate = new MroAircraftCertificate();

                                // Set the file path for the uploaded certificate file



        if ($model->load(Yii::$app->request->post())) {
           // VarDumper::dump( $model);die();


            if (MroProfile::find()->where(['username' => $model->username])->exists()) {
                Yii::$app->session->setFlash('error', 'Username is already taken');
                return $this->refresh();
            }
            
            $model->profile_photo = UploadedFile::getInstance($model, 'profile_photo');
            if ($model->profile_photo) {
                $uploadPath = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . $model->profile_photo->extension;
                if ($model->profile_photo->saveAs($uploadPath)) {
                    $model->profile_photo = $uploadPath;
                } else {
                    Yii::$app->session->setFlash('error', 'Failed to upload profile photo');
                    return $this->refresh();
                }
            }
            $model->company_photo = UploadedFile::getInstance($model, 'company_photo');
            if ($model->company_photo) {
                $uploadPath = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . $model->company_photo->extension;
                if ($model->company_photo->saveAs($uploadPath)) {
                    $model->company_photo = $uploadPath;
                } else {
                    Yii::$app->session->setFlash('error', 'Failed to upload company_photo photo');
                    return $this->refresh();
                }
            }
            
            if ($model->password !== null) {
                $model->password = trim($model->password);
            }
            if ($model->confirm_password !== null) {
                $model->confirm_password = trim($model->confirm_password);
            }
            if ($model->password !== $model->confirm_password) {
                // Un mot de passe en clair ne doit jamais être recopié dans la session ou la réponse HTML.
                Yii::$app->session->setFlash('error', 'Passwords do not match.');
                return $this->refresh();
            }
            

            if ($model->validate()) {

                // Hash the password before saving
                if (!empty($model->password)) {
                    $model->password = Yii::$app->security->generatePasswordHash($model->password);
                } else {
                    // Handle the case where the password is not provided
                    // For example, you might want to throw an exception or set an error message
                    throw new \InvalidArgumentException('Password cannot be empty.');
                }
                    
                // Generate and save verification token
                $model->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
            
            if ($model->save()) {
                if ($mroaircraftcertificate->load(Yii::$app->request->post())) {
  $mroId = $model->mro_id; // ✅ NOW IT EXISTS

        /*
        * =========================
        * INSURANCE DOCUMENT FIX
        * =========================
        */
        $insuranceFile = UploadedFile::getInstance($model, 'insurance_document');

        if ($insuranceFile) {

            $uploadPath = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . $insuranceFile->extension;

            if ($insuranceFile->saveAs($uploadPath)) {

                $insuranceDoc = new MroInsuranceDocuments();
                $insuranceDoc->mro_id = $mroId;
                $insuranceDoc->file_path = $uploadPath;
                $insuranceDoc->file_name = $insuranceFile->name;
                $insuranceDoc->file_type = $insuranceFile->type;
                $insuranceDoc->file_size = $insuranceFile->size;

                if (!$insuranceDoc->save()) {
                    Yii::error($insuranceDoc->errors);
                }
            }
        }
                $mroaircraftcertificate->mro_id = $model->mro_id;
                $certificateFilex = UploadedFile::getInstance($mroaircraftcertificate, 'certificate');
                if ($certificateFilex) {
                    $uploadPath = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . $certificateFilex->extension;
                    $certificateFilex->saveAs($uploadPath);
                    $mroaircraftcertificate->certificate = $uploadPath;
                
                }
            } if (!$mroaircraftcertificate->validate() ) {
              VarDumper::dump(print_r($mroaircraftcertificate->errors, true));die();;

            }
            
                $mroaircraftcertificate->save();
                $mro_notifications->mro_id= $model->mro_id;
                $mro_notifications->notify_by_email= 1;
                $mro_notifications->notify_by_platform= 1;
                $mro_notifications->notify_by_both= 1;
                $mro_notifications->notify_for_non_certified_aircraft= 0;
                $mro_notifications->notify_for_certified_aircraft= 1;
                $mro_notifications->notify_for_appointment_acceptance= 1;
                $mro_notifications->notify_for_feedback= 1;
                $mro_notifications->save();
                $certificate->mro_id = $model->mro_id;
                $certificate->type = Yii::$app->request->post('Certificates')['type'];
                $certificate->certificate_type_id = Yii::$app->request->post('Certificates')['certificate_type_id'];
                // Handle file upload
                $certificateFile = UploadedFile::getInstance($certificate, 'certificate');
                // Set the file path for the uploaded certificate file
                if ($certificateFile) {
                    $uploadPath = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . $certificateFile->extension;
                    $certificateFile->saveAs($uploadPath);
                    $certificate->certificate = $uploadPath;
                
                }                
                if ($certificate->validate() && $certificate->save()) {
                    $mroairport->load(Yii::$app->request->post());
                    $mroairport->mro_id = $model->mro_id;
                    if($mroairport->validate() && $mroairport->save()){
                        // Generate and save verification token

                        $verificationLink = Yii::$app->urlManager->createAbsoluteUrl(['site/verify-email', 'token' => $model->verification_token]);

                        try {
                            // $mailer = Yii::$app->mailer;
                            // $message = $mailer->compose()
                            //     ->setTo($model->email)
                            //     ->setSubject('Verify your email address')
                            //     ->setTextBody("Follow the link below to verify your email address:\n\n$verificationLink");


// Résolution du nom affiché
if (!empty($model->first_name) || !empty($model->last_name)) {
    $fullName = trim(($model->first_name ?? '') . ' ' . ($model->last_name ?? ''));
} elseif (!empty($model->name)) {
    $fullName = $model->name;
} elseif (!empty($model->username)) {
    $fullName = $model->username;
} elseif (!empty($model->email)) {
    $fullName = $model->email;
} else {
    $fullName = 'User';
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

// Send the verification email.
// Important: send() already returns true/false, so do not call ->send() again.
$isSent = Yii::$app->mailer->compose()
    ->setTo($model->email)
    ->setFrom([
        Yii::$app->params['supportEmail'] ?? 'no-reply@coreaviationnetwork.com' => Yii::$app->name,
    ])
    ->setSubject('Verify your email address - ' . Yii::$app->name)
    ->setHtmlBody($htmlBody)
    ->send();

                            /*
                             * RETOUR UTILISATEUR COHÉRENT : à ce stade le profil MRO et
                             * ses documents sont déjà enregistrés. Un échec SMTP ne doit
                             * donc pas être présenté comme un échec d'inscription. Une
                             * seule notification indique le résultat complet de l'action.
                             */
                            if ($isSent) {
                                Yii::$app->session->setFlash(
                                    'success',
                                    'MRO profile created successfully. A verification email has been sent.'
                                );
                            } else {
                                Yii::$app->session->setFlash(
                                    'warning',
                                    'MRO profile created successfully, but the verification email could not be sent. Please try again later or contact support.'
                                );
                            }
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
                        }

                            return $this->redirect(['site/landing']);

                    }else{
                        Yii::$app->session->setFlash('error', 'Failed to save MRO Working Airport: ');

                    }

                } else {
                    Yii::$app->session->setFlash('error', 'Failed to save certificate: ' );
                }
            } else {
                Yii::$app->session->setFlash('error', 'Failed to save MRO Profile: ' );
            }
        }
        }
        
        return $this->render('become-mro', [
            'mroairport' => $mroairport, // Pass the airplane model to the view
            'countries' => $countries,
            'certificate' => $certificate, // Pass the airplane model to the view
            'model' => $model,
            'terms' => $termsContent,
            'mroaircraftcertificate'=>$mroaircraftcertificate,
        ]);
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
    return $this->render('home');
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
    $loginFailures = $loginSubmitted ? (int) Yii::$app->cache->get($loginRateKey) : 0;
    $loginRateLimited = $loginSubmitted && $loginFailures >= 5;

    /* Une fenêtre glissante simple limite les essais automatisés par compte et adresse IP. */
    $registerLoginFailure = static function () use ($loginRateKey, $loginFailures) {
        Yii::$app->cache->set($loginRateKey, $loginFailures + 1, 900);
    };

    if ($loginRateLimited) {
        $model->addError('password', 'Too many login attempts. Please try again in 15 minutes.');
    }

    if ($loginSubmitted && !$loginRateLimited && $model->login()) {
        $user = Yii::$app->user->identity;
        if ($user) {
            if ($user->status == 'banned') {
                $registerLoginFailure();
                Yii::$app->auditService->record('LOGIN_FAILED', [
                    'username' => $model->username,
                    'new_values' => ['reason' => 'INVALID_CREDENTIALS_OR_ACCOUNT_NOT_ALLOWED'],
                ]);
                Yii::$app->user->logout(); // Deconnecter immediatement un compte bloque.
                Yii::$app->session->setFlash('error', 'Your account has been banned. Please contact support for assistance.');
                return $this->refresh();
            }
            if ($user->getUserType() !== 'admin' && $user->email_verified == 0) {
                $registerLoginFailure();
                Yii::$app->auditService->record('LOGIN_FAILED', [
                    'username' => $model->username,
                    'new_values' => ['reason' => 'INVALID_CREDENTIALS_OR_ACCOUNT_NOT_ALLOWED'],
                ]);
                Yii::$app->user->logout(); // Deconnecter un compte qui n'est pas encore verifie.
                Yii::$app->session->setFlash('error', 'Your account is not verified. Please contact support for assistance.');
                return $this->refresh();
            }
            
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
            Yii::$app->auditService->record('LOGIN');
            Yii::$app->session->setFlash('message', 'You have successfully logged in. ' );
            return $this->redirect(['dashboard/home']);
        } else {
            Yii::$app->session->setFlash('error', 'Failed to set user identity.');
        }
    }

    if ($loginSubmitted && Yii::$app->user->isGuest) {
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

   
    public function actionRequestPasswordReset()
    {
        $this->layout = 'auth';
        $model = new PasswordResetRequestForm();
    
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
                return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset the password for the provided email address.');
            }
        }
    
        return $this->render('requestPasswordResetToken', [
            'model' => $model,
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
        
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for your username.');
                return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', 'Sorry, we are unable to send the username for the provided email address.');
            }
        }

        return $this->render('requestUsernameReset', [
            'model' => $model,
        ]);
    }


    public function actionRequestUsernameReset1()
    {
        $model = new UsernameResetRequestForm();
        
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for your username.');
                return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', 'Sorry, we are unable to send the username for the provided email address.');
            }
        }

        return $this->render('requestUsernameReset', [
            'model' => $model,
        ]);
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
