<?php

namespace app\controllers;

use Yii;
use app\models\MroProfile;
use app\models\MroAircraftCertificate;
use app\models\AircraftModel;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\data\Pagination;

class MroAircraftCertificatesController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            // Allow access only to MRO users.
                            return Yii::$app->session->get('user_type') === 'mro';
                        },
                    ],
                ],
            ],
            // Une suppression ne doit jamais être déclenchée par une URL GET.
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['delete' => ['POST']],
            ],
        ];
    }

    /**
     * Displays all aircraft certificates associated with the logged-in MRO profile.
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionIndex()
    {
        $mroId = Yii::$app->session->get('mro_id');

        $mroProfile = MroProfile::findOne($mroId);
        if (!$mroProfile) {
            throw new NotFoundHttpException('MRO profile not found.');
        }

        $search = trim((string) Yii::$app->request->get('search', ''));

        $certificateTable = MroAircraftCertificate::tableName();
        $aircraftModelTable = AircraftModel::tableName();

        $query = MroAircraftCertificate::find()
            ->where([$certificateTable . '.mro_id' => $mroId])
            ->joinWith('aircraftModel');

        // Apply search filter only when the search value is not empty.
        if ($search !== '') {
            $query->andWhere([
                'or',
                ['like', $certificateTable . '.certificate', $search],
                ['like', $aircraftModelTable . '.manufacturer', $search],
                ['like', $aircraftModelTable . '.model', $search],
            ]);
        }

        $pagination = new Pagination([
            'defaultPageSize' => 20,
            'totalCount' => $query->count(),
            'params' => Yii::$app->request->queryParams,
        ]);

        $certificates = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->orderBy([
                $aircraftModelTable . '.manufacturer' => SORT_ASC,
                $aircraftModelTable . '.model' => SORT_ASC,
            ])
            ->all();

        return $this->render('index', [
            'title' => 'MRO Aircraft Certificates',
            'mroProfile' => $mroProfile,
            'certificates' => $certificates,
            'pagination' => $pagination,
            'search' => $search,
        ]);
    }

    /**
     * Displays a specific aircraft certificate.
     *
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id = null)
    {
        $certificate = $this->findCertificateModel($id);

        return $this->render('view', [
            'certificate' => $certificate,
        ]);
    }

    /**
     * Creates a new aircraft certificate associated with an MRO profile.
     *
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionCreate()
    {
        $mroId = Yii::$app->session->get('mro_id');

        $mroProfile = MroProfile::findOne($mroId);
        if (!$mroProfile) {
            throw new NotFoundHttpException('MRO profile not found.');
        }

        $certificate = new MroAircraftCertificate();
        $certificate->mro_id = $mroId;

        if ($certificate->load(Yii::$app->request->post())) {
            // Save uploaded certificate file if provided.
            $certificateFile = UploadedFile::getInstance($certificate, 'certificate');
            if ($certificateFile) {
                $uploadPath = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . $certificateFile->extension;
                $certificateFile->saveAs($uploadPath);
                $certificate->certificate = $uploadPath;
            }

            if ($certificate->validate() && $certificate->save()) {
                Yii::$app->session->setFlash('success', 'Aircraft certificate added successfully.');
                return $this->redirect(['index']);
            }
        }

        return $this->render('create', [
            'certificate' => $certificate,
            'mroProfile' => $mroProfile,
            'aircraftModels' => AircraftModel::find()->all(),
        ]);
    }

    /**
     * Updates an existing aircraft certificate.
     *
     * @param int $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id = null)
    {
        $certificate = $this->findCertificateModel($id);

        $mroId = Yii::$app->session->get('mro_id');
        $mroProfile = MroProfile::findOne($mroId);
        if (!$mroProfile) {
            throw new NotFoundHttpException('MRO profile not found.');
        }

        // Get the selected aircraft model without relying on lazy loading in the form.
        $aircraftModel = AircraftModel::find()
            ->where(['aircraft_model_id' => $certificate->aircraft_model_id])
            ->one();

        if ($certificate->load(Yii::$app->request->post())) {
            // Keep existing certificate file unless a new file is uploaded.
            $certificateFile = UploadedFile::getInstance($certificate, 'certificate');
            if ($certificateFile) {
                $uploadPath = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . $certificateFile->extension;
                $certificateFile->saveAs($uploadPath);
                $certificate->certificate = $uploadPath;
            }

            if ($certificate->validate() && $certificate->save()) {
                Yii::$app->session->setFlash('success', 'Aircraft certificate updated successfully.');
                return $this->redirect(['index']);
            }
        }

        return $this->render('update', [
            'certificate' => $certificate,
            'mroProfile' => $mroProfile,
            'aircraftModel' => $aircraftModel,
        ]);
    }

    /**
     * Deletes an aircraft certificate.
     *
     * @param int $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionDelete($id = null)
    {
        $certificate = $this->findCertificateModel($id);
        $certificate->delete();

        Yii::$app->session->setFlash('success', 'Aircraft certificate deleted successfully.');
        return $this->redirect(['index']);
    }

    /**
     * Returns aircraft models by manufacturer for dependent dropdowns.
     *
     * @param string $manufacturer
     * @return array
     */
    public function actionGetModelsByManufacturer($manufacturer)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        return AircraftModel::find()
            ->where(['manufacturer' => $manufacturer])
            ->orderBy(['model' => SORT_ASC])
            ->select(['aircraft_model_id as id', 'model as name'])
            ->asArray()
            ->all();
    }

    /**
     * Finds certificate model and ensures it belongs to the logged-in MRO.
     *
     * @param int $id
     * @return MroAircraftCertificate
     * @throws NotFoundHttpException
     */
    protected function findCertificateModel($id)
    {
        if ($id === null || $id === '') {
            throw new NotFoundHttpException('Aircraft certificate ID is missing.');
        }

        $mroId = Yii::$app->session->get('mro_id');

        // Use findOne($id) so Yii resolves the real primary key of the model.
        // This works whether the primary key is named id, certificate_id,
        // mro_aircraft_certificate_id, or another configured PK.
        $certificate = MroAircraftCertificate::findOne($id);

        if (!$certificate || (string) $certificate->mro_id !== (string) $mroId) {
            throw new NotFoundHttpException('Aircraft certificate not found.');
        }

        return $certificate;
    }
}
