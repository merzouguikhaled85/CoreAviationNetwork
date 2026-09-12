<?php

namespace app\controllers;

use app\models\Airports;
use app\models\Cities;
use app\models\Countries;
use Yii;
use app\models\MroProfile;
use app\models\MroprofileAirport;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class MroAirportsController extends Controller
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
                            return in_array(Yii::$app->session->get('user_type'), ['mro']);
                        }
                    ],
                ],
            ],
        ];
    }

    /**
     * Liste des aéroports liés au profil MRO connecté
     */
    public function actionIndex1()
    {
        $mro_id = Yii::$app->session->get('mro_id');

        $mroProfile = MroProfile::findOne($mro_id);
        if (!$mroProfile) {
            throw new NotFoundHttpException('MRO profile not found.');
        }

        $search = Yii::$app->request->get('search');

        $query = Airports::find()
            ->alias('a')
            ->joinWith(['city', 'country'])
            ->innerJoin(
                'mroprofile_airport mpa',
                'mpa.airport_id = a.airport_id'
            )
            ->where(['mpa.mro_id' => $mro_id]);

        if (!empty($search)) {
            $query->andWhere([
                'or',
                ['like', 'a.airport_name', $search],
                ['like', 'a.icao', $search],
                ['like', 'city.city_name', $search],
                ['like', 'country.country_name', $search],
            ]);
        }

        $countQuery = clone $query;

        $pagination = new Pagination([
            'totalCount' => $countQuery->count(),
            'defaultPageSize' => 20,
        ]);

        $airports = $query
            ->orderBy(['a.airport_name' => SORT_ASC])
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        return $this->render('index', [
            'airports' => $airports,
            'pagination' => $pagination,
        ]);
    }
/**
 * Liste des aéroports liés au profil MRO connecté
 */
