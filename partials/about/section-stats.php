<?php
defined('ABSPATH') || exit;

$items = $args['items'] ?? [];
if (empty($items)) {
    return;
}
?>
<section class="section--no-y"><div class="wrap">
    <div class="ab-stats">
        <?php foreach ($items as $item) : ?>
            <div class="stat">
                <?php if (! empty($item['n'])) : ?><div class="n"><?php echo esc_html($item['n']); ?></div><?php endif; ?>
                <?php if (! empty($item['l'])) : ?><div class="l"><?php echo esc_html($item['l']); ?></div><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div></section>
