<?php
/**
 * Related products section for a single blog post or product page.
 *
 * @param array $args {
 *   int   $post_id            Current post/product ID.
 *   int[] $selected_products  Manually selected product IDs (optional).
 * }
 * @package Underscores
 */

defined('ABSPATH') || exit;

if (! function_exists('wc_get_product')) {
    return;
}

$post_id = (int) ($args['post_id'] ?? get_the_ID());
if ($post_id <= 0) {
    return;
}

$selected = array_filter(array_map('intval', (array) ($args['selected_products'] ?? [])));
$products = [];

if (! empty($selected)) {
    $products = wc_get_products([
        'status'   => 'publish',
        'include'  => $selected,
        'limit'    => 4,
        'orderby'  => 'include',
    ]);
} elseif (get_post_type($post_id) === 'product') {
    // Auto: WooCommerce native related-products algorithm.
    $related_ids = function_exists('wc_get_related_products')
        ? wc_get_related_products($post_id, 4)
        : [];
    if (! empty($related_ids)) {
        $products = wc_get_products([
            'status'   => 'publish',
            'include'  => $related_ids,
            'limit'    => 4,
            'orderby'  => 'include',
        ]);
    }
} else {
    // Auto: match product_cat slug with the post's category slugs.
    $post_cats = get_the_category($post_id);
    $slugs     = ! is_wp_error($post_cats) ? wp_list_pluck($post_cats, 'slug') : [];
    if (! empty($slugs)) {
        $products = wc_get_products([
            'status'     => 'publish',
            'limit'      => 4,
            'orderby'    => 'date',
            'order'      => 'DESC',
            'tax_query'  => [[
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $slugs,
            ]],
        ]);
    }
}

if (empty($products)) {
    return;
}
?>
<section class="wrap post-related-products">
    <h2><?php esc_html_e('Sản phẩm liên quan', 'underscores'); ?></h2>
    <div class="grid">
        <?php foreach ($products as $product) : ?>
            <?php get_template_part('partials/components/product-card', null, ['product' => $product->get_id()]); ?>
        <?php endforeach; ?>
    </div>
</section>
