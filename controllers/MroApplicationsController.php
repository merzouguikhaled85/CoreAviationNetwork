<?php

namespace app\controllers;

use app\models\AoNotificationsPreferences;
use app\models\AoProfile;
use app\models\AoRequestsApplications;
use app\models\Appointment;
use app\models\Chat;
use app\models\Conversations;
use app\models\Feedback;
use app\models\RepairReport;
use Yii;
use yii\filters\AccessControl;
use yii\data\Pagination; // Pagination Yii2 pour limiter le nombre de lignes par page
use yii\web\Controller;
use app\models\MroRequestApply;
use app\models\Requests;
use app\models\RequestChangeEvent;
use yii\helpers\VarDumper;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use app\components\UrlIdHelper;


class MroApplicationsController extends Controller
{
    /**
     * MRO ACTION IDS 2026: accept signed public IDs while keeping old numeric
     * bookmarks functional during migration. New links always use UrlIdHelper.
     */
    private function decodeActionId($value): int
    {
        $decoded = UrlIdHelper::decode((string) $value);

        if ($decoded !== null) {
            return (int) $decoded;
        }

        if (ctype_digit((string) $value) && (int) $value > 0) {
            return (int) $value;
        }

        throw new NotFoundHttpException('Invalid application link.');
    }

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
                            // Allow all actions for 'mro' user type
                            if (Yii::$app->session->get('user_type') == 'mro') {
                                return true; // Allow access to all actions for 'mro'
                            }
                            
                            // Allow 'view-answer' action for 'ao' user type
                            if (Yii::$app->session->get('user_type') == 'ao' && $action->id === 'view-answer') {
                                return true; // Allow access to 'view-answer' for 'ao'
                            }
                            
