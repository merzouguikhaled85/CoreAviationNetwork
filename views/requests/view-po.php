<?php

use yii\helpers\Html;
use app\components\UrlIdHelper;

$this->title = 'View PO';
$encodedRequestId = UrlIdHelper::encode($request->request_id);

/* REQUEST PO VIEW 2026: prepare operational request values for display only. */
$aircraft = $request->aircraft;
$operator = $request->aO;
$operatorName = $operator
    ? ($operator->company_name ?: trim(($operator->first_name ?: '') . ' ' . ($operator->last_name ?: '')) ?: $operator->username)
    : 'N/A';
$aircraftName = $aircraft
    ? trim(($aircraft->manufacturer ?: '') . ' ' . ($aircraft->model ?: ''))
    : 'Aircraft deleted';
$aircraftName = $aircraftName !== '' ? $aircraftName : 'N/A';
$formatOperationalDate = static function ($value) {
    if (empty($value)) {
        return 'N/A';
    }
    $timestamp = strtotime($value);
    return $timestamp ? date('d M Y H:i', $timestamp) : $value;
};

$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');

$this->registerCss("
    html,
    body {
        max-width: 100%;
        overflow-x: hidden;
    }

    .requests-page {
        padding: 24px;
        background: #f5f7fb;
        min-height: 100vh;
        max-width: 100%;
        overflow-x: hidden;
    }

    .requests-page .container-fluid {
        max-width: 100%;
        overflow-x: hidden;
        padding-left: 0;
        padding-right: 0;
    }

    .page-header-card {
        background: linear-gradient(135deg, #ffffff, #eef4ff);
        border-radius: 8px;
        padding: 22px 26px;
        margin-bottom: 22px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        border: 1px solid #e5eaf3;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        flex-wrap: wrap;
        max-width: 100%;
    }

    .dash-title {
        margin: 0;
        font-size: 28px;
        font-weight: 800;
        color: #1f2937;
    }

    .subtitle-text {
        color: #6b7280;
        margin-top: 6px;
        font-size: 14px;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn-back-requests {
        border-radius: 9px;
        padding: 11px 18px;
        font-weight: 800;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #ffffff;
        color: #0d3261;
        border: 1px solid #cbd5e1;
        text-decoration: none;
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.12);
        transition: all .2s ease;
    }

    .btn-back-requests:hover {
        background: #0d3261;
        color: #ffffff;
        text-decoration: none;
        transform: translateY(-1px);
    }

    .content-card {
        background: #ffffff;
        border-radius: 14px;
        padding: 18px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        border: 1px solid #e5eaf3;
        max-width: 100%;
        overflow-x: hidden;
        margin-bottom: 22px;
    }

    .section-title {
        margin: 0 0 16px 0;
        color: #1f2937;
        font-size: 19px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .section-title i {
        color: #2563eb;
    }

    .request-summary-card {
        background: #f8fafc;
        border: 1px solid #e5eaf3;
        border-radius: 8px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .request-summary-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 8px;
        background: #dbeafe;
        color: #1d4ed8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .request-summary-label {
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .request-summary-value {
        color: #0f172a;
        font-size: 18px;
        font-weight: 800;
    }

    /* REQUEST PO VIEW 2026: complete, responsive request summary. */
    .request-detail-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }
    .request-detail-head .section-title { margin-bottom: 0; }
    .request-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        color: #075985;
        background: #e0f2fe;
        font-size: 11px;
        font-weight: 900;
        white-space: nowrap;
    }
    .request-detail-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }
    .request-detail-item {
        min-width: 0;
        min-height: 72px;
        padding: 11px 13px;
        border: 1px solid #dde7f1;
        border-radius: 10px;
        background: #f8fafc;
    }
    .request-detail-item.wide { grid-column: span 2; }
    .request-detail-label {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 7px;
        color: #718096;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .request-detail-value {
        color: #0f172a;
        font-size: 13px;
        font-weight: 800;
        line-height: 1.4;
        overflow-wrap: anywhere;
    }
    .request-date-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-top: 10px;
    }
    .request-information {
        width: 100%;
        min-height: 90px;
        margin-top: 10px;
        padding: 12px 13px;
        resize: vertical;
        border: 1px dashed #b8cbe1;
        border-radius: 10px;
        color: #334155;
        background: #ffffff;
        font: inherit;
        font-size: 13px;
        line-height: 1.5;
    }

    .table-responsive-custom {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
        border-radius: 8px;
    }

    .requests-table {
        width: 100%;
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
        table-layout: auto;
    }

    .requests-table thead th {
        background: #f1f5f9;
        color: #334155;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 800;
        padding: 13px 8px;
        border: none;
        white-space: nowrap;
    }

    .requests-table tbody td {
        padding: 13px 8px;
        vertical-align: middle;
        border-top: 1px solid #eef2f7;
        color: #374151;
        font-size: 13px;
    }

    .requests-table tbody tr {
        transition: background .2s ease;
    }

    .requests-table tbody tr:hover {
        background: #f8fbff;
        transform: none;
    }

    .mro-link {
        color: #0f766e;
        font-weight: 800;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .mro-link:hover {
        text-decoration: underline;
    }

    .deleted-mro {
        color: #991b1b;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .description-box {
        max-width: 520px;
        background: #f8fafc;
        border: 1px solid #e5eaf3;
        border-radius: 8px;
        padding: 10px 12px;
        color: #334155;
        line-height: 1.45;
        word-break: break-word;
    }

    .description-readonly {
        width: 100%;
        min-height: 70px;
        padding: 10px 12px;
        resize: vertical;
        border: 1px solid #e5eaf3;
        border-radius: 8px;
        color: #334155;
        background: #f8fafc;
        font: inherit;
        font-size: 12px;
        line-height: 1.45;
    }

    /* REQUEST PO DOCUMENT 2026: explicit preview and download actions. */
    .po-actions {
        display: flex;
        align-items: center;
        gap: 9px;
        flex-wrap: wrap;
    }
    .po-document {
        display: grid;
        gap: 8px;
    }
    .po-document-name {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #334155;
        font-size: 11px;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .po-link {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        padding: 8px 11px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
        white-space: nowrap;
    }

    .po-link:hover {
        background: #dbeafe;
        color: #1e40af;
        text-decoration: none;
    }

    .po-link.po-view {
        color: #ffffff;
        border-color: #2563eb;
        background: #2563eb;
    }
    .po-link.po-view:hover {
        color: #ffffff;
        background: #1d4ed8;
    }
    .po-link.po-download {
        color: #047857;
        border-color: #a7f3d0;
        background: #ecfdf5;
    }
    .po-link.po-download:hover {
        color: #ffffff;
        background: #0f766e;
    }

    .empty-state {
        padding: 35px;
        text-align: center;
        color: #64748b;
        font-weight: 600;
    }

    @media (max-width: 992px) {
        .requests-page {
            padding: 14px;
        }

        .page-header-card {
            padding: 18px;
            align-items: flex-start;
        }

        .dash-title {
            font-size: 23px;
        }

        .header-actions {
            width: 100%;
            justify-content: flex-start;
        }

        .btn-back-requests {
            width: 100%;
            justify-content: center;
        }

        .content-card {
            padding: 12px;
        }

        .table-responsive-custom {
            overflow-x: hidden;
        }

        .requests-table,
        .requests-table thead,
        .requests-table tbody,
        .requests-table th,
        .requests-table td,
        .requests-table tr {
            display: block;
            width: 100%;
        }

        .requests-table thead {
            display: none;
        }

        .requests-table tbody tr {
            background: #ffffff;
            border: 1px solid #e5eaf3;
            border-radius: 8px;
            margin-bottom: 14px;
            padding: 12px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
        }

        .requests-table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            border-top: none;
            border-bottom: 1px solid #eef2f7;
            padding: 10px 4px;
            white-space: normal;
            max-width: 100%;
            overflow: visible;
            text-overflow: unset;
            font-size: 13px;
            text-align: right;
        }

        .requests-table tbody td:last-child {
            border-bottom: none;
        }

        .requests-table tbody td::before {
            content: attr(data-label);
            font-weight: 800;
            color: #334155;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: .04em;
            text-align: left;
            flex: 0 0 42%;
        }

        .description-box {
            max-width: 100%;
            width: 100%;
        }

        .request-detail-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .po-link {
            white-space: normal;
            text-align: center;
            justify-content: center;
        }

        .request-detail-head {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    @media (max-width: 576px) {
        .requests-page {
            padding: 10px;
        }

        .page-header-card {
            border-radius: 8px;
            padding: 16px;
        }

        .content-card {
            border-radius: 8px;
            padding: 10px;
        }

        .requests-table tbody td {
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
            gap: 6px;
        }

        .requests-table tbody td::before {
            flex: none;
        }

        .po-link {
            width: 100%;
        }

        .request-detail-grid,
        .request-date-grid {
            grid-template-columns: 1fr;
        }

        .request-detail-item.wide {
            grid-column: auto;
        }

        .po-actions {
            width: 100%;
        }
    }
");
?>

<!-- SHARED DETAIL SYSTEM: presentation only; PO access remains unchanged. -->
<main class="dash-content requests-page can-detail-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title fw-bold">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-file-earmark-text"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    View purchase order files linked to this request
                </div>
            </div>

            <div class="header-actions">
                <?= Html::a(
                    '<i class="bi bi-arrow-left-circle"></i> Back to Requests',
                    ['index'],
                    ['class' => 'btn-back-requests']
                ) ?>
            </div>
        </div>

        <!-- REQUEST PO VIEW 2026: full request context shown once, without duplicating the PO data. -->
        <div class="content-card">
            <div class="request-detail-head">
                <h3 class="section-title">
                    <i class="bi bi-airplane-engines"></i>
                    Current Request Details
                </h3>
                <span class="request-status-pill">
                    <i class="bi bi-circle-fill"></i>
                    <?= Html::encode(ucwords(str_replace('_', ' ', (string)$request->status))) ?>
                </span>
            </div>

            <div class="request-detail-grid">
                <div class="request-detail-item">
                    <div class="request-detail-label"><i class="bi bi-hash"></i> Request ID</div>
                    <div class="request-detail-value">#<?= Html::encode($request->request_id) ?></div>
                </div>
                <div class="request-detail-item">
                    <div class="request-detail-label"><i class="bi bi-card-text"></i> Registration</div>
                    <div class="request-detail-value"><?= Html::encode($request->aircraft_registration ?: 'N/A') ?></div>
                </div>
                <div class="request-detail-item wide">
                    <div class="request-detail-label"><i class="bi bi-building"></i> Aircraft Operator / CAMO</div>
                    <div class="request-detail-value"><?= Html::encode($operatorName) ?></div>
                </div>
                <div class="request-detail-item wide">
                    <div class="request-detail-label"><i class="bi bi-airplane"></i> Aircraft</div>
                    <div class="request-detail-value"><?= Html::encode($aircraftName) ?></div>
                </div>
                <div class="request-detail-item">
                    <div class="request-detail-label"><i class="bi bi-upc-scan"></i> Serial Number</div>
                    <div class="request-detail-value"><?= Html::encode($request->serial_number ?: 'N/A') ?></div>
                </div>
                <div class="request-detail-item">
                    <div class="request-detail-label"><i class="bi bi-geo-alt"></i> Maintenance Location</div>
                    <div class="request-detail-value"><?= Html::encode($request->location ?: 'N/A') ?></div>
                </div>
            </div>
            <div class="request-date-grid">
                <div class="request-detail-item">
                    <div class="request-detail-label"><i class="bi bi-calendar-event"></i> ETA</div>
                    <div class="request-detail-value"><?= Html::encode($formatOperationalDate($request->eta)) ?></div>
                </div>
                <div class="request-detail-item">
                    <div class="request-detail-label"><i class="bi bi-calendar-check"></i> ETD</div>
                    <div class="request-detail-value"><?= Html::encode($formatOperationalDate($request->etd)) ?></div>
                </div>
            </div>
            <?= Html::textarea('request_details_display', $request->request_details ?: 'No request information available.', [
                'class' => 'request-information',
                'readonly' => true,
                'aria-label' => 'Request information',
            ]) ?>
        </div>

        <!-- PO table card -->
        <div class="content-card">
            <h3 class="section-title">
                <i class="bi bi-file-earmark-arrow-down"></i>
                PO Files
            </h3>

            <div class="table-responsive-custom">
                <table class="table requests-table">
                    <thead>
                        <tr>
                            <th>MRO Username</th>
                            <th>Application Description</th>
                            <th>PO File</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($aoRequestsApplications)): ?>
                            <?php foreach ($aoRequestsApplications as $aoRequestApplication): ?>

                                <?php
                                    $application = $aoRequestApplication->application;
                                    $mro = $application ? $application->mro : null;
                                    $encodedPoId = UrlIdHelper::encode($aoRequestApplication->id);
                                    $poFileName = !empty($aoRequestApplication->po)
                                        ? basename($aoRequestApplication->po)
                                        : null;
                                ?>

                                <tr>
                                    <td data-label="MRO Username">
                                        <?= $mro
                                            ? Html::a(
                                                '<i class="bi bi-building"></i>' . Html::encode($mro->username),
                                                ['mro-profile/view', 'id' => UrlIdHelper::encode($mro->mro_id)],
                                                ['class' => 'mro-link']
                                            )
                                            : '<span class="deleted-mro"><i class="bi bi-exclamation-circle"></i> MRO Deleted</span>' ?>
                                    </td>

                                    <td data-label="Application Description">
                                        <textarea class="description-readonly" readonly aria-label="Application description"><?= Html::encode($application ? ($application->Description ?: 'No description') : 'No description') ?></textarea>
                                    </td>

                                    <td data-label="PO File">
                                        <?php if ($poFileName): ?>
                                            <!-- REQUEST PO DOCUMENT 2026: explicit inline view and encoded download links. -->
                                            <div class="po-document">
                                                <div class="po-document-name">
                                                    <i class="bi bi-file-earmark-pdf"></i>
                                                    <?= Html::encode($poFileName) ?>
                                                </div>
                                                <div class="po-actions">
                                                    <?= Html::a(
                                                        '<i class="bi bi-eye"></i> View Document',
                                                        ['view-po-document', 'id' => $encodedPoId],
                                                        [
                                                            'class' => 'po-link po-view',
                                                            'target' => '_blank',
                                                            'rel' => 'noopener',
                                                            'data-pjax' => '0',
                                                            'data-no-loader' => 'true',
                                                        ]
                                                    ) ?>
                                                    <?= Html::a(
                                                        '<i class="bi bi-download"></i> Download PO',
                                                        ['download-po', 'id' => $encodedPoId],
                                                        [
                                                            'class' => 'po-link po-download',
                                                            'data-pjax' => '0',
                                                            'data-no-loader' => 'true',
                                                        ]
                                                    ) ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted"><i class="bi bi-file-earmark-x me-1"></i>No PO document</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox" style="font-size: 35px;"></i>
                                        <div>No PO files found.</div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>
