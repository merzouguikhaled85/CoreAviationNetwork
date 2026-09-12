<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\bootstrap5\ActiveForm;
use app\models\Currency;

$this->title = 'Submit MRO Quotation';

// Build the AO request attachment URL independently from the MRO quotation upload.
$requestAttachment = trim((string) ($request->attachment ?? ''));
$requestAttachmentUrl = null;
$requestAttachmentName = null;

if ($requestAttachment !== '') {
  $normalizedAttachment = str_replace('\\', '/', $requestAttachment);
  $requestAttachmentName = basename($normalizedAttachment);

  if (preg_match('/^https?:\/\//i', $normalizedAttachment)) {
    $requestAttachmentUrl = $normalizedAttachment;
  } elseif (strpos($normalizedAttachment, '@web/') === 0) {
    $requestAttachmentUrl = Yii::getAlias('@web') . '/' . ltrim(substr($normalizedAttachment, 5), '/');
  } elseif (strpos($normalizedAttachment, '/') !== false) {
    $requestAttachmentUrl = Yii::getAlias('@web/') . ltrim($normalizedAttachment, '/');
  } else {
    $requestAttachmentUrl = Yii::getAlias('@web/uploads/') . $normalizedAttachment;
  }
}

// Existing MRO quotation: browsers cannot prefill a file input, so expose the
// persisted document and retain it unless the user selects a replacement.
$existingQuoteAttachment = $model->isNewRecord
  ? ''
  : trim((string) $model->getOldAttribute('attachment'));
$existingQuoteAttachmentUrl = null;
$existingQuoteAttachmentName = null;

if ($existingQuoteAttachment !== '') {
  $normalizedQuoteAttachment = str_replace('\\', '/', $existingQuoteAttachment);
  $existingQuoteAttachmentName = basename($normalizedQuoteAttachment);

  if (preg_match('/^https?:\/\//i', $normalizedQuoteAttachment)) {
    $existingQuoteAttachmentUrl = $normalizedQuoteAttachment;
  } elseif (strpos($normalizedQuoteAttachment, '@web/') === 0) {
    $existingQuoteAttachmentUrl = Yii::getAlias('@web') . '/' . ltrim(substr($normalizedQuoteAttachment, 5), '/');
  } elseif (strpos($normalizedQuoteAttachment, '/') !== false) {
    $existingQuoteAttachmentUrl = Yii::getAlias('@web/') . ltrim($normalizedQuoteAttachment, '/');
  } else {
    $existingQuoteAttachmentUrl = Yii::getAlias('@web/uploads/') . $normalizedQuoteAttachment;
  }
}

$hasExistingQuoteJs = $existingQuoteAttachmentUrl !== null ? 'true' : 'false';
$existingQuoteNameJs = Json::htmlEncode($existingQuoteAttachmentName ?? '');

// Prepare a compact operational summary for the MRO quotation context.
$requestAircraft = $request->getAircraft()->one();
$requestAo = $request->getAO()->one();
$requestDestination = $request->getDestinationAirport()->one();

$requestOperatorText = $requestAo
  ? ($requestAo->company_name ?: $requestAo->username ?: 'N/A')
  : 'N/A';
$requestAircraftText = $requestAircraft
  ? trim(($requestAircraft->manufacturer ?? '') . ' ' . ($requestAircraft->model ?? ''))
  : 'N/A';
$requestLocationText = $requestDestination
  ? ($requestDestination->airport_name ?: $request->location ?: 'N/A')
  : ($request->location ?: 'N/A');
$requestEtaText = $request->eta ? date('d M Y H:i', strtotime($request->eta)) : 'N/A';
$requestEtdText = $request->etd ? date('d M Y H:i', strtotime($request->etd)) : 'N/A';

// UI icons and confirmation dialogs.
$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
$this->registerJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11', ['position' => \yii\web\View::POS_HEAD]);

/* ===========================
   Premium aviation quote page CSS
   =========================== */
