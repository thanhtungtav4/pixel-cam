<?php

/**
 * Reusable blog card (.bcard). Expects to run inside the loop or receive a post.
 *
 * Image is served via wp_get_attachment_image() so the browser gets a proper
 * srcset + sizes — picks the right variant for the viewport instead of always
 * downloading the 720px version on a 390px screen.
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

$thumb_id  = get_post_thumbnail_id($post_id);
$permalink = get_permalink($post_id);
?>
<article class="bcard">
    <?php if ($thumb_id) : ?>
        <a href="<?php echo esc_url($permalink); ?>" aria-hidden="true" tabindex="-1">
            <?php
            // Sizes: 1-col on mobile, 2-col on tablet/desktop. The inner col width
            // at >980px (sidebar visible) is ~548px. WP will pick the closest
            // registered size <= the requested size, falling back to the next-up.
            echo wp_get_attachment_image($thumb_id, 'pxc_card_16_9', false, [
                'loading' => 'lazy',
                'sizes'   => '(max-width:640px) 100vw, (max-width:980px) calc(50vw - 34px), 548px',
            ]);
            ?>
        </a>
    <?php endif; ?>
    <div class="body">
        <?php if ($cat) : ?><span class="cat-tag"><?php echo esc_html($cat); ?></span><?php endif; ?>
        <h3><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html(get_the_title($post_id)); ?></a></h3>
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
