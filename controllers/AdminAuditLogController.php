<?php

namespace app\controllers;

use app\components\UrlIdHelper;
use app\models\AuditLog;
use app\models\AuditLogSearch;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Consultation uniquement : aucune action de creation, modification ou suppression.
 */
class AdminAuditLogController extends Controller
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
                        return Yii::$app->session->get('user_type') === 'admin';
                    },
                ]],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new AuditLogSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'summary' => $this->loadSummary(),
        ]);
    }

    public function actionView($id)
    {
        $id = UrlIdHelper::decodeOrFail($id, 'Invalid audit event link.');
        $model = AuditLog::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('The requested audit event does not exist.');
        }

        $relatedQuery = AuditLog::find()->andWhere(['<>', 'id', $model->id]);
        if ($model->request_id) {
            $relatedQuery->andWhere(['request_id' => $model->request_id]);
        } else {
            $relatedQuery->andWhere('0=1');
        }

        $relatedProvider = new ActiveDataProvider([
            'query' => $relatedQuery,
            'sort' => ['defaultOrder' => ['created_at' => SORT_ASC, 'id' => SORT_ASC]],
            'pagination' => ['pageSize' => 25],
        ]);

        return $this->render('view', [
            'model' => $model,
            'relatedProvider' => $relatedProvider,
        ]);
    }

    public function actionRequest($requestId)
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $requestId)) {
            throw new NotFoundHttpException('The requested correlation ID is invalid.');
        }

        $searchModel = new AuditLogSearch(['request_id' => $requestId]);
        $params = array_merge(Yii::$app->request->queryParams, ['request_id' => $requestId]);
        $dataProvider = $searchModel->search($params);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'summary' => $this->loadSummary(),
        ]);
    }

    private function loadSummary(): array
    {
        $counts = AuditLog::find()
            ->select(['action', 'count' => 'COUNT(*)'])
            ->groupBy('action')
            ->indexBy('action')
            ->asArray()
            ->all();

        $value = static function (string $action) use ($counts): int {
            return isset($counts[$action]) ? (int) $counts[$action]['count'] : 0;
        };

        return [
            'total' => array_sum(array_map(static function ($row) { return (int) $row['count']; }, $counts)),
            'login' => $value('LOGIN'),
            'login_failed' => $value('LOGIN_FAILED'),
            'update' => $value('UPDATE'),
            'delete' => $value('DELETE'),
        ];
    }
}
