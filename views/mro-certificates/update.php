<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\web\View;

/** @var yii\web\View $this */
/** @var app\models\Certificates $certificate */

/**
 * Page title.
 */
$this->title = 'Update Certificate';

/**
 * Prepare back URL.
 */
$backUrl = Yii::$app->request->referrer ?: ['index'];

/**
 * Prepare current MRO ID safely.
 */
$mroId = Yii::$app->user->identity->mro_id ?? null;

/**
 * Prepare NAA type safely.
 */
$naaType = $certificate->type ?? 'N/A';

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

/**
 * Register SweetAlert2 CSS and JS.
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
    ['position' => View::POS_HEAD]
);

$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
    ['depends' => [\yii\web\JqueryAsset::class]]
);

/**
 * Register page CSS.
 */
$this->registerCss(<<<CSS
html,
body {
    max-width: 100%;
    overflow-x: hidden;
}

.create-page {
    padding: 24px;
    background: #f5f7fb;
    min-height: 100vh;
    max-width: 100%;
    overflow-x: hidden;
}

.create-page .container-fluid {
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

.form-layout {
    display: grid;
    grid-template-columns: 1.25fr 0.75fr;
    gap: 18px;
}

.form-card,
.helper-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 20px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    border: 1px solid #e5eaf3;
    max-width: 100%;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 18px;
    font-size: 17px;
    font-weight: 900;
    color: #0f172a;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.form-field-card {
    background: #f8fafc;
    border: 1px solid #e5eaf3;
    border-radius: 14px;
    padding: 14px;
    min-width: 0;
}

.form-field-card.full-width {
    grid-column: 1 / -1;
}

.field-heading {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #64748b;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 8px;
}

.field-heading i {
    color: #f59e0b;
    font-size: 15px;
}

.required-mark {
    color: #dc2626;
    font-size: 14px;
    line-height: 1;
}

.form-group {
    margin-bottom: 0;
}

.form-group label,
.control-label {
    display: none;
}

.form-control {
    width: 100%;
    min-height: 46px;
    border-radius: 12px !important;
    border: 1px solid #cbd5e1 !important;
    background-color: #ffffff !important;
    color: #0f172a !important;
    font-size: 14px;
    font-weight: 700;
    padding: 10px 13px;
    box-shadow: none !important;
    transition: all .2s ease;
}

.form-control:focus {
    border-color: #f59e0b !important;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.14) !important;
}

.form-control[readonly] {
    background: #f8fafc !important;
    color: #334155 !important;
    cursor: not-allowed;
}

.help-block,
.invalid-feedback {
    margin-top: 7px;
    font-size: 12px;
    font-weight: 700;
    color: #dc2626;
}

.has-error .form-control {
    border-color: #fca5a5 !important;
}

/* Custom English file input */
.custom-file-native {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    opacity: 0 !important;
    pointer-events: none !important;
}

.custom-file-box {
    display: flex;
    align-items: center;
    gap: 14px;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 14px;
    padding: 14px;
}

.custom-file-button {
    min-height: 42px;
    border-radius: 12px;
    padding: 10px 16px;
    background: #f59e0b;
    color: #ffffff !important;
    font-size: 13px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    margin: 0;
    white-space: nowrap;
    box-shadow: 0 8px 18px rgba(245, 158, 11, 0.22);
    transition: all .2s ease;
}

.custom-file-button:hover {
    background: #d97706;
    transform: translateY(-1px);
}

.custom-file-info {
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.custom-file-name {
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.custom-file-hint {
    margin-top: 3px;
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
}

.readonly-box {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #ffffff;
    border: 1px solid #e5eaf3;
    border-radius: 14px;
    padding: 13px;
}

.readonly-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 12px;
    background: #fff7ed;
    color: #c2410c;
    display: flex;
    align-items: center;
    justify-content: center;
}

.readonly-icon i {
    font-size: 20px;
}

.readonly-content {
    min-width: 0;
    width: 100%;
}

.form-actions {
    margin-top: 20px;
    padding-top: 18px;
    border-top: 1px solid #e5eaf3;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-form-action {
    min-height: 44px;
    border-radius: 12px;
    padding: 11px 18px;
    font-size: 13px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    border: 1px solid rgba(15, 23, 42, .12);
    transition: all .2s ease;
}

/* Warning update button */
.btn-update {
    background: #f59e0b;
    color: #ffffff !important;
    box-shadow: 0 8px 18px rgba(245, 158, 11, 0.24);
}

.btn-update:hover {
    background: #d97706;
    color: #ffffff !important;
    transform: translateY(-1px);
    text-decoration: none;
}

.btn-update:disabled {
    opacity: .55;
    cursor: not-allowed;
    transform: none;
}

.btn-cancel {
    background: #64748b;
    color: #ffffff !important;
    box-shadow: 0 8px 18px rgba(100, 116, 139, 0.18);
}

.btn-cancel:hover {
    background: #475569;
    color: #ffffff !important;
    transform: translateY(-1px);
    text-decoration: none;
}

.alert {
    border-radius: 14px;
    border: none;
    font-weight: 800;
    padding: 14px 16px;
    margin-bottom: 18px;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.helper-list {
    display: grid;
    gap: 12px;
}

.helper-item {
    background: #f8fafc;
    border: 1px solid #e5eaf3;
    border-radius: 14px;
    padding: 14px;
}

.helper-label {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
    margin-bottom: 5px;
}

.helper-text {
    color: #64748b;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.5;
    margin: 0;
}

/* SweetAlert2 warning design */
.swal-update-popup {
    width: 380px !important;
    border-radius: 18px !important;
    padding: 22px !important;
}

.swal-update-title {
    font-size: 20px !important;
    font-weight: 900 !important;
    color: #0f172a !important;
}

.swal-update-html {
    font-size: 14px !important;
    font-weight: 700 !important;
    color: #64748b !important;
}

.swal-update-confirm {
    border-radius: 10px !important;
    padding: 10px 18px !important;
    font-size: 13px !important;
    font-weight: 900 !important;
    background: #f59e0b !important;
    color: #ffffff !important;
    box-shadow: 0 8px 18px rgba(245, 158, 11, 0.25) !important;
}

.swal-update-confirm:hover {
    background: #d97706 !important;
}

.swal-update-cancel {
    border-radius: 10px !important;
    padding: 10px 18px !important;
    font-size: 13px !important;
    font-weight: 900 !important;
    background: #64748b !important;
}

.swal2-actions {
    gap: 12px !important;
}

@media (max-width: 992px) {
    .create-page {
        padding: 14px;
    }

    .page-header-card {
        padding: 18px;
    }

    .dash-title {
        font-size: 23px;
    }

    .form-layout {
        grid-template-columns: 1fr;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .create-page {
        padding: 10px;
    }

    .page-header-card,
    .form-card,
    .helper-card {
        border-radius: 14px;
        padding: 16px;
    }

    .header-actions {
        width: 100%;
    }

    .btn-page-action,
    .btn-form-action {
        width: 100%;
    }

    .form-actions {
        justify-content: stretch;
    }

    .custom-file-box,
    .readonly-box {
        flex-direction: column;
        align-items: stretch;
    }

    .custom-file-button {
        width: 100%;
    }
}
CSS);

/**
 * Register JavaScript.
 */
$this->registerJs(<<<JS
function toggleSubmit() {
    if ($('#certificate-file').val()) {
        $('#submit-btn').prop('disabled', false);
    } else {
        $('#submit-btn').prop('disabled', true);
    }
}

function updateFilePreview() {
    const fileInput = document.getElementById('certificate-file');
    const fileNameLabel = document.getElementById('selected-file-name');

    if (!fileInput || !fileNameLabel) {
        return;
    }

    if (fileInput.files && fileInput.files.length > 0) {
        fileNameLabel.textContent = fileInput.files[0].name;
        fileNameLabel.title = fileInput.files[0].name;
    } else {
        fileNameLabel.textContent = 'No file selected';
        fileNameLabel.title = '';
    }
}

$(document).ready(function() {
    const form = $('#update-certificate-form');

    $('#certificate-file').on('change', function() {
        updateFilePreview();
        toggleSubmit();
    });

    form.on('beforeSubmit', function(e) {
        if (form.data('update-confirmed') === true) {
            return true;
        }

        e.preventDefault();

        if (!$('#certificate-file').val()) {
            Swal.fire({
                title: 'Missing certificate file',
                html: 'Please choose a certificate file before updating.',
                icon: 'warning',
                confirmButtonText: '<i class="bi bi-check-circle"></i> Got it',
                customClass: {
                    popup: 'swal-update-popup',
                    title: 'swal-update-title',
                    htmlContainer: 'swal-update-html',
                    confirmButton: 'swal-update-confirm'
                }
            });

            return false;
        }

        Swal.fire({
            title: 'Confirm certificate update?',
            html: 'Please confirm that the selected certificate file is correct before saving changes.',
            icon: 'warning',
            showCancelButton: true,
            // FORM DIALOG ACTIONS: use certificate-specific confirmation labels.
            confirmButtonText: '<i class="bi bi-save"></i> Update certificate',
            cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                popup: 'swal-update-popup',
                title: 'swal-update-title',
                htmlContainer: 'swal-update-html',
                confirmButton: 'swal-update-confirm',
                cancelButton: 'swal-update-cancel'
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                form.data('update-confirmed', true);
                form.trigger('submit');
            }
        });

        return false;
    });

    updateFilePreview();
    toggleSubmit();
});
JS);
?>

<main class="dash-content create-page can-form-page">
    <div class="container-fluid">

        <!-- Page header -->
        <div class="page-header-card">
            <div>
                <h1 class="dash-title">
                    <span style="color: #f59e0b;">
                        <i class="bi bi-pencil-square"></i>
                    </span>
                    <?= Html::encode($this->title) ?>
                </h1>

                <div class="subtitle-text">
                    Update the certificate file linked to the selected National Aviation Authority.
                </div>
            </div>

            <div class="header-actions">
                <?= Html::a(
                    '<i class="bi bi-arrow-left-circle"></i> Back to Certificates',
                    $backUrl,
                    ['class' => 'btn-page-action btn-back']
                ) ?>
            </div>
        </div>

        <!-- Flash messages -->
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                <?= Html::encode(Yii::$app->session->getFlash('success')) ?>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i>
                <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
            </div>
        <?php endif; ?>

        <div class="form-layout">

            <!-- Main update form -->
            <div class="form-card">
                <h2 class="section-title">
                    <i class="bi bi-pencil-square text-warning"></i>
                    Certificate Information
                </h2>

                <?php $form = ActiveForm::begin([
                    'id' => 'update-certificate-form',
                    'options' => [
                        'enctype' => 'multipart/form-data',
                    ],
                ]); ?>

                <div class="form-grid">

                    <!-- Certificate file field -->
                    <div class="form-field-card full-width">
                        <div class="field-heading">
                            <i class="bi bi-cloud-arrow-up"></i>
                            Certificate File <span class="required-mark">*</span>
                        </div>

                        <!-- Hidden native file input -->
                        <?= $form->field($certificate, 'certificate')
                            ->label(false)
                            ->fileInput([
                                'id' => 'certificate-file',
                                'accept' => '.pdf,.doc,.docx',
                                'class' => 'custom-file-native',
                            ]) ?>

                        <!-- Custom English file input -->
                        <div class="custom-file-box">
                            <label for="certificate-file" class="custom-file-button">
                                <i class="bi bi-upload"></i>
                                Choose File
                            </label>

                            <div class="custom-file-info">
                                <span class="custom-file-name" id="selected-file-name">
                                    No file selected
                                </span>

                                <span class="custom-file-hint">
                                    Accepted formats: PDF, DOC, DOCX
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Read-only NAA field -->
                    <div class="form-field-card full-width">
                        <div class="field-heading">
                            <i class="bi bi-shield-check"></i>
                            NAA - National Aviation Authority <span class="required-mark">*</span>
                        </div>

                        <div class="readonly-box">
                            <div class="readonly-icon">
                                <i class="bi bi-shield-check"></i>
                            </div>

                            <div class="readonly-content">
                                <input type="text"
                                       class="form-control"
                                       value="<?= Html::encode($naaType) ?>"
                                       readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden certificate type ID -->
                    <?= $form->field($certificate, 'certificate_type_id')
                        ->hiddenInput()
                        ->label(false) ?>

                    <!-- Hidden MRO ID -->
                    <?= $form->field($certificate, 'mro_id')
                        ->hiddenInput(['value' => $mroId])
                        ->label(false) ?>

                </div>

                <!-- Form actions -->
                <div class="form-actions">
                    <?= Html::a(
                        '<i class="bi bi-x-circle"></i> Cancel',
                        ['index'],
                        ['class' => 'btn-form-action btn-cancel']
                    ) ?>

                    <?= Html::submitButton(
                        '<i class="bi bi-check-circle"></i> Update Certificate',
                        [
                            'class' => 'btn-form-action btn-update',
                            'id' => 'submit-btn',
                            'disabled' => true,
                        ]
                    ) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>

            <!-- Form guide -->
            <div class="helper-card">
                <h2 class="section-title">
                    <i class="bi bi-info-circle text-warning"></i>
                    Update Guide
                </h2>

                <div class="helper-list">

                    <!-- Certificate file help -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-file-earmark-text"></i>
                            Certificate File
                        </div>
                        <p class="helper-text">
                            Choose the new certificate document before updating this record.
                        </p>
                    </div>

                    <!-- NAA help -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-shield-check"></i>
                            NAA
                        </div>
                        <p class="helper-text">
                            The National Aviation Authority is read-only and cannot be changed from this page.
                        </p>
                    </div>

                    <!-- Required file help -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-exclamation-triangle"></i>
                            Required File
                        </div>
                        <p class="helper-text">
                            The update button is enabled only after selecting a certificate file.
                        </p>
                    </div>

                    <!-- Confirmation help -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-check-circle"></i>
                            Confirmation
                        </div>
                        <p class="helper-text">
                            A confirmation popup appears before saving the updated certificate.
                        </p>
                    </div>

                </div>
            </div>

        </div>

    </div>
</main>
