<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * WooCommerce PDP + product rendering.
 *
 *   - Gallery support (zoom/lightbox/slider) + product-card image size.
 *   - PDP summary order: price → gifts → version chips → stock → add-to-cart.
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

        // PDP summary: gifts (@12), version chips (@15), stock (@28), SKU (@6).
        add_action('woocommerce_single_product_summary', [$self, 'sku_line'], 6);
        add_action('woocommerce_single_product_summary', [$self, 'gifts_block'], 12);
        add_action('woocommerce_single_product_summary', [$self, 'version_chips'], 15);
        add_action('woocommerce_single_product_summary', [$self, 'stock_block'], 28);

        // "Mua ngay" + wishlist after add-to-cart on simple product pages.
        add_action('woocommerce_after_add_to_cart_button', [$self, 'buy_now_button']);

        // Buy now: add to cart then jump to checkout.
        add_filter('woocommerce_add_to_cart_redirect', [$self, 'buy_now_redirect']);

        // Drop Woo's single excerpt — design has its own structure.
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);

        // Colour/attribute variations → swatch buttons alongside the Woo <select>.
        add_filter('woocommerce_dropdown_variation_attribute_options_html', [$self, 'variation_swatches'], 20, 2);

        // PDP product tabs (Tổng quan / Thông số / Hỏi đáp / Phụ kiện).
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

    /** Quà tặng kèm — ACF gifts, between price and version chips. */
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
        $total = (string) (get_field('gifts_total', $id) ?: '');
        echo '<div class="pdp-gifts"><div class="gift-head"><b>' . esc_html__('Quà tặng kèm', 'underscores') . '</b>';
        if ($total !== '') {
            echo '<span class="gift-total">' . esc_html($total) . '</span>';
        }
        echo '</div><ul class="gift-list">';
        foreach ($gifts as $gift) {
            echo '<li>' . esc_html($gift['name'] ?? '');
            if (! empty($gift['value'])) {
                echo ' <small>(' . esc_html($gift['value']) . ')</small>';
            }
            echo '</li>';
        }
        echo '</ul></div>';
    }

    /**
     * Render version chips: each sibling product as an <a> to its own URL, the
     * current one marked active, with its price. Two-way linked (see Versions).
     */
    public function version_chips(): void
    {
        global $product;
        if (! $product instanceof \WC_Product) {
            return;
        }
        $current = $product->get_id();
        $chips   = \Theme\Child\Product\Versions::chips($current);
        if (empty($chips)) {
            return;
        }

        $active_label = '';
        foreach ($chips as $chip) {
            if ($chip['current']) {
                $active_label = $chip['label'];
                break;
            }
        }

        $label_text = rtrim(\Theme\Child\Product\Versions::group_label($current), ' :');

        echo '<div class="variant-group pdp-versions" data-variant="version">';
        printf(
            '<div class="lbl">%s%s</div>',
            esc_html($label_text),
            $active_label !== '' ? ': <span class="sel">' . esc_html($active_label) . '</span>' : ''
        );
        echo '<div class="opts">';
        foreach ($chips as $chip) {
            $inner = '<span>' . esc_html($chip['label']) . '</span>'
                . ($chip['price'] !== '' ? '<small>' . wp_kses_post($chip['price']) . '</small>' : '');
            if ($chip['current']) {
                echo '<span class="variant-chip on" aria-current="true">' . $inner . '</span>';
            } else {
                echo '<a class="variant-chip" href="' . esc_url($chip['url']) . '">' . $inner . '</a>';
            }
        }
        echo '</div></div>';
    }

    /** Tình trạng kho + ghi chú giao hàng, right before add-to-cart. */
    public function stock_block(): void
    {
        global $product;
        if (! $product instanceof \WC_Product) {
            return;
        }
        $note = function_exists('get_field') ? (string) (get_field('stock_note', $product->get_id()) ?: '') : '';
        echo '<div class="pdp-stock">';
        if ($product->is_in_stock()) {
            echo '<span class="dot"></span><b>' . esc_html__('Còn hàng', 'underscores') . '</b>';
        } else {
            echo '<span class="dot dot--out"></span><b>' . esc_html__('Hết hàng', 'underscores') . '</b>';
        }
        if ($note !== '') {
            echo '<span class="muted">· ' . esc_html($note) . '</span>';
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
        $product_id = get_the_ID();

        // Reviews tab — Woo's default only adds it when comments_open() is
        // true, which can be false for some products. Force it on so the
        // tab count matches the design's 5-tab layout; the panel can still
        // show "no reviews yet" inside.
        $review_count = (int) get_comments_number($product_id);
        $tabs['reviews'] = [
            /* translators: %s = number of reviews */
            'title'    => sprintf(
                _n('Đánh giá <span class="count">(%s)</span>', 'Đánh giá <span class="count">(%s)</span>', $review_count, 'underscores'),
                number_format_i18n($review_count)
            ),
            'priority' => 30,
            'callback' => 'comments_template',
        ];

        // Drop Woo's default "Mô tả sản phẩm" tab — its content (which
        // usually holds the YouTube embed pasted by the shop owner) moves
        // into the custom TỔNG QUAN tab below. The "Hộp sản phẩm bao gồm"
        // block lives in the right info column (content-single-product.php),
        // not here. Same for `additional_information` — empty on most
        // products and the spec table already covers it.
        unset($tabs['description'], $tabs['additional_information']);

        if (! function_exists('get_field')) {
            return $tabs;
        }

        // Tab 1 — TỔNG QUAN: description (with video embed).
        // "Hộp sản phẩm bao gồm" is rendered separately in the right info
        // column (content-single-product.php) — wysiwyg block, so it sits
        // above-the-fold next to the buy buttons.
        $tabs['pxc_overview'] = [
            'title'    => __('Tổng quan', 'underscores'),
            'priority' => 5,
            'callback' => static function () use ($product_id): void {
                $product = wc_get_product($product_id);
                $description = $product instanceof \WC_Product ? (string) $product->get_description() : '';
                echo '<div class="pdp-overview">';
                echo   '<div class="pdp-overview__main">';
                if ($description !== '') {
                    // apply_filters('the_content', ...) so oEmbed / YouTube
                    // iframe shortcodes render properly.
                    echo '<div class="prose">' . wp_kses_post(apply_filters('the_content', $description)) . '</div>';
                } else {
                    echo '<div class="pdp-overview__empty">'
                       . esc_html__('Chưa có nội dung tổng quan. Hãy dán mô tả / video YouTube vào ô "Mô tả sản phẩm" của trình soạn thảo.', 'underscores')
                       . '</div>';
                }
                echo   '</div>';
                echo '</div>';
            },
        ];

        // Tab 2 — Thông số kỹ thuật.
        // (array) cast: legacy saves may have left these as a plain string,
        // and `?: []` only catches falsy values — an empty string would
        // still skip the cast and blow up the foreach inside the callback.
        $spec_rows = (array) (get_field('spec_rows', $product_id) ?: []);
        if ($spec_rows) {
            $tabs['pxc_spec'] = [
                'title'    => __('Thông số kỹ thuật', 'underscores'),
                'priority' => 15,
                'callback' => static function () use ($spec_rows): void {
                    echo '<table class="spec-table">';
                    foreach ($spec_rows as $row) {
                        if (! is_array($row)) {
                            continue;
                        }
                        echo '<tr><th>' . esc_html($row['key'] ?? '') . '</th><td>' . nl2br(esc_html($row['value'] ?? '')) . '</td></tr>';
                    }
                    echo '</table>';
                },
            ];
        }

        // Tab 4 — Hỏi đáp.
        $qa_items = (array) (get_field('qa', $product_id) ?: []);
        if ($qa_items) {
            $tabs['pxc_qa'] = [
                'title'    => __('Hỏi đáp', 'underscores'),
                'priority' => 45,
                'callback' => static function () use ($qa_items): void {
                    foreach ($qa_items as $item) {
                        if (! is_array($item)) {
                            continue;
                        }
                        echo '<div class="qa-item">';
                        echo '<div class="q">' . esc_html($item['question'] ?? '') . '</div>';
                        echo '<div class="a">' . nl2br(esc_html($item['answer'] ?? '')) . '</div>';
                        if (! empty($item['meta'])) {
                            echo '<div class="meta">' . esc_html($item['meta']) . '</div>';
                        }
                        echo '</div>';
                    }
                },
            ];
        }

        // Tab 5 — Phụ kiện: same Woo upsell source as the "Khách thường
        // mua kèm" sidebar block, so editing in one place updates both.
        $upsell_ids = get_post_meta($product_id, '_upsell_ids', true);
        $upsell_ids = is_array($upsell_ids) ? array_map('intval', $upsell_ids) : [];
        if (! empty($upsell_ids)) {
            $tabs['pxc_accessories'] = [
                'title'    => __('Phụ kiện', 'underscores'),
                'priority' => 55,
                'callback' => static function () use ($upsell_ids): void {
                    // Reuse the existing loop template so cards match the
                    // "Sản phẩm tương tự" block. We pin a stable wrapper
                    // class so the section can be styled if needed.
                    echo '<div class="related products pxc-accessories"><div class="pxc-accessories__grid">';
                    foreach ($upsell_ids as $pid) {
                        $post = get_post((int) $pid);
                        if (! $post instanceof \WP_Post || 'product' !== $post->post_type) {
                            continue;
                        }
                        setup_postdata($post);
                        get_template_part('partials/components/product-card', null, ['post_id' => (int) $pid]);
                    }
                    wp_reset_postdata();
                    echo '</div></div>';
                },
            ];
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
        // 30 fills a 5-col grid in 6 full rows (no partial last row).
        // Bumped from 12 — with 5 columns, 12 left 2 cards orphaned
        // on a partial row, making the grid look empty.
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
     * @param array<string,mixed> $attrs
     * @return array<string,mixed>
     */
    public function product_gallery_image_attrs(array $attrs, \WP_Post $attachment, string $size): array
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
