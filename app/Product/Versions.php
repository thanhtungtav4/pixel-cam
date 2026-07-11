<?php

declare(strict_types=1);

namespace Theme\Child\Product;

defined('ABSPATH') || exit;

/**
 * Product "versions" — sibling products that are storage/config variants of the
 * same line, each with its own URL (like CellphoneS: 256GB / 512GB / 1TB).
 *
 * Links are two-way: if product A lists B in its `product_versions`, then B also
 * shows A — even if B didn't list it. We union:
 *   - the current product's own `product_versions` (forward)
 *   - every product that lists the current one (reverse, via meta LIKE)
 *   - the current product itself
 *
 * Result is a de-duplicated, published list ordered as entered where possible.
 */
final class Versions
{
    private const META_KEY = 'product_versions';

    /**
     * Register cache invalidation (call once from bootstrap).
     */
    public static function register(): void
    {
        $bump = static fn() => underscores_child_bump_cache_version('versions');
        add_action('save_post_product', $bump);
        add_action('deleted_post', $bump);
    }

    /**
     * All version siblings (including $product_id itself), as product IDs.
     * Cached per product in a versioned transient (reverse query is a postmeta
     * LIKE scan — don't run it on every PDP view).
     *
     * @return list<int>
     */
    public static function siblings(int $product_id): array
    {
        static $memo = [];
        if (isset($memo[$product_id])) {
            return $memo[$product_id];
        }

        return $memo[$product_id] = underscores_child_versioned_cache(
            'versions',
            (string) $product_id,
            static fn(): array => self::compute_siblings($product_id)
        );
    }

    /**
     * @return list<int>
     */
    private static function compute_siblings(int $product_id): array
    {
        $ids = [];

        // Forward: this product's own list.
        $own = get_post_meta($product_id, self::META_KEY, true);
        if (is_array($own)) {
            foreach ($own as $id) {
                $ids[] = (int) $id;
            }
        }

        // Reverse: products that list THIS product in their versions meta.
        // ACF stores relationships as a serialized array of STRINGS, e.g.
        //   a:2:{i:0;s:3:"241";i:1;s:2:"22";}
        // so the value token is  s:LEN:"ID";  — match that exact token so "22"
        // never accidentally matches "221".
        global $wpdb;
        $token = '%s:' . strlen((string) $product_id) . ':"' . $product_id . '";%';
        $reverse = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s",
                self::META_KEY,
                $token
            )
        );
        foreach ($reverse as $parent_id) {
            $parent_id = (int) $parent_id;
            $ids[] = $parent_id;
            // Transitive: pull in the parent's whole list too, so the group is
            // complete from any member (A lists [B,C,D] → B also sees C,D).
            $parent_list = get_post_meta($parent_id, self::META_KEY, true);
            if (is_array($parent_list)) {
                foreach ($parent_list as $sib) {
                    $ids[] = (int) $sib;
                }
            }
        }

        // Include self.
        $ids[] = $product_id;

        // Publish-only + de-dupe, keep first-seen order.
        $seen = [];
        $out  = [];
        foreach ($ids as $id) {
            if ($id <= 0 || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            if (get_post_status($id) === 'publish' && get_post_type($id) === 'product') {
                $out[] = $id;
            }
        }

        // Only meaningful when there's more than one version.
        return count($out) > 1 ? $out : [];
    }

    /** Short label for a product's version chip (ACF version_label, fallback title). */
    public static function label(int $product_id): string
    {
        $label = function_exists('get_field') ? (string) (get_field('version_label', $product_id) ?: '') : '';
        return $label !== '' ? $label : get_the_title($product_id);
    }

    /** Group heading (from the current product's ACF, fallback "Phiên bản"). */
    public static function group_label(int $product_id): string
    {
        $g = function_exists('get_field') ? (string) (get_field('version_group_label', $product_id) ?: '') : '';
        return $g !== '' ? $g : __('Phiên bản', 'underscores');
    }

    /**
     * Render-ready chip data for the current product's version group.
     * Primes post caches once (avoids N lazy queries), and only builds the
     * WC_Product / price when actually needed.
     *
     * @return list<array{id:int,label:string,url:string,price:string,current:bool}>
     */
    public static function chips(int $current): array
    {
        $ids = self::siblings($current);
        if (empty($ids)) {
            return [];
        }

        // One batched fetch for all sibling posts → get_permalink / meta hit cache.
        _prime_post_caches($ids, false, true);

        $out = [];
        foreach ($ids as $id) {
            $product = wc_get_product($id);
            if (! $product) {
                continue;
            }
            $out[] = [
                'id'      => $id,
                'label'   => self::label($id),
                'url'     => (string) get_permalink($id),
                'price'   => (string) $product->get_price_html(),
                'current' => $id === $current,
            ];
        }
        return $out;
    }
}
