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

// Fallback alt = post title when image alt is empty or just an upload hash.
// Upload-hash alts hurt SEO + a11y; using the title at least gives context.
$img_alt = '';
if ($thumb_id) {
    $img_alt = (string) get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
    if ($img_alt === '' || preg_match('/^[a-f0-9]{20,}\.?(jpe?g|png|webp)?$/i', $img_alt)) {
        $img_alt = get_the_title($post_id);
    }
}
?>
<article class="bcard">
    <a class="bcard__media<?php echo $thumb_id ? '' : ' bcard__media--empty'; ?>" href="<?php echo esc_url($permalink); ?>" aria-hidden="true" tabindex="-1">
        <?php if ($thumb_id) {
            // Sizes: 1-col on mobile, 2-col on tablet/desktop. The inner col width
            // at >980px (sidebar visible) is ~548px. WP will pick the closest
            // registered size <= the requested size, falling back to the next-up.
            echo wp_get_attachment_image($thumb_id, 'pxc_card_16_9', false, [
                'loading' => 'lazy',
                'sizes'   => '(max-width:640px) 100vw, (max-width:980px) calc(50vw - 34px), 548px',
                'alt'     => $img_alt,
            ]);
        } else {
            // No featured image — keep grid layout intact with a neutral
            // icon placeholder so the card still has the same 16:9 visual weight.
            echo '<svg class="bcard__placeholder-icon" viewBox="0 0 24 24" aria-hidden="true">'
               . '<path d="M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm0 11 5-5 4 4 3-3 5 5M9.5 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" '
               . 'fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>'
               . '</svg>';
        } ?>
    </a>
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