$this->registerCss(<<<CSS
:root{
  --primary:#0D3261;
  --primary-2:#1D4ED8;
  --cyan:#00B2FF;
  --gold:#EED811;
  --bg:#FFFFFF;
  --card:#FFFFFF;
  --line:#D9E5F1;
  --line-2:#E7EEF6;
  --text:#0F172A;
  --muted:#64748B;
  --success:#14804A;
  --danger:#B4232C;
  --warning:#B7791F;
  --shadow:0 18px 46px rgba(15,23,42,.10);
}

.reply-page{
  min-height:calc(100vh - 70px);
  padding:34px 18px;
  background:#FFFFFF;
}

.reply-shell{
  width:100%;
  max-width:1280px;
  margin:0 auto;
}

.reply-card{
  position:relative;
  overflow:hidden;
  background:#FFFFFF;
  border:1px solid rgba(217,229,241,.95);
  border-radius:9px;
  box-shadow:var(--shadow);
}

.reply-card::before{
  display:none;
}

.reply-hero{
  position:relative;
  padding:24px 28px 20px;
  background:#FFFFFF;
  border-bottom:1px solid var(--line-2);
}

.hero-top{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:18px;
}

.simple-header{
  display:flex;
  align-items:center;
  gap:16px;
}

.hero-actions{
  flex:0 0 auto;
}

.btn-back-request{
  min-height:42px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  padding:0 14px;
  border:1px solid #D9E5F1;
  border-radius:9px;
  background: #030303ff;
  color:white;
  font-size:13px;
  font-weight:900;
  text-decoration:none;
  box-shadow:0 10px 24px rgba(15,23,42,.06);
  transition:transform .2s ease, box-shadow .2s ease, background .2s ease;
}

.btn-back-request:hover{
  color:var(--primary);
  background:#FFFFFF;
  transform:translateY(-1px);
  box-shadow:0 14px 30px rgba(1, 3, 8, 0.08);
}

.simple-header-icon{
  width:58px;
  height:58px;
  flex:0 0 58px;
  display:flex;
  align-items:center;
  justify-content:center;
  border-radius:9px;
  background:#FFFFFF;
  border:1px solid var(--line);
  color:var(--primary);
  font-size:26px;
  box-shadow:0 14px 34px rgba(13,50,97,.10);
}

.reply-title{
  margin:0;
  color:var(--text);
  font-size:27px;
  font-weight:900;
  letter-spacing:-.035em;
}

.reply-subtitle{
  max-width:920px;
  margin:8px 0 0;
  color:var(--muted);
  font-size:14px;
  line-height:1.55;
}

.reply-body{
  padding:22px 28px 28px;
}

.reply-flash .alert{
  display:flex;
  align-items:center;
  gap:10px;
  margin-bottom:16px;
  padding:13px 15px;
  border:0;
  border-radius:9px;
  box-shadow:0 12px 28px rgba(15,23,42,.07);
  font-size:14px;
  font-weight:700;
}

.reply-flash .alert-success{
  color:#0F5132;
  background:#FFFFFF;
  border-left:4px solid #14804A;
}

.reply-flash .alert-danger{
  color:#842029;
  background:#FFFFFF;
  border-left:4px solid #B4232C;
}


/* ==========================================================
   STEPPER REQUEST PROGRESS - CORRIGE
   Objectif : la ligne ne traverse plus les cercles.
   Les libelles restent sous les icones et le stepper reste responsive.

   Flux metier :
   1 Create Request -> 2 MRO Quote -> 3 PO Loaded ->
   4 PO Accepted -> 5 Work Started -> 6 MRO Report ->
   7 AO Feedback -> 8 Closed
   ========================================================== */
.steps-card{
  margin-bottom:22px;
  padding:22px 22px 30px;
  border:1px solid #DCE7F3;
  border-radius:18px;
  background:#FFFFFF;
  box-shadow:0 12px 30px rgba(15,23,42,.04);
}

.steps-header{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:16px;
  margin-bottom:26px;
}

.steps-title{
  margin:0;
  color:var(--text);
  font-size:14px;
  font-weight:900;
}

.steps-note{
  color:#94A3B8;
  font-size:12px;
  font-weight:800;
}

/*
 |--------------------------------------------------------------------------
 | Conteneur du stepper
 |--------------------------------------------------------------------------
 | Les dimensions sont centralisees dans des variables.
 | --step-circle : taille du cercle
 | --step-gap    : espace entre la ligne et le cercle
 | --step-line   : epaisseur de la ligne
 */
.quote-steps{
  --step-circle:40px;
  --step-line:4px;
  --step-gap:10px;
  display:flex;
  align-items:flex-start;
  width:100%;
  overflow-x:auto;
  overflow-y:visible;
  padding:8px 6px 8px;
  scrollbar-width:thin;
}

/*
 |--------------------------------------------------------------------------
 | Une etape = cercle en haut + libelle en dessous
 |--------------------------------------------------------------------------
 */
.quote-step{
  position:relative;
  flex:1 1 0;
  min-width:106px;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:flex-start;
  text-align:center;
  z-index:2;
}

/*
 |--------------------------------------------------------------------------
 | Ligne entre deux etapes
 |--------------------------------------------------------------------------
 | Correction importante :
 | La ligne commence APRES le cercle precedent et se termine AVANT
 | le cercle courant. Elle ne passe donc plus au milieu des icones.
 */
.quote-step::before{
  content:"";
  position:absolute;
  top:calc((var(--step-circle) - var(--step-line)) / 2);
  left:calc(-50% + (var(--step-circle) / 2) + var(--step-gap));
  width:calc(100% - var(--step-circle) - (var(--step-gap) * 2));
  height:var(--step-line);
  background:#E5EAF0;
  border-radius:999px;
  z-index:0;
  pointer-events:none;
}

.quote-step:first-child::before{
  display:none;
}

/* Segment vert uniquement pour les etapes deja atteintes. */
.quote-step.done::before,
.quote-step.active::before{
  background:#22C55E;
}

/* Cercle de l'etape. */
.step-index{
  position:relative;
  z-index:3;
  width:var(--step-circle);
  height:var(--step-circle);
  flex:0 0 var(--step-circle);
  display:flex;
  align-items:center;
  justify-content:center;
  border-radius:50%;
  background:#F8FAFC;
  border:2px solid #CBD5E1;
  color:#94A3B8;
  font-size:15px;
  box-shadow:0 4px 12px rgba(15,23,42,.08);
  transition:.22s ease;
}

/* Libelle toujours sous l'icone. */
.step-label{
  display:block;
  width:100%;
  max-width:98px;
  min-height:32px;
  margin:10px auto 0;
  color:#94A3B8;
  font-size:11px;
  font-weight:800;
  line-height:1.25;
  text-align:center;
  white-space:normal;
  overflow-wrap:break-word;
}

/* Etape deja terminee. */
.quote-step.done .step-index{
  background:#22C55E;
  border-color:#22C55E;
  color:#FFFFFF;
}

.quote-step.done .step-label{
  color:#22C55E;
}

/* Etape actuelle. */
.quote-step.active .step-index{
  background:#22C55E;
  border-color:#22C55E;
  color:#FFFFFF;
  box-shadow:
    0 0 0 7px rgba(34,197,94,.14),
    0 0 0 14px rgba(34,197,94,.055),
    0 8px 20px rgba(34,197,94,.22);
}

.quote-step.active .step-label{
  color:#22C55E;
  font-weight:900;
}

/* Etape future. */
.quote-step.pending .step-index{
  background:#F8FAFC;
  border-color:#CBD5E1;
  color:#94A3B8;
}

.quote-step.pending .step-label{
  color:#94A3B8;
}

/*
 |--------------------------------------------------------------------------
 | Responsivite
 |--------------------------------------------------------------------------
 | Sur ecran reduit, chaque etape garde une largeur stable.
 | Le conteneur devient scrollable horizontalement, sans superposition.
 */
@media (max-width:1100px){
  .quote-steps{
    padding-bottom:10px;
  }

  .quote-step{
    flex:0 0 116px;
    min-width:116px;
  }

  .step-label{
    max-width:102px;
  }
}

@media (max-width:768px){
  .steps-card{
    padding:18px 14px 24px;
  }

  .steps-header{
    align-items:flex-start;
    flex-direction:column;
    gap:6px;
    margin-bottom:18px;
  }

  .quote-steps{
    --step-circle:36px;
    --step-line:3px;
    --step-gap:9px;
  }

  .quote-step{
    flex-basis:106px;
    min-width:106px;
  }

  .step-index{
    font-size:14px;
  }

  .step-label{
    max-width:94px;
    font-size:10.5px;
  }
}


.form-panel{
  display:grid;
  grid-template-columns:1.1fr .9fr;
  gap:18px;
}

.form-main,
.form-side{
  border:1px solid var(--line);
  border-radius:18px;
  background:#fff;
  box-shadow:0 14px 38px rgba(15,23,42,.06);
}

.form-main{
  padding:20px;
}

.form-side{
  padding:18px;
  background:#FFFFFF;
}

.section-title{
  display:flex;
  align-items:center;
  gap:10px;
  margin:0 0 18px;
  color:var(--text);
  font-size:16px;
  font-weight:900;
}

.section-title span{
  width:34px;
  height:34px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  border-radius:12px;
  background:#FFFFFF;
  border:1px solid var(--line);
  color:var(--primary);
}

.reply-form .form-label{
  margin-bottom:7px;
  color:var(--text);
  font-size:13px;
  font-weight:900;
}

.reply-form .form-control,
.reply-form .form-select{
  min-height:46px;
  border:1px solid #CBD5E1;
  border-radius:14px;
  background:#FFFFFF;
  color:var(--text);
  font-size:14px;
  box-shadow:none;
  transition:border-color .2s ease, box-shadow .2s ease, background .2s ease;
}

/* Custom chevron for dropdown lists. */
.reply-form .form-select{
  appearance:none;
  -webkit-appearance:none;
  -moz-appearance:none;
  padding-right:44px;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='M5 7.5L10 12.5L15 7.5' stroke='%230D3261' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat:no-repeat;
  background-position:right 15px center;
  background-size:16px 16px;
}

.reply-form textarea.form-control{
  min-height:180px;
  resize:vertical;
  line-height:1.55;
}

.reply-form .form-control:focus,
.reply-form .form-select:focus{
  border-color:var(--cyan);
  background:#fff;
  box-shadow:0 0 0 .22rem rgba(0,178,255,.16);
}

.reply-form .form-control.is-invalid,
.reply-form .form-select.is-invalid{
  border-color:#DC3545;
  background:#FFFFFF;
}

.reply-form .invalid-feedback,
.reply-form .help-block,
.reply-form .text-danger{
  margin-top:6px;
  color:var(--danger) !important;
  font-size:12px;
  font-weight:800;
}

.input-hint{
  margin-top:6px;
  color:var(--muted);
  font-size:12px;
}

.file-uploader{
  position:relative;
  min-height:174px;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  gap:12px;
  padding:18px;
  border:2px dashed rgba(13,50,97,.22);
  border-radius:18px;
  background:#FFFFFF;
  text-align:center;
  transition:border-color .2s ease, background .2s ease, transform .2s ease;
}

.file-uploader:hover,
.file-uploader.drag-over{
  border-color:var(--cyan);
  background:#FFFFFF;
  transform:translateY(-1px);
}

.file-icon{
  width:58px;
  height:58px;
  display:flex;
  align-items:center;
  justify-content:center;
  border-radius:18px;
  background:#FFFFFF;
  border:1px solid var(--line);
  box-shadow:0 10px 22px rgba(15,23,42,.07);
  color:var(--primary);
  font-size:26px;
}

.file-copy{
  width:100%;
  max-width:100%;
  min-width:0;
  text-align:center;
}

.file-name{
  display:block;
  width:100%;
  max-width:100%;
  min-width:0;
  color:var(--text);
  font-size:14px;
  font-weight:900;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}

.file-hint{
  color:var(--muted);
  font-size:12px;
  font-weight:700;
}

.file-actions{
  display:flex;
  justify-content:center;
  flex-wrap:wrap;
  gap:10px;
}

.btn-file{
  min-height:40px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  padding:0 14px;
  border:1px solid rgba(13,50,97,.18);
  border-radius:13px;
  background:#fff;
  color:var(--primary);
  font-size:13px;
  font-weight:900;
  transition:transform .2s ease, box-shadow .2s ease, background .2s ease;
}

.btn-file:hover{
  background:#FFFFFF;
  transform:translateY(-1px);
  box-shadow:0 10px 22px rgba(15,23,42,.08);
}

.btn-file-danger{
  color:var(--danger);
  border-color:rgba(180,35,44,.22);
}

.btn-file-danger:hover{
  background:#FFFFFF;
}

.file-error{
  display:none;
  margin-top:8px;
  color:var(--danger);
  font-size:12px;
  font-weight:900;
}

.quote-meta{
  margin-top:16px;
  padding:14px;
  border:1px solid #E6EEF7;
  border-radius:18px;
  background:#FFFFFF;
}

.quote-meta-title{
  display:flex;
  align-items:center;
  gap:8px;
  margin:0 0 10px;
  color:var(--text);
  font-size:13px;
  font-weight:900;
}

.quote-meta-list{
  display:grid;
  gap:8px;
}

.quote-meta-item{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  color:var(--muted);
  font-size:12px;
  font-weight:800;
}

.quote-meta-item strong{
  color:var(--text);
  text-align:right;
}

.quote-summary{
  margin-top:16px;
  padding:15px;
  border-radius:18px;
  border:1px solid #E6EEF7;
  background:#FFFFFF;
}

.summary-row{
  display:flex;
  justify-content:space-between;
  gap:12px;
  padding:9px 0;
  border-bottom:1px solid #E6EEF7;
  color:var(--muted);
  font-size:13px;
  font-weight:700;
}

.summary-row:last-child{
  border-bottom:0;
}

.summary-row strong{
  color:var(--text);
  text-align:right;
}

.action-bar{
  display:flex;
  align-items:center;
  justify-content:flex-end;
  gap:12px;
  margin-top:18px;
  padding-top:18px;
  border-top:1px solid var(--line-2);
}

.btn-reply-submit{
  min-width:210px;
  height:50px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:9px;
  border:0;
  border-radius:9px;
  background: #286ce2ff;
  color:#fff;
  font-size:14px;
  font-weight:900;
  box-shadow:0 14px 28px rgba(29,78,216,.22);
  transition:transform .2s ease, box-shadow .2s ease, opacity .2s ease;
}

.btn-reply-submit:hover{
  transform:translateY(-2px);
  box-shadow:0 18px 36px rgba(29,78,216,.28);
}

.btn-reply-submit:disabled{
  cursor:not-allowed;
  opacity:.55;
  transform:none;
  box-shadow:none;
  filter:grayscale(.15);
}

.btn-reset-ui{
  height:50px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  padding:0 16px;
  border:1px solid #CBD5E1;
  border-radius:9px;
  background: #286ce2ff;
  color:#fff;
  font-size:14px;
  font-weight:900;
}

.btn-reset-ui:hover{
  background:#FFFFFF;
  color:darkred;
}

.reply-loading{
  position:fixed;
  inset:0;
  z-index:10050;
  display:none;
  align-items:center;
  justify-content:center;
  background:rgba(15,23,42,.34);
  backdrop-filter:blur(5px);
}

.loading-box{
  min-width:280px;
  display:flex;
  align-items:center;
  gap:14px;
  padding:18px;
  border:1px solid rgba(255,255,255,.55);
  border-radius:18px;
  background:rgba(255,255,255,.95);
  box-shadow:0 28px 70px rgba(15,23,42,.35);
}

.loading-spinner{
  width:44px;
  height:44px;
  border:4px solid #E2E8F0;
  border-top-color:var(--primary-2);
  border-radius:50%;
  animation:spin .75s linear infinite;
}

@keyframes spin{
  to{transform:rotate(360deg)}
}

.loading-title{
  color:var(--text);
  font-size:14px;
  font-weight:900;
}

.loading-subtitle{
  margin-top:2px;
  color:var(--muted);
  font-size:12px;
  font-weight:700;
}

@media (max-width:992px){
  .form-panel{
    grid-template-columns:1fr;
  }

}

@media (max-width:768px){
  .reply-page{
    padding:22px 12px;
  }

  .reply-hero,
  .reply-body{
    padding-left:18px;
    padding-right:18px;
  }

  .hero-top{
    flex-direction:column;
  }

  .hero-actions,
  .btn-back-request{
    width:100%;
  }

  .simple-header{
    align-items:flex-start;
    gap:14px;
  }

  .simple-header-icon{
    width:54px;
    height:54px;
    flex-basis:54px;
    border-radius:18px;
    font-size:24px;
  }

  .reply-title{
    font-size:24px;
  }

  .steps-header{
    align-items:flex-start;
    flex-direction:column;
  }

  .quote-step{
    min-width:124px;
  }

  .action-bar{
    align-items:stretch;
    flex-direction:column-reverse;
  }

  .btn-reply-submit,
  .btn-reset-ui{
    width:100%;
  }
}

/* Compact SweetAlert confirmation dialogs */
.swal2-popup.can-swal-compact{
  width:340px !important;
  padding:18px 18px 16px !important;
  border-radius:18px !important;
  box-shadow:0 20px 50px rgba(15,23,42,.18) !important;
}

.swal2-popup.can-swal-compact .swal2-icon{
  width:44px !important;
  height:44px !important;
  margin:4px auto 10px !important;
}

.swal2-popup.can-swal-compact .swal2-title{
  padding:0 !important;
  color:#0F172A !important;
  font-size:18px !important;
  font-weight:900 !important;
  line-height:1.25 !important;
}

.swal2-popup.can-swal-compact .swal2-html-container{
  margin:8px 0 0 !important;
  color:#64748B !important;
  font-size:13px !important;
  line-height:1.45 !important;
}

.swal2-popup.can-swal-compact .swal2-actions{
  width:100% !important;
  margin:16px 0 0 !important;
  gap:8px !important;
}

.swal2-popup.can-swal-compact .swal2-confirm,
.swal2-popup.can-swal-compact .swal2-cancel{
  min-width:108px !important;
  margin:0 !important;
  padding:9px 13px !important;
  border-radius:11px !important;
  font-size:12px !important;
  font-weight:900 !important;
  box-shadow:none !important;
}

.swal2-popup.can-swal-compact .swal2-close{
  font-size:22px !important;
}


/* ==========================================================
   PROFESSIONAL AIRCRAFT MAINTENANCE UI OVERRIDES
   Objectif : donner une apparence plus professionnelle pour une
   application MRO / aircraft maintenance sans modifier le flux métier.
   ========================================================== */
.reply-page{
  background:
    radial-gradient(circle at top left, rgba(13,50,97,.08), transparent 34%),
    linear-gradient(180deg, #F7FAFD 0%, #FFFFFF 48%, #F8FBFF 100%);
}

.reply-shell{
  max-width:1280px;
}

.reply-card{
  border-radius:22px;
  border:1px solid rgba(184,203,225,.78);
  box-shadow:0 24px 70px rgba(13,50,97,.10);
}

.reply-hero{
  padding:26px 30px 22px;
  background:
    linear-gradient(135deg, rgba(13,50,97,.045), rgba(0,178,255,.025)),
    #FFFFFF;
}

.simple-header-icon{
  width:60px;
  height:60px;
  border-radius:18px;
  background:linear-gradient(135deg, #FFFFFF, #F4F8FC);
  border:1px solid #CFE0F2;
  color:var(--primary);
}

.reply-title{
  color:#081F3D;
  font-size:28px;
  letter-spacing:-.03em;
}

.reply-subtitle{
  color:#5F6F85;
}

.hero-badges{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin-top:12px;
}

.hero-badges span{
  display:inline-flex;
  align-items:center;
  gap:7px;
  min-height:30px;
  padding:0 11px;
  border:1px solid #D6E4F3;
  border-radius:999px;
  background:#FFFFFF;
  color:#0D3261;
  font-size:12px;
  font-weight:850;
  box-shadow:0 8px 18px rgba(13,50,97,.05);
}

.btn-back-request{
  background:#0B1F3A;
  border-color:#0B1F3A;
  border-radius:12px;
  box-shadow:0 14px 26px rgba(11,31,58,.18);
}

.btn-back-request:hover{
  color:#0B1F3A;
  border-color:#BFD4EA;
}

.ops-strip{
  display:grid;
  grid-template-columns:repeat(3, minmax(0, 1fr));
  gap:12px;
  margin-bottom:18px;
}

.ops-item{
  display:flex;
  align-items:center;
  gap:12px;
  min-height:74px;
  padding:14px;
  border:1px solid #D9E6F4;
  border-radius:16px;
  background:linear-gradient(180deg, #FFFFFF, #FAFCFF);
  box-shadow:0 12px 28px rgba(13,50,97,.045);
}

.ops-icon{
  width:40px;
  height:40px;
  flex:0 0 40px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  border-radius:14px;
  background:#EFF6FF;
  color:#0D3261;
  border:1px solid #D7E7F8;
  font-size:18px;
}

.ops-item strong{
  display:block;
  color:#0F172A;
  font-size:13px;
  font-weight:950;
  line-height:1.2;
}

.ops-item small{
  display:block;
  margin-top:4px;
  color:#64748B;
  font-size:11.5px;
  font-weight:750;
  line-height:1.25;
}

.existing-quote-document{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:14px;
  margin-bottom:12px;
  padding:12px 14px;
  border:1px solid #BFD1E6;
  border-radius:14px;
  background:#F8FCFF;
}

.existing-quote-info{
  display:flex;
  align-items:center;
  gap:10px;
  min-width:0;
}

.existing-quote-info > i{
  color:#087FB9;
  font-size:20px;
  flex:0 0 auto;
}

.existing-quote-copy{
  min-width:0;
}

.existing-quote-label{
  color:#64748B;
  font-size:11px;
  font-weight:800;
}

.existing-quote-name{
  max-width:330px;
  overflow:hidden;
  color:var(--text);
  font-size:13px;
  font-weight:900;
  text-overflow:ellipsis;
  white-space:nowrap;
}

.existing-quote-actions{
  display:flex;
  flex:0 0 auto;
  gap:8px;
}

.existing-quote-action{
  min-height:36px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:6px;
  padding:7px 12px;
  border:1px solid #BFD1E6;
  border-radius:10px;
  background:#FFFFFF;
  color:#0D3261;
  font-size:12px;
  font-weight:900;
  text-decoration:none;
}

.existing-quote-action:hover{
  border-color:#0EA5E9;
  color:#087FB9;
}

@media (max-width:575.98px){
  .existing-quote-document{
    align-items:flex-start;
    flex-direction:column;
  }

  .existing-quote-actions{
    width:100%;
  }

  .existing-quote-action{
    flex:1 1 0;
  }
}

.request-source-document{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:16px;
  margin-bottom:18px;
  padding:14px 16px;
  border:1px solid #CFE0F2;
  border-radius:14px;
  background:#F8FCFF;
}

.request-source-document-info{
  display:flex;
  align-items:center;
  gap:12px;
  min-width:0;
}

.request-source-document-icon{
  width:40px;
  height:40px;
  flex:0 0 40px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  border-radius:12px;
  background:#E0F2FE;
  color:#0369A1;
  font-size:18px;
}

.request-source-document strong,
.request-source-document small{
  display:block;
}

.request-source-document strong{
  color:#0F172A;
  font-size:13px;
  font-weight:900;
}

.request-source-document small{
  margin-top:3px;
  color:#64748B;
  font-size:12px;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}

.btn-request-document{
  min-height:38px;
  flex:0 0 auto;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:7px;
  padding:0 13px;
  border:1px solid #0EA5E9;
  border-radius:10px;
  background:#0EA5E9;
  color:#FFFFFF !important;
  font-size:12px;
  font-weight:900;
  text-decoration:none;
}

.btn-request-document:hover{
  background:#0284C7;
  color:#FFFFFF !important;
  text-decoration:none;
}

.steps-card{
  background:linear-gradient(180deg, #FFFFFF, #FBFDFF);
  border-color:#D3E2F2;
  border-radius:20px;
  padding:22px 22px 28px;
}

.steps-title{
  color:#0B1F3A;
  letter-spacing:-.01em;
}

.quote-steps{
  --step-circle:38px;
  --step-line:4px;
  --step-gap:12px;
  padding-top:10px;
}

.quote-step{
  min-width:112px;
}

.quote-step::before{
  background:#DCE5EE;
}

.step-index{
  font-size:14px;
  background:#F9FBFD;
  border-color:#C8D7E8;
  color:#7890AB;
}

.quote-step.done .step-index,
.quote-step.active .step-index{
  background:#16C56E;
  border-color:#16C56E;
}

.quote-step.done::before,
.quote-step.active::before{
  background:#16C56E;
}

.step-label{
  max-width:104px;
  margin-top:9px;
  font-size:11px;
  color:#7D8FAA;
}

.quote-step.active .step-label,
.quote-step.done .step-label{
  color:#11A75B;
}

.form-panel{
  grid-template-columns:minmax(0, 1.15fr) minmax(360px, .85fr);
  gap:20px;
}

.form-main,
.form-side{
  border-color:#D4E2F1;
  border-radius:20px;
  box-shadow:0 18px 42px rgba(13,50,97,.065);
}

.form-main{
  background:linear-gradient(180deg, #FFFFFF, #FCFDFF);
}

.form-side{
  background:linear-gradient(180deg, #FFFFFF, #FAFCFF);
}

.section-title{
  color:#0B1F3A;
  font-size:17px;
}

.section-title span{
  border-radius:13px;
  background:#F5FAFF;
  border-color:#CFE0F2;
}

.reply-form .form-label{
  color:#0B1F3A;
}

.reply-form .form-control,
.reply-form .form-select{
  min-height:48px;
  border-radius:15px;
  border-color:#C9D8EA;
  background:#FFFFFF;
}

.reply-form textarea.form-control{
  min-height:178px;
}

.input-hint,
.file-hint{
  color:#8391A5;
}

.file-uploader{
  min-height:178px;
  border-color:#BFD1E6;
  background:
    linear-gradient(180deg, rgba(255,255,255,.98), rgba(249,252,255,.98));
}

.file-uploader:hover,
.file-uploader.drag-over{
  border-color:#0EA5E9;
  background:#F8FCFF;
}

.file-icon{
  color:#0D3261;
  border-radius:16px;
  background:#FFFFFF;
}

.btn-file{
  border-radius:12px;
}

.quote-meta,
.quote-summary{
  border-color:#DCE7F3;
  background:#FFFFFF;
}

.quote-meta-title{
  color:#0B1F3A;
}

.btn-reply-submit{
  border-radius:9px;
  background: #1D4ED8;
}

.btn-reset-ui{
  border-radius:12px;
  background:#FFFFFF;
  color:#B4232C;
  border-color:#F1C4CA;
}

.btn-reset-ui:hover{
  background:#FFF5F6;
  color:#8F1D25;
}

@media (max-width:1100px){
  .ops-strip{
    grid-template-columns:1fr;
  }

  .form-panel{
    grid-template-columns:1fr;
  }
}

@media (max-width:576px){
  .request-source-document{
    align-items:flex-start;
    flex-direction:column;
  }

  .btn-request-document{
    width:100%;
  }
}

@media (max-width:768px){
  .reply-card{
    border-radius:18px;
  }

  .hero-badges{
    gap:6px;
  }

  .hero-badges span{
    width:100%;
    justify-content:flex-start;
  }

  .ops-item{
    align-items:flex-start;
  }

  .quote-steps{
    --step-circle:34px;
    --step-line:3px;
    --step-gap:10px;
  }

  .quote-step{
    flex:0 0 108px;
    min-width:108px;
  }
}

.request-context-side{
  align-self:start;
}

.request-overview-note{
  margin:-9px 0 14px;
  color:#64748B;
  font-size:12px;
  line-height:1.45;
}

.request-overview-list{
  padding:4px 14px;
  border:1px solid #DCE7F3;
  border-radius:14px;
  background:#FFFFFF;
}

.request-overview-list .summary-row{
  align-items:flex-start;
}

.request-overview-list .summary-row span{
  display:inline-flex;
  align-items:center;
  gap:7px;
  flex:0 0 42%;
}

.request-overview-list .summary-row strong{
  min-width:0;
  overflow-wrap:anywhere;
}

.request-context-side .ad-zone{
  margin-top:18px;
  border-radius:14px;
}

.request-context-side .ad-carousel,
.request-context-side .ad-slider,
.request-context-side .ad-slide,
.request-context-side .ad-media-wrap{
  height:100%;
  min-height:0;
}


/* ==========================================================
   PROFESSIONAL ADVERTISING CAROUSEL
   ========================================================== */
.ad-zone{
  position:relative;
  align-self:start;
  width:100%;
  aspect-ratio:16 / 9;
  min-width:0;
  min-height:0;
  max-height:430px;
  padding:0;
  overflow:hidden;
  border:1px solid #D4E2F1;
  border-radius:20px;
  background:#071A31;
  box-shadow:0 18px 42px rgba(13,50,97,.10);
  isolation:isolate;
}

.ad-carousel,
.ad-slider,
.ad-slide,
.ad-media-wrap{
  width:100%;
  height:100%;
  min-height:0;
}

.ad-carousel{
  position:relative;
  overflow:hidden;
}

.ad-slider{
  display:flex;
  will-change:transform;
  transition:transform .72s cubic-bezier(.22,.61,.36,1);
}

.ad-slide{
  flex:0 0 100%;
  min-width:100%;
  position:relative;
  overflow:hidden;
  background:#071A31;
}

.ad-media-wrap{
  position:relative;
  overflow:hidden;
}

.ad-media{
  position:absolute;
  inset:0;
  display:block;
  width:100%;
  height:100%;
  object-fit:contain;
  object-position:center;
  background:#071A31;
  transform:none;
  transition:filter .35s ease;
}

.ad-slide.is-active .ad-media{
  transform:none;
}

.ad-video{
  object-fit:contain;
}

.ad-shade{
  position:absolute;
  inset:0;
  z-index:2;
  pointer-events:none;
  background:
    linear-gradient(180deg, rgba(4,17,34,.18) 0%, rgba(4,17,34,0) 38%),
    linear-gradient(0deg, rgba(4,17,34,.88) 0%, rgba(4,17,34,.10) 50%, rgba(4,17,34,.02) 72%);
}

.ad-topbar{
  position:absolute;
  z-index:4;
  top:18px;
  left:18px;
  right:18px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
}

.ad-sponsored,
.ad-counter{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:7px;
  min-height:32px;
  padding:0 11px;
  border:1px solid rgba(255,255,255,.28);
  border-radius:999px;
  color:#FFFFFF;
  background:rgba(7,26,49,.55);
  box-shadow:0 8px 24px rgba(0,0,0,.16);
  backdrop-filter:blur(10px);
  -webkit-backdrop-filter:blur(10px);
  font-size:11px;
  font-weight:900;
  letter-spacing:.035em;
  text-transform:uppercase;
}

.ad-counter{
  min-width:52px;
  letter-spacing:.02em;
}

.ad-caption{
  position:absolute;
  z-index:4;
  left:24px;
  right:24px;
  bottom:58px;
  display:flex;
  flex-direction:column;
  align-items:flex-start;
  gap:7px;
  color:#FFFFFF;
  text-shadow:0 2px 10px rgba(0,0,0,.35);
}

.ad-caption-kicker{
  color:#71D3FF;
  font-size:11px;
  font-weight:900;
  letter-spacing:.12em;
  text-transform:uppercase;
}

.ad-caption strong{
  max-width:90%;
  font-size:25px;
  font-weight:950;
  line-height:1.08;
  letter-spacing:-.025em;
}

.ad-caption small{
  max-width:86%;
  color:rgba(255,255,255,.80);
  font-size:12px;
  font-weight:700;
  line-height:1.45;
}

.ad-nav{
  position:absolute;
  z-index:6;
  top:50%;
  width:42px;
  height:42px;
  display:flex;
  align-items:center;
  justify-content:center;
  border:1px solid rgba(255,255,255,.52);
  border-radius:50%;
  color:#0D3261;
  background:rgba(255,255,255,.92);
  box-shadow:0 10px 28px rgba(0,0,0,.20);
  transform:translateY(-50%);
  transition:transform .2s ease, background .2s ease, opacity .2s ease;
  opacity:.88;
}

.ad-nav:hover{
  background:#FFFFFF;
  opacity:1;
  transform:translateY(-50%) scale(1.06);
}

.ad-nav-prev{ left:16px; }
.ad-nav-next{ right:16px; }

.ad-dots{
  position:absolute;
  z-index:6;
  left:50%;
  bottom:24px;
  display:flex;
  align-items:center;
  justify-content:center;
  gap:7px;
  transform:translateX(-50%);
}

.ad-dot{
  width:8px;
  height:8px;
  padding:0;
  border:0;
  border-radius:999px;
  background:rgba(255,255,255,.46);
  box-shadow:0 2px 8px rgba(0,0,0,.15);
  transition:width .25s ease, background .25s ease;
}

.ad-dot.is-active{
  width:26px;
  background:#FFFFFF;
}

.ad-progress{
  position:absolute;
  z-index:7;
  left:0;
  right:0;
  bottom:0;
  height:4px;
  background:rgba(255,255,255,.16);
}

.ad-progress span{
  display:block;
  width:0;
  height:100%;
  background:linear-gradient(90deg, #00B2FF, #FFFFFF);
}

.ad-progress span.is-running{
  animation:adProgress 7s linear forwards;
}

@keyframes adProgress{
  from{width:0}
  to{width:100%}
}

.ad-error-state{
  position:absolute;
  inset:0;
  z-index:8;
  display:none;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  gap:10px;
  padding:24px;
  color:#FFFFFF;
  text-align:center;
  background:linear-gradient(145deg, #0D3261, #071A31);
}

.ad-error-state i{
  font-size:40px;
  opacity:.75;
}

.ad-error-state strong{
  font-size:15px;
  font-weight:900;
}

.ad-error-state small{
  color:rgba(255,255,255,.72);
  font-size:12px;
}

.ad-slide.ad-media-error .ad-error-state{
  display:flex;
}

.ad-empty{
  width:100%;
  height:100%;
  min-height:0;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  gap:12px;
  padding:30px;
  text-align:center;
  background:
    radial-gradient(circle at top, rgba(0,178,255,.10), transparent 42%),
    linear-gradient(180deg, #F8FBFF, #FFFFFF);
}

.ad-empty-icon{
  width:64px;
  height:64px;
  display:flex;
  align-items:center;
  justify-content:center;
  border:1px solid #D3E2F2;
  border-radius:20px;
  color:#0D3261;
  background:#FFFFFF;
  box-shadow:0 14px 34px rgba(13,50,97,.10);
  font-size:28px;
}

.ad-empty strong{
  color:#0B1F3A;
  font-size:15px;
  font-weight:950;
}

.ad-empty small{
  max-width:320px;
  color:#718198;
  font-size:12px;
  font-weight:700;
  line-height:1.5;
}

@media (max-width:1100px){
  .ad-zone{
    aspect-ratio:16 / 9;
    max-height:430px;
  }
}

@media (max-width:768px){
  .ad-zone{
    aspect-ratio:16 / 9;
    max-height:none;
  }

  .ad-caption{
    left:18px;
    right:18px;
    bottom:52px;
  }

  .ad-caption strong{
    font-size:21px;
  }

  .ad-caption small{
    max-width:100%;
  }

  .ad-nav{
    width:38px;
    height:38px;
  }

  .ad-nav-prev{ left:12px; }
  .ad-nav-next{ right:12px; }
}

CSS);
?>

<!-- Loading overlay -->
<div class="reply-loading" id="reply-loading" aria-hidden="true">
  <div class="loading-box" role="status" aria-live="polite">
    <div class="loading-spinner" aria-hidden="true"></div>
    <div>
      <div class="loading-title">Submitting your quote…</div>
      <div class="loading-subtitle">Please wait a moment.</div>
    </div>
  </div>
</div>

<!-- SHARED FORM SYSTEM: presentation only; quotation submission rules remain unchanged. -->
<div class="reply-page can-form-page">
  <div class="reply-shell">
    <div class="reply-card">

      <div class="reply-hero">
        <div class="hero-top">
          <div class="simple-header">
            <div class="simple-header-icon" aria-hidden="true">
              <i class="bi bi-send-check"></i>
            </div>
            <div>
              <h1 class="reply-title"><?= Html::encode($this->title) ?></h1>
              <p class="reply-subtitle">
                Prepare a controlled MRO quotation for aircraft maintenance, including scope, commercial terms, currency, price, and the official quotation attachment.
              </p>

              <!-- Professional aviation context badges -->
              <div class="hero-badges" aria-label="Aircraft maintenance workflow context">
                <span><i class="bi bi-shield-check"></i> Controlled MRO workflow</span>
                <span><i class="bi bi-file-earmark-lock2"></i> Official quote required</span>
                <span><i class="bi bi-airplane-engines"></i> Aircraft maintenance</span>
              </div>
            </div>
          </div>

          <div class="hero-actions">
            <?= Html::a('<i class="bi bi-arrow-left"></i> Back to Request', Yii::$app->request->referrer ?: ['index'], [
              'class' => 'btn-back-request',
              'encode' => false,
            ]) ?>
          </div>
        </div>
      </div>

      <div class="reply-body">

        <div class="reply-flash">
          <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="alert alert-success">
              <span aria-hidden="true"><i class="bi bi-check-circle-fill"></i></span>
              <span><?= Yii::$app->session->getFlash('success') ?></span>
            </div>
          <?php endif; ?>

          <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger">
              <span aria-hidden="true"><i class="bi bi-exclamation-triangle-fill"></i></span>
              <span><?= Yii::$app->session->getFlash('error') ?></span>
            </div>
          <?php endif; ?>
        </div>

        <!-- Operational control strip for a professional aircraft maintenance application -->
        <div class="ops-strip" aria-label="MRO operational controls">
          <div class="ops-item">
            <span class="ops-icon"><i class="bi bi-wrench-adjustable-circle"></i></span>
            <div>
              <strong>MRO quotation</strong>
              <small>Technical and commercial quotation</small>
            </div>
          </div>
          <div class="ops-item">
            <span class="ops-icon"><i class="bi bi-paperclip"></i></span>
            <div>
              <strong>Document control</strong>
              <small>PDF, JPG or PNG quotation evidence</small>
            </div>
          </div>
          <div class="ops-item">
            <span class="ops-icon"><i class="bi bi-check2-square"></i></span>
            <div>
              <strong>Submission Requirements</strong>
              <small>Details, price and attachment required</small>
            </div>
          </div>
        </div>

        <?php if ($requestAttachmentUrl !== null): ?>
          <div class="request-source-document">
            <div class="request-source-document-info">
              <span class="request-source-document-icon" aria-hidden="true">
                <i class="bi bi-paperclip"></i>
              </span>
              <div>
                <strong>AO Request Document</strong>
                <small><?= Html::encode($requestAttachmentName) ?></small>
              </div>
            </div>

            <?= Html::a(
              '<i class="bi bi-eye"></i> View attachment',
              $requestAttachmentUrl,
              [
                'class' => 'btn-request-document',
                'target' => '_blank',
                'rel' => 'noopener',
                'encode' => false,
              ]
            ) ?>
          </div>
        <?php endif; ?>

        <?php
        /*
        |--------------------------------------------------------------------------
        | Request progress stepper - flux métier
        |--------------------------------------------------------------------------
        | 1 = Create Request  : la demande est créée par AO.
        | 2 = MRO Quote       : le MRO envoie son devis. Cette page correspond à cette étape.
        | 3 = PO Loaded       : AO charge le Purchase Order.
        | 4 = PO Accepted     : MRO accepte le PO.
        | 5 = Work Started    : MRO démarre le travail.
        | 6 = MRO Report      : MRO soumet le rapport.
        | 7 = AO Feedback     : AO donne son feedback.
        | 8 = Closed          : la demande est clôturée.
        |--------------------------------------------------------------------------
        */
        $currentStep = 2;

        $steps = [
          1 => [
            'label' => 'Request Created',
            'icon' => 'bi-person-fill',
          ],
          2 => [
            'label' => 'MRO Quote',
            'icon' => 'bi-file-earmark-text-fill',
          ],
          3 => [
            'label' => 'PO Uploaded',
            'icon' => 'bi-upload',
          ],
          4 => [
            'label' => 'PO Accepted',
            'icon' => 'bi-check-circle-fill',
          ],
          5 => [
            'label' => 'Work Started',
            'icon' => 'bi-play-circle-fill',
          ],
          6 => [
            'label' => 'Maintenance Report Submitted',
            'icon' => 'bi-clipboard-check-fill',
          ],
          7 => [
            'label' => 'AO Feedback',
            'icon' => 'bi-chat-dots-fill',
          ],
          8 => [
            'label' => 'Request Closed',
            'icon' => 'bi-check-lg',
          ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Calcul de la largeur de la ligne verte
        |--------------------------------------------------------------------------
        | Il y a 8 étapes et donc 7 segments entre les cercles.
        | currentStep = 1 => 0%
        | currentStep = 2 => 14.28%
        | currentStep = 8 => 100%
        */
        $totalSteps = count($steps);
        $progressWidth = $totalSteps > 1
          ? (($currentStep - 1) / ($totalSteps - 1)) * 100
          : 0;
        ?>

        <!-- Request progress steps -->
        <div class="steps-card">
          <div class="steps-header">
            <h2 class="steps-title">Request progress</h2>
            <div class="steps-note">
              Step <?= Html::encode($currentStep) ?> of <?= Html::encode($totalSteps) ?>
            </div>
          </div>

          <div class="quote-steps"
               aria-label="Request progress"
               style="--progress-width: <?= Html::encode($progressWidth) ?>%;">

            <?php foreach ($steps as $number => $step): ?>
              <?php
              /*
              |--------------------------------------------------------------------
              | Classe visuelle de l'étape
              |--------------------------------------------------------------------
              | done    = étape déjà terminée
              | active  = étape actuelle
              | pending = étape future
              */
              if ($number < $currentStep) {
                $stepClass = 'done';
              } elseif ($number === $currentStep) {
                $stepClass = 'active';
              } else {
                $stepClass = 'pending';
              }
              ?>

              <div class="quote-step <?= Html::encode($stepClass) ?>">
                <span class="step-index" aria-hidden="true">
                  <i class="bi <?= Html::encode($step['icon']) ?>"></i>
                </span>

                <span class="step-label">
                  <?= Html::encode($step['label']) ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <?php $form = ActiveForm::begin([
          'id' => 'reply-form',
          'options' => [
            'enctype' => 'multipart/form-data',
            'class' => 'reply-form',
            'novalidate' => true,
          ],
          'enableClientValidation' => true,
          'enableAjaxValidation' => false,
          'fieldConfig' => [
            'errorOptions' => ['class' => 'invalid-feedback d-block'],
            'labelOptions' => ['class' => 'form-label'],
            'inputOptions' => ['class' => 'form-control'],
          ],
        ]); ?>

          <div class="form-panel">
            <div class="form-main">
              <h2 class="section-title"><span><i class="bi bi-pencil-square"></i></span>Technical & commercial quote</h2>

              <?= $form->field($model, 'Description')
                ->textarea([
                  'rows' => 7,
                  'placeholder' => 'Describe the maintenance scope, exclusions, lead time, manpower, tooling, parts assumptions, and commercial conditions...',
                ])
                ->hint('Recommended: include scope of work, aircraft downtime impact, exclusions, lead time, warranty/validity, and operational constraints.', ['class' => 'input-hint'])
                ->label('Scope of Work / Technical Proposal <span class="text-danger">*</span>') ?>

              <div class="row">
                <div class="col-md-6">
                  <?php
                    $currencies = Currency::find()->orderBy(['code' => SORT_ASC])->all();

                    $currencyList = [];
                    foreach ($currencies as $currency) {
                      $currencyList[$currency->code] = $currency->name . ' ' . $currency->symbol . ' (' . $currency->code . ')';
                    }
                  ?>

                  <?= $form->field($model, 'currency')
                    ->dropDownList($currencyList, [
                      'prompt' => 'Select currency',
                      'class' => 'form-select',
                    ])
                    ->label('Quotation Currency <span class="text-danger">*</span>') ?>
                </div>

                <div class="col-md-6">
                  <?= $form->field($model, 'price')
                    ->textInput([
                      'type' => 'number',
                      'step' => '0.01',
                      'min' => '0',
                      'placeholder' => '0.00',
                    ])
                    ->label('Total Quoted Amount <span class="text-danger">*</span>') ?>
                </div>
              </div>

              <!-- ===== ATTACHMENT (moved below Currency & Price) ===== -->
              <div class="mb-3 mt-2">
                <label class="form-label">
                  Official Quotation Document <span class="text-danger">*</span>
                </label>

                <?php if ($existingQuoteAttachmentUrl !== null): ?>
                  <div class="existing-quote-document">
                    <div class="existing-quote-info">
                      <i class="bi bi-file-earmark-check" aria-hidden="true"></i>
                      <div class="existing-quote-copy">
                        <div class="existing-quote-label">Existing quotation document</div>
                        <div class="existing-quote-name" title="<?= Html::encode($existingQuoteAttachmentName) ?>">
                          <?= Html::encode($existingQuoteAttachmentName) ?>
                        </div>
                      </div>
                    </div>

                    <div class="existing-quote-actions">
                      <?= Html::a('<i class="bi bi-eye" aria-hidden="true"></i> View', $existingQuoteAttachmentUrl, [
                        'class' => 'existing-quote-action',
                        'target' => '_blank',
                        'rel' => 'noopener noreferrer',
                        'encode' => false,
                      ]) ?>
                      <?= Html::a('<i class="bi bi-download" aria-hidden="true"></i> Download', $existingQuoteAttachmentUrl, [
                        'class' => 'existing-quote-action',
                        'download' => $existingQuoteAttachmentName,
                        'encode' => false,
                      ]) ?>
                    </div>
                  </div>
                <?php endif; ?>

                <?= $form->field($model, 'attachment', [
                    'template' => "{input}\n{error}",
                  ])
                  ->fileInput([
                    'id' => 'quote-file',
                    'accept' => 'image/png, image/jpeg, application/pdf',
                    'class' => 'd-none',
                  ])
                  ->label(false) ?>

                <div class="file-uploader" id="file-uploader">
                  <div class="file-icon" aria-hidden="true"><i class="bi bi-cloud-arrow-up"></i></div>
                  <div class="file-copy">
                    <div class="file-name" id="file-name"<?= $existingQuoteAttachmentName ? ' title="' . Html::encode($existingQuoteAttachmentName) . '"' : '' ?>>
                      <?= $existingQuoteAttachmentUrl !== null ? 'Current quotation will be retained' : 'No controlled document selected' ?>
                    </div>
                    <div class="file-hint" id="file-hint">
                      <?= $existingQuoteAttachmentUrl !== null
                        ? 'Select a new document only if you want to replace it.'
                        : 'Accepted evidence: PDF, JPG, PNG. Maximum size: 10 MB.' ?>
                    </div>
                  </div>

                  <div class="file-actions">
                    <button type="button" class="btn-file" id="btn-choose">
                      <span aria-hidden="true"><i class="bi bi-plus-lg"></i></span> Choose Document
                    </button>

                    <button type="button" class="btn-file btn-file-danger" id="btn-remove" style="display:none;">
                      <span aria-hidden="true"><i class="bi bi-x-lg"></i></span> Remove
                    </button>
                  </div>
                </div>

                <div class="file-error" id="file-error"></div>
              </div>

              <div class="action-bar">
                <button type="button" class="btn-reset-ui" id="btn-reset-ui">
                  <span aria-hidden="true"><i class="bi bi-arrow-counterclockwise"></i></span> Reset
                </button>

                <?= Html::button('<span aria-hidden="true"><i class="bi bi-send-check"></i></span> Submit Quote', [
                  'type' => 'button',
                  'class' => 'btn-reply-submit',
                  'id' => 'submit-button',
                  'disabled' => true,
                  'encode' => false,
                ]) ?>
              </div>
            </div>

            <?php
            $now = date('Y-m-d H:i:s');

            $adverts = \app\models\Advert::find()
              ->where(['status' => 'active'])
              ->andWhere(['<=', 'start_date', $now])
              ->andWhere(['>=', 'end_date', $now])
              ->orderBy(['advert_id' => SORT_DESC])
              ->all();
            ?>
            <aside class="form-side request-context-side" aria-label="Request overview">
              <h2 class="section-title">
                <span><i class="bi bi-clipboard2-check"></i></span>
                Request overview
              </h2>
              <p class="request-overview-note">
                Verify the operational request data before submitting the quotation.
              </p>

              <div class="request-overview-list">
                <div class="summary-row">
                  <span><i class="bi bi-hash"></i> Request ID</span>
                  <strong>#<?= Html::encode($request->request_id) ?></strong>
                </div>
                <div class="summary-row">
                  <span><i class="bi bi-building"></i> Aircraft Operator / CAMO</span>
                  <strong><?= Html::encode($requestOperatorText) ?></strong>
                </div>
                <div class="summary-row">
                  <span><i class="bi bi-airplane"></i> Aircraft Type / Model</span>
                  <strong><?= Html::encode($requestAircraftText ?: 'N/A') ?></strong>
                </div>
                <div class="summary-row">
                  <span><i class="bi bi-card-text"></i> Aircraft Registration</span>
                  <strong><?= Html::encode($request->aircraft_registration ?: 'N/A') ?></strong>
                </div>
                <div class="summary-row">
                  <span><i class="bi bi-calendar-event"></i> ETA</span>
                  <strong><?= Html::encode($requestEtaText) ?></strong>
                </div>
                <div class="summary-row">
                  <span><i class="bi bi-calendar-check"></i> ETD</span>
                  <strong><?= Html::encode($requestEtdText) ?></strong>
                </div>
                <div class="summary-row">
                  <span><i class="bi bi-geo-alt"></i> Maintenance Location</span>
                  <strong><?= Html::encode($requestLocationText) ?></strong>
                </div>
              </div>

              <?php if (!empty($adverts)): ?>
                <div class="ad-zone" aria-label="Sponsored content">
                  <div class="ad-carousel" id="ad-carousel">
                    <div class="ad-slider" id="custom-ad-slider">

                    <?php foreach ($adverts as $index => $advert): ?>
                      <?php
                        $isUrl = !empty($advert->use_url) && !empty($advert->url);

                        if ($isUrl) {
                          $contentUrl = $advert->url;
                        } else {
                          $contentUrl = Yii::getAlias('@web/uploads/') . ltrim((string) $advert->content, '/');
                        }

                        $advertType = strtolower(trim((string) $advert->advert_type));
                        $advertTitle = !empty($advert->title)
                          ? $advert->title
                          : 'Aviation partner';
                      ?>

                      <article
                        class="ad-slide <?= $index === 0 ? 'is-active' : '' ?>"
                        data-ad-index="<?= (int) $index ?>"
                      >
                        <div class="ad-media-wrap">

                          <?php if ($advertType === 'photo'): ?>
                            <?= Html::img($contentUrl, [
                              'class' => 'ad-media',
                              'alt' => Html::encode($advertTitle),
                              'loading' => $index === 0 ? 'eager' : 'lazy',
                              'onerror' => "this.closest('.ad-slide').classList.add('ad-media-error');",
                            ]) ?>
                          <?php else: ?>
                            <video
                              class="ad-media ad-video"
                              muted
                              loop
                              playsinline
                              preload="metadata"
                              <?= $index === 0 ? 'autoplay' : '' ?>
                              onerror="this.closest('.ad-slide').classList.add('ad-media-error');"
                            >
                              <source
                                src="<?= Html::encode($contentUrl) ?>"
                                type="video/mp4"
                              >
                            </video>
                          <?php endif; ?>

                          <div class="ad-shade" aria-hidden="true"></div>

                          <div class="ad-topbar">
                            <span class="ad-sponsored">
                              <i class="bi bi-megaphone"></i>
                              Sponsored
                            </span>

                            <span class="ad-counter">
                              <?= (int) ($index + 1) ?> / <?= count($adverts) ?>
                            </span>
                          </div>

                          <div class="ad-caption">
                            <span class="ad-caption-kicker">Core Aviation Network</span>
                            <strong><?= Html::encode($advertTitle) ?></strong>
                            <small>Professional aviation services and industry solutions</small>
                          </div>

                          <div class="ad-error-state">
                            <i class="bi bi-image"></i>
                            <strong>Advertisement unavailable</strong>
                            <small>The advertising media could not be loaded.</small>
                          </div>
                        </div>
                      </article>
                    <?php endforeach; ?>

                    </div>

                    <?php if (count($adverts) > 1): ?>
                      <button
                        type="button"
                        class="ad-nav ad-nav-prev"
                        id="ad-prev"
                        aria-label="Previous advertisement"
                      >
                        <i class="bi bi-chevron-left"></i>
                      </button>

                      <button
                        type="button"
                        class="ad-nav ad-nav-next"
                        id="ad-next"
                        aria-label="Next advertisement"
                      >
                        <i class="bi bi-chevron-right"></i>
                      </button>

                      <div class="ad-dots" aria-label="Advertisement navigation">
                        <?php foreach ($adverts as $index => $advert): ?>
                          <button
                            type="button"
                            class="ad-dot <?= $index === 0 ? 'is-active' : '' ?>"
                            data-ad-target="<?= (int) $index ?>"
                            aria-label="Show advertisement <?= (int) ($index + 1) ?>"
                          ></button>
                        <?php endforeach; ?>
                      </div>

                      <div class="ad-progress" aria-hidden="true">
                        <span id="ad-progress-bar"></span>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>
            </aside>
          </div>

        <?php ActiveForm::end(); ?>
      </div>
    </div>
  </div>
</div>

<?php
$this->registerJs(<<<JS
(function(){
  var form = document.getElementById('reply-form');
  var submitButton = document.getElementById('submit-button');
  var resetButton = document.getElementById('btn-reset-ui');
  var overlay = document.getElementById('reply-loading');

  var input = document.getElementById('quote-file');
  var uploader = document.getElementById('file-uploader');
  var btnChoose = document.getElementById('btn-choose');
  var btnRemove = document.getElementById('btn-remove');
  var fileName = document.getElementById('file-name');
  var fileHint = document.getElementById('file-hint');
  var fileError = document.getElementById('file-error');
  var attachmentStatus = document.getElementById('attachment-status');
  var hasExistingAttachment = {$hasExistingQuoteJs};
  var existingAttachmentName = {$existingQuoteNameJs};

  var MAX_MB = 10;
  var allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
  var isSubmitting = false;
  var submitConfirmed = false;

  function hasSweetAlert(){
    return typeof Swal !== 'undefined' && Swal && typeof Swal.fire === 'function';
  }

  function confirmAction(options, onConfirm){
    if(!hasSweetAlert()){
      if(window.confirm(options.fallback || options.title)){
        onConfirm();
      }
      return;
    }

    Swal.fire({
      title: options.title,
      text: options.text,
      icon: options.icon || 'question',
      width: options.width || 340,
      padding: '18px 18px 16px',
      customClass: {
        // SHARED FORM CONFIRMATION: common popup geometry; action logic is unchanged.
        popup: 'can-swal-compact can-form-swal',
        confirmButton: 'can-swal-confirm',
        cancelButton: 'can-swal-cancel'
      },
      showCancelButton: true,
      showCloseButton: false,
      // FORM DIALOG ACTIONS: every fallback action remains explicit and icon-led.
      confirmButtonText: options.confirmButtonText || '<i class="bi bi-check-circle"></i> Confirm',
      cancelButtonText: options.cancelButtonText || '<i class="bi bi-arrow-counterclockwise"></i> Review',
      reverseButtons: true,
      focusCancel: true,
      buttonsStyling: true,
      confirmButtonColor: options.confirmButtonColor || '#1D4ED8',
      cancelButtonColor: options.cancelButtonColor || '#64748B'
    }).then(function(result){
      if(result.isConfirmed){
        onConfirm();
      }
    });
  }

  function showInfoAlert(title, text, icon){
    if(!hasSweetAlert()){
      alert(text || title);
      return;
    }

    Swal.fire({
      title: title,
      text: text,
      icon: icon || 'info',
      width: 340,
      padding: '18px 18px 16px',
      customClass: {
        // SHARED FORM CONFIRMATION: common popup geometry; validation logic is unchanged.
        popup: 'can-swal-compact can-form-swal'
      },
      confirmButtonText: '<i class="bi bi-check-circle"></i> Got it',
      confirmButtonColor: '#1D4ED8'
    });
  }

  // Get form controls by model attribute name without depending on generated IDs.
  function getField(attribute){
    if(!form) return null;
    return form.querySelector('[name$="[' + attribute + ']"]');
  }

  var descriptionInput = getField('Description');
  var currencyInput = getField('currency');
  var priceInput = getField('price');

  // Format bytes into a readable file size.
  function humanSize(bytes){
    var units = ['B', 'KB', 'MB', 'GB'];
    var value = bytes;
    var index = 0;

    while(value >= 1024 && index < units.length - 1){
      value = value / 1024;
      index++;
    }

    return value.toFixed(index === 0 ? 0 : 1) + ' ' + units[index];
  }

  // Show or hide the custom file validation message.
  function setFileError(message){
    if(!fileError) return;

    fileError.style.display = message ? 'block' : 'none';
    fileError.textContent = message || '';
  }

  function showFieldState(field, isValid){
    if(!field) return;
    if(isValid){
      field.classList.remove('is-invalid');
    }else{
      field.classList.add('is-invalid');
    }
  }

  function isValidFile(file, showMessage){
    if(!file){
      if(hasExistingAttachment){
        if(showMessage) setFileError('');
        return true;
      }
      if(showMessage) setFileError('Please attach your quotation file.');
      return false;
    }

    if(allowedTypes.indexOf(file.type) === -1){
      if(showMessage) setFileError('Invalid file type. Please upload a PDF, JPG, or PNG file.');
      return false;
    }

    if(file.size > MAX_MB * 1024 * 1024){
      if(showMessage) setFileError('File is too large. Maximum size is ' + MAX_MB + ' MB.');
      return false;
    }

    if(showMessage) setFileError('');
    return true;
  }

  // Enable submit only when all required fields are valid.
  function refreshSubmitState(showMessages){
    if(!form || !submitButton || isSubmitting) return false;

    var descriptionValid = descriptionInput && descriptionInput.value.trim().length > 0;
    var currencyValid = currencyInput && currencyInput.value.trim().length > 0;
    var priceValue = priceInput ? parseFloat(priceInput.value) : NaN;
    var priceValid = priceInput && !isNaN(priceValue) && priceValue > 0;
    var file = input && input.files && input.files[0] ? input.files[0] : null;
    var fileValid = isValidFile(file, showMessages);

    if(showMessages){
      showFieldState(descriptionInput, descriptionValid);
      showFieldState(currencyInput, currencyValid);
      showFieldState(priceInput, priceValid);
    }

    var formIsValid = descriptionValid && currencyValid && priceValid && fileValid;
    submitButton.disabled = !formIsValid;
    return formIsValid;
  }

  // Reset the custom file uploader UI.
  function resetFile(){
    if(input) input.value = '';
    if(fileName){
      fileName.textContent = hasExistingAttachment ? 'Current quotation will be retained' : 'No controlled document selected';
      if(hasExistingAttachment && existingAttachmentName){
        fileName.setAttribute('title', existingAttachmentName);
      }else{
        fileName.removeAttribute('title');
      }
    }
    if(fileHint){
      fileHint.textContent = hasExistingAttachment
        ? 'Select a new document only if you want to replace it.'
        : 'Accepted evidence: PDF, JPG, PNG. Maximum size: 10 MB.';
    }
    if(btnRemove) btnRemove.style.display = 'none';
    if(attachmentStatus) attachmentStatus.textContent = hasExistingAttachment ? 'Existing evidence retained' : 'Awaiting evidence';
    setFileError('');
    refreshSubmitState(false);
  }

  // Validate the selected file before submitting.
  function validateFile(file){
    return isValidFile(file, true);
  }

  // Update the uploader after a valid file is selected.
  function updateFileUI(file){
    var label = 'File selected';

    if(file.type === 'application/pdf') label = 'PDF file';
    if(file.type === 'image/jpeg') label = 'JPG image';
    if(file.type === 'image/png') label = 'PNG image';

    if(fileName){
      fileName.textContent = file.name;
      fileName.setAttribute('title', file.name);
    }
    if(fileHint) fileHint.textContent = label + ' • ' + humanSize(file.size);
    if(btnRemove) btnRemove.style.display = 'inline-flex';
    if(attachmentStatus) attachmentStatus.textContent = 'Ready to upload';
    refreshSubmitState(false);
  }

  function submitAfterConfirmation(){
    if(!form || !submitButton || isSubmitting) return;

    if(!refreshSubmitState(true)){
      showInfoAlert(
        'Missing information',
        'Please complete the description, currency, price and attachment before submitting.',
        'warning'
      );
      return;
    }

    confirmAction({
      title: 'Submit quote?',
      text: 'Please confirm that the quote details and attachment are correct before sending.',
      icon: 'question',
      confirmButtonText: '<i class="bi bi-send-check"></i> Submit quote',
      cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review',
      fallback: 'Submit quote?'
    }, function(){
      isSubmitting = true;
      submitConfirmed = true;
      submitButton.disabled = true;
      submitButton.innerHTML = '<span class="reply-btn-spinner" aria-hidden="true"></span> Submitting...';

      if(overlay){
        overlay.style.display = 'flex';
        overlay.setAttribute('aria-hidden', 'false');
      }

      // Native submit avoids re-triggering this confirmation and prevents Yii/jQuery handlers from bypassing it.
      HTMLFormElement.prototype.submit.call(form);
    });
  }

  if(btnChoose && input){
    btnChoose.addEventListener('click', function(){
      input.click();
    });
  }

  if(btnRemove){
    btnRemove.addEventListener('click', function(){
      confirmAction({
        title: 'Remove file?',
        text: 'The selected attachment will be removed from this quote.',
        icon: 'warning',
        confirmButtonText: '<i class="bi bi-trash3"></i> Remove',
        cancelButtonText: '<i class="bi bi-file-earmark-check"></i> Keep file',
        confirmButtonColor: '#B4232C',
        fallback: 'Remove selected file?'
      }, function(){
        resetFile();
      });
    });
  }

  if(input){
    input.addEventListener('change', function(){
      var file = input.files && input.files[0] ? input.files[0] : null;

      if(!file){
        resetFile();
        return;
      }

      if(validateFile(file)){
        updateFileUI(file);
      }else{
        input.value = '';
        refreshSubmitState(false);
      }
    });
  }

  // Validate fields live and unlock the submit button only when the form is valid.
  [descriptionInput, currencyInput, priceInput].forEach(function(field){
    if(!field) return;

    field.addEventListener('input', function(){
      showFieldState(field, true);
      refreshSubmitState(false);
    });

    field.addEventListener('change', function(){
      showFieldState(field, true);
      refreshSubmitState(false);
    });

    field.addEventListener('blur', function(){
      refreshSubmitState(true);
    });
  });

  // Drag and drop support for the file uploader.
  if(uploader && input){
    ['dragenter', 'dragover'].forEach(function(eventName){
      uploader.addEventListener(eventName, function(event){
        event.preventDefault();
        uploader.classList.add('drag-over');
      });
    });

    ['dragleave', 'drop'].forEach(function(eventName){
      uploader.addEventListener(eventName, function(event){
        event.preventDefault();
        uploader.classList.remove('drag-over');
      });
    });

    uploader.addEventListener('drop', function(event){
      var file = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0]
        ? event.dataTransfer.files[0]
        : null;

      if(!file) return;

      if(!validateFile(file)){
        input.value = '';
        refreshSubmitState(false);
        return;
      }

      confirmAction({
        title: 'Use this file?',
        text: file.name + ' will be attached to your quote.',
        icon: 'question',
        confirmButtonText: '<i class="bi bi-paperclip"></i> Attach',
        cancelButtonText: '<i class="bi bi-arrow-counterclockwise"></i> Review',
        fallback: 'Attach this file?'
      }, function(){
        var dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        input.files = dataTransfer.files;
        updateFileUI(file);
        refreshSubmitState(false);
      });
    });
  }

  if(resetButton && form){
    resetButton.addEventListener('click', function(){
      confirmAction({
        title: 'Reset quote?',
        text: 'All entered details and the selected file will be cleared.',
        icon: 'warning',
        confirmButtonText: '<i class="bi bi-arrow-repeat"></i> Reset',
        cancelButtonText: '<i class="bi bi-pencil-square"></i> Keep editing',
        fallback: 'Reset this quote?'
      }, function(){
        form.reset();
        resetFile();

        var invalidFields = form.querySelectorAll('.is-invalid');
        invalidFields.forEach(function(field){
          field.classList.remove('is-invalid');
        });

        refreshSubmitState(false);
      });
    });
  }

  if(submitButton){
    submitButton.addEventListener('click', function(event){
      event.preventDefault();
      event.stopPropagation();
      submitAfterConfirmation();
    });
  }

  if(form){
    // Capture direct submit attempts, including pressing Enter, and force SweetAlert confirmation first.
    form.addEventListener('submit', function(event){
      if(submitConfirmed){
        return true;
      }

      event.preventDefault();
      event.stopImmediatePropagation();
      submitAfterConfirmation();
      return false;
    }, true);
  }

  // Professional advertising carousel
  var adCarousel = document.getElementById('ad-carousel');
  var adSlider = document.getElementById('custom-ad-slider');

  if(adCarousel && adSlider){
    var slides = Array.prototype.slice.call(
      adSlider.querySelectorAll('.ad-slide')
    );

    var dots = Array.prototype.slice.call(
      adCarousel.querySelectorAll('.ad-dot')
    );

    var btnNext = document.getElementById('ad-next');
    var btnPrev = document.getElementById('ad-prev');
    var progressBar = document.getElementById('ad-progress-bar');

    var slidesCount = slides.length;
    var currentIndex = 0;
    var autoplayTimer = null;
    var AUTOPLAY_DELAY = 7000;

    function restartProgress(){
      if(!progressBar) return;

      progressBar.classList.remove('is-running');
      progressBar.style.width = '0';

      void progressBar.offsetWidth;

      progressBar.classList.add('is-running');
    }

    function manageVideos(){
      slides.forEach(function(slide, index){
        var video = slide.querySelector('video');

        if(!video) return;

        if(index === currentIndex){
          var playPromise = video.play();

          if(playPromise && typeof playPromise.catch === 'function'){
            playPromise.catch(function(){});
          }
        }else{
          video.pause();
          video.currentTime = 0;
        }
      });
    }

    function updateCarousel(index){
      if(!slidesCount) return;

      if(index < 0) index = slidesCount - 1;
      if(index >= slidesCount) index = 0;

      currentIndex = index;

      adSlider.style.transform =
        'translate3d(-' + (currentIndex * 100) + '%, 0, 0)';

      slides.forEach(function(slide, slideIndex){
        slide.classList.toggle('is-active', slideIndex === currentIndex);
      });

      dots.forEach(function(dot, dotIndex){
        dot.classList.toggle('is-active', dotIndex === currentIndex);
        dot.setAttribute(
          'aria-current',
          dotIndex === currentIndex ? 'true' : 'false'
        );
      });

      manageVideos();
      restartProgress();
    }

    function stopAutoplay(){
      if(autoplayTimer){
        window.clearInterval(autoplayTimer);
        autoplayTimer = null;
      }

      if(progressBar){
        progressBar.classList.remove('is-running');
      }
    }

    function startAutoplay(){
      stopAutoplay();

      if(slidesCount <= 1 || document.hidden) return;

      restartProgress();

      autoplayTimer = window.setInterval(function(){
        updateCarousel(currentIndex + 1);
      }, AUTOPLAY_DELAY);
    }

    function goTo(index){
      updateCarousel(index);
      startAutoplay();
    }

    if(btnNext){
      btnNext.addEventListener('click', function(event){
        event.preventDefault();
        goTo(currentIndex + 1);
      });
    }

    if(btnPrev){
      btnPrev.addEventListener('click', function(event){
        event.preventDefault();
        goTo(currentIndex - 1);
      });
    }

    dots.forEach(function(dot){
      dot.addEventListener('click', function(){
        goTo(parseInt(dot.getAttribute('data-ad-target'), 10) || 0);
      });
    });

    adCarousel.addEventListener('mouseenter', stopAutoplay);
    adCarousel.addEventListener('mouseleave', startAutoplay);

    adCarousel.addEventListener('focusin', stopAutoplay);
    adCarousel.addEventListener('focusout', startAutoplay);

    document.addEventListener('visibilitychange', function(){
      if(document.hidden){
        stopAutoplay();
      }else{
        startAutoplay();
      }
    });

    var touchStartX = 0;
    var touchEndX = 0;

    adCarousel.addEventListener('touchstart', function(event){
      touchStartX = event.changedTouches[0].screenX;
    }, {passive:true});

    adCarousel.addEventListener('touchend', function(event){
      touchEndX = event.changedTouches[0].screenX;

      if(Math.abs(touchEndX - touchStartX) < 45) return;

      if(touchEndX < touchStartX){
        goTo(currentIndex + 1);
      }else{
        goTo(currentIndex - 1);
      }
    }, {passive:true});

    updateCarousel(0);
    startAutoplay();
  }

  refreshSubmitState(false);
})();
JS, \yii\web\View::POS_END);
?>
