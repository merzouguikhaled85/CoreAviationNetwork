<?php

/**
 * SHARED REQUEST SUMMARY
 * Compact list representation only; no request or workflow state is changed.
 *
 * @var yii\web\View $this
 * @var app\models\Requests|null $requestModel
 * @var object|null $aircraft
 */

use yii\helpers\Html;

$requestModel = $requestModel ?? null;
$aircraft = $aircraft ?? ($requestModel ? ($requestModel->aircraft ?? null) : null);

if ($requestModel === null): ?>
    <span class="request-unavailable">
        <i class="bi bi-exclamation-circle"></i> Request unavailable
    </span>
<?php else:
    $manufacturer = trim((string) ($aircraft->manufacturer ?? ''));
    $modelName = trim((string) ($aircraft->model ?? ''));
    $aircraftName = $modelName !== '' && $manufacturer !== '' && stripos($modelName, $manufacturer) === 0
        ? $modelName
        : trim($manufacturer . ' ' . $modelName);
    $aircraftName = $aircraftName !== '' ? $aircraftName : 'Aircraft unavailable';
    $serialNumber = trim((string) ($requestModel->serial_number ?? $aircraft->serial_number ?? ''));
    $serialNumber = $serialNumber !== '' ? $serialNumber : 'N/A';
    $compactModel = $modelName;
    if ($manufacturer !== '' && stripos($compactModel, $manufacturer) === 0) {
        $compactModel = trim(substr($compactModel, strlen($manufacturer)));
    }
    $formatDate = static function ($value): string {
        if (empty($value) || strtotime((string) $value) === false) {
            return 'N/A';
        }
        return date('d M Y H:i', strtotime((string) $value));
    };
    $maintenanceLocation = trim((string) ($requestModel->location ?? '')) ?: 'N/A';
    $etaLabel = $formatDate($requestModel->eta ?? null);
    $etdLabel = $formatDate($requestModel->etd ?? null);
    ?>
    <!-- SHARED ROW INTERACTION: request data enables the complete row to open the modal. -->
    <?= Html::beginTag('div', [
        'class' => 'can-request-summary',
        'data-request-id' => '#' . ($requestModel->request_id ?? 'N/A'),
        'data-aircraft' => $aircraftName,
        'data-registration' => $requestModel->aircraft_registration ?? $aircraft->registration_number ?? 'N/A',
        'data-serial' => $serialNumber,
        'data-eta' => $etaLabel,
        'data-etd' => $etdLabel,
        'data-location' => $maintenanceLocation,
        'data-status' => ucwords(str_replace('_', ' ', (string) ($requestModel->status ?? 'N/A'))),
        'data-description' => trim((string) ($requestModel->request_details ?? '')) ?: 'No request information provided.',
    ]) ?>
        <div class="can-request-aircraft" title="<?= Html::encode($aircraftName) ?>">
            <i class="bi bi-airplane"></i>
            <span>
                <span class="can-request-make"><?= Html::encode($manufacturer ?: 'Aircraft') ?></span>
                <small><?= Html::encode($compactModel ?: 'Model unavailable') ?></small>
                <!-- SHARED AIRCRAFT SUMMARY: identify the aircraft below its model without another column. -->
                <small class="can-request-serial" title="Aircraft serial number">
                    <i class="bi bi-upc-scan"></i> S/N <?= Html::encode($serialNumber) ?>
                </small>
            </span>
        </div>
        <span class="can-row-details-hint" aria-hidden="true">
            <i class="bi bi-eye"></i><span>View details</span><i class="bi bi-chevron-right"></i>
        </span>
    <?= Html::endTag('div') ?>
<?php endif; ?>
