<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * WooCommerce PDP + product rendering.
 *
 *   - Gallery support (zoom/lightbox/slider) + product-card image size.
 *   - PDP summary order: price → gifts → stock → add-to-cart.
 *   - Variation swatches (buttons above the native <select>).
 *   - Custom product tabs (Tổng quan / Thông số / Hỏi đáp / Phụ kiện).
 *   - Loop add-to-cart class + columns + per-page.
 *   - LCP-aware gallery image attributes (featured=eager+high, gallery=lazy+low).
 *   - Vietnamese add-to-cart labels and price save badge.
 *
 * All entry points guard on class_exists('WooCommerce') so disabling the
 * plugin never fatals the front end.
 */
final class WooProductHook
{
    public static function register(): void
    {
        if (! class_exists('WooCommerce')) {
            return;
        }

        $self = new self();

        add_action('after_setup_theme', [$self, 'add_gallery_support']);
        add_action('after_setup_theme', [$self, 'register_image_sizes']);

        // Vietnamese add-to-cart labels (don't rely on the Woo .mo being present).
        add_filter('woocommerce_product_add_to_cart_text', [$self, 'add_to_cart_text'], 10, 2);
        add_filter('woocommerce_product_single_add_to_cart_text', [$self, 'single_add_to_cart_text'], 10, 2);
        add_filter('woocommerce_get_price_html', [$self, 'add_price_save_badge'], 10, 2);
        // WordPress.org currently has no Vietnamese language pack for YITH
        // Wishlist. Keep its public UI consistent with the Vietnamese store.
        add_filter('gettext', [$self, 'translate_yith_text'], 20, 3);

        // Keep Woo's AJAX add-to-cart classes, just append the .addcart look.
        add_filter('woocommerce_loop_add_to_cart_args', [$self, 'add_to_cart_args'], 10, 2);

        // Loop counts (also apply to upsells).
        add_filter('loop_shop_columns', [$self, 'loop_columns']);
        add_filter('loop_shop_per_page', [$self, 'loop_per_page']);
        add_filter('woocommerce_upsells_columns', [$self, 'loop_columns']);

        // Gallery image attributes (LCP-aware).
        add_filter('wp_get_attachment_image_attributes', [$self, 'product_gallery_image_attrs'], 10, 3);

        // Review form (PDP) — only loaded when the single-product template
        // is rendered.
        add_action('wp_enqueue_scripts', [$self, 'enqueue_review_form_asset']);

        // PDP summary: gifts (@12), stock (@28), SKU (@6).
        add_action('woocommerce_single_product_summary', [$self, 'sku_line'], 6);
        add_action('woocommerce_single_product_summary', [$self, 'gifts_block'], 12);
        add_action('woocommerce_single_product_summary', [$self, 'stock_block'], 28);

        // "Mua ngay" + wishlist after add-to-cart on simple product pages.
        add_action('woocommerce_after_add_to_cart_button', [$self, 'buy_now_button']);

        // Buy now: add to cart then jump to checkout.
        add_filter('woocommerce_add_to_cart_redirect', [$self, 'buy_now_redirect']);

        // Drop Woo's single excerpt — design has its own structure.
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);

        // Colour/attribute variations → swatch buttons alongside the Woo <select>.
        add_filter('woocommerce_dropdown_variation_attribute_options_html', [$self, 'variation_swatches'], 20, 2);

        // Keep Woo's native tabs, only localize the two content labels.
        add_filter('woocommerce_product_tabs', [$self, 'product_tabs'], 98);

        // Internal-link blocks after related.
        add_action('woocommerce_after_single_product_summary', [$self, 'internal_links'], 25);

