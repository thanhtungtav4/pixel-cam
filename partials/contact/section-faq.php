<?php
/**
 * Contact page — FAQ section (heading + meta + items).
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$heading = $args['heading'] ?? '';
$meta    = $args['meta'] ?? '';
$items   = $args['items'] ?? [];

if (empty($items)) {
    return;
}
?>
<div class="ct-faq">
    <?php if ($heading) : ?><h3><?php echo esc_html($heading); ?></h3><?php endif; ?>
    <?php if ($meta) : ?><p class="head-meta"><?php echo esc_html($meta); ?></p><?php endif; ?>
    <?php foreach ($items as $i => $item) : ?>
        <details class="item"<?php echo $i === 0 ? ' open' : ''; ?>>
            <summary><?php echo esc_html($item['question'] ?? ''); ?></summary>
            <div class="answer"><?php echo esc_html($item['answer'] ?? ''); ?></div>
        </details>
    <?php endforeach; ?>
</div>
