<?php

namespace app\controllers;

use app\models\AoNotificationsPreferences;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\Conversations;
use app\models\Requests;
use app\models\MroProfile;
use app\models\AoProfile;
use app\models\Chat;
use app\models\MroNotificationsPreferences;
use app\components\UrlIdHelper;
use yii\helpers\VarDumper;
use yii\db\Expression;
use yii\data\Pagination;

use yii\web\NotFoundHttpException;

class ConversationsController extends Controller
{

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['home'],
                'rules' => [
                    [
                        'actions' => ['home'],
                        'allow' => true,
                        'roles' => ['@'], // Only authenticated users
                    ],
                ],
            ],
        ];
    }
public function actionIndex()
{
    $user_id = null;
    $user_type = null;

    if (Yii::$app->session->get('mro_id')) {
        $user_id = Yii::$app->session->get('mro_id');
        $user_type = 'mro';
    } elseif (Yii::$app->session->get('ao_id')) {
        $user_id = Yii::$app->session->get('ao_id');
        $user_type = 'ao';
    }

    if ($user_id === null || $user_type === null) {
        throw new \yii\web\ForbiddenHttpException('You are not authorized to view this page.');
    }

    // Search keyword used by the request-style filter form.
    $search = trim((string) Yii::$app->request->get('search', ''));

    // Keep the chat scope limited to the connected user type.
    // This avoids mixing AO and MRO records that may share the same numeric ID.
    $chatQuery = Chat::find();

    if ($user_type === 'mro') {
        $chatQuery->where(['mro_id' => $user_id]);
    } else {
        $chatQuery->where(['ao_id' => $user_id]);
    }

    $chats = $chatQuery->all();

    $groupedConversations = [];
    $groupLatestTime = [];

    foreach ($chats as $chat) {
        $conversations = Conversations::find()
            // LIST DISPLAY: preload request and aircraft data used by the compact request summary.
            ->with(['request.aircraft'])
            ->where(['chat_id' => $chat->chat_id])
            ->orderBy(['timestamp' => SORT_DESC]) // newest first
            ->all();

        foreach ($conversations as $conversation) {
            $key = $conversation->request_id . '_' . $chat->chat_id;

            $groupedConversations[$key][] = $conversation;

            // Store latest timestamp per group. The first message is already the latest.
            if (!isset($groupLatestTime[$key])) {
                $groupLatestTime[$key] = strtotime((string) $conversation->timestamp);
            }
        }
    }

    // Sort messages inside each group from newest to oldest.
    foreach ($groupedConversations as $key => $conversations) {
        usort($groupedConversations[$key], function ($a, $b) {
            return strtotime((string) $b->timestamp) <=> strtotime((string) $a->timestamp);
        });
    }

    // Sort conversation groups by latest message date.
    uksort($groupedConversations, function ($a, $b) use ($groupLatestTime) {
        return ($groupLatestTime[$b] ?? 0) <=> ($groupLatestTime[$a] ?? 0);
    });

    // Apply the filter after grouping so the search can include related usernames.
    if ($search !== '') {
        $needle = strtolower($search);

        $groupedConversations = array_filter($groupedConversations, static function ($conversations) use ($needle) {
            foreach ($conversations as $message) {
                $senderName = $message->sender->username ?? $message->sender->email ?? '';
                $receiverName = $message->receiver->username ?? $message->receiver->email ?? '';
                // LIST SEARCH: include the operational request details displayed in each row.
                $request = $message->request;
                $aircraft = $request ? $request->aircraft : null;

                $haystack = strtolower(implode(' ', [
                    (string) ($message->request_id ?? ''),
                    (string) ($message->chat_id ?? ''),
                    (string) ($message->message ?? ''),
                    (string) ($message->timestamp ?? ''),
                    (string) ($message->sender_type ?? ''),
                    (string) ($message->receiver_type ?? ''),
                    (string) $senderName,
                    (string) $receiverName,
                    (string) ($aircraft->manufacturer ?? ''),
                    (string) ($aircraft->model ?? ''),
                    (string) ($request->aircraft_registration ?? ''),
                    (string) ($request->serial_number ?? ''),
                    (string) ($request->eta ?? ''),
                    (string) ($request->etd ?? ''),
                    (string) ($request->location ?? ''),
                    (string) ($request->request_details ?? ''),
                ]));

                if (strpos($haystack, $needle) !== false) {
                    return true;
                }
            }

            return false;
        });
    }

    // Paginate grouped conversations manually because this page displays groups, not raw rows.
    $pagination = new Pagination([
        'defaultPageSize' => 7,
        'totalCount' => count($groupedConversations),
    ]);

    $groupedConversations = array_slice(
        $groupedConversations,
        $pagination->offset,
        $pagination->limit,
        true
    );

    return $this->render('index', [
        'chats' => $chats,
        'groupedConversations' => $groupedConversations,
        'pagination' => $pagination,
        'search' => $search,
    ]);
}

    public function actionView1($chat_id)
    {
        // Find the conversation based on the provided chat_id
        $conversation = Conversations::find()
            ->where(['chat_id' => $chat_id])
            ->one();
    
        // Check if the conversation exists
        if ($conversation === null) {
            throw new NotFoundHttpException('The requested conversation does not exist.');
        }
    
        // Create a new message instance
        $newMessage = new Conversations();
    
        // Check if the new message is submitted via POST request and is validated
        if ($newMessage->load(Yii::$app->request->post()) && $newMessage->validate()) {
            // Set the chat_id, request_id, sender_id, sender_type, receiver_id, and receiver_type for the new message
            $newMessage->chat_id = $chat_id;
            $newMessage->request_id = $conversation->request_id;
            $newMessage->sender_id = Yii::$app->session->get('mro_id');
            $newMessage->sender_type = 'mro';
            $newMessage->receiver_id = $conversation->receiver_id;
            $newMessage->receiver_type = 'ao';
    
            // Save the new message
            if ($newMessage->save()) {
                Yii::$app->session->setFlash('message', 'Message sent successfully.');
            } else {
                Yii::$app->session->setFlash('error', 'Failed to send message.');
            }
        }
    
        // Fetch all messages related to the provided chat_id
        $messages = Conversations::find()
        ->where(['chat_id' => $chat_id])
        ->orderBy(['timestamp' => SORT_ASC])
        ->all();
    
    
        return $this->render('view', [
            'conversation' => $conversation,
            'newMessage' => $newMessage,
            'messages' => $messages,
        ]);
    }
    

    public function actionView($chat_id)
{
    /*
     * SECURITY: public conversation URLs carry a signed chat token.
     * Decode it once, then verify that the connected AO/MRO owns this chat.
     */
    $chat_id = $this->decodeChatId($chat_id);
    $chat = $this->findAuthorizedChat($chat_id);

    // Find the first conversation message linked to this chat
    $conversation = Conversations::find()
        ->where(['chat_id' => $chat_id])
        ->one();

    if ($conversation === null) {
        throw new NotFoundHttpException('The requested conversation does not exist.');
    }

    // Create a new message instance
    $newMessage = new Conversations();

    // Detect connected user type
    $isMro = Yii::$app->session->get('mro_id') ? true : false;
    $isAo = Yii::$app->session->get('ao_id') ? true : false;

    if (!$isMro && !$isAo) {
        throw new \yii\web\ForbiddenHttpException('You are not authorized to send messages.');
    }

    // Handle new message submission
    if ($newMessage->load(Yii::$app->request->post())) {

        // Assign chat and request data
        $newMessage->chat_id = $chat_id;
        $newMessage->request_id = $conversation->request_id;

        // Assign sender and receiver depending on connected user type
        if ($isMro) {
            $newMessage->sender_id = Yii::$app->session->get('mro_id');
            $newMessage->sender_type = 'mro';
            $newMessage->receiver_id = $chat->ao_id;
            $newMessage->receiver_type = 'ao';
        } else {
            $newMessage->sender_id = Yii::$app->session->get('ao_id');
            $newMessage->sender_type = 'ao';
            $newMessage->receiver_id = $chat->mro_id;
            $newMessage->receiver_type = 'mro';
        }

        // Save message
        if ($newMessage->save()) {
            Yii::$app->session->setFlash('success', 'Message sent successfully.');
            return $this->redirect(['view', 'chat_id' => UrlIdHelper::encode($chat_id)]);
        }

        Yii::$app->session->setFlash('error', 'Failed to send message.');
    }

    // Fetch all messages linked to this chat
    $messages = Conversations::find()
        ->where(['chat_id' => $chat_id])
        ->orderBy(['timestamp' => SORT_ASC])
        ->all();

    // Render view
    return $this->render('view', [
        'conversation' => $conversation,
        'newMessage' => $newMessage,
        'messageModel' => $newMessage, // Required by the SaaS design form
        'messages' => $messages,
    ]);
}
    
    public function actionReply1($chat_id)
    {
        // Fetch the chat associated with the conversation
        $chat = Chat::findOne($chat_id);
    
        // Load the conversation by its ID
        $conversation = Conversations::findOne(['chat_id' => $chat_id]);
    
        // Check if the conversation exists
        if ($conversation === null) {
            throw new NotFoundHttpException('The requested conversation does not exist.');
        }
    
        // Create a new message for the conversation
        $newMessage = new Conversations();
    
        // Check if the new message is submitted via POST request
        if ($newMessage->load(Yii::$app->request->post())) {
            // Set the chat ID for the new message
            $newMessage->chat_id = $chat_id;
    
            // Set the sender and receiver IDs and types
            $newMessage->request_id = $conversation->request_id;
            $newMessage->sender_id = Yii::$app->session->get('mro_id') ? Yii::$app->session->get('mro_id') : Yii::$app->session->get('ao_id');
            $newMessage->sender_type = Yii::$app->session->get('mro_id') ? 'mro' : 'ao';
            $newMessage->receiver_id = Yii::$app->session->get('mro_id') ? $chat->ao_id : $chat->mro_id;
            $newMessage->receiver_type = Yii::$app->session->get('mro_id') ? 'ao' : 'mro';
    
            // Save the new message
            if ($newMessage->save()) {
                if($newMessage->receiver_type == 'mro'){
                              //platform notification
                              $aoNotificationsPreferences = MroNotificationsPreferences::findOne(['mro_id' => $newMessage->receiver_id]);
                              if ($aoNotificationsPreferences && $aoNotificationsPreferences->notify_by_platform) {
                                // Call the action to save the notification
                                Yii::$app->runAction('notification/save-notification', [
                                    'recipientType' => 'mro',
                                    'recipientId' => $newMessage->receiver_id,
                                    'message' => 'New message received for request #' . $newMessage->request_id . '.',
                                    // SECURITY: notification links expose only the signed chat token.
                                    'actions' => Yii::$app->urlManager->createUrl([
                                        'conversations/view',
                                        'chat_id' => UrlIdHelper::encode($newMessage->chat_id),
                                    ]),

                                ]);
                            }
                }else if ($newMessage->receiver_type == 'ao'){
                      //platform notification
                      $aoNotificationsPreferences = AoNotificationsPreferences::findOne(['ao_id' => $newMessage->receiver_id]);
                      if ($aoNotificationsPreferences && $aoNotificationsPreferences->notify_by_platform) {
                        // Call the action to save the notification
                        Yii::$app->runAction('notification/save-notification', [
                            'recipientType' => 'ao',
                            'recipientId' => $newMessage->receiver_id,
                            'message' => 'New message received for request #' . $newMessage->request_id . '.',
                            // SECURITY: notification links expose only the signed chat token.
                            'actions' => Yii::$app->urlManager->createUrl([
                                'conversations/view',
                                'chat_id' => UrlIdHelper::encode($newMessage->chat_id),
                            ]),

                        ]);
                    }
                }





                Yii::$app->session->setFlash('message', 'Message sent successfully.');
            } else {
                Yii::$app->session->setFlash('error', 'Failed to send message.');
            }
        }
        $newMessage->message = '';

        // Fetch all messages related to the conversation's chat ID
        $messages = Conversations::find()
        ->where(['chat_id' => $chat_id])
        ->orderBy(['timestamp' => SORT_ASC])
        ->all();
    
        // Render the reply form with the conversation and new message
        return $this->render('view', [
            'conversation' => $conversation,
            'newMessage' => $newMessage,
            'messages' => $messages,
        ]);
    }
    
    public function actionReply($chat_id)
{
    /* Apply the same signed-ID and participant check used by actionView(). */
    $chat_id = $this->decodeChatId($chat_id);
    $chat = $this->findAuthorizedChat($chat_id);

    // Load the conversation by its chat ID
    $conversation = Conversations::findOne(['chat_id' => $chat_id]);

    // Check if the conversation exists
    if ($conversation === null) {
        throw new NotFoundHttpException('The requested conversation does not exist.');
    }

    // Create a new message for the conversation
    $newMessage = new Conversations();

    // Check if the new message is submitted via POST request
    if ($newMessage->load(Yii::$app->request->post())) {

        // Set the chat ID for the new message
        $newMessage->chat_id = $chat_id;

        // Set request ID
        $newMessage->request_id = $conversation->request_id;

        // Detect current sender type
        $isMro = Yii::$app->session->get('mro_id') ? true : false;

        // Set sender data
        $newMessage->sender_id = $isMro
            ? Yii::$app->session->get('mro_id')
            : Yii::$app->session->get('ao_id');

        $newMessage->sender_type = $isMro ? 'mro' : 'ao';

        // Set receiver data
        $newMessage->receiver_id = $isMro
            ? $chat->ao_id
            : $chat->mro_id;

        $newMessage->receiver_type = $isMro ? 'ao' : 'mro';

        // Save the new message
        if ($newMessage->save()) {

            // Send platform notification to MRO
            if ($newMessage->receiver_type === 'mro') {

                $mroNotificationsPreferences = MroNotificationsPreferences::findOne([
                    'mro_id' => $newMessage->receiver_id,
                ]);

                if ($mroNotificationsPreferences && $mroNotificationsPreferences->notify_by_platform) {
                    Yii::$app->runAction('notification/save-notification', [
                        'recipientType' => 'mro',
                        'recipientId' => $newMessage->receiver_id,
                        'message' => 'New message received for request #' . $newMessage->request_id . '.',
                        // Keep notification links signed; raw chat IDs must never be exposed.
                        'actions' => Yii::$app->urlManager->createUrl([
                            'conversations/view',
                            'chat_id' => UrlIdHelper::encode($newMessage->chat_id),
                        ]),
                    ]);
                }
            }

            // Send platform notification to AO
            if ($newMessage->receiver_type === 'ao') {

                $aoNotificationsPreferences = AoNotificationsPreferences::findOne([
                    'ao_id' => $newMessage->receiver_id,
                ]);

                if ($aoNotificationsPreferences && $aoNotificationsPreferences->notify_by_platform) {
                    Yii::$app->runAction('notification/save-notification', [
                        'recipientType' => 'ao',
                        'recipientId' => $newMessage->receiver_id,
                        'message' => 'New message received for request #' . $newMessage->request_id . '.',
                        // Keep notification links signed; raw chat IDs must never be exposed.
                        'actions' => Yii::$app->urlManager->createUrl([
                            'conversations/view',
                            'chat_id' => UrlIdHelper::encode($newMessage->chat_id),
                        ]),
                    ]);
                }
            }

            // No success flash here.
            // The global toaster would appear after every sent chat message.
            Yii::$app->session->removeFlash('message');
            Yii::$app->session->removeFlash('success');

            return $this->redirect(['view', 'chat_id' => UrlIdHelper::encode($chat_id)]);
        }

        // Keep only error flash
        Yii::$app->session->setFlash('error', 'Failed to send message.');

        return $this->redirect(['view', 'chat_id' => UrlIdHelper::encode($chat_id)]);
    }

    // Reset message field
    $newMessage->message = '';

    // Fetch all messages related to the conversation's chat ID
    $messages = Conversations::find()
        ->where(['chat_id' => $chat_id])
        ->orderBy(['timestamp' => SORT_ASC])
        ->all();

    // Render the chat view
    return $this->render('view', [
        'conversation' => $conversation,
        'newMessage' => $newMessage,
        'messages' => $messages,
    ]);
}
    


    public function actionCreate()
    {
        // Create a new conversation model
        $conversation = new Conversations();

        // Check if the request is a POST request and load the posted data into the model
        if ($conversation->load(Yii::$app->request->post()) && $conversation->save()) {
            // If the model is saved successfully, redirect to the conversation view page
            return $this->redirect(['view', 'id' => $conversation->id]);
        }

        // If the model couldn't be saved or the request is not POST, render the chat create form
        return $this->render('create', [
            'conversation' => $conversation,
        ]);
    }
