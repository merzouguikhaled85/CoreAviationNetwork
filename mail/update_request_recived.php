<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $request app\models\Requests */

$aoName = $request->aO->username ?? 'AO';
$requestDetails = $request->request_details;
$aircraftDetails = $request->aircraft_registration . ' - ' . $request->serial_number;
$eta = Yii::$app->formatter->asDatetime($request->eta);
$etd = Yii::$app->formatter->asDatetime($request->etd);
$location = $request->location;
$destination = $request->destinationAirport->airport_name ?? 'N/A';

$appName = Yii::$app->name ?: 'Core Aviation Network';
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
                                Request Update Notification
                            </p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding:36px 38px;">

                            <h2 style="margin:0 0 16px; color:#111827; font-size:22px;">
                                Request update received
                            </h2>

                            <p style="margin:0 0 14px; font-size:15px; line-height:1.7; color:#374151;">
                                Dear MRO,
                            </p>

                            <p style="margin:0 0 24px; font-size:15px; line-height:1.7; color:#374151;">
                                We have received your update request for the following maintenance request.
                            </p>

                            <!-- Details Card -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; margin:24px 0;">
                                <tr>
                                    <td style="background:#f8fafc; padding:14px 18px; font-size:14px; font-weight:700; color:#111827;">
                                        Request Details
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:0;">
                                        <table width="100%" cellpadding="0" cellspacing="0">

                                            <tr>
                                                <td style="width:38%; padding:13px 18px; border-top:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:700;">
                                                    Aircraft Details
                                                </td>
                                                <td style="padding:13px 18px; border-top:1px solid #e5e7eb; font-size:14px; color:#111827;">
                                                    <?= Html::encode($aircraftDetails) ?>
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

                                            <tr>
                                                <td style="width:38%; padding:13px 18px; border-top:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:700;">
                                                    Location
                                                </td>
                                                <td style="padding:13px 18px; border-top:1px solid #e5e7eb; font-size:14px; color:#111827;">
                                                    <?= Html::encode($location) ?>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="width:38%; padding:13px 18px; border-top:1px solid #e5e7eb; font-size:13px; color:#6b7280; font-weight:700;">
                                                    Destination Airport
                                                </td>
                                                <td style="padding:13px 18px; border-top:1px solid #e5e7eb; font-size:14px; color:#111827;">
                                                    <?= Html::encode($destination) ?>
                                                </td>
                                            </tr>

                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 14px; font-size:15px; line-height:1.7; color:#374151;">
                                We will review your request and get back to you shortly.
                            </p>

                            <p style="margin:24px 0 0; font-size:15px; line-height:1.7; color:#374151;">
                                Thank you,<br>
                                <strong>Your MRO Team</strong>
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