        // Related / upsell counts + VN headings.
        add_filter('woocommerce_output_related_products_args', [$self, 'related_args']);
        add_filter('woocommerce_product_related_products_heading', [$self, 'related_heading']);
        add_filter('woocommerce_product_upsells_products_heading', [$self, 'upsells_heading']);
    }

    /* ------------------------------------------------------------------ *
     * Gallery + image sizes
     * ------------------------------------------------------------------ */

    public function add_gallery_support(): void
    {
        add_theme_support('wc-product-gallery-zoom');
        add_theme_support('wc-product-gallery-lightbox');
        add_theme_support('wc-product-gallery-slider');
    }

    /**
     * Product-card image size that matches the card's 1:1 box.
     *
     * 600×600 hard-cropped = 2× the ~300px card slot, so it stays sharp on
     * retina. Used by product-card.php instead of Woo's generic
     * `woocommerce_thumbnail` (whose dimensions depend on store settings).
     */
    public function register_image_sizes(): void
    {
        add_image_size('pxc_card', 600, 600, true);
    }

    public function translate_yith_text(string $translation, string $text, string $domain): string
    {
        if ($domain !== 'yith-woocommerce-wishlist') {
            return $translation;
        }

        $translations = [
            'My wishlist'                              => 'Danh sách yêu thích',
            'Product name'                             => 'Sản phẩm',
            'Unit price'                               => 'Đơn giá',
            'Stock status'                             => 'Tình trạng',
            'No products added to the wishlist'        => 'Chưa có sản phẩm nào trong danh sách yêu thích',
            'Remove this product'                      => 'Xóa sản phẩm này',
            'Remove'                                   => 'Xóa',
            'Add to cart'                              => 'Thêm vào giỏ',
            'Share on:'                                => 'Chia sẻ qua:',
            'In stock'                                 => 'Còn hàng',
            'Out of stock'                             => 'Hết hàng',
            'Product added!'                           => 'Đã thêm vào yêu thích!',
            'Add to wishlist'                          => 'Thêm vào yêu thích',
            'Browse wishlist'                          => 'Xem danh sách yêu thích',
            'The product is already in your wishlist!' => 'Sản phẩm đã có trong danh sách yêu thích!',
        ];

        return $translations[$text] ?? $translation;
    }

    /* ------------------------------------------------------------------ *
     * PDP blocks
     * ------------------------------------------------------------------ */

    public function sku_line(): void
    {
        global $product;
        if (! $product instanceof \WC_Product) {
            return;
        }
        $sku = $product->get_sku();
        if ($sku === '') {
            return;
        }
        echo '<div class="sku">' . esc_html__('Mã SKU:', 'underscores') . ' <b>' . esc_html($sku) . '</b></div>';
    }

    /** Quà tặng kèm — the only product-specific ACF field kept by the theme. */
    public function gifts_block(): void
    {
        global $product;
        if (! $product instanceof \WC_Product || ! function_exists('get_field')) {
            return;
        }
        $id    = $product->get_id();
        $gifts = array_values(array_filter(
            (array) (get_field('gifts', $id) ?: []),
            static fn($gift): bool => is_array($gift) && trim((string) ($gift['name'] ?? '')) !== ''
        ));
        if (empty($gifts)) {
            return;
        }
        $total = array_reduce(
            $gifts,
            static fn(float $sum, array $gift): float => $sum + max(0.0, (float) ($gift['value'] ?? 0)),
            0.0
        );
        echo '<div class="pdp-gifts"><div class="gift-head"><b>' . esc_html__('Quà tặng kèm', 'underscores') . '</b>';
        if ($total > 0) {
            echo '<span class="gift-total">' . sprintf(
                /* translators: %s = total value of included gifts. */
                esc_html__('Tổng trị giá %s', 'underscores'),
                wp_kses_post(wc_price($total))
            ) . '</span>';
        }
        echo '</div><ul class="gift-list">';
        foreach ($gifts as $gift) {
            echo '<li>' . esc_html($gift['name'] ?? '');
            $value = max(0.0, (float) ($gift['value'] ?? 0));
            if ($value > 0) {
                echo ' <small>(' . wp_kses_post(wc_price($value)) . ')</small>';
            }
            echo '</li>';
        }
        echo '</ul></div>';
    }

    /** Woo stock status, right before add-to-cart. */
    public function stock_block(): void
    {
        global $product;
        if (! $product instanceof \WC_Product) {
            return;
        }
        echo '<div class="pdp-stock">';
        if ($product->is_in_stock()) {
            echo '<span class="dot"></span><b>' . esc_html__('Còn hàng', 'underscores') . '</b>';
        } else {
            echo '<span class="dot dot--out"></span><b>' . esc_html__('Hết hàng', 'underscores') . '</b>';
        }
        echo '</div>';
    }

    public function buy_now_button(): void
    {
        global $product;
        if (! $product instanceof \WC_Product) {
            return;
        }
        if (! $product->is_purchasable() || ! $product->is_in_stock()) {
            return;
        }
        printf(
            '<button type="submit" name="buy_now" value="1" class="buynow button alt"><span>%s</span><small>%s</small></button>',
            esc_html__('Mua ngay', 'underscores'),
            esc_html__('Giao tận tay trong 4 giờ', 'underscores')
        );

        // Wishlist toggle (shared helper) — 'ic' variant for the PDP layout.
        echo underscores_child_wishlist_button($product->get_id(), 'ic'); // returns escaped markup
    }

    public function buy_now_redirect(string $url): string
    {
        if (isset($_REQUEST['buy_now'])) {
            return wc_get_checkout_url();
        }
        return $url;
    }

    public function internal_links(): void
    {
        get_template_part('partials/components/product-internal-links');
    }

    /* ------------------------------------------------------------------ *
     * Variation swatches
     * ------------------------------------------------------------------ */

    /**
     * Add swatch buttons above Woo's variation <select>. Buttons set the
     * (hidden) select value via JS, so Woo's own variation script still
     * drives the price / image / add-to-cart updates. Falls back to the
     * plain select with no JS.
     *
     * @param string $html
     * @param array<string,mixed> $args
     */
    public function variation_swatches(string $html, array $args): string
    {
        $options   = $args['options'] ?? [];
        $product   = $args['product'] ?? null;
        $attribute = $args['attribute'] ?? '';
        if (empty($options) || ! $product instanceof \WC_Product || $attribute === '') {
            return $html;
        }

        // Get product brand for dynamic brand warranty subtext. Empty when the
        // product has no brand term — never invent one (data-rendering rule).
        $brand_terms = get_the_terms($product->get_id(), 'product_brand');
        $brand = (! is_wp_error($brand_terms) && ! empty($brand_terms)) ? $brand_terms[0]->name : '';

        // Term name and description lookup.
        $names = [];
        $descriptions = [];
        if (taxonomy_exists($attribute)) {
            foreach (wc_get_product_terms($product->get_id(), $attribute, ['fields' => 'all']) as $term) {
                if (in_array($term->slug, $options, true)) {
                    $names[$term->slug] = $term->name;
                    $descriptions[$term->slug] = $term->description;
                }
            }
        }

        // Fallback description mappings for standard attributes.
        $fallbacks = [
            'tieu-chuan'           => __('Không phụ kiện', 'underscores'),
            'bao-hanh-pixelcam'    => __('Bảo hành tại cửa hàng 12 tháng', 'underscores'),
            // Only name the brand when the product actually has one.
            'bao-hanh-chinh-hang'  => $brand !== ''
                ? sprintf(__('Bảo hành hãng %s 24 tháng', 'underscores'), $brand)
                : __('Bảo hành chính hãng 24 tháng', 'underscores'),
        ];

        // Retrieve variable product variation prices to show prices / differences.
        $min_price = 0.0;
        $variation_prices = [];
        $variation_stock = [];
        if ($product->is_type('variable')) {
            /** @var \WC_Product_Variable $product */
            // Memoize per product: variation_swatches() runs once per attribute
            // group, so a 2-attribute product would otherwise rebuild every
            // variation twice. Cache the array for this request.
            static $variations_cache = [];
            $pid = $product->get_id();
            if (! isset($variations_cache[$pid])) {
                $variations_cache[$pid] = $product->get_available_variations();
            }
            $variations = $variations_cache[$pid];
            $prices = [];
            foreach ($variations as $var) {
                $prices[] = (float) $var['display_price'];

                // Track prices for this specific attribute.
                $attr_key = 'attribute_' . $attribute;
                if (isset($var['attributes'][$attr_key])) {
                    $val = $var['attributes'][$attr_key];
                    if ($val !== '') {
                        $variation_prices[$val] = (float) $var['display_price'];
                        $variation_stock[$val] = (bool) $var['is_in_stock'];
                    }
                }
            }
            $min_price = ! empty($prices) ? min($prices) : 0.0;
        }

        $buttons = '<div class="swatches" role="group">';
        foreach ($options as $slug) {
            $name = $names[$slug] ?? $slug;
            $desc = $descriptions[$slug] ?? ($fallbacks[$slug] ?? '');

            // If out of stock, show "Hết hàng"
            if (isset($variation_stock[$slug]) && ! $variation_stock[$slug]) {
                $desc = __('Hết hàng', 'underscores');
            } else {
                $has_price_diff = false;
                $price_subtext = '';
                if (isset($variation_prices[$slug])) {
                    $opt_price = $variation_prices[$slug];
                    if ($opt_price > $min_price) {
                        $diff = $opt_price - $min_price;
                        $price_subtext = '+' . strip_tags(wc_price($diff));
                        $has_price_diff = true;
                    } elseif ($opt_price === $min_price && $attribute === 'pa_phien-ban' && $desc === '') {
                        $price_subtext = strip_tags(wc_price($opt_price));
                    }
                }

                if ($has_price_diff) {
                    $desc = $price_subtext;
                } else {
                    $desc = $descriptions[$slug] ?? ($fallbacks[$slug] ?? $price_subtext);
                }
            }

            // Inject brand name into warranty label if missing
            if ($slug === 'bao-hanh-chinh-hang' && $desc !== '' && strpos($desc, 'hãng') !== false && strpos($desc, $brand) === false) {
                $desc = str_replace('hãng', 'hãng ' . $brand, $desc);
            }

            $buttons .= sprintf(
                '<button type="button" class="swatch variant-chip" data-value="%s"><span>%s</span>%s</button>',
                esc_attr($slug),
                esc_html($name),
                $desc !== '' ? '<small>' . esc_html($desc) . '</small>' : ''
            );
        }
        $buttons .= '</div>';

        // Swatches first, then the (JS-hidden) native select for compatibility.
        return $buttons . $html;
    }

    /* ------------------------------------------------------------------ *
     * Tabs
     * ------------------------------------------------------------------ */

    /**
     * @param array<string,array<string,mixed>> $tabs
     * @return array<string,array<string,mixed>>
     */
    public function product_tabs(array $tabs): array
    {
        if (isset($tabs['description'])) {
            $tabs['description']['title'] = __('Tổng quan', 'underscores');
            $tabs['description']['priority'] = 5;
        }
        if (isset($tabs['additional_information'])) {
            $tabs['additional_information']['title'] = __('Thông số kỹ thuật', 'underscores');
            $tabs['additional_information']['priority'] = 15;
        }

        return $tabs;
    }

    /* ------------------------------------------------------------------ *
     * Loop helpers (used by archive + related + upsells)
     * ------------------------------------------------------------------ */

    public function loop_columns(int $columns): int
    {
        return 3;
    }

    public function loop_per_page(int $per_page): int
    {
        // 30 fills the 5-col desktop grid in 6 full rows (no partial last row).
        // On mobile 2-col, 30 → 15 full rows, so even batches never orphan.
        // (Categories with odd totals — 7, 25, 9, 3 — will have a 1-card
        // orphan on mobile regardless of per_page, since the count itself
        // is odd; that needs a data-level fix, not a pagination one.)
        return 30;
    }

    /**
     * @param array<string,mixed> $args
     * @return array<string,mixed>
     */
    public function add_to_cart_args(array $args, \WC_Product $product): array
    {
        $args['class'] = trim(($args['class'] ?? '') . ' addcart');
        return $args;
    }

    public function add_to_cart_text(string $text, \WC_Product $product): string
    {
        if (! $product->is_purchasable() || ! $product->is_in_stock()) {
            return __('Hết hàng', 'underscores');
        }
        if ($product->is_type('variable')) {
            return __('Chọn tùy chọn', 'underscores');
        }
        if ($product->is_type('grouped')) {
            return __('Xem sản phẩm', 'underscores');
        }
        if ($product->is_type('external')) {
            return $product->get_button_text() ?: __('Mua ngay', 'underscores');
        }
        return __('Thêm vào giỏ', 'underscores');
    }

    public function single_add_to_cart_text(string $text, \WC_Product $product): string
    {
        if ($product->is_type('external')) {
            return $product->get_button_text() ?: __('Mua ngay', 'underscores');
        }
        return __('Thêm vào giỏ', 'underscores');
    }

    /**
     * Add sale save badge next to the single product price.
     */
    public function add_price_save_badge(string $price_html, \WC_Product $product): string
    {
        if (! is_admin()) {
            $percentage = underscores_child_sale_percent($product);
            if ($percentage !== null) {
                $price_html .= ' <span class="save">-' . $percentage . '%</span>';
            }
        }
        return $price_html;
    }

    /**
     * @param array<string,mixed> $args
     * @return array<string,mixed>
     */
    public function related_args(array $args): array
    {
        $args['posts_per_page'] = 3;
        $args['columns']        = 3;
        return $args;
    }

    public function related_heading(): string
    {
        return __('Sản phẩm tương tự', 'underscores');
    }

    public function upsells_heading(): string
    {
        return __('Khách thường mua kèm', 'underscores');
    }

    /**
     * Featured image = LCP candidate (eager + high). Gallery thumbs = below
     * the fold (lazy + low) so they don't compete for bandwidth with the
     * main image.
     *
     * NOTE: $size is `mixed` because WP core passes either a string
     * (`'large'`) or an array (`[600, 600]`) depending on the caller.
     * `_wp_post_thumbnail_html()` uses the array form for the featured-image
     * meta box in wp-admin, which was throwing TypeError before.
     *
     * @param array<string,mixed> $attrs
     * @return array<string,mixed>
     */
    public function product_gallery_image_attrs(array $attrs, \WP_Post $attachment, mixed $size): array
    {
        // Scope to single-product pages so we don't affect blog / shop / cart
        // image attributes.
        if (! function_exists('is_product') || ! is_product()) {
            return $attrs;
        }

        $product = wc_get_product();
        if (! $product instanceof \WC_Product) {
            return $attrs;
        }

        $thumb_id    = (int) $product->get_image_id();
        $gallery_ids = (array) $product->get_gallery_image_ids();
        $is_thumb    = $attachment->ID === $thumb_id;
        $is_gallery  = in_array($attachment->ID, $gallery_ids, true);

        if (! $is_thumb && ! $is_gallery) {
            return $attrs;
        }

        if ($is_thumb) {
            $attrs['loading']       = 'eager';
            $attrs['fetchpriority'] = 'high';
        } else {
            $attrs['loading']       = 'lazy';
            $attrs['fetchpriority'] = 'low';
        }
        return $attrs;
    }

    /**
     * Enqueue the review form script only on the single product page (the
     * custom single-product-reviews.php template lives there).
     */
    public function enqueue_review_form_asset(): void
    {
        if (! function_exists('is_product') || ! is_product()) {
            return;
        }

        $relative = 'assets/scripts/woocommerce/review-form.js';
        $path     = underscores_child_asset_path($relative);
        if (! file_exists($path)) {
            return;
        }

        wp_enqueue_script(
            'pxc-review-form',
            underscores_child_asset_uri($relative),
            [],
            underscores_child_asset_version($relative),
            true
        );
    }
}
