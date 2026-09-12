<?php

namespace app\controllers;

use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\web\Controller;
use app\models\Appointment;
use app\models\Aircrafts;
use app\models\AoRequestsApplications;
use app\models\MroNotificationsPreferences;
use app\models\MroProfile;
use app\models\Requests;
use app\models\RequestChangeEvent;
use app\components\UrlIdHelper;
use yii\web\NotFoundHttpException;

class AoAppointmentsController extends Controller
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
                            // Allow access only to AO users.
                            return in_array(Yii::$app->session->get('user_type'), ['ao']);
                        }
                    ],
                ],
            ],
        ];
    }

    /**
     * Rend la page complete pour une navigation AO normale et uniquement la carte
     * des rendez-vous pour le rafraichissement cible. Le double controle AJAX et
     * X-CAN-Fragment empeche un autre appel asynchrone de recevoir un rendu partiel
     * par erreur ; les donnees ont deja subi les filtres metier et la pagination.
     *
     * @param array $params Donnees preparees par actionIndex pour la vue.
     * @return string
     */
    protected function renderAppointmentsIndex(array $params)
    {
        if (
            Yii::$app->request->isAjax
            && Yii::$app->request->headers->get('X-CAN-Fragment') === 'request-list'
        ) {
            return $this->renderPartial('_appointment-list', $params);
        }

        return $this->render('index', $params);
    }

    public function actionIndex()
    {
        /*
         * CURSEUR DE SYNCHRONISATION : sa capture avant les rendez-vous permet
         * de récupérer tout événement créé pendant ou après le rendu de la page.
         */
        $syncCursor = (int) (RequestChangeEvent::find()->max('id') ?: 0);

        $aoId = Yii::$app->session->get('ao_id');

        // Read the same GET parameter used by the search form in the view.
        $search = trim((string) Yii::$app->request->get('search', ''));

        // Base query: show only appointments that belong to the logged-in AO.
        $query = Appointment::find()
            ->alias('a')
            ->where(['a.ao_id' => $aoId])
            ->leftJoin(['mro' => MroProfile::tableName()], 'mro.mro_id = a.mro_id')
            ->orderBy([
                'a.appointment_date' => SORT_DESC,
                'a.id' => SORT_DESC,
            ]);

        // Apply grouped search filters without breaking the AO ownership condition.
        if ($search !== '') {
            /*
             * REQUEST DETAILS SEARCH: include the operational values displayed
             * in the new Request Details column.
             */
            $aircraftIds = Aircrafts::find()
                ->select('aircraft_id')
                ->where(['or',
                    ['like', 'manufacturer', $search],
                    ['like', 'model', $search],
                    ['like', 'registration_number', $search],
                    ['like', 'serial_number', $search],
                ])
                ->column();

            $requestConditions = ['or',
                ['like', 'aircraft_registration', $search],
                ['like', 'serial_number', $search],
                ['like', 'eta', $search],
                ['like', 'etd', $search],
                ['like', 'location', $search],
                ['like', 'request_details', $search],
            ];

            if (!empty($aircraftIds)) {
                $requestConditions[] = ['in', 'aircraft_id', $aircraftIds];
            }

            $matchingRequestIds = Requests::find()
                ->select('request_id')
                ->where($requestConditions)
                ->column();

            $appointmentConditions = ['or',
                ['like', 'a.id', $search],
                ['like', 'a.request_id', $search],
                ['like', 'a.mro_id', $search],
                ['like', 'a.status', $search],
                ['like', 'a.appointment_date', $search],
                ['like', 'a.reschedule_appointment_date', $search],
                ['like', 'mro.username', $search],
                ['like', 'mro.email', $search],
            ];

            if (!empty($matchingRequestIds)) {
                $appointmentConditions[] = ['in', 'a.request_id', $matchingRequestIds];
            }

            $query->andWhere($appointmentConditions);
        }

        // Pagination compatible with the mro-appointments design applied to ao-appointments.
        $countQuery = clone $query;
        $pagination = new Pagination([
            'totalCount' => $countQuery->count(),
            'pageSize' => 7,
            'pageSizeParam' => false,
        ]);

        $appointments = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        /*
         * REQUEST DETAILS: preload Requests and Aircraft relations for this page.
         * The appointment query and workflow remain unchanged.
         */
        $requestMap = [];
        $requestIds = [];

        foreach ($appointments as $appointment) {
            if (!empty($appointment->request_id)) {
                $requestIds[] = (int) $appointment->request_id;
            }
        }

        $requestIds = array_unique($requestIds);

        if (!empty($requestIds)) {
            $requestMap = Requests::find()
                ->with(['aircraft'])
                ->where(['request_id' => $requestIds])
                ->indexBy('request_id')
                ->all();
        }

        // Optional optimization for the view: avoid repeated MRO queries in the table loop.
        $mroIds = [];
        foreach ($appointments as $appointment) {
            if (!empty($appointment->mro_id)) {
                $mroIds[] = (int) $appointment->mro_id;
            }
        }

        $mroMap = [];
        if (!empty($mroIds)) {
            $mroMap = MroProfile::find()
                ->select(['username', 'mro_id'])
                ->where(['mro_id' => array_unique($mroIds)])
                ->indexBy('mro_id')
                ->column();
        }

        /*
         * RENDU ADAPTATIF : les calculs de propriete, recherche et pagination restent
         * inchanges ; seul le format HTML depend du rafraichissement demande.
         */
        return $this->renderAppointmentsIndex([
            'appointments' => $appointments,
            'pagination' => $pagination,
            'search' => $search,
            'mroMap' => $mroMap,
            'syncCursor' => $syncCursor,
            'requestMap' => $requestMap,
        ]);
    }

    public function actionCancel($id)
    {
        // SIGNED APPOINTMENT ID: do not expose the database key in browser actions.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid appointment link.');
        $appointment = Appointment::findOne($id);

        if ($appointment) {
            // Set status to canceled.
            $appointment->status = 'canceled';

            if ($appointment->save()) {
                $mro = MroProfile::findOne($appointment->mro_id);

                if ($mro) {
                    Yii::$app->mailer->compose('appointment_canceled', ['appointment' => $appointment])
                        ->setTo($mro->email)
                        ->setSubject('appointment canceled')
                        ->send();

                    // Platform notification.
                    Yii::$app->runAction('notification/save-notification', [
                        'recipientType' => 'mro',
                        'recipientId' => $mro->mro_id,
                        'message' => 'Your appointment for Request #' . $appointment->request_id .
                            ' scheduled on ' .
                            Yii::$app->formatter->asDatetime($appointment->appointment_date) .
                            ' has been canceled.',
                        'actions' => Yii::$app->request->baseUrl . '/mro-appointments'
                    ]);
                }

                Yii::$app->session->setFlash('success', 'Appointment canceled successfully.');
            } else {
                Yii::$app->session->setFlash('error', 'Failed to cancel appointment.');
            }
        } else {
            Yii::$app->session->setFlash('error', 'Appointment not found.');
        }

        return $this->redirect(['index']);
    }

    public function actionConfirm($id)
    {
        // SIGNED APPOINTMENT ID: do not expose the database key in browser actions.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid appointment link.');
        $appointment = Appointment::findOne($id);

        if ($appointment) {
            // Set status to confirmed.
            $appointment->status = 'confirmed';

            if ($appointment->save()) {
                $mro = MroProfile::findOne($appointment->mro_id);

                if ($mro) {
                    $notificationPreferences = MroNotificationsPreferences::findOne(['mro_id' => $mro->mro_id]);

                    if (
                        $notificationPreferences &&
                        $notificationPreferences->notify_by_email &&
                        $notificationPreferences->notify_for_appointment_acceptance
                    ) {
                        Yii::$app->mailer->compose('appointment_confirmed', ['appointment' => $appointment])
                            ->setTo($mro->email)
                            ->setSubject('appointment confirmed')
                            ->send();
                    }

                    // Platform notification.
                    if (
                        $notificationPreferences &&
                        $notificationPreferences->notify_by_platform &&
                        $notificationPreferences->notify_for_appointment_acceptance
                    ) {
                        Yii::$app->runAction('notification/save-notification', [
                            'recipientType' => 'mro',
                            'recipientId' => $mro->mro_id,
                            'message' => 'Your appointment for Request #' . $appointment->request_id .
                                ' has been confirmed for ' .
                                Yii::$app->formatter->asDatetime($appointment->appointment_date),
                            'actions' => Yii::$app->request->baseUrl . '/mro-appointments'
                        ]);
                    }
                }

                Yii::$app->session->setFlash('success', 'Appointment confirmed successfully.');
            } else {
                Yii::$app->session->setFlash('error', 'Failed to confirm appointment.');
            }
        } else {
            Yii::$app->session->setFlash('error', 'Appointment not found.');
        }

        return $this->redirect(['index']);
    }

    public function actionReschedule($id)
    {
        // SIGNED APPOINTMENT ID: do not expose the database key in browser actions.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid appointment link.');
        $appointment = Appointment::findOne($id);

        if (!$appointment) {
            throw new NotFoundHttpException("Appointment not found with ID: $id");
        }

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post('Appointment');

            if (!empty($post['reschedule_appointment_date'])) {
                $appointment->reschedule_appointment_date = date('Y-m-d H:i:s', strtotime($post['reschedule_appointment_date']));
            }

            $appointment->status = 'reschedule';

            if ($appointment->save()) {
                $mro = MroProfile::findOne($appointment->mro_id);

                if ($mro) {
                    $notificationPreferences = MroNotificationsPreferences::findOne(['mro_id' => $mro->mro_id]);

                    if (
                        $notificationPreferences &&
                        $notificationPreferences->notify_by_email &&
                        $notificationPreferences->notify_for_appointment_acceptance
                    ) {
                        Yii::$app->mailer->compose('appointment_reschedule', ['appointment' => $appointment])
                            ->setTo($mro->email)
                            ->setSubject('appointment reschedule demand')
                            ->send();
                    }

                    // Platform notification.
                    if (
                        $notificationPreferences &&
                        $notificationPreferences->notify_by_platform &&
                        $notificationPreferences->notify_for_appointment_acceptance
                    ) {
                        Yii::$app->runAction('notification/save-notification', [
                            'recipientType' => 'mro',
                            'recipientId' => $mro->mro_id,
                            'message' => 'Appointment rescheduled for Request #' . $appointment->request_id .
                                ' to ' . Yii::$app->formatter->asDatetime($appointment->reschedule_appointment_date),
                            'actions' => Yii::$app->request->baseUrl . '/mro-appointments'
                        ]);
                    }
                }

                Yii::$app->session->setFlash('success', 'Appointment reschedule successfully.');

                // Redirect to appointments list page.
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('error', 'Failed to reschedule appointment.');
            }
        }

        return $this->render('reschedule', [
            'appointment' => $appointment,
        ]);
    }
}
