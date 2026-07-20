<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * My Account / login-register wiring.
 *
 *   - Enqueue the auth-toggle JS on the my-account login page (no inline JS).
 *   - Vietnamese labels for the My Account navigation.
 *
 * Extracted from WooHook to keep that class focused on shop/product/cart.
 */
final class WooAccountHook
{
    public static function register(): void
    {
        $self = new self();

        add_action('wp_enqueue_scripts', [$self, 'enqueue_auth_toggle']);
        add_filter('woocommerce_account_menu_items', [$self, 'account_menu_items']);
    }

    public function enqueue_auth_toggle(): void
    {
        // Only load on the customer-login screen (login + register). Don't pull
        // it in for the regular my-account dashboard.
        if (! function_exists('is_account_page') || ! is_account_page()) {
            return;
        }
        if (is_user_logged_in()) {
            return;
        }

        $relative = 'assets/scripts/woocommerce/auth-toggle.js';
        $path     = underscores_child_asset_path($relative);
        if (! file_exists($path)) {
            return;
        }

        wp_enqueue_script(
            'pxc-auth-toggle',
            underscores_child_asset_uri($relative),
            [],
            underscores_child_asset_version($relative),
            true
        );
    }

    /**
     * Vietnamese labels for the My Account navigation.
     *
     * @param array<string,string> $items
     * @return array<string,string>
     */
    public function account_menu_items(array $items): array
    {
        $vi = [
            'dashboard'       => __('Tổng quan', 'underscores'),
            'orders'          => __('Đơn hàng', 'underscores'),
            'downloads'       => __('Tải xuống', 'underscores'),
            'edit-address'    => __('Địa chỉ', 'underscores'),
            'edit-account'    => __('Tài khoản', 'underscores'),
            'customer-logout' => __('Đăng xuất', 'underscores'),
        ];
        foreach ($items as $key => $label) {
            if (isset($vi[$key])) {
                $items[$key] = $vi[$key];
            }
        }
        return $items;
    }
}
