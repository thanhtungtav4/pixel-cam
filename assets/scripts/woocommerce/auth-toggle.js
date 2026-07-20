/**
 * Auth toggle — my-account login/register tabs.
 *
 * Toggles .auth-login-box / .auth-register-box visibility on click and reads
 * the initial state from window.location.hash (#login / #register) so a deep
 * link straight to the register form works.
 *
 * Vanilla JS, no jQuery. Enqueued on the my-account page when the user is
 * logged-out (see Theme\Child\Hooks\WooAccountHook).
 */
(function () {
    'use strict';

    function init() {
        var loginBox = document.querySelector('.auth-login-box');
        var registerBox = document.querySelector('.auth-register-box');
        var toggleBtns = document.querySelectorAll('.auth-toggle-btn');

        if (!loginBox || !toggleBtns.length) {
            return;
        }

        toggleBtns.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var target = btn.getAttribute('data-target');
                if (target === 'register') {
                    loginBox.style.display = 'none';
                    if (registerBox) {
                        registerBox.style.display = 'block';
                        window.location.hash = 'register';
                    }
                } else {
                    if (registerBox) {
                        registerBox.style.display = 'none';
                    }
                    loginBox.style.display = 'block';
                    window.location.hash = 'login';
                }
            });
        });

        if (window.location.hash === '#register' && registerBox) {
            loginBox.style.display = 'none';
            registerBox.style.display = 'block';
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
