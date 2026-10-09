/* Inscription volontaire de chaque navigateur ; aucun jeton dans le stockage local. */
(function () {
    'use strict';
    const root = document.getElementById('aog-push-settings');
    if (!root) return;
    const settings = JSON.parse(root.dataset.settings);
    const button = document.getElementById('aog-push-toggle');
    const status = document.getElementById('aog-push-status');
    const stateBadge = document.getElementById('aog-push-state');
    const storageKey = 'can.aogPushAccount';
    let messaging;
    let enabled = false;
    let busy = false;

    function showNotification() {
        // Le texte reste contrôlé par l'application ; aucun HTML reçu n'est injecté.
        let stack = document.getElementById('can-aog-notifications');
        if (!stack) {
            stack = document.createElement('div');
            stack.id = 'can-aog-notifications';
            stack.className = 'can-aog-notifications';
            stack.setAttribute('role', 'region');
            stack.setAttribute('aria-label', 'AOG notifications');
            document.body.appendChild(stack);
        }
        const card = document.createElement('section');
        card.className = 'can-aog-toast';
        card.setAttribute('role', 'status');
        card.setAttribute('aria-live', 'polite');
        card.setAttribute('aria-atomic', 'true');
        const header = document.createElement('div');
        header.className = 'can-aog-toast__header';
        const logo = document.createElement('img');
        logo.className = 'can-aog-toast__logo';
        logo.src = settings.logoUrl;
        logo.alt = 'Core Aviation Network';
        logo.width = 90;
        logo.height = 58;
        const badge = document.createElement('span');
        badge.className = 'can-aog-toast__badge';
        badge.textContent = 'AOG ALERT';
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'can-aog-toast__close';
        close.setAttribute('aria-label', 'Dismiss AOG notification');
        close.title = 'Dismiss';
        close.textContent = '×';
        function dismiss() {
            card.remove();
            if (!stack.children.length) stack.remove();
        }
        close.addEventListener('click', dismiss);
        header.append(logo, badge, close);
        const title = document.createElement('h2');
        title.className = 'can-aog-toast__title';
        title.textContent = 'New AOG Request';
        const description = document.createElement('p');
        description.className = 'can-aog-toast__description';
        description.textContent = 'A new request needs your attention. View the details and response deadline.';
        const actions = document.createElement('div');
        actions.className = 'can-aog-toast__actions';
        const link = document.createElement('a');
        link.className = 'can-aog-toast__open';
        link.href = settings.requestsUrl;
        link.textContent = 'View requests';
        const arrow = document.createElement('span');
        arrow.setAttribute('aria-hidden', 'true');
        arrow.textContent = '→';
        link.appendChild(arrow);
        const dismissButton = document.createElement('button');
        dismissButton.type = 'button';
        dismissButton.className = 'can-aog-toast__dismiss';
        dismissButton.textContent = 'Dismiss';
        dismissButton.addEventListener('click', dismiss);
        actions.append(link, dismissButton);
        card.append(header, title, description, actions);
        stack.appendChild(card);
        // Limiter l'encombrement sans fermer automatiquement la dernière alerte AOG.
        while (stack.children.length > 3) stack.firstElementChild.remove();
    }

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
        button.textContent = busy ? 'Updating…' : (enabled ? 'Turn off alerts' : 'Enable alerts');
        button.setAttribute('aria-label', enabled ? 'Disable AOG notifications' : 'Enable AOG notifications');
        button.setAttribute('aria-pressed', String(enabled));
        root.dataset.state = busy ? 'loading' : (enabled ? 'enabled' : 'disabled');
        if (stateBadge) stateBadge.textContent = busy ? 'Updating' : (enabled ? 'Enabled' : 'Disabled');
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
            update(enabled ? 'Enabled on this device.' : 'Alerts are off on this device.');
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
            messaging.onMessage(showNotification);
            if (Notification.permission === 'granted' && savedAccount() === settings.account) {
                busy = true;
                await subscribe();
                busy = false;
                update('Enabled on this device.');
            } else {
                update('Receive new AOG requests on this device.');
            }
        } catch (_) {
            busy = false;
            if (messaging) update('Could not enable notifications. Please try again.');
            else {
                button.disabled = true;
                root.dataset.state = 'unavailable';
                if (stateBadge) stateBadge.textContent = 'Unavailable';
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
