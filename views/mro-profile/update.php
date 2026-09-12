<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\View;

/** @var yii\web\View $this */
/** @var app\models\MroProfile $mroProfile */
/** @var array $countries */
/** @var array $cities */

/**
 * Page title.
 */
$this->title = 'Update MRO Profile';

/**
 * Prepare profile data safely.
 */
$username = $mroProfile->username ?? 'N/A';
$email = $mroProfile->email ?? 'N/A';
$companyName = $mroProfile->company_name ?? 'N/A';
$contactNumber = $mroProfile->contact_number ?? 'N/A';
$website = $mroProfile->website ?? 'N/A';

/**
 * Build file URLs safely for current photos and references.
 */
$buildFileUrl = static function ($file, $defaultFolder = '') {
    if (empty($file)) {
        return null;
    }

    $file = trim((string) $file);

    if ($file === '') {
        return null;
    }

    if (preg_match('/^https?:\/\//i', $file)) {
        return $file;
    }

    if (strpos($file, '@web/') === 0) {
        return Yii::getAlias('@web') . '/' . ltrim(substr($file, 5), '/');
    }

    if (strpos($file, '/') !== false) {
        return Yii::$app->request->baseUrl . '/' . ltrim($file, '/');
    }

    return Yii::$app->request->baseUrl . '/' . trim($defaultFolder, '/') . '/' . ltrim($file, '/');
};

/**
 * Prepare current media safely.
 */
$currentProfilePhoto = $mroProfile->profile_photo ?? null;
$currentProfilePhotoName = !empty($currentProfilePhoto) ? basename((string) $currentProfilePhoto) : 'No current profile photo';
$currentProfilePhotoUrl = !empty($currentProfilePhoto) ? $buildFileUrl($currentProfilePhoto) : null;

$currentCompanyPhoto = $mroProfile->company_photo ?? null;
$currentCompanyPhotoName = !empty($currentCompanyPhoto) ? basename((string) $currentCompanyPhoto) : 'No current company photo';
$currentCompanyPhotoUrl = !empty($currentCompanyPhoto) ? $buildFileUrl($currentCompanyPhoto) : null;

/**
 * Normalize existing references for safe display.
 */
$existingReferences = $mroProfile->main_references ?? [];

if (is_string($existingReferences)) {
    $decodedReferences = json_decode($existingReferences, true);
    $existingReferences = is_array($decodedReferences) ? $decodedReferences : array_filter([$existingReferences]);
}

if (!is_array($existingReferences)) {
    $existingReferences = [];
}

/**
 * Prepare navigation URLs.
 */
$backUrl = Yii::$app->request->referrer ?: ['index'];
$cancelUrl = $backUrl;

/**
 * Same route and same city logic as the original MRO profile page.
 */
$cityUrl = Url::to(['mro-profile/cities-by-country']);
$selectedCityId = $mroProfile->city_id ?? '';

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
 * Register Select2 CSS and JS.
 * Select2 gives the Yii dropdowns an ng-select-like searchable UI without changing backend logic.
 */
$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
    ['position' => View::POS_HEAD]
);

$this->registerJsFile(
    'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
    ['depends' => [\yii\web\JqueryAsset::class]]
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
 * This follows the same design used by the Update AO Profile page.
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

.section-divider {
    margin-top: 20px;
    padding-top: 18px;
    border-top: 1px solid #e5eaf3;
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

.form-control,
.form-select {
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

.form-select {
    appearance: auto;
}

/* Select2 ng-select-like styling */
.select2-container {
    width: 100% !important;
}

.select2-container--default .select2-selection--single {
    width: 100%;
    min-height: 46px;
    border-radius: 12px !important;
    border: 1px solid #cbd5e1 !important;
    background-color: #ffffff !important;
    color: #0f172a !important;
    box-shadow: none !important;
    display: flex;
    align-items: center;
    transition: all .2s ease;
}

.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single {
    border-color: #f59e0b !important;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.14) !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #0f172a !important;
    font-size: 14px;
    font-weight: 700;
    line-height: 46px !important;
    padding-left: 13px;
    padding-right: 38px;
}

.select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #94a3b8 !important;
    font-weight: 700;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 46px !important;
    right: 10px !important;
}

.select2-dropdown {
    border: 1px solid #cbd5e1 !important;
    border-radius: 14px !important;
    overflow: hidden;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.14);
}

.select2-search--dropdown {
    padding: 10px;
}

.select2-container--default .select2-search--dropdown .select2-search__field {
    min-height: 40px;
    border-radius: 10px !important;
    border: 1px solid #cbd5e1 !important;
    padding: 8px 11px;
    font-size: 13px;
    font-weight: 700;
    outline: none !important;
}

.select2-container--default .select2-results__option {
    padding: 10px 13px;
    font-size: 13px;
    font-weight: 700;
}

.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background: #f59e0b !important;
    color: #ffffff !important;
}

.select2-container--default .select2-results__option--selected {
    background: #fff7ed !important;
    color: #9a3412 !important;
}

.select2-selection.is-invalid-field {
    border-color: #fca5a5 !important;
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.08) !important;
}