                            return false; // Deny access for other cases
                        }
                    ],
                ],
            ],
        ];
    }

    /**
     * Rend la page complète pendant une navigation standard et uniquement la liste
     * pendant une actualisation du gestionnaire central. L'en-tête X-CAN-Fragment
     * est exigé en plus du marqueur AJAX pour ne pas changer le résultat d'autres
     * appels asynchrones qui pourraient utiliser ces mêmes actions Yii2.
     *
     * @param array $params Résultats déjà filtrés par l'action métier appelante.
     * @return string
     */
    protected function renderApplicationsIndex(array $params)
    {
        if (
            Yii::$app->request->isAjax
            && Yii::$app->request->headers->get('X-CAN-Fragment') === 'request-list'
        ) {
            return $this->renderPartial('_application-list', $params);
        }

        return $this->render('index', $params);
    }
    
    /**
     * Open Requests page.
     *
     * Ajouts :
     * - Filtre/recherche via GET: ?search=...
     * - Compatibilite avec ancien parametre ?q=...
     * - Pagination Yii2
     * - Conservation de l'ancien flux PO Loaded / Accept PO / Start Work
     */
    public function actionIndex()
    {
        /*
         * CURSEUR DE SYNCHRONISATION : capturé avant les candidatures afin que
         * les statuts modifiés pendant le rendu ne puissent jamais être ignorés.
         */
        $syncCursor = (int) (RequestChangeEvent::find()->max('id') ?: 0);

        // Recuperer l'identifiant du MRO connecte depuis la session.
        $mro_id = Yii::$app->session->get('mro_id');

        // Mot cle de recherche saisi dans la vue index.
        // On accepte aussi "q" pour rester compatible avec ton ancien code.
        $search = trim((string) Yii::$app->request->get('search', Yii::$app->request->get('q', '')));
        $searchLower = mb_strtolower($search, 'UTF-8');

        // Recuperer toutes les applications du MRO.
        // On garde all() car le filtrage depend aussi des statuts calcules: PO Loaded, report_submitted, etc.
        $appliedRequests = MroRequestApply::find()
            ->where(['mro_id' => $mro_id])
            ->orderBy(['request_id' => SORT_DESC])
            ->all();

        $filteredRequests = [];

        foreach ($appliedRequests as $appliedRequest) {
            // Charger la request liee a l'application MRO.
            $request = Requests::findOne($appliedRequest->request_id);

            // Si la request a ete supprimee, on ignore cette ligne.
            if ($request === null) {
                continue;
            }

            // Verifier si un PO existe pour cette application.
            // Important: si status = answered + PO existe, on doit afficher "PO Loaded"
            // afin que le bouton "Accept PO" reste visible.
            $aoRequestApplication = AoRequestsApplications::find()
                ->where(['application_id' => $appliedRequest->id])
                ->andWhere(['not', ['po' => null]])
                ->andWhere(['<>', 'po', ''])
                ->orderBy(['id' => SORT_DESC])
                ->one();

            $hasPo = $aoRequestApplication !== null;

            // Page Open Requests: exclure les demandes closed et created.
            if ($request->status === 'closed' || $request->status === 'created') {
                continue;
            }

            // Ancien flux: answered sans PO ne doit pas encore apparaitre ici.
            if ($request->status === 'answered' && !$hasPo) {
                continue;
            }

            // Calculer le status affiche dans la vue.
            $status = (string) $request->status;

            // Si l'AO a charge un PO, afficher PO Loaded.
            if ($status === 'answered' && $hasPo) {
                $status = 'po_loaded';
            }

            // Si un CRS / repair report existe, afficher Report Submitted
            // sauf si la request est deja closed ou canceled.
            $reportExists = RepairReport::find()
                ->where(['mro_request_apply_id' => $appliedRequest->id])
                ->exists();

            if ($reportExists && !in_array($status, ['closed', 'canceled'], true)) {
                $status = 'report_submitted';
            }

            // Appliquer le filtre seulement si l'utilisateur a saisi un mot cle.
            if ($search !== '') {
                $aircraft = $request->getAircraft()->one();
                $ao = $request->aO ?? null;

                // Texte global utilise pour la recherche.
                // Tu peux ajouter ici d'autres champs si besoin.
                $haystack = mb_strtolower(implode(' ', [
                    $request->request_id,
                    $request->aircraft_registration,
                    $request->serial_number,
                    $request->location,
                    $request->maintenance_location ?? '',
                    $request->eta,
                    $request->etd,
                    $status,
                    str_replace('_', ' ', $status),
                    $aircraft ? $aircraft->model : '',
                    $ao ? ($ao->username ?? '') : '',
                    $ao ? ($ao->email ?? '') : '',
                ]), 'UTF-8');

                // Si le mot cle n'existe pas dans les champs recherches, on ignore la ligne.
                if (strpos($haystack, $searchLower) === false) {
                    continue;
                }
            }

            $filteredRequests[] = $appliedRequest;
        }

        // Pagination apres filtrage.
        $pagination = new Pagination([
            'totalCount' => count($filteredRequests),
            'pageSize' => 10, // Nombre de lignes par page
            'pageSizeParam' => false, // Evite la modification du pageSize depuis l'URL
            'params' => Yii::$app->request->queryParams, // Conserve search pendant la navigation pagination
        ]);

        // Extraire uniquement les lignes de la page actuelle.
        $paginatedRequests = array_slice(
            $filteredRequests,
            $pagination->offset,
            $pagination->limit
        );

        return $this->renderApplicationsIndex([
            'appliedRequests' => $paginatedRequests,
            'pagination' => $pagination,
            'search' => $search,
            'title' => 'Open Requests',
            'syncCursor' => $syncCursor,
        ]);
    }

    /**
     * Closed Requests page.
     *
     * Meme filtre + meme pagination que actionIndex(), mais seulement pour status = closed.
     */
    public function actionClosedRequests()
    {
        /*
         * CURSEUR DE SYNCHRONISATION : conserve le dernier événement connu avant
         * la lecture de l'historique, y compris lorsque l'onglet démarre masqué.
         */
        $syncCursor = (int) (RequestChangeEvent::find()->max('id') ?: 0);

        // Recuperer l'identifiant du MRO connecte.
        $mro_id = Yii::$app->session->get('mro_id');

        /*
         * CLOSED HISTORY: show the last three months by default.
         * A four-digit value allows the user to display one complete year.
         */
        $historyPeriod = trim((string) Yii::$app->request->get('period', '3months'));

        if ($historyPeriod !== '3months' && !preg_match('/^\d{4}$/', $historyPeriod)) {
            $historyPeriod = '3months';
        }

        $threeMonthsAgo = strtotime('-3 months');
        $historyYears = [];
        $closureTimestamps = [];

        // Mot cle de recherche venant du formulaire GET.
        $search = trim((string) Yii::$app->request->get('search', Yii::$app->request->get('q', '')));
        $searchLower = mb_strtolower($search, 'UTF-8');

        // Recuperer toutes les applications du MRO.
        $appliedRequests = MroRequestApply::find()
            ->where(['mro_id' => $mro_id])
            ->orderBy(['request_id' => SORT_DESC])
            ->all();

        $filteredRequests = [];

        foreach ($appliedRequests as $appliedRequest) {
            $request = Requests::findOne($appliedRequest->request_id);

            // Ignorer si la request n'existe plus.
            if ($request === null) {
                continue;
            }

            // Page Closed Requests: garder uniquement les demandes fermees.
            if ($request->status !== 'closed') {
                continue;
            }

            /*
             * CLOSED HISTORY: an approved repair report is the event that closes
             * the request. Its automatically maintained updated_at is therefore
             * used as the closure date without changing the existing workflow.
             */
            $closedAt = RepairReport::find()
                ->alias('closing_report')
                ->select('closing_report.updated_at')
                ->innerJoin(
                    ['closing_application' => MroRequestApply::tableName()],
                    'closing_application.id = closing_report.mro_request_apply_id'
                )
                ->where([
                    'closing_application.request_id' => $request->request_id,
                    'closing_report.quote_approved' => 1,
                ])
                ->orderBy([
                    'closing_report.updated_at' => SORT_DESC,
                    'closing_report.repair_report_id' => SORT_DESC,
                ])
                ->scalar();

            $closedTimestamp = $closedAt ? strtotime((string) $closedAt) : false;

            if ($closedTimestamp !== false) {
                $closedYear = (int) date('Y', $closedTimestamp);
                $historyYears[$closedYear] = $closedYear;
                $closureTimestamps[$appliedRequest->id] = $closedTimestamp;
            }

            // Apply the selected history period before the text search.
            if ($historyPeriod === '3months') {
                if ($closedTimestamp === false || $closedTimestamp < $threeMonthsAgo) {
                    continue;
                }
            } elseif ($closedTimestamp === false || date('Y', $closedTimestamp) !== $historyPeriod) {
                continue;
            }

            // Appliquer la recherche si un mot cle est saisi.
            if ($search !== '') {
                $aircraft = $request->getAircraft()->one();
                $ao = $request->aO ?? null;

                $haystack = mb_strtolower(implode(' ', [
                    $request->request_id,
                    $request->aircraft_registration,
                    $request->serial_number,
                    $request->location,
                    $request->maintenance_location ?? '',
                    $request->eta,
                    $request->etd,
                    $request->status,
                    str_replace('_', ' ', $request->status),
                    $aircraft ? $aircraft->model : '',
                    $ao ? ($ao->username ?? '') : '',
                    $ao ? ($ao->email ?? '') : '',
                ]), 'UTF-8');

                if (strpos($haystack, $searchLower) === false) {
                    continue;
                }
            }

            $filteredRequests[] = $appliedRequest;
        }

        // Display available years from newest to oldest, like an order history.
        rsort($historyYears, SORT_NUMERIC);

        usort($filteredRequests, static function ($left, $right) use ($closureTimestamps) {
            $leftTimestamp = $closureTimestamps[$left->id] ?? 0;
            $rightTimestamp = $closureTimestamps[$right->id] ?? 0;

            return $rightTimestamp <=> $leftTimestamp;
        });

        // Pagination apres filtrage.
        $pagination = new Pagination([
            'totalCount' => count($filteredRequests),
            'pageSize' => 10,
            'pageSizeParam' => false,
            'params' => Yii::$app->request->queryParams,
        ]);

        $paginatedRequests = array_slice(
            $filteredRequests,
            $pagination->offset,
            $pagination->limit
        );

        return $this->renderApplicationsIndex([
            'appliedRequests' => $paginatedRequests,
            'pagination' => $pagination,
            'search' => $search,
            'title' => 'Closed Requests',
            'historyPeriod' => $historyPeriod,
            'historyYears' => $historyYears,
            'syncCursor' => $syncCursor,
        ]);
    }

    public function actionViewAnswer($id)
    {
        // Decode encrypted ID from URL
    $id = UrlIdHelper::decode($id);

    // If token is invalid, stop request
    if (!$id) {
        throw new NotFoundHttpException('Invalid request link.');
    }
        // Find the mroRequestsApplications record by ID
        $mroRequestApplication = MroRequestApply::findOne($id);

        if ($mroRequestApplication) {
            // You can render a view or perform any other necessary actions here
            return $this->render('view-answer', [
                'mroRequestApplication' => $mroRequestApplication,
            ]);
        } else {
            // Handle the case when the record is not found
            throw new \yii\web\NotFoundHttpException('The requested page does not exist.');
        }
    }
    public function actionCancelApplication($id)
{
    // MRO ACTION IDS 2026: resolve the signed application identifier.
    $id = $this->decodeActionId($id);
    $mroRequestApplication = MroRequestApply::findOne($id);
    if ($mroRequestApplication) {
        $requestId = $mroRequestApplication->request_id;
        $mroRequestApplication->delete();

        // Check if there are no more applications for the request
        $applicationsCount = MroRequestApply::find()->where(['request_id' => $requestId])->count();
        if ($applicationsCount === 0) {
            // Update request status to 'created'
            $request = Requests::findOne($requestId);
            if ($request) {
                $request->status = 'created';
                $request->save();
            }
        }

        Yii::$app->session->setFlash('success', 'Application canceled successfully.');
    } else {
        Yii::$app->session->setFlash('error', 'Failed to cancel application.');
    }

    return $this->redirect(['index']);
}
public function actionViewPo($id)
{
    /*
     * VIEW PO CANONICAL ID 2026: old numeric bookmarks are redirected once to
     * the signed public URL. All links generated by the application are encoded.
     */
    if (ctype_digit((string) $id) && (int) $id > 0) {
        return $this->redirect([
            'view-po',
            'id' => UrlIdHelper::encode((int) $id),
        ]);
    }

    // MRO ACTION IDS 2026: view-po now uses the same signed ID as the list.
    $id = $this->decodeActionId($id);
    $aoRequestsApplications = AoRequestsApplications::find()->where(['application_id' => $id])->all();

    return $this->render('view-po', [
        'aoRequestsApplications' => $aoRequestsApplications,
    ]);
}
public function actionAcceptPo($id)
{
    // MRO ACTION IDS 2026: accept-po receives the encoded MRO application ID.
    $id = $this->decodeActionId($id);
    // The view sends MroRequestApply::id. Keep a fallback for older links
    // that may send AoRequestsApplications::id directly.
    $application = MroRequestApply::findOne($id);

    if ($application) {
        $aoRequestApplication = AoRequestsApplications::find()
            ->where(['application_id' => $application->id])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        $request = Requests::findOne($application->request_id);
    } else {
        $aoRequestApplication = AoRequestsApplications::findOne($id);
        $request = $aoRequestApplication ? $aoRequestApplication->getRequest()->one() : null;
    }

    if (!$aoRequestApplication || !$request) {
        throw new NotFoundHttpException('The requested page does not exist.');
    }

    if (empty($aoRequestApplication->po)) {
        Yii::$app->session->setFlash('error', 'Cannot accept PO: no PO file has been uploaded yet.');
        return $this->redirect(['index']);
    }

    $request->status = 'work_accepted';
    if (!$request->save(false)) {
        Yii::$app->session->setFlash('error', 'Failed to accept PO.');
        return $this->redirect(['index']);
    }

    $ao = $request->aO;
    $aoNotificationsPreferences = $ao ? AoNotificationsPreferences::findOne(['ao_id' => $ao->ao_id]) : null;

    if ($ao && $aoNotificationsPreferences && $aoNotificationsPreferences->notify_po_acceptance && $aoNotificationsPreferences->notify_by_email) {
        Yii::$app->mailer->compose('ao_application_accepted', ['application' => $aoRequestApplication])
            ->setTo($ao->email)
            ->setSubject('PO Accepted')
            ->send();
    }

    if ($ao && $aoNotificationsPreferences && $aoNotificationsPreferences->notify_po_acceptance && $aoNotificationsPreferences->notify_by_platform) {
        Yii::$app->runAction('notification/save-notification', [
            'recipientType' => 'ao',
            'recipientId'   => $ao->ao_id,
            'message'       => 'The PO has been accepted.',
            'actions'       => Yii::$app->request->baseUrl . '/requests/open-requests',
        ]);
    }

    Yii::$app->session->setFlash('success', 'PO accepted successfully.');
    return $this->redirect(['index']);
}

