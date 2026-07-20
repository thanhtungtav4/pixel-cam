<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * Per-menu-item icon field (SVG/PNG) using WordPress-native menu fields.
 *
 *   - Adds an "Icon" media picker to each item in Appearance → Menus.
 *   - Stores the chosen attachment ID in menu-item meta `_pxc_menu_icon`.
 *   - Renders the icon inline before the link label on the front end.
 *
 * No ACF needed. SVG upload is enabled by MediaHook (sanitized on upload).
 */
final class MenuHook
{
    private const META_KEY = '_pxc_menu_icon';

    public static function register(): void
    {
        $self = new self();

        add_action('wp_nav_menu_item_custom_fields', [$self, 'render_field'], 10, 2);
        add_action('wp_update_nav_menu_item', [$self, 'save_field'], 10, 2);
        add_filter('walker_nav_menu_start_el', [$self, 'prepend_icon'], 10, 4);
        add_filter('wp_nav_menu_objects', [$self, 'prime_icon_caches'], 10, 2);
        add_action('admin_enqueue_scripts', [$self, 'enqueue_media_picker']);
    }

    /**
     * Batch-prime attachment caches for all menu-item icons before the walk.
     *
     * Without this, icon_html() runs wp_get_attachment_url() per item → one
     * uncached attachment (post + _wp_attached_file meta) lookup each. A 30-item
     * mega menu = ~30–60 uncached queries on EVERY page. Priming once collapses
     * that to a single batched query.
     *
     * @param array<int,\WP_Post> $items
     * @param object              $args
     * @return array<int,\WP_Post>
     */
    public function prime_icon_caches($items, $args)
    {
        if (! is_array($items) || $items === []) {
            return $items;
        }
        $icon_ids = [];
        foreach ($items as $item) {
            $icon_id = (int) get_post_meta((int) $item->ID, self::META_KEY, true);
            if ($icon_id) {
                $icon_ids[] = $icon_id;
            }
        }
        if ($icon_ids !== []) {
            // Prime post objects + their meta (incl. _wp_attached_file) in one go.
            _prime_post_caches(array_unique($icon_ids), false, true);
        }
        return $items;
    }

    /**
     * Icon picker row in the menu-item editor.
     */
    public function render_field($item_id, $item): void
    {
        $icon_id  = (int) get_post_meta($item_id, self::META_KEY, true);
        $icon_url = $icon_id ? wp_get_attachment_url($icon_id) : '';
        ?>
        <p class="field-pxc-icon description description-wide">
            <label><?php esc_html_e('Icon (SVG/PNG)', 'underscores'); ?></label><br>
            <span class="pxc-icon-preview">
                <?php if ($icon_url) : ?>
                    <img src="<?php echo esc_url($icon_url); ?>" alt="">
                <?php endif; ?>
            </span>
            <input type="hidden" class="pxc-icon-id" name="pxc_menu_icon[<?php echo (int) $item_id; ?>]" value="<?php echo esc_attr((string) $icon_id); ?>">
            <button type="button" class="button pxc-icon-pick"><?php esc_html_e('Chọn icon', 'underscores'); ?></button>
            <button type="button" class="button-link pxc-icon-remove<?php echo $icon_id ? '' : ' is-hidden'; ?>"><?php esc_html_e('Xóa', 'underscores'); ?></button>
        </p>
        <?php
    }

    /**
     * Save the icon ID posted alongside the menu item.
     */
    public function save_field($menu_id, $menu_item_db_id): void
    {
        // Defense-in-depth: core already gates the nav-menu screen behind this
        // cap + the menu nonce, but never trust the calling context implicitly.
        if (! current_user_can('edit_theme_options')) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification -- core verifies the menu nonce.
        $posted = $_POST['pxc_menu_icon'][$menu_item_db_id] ?? null;
        if ($posted === null || $posted === '') {
            delete_post_meta($menu_item_db_id, self::META_KEY);
            return;
        }
        update_post_meta($menu_item_db_id, self::META_KEY, (int) $posted);
    }

    /**
     * Prepend the icon <img> to the item output on the front end.
     *
     * @param string $item_output
     * @return string
     */
    public function prepend_icon($item_output, $item, $depth, $args): string
    {
        $img = self::icon_html((int) $item->ID);
        if ($img === '') {
            return $item_output;
        }
        // Insert the icon just inside the opening <a ...> tag.
        return preg_replace('/(<a\b[^>]*>)/', '$1' . $img, $item_output, 1) ?? $item_output;
    }

    /**
     * Icon <img> for a menu item, or '' if none. Shared so custom walkers
     * (e.g. MegaWalker) render the same icon without duplicating the logic.
     */
    public static function icon_html(int $item_id): string
    {
        $icon_id = (int) get_post_meta($item_id, self::META_KEY, true);
        if (! $icon_id) {
            return '';
        }
        $url = wp_get_attachment_url($icon_id);
        if (! $url) {
            return '';
        }
        return '<img class="menu-icon" src="' . esc_url($url) . '" alt="" width="18" height="18" loading="lazy" />';
    }

    /**
     * Load wp.media + a tiny picker script on the menu editor screen only.
     * The picker is a real enqueued file (not wp_add_inline_script against
     * jquery-core — that's a dep handle, not an enqueued handle, and silently
     * no-ops in some WP load orders).
     */
    public function enqueue_media_picker(string $hook): void
    {
        if ($hook !== 'nav-menus.php') {
            return;
        }
        wp_enqueue_media();

        $relative = 'assets/scripts/admin/menu-icon-picker.js';
        $path     = underscores_child_asset_path($relative);
        if (! file_exists($path)) {
            return;
        }

        wp_enqueue_script(
            'pxc-menu-icon-picker',
            underscores_child_asset_uri($relative),
            [],
            underscores_child_asset_version($relative),
            true
        );
    }
}
