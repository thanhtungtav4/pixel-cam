<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * Technical SEO hooks:
 *   - #10  Primary product category → breadcrumb + permalink.
 *   - #13  Discontinued products: 410 Gone or 301 redirect (ACF driven).
 *   - #7   Product schema data (GTIN/MPN/shipping/return) → Rank Math.
 */
final class SeoHook
{
    public static function register(): void
    {
        $self = new self();
        add_action('template_redirect', [$self, 'handle_discontinued_product'], 5);
        add_filter('woocommerce_breadcrumb_links', [$self, 'primary_category_breadcrumb']);
        add_filter('post_type_link', [$self, 'primary_category_permalink'], 10, 2);
        add_filter('rank_math/snippet/rich_snippet_product_entities', [$self, 'enrich_product_schema']);

        // Faceted URLs (filter / sort / price / rating / search) must not be
        // indexed and should canonicalize to the clean archive.
        add_filter('rank_math/frontend/robots', [$self, 'faceted_robots']);
        add_filter('rank_math/frontend/canonical', [$self, 'faceted_canonical']);
        // Fallback when Rank Math is inactive: print the tags ourselves.
        add_action('wp_head', [$self, 'faceted_head_fallback'], 1);
    }

    /**
     * Query args that turn a clean archive into a non-indexable faceted variant.
     *
     * @var list<string>
     */
    // Note: 'paged' is intentionally NOT here — paginated archive pages (page 2+)
    // are real content and should stay indexable.
    private const FACET_PARAMS = ['orderby', 'min_price', 'max_price', 'rating_filter', 's', 'product-page', 'add-to-cart'];

    /**
     * Is the CURRENT request a faceted / filtered URL?
     * (filter_pa_*, layered-nav query_type_*, or any FACET_PARAM present.)
     */
    private function is_faceted(): bool
    {
        return self::params_are_faceted($_GET); // phpcs:ignore WordPress.Security.NonceVerification
    }