public function actionSetAppointment($app_request_id)
{
    $app_request_id = UrlIdHelper::decode($app_request_id);

    if (!$app_request_id) {
        throw new NotFoundHttpException('Invalid request link.');
    }
    $appointment = new Appointment();
    $application = MroRequestApply::findOne($app_request_id);

    if (!$application) {
        throw new \yii\web\NotFoundHttpException('The requested application does not exist.');
    }

    if (Yii::$app->request->isPost) {

        $appointment->load(Yii::$app->request->post());
                    // Convert to proper MySQL DATETIME format
    if (!empty($appointment->appointment_date)) {
        $appointment->appointment_date = date('Y-m-d H:i:s', strtotime($appointment->appointment_date));
    }
        $appointment->mro_id = Yii::$app->session->get('mro_id');
        $appointment->ao_id = $application->getRequest()->one()->ao_id;
        $appointment->request_id = $application->request_id;
        $appointment->ao_requests_applications_id = $app_request_id;
        $appointment->status = 'waiting response';


        if ($appointment->save()) {
            // Send email notification to the AO
            $ao = AoProfile::findOne($appointment->ao_id) ;
            $aoNotificationsPreferences = AoNotificationsPreferences::findOne(['ao_id' => $ao->ao_id]);

            if ($aoNotificationsPreferences && $aoNotificationsPreferences->notify_appointment_requests && $aoNotificationsPreferences->notify_by_email) {
            Yii::$app->mailer->compose('appointment_created', ['application' => $appointment])
                ->setTo($ao->email)
                
                ->setSubject('New appointment request')
                ->send();
            }
                              //platform notification
                              if ($aoNotificationsPreferences && $aoNotificationsPreferences->notify_appointment_requests && $aoNotificationsPreferences->notify_by_platform) {
                                // Call the action to save the notification
                                Yii::$app->runAction('notification/save-notification', [
                                    'recipientType' => 'ao',
                                    'recipientId' => $ao->ao_id,
                                    'message' => 'A new appointment has been requested.',
                                        'actions' =>  Yii::$app->request->baseUrl .'/ao-appointments', // Or any route you want

                                ]);
                            }
            Yii::$app->session->setFlash('success', 'Appointment set successfully.');
            return $this->redirect(['mro-applications/index']);
        } else {
            Yii::$app->session->setFlash('error', 'Failed to set appointment.');
        }
    }

    return $this->render('set-appointment', [
        'appointment' => $appointment,
        'application' => $application,
    ]);
}

