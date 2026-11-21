<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\UrlIdHelper;

/*
 * UTILISATEUR CONNECTÉ (correctif 2026-11) : identifié par son rôle et son
 * identifiant de session, comme sender_type / sender_id des messages.
 * Calculé une seule fois (le fragment est aussi rendu par l'AJAX fetch-messages).
 */
$currentUserType = (string) Yii::$app->session->get('user_type');
$currentUserId = in_array($currentUserType, ['ao', 'mro'], true)
    ? (int) Yii::$app->session->get($currentUserType . '_id')
    : 0;
$currentUsername = (string) Yii::$app->session->get('username');
?>

<?php if (!empty($messages)) : ?>

    <ul class="message-ul">
        <?php
        $previousSender = null;
        $previousDay = null;
        ?>

        <?php foreach ($messages as $message) : ?>

            <?php
            /*
             * PIÈCES JOINTES (2026-11) : un message peut ne contenir qu'un fichier.
             * Les lignes vides historiques (création de conversation sans texte)
             * restent masquées comme avant.
             */
            ?>
            <?php if (!empty($message->message) || $message->hasAttachment()) : ?>

                <?php
                // Get sender username safely
                $senderUsername = $message->sender->username ?? 'Unknown';

                /*
                 * CORRECTIF « À MOI / REÇU » : avant, on comparait uniquement le nom
                 * d'utilisateur, alors qu'un AO et un MRO peuvent porter le même nom
                 * (tables distinctes) et que la clé de session peut être absente.
                 * On compare désormais le rôle ET l'identifiant de l'expéditeur.
                 * Repli sur l'ancienne comparaison pour un message sans sender_type.
                 */
                if ((string) $message->sender_type !== '' && $currentUserId > 0) {
                    $isMe = (string) $message->sender_type === $currentUserType
                        && (int) $message->sender_id === $currentUserId;
                } else {
                    $isMe = $currentUsername !== '' && $senderUsername === $currentUsername;
                }

                // Format day and time
                $messageDay = !empty($message->timestamp)
                    ? Yii::$app->formatter->asDate($message->timestamp, 'php:d M Y')
                    : '';

                $messageTime = !empty($message->timestamp)
                    ? Yii::$app->formatter->asDatetime($message->timestamp, 'php:H:i')
                    : '';

                // Group consecutive messages from the same sender on the same day
                $isGrouped = ($previousSender === $senderUsername && $previousDay === $messageDay);

                // Show date divider when day changes
                $showDayDivider = ($previousDay !== $messageDay);
                ?>

                <?php if ($showDayDivider): ?>
                    <li class="day-divider" role="separator" aria-label="<?= Html::encode('Messages of ' . $messageDay) ?>">
                        <span aria-hidden="true"><?= Html::encode($messageDay) ?></span>
                    </li>
                <?php endif; ?>

                <?php
                /*
                 * ACCESSIBILITÉ (2026-11) : data-message-key / data-incoming / data-sender
                 * permettent au script de la page d'annoncer uniquement les NOUVEAUX
                 * messages reçus (zone aria-live), sans relire toute la conversation.
                 */
                ?>
                <li class="msg <?= $isMe ? 'me' : '' ?> <?= $isGrouped ? 'grouped' : '' ?>"
                    data-message-key="<?= Html::encode((string) $message->getPrimaryKey()) ?>"
                    data-incoming="<?= $isMe ? '0' : '1' ?>"
                    data-sender="<?= Html::encode($senderUsername) ?>">
                    <div class="bubble">
                        <!-- Lecteurs d'écran : qui parle, même quand le nom n'est pas affiché. -->
                        <span class="visually-hidden"><?= Html::encode($isMe ? 'You wrote:' : $senderUsername . ' wrote:') ?></span>

                        <!-- Show sender name only on first received message of a group -->
                        <?php if (!$isMe && !$isGrouped): ?>
                            <span class="sender-name">
                                <?= Html::encode($senderUsername) ?>
                            </span>
                        <?php endif; ?>

                        <!-- Message body -->
                        <?php if (trim((string) $message->message) !== ''): ?>
                            <div class="msg-text">
                                <?= nl2br(Html::encode($message->message)) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($message->hasAttachment()): ?>
                            <?php
                            // Lien signé, servi par ConversationsController::actionAttachment (accès contrôlé).
                            $attachmentToken = UrlIdHelper::encode((int) $message->getPrimaryKey());
                            $attachmentName = (string) ($message->attachment_name ?: 'attachment');
                            $attachmentViewUrl = Url::to(['conversations/attachment', 'id' => $attachmentToken, 'inline' => 1]);
                            $attachmentDownloadUrl = Url::to(['conversations/attachment', 'id' => $attachmentToken]);
                            ?>
                            <?php if ($message->isImageAttachment()): ?>
                                <a class="msg-attachment-image"
                                   href="<?= Html::encode($attachmentViewUrl) ?>"
                                   target="_blank" rel="noopener" data-pjax="0" data-no-loader="1"
                                   title="<?= Html::encode('Open ' . $attachmentName) ?>">
                                    <img src="<?= Html::encode($attachmentViewUrl) ?>"
                                         alt="<?= Html::encode('Image attachment: ' . $attachmentName) ?>"
                                         loading="lazy">
                                </a>
                            <?php endif; ?>
                            <a class="msg-attachment"
                               href="<?= Html::encode($attachmentDownloadUrl) ?>"
                               data-pjax="0" data-no-loader="1"
                               aria-label="<?= Html::encode('Download ' . $attachmentName . ', ' . $message->getAttachmentSizeLabel()) ?>">
                                <span class="msg-attachment-icon" aria-hidden="true">
                                    <i class="bi <?= $message->isImageAttachment() ? 'bi-file-earmark-image' : 'bi-file-earmark-pdf' ?>"></i>
                                </span>
                                <span class="msg-attachment-copy">
                                    <span class="msg-attachment-name"><?= Html::encode($attachmentName) ?></span>
                                    <span class="msg-attachment-size"><?= Html::encode($message->getAttachmentSizeLabel()) ?></span>
                                </span>
                                <i class="bi bi-download" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>

                        <!-- Message time and status -->
                        <div class="meta">
                            <?php if (!empty($message->timestamp) && strtotime((string) $message->timestamp)): ?>
                                <time datetime="<?= Html::encode(date('c', strtotime((string) $message->timestamp))) ?>"><?= Html::encode($messageTime) ?></time>
                            <?php else: ?>
                                <span><?= Html::encode($messageTime) ?></span>
                            <?php endif; ?>

                            <?php if ($isMe): ?>
                                <?php /* CORRECTIF : une seule coche = « envoyé ». Le double ✓✓ laissait croire
                                         à un accusé de lecture, qui n'est pas enregistré par la plateforme. */ ?>
                                <i class="bi bi-check2" title="Sent" aria-label="Sent"></i>
                            <?php endif; ?>
                        </div>

                    </div>
                </li>

                <?php
                $previousSender = $senderUsername;
                $previousDay = $messageDay;
                ?>

            <?php endif; ?>

        <?php endforeach; ?>
    </ul>

<?php else : ?>

    <div class="empty-chat">
        <div class="empty-chat-box">
            <div class="empty-chat-icon">
                <i class="bi bi-chat-dots"></i>
            </div>

            <div class="empty-chat-title">
                No messages yet
            </div>

            <div class="empty-chat-text">
                Start the conversation by sending the first message.
            </div>
        </div>
    </div>

<?php endif; ?>