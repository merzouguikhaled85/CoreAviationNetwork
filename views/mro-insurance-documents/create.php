<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Upload Insurance Documents';
$this->params['breadcrumbs'][] = ['label' => 'MRO Insurance Documents', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;


$inputId = 'insurance-file-input';

$this->registerCssFile(
    'https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css',
    ['position' => $this::POS_HEAD]
);

$this->registerCss(<<<CSS
:root {
  --deep-blue: #0D3261;
  --deep-blue-dark: #08264C;
  --sky: #00B2FF;
  --gold: #EED811;
  --soft-border: #D8E4EE;
  --soft-bg: #F4F7FC;
  --text-main: #0F172A;
  --text-muted: #6B7280;
  --danger: #EF1010;
  --success: #16A34A;
}

.insurance-upload-wrapper,
.insurance-upload-wrapper *,
.insurance-upload-wrapper *::before,
.insurance-upload-wrapper *::after {
  box-sizing: border-box;
}

.insurance-upload-wrapper {
  width: 100vw;
  max-width: 100vw;
  min-height: calc(100vh - 70px);
  margin-left: calc(50% - 50vw);
  margin-right: calc(50% - 50vw);
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 34px 24px;
  background:
    radial-gradient(circle at 14% 18%, rgba(13, 50, 97, 0.08) 0%, transparent 32%),
    radial-gradient(circle at 86% 18%, rgba(0, 178, 255, 0.10) 0%, transparent 34%),
    radial-gradient(circle at 50% 100%, rgba(238, 216, 17, 0.08) 0%, transparent 42%),
    linear-gradient(145deg, #EEF5FF 0%, #FFFFFF 48%, #F6FBFF 100%);
}

.insurance-upload-card {
  width: min(96vw, 1250px);
  background: rgba(255, 255, 255, 0.97);
  border: 1px solid rgba(216, 228, 238, 0.95);
  border-radius: 24px;
  box-shadow: 0 24px 70px rgba(15, 23, 42, 0.18);
  position: relative;
  overflow: hidden;
}

.insurance-upload-card::before {
  content: "";
  position: absolute;
  inset: 0 0 auto 0;
  height: 5px;
  background: linear-gradient(135deg, var(--deep-blue), var(--sky), var(--gold));
}

.insurance-upload-header {
  padding: 30px 34px 24px;
  background:
    linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(244, 248, 255, 0.98)),
    radial-gradient(circle at 100% 0%, rgba(0, 178, 255, 0.12), transparent 35%);
  border-bottom: 1px solid rgba(216, 228, 238, 0.9);
}

.insurance-upload-header-main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
}

.insurance-upload-title-box {
  display: flex;
  align-items: center;
  gap: 18px;
  min-width: 0;
}

.insurance-upload-icon {
  width: 62px;
  height: 62px;
  min-width: 62px;
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(145deg, #E7F4FF, #FFFFFF);
  box-shadow:
    0 14px 32px rgba(13, 50, 97, 0.16),
    inset 0 0 0 1px rgba(255, 255, 255, 0.85);
}

.insurance-upload-icon i {
  font-size: 31px;
  color: var(--deep-blue);
}

.insurance-upload-title-text {
  min-width: 0;
}

.insurance-upload-kicker {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 5px;
  color: var(--deep-blue);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.insurance-upload-title {
  margin: 0;
  color: var(--text-main);
  font-size: 26px;
  font-weight: 800;
  line-height: 1.22;
}

.insurance-upload-title .accent {
  color: var(--sky);
}

.insurance-upload-subtitle {
  margin: 6px 0 0;
  color: var(--text-muted);
  font-size: 14px;
  line-height: 1.5;
}

.insurance-back-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  flex: 0 0 auto;
  border-radius: 12px;
  padding: 11px 18px;
  border: 1px solid var(--deep-blue);
  color: var(--deep-blue);
  background: #FFFFFF;
  font-weight: 800;
  text-decoration: none;
  white-space: nowrap;
  box-shadow: 0 10px 24px rgba(13, 50, 97, 0.08);
  transition: all 0.2s ease;
}

.insurance-back-btn:hover,
.insurance-back-btn:focus {
  background: var(--deep-blue);
  color: #FFFFFF;
  border-color: var(--deep-blue);
  text-decoration: none;
  transform: translateY(-1px);
}

.insurance-upload-content {
  padding: 30px 34px 34px;
}

.insurance-error-summary {
  margin-bottom: 18px;
  padding: 15px 17px;
  border-radius: 14px;
  border: 1px solid #FECACA;
  background: #FEF2F2;
  color: #991B1B;
}

.insurance-error-summary ul {
  margin-bottom: 0;
}

.insurance-upload-form {
  margin: 0;
}

.insurance-form-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.45fr) minmax(300px, 0.55fr);
  gap: 22px;
  align-items: stretch;
}

