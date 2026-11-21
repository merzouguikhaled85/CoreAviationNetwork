<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use app\components\UrlIdHelper;
use app\models\AoProfile;
use app\models\Chat;
use app\models\MroProfile;
use app\models\Requests;
use app\models\Conversations;

$this->title = 'Conversation Request : ' . ($conversation->request_id ?? '');

$this->registerCssFile('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');

/* SECURITY: every browser-facing chat action receives the signed chat token. */
$conversationId = UrlIdHelper::encode($conversation->chat_id);
$fetchUrl = Url::to(['conversations/fetch-messages', 'conversationId' => $conversationId]);
$messageCount = is_countable($messages) ? count($messages) : 0;

/*
 * PRESENTATION CONTEXT:
 * Resolve the other participant once so the chat reads like an operational
 * AO/MRO workspace. This block is read-only and does not change the chat flow.
 */
$chatRecord = Chat::findOne($conversation->chat_id);
$currentUserIsMro = (bool) Yii::$app->session->get('mro_id');
$otherParticipant = $chatRecord
    ? ($currentUserIsMro
        ? AoProfile::findOne($chatRecord->ao_id)
        : MroProfile::findOne($chatRecord->mro_id))
    : null;
$otherParticipantRole = $currentUserIsMro ? 'Aircraft Operator / CAMO' : 'Maintenance Organization';
$otherParticipantName = $otherParticipant
    ? ($otherParticipant->company_name ?: $otherParticipant->username ?: $otherParticipantRole)
    : $otherParticipantRole;
$participantInitial = strtoupper(substr(trim((string) $otherParticipantName), 0, 1)) ?: 'A';

/*
 * CONTEXTE DE LA DEMANDE (2026-11) : la barre sous l'en-tête affiche l'avion,
 * la priorité opérationnelle et le statut, avec un lien vers la demande.
 * Lecture seule ; l'accès à la conversation est déjà contrôlé par le contrôleur.
 */
$contextRequest = Requests::find()
    ->where(['request_id' => $conversation->request_id])
    ->with('aircraft')
    ->one();
$contextAircraft = $contextRequest ? $contextRequest->aircraft : null;
$contextRegistration = $contextRequest
    ? trim((string) ($contextRequest->aircraft_registration ?: ($contextAircraft->registration_number ?? '')))
    : '';
$contextModel = '';
if ($contextAircraft) {
    $contextManufacturer = trim((string) ($contextAircraft->manufacturer ?? ''));
    $contextModelName = trim((string) ($contextAircraft->model ?? ''));
    /*
     * Le champ « model » contient parfois déjà le constructeur (« Dassault Falcon 7X ») :
     * on n'ajoute le constructeur que s'il n'y figure pas, pour éviter « Dassault Dassault ».
     */
    $contextModel = ($contextManufacturer !== '' && stripos($contextModelName, $contextManufacturer) !== 0)
        ? trim($contextManufacturer . ' ' . $contextModelName)
        : $contextModelName;
}
$contextPriority = $contextRequest ? strtolower((string) ($contextRequest->operational_priority ?: 'routine')) : '';
$contextPriorityLabel = $contextRequest ? $contextRequest->getOperationalPriorityLabel() : '';
$contextPriorityIcons = [
    'aog' => 'bi-exclamation-octagon',
    'urgent' => 'bi-lightning-charge',
    'routine' => 'bi-calendar-check',
];
$contextStatus = $contextRequest ? (string) $contextRequest->status : '';
/*
 * Statut vu par le MRO : « answered » est global (un concurrent a pu répondre),
 * on affiche donc « Open for quotes » (même principe que la liste MRO, D-013).
 */
$contextStatusLabel = ($currentUserIsMro && in_array($contextStatus, ['created', 'answered'], true))
    ? 'Open for quotes'
    : ($contextStatus !== '' ? ucwords(str_replace('_', ' ', $contextStatus)) : '');
$contextRequestUrl = $contextRequest
    ? Url::to(['requests/view', 'id' => UrlIdHelper::encode((int) $contextRequest->request_id)])
    : null;

/* PIÈCES JOINTES : limites partagées avec le modèle (validation serveur identique). */
$attachmentMaxBytes = Conversations::ATTACHMENT_MAX_BYTES;
$attachmentExtensionsJs = json_encode(Conversations::ATTACHMENT_EXTENSIONS);

/**
 * Remove success flashes on chat pages.
 * The sent message is already visible in the conversation, so no success toaster is needed.
 */
Yii::$app->session->removeFlash('message');
Yii::$app->session->removeFlash('success');

/**
 * Hide global spinner on conversation page.
 * Chat uses automatic AJAX polling, so global loader must stay hidden.
 */
$this->registerCss(<<<CSS
#av-global-loader {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
}
CSS);

$this->registerJs(<<<JS
window.AV_DISABLE_GLOBAL_LOADER = true;

window.hideGlobalLoaderOnChatPage = function () {
    var loader = document.getElementById('av-global-loader');

    if (loader) {
        loader.classList.add('hidden');
        loader.style.display = 'none';
        loader.style.opacity = '0';
        loader.style.visibility = 'hidden';
        loader.style.pointerEvents = 'none';
    }
};

window.hideGlobalLoaderOnChatPage();
document.addEventListener('DOMContentLoaded', window.hideGlobalLoaderOnChatPage);
window.addEventListener('load', window.hideGlobalLoaderOnChatPage);
setTimeout(window.hideGlobalLoaderOnChatPage, 300);
setTimeout(window.hideGlobalLoaderOnChatPage, 1000);
JS);

/**
 * Professional WhatsApp-like chat design
 */