public function actionIndex()
{
    $mro_id = Yii::$app->session->get('mro_id');

    $mroProfile = MroProfile::findOne($mro_id);
    if (!$mroProfile) {
        throw new NotFoundHttpException('MRO profile not found.');
    }

    $search = Yii::$app->request->get('search');

    $query = Airports::find()
        ->alias('a')
        ->leftJoin(['c' => 'cities'], 'a.city_id = c.city_id')
        ->leftJoin(['co' => 'countries'], 'a.country_id = co.country_id')
        ->innerJoin(['mpa' => 'mroprofile_airport'], 'mpa.airport_id = a.airport_id')
        ->where(['mpa.mro_id' => $mro_id]);

    if (!empty($search)) {
        $query->andWhere([
            'or',
            ['like', 'a.airport_name', $search],
            ['like', 'a.icao', $search],
            ['like', 'c.city_name', $search],
            ['like', 'co.country_name', $search],
        ]);
    }

    $countQuery = clone $query;

    $pagination = new Pagination([
        'totalCount' => $countQuery->count(),
        'defaultPageSize' => 8,
    ]);

    $airports = $query
        ->with(['city', 'country'])
        ->orderBy(['a.airport_name' => SORT_ASC])
        ->offset($pagination->offset)
        ->limit($pagination->limit)
        ->all();

    return $this->render('index', [
        'airports' => $airports,
        'pagination' => $pagination,
        'search' => $search,
    ]);
}
    /**
     * Afficher un aéroport lié au MRO
     */
    public function actionView($id)
    {
        $mro_id = Yii::$app->session->get('mro_id');

        $airport = MroprofileAirport::findOne([
            'mro_id' => $mro_id,
            'airport_id' => $id,
        ]);

        if (!$airport) {
            throw new NotFoundHttpException('Airport not found.');
        }

        return $this->render('view', [
            'airport' => $airport,
        ]);
    }

    /**
     * Ajouter des aéroports au profil MRO
     */
    public function actionCreate()
    {
        $mro_id = Yii::$app->session->get('mro_id');

        $mroProfile = MroProfile::findOne($mro_id);
        if (!$mroProfile) {
            throw new NotFoundHttpException('MRO profile not found.');
        }

        $model = new MroprofileAirport();
        $model->mro_id = $mro_id;

        if ($model->load(Yii::$app->request->post())) {

            $post = Yii::$app->request->post('MroprofileAirport');

            $selectedAirportIds = [];

            /*
             * Airports venant de la sélection pays -> ville -> airport
             */
            if (!empty($post['airport_id'])) {
                $selectedAirportIds = array_merge(
                    $selectedAirportIds,
                    (array)$post['airport_id']
                );
            }

            /*
             * Airports venant de la recherche ICAO
             */
            if (!empty($post['airport_id_icao'])) {
                $selectedAirportIds = array_merge(
                    $selectedAirportIds,
                    (array)$post['airport_id_icao']
                );
            }

            /*
             * Supprimer les doublons et les valeurs vides
             */
            $selectedAirportIds = array_filter(array_unique($selectedAirportIds));

            if (!empty($selectedAirportIds)) {

                foreach ($selectedAirportIds as $airportId) {

                    $exists = MroprofileAirport::find()
                        ->where([
                            'mro_id' => $mro_id,
                            'airport_id' => $airportId,
                        ])
                        ->exists();

                    if (!$exists) {

                        $mroAirport = new MroprofileAirport();
                        $mroAirport->mro_id = $mro_id;
                        $mroAirport->airport_id = $airportId;

                        if (!$mroAirport->save()) {
                            Yii::$app->session->setFlash(
                                'error',
                                'Error saving some airports.'
                            );
                        }
                    }
                }

                Yii::$app->session->setFlash(
                    'message',
                    'MRO Airports saved successfully.'
                );

            } else {
                Yii::$app->session->setFlash(
                    'info',
                    'No airports selected for saving.'
                );
            }

            return $this->redirect(['index']);
        }

        /*
         * OK : countries généralement limité.
         */
        $countries = Countries::find()
            ->orderBy(['country_name' => SORT_ASC])
            ->all();

        /*
         * IMPORTANT :
         * Ne pas charger tous les airports ici.
         * Avant tu avais Airports::find()->all(), c’est la cause de l’erreur mémoire.
         * Les airports seront chargés par AJAX avec actionGetAirports() ou actionSearchAirports().
         */
        $airports = [];

        /*
         * Au départ, aucune ville chargée.
         * Les villes seront chargées par AJAX selon les pays choisis.
         */
        $cities = [];

        return $this->render('create', [
            'mroProfile' => $mroProfile,
            'model' => $model,
            'countries' => $countries,
            'cities' => $cities,
            'airports' => $airports,
        ]);
    }

    /**
     * Modifier une liaison MRO-Airport
     */
    public function actionUpdate($id)
    {
        $mro_id = Yii::$app->session->get('mro_id');

        $mroAirport = MroprofileAirport::findOne([
            'mro_id' => $mro_id,
            'airport_id' => $id,
        ]);

        if (!$mroAirport) {
            throw new NotFoundHttpException('Airport not found.');
        }

        if ($mroAirport->load(Yii::$app->request->post()) && $mroAirport->save()) {
            Yii::$app->session->setFlash('success', 'MRO Airport updated successfully.');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $mroAirport,
            'mroProfile' => MroProfile::findOne($mro_id),
            'countries' => Countries::find()->orderBy(['country_name' => SORT_ASC])->all(),
            'cities' => [],
            'airports' => [],
        ]);
    }

    /**
     * Supprimer un aéroport du profil MRO connecté
     */
    public function actionDelete($id)
    {
        $mro_id = Yii::$app->session->get('mro_id');

        $airport = MroprofileAirport::findOne([
            'mro_id' => $mro_id,
            'airport_id' => $id,
        ]);

        if (!$airport) {
            throw new NotFoundHttpException('Airport not found.');
        }

        $airport->delete();

        Yii::$app->session->setFlash('message', 'Airport removed successfully.');

        return $this->redirect(['index']);
    }

    /**
     * Récupérer les villes par pays
     */
    public function actionGetCities($country_ids)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $countryIdsArray = explode(',', $country_ids);
        $countryIdsArray = array_filter($countryIdsArray);

        if (empty($countryIdsArray)) {
            return [];
        }

        $cities = Cities::find()
            ->where(['country_id' => $countryIdsArray])
            ->orderBy(['city_name' => SORT_ASC])
            ->all();

        return ArrayHelper::map($cities, 'city_id', 'city_name');
    }

    /**
     * Récupérer les aéroports par villes
     * sans charger les aéroports déjà liés au MRO
     */
    public function actionGetAirports($city_ids)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $mro_id = Yii::$app->session->get('mro_id');

        $cityIdsArray = explode(',', $city_ids);
        $cityIdsArray = array_filter($cityIdsArray);

        if (empty($cityIdsArray)) {
            return [];
        }

        $subquery = MroprofileAirport::find()
            ->select('airport_id')
            ->where(['mro_id' => $mro_id]);

        $airports = Airports::find()
            ->where(['city_id' => $cityIdsArray])
            ->andWhere(['not in', 'airport_id', $subquery])
            ->orderBy(['airport_name' => SORT_ASC])
            ->limit(500)
            ->all();

        return ArrayHelper::map($airports, 'airport_id', 'airport_name');
    }

    /**
     * Recherche AJAX par ICAO ou nom d'aéroport
     * sans charger les aéroports déjà liés au MRO
     */
    public function actionSearchAirports($q = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $mro_id = Yii::$app->session->get('mro_id');

        $results = [];

        if ($q !== null && trim($q) !== '') {

            $subquery = MroprofileAirport::find()
                ->select('airport_id')
                ->where(['mro_id' => $mro_id]);

            $airports = Airports::find()
                ->where(['not in', 'airport_id', $subquery])
                ->andWhere([
                    'or',
                    ['like', 'icao', $q],
                    ['like', 'airport_name', $q],
                ])
                ->orderBy(['airport_name' => SORT_ASC])
                ->limit(50)
                ->all();

            foreach ($airports as $airport) {
                $results[] = [
                    'id' => $airport->airport_id,
                    'text' => trim(($airport->icao ?: 'N/A') . ' - ' . $airport->airport_name),
                ];
            }
        }

        return ['results' => $results];
    }
}