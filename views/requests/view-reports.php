<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use app\components\UrlIdHelper;
use yii\web\View;


$this->title = 'View CRS';
$encodedRequestId = UrlIdHelper::encode($request->request_id);
$this->params['breadcrumbs'][] = ['label' => 'Requests', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $request->request_id, 'url' => ['view', 'id' => $encodedRequestId]];
$this->params['breadcrumbs'][] = $this->title;

/* CRS REPORT REVIEW 2026: prepare request context for display only. */
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

/* Bootstrap Icons */
$this->registerCssFile(
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css',
  ['position' => $this::POS_HEAD]
);

/* CRS REPORT REVIEW 2026: confirmations for CRS decisions. */
$this->registerJsFile(
  'https://cdn.jsdelivr.net/npm/sweetalert2@11',
  ['position' => View::POS_END]
);

/* ===============================
   CSS — DESIGN "NOTIFICATION CARD"
   VIEW CRS / REPORTS
================================= */
$this->registerCss(<<<CSS
:root{
  --deep-blue:#0D3261;
  --sky:#00B2FF;
  --gold:#EED811;
  --soft-border:#D8E4EE;
  --text-main:#0F172A;
  --text-muted:#6B7280;
}

/* wrapper pleine largeur */
.notification-pref-wrapper{
  min-height: calc(100vh - 70px);
  padding: 16px 24px 32px;
  margin: 0 -24px;
  background:
    radial-gradient(circle at 20% 50%, rgba(13,50,97,.06) 0%, transparent 50%),
    radial-gradient(circle at 80% 20%, rgba(0,178,255,.06) 0%, transparent 50%),
    linear-gradient(145deg,#EFF5FF 0%,#FFFFFF 45%,#F8FBFF 100%);
}

/* Card principale */
.notification-pref-card{
  width:100%;
  background:#fff;
  border-radius:22px;
  border:1px solid var(--soft-border);
  box-shadow: 0 16px 40px rgba(15,23,42,.14);
  overflow:hidden;
  position:relative;
}
.notification-pref-card::before{
  content:"";
  position:absolute;
  inset:0 0 auto 0;
  height:5px;
  background: linear-gradient(135deg,var(--deep-blue),var(--sky),var(--gold));
  background-size:220% 220%;
  animation: grad 8s ease infinite;
}
@keyframes grad{
  0%{background-position:0% 50%}
  50%{background-position:100% 50%}
  100%{background-position:0% 50%}
}

/* Header */
.notification-header{
  padding:22px 28px 12px;
  background: linear-gradient(135deg,#fff,#F4F8FF);
  border-bottom:1px solid rgba(226,232,240,.7);
}
.notification-title-row{
  display:flex;
  gap:18px;
  align-items:center;
  flex-wrap:wrap;
}
.notification-title-icon{
  width:58px;height:58px;
  border-radius:16px;
  background:#E7F4FF;
  color:var(--deep-blue);
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:26px;
  box-shadow:0 12px 30px rgba(15,23,42,.28);
  position:relative;
  overflow:hidden;
}
.notification-title-icon::before{
  content:"";
  position:absolute;
  inset:0;
  background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,.32) 50%, transparent 70%);
  animation: shine 3s infinite linear;
}
@keyframes shine{ 0%{transform:translateX(-100%)} 100%{transform:translateX(100%)} }

.notification-title{
  margin:0;
  font-size:24px;
  font-weight:700;
  letter-spacing:-0.02em;
  color:var(--text-main);
}
.notification-subtitle{
  margin-top:4px;
  font-size:14px;
  color:var(--text-muted);
  max-width: 980px;
}
.notification-meta{
  margin-top:10px;
  display:flex;
  flex-wrap:wrap;
  gap:10px;
}
.meta-pill{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:6px 10px;
  border-radius:999px;
  border:1px solid rgba(216,228,238,.9);
  background:#F7FAFF;
  color:var(--text-main);
  font-size:12px;
  font-weight:700;
}
.meta-pill i{ color: var(--deep-blue); }

/* Body */
.notification-body{
  padding:16px 24px 24px;
}

/* Flash */
.req-flash{ margin: 0 0 12px; }
.req-flash .alert{
  border-radius: 12px;
  border:none;
  padding:10px 14px;
  font-size:14px;
}

/* Section title */
.section-title{
  margin: 10px 0 10px;
  font-size:12px;
  font-weight:800;
  letter-spacing:.10em;
  text-transform:uppercase;
  color: var(--deep-blue);
  display:flex;
  align-items:center;
  gap:8px;
}
.section-title i{ color: var(--sky); font-size:16px; }

/* Table wrapper */
.table-wrap{
  border-radius:18px;
  border:1px solid var(--soft-border);
  background:#F9FBFF;
  overflow:hidden;
}
.table-wrap .table-responsive-pro{
  overflow:auto;
  -webkit-overflow-scrolling: touch;
}

/* Table */
.req-table{
  width:100%;
  margin:0;
  border-collapse:separate;
  border-spacing:0;
}
.req-table thead th{
  font-size:11px;
  text-transform:uppercase;
  letter-spacing:.08em;
  font-weight:700;
  color:var(--text-muted);
  background:#E5EDF9;
  border-bottom:1px solid var(--soft-border);
  padding:10px 12px;
  white-space:nowrap;
}
.req-table tbody td{
  font-size:13px;
  color:var(--text-main);
  padding:12px 14px;
  vertical-align:middle;
  border-bottom:1px solid rgba(226,232,240,.8);
}
.req-table tbody tr:hover{ background:#EEF4FF; }
.req-table tbody tr:last-child td{ border-bottom:none; }

/* Badges */
.id-badge{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:6px 10px;
  border-radius:12px;
  background:#E7F4FF;
  border:1px solid rgba(0,178,255,.20);
  color:var(--deep-blue);
  font-weight:800;
  font-size:12px;
}
.id-badge i{ font-size:14px; }

.status-pill{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:5px 10px;
  border-radius:999px;
  font-size:11px;
  font-weight:800;
  text-transform:uppercase;
  letter-spacing:.06em;
  background:#E0ECFF;
  color:#1D4ED8;
  white-space:nowrap;
}

/* Report column (ellipsis) */
.report-column{
  max-width: 520px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.report-column:hover{
  white-space: normal;
  overflow: visible;
}

/* Actions */
.actions{
  display:flex;
  flex-wrap:wrap;
  gap:6px;
  align-items:center;
}
.actions form{ display:inline; margin:0; }
.btn-icon{
  width:34px;
  height:34px;
  padding:0;
  border-radius:10px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  font-size:14px;
  font-weight:800;
}
.btn-cert{
  border-radius: 9px;
}

/* CRS REPORT REVIEW 2026: operational request summary. */
.request-detail-grid{
  display:grid;
  grid-template-columns:repeat(4,minmax(0,1fr));
  gap:10px;
  margin-bottom:10px;
}
.request-detail-item{
  min-width:0;
  min-height:72px;
  padding:11px 13px;
  border:1px solid var(--soft-border);
  border-radius:12px;
  background:#F8FAFC;
}
.request-detail-item.wide{ grid-column:span 2; }
.request-detail-label{
  display:flex;
  align-items:center;
  gap:6px;
  margin-bottom:7px;
  color:#708096;
  font-size:10px;
  font-weight:800;
  letter-spacing:.06em;
  text-transform:uppercase;
}
.request-detail-value{
  color:var(--text-main);
  font-size:13px;
  font-weight:800;
  overflow-wrap:anywhere;
}
.request-date-grid{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:10px;
}
.request-info-readonly{
  width:100%;
  min-height:90px;
  margin-top:10px;
  padding:12px 13px;
  resize:vertical;
  border:1px dashed #B8CBE1;
  border-radius:12px;
  color:#334155;
  background:#fff;
  font:inherit;
  font-size:13px;
  line-height:1.5;
}

/* CRS REPORT REVIEW 2026: readable action buttons and CRS detail modal. */
.btn-action{
  min-height:34px;
  padding:7px 10px;
  border:0;
  border-radius:9px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:6px;
  color:#fff !important;
  font-size:11px;
  font-weight:800;
  text-decoration:none;
  transition:.2s ease;
}
.btn-action:hover{ color:#fff !important; transform:translateY(-1px); filter:brightness(.95); }
.btn-view-crs{ background:#2563EB; }
.btn-accept-crs{ background:#15803D; }
.btn-change-crs{ background:#DC2626; }
.btn-quote-crs{ background:#7C3AED; }
.btn-feedback-crs{ background:#0369A1; }
.btn-download-crs{ background:#0F766E; }
.actions form,.crs-modal-actions form{ display:inline-flex; margin:0; }
.crs-modal .modal-content{
  border:0;
  border-radius:14px;
  overflow:hidden;
  box-shadow:0 24px 70px rgba(15,23,42,.28);
}
.crs-modal .modal-header{
  padding:16px 20px;
  border-bottom:1px solid var(--soft-border);
  background:linear-gradient(135deg,#F8FBFF,#EEF6FF);
}
.crs-modal .modal-title{ font-size:18px; font-weight:900; color:var(--text-main); }
.crs-modal .modal-body{ padding:20px; }
.crs-modal-meta{
  display:grid;
  grid-template-columns:repeat(3,minmax(0,1fr));
  gap:9px;
  margin-bottom:13px;
}
.crs-modal-meta-item{
  min-width:0;
  padding:10px 11px;
  border:1px solid var(--soft-border);
  border-radius:9px;
  background:#F8FAFC;
}
.crs-modal-label{ margin-bottom:4px; color:#708096; font-size:9px; font-weight:900; letter-spacing:.06em; text-transform:uppercase; }
.crs-modal-value{ color:var(--text-main); font-size:12px; font-weight:900; overflow-wrap:anywhere; }
.crs-report-readonly{
  width:100%;
  min-height:180px;
  padding:13px;
  resize:vertical;
  border:1px solid #CBD9E8;
  border-radius:10px;
  color:#334155;
  background:#FBFDFF;
  font:inherit;
  font-size:13px;
  line-height:1.55;
}
.crs-document-box{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  padding:12px;
  margin-top:12px;
  border:1px solid var(--soft-border);
  border-radius:10px;
  background:#F8FAFC;
}
.crs-document-name{ min-width:0; color:#475569; font-size:12px; font-weight:800; overflow-wrap:anywhere; }
.crs-modal-actions{
  display:flex;
  justify-content:flex-end;
  gap:7px;
  flex-wrap:wrap;
  padding:14px 20px;
  border-top:1px solid #E2E8F0;
  background:#F8FAFC;
}
/* MODAL CLOSE BUTTON: explicit states prevent global action-button hover rules hiding its label. */
.crs-modal .btn-close-modal{
  min-width:96px;
  color:#334155 !important;
  background:#fff !important;
  border:1px solid #CBD5E1 !important;
  box-shadow:0 3px 8px rgba(15,23,42,.06);
}
.crs-modal .btn-close-modal:hover{
  color:#fff !important;
  background:#334155 !important;
  border-color:#334155 !important;
  filter:none;
  transform:translateY(-1px);
  box-shadow:0 7px 15px rgba(51,65,85,.22);
}
.crs-modal .btn-close-modal:focus-visible{
  color:#0F172A !important;
  background:#fff !important;
  border-color:#2563EB !important;
  outline:3px solid rgba(37,99,235,.20);
  outline-offset:2px;
}
.crs-modal .btn-close-modal:active{
  color:#fff !important;
  background:#1E293B !important;
  border-color:#1E293B !important;
  transform:translateY(0);
}

/* CRS REPORT REVIEW 2026: compact SweetAlert confirmation. */
.swal2-popup.crs-confirm-popup{ width:390px !important; max-width:92vw !important; padding:18px 20px !important; border-radius:12px !important; }
.swal2-popup.crs-confirm-popup .swal2-icon{ width:54px !important; height:54px !important; margin:8px auto 12px !important; }
.swal2-popup.crs-confirm-popup .swal2-icon-content{ font-size:32px !important; }
.swal2-title.crs-confirm-title{ padding:0 !important; color:var(--text-main) !important; font-size:20px !important; font-weight:900 !important; }
.swal2-html-container.crs-confirm-message{ color:#475569 !important; font-size:13px !important; line-height:1.45 !important; }
.crs-swal-confirm,.crs-swal-cancel{ min-width:120px !important; min-height:39px !important; border:0 !important; border-radius:9px !important; font-size:12px !important; font-weight:900 !important; }
.crs-swal-confirm.accept{ color:#fff !important; background:#15803D !important; }
.crs-swal-confirm.change{ color:#fff !important; background:#DC2626 !important; }
.crs-swal-cancel{ color:#334155 !important; background:#E2E8F0 !important; }

/* Empty state */
.empty{
  padding:40px 20px;
  text-align:center;
  color:var(--text-muted);
}

/* Responsive */
@media (max-width: 992px){
  .notification-pref-wrapper{ padding:12px; margin:0 -12px; }
  .notification-header{ padding:18px 16px; }
  .notification-body{ padding:12px; }
  .report-column{ max-width: 320px; }
  .request-detail-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); }
}
@media (max-width: 767.98px){
  .request-detail-grid,.request-date-grid,.crs-modal-meta{ grid-template-columns:1fr; }
  .request-detail-item.wide{ grid-column:auto; }
  .actions .btn-action,.crs-modal-actions .btn-action{ flex:1 1 calc(50% - 7px); }
  .crs-document-box{ align-items:stretch; flex-direction:column; }
}
CSS);

/* CRS REPORT REVIEW 2026: tooltips and confirmation before a CRS decision. */
$this->registerJs(<<<JS
document.addEventListener('DOMContentLoaded', function () {
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });
});

document.addEventListener('submit', function (event) {
  var form = event.target.closest('.js-crs-decision-form');
  if (!form || form.dataset.confirmed === '1') {
    return;
  }

  event.preventDefault();
  var isAccept = form.dataset.decision === 'yes';

  if (typeof Swal === 'undefined') {
    if (window.confirm(isAccept ? 'Accept this CRS?' : 'Request a CRS change?')) {
      form.dataset.confirmed = '1';
      form.submit();
    }
    return;
  }

  Swal.fire({
    title: isAccept ? 'Accept this CRS?' : 'Request a CRS change?',
    html: isAccept
      ? '<strong>The request will be closed after this CRS is accepted.</strong>'
      : '<strong>The MRO will be asked to submit a corrected CRS document.</strong>',
    icon: isAccept ? 'question' : 'warning',
    showCancelButton: true,
    reverseButtons: true,
    focusCancel: true,
    allowOutsideClick: false,
    confirmButtonText: isAccept
      ? '<i class="bi bi-check-circle-fill"></i> Accept CRS'
      : '<i class="bi bi-arrow-repeat"></i> Request change',
    cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review report',
    buttonsStyling: false,
    // SHARED DETAIL CONFIRMATION: reuse the common compact dialog presentation.
    customClass: {
      popup: 'crs-confirm-popup can-detail-swal',
      title: 'crs-confirm-title',
      htmlContainer: 'crs-confirm-message',
      confirmButton: 'crs-swal-confirm ' + (isAccept ? 'accept' : 'change'),
      cancelButton: 'crs-swal-cancel'
    }
  }).then(function (result) {
    if (result.isConfirmed) {
      form.dataset.confirmed = '1';
      form.submit();
    }
  });
});
JS);
?>

<!-- SHARED DETAIL SYSTEM: presentation only; CRS decisions remain unchanged. -->
<div class="notification-pref-wrapper can-detail-page">
  <div class="notification-pref-card">

    <!-- HEADER -->
    <div class="notification-header">
      <div class="notification-title-row">
        <div class="notification-title-icon">
          <i class="bi bi-file-earmark-text"></i>
        </div>
        <div>
          <h1 class="notification-title"><?= Html::encode($this->title) ?></h1>
          <div class="notification-subtitle">
            Review the maintenance release before closing the request.
          </div>

          <div class="notification-meta">
            <span class="meta-pill">
              <i class="bi bi-hash"></i>
              Request: <?= Html::encode($request->request_id) ?>
            </span>
            <span class="meta-pill">
              <i class="bi bi-info-circle"></i>
              Status: <?= Html::encode(ucwords(str_replace('_',' ', (string)$request->status))) ?>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- BODY -->
    <div class="notification-body">

      <!-- Flash -->
      <div class="req-flash">
        <?php if (Yii::$app->session->hasFlash('success')): ?>
          <div class="alert alert-success"><?= Html::encode(Yii::$app->session->getFlash('success')) ?></div>
        <?php endif; ?>
        <?php if (Yii::$app->session->hasFlash('error')): ?>
          <div class="alert alert-danger"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
        <?php endif; ?>
      </div>

      <!-- CRS REPORT REVIEW 2026: request ID remains in the header; operational data is shown once below. -->
      <div class="section-title"><i class="bi bi-airplane-engines"></i> Current Request Details</div>
      <div class="request-detail-grid">
        <div class="request-detail-item wide">
          <div class="request-detail-label"><i class="bi bi-building"></i> Aircraft Operator / CAMO</div>
          <div class="request-detail-value"><?= Html::encode($operatorName) ?></div>
        </div>
        <div class="request-detail-item wide">
          <div class="request-detail-label"><i class="bi bi-airplane"></i> Aircraft</div>
          <div class="request-detail-value"><?= Html::encode($aircraftName) ?></div>
        </div>
        <div class="request-detail-item">
          <div class="request-detail-label"><i class="bi bi-card-text"></i> Registration</div>
          <div class="request-detail-value"><?= Html::encode($request->aircraft_registration ?: 'N/A') ?></div>
        </div>
        <div class="request-detail-item">
          <div class="request-detail-label"><i class="bi bi-upc-scan"></i> Serial Number</div>
          <div class="request-detail-value"><?= Html::encode($request->serial_number ?: 'N/A') ?></div>
        </div>
        <div class="request-detail-item wide">
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
        'class' => 'request-info-readonly',
        'readonly' => true,
        'aria-label' => 'Request information',
      ]) ?>

      <!-- CRS table -->
      <div class="section-title"><i class="bi bi-wrench-adjustable-circle"></i> Maintenance Release &amp; Repair Reports</div>
      <div class="table-wrap">
        <div class="table-responsive-pro">
          <table class="req-table">
            <thead>
              <tr>
                <th>CRS ID</th>
                <th>MRO</th>
                <th>Report</th>
                <th>CRS Attachment</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>

            <?php if (empty($repairReports)): ?>
              <tr>
                <td colspan="5">
                  <div class="empty">
                    <i class="bi bi-inboxes" style="font-size:28px; color:var(--deep-blue)"></i>
                    <div style="margin-top:10px; font-weight:800;">No CRS reports yet</div>
                    <div style="margin-top:4px;">Reports will appear here once MRO submits CRS for this request.</div>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($repairReports as $report): ?>
                <?php
                  /* CRS REPORT REVIEW 2026: prepare one detail modal for this report. */
                  $reqStatus = (string)$request->status;
                  $mro = $report->getMro();
                  $mroName = $mro ? $mro->username : 'N/A';
                  $existingfeedback = \app\models\Feedback::find()
                    ->where(['request_id' => $request->request_id])
                    ->exists();
                  $canApproveCrs = (
                    $reqStatus !== 'closed'
                    && ($report->quote_approved != 0 || $report->quote_approved === null)
                    && $report->CRSed != 0
                  );
                  $modalId = 'crs-report-modal-' . (int)$report->repair_report_id;
                  $attachmentName = !empty($report->CRS_attachment) ? basename($report->CRS_attachment) : null;
                  $attachmentUrl = $attachmentName
                    ? Yii::getAlias('@web/uploads/') . ltrim($report->CRS_attachment, '/')
                    : null;

                  if ($report->quote_approved == 1) {
                    $reportStatus = 'Accepted';
                  } elseif ($report->quote_approved !== null && $report->quote_approved == 0) {
                    $reportStatus = 'Change requested';
                  } else {
                    $reportStatus = 'Awaiting review';
                  }
                ?>
                <!-- SHARED DETAIL TABLE: data labels provide readable mobile cards. -->
                <tr>
                  <td data-label="CRS ID">
                    <span class="id-badge"><i class="bi bi-hash"></i><?= Html::encode($report->repair_report_id) ?></span>
                  </td>

                  <td data-label="MRO"><?= Html::encode($mroName) ?></td>

                  <td data-label="Report" class="report-column" title="<?= Html::encode($report->report) ?>">
                    <?= Html::encode($report->report) ?>
                  </td>

                  <td data-label="CRS Attachment">
                    <?php if ($attachmentUrl): ?>
                      <?= Html::a(
                        '<i class="bi bi-download"></i> Download',
                        $attachmentUrl,
                        [
                          'class' => 'btn-action btn-download-crs',
                          'target' => '_blank',
                          'rel' => 'noopener',
                          'data-pjax' => '0',
                          'data-no-loader' => 'true',
                        ]
                      ) ?>
                    <?php else: ?>
                      <span class="text-muted">N/A</span>
                    <?php endif; ?>
                  </td>

                  <td data-label="Actions">
                    <div class="actions">
                      <button type="button" class="btn-action btn-view-crs" data-bs-toggle="modal" data-bs-target="#<?= Html::encode($modalId) ?>">
                        <i class="bi bi-eye"></i> View CRS
                      </button>
                    </div>

                <!-- CRS REPORT REVIEW 2026: complete CRS information and available workflow actions. -->
                <div class="modal fade crs-modal" id="<?= Html::encode($modalId) ?>" tabindex="-1" aria-labelledby="<?= Html::encode($modalId) ?>-title" aria-hidden="true">
                  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h2 class="modal-title" id="<?= Html::encode($modalId) ?>-title">
                          <i class="bi bi-file-earmark-medical me-2"></i>CRS Report #<?= Html::encode($report->repair_report_id) ?>
                        </h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="crs-modal-meta">
                          <div class="crs-modal-meta-item">
                            <div class="crs-modal-label">MRO</div>
                            <div class="crs-modal-value"><?= Html::encode($mroName) ?></div>
                          </div>
                          <div class="crs-modal-meta-item">
                            <div class="crs-modal-label">Status</div>
                            <div class="crs-modal-value"><?= Html::encode($reportStatus) ?></div>
                          </div>
                          <div class="crs-modal-meta-item">
                            <div class="crs-modal-label">Submitted</div>
                            <div class="crs-modal-value"><?= Html::encode($formatOperationalDate($report->created_at)) ?></div>
                          </div>
                        </div>

                        <div class="crs-modal-label"><i class="bi bi-card-text me-1"></i> Maintenance Report</div>
                        <textarea class="crs-report-readonly" readonly><?= Html::encode($report->report ?: 'No maintenance report provided.') ?></textarea>

                        <div class="crs-document-box">
                          <div class="crs-document-name">
                            <i class="bi <?= $attachmentUrl ? 'bi-file-earmark-pdf' : 'bi-file-earmark-x' ?> me-2"></i>
                            <?= Html::encode($attachmentName ?: 'No CRS attachment available') ?>
                          </div>
                          <?php if ($attachmentUrl): ?>
                            <?= Html::a('<i class="bi bi-eye"></i> View / Download', $attachmentUrl, [
                              'class' => 'btn-action btn-download-crs',
                              'target' => '_blank',
                              'rel' => 'noopener',
                              'data-pjax' => '0',
                              'data-no-loader' => 'true',
                            ]) ?>
                          <?php endif; ?>
                        </div>
                      </div>

                      <div class="crs-modal-actions">
                        <?php if ($canApproveCrs): ?>
                          <?php ActiveForm::begin([
                            'method' => 'post',
                            'action' => ['view-reports', 'id' => $encodedRequestId],
                            'options' => ['class' => 'js-crs-decision-form', 'data-decision' => 'yes'],
                          ]); ?>
                            <?= Html::hiddenInput('report_id', $report->repair_report_id) ?>
                            <?= Html::hiddenInput('approve', 'yes') ?>
                            <?= Html::submitButton('<i class="bi bi-check-circle-fill"></i> Accept CRS', ['class' => 'btn-action btn-accept-crs']) ?>
                          <?php ActiveForm::end(); ?>

                          <?php ActiveForm::begin([
                            'method' => 'post',
                            'action' => ['view-reports', 'id' => $encodedRequestId],
                            'options' => ['class' => 'js-crs-decision-form', 'data-decision' => 'no'],
                          ]); ?>
                            <?= Html::hiddenInput('report_id', $report->repair_report_id) ?>
                            <?= Html::hiddenInput('approve', 'no') ?>
                            <?= Html::submitButton('<i class="bi bi-arrow-repeat"></i> Request Change', ['class' => 'btn-action btn-change-crs']) ?>
                          <?php ActiveForm::end(); ?>
                        <?php endif; ?>

                        <?php if ($report->mro_quote && $reqStatus !== 'closed'): ?>
                          <?= Html::a('<i class="bi bi-cash-coin"></i> Approve Quote', ['approve-quote', 'id' => UrlIdHelper::encode($report->repair_report_id)], ['class' => 'btn-action btn-quote-crs']) ?>
                        <?php endif; ?>
                        <?php if ($reqStatus === 'closed' && !$existingfeedback && $report->quote_approved == 1): ?>
                          <!-- FEEDBACK ENCODED ID 2026: never expose the numeric CRS report ID. -->
                          <?= Html::a('<i class="bi bi-chat-dots"></i> Feedback', ['provide-feedback', 'id' => UrlIdHelper::encode($report->repair_report_id)], ['class' => 'btn-action btn-feedback-crs']) ?>
                        <?php endif; ?>
                        <button type="button" class="btn-action btn-close-modal" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i> Close</button>
                      </div>
                    </div>
                  </div>
                </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>

            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
