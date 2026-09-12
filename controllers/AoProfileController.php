<?php

namespace app\controllers;

use app\models\AircraftModel;
use app\models\Aircrafts;
use app\models\AoNotificationsPreferences;
use app\models\MroProfileResetPasswordForm;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use app\models\AoProfile;
use yii\web\UploadedFile;
use yii\data\Pagination;
use app\models\Cities;
use app\models\Countries;
use yii\helpers\Url;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\helpers\VarDumper;
use yii\web\NotFoundHttpException;
use app\components\UrlIdHelper;


class AoProfileController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['update', 'cities-by-country'],
                        'roles' => ['@'], // Only authenticated users
                        'matchCallback' => function ($rule, $action) {
                            return Yii::$app->session->get('user_type') === 'ao';
                        }
                    ],
                    [
                        'allow' => true,
                        'actions' => ['reset-password'],
                        'roles' => ['@'], // Only authenticated users
                        'matchCallback' => function ($rule, $action) {
                            return Yii::$app->session->get('user_type') === 'ao';
                        }
                    ],
                    [
                        'allow' => true,
                        'actions' => ['view'],
                        'roles' => ['@'], // Only authenticated users
                        'matchCallback' => function ($rule, $action) {
                            $userType = Yii::$app->session->get('user_type');
                            return in_array($userType, ['ao']);
                        }
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index'],
                        'roles' => ['@'], // Only authenticated users
                        'matchCallback' => function ($rule, $action) {
                            $userType = Yii::$app->session->get('user_type');
                            return in_array($userType, ['admin']);
                        }
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'], // Only authenticated users
                        'matchCallback' => function ($rule, $action) {
                            // Allow access only to users with specific user types
                            return in_array(Yii::$app->session->get('user_type'), ['admin']);
                        }
                    ],
                ],
            ],
        ];
    }
    /**
     * Displays the list of AO profiles.
     * @return string
     */
    public function actionIndex1()
    {
        // Define the query
        $query = AoProfile::find();
    
        // Create a pagination object with a total count and a limit of 20 per page
        $pagination = new Pagination([
            'defaultPageSize' => 20,
            'totalCount' => $query->count(),
        ]);
    
        // Adjust the query using the pagination object
        $aoProfiles = $query->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();
    
        // Render the view, passing the aoProfiles and pagination objects
        return $this->render('index', [
            'aoProfiles' => $aoProfiles,
            'pagination' => $pagination,
        ]);
    }

    public function actionIndex()
{
    // Create the base query
    $query = AoProfile::find();

    // Get filter values from GET request
    $search = Yii::$app->request->get('search');
    $status = Yii::$app->request->get('status');
    $emailVerified = Yii::$app->request->get('email_verified');

    // Apply global search filter
    if (!empty($search)) {
        $search = trim($search);

        $query->andFilterWhere([
            'or',
            ['like', 'username', $search],
            ['like', 'email', $search],
            ['like', 'first_name', $search],
            ['like', 'last_name', $search],
            ['like', 'contact_number', $search],
            ['like', 'company_name', $search],
        ]);
    }

    // Apply status filter
    if ($status !== null && $status !== '') {
        $query->andWhere(['status' => $status]);
    }

    // Apply email verified filter
    if ($emailVerified !== null && $emailVerified !== '') {
        $query->andWhere(['email_verified' => (int) $emailVerified]);
    }

    // Clone query before pagination count
    $countQuery = clone $query;

    // Create pagination object
    $pagination = new Pagination([
        'defaultPageSize' => 7,
        'totalCount' => $countQuery->count(),
    ]);

    // Get filtered and paginated AO profiles
    $aoProfiles = $query
        ->orderBy(['ao_id' => SORT_DESC])
        ->offset($pagination->offset)
        ->limit($pagination->limit)
        ->all();

    // Render the index view
    return $this->render('index', [
        'aoProfiles' => $aoProfiles,
        'pagination' => $pagination,
        'search' => $search,
        'status' => $status,
        'emailVerified' => $emailVerified,
    ]);
}

    /**
     * Updates an existing AO profile.
     * @param int $id
     * @return string|\yii\web\Response
     */
