<?php

use app\models\SupportTicket;
use yii\helpers\Html;

/** @var SupportTicket $ticket */

$categories = SupportTicket::categoryOptions();
$categoryLabel = $categories[$ticket->category] ?? $ticket->category;
?>
<?php
/*
 * E-MAIL SUPPORT :
 * la mise en page reste volontairement simple pour Gmail, Outlook et les clients
 * mobiles. Toutes les valeurs utilisateur sont encodees avant affichage.
 */
?>
<div style="margin:0;padding:24px;background:#eef4fb;font-family:Arial,sans-serif;color:#10233f;">
    <div style="max-width:680px;margin:0 auto;overflow:hidden;border:1px solid #d5e2f0;border-radius:16px;background:#ffffff;">
        <div style="padding:22px 26px;background:#081426;color:#ffffff;">
            <div style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#55c5f3;">
                Core Aviation Network
            </div>
            <h1 style="margin:8px 0 0;font-size:22px;line-height:1.3;">
                New support ticket <?= Html::encode($ticket->getPublicReference()) ?>
            </h1>
        </div>

        <div style="padding:24px 26px;">
            <table role="presentation" style="width:100%;border-collapse:collapse;font-size:14px;">
                <tr>
                    <td style="padding:8px 0;color:#64748b;">Priority</td>
                    <td style="padding:8px 0;text-align:right;font-weight:700;"><?= Html::encode(ucfirst($ticket->priority)) ?></td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#64748b;">Category</td>
                    <td style="padding:8px 0;text-align:right;"><?= Html::encode($categoryLabel) ?></td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#64748b;">Sender</td>
                    <td style="padding:8px 0;text-align:right;">
                        <?= Html::encode($ticket->name) ?> &lt;<?= Html::encode($ticket->email) ?>&gt;
                    </td>
                </tr>
                <?php if ($ticket->request_reference): ?>
                    <tr>
                        <td style="padding:8px 0;color:#64748b;">Request reference</td>
                        <td style="padding:8px 0;text-align:right;"><?= Html::encode($ticket->request_reference) ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td style="padding:8px 0;color:#64748b;">Account context</td>
                    <td style="padding:8px 0;text-align:right;">
                        <?= Html::encode($ticket->user_type ?: 'Guest') ?>
                        <?= $ticket->user_id ? ' #' . (int) $ticket->user_id : '' ?>
                    </td>
                </tr>
            </table>

            <div style="margin-top:20px;padding:18px;border:1px solid #dbe7f3;border-radius:12px;background:#f8fbff;">
                <div style="margin-bottom:8px;font-size:12px;font-weight:700;text-transform:uppercase;color:#64748b;">Subject</div>
                <div style="font-size:16px;font-weight:700;"><?= Html::encode($ticket->subject) ?></div>
            </div>

            <div style="margin-top:14px;padding:18px;border:1px solid #dbe7f3;border-radius:12px;background:#ffffff;">
                <div style="margin-bottom:8px;font-size:12px;font-weight:700;text-transform:uppercase;color:#64748b;">Message</div>
                <div style="white-space:pre-wrap;font-size:14px;line-height:1.65;"><?= Html::encode($ticket->message) ?></div>
            </div>

            <?php if ($ticket->attachment_original_name): ?>
                <p style="margin:16px 0 0;font-size:13px;color:#52647d;">
                    Attachment: <?= Html::encode($ticket->attachment_original_name) ?>
                    (<?= number_format(((int) $ticket->attachment_size) / 1024, 1) ?> KB)
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>
