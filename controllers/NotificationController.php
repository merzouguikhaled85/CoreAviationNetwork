<?php

namespace app\controllers;

use app\models\Notification;
use app\models\Requests;
use app\components\NotificationActionUrl;
use Yii;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class NotificationController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'notification-count' => ['GET'],
                    'notification-list' => ['GET'],
                    'mark-all-as-read' => ['POST'],
                    'delete-all' => ['POST'],
                    'update-notification' => ['POST'],
                    'delete-notification' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Enregistre une notification depuis une autre action du serveur.
     * Cette action n'est jamais une API publique afin d'empêcher un utilisateur
     * connecté de fabriquer une notification pour un autre compte.
     */
    public function actionSaveNotification($recipientType, $recipientId, $message, $actions = null, $requestId = null)
    {
        if (Yii::$app->requestedAction === $this->action) {
            throw new NotFoundHttpException('Page introuvable.');
        }

        if (!in_array($recipientType, ['ao', 'mro', 'admin'], true) || (int) $recipientId <= 0) {
            Yii::warning('Notification refusée : destinataire invalide.', __METHOD__);
            return false;
        }

        $notification = new Notification();
        $notification->recipient_type = $recipientType;
        $notification->recipient_id = (int) $recipientId;
        $notification->content = (string) $message;
        $notification->actions = NotificationActionUrl::normalize($actions, Yii::$app->request->baseUrl);
        $notification->created_at = date('Y-m-d H:i:s');

        /*
         * PRIORITÉ : celle de la demande concernée. Tant que la migration
         * m261122_160000 n'est pas appliquée, la colonne n'existe pas : on l'ignore.
         */
        if ($notification->hasAttribute('priority')) {
            $notification->priority = $this->resolveRequestPriority($requestId, (string) $message);
        }

        if ($notification->validate() && $notification->save(false)) {
            return true;
        }

        Yii::error('Échec de l’enregistrement de la notification : ' . json_encode($notification->errors), __METHOD__);
        return false;
    }

    /**
     * Priorité opérationnelle (aog / urgent / routine) de la demande liée à une
     * notification. L'identifiant est passé explicitement par l'appelant ou, à
     * défaut, lu dans le message (« request #123 »). NULL si aucune demande.
     */
    private function resolveRequestPriority($requestId, string $message): ?string
    {
        $id = (int) $requestId;
        if ($id <= 0 && preg_match('/request\s*#\s*(\d+)/i', $message, $match)) {
            $id = (int) $match[1];
        }
        if ($id <= 0) {
            return null;
        }

        $priority = strtolower((string) Requests::find()
            ->select('operational_priority')
            ->where(['request_id' => $id])
            ->scalar());

        return in_array($priority, [Requests::PRIORITY_AOG, Requests::PRIORITY_URGENT, Requests::PRIORITY_ROUTINE], true)
            ? $priority
            : null;
    }

    public function actionMarkAllAsRead()
    {
        $this->asJsonResponse();
        $query = $this->queryForCurrentUser()
            ->andWhere(['or', ['status' => null], ['<>', 'status', 'read']]);
        $updated = Notification::updateAll(['status' => 'read'], $query->where);

        return ['success' => true, 'updated' => $updated];
    }

    public function actionDeleteAll()
    {
        $this->asJsonResponse();
        $query = $this->queryForCurrentUser();
        return ['success' => true, 'deleted' => Notification::deleteAll($query->where)];
    }

    public function actionNotificationCount()
    {
        $this->asJsonResponse();
        $count = $this->queryForCurrentUser()
            ->andWhere(['or', ['status' => null], ['<>', 'status', 'read']])
            ->count();

        return ['success' => true, 'count' => (int) $count];
    }

    public function actionUpdateNotification($id)
    {
        $this->asJsonResponse();
        $notification = $this->queryForCurrentUser()->andWhere(['id' => (int) $id])->one();
        if ($notification === null) {
            throw new NotFoundHttpException('Notification introuvable.');
        }

        $notification->status = 'read';
        return ['success' => $notification->save(false, ['status'])];
    }

    public function actionDeleteNotification($id)
    {
        $this->asJsonResponse();
        $notification = $this->queryForCurrentUser()->andWhere(['id' => (int) $id])->one();
        if ($notification === null) {
            throw new NotFoundHttpException('Notification introuvable.');
        }

        return ['success' => (bool) $notification->delete()];
    }

    public function actionNotificationList()
    {
        $this->asJsonResponse();

        /* Seules les valeurs connues peuvent influencer la requête SQL. */
        $filter = (string) Yii::$app->request->get('filter', 'all');
        if (!in_array($filter, ['all', 'unread', 'read'], true)) {
            $filter = 'all';
        }

        /* Une même portée sécurisée alimente la liste et les trois compteurs. */
        $query = $this->queryForCurrentUser();
        $countRow = (clone $query)
            ->select([
                'total_count' => new Expression('COUNT(*)'),
                'read_count' => new Expression("SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END)"),
                'unread_count' => new Expression("SUM(CASE WHEN status IS NULL OR status <> 'read' THEN 1 ELSE 0 END)"),
            ])
            ->asArray()
            ->one();

        $counts = [
            'all' => (int) ($countRow['total_count'] ?? 0),
            'read' => (int) ($countRow['read_count'] ?? 0),
            'unread' => (int) ($countRow['unread_count'] ?? 0),
        ];

        if ($filter === 'read') {
            $query->andWhere(['status' => 'read']);
        } elseif ($filter === 'unread') {
            $query->andWhere(['or', ['status' => null], ['<>', 'status', 'read']]);
        }

        $notifications = $query->orderBy(['created_at' => SORT_DESC])->limit(30)->all();
        $notificationData = [];
        foreach ($notifications as $notification) {
            $notificationData[] = [
                'id' => (int) $notification->id,
                'message' => $notification->content,
                'read' => $notification->status,
                'priority' => $notification->hasAttribute('priority') ? $notification->priority : null,
                'actions' => NotificationActionUrl::normalize($notification->actions, Yii::$app->request->baseUrl),
                'created_at' => $notification->created_at
                    ? Yii::$app->formatter->asDatetime($notification->created_at, 'php:d M Y, H:i')
                    : null,
            ];
        }

        return [
            'success' => true,
            'filter' => $filter,
            'counts' => $counts,
            'notifications' => $notificationData,
        ];
    }

    /**
     * Construit la portée de lecture et d'écriture du compte connecté.
     * Les administrateurs conservent la vue globale historique des notifications admin.
     */
    private function queryForCurrentUser()
    {
        [$userType, $userId] = $this->resolveCurrentRecipient();
        $query = Notification::find()->where(['recipient_type' => $userType]);
        if ($userType !== 'admin') {
            $query->andWhere(['recipient_id' => $userId]);
        }

        return $query;
    }

    /**
     * Répare la session à partir de l'identité Yii lorsque la session métier
     * n'a pas encore été initialisée, puis refuse toute identité incohérente.
     */
    private function resolveCurrentRecipient()
    {
        $session = Yii::$app->session;
        $userType = $session->get('user_type');
        $idByType = ['ao' => 'ao_id', 'mro' => 'mro_id', 'admin' => 'admin_id'];

        if (isset($idByType[$userType])) {
            $userId = (int) $session->get($idByType[$userType]);
            if ($userId > 0) {
                return [$userType, $userId];
            }
        }

        $identity = Yii::$app->user->identity;
        foreach ($idByType as $type => $attribute) {
            $userId = (int) ($identity->{$attribute} ?? 0);
            if ($userId > 0) {
                $session->set('user_type', $type);
                $session->set($attribute, $userId);
                return [$type, $userId];
            }
        }

        throw new ForbiddenHttpException('Identité utilisateur invalide.');
    }

    private function asJsonResponse()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
    }
}
