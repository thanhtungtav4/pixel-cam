/**
 * Menu item icon picker — admin UI for the custom "Icon" field on
 * Appearance → Menus.
 *
 * Vanilla JS (no jQuery) so we can enqueue as a real handle without depending
 * on jquery-core (which is a dep handle, not an enqueued one — wp_add_inline_script
 * against it has been observed to silently no-op depending on WP load order).
 * Reuses wp.media already on the screen.
 */
(function () {
    'use strict';

    function init() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.pxc-icon-pick');
            if (btn) {
                e.preventDefault();
                openPicker(btn);
                return;
            }
            var rm = e.target.closest('.pxc-icon-remove');
            if (rm) {
                e.preventDefault();
                clearIcon(rm);
            }
        });
    }

    function openPicker(btn) {
        var row = btn.closest('.field-pxc-icon');
        if (!row || typeof wp === 'undefined' || !wp.media) {
            return;
        }
        var frame = wp.media({
            title: 'Chọn icon',
            library: { type: ['image/svg+xml', 'image'] },
            multiple: false,
            button: { text: 'Dùng icon' }
        });
        frame.on('select', function () {
            var a = frame.state().get('selection').first().toJSON();
            row.querySelector('.pxc-icon-id').value = a.id;
            row.querySelector('.pxc-icon-preview').innerHTML = '<img src="' + a.url + '" alt="">';
            var rm = row.querySelector('.pxc-icon-remove');
            if (rm) {
                rm.classList.remove('is-hidden');
            }
        });
        frame.open();
    }

    function clearIcon(btn) {
        var row = btn.closest('.field-pxc-icon');
        if (!row) {
            return;
        }
        row.querySelector('.pxc-icon-id').value = '';
        row.querySelector('.pxc-icon-preview').innerHTML = '';
        btn.classList.add('is-hidden');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
