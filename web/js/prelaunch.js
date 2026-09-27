(function ($) {
    'use strict';

    function scrollToEarlyAccess(event) {
        var target = document.getElementById('early-access');
        if (!target) return;

        event.preventDefault();
        target.scrollIntoView({
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
            block: 'start'
        });
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', '#early-access');
        }
    }

    $(function () {
        $('[data-early-access-link]').on('click', scrollToEarlyAccess);

        var $form = $('[data-early-access-form]');
        if (!$form.length) return;

        $form.on('beforeSubmit', function () {
            var $button = $form.find('.early-access-submit');
            if ($button.prop('disabled')) return false;

            $button.prop('disabled', true).attr('aria-busy', 'true');
            $button.find('span').text($button.data('joining-label') || 'Joining...');
            return true;
        });

        if ($form.find('.has-error').length) {
            document.getElementById('early-access').scrollIntoView({ block: 'start' });
        }
    });
})(window.jQuery);