public function actionStartWork($id)
{
    // MRO ACTION IDS 2026: start-work receives the encoded request ID.
    $id = $this->decodeActionId($id);
    $request = Requests::findOne($id);
    if ($request) {
        $request->status = 'work_started';
        if ($request->save()) {

            // Send email notification to the AO
            $ao = $request->aO;
            $aoNotificationsPreferences = AoNotificationsPreferences::findOne(['ao_id' => $ao->ao_id]);
            if ($aoNotificationsPreferences && $aoNotificationsPreferences->notify_work_start && $aoNotificationsPreferences->notify_by_email) {
                Yii::$app->mailer->compose('work_started', ['request' => $request])
                    ->setTo($ao->email)
                    
                    ->setSubject('Work Started')
                    ->send();
                }

                
                  //platform notification
                  if ($aoNotificationsPreferences && $aoNotificationsPreferences->notify_work_start && $aoNotificationsPreferences->notify_by_platform) {
                    // Call the action to save the notification
                    Yii::$app->runAction('notification/save-notification', [
                        'recipientType' => 'ao',
                        'recipientId' => $ao->ao_id,
                        'message' => 'The work has been started for your request',
                                                                'actions' =>  Yii::$app->request->baseUrl .'/requests/open-requests', // Or any route you want

                    ]);
                }

            Yii::$app->session->setFlash('success', 'Work has been started successfully');
        } else {
            Yii::$app->session->setFlash('error', 'Failed to start work.');
        }
    } else {
        Yii::$app->session->setFlash('error', 'Request not found.');
    }

    return $this->redirect(['index']);
}

