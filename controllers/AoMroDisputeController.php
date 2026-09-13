<?php

namespace app\controllers;

use app\models\AoRequestsApplications;
use Yii;
use app\components\UrlIdHelper;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\data\Pagination;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use app\models\Dispute;
use app\models\AoProfile;
use app\models\MroProfile;
use app\models\MroRequestApply;
use app\models\Requests;

class AoMroDisputeController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Only authenticated users
                        'matchCallback' => function ($rule, $action) {
                            // Allow access only to users with specific user types
                            return in_array(Yii::$app->session->get('user_type'), ['ao', 'mro']);
                        }
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'close' => ['POST'],
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    // Index action to view all disputes
    public function actionIndex()
    {
        // Keep the same GET parameter used by the view filter form.
        $search = trim((string) Yii::$app->request->get('search', ''));
        [$userType, $userId] = $this->currentActor();

        // Use aliases to avoid ambiguous column names when filtering joined tables.
        $query = Dispute::find()->alias('d')
            ->leftJoin(['ao' => 'ao_profiles'], 'd.ao_id = ao.ao_id')
            ->leftJoin(['mro' => 'mro_profiles'], 'd.mro_id = mro.mro_id')
            ->leftJoin(['r' => 'requests'], 'd.request_id = r.request_id')
            ->where($userType === 'ao'
                ? ['d.ao_id' => $userId]
                : ['d.mro_id' => $userId]);

        // Important: keep all search conditions inside one AND group.
        // Using orFilterWhere() directly after where() breaks the user restriction
        // and can also return wrong results.
        if ($search !== '') {
            $query->andWhere([
                'or',
                ['like', 'd.dispute_id', $search],
                ['like', 'd.request_id', $search],
                ['like', 'd.description', $search],
                ['like', 'd.status', $search],
                ['like', 'd.created_by', $search],
                ['like', 'd.admin_response', $search],
                ['like', 'd.po', $search],
                ['like', 'ao.username', $search],
                ['like', 'ao.email', $search],
                ['like', 'mro.username', $search],
                ['like', 'mro.email', $search],
            ]);
        }

        $countQuery = clone $query;
        $pagination = new Pagination([
            'totalCount' => $countQuery->count(),
            'pageSize' => 20,
        ]);

        $disputes = $query
            ->orderBy(['d.timestamp' => SORT_DESC])
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        return $this->render('index', [
            'disputes' => $disputes,
            'pagination' => $pagination,
            'search' => $search,
        ]);
    }


    // View a single dispute
    public function actionView($id)
    {
        /*
         * DISPUTE ENCODED ID 2026:
         * old numeric bookmarks are redirected once; all current links use a signed ID.
         */
        if (ctype_digit((string) $id) && (int) $id > 0) {
            return $this->redirect(['view', 'id' => UrlIdHelper::encode((int) $id)]);
        }

        $disputeId = UrlIdHelper::decode((string) $id);

        if ($disputeId === null) {
            throw new NotFoundHttpException('Invalid dispute link.');
        }

        $dispute = $this->findModel($disputeId);

        [$userType, $userId] = $this->currentActor();
        $ownerId = $userType === 'ao' ? (int) $dispute->ao_id : (int) $dispute->mro_id;
        if ($ownerId !== $userId) {
            throw new ForbiddenHttpException('You are not allowed to view this dispute.');
        }

        return $this->render('view', ['dispute' => $dispute]);
    }

    // Create a new dispute
    public function actionCreate()
    {
        $dispute = new Dispute();
        
        // Load the request data based on user type (AO or MRO)
        if (Yii::$app->session->get('user_type') == 'ao') {
            [, $aoId] = $this->currentActor();
            $requests = AoRequestsApplications::find()
                ->select('ao_requests_applications.request_id, MAX(ao_requests_applications.id) AS max_id')
                ->leftJoin('requests', 'ao_requests_applications.request_id = requests.request_id')
                ->where(['requests.ao_id' => $aoId])
                ->groupBy('ao_requests_applications.request_id')
                ->orderBy(['max_id' => SORT_DESC])
                ->all();
            $requests = ArrayHelper::map($requests, 'request_id', 'request_id');
            $aoProfiles = ArrayHelper::map(AoProfile::find()->all(), 'ao_id', 'username');
            $mroProfiles = [];
        } elseif (Yii::$app->session->get('user_type') == 'mro') {
            [, $mroId] = $this->currentActor();
            $requests = MroRequestApply::find()
                ->select('request_id, MAX(id) AS max_id')
                ->where(['mro_id' => $mroId])
                ->groupBy('request_id')
                ->orderBy(['max_id' => SORT_DESC])
                ->all();
            $requests = ArrayHelper::map($requests, 'request_id', 'request_id');
            $mroProfiles = ArrayHelper::map(MroProfile::find()->all(), 'mro_id', 'username');
            $aoProfiles = [];
        } else {
            $requests = Requests::find()->all();
            $requests = ArrayHelper::map($requests, 'request_id', 'request_id');
            $aoProfiles = ArrayHelper::map(AoProfile::find()->all(), 'ao_id', 'username');
            $mroProfiles = ArrayHelper::map(MroProfile::find()->all(), 'mro_id', 'username');
        }
    
        // Handle form submission
        if ($dispute->load(Yii::$app->request->post()) && $dispute->save()) {
            if (Yii::$app->session->get('user_type') == 'ao') {

            Yii::$app->mailer->compose('dispute_created', ['dispute' => $dispute])
            ->setTo($dispute->getMro()->one()->email)
            
            ->setSubject('dispute created')
            ->send();
    
              //platform notification
                // Call the action to save the notification
                Yii::$app->runAction('notification/save-notification', [
                    'recipientType' => 'mro',
                    'recipientId' => $dispute->mro_id,
'message' => 'Dispute created for request #' . $dispute->request_id . '.',                    'actions' => Yii::$app->request->baseUrl . '/disputes', // Or any route you want

                ]);
            }else if (Yii::$app->session->get('user_type') == 'mro') {

                Yii::$app->mailer->compose('dispute_created', ['dispute' => $dispute])
                ->setTo($dispute->getAo()->one()->email)
                
                ->setSubject('admin dispute respose')
                ->send();
        
                  //platform notification
                    // Call the action to save the notification
                    Yii::$app->runAction('notification/save-notification', [
                        'recipientType' => 'ao',
                        'recipientId' => $dispute->ao_id,
'message' => 'Admin dispute response received for request #' . $dispute->request_id . '.',                        'actions' => Yii::$app->request->baseUrl . '/disputes', // Or any route you want

                    ]);
                }

            Yii::$app->session->setFlash('success', 'Dispute created successfully.');
            return $this->redirect(['index']);
        }
    
        return $this->render('create', [
            'dispute' => $dispute,
            'requests' => $requests,
            'aoProfiles' => $aoProfiles,
            'mroProfiles' => $mroProfiles,
        ]);
    }
    
    

    // Update an existing dispute
    public function actionUpdate($id)
    {
        // SIGNED DISPUTE ID: all browser actions use the same protected identifier.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid dispute link.');
        $dispute = $this->findModel($id);

        if ($dispute->load(Yii::$app->request->post()) && $dispute->save()) {
            Yii::$app->session->setFlash('success', 'Dispute updated successfully.');
            return $this->redirect(['index']);
        }

        $aoProfiles = AoProfile::find()->all();
        $mroProfiles = MroProfile::find()->all();
        $requests = Requests::find()->all();

        return $this->render('update', [
            'dispute' => $dispute,
            'aoProfiles' => $aoProfiles,
            'mroProfiles' => $mroProfiles,
            'requests' => $requests,
        ]);
    }

    // Close a dispute
    public function actionClose($id)
    {
        // SIGNED DISPUTE ID: all browser actions use the same protected identifier.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid dispute link.');
        $dispute = $this->findModel($id);
        $dispute->status = 'resolved';

        if ($dispute->save()) {
            Yii::$app->session->setFlash('success', 'Dispute closed successfully.');
        } else {
            Yii::$app->session->setFlash('error', 'Failed to close the dispute.');
        }

        return $this->redirect(['index']);
    }

    // Remove a dispute
    public function actionDelete($id)
    {
        // SIGNED DISPUTE ID: all browser actions use the same protected identifier.
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid dispute link.');
        $dispute = $this->findModel($id);

        if ($dispute->delete()) {
            Yii::$app->session->setFlash('success', 'Dispute removed successfully.');
        } else {
            Yii::$app->session->setFlash('error', 'Failed to remove the dispute.');
        }

        return $this->redirect(['index']);
    }

    // Find a dispute model by ID
    protected function findModel($id)
    {
        if (($model = Dispute::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested dispute does not exist.');
    }


    /**
     * Return selected request details for the create dispute form.
     * This action is used by AJAX when the user selects a request.
     */
    public function actionFetchDetails($requestId)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $details = $this->fetchDetails($requestId);

        if ($details === false) {
            return [
                'success' => false,
                'message' => 'Details not found.',
            ];
        }

        return array_merge([
            'success' => true,
        ], $details);
    }

    /**
     * Fetch AO, MRO, PO and request aircraft details.
     *
     * The method is intentionally defensive because some project tables may
     * use different column names depending on the module/version.
     *
     * @param int|string $requestId
     * @return array|false
     */
    protected function fetchDetails($requestId)
    {
        $request = Requests::findOne($requestId);

        if ($request === null) {
            return false;
        }

        // Latest AO application linked to the selected request.
        $aoRequest = AoRequestsApplications::find()
            ->where(['request_id' => $requestId])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        // Latest MRO application linked to the selected request.
        $mroRequest = MroRequestApply::find()
            ->where(['request_id' => $requestId])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        // Basic dispute hidden values.
        $aoId = $this->getModelValue($request, ['ao_id']);
        $mroId = $this->getModelValue($aoRequest, ['mro_id']);

        if (empty($mroId)) {
            $mroId = $this->getModelValue($mroRequest, ['mro_id']);
        }

        $po = $this->getModelValue($aoRequest, ['po']);

        if (empty($po)) {
            $po = $this->getModelValue($mroRequest, ['po']);
        }

        // Usernames.
        $aoProfile = !empty($aoId) ? AoProfile::findOne($aoId) : null;
        $mroProfile = !empty($mroId) ? MroProfile::findOne($mroId) : null;

        $aoUsername = $aoProfile ? $aoProfile->username : null;
        $mroUsername = $mroProfile ? $mroProfile->username : null;

        // Registration / serial can be stored directly in request.
        $registrationNumber = $this->getModelValue($request, [
            'registration_number',
            'aircraft_registration',
            'aircraft_registration_number',
            'registration',
            'tail_number',
        ]);

        $serialNumber = $this->getModelValue($request, [
            'serial_number',
            'aircraft_serial_number',
            'msn',
            'serial',
        ]);

        // Manufacturer and aircraft type can be stored directly in request or inside an aircraft/airplane relation.
        $manufacturer = $this->getModelValue($request, [
            'manufacturer',
            'aircraft_manufacturer',
            'aircraft_manufacturer_name',
            'aircraft_make',
            'make',
        ]);

        $aircraftType = $this->getModelValue($request, [
            'aircraft_type',
            'aircraft_model',
            'aircraft_model_name',
            'model_name',
            'model',
            'type',
        ]);

        // Load the real aircraft/airplane record. This is the important part for your case.
        // In your request details page the information exists, so it is probably stored in an aircraft/airplane model.
        $aircraft = $this->findAircraftRecord($request, $registrationNumber);

        if ($aircraft !== null) {
            if (empty($manufacturer)) {
                $manufacturer = $this->getModelValue($aircraft, [
                    'manufacturer',
                    'aircraft_manufacturer',
                    'aircraft_manufacturer_name',
                    'aircraft_make',
                    'make',
                    'brand',
                    'name',
                ]);
            }

            if (empty($aircraftType)) {
                $aircraftType = $this->getModelValue($aircraft, [
                    'model',
                    'aircraft_model',
                    'aircraft_model_name',
                    'model_name',
                    'aircraft_type',
                    'type',
                    'type_name',
                ]);
            }

            if (empty($registrationNumber)) {
                $registrationNumber = $this->getModelValue($aircraft, [
                    'registration_number',
                    'aircraft_registration',
                    'aircraft_registration_number',
                    'registration',
                    'tail_number',
                ]);
            }

            if (empty($serialNumber)) {
                $serialNumber = $this->getModelValue($aircraft, [
                    'serial_number',
                    'aircraft_serial_number',
                    'msn',
                    'serial',
                ]);
            }
        }

        // Load aircraft model master table if the aircraft stores only aircraft_model_id.
        $aircraftModel = $this->findAircraftModelRecord($request, $aircraft, $aircraftType);

        if ($aircraftModel !== null) {
            if (empty($manufacturer)) {
                $manufacturer = $this->getModelValue($aircraftModel, [
                    'manufacturer',
                    'aircraft_manufacturer',
                    'brand',
                ]);
            }

            if (empty($aircraftType)) {
                $aircraftType = $this->getModelValue($aircraftModel, [
                    'model',
                    'aircraft_model',
                    'model_name',
                    'name',
                    'type',
                ]);
            }
        }


        // Final fallback: read the aircraft and aircraft model directly from database tables.
        // This fixes cases where the request stores only registration/serial number and the
        // Yii relation/model name is different from the expected one.
        if (empty($manufacturer) || empty($aircraftType)) {
            $aircraftDbDetails = $this->findAircraftDetailsFromDatabase($registrationNumber, $serialNumber, $aoId);

            if (!empty($aircraftDbDetails['manufacturer']) && empty($manufacturer)) {
                $manufacturer = $aircraftDbDetails['manufacturer'];
            }

            if (!empty($aircraftDbDetails['aircraft_type']) && empty($aircraftType)) {
                $aircraftType = $aircraftDbDetails['aircraft_type'];
            }

            if (!empty($aircraftDbDetails['registration_number']) && empty($registrationNumber)) {
                $registrationNumber = $aircraftDbDetails['registration_number'];
            }

            if (!empty($aircraftDbDetails['serial_number']) && empty($serialNumber)) {
                $serialNumber = $aircraftDbDetails['serial_number'];
            }
        }

        // Airport / maintenance location details.
        $airportName = $this->getModelValue($request, [
            'airport',
            'airport_name',
            'maintenance_location',
            'location',
        ]);

        $airportId = $this->getModelValue($request, [
            'airport_id',
            'maintenance_location_id',
            'maintenance_airport_id',
        ]);

        $airportClass = 'app\\models\\Airport';

        if (empty($airportName) && !empty($airportId) && class_exists($airportClass)) {
            $airport = $airportClass::findOne($airportId);

            if ($airport !== null) {
                $airportName = $this->getModelValue($airport, [
                    'airport_name',
                    'name',
                    'icao_code',
                    'iata_code',
                ]);
            }
        }

        // Dates and status.
        $requestStatus = $this->getModelValue($request, ['status', 'request_status']);
        $createdAt = $this->getModelValue($request, ['created_at', 'timestamp', 'created_date']);
        $etd = $this->getModelValue($request, ['etd', 'estimated_time_departure']);
        $eta = $this->getModelValue($request, ['eta', 'estimated_time_arrival']);
        $displayDate = $createdAt ?: ($etd ?: $eta);

        return [
            // Values saved in dispute hidden fields.
            'ao_id' => $aoId,
            'mro_id' => $mroId,
            'po' => $po,
            'ao_username' => $aoUsername,
            'mro_username' => $mroUsername,

            // Request details displayed in the detail panel.
            'manufacturer' => $manufacturer,
            'aircraft_manufacturer' => $manufacturer,
            'aircraft_type' => $aircraftType,
            'aircraft_model' => $aircraftType,
            'model' => $aircraftType,
            'registration_number' => $registrationNumber,
            'aircraft_registration' => $registrationNumber,
            'serial_number' => $serialNumber,
            'aircraft_serial_number' => $serialNumber,
            'airport' => $airportName,
            'airport_name' => $airportName,
            'maintenance_location' => $airportName,
            'request_status' => $requestStatus,
            'status' => $requestStatus,
            'created_at' => $createdAt,
            'date' => $displayDate,
            'etd' => $etd,
            'eta' => $eta,
        ];
    }

    /**
     * Finds the aircraft / airplane record linked to the request.
     * Supports different model names used in the project.
     *
     * @param Requests $request
     * @param string|null $registrationNumber
     * @return mixed|null
     */
    protected function findAircraftRecord($request, $registrationNumber = null)
    {
        // First try common relations used in Yii ActiveRecord.
        foreach (['aircraft', 'airplane', 'aoAircraft', 'aoAirplane', 'plane'] as $relationName) {
            $relation = $this->getRelatedModel($request, $relationName);

            if ($relation !== null) {
                return $relation;
            }
        }

        $possibleIds = [
            $this->getModelValue($request, ['aircraft_id']),
            $this->getModelValue($request, ['airplane_id']),
            $this->getModelValue($request, ['ao_aircraft_id']),
            $this->getModelValue($request, ['ao_airplane_id']),
            $this->getModelValue($request, ['plane_id']),
        ];

        $possibleClasses = [
            'app\\models\\Airplane',
            'app\\models\\Airplanes',
            'app\\models\\Aircraft',
            'app\\models\\Aircrafts',
            'app\\models\\AoAircraft',
            'app\\models\\AoAirplane',
            'app\\models\\AoAirplanes',
        ];

        foreach ($possibleClasses as $className) {
            if (!class_exists($className)) {
                continue;
            }

            foreach ($possibleIds as $id) {
                if (!empty($id)) {
                    $record = $className::findOne($id);

                    if ($record !== null) {
                        return $record;
                    }
                }
            }
        }

        // Fallback: find aircraft by registration number.
        if (!empty($registrationNumber)) {
            foreach ($possibleClasses as $className) {
                if (!class_exists($className)) {
                    continue;
                }

                $model = new $className();

                foreach (['registration_number', 'aircraft_registration', 'aircraft_registration_number', 'registration', 'tail_number'] as $attribute) {
                    if (method_exists($model, 'hasAttribute') && $model->hasAttribute($attribute)) {
                        $record = $className::find()
                            ->where([$attribute => $registrationNumber])
                            ->one();

                        if ($record !== null) {
                            return $record;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Finds the aircraft model master record using the request/aircraft model id or model name.
     *
     * @param Requests $request
     * @param mixed|null $aircraft
     * @param string|null $aircraftType
     * @return mixed|null
     */
    protected function findAircraftModelRecord($request, $aircraft = null, $aircraftType = null)
    {
        foreach (['aircraftModel', 'aircraftType', 'modelInfo', 'modelData'] as $relationName) {
            $relation = $this->getRelatedModel($request, $relationName);

            if ($relation !== null) {
                return $relation;
            }

            if ($aircraft !== null) {
                $relation = $this->getRelatedModel($aircraft, $relationName);

                if ($relation !== null) {
                    return $relation;
                }
            }
        }

        $aircraftModelId = $this->getModelValue($request, ['aircraft_model_id', 'model_id', 'aircraft_type_id']);

        if (empty($aircraftModelId) && $aircraft !== null) {
            $aircraftModelId = $this->getModelValue($aircraft, ['aircraft_model_id', 'model_id', 'aircraft_type_id']);
        }

        $possibleClasses = [
            'app\\models\\AircraftModel',
            'app\\models\\AircraftModels',
            'app\\models\\AircraftType',
            'app\\models\\AircraftTypes',
        ];

        foreach ($possibleClasses as $className) {
            if (!class_exists($className)) {
                continue;
            }

            if (!empty($aircraftModelId)) {
                $record = $className::findOne($aircraftModelId);

                if ($record !== null) {
                    return $record;
                }
            }

            if (!empty($aircraftType)) {
                $model = new $className();

                foreach (['model', 'aircraft_model', 'model_name', 'name', 'type'] as $attribute) {
                    if (method_exists($model, 'hasAttribute') && $model->hasAttribute($attribute)) {
                        $record = $className::find()
                            ->where([$attribute => $aircraftType])
                            ->one();

                        if ($record !== null) {
                            return $record;
                        }
                    }
                }
            }
        }

        return null;
    }


    /**
     * Finds aircraft manufacturer/type directly from DB tables using registration number or serial number.
     * This is used as a fallback when the Requests model does not expose a direct relation.
     *
     * @param string|null $registrationNumber
     * @param string|null $serialNumber
     * @param int|string|null $aoId
     * @return array
     */
    protected function findAircraftDetailsFromDatabase($registrationNumber = null, $serialNumber = null, $aoId = null)
    {
        $aircraftRow = $this->findAircraftRowFromDatabase($registrationNumber, $serialNumber, $aoId);

        if (empty($aircraftRow)) {
            return [];
        }

        $manufacturer = $this->getArrayValue($aircraftRow, [
            'manufacturer',
            'aircraft_manufacturer',
            'aircraft_manufacturer_name',
            'aircraft_make',
            'make',
            'brand',
        ]);

        $aircraftType = $this->getArrayValue($aircraftRow, [
            'model',
            'aircraft_model',
            'aircraft_model_name',
            'model_name',
            'aircraft_type',
            'type',
            'type_name',
            'name',
        ]);

        $aircraftModelId = $this->getArrayValue($aircraftRow, [
            'aircraft_model_id',
            'model_id',
            'aircraft_type_id',
            'type_id',
        ]);

        $registrationFromAircraft = $this->getArrayValue($aircraftRow, [
            'registration_number',
            'aircraft_registration',
            'aircraft_registration_number',
            'registration',
            'tail_number',
        ]);

        $serialFromAircraft = $this->getArrayValue($aircraftRow, [
            'serial_number',
            'aircraft_serial_number',
            'msn',
            'serial',
        ]);

        $modelRow = $this->findAircraftModelRowFromDatabase($aircraftModelId, $aircraftType);

        if (!empty($modelRow)) {
            if (empty($manufacturer)) {
                $manufacturer = $this->getArrayValue($modelRow, [
                    'manufacturer',
                    'aircraft_manufacturer',
                    'aircraft_manufacturer_name',
                    'make',
                    'brand',
                ]);
            }

            if (empty($aircraftType)) {
                $aircraftType = $this->getArrayValue($modelRow, [
                    'model',
                    'aircraft_model',
                    'aircraft_model_name',
                    'model_name',
                    'name',
                    'type',
                ]);
            }
        }

        return [
            'manufacturer' => $manufacturer,
            'aircraft_type' => $aircraftType,
            'registration_number' => $registrationFromAircraft,
            'serial_number' => $serialFromAircraft,
        ];
    }

    /**
     * Finds an aircraft row from common aircraft tables.
     *
     * @param string|null $registrationNumber
     * @param string|null $serialNumber
     * @param int|string|null $aoId
     * @return array|null
     */
    protected function findAircraftRowFromDatabase($registrationNumber = null, $serialNumber = null, $aoId = null)
    {
        $possibleTables = [
            'airplanes',
            'airplane',
            'ao_airplanes',
            'ao_aircraft',
            'ao_aircrafts',
            'aircraft',
            'aircrafts',
            'aircraft_profiles',
            'aircraft_profile',
        ];

        $registrationColumns = [
            'registration_number',
            'aircraft_registration',
            'aircraft_registration_number',
            'registration',
            'tail_number',
        ];

        $serialColumns = [
            'serial_number',
            'aircraft_serial_number',
            'msn',
            'serial',
        ];

        $aoColumns = ['ao_id', 'aircraft_operator_id', 'operator_id'];

        foreach ($possibleTables as $tableName) {
            $schema = Yii::$app->db->schema->getTableSchema($tableName, true);

            if ($schema === null) {
                continue;
            }

            // First search by registration number because it is unique and shown in your request details page.
            if (!empty($registrationNumber)) {
                foreach ($registrationColumns as $column) {
                    if (!isset($schema->columns[$column])) {
                        continue;
                    }

                    $query = (new \yii\db\Query())->from($tableName)->where([$column => $registrationNumber]);

                    $this->addOptionalAoCondition($query, $schema, $aoColumns, $aoId);

                    $row = $query->one();

                    if (!empty($row)) {
                        return $row;
                    }
                }
            }

            // Then search by serial number.
            if (!empty($serialNumber)) {
                foreach ($serialColumns as $column) {
                    if (!isset($schema->columns[$column])) {
                        continue;
                    }

                    $query = (new \yii\db\Query())->from($tableName)->where([$column => $serialNumber]);

                    $this->addOptionalAoCondition($query, $schema, $aoColumns, $aoId);

                    $row = $query->one();

                    if (!empty($row)) {
                        return $row;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Adds AO condition only when the table contains an AO column.
     *
     * @param \yii\db\Query $query
     * @param \yii\db\TableSchema $schema
     * @param array $aoColumns
     * @param int|string|null $aoId
     * @return void
     */
    protected function addOptionalAoCondition($query, $schema, array $aoColumns, $aoId = null)
    {
        if (empty($aoId)) {
            return;
        }

        foreach ($aoColumns as $column) {
            if (isset($schema->columns[$column])) {
                $query->andWhere([$column => $aoId]);
                return;
            }
        }
    }

    /**
     * Finds an aircraft model row from common aircraft model tables.
     *
     * @param int|string|null $aircraftModelId
     * @param string|null $aircraftType
     * @return array|null
     */
    protected function findAircraftModelRowFromDatabase($aircraftModelId = null, $aircraftType = null)
    {
        $possibleTables = [
            'aircraft_models',
            'aircraft_model',
            'aircraft_types',
            'aircraft_type',
        ];

        $idColumns = ['aircraft_model_id', 'id', 'model_id', 'aircraft_type_id', 'type_id'];
        $modelColumns = ['model', 'aircraft_model', 'aircraft_model_name', 'model_name', 'name', 'type'];

        foreach ($possibleTables as $tableName) {
            $schema = Yii::$app->db->schema->getTableSchema($tableName, true);

            if ($schema === null) {
                continue;
            }

            if (!empty($aircraftModelId)) {
                foreach ($idColumns as $column) {
                    if (!isset($schema->columns[$column])) {
                        continue;
                    }

                    $row = (new \yii\db\Query())
                        ->from($tableName)
                        ->where([$column => $aircraftModelId])
                        ->one();

                    if (!empty($row)) {
                        return $row;
                    }
                }
            }

            if (!empty($aircraftType)) {
                foreach ($modelColumns as $column) {
                    if (!isset($schema->columns[$column])) {
                        continue;
                    }

                    $row = (new \yii\db\Query())
                        ->from($tableName)
                        ->where([$column => $aircraftType])
                        ->one();

                    if (!empty($row)) {
                        return $row;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Safely reads the first available value from an array using possible keys.
     *
     * @param array|null $row
     * @param array $keys
     * @param mixed $default
     * @return mixed
     */
    protected function getArrayValue($row, array $keys, $default = null)
    {
        if (empty($row) || !is_array($row)) {
            return $default;
        }

        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return $default;
    }

    /**
     * Safely gets a related ActiveRecord model by relation/property name.
     *
     * @param mixed $model
     * @param string $relationName
     * @return mixed|null
     */
    protected function getRelatedModel($model, $relationName)
    {
        if ($model === null) {
            return null;
        }

        try {
            if (method_exists($model, 'canGetProperty') && $model->canGetProperty($relationName)) {
                $value = $model->{$relationName};

                if (is_array($value)) {
                    return !empty($value) ? reset($value) : null;
                }

                if (is_object($value)) {
                    return $value;
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * Safely reads a value from an ActiveRecord using a list of possible attributes.
     * This prevents errors when the same business information has different column names.
     *
     * @param mixed $model
     * @param array $attributes
     * @param mixed $default
     * @return mixed
     */
    protected function getModelValue($model, array $attributes, $default = null)
    {
        if ($model === null) {
            return $default;
        }

        foreach ($attributes as $attribute) {
            // ActiveRecord database attribute.
            if (method_exists($model, 'hasAttribute') && $model->hasAttribute($attribute)) {
                $value = $model->getAttribute($attribute);

                if ($value !== null && $value !== '') {
                    return $value;
                }
            }

            // Yii getter / virtual property. Ignore objects because they are relations.
            try {
                if (method_exists($model, 'canGetProperty') && $model->canGetProperty($attribute)) {
                    $value = $model->{$attribute};

                    if (!is_object($value) && !is_array($value) && $value !== null && $value !== '') {
                        return $value;
                    }
                }
            } catch (\Throwable $e) {
                // Ignore unsafe virtual properties.
            }

            // Plain PHP public property fallback.
            if (is_object($model) && property_exists($model, $attribute)) {
                $value = $model->{$attribute};

                if (!is_object($value) && !is_array($value) && $value !== null && $value !== '') {
                    return $value;
                }
            }
        }

        return $default;
    }
    /**
     * Retourne le rôle et l'identifiant numérique du participant connecté.
     */
    private function currentActor()
    {
        $userType = Yii::$app->session->get('user_type');
        $identity = Yii::$app->user->identity;
        $attribute = $userType === 'ao' ? 'ao_id' : ($userType === 'mro' ? 'mro_id' : null);
        $userId = $attribute !== null ? (int) ($identity->{$attribute} ?? 0) : 0;

        if ($userId <= 0) {
            throw new ForbiddenHttpException('Invalid user identity.');
        }

        return [$userType, $userId];
    }
}
