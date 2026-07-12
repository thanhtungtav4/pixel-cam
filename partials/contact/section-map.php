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
?>
<div class="ct-map">
    <?php if ($heading || $caption) : ?>
        <div class="head">
            <?php if ($heading) : ?><h4><?php echo esc_html($heading); ?></h4><?php endif; ?>
            <?php if ($caption) : ?><span class="caption"><?php echo esc_html($caption); ?></span><?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($embed) : ?>
        <div class="canvas"><?php echo wp_kses($embed, ['iframe' => ['src' => [], 'width' => [], 'height' => [], 'style' => [], 'loading' => [], 'allowfullscreen' => [], 'referrerpolicy' => [], 'frameborder' => []]]); ?></div>
    <?php endif; ?>
</div>
