<?php
namespace app\controllers;

use Yii;
use app\models\Terms;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

class TermsController extends Controller
{

    public function behaviors()
    {
        return [
            /*
             * CORRECTIF SÉCURITÉ S-1 (critique, 2026-11) :
             * le bloc 'access' était placé PAR ERREUR à l'intérieur de
             * 'verbs' => 'actions'. Yii le lisait donc comme une règle de verbe
             * HTTP pour une action nommée « access » (inexistante) et
             * n'attachait jamais le filtre AccessControl. Résultat : n'importe
             * quel visiteur, même non connecté, pouvait créer, modifier ou
             * supprimer les Conditions Générales.
             *
             * Correction : 'access' est remonté au même niveau que 'verbs'.
             * Toutes les actions sont réservées aux administrateurs ; la page
             * publique des CGU reste servie par SiteController::actionTerms().
             */
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Only authenticated users
                        'matchCallback' => function ($rule, $action) {
                            // Allow access only to users with specific user types
                            return Yii::$app->session->get('user_type') === 'admin';
                        }
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        // List all terms
        $terms = Terms::find()->all();
        return $this->render('index', [
            'terms' => $terms,
        ]);
    }

    public function actionView($id)
    {
        $model = Terms::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    public function actionCreate()
    {
        // Create a new term
        $model = new Terms();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        // Update an existing term
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }
        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        // Delete a term
        $this->findModel($id)->delete();
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        // Find a term model by its primary key
        if (($model = Terms::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