    /**
     * Pure predicate — WP-free, unit-testable (see bin/seo-faceted-check.php).
     *
     * @param array<string,mixed> $get
     */
    public static function params_are_faceted(array $get): bool
    {
        foreach (self::FACET_PARAMS as $p) {
            if (isset($get[$p]) && $get[$p] !== '') {
                return true;
            }
        }
        foreach (array_keys($get) as $k) {
            $k = (string) $k;
            if (str_starts_with($k, 'filter_') || str_starts_with($k, 'query_type_') || str_starts_with($k, 'attribute_')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Robots for the clean archive → follow but noindex all facets.
     *
     * @param array<string,string> $robots
     * @return array<string,string>
     */
    public function faceted_robots(array $robots): array
    {
        if ($this->is_faceted()) {
            $robots['index']  = 'noindex';
            $robots['follow'] = 'follow';
        }
        return $robots;
    }

    /**
     * Canonicalize a faceted URL back to its clean archive so link equity and
     * indexing consolidate on one URL.
     */
    public function faceted_canonical(string $canonical): string
    {
        $clean = $this->clean_archive_url();
        return $clean !== '' ? $clean : $canonical;
    }

    /**
     * If Rank Math (or any SEO plugin) is off, still emit robots + canonical for
     * faceted URLs so they never get indexed.
     */
    public function faceted_head_fallback(): void
    {
        if (defined('RANK_MATH_VERSION') || ! $this->is_faceted() || is_admin() || is_feed()) {
            return;
        }
        echo '<meta name="robots" content="noindex,follow" />' . "\n";
        $clean = $this->clean_archive_url();
        if ($clean !== '') {
            echo '<link rel="canonical" href="' . esc_url($clean) . '" />' . "\n";
        }
    }

    /** Clean archive URL for the current shop/category/tag (no query args). */
    private function clean_archive_url(): string
    {
        if (function_exists('is_shop') && is_shop()) {
            return (string) get_permalink(wc_get_page_id('shop'));
        }
        if (is_product_taxonomy() || is_category() || is_tag()) {
            $term = get_queried_object();
            if ($term instanceof \WP_Term) {
                $link = get_term_link($term);
                return is_wp_error($link) ? '' : (string) $link;
            }
        }
        // Single product with a selected variation (?attribute_*=) → the product's
        // own clean URL.
        if (function_exists('is_product') && is_product()) {
            return (string) get_permalink(get_queried_object_id());
        }
        return '';
    }

    /**
     * #13 — Discontinued: 301 redirect or 410 Gone before single product loads.
     */
    public function handle_discontinued_product(): void
    {
        if (! is_singular('product') || ! function_exists('get_field')) {
            return;
        }
        $product_id = (int) get_queried_object_id();
        if ($product_id <= 0) {
            return;
        }
        if (! (bool) get_field('discontinued', $product_id)) {
            return;
        }
        $redirect = (string) (get_field('redirect_url', $product_id) ?: '');
        if ($redirect !== '') {
            wp_safe_redirect(esc_url_raw($redirect), 301);
            exit;
        }
        // 410 Gone: status header is what crawlers honor. wp_die() lets
        // maintenance/security plugins still hook in (vs raw echo+exit), and
        // automatically applies the proper 410 status.
        status_header(410);
        nocache_headers();
        wp_die(
            wp_kses_post(
                '<p>' . esc_html__('Sản phẩm này đã ngừng kinh doanh. Vui lòng quay lại cửa hàng.', 'underscores') . '</p>'
                . '<p><a href="' . esc_url(home_url('/')) . '">' . esc_html__('Về trang chủ', 'underscores') . '</a></p>'
            ),
            esc_html__('Sản phẩm đã ngừng bán', 'underscores'),
            ['response' => 410]
        );
    }

    public function primary_category_breadcrumb(array $crumbs): array
    {
        if (! is_singular('product') || ! function_exists('get_field')) {
            return $crumbs;
        }
        $primary = (int) (get_field('primary_category', get_queried_object_id()) ?: 0);
        if ($primary <= 0) {
            return $crumbs;
        }
        $term = get_term($primary, 'product_cat');
        if (! $term || is_wp_error($term)) {
            return $crumbs;
        }
        $chain = [];
        $ancestors = array_reverse(get_ancestors($term->term_id, 'product_cat', 'taxonomy'));
        $ancestors[] = $term->term_id;
        foreach ($ancestors as $tid) {
            $t = get_term($tid, 'product_cat');
            if ($t && ! is_wp_error($t)) {
                $chain[] = [$t->name, get_term_link($t)];
            }
        }
        $shop_index = null;
        $shop_label = function_exists('wc_get_page_id') ? get_the_title(wc_get_page_id('shop')) : '';
        foreach ($crumbs as $i => $c) {
            if (is_array($c) && isset($c[0]) && $shop_label !== '' && $c[0] === $shop_label) {
                $shop_index = $i;
                break;
            }
        }
        if ($shop_index !== null && ! empty($chain)) {
            array_splice($crumbs, $shop_index + 1, count($crumbs) - $shop_index - 2, $chain);
        }
        return $crumbs;
    }

    public function primary_category_permalink(string $permalink, $post): string
    {
        if (! $post instanceof \WP_Post || $post->post_type !== 'product') {
            return $permalink;
        }
        if (strpos($permalink, '%product_cat%') === false || ! function_exists('get_field')) {
            return $permalink;
        }
        $primary = (int) (get_field('primary_category', $post->ID) ?: 0);
        if ($primary <= 0) {
            return $permalink;
        }
        $term = get_term($primary, 'product_cat');
        if ($term && ! is_wp_error($term)) {
            return str_replace('%product_cat%', $term->slug, $permalink);
        }
        return $permalink;
    }

    public function enrich_product_schema(array $entities): array
    {
        if (! function_exists('get_field') || ! is_singular('product')) {
            return $entities;
        }
        $product_id = (int) get_queried_object_id();
        if ($product_id <= 0) {
            return $entities;
        }
        $gtin = (string) (get_field('gtin', $product_id) ?: '');
        $mpn  = (string) (get_field('mpn', $product_id) ?: '');
        $ship = (string) (get_field('shipping_policy', $product_id) ?: '');
        $ret  = (string) (get_field('return_policy', $product_id) ?: '');
        foreach ($entities as &$entity) {
            if (! is_array($entity)) {
                continue;
            }
            $type = $entity['@type'] ?? '';
            $is_product = $type === 'Product' || (is_array($type) && in_array('Product', $type, true));
            if (! $is_product) {
                continue;
            }
            if ($gtin !== '') {
                $entity['gtin13'] = $gtin;
            }
            if ($mpn !== '') {
                $entity['mpn'] = $mpn;
            }
            if ($ship !== '') {
                $entity['shippingDetails'] = ['@type' => 'OfferShippingDetails', 'description' => $ship];
            }
            if ($ret !== '') {
                $entity['hasMerchantReturnPolicy'] = [
                    '@type'                => 'MerchantReturnPolicy',
                    'applicableCountry'    => 'VN',
                    'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                    'description'          => $ret,
                ];
            }
        }
        unset($entity);
        return $entities;
    }
}
