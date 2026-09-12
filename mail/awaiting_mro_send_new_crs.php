<?php
/* @var $this yii\web\View */
/* @var $mro app\models\MroProfile */

use yii\helpers\Html;

$appName = Yii::$app->name ?: 'Core Aviation Network';

$mroName = $mro->username ?? 'MRO';

$dashboardLink = Yii::$app->urlManager->createAbsoluteUrl([
    'site/index',
]);
?>

<div style="margin:0; padding:0; background:#f3f6fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f6fb; padding:30px 0;">
        <tr>
            <td align="center">

                <table width="620" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 10px 30px rgba(15,23,42,0.08);">

                    <tr>
                        <td style="background:#0f172a; padding:28px 35px; text-align:center;">
                            <h1 style="margin:0; color:#ffffff; font-size:24px; font-weight:700;">
                                <?= Html::encode($appName) ?>
                            </h1>
                            <p style="margin:8px 0 0; color:#cbd5e1; font-size:14px;">
                                CRS Submission Required
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:36px 38px;">

                            <h2 style="margin:0 0 16px; color:#111827; font-size:22px;">
                                Awaiting your new CRS submission
                            </h2>

                            <p style="margin:0 0 14px; font-size:15px; line-height:1.7; color:#374151;">
                                Hello <?= Html::encode($mroName) ?>,
                            </p>

                            <p style="margin:0 0 22px; font-size:15px; line-height:1.7; color:#374151;">
                                This is a notification that we are awaiting your new CRS submission.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#fff7ed; border:1px solid #fed7aa; border-radius:12px; margin:24px 0;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <p style="margin:0; font-size:14px; line-height:1.6; color:#9a3412;">
                                            Please log in to your account and provide the required CRS information as soon as possible.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <div style="text-align:center; margin:30px 0;">
                                <a href="<?= Html::encode($dashboardLink) ?>"
                                   style="display:inline-block; background:#2563eb; color:#ffffff; text-decoration:none; padding:14px 28px; border-radius:10px; font-size:15px; font-weight:700;">
                                    Go to Dashboard
                                </a>
                            </div>

                            <p style="margin:0 0 14px; font-size:15px; line-height:1.7; color:#374151;">
                                Thank you for your prompt attention to this matter.
                            </p>

                            <p style="margin:24px 0 0; font-size:15px; line-height:1.7; color:#374151;">
                                Best regards,<br>
                                <strong><?= Html::encode($appName) ?> Team</strong>
                            </p>

                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f8fafc; padding:20px 35px; text-align:center; border-top:1px solid #e5e7eb;">
                            <p style="margin:0; font-size:12px; color:#94a3b8;">
                                This is an automatic message from <?= Html::encode($appName) ?>. Please do not reply.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</div>