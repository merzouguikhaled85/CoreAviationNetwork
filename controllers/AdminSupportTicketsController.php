<?php

namespace app\controllers;

use app\components\UrlIdHelper;
use app\models\AdminProfile;
use app\models\SupportTicket;
use app\models\SupportTicketHistory;
use app\models\SupportTicketReply;
use Yii;
use yii\data\Pagination;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Tableau de traitement des tickets reserve aux administrateurs CAN.
 */
class AdminSupportTicketsController extends Controller
{
    /**
     * Toute action exige une session admin. Les mutations utilisent POST et un
     * controle CSRF local explicite car l'ancienne configuration globale le desactive.
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                /*
                 * MUTATIONS SUPPORT : la gestion, la creation d'une reponse et son
                 * envoi SMTP refusent toutes une invocation GET directe.
                 */
                'actions' => [
                    'update' => ['POST'],
                    'reply' => ['POST'],
                    'retry-reply' => ['POST'],
                    'dispatch-reply' => ['POST'],
                ],
            ],
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

    /**
     * Liste filtrable. Les urgences arrivent avant les priorites normales, puis
     * les tickets les plus anciens afin de limiter l'attente operationnelle.
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $search = trim((string) $request->get('search', ''));
        $status = trim((string) $request->get('status', ''));
        $priority = trim((string) $request->get('priority', ''));
        $category = trim((string) $request->get('category', ''));
        $assignedAdminId = trim((string) $request->get('assigned_admin_id', ''));
        /*
         * TAILLE DE PAGE CONTROLEE : seules trois valeurs sont acceptees afin qu'une
         * URL manipulee ne puisse jamais demander le chargement complet de la table.
         * Le decoupage reste integralement execute par LIMIT/OFFSET dans MySQL.
         */
        $requestedPageSize = (int) $request->get('page_size', 10);
        $allowedPageSizes = [10, 20, 50];
        $pageSize = in_array($requestedPageSize, $allowedPageSizes, true)
            ? $requestedPageSize
            : 10;

        $query = SupportTicket::find()
            ->alias('ticket')
            ->with('assignedAdmin');

        if ($search !== '') {
            $conditions = ['or',
                ['like', 'ticket.name', $search],
                ['like', 'ticket.email', $search],
                ['like', 'ticket.subject', $search],
                ['like', 'ticket.request_reference', $search],
            ];
            if (ctype_digit($search)) {
                $conditions[] = ['ticket.id' => (int) $search];
            }
            /*
             * RECHERCHE PAR REFERENCE PUBLIQUE : SUP-YYYYMMDD-000123 est un libelle
             * calcule et non une colonne. On extrait donc uniquement sa terminaison
             * numerique pour retrouver la cle interne correspondante.
             */
            if (preg_match('/^SUP-\d{8}-(\d{6})$/i', $search, $referenceMatch)) {
                $conditions[] = ['ticket.id' => (int) $referenceMatch[1]];
            }
            $query->andWhere($conditions);
        }

        $query->andFilterWhere(['ticket.status' => array_key_exists($status, SupportTicket::statusOptions()) ? $status : null]);
        $query->andFilterWhere(['ticket.priority' => array_key_exists($priority, SupportTicket::priorityOptions()) ? $priority : null]);
        $query->andFilterWhere(['ticket.category' => array_key_exists($category, SupportTicket::categoryOptions()) ? $category : null]);

        if ($assignedAdminId === 'unassigned') {
            $query->andWhere(['ticket.assigned_admin_id' => null]);
        } elseif (ctype_digit($assignedAdminId) && (int) $assignedAdminId > 0) {
            $query->andWhere(['ticket.assigned_admin_id' => (int) $assignedAdminId]);
        }

        /*
         * ORDONNANCEMENT OPERATIONNEL : l'expression SQL unique evite d'utiliser un
         * objet Expression comme cle de tableau PHP. Les urgences et les tickets actifs
         * restent prioritaires, puis l'anciennete garantit un traitement equitable.
         */
        $query->orderBy(new Expression(
            "CASE WHEN ticket.priority = 'urgent' THEN 0 ELSE 1 END ASC, "
            . "CASE ticket.status WHEN 'new' THEN 0 WHEN 'in_progress' THEN 1 "
            . "WHEN 'waiting_user' THEN 2 WHEN 'resolved' THEN 3 ELSE 4 END ASC, "
            . 'ticket.created_at ASC, ticket.id ASC'
        ));

        $pagination = new Pagination([
            'totalCount' => (clone $query)->count(),
            'pageSize' => $pageSize,
            'pageSizeParam' => false,
        ]);
        $tickets = $query->offset($pagination->offset)->limit($pagination->limit)->all();

        /*
         * COMPTEURS SYNTHETIQUES :
         * ils sont calcules sans les filtres afin de montrer la charge globale du
         * support, meme lorsque l'administrateur inspecte un sous-ensemble.
         */
        $stats = [
            'new' => SupportTicket::find()->where(['status' => 'new'])->count(),
            'urgent' => SupportTicket::find()->where(['priority' => 'urgent'])->andWhere(['not in', 'status', ['resolved', 'closed']])->count(),
            'waiting' => SupportTicket::find()->where(['status' => 'waiting_user'])->count(),
            'open' => SupportTicket::find()->where(['not in', 'status', ['resolved', 'closed']])->count(),
        ];

        return $this->render('index', [
            'tickets' => $tickets,
            'pagination' => $pagination,
            'stats' => $stats,
            'admins' => AdminProfile::find()->orderBy(['first_name' => SORT_ASC, 'last_name' => SORT_ASC, 'username' => SORT_ASC])->all(),
            'filters' => compact('search', 'status', 'priority', 'category', 'assignedAdminId', 'pageSize'),
            'allowedPageSizes' => $allowedPageSizes,
        ]);
    }

    /**
     * Affiche les donnees completes et l'historique avec un identifiant URL signe.
     */
    public function actionView($id)
    {
        $ticket = $this->findTicket($id);

        return $this->render('view', [
            'ticket' => $ticket,
            'history' => $ticket->history,
            'replies' => $ticket->replies,
            'admins' => AdminProfile::find()->orderBy(['first_name' => SORT_ASC, 'last_name' => SORT_ASC, 'username' => SORT_ASC])->all(),
        ]);
    }

    /**
     * Enregistre d'abord la reponse sans contacter SMTP. Le navigateur recoit ensuite
     * un jeton a usage limite pour declencher l'envoi dans une seconde requete : la
     * redirection de la page admin reste ainsi independante de la latence du mailer.
     */
    public function actionReply($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->validateRequestToken();
        $ticket = $this->findTicket($id);
        $message = trim((string) Yii::$app->request->post('message', ''));
        $statusAfterSend = trim((string) Yii::$app->request->post('status_after_send', 'waiting_user'));
        $dispatchToken = Yii::$app->security->generateRandomString(48);

        $reply = new SupportTicketReply([
            'support_ticket_id' => (int) $ticket->id,
            'admin_id' => (int) Yii::$app->session->get('admin_id'),
            'message' => $message,
            'status_after_send' => $statusAfterSend,
            'email_status' => 'pending',
            'dispatch_token_hash' => hash('sha256', $dispatchToken),
        ]);

        if (!$reply->save()) {
            Yii::$app->response->statusCode = 422;
            return [
                'success' => false,
                'message' => 'Please correct the reply before sending.',
                'errors' => $reply->getErrors(),
            ];
        }

        return [
            'success' => true,
            'replyId' => UrlIdHelper::encode((int) $reply->id),
            'dispatchToken' => $dispatchToken,
            'dispatchUrl' => Yii::$app->urlManager->createUrl(['admin-support-tickets/dispatch-reply']),
        ];
    }

    /**
     * Envoie une reponse deja sauvegardee. Le statut et l'affectation du ticket ne
     * changent qu'apres confirmation de l'envoi, puis une entree d'audit est creee
     * dans la meme transaction que la mise a jour du dossier.
     */
    public function actionDispatchReply()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->validateRequestToken();
        $encodedReplyId = (string) Yii::$app->request->post('reply_id', '');
        $token = (string) Yii::$app->request->post('token', '');
        $replyId = UrlIdHelper::decodeOrFail($encodedReplyId, 'Invalid support reply link.');
        $reply = SupportTicketReply::find()->with('ticket')->where(['id' => $replyId])->one();

        if (
            $reply === null
            || $token === ''
            || !hash_equals((string) $reply->dispatch_token_hash, hash('sha256', $token))
        ) {
            throw new BadRequestHttpException('Invalid support reply dispatch request.');
        }
        if ($reply->email_status === 'sent') {
            return ['success' => true, 'message' => 'The reply was already sent.'];
        }

        /*
         * VERROU IDEMPOTENT : une mise a jour conditionnelle revendique l'envoi pour
         * une seule requete. Deux clics ou deux onglets ne peuvent donc pas transmettre
         * deux fois le meme e-mail au demandeur.
         */
        $claimed = SupportTicketReply::updateAll(
            ['email_status' => 'processing', 'email_error' => null],
            ['and', ['id' => (int) $reply->id], ['in', 'email_status', ['pending', 'failed']]]
        );
        if ($claimed !== 1) {
            $reply->refresh();
            if ($reply->email_status === 'sent') {
                return ['success' => true, 'message' => 'The reply was already sent.'];
            }
            Yii::$app->response->statusCode = 409;
            return ['success' => false, 'message' => 'This reply is already being processed.'];
        }
        $reply->email_status = 'processing';
        $reply->email_error = null;
        try {
            $supportEmail = (string) (Yii::$app->params['supportEmail'] ?? Yii::$app->params['adminEmail']);
            $sent = (bool) Yii::$app->mailer
                ->compose('support_reply', ['ticket' => $reply->ticket, 'reply' => $reply])
                ->setTo([$reply->ticket->email => $reply->ticket->name])
                ->setReplyTo($supportEmail)
                ->setSubject('[CAN Support] Reply to ' . $reply->ticket->publicReference)
                ->send();
            if (!$sent) {
                throw new \RuntimeException('Mailer returned false.');
            }
        } catch (\Throwable $exception) {
            /*
             * ECHEC RECUPERABLE : le message et son jeton restent en base afin que le
             * meme envoi puisse etre retente, sans dupliquer le texte du conseiller.
             */
            $reply->updateAttributes([
                'email_status' => 'failed',
                'email_error' => mb_substr($exception->getMessage(), 0, 2000),
            ]);
            Yii::warning(['replyId' => (int) $reply->id, 'exception' => $exception->getMessage()], __METHOD__);
            Yii::$app->response->statusCode = 503;
            return ['success' => false, 'message' => 'The reply was saved, but the email could not be sent. You can retry now.'];
        }

        $ticket = $reply->ticket;
        $oldStatus = (string) $ticket->status;
        $oldAssignedAdminId = $ticket->assigned_admin_id ? (int) $ticket->assigned_admin_id : null;
        $currentAdminId = (int) Yii::$app->session->get('admin_id');
        $newAssignedAdminId = $oldAssignedAdminId ?: $currentAdminId;
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $reply->updateAttributes([
                'email_status' => 'sent',
                'email_error' => null,
                'dispatch_token_hash' => '',
                'sent_at' => date('Y-m-d H:i:s'),
            ]);
            $ticket->updateAttributes([
                'status' => $reply->status_after_send,
                'assigned_admin_id' => $newAssignedAdminId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $history = new SupportTicketHistory([
                'support_ticket_id' => (int) $ticket->id,
                'admin_id' => $currentAdminId,
                'action' => 'reply_sent',
                'old_status' => $oldStatus,
                'new_status' => $reply->status_after_send,
                'old_assigned_admin_id' => $oldAssignedAdminId,
                'new_assigned_admin_id' => $newAssignedAdminId,
                'comment' => 'Reply sent to the requester by email.',
            ]);
            if (!$history->save()) {
                throw new \RuntimeException('The reply history could not be saved.');
            }
            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            /*
             * L'E-MAIL EST DEJA PARTI : on force son etat livre apres le rollback pour
             * empecher une relance qui produirait un doublon. Seule la transition de
             * statut devra alors etre regularisee manuellement par l'administrateur.
             */
            $reply->updateAttributes([
                'email_status' => 'sent',
                'email_error' => 'Email delivered; ticket history finalization failed.',
                'dispatch_token_hash' => '',
                'sent_at' => date('Y-m-d H:i:s'),
            ]);
            Yii::error(['replyId' => (int) $reply->id, 'exception' => $exception->getMessage()], __METHOD__);
            Yii::$app->response->statusCode = 500;
            return ['success' => false, 'message' => 'The email was sent, but the ticket history could not be finalized.'];
        }

        return ['success' => true, 'message' => 'Reply sent successfully.'];
    }

    /**
     * Genere un nouveau jeton pour une reponse non livree. Le texte existant est
     * reutilise tel quel : l'administrateur peut donc reprendre un echec SMTP ancien
     * sans copier, modifier ni dupliquer le message dans la conversation.
     */
    public function actionRetryReply($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->validateRequestToken();
        $replyId = UrlIdHelper::decodeOrFail($id, 'Invalid support reply link.');
        $reply = SupportTicketReply::findOne($replyId);
        if ($reply === null) {
            throw new NotFoundHttpException('Support reply not found.');
        }
        if ($reply->email_status === 'sent') {
            return ['success' => true, 'alreadySent' => true, 'message' => 'The reply was already sent.'];
        }

        $dispatchToken = Yii::$app->security->generateRandomString(48);
        $reply->updateAttributes([
            'email_status' => 'pending',
            'email_error' => null,
            'dispatch_token_hash' => hash('sha256', $dispatchToken),
        ]);
        return [
            'success' => true,
            'replyId' => UrlIdHelper::encode((int) $reply->id),
            'dispatchToken' => $dispatchToken,
            'dispatchUrl' => Yii::$app->urlManager->createUrl(['admin-support-tickets/dispatch-reply']),
        ];
    }

    /**
     * Met a jour statut et assignation dans une transaction, puis inscrit une seule
     * entree d'audit contenant les valeurs avant et apres modification.
     */
    public function actionUpdate($id)
    {
        $this->validateRequestToken();
        $ticket = $this->findTicket($id);
        $oldStatus = (string) $ticket->status;
        $oldAssignedAdminId = $ticket->assigned_admin_id ? (int) $ticket->assigned_admin_id : null;
        $status = trim((string) Yii::$app->request->post('status', ''));
        $assignedValue = trim((string) Yii::$app->request->post('assigned_admin_id', ''));
        $comment = trim((string) Yii::$app->request->post('comment', ''));

        if (!array_key_exists($status, SupportTicket::statusOptions())) {
            throw new BadRequestHttpException('Invalid support ticket status.');
        }

        $assignedAdminId = $assignedValue === '' ? null : (int) $assignedValue;
        if ($assignedAdminId !== null && AdminProfile::findOne($assignedAdminId) === null) {
            throw new BadRequestHttpException('Invalid administrator assignment.');
        }
        if (mb_strlen($comment) > 500) {
            throw new BadRequestHttpException('The update comment cannot exceed 500 characters.');
        }

        if ($oldStatus === $status && $oldAssignedAdminId === $assignedAdminId && $comment === '') {
            Yii::$app->session->setFlash('info', 'No support ticket change was detected.');
            return $this->redirect(['view', 'id' => UrlIdHelper::encode((int) $ticket->id)]);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $ticket->updateAttributes([
                'status' => $status,
                'assigned_admin_id' => $assignedAdminId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $history = new SupportTicketHistory([
                'support_ticket_id' => (int) $ticket->id,
                'admin_id' => (int) Yii::$app->session->get('admin_id'),
                'action' => 'ticket_updated',
                'old_status' => $oldStatus,
                'new_status' => $status,
                'old_assigned_admin_id' => $oldAssignedAdminId,
                'new_assigned_admin_id' => $assignedAdminId,
                'comment' => $comment !== '' ? $comment : null,
            ]);
            if (!$history->save()) {
                throw new \RuntimeException('The support ticket history could not be saved.');
            }

            $transaction->commit();
            Yii::$app->session->setFlash('success', 'Support ticket updated successfully.');
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            Yii::error(['ticketId' => (int) $ticket->id, 'exception' => $exception->getMessage()], __METHOD__);
            Yii::$app->session->setFlash('error', 'The support ticket could not be updated.');
        }

        return $this->redirect(['view', 'id' => UrlIdHelper::encode((int) $ticket->id)]);
    }

    /**
     * Telecharge une piece jointe privee apres le meme controle admin et ID signe.
     */
    public function actionDownload($id)
    {
        $ticket = $this->findTicket($id);
        if (!$ticket->attachment_path || !is_file($ticket->attachment_path)) {
            throw new NotFoundHttpException('Support attachment not found.');
        }

        return Yii::$app->response->sendFile(
            $ticket->attachment_path,
            $ticket->attachment_original_name ?: ('support-ticket-' . $ticket->id)
        );
    }

    /**
     * Decode l'identifiant public avant toute lecture en base.
     */
    private function findTicket($encodedId)
    {
        $id = UrlIdHelper::decodeOrFail($encodedId, 'Invalid support ticket link.');
        $ticket = SupportTicket::find()->with('assignedAdmin')->where(['id' => $id])->one();
        if ($ticket === null) {
            throw new NotFoundHttpException('Support ticket not found.');
        }
        return $ticket;
    }

    /**
     * Controle CSRF explicite applique uniquement a la mutation du ticket.
     */
    private function validateRequestToken()
    {
        $request = Yii::$app->request;
        $clientToken = $request->getBodyParam($request->csrfParam);
        $security = Yii::$app->security;
        $valid = is_string($clientToken)
            && $security->compareString(
                $security->unmaskToken($clientToken),
                $security->unmaskToken($request->getCsrfToken())
            );
        if (!$valid) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }
    }
}
