<?php

use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use kartik\select2\Select2;
use app\models\Aircrafts;
use app\models\Requests;
use kartik\datetime\DateTimePicker;
use yii\web\JsExpression;

$this->title = 'Create Request';

/*
 * VALEUR VISUELLE INITIALE : un nouvel ActiveRecord n'importe pas toujours la
 * valeur DEFAULT de la base avant sa première sauvegarde. On affiche donc Routine
 * dès l'ouverture ; la règle default du modèle reste l'autorité côté serveur.
 */
if ($request->operational_priority === null || $request->operational_priority === '') {
    $request->operational_priority = Requests::PRIORITY_ROUTINE;
}

$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');

$this->registerCss(<<<CSS
:root {
    --deep-blue: #0D3261;
    --sky: #00B2FF;
    --gold: #EED811;
    --soft-border: #D8E4EE;
    --soft-bg: #F4F7FC;
    --card-bg: rgba(255, 255, 255, .96);
    --text-main: #0F172A;
    --text-muted: #64748B;
    --danger: #EF4444;
    --success: #22C55E;
    --shadow-main: 0 24px 60px rgba(13, 50, 97, .16);
    --shadow-soft: 0 10px 26px rgba(15, 23, 42, .08);
}

/* Global responsive safety */
html,
body {
    width: 100%;
    max-width: 100%;
    overflow-x: hidden !important;
}

*,
*::before,
*::after {
    box-sizing: border-box;
}

/* Page wrapper: no negative margin, no forced width */
.dash-content.create-request-page,
.create-request-page {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    min-height: calc(100vh - 70px);
    padding: 20px 22px 28px;
    margin: 0 !important;
    overflow-x: hidden !important;
    background:
        radial-gradient(circle at 12% 15%, rgba(0,178,255,.16) 0%, transparent 34%),
        radial-gradient(circle at 88% 8%, rgba(238,216,17,.15) 0%, transparent 30%),
        linear-gradient(145deg, #EFF5FF 0%, #FFFFFF 42%, #F8FBFF 100%);
}

.create-request-page .container,
.create-request-page .container-fluid {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    overflow-x: hidden !important;
}

.create-request-card {
    width: 100%;
    max-width: 1140px;
    min-width: 0;
    margin: 0 auto;
    background: var(--card-bg);
    border-radius: 22px;
    border: 1px solid rgba(216, 228, 238, .95);
    box-shadow: 0 20px 46px rgba(13, 50, 97, .14);
    overflow: hidden;
    position: relative;
}

.create-request-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 7px;
    background: linear-gradient(135deg, var(--deep-blue), var(--sky), var(--gold));
}

.create-request-header {
    padding: 22px 26px 18px;
    background:
        linear-gradient(135deg, rgba(255,255,255,.98), rgba(244,248,255,.96)),
        radial-gradient(circle at 100% 0%, rgba(0,178,255,.16), transparent 28%);
    border-bottom: 1px solid rgba(226,232,240,.85);
}

/* Header layout keeps the same dashboard identity while adding useful context badges. */
.create-request-header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    min-width: 0;
}

.create-request-header-badges {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
}

.create-request-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 34px;
    padding: 7px 12px;
    border-radius: 999px;
    background: #FFFFFF;
    border: 1px solid rgba(203,213,225,.9);
    color: var(--deep-blue);
    font-size: 12px;
    font-weight: 850;
    box-shadow: 0 8px 18px rgba(13,50,97,.08);
    white-space: nowrap;
}

.create-request-title-row {
    display: flex;
    align-items: center;
    gap: 16px;
    min-width: 0;
}

.create-request-icon {
    width: 56px;
    height: 56px;
    min-width: 56px;
    border-radius: 18px;
    background: linear-gradient(135deg, #E7F4FF, #FFFFFF);
    color: var(--deep-blue);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 14px 28px rgba(13,50,97,.17);
}

.create-request-icon i {
    font-size: 26px;
}

.create-request-title {
    margin: 0;
    font-size: 25px;
    font-weight: 800;
    color: var(--text-main);
    letter-spacing: -.02em;
    word-break: break-word;
}

.create-request-subtitle {
    margin-top: 4px;
    color: var(--text-muted);
    font-size: 13px;
}

.create-request-body {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    padding: 18px 20px 22px;
    overflow-x: hidden;
}

#create-request-form {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow-x: hidden;
}

.alert {
    border: none;
    border-radius: 14px;
    padding: 12px 16px;
    font-weight: 700;
    box-shadow: 0 8px 18px rgba(15,23,42,.08);
    word-break: break-word;
}

.alert-success {
    background: #DCFCE7;
    color: #166534;
}

.alert-danger {
    background: #FEE2E2;
    color: #991B1B;
}

