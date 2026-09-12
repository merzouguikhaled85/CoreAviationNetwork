<?php

namespace app\controllers;

use app\models\SupportTicket;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * Recoit les demandes de la bulle Support sans melanger ce flux avec les
 * requests de maintenance, les disputes ou les conversations AO/MRO.
 */
class SupportController extends Controller
{
    /**
     * Les deux points d'entree modifient des donnees et refusent donc toute
     * invocation GET, y compris si une URL est ouverte manuellement.
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['POST'],
                    'dispatch' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Enregistre d'abord le ticket, puis remet au navigateur un jeton court pour
     * declencher l'e-mail dans une seconde requete non bloquante pour l'interface.
     */
    public function actionCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->validateRequestToken();

        if (!$this->consumeRateLimit()) {
            Yii::$app->response->statusCode = 429;
            return [
                'success' => false,
                'message' => 'Too many support requests. Please try again in a few minutes.',
            ];
        }

        $ticket = new SupportTicket();
        $ticket->load(Yii::$app->request->post());

        /*
         * PIEGE ANTI-ROBOT :
         * une soumission qui remplit ce champ invisible recoit une reponse neutre
         * sans creer de ticket ni reveler le mecanisme de protection.
         */
        if (trim((string) $ticket->website) !== '') {
            return [
                'success' => true,
                'reference' => 'SUP-RECEIVED',
                'message' => 'Your support request has been received.',
            ];
        }

        /*
         * CAPTCHA RESERVE AUX VISITEURS :
         * les comptes authentifies ont deja une identite Yii et ne subissent pas
         * de friction supplementaire. Un visiteur doit en revanche obtenir un
         * jeton Turnstile puis le faire confirmer par Cloudflare cote serveur.
         */
        $challenge = $this->validateGuestChallenge();
        if (!$challenge['valid']) {
            Yii::$app->response->statusCode = $challenge['status'];
            return [
                'success' => false,
                'message' => $challenge['message'],
                'errors' => [
                    'turnstile' => [$challenge['message']],
                ],
            ];
        }

        $ticket->attachment = UploadedFile::getInstance($ticket, 'attachment');
        $dispatchToken = Yii::$app->security->generateRandomString(48);
        $ticket->email_dispatch_token_hash = hash('sha256', $dispatchToken);
        $ticket->status = 'new';
        $ticket->email_status = 'pending';
        $ticket->user_type = $this->resolveUserType();
        $ticket->user_id = Yii::$app->user->isGuest ? null : (int) Yii::$app->user->id;
        $ticket->ip_hash = $this->hashClientAddress();
        $ticket->user_agent = mb_substr((string) Yii::$app->request->userAgent, 0, 255);

        if (!$ticket->validate()) {
            Yii::$app->response->statusCode = 422;
            return [
                'success' => false,
                'message' => 'Please correct the highlighted fields.',
                'errors' => $ticket->getErrors(),
            ];
        }

        $transaction = Yii::$app->db->beginTransaction();
        $savedAttachmentPath = null;

        try {
            if (!$ticket->save(false)) {
                throw new \RuntimeException('The support ticket could not be saved.');
            }

            /*
             * STOCKAGE PRIVE :
             * la piece jointe est placee sous runtime, hors du dossier web. Son nom
             * aleatoire empeche toute execution directe ou collision entre tickets.
             */
            if ($ticket->attachment !== null) {
                $directory = Yii::getAlias('@runtime/support-attachments');
                FileHelper::createDirectory($directory, 0775, true);
                $extension = strtolower((string) $ticket->attachment->extension);
                $savedAttachmentPath = $directory . DIRECTORY_SEPARATOR
                    . Yii::$app->security->generateRandomString(32) . '.' . $extension;

                if (!$ticket->attachment->saveAs($savedAttachmentPath, false)) {
                    throw new \RuntimeException('The support attachment could not be saved.');
                }

                $ticket->attachment_path = $savedAttachmentPath;
                $ticket->attachment_original_name = mb_substr(
                    (string) $ticket->attachment->name,
                    0,
                    255
                );
                /*
                 * TYPE MIME REEL :
                 * on relit le fichier enregistre au lieu de faire confiance au type
                 * annonce par le navigateur, qui peut etre falsifie par le client.
                 */
                $ticket->attachment_mime_type = (string) (
                    FileHelper::getMimeType($savedAttachmentPath)
                    ?: $ticket->attachment->type
                );
                $ticket->attachment_size = (int) $ticket->attachment->size;
                $ticket->save(false, [
                    'attachment_path',
                    'attachment_original_name',
                    'attachment_mime_type',
                    'attachment_size',
                ]);
            }

            $transaction->commit();
            $ticket->refresh();
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            if ($savedAttachmentPath && is_file($savedAttachmentPath)) {
                @unlink($savedAttachmentPath);
            }
            Yii::error([
                'message' => 'Support ticket persistence failed.',
                'exception' => $exception->getMessage(),
            ], __METHOD__);
            Yii::$app->response->statusCode = 500;

            return [
                'success' => false,
                'message' => 'The support service is temporarily unavailable. Please try again.',
            ];
        }

