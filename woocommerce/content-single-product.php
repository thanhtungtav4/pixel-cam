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

if (! $product instanceof WC_Product) {
    return;
}

do_action('woocommerce_before_single_product');

if (post_password_required()) {
    echo get_the_password_form();
    return;
}

$product_id = $product->get_id();

$acf_fields = function_exists('get_field') ? [
    'install_text' => get_field('install_text', $product_id) ?: '',
    'gifts'        => get_field('gifts', $product_id) ?: [],
    'gifts_total'  => get_field('gifts_total', $product_id) ?: '',
    'stock_note'   => get_field('stock_note', $product_id) ?: '',
    'box_items'    => get_field('box_items', $product_id) ?: '',
] : [
    'install_text' => '',
    'gifts'        => [],
    'gifts_total'  => '',
    'stock_note'   => '',
    'box_items'    => '',
];
$install_text = $acf_fields['install_text'];
$gifts        = $acf_fields['gifts'];
$gifts_total  = $acf_fields['gifts_total'];
$stock_note   = $acf_fields['stock_note'];
$box_items    = trim((string) $acf_fields['box_items']);

$product_settings = function_exists('underscores_get_option') ? (underscores_get_option('product_section') ?: []) : [];
$perks            = $product_settings['perks'] ?? [];

$acf_fields = function_exists('get_fields') ? (get_fields($product_id) ?: []) : [];
$related_posts_is_show    = ! empty($acf_fields['related_posts_is_show']);
$related_products_is_show = ! empty($acf_fields['related_products_is_show']);
$related_posts            = ! empty($acf_fields['related_posts']) ? array_map('intval', (array) $acf_fields['related_posts']) : [];
$related_products         = ! empty($acf_fields['related_products']) ? array_map('intval', (array) $acf_fields['related_products']) : [];

// Brand (taxonomy) + gallery badge state (mirrors the card).
$brand_terms = get_the_terms($product_id, 'product_brand');
$brand       = (! is_wp_error($brand_terms) && ! empty($brand_terms)) ? $brand_terms[0]->name : '';
$sku         = $product->get_sku();

['class' => $badge_class, 'label' => $badge_label] = underscores_child_product_badge($product);
?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class('pdp-single', $product); ?>>

    <div class="wrap">
        <?php
        woocommerce_breadcrumb([
            'wrap_before' => '<nav class="crumb" aria-label="Breadcrumb">',
            'wrap_after'  => '</nav>',
            'delimiter'   => '<span class="sep">/</span>',
            'home'        => __('Trang chủ', 'underscores'),
        ]);
        ?>
    </div>

    <div class="wrap pdp-layout">
        <div class="pdp-gallery-col">
            <?php if ($badge_label) : ?>
                <div class="pdp-badges"><span class="bd <?php echo esc_attr($badge_class); ?>"><?php echo esc_html($badge_label); ?></span></div>
            <?php endif; ?>
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
            <?php if ($brand) : ?><span class="brand"><?php echo esc_html($brand); ?></span><?php endif; ?>
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
            // Gifts (@12), version chips (@15) and stock (@28) are hooked into
            // woocommerce_single_product_summary in WooProductHook, so they land in the
            // right order (before add-to-cart @30).
            do_action('woocommerce_single_product_summary');
            ?>

            <?php if ($install_text) : ?>
                <div class="install"><?php echo esc_html($install_text); ?></div>
            <?php endif; ?>

            <?php if ($box_items !== '') : ?>
                <div class="pdp-box">
                    <h3 class="pdp-box__title"><?php esc_html_e('Hộp sản phẩm bao gồm', 'underscores'); ?></h3>
                    <div class="pdp-box__body">
                        <?php
                        // apply_filters('the_content', ...) so the editor's HTML
                        // (ul/li, <strong>, <a>) renders properly. wp_kses_post
                        // strips anything dangerous (script, on*, etc).
                        echo wp_kses_post(apply_filters('the_content', $box_items));
                        ?>
                    </div>
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

            <?php
            // (No more "Hộp sản phẩm bao gồm" stub here — it's now rendered
            // above, between install_text and perks, as a wysiwyg block.)
            ?>
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

<?php if ($related_posts_is_show) : ?>
    <?php get_template_part('partials/components/post-related', null, [
        'post_id'        => $product_id,
        'selected_posts' => $related_posts,
    ]); ?>
<?php endif; ?>

<?php if ($related_products_is_show) : ?>
    <?php get_template_part('partials/components/post-related-products', null, [
        'post_id'           => $product_id,
        'selected_products' => $related_products,
    ]); ?>
<?php endif; ?>

<?php do_action('woocommerce_after_single_product'); ?>