/* Small helper note displayed above the form. */
.create-request-note {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    width: 100%;
    margin-bottom: 14px;
    padding: 11px 14px;
    border-radius: 16px;
    background: linear-gradient(135deg, #F8FBFF, #FFFFFF);
    border: 1px solid rgba(216,228,238,.95);
    color: var(--text-muted);
    font-size: 13px;
    font-weight: 650;
    box-shadow: var(--shadow-soft);
}

.create-request-note i {
    color: var(--sky);
    font-size: 18px;
    line-height: 1.1;
    flex: 0 0 auto;
}


.progress-bar-container {
    width: 100%;
    max-width: 100%;
    display: flex;
    gap: 8px;
    overflow-x: auto;
    overflow-y: hidden;
    padding: 13px;
    margin-bottom: 20px;
    background: linear-gradient(135deg, #F8FBFF, #FFFFFF);
    border: 1px solid var(--soft-border);
    border-radius: 18px;
    box-shadow: 0 8px 20px rgba(15,23,42,.05);
    -webkit-overflow-scrolling: touch;
}

.progress-bar-step {
    position: relative;
    flex: 0 0 auto;
    padding: 9px 14px;
    border-radius: 999px;
    background: #EEF2F7;
    color: #475569;
    font-size: 12px;
    font-weight: 800;
    white-space: nowrap;
}

.progress-bar-step::after {
    content: "\\F285";
    font-family: "bootstrap-icons";
    margin-left: 8px;
    font-size: 10px;
    color: currentColor;
}

.progress-bar-step:last-child::after {
    content: "";
    margin-left: 0;
}

.step-active {
    background: #DBEAFE;
    color: #1D4ED8;
    box-shadow: 0 6px 14px rgba(37,99,235,.18);
}

.form-section {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    background: #FFFFFF;
    border: 1px solid var(--soft-border);
    border-radius: 20px;
    padding: 20px;
    margin-bottom: 18px;
    box-shadow: var(--shadow-soft);
    overflow: hidden;
}

.form-section-title {
    display: flex;
    align-items: center;
    gap: 9px;
    margin: 0 0 6px;
    color: var(--text-main);
    font-size: 15px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .04em;
    word-break: break-word;
}

.form-section-title i {
    width: 30px;
    height: 30px;
    border-radius: 10px;
    background: #EFF6FF;
    color: var(--deep-blue);
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.form-section-subtitle {
    margin: 0 0 16px;
    color: var(--text-muted);
    font-size: 13px;
    font-weight: 600;
}


.form-grid {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.form-grid > div,
.form-grid .form-group,
.form-grid [class*="field-"] {
    min-width: 0;
    max-width: 100%;
}

.form-grid .full-width {
    grid-column: 1 / -1;
}

.create-request-page .control-label {
    color: var(--text-main);
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 7px;
    display: block;
    word-break: break-word;
}

.create-request-page .help-block {
    width: 100%;
    max-width: 100%;
    margin-top: 6px;
    color: var(--danger);
    font-size: 12px;
    font-weight: 700;
    word-break: break-word;
}

/* Empty validation messages stay hidden for a cleaner first load. */
.create-request-page .help-block:empty {
    display: none !important;
}

/* Inputs: never exceed parent width */
.create-request-page .form-control,
.create-request-page select.form-control,
.create-request-page textarea.form-control,
.create-request-page input[type="text"],
.create-request-page input[type="file"],
.create-request-page input[type="password"],
.create-request-page input[type="email"],
.create-request-page textarea,
.create-request-page select {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
    height: 44px;
    border-radius: 13px;
    border: 1px solid #CBD5E1;
    background-color: #FFFFFF;
    color: var(--text-main);
    font-weight: 650;
    font-size: 14px;
    padding: 10px 13px;
    box-shadow: none;
    transition: .2s ease;
}

.create-request-page textarea.form-control,
.create-request-page textarea {
    min-height: 118px;
    height: auto;
    resize: vertical;
    display: block;
}

.create-request-page .form-control:focus,
.create-request-page select.form-control:focus,
.create-request-page textarea.form-control:focus {
    border-color: var(--sky);
    box-shadow: 0 0 0 4px rgba(0,178,255,.13);
}

.create-request-page .form-control[disabled],
.create-request-page .form-control[readonly] {
    background: #F1F5F9;
    color: #475569;
    cursor: not-allowed;
}

.chevron-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image:
        linear-gradient(45deg, transparent 50%, #0D3261 50%),
        linear-gradient(135deg, #0D3261 50%, transparent 50%);
    background-position:
        calc(100% - 20px) calc(50% + 1px),
        calc(100% - 14px) calc(50% + 1px);
    background-size: 6px 6px, 6px 6px;
    background-repeat: no-repeat;
    padding-right: 42px !important;
}

/* Bootstrap / Kartik input groups */
.create-request-page .input-group,
.create-request-page .kv-datetime-picker,
.create-request-page .date,
.create-request-page .datetimepicker {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
    display: flex !important;
    flex-wrap: nowrap !important;
}

.create-request-page .input-group .form-control {
    width: 1% !important;
    min-width: 0 !important;
    flex: 1 1 auto !important;
}

.create-request-page .input-group-addon,
.create-request-page .input-group-text {
    width: auto !important;
    flex: 0 0 auto !important;
    border-radius: 13px 0 0 13px;
}

/* Select2 design */
.create-request-page .select2,
.create-request-page .select2-container,
.create-request-page .select2-container--krajee,
.create-request-page .select2-container--krajee-bs5 {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
}

.create-request-page .select2-container--krajee-bs5 .select2-selection,
.create-request-page .select2-container--krajee .select2-selection,
.create-request-page .select2-container .select2-selection--single {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
    height: 44px !important;
    border-radius: 13px !important;
    border: 1px solid #CBD5E1 !important;
    display: flex !important;
    align-items: center !important;
    box-shadow: none !important;
}

.create-request-page .select2-container--krajee-bs5 .select2-selection__rendered,
.create-request-page .select2-container--krajee .select2-selection__rendered,
.create-request-page .select2-container .select2-selection--single .select2-selection__rendered {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
    line-height: 42px !important;
    color: var(--text-main) !important;
    font-weight: 650 !important;
    padding-left: 13px !important;
    padding-right: 42px !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
}

.create-request-page .select2-container--krajee-bs5 .select2-selection__arrow,
.create-request-page .select2-container--krajee .select2-selection__arrow,
.create-request-page .select2-container .select2-selection--single .select2-selection__arrow {
    height: 42px !important;
    right: 12px !important;
}

.create-request-page .select2-container--krajee-bs5 .select2-selection__arrow b,
.create-request-page .select2-container--krajee .select2-selection__arrow b,
.create-request-page .select2-container .select2-selection--single .select2-selection__arrow b {
    border-color: #0D3261 transparent transparent transparent !important;
    border-width: 6px 5px 0 5px !important;
}

.create-request-page .select2-container--open .select2-selection {
    border-color: var(--sky) !important;
    box-shadow: 0 0 0 4px rgba(0,178,255,.12) !important;
}

.create-request-page .select2-selection__clear {
    display: none !important;
}

/* Hide DateTimePicker X / clear buttons */
.create-request-page .kv-datetime-remove,
.create-request-page .kv-date-remove,
.create-request-page .glyphicon-remove,
.create-request-page .input-group-addon .glyphicon-remove,
.create-request-page .input-group-text .glyphicon-remove,
.create-request-page .input-group-addon[title="Clear field"],
.create-request-page .input-group-text[title="Clear field"],
.create-request-page .input-group-addon[title="Clear"],
.create-request-page .input-group-text[title="Clear"] {
    display: none !important;
}

/* Custom English file input */
.custom-file-wrapper,
.custom-file-field {
    width: 100%;
    max-width: 100%;
    min-width: 0;
}

.custom-file-native {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    opacity: 0 !important;
    overflow: hidden !important;
    z-index: -1 !important;
}

.custom-file-display {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    min-height: 44px;
    border-radius: 14px;
    border: 1px dashed #94A3B8;
    background: linear-gradient(135deg, #FFFFFF, #F8FBFF);
    display: flex;
    align-items: center;
    overflow: hidden;
}

.custom-file-button {
    height: 44px;
    padding: 0 16px;
    background: #E2E8F0;
    color: var(--text-main);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    border-right: 1px solid #CBD5E1;
    cursor: pointer;
    white-space: nowrap;
    margin: 0;
    flex: 0 0 auto;
}

.custom-file-button:hover {
    background: #CBD5E1;
}

.custom-file-name {
    min-width: 0;
    flex: 1 1 auto;
    padding: 0 14px;
    color: var(--text-main);
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.form-actions {
    width: 100%;
    max-width: 100%;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 8px;
    padding-top: 16px;
    border-top: 1px solid rgba(216,228,238,.95);
}

.btn-create-request-submit,
.btn-back-requests {
    max-width: 100%;
    min-height: 46px;
    border-radius: 12px;
    padding: 12px 18px;
    font-weight: 800;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    transition: .2s ease;
    white-space: normal;
    text-align: center;
}

.btn-create-request-submit {
    min-width: 190px;
    background: #2563EB;
    color: #FFFFFF !important;
    box-shadow: 0 10px 22px rgba(37,99,235,.25);
}

.btn-create-request-submit:hover:not(:disabled) {
    transform: translateY(-2px);
    color: #FFFFFF !important;
    box-shadow: 0 14px 28px rgba(37,99,235,.32);
}

.btn-create-request-submit:disabled {
    opacity: .55;
    cursor: not-allowed;
    filter: grayscale(.2);
    transform: none !important;
    box-shadow: none !important;
}

.btn-back-requests {
    min-width: 165px;
    background: #FFFFFF;
    color: var(--deep-blue) !important;
    border: 1px solid rgba(203,213,225,.95);
    box-shadow: 0 8px 18px rgba(13,50,97,.08);
}

.btn-back-requests:hover {
    background: #F8FBFF;
    color: var(--deep-blue) !important;
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(13,50,97,.12);
}

#loading-spinner {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(15, 23, 42, .35);
    backdrop-filter: blur(3px);
    align-items: center;
    justify-content: center;
}

.spinner-box {
    max-width: calc(100vw - 32px);
    background: #FFFFFF;
    border-radius: 18px;
    padding: 20px 24px;
    box-shadow: 0 20px 45px rgba(15,23,42,.25);
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 800;
    color: var(--text-main);
}

.spinner-box img {
    width: 34px;
    height: 34px;
}

.has-error .form-control,
.has-error .select2-selection,
.has-error .custom-file-display {
    border-color: var(--danger) !important;
}

.has-success .form-control,
.has-success .select2-selection,
.has-success .custom-file-display {
    border-color: var(--success) !important;
}

@media (max-width: 992px) {
    .form-grid {
        grid-template-columns: 1fr;
    }

    .create-request-header-top {
        flex-direction: column;
        align-items: flex-start;
    }

    .create-request-header-badges {
        justify-content: flex-start;
    }

    .dash-content.create-request-page,
    .create-request-page {
        padding: 12px;
    }

    .create-request-header {
        padding: 18px;
    }

    .create-request-body {
        padding: 14px;
    }

    .create-request-title {
        font-size: 22px;
    }
}

@media (max-width: 576px) {
    .dash-content.create-request-page,
    .create-request-page {
        padding: 10px;
    }

    .create-request-card {
        border-radius: 16px;
    }

    .create-request-header {
        padding: 16px 12px;
    }

    .create-request-title-row {
        align-items: flex-start;
        gap: 10px;
    }

    .create-request-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 12px;
    }

    .create-request-icon i {
        font-size: 20px;
    }

    .create-request-title {
        font-size: 20px;
    }

    .create-request-subtitle {
        font-size: 12px;
    }

    .create-request-body {
        padding: 12px 10px 14px;
    }

    .form-section {
        padding: 12px;
        border-radius: 15px;
    }

    .form-section-title {
        font-size: 13px;
        line-height: 1.35;
    }

    .create-request-page .form-control,
    .create-request-page select.form-control,
    .create-request-page textarea.form-control,
    .create-request-page input[type="text"],
    .create-request-page input[type="file"],
    .create-request-page input[type="password"],
    .create-request-page input[type="email"],
    .create-request-page textarea,
    .create-request-page select {
        font-size: 13px;
        padding-left: 10px;
        padding-right: 10px;
    }

    .create-request-page textarea.form-control,
    .create-request-page textarea {
        min-height: 150px;
    }

    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .btn-create-request-submit,
    .btn-back-requests {
        width: 100%;
    }

    .custom-file-display {
        flex-direction: column;
        align-items: stretch;
    }

    .custom-file-button {
        width: 100%;
        border-right: 0;
        border-bottom: 1px solid #CBD5E1;
    }

    .custom-file-name {
        width: 100%;
        min-height: 42px;
        display: flex;
        align-items: center;
    }
}

@media (max-width: 380px) {
    .dash-content.create-request-page,
    .create-request-page {
        padding: 8px;
    }

    .create-request-body {
        padding: 10px 8px 12px;
    }

    .form-section {
        padding: 10px;
    }
}

/* =========================================================
   RESPONSIVE HARD FIX
   This block is intentionally placed at the end to override
   Bootstrap, Spur dashboard and Kartik widgets without breaking
   the dashboard layout on small screens.
========================================================= */

.create-request-page,
.create-request-page * {
    box-sizing: border-box !important;
}

.create-request-page {
    inline-size: 100% !important;
    max-inline-size: 100% !important;
    overflow-x: clip !important;
}

/* Two clean columns on desktop, one safe column on mobile. */
.create-request-page .form-grid {
    display: grid !important;
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    gap: 18px !important;
    inline-size: 100% !important;
    max-inline-size: 100% !important;
    min-inline-size: 0 !important;
}

.create-request-page .form-grid > *,
.create-request-page .form-group,
.create-request-page [class*="field-"] {
    inline-size: 100% !important;
    max-inline-size: 100% !important;
    min-inline-size: 0 !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
}

.create-request-page .full-width {
    grid-column: 1 / -1 !important;
}

/* Safer sections */
.create-request-page .create-request-card,
.create-request-page .create-request-body,
.create-request-page .form-section,
.create-request-page form,
.create-request-page .container,
.create-request-page .container-fluid {
    inline-size: 100% !important;
    max-inline-size: 100% !important;
    min-inline-size: 0 !important;
    overflow-x: clip !important;
}

/*
 * Les champs visuels et les widgets ne doivent jamais dépasser la carte.
 * Les radios, cases à cocher et champs cachés sont volontairement exclus :
 * leur largeur est pilotée par leur composant spécialisé. Les forcer à 100 %
 * détruisait notamment les cartes AOG/Urgent/Routine en production.
 */
.create-request-page input:not([type="radio"]):not([type="checkbox"]):not([type="hidden"]),
.create-request-page select,
.create-request-page textarea,
.create-request-page button,
.create-request-page .form-control,
.create-request-page .input-group,
.create-request-page .kv-datetime-picker,
.create-request-page .date,
.create-request-page .datetimepicker,
.create-request-page .select2,
.create-request-page .select2-container,
.create-request-page .select2-selection,
.create-request-page .custom-file-wrapper,
.create-request-page .custom-file-field,
.create-request-page .custom-file-display {
    inline-size: 100% !important;
    max-inline-size: 100% !important;
    min-inline-size: 0 !important;
}

.create-request-page textarea,
.create-request-page textarea.form-control {
    display: block !important;
    max-inline-size: 100% !important;
    resize: vertical;
    overflow-x: hidden !important;
}

/* DateTimePicker: input + icon without overflow */
.create-request-page .input-group,
.create-request-page .kv-datetime-picker .input-group {
    display: flex !important;
    flex-wrap: nowrap !important;
    align-items: stretch !important;
}

.create-request-page .input-group > .form-control,
.create-request-page .kv-datetime-picker .input-group > .form-control {
    flex: 1 1 0 !important;
    inline-size: 1% !important;
    min-inline-size: 0 !important;
}

.create-request-page .input-group-addon,
.create-request-page .input-group-text,
.create-request-page .input-group-prepend,
.create-request-page .input-group-append {
    flex: 0 0 auto !important;
    max-inline-size: 46px !important;
}

/* Buttons: aligned on desktop and stacked on mobile. */
.create-request-page .form-actions {
    inline-size: 100% !important;
    max-inline-size: 100% !important;
    display: flex !important;
    justify-content: flex-end !important;
}

/* Mobile: remove all dashboard and Bootstrap sources of horizontal overflow */
@media (max-width: 767.98px) {
    html,
    body {
        inline-size: 100% !important;
        max-inline-size: 100% !important;
        overflow-x: hidden !important;
    }

    .dash,
    .dash-app,
    .dash-content,
    main.dash-content.create-request-page,
    .create-request-page {
        inline-size: 100% !important;
        max-inline-size: 100% !important;
        min-inline-size: 0 !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        overflow-x: clip !important;
    }

    .dash-app {
        display: block !important;
        flex: none !important;
    }

    main.dash-content.create-request-page,
    .dash-content.create-request-page {
        padding: 10px !important;
    }

    .create-request-page .container,
    .create-request-page .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .create-request-page .create-request-header {
        padding: 16px 12px !important;
    }

    .create-request-page .create-request-body {
        padding: 12px 10px 14px !important;
    }

    .create-request-page .form-section {
        padding: 14px !important;
        border-radius: 16px !important;
        margin-bottom: 14px !important;
    }

    .create-request-page .form-grid {
        display: block !important;
        grid-template-columns: minmax(0, 1fr) !important;
    }

    .create-request-page .form-grid > *,
    .create-request-page .form-group,
    .create-request-page [class*="field-"] {
        display: block !important;
        margin-bottom: 16px !important;
    }

    .create-request-page .form-grid > *:last-child,
    .create-request-page .form-section .form-group:last-child,
    .create-request-page .form-section [class*="field-"]:last-child {
        margin-bottom: 0 !important;
    }

    .create-request-page .control-label,
    .create-request-page label {
        font-size: 12px !important;
        line-height: 1.35 !important;
        white-space: normal !important;
    }

    .create-request-page .form-control,
    .create-request-page .select2-selection,
    .create-request-page .custom-file-display {
        min-height: 46px !important;
        font-size: 14px !important;
    }

    .create-request-page textarea.form-control {
        min-height: 160px !important;
    }

    .create-request-page .custom-file-display {
        flex-direction: column !important;
        align-items: stretch !important;
        min-height: auto !important;
    }

    .create-request-page .custom-file-button {
        width: 100% !important;
        border-right: 0 !important;
        border-bottom: 1px solid #CBD5E1 !important;
    }

    .create-request-page .custom-file-name {
        width: 100% !important;
        min-height: 44px !important;
        display: flex !important;
        align-items: center !important;
    }

    .create-request-page .form-actions {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 12px !important;
    }

    .create-request-page .btn-create-request-submit,
    .create-request-page .btn-back-requests {
        width: 100% !important;
        min-height: 48px !important;
    }
}

@media (max-width: 380px) {
    main.dash-content.create-request-page,
    .dash-content.create-request-page {
        padding: 8px !important;
    }

    .create-request-page .create-request-body {
        padding: 10px 8px 12px !important;
    }

    .create-request-page .form-section {
        padding: 12px !important;
    }
}

CSS);
?>

<!-- SHARED FORM SYSTEM: presentation only; request creation rules remain unchanged. -->
<main class="dash-content create-request-page can-form-page">
    <div class="container-fluid">
        <div class="create-request-card">

            <div class="create-request-header">
                <div class="create-request-header-top">
                    <div class="create-request-title-row">
                        <div class="create-request-icon">
                            <i class="bi bi-plus-square"></i>
                        </div>

                        <div>
                            <h1 class="create-request-title">
                                <?= Html::encode($this->title) ?>
                            </h1>
                            <div class="create-request-subtitle">
                                Create a new aircraft maintenance request with clear aircraft, location and schedule details.
                            </div>
                        </div>
                    </div>

                    <div class="create-request-header-badges">
                        <span class="create-request-badge">
                            <i class="bi bi-shield-check"></i>
                            AO Request
                        </span>
                        <span class="create-request-badge">
                            <i class="bi bi-asterisk"></i>
                            Required fields
                        </span>
                    </div>
                </div>
            </div>

            <div class="create-request-body">

                <?php if (Yii::$app->session->hasFlash('success')): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i>
                        <?= Yii::$app->session->getFlash('success') ?>
                    </div>
                <?php endif; ?>

                <?php if (Yii::$app->session->hasFlash('error')): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        <?= Yii::$app->session->getFlash('error') ?>
                    </div>
                <?php endif; ?>

                <div class="create-request-note">
                    <i class="bi bi-info-circle"></i>
                    <div>
                        Complete the required fields below. Validation messages appear after interaction, and the submit button activates when the form is ready.
                    </div>
                </div>

                <?php $form = ActiveForm::begin([
                    'id' => 'create-request-form',
                    'options' => [
                        'enctype' => 'multipart/form-data',
                    ],
                    'enableClientValidation' => true,
                    'enableAjaxValidation' => false,
                ]); ?>

                <div class="progress-bar-container">
                    <span class="progress-bar-step step-active">Create a Request</span>
                    <span class="progress-bar-step">MRO Quote</span>
                    <span class="progress-bar-step">PO Loaded</span>
                    <span class="progress-bar-step">PO Accepted By MRO</span>
                    <span class="progress-bar-step">Work Started</span>
                    <span class="progress-bar-step">MRO Report</span>
                    <span class="progress-bar-step">AO Feedback</span>
                    <span class="progress-bar-step">Request Closed</span>
                </div>

                <div id="loading-spinner">
                    <div class="spinner-box">
                        <img src="<?= Yii::getAlias('@web/img/spinner.gif') ?>" alt="Loading..." />
                        <span>Creating request...</span>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="bi bi-airplane-engines"></i>
                        Aircraft Information
                    </h2>
                    <p class="form-section-subtitle">
                        Select the aircraft. Registration, serial number and authority will be completed automatically.
                    </p>

                    <div class="form-grid">
                        <div>
                            <?= $form->field($request, 'aircraft_id')->dropDownList(
                                ArrayHelper::map(
                                    Aircrafts::find()
                                        ->where(['ao_id' => Yii::$app->session->get('ao_id')])
                                        ->all(),
                                    'aircraft_id',
                                    function ($model) {
                                        return $model->manufacturer . ' - ' .
                                            $model->model . ' - ' .
                                            $model->serial_number . ' - ' .
                                            $model->registration_number . ' - ' .
                                            ($model->certificateType ? $model->certificateType->type : '');
                                    }
                                ),
                                [
                                    'prompt' => 'Select Aircraft',
                                    'id' => 'aircraft-dropdown',
                                    'class' => 'form-control chevron-select',
                                ]
                            )->label("Aircraft <span class='text-danger'>*</span>") ?>
                        </div>

                        <div>
                            <?= $form->field($request, 'required_certificates')->textInput([
                                'maxlength' => true,
                                'disabled' => true,
                                'id' => 'required-certificates',
                                'class' => 'form-control',
                            ])->label('National Aviation Authority') ?>
                        </div>

                        <div>
                            <?= $form->field($request, 'aircraft_registration')->textInput([
                                'maxlength' => true,
                                'disabled' => true,
                                'id' => 'aircraft-registration',
                                'class' => 'form-control',
                            ])->label('Aircraft Registration') ?>
                        </div>

                        <div>
                            <?= $form->field($request, 'serial_number')->textInput([
                                'maxlength' => true,
                                'disabled' => true,
                                'id' => 'serial-number',
                                'class' => 'form-control',
                            ])->label('Serial Number') ?>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="bi bi-geo-alt"></i>
                        Maintenance Location
                    </h2>
                    <p class="form-section-subtitle">
                        Search by ICAO, airport name or city. Type at least three characters to start searching.
                    </p>

                    <div class="form-grid">
                        <div class="full-width" id="destination-row">
                            <?= $form->field($request, 'destination')->widget(Select2::class, [
                                'data' => [],
                                'options' => [
                                    'placeholder' => 'Type to search for an airport',
                                    'id' => 'destination-dropdown',
                                ],
                                'pluginOptions' => [
                                    'allowClear' => false,
                                    'minimumInputLength' => 3,
                                    'ajax' => [
                                        'url' => Url::to(['requests/search-airports']),
                                        'dataType' => 'json',
                                        'delay' => 250,
                                        'data' => new JsExpression('function(params) {
                                            return {
                                                term: params.term
                                            };
                                        }'),
                                        'processResults' => new JsExpression('function(data) {
                                            return {
                                                results: data
                                            };
                                        }'),
                                    ],
                                    'escapeMarkup' => new JsExpression('function(markup) {
                                        return markup;
                                    }'),
                                    'templateResult' => new JsExpression('function(data) {
                                        return data.text;
                                    }'),
                                    'templateSelection' => new JsExpression('function(data) {
                                        return data.text;
                                    }'),
                                ],
                            ])->label("ICAO (Airport) <span class='text-danger'>*</span>") ?>
                        </div>
                    </div>
                </div>

                <?= $form->field($request, 'aircraft_registration')->hiddenInput([
                    'id' => 'aircraftregistration',
                ])->label(false) ?>

                <?= $form->field($request, 'serial_number')->hiddenInput([
                    'id' => 'serialnumber',
                ])->label(false) ?>

                <?= $form->field($request, 'required_certificates')->hiddenInput([
                    'id' => 'requiredcertificates',
                ])->label(false) ?>

                <!--
                    PRIORITÉ OPÉRATIONNELLE : ces trois choix renseignent uniquement
                    l'urgence attendue par l'opérateur. Ils ne modifient aucun statut
                    métier et n'exécutent aucune transition automatique de la demande.
                -->
                <div class="form-section operational-priority-section" data-operational-priority>
                    <h2 class="form-section-title">
                        <i class="bi bi-broadcast-pin" aria-hidden="true"></i>
                        Operational Priority
                    </h2>
                    <p class="form-section-subtitle">
                        Indicate how quickly MRO partners should review this request.
                    </p>

                    <?= $form->field($request, 'operational_priority', [
                        'template' => "{input}\n{error}",
                        'options' => ['class' => 'operational-priority-field'],
                    ])->radioList(Requests::getOperationalPriorityOptions(), [
                        'class' => 'operational-priority-list',
                        'item' => static function ($index, $label, $name, $checked, $value) {
                            $icons = [
                                Requests::PRIORITY_AOG => 'bi-exclamation-octagon',
                                Requests::PRIORITY_URGENT => 'bi-lightning-charge',
                                Requests::PRIORITY_ROUTINE => 'bi-calendar-check',
                            ];
                            $descriptions = [
                                Requests::PRIORITY_AOG => 'Aircraft grounded · response time required',
                                Requests::PRIORITY_URGENT => 'Fast review · response time optional',
                                Requests::PRIORITY_ROUTINE => 'Standard planning · no response deadline',
                            ];

                            return Html::tag('label',
                                Html::radio($name, $checked, [
                                    'value' => $value,
                                    'class' => 'operational-priority-input',
                                ])
                                . Html::tag('span', Html::tag('i', '', [
                                    'class' => 'bi ' . $icons[$value],
                                    'aria-hidden' => 'true',
                                ]), ['class' => 'operational-priority-icon'])
                                . Html::tag('span',
                                    Html::tag('span', Html::encode($label), ['class' => 'operational-priority-name'])
                                    . Html::tag('span', Html::encode($descriptions[$value]), ['class' => 'operational-priority-description']),
                                    ['class' => 'operational-priority-copy']
                                ),
                                ['class' => 'operational-priority-card priority-' . $value]
                            );
                        },
                    ])->label(false) ?>

                    <!--
                        DÉLA DE RÉPONSE : visible pour AOG et Urgent, obligatoire
                        seulement pour AOG. Les raccourcis remplissent le même champ
                        numérique et n'ajoutent donc aucune donnée métier parallèle.
                    -->
                    <div class="operational-response-panel" data-response-panel hidden>
                        <div class="operational-response-heading">
                            <i class="bi bi-stopwatch" aria-hidden="true"></i>
                            <div>
                                <span class="operational-response-title">Expected response time</span>
                                <span class="operational-response-rule" data-response-rule></span>
                            </div>
                        </div>
                        <div class="operational-response-controls">
                            <?= $form->field($request, 'response_required_minutes')->input('number', [
                                'id' => 'response-required-minutes',
                                'min' => 15,
                                'max' => 1440,
                                'step' => 15,
                                'placeholder' => 'Custom minutes',
                            ])->label('Response time (minutes)', ['data-response-label' => true]) ?>
                            <div class="operational-response-presets" aria-label="Quick response times">
                                <button type="button" data-response-minutes="30">30 min</button>
                                <button type="button" data-response-minutes="60">1 h</button>
                                <button type="button" data-response-minutes="120">2 h</button>
                                <button type="button" data-response-minutes="240">4 h</button>
                            </div>
                        </div>
                        <div class="operational-deadline-preview" data-deadline-preview></div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="bi bi-calendar-event"></i>
                        Schedule
                    </h2>
                    <p class="form-section-subtitle">
                        Define the planned arrival and departure window. ETD must be after ETA.
                    </p>

                    <div class="form-grid">
                        <div>
                            <?= $form->field($request, 'eta')->widget(DateTimePicker::class, [
                                'options' => [
                                    'placeholder' => 'Select date and time...',
                                    'id' => 'eta',
                                    'class' => 'form-control',
                                ],
                                'pluginOptions' => [
                                    'format' => 'yyyy-mm-dd hh:ii:ss',
                                    'autoclose' => true,
                                    'todayHighlight' => true,
                                    'showRemove' => false,
                                ],
                            ])->label('ETA<span class="text-danger">*</span>') ?>
                        </div>

                        <div>
                            <?= $form->field($request, 'etd')->widget(DateTimePicker::class, [
                                'options' => [
                                    'placeholder' => 'Select date and time...',
                                    'id' => 'etd',
                                    'class' => 'form-control',
                                ],
                                'pluginOptions' => [
                                    'format' => 'yyyy-mm-dd hh:ii:ss',
                                    'autoclose' => true,
                                    'todayHighlight' => true,
                                    'showRemove' => false,
                                ],
                            ])->label('ETD<span class="text-danger">*</span>') ?>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="bi bi-file-earmark-text"></i>
                        Request Details
                    </h2>
                    <p class="form-section-subtitle">
                        Add supporting documents if needed and describe the maintenance work clearly.
                    </p>

                    <div class="form-grid">
                        <div class="full-width">
                            <label class="control-label">Attachment</label>

                            <div class="custom-file-wrapper">
                                <?= $form->field($request, 'attachment', [
                                    'template' => "{input}\n{error}",
                                    'options' => ['class' => 'custom-file-field'],
                                ])->fileInput([
                                    'accept' => 'image/*,.pdf,.doc,.docx',
                                    'id' => 'attachment-input',
                                    'class' => 'custom-file-native',
                                ]) ?>

                                <div class="custom-file-display">
                                    <label for="attachment-input" class="custom-file-button">
                                        <!-- FILE ACTION ICON: presentation only; native input remains unchanged. -->
                                        <i class="bi bi-upload" aria-hidden="true"></i>
                                        Choose file
                                    </label>

                                    <span id="attachment-file-name" class="custom-file-name">
                                        No file chosen
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="full-width">
                            <?= $form->field($request, 'request_details')->textarea([
                                'rows' => 6,
                                'class' => 'form-control',
                                'id' => 'request-details',
                                'placeholder' => 'Describe the requested maintenance work...',
                            ])->label('Request Details<span class="text-danger">*</span>') ?>
                        </div>
                    </div>
                </div>

                <?= $form->field($request, 'status')->hiddenInput([
                    'value' => 'created',
                ])->label(false) ?>

                <?= $form->field($request, 'ao_id')->hiddenInput([
                    'value' => Yii::$app->session->get('ao_id'),
                ])->label(false) ?>

                <div class="form-actions">
                    <?= Html::submitButton(
                        '<i class="bi bi-check2-circle"></i> Create Request',
                        [
                            'class' => 'btn-create-request-submit',
                            'id' => 'submit-button',
                            'disabled' => true,
                        ]
                    ) ?>

                    <?= Html::a(
                        '<i class="bi bi-arrow-left"></i> Back to Requests',
                        ['index'],
                        [
                            'class' => 'btn-back-requests',
                        ]
                    ) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</main>

<?php
$this->registerJs(<<<JS
// Track fields that the user has interacted with.
// This keeps the first screen clean and avoids showing red validation messages before the user starts typing.
var touchedFields = {};
var requiredFieldIds = ["aircraft-dropdown", "destination-dropdown", "eta", "etd", "request-details"];

// Synchronize aircraft metadata fields when the AO selects an aircraft.
var aircraftDropdown = document.getElementById("aircraft-dropdown");

if (aircraftDropdown) {
    aircraftDropdown.addEventListener("change", function () {
        var selectedAircraft = this.options[this.selectedIndex];

        touchedFields["aircraft-dropdown"] = true;

        if (!selectedAircraft) {
            validateCreateForm(false);
            return;
        }

        var aircraftText = selectedAircraft.textContent || "";
        var parts = aircraftText.split(" - ");

        var aircraftSerialNumber = parts[2] || "";
        var aircraftRegistration = parts[3] || "";
        var certificateType = parts[4] || "";

        var aircraftRegistrationInput = document.getElementById("aircraft-registration");
        var serialNumberInput = document.getElementById("serial-number");
        var requiredCertificatesInput = document.getElementById("required-certificates");

        var aircraftRegistrationHidden = document.getElementById("aircraftregistration");
        var serialNumberHidden = document.getElementById("serialnumber");
        var requiredCertificatesHidden = document.getElementById("requiredcertificates");

        if (aircraftRegistrationInput) {
            aircraftRegistrationInput.value = aircraftRegistration;
        }

        if (serialNumberInput) {
            serialNumberInput.value = aircraftSerialNumber;
        }

        if (requiredCertificatesInput) {
            requiredCertificatesInput.value = certificateType;
        }

        if (aircraftRegistrationHidden) {
            aircraftRegistrationHidden.value = aircraftRegistration;
        }

        if (serialNumberHidden) {
            serialNumberHidden.value = aircraftSerialNumber;
        }

        if (requiredCertificatesHidden) {
            requiredCertificatesHidden.value = certificateType;
        }

        validateCreateForm(false);
    });
}

var form = document.getElementById("create-request-form");
var submitButton = document.getElementById("submit-button");
var loadingIndicator = document.getElementById("loading-spinner");
var attachmentInput = document.getElementById("attachment-input");
var attachmentFileName = document.getElementById("attachment-file-name");

if (attachmentInput && attachmentFileName) {
    attachmentInput.addEventListener("change", function () {
        attachmentFileName.textContent = this.files && this.files.length > 0
            ? this.files[0].name
            : "No file chosen";
    });
}

// Convert the DateTimePicker value to a JavaScript date safely.
function parseDateValue(value) {
    if (!value) {
        return null;
    }

    var parsedDate = new Date(value.replace(" ", "T"));

    if (isNaN(parsedDate.getTime())) {
        return null;
    }

    return parsedDate;
}

// Find the Yii field wrapper for native inputs and Kartik widgets.
function getFieldContainer(input) {
    if (!input) {
        return null;
    }

    var fieldContainer = input.closest(".form-group") || input.closest("[class*='field-']");

    if (!fieldContainer) {
        fieldContainer = input.parentElement;
    }

    return fieldContainer;
}

// Display a custom validation error only when the field was touched or when submitting.
function setFieldError(input, message) {
    var fieldContainer = getFieldContainer(input);

    if (!fieldContainer) {
        return;
    }

    fieldContainer.classList.add("has-error");
    fieldContainer.classList.add("is-touched");
    fieldContainer.classList.remove("has-success");

    var helpBlock = fieldContainer.querySelector(".help-block");

    if (helpBlock) {
        helpBlock.innerHTML = message;
        helpBlock.style.display = "block";
    }
}

// Clear the validation state when the field becomes valid.
function clearFieldError(input) {
    var fieldContainer = getFieldContainer(input);

    if (!fieldContainer) {
        return;
    }

    fieldContainer.classList.remove("has-error");
    fieldContainer.classList.add("has-success");

    var helpBlock = fieldContainer.querySelector(".help-block");

    if (helpBlock) {
        helpBlock.innerHTML = "";
        helpBlock.style.display = "none";
    }
}

// Remove visual validation when the page first loads and the field has not been touched yet.
function resetFieldVisualState(input) {
    var fieldContainer = getFieldContainer(input);

    if (!fieldContainer) {
        return;
    }

    fieldContainer.classList.remove("has-error");
    fieldContainer.classList.remove("has-success");

    var helpBlock = fieldContainer.querySelector(".help-block");

    if (helpBlock) {
        helpBlock.innerHTML = "";
        helpBlock.style.display = "none";
    }
}

// Read Select2 values safely, even when the widget is initialized by Kartik.
function getSelect2Value(id) {
    var element = document.getElementById(id);

    if (!element) {
        return "";
    }

    if (typeof jQuery !== "undefined" && jQuery(element).data("select2")) {
        return jQuery(element).val();
    }

    return element.value;
}

// Apply a field state while keeping first-load validation visually quiet.
function applyFieldState(id, input, isFieldValid, errorMessage, forceShowErrors) {
    if (isFieldValid) {
        clearFieldError(input);
        return true;
    }

    if (forceShowErrors || touchedFields[id]) {
        setFieldError(input, errorMessage);
    } else {
        resetFieldVisualState(input);
    }

    return false;
}

// Validate required fields and enable the submit button only when the form is ready.
function validateCreateForm(forceShowErrors) {
    forceShowErrors = forceShowErrors === true;

    var isValid = true;

    var aircraftInput = document.getElementById("aircraft-dropdown");
    var destinationInput = document.getElementById("destination-dropdown");
    var etaInput = document.getElementById("eta");
    var etdInput = document.getElementById("etd");
    var detailsInput = document.getElementById("request-details");

    var aircraftValue = aircraftInput ? aircraftInput.value : "";
    var destinationValue = getSelect2Value("destination-dropdown");
    var etaValue = etaInput ? etaInput.value.trim() : "";
    var etdValue = etdInput ? etdInput.value.trim() : "";
    var detailsValue = detailsInput ? detailsInput.value.trim() : "";

    if (!applyFieldState("aircraft-dropdown", aircraftInput, !!aircraftValue, "Aircraft is required.", forceShowErrors)) {
        isValid = false;
    }

    if (!applyFieldState("destination-dropdown", destinationInput, !!destinationValue, "Maintenance location is required.", forceShowErrors)) {
        isValid = false;
    }

    if (!applyFieldState("eta", etaInput, !!etaValue, "ETA cannot be blank.", forceShowErrors)) {
        isValid = false;
    }

    var etdIsValid = !!etdValue;
    var etdErrorMessage = "ETD cannot be blank.";

    if (etaValue && etdValue) {
        var etaDate = parseDateValue(etaValue);
        var etdDate = parseDateValue(etdValue);

        if (etaDate && etdDate && etdDate <= etaDate) {
            etdIsValid = false;
            etdErrorMessage = "ETD must be after ETA.";
        }
    }

    if (!applyFieldState("etd", etdInput, etdIsValid, etdErrorMessage, forceShowErrors)) {
        isValid = false;
    }

    if (!applyFieldState("request-details", detailsInput, !!detailsValue, "Request details cannot be blank.", forceShowErrors)) {
        isValid = false;
    }

    /*
     * VALIDATION AOG CÔTÉ NAVIGATEUR : ce contrôle améliore le retour immédiat
     * et maintient le bouton désactivé. La même règle reste obligatoirement
     * contrôlée par Yii côté serveur afin qu'elle ne puisse pas être contournée.
     */
    var selectedPriority = document.querySelector('[name="Requests[operational_priority]"]:checked');
    var priorityValue = selectedPriority ? selectedPriority.value : "routine";
    var responseInput = document.getElementById("response-required-minutes");
    var responseValue = responseInput ? responseInput.value.trim() : "";
    var responseMinutes = Number(responseValue);
    var responseHasValue = responseValue !== "";
    var responseIsInRange = responseHasValue
        && Number.isFinite(responseMinutes)
        && responseMinutes >= 15
        && responseMinutes <= 1440;
    var responseIsValid = (priorityValue !== "aog" || responseHasValue)
        && (!responseHasValue || responseIsInRange);

    if (!responseIsValid) {
        applyFieldState(
            "response-required-minutes",
            responseInput,
            false,
            priorityValue === "aog"
                ? "AOG response time must be between 15 minutes and 24 hours."
                : "Response time must be between 15 minutes and 24 hours.",
            forceShowErrors
        );
        isValid = false;
    } else if (responseInput) {
        clearFieldError(responseInput);
    }

    if (submitButton) {
        submitButton.disabled = !isValid;
    }

    return isValid;
}

requiredFieldIds.forEach(function (id) {
    var element = document.getElementById(id);

    if (element) {
        ["change", "keyup", "blur", "input"].forEach(function (eventName) {
            element.addEventListener(eventName, function () {
                touchedFields[id] = true;
                validateCreateForm(false);
            });
        });
    }
});

/*
 * SYNCHRONISATION DE LA PRIORITÉ : le module visuel émet cet événement après
 * chaque choix ou changement de délai ; la validation générale est alors rejouée
 * sans attendre une autre action de l'utilisateur.
 */
var operationalPrioritySection = document.querySelector("[data-operational-priority]");
if (operationalPrioritySection) {
    operationalPrioritySection.addEventListener("can:priority-validity-change", function () {
        validateCreateForm(false);
    });
}

if (typeof jQuery !== "undefined") {
    jQuery("#destination-dropdown").on("change select2:select select2:clear", function () {
        touchedFields["destination-dropdown"] = true;
        validateCreateForm(false);
    });

    jQuery("#eta").on("changeDate change clearDate", function () {
        touchedFields["eta"] = true;
        validateCreateForm(false);
    });

    jQuery("#etd").on("changeDate change clearDate", function () {
        touchedFields["etd"] = true;
        validateCreateForm(false);
    });

    jQuery("#create-request-form").on("afterValidate", function () {
        validateCreateForm(true);
    });
}

if (form) {
    // Prevent invalid submissions and show the loading overlay only for valid data.
    form.addEventListener("submit", function (event) {
        if (!validateCreateForm(true)) {
            event.preventDefault();

            if (submitButton) {
                submitButton.disabled = true;
            }

            if (loadingIndicator) {
                loadingIndicator.style.display = "none";
            }

            return false;
        }

        if (submitButton) {
            submitButton.disabled = true;
        }

        if (loadingIndicator) {
            loadingIndicator.style.display = "flex";
        }

        return true;
    });
}

// Keep the submit button disabled on first load without showing red errors immediately.
var hasServerSideErrors = form && form.querySelector(".has-error") !== null;
validateCreateForm(hasServerSideErrors);
JS);

?>