// public function actionSubmitReport($id)
// {
//     $request = MroRequestApply::findOne($id);

//     if (!$request) {
//         throw new NotFoundHttpException('Request not found.');
//     }

//     $model = new RepairReport();
//     $model->mro_request_apply_id = $request->id;

//     if ($model->load(Yii::$app->request->post())) {
//         // Retrieve the uploaded file instance
//         $model->CRS_attachment = UploadedFile::getInstance($model, 'CRS_attachment');
//         // Check if the file was uploaded
//         if ($model->CRS_attachment) {
//             // Generate a unique file name
//             $fileName = 'upload_' . Yii::$app->security->generateRandomString(10) . '.' . $model->CRS_attachment->extension;
//             // Move the uploaded file to the desired location
//             $model->CRS_attachment->saveAs('uploads/' . $fileName);
//             // Set the full path to the model attribute
//             $model->CRS_attachment =  $fileName;
//         }
//        // die();
//         if ($model->save()) {
//             // Send email notification to the AO
//             $ao = Requests::findOne($request->request_id)->aO ;
            
//             $aoNotificationsPreferences = AoNotificationsPreferences::findOne(['ao_id' => $ao->ao_id]);
//             if ($aoNotificationsPreferences && $aoNotificationsPreferences->notify_report_submissions && $aoNotificationsPreferences->notify_by_email) {
//                 Yii::$app->mailer->compose('report_submissions', ['RepairReport' => $model])
//                     ->setTo($ao->email)
                    
//                     ->setSubject('New report submited')
//                     ->send();
//                 }

//                   //platform notification
//                   if ($aoNotificationsPreferences && $aoNotificationsPreferences->notify_report_submissions && $aoNotificationsPreferences->notify_by_platform) {
//                     // Call the action to save the notification
//                     Yii::$app->runAction('notification/save-notification', [
//                         'recipientType' => 'ao',
//                         'recipientId' => $ao->ao_id,
//                         'message' => 'A new report has been submitted for your request.',
//                                                                                         'actions' =>  Yii::$app->request->baseUrl .'/requests/open-requests', // Or any route you want

//                     ]);
//             }

