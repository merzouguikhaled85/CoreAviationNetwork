<?php

use yii\helpers\Html;
use yii\web\View;
use app\components\UrlIdHelper;

/** @var yii\web\View $this */
/** @var app\models\MroCertificates $certificate */
/** @var app\models\MroCertificates $model */

/**
 * Support both variable names:
 * - $certificate from the existing controller
 * - $model if the controller sends model
 */
$certificateModel = $certificate ?? ($model ?? null);

if ($certificateModel === null) {
    throw new \yii\web\NotFoundHttpException('Certificate not found.');
}

/**
 * Safe attribute getter.
 * This avoids errors if some fields do not exist in the current model.
 */
$getSafeAttribute = static function ($model, array $fields, $default = 'N/A') {
    if ($model === null) {
        return $default;
    }

    foreach ($fields as $field) {
        try {
            if (
                method_exists($model, 'hasAttribute')
                && $model->hasAttribute($field)
                && $model->{$field} !== null
                && $model->{$field} !== ''
            ) {
                return $model->{$field};
            }

            if (
                method_exists($model, 'canGetProperty')
                && $model->canGetProperty($field)
                && $model->{$field} !== null
                && $model->{$field} !== ''
            ) {
                return $model->{$field};
            }

            if (
                property_exists($model, $field)
                && $model->{$field} !== null
                && $model->{$field} !== ''
            ) {
                return $model->{$field};
            }
        } catch (\Throwable $e) {
            continue;
        }
    }

    return $default;
};

/**
 * Build a safe certificate file URL.
 */
$buildFileUrl = static function ($filePath) {
    if (empty($filePath) || $filePath === 'N/A') {
        return null;
    }

    $filePath = trim((string) $filePath);

    if ($filePath === '') {
        return null;
    }

    if (preg_match('/^https?:\/\//i', $filePath)) {
        return $filePath;
    }

    if (strpos($filePath, '@web/') === 0) {
        return Yii::getAlias('@web') . '/' . ltrim(substr($filePath, 5), '/');
    }

    return Yii::$app->request->baseUrl . '/' . ltrim($filePath, '/');
};

/**
 * Load old working relations safely.
 * The old view used:
 * - $certificate->mro->username
 * - $certificate->aircraftModel->manufacturer
 * - $certificate->aircraftModel->model
 */
$mro = null;
$aircraftModelRelation = null;

try {
    if (
        method_exists($certificateModel, 'canGetProperty')
        && $certificateModel->canGetProperty('mro')
    ) {
        $mro = $certificateModel->mro;
    } elseif (method_exists($certificateModel, 'getMro')) {
        $mro = $certificateModel->getMro()->one();
    }
} catch (\Throwable $e) {
    $mro = null;
}

try {
    if (
        method_exists($certificateModel, 'canGetProperty')
        && $certificateModel->canGetProperty('aircraftModel')
    ) {
        $aircraftModelRelation = $certificateModel->aircraftModel;
    } elseif (method_exists($certificateModel, 'getAircraftModel')) {
        $aircraftModelRelation = $certificateModel->getAircraftModel()->one();
    }
} catch (\Throwable $e) {
    $aircraftModelRelation = null;
}

/**
 * Prepare certificate values.
 */
$certificateId = $getSafeAttribute($certificateModel, [
    'aircraft_certificate_id',
    'certificate_id',
    'id',
]);
$encodedCertificateId = UrlIdHelper::encode($certificateId);

$mroName = $mro->username ?? 'N/A';

$manufacturer = $aircraftModelRelation->manufacturer
    ?? $getSafeAttribute($certificateModel, ['manufacturer', 'aircraft_manufacturer'], 'N/A');

$aircraftType = $aircraftModelRelation->model
    ?? $getSafeAttribute($certificateModel, ['model', 'aircraft_model', 'aircraft_type'], 'N/A');

$certificateFile = $getSafeAttribute($certificateModel, [
    'certificate',
    'certificate_file',
    'file',
    'document',
    'file_path',
], null);

