<?php

/**
 * Front page — Feature strip (commitments).
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$items = $args['items'] ?? [];

if (empty($items)) {
    return;
}
?>
<div class="strip"><div class="wrap">
    <?php foreach ($items as $item) :
        $icon = $item['icon'] ?? 0;
        ?>
        <div class="item">
            <?php if ($icon) {
                echo wp_get_attachment_image($icon, 'thumbnail', false, [
                    // The adjacent title carries the meaning; keep the icon
                    // decorative while making below-fold loading explicit.
                    'alt'          => '',
                    'aria-hidden'  => 'true',
                    'loading'      => 'lazy',
                    'decoding'     => 'async',
                ]);
            } ?>
            <div>
                <?php if (! empty($item['title'])) : ?><b><?php echo esc_html($item['title']); ?></b><?php endif; ?>
                <?php if (! empty($item['desc'])) : ?><small><?php echo esc_html($item['desc']); ?></small><?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div></div>
