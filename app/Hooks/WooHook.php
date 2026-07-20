<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * WooCommerce integration for the Pixel Cam store.
 *
 * - Product gallery support (zoom/lightbox/slider).
 * - Replace the default Woo content wrapper with a plain .wrap (header.php
 *   already owns <main>).
 * - Mini-cart count/total via core cart fragments (no custom AJAX).
 * - Drop the always-on cart-fragments poll outside shop/cart/checkout.
 *
 * All entry points guard on class_exists('WooCommerce') so disabling the
 * plugin never fatals the front end.
 */
final class WooHook
{
    public static function register(): void
    {
        if (! class_exists('WooCommerce')) {
            return;
        }

        $self = new self();

        add_action('after_setup_theme', [$self, 'add_gallery_support']);
        add_action('after_setup_theme', [$self, 'register_image_sizes']);
        add_action('init', [$self, 'swap_content_wrapper']);
        // Product search (?s=...&post_type=product) is is_search(), not a
        // product post-type archive, so Woo's template_loader skips
        // archive-product.php and WP falls back to the theme search.php (no shop
        // wrapper). Route it through woocommerce_content() so the shop layout
        // (page head + sidebar + product grid) renders like the shop archive.
        add_filter('template_include', [$self, 'product_search_template'], 99);
        add_filter('woocommerce_add_to_cart_fragments', [$self, 'cart_count_fragment']);
        add_action('wp_enqueue_scripts', [$self, 'trim_cart_fragments'], 99);
        add_filter('wp_get_attachment_image_attributes', [$self, 'product_gallery_image_attrs'], 10, 3);

        // Vietnamese add-to-cart labels (don't rely on the Woo .mo being present).
        add_filter('woocommerce_product_add_to_cart_text', [$self, 'add_to_cart_text'], 10, 2);
        add_filter('woocommerce_product_single_add_to_cart_text', [$self, 'single_add_to_cart_text'], 10, 2);
        add_filter('woocommerce_get_price_html', [$self, 'add_price_save_badge'], 10, 2);

        // Vietnamese mini-cart / cart strings that Woo prints inline (no dedicated
        // filter) — translate them directly so the empty state reads in Vietnamese.
        add_filter('gettext_woocommerce', [$self, 'translate_cart_strings'], 10, 3);

        // Shop / category page head: breadcrumb + H1 + description in a .page-head.
        // woocommerce_content() does NOT fire woocommerce_before_main_content, so
        // hook before_shop_loop at priority 0 — before open_shop_grid (@1) opens
        // .shop, so the head sits above the sidebar + grid.
        add_action('woocommerce_before_shop_loop', [$self, 'shop_page_head'], 0);
        // Empty results skip before_shop_loop entirely — still need page head +
        // shop shell (sidebar layout) so search-no-results isn't a bare notice.
        add_action('woocommerce_no_products_found', [$self, 'shop_page_head'], 0);
        add_action('woocommerce_no_products_found', [$self, 'open_shop_grid'], 1);
        remove_action('woocommerce_no_products_found', 'wc_no_products_found', 10);
        add_action('woocommerce_no_products_found', [$self, 'no_products_found'], 10);
        add_action('woocommerce_no_products_found', [$self, 'close_shop_grid'], 20);
        // Woo prints the archive title/description inside the loop by default;
        // we render our own .page-head above, so drop the defaults.
        add_filter('woocommerce_show_page_title', '__return_false');
        remove_action('woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10);
        remove_action('woocommerce_archive_description', 'woocommerce_product_archive_description', 10);

        // SKU line between the title (@5) and price (@10) — matches the design.
        add_action('woocommerce_single_product_summary', [$self, 'sku_line'], 6);

        // PDP summary order to match the design:
        //   price(10) → gifts(12) → version(15) → stock(28) → add-to-cart(30)
        add_action('woocommerce_single_product_summary', [$self, 'gifts_block'], 12);
        add_action('woocommerce_single_product_summary', [$self, 'version_chips'], 15);
        add_action('woocommerce_single_product_summary', [$self, 'stock_block'], 28);

        // Colour/attribute variations → swatch buttons alongside the Woo <select>.
        add_filter('woocommerce_dropdown_variation_attribute_options_html', [$self, 'variation_swatches'], 20, 2);

        // "Mua ngay" button next to add-to-cart on simple product pages.
        add_action('woocommerce_after_add_to_cart_button', [$self, 'buy_now_button']);

        // Cross-sells default to .cart-collaterals (the summary column) which
        // breaks the 2-column layout — move them below the cart, full width.
        // woocommerce_cross_sell_display self-hides when there are none, so this
        // only appears when products actually have cross-sells.
        remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');
        add_action('woocommerce_after_cart', [$self, 'cross_sells_full_width'], 10);

        remove_action('woocommerce_cart_is_empty', 'wc_empty_cart_message', 10);

        // Vietnamese My Account menu labels.
        add_filter('woocommerce_account_menu_items', [$self, 'account_menu_items']);
        add_filter('woocommerce_add_to_cart_redirect', [$self, 'buy_now_redirect']);
        
        // Hide WooCommerce single product short description
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
        
        // Translate checkout coupon message to match Vietnamese locale and prevent browser translation icons
        add_filter('woocommerce_checkout_coupon_message', function() {
            return 'Bạn có mã giảm giá? <a href="#" class="showcoupon">Ấn vào đây để nhập mã</a>';
        });

        // VietQR options page and fields registration
        add_action('acf/init', [$self, 'register_vietqr_settings']);

        // Output VietQR code on checkout order thankyou page
        add_action('woocommerce_thankyou', [$self, 'add_vietqr_to_thankyou_page'], 10, 1);

        // Translate Bank Transfer gateway title and description to Vietnamese
        add_filter('woocommerce_gateway_title', [$self, 'custom_gateway_title'], 20, 2);
        add_filter('woocommerce_gateway_description', [$self, 'custom_gateway_description'], 20, 2);

        // Sort bar layout (Woo defaults: result_count @20, catalog_ordering @30):
        //   .sortbar [ .res(20)  .right[ ordering(30)  view-toggle(31) ] ]
        add_action('woocommerce_before_shop_loop', [$self, 'open_sortbar'], 19);   // <div class="sortbar">
        add_action('woocommerce_before_shop_loop', [$self, 'open_sortbar_right'], 29); // <div class="right">
        add_action('woocommerce_before_shop_loop', [$self, 'view_toggle'], 31);
        add_action('woocommerce_before_shop_loop', [$self, 'close_sortbar'], 32);  // </div></div>

        // Vietnamese ordering labels + a Vietnamese result count.
        add_filter('woocommerce_catalog_orderby', [$self, 'catalog_orderby']);
        add_action('woocommerce_before_shop_loop', [$self, 'swap_result_count'], 18);

        // Shop loop: render as a plain .grid of product cards (matches home).
        add_filter('woocommerce_product_loop_start', [$self, 'loop_start']);
        add_filter('woocommerce_product_loop_end', [$self, 'loop_end']);
        add_filter('loop_shop_columns', [$self, 'loop_columns']);
        add_filter('loop_shop_per_page', [$self, 'loop_per_page']);

        // Single product: extra tabs (spec / Q&A / warranty) driven by ACF.
        add_filter('woocommerce_product_tabs', [$self, 'product_tabs'], 98);

        // Keep Woo's AJAX add-to-cart classes, just append the .addcart look.
        add_filter('woocommerce_loop_add_to_cart_args', [$self, 'add_to_cart_args'], 10, 2);

        // Internal-link blocks (#11): same material + related posts, after related.
        add_action('woocommerce_after_single_product_summary', [$self, 'internal_links'], 25);

        // Related / upsell counts + VN headings.
        add_filter('woocommerce_output_related_products_args', [$self, 'related_args']);
        add_filter('woocommerce_product_related_products_heading', [$self, 'related_heading']);
        add_filter('woocommerce_product_upsells_products_heading', [$self, 'upsells_heading']);
        add_filter('woocommerce_upsells_columns', [$self, 'loop_columns']);
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
     * @param array<string,array<string,mixed>> $tabs
     * @return array<string,array<string,mixed>>
     */
    /**
     * @param array<string,array<string,mixed>> $tabs
     * @return array<string,array<string,mixed>>
     */
    public function product_tabs(array $tabs): array
    {
        $product_id = get_the_ID();

        // Reviews tab — Woo's default only adds it when comments_open() is
        // true, which can be false for some products (e.g. global setting
        // off, or a specific post has comments closed). Force it on so the
        // tab count matches the vjshop-style 5-tab layout; the panel can
        // still show "no reviews yet" inside.
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
        // into the custom TỔNG QUAN tab below, alongside the "Hộp sản phẩm
        // bao gồm" card. Same for `additional_information` — empty on most
        // products and the spec table already covers it.
        unset($tabs['description'], $tabs['additional_information']);

        if (! function_exists('get_field')) {
            return $tabs;
        }

        // Tab 1 — TỔNG QUAN: description (with video embed) + box items.
        $box_items = get_field('box_items', $product_id) ?: [];
        $tabs['pxc_overview'] = [
            'title'    => __('Tổng quan', 'underscores'),
            'priority' => 5,
            'callback' => static function () use ($product_id, $box_items): void {
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
                if (! empty($box_items)) {
                    echo '<aside class="pdp-overview__box" aria-label="' . esc_attr__('Hộp sản phẩm bao gồm', 'underscores') . '">';
                    echo   '<div class="box-head">'
                         .   '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">'
                         .     '<path d="M3 7l9-4 9 4-9 4-9-4z" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linejoin="round"/>'
                         .     '<path d="M3 7v10l9 4 9-4V7" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linejoin="round"/>'
                         .     '<path d="M12 11v10" stroke="currentColor" stroke-width="1.6" fill="none"/>'
                         .   '</svg>'
                         .   '<b>' . esc_html__('Hộp sản phẩm bao gồm', 'underscores') . '</b>'
                         . '</div>';
                    // Reuse `.pdp-box` + `.box-list` styles that the right
                    // info column used to render with (see the now-removed
                    // block). The new wrapper just adds a margin-top so it
                    // sits next to the description.
                    echo   '<ul class="box-list">';
                    foreach ($box_items as $item) {
                        $text = trim((string) ($item['text'] ?? ''));
                        if ($text === '') {
                            continue;
                        }
                        echo '<li>' . esc_html($text) . '</li>';
                    }
                    echo   '</ul>';
                    echo '</aside>';
                }
                echo '</div>';
            },
        ];

        // Tab 2 — Thông số kỹ thuật.
        $spec_rows = get_field('spec_rows', $product_id) ?: [];
        if ($spec_rows) {
            $tabs['pxc_spec'] = [
                'title'    => __('Thông số kỹ thuật', 'underscores'),
                'priority' => 15,
                'callback' => static function () use ($spec_rows): void {
                    echo '<table class="spec-table">';
                    foreach ($spec_rows as $row) {
                        echo '<tr><th>' . esc_html($row['key'] ?? '') . '</th><td>' . nl2br(esc_html($row['value'] ?? '')) . '</td></tr>';
                    }
                    echo '</table>';
                },
            ];
        }

        // Tab 4 — Hỏi đáp.
        $qa_items = get_field('qa', $product_id) ?: [];
        if ($qa_items) {
            $tabs['pxc_qa'] = [
                'title'    => __('Hỏi đáp', 'underscores'),
                'priority' => 45,
                'callback' => static function () use ($qa_items): void {
                    foreach ($qa_items as $item) {
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
                        // phpcs:ignore WordPress.WP.DirectDatabaseQuery
                        $GLOBALS['post'] = $post; // product-card partial reads get_the_ID()
                        setup_postdata($post);
                        get_template_part('partials/components/product-card', null, ['product' => (int) $pid]);
                    }
                    wp_reset_postdata();
                    echo '</div></div>';
                },
            ];
        }

        return $tabs;
    }

    public function internal_links(): void
    {
        get_template_part('partials/components/product-internal-links');
    }

    /**
     * Append the theme's .addcart class to Woo's loop add-to-cart button without
     * dropping its .add_to_cart_button.ajax_add_to_cart classes (those drive the
     * AJAX add + mini-cart fragment refresh).
     *
     * @param array<string,mixed> $args
     * @return array<string,mixed>
     */
    public function add_to_cart_args(array $args, \WC_Product $product): array
    {
        $args['class'] = trim(($args['class'] ?? '') . ' addcart');
        return $args;
    }

    /**
     * "Mua ngay" on the single product page: add to cart then jump to checkout.
     * Only for simple, in-stock, purchasable products (variable products need
     * their options chosen first). JS syncs the chosen quantity into the URL.
     */
    /**
     * Render version chips: each sibling product as an <a> to its own URL, the
     * current one marked active, with its price. Two-way linked (see Versions).
     */
    /**
     * Add swatch buttons above Woo's variation <select>. Buttons set the (hidden)
     * select value via JS, so Woo's own variation script still drives the price /
     * image / add-to-cart updates. Falls back to the plain select with no JS.
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

        // Get product brand for dynamic brand warranty subtext.
        $brand_terms = get_the_terms($product->get_id(), 'product_brand');
        $brand = (! is_wp_error($brand_terms) && ! empty($brand_terms)) ? $brand_terms[0]->name : 'Sony';

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
            'bao-hanh-chinh-hang'  => sprintf(__('Bảo hành hãng %s 24 tháng', 'underscores'), $brand),
        ];

        // Retrieve variable product variation prices to show prices / differences.
        $min_price = 0.0;
        $variation_prices = [];
        $variation_stock = [];
        if ($product->is_type('variable')) {
            /** @var \WC_Product_Variable $product */
            $variations = $product->get_available_variations();
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

    /**
     * Cross-sells below the cart, full width. Woo's display self-hides when the
     * cart has no cross-sell products, so the section only shows when relevant.
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

        $product_id = $product->get_id();
        $has_yith    = defined('YITH_WCWL') && function_exists('YITH_WCWL');
        $in_wishlist = $has_yith && YITH_WCWL()->is_product_in_wishlist($product_id);
        $add_url     = $has_yith ? add_query_arg('add_to_wishlist', $product_id, get_permalink($product_id)) : '';

        printf(
            '<button class="wish ic%s" type="button" data-product-id="%d" data-add-url="%s" aria-label="%s" aria-pressed="%s">
                <svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8z"/></svg>
            </button>',
            $in_wishlist ? ' on' : '',
            (int) $product_id,
            esc_url($add_url),
            esc_attr__('Thêm vào yêu thích', 'underscores'),
            $in_wishlist ? 'true' : 'false'
        );
    }

    public function buy_now_redirect(string $url): string
    {
        if (isset($_REQUEST['buy_now'])) {
            return wc_get_checkout_url();
        }
        return $url;
    }

    /**
     * Loop (card) add-to-cart label in Vietnamese, per product state.
     */
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

    /**
     * Single product page add-to-cart label in Vietnamese.
     */
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
     * Translate the handful of cart strings Woo prints without a dedicated
     * filter (mini-cart empty message + View cart / Checkout buttons).
     */
    public function translate_cart_strings(string $translated, string $text, string $domain): string
    {
        static $map = [
            'No products in the cart.'                  => 'Chưa có sản phẩm trong giỏ.',
            'View cart'                                 => 'Xem giỏ hàng',
            'View Cart'                                 => 'Xem giỏ hàng',
            'Checkout'                                  => 'Thanh toán',
            'Shop'                                      => 'Cửa hàng',
            'Home'                                      => 'Trang chủ',
            'Search results for &ldquo;%s&rdquo;'       => 'Kết quả tìm kiếm cho “%s”',
            'Products tagged &ldquo;%s&rdquo;'          => 'Sản phẩm gắn thẻ “%s”',
            'No products were found matching your selection.' => 'Không tìm thấy sản phẩm phù hợp với bộ lọc.',
            'Proceed to checkout'                       => 'Tiến hành thanh toán',
            'Return to shop'                            => 'Tiếp tục mua sắm',
            'Your cart is currently empty.'             => 'Giỏ hàng đang trống.',
            'Update cart'                               => 'Cập nhật giỏ',
            'Apply coupon'                              => 'Áp dụng mã',
            'Place order'                               => 'Đặt hàng',
            'You may be interested in&hellip;'          => 'Có thể bạn cũng thích',
            'You may be interested in…'                 => 'Có thể bạn cũng thích',
        ];

        return $map[$text] ?? $translated;
    }

    public function loop_start(?string $html = ''): string
    {
        // No id here: this filter runs for every Woo loop (shop, related,
        // upsells, [products] shortcode). A shared id would duplicate on pages
        // that show more than one loop. WooCommerce 10.x occasionally passes
        // null here on empty loops — default to '' to keep the PDP rendering.
        return '<div class="grid">';
    }

    public function loop_end(?string $html = ''): string
    {
        return '</div>';
    }

    public function loop_columns(int $columns): int
    {
        return 3;
    }

    public function loop_per_page(int $per_page): int
    {
        return 12;
    }

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

    public function swap_content_wrapper(): void
    {
        remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
        remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);

        add_action('woocommerce_before_main_content', [$this, 'open_content_wrapper'], 10);
        add_action('woocommerce_after_main_content', [$this, 'close_content_wrapper'], 10);

        // woocommerce_content() (used via woocommerce.php) does NOT fire
        // before/after_main_content, so wrap the shop grid around the loop hooks.
        add_action('woocommerce_before_shop_loop', [$this, 'open_shop_grid'], 1);
        add_action('woocommerce_after_shop_loop', [$this, 'close_shop_grid'], 999);
    }

    public function product_search_template(string $template): string
    {
        if (is_search() && get_query_var('post_type') === 'product') {
            $located = locate_template('woocommerce.php');
            if ($located) {
                return $located;
            }
        }

        return $template;
    }

    public function open_content_wrapper(): void
    {
        echo '<div class="wrap woo-content">';
    }

    public function close_content_wrapper(): void
    {
        echo '</div>';
    }

    public function open_shop_grid(): void
    {
        // Per-category filters (FilterHook) take priority; fall back to the
        // shared widget sidebar (shop / categories without their own config).
        $filter      = new \Theme\Child\Hooks\FilterHook();
        $has_cat     = $filter->has_config();
        $has_widget  = ! $has_cat && is_active_sidebar('shop-filters');
        $has_filters = $has_cat || $has_widget;

        // .wrap constrains width + gutters (woocommerce_content() doesn't add one).
        echo '<div class="wrap shop-wrap">';

        if ($has_filters) {
            echo '<button class="filter-toggle" id="filterToggle" type="button"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M7 12h10M10 18h4"/></svg>' . esc_html__('Bộ lọc', 'underscores') . '</button>';
        }

        echo '<div class="shop' . ($has_filters ? '' : ' shop--no-filters') . '">';

        if ($has_filters) {
            echo '<aside class="filters" id="filters">';
            if ($has_cat) {
                $filter->render_sidebar();
            } else {
                dynamic_sidebar('shop-filters');
            }
            echo '</aside>';
        }

        echo '<div class="shop-main">';
    }

    public function close_shop_grid(): void
    {
        echo '</div></div></div>'; // .shop-main, .shop, .shop-wrap
    }

    /**
     * Empty shop / search / filtered archive: design empty-state instead of
     * Woo's default info notice. Product search gets search-specific copy.
     */
    public function no_products_found(): void
    {
        $is_product_search = is_search() && get_query_var('post_type') === 'product';
        $shop_url          = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');

        echo '<div class="empty-state empty-state--bordered">';

        if ($is_product_search) {
            $term = get_search_query();
            echo '<p class="es-title">' . esc_html__('Không tìm thấy sản phẩm', 'underscores') . '</p>';
            echo '<p class="es-sub">';
            echo esc_html(
                $term !== ''
                    /* translators: %s: search term */
                    ? sprintf(__('Không có sản phẩm nào khớp với “%s”. Thử từ khóa khác hoặc xem toàn bộ cửa hàng.', 'underscores'), $term)
                    : __('Không có sản phẩm nào khớp. Thử từ khóa khác hoặc xem toàn bộ cửa hàng.', 'underscores')
            );
            echo '</p>';
        } else {
            echo '<p class="es-title">' . esc_html__('Không tìm thấy sản phẩm', 'underscores') . '</p>';
            echo '<p class="es-sub">' . esc_html__('Không có sản phẩm phù hợp với bộ lọc. Thử bỏ bớt điều kiện hoặc xem toàn bộ cửa hàng.', 'underscores') . '</p>';
        }

        if ($shop_url) {
            echo '<a class="btn btn-primary" href="' . esc_url($shop_url) . '">' . esc_html__('Xem cửa hàng', 'underscores') . '</a>';
        }

        echo '</div>';
    }

    /**
     * Breadcrumb + H1 + description block above the shop/category grid.
     */
    public function shop_page_head(): void
    {
        // Only product listings use this head: shop, product taxonomy (category /
        // tag / attribute) and product search. Skip single product, cart, checkout.
        $is_listing = is_shop()
            || is_product_taxonomy()
            || (is_search() && get_query_var('post_type') === 'product');

        if (! $is_listing) {
            return;
        }

        echo '<div class="wrap page-head-wrap">';

        // Breadcrumb (Woo's, already themed via woocommerce_breadcrumb).
        woocommerce_breadcrumb([
            'wrap_before' => '<nav class="crumb" aria-label="Breadcrumb">',
            'wrap_after'  => '</nav>',
            'delimiter'   => '<span class="sep">/</span>',
            'home'        => __('Trang chủ', 'underscores'),
        ]);

        echo '<div class="page-head">';

        // Same head for shop, product category, product tag, product attribute
        // archives, and product search — all render the products.html layout.
        $title = '';
        if (is_search()) {
            /* translators: %s: search term */
            $title = sprintf(__('Kết quả tìm kiếm: %s', 'underscores'), get_search_query());
        } elseif (is_shop()) {
            $title = get_the_title(wc_get_page_id('shop'));
        } elseif (is_tax('product_tag')) {
            /* translators: %s: tag name */
            $title = sprintf(__('Sản phẩm gắn thẻ: %s', 'underscores'), single_term_title('', false));
        } elseif (is_product_taxonomy()) {
            $term  = get_queried_object();
            $title = $term instanceof \WP_Term ? single_term_title('', false) : woocommerce_page_title(false);
        } else {
            $title = woocommerce_page_title(false);
        }
        echo '<h1>' . esc_html($title) . '</h1>';

        // Category / tag description (search has none).
        $desc = '';
        if (is_product_taxonomy()) {
            $term = get_queried_object();
            if ($term instanceof \WP_Term && $term->description !== '') {
                $desc = $term->description;
            }
        }
        if ($desc !== '') {
            // Collapsible description: `.meta-wrap` is hidden by JS when the
            // content fits within the collapsed height (so short descriptions
            // show the full text without a button). See `initMetaToggle`.
            echo '<div class="meta-wrap" data-meta-wrap>'
                . '<div class="meta" data-meta>' . wp_kses_post(wpautop($desc)) . '</div>'
                . '<button class="meta-toggle" type="button" data-meta-toggle '
                . 'aria-expanded="false" aria-controls="' . esc_attr('meta-' . md5($desc)) . '">'
                . '<span class="meta-toggle__label" data-meta-label>'
                . esc_html__('Xem thêm', 'underscores') . '</span>'
                . '<svg class="meta-toggle__icon" viewBox="0 0 24 24" width="14" height="14" '
                . 'aria-hidden="true" focusable="false">'
                . '<path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" '
                . 'fill="none" stroke-linecap="round" stroke-linejoin="round"/>'
                . '</svg>'
                . '</button>'
                . '</div>';
        }

        echo '</div></div>'; // .page-head, .page-head-wrap
    }

    /**
     * Replace Woo's English result count with a Vietnamese one (runs before
     * open_sortbar so our .res sits at the start of the bar).
     */
    public function swap_result_count(): void
    {
        remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
        add_action('woocommerce_before_shop_loop', [$this, 'result_count'], 20);
    }

    public function result_count(): void
    {
        $total   = (int) wc_get_loop_prop('total');
        $per     = (int) wc_get_loop_prop('per_page');
        $current = max(1, (int) wc_get_loop_prop('current_page'));

        if ($total < 1) {
            return;
        }

        $first = ($per * ($current - 1)) + 1;
        $last  = min($total, $per * $current);

        printf(
            '<p class="res" role="status">%s</p>',
            sprintf(
                /* translators: 1: first item, 2: last item, 3: total */
                esc_html__('Hiển thị %1$s–%2$s trong %3$s sản phẩm', 'underscores'),
                '<b>' . number_format_i18n($first) . '</b>',
                '<b>' . number_format_i18n($last) . '</b>',
                '<b>' . number_format_i18n($total) . '</b>'
            )
        );
    }

    public function open_sortbar(): void
    {
        echo '<div class="sortbar">';
    }

    public function open_sortbar_right(): void
    {
        echo '<div class="right">';
    }

    public function close_sortbar(): void
    {
        echo '</div></div>'; // .right, .sortbar
    }

    /**
     * Grid / list view toggle (JS in pixel-cam.js flips .grid.list + stores it).
     */
    public function view_toggle(): void
    {
        echo '<div class="view-toggle" role="group" aria-label="' . esc_attr__('Kiểu hiển thị', 'underscores') . '">'
            . '<button type="button" class="on" data-view="grid" aria-label="' . esc_attr__('Lưới', 'underscores') . '"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></button>'
            . '<button type="button" data-view="list" aria-label="' . esc_attr__('Danh sách', 'underscores') . '"><svg viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg></button>'
            . '</div>';
    }

    /**
     * @param array<string,string> $options
     * @return array<string,string>
     */
    public function catalog_orderby(array $options): array
    {
        // Vietnamese labels, keyed so we only relabel the options Woo actually
        // offers (e.g. 'relevance' appears only on search pages).
        $labels = [
            'menu_order' => __('Mặc định', 'underscores'),
            'relevance'  => __('Liên quan nhất', 'underscores'),
            'popularity' => __('Bán chạy', 'underscores'),
            'rating'     => __('Đánh giá cao nhất', 'underscores'),
            'date'       => __('Mới nhất', 'underscores'),
            'price'      => __('Giá: thấp → cao', 'underscores'),
            'price-desc' => __('Giá: cao → thấp', 'underscores'),
        ];

        foreach ($options as $key => $label) {
            if (isset($labels[$key])) {
                $options[$key] = $labels[$key];
            }
        }
        return $options;
    }



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

        // ponytail: front-page renders products via a partial (not a shortcode),
        // so opt it in explicitly rather than parsing rendered add-to-cart buttons.
        if (! $needs && is_front_page()) {
            $needs = true;
        }

        // Ceiling: won't detect add-to-cart buttons printed by template tags on
        // arbitrary pages. Upgrade: add_filter('underscores_needs_cart_fragments','__return_true').
        return apply_filters('underscores_needs_cart_fragments', $needs);
    }

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