$this->registerCss(<<<CSS
:root {
    /* Core Aviation Network palette replaces the WhatsApp-like green theme. */
    --chat-green: #0D3261;
    --chat-green-dark: #08264A;
    --chat-green-soft: #DDEEFF;
    --chat-green-light: #EFF7FF;
    --chat-bg: #EDF3FA;
    --chat-panel: #f0f2f5;
    --chat-white: #ffffff;
    --chat-border: #d1d7db;
    --chat-text: #111b21;
    --chat-muted: #667781;
    --chat-shadow: rgba(17, 27, 33, .14);
    --chat-sky: #00B2FF;
    --chat-gold: #EED811;
}

/* Main page */
.chat-shell {
    min-height: calc(100vh - 90px);
    padding: 22px;
    background:
        radial-gradient(circle at 18% 12%, rgba(0, 178, 255, .14), transparent 30%),
        radial-gradient(circle at 88% 20%, rgba(13, 50, 97, .11), transparent 32%),
        linear-gradient(135deg, #E9F4FF 0%, #F6F9FD 48%, #FFFFFF 100%);
    position: relative;
}

/* Subtle aviation-grid texture keeps the page branded without visual noise. */
.chat-shell::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    opacity: .22;
    background-image:
        linear-gradient(rgba(13, 50, 97, .08) 1px, transparent 1px),
        linear-gradient(90deg, rgba(13, 50, 97, .08) 1px, transparent 1px);
    background-size: 44px 44px;
}

/* Main chat card */
.chat-card {
    width: 100%;
    max-width: 1240px;
    height: min(780px, calc(100vh - 118px));
    /* Keep the composer visible on shorter desktop screens. */
    min-height: 520px;
    margin: 0 auto;
    background: var(--chat-white);
    border: 1px solid rgba(209, 215, 219, .9);
    border-radius: 22px;
    box-shadow: 0 24px 64px var(--chat-shadow);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    position: relative;
    z-index: 1;
}

/* Header */
.chat-header {
    min-height: 74px;
    padding: 13px 18px;
    background:
        radial-gradient(circle at 88% 15%, rgba(0, 178, 255, .28), transparent 30%),
        linear-gradient(135deg, var(--chat-green), var(--chat-green-dark));
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
    flex-shrink: 0;
    position: relative;
}

/* CAN cyan/gold accent identifies the aviation workspace. */
.chat-header::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--chat-sky), #38BDF8 70%, var(--chat-gold));
}

.chat-title-block {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.chat-title-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: rgba(255, 255, 255, .96);
    color: var(--chat-green);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 8px 20px rgba(0, 0, 0, .18);
    font-size: 18px;
    font-weight: 900;
    position: relative;
}

.chat-title-icon::after {
    content: "";
    position: absolute;
    right: -2px;
    bottom: -2px;
    width: 13px;
    height: 13px;
    border: 3px solid var(--chat-green);
    border-radius: 50%;
    background: #22C55E;
}

.chat-title-text {
    min-width: 0;
}

.chat-title {
    margin: 0;
    color: #ffffff;
    font-size: 18px;
    font-weight: 800;
    letter-spacing: -.01em;
    line-height: 1.2;
}

.chat-title span {
    color: var(--chat-green-soft);
}

.chat-subtitle {
    margin-top: 4px;
    color: rgba(255, 255, 255, .80);
    font-size: 12px;
    font-weight: 600;
}

.chat-header-actions {
    display: flex;
    align-items: center;
    gap: 9px;
    flex-wrap: wrap;
}

.chat-status-pill,
.chat-sync-pill,
.chat-back-btn {
    height: 36px;
    border-radius: 999px;
    padding: 0 13px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none;
    white-space: nowrap;
}

.chat-status-pill,
.chat-sync-pill {
    background: rgba(255, 255, 255, .15);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, .22);
}

.chat-sync-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #25d366;
    box-shadow: 0 0 0 4px rgba(37, 211, 102, .16);
}

.chat-back-btn {
    background: rgba(255, 255, 255, .96);
    color: var(--chat-green-dark) !important;
    border: 1px solid rgba(255, 255, 255, .45);
    transition: .2s ease;
}

.chat-back-btn:hover {
    background: #ffffff;
    color: var(--chat-green-dark) !important;
    transform: translateY(-1px);
}

/* Compact information bar */
.chat-info-bar {
    min-height: 42px;
    padding: 9px 18px;
    background: var(--chat-panel);
    border-bottom: 1px solid var(--chat-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    color: var(--chat-muted);
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}

.chat-info-item {
    display: inline-flex;
    align-items: center;
    gap: 7px;
}

.chat-info-item i {
    color: var(--chat-green);
}

/* Alert area */
.chat-alert-wrapper {
    padding: 12px 18px 0;
    background: var(--chat-panel);
    flex-shrink: 0;
}

.alert {
    margin: 0;
    border: none;
    border-radius: 12px;
    padding: 12px 14px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
}

/* Chat body */
.chat-body {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 24px 30px;
    background-color: var(--chat-bg);
    background-image:
        radial-gradient(circle at 15% 20%, rgba(255,255,255,.36) 0 2px, transparent 3px),
        radial-gradient(circle at 80% 30%, rgba(255,255,255,.28) 0 2px, transparent 3px),
        radial-gradient(circle at 40% 70%, rgba(255,255,255,.22) 0 2px, transparent 3px);
    background-size: 120px 120px, 160px 160px, 180px 180px;
    position: relative;
    scroll-behavior: smooth;
}

.chat-body::-webkit-scrollbar {
    width: 8px;
}

.chat-body::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, .05);
}

.chat-body::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, .22);
    border-radius: 999px;
}

/* Message list */
.message-list {
    width: 100%;
}

.message-ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.msg {
    display: flex;
    width: 100%;
    margin-bottom: 8px;
}

.msg.me {
    justify-content: flex-end;
}

.msg:not(.me) {
    justify-content: flex-start;
}

.msg.grouped {
    margin-top: -4px;
}

/* Date divider */
.day-divider {
    display: flex;
    justify-content: center;
    margin: 12px 0 18px;
}

.day-divider span {
    background: rgba(255, 255, 255, .78);
    color: #54656f;
    border-radius: 999px;
    padding: 6px 12px;
    font-size: 11px;
    font-weight: 800;
    box-shadow: 0 1px 2px rgba(17, 27, 33, .10);
}

/* Message bubble */
.bubble {
    position: relative;
    width: fit-content;
    min-width: 105px;
    max-width: 66%;
    padding: 7px 9px 6px;
    border-radius: 8px;
    background: #ffffff;
    color: var(--chat-text);
    box-shadow: 0 1px 2px rgba(17, 27, 33, .16);
}

.msg.me .bubble {
    background: var(--chat-green-soft);
    color: var(--chat-text);
    border-top-right-radius: 0;
}

.msg:not(.me) .bubble {
    border-top-left-radius: 0;
}

/* Bubble tails */
.msg.me:not(.grouped) .bubble::after {
    content: "";
    position: absolute;
    top: 0;
    right: -8px;
    width: 0;
    height: 0;
    border-top: 8px solid var(--chat-green-soft);
    border-right: 8px solid transparent;
}

