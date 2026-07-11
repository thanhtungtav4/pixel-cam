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

        <?php
        // Wishlist toggle — icon-only .wish button matching the design. We do NOT
        // add YITH's .add_to_wishlist class (its JS injects an extra text label +
        // icon we don't want). Instead the button carries YITH's add URL and theme
        // JS posts to it, so the heart stays the only visible element.
        $has_yith    = defined('YITH_WCWL') && function_exists('YITH_WCWL');
        $in_wishlist = $has_yith && YITH_WCWL()->is_product_in_wishlist($product_id);
        $add_url     = $has_yith ? add_query_arg('add_to_wishlist', $product_id, get_permalink($product_id)) : '';
        ?>
        <button
            class="wish<?php echo $in_wishlist ? ' on' : ''; ?>"
            type="button"
            data-product-id="<?php echo esc_attr((string) $product_id); ?>"
            <?php if ($add_url) : ?>data-add-url="<?php echo esc_url($add_url); ?>"<?php endif; ?>
            aria-label="<?php esc_attr_e('Thêm vào yêu thích', 'underscores'); ?>"
            aria-pressed="<?php echo $in_wishlist ? 'true' : 'false'; ?>">
            <svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8z"/></svg>
        </button>

        <?php echo $product->get_image('pxc_card', $image_attr); ?>
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
        // Add-to-cart: Woo's loop button reads the GLOBAL $product, but this card
        // is also rendered from the front page / internal-links partials where that
        // global may be a different product or unset. Point it at this card's
        // product for the call, then restore. Keeps Woo's AJAX classes intact so
        // the add is ajax + refreshes the mini-cart fragment.
        $previous_product = $GLOBALS['product'] ?? null;
        $GLOBALS['product'] = $product; // $product here = this card's WC_Product
        woocommerce_template_loop_add_to_cart();
        $GLOBALS['product'] = $previous_product;
        ?>
    </div>
</article>