        return [
            'success' => true,
            'id' => (int) $ticket->id,
            'reference' => $ticket->getPublicReference(),
            'message' => 'Your support request has been recorded.',
            'dispatchUrl' => Yii::$app->urlManager->createUrl(['/support/dispatch']),
            'dispatchToken' => $dispatchToken,
        ];
    }

    /**
     * Tente l'envoi apres la confirmation visuelle du ticket. Le jeton lie au
     * ticket empeche un tiers de declencher des envois arbitraires par identifiant.
     */
    public function actionDispatch()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->validateRequestToken();

        $id = (int) Yii::$app->request->post('id');
        $token = (string) Yii::$app->request->post('token');
        $ticket = $id > 0 ? SupportTicket::findOne($id) : null;

        if (
            $ticket === null
            || $token === ''
            || !hash_equals((string) $ticket->email_dispatch_token_hash, hash('sha256', $token))
        ) {
            throw new BadRequestHttpException('Invalid support dispatch request.');
        }

        if ($ticket->email_status === 'sent' && $ticket->acknowledgement_status === 'sent') {
            return [
                'success' => true,
                'emailStatus' => 'sent',
                'acknowledgementStatus' => 'sent',
            ];
        }

        /*
         * DEUX ENVOIS INDEPENDANTS :
         * l'alerte interne et l'accuse utilisateur sont traites separement. Si un
         * seul echoue, un nouvel appel ne renverra que le message encore manquant.
         */
        $emailStatus = $ticket->email_status === 'sent'
            ? 'sent'
            : $this->sendTicketToPlatform($ticket);
        $acknowledgementStatus = $ticket->acknowledgement_status === 'sent'
            ? 'sent'
            : $this->sendAcknowledgementToRequester($ticket);

        $ticket->updateAttributes([
            'email_dispatch_token_hash' => (
                $emailStatus === 'sent' && $acknowledgementStatus === 'sent'
            ) ? '' : $ticket->email_dispatch_token_hash,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'success' => true,
            'emailStatus' => $emailStatus,
            'acknowledgementStatus' => $acknowledgementStatus,
        ];
    }

    /**
     * Envoie a l'equipe plateforme toutes les informations et la piece jointe.
     */
    private function sendTicketToPlatform(SupportTicket $ticket)
    {
        $ticket->updateAttributes([
            'email_status' => 'processing',
            'email_attempted_at' => date('Y-m-d H:i:s'),
            'email_error' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        try {
            $supportEmail = $this->getSupportEmail();
            $mail = Yii::$app->mailer
                ->compose('support_ticket', ['ticket' => $ticket])
                ->setTo($supportEmail)
                ->setReplyTo([$ticket->email => $ticket->name])
                ->setSubject(
                    '[CAN Support][' . strtoupper($ticket->priority) . '] '
                    . $ticket->getPublicReference() . ' - ' . $ticket->subject
                );

            if ($ticket->attachment_path && is_file($ticket->attachment_path)) {
                $mail->attach($ticket->attachment_path, [
                    'fileName' => $ticket->attachment_original_name,
                    'contentType' => $ticket->attachment_mime_type,
                ]);
            }

            $sent = (bool) $mail->send();
            $ticket->updateAttributes([
                'email_status' => $sent ? 'sent' : 'failed',
                'email_error' => $sent ? null : 'Mailer returned false.',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return $sent ? 'sent' : 'failed';
        } catch (\Throwable $exception) {
            /*
             * RESILIENCE DU MAIL INTERNE :
             * l'erreur est conservee sans annuler le ticket ni empecher l'accuse de
             * reception d'etre tente dans l'etape suivante.
             */
            $ticket->updateAttributes([
                'email_status' => 'failed',
                'email_error' => mb_substr($exception->getMessage(), 0, 2000),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            Yii::warning([
                'ticketId' => (int) $ticket->id,
                'message' => 'Support e-mail dispatch failed.',
                'exception' => $exception->getMessage(),
            ], __METHOD__);
            return 'failed';
        }
    }

    /**
     * Confirme au demandeur que le ticket est conserve et rappelle sa reference.
     */
    private function sendAcknowledgementToRequester(SupportTicket $ticket)
    {
        $ticket->updateAttributes([
            'acknowledgement_status' => 'processing',
            'acknowledgement_attempted_at' => date('Y-m-d H:i:s'),
            'acknowledgement_error' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        try {
            $sent = (bool) Yii::$app->mailer
                ->compose('support_acknowledgement', ['ticket' => $ticket])
                ->setTo([$ticket->email => $ticket->name])
                ->setReplyTo($this->getSupportEmail())
                ->setSubject(
                    '[CAN Support] We received ' . $ticket->getPublicReference()
                )
                ->send();

            $ticket->updateAttributes([
                'acknowledgement_status' => $sent ? 'sent' : 'failed',
                'acknowledgement_error' => $sent ? null : 'Mailer returned false.',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return $sent ? 'sent' : 'failed';
        } catch (\Throwable $exception) {
            $ticket->updateAttributes([
                'acknowledgement_status' => 'failed',
                'acknowledgement_error' => mb_substr($exception->getMessage(), 0, 2000),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            Yii::warning([
                'ticketId' => (int) $ticket->id,
                'message' => 'Support acknowledgement e-mail failed.',
                'exception' => $exception->getMessage(),
            ], __METHOD__);
            return 'failed';
        }
    }

    /**
     * Centralise l'adresse de reponse utilisee dans les deux messages Support.
     */
    private function getSupportEmail()
    {
        return (string) (
            Yii::$app->params['supportEmail']
            ?? Yii::$app->params['adminEmail']
        );
    }

    /**
     * Force la verification CSRF localement car la configuration historique de
     * l'application la desactive encore globalement. Le widget envoie toujours
     * le jeton genere par Yii dans son FormData.
     */
    private function validateRequestToken()
    {
        /*
         * COMPARAISON EXPLICITE :
         * Request::validateCsrfToken() court-circuite le controle lorsque
         * enableCsrfValidation vaut false. On reproduit donc ici la comparaison
         * interne de Yii avec ses jetons demasques, uniquement pour le Support.
         */
        $request = Yii::$app->request;
        $clientToken = $request->getBodyParam($request->csrfParam);
        $serverToken = $request->getCsrfToken();
        $security = Yii::$app->security;
        $valid = is_string($clientToken)
            && $security->compareString(
                $security->unmaskToken($clientToken),
                $security->unmaskToken($serverToken)
            );

        if (!$valid) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }
    }

    /**
     * Valide le jeton Turnstile d'un visiteur directement aupres de Siteverify.
     * Le secret ne quitte jamais PHP et ni le jeton ni le secret ne sont journalises.
     */
    private function validateGuestChallenge()
    {
        if (!Yii::$app->user->isGuest) {
            return ['valid' => true, 'status' => 200, 'message' => ''];
        }

        $secret = trim((string) (Yii::$app->params['turnstileSecretKey'] ?? ''));
        $siteKey = trim((string) (Yii::$app->params['turnstileSiteKey'] ?? ''));
        $expectedHostname = strtolower(rtrim(trim((string) (
            Yii::$app->params['turnstileExpectedHostname'] ?? ''
        )), '.'));
        if ($secret === '' || $siteKey === '' || $expectedHostname === '') {
            Yii::error('Turnstile is not configured for guest support requests.', __METHOD__);
            return [
                'valid' => false,
                'status' => 503,
                'message' => 'Visitor verification is temporarily unavailable. Please try again later.',
            ];
        }

        $token = trim((string) Yii::$app->request->post('cf-turnstile-response', ''));
        if ($token === '' || strlen($token) > 2048) {
            return [
                'valid' => false,
                'status' => 422,
                'message' => 'Please complete the visitor verification.',
            ];
        }

        /*
         * APPEL BORNE DANS LE TEMPS :
         * les delais courts evitent qu'une panne externe reproduise les blocages SMTP
         * deja observes. En cas d'indisponibilite, aucun ticket non verifie n'est cree.
         */
        $handle = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        $curlOptions = [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_POSTFIELDS => http_build_query([
                'secret' => $secret,
                'response' => $token,
                'remoteip' => (string) Yii::$app->request->userIP,
            ]),
        ];

        /*
         * CERTIFICATS WINDOWS :
         * cette installation PHP/OpenSSL ne declare aucun fichier cacert.pem.
         * cURL 8 utilise donc le magasin racine natif de Windows, sans desactiver
         * CURLOPT_SSL_VERIFYPEER et sans accepter de certificat non approuve.
         */
        if (defined('CURLSSLOPT_NATIVE_CA')) {
            $curlOptions[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
        }
        curl_setopt_array($handle, $curlOptions);

        $rawResponse = curl_exec($handle);
        $httpCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $transportError = curl_error($handle);
        curl_close($handle);

        if ($rawResponse === false || $transportError !== '' || $httpCode !== 200) {
            Yii::warning([
                'message' => 'Turnstile Siteverify transport failed.',
                'httpCode' => $httpCode,
                'transportError' => $transportError,
            ], __METHOD__);
            return [
                'valid' => false,
                'status' => 503,
                'message' => 'Visitor verification is temporarily unavailable. Please try again.',
            ];
        }

        $result = json_decode((string) $rawResponse, true);
        $verifiedAction = is_array($result) ? ($result['action'] ?? 'support_ticket') : null;
        $verifiedHostname = is_array($result)
            ? strtolower(rtrim(trim((string) ($result['hostname'] ?? '')), '.'))
            : '';

        /*
         * ACTION ATTENDUE :
         * la production accepte exclusivement support_ticket. Cloudflare renvoie
         * toutefois test avec ses cles factices officielles ; cette valeur n'est
         * donc toleree que lorsque Yii fonctionne explicitement en developpement.
         */
        $actionIsValid = $verifiedAction === 'support_ticket'
            || (YII_ENV_DEV && $verifiedAction === 'test');

        /*
         * HOSTNAME ATTENDU : même avec une validation Siteverify réussie, le
         * jeton doit provenir du domaine prévu pour empêcher l'usage du widget
         * depuis un autre site. La comparaison reste insensible à la casse.
         */
        $hostnameIsValid = $verifiedHostname !== ''
            && hash_equals($expectedHostname, $verifiedHostname);
        $valid = is_array($result)
            && !empty($result['success'])
            && $actionIsValid
            && $hostnameIsValid;

        if (!$valid) {
            Yii::warning([
                'message' => 'Turnstile rejected a guest support request.',
                'errorCodes' => is_array($result) ? ($result['error-codes'] ?? []) : ['invalid-json'],
                'hostnameMatches' => $hostnameIsValid,
            ], __METHOD__);
            return [
                'valid' => false,
                'status' => 422,
                'message' => 'Visitor verification failed or expired. Please try again.',
            ];
        }

        return ['valid' => true, 'status' => 200, 'message' => ''];
    }

    /**
     * Limite a cinq creations sur dix minutes par utilisateur ou adresse hachee.
     */
    private function consumeRateLimit()
    {
        $identity = Yii::$app->user->isGuest
            ? $this->hashClientAddress()
            : 'user-' . (int) Yii::$app->user->id;
        $key = 'support-rate-' . $identity;
        $now = time();
        $window = Yii::$app->cache->get($key);

        if (!is_array($window) || ($now - (int) ($window['started'] ?? 0)) >= 600) {
            Yii::$app->cache->set($key, ['started' => $now, 'count' => 1], 600);
            return true;
        }

        if ((int) ($window['count'] ?? 0) >= 5) {
            return false;
        }

        $window['count'] = (int) ($window['count'] ?? 0) + 1;
        Yii::$app->cache->set($key, $window, 600);
        return true;
    }

    /**
     * Retourne uniquement un role connu ; les autres valeurs de session ne sont
     * jamais persistees telles quelles dans le ticket.
     */
    private function resolveUserType()
    {
        if (Yii::$app->user->isGuest) {
            return null;
        }

        $type = (string) Yii::$app->session->get('user_type');
        return in_array($type, ['ao', 'mro', 'admin'], true) ? $type : null;
    }

    /**
     * Hache l'adresse avec le secret applicatif pour permettre l'anti-spam sans
     * stocker l'IP brute du visiteur dans la table Support.
     */
    private function hashClientAddress()
    {
        $secret = (string) (
            Yii::$app->params['urlIdSecret']
            ?? Yii::$app->request->cookieValidationKey
        );
        return hash_hmac('sha256', (string) Yii::$app->request->userIP, $secret);
    }
}
