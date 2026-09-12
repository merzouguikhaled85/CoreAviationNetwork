<?php

namespace app\controllers;

use Yii;
use app\models\MroInsuranceDocuments;
use app\models\MroProfile;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\helpers\FileHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class MroInsuranceDocumentsController extends Controller
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
                            return Yii::$app->session->get('user_type') === 'mro';
                        },
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all insurance documents for the connected MRO.
     */
    public function actionIndex()
    {
        $mroId = Yii::$app->session->get('mro_id');

        $mroProfile = MroProfile::findOne($mroId);
        if (!$mroProfile) {
            throw new NotFoundHttpException('MRO profile not found.');
        }

        $search = trim((string) Yii::$app->request->get('search', ''));

        $query = MroInsuranceDocuments::find()
            ->where(['mro_id' => $mroId]);

        // Search is grouped in one AND condition so it never breaks the MRO ownership filter.
        if ($search !== '') {
            $query->andWhere([
                'or',
                ['like', 'id', $search],
                ['like', 'file_name', $search],
                ['like', 'file_type', $search],
                ['like', 'file_path', $search],
                ['like', 'created_at', $search],
            ]);
        }

        $pagination = new Pagination([
            'defaultPageSize' => 7,
            'totalCount' => $query->count(),
        ]);

        $documents = $query
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        return $this->render('index', [
            'title' => 'MRO Insurance Documents',
            'documents' => $documents,
            'mroProfile' => $mroProfile,
            'pagination' => $pagination,
            'search' => $search,
        ]);
    }

    /**
     * Uploads one or multiple insurance documents.
     */
    public function actionCreate()
    {
        $mroId = Yii::$app->session->get('mro_id');

        $mroProfile = MroProfile::findOne($mroId);
        if (!$mroProfile) {
            throw new NotFoundHttpException('MRO profile not found.');
        }

        $model = new MroInsuranceDocuments();

        if (Yii::$app->request->isPost) {
            $files = UploadedFile::getInstancesByName('insurance_files');

            if (empty($files)) {
                Yii::$app->session->setFlash('error', 'No files selected.');

                return $this->render('create', [
                    'model' => $model,
                ]);
            }

            $uploadDir = Yii::getAlias('@webroot/uploads/insurance');
            $relativeDir = 'uploads/insurance';

            if (!is_dir($uploadDir)) {
                FileHelper::createDirectory($uploadDir, 0777, true);
            }

            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx'];
            $maxSize = 20 * 1024 * 1024; // 20 MB

            $savedCount = 0;
            $errors = [];

            foreach ($files as $file) {
                $extension = strtolower((string) $file->extension);

                if (!in_array($extension, $allowedExtensions, true)) {
                    $errors[] = $file->name . ' has an invalid file type.';
                    continue;
                }

                if ((int) $file->size > $maxSize) {
                    $errors[] = $file->name . ' exceeds the maximum size of 20 MB.';
                    continue;
                }

                $storedFileName = uniqid('insurance_', true) . '.' . $extension;
                $absolutePath = $uploadDir . DIRECTORY_SEPARATOR . $storedFileName;
                $relativePath = $relativeDir . '/' . $storedFileName;

                if ($file->saveAs($absolutePath)) {
                    $document = new MroInsuranceDocuments();
                    $document->mro_id = $mroId;
                    $document->file_path = $relativePath;
                    $document->file_name = $file->name;
                    $document->file_type = $file->type;
                    $document->file_size = $file->size;
                    $document->created_at = date('Y-m-d H:i:s');

                    if ($document->save(false)) {
                        $savedCount++;
                    } else {
                        @unlink($absolutePath);
                        $errors[] = $file->name . ' could not be saved in the database.';
                    }
                } else {
                    $errors[] = $file->name . ' could not be uploaded.';
                }
            }

            if ($savedCount > 0) {
                Yii::$app->session->setFlash('success', $savedCount . ' insurance document(s) uploaded successfully.');

                if (!empty($errors)) {
                    Yii::$app->session->setFlash('error', implode('<br>', $errors));
                }

                return $this->redirect(['index']);
            }

            Yii::$app->session->setFlash(
                'error',
                !empty($errors) ? implode('<br>', $errors) : 'No document could be uploaded.'
            );
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Displays a single insurance document.
     */
    public function actionView($id = null)
    {
        if ($id === null || $id === '') {
            throw new NotFoundHttpException('Document ID is missing.');
        }

        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes a single insurance document.
     */
    public function actionDelete($id = null)
    {
        if ($id === null || $id === '') {
            throw new NotFoundHttpException('Document ID is missing.');
        }

        $model = $this->findModel($id);

        // Delete the physical file from webroot when it exists.
        if (!empty($model->file_path)) {
            $absolutePath = Yii::getAlias('@webroot/' . ltrim((string) $model->file_path, '/'));

            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }

        $model->delete();

        Yii::$app->session->setFlash('success', 'Document deleted successfully.');

        return $this->redirect(['index']);
    }

    /**
     * Finds one document and guarantees it belongs to the connected MRO.
     */
    protected function findModel($id)
    {
        $mroId = Yii::$app->session->get('mro_id');

        $model = MroInsuranceDocuments::find()
            ->where(['id' => $id, 'mro_id' => $mroId])
            ->one();

        if (!$model) {
            throw new NotFoundHttpException('Document not found.');
        }

        return $model;
    }
}
