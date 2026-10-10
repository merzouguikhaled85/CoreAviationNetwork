/* Fermeture des messages en ligne, y compris ceux inseres apres chargement. */
(function () {
    'use strict';

    function enhance(alert) {
        if (alert.querySelector('[data-can-alert-close], .btn-close, [data-bs-dismiss="alert"], [data-dismiss="alert"]')) return;
        alert.classList.add('alert-dismissible', 'can-dismissible-alert');
        if (!alert.hasAttribute('role')) alert.setAttribute('role', 'alert');

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'can-alert-close';
        close.setAttribute('data-can-alert-close', '');
        close.setAttribute('aria-label', 'Close');
        close.title = 'Close';
        const icon = document.createElement('span');
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = '×';
        close.appendChild(icon);
        close.addEventListener('click', function () {
            if (window.bootstrap && window.bootstrap.Alert) {
                window.bootstrap.Alert.getOrCreateInstance(alert).close();
            } else {
                alert.remove();
            }
        });
        alert.appendChild(close);
    }

    function scan(container) {
        if (container.nodeType === 1 && container.matches('.alert')) enhance(container);
        container.querySelectorAll('.alert').forEach(enhance);
    }

    function initialize() {
        scan(document.body);
        const observer = new MutationObserver(function (records) {
            records.forEach(function (record) {
                record.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) scan(node);
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
}());
