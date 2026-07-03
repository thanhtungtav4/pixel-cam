<?php

/**
 * PDP internal-link blocks (#11): same material + related blog posts.
 *
 * Same-category is already covered by Woo's core related products
 * (woocommerce_output_related_products), so this partial only adds the
 * links Woo does NOT: products sharing the material attribute, and blog
 * posts sharing a category slug with the product's categories.
 *
 * Every link is a real <a href> (crawlable). Blocks self-hide when empty —
 * no invented content.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

if (! function_exists('wc_get_product')) {
    return;
}

$product = $args['product'] ?? ($GLOBALS['product'] ?? null);
if (is_numeric($product)) {
    $product = wc_get_product((int) $product);
}
if (! $product instanceof WC_Product) {
    return;
}

$product_id = $product->get_id();

/* ---- Same material -------------------------------------------------------
 * Prefer the Woo attribute pa_material; fall back to ACF feed_material text.
 */
$material_ids = [];
$material_terms = get_the_terms($product_id, 'pa_material');
if (! is_wp_error($material_terms) && ! empty($material_terms)) {
    $material_ids = wp_list_pluck($material_terms, 'term_id');
}

$same_material = [];
if ($material_ids) {
    $same_material = wc_get_products([
        'status'   => 'publish',
        'limit'    => 4,
        'exclude'  => [$product_id],
        'orderby'  => 'rand',
        'tax_query' => [[
            'taxonomy' => 'pa_material',
            'field'    => 'term_id',
            'terms'    => $material_ids,
        ]],
    ]);
}

/* ---- Related blog posts --------------------------------------------------
 * Posts in a category whose slug matches one of the product's category slugs
 * (e.g. product cat "may-anh-sony" ↔ blog cat "may-anh-sony"). Cheap, no
 * mapping table — relies on shared slugs. Ceiling: if editors don't align
 * slugs, this returns nothing (block hides). Upgrade: an ACF post relation.
 */
$related_posts = [];
$product_cat_slugs = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'slugs']);
if (! is_wp_error($product_cat_slugs) && $product_cat_slugs) {
    $related_posts = get_posts([
        'post_type'        => 'post',
        'posts_per_page'   => 3,
        'ignore_sticky_posts' => true,
        'no_found_rows'    => true,
        'category_name'    => implode(',', $product_cat_slugs),
    ]);
}

if (empty($same_material) && empty($related_posts)) {
    return;
}
?>
<section class="wrap pdp-internal-links">
    <?php if (! empty($same_material)) : ?>
        <div class="il-block il-material">
            <h2><?php esc_html_e('Cùng chất liệu', 'underscores'); ?></h2>
            <div class="grid">
                <?php foreach ($same_material as $item) :
                    get_template_part('partials/components/product-card', null, ['product' => $item]);
                endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (! empty($related_posts)) : ?>
        <div class="il-block il-posts">
            <h2><?php esc_html_e('Bài viết liên quan', 'underscores'); ?></h2>
            <div class="grid">
                <?php foreach ($related_posts as $post_item) :
                    get_template_part('partials/components/post-card', null, ['post_id' => $post_item->ID]);
                endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