public function actionUpdate($id)
{
   // Decode encrypted ID from URL
    $id = UrlIdHelper::decode($id);

    // If token is invalid, stop request
    if (!$id) {
        throw new NotFoundHttpException('Invalid request link.');
    }
    $aoProfile = AoProfile::findOne($id);

    $countries = Countries::find()->orderBy('country_name')->all();

    // Only load cities for the current saved country
    $cities = [];
    if ($aoProfile->country_id) {
        $cities = Cities::find()
            ->select(['city_id', 'city_name'])
            ->where(['country_id' => $aoProfile->country_id])
            ->orderBy('city_name')
            ->all();
    }

    if ($aoProfile->load(Yii::$app->request->post())) {
        $profile_photo = UploadedFile::getInstance($aoProfile, 'profile_photo');

        if ($profile_photo) {
            $uploadPath = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . $profile_photo->extension;
            $profile_photo->saveAs($uploadPath);
            $aoProfile->profile_photo = $uploadPath;
        } else {
            $aoProfile->profile_photo = $aoProfile->getOldAttribute('profile_photo');
        }

        if (Yii::$app->session->get('user_type') === 'admin') {
            $aoProfile->status = 'active';
        }

        if ($aoProfile->validate() && $aoProfile->save()) {
            Yii::$app->getSession()->setFlash('message', 'AO Profile Updated Successfully!');
            return $this->redirect(['dashboard/home']);
        } else {
            Yii::$app->getSession()->setFlash('error', 'Failed to update profile.');
        }
    }

    return $this->render('update', [
        'aoProfile' => $aoProfile,
        'countries' => $countries,
        'cities' => $cities,
    ]);
}
public function actionCitiesByCountry($country_id)
{
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

    $cities = Cities::find()
        ->select(['city_id AS id', 'city_name AS name'])
        ->where(['country_id' => (int)$country_id])
        ->orderBy(['city_name' => SORT_ASC])
        ->asArray()
        ->all();

    return $cities;
}
    

    /**
     * Displays the details of an AO profile.
     * @param int $id
     * @return string
     */
    public function actionView($id)
    {
        $aoProfile = AoProfile::findOne($id);

        return $this->render('view', [
            'aoProfile' => $aoProfile,
        ]);
    }

    /**
     * Deletes an existing AO profile.
     * @param int $id
     * @return \yii\web\Response
     */
    public function actionDelete($id)
    {
        $aoProfile = AoProfile::findOne($id);
        if ($aoProfile && $aoProfile->delete()) {
            Yii::$app->getSession()->setFlash('message', 'AO Profile Deleted Successfully!');
        } else {
            Yii::$app->getSession()->setFlash('message', 'Failed to Delete AO Profile!');
        }
        return $this->redirect(['index']);
    }

    /**
     * Creates a new AO profile.
     * @return string|\yii\web\Response
     */
   
  public function actionCreate()
  {
      $model = new AoProfile();
      $airplaneModel = new Aircrafts(); // Create an instance of the Airplane model
      $manufacturers = AircraftModel::find()->select('manufacturer')->distinct()->all(); // Fetch all distinct manufacturers from the aircraft_model table
      $models = AircraftModel::find()->all(); // Fetch all models from the aircraft_model table
      // Fetch countries, cities, and terms from your database or any other source
      $countries = Countries::find()->orderBy('country_name')->all(); // Assuming you have a Country model
     // $cities = Cities::find()->orderBy('city_name')->all(); // Assuming you have a City model
      $ao_notifications = new AoNotificationsPreferences();

      // Prepare cities data in JSON format
     // $citiesByCountry = [];
    //  foreach ($cities as $city) {
    //      $citiesByCountry[$city->country_id][] = $city;
//}
    $citiesByCountryJson = Json::encode([]); // Empty at first
  
      if ($model->load(Yii::$app->request->post())) {
          // Check if the username is unique
          if (AoProfile::find()->where(['username' => $model->username])->exists()) {
              Yii::$app->session->setFlash('usernameError', 'Username is already taken');
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
              Yii::$app->session->setFlash('passwordError', 'Passwords do not match');
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
                    //   $message = Yii::$app->mailer->compose()
                    //       ->setTo($model->email)
                    //       ->setSubject('Verify your email_address')
                    //       ->setTextBody("Follow the link below to verify your email address:\n\n$verificationLink")
                    //       ->send();

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
                      return $this->redirect(['index']);
                  }
              } else {
                  // Handle save errors
                  Yii::$app->session->setFlash('error', 'Failed to save AO Profile: ' . print_r($model->errors, true));



              }
          } else {
              // Handle validation errors
              Yii::$app->session->setFlash('error', 'Validation failed: ' . print_r($model->errors, true));
          }
      }
  
      return $this->render('create', [
          'model' => $model,
          'airplaneModel' => $airplaneModel, // Pass the airplane model to the view
          'countries' => $countries,
          'citiesByCountryJson' => $citiesByCountryJson,
          'manufacturers' => $manufacturers, // Pass the manufacturers to the view
          'models' => $models, // Pass the models to the view
      ]);
  }

