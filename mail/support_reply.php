<?php

use app\models\SupportTicket;
use app\models\SupportTicketReply;
use yii\helpers\Html;

/** @var SupportTicket $ticket */
/** @var SupportTicketReply $reply */

/*
 * REPONSE SUPPORT OFFICIELLE : ce gabarit reprend la carte visuelle des e-mails de
 * verification et d'accuse de reception. Le HTML reste volontairement simple et en
 * styles integres pour garantir sa lecture dans Gmail, Outlook et les clients mobiles.
 */
?>
<div style="margin:0;padding:24px;background:#eef4fb;font-family:Arial,sans-serif;color:#10233f;">
    <div style="max-width:680px;margin:0 auto;overflow:hidden;border:1px solid #d5e2f0;border-radius:16px;background:#ffffff;">
        <!-- EN-TETE DE MARQUE : permet d'identifier immediatement l'origine du message. -->
        <div style="padding:22px 26px;background:#081426;color:#ffffff;">
            <div style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#55c5f3;">Core Aviation Network</div>
            <h1 style="margin:8px 0 0;font-size:22px;line-height:1.3;">Support team reply</h1>
        </div>

        <div style="padding:24px 26px;">
            <p style="margin:0 0 14px;line-height:1.65;">Dear <?= Html::encode($ticket->name) ?>,</p>
            <p style="margin:0 0 18px;line-height:1.65;">Our support team has replied to your request.</p>

            <!-- REFERENCE : elle relie la reponse au ticket initial sans exposer l'ID URL interne. -->
            <div style="margin-bottom:18px;padding:16px;border:1px solid #bcdcf5;border-radius:12px;background:#f0f8ff;text-align:center;">
                <div style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#55708f;">Ticket reference</div>
                <div style="margin-top:6px;font-size:22px;font-weight:700;color:#0969b8;"><?= Html::encode($ticket->publicReference) ?></div>
            </div>

            <!-- CONTENU : white-space conserve les paragraphes rediges par l'administrateur. -->
            <div style="padding:18px;border:1px solid #dbe7f3;border-radius:12px;background:#fbfdff;">
                <div style="margin-bottom:8px;font-size:11px;font-weight:700;text-transform:uppercase;color:#64748b;">Support response</div>
                <div style="white-space:pre-wrap;font-size:14px;line-height:1.7;"><?= Html::encode($reply->message) ?></div>
            </div>

            <div style="margin-top:16px;padding:14px;border-left:4px solid #0b9fd3;background:#f2f9fd;font-size:13px;line-height:1.6;color:#52647d;">
                To continue the conversation, reply directly to this email and keep
                <strong><?= Html::encode($ticket->publicReference) ?></strong> in the subject.
            </div>
            <p style="margin:20px 0 0;line-height:1.65;">Best regards,<br><strong>Core Aviation Network Support</strong></p>
        </div>
    </div>
</div>
