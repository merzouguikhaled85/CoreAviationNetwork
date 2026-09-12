<?php
/** @var yii\web\View $this */

use yii\helpers\Url;

/**
 * Detect current route
 * Disable global spinner on conversation page because chat uses automatic AJAX refresh.
 */
$currentController = Yii::$app->controller ? Yii::$app->controller->id : '';
$currentAction = Yii::$app->controller && Yii::$app->controller->action ? Yii::$app->controller->action->id : '';

$disableGlobalLoader = (
    $currentController === 'conversations'
    && in_array($currentAction, ['view'], true)
);

/**
 * If current page is conversation/view, force hide loader and stop rendering spinner HTML.
 */
if ($disableGlobalLoader) {
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

(function () {
    function hideConversationLoader() {
        var loader = document.getElementById('av-global-loader');

        if (loader) {
            loader.classList.add('hidden');
            loader.style.display = 'none';
            loader.style.opacity = '0';
            loader.style.visibility = 'hidden';
            loader.style.pointerEvents = 'none';
        }
    }

    hideConversationLoader();
    document.addEventListener('DOMContentLoaded', hideConversationLoader);
    window.addEventListener('load', hideConversationLoader);
    setTimeout(hideConversationLoader, 300);
})();
JS);

    return;
}

/**
 * Styles du Spinner Global (Preloader)
 */
$this->registerCss(<<<CSS
#av-global-loader {
    position: fixed;
    top: 0; 
    left: 0; 
    width: 100%; 
    height: 100%;
    background: radial-gradient(circle at center, #ffffff 40%, #f8fafc 100%);
    z-index: 99999;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: opacity 0.4s ease, visibility 0.4s ease;
}

#av-global-loader.hidden {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
}

.av-loader-box {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
    padding: 30px;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border-radius: 16px;
}

.av-loader-logo {
    margin-bottom: 5px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.loader-logo-img {
    height: 120px;
    width: auto;
    object-fit: contain;
    filter: drop-shadow(0 4px 10px rgba(15, 23, 42, 0.06));
    animation: av-logo-pulse 2s ease-in-out infinite alternate;
}

.av-loader-ring {
    width: 48px;
    height: 48px;
    border: 3px solid rgba(11, 94, 215, 0.08);
    border-top: 3px solid var(--av-blue, #0b5ed7);
    border-radius: 50%;
    animation: av-ring-spin 0.8s cubic-bezier(0.5, 0.1, 0.5, 0.9) infinite;
    margin: 0 auto;
}

@keyframes av-ring-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes av-logo-pulse {
    0% { transform: scale(0.98); opacity: 0.92; }
    100% { transform: scale(1.02); opacity: 1; }
}

.av-loader-text {
    font-weight: 800;
    color: #1e293b;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 3px;
    animation: av-pulse 1.5s ease-in-out infinite;
}

@keyframes av-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
CSS);

/**
 * JavaScript du Spinner
 */
$jsSpinner = <<<JS
(function () {
    var loader = document.getElementById('av-global-loader');

    function hideLoader() {
        if (window.AV_DISABLE_GLOBAL_LOADER === true) {
            return;
        }

        if (loader && !loader.classList.contains('hidden')) {
            setTimeout(function() {
                loader.classList.add('hidden');
            }, 300);
        }
    }

    function showLoader() {
        if (window.AV_DISABLE_GLOBAL_LOADER === true) {
            return;
        }

        if (loader) {
            loader.classList.remove('hidden');
        }
    }

    // Hide when page loading is finished
    window.addEventListener('load', hideLoader);

    /*
     * GLOBAL LOADER SAFETY 2026: the interface is usable after DOMContentLoaded.
     * Do not keep it blocked by a slow advertisement, image, video or CDN asset.
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hideLoader, { once: true });
    } else {
        // Yii registers this block at DOM-ready: hide immediately in that case.
        hideLoader();
    }

    // Absolute fallback for cached pages or third-party resources that never finish.
    window.setTimeout(hideLoader, 4000);

    // Browser back/forward cache support
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            hideLoader();
        }
    });

    // Security fallback if document is already loaded
    if (document.readyState === 'complete') {
        hideLoader();
    }

    // Show when leaving the page
    window.addEventListener('beforeunload', function() {
        showLoader();
    });

    // Pjax support
    $(document).on('pjax:send', function() { 
        showLoader(); 
    });

    $(document).on('pjax:complete', function() { 
        hideLoader(); 
    });
})();
JS;

$this->registerJs($jsSpinner);
?>

<!-- HTML du Spinner -->
<div id="av-global-loader">
    <div class="av-loader-box">
        <div class="av-loader-logo">
            <img src="<?= Url::to('@web/logo/can-logo-main.png') ?>" alt="Core Aviation Network Logo" class="loader-logo-img">
        </div>

        <div class="av-loader-ring"></div>

        <div class="av-loader-text">
            Aircraft Services Network
        </div>
    </div>
</div>
