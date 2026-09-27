<?php

use app\models\PrelaunchSubscriber;
use yii\helpers\Html;

/** @var PrelaunchSubscriber $subscriber */
/** @var string $confirmationUrl */
/** @var string $unsubscribeUrl */
?>
<div style="margin:0;padding:24px;background:#eef4fb;font-family:Arial,sans-serif;color:#10233f;">
    <div style="max-width:680px;margin:0 auto;overflow:hidden;border:1px solid #d5e2f0;border-radius:16px;background:#ffffff;">
        <div style="padding:22px 26px;background:#081426;color:#ffffff;">
            <div style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#55c5f3;">Core Aviation Network</div>
            <h1 style="margin:8px 0 0;font-size:22px;line-height:1.3;">Confirm your early access registration</h1>
        </div>

        <div style="padding:24px 26px;">
            <p style="margin:0 0 14px;line-height:1.65;">Dear <?= Html::encode($subscriber->first_name) ?>,</p>
            <p style="margin:0 0 18px;line-height:1.65;">
                Thank you for joining the Core Aviation Network early access list. Confirm your business email address to receive the official launch notification.
            </p>

            <div style="margin:22px 0;text-align:center;">
                <a href="<?= Html::encode($confirmationUrl) ?>" style="display:inline-block;padding:13px 22px;border-radius:9px;background:#0b75c9;color:#ffffff;font-weight:700;text-decoration:none;">
                    Confirm my registration
                </a>
            </div>

            <p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#52647d;">
                This confirmation link expires after 24 hours. If the button does not work, copy this address into your browser:
            </p>
            <p style="margin:0 0 18px;font-size:12px;line-height:1.6;word-break:break-all;">
                <?= Html::a(Html::encode($confirmationUrl), $confirmationUrl) ?>
            </p>

            <div style="padding:14px 16px;border:1px solid #dbe7f3;border-radius:12px;background:#f8fbff;font-size:13px;line-height:1.6;color:#52647d;">
                If you did not request this registration, you can ignore this message or
                <?= Html::a('remove this address from the list', $unsubscribeUrl, ['style' => 'color:#0b75c9;']) ?>.
            </div>
        </div>
    </div>
</div>
