<?php

use yii\helpers\Html;

?>

<?php if (!empty($messages)) : ?>

    <ul class="message-ul">
        <?php
        $previousSender = null;
        $previousDay = null;
        ?>

        <?php foreach ($messages as $message) : ?>

            <?php if (!empty($message->message)) : ?>

                <?php
                // Get sender username safely
                $senderUsername = $message->sender->username ?? 'Unknown';

                // Check if the current message belongs to the connected user
                $isMe = $senderUsername === Yii::$app->session->get('username');

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
                    <li class="day-divider">
                        <span><?= Html::encode($messageDay) ?></span>
                    </li>
                <?php endif; ?>

                <li class="msg <?= $isMe ? 'me' : '' ?> <?= $isGrouped ? 'grouped' : '' ?>">
                    <div class="bubble">

                        <!-- Show sender name only on first received message of a group -->
                        <?php if (!$isMe && !$isGrouped): ?>
                            <span class="sender-name">
                                <?= Html::encode($senderUsername) ?>
                            </span>
                        <?php endif; ?>

                        <!-- Message body -->
                        <div class="msg-text">
                            <?= nl2br(Html::encode($message->message)) ?>
                        </div>

                        <!-- Message time and status -->
                        <div class="meta">
                            <span><?= Html::encode($messageTime) ?></span>

                            <?php if ($isMe): ?>
                                <i class="bi bi-check2-all"></i>
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