        // Only touch images attached to the current product's gallery. The
        // gallery is a list of attachment IDs in the `_product_image_gallery`
        // post meta; the featured image is the post thumbnail.
        $thumb_id      = (int) $product->get_image_id();
        $gallery_ids   = (array) $product->get_gallery_image_ids();
        $is_thumb      = $attachment->ID === $thumb_id;
        $is_gallery    = in_array($attachment->ID, $gallery_ids, true);

        if (! $is_thumb && ! $is_gallery) {
            return $attrs;
        }

        // Featured image is the LCP candidate — eager + high. Gallery
        // thumbs are below the fold, lazy + low priority so they don't
        // compete for bandwidth with the main image.
        if ($is_thumb) {
            $attrs['loading']       = 'eager';
            $attrs['fetchpriority'] = 'high';
        } else {
            $attrs['loading']       = 'lazy';
            $attrs['fetchpriority'] = 'low';
        }

        return $attrs;
    }

    public function register_vietqr_settings(): void
    {
        if (function_exists('acf_add_options_page')) {
            acf_add_options_page([
                'page_title'    => 'VietQR Settings',
                'menu_title'    => 'VietQR Settings',
                'menu_slug'     => 'vietqr-settings',
                'capability'    => 'edit_posts',
                'redirect'      => false,
            ]);
        }

        if (function_exists('acf_add_local_field_group')) {
            acf_add_local_field_group([
                'key' => 'group_vietqr_settings',
                'title' => 'Cấu hình VietQR',
                'fields' => [
                    [
                        'key' => 'field_vietqr_bank_id',
                        'label' => 'Mã ngân hàng (Bank ID)',
                        'name' => 'vietqr_bank_id',
                        'type' => 'text',
                        'instructions' => 'Ví dụ: MB, VCB, ICB, ACB, TCB, VIB...',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_vietqr_account_number',
                        'label' => 'Số tài khoản',
                        'name' => 'vietqr_account_number',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_vietqr_account_name',
                        'label' => 'Tên chủ tài khoản',
                        'name' => 'vietqr_account_name',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_vietqr_template',
                        'label' => 'Template VietQR',
                        'name' => 'vietqr_template',
                        'type' => 'select',
                        'choices' => [
                            'compact' => 'Compact (Chi tiết)',
                            'compact2' => 'Compact 2 (Chi tiết dọc)',
                            'qr_only' => 'QR Only (Chỉ mã QR)',
                            'print' => 'Print (In ấn)',
                        ],
                        'default_value' => 'compact2',
                    ],
                ],
                'location' => [
                    [
                        [
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => 'vietqr-settings',
                        ],
                    ],
                ],
            ]);
        }
    }

    public function add_vietqr_to_thankyou_page($order_id): void
    {
        if (!$order_id) {
            return;
        }
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Only show for Bank Transfer (bacs) payment method
        if ($order->get_payment_method() !== 'bacs') {
            return;
        }

        // Load ACF options settings with direct get_option fallbacks to be completely fail-safe
        $bank_id = function_exists('get_field') ? get_field('vietqr_bank_id', 'option') : null;
        $bank_id = $bank_id ?: get_option('options_vietqr_bank_id');

        $account_number = function_exists('get_field') ? get_field('vietqr_account_number', 'option') : null;
        $account_number = $account_number ?: get_option('options_vietqr_account_number');

        $account_name = function_exists('get_field') ? get_field('vietqr_account_name', 'option') : null;
        $account_name = $account_name ?: get_option('options_vietqr_account_name');

        $template = function_exists('get_field') ? get_field('vietqr_template', 'option') : null;
        $template = $template ?: get_option('options_vietqr_template') ?: 'compact2';

        if (!$bank_id || !$account_number || !$account_name) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('VietQR: missing bank settings — QR block skipped on thankyou page.');
            }
            return;
        }

        $amount = $order->get_total();
        // Format description as "DH1234"
        $description = 'DH' . $order_id;

        // Clean up account name for URL encoding
        $encoded_name = rawurlencode($account_name);
        $encoded_desc = rawurlencode($description);

        $qr_url = "https://img.vietqr.io/image/{$bank_id}-{$account_number}-{$template}.png?amount={$amount}&addInfo={$encoded_desc}&accountName={$encoded_name}";
        ?>
        <div class="vietqr-payment-info">
            <h3><?php esc_html_e('Thanh toán quét mã VietQR', 'underscores'); ?></h3>
            <p>
                <?php esc_html_e('Quét mã QR dưới đây bằng ứng dụng Ngân hàng để thanh toán nhanh chóng và chính xác số tiền.', 'underscores'); ?>
            </p>

            <div class="vietqr-qr-wrap">
                <img class="vietqr-qr-img" src="<?php echo esc_url($qr_url); ?>" alt="VietQR Payment Code" />
            </div>

            <div class="vietqr-details">
                <div><strong><?php esc_html_e('Ngân hàng:', 'underscores'); ?></strong> <?php echo esc_html($bank_id); ?></div>
                <div><strong><?php esc_html_e('Số tài khoản:', 'underscores'); ?></strong> <?php echo esc_html($account_number); ?></div>
                <div><strong><?php esc_html_e('Tên chủ tài khoản:', 'underscores'); ?></strong> <?php echo esc_html($account_name); ?></div>
                <div><strong><?php esc_html_e('Số tiền chuyển khoản:', 'underscores'); ?></strong> <span class="vietqr-amount"><?php echo wc_price($amount); ?></span></div>
                <div><strong><?php esc_html_e('Nội dung chuyển khoản:', 'underscores'); ?></strong> <span class="vietqr-transfer-note"><?php echo esc_html($description); ?></span></div>
            </div>
        </div>
        <?php
    }

    public function custom_gateway_title($title, $gateway_id): string
    {
        if ($gateway_id === 'bacs') {
            return 'Chuyển khoản ngân hàng';
        }
        return $title;
    }

    public function custom_gateway_description($description, $gateway_id): string
    {
        if ($gateway_id === 'bacs') {
            return 'Quét mã VietQR hoặc chuyển khoản trực tiếp vào tài khoản ngân hàng của chúng tôi. Vui lòng nhập đúng Mã đơn hàng làm nội dung chuyển khoản để đơn hàng được xác nhận tự động.';
        }
        return $description;
    }
}
