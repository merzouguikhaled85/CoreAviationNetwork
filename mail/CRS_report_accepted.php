<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $report app\models\RepairReport */
/* @var $mro app\models\MroProfile */
/* @var $request app\models\Requests */
/* @var $url string */

/*
 * CONTEXTE DU CRS ACCEPTÉ
 * ----------------------
 * Les informations affichées correspondent uniquement au dossier du MRO destinataire.
 * Les pièces jointes restent protégées dans la plateforme et ne quittent pas le système.
 */
$aircraft = $request->getAircraft()->one();
$airport = $request->getDestinationAirport()->one();
$operator = $request->getAO()->one();
$appName = Html::encode(Yii::$app->name);
$safeUrl = Html::encode($url);
$mroName = Html::encode($mro->username ?: 'MRO partner');
$operatorName = Html::encode(
    $operator
        ? ($operator->company_name ?: $operator->username)
        : 'Aircraft operator'
);
$aircraftName = Html::encode(trim(
    ($aircraft ? (string) $aircraft->manufacturer : '') . ' ' .
    ($aircraft ? (string) $aircraft->model : '')
));
$aircraftName = $aircraftName !== '' ? $aircraftName : 'N/A';
$airportName = Html::encode(implode(' · ', array_filter([
    $airport ? $airport->icao : null,
    $airport ? $airport->airport_name : null,
    $airport ? $airport->country_name : null,
])) ?: 'N/A');

/* Les heures restent exprimées dans le temps local du lieu de maintenance. */
$formatMaintenanceDate = static function ($value): string {
    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d M Y H:i', $timestamp) : 'N/A';
};
?>

<!-- Carte de notification harmonisée avec l'e-mail de vérification CAN. -->
<div style="margin:0; padding:0; background:#f4f6f8;">
    <div style="max-width:640px; margin:0 auto; padding:24px; font-family:Arial,Helvetica,sans-serif; color:#1f2937;">
        <div style="background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
            <div style="padding:18px 22px; background:#0b1a2e; color:#ffffff;">
                <div style="font-size:16px; font-weight:700; letter-spacing:.2px;"><?= $appName ?></div>
                <div style="font-size:13px; opacity:.9; margin-top:4px;">Maintenance release decision</div>
            </div>

            <div style="padding:22px;">
                <div style="display:inline-block; padding:5px 10px; margin-bottom:12px; border-radius:999px; background:#dcfce7; color:#166534; font-size:12px; font-weight:700;">CRS ACCEPTED</div>
                <h2 style="margin:0 0 12px; font-size:20px; line-height:1.3; color:#111827;">Certificate of Release to Service accepted</h2>
                <p style="margin:0 0 14px; font-size:14px; line-height:1.7;">Hello <strong><?= $mroName ?></strong>, the Aircraft Operator has accepted CRS report <strong>#<?= Html::encode($report->repair_report_id) ?></strong>.</p>

                <!-- Références et données essentielles permettant d'identifier le dossier sans ambiguïté. -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate; border-spacing:0 8px; font-size:13px;">
                    <tr>
                        <td style="width:50%; padding:11px 12px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px;"><span style="display:block; color:#64748b; font-size:11px; text-transform:uppercase;">Request</span><strong>#<?= Html::encode($request->request_id) ?></strong></td>
                        <td style="width:50%; padding:11px 12px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px;"><span style="display:block; color:#64748b; font-size:11px; text-transform:uppercase;">CRS report</span><strong>#<?= Html::encode($report->repair_report_id) ?></strong></td>
                    </tr>
                    <tr>
                        <td style="padding:11px 12px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px;"><span style="display:block; color:#64748b; font-size:11px; text-transform:uppercase;">Operator</span><strong><?= $operatorName ?></strong></td>
                        <td style="padding:11px 12px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px;"><span style="display:block; color:#64748b; font-size:11px; text-transform:uppercase;">Aircraft</span><strong><?= $aircraftName ?></strong></td>
                    </tr>
                    <tr>
                        <td style="padding:11px 12px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px;"><span style="display:block; color:#64748b; font-size:11px; text-transform:uppercase;">Registration / Serial</span><strong><?= Html::encode($request->aircraft_registration) ?> · <?= Html::encode($request->serial_number) ?></strong></td>
                        <td style="padding:11px 12px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px;"><span style="display:block; color:#64748b; font-size:11px; text-transform:uppercase;">Status</span><strong>Request closed</strong></td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding:11px 12px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px;"><span style="display:block; color:#64748b; font-size:11px; text-transform:uppercase;">Maintenance location</span><strong><?= $airportName ?></strong></td>
                    </tr>
                    <tr>
                        <td style="padding:11px 12px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px;"><span style="display:block; color:#1d4ed8; font-size:11px; text-transform:uppercase;">ETA · Local maintenance time</span><strong><?= Html::encode($formatMaintenanceDate($request->eta)) ?></strong></td>
                        <td style="padding:11px 12px; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:8px;"><span style="display:block; color:#047857; font-size:11px; text-transform:uppercase;">ETD · Local maintenance time</span><strong><?= Html::encode($formatMaintenanceDate($request->etd)) ?></strong></td>
                    </tr>
                </table>

                <div style="margin:18px 0;">
                    <a href="<?= $safeUrl ?>" style="display:inline-block; background:#0b5ed7; color:#ffffff; text-decoration:none; padding:12px 18px; border-radius:8px; font-size:14px; font-weight:700;">View CRS details</a>
                </div>
                <p style="margin:0; font-size:13px; line-height:1.7; color:#475569;">Thank you for completing the maintenance release process. Sign in to access the controlled report and its document.</p>
            </div>

            <div style="padding:14px 22px; background:#ffffff; border-top:1px solid #e5e7eb;">
                <p style="margin:0; font-size:12px; line-height:1.6; color:#6b7280;">This is an automated message from <?= $appName ?>. Please do not reply.</p>
            </div>
        </div>
    </div>
</div>
