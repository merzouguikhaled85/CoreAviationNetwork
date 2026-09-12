<?php
/* @var $this yii\web\View */
/* @var $request app\models\Requests */

use yii\helpers\Html;

$requestLink = Yii::$app->urlManager->createAbsoluteUrl([
    'mro-requests/view',
    'id' => $request->request_id
]);

$appName = Yii::$app->name ?: 'Core Aviation Network';

$aoName = $request->aO->username ?? 'AO';
$aircraftRegistration = $request->aircraft_registration ?? 'N/A';
$requestDetails = $request->request_details ?? 'N/A';
$eta = !empty($request->eta) ? Yii::$app->formatter->asDatetime($request->eta) : 'N/A';
$etd = !empty($request->etd) ? Yii::$app->formatter->asDatetime($request->etd) : 'N/A';
?>

<div style="margin:0; padding:0; background:#f3f6fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f6fb; padding:30px 0;">
        <tr>
            <td align="center">

                <table width="620" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 10px 30px rgba(15,23,42,0.08);">

                    <!-- Header -->
                    <tr>
                        <td style="background:#0f172a; padding:28px 35px; text-align:center;">
                            <h1 style="margin:0; color:#ffffff; font-size:24px; font-weight:700;">
                                <?= Html::encode($appName) ?>
                            </h1>
                            <p style="margin:8px 0 0; color:#cbd5e1; font-size:14px;">
                                Update Request Approved
                            </p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding:36px 38px;">

                            <h2 style="margin:0 0 16px; color:#111827; font-size:22px;">
                                Update request approved
                            </h2>

                            <p style="margin:0 0 14px; font-size:15px; line-height:1.7; color:#374151;">
                                Dear <?= Html::encode($aoName) ?>,
                            </p>

                            <p style="margin:0 0 22px; font-size:15px; line-height:1.7; color:#374151;">
                                We are pleased to inform you that the update request for the aircraft
                                <strong><?= Html::encode($aircraftRegistration) ?></strong> has been approved.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#ecfdf5; border:1px solid #bbf7d0; border-radius:12px; margin:24px 0;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <p style="margin:0; font-size:14px; line-height:1.6; color:#166534;">
                                            Your requested update has been successfully approved. You can now view the updated request details.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Details Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; margin:24px 0;">
                                <tr>
                                    <td style="background:#f8fafc; padding:14px 18px; font-size:14px; font-weight:700; color:#111827;">
                                        Updated Request Details
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:0;">
                                        <table width="100%" cellpadding="0" cellspacing="0">

                                            <tr>
                                                <td style="width:38%; padding:13px 18px; border-top:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:700;">
                                                    Aircraft Registration
                                                </td>
                                                <td style="padding:13px 18px; border-top:1px solid #e5e7eb; font-size:14px; color:#111827;">
                                                    <?= Html::encode($aircraftRegistration) ?>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="width:38%; padding:13px 18px; border-top:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:700;">
                                                    Request Details
                                                </td>
                                                <td style="padding:13px 18px; border-top:1px solid #e5e7eb; font-size:14px; color:#111827;">
                                                    <?= Html::encode($requestDetails) ?>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="width:38%; padding:13px 18px; border-top:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:700;">
                                                    ETA
                                                </td>
                                                <td style="padding:13px 18px; border-top:1px solid #e5e7eb; font-size:14px; color:#111827;">
                                                    <?= Html::encode($eta) ?>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="width:38%; padding:13px 18px; border-top:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:700;">
                                                    ETD
                                                </td>
                                                <td style="padding:13px 18px; border-top:1px solid #e5e7eb; font-size:14px; color:#111827;">
                                                    <?= Html::encode($etd) ?>
                                                </td>
                                            </tr>

                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Button -->
                            <div style="text-align:center; margin:30px 0;">
                                <a href="<?= Html::encode($requestLink) ?>"
                                   style="display:inline-block; background:#2563eb; color:#ffffff; text-decoration:none; padding:14px 28px; border-radius:10px; font-size:15px; font-weight:700;">
                                    View Updated Request
                                </a>
                            </div>

                            <p style="margin:0 0 14px; font-size:15px; line-height:1.7; color:#374151;">
                                Thank you for using our services.
                            </p>

                            <p style="margin:24px 0 0; font-size:15px; line-height:1.7; color:#374151;">
                                Best regards,<br>
                                <strong><?= Html::encode($appName) ?> Team</strong>
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
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