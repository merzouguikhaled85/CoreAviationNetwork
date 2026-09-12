<?php

namespace app\controllers;

use app\models\AoNotificationsPreferences;
use app\models\AoProfile;
use app\models\MroAircraftCertificate;
use Yii;
use yii\db\ActiveRecord;
use yii\web\Controller;
use app\models\Requests;
use app\models\Certificates;
use app\models\Conversations;
use app\models\Chat;
use app\models\MroProfile;
use app\models\MroprofileAirport;
use app\models\MroRequestApply;
use app\models\RequestChangeEvent;
use yii\helpers\VarDumper;
use yii\web\UploadedFile;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\data\Pagination;
use app\models\AoRequestsApplications;
use app\components\UrlIdHelper;


class MroRequestsController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Only authenticated users
                        'matchCallback' => function ($rule, $action) {
                            // Allow access only to users with specific user types
                            return in_array(Yii::$app->session->get('user_type'), ['mro']);
                        }
                    ],
                ],
            ],
        ];
    }
public function actionIndex()
{
    /*
     * CURSEUR DE SYNCHRONISATION : il est lu avant les demandes disponibles.
     * Toute modification concurrente restera donc visible au prochain poll MRO.
     */
    $syncCursor = (int) (RequestChangeEvent::find()->max('id') ?: 0);

    // Get the MRO ID from the current session.
    $mro_id = Yii::$app->session->get('mro_id');

    // Read the search keyword sent by the filter form in the index view.
    $search = trim((string) Yii::$app->request->get('search', ''));

    /*
     * FILTRE DE PRIORITÉ MRO : seules les trois valeurs métier connues sont
     * acceptées. Une valeur falsifiée dans l'URL est ignorée sans élargir les
     * critères de certificats, d'aéroport ou de propriété déjà appliqués.
     */
    $priorityFilter = strtolower(trim((string) Yii::$app->request->get('priority', '')));
    $allowedPriorities = array_keys(Requests::getOperationalPriorityOptions());
    if (!in_array($priorityFilter, $allowedPriorities, true)) {
        $priorityFilter = '';
    }

    // Build the query first. Do not call all() before applying search/pagination.
    $query = Requests::find()
        ->where(['requests.status' => ['answered', 'created']])

        // MRO must have the required certificate.
        ->andWhere([
            'IN',
            'requests.required_certificates',
            Certificates::find()
                ->select('type')
                ->where(['mro_id' => $mro_id])
        ])

        // Destination airport must match the MRO covered airports.
        ->andWhere([
            'requests.destination' => MroprofileAirport::find()
                ->select('airport_id')
                ->where(['mro_id' => $mro_id])
        ])

        // Join aircraft because the certificate check and search both need aircraft data.
        ->joinWith('aircraft')

        // MRO must have the aircraft certificate.
        ->andWhere([
            'EXISTS',
            MroAircraftCertificate::find()
                ->where('mro_aircraft_certificate.aircraft_model_id = aircrafts.aircraft_model_id')
                ->andWhere(['mro_aircraft_certificate.mro_id' => $mro_id])
                ->andWhere([
                    'aircrafts.aircraft_id' => new \yii\db\Expression('requests.aircraft_id')
                ])
        ])

        // Show request if:
        // - no PO has been loaded yet
        // OR
        // - the loaded PO belongs to this MRO.
        ->andWhere([
            'OR',
            [
                'NOT EXISTS',
                AoRequestsApplications::find()
                    ->alias('ara')
                    ->select(new \yii\db\Expression(1))
                    ->where('ara.request_id = requests.request_id')
                    ->andWhere(['IS NOT', 'ara.po', null])
                    ->andWhere(['<>', 'ara.po', ''])
            ],
            [
                'EXISTS',
                AoRequestsApplications::find()
                    ->alias('ara')
                    ->innerJoin(
                        'mro_request_apply mra',
                        'mra.id = ara.application_id'
                    )
                    ->select(new \yii\db\Expression(1))
                    ->where('ara.request_id = requests.request_id')
                    ->andWhere(['mra.mro_id' => $mro_id])
                    ->andWhere(['IS NOT', 'ara.po', null])
                    ->andWhere(['<>', 'ara.po', ''])
            ]
        ]);

    // PRIORITÉ AVANT PAGINATION : le compteur et les lignes utilisent le même filtre.
    if ($priorityFilter !== '') {
        $query->andWhere(['requests.operational_priority' => $priorityFilter]);
    }

    // Apply the search filter. This must stay before pagination and all().
    if ($search !== '') {
        $searchConditions = [
            'OR',
            ['like', 'requests.status', $search],
            ['like', 'requests.aircraft_registration', $search],
            ['like', 'requests.serial_number', $search],
            ['like', 'requests.required_certificates', $search],
            ['like', 'requests.eta', $search],
            ['like', 'requests.etd', $search],
            ['like', 'aircrafts.model', $search],
            [
                'IN',
                'requests.ao_id',
                AoProfile::find()
                    ->select('ao_id')
                    ->where(['like', 'username', $search])
            ],
        ];

        // Numeric search is used for IDs. This avoids applying LIKE on integer columns.
        if (is_numeric($search)) {
            $searchConditions[] = ['requests.request_id' => (int) $search];
            $searchConditions[] = ['requests.destination' => (int) $search];
        }

        $query->andWhere($searchConditions);
    }

    // Pagination keeps the request design pager working and preserves the search parameter.
    $pagination = new Pagination([
        'totalCount' => (clone $query)->count(),
        'pageSize' => 10,
        'pageSizeParam' => false,
        'params' => Yii::$app->request->queryParams,
    ]);

    $requests = $query
        /*
         * TRI OPÉRATIONNEL MRO AVANT PAGINATION : les demandes AOG restent en
         * tête de la liste complète, suivies des Urgent puis des Routine. Tous
         * les filtres d'éligibilité et l'ordre récent interne restent inchangés.
         */
        ->orderBy(Requests::getOperationalPriorityOrderExpression('requests'))
        ->addOrderBy(['requests.request_id' => SORT_DESC])
        ->offset($pagination->offset)
        ->limit($pagination->limit)
        ->all();

    $title = 'New requests';

    $viewParams = [
        'requests' => $requests,
        'title' => $title,
        'search' => $search,
        'pagination' => $pagination,
        'syncCursor' => $syncCursor,
    ];

    /*
     * RENDU AJAX CIBLÉ : seul le gestionnaire central qui transmet l'en-tête
     * X-CAN-Fragment reçoit le tableau. Une navigation normale continue de rendre
     * la page complète, ce qui préserve le comportement actuel et le référencement.
     */
    if (
        Yii::$app->request->isAjax
        && Yii::$app->request->headers->get('X-CAN-Fragment') === 'request-list'
    ) {
        /*
         * FRAGMENT AUTONOME : les mêmes données filtrées et paginées alimentent le
         * tableau initial et sa version AJAX. Les aides de présentation résident
         * dans le fragment afin d'éviter toute divergence entre les deux rendus.
         */
        return $this->renderPartial('_request-list', $viewParams);
    }

    return $this->render('index', $viewParams);
}
    

 
    

    public function actionContact($id)
    {
        // Check if a conversation already exists for the given request
        $conversation = Conversations::findOne(['request_id' => $id]);
    
        // If conversation does not exist, create a new one
        if (!$conversation) {
            // Create a new chat
            $chat = new Chat();
            $chat->mro_id = Yii::$app->session->get('mro_id');
            $chat->ao_id = Requests::findOne(['request_id' => $id])->ao_id;
            $chat->request_id = $id;

            $chat->save();
    
            // Create a new conversation
            $conversation = new Conversations();
            $conversation->request_id = $id;
            $conversation->chat_id = $chat->chat_id;
            $conversation->sender_id = Yii::$app->session->get('mro_id');
            $conversation->sender_type ='mro';
            $conversation->receiver_id = $chat->ao_id;
            $conversation->receiver_type ='ao';
            // Additional attributes initialization if needed
            $conversation->save();
$mroId = Yii::$app->session->get('mro_id');
$mro = \app\models\MroProfile::findOne($mroId);
              //platform notification
              $aoNotificationsPreferences = AoNotificationsPreferences::findOne(['ao_id' => $chat->ao_id]);
              if ($aoNotificationsPreferences && $aoNotificationsPreferences->notify_by_platform) {
                // Call the action to save the notification
                Yii::$app->runAction('notification/save-notification', [
                    'recipientType' => 'ao',
                    'recipientId' => $chat->ao_id,
                    'message' => 'New message received from ' . $mro->username . ' for request #' . $id . '.',
                    // SECURITY: notifications must not expose a raw chat identifier.
                    'actions' => Yii::$app->urlManager->createUrl([
                        'conversations/view',
                        'chat_id' => UrlIdHelper::encode($conversation->chat_id),
                    ]),
                ]);
            }
        }
    
        // Redirect to view the conversation
        // SECURITY: keep the chat identifier signed in browser-facing URLs.
        return $this->redirect([
            'conversations/view',
            'chat_id' => UrlIdHelper::encode($conversation->chat_id),
        ]);
    }
    

