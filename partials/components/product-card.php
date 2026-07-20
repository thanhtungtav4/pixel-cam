<?php

/**
 * Reusable product card (WooCommerce) — matches the Pixel Cam design card
 * (.pcard) exactly: badge + wishlist live inside .imgwrap, image sits in a
 * .ph aspect box, price uses .now/.old so the design CSS applies.
 *
 * @param array $args {
 *   'product' => WC_Product|int  Product or ID.
 *   'eager'   => bool            Mark this card's image as LCP. Caller decides;
 *                                pass true for the first card above the fold.
 *                                Default false → native lazy-load.
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

// Badge: sale % (falls back to "SALE") → HOT → MỚI. Card shows the "new" flag.
['class' => $badge_class, 'label' => $badge_label] = underscores_child_product_badge($product, true);

// LCP-aware image attrs. Non-eager cards keep native lazy-load (below fold).
$image_attr = $eager
    ? ['loading' => 'eager', 'fetchpriority' => 'high']
    : ['loading' => 'lazy'];

// Price → design's .now / .old.
// - Variable/grouped/other non-simple types: price is a RANGE ("Từ …"), so
//   defer to get_price_html() which handles ranges, tax, currency, and the
//   theme's save-badge filter. Rendered as a single .now block.
// - Simple products: build the two-price .now/.old design, using
//   wc_get_price_to_display() so incl/excl-tax shop setting is honored.
$is_simple_price = $product->is_type('simple') || $product->is_type('external');
if ($is_simple_price) {
    $price_now  = $product->get_price() !== '' ? wc_get_price_to_display($product) : '';
    $price_prev = $product->is_on_sale()
        ? wc_get_price_to_display($product, ['price' => $product->get_regular_price()])
        : '';
} else {
    $price_now  = '';
    $price_prev = '';
    $price_html = $product->get_price_html(); // range / tax / save-badge handled by Woo + theme filter
}

// Optional spec line (design ".spec"): sensor · mount from ACF, hidden if empty.
$spec_line = function_exists('get_field') ? (string) (get_field('card_spec', $product_id) ?: '') : '';
?>
<article class="pcard">
    <a class="imgwrap" href="<?php echo esc_url($permalink); ?>" aria-label="<?php echo esc_attr($name); ?>">
        <?php if ($badge_label) : ?>
            <div class="badges"><span class="bd <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($badge_label); ?></span></div>
        <?php endif; ?>

        <?php
        // Wishlist toggle — icon-only .wish button (shared helper). We do NOT add
        // YITH's .add_to_wishlist class (its JS injects extra label/icon); the
        // button carries YITH's add URL and theme JS posts to it.
        echo underscores_child_wishlist_button($product_id); // helper returns escaped markup
        ?>

        <?php echo $product->get_image('pxc_card', $image_attr); ?>
    </a>

    <div class="body">
        <?php if ($brand) : ?><span class="brand"><?php echo esc_html($brand); ?></span><?php endif; ?>
        <h4 class="name"><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($name); ?></a></h4>
        <?php if ($spec_line) : ?><span class="spec"><?php echo esc_html($spec_line); ?></span><?php endif; ?>

        <div class="price">
            <?php if ($is_simple_price) : ?>
                <?php if ($price_now !== '') : ?>
                    <span class="now"><?php echo wp_kses_post(wc_price((float) $price_now)); ?></span>
                <?php endif; ?>
                <?php if ($price_prev !== '') : ?>
                    <span class="old"><?php echo wp_kses_post(wc_price((float) $price_prev)); ?></span>
                <?php endif; ?>
            <?php elseif (! empty($price_html)) : ?>
                <span class="now"><?php echo wp_kses_post($price_html); ?></span>
            <?php endif; ?>
        </div>

        <?php
        // Add-to-cart: Woo's loop button reads the GLOBAL $product, but this
        // card is also rendered from the front page / internal-links partials
        // where that global may be a different product or unset. Use the
        // official Woo helper `wc_setup_product_data()` which sets up
        // $product, $post, and the WP post globals in one call, then restore
        // afterwards (in finally{} so a throwing callback doesn't leak the
        // card's product into the rest of the loop).
        $previous_post = $GLOBALS['post'] ?? null;
        wc_setup_product_data($product);
        try {
            woocommerce_template_loop_add_to_cart();
        } finally {
            if ($previous_post instanceof \WP_Post) {
                // Restore to the previous post (sets $post + $product to that
                // post's product if it's a product, or null otherwise).
                wc_setup_product_data($previous_post);
            } else {
                wp_reset_postdata();
            }
        }
        ?>
    </div>
</article>