.insurance-upload-panel,
.insurance-info-panel {
  border: 1px solid var(--soft-border);
  border-radius: 20px;
  background: #FFFFFF;
  box-shadow: 0 13px 34px rgba(15, 23, 42, 0.07);
}

.insurance-upload-panel {
  padding: 22px;
}

.insurance-section-title {
  margin: 0 0 6px;
  color: var(--text-main);
  font-size: 18px;
  font-weight: 800;
}

.insurance-section-subtitle {
  margin: 0 0 18px;
  color: var(--text-muted);
  font-size: 13px;
  line-height: 1.5;
}

.insurance-file-input {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  pointer-events: none;
}

.insurance-dropzone {
  min-height: 260px;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 28px;
  border: 2px dashed #B8D7EC;
  border-radius: 20px;
  background:
    radial-gradient(circle at 50% 0%, rgba(0, 178, 255, 0.09), transparent 45%),
    linear-gradient(145deg, #F8FBFF, #FFFFFF);
  cursor: pointer;
  transition: all 0.2s ease;
}

.insurance-dropzone:hover,
.insurance-dropzone.is-dragover {
  border-color: var(--sky);
  background:
    radial-gradient(circle at 50% 0%, rgba(0, 178, 255, 0.15), transparent 45%),
    linear-gradient(145deg, #F2FAFF, #FFFFFF);
  transform: translateY(-1px);
  box-shadow: 0 14px 32px rgba(13, 50, 97, 0.11);
}

.insurance-dropzone-inner {
  text-align: center;
  max-width: 520px;
}

.insurance-dropzone-icon {
  width: 74px;
  height: 74px;
  margin: 0 auto 16px;
  border-radius: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #E7F4FF;
  color: var(--deep-blue);
  box-shadow: 0 14px 30px rgba(13, 50, 97, 0.12);
}

.insurance-dropzone-icon i {
  font-size: 38px;
}

.insurance-dropzone-title {
  display: block;
  color: var(--text-main);
  font-size: 19px;
  font-weight: 900;
  margin-bottom: 7px;
}

.insurance-dropzone-text {
  display: block;
  color: var(--text-muted);
  font-size: 14px;
  line-height: 1.5;
  margin-bottom: 16px;
}

.insurance-browse-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  padding: 11px 18px;
  border-radius: 12px;
  background: var(--deep-blue);
  color: #FFFFFF;
  font-weight: 800;
  font-size: 14px;
  box-shadow: 0 14px 30px rgba(13, 50, 97, 0.18);
}

.insurance-dropzone-hint {
  display: block;
  margin-top: 14px;
  color: #8A95A5;
  font-size: 12px;
}

.insurance-field-error {
  margin-top: 10px;
  color: #B91C1C;
  font-size: 13px;
  font-weight: 700;
}

.insurance-selected-box {
  margin-top: 18px;
  border: 1px solid var(--soft-border);
  border-radius: 18px;
  background: #F9FBFF;
  overflow: hidden;
}

.insurance-selected-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 14px 16px;
  border-bottom: 1px solid var(--soft-border);
  background: #EEF6FF;
}

.insurance-selected-title {
  color: var(--deep-blue);
  font-size: 13px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.insurance-selected-meta {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--text-muted);
  font-size: 12px;
  font-weight: 700;
}

.insurance-clear-btn {
  border: none;
  background: transparent;
  color: var(--danger);
  font-size: 12px;
  font-weight: 900;
  padding: 4px 0;
  cursor: pointer;
}

