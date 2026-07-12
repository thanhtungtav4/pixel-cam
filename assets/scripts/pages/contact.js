'use strict';

/**
 * Contact page — fallback form submission (demo UX only).
 * Wires up the markup produced by partials/components/contact-fallback-form.php
 * when no CF7 shortcode is set in ACF.
 */
(function () {
    var form = document.getElementById('contactForm');
    var ok = document.getElementById('formOk');
    if (!form) {
        return;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        var id = 'PX-' + Math.random().toString(36).slice(2, 6).toUpperCase() + '-' + new Date().getFullYear();
        var ticketEl = document.getElementById('ticketId');
        if (ticketEl) {
            ticketEl.textContent = id;
        }
        if (ok) {
            ok.classList.add('show');
            ok.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        form.reset();
    });
})();
