// web/js/can.js

document.addEventListener('DOMContentLoaded', function () {

    /* =====================================
       1. Theme Management (light / dark)
    ===================================== */

    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    const savedTheme = localStorage.getItem('theme') || 'light';

    function applyTheme(theme) {
        body.setAttribute('data-theme', theme);
        if (themeToggle) {
            themeToggle.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
        }
        localStorage.setItem('theme', theme);
    }

    applyTheme(savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const current = body.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            applyTheme(next);
        });
    }


    /* =====================================
       2. Mobile Menu (burger)
    ===================================== */

    const mobileBtn = document.getElementById('mobileMenuBtn');
    const mobileNav = document.getElementById('mobileNav');

    if (mobileBtn && mobileNav) {
        mobileBtn.addEventListener('click', () => {
            const isOpen = mobileBtn.classList.toggle('open');
            mobileNav.classList.toggle('open');
            mobileBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        // Fermer le menu quand on clique sur un lien
        mobileNav.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                mobileBtn.classList.remove('open');
                mobileNav.classList.remove('open');
                mobileBtn.setAttribute('aria-expanded', 'false');
            });
        });
    }


    /* =====================================
       3. Scroll Effects (header + scrollTop)
    ===================================== */

    const header = document.querySelector('.header');
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    const footer = document.querySelector('.footer');

    function handleScroll() {
        const y = window.scrollY;

        // Effet glass sur la navbar
        if (header) {
            if (y > 50) header.classList.add('scrolled');
            else header.classList.remove('scrolled');
        }

        // Bouton remonter en haut
        if (scrollTopBtn) {
            if (y > 300) scrollTopBtn.classList.add('visible');
            else scrollTopBtn.classList.remove('visible');
        }
    }

    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll(); // init

    if (scrollTopBtn) {
        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }


    /* =====================================
       4. Footer Reveal (apparition soft)
    ===================================== */

    if (footer) {
        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    footer.classList.add('visible');
                    obs.unobserve(footer);
                }
            });
        }, { threshold: 0.1 });

        observer.observe(footer);
    }


    /* =====================================
       5. USER DROPDOWN (desktop)
    ===================================== */

    const userMenu = document.querySelector('.user-menu');
    const userToggle = document.querySelector('.user-menu-toggle');

    if (userMenu && userToggle) {

        // Ouvrir / fermer
        userToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenu.classList.toggle('open');
        });

        // Fermer si on clique ailleurs
        document.addEventListener('click', () => {
            userMenu.classList.remove('open');
        });

        // Ne pas fermer si on clique dans le dropdown
        const dropdown = userMenu.querySelector('.user-menu-dropdown');
        if (dropdown) {
            dropdown.addEventListener('click', (e) => e.stopPropagation());
        }
    }

    // spinner
    initCanSpinner();

});

//  spinner
function initCanSpinner() {
    const spinner = document.getElementById('can-global-spinner');
    if (!spinner) return;

    let lock = 0;

    const applyHidden = () => {
        spinner.classList.remove('is-active');
        spinner.setAttribute('aria-hidden', 'true');
        document.documentElement.removeAttribute('aria-busy');
    };

    const applyVisible = () => {
        spinner.classList.add('is-active');
        spinner.setAttribute('aria-hidden', 'false');
        document.documentElement.setAttribute('aria-busy', 'true');
    };

    const show = () => {
        lock++;
        if (lock === 1) applyVisible();
    };

    const hide = () => {
        lock = Math.max(0, lock - 1);
        if (lock === 0) applyHidden();
    };

    window.canSpinner = { show, hide };

    // Toujours caché au départ
    applyHidden();
    lock = 0;

    // SUBMIT global (capture)
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.hasAttribute('data-no-spinner')) return;

        if (form.dataset.submitted === 'true') {
            e.preventDefault();
            return;
        }
        form.dataset.submitted = 'true';
        show();
    }, true);

    // Yii ActiveForm: si validation client bloque le submit -> on annule spinner
    if (window.jQuery) {
        $(document).on('afterValidate', 'form', function (event, messages, errorAttributes) {
            const form = event.target;
            if (!form || form.hasAttribute('data-no-spinner')) return;

            const hasErrors = Array.isArray(errorAttributes) && errorAttributes.length > 0;
            if (hasErrors) {
                delete form.dataset.submitted;
                hide();
            }
        });
    }

    // AJAX global
    if (window.jQuery) {
        $(document).on('ajaxStart', () => show());
        $(document).on('ajaxStop ajaxError', () => hide());
    }

    // PJAX
    if (window.jQuery && $.pjax) {
        $(document).on('pjax:start', () => show());
        $(document).on('pjax:end pjax:error', () => hide());
    }

    // navigation classique (optionnel)
    window.addEventListener('beforeunload', () => {
        if (document.visibilityState === 'hidden') return;
        show();
    });

    // BFCache (retour arrière)
    window.addEventListener('pageshow', (event) => {
        applyHidden();
        lock = 0;

        if (event.persisted) {
            document.querySelectorAll('form[data-submitted]')
                .forEach(f => delete f.dataset.submitted);
        }
    });

    // Sécurité: cacher au load
    window.addEventListener('load', () => {
        applyHidden();
        lock = 0;
    });
}
