<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * WooCommerce template-level layout:
 *   - Replaces Woo's content wrapper with a plain .wrap (header.php owns <main>).
 *   - Shop / category / search: page-head (breadcrumb + H1 + collapsible desc),
 *     sort bar (result count + ordering + view toggle), no-results state.
 *   - Per-loop LCP counter init (consumed by content-product.php).
 *   - Product search ?s=...&post_type=product → shop layout (not search.php).
 *
 * Extracted from WooHook to keep PDP/product logic separate.
 */
final class WooTemplateHook
{
    public static function register(): void
    {
        if (! class_exists('WooCommerce')) {
            return;
        }

        $self = new self();

        add_action('init', [$self, 'swap_content_wrapper']);
        add_filter('template_include', [$self, 'product_search_template'], 99);

        // Drop Woo's default title/description in product archives — we render
        // our own .page-head above.
        add_filter('woocommerce_show_page_title', '__return_false');
        remove_action('woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10);
        remove_action('woocommerce_archive_description', 'woocommerce_product_archive_description', 10);

        // Page head (breadcrumb + H1 + description) above the shop grid.
        add_action('woocommerce_before_shop_loop', [$self, 'shop_page_head'], 0);

        // Empty results: page head + shop shell + custom empty state.
        add_action('woocommerce_no_products_found', [$self, 'shop_page_head'], 0);
        add_action('woocommerce_no_products_found', [$self, 'open_shop_grid'], 1);
        remove_action('woocommerce_no_products_found', 'wc_no_products_found', 10);
        add_action('woocommerce_no_products_found', [$self, 'no_products_found'], 10);
        add_action('woocommerce_no_products_found', [$self, 'close_shop_grid'], 20);

        // Sort bar (Woo defaults: result_count @20, catalog_ordering @30):
        //   .sortbar [ .res(20)  .right[ ordering(30)  view-toggle(31) ] ]
        add_action('woocommerce_before_shop_loop', [$self, 'swap_result_count'], 18);
        add_action('woocommerce_before_shop_loop', [$self, 'open_sortbar'], 19);
        add_action('woocommerce_before_shop_loop', [$self, 'open_sortbar_right'], 29);
        add_action('woocommerce_before_shop_loop', [$self, 'view_toggle'], 31);
        add_action('woocommerce_before_shop_loop', [$self, 'close_sortbar'], 32);

        // Vietnamese ordering labels.
        add_filter('woocommerce_catalog_orderby', [$self, 'catalog_orderby']);

        // Shop loop: plain .grid wrapper for product cards.
        add_filter('woocommerce_product_loop_start', [$self, 'loop_start']);
        add_filter('woocommerce_product_loop_end', [$self, 'loop_end']);
    }

    /* ------------------------------------------------------------------ *
     * Content wrapper
     * ------------------------------------------------------------------ */

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
        $filter      = new FilterHook();
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

    /* ------------------------------------------------------------------ *
     * Empty state + page head
     * ------------------------------------------------------------------ */

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

    /* ------------------------------------------------------------------ *
     * Sort bar / result count / view toggle
     * ------------------------------------------------------------------ */

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

    /* ------------------------------------------------------------------ *
     * Loop wrapper + per-loop LCP counter
     * ------------------------------------------------------------------ */

    public function loop_start(string $html): string
    {
        // Per-loop LCP counter reset. This filter runs exactly ONCE per Woo
        // loop (shop, related, upsells, [products] shortcode), so it's the
        // correct seam to start a fresh index per loop — unlike registering
        // a hook from inside content-product.php (which runs per product and
        // mis-fires).
        $loop_id = (int) ($GLOBALS['pxc_loop_current'] ?? 0) + 1;
        $GLOBALS['pxc_loop_current']            = $loop_id;
        $GLOBALS['pxc_loop_seen'][$loop_id]     = -1; // first product bumps to 0

        // No id here: a shared id would duplicate on pages with >1 loop.
        return '<div class="grid">';
    }

    public function loop_end(string $html): string
    {
        return '</div>';
    }
}
