<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\base\InvalidConfigException;

use app\models\Appointment;
use app\models\AoProfile;
use app\models\Requests;
use app\models\RequestChangeEvent;
use app\models\Aircrafts;
use app\components\UrlIdHelper;

class MroAppointmentsController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return in_array(Yii::$app->session->get('user_type'), ['mro'], true);
                        },
                    ],
                ],
            ],

            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'remove-appointment' => ['POST'],
                    'accept-reschedule'  => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Rend la page complete pour une navigation MRO et seulement la carte de liste
     * lorsque le gestionnaire de synchronisation la redemande. L'en-tete explicite
     * complete isAjax pour isoler ce protocole des autres appels AJAX du controleur.
     *
     * @param array $params Donnees deja filtrees selon le MRO connecte.
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
         * CURSEUR DE SYNCHRONISATION : lu avant les données du rendez-vous, ce
         * repère garantit la détection des changements survenus pendant le rendu.
         */
        $syncCursor = (int) (RequestChangeEvent::find()->max('id') ?: 0);

        $mroId = (int) Yii::$app->session->get('mro_id');

        if ($mroId <= 0) {
            Yii::$app->session->setFlash('error', 'You are not logged in as an MRO.');
            return $this->redirect(['site/login']);
        }

        $search = trim((string) Yii::$app->request->get('search', ''));

        /*
         * Résolution dynamique des colonnes.
         * Cela évite les erreurs si tes colonnes sont nommées ao_id, AO_ID, AoId, etc.
         */
        $aoPk      = $this->getPrimaryKeyName(AoProfile::class);
        $aoUserCol = $this->resolveColumn(AoProfile::class, ['username', 'Username', 'USER_NAME']);
        $aoEmail   = $this->resolveColumn(AoProfile::class, ['email', 'Email', 'EMAIL']);

        $apptPk    = $this->getPrimaryKeyName(Appointment::class);
        $apptAoFk  = $this->resolveColumn(Appointment::class, ['ao_id', 'AoId', 'AO_ID']);
        $apptMroFk = $this->resolveColumn(Appointment::class, ['mro_id', 'MroId', 'MRO_ID']);
        $apptReq   = $this->resolveColumn(Appointment::class, ['request_id', 'RequestId', 'REQUEST_ID']);
        $apptStat  = $this->resolveColumn(Appointment::class, ['status', 'Status', 'STATUS']);
        $apptDate  = $this->resolveColumn(Appointment::class, ['appointment_date', 'AppointmentDate', 'APPOINTMENT_DATE']);
        $apptReDt  = $this->resolveColumn(Appointment::class, ['reschedule_appointment_date', 'RescheduleAppointmentDate', 'RESCHEDULE_APPOINTMENT_DATE']);

        if ($apptMroFk === null || $apptAoFk === null || $apptPk === null) {
            throw new InvalidConfigException('Appointment schema columns could not be resolved.');
        }

        /*
         * Query principale : seulement les appointments du MRO connecté.
         */
        $query = Appointment::find()
            ->where([$apptMroFk => $mroId]);

        /*
         * Recherche par :
         * - Request ID
         * - Status
         * - Date appointment
         * - Reschedule date
         * - AO username
         */
        if ($search !== '') {
            $aoIds = [];
            $matchingRequestIds = [];

            if ($aoUserCol !== null && $aoPk !== null) {
                $aoIds = AoProfile::find()
                    ->select($aoPk)
                    ->where(['like', $aoUserCol, $search])
                    ->column();
            }

            /*
             * REQUEST DETAILS SEARCH: include every operational value displayed in the row.
             * Aircraft matching is resolved separately to keep the Appointment query unchanged.
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

            $conditions = ['or'];

            if ($apptReq !== null) {
                $conditions[] = ['like', $apptReq, $search];
            }

            if ($apptStat !== null) {
                $conditions[] = ['like', $apptStat, $search];
            }

            if ($apptDate !== null) {
                $conditions[] = ['like', $apptDate, $search];
            }

            if ($apptReDt !== null) {
                $conditions[] = ['like', $apptReDt, $search];
            }

            if (!empty($aoIds)) {
                $conditions[] = ['in', $apptAoFk, $aoIds];
            }

            if (!empty($matchingRequestIds) && $apptReq !== null) {
                $conditions[] = ['in', $apptReq, $matchingRequestIds];
            }

            if (count($conditions) > 1) {
                $query->andWhere($conditions);
            }
        }

        /*
         * Pagination.
         * Mets defaultPageSize à 2 pour tester rapidement si tu as peu de lignes.
         */
        $countQuery = clone $query;

        $pagination = new Pagination([
            'defaultPageSize' => 5,
            'totalCount' => $countQuery->count(),
            'pageSizeParam' => false,
        ]);

        $appointments = $query
            ->orderBy([$apptPk => SORT_DESC])
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        /*
         * REQUEST DETAILS: preload all requests and aircraft for the current page.
         * This keeps the appointment business query unchanged and avoids one SQL query per row.
         */
        $requestMap = [];

        if (!empty($appointments) && $apptReq !== null) {
            $requestIds = [];

            foreach ($appointments as $appointment) {
                if (!empty($appointment->{$apptReq})) {
                    $requestIds[] = (int) $appointment->{$apptReq};
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
        }

        /*
         * Map AO : évite de faire AoProfile::findOne() dans chaque ligne de la vue.
         */
        $aoMap = [];

        if (!empty($appointments) && $aoPk !== null) {
            $ids = [];

            foreach ($appointments as $appointment) {
                if (!empty($appointment->{$apptAoFk})) {
                    $ids[] = (int) $appointment->{$apptAoFk};
                }
            }

            $ids = array_unique($ids);

            if (!empty($ids)) {
                $select = [$aoPk];

                if ($aoUserCol !== null) {
                    $select[] = $aoUserCol;
                }

                if ($aoEmail !== null) {
                    $select[] = $aoEmail;
                }

                $rows = AoProfile::find()
                    ->select($select)
                    ->where(['in', $aoPk, $ids])
                    ->asArray()
                    ->all();

                foreach ($rows as $row) {
                    $key = (int) $row[$aoPk];
                    $value = $aoUserCol !== null && isset($row[$aoUserCol])
                        ? (string) $row[$aoUserCol]
                        : (string) $key;

                    $aoMap[$key] = $value;
                }
            }
        }

        /*
         * RENDU ADAPTATIF : le schema dynamique, les autorisations et les actions
         * metier ne changent pas ; la reponse devient partielle uniquement sur demande.
         */
        return $this->renderAppointmentsIndex([
            'appointments' => $appointments,
            'pagination' => $pagination,
            'search' => $search,
            'aoMap' => $aoMap,
            'syncCursor' => $syncCursor,
            'requestMap' => $requestMap,
            'cols' => [
                'pk' => $apptPk,
                'aoFk' => $apptAoFk,
                'req' => $apptReq,
                'status' => $apptStat,
                'date' => $apptDate,
                'reDate' => $apptReDt,
            ],
        ]);
    }

    public function actionRemoveAppointment($id)
    {
        // SIGNED APPOINTMENT ID: decode the public token before the existing workflow.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid appointment link.');
        $mroId = (int) Yii::$app->session->get('mro_id');

        if ($mroId <= 0) {
            Yii::$app->session->setFlash('error', 'You are not logged in as an MRO.');
            return $this->redirect(['site/login']);
        }

        $apptPk    = $this->getPrimaryKeyName(Appointment::class);
        $apptAoFk  = $this->resolveColumn(Appointment::class, ['ao_id', 'AoId', 'AO_ID']);
        $apptMroFk = $this->resolveColumn(Appointment::class, ['mro_id', 'MroId', 'MRO_ID']);
        $apptStat  = $this->resolveColumn(Appointment::class, ['status', 'Status', 'STATUS']);
        $apptReq   = $this->resolveColumn(Appointment::class, ['request_id', 'RequestId', 'REQUEST_ID']);
        $apptDate  = $this->resolveColumn(Appointment::class, ['appointment_date', 'AppointmentDate', 'APPOINTMENT_DATE']);

        if ($apptPk === null || $apptAoFk === null || $apptMroFk === null || $apptStat === null) {
            Yii::$app->session->setFlash('error', 'Appointment schema is invalid.');
            return $this->redirect(['index']);
        }

        $appointment = Appointment::findOne([$apptPk => $id]);

        if (!$appointment || (int) $appointment->{$apptMroFk} !== $mroId) {
            Yii::$app->session->setFlash('error', 'Appointment not found.');
            return $this->redirect(['index']);
        }

        if (strtolower((string) $appointment->{$apptStat}) === 'confirmed') {
            Yii::$app->session->setFlash('error', 'Confirmed appointments cannot be removed.');
            return $this->redirect(['index']);
        }

        $aoPk    = $this->getPrimaryKeyName(AoProfile::class);
        $aoEmail = $this->resolveColumn(AoProfile::class, ['email', 'Email', 'EMAIL']);

        $ao = null;

        if ($aoPk !== null && !empty($appointment->{$apptAoFk})) {
            $ao = AoProfile::findOne([$aoPk => $appointment->{$apptAoFk}]);
        }

        /*
         * Email notification.
         */
        if ($ao && $aoEmail && !empty($ao->{$aoEmail})) {
            try {
                Yii::$app->mailer
                    ->compose('appointment_deleted', [
                        'appointment' => $appointment,
                    ])
                    ->setTo($ao->{$aoEmail})
                    ->setSubject('Appointment deleted')
                    ->send();
            } catch (\Throwable $e) {
                Yii::error('Email send failed: ' . $e->getMessage(), __METHOD__);
            }
        }

        /*
         * Platform notification.
         */
        if ($ao && $aoPk !== null) {
            try {
                $requestId = $apptReq !== null ? $appointment->{$apptReq} : '';
                $dateText = '';

                if ($apptDate !== null && !empty($appointment->{$apptDate})) {
                    $dateText = Yii::$app->formatter->asDatetime($appointment->{$apptDate}, 'php:Y-m-d H:i');
                }

                Yii::$app->runAction('notification/save-notification', [
                    'recipientType' => 'ao',
                    'recipientId' => $ao->{$aoPk} ?? null,
                    'message' => 'Appointment removed for Request #' . $requestId . ($dateText ? ' scheduled on ' . $dateText : '') . '.',
                    'actions' => Yii::$app->request->baseUrl . '/ao-appointments',
                ]);
            } catch (\Throwable $e) {
                Yii::error('Notification failed: ' . $e->getMessage(), __METHOD__);
            }
        }

        if ($appointment->delete()) {
            Yii::$app->session->setFlash('success', 'Appointment removed successfully.');
        } else {
            Yii::$app->session->setFlash('error', 'Failed to remove appointment.');
        }

        return $this->redirect(['index']);
    }

    public function actionAcceptReschedule($id)
    {
        // SIGNED APPOINTMENT ID: decode the public token before the existing workflow.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid appointment link.');
        $mroId = (int) Yii::$app->session->get('mro_id');

        if ($mroId <= 0) {
            Yii::$app->session->setFlash('error', 'You are not logged in as an MRO.');
            return $this->redirect(['site/login']);
        }

        $apptPk    = $this->getPrimaryKeyName(Appointment::class);
        $apptAoFk  = $this->resolveColumn(Appointment::class, ['ao_id', 'AoId', 'AO_ID']);
        $apptMroFk = $this->resolveColumn(Appointment::class, ['mro_id', 'MroId', 'MRO_ID']);
        $apptStat  = $this->resolveColumn(Appointment::class, ['status', 'Status', 'STATUS']);
        $apptDate  = $this->resolveColumn(Appointment::class, ['appointment_date', 'AppointmentDate', 'APPOINTMENT_DATE']);
        $apptReDt  = $this->resolveColumn(Appointment::class, ['reschedule_appointment_date', 'RescheduleAppointmentDate', 'RESCHEDULE_APPOINTMENT_DATE']);
        $apptReq   = $this->resolveColumn(Appointment::class, ['request_id', 'RequestId', 'REQUEST_ID']);

        if ($apptPk === null || $apptAoFk === null || $apptMroFk === null || $apptStat === null || $apptDate === null || $apptReDt === null) {
            throw new InvalidConfigException('Appointment schema columns could not be resolved.');
        }

        $appointment = Appointment::findOne([$apptPk => $id]);

        if (!$appointment || (int) $appointment->{$apptMroFk} !== $mroId) {
            throw new NotFoundHttpException("Appointment not found with ID: $id");
        }

        if (strtolower((string) $appointment->{$apptStat}) !== 'reschedule' || empty($appointment->{$apptReDt})) {
            Yii::$app->session->setFlash('error', 'Appointment cannot be accepted. Status is not "reschedule".');
            return $this->redirect(['index']);
        }

        $appointment->{$apptDate} = $appointment->{$apptReDt};
        $appointment->{$apptStat} = 'confirmed';

        if ($appointment->save(false)) {
            $aoPk    = $this->getPrimaryKeyName(AoProfile::class);
            $aoEmail = $this->resolveColumn(AoProfile::class, ['email', 'Email', 'EMAIL']);

            $ao = null;

            if ($aoPk !== null && !empty($appointment->{$apptAoFk})) {
                $ao = AoProfile::findOne([$aoPk => $appointment->{$apptAoFk}]);
            }

            if ($ao && $aoEmail && !empty($ao->{$aoEmail})) {
                try {
                    Yii::$app->mailer
                        ->compose('appointment_confirmed', [
                            'appointment' => $appointment,
                        ])
                        ->setTo($ao->{$aoEmail})
                        ->setSubject('Appointment confirmed')
                        ->send();
                } catch (\Throwable $e) {
                    Yii::error('Email send failed: ' . $e->getMessage(), __METHOD__);
                }
            }

            if ($ao && $aoPk !== null) {
                try {
                    $requestId = $apptReq !== null ? $appointment->{$apptReq} : '';
                    $appointmentDate = Yii::$app->formatter->asDatetime($appointment->{$apptDate}, 'php:Y-m-d H:i');

                    Yii::$app->runAction('notification/save-notification', [
                        'recipientType' => 'ao',
                        'recipientId' => $ao->{$aoPk} ?? null,
                        'message' => 'Your appointment for Request #' . $requestId . ' has been confirmed for ' . $appointmentDate . '.',
                        'actions' => Yii::$app->request->baseUrl . '/ao-appointments',
                    ]);
                } catch (\Throwable $e) {
                    Yii::error('Notification failed: ' . $e->getMessage(), __METHOD__);
                }
            }

            Yii::$app->session->setFlash('success', 'Appointment reschedule date accepted.');
        } else {
            Yii::$app->session->setFlash('error', 'Failed to accept reschedule appointment date.');
        }

        return $this->redirect(['index']);
    }

    private function getPrimaryKeyName(string $arClass): ?string
    {
        $primaryKeys = $arClass::primaryKey();

        return !empty($primaryKeys) ? $primaryKeys[0] : null;
    }

    private function resolveColumn(string $arClass, array $candidates): ?string
    {
        $schema = $arClass::getTableSchema();

        if (!$schema) {
            return null;
        }

        foreach ($candidates as $candidate) {
            if (isset($schema->columns[$candidate])) {
                return $candidate;
            }
        }

        $lowerMap = [];

        foreach ($schema->columns as $name => $column) {
            $lowerMap[strtolower($name)] = $name;
        }

        foreach ($candidates as $candidate) {
            $lower = strtolower($candidate);

            if (isset($lowerMap[$lower])) {
                return $lowerMap[$lower];
            }
        }

        return null;
    }
}