$fileUrl = $buildFileUrl($certificateFile);

$fileName = !empty($certificateFile)
    ? basename((string) $certificateFile)
    : 'N/A';

$createdAt = $getSafeAttribute($certificateModel, [
    'created_at',
    'createdAt',
    'uploaded_at',
    'date',
], 'N/A');

$updatedAt = $getSafeAttribute($certificateModel, [
    'updated_at',
    'updatedAt',
], 'N/A');

/**
 * Page title and breadcrumbs.
 */
$this->title = 'View Certificate: ' . $certificateId;
$this->params['breadcrumbs'][] = ['label' => 'Certificates', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

/**
 * Register Yii asset.
 */
\yii\web\YiiAsset::register($this);

/**
 * Register Bootstrap Icons.
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css',
    ['position' => View::POS_HEAD]
);

/* MRO CERTIFICATE CONFIRMATION 2026: SweetAlert for edit and destructive actions. */
$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    ['position' => View::POS_END]
);

/**
 * Safe back URL.
 */
$backUrl = Yii::$app->request->referrer ?: ['index'];

/**
 * Register page CSS.
 */
$this->registerCss(<<<CSS
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
    border-radius: 18px;
    padding: 22px 26px;
    margin-bottom: 22px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    border: 1px solid #e5eaf3;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    flex-wrap: wrap;
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

.btn-page-action {
    border-radius: 9px;
    padding: 10px 18px;
    font-weight: 800;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    white-space: nowrap;
    text-decoration: none;
    transition: all .2s ease;
}

.btn-back {
    background: #ffffff;
    color: #334155 !important;
    border: 1px solid #cbd5e1;
}

.btn-back:hover {
    background: #f1f5f9;
    color: #0f172a !important;
    transform: translateY(-1px);
    text-decoration: none;
}

.btn-header-download {
    color: #ffffff !important;
    background: #0f766e;
    border: 1px solid #0f766e;
}
.btn-header-download:hover { color: #ffffff !important; background: #0d665f; transform: translateY(-1px); }
.btn-header-update {
    color: #ffffff !important;
    background: #2563eb;
    border: 1px solid #2563eb;
}
.btn-header-update:hover { color: #ffffff !important; background: #1d4ed8; transform: translateY(-1px); }
.btn-header-delete {
    color: #b91c1c !important;
    background: #ffffff;
    border: 1px solid #fecaca;
}
.btn-header-delete:hover { color: #ffffff !important; background: #dc2626; transform: translateY(-1px); }

/* MRO CERTIFICATE CONFIRMATION 2026: compact confirmation dialog and spaced icon buttons. */
.swal2-popup.certificate-confirm-popup {
    width: 390px !important;
    max-width: 92vw !important;
    padding: 18px 20px !important;
    border-radius: 12px !important;
}
.swal2-popup.certificate-confirm-popup .swal2-icon {
    width: 54px !important;
    height: 54px !important;
    margin: 8px auto 12px !important;
}
.swal2-popup.certificate-confirm-popup .swal2-icon-content { font-size: 32px !important; }
.swal2-title.certificate-confirm-title {
    padding: 0 !important;
    color: #0f172a !important;
    font-size: 20px !important;
    font-weight: 900 !important;
}
.swal2-html-container.certificate-confirm-message {
    color: #475569 !important;
    font-size: 13px !important;
    line-height: 1.45 !important;
}
.swal2-popup.certificate-confirm-popup .swal2-actions {
    gap: 16px !important;
    margin-top: 16px !important;
}
.certificate-swal-confirm,
.certificate-swal-cancel {
    min-width: 120px !important;
    min-height: 39px !important;
    border: 0 !important;
    border-radius: 9px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    font-size: 12px !important;
    font-weight: 900 !important;
}
.certificate-swal-confirm.update { color: #fff !important; background: #2563eb !important; }
.certificate-swal-confirm.delete { color: #fff !important; background: #dc2626 !important; }
.certificate-swal-cancel { color: #334155 !important; background: #e2e8f0 !important; }

.view-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 18px;
}

.content-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    border: 1px solid #e5eaf3;
    max-width: 100%;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 16px;
    font-size: 17px;
    font-weight: 900;
    color: #0f172a;
}

.detail-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.detail-item {
    background: #f8fafc;
    border: 1px solid #e5eaf3;
    border-radius: 14px;
    padding: 14px;
    min-width: 0;
}

.detail-item.full-width {
    grid-column: 1 / -1;
}

.detail-label {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #64748b;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 7px;
}

.detail-value {
    color: #1f2937;
    font-size: 14px;
    font-weight: 800;
    overflow-wrap: anywhere;
}

.certificate-id-badge,
.mro-badge,
.manufacturer-badge,
.aircraft-badge,
.file-badge,
.date-badge,
.document-badge,
.empty-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 12px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
    text-decoration: none;
}

.certificate-id-badge {
    background: #eef2ff;
    color: #4338ca;
}

.mro-badge {
    background: #f1f5f9;
    color: #334155;
}

.manufacturer-badge {
    background: #e0f2fe;
    color: #0369a1;
}

.aircraft-badge {
    background: #ecfdf5;
    color: #047857;
}

.file-badge {
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e5e7eb;
}

.date-badge {
    background: #fff7ed;
    color: #c2410c;
}

.document-badge {
    background: #ecfdf5;
    color: #047857 !important;
    border: 1px solid #bbf7d0;
}

.document-badge:hover {
    background: #d1fae5;
    color: #065f46 !important;
    text-decoration: none;
}

/* MRO CERTIFICATE DOCUMENT 2026: separate preview and download actions. */
.document-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.document-view-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 12px;
    border: 1px solid #bfdbfe;
    border-radius: 9px;
    color: #1d4ed8 !important;
    background: #eff6ff;
    font-size: 12px;
    font-weight: 900;
    text-decoration: none;
}
.document-view-badge:hover {
    color: #ffffff !important;
    background: #2563eb;
    text-decoration: none;
}

.empty-badge {
    background: #f8fafc;
    color: #94a3b8;
    border: 1px solid #e5e7eb;
}

.path-box {
    line-height: 1.65;
    font-weight: 700;
    color: #334155;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    padding: 12px;
    overflow-wrap: anywhere;
}

.quick-actions {
    display: grid;
    gap: 10px;
}

.quick-action-btn {
    width: 100%;
    min-height: 44px;
    border-radius: 12px;
    padding: 11px 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 900;
    color: #ffffff !important;
    text-decoration: none;
    border: 1px solid rgba(15, 23, 42, .12);
    box-shadow: 0 8px 18px rgba(15, 23, 42, .12);
    transition: all .2s ease;
}

.quick-action-btn:hover {
    color: #ffffff !important;
    text-decoration: none;
    transform: translateY(-1px);
    box-shadow: 0 12px 24px rgba(15, 23, 42, .16);
}

.qa-view {
    background: #0ea5e9;
}

.qa-back {
    background: #64748b;
}

.summary-box {
    display: grid;
    gap: 10px;
}

.summary-item {
    background: #f8fafc;
    border: 1px solid #e5eaf3;
    border-radius: 14px;
    padding: 13px 14px;
}

.summary-label {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #64748b;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 6px;
}

.summary-value {
    color: #0f172a;
    font-size: 14px;
    font-weight: 900;
    overflow-wrap: anywhere;
}

@media (max-width: 992px) {
    .requests-page {
        padding: 14px;
    }

    .page-header-card {
        padding: 18px;
    }

    .dash-title {
        font-size: 23px;
    }

    .view-grid {
        grid-template-columns: 1fr;
    }

    .detail-list {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .requests-page {
        padding: 10px;
    }

    .page-header-card,
    .content-card {
        border-radius: 14px;
        padding: 16px;
    }

    .header-actions {
        width: 100%;
    }

    .btn-page-action {
        width: 100%;
        justify-content: center;
    }

    .certificate-id-badge,
    .mro-badge,
    .manufacturer-badge,
    .aircraft-badge,
    .file-badge,
    .date-badge,
    .document-badge,
    .empty-badge {
        white-space: normal;
    }
}
CSS);

/* MRO CERTIFICATE CONFIRMATION 2026: preserve Yii data-method while replacing native confirm. */
$this->registerJs(<<<JS
if (typeof yii !== 'undefined') {
    yii.confirm = function (message, okCallback, cancelCallback) {
        var isDelete = String(message).toLowerCase().indexOf('delete') !== -1;
        var title = isDelete ? 'Delete this certificate?' : 'Update this certificate?';
        var detail = isDelete
            ? '<strong>This certificate will be permanently deleted.</strong>'
            : '<strong>You will be redirected to the certificate update form.</strong>';

        if (typeof Swal === 'undefined') {
            if (window.confirm(message)) {
                okCallback();
            } else if (cancelCallback) {
                cancelCallback();
            }
            return;
        }

        Swal.fire({
            title: title,
            html: detail,
            icon: isDelete ? 'warning' : 'question',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            allowOutsideClick: false,
            confirmButtonText: isDelete
                ? '<i class="bi bi-trash3-fill"></i> Delete'
                : '<i class="bi bi-pencil-square"></i> Continue',
            cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review action',
            buttonsStyling: false,
            // SHARED DETAIL CONFIRMATION: reuse the common compact dialog presentation.
            customClass: {
                popup: 'certificate-confirm-popup can-detail-swal',
                title: 'certificate-confirm-title',
                htmlContainer: 'certificate-confirm-message',
                confirmButton: 'certificate-swal-confirm ' + (isDelete ? 'delete' : 'update'),
                cancelButton: 'certificate-swal-cancel'
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                okCallback();
            } else if (cancelCallback) {
                cancelCallback();
            }
        });
    };
}
JS, View::POS_READY);
?>

<!-- SHARED DETAIL SYSTEM: presentation only; certificate actions remain unchanged. -->
<main class="dash-content requests-page can-detail-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title fw-bold">
                    <span style="color: var(--bs-info);">
                        <i class="bi bi-shield-check"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Review MRO certificate information and download the official document.
                </div>
            </div>

            <!-- MRO CERTIFICATE VIEW 2026: business actions moved beside Back; IDs stay encoded. -->
            <div class="header-actions">
                <?php if (!empty($fileUrl)): ?>
                    <?= Html::a(
                        '<i class="bi bi-eye"></i> View Document',
                        $fileUrl,
                        [
                            'class' => 'btn-page-action btn-header-update',
                            'target' => '_blank',
                            'rel' => 'noopener',
                            'data-pjax' => '0',
                            'data-no-loader' => 'true',
                        ]
                    ) ?>
                    <?= Html::a(
                        '<i class="bi bi-download"></i> Download',
                        $fileUrl,
                        [
                            'class' => 'btn-page-action btn-header-download',
                            'target' => '_blank',
                            'rel' => 'noopener',
                            'download' => $fileName,
                            'data-pjax' => '0',
                            'data-no-loader' => 'true',
                        ]
                    ) ?>
                <?php endif; ?>
                <?= Html::a(
                    '<i class="bi bi-pencil-square"></i> Update',
                    ['update', 'id' => $encodedCertificateId],
                    [
                        'class' => 'btn-page-action btn-header-update',
                        'data' => ['confirm' => 'Open the certificate update form?'],
                    ]
                ) ?>
                <?= Html::a(
                    '<i class="bi bi-trash3"></i> Delete',
                    ['delete', 'id' => $encodedCertificateId],
                    [
                        'class' => 'btn-page-action btn-header-delete',
                        'data' => [
                            'confirm' => 'Are you sure you want to delete this certificate?',
                            'method' => 'post',
                        ],
                    ]
                ) ?>
                <?= Html::a(
                    '<i class="bi bi-arrow-left-circle"></i> Back',
                    $backUrl,
                    ['class' => 'btn-page-action btn-back']
                ) ?>
            </div>
        </div>

        <div class="view-grid">

            <!-- Main certificate details -->
            <div class="content-card">
                <h2 class="section-title">
                    <i class="bi bi-info-circle text-primary"></i>
                    Certificate Details
                </h2>

                <div class="detail-list">

                    <!-- Certificate ID -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-hash"></i>
                            Certificate ID
                        </div>

                        <div class="detail-value">
                            <span class="certificate-id-badge">
                                #<?= Html::encode($certificateId) ?>
                            </span>
                        </div>
                    </div>

                    <!-- MRO Name -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-building"></i>
                            MRO Name
                        </div>

                        <div class="detail-value">
                            <span class="mro-badge">
                                <i class="bi bi-building-check"></i>
                                <?= Html::encode($mroName) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Aircraft Manufacturer -->
                    <!-- <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-tools"></i>
                            Aircraft Manufacturer
                        </div>

                        <div class="detail-value">
                            <span class="manufacturer-badge">
                                <i class="bi bi-tools"></i>
                                <?= Html::encode($manufacturer) ?>
                            </span>
                        </div>
                    </div> -->

                    <!-- Aircraft Type -->
                    <!-- <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-airplane-engines"></i>
                            Aircraft Type
                        </div>

                        <div class="detail-value">
                            <span class="aircraft-badge">
                                <i class="bi bi-airplane"></i>
                                <?= Html::encode($aircraftType) ?>
                            </span>
                        </div>
                    </div> -->

                    <!-- Certificate File -->
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-file-earmark-text"></i>
                            Certificate File
                        </div>

                        <div class="detail-value">
                            <span class="file-badge">
                                <i class="bi bi-file-earmark"></i>
                                <?= Html::encode($fileName) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Created At -->
                    <!-- <div class="detail-item">
                        <div class="detail-label">
                            <i class="bi bi-calendar-check"></i>
                            Created At
                        </div>

                        <div class="detail-value">
                            <span class="date-badge">
                                <i class="bi bi-calendar-check"></i>
                                <?= Html::encode($createdAt) ?>
                            </span>
                        </div>
                    </div> -->

                    <!-- Document -->
                    <div class="detail-item full-width">
                        <div class="detail-label">
                            <i class="bi bi-cloud-arrow-down"></i>
                            Document
                        </div>

                        <div class="detail-value">
                            <?php if (!empty($fileUrl)): ?>
                                <!-- MRO CERTIFICATE DOCUMENT 2026: preview and download are explicit. -->
                                <div class="document-actions">
                                    <?= Html::a(
                                        '<i class="bi bi-eye"></i> View Document',
                                        $fileUrl,
                                        [
                                            'class' => 'document-view-badge',
                                            'target' => '_blank',
                                            'rel' => 'noopener',
                                            'data-pjax' => '0',
                                            'data-no-loader' => 'true',
                                        ]
                                    ) ?>
                                    <?= Html::a(
                                        '<i class="bi bi-download"></i> Download Certificate',
                                        $fileUrl,
                                        [
                                            'class' => 'document-badge',
                                            'target' => '_blank',
                                            'rel' => 'noopener',
                                            'download' => $fileName,
                                            'title' => 'Download ' . $fileName,
                                            'data-pjax' => '0',
                                            'data-no-loader' => 'true',
                                        ]
                                    ) ?>
                                </div>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="bi bi-file-earmark-x"></i>
                                    No certificate file
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Certificate Path -->
                    <!-- <div class="detail-item full-width">
                        <div class="detail-label">
                            <i class="bi bi-folder2-open"></i>
                            Certificate Path
                        </div>

                        <div class="path-box">
                            <?= Html::encode($certificateFile ?: 'N/A') ?>
                        </div>
                    </div> -->

                </div>
            </div>

            <!-- MRO CERTIFICATE VIEW 2026: Quick Actions and duplicate Summary removed. -->

        </div>

    </div>
</main>
