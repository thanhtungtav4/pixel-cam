<?php

/**
 * Product loop item — Pixel Cam.
 *
 * Overrides Woo's default loop item to reuse the shared product-card partial,
 * so shop archive and the home "best sellers" grid look identical.
 *
 * LCP: first item on page 1 is the archive LCP candidate → load its image
 * eager + fetchpriority=high. We track the index with a static counter
 * because wc_get_loop_prop('loop') is unreliable in some Woo pagination
 * paths (returns 0 for every product in the current setup).
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

global $product;

if (! is_a($product, WC_Product::class) || ! $product->is_visible()) {
    return;
}

// Reset the counter on page 1 of the main query so the first card on a new
// archive page (paged) becomes the LCP candidate. Filter is hit once per
// loop start, before any product renders.
add_action('woocommerce_product_loop_start', static function () {
    static $loop_id = 0;
    $loop_id++;
    $GLOBALS['pxc_loop_seen'][$loop_id] = 0;
    $GLOBALS['pxc_loop_current'] = $loop_id;
}, 1);

// Bump the per-loop counter for this product. First time we see a loop id
// (loop start) is index 0; first product in the first loop on page 1 = LCP.
if (! isset($GLOBALS['pxc_loop_seen']) || ! is_array($GLOBALS['pxc_loop_seen'])) {
    $GLOBALS['pxc_loop_seen'] = [];
}
$current_loop = (int) ($GLOBALS['pxc_loop_current'] ?? 1);
$index_in_loop = (int) ($GLOBALS['pxc_loop_seen'][$current_loop] ?? -1) + 1;
$GLOBALS['pxc_loop_seen'][$current_loop] = $index_in_loop;

$on_first_page = (int) max(1, (int) get_query_var('paged')) === 1;
$is_first      = ($index_in_loop === 0) && $on_first_page;

get_template_part('partials/components/product-card', null, [
    'product' => $product,
    'eager'   => $is_first,
]);
