<?php
/**
 * Contact page — Map section (single iframe + caption).
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$heading = $args['heading'] ?? '';
$caption = $args['caption'] ?? '';
$embed   = $args['embed'] ?? '';

if (! $heading && ! $embed) {
    return;
}

if ($embed && ! preg_match('/<iframe\b[^>]*\btitle\s*=/i', $embed)) {
    $map_title = $heading ?: __('Bản đồ cửa hàng Pixel Cam', 'underscores');
    $embed = (string) preg_replace('/<iframe\b/i', '<iframe title="' . esc_attr($map_title) . '"', $embed, 1);
}
?>
<div class="ct-map">
    <?php if ($heading || $caption) : ?>
        <div class="head">
            <?php if ($heading) : ?><h4><?php echo esc_html($heading); ?></h4><?php endif; ?>
            <?php if ($caption) : ?><span class="caption"><?php echo esc_html($caption); ?></span><?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($embed) : ?>
        <div class="canvas"><?php echo wp_kses($embed, ['iframe' => ['src' => [], 'title' => [], 'width' => [], 'height' => [], 'style' => [], 'loading' => [], 'allowfullscreen' => [], 'referrerpolicy' => [], 'frameborder' => []]]); ?></div>
    <?php endif; ?>
</div>
