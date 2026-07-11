<?php

/**
 * Reusable blog card (.bcard). Expects to run inside the loop or receive a post.
 *
 * @param array $args ['post_id' => int]
 * @package Underscores
 */

defined('ABSPATH') || exit;

$post_id = $args['post_id'] ?? get_the_ID();
if (! $post_id) {
    return;
}

$cats = get_the_category($post_id);
$cat  = ! empty($cats) ? $cats[0]->name : '';
?>
<article class="bcard">
    <?php if (has_post_thumbnail($post_id)) : ?>
        <a href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php echo get_the_post_thumbnail($post_id, 'pxc_card_16_10', ['loading' => 'lazy']); ?></a>
    <?php endif; ?>
    <div class="body">
        <?php if ($cat) : ?><span class="cat-tag"><?php echo esc_html($cat); ?></span><?php endif; ?>
        <h3><a href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php echo esc_html(get_the_title($post_id)); ?></a></h3>
        <?php if (has_excerpt($post_id)) : ?>
            <p><?php echo esc_html(get_the_excerpt($post_id)); ?></p>
        <?php endif; ?>
        <div class="meta">
            <span><?php echo esc_html(get_the_author_meta('display_name', get_post_field('post_author', $post_id))); ?></span>
            <span>·</span>
            <span><?php echo esc_html(get_the_date('', $post_id)); ?></span>
        </div>
    </div>
</article>
