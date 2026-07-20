<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * Per-category product filters (minimal first pass).
 *
 * Each product category can pick which WooCommerce product attributes (pa_*)
 * appear as filters + whether to show a price range. Filtering itself is
 * handled by WooCommerce core layered nav (?filter_pa_x=, ?min_price=), so
 * this hook only:
 *   - adds the ACF picker on the product_cat term (choices = live pa_* list),
 *   - renders the matching sidebar on that category archive.
 *
 * Reuses the existing .filters / .fgroup / .fopt CSS. Reload-based (URL param),
 * which SeoHook already flags noindex,follow — no index bloat.
 *
 * Not yet: parent→child inheritance, rating filter, price presets, AJAX.
 */
final class FilterHook
{
    private const META_ATTRS = 'pxc_filter_attributes'; // list<string> of pa_* taxonomy names
    private const META_PRICE = 'pxc_filter_price';       // '1' | ''

    public static function register(): void
    {
        if (! class_exists('WooCommerce')) {
            return;
        }
        $self = new self();

        // ACF choices are dynamic (live attribute list) → populate at load time.
        add_filter('acf/load_field/key=field_pxc_filter_attributes', [$self, 'load_attribute_choices']);

        // Bust the cached filter term lists when the catalog changes.
        $flush = static fn() => underscores_child_bump_cache_version('filter_terms');
        add_action('save_post_product', $flush);
        add_action('woocommerce_product_set_stock_status', $flush);
        add_action('deleted_post', $flush);

        // Bust the per-term config cache when a product_cat term is edited or
        // its meta is updated (admin changes the filter config).
        $flush_term_config = static fn() => underscores_child_bump_cache_version('filter_term_config');
        add_action('created_term', $flush_term_config);
        add_action('edited_term', $flush_term_config);
        add_action('deleted_term', $flush_term_config);
        add_action('updated_term_meta', $flush_term_config);
        add_action('added_term_meta', $flush_term_config);
        add_action('deleted_term_meta', $flush_term_config);

        // "Đang lọc" chips above the grid (before result count @20).
        add_action('woocommerce_before_shop_loop', [$self, 'render_active_chips'], 17);
    }

