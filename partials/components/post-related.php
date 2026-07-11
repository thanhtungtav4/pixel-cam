<?php
/**
 * Related posts section for a single blog post or product page.
 *
 * @param array $args {
 *   int   $post_id         Current post/product ID.
 *   int[] $selected_posts  Manually selected post IDs (optional).
 * }
 * @package Underscores
 */

defined('ABSPATH') || exit;

$post_id = (int) ($args['post_id'] ?? get_the_ID());
if ($post_id <= 0) {
    return;
}

$selected = array_filter(array_map('intval', (array) ($args['selected_posts'] ?? [])));
$related  = [];

if (! empty($selected)) {
    $related = get_posts([
        'post_type'           => 'post',
        'post__in'            => $selected,
        'posts_per_page'      => 4,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
        'orderby'             => 'post__in',
    ]);
} elseif (get_post_type($post_id) === 'product') {
    // Auto: posts whose category slug matches one of the product's category slugs.
    $slugs = wp_get_post_terms($post_id, 'product_cat', ['fields' => 'slugs']);
    if (! is_wp_error($slugs) && ! empty($slugs)) {
        $related = get_posts([
            'post_type'           => 'post',
            'posts_per_page'      => 4,
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
            'category_name'       => implode(',', $slugs),
            'orderby'             => 'date',
            'order'               => 'DESC',
        ]);
    }
} else {
    $cats = get_the_category($post_id);
    $cat_ids = ! is_wp_error($cats) ? wp_list_pluck($cats, 'term_id') : [];
    if (! empty($cat_ids)) {
        $related = get_posts([
            'post_type'           => 'post',
            'posts_per_page'      => 4,
            'post__not_in'        => [$post_id],
            'category__in'        => $cat_ids,
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
            'orderby'             => 'date',
            'order'               => 'DESC',
        ]);
    }
}

if (empty($related)) {
    return;
}
?>
<section class="wrap post-related">
    <h2><?php esc_html_e('Bài viết liên quan', 'underscores'); ?></h2>
    <div class="blog-grid">
        <?php foreach ($related as $post_item) : ?>
            <?php get_template_part('partials/components/post-card', null, ['post_id' => $post_item->ID]); ?>
        <?php endforeach; ?>
    </div>
</section>
