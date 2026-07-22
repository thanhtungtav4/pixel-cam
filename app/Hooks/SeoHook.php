<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * Technical SEO hooks. Rank Math owns product metadata/schema/redirections;
 * this class only handles faceted archives and reuses Rank Math's primary
 * product category for the Woo breadcrumb/permalink when configured.
 */
final class SeoHook
{
    public static function register(): void
    {
        $self = new self();
        add_filter('woocommerce_breadcrumb_links', [$self, 'primary_category_breadcrumb']);
        add_filter('post_type_link', [$self, 'primary_category_permalink'], 10, 2);

        // Faceted URLs (filter / sort / price / rating / search) must not be
        // indexed and should canonicalize to the clean archive.
        add_filter('rank_math/frontend/robots', [$self, 'faceted_robots']);
        add_filter('rank_math/frontend/canonical', [$self, 'faceted_canonical']);
        add_filter('rank_math/json_ld', [$self, 'enrich_organization_schema'], 120, 2);
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
        if (! $this->is_faceted()) {
            return $canonical;
        }
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

    /**
     * Rank Math's free Organization entity omits its canonical URL. Add the
     * missing property without duplicating or replacing plugin-owned schema.
     *
     * @param array<string,array<string,mixed>> $data
     * @return array<string,array<string,mixed>>
     */
    public function enrich_organization_schema(array $data, $jsonld): array
    {
        foreach ($data as $key => $entity) {
            $types = (array) ($entity['@type'] ?? []);
            if (! in_array('Organization', $types, true)) {
                continue;
            }

            $data[$key]['url'] = home_url('/');
        }

        return $data;
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

    public function primary_category_breadcrumb(array $crumbs): array
    {
        if (! is_singular('product')) {
            return $crumbs;
        }
        $primary = (int) get_post_meta(get_queried_object_id(), 'rank_math_primary_product_cat', true);
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
        if (strpos($permalink, '%product_cat%') === false) {
            return $permalink;
        }
        $primary = (int) get_post_meta($post->ID, 'rank_math_primary_product_cat', true);
        if ($primary <= 0) {
            return $permalink;
        }
        $term = get_term($primary, 'product_cat');
        if ($term && ! is_wp_error($term)) {
            return str_replace('%product_cat%', $term->slug, $permalink);
        }
        return $permalink;
    }

}