    /**
     * Cached term list (name/slug/count) for an attribute — avoids a get_terms()
     * query on every shop request. Versioned transient (group 'filter_terms').
     *
     * @return list<array{name:string,slug:string,count:int}>
     */
    private function get_filter_terms(string $taxonomy): array
    {
        return underscores_child_versioned_cache('filter_terms', $taxonomy, static function () use ($taxonomy): array {
            $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
            $out   = [];
            if (! is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $out[] = ['name' => $term->name, 'slug' => $term->slug, 'count' => (int) $term->count];
                }
            }
            return $out;
        });
    }

    /* ------------------------------------------------------------------ *
     * ACF: available attribute choices
     * ------------------------------------------------------------------ */

    /**
     * @param array<string,mixed> $field
     * @return array<string,mixed>
     */
    public function load_attribute_choices(array $field): array
    {
        $field['choices'] = [];
        if (! function_exists('wc_get_attribute_taxonomies')) {
            return $field;
        }
        foreach (wc_get_attribute_taxonomies() as $tax) {
            $name              = wc_attribute_taxonomy_name($tax->attribute_name); // pa_xxx
            $field['choices'][$name] = $tax->attribute_label ?: $tax->attribute_name;
        }
        return $field;
    }

    /* ------------------------------------------------------------------ *
     * Config for the current category
     * ------------------------------------------------------------------ */

    /**
     * @return array{attributes:list<string>,price:bool}|null  null = no config.
     */
    public function config_for_current(): ?array
    {
        if (! is_product_taxonomy()) {
            return null;
        }
        $term = get_queried_object();
        if (! $term instanceof \WP_Term) {
            return null;
        }

        // Cached per term: term_meta reads are 2 separate queries each request
        // for every category page. Bump the cache group on taxonomy edits via
        // `created_term` / `edited_term` to keep this honest.
        return underscores_child_versioned_cache(
            'filter_term_config',
            't_' . $term->term_id,
            function () use ($term): ?array {
                $attrs = get_term_meta($term->term_id, self::META_ATTRS, true);
                $attrs = is_array($attrs) ? array_values(array_filter(array_map('strval', $attrs))) : [];
                $price = (string) get_term_meta($term->term_id, self::META_PRICE, true) === '1';

                if (empty($attrs) && ! $price) {
                    return null;
                }
                return ['attributes' => $attrs, 'price' => $price];
            }
        );
    }

    public function has_config(): bool
    {
        return $this->config_for_current() !== null;
    }

    /* ------------------------------------------------------------------ *
     * Render
     * ------------------------------------------------------------------ */

    public function render_sidebar(): void
    {
        $config = $this->config_for_current();
        if (! $config) {
            return;
        }

        foreach ($config['attributes'] as $taxonomy) {
            $this->render_attribute_group($taxonomy);
        }
        if ($config['price']) {
            $this->render_price_group();
        }
        $this->render_reset();
    }

    private function render_attribute_group(string $taxonomy): void
    {
        if (! taxonomy_exists($taxonomy)) {
            return;
        }
        $terms = $this->get_filter_terms($taxonomy);
        if (empty($terms)) {
            return;
        }

        $tax_obj = get_taxonomy($taxonomy);
        $label   = $tax_obj ? $tax_obj->labels->singular_name : $taxonomy;

        // Woo's chosen layered-nav values for this attribute: ?filter_pa_x=a,b
        $param   = 'filter_' . $taxonomy;
        $chosen  = isset($_GET[$param]) ? array_filter(explode(',', sanitize_text_field(wp_unslash((string) $_GET[$param])))) : []; // phpcs:ignore WordPress.Security.NonceVerification

        echo '<div class="fgroup"><h5>' . esc_html($label) . '</h5>';
        foreach ($terms as $term) {
            $is_on = in_array($term['slug'], $chosen, true);
            $url   = $this->toggle_url($param, $term['slug'], $chosen);
            printf(
                '<a class="fopt%s" href="%s" rel="nofollow"><span class="fopt-box" aria-hidden="true"></span>%s <span class="ct">%d</span></a>',
                $is_on ? ' on' : '',
                esc_url($url),
                esc_html($term['name']),
                (int) $term['count']
            );
        }
        echo '</div>';
    }

    private function render_price_group(): void
    {
        $min = isset($_GET['min_price']) ? (int) $_GET['min_price'] : ''; // phpcs:ignore WordPress.Security.NonceVerification
        $max = isset($_GET['max_price']) ? (int) $_GET['max_price'] : ''; // phpcs:ignore WordPress.Security.NonceVerification

        echo '<form class="fgroup fgroup-price" method="get">';
        echo '<h5>' . esc_html__('Khoảng giá', 'underscores') . '</h5>';
        // Preserve other active query params (attributes, orderby) on submit.
        foreach ($this->preserved_hidden_inputs(['min_price', 'max_price', 'paged']) as $hidden) {
            echo $hidden;
        }
        echo '<div class="price-row">'
            . '<input type="number" name="min_price" inputmode="numeric" min="0" placeholder="' . esc_attr__('Từ', 'underscores') . '" value="' . esc_attr((string) $min) . '" aria-label="' . esc_attr__('Giá từ', 'underscores') . '">'
            . '<span>—</span>'
            . '<input type="number" name="max_price" inputmode="numeric" min="0" placeholder="' . esc_attr__('Đến', 'underscores') . '" value="' . esc_attr((string) $max) . '" aria-label="' . esc_attr__('Giá đến', 'underscores') . '">'
            . '</div>';
        echo '<button type="submit" class="button filter-apply">' . esc_html__('Áp dụng', 'underscores') . '</button>';
        echo '</form>';
    }

    /**
     * "Đang lọc" chips above the grid: one removable chip per active filter
     * value + price range, plus a "clear all". aria-live so screen readers hear
     * the result set changed after an AJAX filter.
     */
    public function render_active_chips(): void
    {
        if (! $this->has_config()) {
            return;
        }

        $chips = [];

        // Attribute values.
        foreach (array_keys($_GET) as $key) { // phpcs:ignore WordPress.Security.NonceVerification
            $key = (string) $key;
            if (! str_starts_with($key, 'filter_')) {
                continue;
            }
            $taxonomy = substr($key, strlen('filter_'));
            if (! taxonomy_exists($taxonomy)) {
                continue;
            }
            $slugs = array_filter(explode(',', sanitize_text_field(wp_unslash((string) $_GET[$key])))); // phpcs:ignore WordPress.Security.NonceVerification
            foreach ($slugs as $slug) {
                $term = get_term_by('slug', $slug, $taxonomy);
                if (! $term) {
                    continue;
                }
                $remaining = array_values(array_diff($slugs, [$slug]));
                $url = $remaining
                    ? add_query_arg($key, implode(',', $remaining), remove_query_arg(['paged'], $this->current_url()))
                    : remove_query_arg([$key, 'paged'], $this->current_url());
                $chips[] = ['label' => $term->name, 'url' => $url];
            }
        }

        // Price range.
        $min = isset($_GET['min_price']) ? (int) $_GET['min_price'] : 0; // phpcs:ignore WordPress.Security.NonceVerification
        $max = isset($_GET['max_price']) ? (int) $_GET['max_price'] : 0; // phpcs:ignore WordPress.Security.NonceVerification
        if ($min || $max) {
            $label = ($min ? number_format_i18n($min) . 'đ' : '0')
                . ' — ' . ($max ? number_format_i18n($max) . 'đ' : '∞');
            $chips[] = [
                'label' => $label,
                'url'   => remove_query_arg(['min_price', 'max_price', 'paged'], $this->current_url()),
            ];
        }

        if (empty($chips)) {
            return;
        }

        echo '<div class="active-filters" aria-live="polite">';
        echo '<span class="af-label">' . esc_html__('Đang lọc:', 'underscores') . '</span>';
        foreach ($chips as $chip) {
            printf(
                '<a class="af-chip" href="%s" rel="nofollow">%s<span class="af-x" aria-hidden="true">×</span></a>',
                esc_url($chip['url']),
                esc_html($chip['label'])
            );
        }
        $base = get_term_link(get_queried_object());
        if (! is_wp_error($base)) {
            echo '<a class="af-clear" href="' . esc_url($base) . '">' . esc_html__('Xóa tất cả', 'underscores') . '</a>';
        }
        echo '</div>';
    }

    private function render_reset(): void
    {
        // Any active filter param? Show a clear link back to the bare category.
        $has_active = false;
        foreach (array_keys($_GET) as $k) { // phpcs:ignore WordPress.Security.NonceVerification
            if (str_starts_with((string) $k, 'filter_') || in_array($k, ['min_price', 'max_price', 'rating_filter'], true)) {
                $has_active = true;
                break;
            }
        }
        if (! $has_active) {
            return;
        }
        $base = get_term_link(get_queried_object());
        if (is_wp_error($base)) {
            return;
        }
        echo '<div class="fgroup"><a class="filter-reset" href="' . esc_url($base) . '">' . esc_html__('Xóa bộ lọc', 'underscores') . '</a></div>';
    }

    /* ------------------------------------------------------------------ *
     * Helpers
     * ------------------------------------------------------------------ */

    /**
     * Build the URL that toggles $slug in the comma-list of $param, keeping
     * every other current query arg intact.
     *
     * @param list<string> $chosen
     */
    private function toggle_url(string $param, string $slug, array $chosen): string
    {
        $next = in_array($slug, $chosen, true)
            ? array_values(array_diff($chosen, [$slug]))
            : array_merge($chosen, [$slug]);

        $base = remove_query_arg(['paged'], $this->current_url());
        return $next
            ? add_query_arg($param, implode(',', $next), $base)
            : remove_query_arg($param, $base);
    }

    private function current_url(): string
    {
        $req = (string) ($_SERVER['REQUEST_URI'] ?? '');
        return home_url(wp_unslash($req));
    }

    /**
     * Hidden inputs mirroring current filter query args (so the price GET form
     * doesn't drop them). Skips the ones the form owns.
     *
     * @param list<string> $skip
     * @return list<string>
     */
    private function preserved_hidden_inputs(array $skip): array
    {
        $out = [];
        foreach ($_GET as $k => $v) { // phpcs:ignore WordPress.Security.NonceVerification
            $k = (string) $k;
            if (in_array($k, $skip, true) || is_array($v)) {
                continue;
            }
            $out[] = '<input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr(sanitize_text_field(wp_unslash((string) $v))) . '">';
        }
        return $out;
    }
}