.msg:not(.me):not(.grouped) .bubble::before {
    content: "";
    position: absolute;
    top: 0;
    left: -8px;
    width: 0;
    height: 0;
    border-top: 8px solid #ffffff;
    border-left: 8px solid transparent;
}

.msg.grouped .bubble {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}

/* Sender */
.sender-name {
    display: block;
    margin-bottom: 3px;
    color: var(--chat-green);
    font-size: 12px;
    font-weight: 800;
}

/* Text */
.msg-text {
    display: block;
    margin: 0;
    padding-right: 50px;
    color: var(--chat-text);
    font-size: 14px;
    font-weight: 500;
    line-height: 1.45;
    word-break: break-word;
    white-space: pre-wrap;
}

/* Time and status */
.meta {
    margin-top: 2px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 4px;
    color: var(--chat-muted);
    font-size: 10.5px;
    font-weight: 600;
    white-space: nowrap;
}

.meta i {
    font-size: 12px;
}

.msg.me .meta i {
    color: #53bdeb;
}

/* Empty state */
.empty-chat {
    height: 100%;
    min-height: 390px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.empty-chat-box {
    max-width: 370px;
    padding: 30px 34px;
    border-radius: 20px;
    background: rgba(255, 255, 255, .82);
    border: 1px solid rgba(255, 255, 255, .75);
    box-shadow: 0 16px 34px rgba(17, 27, 33, .10);
}

.empty-chat-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 14px;
    border-radius: 50%;
    background: var(--chat-green-soft);
    color: var(--chat-green-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
}

.empty-chat-title {
    color: var(--chat-text);
    font-size: 17px;
    font-weight: 800;
    margin-bottom: 5px;
}

.empty-chat-text {
    color: var(--chat-muted);
    font-size: 13px;
    font-weight: 600;
}

/* Scroll bottom button */
.scroll-bottom-btn {
    position: absolute;
    right: 22px;
    bottom: 18px;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    border: none;
    background: #ffffff;
    color: var(--chat-green);
    box-shadow: 0 10px 24px rgba(17, 27, 33, .18);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 5;
    transition: .2s ease;
}

.scroll-bottom-btn:hover {
    transform: translateY(-2px);
}

/* Footer */
.chat-footer {
    padding: 12px 14px;
    background: var(--chat-panel);
    border-top: 1px solid var(--chat-border);
    flex-shrink: 0;
}

.chat-actions {
    display: flex;
    align-items: flex-end;
    gap: 10px;
}

.chat-input-wrap {
    flex: 1;
}

.chat-input-wrap .form-group {
    margin-bottom: 0;
}

.chat-input-wrap .control-label {
    display: none;
}

.chat-input {
    min-height: 48px;
    max-height: 140px;
    border-radius: 24px;
    border: 1px solid transparent;
    background: #ffffff;
    color: var(--chat-text);
    font-size: 14px;
    font-weight: 500;
    padding: 13px 17px;
    resize: none;
    box-shadow: none;
    transition: .2s ease;
}

.chat-input:hover,
.chat-input:focus {
    background: #ffffff;
    border-color: rgba(13, 50, 97, .25);
    box-shadow: none;
}

.chat-input::placeholder {
    color: #8696a0;
    font-weight: 500;
}

.chat-input-wrap .help-block {
    color: #dc2626;
    font-size: 12px;
    font-weight: 700;
    margin-top: 6px;
}

/* Send button */
.btn-send-chat {
    width: 48px;
    height: 48px;
    min-width: 48px;
    border: none;
    border-radius: 50%;
    background: var(--chat-green);
    color: #ffffff !important;
    font-size: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 18px rgba(0, 128, 105, .24);
    transition: .2s ease;
}

.btn-send-chat i {
    font-size: 19px;
}

.btn-send-chat:hover:not(:disabled) {
    background: var(--chat-green-dark);
    transform: translateY(-1px);
}

.btn-send-chat:disabled {
    opacity: .46;
    cursor: not-allowed;
    box-shadow: none;
}

/* Help text */
.chat-help-text {
    margin-top: 8px;
    color: var(--chat-muted);
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.chat-help-text i {
    color: var(--chat-green);
}

/* ==========================================================
   VISUAL REFINEMENT OVERRIDES
   These rules improve hierarchy only; messaging behavior is unchanged.
   ========================================================== */
.chat-info-bar {
    background: linear-gradient(90deg, #F8FAFC, #EEF6FF);
}

.chat-info-primary {
    color: #0F172A;
    font-weight: 800;
}

.chat-body {
    padding: 26px 34px;
    background-image:
        linear-gradient(rgba(13, 50, 97, .025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(13, 50, 97, .025) 1px, transparent 1px),
        radial-gradient(circle at 15% 20%, rgba(255,255,255,.42) 0 2px, transparent 3px);
    background-size: 36px 36px, 36px 36px, 140px 140px;
}

.msg {
    margin-bottom: 10px;
}

.day-divider span {
    border: 1px solid rgba(203, 213, 225, .75);
    box-shadow: 0 4px 12px rgba(17, 27, 33, .08);
}

.bubble {
    max-width: 70%;
    padding: 9px 11px 7px;
    border: 1px solid rgba(203, 213, 225, .72);
    border-radius: 14px;
}

.msg.me .bubble {
    border-color: #BFDBFE;
}

.chat-footer {
    padding: 13px 15px 11px;
    background: linear-gradient(180deg, #F8FAFC, #EEF2F7);
}

.chat-actions {
    gap: 11px;
    padding: 5px;
    border: 1px solid #D9E4EF;
    border-radius: 28px;
    background: #FFFFFF;
    box-shadow: 0 6px 18px rgba(15, 23, 42, .07);
}

.chat-input,
.chat-input:hover,
.chat-input:focus {
    padding: 13px 15px;
    border-color: transparent;
    background: transparent;
}

.btn-send-chat {
    background: linear-gradient(135deg, var(--chat-green), #1769AA);
    box-shadow: 0 8px 18px rgba(13, 50, 97, .28);
}

/* Responsive */
@media (max-width: 768px) {
    .chat-shell {
        padding: 0;
        min-height: calc(100vh - 70px);
        background: var(--chat-bg);
    }

    .chat-card {
        max-width: 100%;
        height: calc(100vh - 70px);
        min-height: calc(100vh - 70px);
        border-radius: 0;
        box-shadow: none;
        border: none;
    }

    .chat-header {
        padding: 11px 13px;
        min-height: 64px;
    }

    .chat-title {
        font-size: 15px;
    }

    .chat-title-icon {
        width: 40px;
        height: 40px;
    }

    .chat-subtitle {
        font-size: 11px;
    }

    .chat-header-actions {
        width: 100%;
        justify-content: space-between;
        gap: 7px;
    }

    .chat-status-pill,
    .chat-sync-pill,
    .chat-back-btn {
        height: 34px;
        padding: 0 11px;
        font-size: 11px;
    }

    .chat-info-bar {
        display: none;
    }

    .chat-body {
        padding: 18px 12px;
    }

    .bubble {
        max-width: 82%;
    }

    .msg-text {
        padding-right: 42px;
        font-size: 13.5px;
    }

    .chat-footer {
        padding: 10px;
    }

    .chat-actions {
        gap: 8px;
    }

    .chat-help-text {
        display: none;
    }
}

.chat-participant-role {
    color: rgba(255, 255, 255, .72);
}

/* ==========================================================
   CONTEXTE DE LA DEMANDE (2026-11)
   ========================================================== */
.chat-info-bar {
    justify-content: flex-start;
    flex-wrap: wrap;
}
.chat-context-count {
    margin-left: auto;
}
.chat-context-link {
    color: inherit;
    text-decoration: none;
    border-radius: 8px;
    padding: 2px 6px;
    margin-left: -6px;
}
.chat-context-link:hover {
    background: rgba(13, 50, 97, .07);
    color: var(--chat-green);
}
.chat-context-link-icon {
    font-size: 11px;
    opacity: .7;
}
.chat-context-aircraft strong {
    color: #0F172A;
    letter-spacing: .02em;
}
.chat-context-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 800;
    border: 1px solid transparent;
    white-space: nowrap;
}
.chat-context-badge.priority-aog { background: #FEF2F2; color: #B91C1C; border-color: #FECACA; }
.chat-context-badge.priority-urgent { background: #FFFBEB; color: #B45309; border-color: #FDE68A; }
.chat-context-badge.priority-routine { background: #EFF6FF; color: #1D4ED8; border-color: #BFDBFE; }
.chat-context-status { background: #F1F5F9; color: #334155; border-color: #E2E8F0; }
.chat-context-status i { font-size: 7px; color: #22C55E; }

/* ==========================================================
   PIÈCES JOINTES (2026-11)
   ========================================================== */
.btn-attach-chat {
    width: 48px;
    height: 48px;
    min-width: 48px;
    border-radius: 50%;
    border: 1px solid var(--chat-border);
    background: #ffffff;
    color: var(--chat-green);
    font-size: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background .2s ease, color .2s ease;
}
.btn-attach-chat:hover {
    background: var(--chat-green-light);
}
.btn-attach-chat.has-file {
    background: var(--chat-green);
    color: #ffffff;
    border-color: var(--chat-green);
}
.chat-attachment-preview {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 8px 58px;
    padding: 7px 10px;
    border-radius: 12px;
    background: var(--chat-green-light);
    border: 1px solid var(--chat-green-soft);
    color: #0F172A;
    font-size: 12.5px;
    max-width: calc(100% - 58px);
}
.chat-attachment-preview[hidden] { display: none; }
.chat-attachment-preview-name {
    font-weight: 800;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}
.chat-attachment-preview-size { color: var(--chat-muted); white-space: nowrap; }
.chat-attachment-remove {
    margin-left: auto;
    border: 0;
    background: transparent;
    color: #64748B;
    width: 28px;
    height: 28px;
    border-radius: 50%;
}
.chat-attachment-remove:hover { background: rgba(15, 23, 42, .08); color: #B91C1C; }

.msg-attachment-image {
    display: block;
    margin: 4px 0 6px;
    border-radius: 10px;
    overflow: hidden;
    max-width: 280px;
}
.msg-attachment-image img {
    display: block;
    width: 100%;
    max-height: 220px;
    object-fit: cover;
}
.msg-attachment {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 4px 0 2px;
    padding: 8px 10px;
    border-radius: 10px;
    background: rgba(15, 23, 42, .05);
    color: #0F172A;
    text-decoration: none;
    max-width: 320px;
}
.msg-attachment:hover { background: rgba(15, 23, 42, .09); color: #0F172A; }
.msg-attachment-icon { font-size: 22px; color: #B91C1C; line-height: 1; }
.msg-attachment-copy { display: flex; flex-direction: column; min-width: 0; flex: 1; }
.msg-attachment-name { font-weight: 800; font-size: 12.5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.msg-attachment-size { font-size: 11px; color: var(--chat-muted); }

/* ==========================================================
   ACCESSIBILITÉ (2026-11) : focus clavier visible
   ========================================================== */
.chat-card a:focus-visible,
.chat-card button:focus-visible,
.chat-card textarea:focus-visible,
.chat-body:focus-visible {
    outline: 3px solid var(--chat-sky);
    outline-offset: 2px;
}

@media (max-width: 576px) {
    .btn-attach-chat { width: 42px; height: 42px; min-width: 42px; font-size: 18px; }
    .chat-attachment-preview { margin-left: 0; max-width: 100%; }
    .chat-context-count { margin-left: 0; }
}

/* ==========================================================================
   THÈME « MESSAGERIE » FAÇON WHATSAPP — COULEURS CAN, SANS VERT (2026-11)
   --------------------------------------------------------------------------
   Bloc placé en dernier : il remplace l'apparence précédente sans modifier le
   HTML ni le fonctionnement (envoi AJAX, actualisation, pièces jointes).
   Palette : bleu marine CAN (#0D3261), bleu ciel (#00B2FF), bulles blanches
   (reçues) et bleu très clair (envoyées), fond gris-bleu discret.
   ========================================================================== */
:root {
    --wa-navy: #0D3261;
    --wa-navy-dark: #08264A;
    --wa-sky: #00B2FF;
    --wa-sent: #D9EDFF;          /* bulle envoyée */
    --wa-received: #FFFFFF;      /* bulle reçue */
    --wa-wallpaper: #EAF0F6;     /* fond de conversation */
    --wa-bar: #F0F2F5;           /* barres haute/basse */
    --wa-text: #111B21;
    --wa-muted: #667781;
    --wa-shadow: 0 1px .5px rgba(11, 20, 26, .13);
}

/* Page : fond uni, carte pleine hauteur, sans quadrillage décoratif */
.chat-shell {
    padding: 16px;
    background: #E3E9F0;
}
.chat-shell::before {
    display: none;
}
.chat-card {
    border-radius: 12px;
    border: 0;
    box-shadow: 0 6px 24px rgba(11, 20, 26, .12);
}

/* En-tête compact, bleu marine uni */
.chat-header {
    min-height: 62px;
    padding: 10px 16px;
    background: var(--wa-navy);
}
.chat-header::after {
    height: 2px;
    background: var(--wa-sky);
}
.chat-title-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #DCE8F5;
    color: var(--wa-navy);
    font-size: 16px;
    box-shadow: none;
}
.chat-title-icon::after {
    display: none;            /* pastille « en ligne » verte supprimée (pas de vraie présence) */
}
.chat-title {
    font-size: 16px;
    font-weight: 700;
}
.chat-subtitle {
    font-size: 12px;
    opacity: .85;
}
.chat-sync-pill,
.chat-status-pill {
    padding: 5px 10px;
    font-size: 11px;
    background: rgba(255, 255, 255, .10);
    border-color: rgba(255, 255, 255, .18);
}
.chat-sync-dot {
    background: var(--wa-sky);
    box-shadow: 0 0 0 3px rgba(0, 178, 255, .22);
}
.chat-back-btn {
    padding: 6px 12px;
    font-size: 12px;
}

/* Barre de contexte : claire et fine */
.chat-info-bar {
    min-height: 38px;
    padding: 6px 16px;
    background: #FFFFFF;
    border-bottom: 1px solid #E2E8F0;
}
.chat-context-status i {
    color: var(--wa-sky);
}

/* Fond de conversation : uni avec un motif de points très discret */
.chat-body {
    padding: 18px 7% 14px;
    background-color: var(--wa-wallpaper);
    background-image: radial-gradient(rgba(13, 50, 97, .07) 1px, transparent 1.2px);
    background-size: 22px 22px;
}

/* Espacement : serré entre messages d'un même expéditeur, aéré entre expéditeurs */
.msg {
    margin-bottom: 2px;
}
.msg:not(.grouped) {
    margin-top: 10px;
}
.msg.grouped {
    margin-top: 0;
}

/* Séparateur de jour : pastille centrée qui reste visible en haut au défilement */
.day-divider {
    position: sticky;
    top: 6px;
    z-index: 2;
    margin: 10px 0 8px;
}
.day-divider span {
    background: #FFFFFF;
    color: #54656F;
    border: 0;
    border-radius: 8px;
    padding: 5px 12px;
    font-size: 11.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .02em;
    box-shadow: var(--wa-shadow);
}

/* Bulles */
.bubble {
    display: flow-root;          /* contient l'heure flottante */
    min-width: 0;
    max-width: 65%;
    padding: 6px 8px 7px 9px;
    border: 0;
    border-radius: 8px;
    background: var(--wa-received);
    color: var(--wa-text);
    box-shadow: var(--wa-shadow);
}
.msg.me .bubble {
    background: var(--wa-sent);
    border-color: transparent;
}
/* Queue de bulle uniquement sur le premier message d'un groupe */
.msg.me:not(.grouped) .bubble {
    border-top-right-radius: 0;
}
.msg:not(.me):not(.grouped) .bubble {
    border-top-left-radius: 0;
}
.msg.grouped .bubble {
    border-radius: 8px;
}
.msg.me:not(.grouped) .bubble::after {
    border-top-color: var(--wa-sent);
}
.msg:not(.me):not(.grouped) .bubble::before {
    border-top-color: var(--wa-received);
}

/* Nom de l'expéditeur (messages reçus) */
.sender-name {
    margin-bottom: 2px;
    color: var(--wa-navy);
    font-size: 12.5px;
    font-weight: 700;
}

/*
 * Texte : CORRECTIF du texte « centré ». L'ancien white-space: pre-wrap affichait
 * l'indentation et les retours à la ligne du template PHP. nl2br() fournit déjà
 * les vrais retours à la ligne, donc white-space: normal suffit.
 */
.msg-text {
    display: inline;
    padding-right: 0;
    white-space: normal;
    font-size: 14.2px;
    font-weight: 400;
    line-height: 1.4;
    overflow-wrap: anywhere;
}

/* Heure + coche flottantes en bas à droite, sur la dernière ligne quand il y a la place */
.meta {
    float: right;
    position: relative;
    top: 5px;
    margin: 0 0 0 12px;
    color: var(--wa-muted);
    font-size: 11px;
    font-weight: 400;
}
.msg.me .meta i {
    color: var(--wa-sky);
}

/* Pièces jointes dans les bulles */
.msg-attachment-image,
.msg-attachment {
    margin: 2px 0 4px;
}
.msg-attachment {
    background: rgba(13, 50, 97, .06);
}
.msg.me .msg-attachment {
    background: rgba(13, 50, 97, .08);
}

/* Bouton « descendre » rond, comme WhatsApp */
.scroll-bottom-btn {
    background: #FFFFFF;
    color: var(--wa-muted);
    box-shadow: 0 2px 6px rgba(11, 20, 26, .2);
}

/* Zone de saisie */
.chat-footer {
    padding: 8px 12px 6px;
    background: var(--wa-bar);
    border-top: 0;
}
.chat-actions {
    align-items: flex-end;
    gap: 8px;
}
.btn-attach-chat {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border: 0;
    background: transparent;
    color: #54656F;
    font-size: 22px;
}
.btn-attach-chat:hover {
    background: rgba(11, 20, 26, .06);
}
.btn-attach-chat.has-file {
    background: rgba(0, 178, 255, .14);
    color: var(--wa-navy);
}
.chat-input {
    min-height: 42px;
    padding: 10px 14px;
    border: 0;
    border-radius: 8px;
    background: #FFFFFF;
    font-size: 14.5px;
    font-weight: 400;
}
.chat-input:hover,
.chat-input:focus {
    border: 0;
    box-shadow: none;
}
.btn-send-chat {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 50%;
    background: var(--wa-navy);
    box-shadow: none;
}
.btn-send-chat:hover:not(:disabled) {
    background: var(--wa-sky);
}
.btn-send-chat:disabled {
    background: #B8C4CF;
}
.chat-attachment-preview {
    margin: 0 0 6px 50px;
    border-radius: 8px;
    background: #FFFFFF;
    border: 0;
    box-shadow: var(--wa-shadow);
}
.chat-help-text {
    margin-top: 4px;
    font-size: 11px;
    color: var(--wa-muted);
}

/* Mobile : bulles plus larges, marges réduites */
@media (max-width: 768px) {
    .chat-shell { padding: 0; }
    .chat-card { border-radius: 0; }
    .chat-body { padding: 12px 10px; }
    .bubble { max-width: 85%; }
    .chat-attachment-preview { margin-left: 0; }
}

/* Short laptop viewport: do not let a fixed minimum height push the composer away. */
@media (max-height: 700px) and (min-width: 769px) {
    .chat-card {
        height: calc(100vh - 104px);
        min-height: 0;
    }
}
CSS);

/**
 * Chat JavaScript
 */
$this->registerJs(<<<JS
(function () {
    var messageTextarea = $("#message-textarea");
    var sendButton = $("#send-button");
    var syncStatus = $("#sync-status");
    var scrollBottomBtn = $("#scroll-bottom-btn");
    var messageList = $("#message-list");
    var replyForm = $("#reply-form");
    var isSending = false; // empêche un double envoi (double appui sur Entrée)

    /* PIÈCES JOINTES (2026-11) : mêmes limites que le serveur (modèle Conversations). */
    var attachmentInput = $("#chat-attachment-input");
    var attachButton = $("#chat-attach-button");
    var attachmentPreview = $("#chat-attachment-preview");
    var attachmentMaxBytes = {$attachmentMaxBytes};
    var attachmentExtensions = {$attachmentExtensionsJs};

    /* ACCESSIBILITÉ : messages déjà affichés, pour n'annoncer que les nouveaux. */
    var liveRegion = $("#chat-live-region");
    var knownMessageKeys = {};
    messageList.find(".msg[data-message-key]").each(function () {
        knownMessageKeys[$(this).attr("data-message-key")] = true;
    });

    function selectedAttachment() {
        var input = attachmentInput.get(0);
        return input && input.files && input.files.length ? input.files[0] : null;
    }

    function formatFileSize(bytes) {
        return bytes >= 1048576
            ? (Math.round(bytes / 104857.6) / 10) + " MB"
            : Math.max(1, Math.round(bytes / 1024)) + " KB";
    }

    function clearAttachment() {
        attachmentInput.val("");
        attachmentPreview.attr("hidden", true);
        attachButton.removeClass("has-file");
    }

    function hasContent() {
        return $.trim(messageTextarea.val() || "") !== "" || selectedAttachment() !== null;
    }
    var lastRenderedHtml = $.trim(messageList.html() || "");

    function getChatBody() {
        return $("#chatBody");
    }

    function isNearBottom() {
        var chatBody = getChatBody();

        if (!chatBody.length) {
            return true;
        }

        return chatBody[0].scrollTop + chatBody.innerHeight() >= chatBody[0].scrollHeight - 100;
    }

    function scrollMessagesToBottom() {
        var chatBody = getChatBody();

        if (chatBody.length) {
            chatBody.scrollTop(chatBody[0].scrollHeight);
        }

        scrollBottomBtn.hide();
    }

    function toggleSendButton() {
        // Envoi possible avec un texte OU une pièce jointe.
        sendButton.prop("disabled", !hasContent());
    }

    function autoResizeTextarea() {
        var el = messageTextarea.get(0);

        if (!el) {
            return;
        }

        el.style.height = "auto";
        el.style.height = Math.min(el.scrollHeight, 140) + "px";
    }

    function setSyncStatus(text, active) {
        if (!syncStatus.length) {
            return;
        }

        syncStatus.find(".sync-text").text(text);

        if (active) {
            syncStatus.find(".chat-sync-dot").css("background", "#00B2FF"); // bleu CAN (plus de vert)
        } else {
            syncStatus.find(".chat-sync-dot").css("background", "#94a3b8");
        }
    }

    function refreshMessages(forceScroll) {
        if (window.hideGlobalLoaderOnChatPage) {
            window.hideGlobalLoaderOnChatPage();
        }

        // forceScroll : après l'envoi d'un message, on descend toujours jusqu'au dernier message.
        var shouldScroll = forceScroll === true || isNearBottom();

        setSyncStatus("Syncing...", true);

        $.ajax({
            url: "{$fetchUrl}",
            type: "GET",
            global: false,
            cache: false,
            success: function(data) {
                var normalizedData = $.trim(data || "");

                if (normalizedData !== lastRenderedHtml) {
                    messageList.html(data);
                    lastRenderedHtml = normalizedData;

                    /*
                     * ACCESSIBILITÉ : annonce uniquement les NOUVEAUX messages reçus
                     * (pas ceux envoyés par l'utilisateur, ni toute la conversation).
                     */
                    var newIncoming = [];
                    messageList.find(".msg[data-message-key]").each(function () {
                        var key = $(this).attr("data-message-key");
                        if (!knownMessageKeys[key]) {
                            knownMessageKeys[key] = true;
                            if ($(this).attr("data-incoming") === "1") {
                                newIncoming.push($(this).attr("data-sender") || "");
                            }
                        }
                    });
                    if (newIncoming.length === 1) {
                        liveRegion.text("New message from " + newIncoming[0] + ".");
                    } else if (newIncoming.length > 1) {
                        liveRegion.text(newIncoming.length + " new messages.");
                    }

                    // UI ONLY: keep the header counter synchronized after AJAX refresh.
                    $("#message-count").text(messageList.find(".msg").length);

                    if (shouldScroll) {
                        scrollMessagesToBottom();
                    }
                }

                setSyncStatus("Auto-refresh", true);

                if (window.hideGlobalLoaderOnChatPage) {
                    window.hideGlobalLoaderOnChatPage();
                }
            },
            error: function() {
                setSyncStatus("Offline", false);

                if (window.hideGlobalLoaderOnChatPage) {
                    window.hideGlobalLoaderOnChatPage();
                }
            }
        });
    }

    $(document).ready(function() {
        if (window.hideGlobalLoaderOnChatPage) {
            window.hideGlobalLoaderOnChatPage();
        }

        scrollMessagesToBottom();
        toggleSendButton();
        autoResizeTextarea();

        messageTextarea.on("input keyup change", function() {
            toggleSendButton();
            autoResizeTextarea();
        });

        /*
         * CLAVIER (2026-11) : Entrée envoie le message, Maj + Entrée insère un
         * retour à la ligne (convention des messageries). Ctrl + Entrée envoie
         * toujours. isComposing évite d'envoyer pendant une saisie IME.
         */
        messageTextarea.on("keydown", function(event) {
            if (event.key === "Enter" && !event.shiftKey && !event.isComposing) {
                event.preventDefault();
                replyForm.trigger("submit");
            }
        });

        getChatBody().on("scroll", function() {
            if (isNearBottom()) {
                scrollBottomBtn.hide();
            } else {
                scrollBottomBtn.css("display", "flex");
            }
        });

        scrollBottomBtn.on("click", function() {
            scrollMessagesToBottom();
        });

        /*
         * ENVOI AJAX (2026-11) : le message est envoyé sans recharger la page.
         *  - Yii valide d'abord le formulaire, puis déclenche « beforeSubmit » :
         *    on y lance l'envoi AJAX et on retourne false pour bloquer l'envoi classique.
         *  - Si le script ActiveForm n'est pas chargé, le gestionnaire « submit »
         *    envoie directement en AJAX.
         *  - Sans JavaScript, le formulaire reste un envoi classique (redirection).
         * En cas d'échec, le texte saisi est conservé et l'erreur affichée par Toastr.
         */
        function showSendError(message) {
            var text = message || "Failed to send message. Please try again.";

            if (typeof toastr !== "undefined") {
                toastr.error(text);
            } else {
                alert(text);
            }
        }

        function sendMessageAjax() {
            if (isSending || !hasContent()) {
                return;
            }

            isSending = true;
            sendButton.prop("disabled", true);
            sendButton.html('<i class="bi bi-hourglass-split"></i>');

            if (window.hideGlobalLoaderOnChatPage) {
                window.hideGlobalLoaderOnChatPage();
            }

            /*
             * FormData (et non serialize()) pour transmettre aussi le fichier joint.
             * Il contient le jeton CSRF du formulaire.
             */
            $.ajax({
                url: replyForm.attr("action"),
                type: "POST",
                data: new FormData(replyForm.get(0)),
                processData: false,
                contentType: false,
                dataType: "json",
                global: false,
                cache: false
            }).done(function(response) {
                if (response && response.success) {
                    messageTextarea.val("");
                    clearAttachment();
                    autoResizeTextarea();
                    refreshMessages(true);
                } else {
                    showSendError(response && response.message);
                }
            }).fail(function(xhr) {
                showSendError(xhr.responseJSON && xhr.responseJSON.message);
            }).always(function() {
                isSending = false;
                sendButton.html('<i class="bi bi-send-fill"></i>');
                toggleSendButton();
                messageTextarea.trigger("focus");
            });
        }

        /* PIÈCES JOINTES : choix, contrôle immédiat (type + taille) et retrait. */
        attachButton.on("click", function () {
            attachmentInput.trigger("click");
        });

        attachmentInput.on("change", function () {
            var file = selectedAttachment();
            if (!file) {
                clearAttachment();
                toggleSendButton();
                return;
            }

            var extension = (file.name.split(".").pop() || "").toLowerCase();
            if (attachmentExtensions.indexOf(extension) === -1) {
                clearAttachment();
                showSendError("Only PDF, PNG and JPG files can be attached.");
            } else if (file.size > attachmentMaxBytes) {
                clearAttachment();
                showSendError("The attachment is too large. Maximum size: 10 MB.");
            } else {
                $("#chat-attachment-name").text(file.name);
                $("#chat-attachment-size").text(formatFileSize(file.size));
                attachmentPreview.removeAttr("hidden");
                attachButton.addClass("has-file");
            }

            toggleSendButton();
            messageTextarea.trigger("focus");
        });

        $("#chat-attachment-remove").on("click", function () {
            clearAttachment();
            toggleSendButton();
            attachButton.trigger("focus");
        });

        replyForm.on("beforeSubmit", function() {
            sendMessageAjax();
            return false; // bloque l'envoi classique : le message part en AJAX
        });

        replyForm.on("submit", function(event) {
            toggleSendButton();

            if (sendButton.prop("disabled") || isSending) {
                event.preventDefault();
                return false;
            }

            // Pas de script ActiveForm : envoi AJAX direct.
            if (!replyForm.data("yiiActiveForm")) {
                event.preventDefault();
                sendMessageAjax();
                return false;
            }

            return true; // Yii valide puis déclenche beforeSubmit
        });

        /*
         * ACTUALISATION ÉCONOME (2026-11) : pas de requête toutes les 5 s quand
         * l'onglet est masqué ; une actualisation immédiate a lieu au retour.
         */
        setInterval(function () {
            if (!document.hidden) {
                refreshMessages();
            }
        }, 5000);

        document.addEventListener("visibilitychange", function () {
            if (!document.hidden) {
                refreshMessages();
            }
        });
    });
})();
JS);
?>

<div class="chat-shell">
    <div class="chat-card">

        <!-- Chat header -->
        <div class="chat-header">
            <div class="chat-title-block">
                <!-- Read-only participant identity prepared above from the authorized Chat record. -->
                <span class="chat-title-icon">
                    <?= Html::encode($participantInitial) ?>
                </span>

                <div class="chat-title-text">
                    <h1 class="chat-title">
                        <?= Html::encode($otherParticipantName) ?>
                    </h1>

                    <div class="chat-subtitle">
                        <span class="chat-participant-role"><?= Html::encode($otherParticipantRole) ?></span>
                        &nbsp;•&nbsp; Request #<?= Html::encode($conversation->request_id) ?>
                    </div>
                </div>
            </div>

            <div class="chat-header-actions">
                <span class="chat-sync-pill" id="sync-status">
                    <span class="chat-sync-dot"></span>
                    <span class="sync-text">Auto-refresh</span>
                </span>

                <span class="chat-status-pill">
                    <i class="bi bi-shield-lock"></i>
                    Private channel
                </span>

                <!-- Deterministic navigation works even when the page was opened from a notification. -->
                <?= Html::a('<i class="bi bi-arrow-left"></i> Back', ['conversations/index'], [
                    'class' => 'chat-back-btn',
                ]) ?>
            </div>
        </div>

        <!-- Chat info bar -->
        <div class="chat-info-bar" role="group" aria-label="Request context">
            <?php /* CONTEXTE : lien vers la demande, avion, priorité, statut. */ ?>
            <?php if ($contextRequestUrl): ?>
                <a class="chat-info-item chat-context-link" href="<?= Html::encode($contextRequestUrl) ?>" data-pjax="0"
                   title="Open request #<?= Html::encode($conversation->request_id) ?>">
                    <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                    <span class="chat-info-primary">Request #<?= Html::encode($conversation->request_id) ?></span>
                    <i class="bi bi-box-arrow-up-right chat-context-link-icon" aria-hidden="true"></i>
                </a>
            <?php else: ?>
                <div class="chat-info-item">
                    <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                    <span class="chat-info-primary">Request #<?= Html::encode($conversation->request_id) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($contextRegistration !== '' || $contextModel !== ''): ?>
                <div class="chat-info-item chat-context-aircraft">
                    <i class="bi bi-airplane" aria-hidden="true"></i>
                    <span class="visually-hidden">Aircraft:</span>
                    <?php if ($contextRegistration !== ''): ?>
                        <strong><?= Html::encode($contextRegistration) ?></strong>
                    <?php endif; ?>
                    <?php if ($contextModel !== ''): ?>
                        <span><?= Html::encode(($contextRegistration !== '' ? '· ' : '') . $contextModel) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($contextPriorityLabel !== ''): ?>
                <span class="chat-context-badge priority-<?= Html::encode($contextPriority) ?>">
                    <i class="bi <?= Html::encode($contextPriorityIcons[$contextPriority] ?? 'bi-calendar-check') ?>" aria-hidden="true"></i>
                    <span class="visually-hidden">Priority:</span>
                    <?= Html::encode($contextPriorityLabel) ?>
                </span>
            <?php endif; ?>

            <?php if ($contextStatusLabel !== ''): ?>
                <span class="chat-context-badge chat-context-status">
                    <i class="bi bi-circle-fill" aria-hidden="true"></i>
                    <span class="visually-hidden">Status:</span>
                    <?= Html::encode($contextStatusLabel) ?>
                </span>
            <?php endif; ?>

            <?php /* Rôle de l'interlocuteur retiré ici : déjà affiché sous son nom dans l'en-tête. */ ?>
            <div class="chat-info-item chat-context-count">
                <i class="bi bi-chat-left-text"></i>
                <span id="message-count"><?= Html::encode($messageCount) ?></span> message(s)
            </div>

            <?php /* « Auto-refresh every 5 seconds » retiré : déjà indiqué par la pastille de l'en-tête. */ ?>
        </div>

        <!--
            MESSAGES D'ERREUR : affichés uniquement par Toastr (views/layouts/main.php).
            L'ancien bandeau faisait doublon et affichait le texte sans échappement.
        -->

        <!-- Chat body -->
        <?php /* ACCESSIBILITÉ : zone nommée et défilable au clavier (tabindex). */ ?>
        <div class="chat-body" id="chatBody" role="region" aria-label="Messages" tabindex="0">
            <div class="message-list" id="message-list">
                <?= $this->render('_message_list', [
                    'messages' => $messages,
                ]) ?>
            </div>

            <button type="button" class="scroll-bottom-btn" id="scroll-bottom-btn" title="Scroll to latest message" aria-label="Scroll to latest message">
                <i class="bi bi-arrow-down" aria-hidden="true"></i>
            </button>
        </div>

        <?php /* ACCESSIBILITÉ : annonce vocale des nouveaux messages reçus (rempli par le script). */ ?>
        <div id="chat-live-region" class="visually-hidden" aria-live="polite" aria-atomic="true"></div>

        <!-- Chat footer -->
        <div class="chat-footer">
            <?php $form = ActiveForm::begin([
                'id' => 'reply-form',
                // SECURITY: never post a raw chat identifier back to the controller.
                'action' => ['conversations/reply', 'chat_id' => $conversationId],
                // PIÈCES JOINTES : nécessaire pour l'envoi classique (sans JavaScript).
                'options' => ['enctype' => 'multipart/form-data'],
            ]); ?>

            <?php /* Fichier choisi, affiché avant l'envoi (masqué tant qu'aucun fichier). */ ?>
            <div class="chat-attachment-preview" id="chat-attachment-preview" hidden>
                <i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i>
                <span class="chat-attachment-preview-name" id="chat-attachment-name"></span>
                <span class="chat-attachment-preview-size" id="chat-attachment-size"></span>
                <button type="button" class="chat-attachment-remove" id="chat-attachment-remove" aria-label="Remove attachment" title="Remove attachment">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>

            <div class="chat-actions">
                <?php /* Bouton trombone : ouvre le sélecteur de fichier (champ natif masqué). */ ?>
                <button type="button" class="btn-attach-chat" id="chat-attach-button"
                        title="Attach a file (PDF, PNG, JPG – max 10 MB)"
                        aria-label="Attach a file (PDF, PNG or JPG, maximum 10 MB)">
                    <i class="bi bi-paperclip" aria-hidden="true"></i>
                </button>
                <?= Html::activeFileInput($newMessage, 'attachmentFile', [
                    'id' => 'chat-attachment-input',
                    'class' => 'visually-hidden',
                    'tabindex' => '-1',
                    'accept' => '.pdf,.png,.jpg,.jpeg,application/pdf,image/png,image/jpeg',
                    'aria-hidden' => 'true',
                ]) ?>

                <div class="chat-input-wrap">
                    <?= $form->field($newMessage, 'message')->textarea([
                        'rows' => 1,
                        'id' => 'message-textarea',
                        'class' => 'form-control chat-input',
                        'placeholder' => 'Message ' . $otherParticipantName . '...',
                        // ACCESSIBILITÉ : le libellé visuel est masqué, il faut un nom accessible.
                        'aria-label' => 'Message to ' . $otherParticipantName,
                    ])->label(false) ?>
                </div>

                <?= Html::submitButton(
                    '<i class="bi bi-send-fill"></i>',
                    [
                        'class' => 'btn-send-chat',
                        'id' => 'send-button',
                        'disabled' => true,
                        'title' => 'Send message',
                        'aria-label' => 'Send message',
                    ]
                ) ?>
            </div>

            <?php ActiveForm::end(); ?>

            <div class="chat-help-text">
                <i class="bi bi-info-circle"></i>
                Press <strong>Enter</strong> to send, <strong>Shift + Enter</strong> for a new line. Attach PDF, PNG or JPG (max 10 MB).
            </div>
        </div>

    </div>
</div>
