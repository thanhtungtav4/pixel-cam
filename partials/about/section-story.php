<?php
defined('ABSPATH') || exit;

$eyebrow = $args['eyebrow'] ?? '';
$heading = $args['heading'] ?? '';
$image   = $args['image'] ?? 0;
$text    = $args['text'] ?? '';
?>
<section class="ab-section"><div class="wrap">
    <?php if ($eyebrow || $heading) : ?>
        <div class="head">
            <?php if ($eyebrow) : ?><div class="eyebrow"><?php echo esc_html($eyebrow); ?></div><?php endif; ?>
            <?php if ($heading) : ?><h2><?php echo esc_html($heading); ?></h2><?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="ab-story">
        <?php if ($image) : ?>
            <div class="photo"><?php echo wp_get_attachment_image($image, 'large'); ?></div>
        <?php endif; ?>
        <?php if ($text) : ?>
            <div class="text"><?php echo wp_kses_post($text); ?></div>
        <?php endif; ?>
    </div>
</div></section>
