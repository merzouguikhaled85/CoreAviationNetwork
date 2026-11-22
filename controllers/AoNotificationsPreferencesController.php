<?php

namespace app\controllers;

use Yii;
use app\models\AoNotificationsPreferences;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\Pagination;
use yii\helpers\VarDumper;

class AoNotificationsPreferencesController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Only authenticated users
                        'matchCallback' => function ($rule, $action) {
                            // Allow access only to users with specific user types
                            return in_array(Yii::$app->session->get('user_type'), ['ao']);
                        }
                    ],
                ],
            ],
        ];
    }
  

    /**
     * Lists all AoNotificationsPreferences models.
     * @return mixed
     */
public function actionIndex()
{
    $ao_id = Yii::$app->session->get('ao_id'); // Adjust as per your session setup

    // Check if the AO ID is present in the session
    if ($ao_id === null) {
        throw new NotFoundHttpException('AO ID is not set in the session.');
    }

    // Try to find the existing notification preference record
    $model = AoNotificationsPreferences::find()->where(['ao_id' => $ao_id])->one();

    // If no existing record, create a new model instance
    if (!$model) {
        // Valeurs par défaut centralisées dans AoNotificationsPreferences::DEFAULTS.
        $model = AoNotificationsPreferences::ensureDefaultsFor((int) $ao_id);
        if ($model === null) {
            throw new \yii\web\ServerErrorHttpException('Notification preferences could not be created.');
        }

        // Redirect to the update view for the newly created model
        return $this->redirect(['update', 'id' => $model->id]); // Use the id of the new model
    }

    // Load data and save the model if it's being updated
    if ($this->loadForCurrentOwner($model) && $model->save()) {
        Yii::$app->session->setFlash('success', 'Notification preference updated successfully.');
        return $this->redirect(['update', 'id' => $model->id]); // Use the model's id
    }

    // Render the update view with the model
    return $this->render('update', [
        'model' => $model,
    ]);
}


    /**
     * Displays a single AoNotificationsPreferences model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new AoNotificationsPreferences model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $ao_id = Yii::$app->session->get('ao_id'); // Adjust as per your session setup

        $model = new AoNotificationsPreferences();
        $model->ao_id = $ao_id;

        if ($this->loadForCurrentOwner($model) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Notification preference created successfully.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing AoNotificationsPreferences model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->loadForCurrentOwner($model) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Notification preference updated successfully.');
            return $this->redirect(['update', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing AoNotificationsPreferences model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Notification preference deleted successfully.');

        return $this->redirect(['index']);
    }

    /**
     * Finds the AoNotificationsPreferences model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return AoNotificationsPreferences the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    /* =====================================================================
     * CONTRÔLE DE PROPRIÉTÉ DES PRÉFÉRENCES (correctif sécurité IDOR, 2026-11)
     * =====================================================================
     *
     * PROBLÈME CORRIGÉ :
     *  1. view / update / delete chargeaient la ligne de préférences par son
     *     seul identifiant : un AO pouvait lire, modifier ou supprimer les
     *     préférences d'un autre AO en changeant l'id dans l'URL ;
     *  2. ao_id est un attribut « safe » du modèle (règles required/integer) :
     *     un POST forgé pouvait réattribuer une ligne à un autre compte.
     *
     * PRINCIPE :
     *  - findModel() ne trouve que les préférences du AO connecté
     *    (sinon 404, sans révéler l'existence de la ligne) ;
     *  - après chaque load(), ao_id est réimposé depuis la session serveur.
     */

    /**
     * Identifiant du AO connecté, lu uniquement dans la session serveur.
     *
     * @throws NotFoundHttpException si la session ne contient pas d'identifiant
     */
    private function currentOwnerId(): int
    {
        $ownerId = (int) Yii::$app->session->get('ao_id');
        if ($ownerId <= 0) {
            throw new NotFoundHttpException('AO ID is not set in the session.');
        }

        return $ownerId;
    }

    /**
     * Charge les données POST dans le modèle puis réimpose le propriétaire.
     * Remplace l'appel direct à $model->load() dans toutes les actions.
     */
    private function loadForCurrentOwner(AoNotificationsPreferences $model): bool
    {
        $loaded = $model->load(Yii::$app->request->post());

        // Le propriétaire ne vient jamais du formulaire.
        $model->ao_id = $this->currentOwnerId();

        return $loaded;
    }

    protected function findModel($id)
    {
        // CORRECTIF IDOR : on ne cherche que parmi les préférences du AO connecté.
        $model = AoNotificationsPreferences::findOne([
            'id' => (int) $id,
            'ao_id' => $this->currentOwnerId(),
        ]);

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
