/*
 * PRIORITÉ OPÉRATIONNELLE — COMPORTEMENT VISUEL PARTAGÉ
 * Ce module synchronise les cartes et le délai. Il ne sauvegarde rien directement
 * et ne modifie aucun statut : le modèle Requests reste l'autorité métier.
 */
(function () {
    'use strict';

    function initialiseOperationalPriority(root) {
        var priorityInputs = root.querySelectorAll('.operational-priority-input');
        var responsePanel = root.querySelector('[data-response-panel]');
        var responseInput = root.querySelector('#response-required-minutes');
        var responseRule = root.querySelector('[data-response-rule]');
        var deadlinePreview = root.querySelector('[data-deadline-preview]');
        var responseLabel = root.querySelector('[data-response-label]');

        if (!priorityInputs.length || !responsePanel || !responseInput) {
            return;
        }

        function selectedPriority() {
            var selected = root.querySelector('.operational-priority-input:checked');
            return selected ? selected.value : 'routine';
        }

        function validResponseWindow(priority) {
            var minutes = Number(responseInput.value);
            return priority !== 'aog' || (Number.isFinite(minutes) && minutes >= 15 && minutes <= 1440);
        }

        function refreshDeadlinePreview(priority) {
            var minutes = Number(responseInput.value);
            if (priority === 'routine' || !Number.isFinite(minutes) || minutes < 15 || minutes > 1440) {
                deadlinePreview.textContent = '';
                return;
            }

            var estimatedDeadline = new Date(Date.now() + (minutes * 60000));
            deadlinePreview.innerHTML = '<i class="bi bi-clock-history" aria-hidden="true"></i> Estimated response deadline: '
                + estimatedDeadline.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short', timeZone: 'UTC' })
                + ' UTC';
        }

        function notifyValidity(priority) {
            root.dispatchEvent(new CustomEvent('can:priority-validity-change', {
                bubbles: true,
                detail: { valid: validResponseWindow(priority), priority: priority }
            }));
        }

        function refreshPriorityState() {
            var priority = selectedPriority();
            var showResponse = priority === 'aog' || priority === 'urgent';

            root.querySelectorAll('.operational-priority-card').forEach(function (card) {
                var input = card.querySelector('.operational-priority-input');
                card.classList.toggle('is-selected', Boolean(input && input.checked));
            });

            responsePanel.hidden = !showResponse;
            responseInput.required = priority === 'aog';
            responseInput.setAttribute('aria-required', priority === 'aog' ? 'true' : 'false');

            if (responseLabel) {
                responseLabel.classList.toggle('is-required', priority === 'aog');
            }

            if (responseRule) {
                responseRule.textContent = priority === 'aog'
                    ? 'Required for AOG · from 15 minutes to 24 hours'
                    : 'Optional for Urgent · from 15 minutes to 24 hours';
            }

            if (priority === 'routine') {
                responseInput.value = '';
            }

            refreshDeadlinePreview(priority);
            notifyValidity(priority);
        }

        priorityInputs.forEach(function (input) {
            input.addEventListener('change', refreshPriorityState);
        });

        root.querySelectorAll('[data-response-minutes]').forEach(function (button) {
            button.addEventListener('click', function () {
                responseInput.value = button.getAttribute('data-response-minutes') || '';
                responseInput.dispatchEvent(new Event('input', { bubbles: true }));
                responseInput.focus();
            });
        });

        responseInput.addEventListener('input', function () {
            var priority = selectedPriority();
            refreshDeadlinePreview(priority);
            notifyValidity(priority);
        });

        refreshPriorityState();
    }

    /*
     * TEMPS RESTANT : l'échéance absolue reste fournie par le serveur en UTC.
     * Le navigateur ne fait qu'en produire un libellé relatif, rafraîchi toutes
     * les 30 secondes. Cela reste informatif et ne change aucune donnée.
     */
    function formatRemainingTime(milliseconds) {
        var absoluteMinutes = Math.max(1, Math.ceil(Math.abs(milliseconds) / 60000));
        var days = Math.floor(absoluteMinutes / 1440);
        var hours = Math.floor((absoluteMinutes % 1440) / 60);
        var minutes = absoluteMinutes % 60;
        var parts = [];

        if (days > 0) {
            parts.push(days + ' d');
        }
        if (hours > 0 && parts.length < 2) {
            parts.push(hours + ' h');
        }
        if (days === 0 && minutes > 0 && parts.length < 2) {
            parts.push(minutes + ' min');
        }

        return parts.join(' ');
    }

    function refreshResponseCountdowns() {
        document.querySelectorAll('[data-response-deadline-utc]').forEach(function (element) {
            var label = element.querySelector('[data-countdown-label]');
            var deadline = Date.parse(element.getAttribute('data-response-deadline-utc') || '');

            if (!label || !Number.isFinite(deadline)) {
                return;
            }

            var remaining = deadline - Date.now();
            var overdue = remaining <= 0;
            element.classList.toggle('is-overdue', overdue);
            label.textContent = formatRemainingTime(remaining)
                + (overdue ? ' overdue' : ' remaining');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-operational-priority]').forEach(initialiseOperationalPriority);
        refreshResponseCountdowns();
        window.setInterval(refreshResponseCountdowns, 30000);
    });
}());
