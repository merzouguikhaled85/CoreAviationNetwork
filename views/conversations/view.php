<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use app\components\UrlIdHelper;
use app\models\AoProfile;
use app\models\Chat;
use app\models\MroProfile;

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
        var value = $.trim(messageTextarea.val() || "");
        sendButton.prop("disabled", value.length === 0);
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
            syncStatus.find(".chat-sync-dot").css("background", "#25d366");
        } else {
            syncStatus.find(".chat-sync-dot").css("background", "#94a3b8");
        }
    }

    function refreshMessages() {
        if (window.hideGlobalLoaderOnChatPage) {
            window.hideGlobalLoaderOnChatPage();
        }

        var shouldScroll = isNearBottom();

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

        messageTextarea.on("keydown", function(event) {
            if (event.ctrlKey && event.key === "Enter") {
                $("#reply-form").submit();
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

        $("#reply-form").on("submit", function(event) {
            toggleSendButton();

            if (sendButton.prop("disabled")) {
                event.preventDefault();
                return false;
            }

            sendButton.prop("disabled", true);
            sendButton.html('<i class="bi bi-hourglass-split"></i>');

            if (window.hideGlobalLoaderOnChatPage) {
                window.hideGlobalLoaderOnChatPage();
            }

            return true;
        });

        setInterval(refreshMessages, 5000);
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
        <div class="chat-info-bar">
            <div class="chat-info-item">
                <i class="bi bi-clipboard-check"></i>
                <span class="chat-info-primary">Request #<?= Html::encode($conversation->request_id) ?></span>
            </div>

            <div class="chat-info-item">
                <i class="bi bi-person-badge"></i>
                <?= Html::encode($otherParticipantRole) ?>
            </div>

            <div class="chat-info-item">
                <i class="bi bi-chat-left-text"></i>
                <span id="message-count"><?= Html::encode($messageCount) ?></span> message(s)
            </div>

            <div class="chat-info-item">
                <i class="bi bi-arrow-repeat"></i>
                Auto-refresh every 5 seconds
            </div>
        </div>

        <!-- Error flash only -->
        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="chat-alert-wrapper">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?= Yii::$app->session->getFlash('error') ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Chat body -->
        <div class="chat-body" id="chatBody">
            <div class="message-list" id="message-list">
                <?= $this->render('_message_list', [
                    'messages' => $messages,
                ]) ?>
            </div>

            <button type="button" class="scroll-bottom-btn" id="scroll-bottom-btn" title="Scroll to latest message">
                <i class="bi bi-arrow-down"></i>
            </button>
        </div>

        <!-- Chat footer -->
        <div class="chat-footer">
            <?php $form = ActiveForm::begin([
                'id' => 'reply-form',
                // SECURITY: never post a raw chat identifier back to the controller.
                'action' => ['conversations/reply', 'chat_id' => $conversationId],
            ]); ?>

            <div class="chat-actions">
                <div class="chat-input-wrap">
                    <?= $form->field($newMessage, 'message')->textarea([
                        'rows' => 1,
                        'id' => 'message-textarea',
                        'class' => 'form-control chat-input',
                        'placeholder' => 'Message ' . $otherParticipantName . '...',
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
                Press <strong>Ctrl + Enter</strong> to send. Messages refresh automatically.
            </div>
        </div>

    </div>
</div>
