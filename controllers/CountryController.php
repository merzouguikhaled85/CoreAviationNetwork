<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use app\models\Countries;
use yii\data\Pagination;


class CountryController extends Controller
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
    /**
     * Displays the list of countries.
     *
     * @return string
     */
    public function actionIndex($search = null)
    {
        $query = Countries::find();
        
        // Apply search filter if search query is provided
        if ($search !== null) {
            $query->andFilterWhere(['like', 'country_name', $search]);
        }
            // Add orderBy clause to sort by country_name ascending
    $query->orderBy(['country_name' => SORT_ASC]);
    
        $countQuery = clone $query;
        $totalCount = $countQuery->count();
    
        $pagination = new Pagination([
            'totalCount' => $totalCount,
            'pageSize' => 20, // Maximum number of countries per page
        ]);
    
        $countries = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();
    
        return $this->render('home', [
            'countries' => $countries,
            'pagination' => $pagination,
        ]);
    }
    
    

    /**
     * Displays a single country.
     *
     * @param int $id
     * @return string
     */
    public function actionView($id)
    {
        $country = Countries::findOne($id);
        return $this->render('view', ['country' => $country]);
    }

    /**
     * Updates an existing country.
     *
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionUpdate($id)
    {
        $country = Countries::findOne($id);
        if ($country->load(Yii::$app->request->post()) && $country->save()) {
            Yii::$app->getSession()->setFlash('message', 'Country Updated Successfully!');
            return $this->redirect(['index']);
        }
        return $this->render('update', ['country' => $country]);
    }

    /**
     * Deletes an existing country.
     *
     * @param int $id
     * @return \yii\web\Response
     */
    public function actionDelete($id)
    {
        $country = Countries::findOne($id);
        if ($country && $country->delete()) {
            Yii::$app->getSession()->setFlash('message', 'Country Deleted Successfully!');
        } else {
            Yii::$app->getSession()->setFlash('message', 'Failed to Delete Country!');
        }
        return $this->redirect(['index']);
    }

    /**
     * Creates a new country.
     *
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $country = new Countries();
        if ($country->load(Yii::$app->request->post()) && $country->save()) {
            Yii::$app->getSession()->setFlash('message', 'Country added successfully!');
            return $this->redirect(['index']);
        }
        return $this->render('create', ['country' => $country]);
    }
}
