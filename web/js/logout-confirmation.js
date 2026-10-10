/* Confirmation partagée par les menus du profil, desktop et mobile. */
(function () {
    'use strict';
    const pending = new WeakSet();

    document.addEventListener('submit', async function (event) {
        const form = event.target;
        if (!form.matches('form[data-confirm-logout-all]')) return;
        event.preventDefault();
        if (pending.has(form)) return;
        pending.add(form);

        try {
            let confirmed;
            if (window.Swal && typeof window.Swal.fire === 'function') {
                const result = await window.Swal.fire({
                    icon: 'warning',
                    title: 'Sign out from all devices?',
                    text: 'You will be signed out on all devices, including this one.',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, sign out',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#e63946',
                    cancelButtonColor: '#6c757d',
                    focusCancel: true,
                    reverseButtons: true,
                    width: 420
                });
                confirmed = result.isConfirmed;
            } else {
                // Conserver une confirmation si le CDN SweetAlert est indisponible.
                confirmed = window.confirm('Sign out from all devices?');
            }
            if (confirmed) {
                // Soumettre le POST avec son jeton CSRF sans rouvrir la confirmation.
                HTMLFormElement.prototype.submit.call(form);
            }
        } finally {
            pending.delete(form);
        }
    });
}());
