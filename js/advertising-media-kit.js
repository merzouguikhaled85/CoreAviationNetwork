(function () {
    'use strict';

    var root = document.querySelector('.ad-media-page');
    if (!root) return;

    var periodButtons = root.querySelectorAll('.ad-period-switch button');
    var periodSelect = root.querySelector('#ad-period');
    var planSelect = root.querySelector('#ad-plan');
    var placementSelect = root.querySelector('#ad-placement');
    var form = root.querySelector('#ad-campaign-form');
    var status = root.querySelector('.ad-copy-status');

    function money(value) {
        return new Intl.NumberFormat('en-IE', { maximumFractionDigits: 0 }).format(Math.round(value));
    }

    function getPeriod() {
        var option = periodSelect.options[periodSelect.selectedIndex];
        return {
            months: Number(option.value),
            discount: Number(option.getAttribute('data-discount') || 0),
            label: option.textContent.split(' — ')[0]
        };
    }

    function updatePricing() {
        var period = getPeriod();

        root.querySelectorAll('[data-plan-card]').forEach(function (card) {
            var monthly = Number(card.getAttribute('data-base-price')) * (1 - period.discount / 100);
            card.querySelector('[data-monthly-price]').textContent = '€' + money(monthly);
            card.querySelector('[data-total-price]').textContent = '€' + money(monthly * period.months) + ' excl. tax over ' + period.label;
        });

        var selectedPlan = planSelect.options[planSelect.selectedIndex];
        var selectedMonthly = Number(selectedPlan.getAttribute('data-price')) * (1 - period.discount / 100);
        root.querySelector('#ad-estimate-total').innerHTML = '€' + money(selectedMonthly * period.months) + ' <small>excl. tax</small>';
        root.querySelector('#ad-estimate-monthly').textContent = '€' + money(selectedMonthly) + ' per month for ' + period.label;

        periodButtons.forEach(function (button) {
            var active = Number(button.getAttribute('data-months')) === period.months;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    periodButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            periodSelect.value = button.getAttribute('data-months');
            updatePricing();
        });
    });

    periodSelect.addEventListener('change', updatePricing);
    planSelect.addEventListener('change', updatePricing);

    root.querySelectorAll('[data-choose-plan]').forEach(function (button) {
        button.addEventListener('click', function () {
            planSelect.value = button.getAttribute('data-choose-plan');
            updatePricing();
            root.querySelector('#campaign-planner').scrollIntoView({ behavior: 'smooth' });
        });
    });

    root.querySelectorAll('.ad-faq-item button').forEach(function (button) {
        button.addEventListener('click', function () {
            var item = button.closest('.ad-faq-item');
            var shouldOpen = !item.classList.contains('is-open');

            root.querySelectorAll('.ad-faq-item').forEach(function (other) {
                other.classList.remove('is-open');
                other.querySelector('button').setAttribute('aria-expanded', 'false');
            });

            if (shouldOpen) {
                item.classList.add('is-open');
                button.setAttribute('aria-expanded', 'true');
            }
        });
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        var period = getPeriod();
        var plan = planSelect.options[planSelect.selectedIndex];
        var placement = placementSelect.options[placementSelect.selectedIndex];
        var monthly = Number(plan.getAttribute('data-price')) * (1 - period.discount / 100);
        var brief = [
            'Core Aviation Network — Advertising request',
            'Placement: ' + placement.textContent,
            'Package: ' + plan.textContent,
            'Period: ' + period.label,
            'Estimated budget: €' + money(monthly * period.months) + ' excl. tax'
        ].join('\n');

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(brief).then(function () {
                status.textContent = 'Campaign brief copied to the clipboard.';
            }).catch(function () {
                status.textContent = 'Automatic copy is unavailable. Please use the contact link.';
            });
        } else {
            status.textContent = 'Automatic copy is unavailable. Please use the contact link.';
        }
    });

    updatePricing();
})();
