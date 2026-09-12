<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $url string */
/* @var $request app\models\Requests */
/* @var $aircraft app\models\Aircrafts */

/*
 * COMPATIBILITÉ DES NOTIFICATIONS EXISTANTES
 * ------------------------------------------
 * Les créations récentes fournissent la demande complète. L'ancien e-mail « Request
 * Updated » ne fournit encore que l'URL : il conserve donc son message générique et
 * ne reçoit aucune donnée supplémentaire sans autorisation métier explicite.
 */
$hasRequestDetails = isset($request, $aircraft) && $request && $aircraft;
$appName = Html::encode(Yii::$app->name);
$safeUrl = Html::encode($url);

if ($hasRequestDetails) {
    $airport = $request->getDestinationAirport()->one();
    $operator = $request->getAO()->one();
    $operatorName = Html::encode(
        $operator
            ? ($operator->company_name ?: $operator->username)
            : 'Aircraft operator'
    );
    $aircraftName = Html::encode(trim((string) $aircraft->manufacturer . ' ' . (string) $aircraft->model));
    $airportName = Html::encode(implode(' · ', array_filter([
        $airport ? $airport->icao : null,
        $airport ? $airport->airport_name : null,
        $airport ? $airport->country_name : null,
    ])) ?: 'N/A');
    $priority = Html::encode($request->getOperationalPriorityLabel());
    $responseDue = !empty($request->response_due_at_utc)
        ? Html::encode($request->response_due_at_utc . ' UTC')
        : 'No response deadline';
}

/*
 * Les horaires restent ceux du lieu de maintenance. Le format est rendu plus lisible
 * sans appliquer de conversion susceptible de changer l'heure saisie par l'opérateur.
 */
$formatMaintenanceDate = static function ($value): string {
    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d M Y H:i', $timestamp) : 'N/A';
};
?>

<!-- Modèle compatible avec les clients mail : tous les styles importants sont intégrés. -->
<div style="margin:0; padding:0; background:#f4f6f8;">
    <div style="max-width:640px; margin:0 auto; padding:24px; font-family:Arial,Helvetica,sans-serif; color:#1f2937;">
        <div style="background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
            <div style="padding:18px 22px; background:#0b1a2e; color:#ffffff;">
                <div style="font-size:16px; font-weight:700; letter-spacing:.2px;"><?= $appName ?></div>
                <div style="font-size:13px; opacity:.9; margin-top:4px;">Maintenance request notification</div>
            </div>

            <div style="padding:22px;">
                <div style="display:inline-block; padding:5px 10px; margin-bottom:12px; border-radius:999px; background:#e0f2fe; color:#0369a1; font-size:12px; font-weight:700;">NEW REQUEST</div>
                <h2 style="margin:0 0 12px; font-size:20px; line-height:1.3; color:#111827;">A request matches your scope of work</h2>
                <?php if ($hasRequestDetails): ?>
                    <p style="margin:0 0 14px; font-size:14px; line-height:1.7;">Request <strong>#<?= Html::encode($request->request_id) ?></strong> is available for review and quotation.</p>
                <?php else: ?>
                    <p style="margin:0 0 14px; font-size:14px; line-height:1.7;">A request matching your scope of work is available for review on the platform.</p>
                <?php endif; ?>

                <?php if ($hasRequestDetails): ?>
                <!-- Résumé opérationnel permettant au MRO d'évaluer rapidement la demande. -->
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate; border-spacing:0 8px; font-size:13px;">
                    <tr>
                        <td style="width:50%; padding:11px 12px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px;"><span style="display:block; color:#64748b; font-size:11px; text-transform:uppercase;">Operator</span><strong><?= $operatorName ?></strong></td>
                        <td style="width:50%; padding:11px 12px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px;"><span style="display:block; color:#64748b; font-size:11px; text-transform:uppercase;">Aircraft</span><strong><?= $aircraftName ?></strong></td>
                    </tr>
                    <tr>
                        <td style="padding:11px 12px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px;"><span style="display:block; color:#64748b; font-size:11px; text-transform:uppercase;">Registration</span><strong><?= Html::encode($request->aircraft_registration) ?></strong></td>
                        <td style="padding:11px 12px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px;"><span style="display:block; color:#64748b; font-size:11px; text-transform:uppercase;">Serial number</span><strong><?= Html::encode($request->serial_number) ?></strong></td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding:11px 12px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px;"><span style="display:block; color:#64748b; font-size:11px; text-transform:uppercase;">Maintenance location</span><strong><?= $airportName ?></strong></td>
                    </tr>
                    <tr>
                        <td style="padding:11px 12px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px;"><span style="display:block; color:#1d4ed8; font-size:11px; text-transform:uppercase;">ETA · Local maintenance time</span><strong><?= Html::encode($formatMaintenanceDate($request->eta)) ?></strong></td>
                        <td style="padding:11px 12px; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:8px;"><span style="display:block; color:#047857; font-size:11px; text-transform:uppercase;">ETD · Local maintenance time</span><strong><?= Html::encode($formatMaintenanceDate($request->etd)) ?></strong></td>
                    </tr>
                    <tr>
                        <td style="padding:11px 12px; background:#fff7ed; border:1px solid #fed7aa; border-radius:8px;"><span style="display:block; color:#c2410c; font-size:11px; text-transform:uppercase;">Priority</span><strong><?= $priority ?></strong></td>
                        <td style="padding:11px 12px; background:#fff7ed; border:1px solid #fed7aa; border-radius:8px;"><span style="display:block; color:#c2410c; font-size:11px; text-transform:uppercase;">Response due</span><strong><?= $responseDue ?></strong></td>
                    </tr>
                </table>

                <div style="margin:8px 0 16px; padding:12px 14px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; font-size:13px; line-height:1.6;">
                    <span style="display:block; color:#64748b; font-size:11px; text-transform:uppercase;">Maintenance request</span>
                    <?= nl2br(Html::encode($request->request_details)) ?>
                </div>
                <?php endif; ?>

                <div style="margin:18px 0;">
                    <a href="<?= $safeUrl ?>" style="display:inline-block; background:#0b5ed7; color:#ffffff; text-decoration:none; padding:12px 18px; border-radius:8px; font-size:14px; font-weight:700;">Review request</a>
                </div>
                <p style="margin:0; font-size:13px; line-height:1.7; color:#475569;">Sign in to the platform before submitting a quotation or viewing controlled documents.</p>
            </div>

            <div style="padding:14px 22px; background:#ffffff; border-top:1px solid #e5e7eb;">
                <p style="margin:0; font-size:12px; line-height:1.6; color:#6b7280;">This is an automated message from <?= $appName ?>. Please do not reply.</p>
            </div>
        </div>
    </div>
</div>
