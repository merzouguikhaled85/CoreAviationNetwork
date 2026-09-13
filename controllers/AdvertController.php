<?php 

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\UploadedFile;
use app\models\Advert;
use app\models\AdminProfile;
use yii\data\Pagination;


class AdvertController extends Controller
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
                            return in_array(Yii::$app->session->get('user_type'), ['admin']);
                        }
                    ],
                ],
            ],
            // Les changements d'état exigent une requête POST protégée par CSRF.
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'toggle-status' => ['POST'],
                    'delete' => ['POST'],
                ],
            ],
        ];
    }
    public function actionIndex()
    {
        // Define the query
        $query = Advert::find()->with('admin');
    
        // Create a pagination object with a total count and a limit of 20 per page
        $pagination = new Pagination([
            'defaultPageSize' => 20,
            'totalCount' => $query->count(),
        ]);
    
        // Adjust the query using the pagination object
        $adverts = $query->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();
    
        // Render the view, passing the adverts and pagination objects
        return $this->render('index', [
            'adverts' => $adverts,
            'pagination' => $pagination,
        ]);
    }

    public function actionCreate()
    {
        $advert = new Advert();
        $admins = AdminProfile::find()->select(['admin_id', 'username'])->all();
        $adminList = ArrayHelper::map($admins, 'admin_id', 'username');

        if ($advert->load(Yii::$app->request->post())) {
            $useUrl = Yii::$app->request->post('Advert')['use_url'];
            if ($useUrl) {
                $advert->advert_type = 'video';
                $advert->content = Yii::$app->request->post('Advert')['url'];
            } else {
                $uploadedFile = UploadedFile::getInstance($advert, 'content');
                if ($uploadedFile instanceof UploadedFile) {
                    $extension = strtolower($uploadedFile->extension);
                    $fileName = 'advert_' . Yii::$app->security->generateRandomString(10) . '.' . $extension;
                    $uploadedFile->saveAs('uploads/' . $fileName);

                    $advert->content = $fileName;
                    $advert->advert_type = in_array($extension, ['jpg', 'jpeg', 'png', 'gif']) ? 'photo' : 'video';
                } else {
                    Yii::$app->session->setFlash('message', 'Invalid file upload.');
                    return $this->refresh();
                }
            }

            $advert->start_date = Yii::$app->request->post('Advert')['start_date'];
            $advert->end_date = Yii::$app->request->post('Advert')['end_date'];
            $advert->status = 'inactive';

            if ($advert->save(false)) {
                Yii::$app->session->setFlash('message', 'Advert created successfully.');
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('message', 'Failed to create advert.');
            }
        }

        return $this->render('create', ['advert' => $advert, 'adminList' => $adminList]);
    }
    
    
    public function actionUpdate($id)
    {
        $advert = Advert::findOne($id);
        $admins = AdminProfile::find()->select(['admin_id', 'username'])->all();
        $adminList = ArrayHelper::map($admins, 'admin_id', 'username');
    
        if ($advert->load(Yii::$app->request->post())) {
            // Check if the user selected to use a URL instead of uploading a file
            $useUrl = Yii::$app->request->post('Advert')['use_url'];
            if ($useUrl) {
                $advert->advert_type = 'video'; // Set advert type to video if using URL
                $advert->content = Yii::$app->request->post('Advert')['url']; // Set URL directly
            } else {
                $uploadedFile = UploadedFile::getInstance($advert, 'content');
                if ($uploadedFile instanceof UploadedFile) {
                    $extension = strtolower($uploadedFile->extension);
                    $fileName = 'advert_' . Yii::$app->security->generateRandomString(10) . '.' . $extension;
                    $uploadedFile->saveAs('uploads/' . $fileName);

                    $advert->content = $fileName;
                    $advert->advert_type = in_array($extension, ['jpg', 'jpeg', 'png', 'gif']) ? 'photo' : 'video';
                } else {
                    // If content is not an UploadedFile instance, it means we retain the existing file
                    // But if it was an external URL, the type is probably video or we just keep it
                }
            }
    
            $advert->start_date = Yii::$app->request->post('Advert')['start_date'];
            $advert->end_date = Yii::$app->request->post('Advert')['end_date'];
    
            if ($advert->save(false)) {
                Yii::$app->session->setFlash('message', 'Advert updated successfully.');
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('message', 'Failed to update advert.');
            }
        }
    
        return $this->render('update', ['advert' => $advert, 'adminList' => $adminList]);
    }
    

    public function actionToggleStatus($id)
    {
        $advert = Advert::findOne($id);
        if ($advert) {
            $advert->toggleStatus();
            Yii::$app->session->setFlash('message', 'Advert status toggled successfully.');
        } else {
            Yii::$app->session->setFlash('message', 'Advert not found.');
        }
        return $this->redirect(['index']);
    }

    public function actionDelete($id)
    {
        $advert = Advert::findOne($id);
        if ($advert) {
            $advert->delete();
            Yii::$app->session->setFlash('message', 'Advert deleted successfully.');
        } else {
            Yii::$app->session->setFlash('message', 'Advert not found.');
        }

        return $this->redirect(['index']);
    }

    public function actionView($id)
    {


        $advert = Advert::findOne($id);

        return $this->render('view', ['advert' => $advert]);
    }
}