//             // Update the status of the request if CRS is checked
//            // if ($model->CRSed) {
//                 $brequest = $model->getRequest();
//                 $brequest->status = 'report_submitted';
//                 $brequest->save(false); // Save the request without validation
//             //}

//             Yii::$app->session->setFlash('success', 'Report submitted successfully.');
//             return $this->redirect(['index']);
//         }
//     }

//     return $this->render('submit-report', [
//         'model' => $model,
//     ]);
// }

public function actionSubmitReport($id)
{
    /*
    |--------------------------------------------------------------------------
    | Decode encrypted ID from URL
    |--------------------------------------------------------------------------
    */
    $id = UrlIdHelper::decode($id);

    if (!$id) {
        throw new NotFoundHttpException('Invalid request link.');
    }

    /*
    |--------------------------------------------------------------------------
    | Find MRO application
    |--------------------------------------------------------------------------
    | $id is the MroRequestApply id after decoding.
    */
    $application = MroRequestApply::findOne($id);

    if (!$application) {
        throw new NotFoundHttpException('Request not found.');
    }

    /*
    |--------------------------------------------------------------------------
    | Find linked Request
    |--------------------------------------------------------------------------
    | Correction importante:
    | Ne pas utiliser $model->getRequest()->one() ici.
    | Dans ton code actuel, cela provoque:
    | Calling unknown method: app\models\Requests::one()
    */
    $req = Requests::findOne($application->request_id);

    if ($req === null) {
        throw new NotFoundHttpException('Linked request not found.');
    }

    /*
    |--------------------------------------------------------------------------
    | Create RepairReport model
    |--------------------------------------------------------------------------
    */
    $model = new RepairReport();
    $model->mro_request_apply_id = $application->id;

    /*
    |--------------------------------------------------------------------------
    | Handle form submit
    |--------------------------------------------------------------------------
    */
    if ($model->load(Yii::$app->request->post())) {

        /*
        |----------------------------------------------------------------------
        | CRS attachment upload - optional
        |----------------------------------------------------------------------
        */
        $uploadedFile = UploadedFile::getInstance($model, 'CRS_attachment');

        if ($uploadedFile !== null) {
            $fileName = 'upload_' . Yii::$app->security->generateRandomString(10) . '.' . $uploadedFile->extension;
            $uploadDir = Yii::getAlias('@webroot/uploads/');

            // Create uploads directory if it does not exist.
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $target = $uploadDir . $fileName;

            if ($uploadedFile->saveAs($target)) {
                // Store only the file name in database.
                $model->CRS_attachment = $fileName;
            } else {
                Yii::$app->session->setFlash('error', 'Failed to upload CRS attachment.');
                return $this->refresh();
            }
        } else {
            // Attachment is optional.
            $model->CRS_attachment = null;
        }

        /*
        |----------------------------------------------------------------------
        | Save RepairReport
        |----------------------------------------------------------------------
        */
        if ($model->save()) {

            /*
            |------------------------------------------------------------------
            | Notify AO
            |------------------------------------------------------------------
            | Business flow unchanged:
            | - send email if AO preferences allow it
            | - send platform notification if AO preferences allow it
            */
            if ($req->aO) {
                $ao = $req->aO;

                $aoPrefs = AoNotificationsPreferences::findOne([
                    'ao_id' => $ao->ao_id,
                ]);

                if (
                    $aoPrefs
                    && $aoPrefs->notify_report_submissions
                    && $aoPrefs->notify_by_email
                ) {
                    Yii::$app->mailer
                        ->compose('report_submissions', [
                            'RepairReport' => $model,
                        ])
                        ->setTo($ao->email)
                        ->setSubject('New report submitted')
                        ->send();
                }

                if (
                    $aoPrefs
                    && $aoPrefs->notify_report_submissions
                    && $aoPrefs->notify_by_platform
                ) {
                    Yii::$app->runAction('notification/save-notification', [
                        'recipientType' => 'ao',
                        'recipientId'   => $ao->ao_id,
                        'message'       => 'A new report has been submitted for your request.',
                        'actions'       => Yii::$app->request->baseUrl . '/requests/open-requests',
                    ]);
                }
            }

            /*
            |------------------------------------------------------------------
            | Update request status
            |------------------------------------------------------------------
            */
            $req->status = 'report_submitted';
            // If you have a constant, you can use:
            // $req->status = Requests::STATUS_REPORT_SUBMITTED;

            $req->save(false);

            Yii::$app->session->setFlash('success', 'Report submitted successfully.');
            return $this->redirect(['index']);
        }

        Yii::$app->session->setFlash('error', 'Failed to save report. Please check the form.');
    }

    /*
    |--------------------------------------------------------------------------
    | Render submit-report page
    |--------------------------------------------------------------------------
    | request is passed to the view for the pro stepper/design.
    */
    return $this->render('submit-report', [
        'model'       => $model,
        'application' => $application,
        'request'     => $req,
    ]);
}

