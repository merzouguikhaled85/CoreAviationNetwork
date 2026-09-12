<?php

namespace app\controllers;

use app\models\AircraftModel;
use app\models\Certificates;
use app\models\Cities;
use app\models\Countries;
use app\models\Feedback;
use app\models\MroAircraftCertificate;
use app\models\MroNotificationsPreferences;
use app\models\MroprofileAirport;
use app\models\MroProfileResetPasswordForm;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\Controller;
use app\models\MroProfile;
use yii\web\UploadedFile;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\VarDumper;
use yii\web\NotFoundHttpException;
use app\components\UrlIdHelper;


class MroProfileController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['update','cities-by-country'],
                        'roles' => ['@'], // Only authenticated users
                        'matchCallback' => function ($rule, $action) {
                            return Yii::$app->session->get('user_type') === 'mro';
                        }
                    ],
                    [
                        'allow' => true,
                        'actions' => ['view'],
                        'roles' => ['@'], // Only authenticated users
                        'matchCallback' => function ($rule, $action) {
                            $userType = Yii::$app->session->get('user_type');
                            return in_array($userType, ['ao', 'mro']);
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
                        'actions' => ['reset-password','update-insurance'],
                        'roles' => ['@'], // Only authenticated users
                        'matchCallback' => function ($rule, $action) {
                            return Yii::$app->session->get('user_type') === 'mro';
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
     * Displays the list of MRO profiles.
     * @return string
     */

     

     public function actionIndex1()
     {
         // Define the query
         $query = MroProfile::find();
     
         // Create a pagination object with a total count and a limit of 20 per page
         $pagination = new Pagination([
             'defaultPageSize' => 20,
             'totalCount' => $query->count(),
         ]);
     
         // Adjust the query using the pagination object
         $mroProfiles = $query->offset($pagination->offset)
             ->limit($pagination->limit)
             ->all();
     
         // Render the view, passing the mroProfiles and pagination objects
         return $this->render('index', [
             'mroProfiles' => $mroProfiles,
             'pagination' => $pagination,
         ]);
     }

     public function actionIndex()
{
    // Create the base query
    $query = MroProfile::find();

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

    // Create pagination object with 20 items per page
    $pagination = new Pagination([
        'defaultPageSize' => 8,
        'totalCount' => $countQuery->count(),
    ]);

    // Get filtered and paginated MRO profiles
    $mroProfiles = $query
        ->orderBy(['mro_id' => SORT_DESC])
        ->offset($pagination->offset)
        ->limit($pagination->limit)
        ->all();

    // Render the index view
    return $this->render('index', [
        'mroProfiles' => $mroProfiles,
        'pagination' => $pagination,
        'search' => $search,
        'status' => $status,
        'emailVerified' => $emailVerified,
    ]);
}


    /**
     * Updates an existing MRO profile.
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
    $mroProfile = MroProfile::findOne($id);
    $mroProfile->scenario = 'update';
    
    $countries = Countries::find()->orderBy('country_name')->all();
    
    // Only load cities for the current saved country
    $cities = [];
    if ($mroProfile->country_id) {
        $cities = Cities::find()
            ->select(['city_id', 'city_name'])
            ->where(['country_id' => $mroProfile->country_id])
            ->orderBy('city_name')
            ->all();
    }

    // Check if admin
    $isAdmin = Yii::$app->session->get('user_type') === 'admin';

    if ($mroProfile->load(Yii::$app->request->post())) {
            $mroProfile->zip_code = Yii::$app->request->post('MroProfile')['zip_code'] ?? $mroProfile->zip_code;
    $mroProfile->address = Yii::$app->request->post('MroProfile')['address'] ?? $mroProfile->address;

        // Prevent non-admin users from updating status
        if (!$isAdmin) {
            unset($mroProfile->status);
        }

        // Handle file uploads
        $uploadFields = ['insurance_document', 'company_photo', 'profile_photo'];
        foreach ($uploadFields as $field) {
            $file = UploadedFile::getInstance($mroProfile, $field);
            if ($file) {
                $uploadPath = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . $file->extension;
                $file->saveAs($uploadPath);
                $mroProfile->$field = $uploadPath;
            } else {
                $mroProfile->$field = $mroProfile->getOldAttribute($field);
            }
        }

        // Handle multiple main_references
        $mainReferencesFiles = UploadedFile::getInstances($mroProfile, 'main_references');
        if (!empty($mainReferencesFiles)) {
            $mainReferencesPaths = [];
            foreach ($mainReferencesFiles as $file) {
                $uploadPath = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . $file->extension;
                $file->saveAs($uploadPath);
                $mainReferencesPaths[] = $uploadPath;
            }
            $mroProfile->main_references = implode(',', $mainReferencesPaths);
        } else {
            $mroProfile->main_references = $mroProfile->getOldAttribute('main_references');
        }

        if ($mroProfile->validate() && $mroProfile->save()) {
            Yii::$app->getSession()->setFlash('message', 'MRO Profile Updated Successfully!');
            return $this->redirect(['dashboard/home']);
        }
    }

    // Convert stored main references into array for display
    if (!empty($mroProfile->main_references)) {
        $mroProfile->main_references = explode(',', $mroProfile->main_references);
    }

    return $this->render('update', [
        'mroProfile' => $mroProfile,
        'countries' => $countries,
        'cities' => $cities, // only relevant cities
    ]);
}

public function actionCitiesByCountry($country_id)
{
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

    $cities = \app\models\Cities::find()
        ->select(['city_id AS id', 'city_name AS name'])
        ->where(['country_id' => $country_id])
        ->orderBy('city_name')
        ->asArray()
        ->all();

    return $cities;
}


    
    public function actionView($id ,$fromApplication = null ,$fromMro = null)
    {
             // Decode encrypted ID from URL
    $id = UrlIdHelper::decode($id);

    // If token is invalid, stop request
    if (!$id) {
        throw new NotFoundHttpException('Invalid request link.');
    }
        $mroProfile = MroProfile::findOne($id);
        
        // Fetch feedback related to this MRO profile
        $feedbacks = Feedback::find()->where(['mro_id' => $mroProfile->mro_id])->all() ?? [];
    
        // Calculate project realised count (unique request ids)
        $uniqueRequestIds = array_unique(array_column($feedbacks, 'request_id'));
        $projectRealisedCount = count($uniqueRequestIds);
        
        // Calculate average rating
        $totalRating = 0;
        $ratingCount = count($feedbacks);
        
        if ($ratingCount > 0) {
            foreach ($feedbacks as $feedback) {
                $totalRating += $feedback->rating;
            }
            $averageRating = $totalRating / $ratingCount;
        } else {
            $averageRating = 0; // No feedback, so average rating is 0
        }
    
        return $this->render('view', [
            'mroProfile' => $mroProfile,
            'fromApplication' => $fromApplication,
            'fromMro' => $fromMro,
            'projectRealisedCount' => $projectRealisedCount,
            'averageRating' => $averageRating,
        ]);
    }
    
    

    /**
     * Deletes an existing MRO profile.
     * @param int $id
     * @return \yii\web\Response
     */
    public function actionDelete($id)
    {
        $mroProfile = MroProfile::findOne($id);
        if ($mroProfile && $mroProfile->delete()) {
            Yii::$app->getSession()->setFlash('message', 'MRO Profile Deleted Successfully!');
        } else {
            Yii::$app->getSession()->setFlash('message', 'Failed to Delete MRO Profile!');
        }
        return $this->redirect(['index']);
    }

    /**
     * Creates a new MRO profile.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new MroProfile();
        $certificate = new Certificates();
        $mros = MroProfile::find()->all(); // Assuming Mro is the model for MROs, adjust as per your actual model name
        $mroairport = new MroprofileAirport();
        // Fetch countries, cities, and terms from your database or any other source
        $countries = Countries::find()->orderBy('country_name')->all(); // Assuming you have a Country model
      //  $cities = Cities::find()->orderBy('city_name')->all(); // Assuming you have a City model
        $mro_notifications = new MroNotificationsPreferences();
        $mroaircraftcertificate = new MroAircraftCertificate();

        // Prepare cities data in JSON format
        $citiesByCountry = [];
      //  foreach ($cities as $city) {
     //       $citiesByCountry[$city->country_id][] = $city;
    //    }
    $citiesByCountryJson = Json::encode([]); // Empty at first

                                // Set the file path for the uploaded certificate file



        if ($model->load(Yii::$app->request->post())) {
            $certificateFilexs = UploadedFile::getInstance($model, 'insurance_document');
            if ($certificateFilexs) {
                $uploadPath = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . $certificateFilexs->extension;
                $certificateFilexs->saveAs($uploadPath);
                $model->insurance_document = $uploadPath;

            }
          //  VarDumper::dump( $model->insurance_document);die();
            if (MroProfile::find()->where(['username' => $model->username])->exists()) {
                Yii::$app->session->setFlash('usernameError', 'Username is already taken');
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
                Yii::$app->session->setFlash('passwordError', 'Passwords do not match ' .$model->password .' : '.$model->confirm_password);
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
                            $mailer = Yii::$app->mailer;
                            $message = $mailer->compose()
                                ->setTo($model->email)
                                ->setSubject('Verify your_email_address')
                                ->setTextBody("Follow the link below to verify your email address:\n\n$verificationLink");

                            if ($message->send()) {
                                Yii::$app->session->setFlash('success', 'Verification email sent successfully.');
                            } else {
                                Yii::$app->session->setFlash('error', 'Failed to send verification email.');
                            }
                        } catch (InvalidConfigException $e) {
                            Yii::$app->session->setFlash('error', 'Error configuring mailer: ' . $e->getMessage());
                        }


                            Yii::$app->getSession()->setFlash('message', 'MRO Profile and Certificate Added Successfully!');
                            return $this->redirect(['index']);

                    }else{
                        Yii::$app->session->setFlash('error', 'Failed to save MRO Working Airport: ' . print_r($mroairport->errors, true));

                    }

                } else {
                    Yii::$app->session->setFlash('error', 'Failed to save certificate: ' . print_r($certificate->errors, true));
                }
            } else {
                Yii::$app->session->setFlash('error', 'Failed to save MRO Profile: ' . print_r($model->errors, true));
            }
        }
        }
    
        return $this->render('create', [
            'model' => $model,
            'mroairport' => $mroairport, // Pass the airplane model to the view
            'countries' => $countries,
            'citiesByCountryJson' => $citiesByCountryJson,
            'certificate' => $certificate, // Pass the airplane model to the view
            'mros' => $mros, // Pass the $mros variable to the view
            'mroaircraftcertificate'=>$mroaircraftcertificate,
            'aircraftModels' => AircraftModel::find()->all(),
    
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

    // Format results for dropdown
    $formattedCities = [];
    foreach ($cities as $city) {
        $formattedCities[] = [
            'id' => $city['city_id'],
            'text' => $city['city_name'],
        ];
    }

    return ['cities' => $formattedCities];
}


public function actionGetModelsByManufacturer($manufacturer = null)
{
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

    if (empty($manufacturer)) {
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
        $result[] = [
            'id' => $model['aircraft_model_id'],
            'name' => $model['model'],
        ];
    }

    return $result;
}

    /**
     * Toggles the status of an existing MRO profile.
     * @param int $id
     * @return \yii\web\Response
     */
    public function actionToggleStatus($id)
    {
        $mroProfile = MroProfile::findOne($id);

        // Toggle the status to the next state
        switch ($mroProfile->status) {
            case 'active':
                $mroProfile->status = 'banned';
                break;
            case 'banned':
                $mroProfile->status = 'hidden';
                break;
            case 'hidden':
                $mroProfile->status = 'active';
                break;
            default:
                break;
        }

        if ($mroProfile->save()) {
            Yii::$app->getSession()->setFlash('message', 'MRO Profile Status Updated Successfully!');
        } else {
            Yii::$app->getSession()->setFlash('error', 'Failed to Update MRO Profile Status!');
        }

        return $this->redirect(['index']);
    }

    public function actionSendPasswordReset($id)
    {
        $user = MroProfile::findOne($id);
    
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

    public function actionUpdateInsurance($id)
    {
        $mroProfile = MroProfile::findOne($id);
        if (!$mroProfile) {
            throw new NotFoundHttpException('Profile not found.');
        }
    
        // Check if the form is submitted via POST
        if (Yii::$app->request->isPost) {
            // Get the uploaded file
            $uploadedFile = UploadedFile::getInstance($mroProfile, 'insurance_document');
    
            // Only update if a new file is provided
            if ($uploadedFile) {
                // Set the file path for saving
                // Generate a random number
                $randomNumber = rand(1000, 9999); // Adjust the range as needed

                // Set the file path for saving with a random number appended to the basename
                $filePath = 'uploads/' . $uploadedFile->baseName . '_' . $randomNumber . '.' . $uploadedFile->extension;
    
                // Save the file
                if ($uploadedFile->saveAs($filePath)) {
                    // Update the model's insurance_document field with the new file path
                    $mroProfile->insurance_document = $filePath;
                } else {
                    Yii::$app->session->setFlash('error', 'Failed to save the uploaded file.');
                }
            }
    
            // Validate and save the model
            if ($mroProfile->validate()) {
                if ($mroProfile->save(false)) { // Save without validation since we already validated
                    Yii::$app->session->setFlash('success', 'Insurance document updated successfully.');
                    return $this->redirect(['dashboard/home']);
                } else {
                    Yii::$app->session->setFlash('error', 'Failed to update insurance document.');
                }
            } else {
                // Output validation errors for debugging
                Yii::error($mroProfile->getErrors());
            }
        }
    
        // Render the update form with the current insurance document data
        return $this->render('update-insurance', [
            'mroProfile' => $mroProfile,
        ]);
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
        $model = MroProfile::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('MRO Profile not found.');
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



    public function actionGetModelsByManufacturer2($manufacturer = null)
{
    Yii::$app->response->format = Response::FORMAT_JSON;

    if (empty($manufacturer)) {
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
        $result[] = [
            'id' => $model['aircraft_model_id'],
            'name' => $model['model'],
        ];
    }

    return $result;
}
    
    
    
}
