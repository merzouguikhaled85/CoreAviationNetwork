<?php

namespace app\controllers;

use app\models\AdminPasswordForm;
use app\models\AdminProfile;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class AdminController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [[
                    'allow' => true,
                    'roles' => ['@'],
                    'matchCallback' => static function () {
                        $identity = Yii::$app->user->identity;
                        return Yii::$app->session->get('user_type') === 'admin'
                            && $identity !== null
                            && (int) $identity->admin_id > 0;
                    },
                ]],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['update-password' => ['GET', 'POST']],
            ],
        ];
    }

    public function actionUpdatePassword()
    {
        $model = new AdminPasswordForm($this->findCurrentAdmin());

        // GET only renders the form. Yii's normal CSRF protection guards POST.
        if (Yii::$app->request->isPost
            && $model->load(Yii::$app->request->post())
            && $model->updatePassword()) {
            Yii::$app->user->logout(true);
            Yii::$app->session->setFlash('success', 'Password updated. Please sign in again with your new password.');
            return $this->redirect(['/site/login']);
        }

        return $this->render('update-password', ['model' => $model]);
    }

    protected function findCurrentAdmin(): AdminProfile
    {
        // Never accept a target account ID or username from the submitted form.
        $admin = AdminProfile::findOne(['admin_id' => (int) Yii::$app->user->identity->admin_id]);
        if ($admin === null) {
            throw new NotFoundHttpException('Administrator account not found.');
        }
        return $admin;
    }
}
