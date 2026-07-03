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
        add_action('init', [$self, 'swap_content_wrapper']);
        add_filter('woocommerce_add_to_cart_fragments', [$self, 'cart_count_fragment']);
        add_action('wp_enqueue_scripts', [$self, 'trim_cart_fragments'], 99);

        // Shop loop: render as a plain .grid of product cards (matches home).
        add_filter('woocommerce_product_loop_start', [$self, 'loop_start']);
        add_filter('woocommerce_product_loop_end', [$self, 'loop_end']);
        add_filter('loop_shop_columns', [$self, 'loop_columns']);
        add_filter('loop_shop_per_page', [$self, 'loop_per_page']);

        // Single product: extra tabs (spec / Q&A / warranty) driven by ACF.
        add_filter('woocommerce_product_tabs', [$self, 'product_tabs'], 98);

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
    public function product_tabs(array $tabs): array
    {
        $product_id = get_the_ID();

        if (! function_exists('get_field')) {
            return $tabs;
        }

        if (get_field('spec_rows', $product_id)) {
            $tabs['pxc_spec'] = [
                'title'    => __('Thông số kỹ thuật', 'underscores'),
                'priority' => 15,
                'callback' => [$this, 'render_spec_tab'],
            ];
        }

        if (get_field('qa', $product_id)) {
            $tabs['pxc_qa'] = [
                'title'    => __('Hỏi đáp', 'underscores'),
                'priority' => 45,
                'callback' => [$this, 'render_qa_tab'],
            ];
        }

        if (get_field('warranty', $product_id)) {
            $tabs['pxc_warranty'] = [
                'title'    => __('Bảo hành', 'underscores'),
                'priority' => 50,
                'callback' => [$this, 'render_warranty_tab'],
            ];
        }

        return $tabs;
    }

    public function render_spec_tab(): void
    {
        $rows = get_field('spec_rows', get_the_ID()) ?: [];
        if (empty($rows)) {
            return;
        }
        echo '<table class="spec-table">';
        foreach ($rows as $row) {
            echo '<tr><th>' . esc_html($row['key'] ?? '') . '</th><td>' . nl2br(esc_html($row['value'] ?? '')) . '</td></tr>';
        }
        echo '</table>';
    }

    public function render_qa_tab(): void
    {
        $items = get_field('qa', get_the_ID()) ?: [];
        if (empty($items)) {
            return;
        }
        foreach ($items as $item) {
            echo '<div class="qa-item">';
            echo '<div class="q">' . esc_html($item['question'] ?? '') . '</div>';
            echo '<div class="a">' . nl2br(esc_html($item['answer'] ?? '')) . '</div>';
            if (! empty($item['meta'])) {
                echo '<div class="meta">' . esc_html($item['meta']) . '</div>';
            }
            echo '</div>';
        }
    }

    public function render_warranty_tab(): void
    {
        $html = get_field('warranty', get_the_ID());
        if ($html) {
            echo '<div class="prose">' . wp_kses_post($html) . '</div>';
        }
    }

    public function internal_links(): void
    {
        get_template_part('partials/components/product-internal-links');
    }

    public function loop_start(string $html): string
    {
        // No id here: this filter runs for every Woo loop (shop, related,
        // upsells, [products] shortcode). A shared id would duplicate on pages
        // that show more than one loop.
        return '<div class="grid">';
    }

    public function loop_end(string $html): string
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
        $has_filters = is_active_sidebar('shop-filters');

        if ($has_filters) {
            echo '<button class="filter-toggle" id="filterToggle" type="button"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M7 12h10M10 18h4"/></svg>' . esc_html__('Bộ lọc', 'underscores') . '</button>';
        }

        echo '<div class="shop' . ($has_filters ? '' : ' shop--no-filters') . '">';

        if ($has_filters) {
            echo '<aside class="filters" id="filters">';
            dynamic_sidebar('shop-filters');
            echo '</aside>';
        }

        echo '<div class="shop-main">';
    }

    public function close_shop_grid(): void
    {
        echo '</div></div>'; // .shop-main, .shop
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
}
