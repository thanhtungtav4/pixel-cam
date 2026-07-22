<?php

/**
 * Front page — Best-selling products.
 *
 * Mode:
 *   auto   → top sellers by total_sales (count configurable)
 *   manual → hand-picked products (ACF relationship)
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

if (! class_exists('WooCommerce')) {
    return;
}

$heading = $args['heading'] ?? '';
$link    = underscores_child_acf_link($args['link'] ?? []);
$mode    = $args['mode'] ?? 'auto';
$count   = max(1, (int) ($args['count'] ?? 6));
$manual  = $args['manual_products'] ?? [];

if ($mode === 'manual') {
    $product_ids = array_map('intval', (array) $manual);
} else {
    $product_ids = wc_get_products([
        'status'   => 'publish',
        'limit'    => $count,
        'orderby'  => 'meta_value_num',
        'meta_key' => 'total_sales',
        'order'    => 'DESC',
        'return'   => 'ids',
    ]);
}

if (empty($product_ids)) {
    return;
}
?>
<section class="section--flush"><div class="wrap">
    <?php if ($heading || $link) : ?>
        <div class="sec-head">
            <?php if ($heading) : ?><h2><?php echo esc_html($heading); ?></h2><?php endif; ?>
            <?php if ($link) : ?><a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['title']); ?></a><?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="grid" id="productGrid">
        <?php foreach ($product_ids as $i => $product_id) {
            get_template_part('partials/components/product-card', null, [
                'product' => $product_id,
                'eager'   => $i === 0, // first card = LCP candidate
            ]);
        } ?>
    </div>
</div></section>
