<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * WooCommerce cart-side wiring.
 *
 *   - Header cart badge fragment refresh after AJAX add-to-cart.
 *   - Drop Woo's always-on cart-fragments poll outside shop/cart/checkout.
 *   - Cross-sells moved to full-width below the cart.
 *   - Drop the default empty-cart notice in favour of the theme template.
 *   - Vietnamese strings Woo prints without a dedicated filter (mini-cart
 *     empty + view cart / checkout buttons).
 *
 * Extracted from WooHook to keep that class focused.
 */
final class WooCartHook
{
    public static function register(): void
    {
        if (! class_exists('WooCommerce')) {
            return;
        }

        $self = new self();

        add_filter('woocommerce_add_to_cart_fragments', [$self, 'cart_count_fragment']);
        add_action('wp_enqueue_scripts', [$self, 'trim_cart_fragments'], 99);

        // Cross-sells default to .cart-collaterals (the summary column) which
        // breaks the 2-column layout — move them below the cart, full width.
        // woocommerce_cross_sell_display self-hides when there are none, so
        // this only appears when products actually have cross-sells.
        remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');
        add_action('woocommerce_after_cart', [$self, 'cross_sells_full_width'], 10);

        remove_action('woocommerce_cart_is_empty', 'wc_empty_cart_message', 10);

        // Translate the checkout coupon message. The default message
        // contains a fragment that browsers auto-translate in non-VN
        // locales, which we don't want on a Vietnamese store.
        add_filter('woocommerce_checkout_coupon_message', [$self, 'checkout_coupon_message']);

        // Vietnamese mini-cart / cart strings that Woo prints inline (no
        // dedicated filter) — translate them directly so the empty state
        // reads in Vietnamese.
        add_filter('gettext_woocommerce', [$self, 'translate_cart_strings'], 10, 3);
    }

    /* ------------------------------------------------------------------ *
     * Cart fragments
     * ------------------------------------------------------------------ */

    /**
     * Update the header cart badge (#cartBadge) after an AJAX add-to-cart.
     *
     * @param array<string,string> $fragments
     * @return array<string,string>
     */
    public function cart_count_fragment(array $fragments): array
    {
        $count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
        $fragments['#cartBadge'] = '<span class="badge" id="cartBadge">' . (int) $count . '</span>';

        return $fragments;
    }

    public function trim_cart_fragments(): void
    {
        if ($this->needs_cart_fragments()) {
            return;
        }

        wp_dequeue_script('wc-cart-fragments');
    }

    private function needs_cart_fragments(): bool
    {
        $needs = is_woocommerce() || is_cart() || is_checkout();

        if (! $needs && ($post = get_post())) {
            $needs = has_shortcode($post->post_content, 'products')
                || has_shortcode($post->post_content, 'add_to_cart')
                || has_shortcode($post->post_content, 'product_page');
        }

        // Front-page renders products via a partial (not a shortcode), so opt
        // it in explicitly rather than parsing rendered add-to-cart buttons.
        if (! $needs && is_front_page()) {
            $needs = true;
        }

        // Ceiling: won't detect add-to-cart buttons printed by template tags on
        // arbitrary pages. Upgrade: add_filter('underscores_needs_cart_fragments','__return_true').
        return apply_filters('underscores_needs_cart_fragments', $needs);
    }

    /* ------------------------------------------------------------------ *
     * Cross-sells
     * ------------------------------------------------------------------ */

    /**
     * Cross-sells below the cart, full width. Woo's display self-hides when
     * the cart has no cross-sell products, so the section only shows when
     * relevant.
     */
    public function cross_sells_full_width(): void
    {
        if (! function_exists('woocommerce_cross_sell_display')) {
            return;
        }
        $cross = WC()->cart ? WC()->cart->get_cross_sells() : [];
        if (empty($cross)) {
            return;
        }
        // Woo's cross-sells.php prints its own <h2> heading (translated below).
        echo '<section class="cross-sells-section"><div class="wrap">';
        woocommerce_cross_sell_display(4, 4); // limit 4, 4 columns
        echo '</div></section>';
    }

    /* ------------------------------------------------------------------ *
     * Inline string translation
     * ------------------------------------------------------------------ */

    /**
     * Translate the handful of cart strings Woo prints without a dedicated
     * filter (mini-cart empty message + View cart / Checkout buttons).
     *
     * NOTE: These are WooCommerce-domain strings (`__('...', 'woocommerce')`).
     * They would normally be translated via a `woocommerce-vi.mo` file in
     * wp-content/languages/woocommerce/. We keep this map as a hard fallback
     * so the storefront stays Vietnamese even if a Woo .mo is missing or
     * incomplete. Remove this filter once Woo ships a vi .po we trust.
     *
     * @param string $translated
     * @param string $text
     * @param string $domain
     * @return string
     */
    public function translate_cart_strings(string $translated, string $text, string $domain): string
    {
        static $map = [
            'No products in the cart.'                        => 'Chưa có sản phẩm trong giỏ.',
            'View cart'                                       => 'Xem giỏ hàng',
            'View Cart'                                       => 'Xem giỏ hàng',
            'Checkout'                                        => 'Thanh toán',
            'Shop'                                            => 'Cửa hàng',
            'Home'                                            => 'Trang chủ',
            'Search results for &ldquo;%s&rdquo;'             => 'Kết quả tìm kiếm cho “%s”',
            'Products tagged &ldquo;%s&rdquo;'                => 'Sản phẩm gắn thẻ “%s”',
            'No products were found matching your selection.' => 'Không tìm thấy sản phẩm phù hợp với bộ lọc.',
            'Proceed to checkout'                             => 'Tiến hành thanh toán',
            'Return to shop'                                  => 'Tiếp tục mua sắm',
            'Your cart is currently empty.'                   => 'Giỏ hàng đang trống.',
            'Update cart'                                     => 'Cập nhật giỏ',
            'Apply coupon'                                    => 'Áp dụng mã',
            'Place order'                                     => 'Đặt hàng',
            'You may be interested in&hellip;'                => 'Có thể bạn cũng thích',
            'You may be interested in…'                       => 'Có thể bạn cũng thích',
        ];

        return $map[$text] ?? $translated;
    }

    /**
     * Vietnamese coupon message on the checkout page. Replaces Woo's default
     * ("Have a coupon? Click here to enter your code") and the embedded
     * <a> stops browsers from auto-translating the inline English in non-VN
     * locales.
     */
    public function checkout_coupon_message(): string
    {
        return 'Bạn có mã giảm giá? <a href="#" class="showcoupon">Ấn vào đây để nhập mã</a>';
    }
}
