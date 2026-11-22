<?php
/** @var yii\web\View $this */

use yii\helpers\Url;

/**
 * CHARGEMENT GLOBAL CAN (2026-11-22)
 *
 * Trois niveaux, du plus discret au plus visible :
 *  1. barre de progression fine en haut de l'écran dès qu'une navigation démarre ;
 *  2. overlay compact (logo CAN + anneau + « Loading… ») seulement si la page
 *     suivante met plus de 700 ms à arriver ;
 *  3. pendant l'envoi d'un formulaire, spinner dans le bouton d'envoi.
 *
 * Contrats conservés pour le reste du projet :
 *  - l'overlay garde l'id #av-global-loader et la classe .hidden (widget Support,
 *    conversations/view.php, mro-applications/view-reports.php) ;
 *  - window.AV_DISABLE_GLOBAL_LOADER = true désactive tout.
 * Nouveau : window.CANLoader.show() / .hide(), et l'attribut data-no-loader sur
 * un lien ou un formulaire pour ne rien afficher.
 */
$currentController = Yii::$app->controller ? Yii::$app->controller->id : '';
$currentAction = Yii::$app->controller && Yii::$app->controller->action ? Yii::$app->controller->action->id : '';

// La conversation se rafraîchit en AJAX : aucun chargement global sur cette page.
$disableGlobalLoader = $currentController === 'conversations' && $currentAction === 'view';

if ($disableGlobalLoader) {
    $this->registerJs('window.AV_DISABLE_GLOBAL_LOADER = true;', \yii\web\View::POS_HEAD);
    return;
}

$this->registerCss(<<<CSS
/* 1. Barre de progression */
#can-nav-progress {
    position: fixed;
    top: 0;
    left: 0;
    height: 3px;
    width: 0;
    background: #0b5ed7;
    box-shadow: 0 0 8px rgba(11, 94, 215, 0.45);
    opacity: 0;
    z-index: 100000;
    pointer-events: none;
}

/* 2. Overlay compact */
#av-global-loader {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(248, 250, 252, 0.72);
    transition: opacity 0.2s ease, visibility 0.2s ease;
}

#av-global-loader.hidden {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
}

.av-loader-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
    padding: 22px 30px 18px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.10);
    transform: translateY(4px);
    transition: transform 0.2s ease;
}

#av-global-loader:not(.hidden) .av-loader-box {
    transform: none;
}

.av-loader-logo {
    height: 54px;
    width: auto;
    display: block;
}

.av-loader-status {
    display: flex;
    align-items: center;
    gap: 10px;
    font: 500 13px/1.2 Inter, system-ui, -apple-system, "Segoe UI", sans-serif;
    color: #475569;
}

.av-loader-ring {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid rgba(11, 94, 215, 0.15);
    border-top-color: #0b5ed7;
    animation: av-ring-spin 0.8s linear infinite;
}

/* Écran de chargement propre à une page : remplacé par le loader CAN (reste en place, invisible). */
.can-own-loader-replaced {
    opacity: 0 !important;
    transition: none !important;
}

/* 3. Bouton d'envoi en cours */
.can-btn-loading {
    pointer-events: none;
    opacity: 0.85;
}

.can-btn-spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    margin-right: 6px;
    border-radius: 50%;
    border: 2px solid currentColor;
    border-right-color: transparent;
    vertical-align: -2px;
    animation: av-ring-spin 0.7s linear infinite;
}

@keyframes av-ring-spin {
    to { transform: rotate(360deg); }
}

@media (prefers-reduced-motion: reduce) {
    .av-loader-ring,
    .can-btn-spinner {
        animation-duration: 2.4s;
    }
    #av-global-loader,
    .av-loader-box {
        transition: none;
    }
}
CSS);

