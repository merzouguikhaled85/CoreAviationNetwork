<?php
namespace app\controllers;

use Yii;
use app\models\MroProfile;
use app\models\Certificates;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\data\Pagination;
use app\components\UrlIdHelper;

class MroCertificatesController extends Controller
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
    /**
     * Displays all certificates associated with a specific MRO profile.
     * @param int $mro_id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionIndex()
    {
        $mro_id = Yii::$app->session->get('mro_id');

        $mroProfile = MroProfile::findOne($mro_id);
        if (!$mroProfile) {
            throw new NotFoundHttpException('MRO profile not found.');
        }

$query = Certificates::find()
    ->where(['mro_id' => $mro_id])
    ->orderBy(['type' => SORT_ASC]);

        // Handle search if search query parameter is provided
        $search = Yii::$app->request->get('search');
        if ($search) {
            // Assuming 'type' and 'certificate' are the attributes you want to search
            $query->andWhere(['or', ['like', 'type', $search], ['like', 'certificate', $search]]);
        }


        $pagination = new Pagination([
            'defaultPageSize' => 20,
            'totalCount' => $query->count(),
        ]);

        $certificates = $query->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        return $this->render('index', [
            'mroProfile' => $mroProfile,
            'certificates' => $certificates,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Displays a specific certificate.
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        /* MRO CERTIFICATE ENCODED ID 2026: decode URL token and enforce MRO ownership. */
        $certificateId = UrlIdHelper::decode($id);
        $mroId = Yii::$app->session->get('mro_id');
        if (!$certificateId) {
            throw new NotFoundHttpException('Invalid certificate link.');
        }

        $certificate = Certificates::findOne(['certificate_id' => $certificateId, 'mro_id' => $mroId]);
        if (!$certificate) {
            throw new NotFoundHttpException('Certificate not found.');
        }

        return $this->render('view', [
            'certificate' => $certificate,
        ]);
    }

    /**
     * Creates a new certificate associated with an MRO profile.
     * @param int $mro_id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
public function actionCreate()
{
    $mro_id = Yii::$app->session->get('mro_id');

    $mroProfile = MroProfile::findOne($mro_id);
    if (!$mroProfile) {
        throw new NotFoundHttpException('MRO profile not found.');
    }
$existingTypes = \app\models\Certificates::find()
    ->select('type')
    ->where(['mro_id' => $mro_id])
    ->column();
    $certificate = new Certificates();
    $certificate->mro_id = $mro_id;

    if ($certificate->load(Yii::$app->request->post())) {

        // 🔥 FILE UPLOAD
        $certificateFile = UploadedFile::getInstance($certificate, 'certificate');

        if (!$certificateFile) {
            Yii::$app->session->setFlash('error', 'Please upload a certificate file.');
            return $this->render('create', [
                'certificate' => $certificate,
                'mroProfile' => $mroProfile,
            ]);
        }

        $uploadPath = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . $certificateFile->extension;

        if (!$certificateFile->saveAs($uploadPath)) {
            Yii::$app->session->setFlash('error', 'Failed to upload certificate file.');
            return $this->render('create', [
                'certificate' => $certificate,
                'mroProfile' => $mroProfile,
            ]);
        }

        $certificate->certificate = $uploadPath;

        // 🔥 SAVE MODEL
        if ($certificate->validate() && $certificate->save()) {

            Yii::$app->session->setFlash('success', 'Certificate added successfully.');

            return $this->redirect(['index']);
        }

        // 🔥 VALIDATION ERRORS
        Yii::$app->session->setFlash('error', 'Please fix the validation errors and try again.');
    }

    return $this->render('create', [
        'certificate' => $certificate,
        'mroProfile' => $mroProfile,
        'existingTypes' =>$existingTypes,
    ]);
}

    /**
     * Updates an existing certificate.
     * @param int $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        /* MRO CERTIFICATE ENCODED ID 2026: decode URL token and enforce MRO ownership. */
        $certificateId = UrlIdHelper::decode($id);
        $mro_id = Yii::$app->session->get('mro_id');
        if (!$certificateId) {
            throw new NotFoundHttpException('Invalid certificate link.');
        }

        $certificate = Certificates::findOne(['certificate_id' => $certificateId, 'mro_id' => $mro_id]);
        if (!$certificate) {
            throw new NotFoundHttpException('Certificate not found.');
        }

        $mroProfile = MroProfile::findOne($mro_id);
        if (!$mroProfile) {
            throw new NotFoundHttpException('MRO profile not found.');
        }


        if ($certificate->load(Yii::$app->request->post())) {
            // Handle file upload
            $certificateFile = UploadedFile::getInstance($certificate, 'certificate');
            // Set the file path for the uploaded certificate file
            if ($certificateFile) {
                $uploadPath = 'uploads/' . Yii::$app->security->generateRandomString() . '.' . $certificateFile->extension;
                $certificateFile->saveAs($uploadPath);
                $certificate->certificate = $uploadPath;
            
            }
            if ($certificate->validate() && $certificate->save()) {
                Yii::$app->session->setFlash('success', 'Certificate Updated successfully.');
                return $this->redirect(['index']);
            }
        }

        return $this->render('update', [
            'certificate' => $certificate,
            'mroProfile' => $mroProfile,
        ]);
    }

    /**
     * Deletes a certificate.
     * @param int $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionDelete($id)
    {
        /* MRO CERTIFICATE ENCODED ID 2026: decode URL token and enforce MRO ownership. */
        $certificateId = UrlIdHelper::decode($id);
        $mroId = Yii::$app->session->get('mro_id');
        if (!$certificateId) {
            throw new NotFoundHttpException('Invalid certificate link.');
        }

        $certificate = Certificates::findOne(['certificate_id' => $certificateId, 'mro_id' => $mroId]);
        if (!$certificate) {
            throw new NotFoundHttpException('Certificate not found.');
        }

        $mro_id = $certificate->mro_id;
        $certificate->delete();

        Yii::$app->session->setFlash('success', 'Certificate deleted successfully.');
        return $this->redirect(['index']);
    }
}
