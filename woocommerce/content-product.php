<?php

/**
 * Product loop item — Pixel Cam.
 *
 * Overrides Woo's default loop item to reuse the shared product-card partial,
 * so shop archive and the home "best sellers" grid look identical.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

global $product;

if (! is_a($product, WC_Product::class) || ! $product->is_visible()) {
    return;
}

// First item on page 1 is the archive LCP candidate → load its image eager.
$loop_index = (int) wc_get_loop_prop('loop');
$is_first   = $loop_index === 0 && (int) max(1, (int) get_query_var('paged')) === 1;

get_template_part('partials/components/product-card', null, [
    'product' => $product,
    'eager'   => $is_first,
]);
