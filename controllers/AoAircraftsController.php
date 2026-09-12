<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use app\models\Aircrafts;
use app\models\AircraftModel;
use app\models\AoProfile;
use yii\data\Pagination;
use yii\web\NotFoundHttpException;

class AoAircraftsController extends Controller
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
                            // Allow access only to AO users
                            return in_array(Yii::$app->session->get('user_type'), ['ao'], true);
                        },
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        // Fetch the AO ID from the session.
        $ao_id = Yii::$app->session->get('ao_id');

        // Keep the same search parameter used by the view.
        $search = trim((string) Yii::$app->request->get('search', ''));

        // Fetch only aircraft that belong to the connected AO.
        $query = Aircrafts::find()
            ->with(['owner', 'certificateType'])
            ->where(['ao_id' => $ao_id]);

        // Apply search before count and pagination.
        if ($search !== '') {
            $query->andWhere([
                'or',
                ['like', 'manufacturer', $search],
                ['like', 'model', $search],
                ['like', 'serial_number', $search],
                ['like', 'registration_number', $search],
            ]);
        }

        $pagination = new Pagination([
            'defaultPageSize' => 20,
            'totalCount' => $query->count(),
        ]);

        $aircraftModels = $query
            ->orderBy([
                'manufacturer' => SORT_ASC,
                'model' => SORT_ASC,
                'registration_number' => SORT_ASC,
            ])
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        return $this->render('index', [
            'aircraftModels' => $aircraftModels,
            'pagination' => $pagination,
            'search' => $search,
        ]);
    }

    public function actionView($id)
    {
        $ao_id = Yii::$app->session->get('ao_id');

        $aircraft = Aircrafts::findOne([
            'aircraft_id' => $id,
            'ao_id' => $ao_id,
        ]);

        if (!$aircraft) {
            throw new NotFoundHttpException('Aircraft not found.');
        }

        return $this->render('view', [
            'aircraft' => $aircraft,
        ]);
    }

    public function actionCreate()
    {
        $ao_id = Yii::$app->session->get('ao_id');
        $owner = AoProfile::findOne(['ao_id' => $ao_id]);

        $aircraftModel = new Aircrafts();
        $manufacturers = AircraftModel::find()
            ->orderBy(['manufacturer' => SORT_ASC])
            ->select('manufacturer')
            ->distinct()
            ->all();
        $models = AircraftModel::find()->orderBy(['model' => SORT_ASC])->all();

        if ($aircraftModel->load(Yii::$app->request->post())) {
            // Always attach the aircraft to the connected AO.
            $aircraftModel->ao_id = $ao_id;

            $aircraftModel->aircraft_model_id = AircraftModel::find()
                ->select('aircraft_model_id')
                ->where(['model' => $aircraftModel->model])
                ->andWhere(['manufacturer' => $aircraftModel->manufacturer])
                ->scalar();

            if ($aircraftModel->save()) {
                Yii::$app->session->setFlash('message', 'Aircraft Model created successfully!');
                return $this->redirect(['index']);
            }

            Yii::$app->session->setFlash(
                'error',
                'Failed to create aircraft model. Please check the submitted data.'
            );
        }

        return $this->render('create', [
            'aircraftModel' => $aircraftModel,
            'owner' => $owner,
            'manufacturers' => $manufacturers,
            'models' => $models,
        ]);
    }

    public function actionUpdate($id)
    {
        $ao_id = Yii::$app->session->get('ao_id');
        $owner = AoProfile::findOne(['ao_id' => $ao_id]);

        $aircraftModel = Aircrafts::findOne([
            'aircraft_id' => $id,
            'ao_id' => $ao_id,
        ]);

        if (!$aircraftModel) {
            throw new NotFoundHttpException('Aircraft model not found.');
        }

        $manufacturers = AircraftModel::find()
            ->orderBy(['manufacturer' => SORT_ASC])
            ->select('manufacturer')
            ->distinct()
            ->all();
        $models = AircraftModel::find()->orderBy(['model' => SORT_ASC])->all();

        if ($aircraftModel->load(Yii::$app->request->post())) {
            // Keep ownership fixed to the connected AO.
            $aircraftModel->ao_id = $ao_id;

            $aircraftModel->aircraft_model_id = AircraftModel::find()
                ->select('aircraft_model_id')
                ->where(['model' => $aircraftModel->model])
                ->andWhere(['manufacturer' => $aircraftModel->manufacturer])
                ->scalar();

            if ($aircraftModel->save()) {
                Yii::$app->session->setFlash('message', 'Aircraft Model updated successfully!');
                return $this->redirect(['index']);
            }

            Yii::$app->session->setFlash(
                'error',
                'Failed to update aircraft model. Please check the submitted data.'
            );
        }

        return $this->render('update', [
            'aircraftModel' => $aircraftModel,
            'owner' => $owner,
            'manufacturers' => $manufacturers,
            'models' => $models,
        ]);
    }

    public function actionDelete($id)
    {
        $ao_id = Yii::$app->session->get('ao_id');

        $aircraftModel = Aircrafts::findOne([
            'aircraft_id' => $id,
            'ao_id' => $ao_id,
        ]);

        if (!$aircraftModel) {
            Yii::$app->session->setFlash('error', 'Aircraft model not found.');
            return $this->redirect(['index']);
        }

        if ($aircraftModel->delete()) {
            Yii::$app->session->setFlash('success', 'Aircraft model deleted successfully.');
        } else {
            Yii::$app->session->setFlash('error', 'Failed to delete aircraft model.');
        }

        return $this->redirect(['index']);
    }
}