.insurance-clear-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.insurance-selected-empty {
  padding: 20px 16px;
  color: var(--text-muted);
  font-size: 13px;
  text-align: center;
}

.insurance-file-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.insurance-file-item {
  display: grid;
  grid-template-columns: 38px minmax(0, 1fr) auto;
  gap: 12px;
  align-items: center;
  padding: 13px 16px;
  border-top: 1px solid #E5EDF5;
  background: #FFFFFF;
}

.insurance-file-item:first-child {
  border-top: none;
}

.insurance-file-icon {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #E7F4FF;
  color: var(--deep-blue);
}

.insurance-file-icon i {
  font-size: 20px;
}

.insurance-file-info {
  min-width: 0;
}

.insurance-file-name {
  display: block;
  color: var(--text-main);
  font-size: 14px;
  font-weight: 800;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.insurance-file-size {
  display: block;
  margin-top: 3px;
  color: var(--text-muted);
  font-size: 12px;
  font-weight: 600;
}

.insurance-remove-file {
  width: 34px;
  height: 34px;
  border: none;
  border-radius: 10px;
  background: #FEF2F2;
  color: var(--danger);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.insurance-remove-file i {
  font-size: 18px;
}

.insurance-info-panel {
  padding: 20px;
  background:
    linear-gradient(145deg, #FFFFFF, #F7FBFF);
}

.insurance-info-card {
  padding: 16px;
  border-radius: 16px;
  background: #FFFFFF;
  border: 1px solid #E5EDF5;
  margin-bottom: 14px;
}

.insurance-info-card:last-child {
  margin-bottom: 0;
}

.insurance-info-card-title {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0 0 10px;
  color: var(--deep-blue);
  font-size: 14px;
  font-weight: 900;
}

.insurance-info-card-title i {
  font-size: 19px;
}

.insurance-info-list {
  margin: 0;
  padding-left: 0;
  list-style: none;
}

.insurance-info-list li {
  display: flex;
  gap: 8px;
  color: var(--text-muted);
  font-size: 13px;
  line-height: 1.5;
  margin-bottom: 8px;
}

.insurance-info-list li:last-child {
  margin-bottom: 0;
}

.insurance-info-list i {
  color: var(--success);
  font-size: 16px;
  flex: 0 0 auto;
  margin-top: 2px;
}

.insurance-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 22px;
  border-top: 1px solid #E5EDF5;
}

.btn-insurance-cancel,
.btn-insurance-submit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  border-radius: 12px;
  padding: 12px 20px;
  font-weight: 900;
  text-decoration: none;
  transition: all 0.2s ease;
}

.btn-insurance-cancel {
  border: 1px solid var(--soft-border);
  color: var(--deep-blue);
  background: #FFFFFF;
}

.btn-insurance-cancel:hover,
.btn-insurance-cancel:focus {
  color: var(--deep-blue);
  background: #F4F8FF;
  text-decoration: none;
}

.btn-insurance-submit {
  border: 1px solid var(--deep-blue);
  background: var(--deep-blue);
  color: #FFFFFF;
  box-shadow: 0 14px 30px rgba(13, 50, 97, 0.18);
}

.btn-insurance-submit:hover,
.btn-insurance-submit:focus {
  background: var(--deep-blue-dark);
  border-color: var(--deep-blue-dark);
  color: #FFFFFF;
  transform: translateY(-1px);
}

@media (max-width: 992px) {
  .insurance-upload-wrapper {
    padding: 28px 16px;
  }

  .insurance-upload-card {
    width: 100%;
    border-radius: 20px;
  }

  .insurance-upload-header,
  .insurance-upload-content {
    padding-left: 22px;
    padding-right: 22px;
  }

  .insurance-upload-header-main {
    flex-direction: column;
    align-items: stretch;
  }

  .insurance-back-btn {
    width: 100%;
  }

  .insurance-form-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 576px) {
  .insurance-upload-wrapper {
    padding: 14px 10px;
  }

  .insurance-upload-card {
    border-radius: 16px;
  }

  .insurance-upload-header {
    padding: 20px 16px 16px;
  }

  .insurance-upload-content {
    padding: 18px 14px 22px;
  }

  .insurance-upload-title-box {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .insurance-upload-icon {
    width: 52px;
    height: 52px;
    min-width: 52px;
    border-radius: 15px;
  }

  .insurance-upload-icon i {
    font-size: 26px;
  }

  .insurance-upload-title {
    font-size: 20px;
  }

  .insurance-upload-subtitle {
    font-size: 13px;
  }

  .insurance-upload-panel,
  .insurance-info-panel {
    border-radius: 16px;
    padding: 16px;
  }

  .insurance-dropzone {
    min-height: 230px;
    padding: 22px 16px;
    border-radius: 16px;
  }

  .insurance-dropzone-icon {
    width: 62px;
    height: 62px;
    border-radius: 18px;
  }

  .insurance-dropzone-icon i {
    font-size: 31px;
  }

  .insurance-dropzone-title {
    font-size: 17px;
  }

  .insurance-file-item {
    grid-template-columns: 34px minmax(0, 1fr) auto;
    gap: 10px;
    padding: 12px;
  }

  .insurance-file-icon {
    width: 34px;
    height: 34px;
  }

  .insurance-file-name {
    font-size: 13px;
  }

  .insurance-selected-header {
    align-items: flex-start;
    flex-direction: column;
    gap: 8px;
  }

  .insurance-actions {
    flex-direction: column-reverse;
    align-items: stretch;
  }

  .btn-insurance-cancel,
  .btn-insurance-submit {
    width: 100%;
  }
}
CSS);

$this->registerJs(<<<JS
(function () {
  var input = document.getElementById('$inputId');
  var dropzone = document.querySelector('[data-insurance-dropzone]');
  var fileList = document.querySelector('[data-insurance-file-list]');
  var emptyState = document.querySelector('[data-insurance-empty]');
  var countText = document.querySelector('[data-insurance-count]');
  var sizeText = document.querySelector('[data-insurance-size]');
  var clearButton = document.querySelector('[data-insurance-clear]');

  if (!input || !dropzone || !fileList) {
    return;
  }

  var selectedFiles = [];

  function formatBytes(bytes) {
    if (bytes >= 1073741824) {
      return (bytes / 1073741824).toFixed(2) + ' GB';
    }

    if (bytes >= 1048576) {
      return (bytes / 1048576).toFixed(2) + ' MB';
    }

    if (bytes >= 1024) {
      return (bytes / 1024).toFixed(2) + ' KB';
    }

    return bytes + ' B';
  }

  function getIconClass(file) {
    var type = (file.type || '').toLowerCase();
    var name = (file.name || '').toLowerCase();

    if (type.indexOf('image') === 0 || /\.(jpg|jpeg|png|gif|webp|bmp|svg)$/.test(name)) {
      return 'ri-image-line';
    }

    if (type.indexOf('pdf') !== -1 || /\.pdf$/.test(name)) {
      return 'ri-file-pdf-2-line';
    }

    if (type.indexOf('word') !== -1 || /\.(doc|docx)$/.test(name)) {
      return 'ri-file-word-2-line';
    }

    if (type.indexOf('excel') !== -1 || type.indexOf('spreadsheet') !== -1 || /\.(xls|xlsx|csv)$/.test(name)) {
      return 'ri-file-excel-2-line';
    }

    if (/\.(zip|rar|7z)$/.test(name)) {
      return 'ri-folder-zip-line';
    }

    return 'ri-file-line';
  }

  function syncInputFiles() {
    if (typeof DataTransfer === 'undefined') {
      return;
    }

    var dataTransfer = new DataTransfer();

    selectedFiles.forEach(function (file) {
      dataTransfer.items.add(file);
    });

    input.files = dataTransfer.files;
  }

  function renderFiles() {
    var totalSize = 0;

    fileList.innerHTML = '';

    selectedFiles.forEach(function (file, index) {
      totalSize += file.size || 0;

      var item = document.createElement('li');
      item.className = 'insurance-file-item';

      var icon = document.createElement('span');
      icon.className = 'insurance-file-icon';
      icon.innerHTML = '<i class="' + getIconClass(file) + '"></i>';

      var info = document.createElement('span');
      info.className = 'insurance-file-info';

      var name = document.createElement('span');
      name.className = 'insurance-file-name';
      name.textContent = file.name;
      name.title = file.name;

      var size = document.createElement('span');
      size.className = 'insurance-file-size';
      size.textContent = formatBytes(file.size || 0);

      info.appendChild(name);
      info.appendChild(size);

      var removeButton = document.createElement('button');
      removeButton.type = 'button';
      removeButton.className = 'insurance-remove-file';
      removeButton.setAttribute('aria-label', 'Remove file');
      removeButton.innerHTML = '<i class="ri-close-line"></i>';

      removeButton.addEventListener('click', function () {
        selectedFiles.splice(index, 1);
        syncInputFiles();
        renderFiles();
      });

      item.appendChild(icon);
      item.appendChild(info);
      item.appendChild(removeButton);

      fileList.appendChild(item);
    });

    if (emptyState) {
      emptyState.style.display = selectedFiles.length > 0 ? 'none' : '';
    }

    if (countText) {
      countText.textContent = selectedFiles.length + (selectedFiles.length === 1 ? ' file selected' : ' files selected');
    }

    if (sizeText) {
      sizeText.textContent = formatBytes(totalSize);
    }

    if (clearButton) {
      clearButton.disabled = selectedFiles.length === 0;
    }
  }

  function fileKey(file) {
    return [file.name, file.size, file.lastModified].join('|');
  }

  function mergeFiles(files) {
    var existingKeys = selectedFiles.map(fileKey);

    Array.prototype.slice.call(files).forEach(function (file) {
      if (existingKeys.indexOf(fileKey(file)) === -1) {
        selectedFiles.push(file);
        existingKeys.push(fileKey(file));
      }
    });

    syncInputFiles();
    renderFiles();
  }

  input.addEventListener('change', function () {
    selectedFiles = Array.prototype.slice.call(input.files || []);
    renderFiles();
  });

  ['dragenter', 'dragover'].forEach(function (eventName) {
    dropzone.addEventListener(eventName, function (event) {
      event.preventDefault();
      event.stopPropagation();
      dropzone.classList.add('is-dragover');
    });
  });

  ['dragleave', 'drop'].forEach(function (eventName) {
    dropzone.addEventListener(eventName, function (event) {
      event.preventDefault();
      event.stopPropagation();
      dropzone.classList.remove('is-dragover');
    });
  });

  dropzone.addEventListener('drop', function (event) {
    var files = event.dataTransfer && event.dataTransfer.files ? event.dataTransfer.files : [];

    if (files.length > 0) {
      mergeFiles(files);
    }
  });

  if (clearButton) {
    clearButton.addEventListener('click', function () {
      selectedFiles = [];
      input.value = '';
      syncInputFiles();
      renderFiles();
    });
  }

  renderFiles();
})();
JS);
?>

<main class="insurance-upload-wrapper can-form-page">
    <div class="insurance-upload-card">

        <div class="insurance-upload-header">
            <div class="insurance-upload-header-main">
                <div class="insurance-upload-title-box">
                    <div class="insurance-upload-icon">
                        <i class="ri-upload-cloud-2-line"></i>
                    </div>

                    <div class="insurance-upload-title-text">
                        <span class="insurance-upload-kicker">
                            <i class="ri-shield-check-line"></i>
                            MRO Insurance Upload
                        </span>

                        <h1 class="insurance-upload-title">
                            Upload <span class="accent">Insurance Documents</span>
                        </h1>

                        <p class="insurance-upload-subtitle">
                            Add one or multiple insurance files to your MRO document archive.
                        </p>
                    </div>
                </div>

                <?= Html::a(
                    '<i class="ri-arrow-left-line"></i> Back to Documents',
                    ['index'],
                    ['class' => 'insurance-back-btn']
                ) ?>
            </div>
        </div>

        <div class="insurance-upload-content">

            <?php if (Yii::$app->session->hasFlash('success')): ?>
                <div class="alert alert-success">
                    <?= Yii::$app->session->getFlash('success') ?>
                </div>
            <?php endif; ?>

            <?php if (Yii::$app->session->hasFlash('error')): ?>
                <div class="alert alert-danger">
                    <?= Yii::$app->session->getFlash('error') ?>
                </div>
            <?php endif; ?>

            <?php if ($model->hasErrors()): ?>
                <div class="insurance-error-summary">
                    <?= Html::errorSummary($model) ?>
                </div>
            <?php endif; ?>

            <?php $form = ActiveForm::begin([
                'id' => 'insurance-upload-form',
                'options' => [
                    'enctype' => 'multipart/form-data',
                    'class' => 'insurance-upload-form',
                ],
            ]); ?>

                <div class="insurance-form-grid">

                    <div class="insurance-upload-panel">
                        <h2 class="insurance-section-title">
                            Select document files
                        </h2>

                        <p class="insurance-section-subtitle">
                            Drag and drop your files below, or click the upload area to browse from your device.
                        </p>

                        <label class="insurance-dropzone" for="<?= Html::encode($inputId) ?>" data-insurance-dropzone>
                            <span class="insurance-dropzone-inner">
                                <span class="insurance-dropzone-icon">
                                    <i class="ri-upload-cloud-line"></i>
                                </span>

                                <span class="insurance-dropzone-title">
                                    Drop files here
                                </span>

                                <span class="insurance-dropzone-text">
                                    You can upload one or multiple files at once.
                                </span>

                                <span class="insurance-browse-btn">
                                    <i class="ri-folder-open-line"></i>
                                    Browse Files
                                </span>

                                <span class="insurance-dropzone-hint">
                                    Accepted formats: PDF, JPG, PNG, WEBP, DOC, DOCX, XLS, XLSX
                                </span>
                            </span>
                        </label>

                        <?= Html::fileInput(
                            'insurance_files[]',
                            null,
                            [
                                'id' => $inputId,
                                'class' => 'insurance-file-input',
                                'multiple' => true,
                                'accept' => '.pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,image/*,application/pdf',
                            ]
                        ) ?>

                        <div class="insurance-selected-box">
                            <div class="insurance-selected-header">
                                <div>
                                    <span class="insurance-selected-title">
                                        Selected files
                                    </span>

                                    <span class="insurance-selected-meta">
                                        <span data-insurance-count>0 files selected</span>
                                        <span>•</span>
                                        <span data-insurance-size>0 B</span>
                                    </span>
                                </div>

                                <button type="button" class="insurance-clear-btn" data-insurance-clear disabled>
                                    Clear all
                                </button>
                            </div>

                            <div class="insurance-selected-empty" data-insurance-empty>
                                No files selected yet.
                            </div>

                            <ul class="insurance-file-list" data-insurance-file-list></ul>
                        </div>
                    </div>

                    <aside class="insurance-info-panel">
                        <div class="insurance-info-card">
                            <h3 class="insurance-info-card-title">
                                <i class="ri-information-line"></i>
                                Upload guidelines
                            </h3>

                            <ul class="insurance-info-list">
                                <li>
                                    <i class="ri-check-line"></i>
                                    Upload valid insurance certificates or supporting documents.
                                </li>

                                <li>
                                    <i class="ri-check-line"></i>
                                    You can select several files in one operation.
                                </li>

                                <li>
                                    <i class="ri-check-line"></i>
                                    Use clear file names to make future review easier.
                                </li>
                            </ul>
                        </div>

                        <div class="insurance-info-card">
                            <h3 class="insurance-info-card-title">
                                <i class="ri-lock-2-line"></i>
                                Secure archive
                            </h3>

                            <ul class="insurance-info-list">
                                <li>
                                    <i class="ri-check-line"></i>
                                    Documents will be linked to your MRO profile.
                                </li>

                                <li>
                                    <i class="ri-check-line"></i>
                                    Uploaded files can be reviewed from the document library.
                                </li>

                                <li>
                                    <i class="ri-check-line"></i>
                                    Delete outdated documents from the list page when needed.
                                </li>
                            </ul>
                        </div>
                    </aside>

                </div>

                <div class="insurance-actions">
                    <?= Html::a(
                        '<i class="ri-close-line"></i> Cancel',
                        ['index'],
                        ['class' => 'btn-insurance-cancel']
                    ) ?>

                    <?= Html::submitButton(
                        '<i class="ri-upload-cloud-2-line"></i> Upload Document(s)',
                        ['class' => 'btn-insurance-submit']
                    ) ?>
                </div>

            <?php ActiveForm::end(); ?>

        </div>
    </div>
</main>