public function actionLoadCities($countryId)
{
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

    $cities = Cities::find()
        ->select(['city_id', 'city_name'])
        ->where(['country_id' => $countryId])
        ->orderBy('city_name')
        ->asArray()
        ->all();

    $formattedCities = [];
    foreach ($cities as $city) {
        $formattedCities[] = [
            'id' => $city['city_id'],
            'text' => $city['city_name'],
        ];
    }

    return $formattedCities;
}

    /**
     * Toggles the status of an existing AO profile.
     * @param int $id
     * @return \yii\web\Response
     */
    public function actionToggleStatus($id)
    {
        $aoProfile = AoProfile::findOne($id);

        // Toggle the status to the next state
        switch ($aoProfile->status) {
            case 'active':
                $aoProfile->status = 'banned';
                break;
            case 'banned':
                $aoProfile->status = 'hidden';
                break;
            case 'hidden':
                $aoProfile->status = 'active';
                break;
            default:
                break;
        }

        if ($aoProfile->save()) {
            Yii::$app->getSession()->setFlash('message', 'AO Profile Status Updated Successfully!');
        } else {
            Yii::$app->getSession()->setFlash('error', 'Failed to Update AO Profile Status!');
        }

        return $this->redirect(['index']);
    }

    public function actionSendPasswordReset($id)
    {
        $user = AoProfile::findOne($id);
    
        if (!$user) {
            throw new NotFoundHttpException('User not found.');
        }
    
        if ($user->generatePasswordResetToken() && $this->sendPasswordResetEmail($user)) {
            Yii::$app->session->setFlash('message', 'Password reset email sent successfully.');
        } else {
            Yii::$app->session->setFlash('error', 'Failed to send password reset email.');
        }
    
        return $this->redirect(['index']); // Redirect to the index or any other appropriate page
    }
    
    protected function sendPasswordResetEmail($user)
    {
        return Yii::$app->mailer->compose()
            ->setTo($user->email)
            ->setFrom([Yii::$app->params['adminEmail'] => Yii::$app->name])
            ->setSubject('Password reset')
            ->setTextBody('Follow the link to reset your password: ' . Yii::$app->urlManager->createAbsoluteUrl(['site/reset-password', 'token' => $user->password_reset_token]))
            ->send();
    }
    public function actionResetPassword($id)
    {

           // Decode encrypted ID from URL
    $id = UrlIdHelper::decode($id);

    // If token is invalid, stop request
    if (!$id) {
        throw new NotFoundHttpException('Invalid request link.');
    }
        // Fetch the MRO profile model from the database
        $model = AoProfile::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('AO Profile not found.');
        }
    
        // Create an instance of the ResetPasswordForm
        $resetPasswordForm = new MroProfileResetPasswordForm();
        
        // Handle form submission and validation
        if ($resetPasswordForm->load(Yii::$app->request->post()) && $resetPasswordForm->validate()) {
            // Verify the old password from the form against the hashed password in the MroProfile model
            if (!Yii::$app->security->validatePassword($resetPasswordForm->old_password, $model->password)) {
                Yii::$app->session->setFlash('error', 'Old password is incorrect.');
            } else {
                // Hash the new password and assign it to the MroProfile model
                $model->password = Yii::$app->security->generatePasswordHash($resetPasswordForm->new_password);
    
                // Save the updated model
                if ($model->save(false)) {
                    Yii::$app->session->setFlash('success', 'Password reset successfully.');
                    
                    // Log the user out
                    Yii::$app->user->logout();
    
                    // Redirect to the login page (or any other page, like the home page)
                    return $this->redirect(['site/login']);
                } else {
                    Yii::$app->session->setFlash('error', 'Failed to reset the password.');
                }
            }
        }
    
        // Render the reset password form
        return $this->render('logged-reset-password', [
            'model' => $resetPasswordForm,
        ]);
    }
    
}