.select2-selection.is-valid-field {
    border-color: #86efac !important;
    box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.08) !important;
}

.form-control[readonly] {
    background: #f8fafc !important;
    color: #334155 !important;
    cursor: not-allowed;
}

.form-control:focus,
.form-select:focus {
    border-color: #f59e0b !important;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.14) !important;
}

.help-block,
.invalid-feedback {
    margin-top: 7px;
    font-size: 12px;
    font-weight: 700;
    color: #dc2626;
}

.has-error .form-control,
.has-error .form-select {
    border-color: #fca5a5 !important;
}

.form-control.is-invalid-field,
.form-select.is-invalid-field {
    border-color: #fca5a5 !important;
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.08) !important;
}

.form-control.is-valid-field,
.form-select.is-valid-field {
    border-color: #86efac !important;
    box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.08) !important;
}

/* Read-only info boxes */
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

.readonly-label {
    display: block;
    color: #64748b;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.readonly-value {
    display: block;
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
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
    border: none;
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

/* Current photo */
.current-file-box {
    margin-top: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    background: #ffffff;
    border: 1px solid #e5eaf3;
    border-radius: 14px;
    padding: 13px;
}

.current-file-icon {
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

.current-file-icon i {
    font-size: 20px;
}

.current-file-text {
    min-width: 0;
    flex: 1;
}

.current-file-label {
    display: block;
    color: #64748b;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.current-file-name {
    display: block;
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.current-photo-thumb {
    width: 52px;
    height: 52px;
    min-width: 52px;
    border-radius: 14px;
    object-fit: cover;
    border: 1px solid #e5eaf3;
    background: #f8fafc;
}

.current-photo-thumb.is-clickable {
    cursor: pointer;
    transition: all .2s ease;
}

.current-photo-thumb.is-clickable:hover {
    transform: scale(1.04);
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.16);
}

.photo-click-hint {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-top: 4px;
    color: #f59e0b;
    font-size: 11px;
    font-weight: 900;
}

.photo-preview-wrapper {
    display: none;
    margin-top: 12px;
}

.photo-preview-wrapper img {
    width: 110px;
    height: 110px;
    object-fit: cover;
    border-radius: 16px;
    border: 1px solid #e5eaf3;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    background: #f8fafc;
}

/* Actions */
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

.btn-update {
    background: #f59e0b;
    color: #ffffff !important;
    box-shadow: 0 8px 18px rgba(245, 158, 11, 0.24);
}

.btn-update:hover:not(:disabled) {
    background: #d97706;
    color: #ffffff !important;
    transform: translateY(-1px);
    text-decoration: none;
}

.btn-update:disabled,
.btn-update.is-disabled {
    opacity: .55;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
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

.profile-submit-status {
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 16px;
    font-size: 12px;
    font-weight: 800;
    color: #64748b;
}

.profile-submit-status.is-ready {
    color: #166534;
}

.profile-submit-status.is-blocked {
    color: #991b1b;
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

/* Helper */
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

.helper-label i {
    color: #f59e0b;
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

/* Profile photo popup */
.swal-photo-popup {
    width: 430px !important;
    border-radius: 18px !important;
    padding: 20px !important;
}

.swal-photo-popup .swal2-image {
    max-width: 100% !important;
    max-height: 62vh !important;
    object-fit: contain !important;
    border-radius: 16px !important;
    border: 1px solid #e5eaf3 !important;
    margin: 12px auto 0 !important;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12) !important;
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
    .readonly-box,
    .current-file-box {
        flex-direction: column;
        align-items: stretch;
    }

    .custom-file-button {
        width: 100%;
    }

    .current-file-name,
    .readonly-value {
        white-space: normal;
    }

    .profile-submit-status {
        justify-content: flex-start;
    }
}

/* MRO references list */
.mro-files-list {
    display: none;
    margin-top: 12px;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 14px;
    padding: 14px;
}

.mro-files-list-header {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
    margin-bottom: 8px;
}

.mro-files-list ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.mro-file-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 9px 0;
    border-bottom: 1px dashed #e5eaf3;
}

.mro-file-row:last-child {
    border-bottom: none;
}

.mro-file-main {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.mro-file-dot {
    width: 8px;
    height: 8px;
    min-width: 8px;
    border-radius: 999px;
    background: #f59e0b;
}

.mro-file-name {
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.mro-file-size,
.mro-files-total {
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    margin-top: 3px;
}

.mro-file-remove-btn {
    border: 1px solid #fecaca;
    background: #fff5f5;
    color: #dc2626 !important;
    border-radius: 10px;
    padding: 7px 11px;
    font-size: 12px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    transition: all .2s ease;
    white-space: nowrap;
}

.mro-file-remove-btn:hover {
    background: #dc2626;
    color: #ffffff !important;
    transform: translateY(-1px);
}

.mro-files-error {
    display: none;
    margin-top: 10px;
    border-radius: 12px;
    border: 1px solid #fecaca;
    background: #fee2e2;
    color: #991b1b;
    padding: 10px 12px;
    font-size: 12px;
    font-weight: 800;
}

.existing-files-list {
    margin: 10px 0 0;
    padding-left: 0;
    list-style: none;
    display: grid;
    gap: 8px;
}

.existing-files-list li a {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #c2410c !important;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    border-radius: 12px;
    padding: 9px 12px;
    font-size: 13px;
    font-weight: 900;
    text-decoration: none;
    max-width: 100%;
}

.existing-files-list li a:hover {
    color: #9a3412 !important;
    text-decoration: underline;
}

@media (max-width: 576px) {
    .mro-file-row {
        flex-direction: column;
        align-items: stretch;
    }

    .mro-file-remove-btn {
        justify-content: center;
        width: 100%;
    }
}

CSS);

/**
 * Register page JavaScript.
 */
$this->registerJs(<<<JS
function bindImagePreview(inputId, imgId, wrapperId) {
    const input = document.getElementById(inputId);
    const img = document.getElementById(imgId);
    const wrap = document.getElementById(wrapperId);

    if (!input || !img || !wrap) {
        return;
    }

    input.addEventListener('change', function() {
        const file = this.files && this.files[0] ? this.files[0] : null;

        if (!file || !file.type.startsWith('image/')) {
            wrap.style.display = 'none';
            img.src = '';
            return;
        }

        const reader = new FileReader();

        reader.onload = function(e) {
            img.src = e.target.result;
            img.classList.add('is-clickable');
            img.setAttribute('title', 'Click to preview');
            wrap.style.display = 'block';
        };

        reader.readAsDataURL(file);
    });
}

function initNgSelectFields() {
    $('.ng-select-field').each(function() {
        const select = $(this);

        if (select.hasClass('select2-hidden-accessible')) {
            select.select2('destroy');
        }

        select.select2({
            width: '100%',
            allowClear: true,
            placeholder: select.data('placeholder') || select.find('option:first').text() || 'Select option',
            dropdownParent: select.closest('.form-field-card')
        });
    });
}

function syncNgSelectValidationState(field, isValid, showState) {
    if (!field.hasClass('select2-hidden-accessible')) {
        return;
    }

    const selection = field.next('.select2-container').find('.select2-selection');

    if (!selection.length) {
        return;
    }

    if (!showState) {
        selection.removeClass('is-invalid-field is-valid-field');
        return;
    }

    selection.toggleClass('is-invalid-field', !isValid);
    selection.toggleClass('is-valid-field', isValid);
}

function openPhotoPopup(photoSrc, title) {
    if (!photoSrc) {
        return;
    }

    Swal.fire({
        title: title || 'Photo preview',
        imageUrl: photoSrc,
        imageAlt: title || 'Photo preview',
        confirmButtonText: '<i class="bi bi-x-circle"></i> Close',
        customClass: {
            popup: 'swal-photo-popup',
            title: 'swal-update-title',
            confirmButton: 'swal-update-confirm'
        }
    });
}

function updateSingleFileName(inputId, labelId, emptyText) {
    const input = document.getElementById(inputId);
    const label = document.getElementById(labelId);

    if (!input || !label) {
        return;
    }

    if (input.files && input.files.length > 0) {
        label.textContent = input.files[0].name;
        label.title = input.files[0].name;
    } else {
        label.textContent = emptyText;
        label.title = '';
    }
}

function bindFileTrigger(inputId, triggerId, labelId, emptyText) {
    const input = document.getElementById(inputId);
    const trigger = document.getElementById(triggerId);

    if (!input || !trigger) {
        return;
    }

    trigger.addEventListener('click', function() {
        input.click();
    });

    input.addEventListener('change', function() {
        updateSingleFileName(inputId, labelId, emptyText);
    });
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

const multipleInput = document.getElementById('main-references-input');
const filesList = document.getElementById('mro-multiple-files-list');
const filesError = document.getElementById('mro-multiple-files-error');
let selectedFiles = [];
const MAX_TOTAL_BYTES = 20 * 1024 * 1024;

function getSelectedReferencesSize() {
    let totalBytes = 0;

    for (let i = 0; i < selectedFiles.length; i++) {
        totalBytes += selectedFiles[i].size || 0;
    }

    return totalBytes;
}

function renderFilesList() {
    if (!filesList) {
        return;
    }

    const label = document.getElementById('main-references-filename');

    if (!selectedFiles.length) {
        filesList.innerHTML = '';
        filesList.style.display = 'none';

        if (filesError) {
            filesError.style.display = 'none';
            filesError.textContent = '';
        }

        if (label) {
            label.textContent = 'No files selected';
            label.title = '';
        }

        if (typeof checkProfileFormReady === 'function') {
            checkProfileFormReady(false);
        }

        return;
    }

    let totalBytes = 0;
    let html = '<div class="mro-files-list-header"><i class="bi bi-files"></i><span>Selected files</span></div><ul>';

    for (let i = 0; i < selectedFiles.length; i++) {
        const file = selectedFiles[i];
        const sizeKb = file.size ? Math.round(file.size / 1024) : 0;
        totalBytes += file.size || 0;

        html += '<li class="mro-file-row">';
        html += '<div class="mro-file-main"><span class="mro-file-dot"></span><div><div class="mro-file-name">' + escapeHtml(file.name) + '</div>';

        if (sizeKb) {
            html += '<div class="mro-file-size">' + sizeKb + ' KB</div>';
        }

        html += '</div></div>';
        html += '<button type="button" class="mro-file-remove-btn mro-remove-file" data-index="' + i + '"><i class="bi bi-x-circle"></i> Remove</button>';
        html += '</li>';
    }

    html += '</ul>';
    html += '<div class="mro-files-total">Total size: ' + (totalBytes / (1024 * 1024)).toFixed(2) + ' MB (max 20 MB)</div>';

    filesList.innerHTML = html;
    filesList.style.display = 'block';

    if (label) {
        label.textContent = selectedFiles.length === 1 ? selectedFiles[0].name : selectedFiles.length + ' files selected';
        label.title = label.textContent;
    }

    if (filesError) {
        if (totalBytes > MAX_TOTAL_BYTES) {
            filesError.textContent = 'Total size exceeds 20 MB. Please remove some files.';
            filesError.style.display = 'block';
        } else {
            filesError.textContent = '';
            filesError.style.display = 'none';
        }
    }

    if (typeof checkProfileFormReady === 'function') {
        checkProfileFormReady(false);
    }
}

function bindMultipleReferencesInput() {
    if (!multipleInput || !filesList) {
        return;
    }

    multipleInput.addEventListener('change', function() {
        selectedFiles = Array.prototype.slice.call(this.files || []);
        renderFilesList();
    });

    filesList.addEventListener('click', function(e) {
        const target = e.target.closest('.mro-remove-file');

        if (!target) {
            return;
        }

        const index = parseInt(target.getAttribute('data-index'), 10);

        if (isNaN(index) || index < 0 || index >= selectedFiles.length) {
            return;
        }

        selectedFiles.splice(index, 1);

        if (typeof DataTransfer !== 'undefined') {
            const dataTransfer = new DataTransfer();

            for (let i = 0; i < selectedFiles.length; i++) {
                dataTransfer.items.add(selectedFiles[i]);
            }

            multipleInput.files = dataTransfer.files;
        }

        renderFilesList();
    });
}

function loadCities(countryId, selectedCityId = null) {
    const citySelect = $('#city-select');

    citySelect.html('<option value="">Loading...</option>');

    if (!countryId) {
        citySelect.html('<option value="">Select City</option>');
        initNgSelectFields();
        citySelect.trigger('change.select2');

        if (typeof checkProfileFormReady === 'function') {
            checkProfileFormReady(false);
        }

        return;
    }

    $.ajax({
        url: '{$cityUrl}',
        type: 'GET',
        dataType: 'json',
        data: {
            country_id: countryId
        },
        success: function(data) {
            citySelect.empty();
            citySelect.append('<option value="">Select City</option>');

            $.each(data, function(index, city) {
                const cityId = city.id || city.city_id;
                const cityName = city.name || city.city_name;
                const selected = cityId == selectedCityId ? 'selected' : '';

                citySelect.append(
                    '<option value="' + cityId + '" ' + selected + '>' + cityName + '</option>'
                );
            });

            initNgSelectFields();
            citySelect.trigger('change.select2');

            if (typeof checkProfileFormReady === 'function') {
                checkProfileFormReady(false);
            }
        },
        error: function(xhr) {
            console.log(xhr.responseText);
            citySelect.html('<option value="">Error loading cities</option>');
            initNgSelectFields();
            citySelect.trigger('change.select2');

            if (typeof checkProfileFormReady === 'function') {
                checkProfileFormReady(false);
            }
        }
    });
}

function getProfileField(attributeName) {
    return $('#mro-profile-form').find('[name$="[' + attributeName + ']"]').first();
}

function getTrimmedValue(attributeName) {
    const field = getProfileField(attributeName);
    return field.length ? $.trim(field.val()) : '';
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function markField(attributeName, isValid, showState) {
    const field = getProfileField(attributeName);

    if (!field.length) {
        return;
    }

    if (!showState) {
        field.removeClass('is-invalid-field is-valid-field');
        syncNgSelectValidationState(field, isValid, showState);
        return;
    }

    field.toggleClass('is-invalid-field', !isValid);
    field.toggleClass('is-valid-field', isValid);
    syncNgSelectValidationState(field, isValid, showState);
}

function checkProfileFormReady(showState = true) {
    const username = getTrimmedValue('username');
    const email = getTrimmedValue('email');
    const country = getTrimmedValue('country_id');
    const city = getTrimmedValue('city_id');
    const address = getTrimmedValue('address');
    const zipCode = getTrimmedValue('zip_code');
    const referencesSizeOk = getSelectedReferencesSize() <= MAX_TOTAL_BYTES;

    const usernameOk = username.length > 0;
    const emailOk = email.length > 0 && isValidEmail(email);
    const countryOk = country.length > 0;
    const cityOk = city.length > 0;
    const addressOk = address.length > 0;
    const zipCodeOk = zipCode.length > 0;

    markField('username', usernameOk, showState);
    markField('email', emailOk, showState);
    markField('country_id', countryOk, showState);
    markField('city_id', cityOk, showState);
    markField('address', addressOk, showState);
    markField('zip_code', zipCodeOk, showState);

    const isReady = usernameOk && emailOk && countryOk && cityOk && addressOk && zipCodeOk && referencesSizeOk;
    const submitButton = $('#profile-submit-btn');
    const status = $('#profile-submit-status');

    submitButton.prop('disabled', !isReady);
    submitButton.toggleClass('is-disabled', !isReady);

    if (status.length) {
        status.removeClass('is-ready is-blocked');

        if (isReady) {
            status.addClass('is-ready');
            status.html('<i class="bi bi-check-circle"></i><span>Form is valid. You can submit the update.</span>');
        } else if (!referencesSizeOk) {
            status.addClass('is-blocked');
            status.html('<i class="bi bi-exclamation-triangle"></i><span>Total references size exceeds 20 MB.</span>');
        } else {
            status.addClass('is-blocked');
            status.html('<i class="bi bi-exclamation-triangle"></i><span>Please complete all required fields to enable submit.</span>');
        }
    }

    return isReady;
}

$(document).ready(function() {
    const form = $('#mro-profile-form');

    initNgSelectFields();

    bindImagePreview('profile-photo-input', 'profile-photo-preview', 'profile-photo-preview-wrapper');
    bindImagePreview('company-photo-input', 'company-photo-preview', 'company-photo-preview-wrapper');

    bindFileTrigger('profile-photo-input', 'profile-photo-trigger', 'profile-photo-filename', 'No new profile photo selected');
    bindFileTrigger('company-photo-input', 'company-photo-trigger', 'company-photo-filename', 'No new company photo selected');
    bindFileTrigger('main-references-input', 'main-references-trigger', 'main-references-filename', 'No files selected');
    bindMultipleReferencesInput();

    $(document).on('click', '.js-photo-popup', function() {
        openPhotoPopup($(this).attr('src'), $(this).data('photo-title') || 'Photo preview');
    });

    $('#country-select').on('change', function() {
        loadCities($(this).val());
        checkProfileFormReady(true);
    });

    if ($('#country-select').val()) {
        loadCities($('#country-select').val(), '{$selectedCityId}');
    }

    form.on('input change blur', 'input, select, textarea', function() {
        checkProfileFormReady(true);
    });

    form.on('beforeSubmit', function(e) {
        if (form.data('update-confirmed') === true) {
            return true;
        }

        e.preventDefault();

        if (!checkProfileFormReady(true)) {
            Swal.fire({
                title: 'Missing required information',
                html: 'Please complete all required fields before updating the MRO profile.',
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
            title: 'Confirm MRO profile update?',
            html: 'Please confirm that the MRO profile information is correct before saving changes.',
            icon: 'warning',
            showCancelButton: true,
            // FORM DIALOG ACTIONS: use explicit, icon-led choices instead of Yes/No wording.
            confirmButtonText: '<i class="bi bi-save"></i> Update profile',
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

    updateSingleFileName('profile-photo-input', 'profile-photo-filename', 'No new profile photo selected');
    updateSingleFileName('company-photo-input', 'company-photo-filename', 'No new company photo selected');
    checkProfileFormReady(false);
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
                    Update the Maintenance, Repair and Overhaul profile information using the same clean AO profile design.
                </div>
            </div>

            <div class="header-actions">
                <?= Html::a(
                    '<i class="bi bi-arrow-left-circle"></i> Back to Profiles',
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

        <?php if (Yii::$app->session->hasFlash('message')): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                <?= Yii::$app->session->getFlash('message') ?>
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
                    <i class="bi bi-tools text-warning"></i>
                    MRO Profile Information
                </h2>

                <?php $form = ActiveForm::begin([
                    'id' => 'mro-profile-form',
                    'options' => [
                        'enctype' => 'multipart/form-data',
                    ],
                ]); ?>

                <!-- Account details section -->
                <div class="form-grid">

                    <!-- Username -->
                    <div class="form-field-card">
                        <div class="field-heading">
                            <i class="bi bi-person"></i>
                            Username
                            <span class="required-mark">*</span>
                        </div>
                        <?= $form->field($mroProfile, 'username')->textInput([
                            'class' => 'form-control',
                            'placeholder' => 'Enter username',
                        ])->label(false) ?>
                    </div>

                    <!-- Email -->
                    <div class="form-field-card">
                        <div class="field-heading">
                            <i class="bi bi-envelope"></i>
                            Email
                            <span class="required-mark">*</span>
                        </div>
                        <?= $form->field($mroProfile, 'email')->textInput([
                            'class' => 'form-control',
                            'placeholder' => 'Enter email address',
                        ])->label(false) ?>
                    </div>

                    <!-- First name -->
                    <div class="form-field-card">
                        <div class="field-heading">
                            <i class="bi bi-person-lines-fill"></i>
                            First Name
                        </div>
                        <?= $form->field($mroProfile, 'first_name')->textInput([
                            'class' => 'form-control',
                            'placeholder' => 'Enter first name',
                        ])->label(false) ?>
                    </div>

                    <!-- Last name -->
                    <div class="form-field-card">
                        <div class="field-heading">
                            <i class="bi bi-person-lines-fill"></i>
                            Last Name
                        </div>
                        <?= $form->field($mroProfile, 'last_name')->textInput([
                            'class' => 'form-control',
                            'placeholder' => 'Enter last name',
                        ])->label(false) ?>
                    </div>
                </div>

                <!-- Location section -->
                <h2 class="section-title section-divider">
                    <i class="bi bi-geo-alt text-warning"></i>
                    Location
                </h2>

                <div class="form-grid">

                    <!-- Country -->
                    <div class="form-field-card">
                        <div class="field-heading">
                            <i class="bi bi-globe"></i>
                            Country
                            <span class="required-mark">*</span>
                        </div>
                        <?= $form->field($mroProfile, 'country_id')->dropDownList(
                            ArrayHelper::map($countries, 'country_id', 'country_name'),
                            [
                                'prompt' => 'Select Country',
                                'id' => 'country-select',
                                'class' => 'form-select ng-select-field',
                                'data-placeholder' => 'Select Country',
                            ]
                        )->label(false) ?>
                    </div>

                    <!-- City -->
                    <div class="form-field-card">
                        <div class="field-heading">
                            <i class="bi bi-buildings"></i>
                            City
                            <span class="required-mark">*</span>
                        </div>
                        <?= $form->field($mroProfile, 'city_id')->dropDownList(
                            ArrayHelper::map($cities, 'city_id', 'city_name'),
                            [
                                'prompt' => 'Select City',
                                'id' => 'city-select',
                                'class' => 'form-select ng-select-field',
                                'data-placeholder' => 'Select City',
                            ]
                        )->label(false) ?>
                    </div>

                    <!-- Address -->
                    <div class="form-field-card full-width">
                        <div class="field-heading">
                            <i class="bi bi-house-door"></i>
                            Address
                            <span class="required-mark">*</span>
                        </div>
                        <?= $form->field($mroProfile, 'address')->textInput([
                            'maxlength' => true,
                            'class' => 'form-control',
                            'id' => 'address',
                            'placeholder' => 'Enter address',
                        ])->label(false) ?>
                    </div>

                    <!-- Zip code -->
                    <div class="form-field-card">
                        <div class="field-heading">
                            <i class="bi bi-mailbox"></i>
                            Zip Code
                            <span class="required-mark">*</span>
                        </div>
                        <?= $form->field($mroProfile, 'zip_code')->textInput([
                            'maxlength' => true,
                            'class' => 'form-control',
                            'id' => 'zip_code',
                            'placeholder' => 'Enter zip code',
                        ])->label(false) ?>
                    </div>
                </div>

                <!-- Contact and company section -->
                <h2 class="section-title section-divider">
                    <i class="bi bi-building text-warning"></i>
                    Contact & Company
                </h2>

                <div class="form-grid">

                    <!-- Contact number -->
                    <div class="form-field-card">
                        <div class="field-heading">
                            <i class="bi bi-telephone"></i>
                            Contact Number
                        </div>
                        <?= $form->field($mroProfile, 'contact_number')->textInput([
                            'class' => 'form-control',
                            'placeholder' => 'Enter contact number',
                        ])->label(false) ?>
                    </div>

                    <!-- Company name -->
                    <div class="form-field-card">
                        <div class="field-heading">
                            <i class="bi bi-building-check"></i>
                            Company Name
                        </div>
                        <?= $form->field($mroProfile, 'company_name')->textInput([
                            'class' => 'form-control',
                            'placeholder' => 'Enter company name',
                        ])->label(false) ?>
                    </div>

                    <!-- Website -->
                    <div class="form-field-card">
                        <div class="field-heading">
                            <i class="bi bi-link-45deg"></i>
                            Website
                        </div>
                        <?= $form->field($mroProfile, 'website')->textInput([
                            'class' => 'form-control',
                            'placeholder' => 'https://example.com',
                        ])->label(false) ?>
                    </div>

                    <!-- Social media / video link -->
                    <div class="form-field-card">
                        <div class="field-heading">
                            <i class="bi bi-youtube"></i>
                            Social Media / Video Link
                        </div>
                        <?= $form->field($mroProfile, 'youtube_video')->textInput([
                            'class' => 'form-control',
                            'placeholder' => 'Paste YouTube or social media link',
                        ])->label(false) ?>
                    </div>
                </div>

                <!-- Media and references section -->
                <h2 class="section-title section-divider">
                    <i class="bi bi-images text-warning"></i>
                    Media & References
                </h2>

                <div class="form-grid">

                    <!-- Profile photo upload -->
                    <div class="form-field-card">
                        <div class="field-heading">
                            <i class="bi bi-person-bounding-box"></i>
                            Profile Photo
                        </div>

                        <?= $form->field($mroProfile, 'profile_photo')->fileInput([
                            'class' => 'custom-file-native',
                            'id' => 'profile-photo-input',
                            'accept' => 'image/*',
                        ])->label(false) ?>

                        <div class="custom-file-box">
                            <button type="button" id="profile-photo-trigger" class="custom-file-button">
                                <i class="bi bi-cloud-arrow-up"></i>
                                Choose file
                            </button>

                            <div class="custom-file-info">
                                <span id="profile-photo-filename" class="custom-file-name">
                                    No new profile photo selected
                                </span>
                                <span class="custom-file-hint">
                                    Accepted files: JPG, PNG or WEBP image.
                                </span>
                            </div>
                        </div>

                        <!-- New profile photo preview -->
                        <div id="profile-photo-preview-wrapper" class="photo-preview-wrapper">
                            <div class="current-file-box">
                                <div class="current-file-icon">
                                    <i class="bi bi-eye"></i>
                                </div>
                                <div class="current-file-text">
                                    <span class="current-file-label">New profile photo preview</span>
                                    <span class="current-file-name">Preview of the selected image</span>
                                    <span class="photo-click-hint"><i class="bi bi-zoom-in"></i> Click photo to open popup</span>
                                </div>
                                <img
                                    id="profile-photo-preview"
                                    src=""
                                    alt="Profile photo preview"
                                    class="current-photo-thumb js-photo-popup"
                                    data-photo-title="New profile photo preview"
                                >
                            </div>
                        </div>

                        <!-- Current profile photo -->
                        <?php if (!empty($currentProfilePhoto)): ?>
                            <div class="current-file-box">
                                <div class="current-file-icon">
                                    <i class="bi bi-image"></i>
                                </div>

                                <div class="current-file-text">
                                    <span class="current-file-label">Current profile photo</span>
                                    <span class="current-file-name" title="<?= Html::encode($currentProfilePhotoName) ?>">
                                        <?= Html::encode($currentProfilePhotoName) ?>
                                    </span>
                                    <?php if (!empty($currentProfilePhotoUrl)): ?>
                                        <span class="photo-click-hint"><i class="bi bi-zoom-in"></i> Click photo to open popup</span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($currentProfilePhotoUrl)): ?>
                                    <img
                                        src="<?= Html::encode($currentProfilePhotoUrl) ?>"
                                        alt="Current profile photo"
                                        class="current-photo-thumb is-clickable js-photo-popup"
                                        data-photo-title="Current profile photo"
                                        title="Click to preview"
                                    >
                                <?php endif; ?>

                                <?= Html::hiddenInput('profile_photo_existing', $currentProfilePhoto) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Company photo upload -->
                    <div class="form-field-card">
                        <div class="field-heading">
                            <i class="bi bi-building"></i>
                            Company Photo
                        </div>

                        <?= $form->field($mroProfile, 'company_photo')->fileInput([
                            'class' => 'custom-file-native',
                            'id' => 'company-photo-input',
                            'accept' => 'image/*',
                        ])->label(false) ?>

                        <div class="custom-file-box">
                            <button type="button" id="company-photo-trigger" class="custom-file-button">
                                <i class="bi bi-cloud-arrow-up"></i>
                                Choose file
                            </button>

                            <div class="custom-file-info">
                                <span id="company-photo-filename" class="custom-file-name">
                                    No new company photo selected
                                </span>
                                <span class="custom-file-hint">
                                    Accepted files: JPG, PNG or WEBP image.
                                </span>
                            </div>
                        </div>

                        <!-- New company photo preview -->
                        <div id="company-photo-preview-wrapper" class="photo-preview-wrapper">
                            <div class="current-file-box">
                                <div class="current-file-icon">
                                    <i class="bi bi-eye"></i>
                                </div>
                                <div class="current-file-text">
                                    <span class="current-file-label">New company photo preview</span>
                                    <span class="current-file-name">Preview of the selected image</span>
                                    <span class="photo-click-hint"><i class="bi bi-zoom-in"></i> Click photo to open popup</span>
                                </div>
                                <img
                                    id="company-photo-preview"
                                    src=""
                                    alt="Company photo preview"
                                    class="current-photo-thumb js-photo-popup"
                                    data-photo-title="New company photo preview"
                                >
                            </div>
                        </div>

                        <!-- Current company photo -->
                        <?php if (!empty($currentCompanyPhoto)): ?>
                            <div class="current-file-box">
                                <div class="current-file-icon">
                                    <i class="bi bi-image"></i>
                                </div>

                                <div class="current-file-text">
                                    <span class="current-file-label">Current company photo</span>
                                    <span class="current-file-name" title="<?= Html::encode($currentCompanyPhotoName) ?>">
                                        <?= Html::encode($currentCompanyPhotoName) ?>
                                    </span>
                                    <?php if (!empty($currentCompanyPhotoUrl)): ?>
                                        <span class="photo-click-hint"><i class="bi bi-zoom-in"></i> Click photo to open popup</span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($currentCompanyPhotoUrl)): ?>
                                    <img
                                        src="<?= Html::encode($currentCompanyPhotoUrl) ?>"
                                        alt="Current company photo"
                                        class="current-photo-thumb is-clickable js-photo-popup"
                                        data-photo-title="Current company photo"
                                        title="Click to preview"
                                    >
                                <?php endif; ?>

                                <?= Html::hiddenInput('company_photo_existing', $currentCompanyPhoto) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Main references upload -->
                    <div class="form-field-card full-width">
                        <div class="field-heading">
                            <i class="bi bi-files"></i>
                            Main References
                        </div>

                        <?= $form->field($mroProfile, 'main_references[]')->fileInput([
                            'class' => 'custom-file-native',
                            'multiple' => true,
                            'id' => 'main-references-input',
                        ])->label(false) ?>

                        <div class="custom-file-box">
                            <button type="button" id="main-references-trigger" class="custom-file-button">
                                <i class="bi bi-folder-plus"></i>
                                Select file(s)
                            </button>

                            <div class="custom-file-info">
                                <span id="main-references-filename" class="custom-file-name">
                                    No files selected
                                </span>
                                <span class="custom-file-hint">
                                    You can upload several reference files. Total selected size must not exceed 20 MB.
                                </span>
                            </div>
                        </div>

                        <div id="mro-multiple-files-list" class="mro-files-list"></div>
                        <div id="mro-multiple-files-error" class="mro-files-error"></div>

                        <?php if (!empty($existingReferences)): ?>
                            <div class="current-file-box">
                                <div class="current-file-icon">
                                    <i class="bi bi-paperclip"></i>
                                </div>

                                <div class="current-file-text">
                                    <span class="current-file-label">Existing main references</span>
                                    <ul class="existing-files-list">
                                        <?php foreach ($existingReferences as $reference): ?>
                                            <?php
                                                $referenceUrl = $buildFileUrl($reference);
                                                $referenceName = basename((string) $reference);
                                            ?>
                                            <li>
                                                <a href="<?= Html::encode($referenceUrl) ?>" target="_blank" rel="noopener">
                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                    <?= Html::encode($referenceName) ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Submit status -->
                <div id="profile-submit-status" class="profile-submit-status is-blocked">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>Please complete all required fields to enable submit.</span>
                </div>

                <!-- Form actions -->
                <div class="form-actions">
                    <?= Html::a(
                        '<i class="bi bi-x-circle"></i> Cancel',
                        $cancelUrl,
                        ['class' => 'btn-form-action btn-cancel']
                    ) ?>

                    <?= Html::submitButton(
                        '<i class="bi bi-check-circle"></i> Update MRO Profile',
                        [
                            'class' => 'btn-form-action btn-update is-disabled',
                            'id' => 'profile-submit-btn',
                            'disabled' => true,
                        ]
                    ) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>

            <!-- Helper and profile summary card -->
            <div class="helper-card">
                <h2 class="section-title">
                    <i class="bi bi-info-circle text-warning"></i>
                    Profile Summary
                </h2>

                <div class="helper-list">

                    <!-- Current username -->
                    <div class="readonly-box">
                        <div class="readonly-icon">
                            <i class="bi bi-person"></i>
                        </div>
                        <div class="readonly-content">
                            <span class="readonly-label">Username</span>
                            <span class="readonly-value" title="<?= Html::encode($username) ?>">
                                <?= Html::encode($username) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Current email -->
                    <div class="readonly-box">
                        <div class="readonly-icon">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div class="readonly-content">
                            <span class="readonly-label">Email</span>
                            <span class="readonly-value" title="<?= Html::encode($email) ?>">
                                <?= Html::encode($email) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Current company -->
                    <div class="readonly-box">
                        <div class="readonly-icon">
                            <i class="bi bi-building"></i>
                        </div>
                        <div class="readonly-content">
                            <span class="readonly-label">Company</span>
                            <span class="readonly-value" title="<?= Html::encode($companyName) ?>">
                                <?= Html::encode($companyName) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Current contact number -->
                    <div class="readonly-box">
                        <div class="readonly-icon">
                            <i class="bi bi-telephone"></i>
                        </div>
                        <div class="readonly-content">
                            <span class="readonly-label">Contact</span>
                            <span class="readonly-value" title="<?= Html::encode($contactNumber) ?>">
                                <?= Html::encode($contactNumber) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Current website -->
                    <div class="readonly-box">
                        <div class="readonly-icon">
                            <i class="bi bi-link-45deg"></i>
                        </div>
                        <div class="readonly-content">
                            <span class="readonly-label">Website</span>
                            <span class="readonly-value" title="<?= Html::encode($website) ?>">
                                <?= Html::encode($website) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Helper note -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-shield-check"></i>
                            Validation
                        </div>
                        <p class="helper-text">
                            Username, email, country, city, address and zip code are required before enabling the update button.
                        </p>
                    </div>

                    <!-- Helper note -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-images"></i>
                            Photos Popup
                        </div>
                        <p class="helper-text">
                            Click the profile photo or company photo preview to open it in a SweetAlert popup.
                        </p>
                    </div>

                    <!-- Helper note -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-files"></i>
                            References
                        </div>
                        <p class="helper-text">
                            Reference files keep the same multiple upload behavior, with a 20 MB total client-side limit.
                        </p>
                    </div>

                    <!-- Helper note -->
                    <div class="helper-item">
                        <div class="helper-label">
                            <i class="bi bi-check2-circle"></i>
                            Confirmation
                        </div>
                        <p class="helper-text">
                            A confirmation popup appears before saving, using the same style as the AO Profile update page.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
