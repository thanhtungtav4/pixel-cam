/**
 * Single product — review form toggle + interactive star picker.
 *
 * Behavior:
 *   - .pxc-review-toggle flips the [hidden] state of #pxcReviewForm
 *     and updates aria-expanded on the trigger.
 *   - When the panel opens, focus the form title (NOT a star — focusing a
 *     star would visually mark it as selected via :focus).
 *   - The star picker is fully keyboard-accessible: arrow keys aren't
 *     implemented (would require radiogroup semantics) but Enter/Space
 *     click a star, and Tab moves through the group naturally.
 *   - Clicking the currently-selected star clears the rating.
 *
 * Vanilla JS, no jQuery. Enqueued on single-product pages by the
 * WooProductHook review asset enqueue (see TODO in that hook).
 */
(function () {
    'use strict';

    var LABELS = ['', 'Rất tệ', 'Tệ', 'Bình thường', 'Tốt', 'Rất tốt'];
    var HINT_EMPTY = 'Chạm để chấm';

    function getHidden(panel) {
        return panel.querySelector('#pxc_rating');
    }

    function paintStars(wrap, v) {
        if (!wrap) return;
        var btns = wrap.querySelectorAll('button[data-val]');
        btns.forEach(function (b, i) {
            var on = (i + 1) <= v;
            var isCurrent = on && (i + 1) === v;
            b.classList.toggle('on', on);
            b.setAttribute('aria-checked', isCurrent ? 'true' : 'false');
        });
    }

    function init() {
        var toggle = document.querySelector('.pxc-review-toggle');
        var panel = document.getElementById('pxcReviewForm');
        if (!toggle || !panel) return;

        var cancelBtn = panel.querySelector('.pxc-rf-cancel');
        var wrap = panel.querySelector('[data-stars]');
        var hint = panel.querySelector('.pxc-rf-rate-hint');

        function close() {
            panel.setAttribute('hidden', '');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.focus();
            var form = panel.querySelector('form.pxc-rf');
            if (form) form.reset();
            var hidden = getHidden(panel);
            if (hidden) hidden.value = '';
            if (hint) hint.textContent = HINT_EMPTY;
            paintStars(wrap, 0);
        }

        toggle.addEventListener('click', function () {
            var open = panel.hasAttribute('hidden');
            if (open) {
                panel.removeAttribute('hidden');
                toggle.setAttribute('aria-expanded', 'true');
                setTimeout(function () {
                    var title = panel.querySelector('.pxc-rf-title');
                    if (title) {
                        title.setAttribute('tabindex', '-1');
                        title.focus();
                    }
                }, 60);
            } else {
                close();
            }
        });
        if (cancelBtn) cancelBtn.addEventListener('click', close);

        if (wrap) {
            var btns = wrap.querySelectorAll('button[data-val]');
            btns.forEach(function (b, i) {
                var v = i + 1;
                b.addEventListener('mouseenter', function () {
                    paintStars(wrap, v);
                    if (hint) hint.textContent = v + ' ★ — ' + LABELS[v];
                });
                b.addEventListener('focus', function () {
                    paintStars(wrap, v);
                    if (hint) hint.textContent = v + ' ★ — ' + LABELS[v];
                });
                b.addEventListener('mouseleave', function () {
                    var cur = parseInt((getHidden(panel) && getHidden(panel).value) || 0, 10);
                    paintStars(wrap, cur);
                    if (hint) hint.textContent = cur > 0 ? (cur + ' ★ — ' + LABELS[cur]) : HINT_EMPTY;
                });
                b.addEventListener('click', function () {
                    var hidden = getHidden(panel);
                    if (!hidden) return;
                    // Toggle: clicking the same star clears the rating.
                    var cur = parseInt(hidden.value || 0, 10);
                    var next = (cur === v) ? 0 : v;
                    hidden.value = next;
                    paintStars(wrap, next);
                    if (hint) hint.textContent = next > 0 ? (next + ' ★ — ' + LABELS[next]) : HINT_EMPTY;
                });
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
