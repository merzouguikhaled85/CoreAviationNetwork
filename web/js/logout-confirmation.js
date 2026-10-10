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
                    confirmButtonText: '<i class="fas fa-sign-out-alt" aria-hidden="true"></i> Yes, sign out',
                    cancelButtonText: '<i class="fas fa-times" aria-hidden="true"></i> Cancel',
                    confirmButtonColor: '#e63946',
                    cancelButtonColor: '#6c757d',
                    focusCancel: true,
                    reverseButtons: true,
                    width: 380,
                    customClass: {
                        popup: 'can-status-swal can-logout-swal',
                        icon: 'can-status-swal-icon',
                        title: 'can-status-swal-title',
                        htmlContainer: 'can-status-swal-message',
                        actions: 'can-status-swal-actions',
                        confirmButton: 'can-status-swal-confirm',
                        cancelButton: 'can-status-swal-close'
                    }
                });
                confirmed = result.isConfirmed;
            } else {
                // Conserver une confirmation si le CDN SweetAlert est indisponible.
                confirmed = window.confirm('Sign out from all devices?');
            }
            if (confirmed === true) {
                // Soumettre le POST avec son jeton CSRF sans rouvrir la confirmation.
                if (window.canSpinner) window.canSpinner.show();
                HTMLFormElement.prototype.submit.call(form);
            }
        } finally {
            pending.delete(form);
        }
    });
}());
