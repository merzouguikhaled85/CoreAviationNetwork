<?php

use app\models\SupportTicket;
use yii\helpers\Html;

/** @var SupportTicket $ticket */

$categories = SupportTicket::categoryOptions();
$categoryLabel = $categories[$ticket->category] ?? $ticket->category;
?>
<?php
/*
 * ACCUSE DE RECEPTION SUPPORT :
 * le demandeur recoit la meme reference que l'equipe plateforme ainsi qu'un resume
 * complet. Le contenu utilise seulement du HTML simple compatible Gmail et Outlook.
 */
?>
<div style="margin:0;padding:24px;background:#eef4fb;font-family:Arial,sans-serif;color:#10233f;">
    <div style="max-width:680px;margin:0 auto;overflow:hidden;border:1px solid #d5e2f0;border-radius:16px;background:#ffffff;">
        <div style="padding:22px 26px;background:#081426;color:#ffffff;">
            <div style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#55c5f3;">Core Aviation Network</div>
            <h1 style="margin:8px 0 0;font-size:22px;line-height:1.3;">Support request received</h1>
        </div>

        <div style="padding:24px 26px;">
            <p style="margin:0 0 14px;line-height:1.65;">Dear <?= Html::encode($ticket->name) ?>,</p>
            <p style="margin:0 0 18px;line-height:1.65;">
                We have received your support request. Our platform team will review it and respond as soon as possible.
            </p>

            <div style="margin-bottom:18px;padding:16px;border:1px solid #bcdcf5;border-radius:12px;background:#f0f8ff;text-align:center;">
                <div style="font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#55708f;">Ticket reference</div>
                <div style="margin-top:6px;font-size:22px;font-weight:700;color:#0969b8;"><?= Html::encode($ticket->getPublicReference()) ?></div>
            </div>

            <table role="presentation" style="width:100%;border-collapse:collapse;font-size:14px;">
                <tr><td style="padding:8px 0;color:#64748b;">Ticket ID</td><td style="padding:8px 0;text-align:right;">#<?= (int) $ticket->id ?></td></tr>
                <tr><td style="padding:8px 0;color:#64748b;">Submitted</td><td style="padding:8px 0;text-align:right;"><?= Html::encode($ticket->created_at) ?></td></tr>
                <tr><td style="padding:8px 0;color:#64748b;">Priority</td><td style="padding:8px 0;text-align:right;font-weight:700;"><?= Html::encode(ucfirst($ticket->priority)) ?></td></tr>
                <tr><td style="padding:8px 0;color:#64748b;">Category</td><td style="padding:8px 0;text-align:right;"><?= Html::encode($categoryLabel) ?></td></tr>
                <?php if ($ticket->request_reference): ?>
                    <tr><td style="padding:8px 0;color:#64748b;">Request reference</td><td style="padding:8px 0;text-align:right;"><?= Html::encode($ticket->request_reference) ?></td></tr>
                <?php endif; ?>
            </table>

            <div style="margin-top:18px;padding:16px;border:1px solid #dbe7f3;border-radius:12px;background:#f8fbff;">
                <div style="margin-bottom:6px;font-size:11px;font-weight:700;text-transform:uppercase;color:#64748b;">Subject</div>
                <div style="font-size:15px;font-weight:700;"><?= Html::encode($ticket->subject) ?></div>
            </div>
            <div style="margin-top:12px;padding:16px;border:1px solid #dbe7f3;border-radius:12px;">
                <div style="margin-bottom:6px;font-size:11px;font-weight:700;text-transform:uppercase;color:#64748b;">Your message</div>
                <div style="white-space:pre-wrap;font-size:14px;line-height:1.65;"><?= Html::encode($ticket->message) ?></div>
            </div>

            <?php if ($ticket->attachment_original_name): ?>
                <p style="margin:14px 0 0;font-size:13px;color:#52647d;">
                    Attachment received: <?= Html::encode($ticket->attachment_original_name) ?>
                </p>
            <?php endif; ?>

            <p style="margin:20px 0 0;font-size:12px;line-height:1.55;color:#64748b;">
                Please keep the ticket reference in any future correspondence. This is an automated acknowledgement.
            </p>
        </div>
    </div>
</div>
