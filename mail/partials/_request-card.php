<?php

use yii\helpers\Html;

/**
 * CARTE « DEMANDE » COMMUNE AUX E-MAILS
 * ------------------------------------
 * Résume la demande pour que le destinataire comprenne tout depuis le mail,
 * sans ouvrir la plateforme : numéro, priorité, opérateur, avion, lieu,
 * horaires, échéance de réponse et besoin exprimé.
 *
 * Les horaires sont ceux du lieu de maintenance, affichés tels que saisis
 * (aucune conversion de fuseau qui changerait l'heure de l'opérateur).
 *
 * @var \app\models\Requests $request
 * @var \app\models\Aircrafts|null $aircraft  Facultatif : relu depuis la demande sinon
 * @var bool|null $showDetails  Afficher le texte de la demande (oui par défaut)
 */

$aircraft = $aircraft ?? $request->getAircraft()->one();
$showDetails = $showDetails ?? true;

$airport = $request->getDestinationAirport()->one();
$operator = $request->getAO()->one();
$operatorName = $operator
    ? (trim((string) ($operator->company_name ?? '')) ?: (string) $operator->username)
    : 'Aircraft operator';
$aircraftName = $aircraft
    ? trim((string) $aircraft->manufacturer . ' ' . (string) $aircraft->model)
    : 'N/A';
$airportName = implode(' · ', array_filter([
    $airport ? $airport->icao : null,
    $airport ? $airport->airport_name : null,
    $airport ? $airport->country_name : null,
])) ?: 'N/A';

$priorityKey = strtolower((string) $request->operational_priority);
$priorityLabel = $request->getOperationalPriorityLabel();
$priorityStyles = [
    'aog' => 'background:#fef2f2; border:1px solid #fecaca; color:#b91c1c;',
    'urgent' => 'background:#fffbeb; border:1px solid #fde68a; color:#b45309;',
];
$priorityCellStyle = $priorityStyles[$priorityKey] ?? 'background:#f8fafc; border:1px solid #e5e7eb; color:#111827;';

$formatMaintenanceDate = static function ($value): string {
    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d M Y H:i', $timestamp) : 'N/A';
};
$responseDue = !empty($request->response_due_at_utc)
    ? date('d M Y H:i', strtotime((string) $request->response_due_at_utc)) . ' UTC'
    : 'No response deadline';

$details = trim((string) $request->request_details);
if (mb_strlen($details) > 1200) {
    $details = rtrim(mb_substr($details, 0, 1197)) . '…';
}

$label = static function (string $text, string $color = '#64748b'): string {
    return "<span style='display:block; color:" . $color . "; font-size:11px; text-transform:uppercase; letter-spacing:.3px;'>" . Html::encode($text) . '</span>';
};
$cell = 'padding:11px 12px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px; vertical-align:top;';
?>
            <!-- Carte « Demande » -->
            <div style='margin:0 0 6px; font-size:12px; font-weight:700; color:#0b5ed7; text-transform:uppercase; letter-spacing:.4px;'>
              Request #<?= Html::encode($request->request_id) ?>
            </div>
            <table role='presentation' width='100%' cellspacing='0' cellpadding='0' style='border-collapse:separate; border-spacing:0 8px; font-size:13px; line-height:1.5;'>
              <tr>
                <td style='width:50%; <?= $cell ?>'><?= $label('Operator') ?><strong><?= Html::encode($operatorName) ?></strong></td>
                <td style='width:50%; padding:11px 12px; border-radius:8px; vertical-align:top; <?= $priorityCellStyle ?>'><?= $label('Operational priority', 'inherit') ?><strong><?= Html::encode($priorityLabel) ?></strong></td>
              </tr>
              <tr>
                <td style='<?= $cell ?>'><?= $label('Aircraft') ?><strong><?= Html::encode($aircraftName) ?></strong></td>
                <td style='<?= $cell ?>'><?= $label('Registration · Serial number') ?><strong><?= Html::encode(trim((string) $request->aircraft_registration) ?: 'N/A') ?></strong> · <?= Html::encode(trim((string) $request->serial_number) ?: 'N/A') ?></td>
              </tr>
              <tr>
                <td colspan='2' style='<?= $cell ?>'><?= $label('Maintenance location') ?><strong><?= Html::encode($airportName) ?></strong></td>
              </tr>
              <tr>
                <td style='padding:11px 12px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; vertical-align:top;'><?= $label('ETA · Local time', '#1d4ed8') ?><strong><?= Html::encode($formatMaintenanceDate($request->eta)) ?></strong></td>
                <td style='padding:11px 12px; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:8px; vertical-align:top;'><?= $label('ETD · Local time', '#047857') ?><strong><?= Html::encode($formatMaintenanceDate($request->etd)) ?></strong></td>
              </tr>
              <?php if (trim((string) $request->required_certificates) !== '' || !empty($request->response_due_at_utc)): ?>
              <tr>
                <td style='<?= $cell ?>'><?= $label('Required certificates') ?><strong><?= Html::encode(trim((string) $request->required_certificates) ?: 'N/A') ?></strong></td>
                <td style='<?= $cell ?>'><?= $label('Response due') ?><strong><?= Html::encode($responseDue) ?></strong></td>
              </tr>
              <?php endif; ?>
            </table>

            <?php if ($showDetails && $details !== ''): ?>
            <div style='margin:2px 0 16px; padding:12px 14px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; font-size:13px; line-height:1.6;'>
              <?= $label('Maintenance request') ?>
              <?= nl2br(Html::encode($details)) ?>
            </div>
            <?php endif; ?>