$jsSpinner = <<<'JS'
(function () {
    if (window.CANLoader) {
        return;
    }

    var OVERLAY_DELAY = 700;      // overlay seulement si la page tarde
    var STUCK_TIMEOUT = 12000;    // sécurité : téléchargement, navigation annulée…
    var bar = document.getElementById('can-nav-progress');
    var overlay = document.getElementById('av-global-loader');
    var timers = [];
    var active = false;
    var loadingButton = null;

    function disabled() {
        return window.AV_DISABLE_GLOBAL_LOADER === true;
    }

    function later(fn, ms) {
        timers.push(window.setTimeout(fn, ms));
    }

    function clearTimers() {
        timers.forEach(window.clearTimeout);
        timers = [];
    }

    function setBar(width, duration) {
        if (!bar) return;
        bar.style.transition = 'width ' + duration + 'ms ease-out, opacity 0.2s ease';
        bar.style.opacity = '1';
        bar.style.width = width + '%';
    }

    /*
     * ÉCRAN DE CHARGEMENT D'UNE PAGE (ex. « Submitting your quote… » sur
     * mro-requests/apply) : il est remplacé par la carte CAN avec le logo, qui
     * reprend son message. Un seul chargement à l'écran, toujours avec le logo.
     * Détection : calque fixe visible couvrant la vue, au centre de l'écran.
     */
    var replacedLoader = null;
    var label = overlay ? overlay.querySelector('[data-loader-label]') : null;
    var DEFAULT_LABEL = 'Loading…';

    function findPageLoader() {
        if (!document.elementsFromPoint) return null;
        var stack = document.elementsFromPoint(window.innerWidth / 2, window.innerHeight / 2);
        for (var i = 0; i < stack.length; i++) {
            for (var el = stack[i]; el && el !== document.body && el !== document.documentElement; el = el.parentElement) {
                if (el === overlay || el.id === 'can-nav-progress') break;
                var style = window.getComputedStyle(el);
                if (style.position !== 'fixed' || style.display === 'none' || style.visibility === 'hidden') continue;
                var rect = el.getBoundingClientRect();
                if (rect.width >= window.innerWidth * 0.8 && rect.height >= window.innerHeight * 0.8) {
                    return el;
                }
            }
        }
        return null;
    }

    function pageLoaderMessage(el) {
        var title = el.querySelector('[class*="title"], h1, h2, h3, strong');
        var text = (title ? title.textContent : el.textContent || '').replace(/\s+/g, ' ').trim();
        return text && text.length <= 60 ? text : '';
    }

    function adoptPageLoader() {
        if (replacedLoader || !overlay) return false;
        var el = findPageLoader();
        if (!el) return false;
        replacedLoader = el;
        el.classList.add('can-own-loader-replaced');
        if (label) label.textContent = pageLoaderMessage(el) || DEFAULT_LABEL;
        return true;
    }

    function releasePageLoader() {
        if (replacedLoader) {
            replacedLoader.classList.remove('can-own-loader-replaced');
            replacedLoader = null;
        }
        if (label) label.textContent = DEFAULT_LABEL;
    }

    function showOverlay() {
        if (!overlay || disabled()) return;
        adoptPageLoader();
        // Certaines pages masquent le loader en style inline : on le réinitialise.
        overlay.style.removeProperty('display');
        overlay.style.removeProperty('opacity');
        overlay.style.removeProperty('visibility');
        overlay.style.removeProperty('pointer-events');
        overlay.classList.remove('hidden');
        overlay.setAttribute('aria-busy', 'true');
    }

    function hideOverlay() {
        if (!overlay) return;
        releasePageLoader();
        overlay.classList.add('hidden');
        overlay.setAttribute('aria-busy', 'false');
    }

    function restoreButton() {
        if (!loadingButton) return;
        loadingButton.button.innerHTML = loadingButton.html;
        loadingButton.button.disabled = loadingButton.disabled;
        loadingButton.button.classList.remove('can-btn-loading');
        loadingButton.button.removeAttribute('aria-busy');
        loadingButton = null;
    }

    function start() {
        if (disabled() || active) return;
        active = true;
        clearTimers();

        if (bar) {
            bar.style.transition = 'none';
            bar.style.width = '0';
            void bar.offsetWidth;
        }
        setBar(30, 250);
        later(function () { setBar(80, 6000); }, 260);
        if (adoptPageLoader()) {
            showOverlay();
        } else {
            later(showOverlay, OVERLAY_DELAY);
        }
        // Si la page ne change pas (fichier téléchargé, navigation annulée), on rend la main.
        later(stop, STUCK_TIMEOUT);
    }

    function stop() {
        clearTimers();
        active = false;
        hideOverlay();
        restoreButton();

        if (!bar || bar.style.opacity !== '1') return;
        setBar(100, 180);
        window.setTimeout(function () {
            bar.style.opacity = '0';
            window.setTimeout(function () {
                bar.style.transition = 'none';
                bar.style.width = '0';
            }, 220);
        }, 200);
    }

    // ---------- Liens : quelles navigations méritent un chargement ----------
    var FILE_LINK = /\.(pdf|docx?|xlsx?|csv|zip|rar|7z|png|jpe?g|gif|webp|txt)(\?|#|$)/i;
    var DOWNLOAD_ROUTE = /(download|export|attachment|\/uploads\/)/i;

    function linkStartsNavigation(event, link) {
        if (event.defaultPrevented || event.button !== 0) return false;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
        if (link.hasAttribute('download') || link.hasAttribute('data-no-loader')) return false;
        if (link.hasAttribute('data-bs-toggle') || link.hasAttribute('data-toggle')) return false;

        var target = (link.getAttribute('target') || '').toLowerCase();
        if (target && target !== '_self') return false;

        var href = (link.getAttribute('href') || '').trim();
        if (!href || href.charAt(0) === '#' || /^(mailto|tel|javascript|data|blob):/i.test(href)) return false;
        if (FILE_LINK.test(href) || DOWNLOAD_ROUTE.test(href)) return false;

        var url;
        try {
            url = new URL(link.href, window.location.href);
        } catch (e) {
            return false;
        }
        if (url.origin !== window.location.origin) return false;
        // Simple ancre sur la même page.
        if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return false;

        return true;
    }

    var skipUnloadUntil = 0;

    // Écoute en phase de bouillonnement : les gestionnaires de la page passent d'abord.
    document.addEventListener('click', function (event) {
        var link = event.target.closest ? event.target.closest('a[href]') : null;
        if (!link) return;
        // Laisser les autres scripts décider (preventDefault) avant de démarrer.
        window.setTimeout(function () {
            if (linkStartsNavigation(event, link)) {
                start();
            } else {
                // Téléchargement, mailto, nouvel onglet… : ne pas réagir au beforeunload qui suit.
                skipUnloadUntil = Date.now() + 1500;
            }
        }, 0);
    });

    // Navigations lancées par script (window.location = …).
    window.addEventListener('beforeunload', function () {
        if (Date.now() < skipUnloadUntil) return;
        start();
    });

    // ---------- Formulaires : spinner dans le bouton d'envoi ----------
    function startSubmit(form, submitter) {
        if (disabled() || !form) return;
        if (form.hasAttribute('data-no-loader') || form.getAttribute('target') === '_blank') return;

        var button = submitter && submitter.tagName === 'BUTTON'
            ? submitter
            : form.querySelector('button[type="submit"], button:not([type])');

        // Bouton déjà pris en charge par la page (spinner ou désactivé) : on n'y touche pas.
        var handledByPage = button && (button.disabled || button.querySelector('[class*="spinner"]:not(.btn-spinner)'));

        if (button && !handledByPage && !loadingButton && !button.hasAttribute('data-no-loader')) {
            loadingButton = { button: button, html: button.innerHTML, disabled: button.disabled };
            var label = button.getAttribute('data-loading-text') || 'Submitting…';
            button.innerHTML = '<span class="can-btn-spinner" aria-hidden="true"></span>' + label;
            button.classList.add('can-btn-loading');
            button.setAttribute('aria-busy', 'true');
            button.disabled = true; // les données du formulaire sont déjà constituées
        }

        start();
    }

    // Envoi natif : on attend la fin des autres gestionnaires ; si l'envoi est bloqué, rien.
    document.addEventListener('submit', function (event) {
        var submitter = event.submitter || null;
        window.setTimeout(function () {
            if (!event.defaultPrevented) startSubmit(event.target, submitter);
        }, 0);
    });

    // Yii ActiveForm renvoie le formulaire par script après sa validation :
    // « beforeSubmit » est le dernier signal avant cet envoi.
    if (window.jQuery) {
        window.jQuery(document).on('beforeSubmit', 'form', function (event) {
            var form = this;
            window.setTimeout(function () {
                if (!event.isDefaultPrevented()) startSubmit(form, null);
            }, 0);
        });
    }

    // ---------- Fin de chargement / retour arrière ----------
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) stop();
    });
    window.addEventListener('load', stop);
    if (document.readyState === 'complete') stop();

    // ---------- Pjax ----------
    if (window.jQuery) {
        window.jQuery(document).on('pjax:send', start);
        window.jQuery(document).on('pjax:complete pjax:error', stop);
    }

    window.CANLoader = { show: start, hide: stop };
})();
JS;

$this->registerJs($jsSpinner, \yii\web\View::POS_END);
?>

<div id="can-nav-progress" aria-hidden="true"></div>

<div id="av-global-loader" class="hidden" role="status" aria-live="polite" aria-busy="false">
    <div class="av-loader-box">
        <img src="<?= Url::to('@web/logo/can-logo-main.png') ?>" alt="Core Aviation Network" class="av-loader-logo" width="80" height="54">
        <div class="av-loader-status">
            <span class="av-loader-ring" aria-hidden="true"></span>
            <span data-loader-label>Loading…</span>
        </div>
    </div>
</div>
