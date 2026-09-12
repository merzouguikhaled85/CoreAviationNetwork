<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use app\models\AdminProfile;

class AdminController extends Controller
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
        ];
    }
    // ... other actions

    public function actionUpdatePassword()
    {
        $username = Yii::$app->user->identity->username; // Assuming you are updating the logged-in admin's password
        $model = AdminProfile::findByUsername($username);

        if ($model) {
            // Assuming the new password input is passed as $newPassword from the form
            $hashedPassword = $model->hashPassword($model->password);
            $model->password = $hashedPassword;

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Password updated successfully.');
            } else {
                Yii::$app->session->setFlash('error', 'Failed to update password.');
            }
        } else {
            Yii::$app->session->setFlash('error', 'Admin not found.');
        }

        return $this->redirect(['index']); // Redirect to appropriate page after update
    }
}
