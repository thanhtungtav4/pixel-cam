<?php

/**
 * Single product content — Pixel Cam PDP layout.
 *
 * Keeps Woo core template tags for gallery / price / add-to-cart (so variations
 * and cart still work) but arranges them in the export's .pdp-layout, and adds
 * ACF-driven blocks: install line, gifts, box contents, and shop-wide perks.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

global $product;

do_action('woocommerce_before_single_product');

if (post_password_required()) {
    echo get_the_password_form();
    return;
}

$product_id = $product->get_id();

$install_text = function_exists('get_field') ? (get_field('install_text', $product_id) ?: '') : '';
$gifts        = function_exists('get_field') ? (get_field('gifts', $product_id) ?: []) : [];
$gifts_total  = function_exists('get_field') ? (get_field('gifts_total', $product_id) ?: '') : '';
$box_items    = function_exists('get_field') ? (get_field('box_items', $product_id) ?: []) : [];

$product_settings = function_exists('underscores_get_option') ? (underscores_get_option('product_section') ?: []) : [];
$perks            = $product_settings['perks'] ?? [];
?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class('pdp-single', $product); ?>>

    <?php woocommerce_breadcrumb(); ?>

    <div class="wrap pdp-layout">
        <div class="pdp-gallery-col">
            <?php
            /**
             * Woo gallery (thumbs + main image), sale flash.
             *
             * @hooked woocommerce_show_product_sale_flash - 10
             * @hooked woocommerce_show_product_images - 20
             */
            do_action('woocommerce_before_single_product_summary');
            ?>
        </div>

        <div class="pdp-info summary entry-summary">
            <?php
            /**
             * Title, rating, price, excerpt, add-to-cart, meta.
             *
             * @hooked woocommerce_template_single_title - 5
             * @hooked woocommerce_template_single_rating - 10
             * @hooked woocommerce_template_single_price - 10
             * @hooked woocommerce_template_single_excerpt - 20
             * @hooked woocommerce_template_single_add_to_cart - 30
             * @hooked woocommerce_template_single_meta - 40
             */
            do_action('woocommerce_single_product_summary');
            ?>

            <?php if ($install_text) : ?>
                <div class="install"><?php echo esc_html($install_text); ?></div>
            <?php endif; ?>

            <?php if (! empty($gifts)) : ?>
                <div class="pdp-gifts">
                    <div class="gift-head">
                        <b><?php esc_html_e('Quà tặng kèm', 'underscores'); ?></b>
                        <?php if ($gifts_total) : ?><span class="gift-total"><?php echo esc_html($gifts_total); ?></span><?php endif; ?>
                    </div>
                    <ul class="gift-list">
                        <?php foreach ($gifts as $gift) : ?>
                            <li><?php echo esc_html($gift['name'] ?? ''); ?><?php if (! empty($gift['value'])) : ?> <small>(<?php echo esc_html($gift['value']); ?>)</small><?php endif; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (! empty($perks)) : ?>
                <div class="pdp-perks">
                    <?php foreach ($perks as $perk) :
                        $icon = $perk['icon'] ?? 0;
                        ?>
                        <div class="perk">
                            <?php if ($icon) {
                                echo wp_get_attachment_image($icon, 'thumbnail');
                            } ?>
                            <div>
                                <?php if (! empty($perk['title'])) : ?><b><?php echo esc_html($perk['title']); ?></b><?php endif; ?>
                                <?php if (! empty($perk['desc'])) : ?><small><?php echo esc_html($perk['desc']); ?></small><?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (! empty($box_items)) : ?>
                <div class="pdp-box">
                    <div class="box-head"><b><?php esc_html_e('Hộp sản phẩm bao gồm', 'underscores'); ?></b></div>
                    <ul class="box-list">
                        <?php foreach ($box_items as $item) : ?>
                            <li><?php echo esc_html($item['text'] ?? ''); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="wrap">
        <?php
        /**
         * Tabs, upsells, related products.
         *
         * @hooked woocommerce_output_product_data_tabs - 10
         * @hooked woocommerce_upsell_display - 15
         * @hooked woocommerce_output_related_products - 20
         */
        do_action('woocommerce_after_single_product_summary');
        ?>
    </div>
</div>

<?php do_action('woocommerce_after_single_product'); ?>