public function actionViewReports($id)
{
    $id = UrlIdHelper::decode($id);

    if (!$id) {
        throw new NotFoundHttpException('Invalid request link.');
    }
    // Find the request by ID
    $request = MroRequestApply::findOne($id);

    // Check if the request exists
    if (!$request) {
        throw new NotFoundHttpException('Request not found.');
    }

    // Find all repair reports related to the request
    $repairReports = RepairReport::find()->where(['mro_request_apply_id' => $id])->all();

    return $this->render('view-reports', [
        'request' => $request,
        'repairReports' => $repairReports,
    ]);
}
public function actionProvideNewQuote($id)
{
    // SIGNED REPORT ID: decode before opening or updating the revision form.
    $id = UrlIdHelper::decodeOrFail($id, 'Invalid report link.');
    $report = RepairReport::findOne($id);

    if (!$report) {
        throw new NotFoundHttpException('Report not found.');
    }

    // Check if the request has already been submitted
    if (!empty($report->mro_quote)) {
        Yii::$app->session->setFlash('error', 'A quote has already been provided for this report.');
        return $this->redirect(['view-reports', 'id' => UrlIdHelper::encode($report->mro_request_apply_id)]);
    }

    if (Yii::$app->request->isPost) {
        $post = Yii::$app->request->post();
        
        // Load the uploaded file into $mroAttachment
        $mroAttachment = UploadedFile::getInstance($report, 'mro_attachment');
        
        // Validate and save the provided quote and attachment to the repair report
        $report->mro_quote = $post['RepairReport']['mro_quote'];

        if ($mroAttachment !== null) {
            $uploadPath = Yii::getAlias('@webroot/uploads/');
            $fileName = Yii::$app->security->generateRandomString(12) . '.' . $mroAttachment->extension;
            $filePath = $uploadPath . $fileName;

            if ($mroAttachment->saveAs($filePath)) {
                $report->mro_attachment = 'uploads/' . $fileName;
            } else {
                Yii::$app->session->setFlash('error', 'Failed to upload attachment.');
                return $this->refresh(); // Reload the page to show errors
            }
        }

        if ($report->save()) {
            Yii::$app->session->setFlash('success', 'New quote provided successfully.');
            return $this->redirect(['view-reports', 'id' => UrlIdHelper::encode($report->mro_request_apply_id)]);
        } else {
            Yii::$app->session->setFlash('error', 'Failed to save the new quote.');
        }
    }

    return $this->render('provide-new-quote', [
        'report' => $report,
    ]);
}

public function actionAcceptUpdate($id)
{
    // SIGNED REQUEST ID: retain the current business workflow with a protected URL token.
    $id = UrlIdHelper::decodeOrFail($id, 'Invalid request link.');
    $request = Requests::findOne($id);

    $mroRequest = MroRequestApply::find()
    ->where(['request_id' => $id])
    ->orderBy(['id' => SORT_DESC])
    ->one();
    // Ensure the current status is update_request
    if ($request->status !== Requests::STATUS_UPDATE_REQUEST) {
        throw new NotFoundHttpException('Invalid request status.');
    }

    // Set status to update_request_accepted
    $request->status = Requests::STATUS_UPDATE_REQUEST_ACCEPTED;

    // Save the updated status
    if ($request->save()) {

        $ao = $request->aO;
        $aoNotificationsPreferences = AoNotificationsPreferences::findOne(['ao_id' => $ao->ao_id]);
        if ($aoNotificationsPreferences &&  $aoNotificationsPreferences->notify_by_email) {
            Yii::$app->mailer->compose('update_request_approved', ['request' => $request])
            ->setTo($request->aO->email)
            
            ->setSubject('Update Request Approved')
            ->send();
            }
                                              //platform notification
                                              if ($aoNotificationsPreferences &&  $aoNotificationsPreferences->notify_by_platform) {
                                                // Call the action to save the notification
                                                Yii::$app->runAction('notification/save-notification', [
                                                    'recipientType' => 'ao',
                                                    'recipientId' => $ao->ao_id,
                                                    'message' => 'Update request approved.',
                                                                                                                    'actions' =>  Yii::$app->request->baseUrl .'/requests/new-requests', // Or any route you want

                                                ]);
                                            }


        Yii::$app->session->setFlash('success', 'Update request accepted successfully.');
    } else {
        Yii::$app->session->setFlash('error', 'Failed to accept update request.');
    }

    return $this->redirect([
        'mro-requests/applyupdate',
        'id' => UrlIdHelper::encode($mroRequest->id),
    ]);
}
public function actionDenyRequest($id)
{
    // SIGNED REQUEST ID: retain the current business workflow with a protected URL token.
    $id = UrlIdHelper::decodeOrFail($id, 'Invalid request link.');
    $request = Requests::findOne($id);

    // Initially set the status to update_request_denied
    $request->status = Requests::STATUS_UPDATE_REQUEST_DENIED;

    // Save the updated status
    if ($request->save()) {
        $ao = $request->aO;
        $aoNotificationsPreferences = AoNotificationsPreferences::findOne(['ao_id' => $ao->ao_id]);

        // Sending email notification
        if ($aoNotificationsPreferences && $aoNotificationsPreferences->notify_by_email) {
            Yii::$app->mailer->compose('update_request_denied', ['request' => $request])
                ->setTo($request->aO->email)
                
                ->setSubject('Update Request Denied')
                ->send();
        }

        // Sending platform notification
        if ($aoNotificationsPreferences && $aoNotificationsPreferences->notify_by_platform) {
            Yii::$app->runAction('notification/save-notification', [
                'recipientType' => 'ao',
                'recipientId' => $ao->ao_id,
                'message' => 'Update request Denied.',
            ]);
        }


            $request->status = Requests::STATUS_WORK_STARTED;
            $message = 'Work continued successfully.';
       

        if ($request->save()) {
            Yii::$app->session->setFlash('success', $message);
        } else {
            Yii::$app->session->setFlash('error', 'Failed to update request status.');
        }
    } else {
        Yii::$app->session->setFlash('error', 'Failed to deny request update.');
    }

    return $this->redirect(['index']);
}

