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

// Per-product index within the current loop. The loop counter is initialised
// once per loop by WooTemplateHook::loop_start() (the woocommerce_product_loop_start
// filter), so here we just bump it. First product in a loop = index 0.
$current_loop  = (int) ($GLOBALS['pxc_loop_current'] ?? 1);
$index_in_loop = (int) ($GLOBALS['pxc_loop_seen'][$current_loop] ?? -1) + 1;
$GLOBALS['pxc_loop_seen'][$current_loop] = $index_in_loop;

// LCP candidate = first card of the FIRST loop on page 1 of the archive.
$on_first_page = (int) max(1, (int) get_query_var('paged')) === 1;
$is_first      = ($index_in_loop === 0) && $current_loop === 1 && $on_first_page;

get_template_part('partials/components/product-card', null, [
    'product' => $product,
    'eager'   => $is_first,
]);
