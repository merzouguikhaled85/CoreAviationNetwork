/* Inscription volontaire de chaque navigateur ; aucun jeton dans le stockage local. */
(function () {
    'use strict';
    const root = document.getElementById('aog-push-settings');
    if (!root) return;
    const settings = JSON.parse(root.dataset.settings);
    const button = document.getElementById('aog-push-toggle');
    const status = document.getElementById('aog-push-status');
    const storageKey = 'can.aogPushAccount';
    let messaging;
    let enabled = false;
    let busy = false;

    function savedAccount() {
        try { return localStorage.getItem(storageKey); } catch (_) { return null; }
    }
    function saveAccount(value) {
        try {
            if (value) localStorage.setItem(storageKey, value);
            else localStorage.removeItem(storageKey);
        } catch (_) { /* Le navigateur peut interdire le stockage local. */ }
    }
    function update(message) {
        status.textContent = message;
        button.disabled = busy;
        button.textContent = enabled ? 'Disable AOG notifications' : 'Enable AOG notifications';
    }
    async function post(url, values) {
        const csrf = document.querySelector('meta[name="csrf-token"]');
        const body = new URLSearchParams(values);
        body.set('account', settings.account);
        const response = await fetch(url, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'X-CSRF-Token': csrf ? csrf.content : '',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: body.toString()
        });
        if (!response.ok) throw new Error('Subscription failed');
        const result = await response.json();
        if (!result.success) throw new Error('Subscription failed');
    }
    async function subscribe() {
        const registration = await navigator.serviceWorker.register(settings.workerUrl, { scope: settings.scope });
        // Attendre l'activation réelle avant de demander un abonnement Push.
        if (!registration.active) {
            await new Promise(function (resolve, reject) {
                const worker = registration.installing || registration.waiting;
                if (!worker) { reject(new Error('Worker unavailable')); return; }
                const timer = setTimeout(function () { reject(new Error('Worker timeout')); }, 20000);
                worker.addEventListener('statechange', function () {
                    if (worker.state === 'activated') { clearTimeout(timer); resolve(); }
                    if (worker.state === 'redundant') { clearTimeout(timer); reject(new Error('Worker failed')); }
                });
            });
        }
        const token = await messaging.getToken({ vapidKey: settings.vapidKey,
            serviceWorkerRegistration: registration });
        if (!token) throw new Error('Token unavailable');
        await post(settings.registerUrl, { token: token });
        enabled = true;
        saveAccount(settings.account);
    }
    button.addEventListener('click', async function () {
        if (busy || !messaging) return;
        busy = true;
        update('Updating notification preferences…');
        try {
            if (enabled) {
                // Révoquer côté serveur avant de supprimer le jeton du navigateur.
                await post(settings.unregisterUrl, {});
                enabled = false;
                saveAccount(null);
                await messaging.deleteToken();
            } else {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    busy = false;
                    update('Notifications are blocked. Allow them in your browser settings.');
                    return;
                }
                await subscribe();
            }
            busy = false;
            update(enabled ? 'AOG notifications enabled on this device.' : 'AOG notifications disabled on this device.');
        } catch (_) {
            busy = false;
            update('Could not update notifications. Please try again.');
        }
    });
    async function initialize() {
        try {
            if (!window.isSecureContext || !('serviceWorker' in navigator) || !('Notification' in window)
                || !window.firebase || !firebase.messaging || !(await firebase.messaging.isSupported())) {
                throw new Error('Unsupported browser');
            }
            const app = firebase.initializeApp(settings.config, 'can-aog');
            messaging = app.messaging();
            messaging.onMessage(function () {
                // Affichage sûr au premier plan, sans HTML provenant d'un message externe.
                const alert = document.createElement('div');
                alert.className = 'alert alert-info shadow position-fixed';
                alert.style.cssText = 'right:16px;bottom:16px;z-index:9999;max-width:360px';
                alert.setAttribute('role', 'status');
                const link = document.createElement('a');
                link.href = settings.requestsUrl;
                link.textContent = 'New AOG Request — View requests and response deadlines';
                const close = document.createElement('button');
                close.type = 'button';
                close.className = 'btn btn-sm ms-2';
                close.textContent = 'Dismiss';
                close.addEventListener('click', function () { alert.remove(); });
                alert.append(link, close);
                document.body.appendChild(alert);
            });
            if (Notification.permission === 'granted' && savedAccount() === settings.account) {
                busy = true;
                await subscribe();
                busy = false;
                update('AOG notifications enabled on this device.');
            } else {
                update('Receive new AOG requests on this device.');
            }
        } catch (_) {
            busy = false;
            if (messaging) update('Could not enable notifications. Please try again.');
            else {
                button.disabled = true;
                const ios = /iPad|iPhone|iPod/.test(navigator.userAgent)
                    || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
                const standalone = navigator.standalone || window.matchMedia('(display-mode: standalone)').matches;
                status.textContent = ios && !standalone
                    ? 'Add this site to your Home Screen, open it there, then enable notifications.'
                    : 'Notifications are unavailable in this browser.';
            }
        }
    }
    initialize();
}());