// In your controller file, add the Apply action method
public function actionApply($id)
{
    // Decode encrypted ID from URL
    $id = UrlIdHelper::decode($id);

    // If token is invalid, stop request
    if (!$id) {
        throw new NotFoundHttpException('Invalid request link.');
    }
    // Load the request by its ID
    $request = Requests::findOne($id);
    $mroId = Yii::$app->session->get('mro_id');
    // CHECK IF EXISTS
    $model = MroRequestApply::find()
        ->where([
            'mro_id' => $mroId,
            'request_id' => $id
        ])
        ->one();
    $isNew = false;
    // if not exists → create new
    if (!$model) {
        $isNew = true;

        $model = new MroRequestApply();
        $model->mro_id = $mroId;
        $model->request_id = $id;
    }

    // Keep the persisted quotation when the MRO edits the form without selecting a replacement.
    $existingAttachment = $isNew ? null : trim((string) $model->attachment);
    
    // Check if the form is submitted and validate the uploaded replacement, if any.
    if ($model->load(Yii::$app->request->post())) {
        $uploadedAttachment = UploadedFile::getInstance($model, 'attachment');
        $model->attachment = $uploadedAttachment;

        $isValid = $model->validate();
        if ($isNew && $uploadedAttachment === null) {
            $model->addError('attachment', 'Please attach your quotation file.');
            $isValid = false;
        }

        if ($isValid) {
            if ($uploadedAttachment !== null) {
                // Generate a unique filename and store the replacement quotation.
                $fileName = Yii::$app->security->generateRandomString(20) . '.' . $uploadedAttachment->extension;
                $uploadPath = Yii::getAlias('@webroot/uploads/');

                if ($uploadedAttachment->saveAs($uploadPath . $fileName)) {
                    $model->attachment = 'uploads/' . $fileName;
                } else {
                    $model->addError('attachment', 'The quotation document could not be uploaded.');
                }
            } else {
                $model->attachment = $existingAttachment;
            }

        // Set the MRO ID
        $model->mro_id = Yii::$app->session->get('mro_id');
        // Set the request ID
        $model->request_id = $id;

        // Save the model
        if (!$model->hasErrors() && $model->save(false)) {


$ao = $request->aO;
$aoNotificationsPreferences = AoNotificationsPreferences::findOne(['ao_id' => $ao->ao_id]);

if ($aoNotificationsPreferences && $aoNotificationsPreferences->notify_mro_replies) {

    // EMAIL
    if ($aoNotificationsPreferences->notify_by_email) {

        Yii::$app->mailer->compose('mro_replies', [
            'MroRequestApply' => $model
        ])
        ->setTo($ao->email)
        ->setSubject($isNew ? 'New Request Application' : 'Updated Request Application')
        ->send();
    }

    // PLATFORM
    if ($aoNotificationsPreferences->notify_by_platform) {

        Yii::$app->runAction('notification/save-notification', [
            'recipientType' => 'ao',
            'recipientId' => $ao->ao_id,

            'message' => $isNew
                ? 'New application received for Request #' . $request->request_id
                : 'Application updated for Request #' . $request->request_id,

            'actions' => Yii::$app->request->baseUrl . '/requests/new-requests',
        ]);
    }
}

              // Update the request status to "answered"
              $request->status = 'answered';
              $request->save();
              
            // Redirect to a relevant page (e.g., view page, index page)
            Yii::$app->session->setFlash('success', 'Reply and quote saved successfully.');
            return $this->redirect(['index']);
        } else {
            Yii::$app->session->setFlash('error', 'Failed to save reply and quote.');

        }
        }
    }

    // Render the apply view with the request and model
    return $this->render('apply', [
        'request' => $request,
        'model' => $model,
    ]);
}
// In your controller file, add the Apply action method
public function actionApplyupdate($id)
{
    // Decode encrypted ID from URL
    $id = UrlIdHelper::decode($id);

    // If token is invalid, stop request
    if (!$id) {
        throw new NotFoundHttpException('Invalid request link.');
    }
   
    $model = MroRequestApply::findOne($id);

    if (!$model) {
        throw new \yii\web\NotFoundHttpException("Application not found.");
    }

    $request = Requests::findOne($model->request_id);

    if (!$request) {
        throw new \yii\web\NotFoundHttpException("Request not found.");
    }

    $oldAttachment = $model->attachment;

    if ($model->load(Yii::$app->request->post())) {
        /**
         * REPLACEMENT DOCUMENT: inject the uploaded file before Yii validation.
         * When no new file is selected, the current quotation remains unchanged.
         */
        $file = UploadedFile::getInstance($model, 'attachment');
        $model->attachment = $file;

        if ($model->validate()) {
            if ($file) {
                $fileName = Yii::$app->security->generateRandomString(20) . '.' . $file->extension;
                $uploadPath = Yii::getAlias('@webroot/uploads/') . $fileName;

                if ($file->saveAs($uploadPath)) {
                    $model->attachment = 'uploads/' . $fileName;
                } else {
                    $model->attachment = $oldAttachment;
                    $model->addError('attachment', 'The quotation document could not be replaced.');
                }
            } else {
                $model->attachment = $oldAttachment;
            }

        /**
         * IMPORTANT: DO NOT CHANGE OWNERSHIP
         * (remove this line if you want strict security)
         */
        // $model->mro_id = Yii::$app->session->get('mro_id');

        if (!$model->hasErrors() && $model->save(false)) {

            $ao = $request->aO;

            $aoNotificationsPreferences =
                AoNotificationsPreferences::findOne([
                    'ao_id' => $ao->ao_id
                ]);

            /**
             * EMAIL
             */
            if (
                $aoNotificationsPreferences &&
                $aoNotificationsPreferences->notify_mro_replies &&
                $aoNotificationsPreferences->notify_by_email
            ) {
                Yii::$app->mailer->compose('mro_replies', [
                    'MroRequestApply' => $model
                ])
                ->setTo($ao->email)
                ->setSubject('Updated Request Application')
                ->send();
            }

            /**
             * PLATFORM NOTIFICATION
             */
            if (
                $aoNotificationsPreferences &&
                $aoNotificationsPreferences->notify_mro_replies &&
                $aoNotificationsPreferences->notify_by_platform
            ) {
                Yii::$app->runAction('notification/save-notification', [
                    'recipientType' => 'ao',
                    'recipientId' => $ao->ao_id,

                    'message' =>
                        'Request #' . $request->request_id .
                        ' application has been updated by MRO.',

                    'actions' =>
                        Yii::$app->request->baseUrl . '/requests/new-requests',
                ]);
            }

            $request->status = 'answered';
            $request->save(false);

            Yii::$app->session->setFlash(
                'success',
                'Application updated successfully.'
            );

            return $this->redirect(['index']);
        }
        }

        // FORM REDISPLAY: keep the existing document visible after any validation/upload error.
        $model->attachment = $oldAttachment;

        Yii::$app->session->setFlash(
            'error',
            'Failed to update application.'
        );
    }

    return $this->render('applyupdate', [
        'request' => $request,
        'model' => $model,
    ]);
}

    public function actionRecommendMro($id)
    {
        $mro_id = Yii::$app->session->get('mro_id');
        $request = Requests::findOne($id);
        $model = new MroProfile();

        // Fetch all certificates where type matches required_certificates from request
        $certifs = Certificates::find()
            ->where(['type' => $request->required_certificates])
            ->asArray()
            ->all();
    
        $mro_ids = array_column($certifs, 'mro_id');
        $mros = MroProfile::find()
            ->where(['mro_id' => $mro_ids])
            ->andWhere(['<>', 'mro_id', $mro_id])
            ->all();
    
        if (Yii::$app->request->isPost) {
            $selectedMroId = Yii::$app->request->post('MroProfile')['mro_id'];

            if ($selectedMroId) {
                $fromMro = MroProfile::findOne($mro_id);
                 $toMro = MroProfile::findOne($selectedMroId);
                // Fetch the aircraft owner email using ao_id
                $aircraftOwner = AoProfile::findOne($request->ao_id);
                if ($aircraftOwner) {
                    $aoEmail = $aircraftOwner->email;
                    // Check AO's notification preferences
                    $aoNotificationsPreferences = AoNotificationsPreferences::findOne(['ao_id' => $aircraftOwner->ao_id]);

                    // Check if preferences exist and AO has opted for email notifications for MRO recommendations
                    if ($aoNotificationsPreferences && $aoNotificationsPreferences->notify_mro_recommendations && $aoNotificationsPreferences->notify_by_email) {
     
                    Yii::$app->mailer->compose('mro_profile_recommendation', [
                        'request' => $request->request_id,
                        'recommendationFrom' => MroProfile::findOne($mro_id)->username,
                        'recommendedMro' => MroProfile::findOne($selectedMroId)->username,
                    ])->setTo($aoEmail)
                        
                        ->setSubject('MRO Profile Recommendation')
                        ->send();
                    }

                                                  //platform notification
                                                  if ($aoNotificationsPreferences && $aoNotificationsPreferences->notify_mro_recommendations && $aoNotificationsPreferences->notify_by_platform) {
                                                    // Call the action to save the notification
                                                    Yii::$app->runAction('notification/save-notification', [
                                                        'recipientType' => 'ao',
                                                        'recipientId' => $aircraftOwner->ao_id,
'message' =>
    'New MRO recommendation received for request #' . $request->request_id .
    '. From ' . $fromMro->username .
    ' recommending ' . $toMro->username . '.',
                                                        'actions' =>  Yii::$app->request->baseUrl .'/conversations', // Or any route you want

                                                    ]);
                                                }
                    // Set a success flash message
                    Yii::$app->session->setFlash('success', 'MRO profile recommended successfully and email sent to the aircraft owner.');
    
                    // Check if a conversation already exists for the given request
                    $conversation = Conversations::findOne(['request_id' => $id]);
    
                    // If conversation does not exist, create a new one
                    if (!$conversation) {
                        // Create a new chat
                        $chat = new Chat();
                        $chat->mro_id = $mro_id;
                        $chat->ao_id = $request->ao_id;
                        $chat->request_id = $id;
                        $chat->save();
    
                        // Create a new conversation
                        $conversation = new Conversations();
                        $conversation->request_id = $id;
                        $conversation->chat_id = $chat->chat_id;
                        $conversation->sender_id = $chat->mro_id;
                        $conversation->sender_type = 'mro';
                        $conversation->receiver_id = $chat->ao_id;
                        $conversation->receiver_type = 'ao';
                        $conversation->message ="Dear Aircraft Owner, We have recommended the following MRO profile for your aircraft request Mro username : ".MroProfile::findOne($selectedMroId)->username;
                        $conversation->save();
                    }
    
                    // Redirect to view the conversation
                    // SECURITY: keep the chat identifier signed in browser-facing URLs.
                    return $this->redirect([
                        'conversations/view',
                        'chat_id' => UrlIdHelper::encode($conversation->chat_id),
                    ]);
                } else {
                    Yii::$app->session->setFlash('error', 'Aircraft owner not found.');
                }
            } else {
                Yii::$app->session->setFlash('error', 'No MRO selected.');
            }
    
            return $this->redirect(['index']);
        }
    
        return $this->render('recommend-mro', [
            'requests' => $mros,
            'model' => $model,
        ]);
    }
    
    
    
    
}