public function actionFetchMessages($conversationId)
{
    /* AJAX polling uses the same signed token and ownership validation as the page. */
    $chatId = $this->decodeChatId($conversationId);
    $this->findAuthorizedChat($chatId);

    $messages = Conversations::find()->where(['chat_id' => $chatId])->orderBy(['timestamp' => SORT_ASC])->all();

    return $this->renderPartial('_message_list', [
        'messages' => $messages,
    ]);
}

/**
 * Decode a signed public chat identifier.
 */
private function decodeChatId($token): int
{
    $chatId = UrlIdHelper::decode($token);

    if (!$chatId) {
        throw new NotFoundHttpException('Invalid conversation link.');
    }

    return (int) $chatId;
}

/**
 * SECURITY: return the chat only when the connected AO or MRO is a participant.
 */
private function findAuthorizedChat(int $chatId): Chat
{
    $chat = Chat::findOne($chatId);

    if ($chat === null) {
        throw new NotFoundHttpException('The requested chat does not exist.');
    }

    $mroId = (int) Yii::$app->session->get('mro_id');
    $aoId = (int) Yii::$app->session->get('ao_id');
    $isParticipant = ($mroId > 0 && (int) $chat->mro_id === $mroId)
        || ($aoId > 0 && (int) $chat->ao_id === $aoId);

    if (!$isParticipant) {
        throw new \yii\web\ForbiddenHttpException('You are not authorized to access this conversation.');
    }

    return $chat;
}

}
