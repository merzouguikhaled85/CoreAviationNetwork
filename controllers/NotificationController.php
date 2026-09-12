<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use app\models\Notification;
use yii\helpers\VarDumper;
use yii\db\Expression;

class NotificationController extends Controller
{
    // Action to save notifications
public function actionSaveNotification($recipientType, $recipientId, $message, $actions = null)
{
    // Create a new instance of the Notification model
    $notification = new Notification();

    // Assign values to the notification attributes
    $notification->recipient_type = $recipientType;
    $notification->recipient_id = $recipientId;
    $notification->content = $message;
    $notification->actions = $actions; // <-- New line added
    $notification->created_at = date('Y-m-d H:i:s'); // Current timestamp


    // Validate and save the notification
    if ($notification->validate() && $notification->save()) {
        return true; // Return true or success response as needed
    } else {
        // Handle validation or save errors
        $errors = $notification->errors;
        Yii::error('Failed to save notification: ' . json_encode($errors));
        return false; // Return false or error response
    }
}


    public function actionMarkAllAsRead()
    {
        // Determine the user type ('ao', 'mro', or 'admin')
        $userType = Yii::$app->session->get('user_type');
        $userId = $this->getUserId($userType); // Get user ID based on userType

        // Fetch notifications to mark as read based on recipient type and ID
        $notifications = Notification::find()
            ->where(['recipient_type' => $userType, 'recipient_id' => $userId])
            ->andWhere(['<>', 'status', 'read'])
            ->all();

        // Mark each notification as read
        foreach ($notifications as $notification) {
            $notification->status = 'read';
            $notification->save();
        }

        // Return success response as JSON
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['success' => true];
    }

    public function actionDeleteAll()
    {
        // Determine the user type ('ao', 'mro', or 'admin')
        $userType = Yii::$app->session->get('user_type');
        $userId = $this->getUserId($userType); // Get user ID based on userType

        // Fetch notifications to mark as read based on recipient type and ID
        $notifications = Notification::find()
            ->where(['recipient_type' => $userType, 'recipient_id' => $userId])
            ->all();

        // Mark each notification as read
        foreach ($notifications as $notification) {
            $notification->delete();
        }
        // Return success response as JSON
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['success' => true];
    }

    public function actionNotificationCount()
    {
        // Determine the user type ('ao', 'mro', or 'admin')
        $userType = Yii::$app->session->get('user_type');
        $userId = $this->getUserId($userType); // Get user ID based on userType
        

        // Logic to fetch notification count based on user type and user ID
        switch ($userType) {
            case 'ao':
                $notificationCount = Notification::find()
                    ->where(['recipient_type' => 'ao', 'recipient_id' => $userId])
                    ->andWhere(['<>', 'status', 'read']) // Add condition here
                    ->count();
                break;
            case 'mro':
                $notificationCount = Notification::find()
                    ->where(['recipient_type' => 'mro', 'recipient_id' => $userId])
                    ->andWhere(['<>', 'status', 'read']) // Add condition here
                    ->count();
                break;
            case 'admin':
                // For admin, you might want to fetch all notifications, so recipient_id is optional
                $notificationCount = Notification::find()
                    ->where(['recipient_type' => 'admin'])
                    ->andWhere(['<>', 'status', 'read']) // Add condition here
                    ->count();
                break;
            default:
                $notificationCount = 0;
                break;
        }

        // Return as JSON response
        Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            'success' => true,
            'count' => $notificationCount,
        ];
    }

    /**
     * Helper function to retrieve user ID based on user type
     */
    private function getUserId($userType)
    {
        switch ($userType) {
            case 'ao':
                return Yii::$app->session->get('ao_id');
            case 'mro':
                return Yii::$app->session->get('mro_id');
            case 'admin':
                return Yii::$app->session->get('admin_id');
            default:
                return null;
        }
    }

    public function actionUpdateNotification($id)
    {
        $notification = Notification::findOne($id);
        if ($notification) {
            $notification->status = 'read';
            if ($notification->save()) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => true];
            }
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['success' => false];
    }
    public function actionDeleteNotification($id)
    {
        $notification = Notification::findOne($id);
        if ($notification && $notification->delete()) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['success' => true];
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['success' => false];
    }
    public function actionNotificationList()
    {
        $userType = Yii::$app->session->get('user_type');
        $userId = $this->getUserId($userType); // Get user ID based on userType

        /*
         * FILTRE DES ONGLETS : seules les trois valeurs connues sont acceptees afin
         * qu'un parametre URL inattendu ne puisse jamais modifier la construction de
         * la requete. Le filtre reste purement visuel et ne change aucun statut métier.
         */
        $filter = (string) Yii::$app->request->get('filter', 'all');
        if (!in_array($filter, ['all', 'unread', 'read'], true)) {
            $filter = 'all';
        }

        /*
         * PORTEE UTILISATEUR : cette requete de base reproduit exactement les regles
         * existantes pour AO, MRO et admin. Elle sert ensuite aux compteurs et a la liste,
         * ce qui garantit que les onglets n'affichent jamais les donnees d'un autre rôle.
         */
        $query = Notification::find();
        switch ($userType) {
            case 'ao':
                $query->where(['recipient_type' => 'ao', 'recipient_id' => $userId]);
                break;
            case 'mro':
                $query->where(['recipient_type' => 'mro', 'recipient_id' => $userId]);
                break;
            case 'admin':
                // Le comportement admin historique reste global pour le type admin.
                $query->where(['recipient_type' => 'admin']);
                break;
            default:
                // Un rôle inconnu obtient toujours une liste vide.
                $query->where('0=1');
                break;
        }

        /*
         * COMPTEURS EN UNE REQUETE : les trois valeurs des onglets sont calculees par
         * agrégation SQL. Un statut NULL ou different de "read" est considere non lu,
         * comme dans le rendu historique du panneau.
         */
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

        /*
         * CHARGEMENT CIBLE : le statut demande est applique cote serveur et le panneau
         * est limite aux 30 notifications les plus recentes pour conserver une ouverture
         * rapide, même lorsqu'un utilisateur possede un historique important.
         */
        if ($filter === 'read') {
            $query->andWhere(['status' => 'read']);
        } elseif ($filter === 'unread') {
            $query->andWhere([
                'or',
                ['status' => null],
                ['<>', 'status', 'read'],
            ]);
        }

        $notifications = $query
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(30)
            ->all();

        // Prepare data for JSON response
        $notificationData = [];
        foreach ($notifications as $notification) {
            /*
             * DATE DE CREATION : la valeur stockee reste intacte en base. Yii applique
             * uniquement son fuseau horaire d'affichage et renvoie un format compact
             * adapte au panneau de notifications.
             */
            $formattedCreatedAt = $notification->created_at
                ? Yii::$app->formatter->asDatetime($notification->created_at, 'php:d M Y, H:i')
                : null;

            $notificationData[] = [
                'id' => $notification->id,
                'message' => $notification->content,
                'read' => $notification->status,
                'actions' => $notification->actions,  // send this to front-end too
                'created_at' => $formattedCreatedAt,

            ];
        }

        // Return as JSON response
        Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            'success' => true,
            'filter' => $filter,
            'counts' => $counts,
            'notifications' => $notificationData,
        ];
    }
}
