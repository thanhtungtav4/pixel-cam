<?php

/**
 * Reusable product card (WooCommerce) — matches the Pixel Cam design card
 * (.pcard) exactly: badge + wishlist live inside .imgwrap, image sits in a
 * .ph aspect box, price uses .now/.old so the design CSS applies.
 *
 * @param array $args {
 *   'product' => WC_Product|int  Product or ID.
 *   'eager'   => bool            Mark this card's image as LCP (first row only):
 *                                eager load + fetchpriority=high. Default false
 *                                → native lazy-load (below the fold).
 * }
 * @package Underscores
 */

defined('ABSPATH') || exit;

if (! function_exists('wc_get_product')) {
    return;
}

$product = $args['product'] ?? null;
if (is_numeric($product)) {
    $product = wc_get_product((int) $product);
}
if (! $product instanceof WC_Product) {
    return;
}

$product_id = $product->get_id();
$eager      = ! empty($args['eager']);
$permalink  = get_permalink($product_id);
$name       = $product->get_name();

$brand_terms = get_the_terms($product_id, 'product_brand');
$brand       = (! is_wp_error($brand_terms) && ! empty($brand_terms)) ? $brand_terms[0]->name : '';

// Badge: design shows a sale % (falls back to "SALE"), plus HOT / MỚI flags.
// Map Woo state → the design's .bd variants.
$badge_class = '';
$badge_label = '';
if ($product->is_on_sale()) {
    $regular = (float) $product->get_regular_price();
    $sale    = (float) $product->get_sale_price();
    $badge_class = 'sale';
    $badge_label = ($regular > 0 && $sale > 0 && $sale < $regular)
        ? '-' . (int) round((1 - $sale / $regular) * 100) . '%'
        : __('SALE', 'underscores');
} elseif ($product->is_featured()) {
    $badge_class = 'hot';
    $badge_label = 'HOT';
} elseif (($ts = get_post_time('U', true, $product_id)) && (time() - $ts) < 30 * DAY_IN_SECONDS) {
    $badge_class = 'new';
    $badge_label = __('MỚI', 'underscores');
}

// LCP-aware image attrs. Non-eager cards keep native lazy-load (below fold).
$image_attr = $eager
    ? ['loading' => 'eager', 'fetchpriority' => 'high']
    : ['loading' => 'lazy'];

// Price → design's .now / .old. Woo's get_price_html() carries its own markup
// the design CSS doesn't style, so build the two-price line ourselves.
$price_now  = $product->get_price();
$price_prev = $product->is_on_sale() ? $product->get_regular_price() : '';

// Optional spec line (design ".spec"): sensor · mount from ACF, hidden if empty.
$spec_line = function_exists('get_field') ? (string) (get_field('card_spec', $product_id) ?: '') : '';
?>
<article class="pcard">
    <a class="imgwrap" href="<?php echo esc_url($permalink); ?>" aria-label="<?php echo esc_attr($name); ?>">
        <?php if ($badge_label) : ?>
            <div class="badges"><span class="bd <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($badge_label); ?></span></div>
        <?php endif; ?>

        <?php if (defined('YITH_WCWL')) : ?>
            <?php echo do_shortcode('[yith_wcwl_add_to_wishlist product_id="' . $product_id . '"]'); ?>
        <?php else : ?>
            <button class="wish" type="button" data-product-id="<?php echo esc_attr((string) $product_id); ?>" aria-label="<?php esc_attr_e('Thêm vào yêu thích', 'underscores'); ?>" aria-pressed="false">
                <svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8z"/></svg>
            </button>
        <?php endif; ?>

        <span class="ph" data-label="<?php echo esc_attr(trim($brand . ' ' . $name)); ?>">
            <?php echo $product->get_image('woocommerce_thumbnail', $image_attr); ?>
        </span>
    </a>

    <div class="body">
        <?php if ($brand) : ?><span class="brand"><?php echo esc_html($brand); ?></span><?php endif; ?>
        <h4 class="name"><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($name); ?></a></h4>
        <?php if ($spec_line) : ?><span class="spec"><?php echo esc_html($spec_line); ?></span><?php endif; ?>

        <div class="price">
            <?php if ($price_now !== '') : ?>
                <span class="now"><?php echo wp_kses_post(wc_price((float) $price_now)); ?></span>
            <?php endif; ?>
            <?php if ($price_prev !== '') : ?>
                <span class="old"><?php echo wp_kses_post(wc_price((float) $price_prev)); ?></span>
            <?php endif; ?>
        </div>

        <?php
        woocommerce_template_loop_add_to_cart([
            'class' => 'addcart button',
        ]);
        ?>
    </div>
</article>
