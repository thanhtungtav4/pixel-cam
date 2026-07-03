<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * System-level technical SEO for the Pixel Cam store.
 *
 * Scope = crawl-budget + status-code + LCP concerns that Rank Math and Woo
 * core do NOT own. Everything Rank Math already does (title/meta/canonical
 * host, sitemap, robots.txt, Product/BreadcrumbList schema) is intentionally
 * left to Rank Math — this hook must not duplicate it.
 *
 * Covered brief points:
 *   #1  / #12  faceted + utility URLs (filter/sort/search/cart/account) get
 *              `noindex,follow` so they never mint index rác.
 *   #13 / #20  out-of-stock / trashed products resolve to a real 410 (gone)
 *              or an editor-set 301 — never a blanket redirect to home.
 *   #15        PDP main gallery image is eager + fetchpriority=high (LCP).
 *   #16        Woo's own CSS/JS bundles are dequeued off Woo pages.
 *
 * All Woo-touching entry points guard on class_exists('WooCommerce').
 */
final class SeoHook
{
    /**
     * Query vars that turn a canonical URL into a faceted / filtered variant.
     * These pages are useful for users but must stay out of the index.
     *
     * @var list<string>
     */
    private const FACET_PARAMS = [
        'orderby',        // Woo sort
        'min_price',      // price filter
        'max_price',
        'rating_filter',
        's',              // internal search
        'post_type',
        'product-page',   // ajax paginate param some filter plugins add
        'add-to-cart',
    ];

    public static function register(): void
    {
        $self = new self();

        // Crawl control — runs on every front-end request head.
        add_action('wp_head', [$self, 'maybe_print_noindex'], 1);

        // Status codes for gone products (#13/#20).
        add_action('template_redirect', [$self, 'handle_gone_product']);

        if (class_exists('WooCommerce')) {
            // LCP: eager-load the first PDP gallery image (#15).
            add_filter('wp_get_attachment_image_attributes', [$self, 'prioritize_lcp_image'], 10, 3);

            // Trim Woo asset bloat off non-Woo pages (#16).
            add_action('wp_enqueue_scripts', [$self, 'dequeue_woo_assets_off_shop'], 99);
        }
    }

    /**
     * #1 / #12 — emit robots noindex on faceted + utility URLs.
     *
     * Rank Math owns the robots meta on canonical URLs; we only ADD noindex
     * when a URL carries a facet param or is a non-indexable Woo endpoint, and
     * only when Rank Math has not already printed a noindex (avoid double tags).
     */
    public function maybe_print_noindex(): void
    {
        if (is_admin() || is_robots() || is_feed()) {
            return;
        }

        if (! $this->is_noindex_url()) {
            return;
        }

        // ponytail: skip if Rank Math already decided noindex — its filter is
        // the source of truth on canonical URLs, we only cover the query-param
        // case it doesn't see. If RM is off, this is the sole robots tag.
        if (has_filter('rank_math/frontend/robots') && $this->rank_math_says_noindex()) {
            return;
        }

        echo '<meta name="robots" content="noindex,follow" data-src="child-seo" />' . "\n";
    }

    /**
     * Faceted params + Woo utility pages that must never be indexed.
     */
    public function is_noindex_url(): bool
    {
        if (self::params_are_faceted($_GET)) { // phpcs:ignore WordPress.Security.NonceVerification
            return true;
        }

        if (function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page())) {
            return true;
        }

        if (function_exists('is_search') && is_search()) {
            return true;
        }

        return (bool) apply_filters('underscores_seo_is_noindex_url', false);
    }

    /**
     * Pure predicate: does this query-arg map represent a faceted / filtered
     * URL that must not be indexed? Static + WP-free so it is unit-testable
     * (see scripts/seo-noindex-check.php).
     *
     * @param array<string,mixed> $get
     */
    public static function params_are_faceted(array $get): bool
    {
        foreach (self::FACET_PARAMS as $param) {
            if (isset($get[$param]) && $get[$param] !== '') {
                return true;
            }
        }

        foreach (array_keys($get) as $key) {
            $key = (string) $key;
            if (str_starts_with($key, 'filter_') || str_starts_with($key, 'query_type_')) {
                return true;
            }
        }

        return false;
    }

    private function rank_math_says_noindex(): bool
    {
        $robots = apply_filters('rank_math/frontend/robots', []);

        return is_array($robots) && isset($robots['index']) && $robots['index'] === 'noindex';
    }

    /**
     * #13 / #20 — a product that is out of stock AND flagged discontinued, or
     * a trashed/private product hit by URL, should return a deliberate status
     * code, not a soft 200 and not a redirect-everything-to-home.
     *
     * Editors control the outcome per product via ACF `seo_gone_action`:
     *   'keep'      → do nothing (default; still buyable when back in stock)
     *   'redirect'  → 301 to ACF `seo_redirect_url` (successor product)
     *   'gone'      → 410 Gone (permanently discontinued)
     */
    public function handle_gone_product(): void
    {
        if (! function_exists('is_product') || ! is_product()) {
            return;
        }

        $product_id = get_queried_object_id();

        if (! $product_id || ! function_exists('get_field')) {
            return;
        }

        $action = (string) (get_field('seo_gone_action', $product_id) ?: 'keep');

        if ($action === 'redirect') {
            $target = esc_url_raw((string) get_field('seo_redirect_url', $product_id));
            if ($target) {
                wp_safe_redirect($target, 301);
                exit;
            }
            return;
        }

        if ($action === 'gone') {
            status_header(410);
            nocache_headers();
            // Let the theme render a normal "gone" body; 410 is enough for bots.
            // ponytail: reuse the 404 template for the body — no separate 410.php.
            // Add one only if the gone page needs different copy than 404.
            get_template_part(404);
            exit;
        }
    }

    /**
     * #15 — mark the first single-product gallery image as the LCP element so
     * the browser fetches it immediately (never lazy).
     *
     * @param array<string,string> $attr
     * @param \WP_Post             $attachment
     * @param string|int[]         $size
     * @return array<string,string>
     */
    public function prioritize_lcp_image(array $attr, $attachment, $size): array
    {
        static $done = false;

        if ($done || is_admin() || ! function_exists('is_product') || ! is_product()) {
            return $attr;
        }

        global $product;

        if (! $product || ! is_object($product)) {
            return $attr;
        }

        // Only the product's own featured image is the LCP candidate.
        if ((int) $product->get_image_id() !== (int) $attachment->ID) {
            return $attr;
        }

        $done                 = true;
        $attr['loading']      = 'eager';
        $attr['fetchpriority'] = 'high';
        unset($attr['decoding']); // let it decode sync as the hero

        return $attr;
    }

    /**
     * #16 — WooCommerce enqueues its stylesheets + blocks/select2 bundles on
     * every page by default. Drop them everywhere except actual Woo pages and
     * the front page (which shows a product grid).
     */
    public function dequeue_woo_assets_off_shop(): void
    {
        $is_woo = (function_exists('is_woocommerce') && is_woocommerce())
            || (function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page()))
            || is_front_page();

        if (apply_filters('underscores_seo_keep_woo_assets', $is_woo)) {
            return;
        }

        foreach (['woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen', 'wc-blocks-style', 'select2'] as $handle) {
            wp_dequeue_style($handle);
        }

        foreach (['woocommerce', 'wc-cart-fragments', 'wc-add-to-cart', 'selectWoo', 'wc-single-product'] as $handle) {
            wp_dequeue_script($handle);
        }
    }
}
