<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $appointment app\models\Appointment */

$appName = Yii::$app->name ?: 'Core Aviation Network';

$requestId = $appointment->request_id ?? 'N/A';
$appointmentDate = !empty($appointment->appointment_date)
    ? Yii::$app->formatter->asDatetime($appointment->appointment_date)
    : 'N/A';
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
                                Appointment Confirmed
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:36px 38px;">

                            <h2 style="margin:0 0 16px; color:#111827; font-size:22px;">
                                Your appointment has been confirmed
                            </h2>

                            <p style="margin:0 0 14px; font-size:15px; line-height:1.7; color:#374151;">
                                Hello,
                            </p>

                            <p style="margin:0 0 22px; font-size:15px; line-height:1.7; color:#374151;">
                                We are pleased to inform you that your appointment has been successfully confirmed.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#ecfdf5; border:1px solid #bbf7d0; border-radius:12px; margin:24px 0;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <p style="margin:0; font-size:14px; line-height:1.6; color:#166534;">
                                            Your appointment is now confirmed. Please make sure to be available at the scheduled date and time.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; margin:24px 0;">
                                <tr>
                                    <td style="background:#f8fafc; padding:14px 18px; font-size:14px; font-weight:700; color:#111827;">
                                        Appointment Details
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:0;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width:38%; padding:13px 18px; border-top:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:700;">
                                                    Request ID
                                                </td>
                                                <td style="padding:13px 18px; border-top:1px solid #e5e7eb; font-size:14px; color:#111827;">
                                                    #<?= Html::encode($requestId) ?>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="width:38%; padding:13px 18px; border-top:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:700;">
                                                    Appointment Date
                                                </td>
                                                <td style="padding:13px 18px; border-top:1px solid #e5e7eb; font-size:14px; color:#111827;">
                                                    <?= Html::encode($appointmentDate) ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 14px; font-size:15px; line-height:1.7; color:#374151;">
                                Thank you for using our services.
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