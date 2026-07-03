<?php
defined('ABSPATH') || exit;

$eyebrow = $args['eyebrow'] ?? '';
$heading = $args['heading'] ?? '';
$items   = $args['items'] ?? [];
if (empty($items)) {
    return;
}
?>
<section class="ab-section"><div class="wrap">
    <?php if ($eyebrow || $heading) : ?>
        <div class="head">
            <?php if ($eyebrow) : ?><div class="eyebrow"><?php echo esc_html($eyebrow); ?></div><?php endif; ?>
            <?php if ($heading) : ?><h2><?php echo esc_html($heading); ?></h2><?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="ab-values">
        <?php foreach ($items as $item) : ?>
            <div class="v">
                <?php if (! empty($item['n'])) : ?><div class="n"><?php echo esc_html($item['n']); ?></div><?php endif; ?>
                <?php if (! empty($item['title'])) : ?><h4><?php echo esc_html($item['title']); ?></h4><?php endif; ?>
                <?php if (! empty($item['desc'])) : ?><p><?php echo esc_html($item['desc']); ?></p><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div></section>