public function actionViewFeedback($id)
{
   $id = UrlIdHelper::decode($id);

    if (!$id) {
        throw new NotFoundHttpException('Invalid request link.');
    }
    // Load the request based on $id
    $request = Requests::findOne($id);

    if (!$request) {
        throw new NotFoundHttpException('The requested page does not exist.');
    }

    // Check if the request is closed and if there's existing feedback
    if ($request->status == 'closed') {
        $feedback = Feedback::findOne(['request_id' => $request->request_id]);

        if ($feedback) {
            /*
             * CONTRÔLE D'APPARTENANCE : l'identifiant signé protège l'URL contre
             * l'altération, mais l'autorisation métier reste explicite. Un MRO ne
             * peut afficher que le feedback associé à son propre profil connecté.
             */
            $connectedMroId = (int) Yii::$app->session->get('mro_id');
            if ($connectedMroId <= 0 || (int) $feedback->mro_id !== $connectedMroId) {
                throw new NotFoundHttpException('The requested feedback does not exist.');
            }

            // Render the view for feedback
            return $this->render('view-feedback', [
                'feedback' => $feedback,
                'request' =>$request,
            ]);
        } else {
            // If there's no feedback found, handle accordingly
            Yii::$app->session->setFlash('error', 'No feedback found for this request.');
        }
    } else {
        // Handle case where request is not closed
        Yii::$app->session->setFlash('error', 'Feedback can only be viewed for closed requests.');
    }

    // Redirect back to the request view or any appropriate action
    return $this->redirect(['index']);
}

public function actionContact($id)
{
    $id = UrlIdHelper::decode($id);

    if (!$id) {
        throw new NotFoundHttpException('Invalid request link.');
    }
    $mroRequestApplication = MroRequestApply::findOne($id);
    // Check if a conversation already exists for the given request
    $conversation = Conversations::findOne(['request_id' => $mroRequestApplication->request_id]);

    // If conversation does not exist, create a new one
    if (!$conversation) {
        // Create a new chat
        $chat = new Chat();
        $chat->mro_id =  $mroRequestApplication->mro_id;
        $chat->ao_id = $mroRequestApplication->getRequest()->one()->ao_id;
        $chat->request_id =  $mroRequestApplication->request_id;

        $chat->save();

        // Create a new conversation
        $conversation = new Conversations();
        $conversation->request_id =  $mroRequestApplication->request_id;
        $conversation->chat_id = $chat->chat_id;
        $conversation->sender_id = $chat->mro_id;
        $conversation->sender_type ='mro';
        $conversation->receiver_id = $chat->ao_id;
        $conversation->receiver_type ='ao';
        // Additional attributes initialization if needed
        $conversation->save();

          //platform notification
          $aoNotificationsPreferences = AoNotificationsPreferences::findOne(['ao_id' => $chat->ao_id]);
          if ($aoNotificationsPreferences && $aoNotificationsPreferences->notify_by_platform) {
            // Call the action to save the notification
            Yii::$app->runAction('notification/save-notification', [
                'recipientType' => 'ao',
                'recipientId' => $chat->ao_id,
                'message' => 'New message recived.',
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


}
