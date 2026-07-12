<?php
/**
 * Contact page — Info cards (left column) + quick actions row.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$cards        = $args['cards'] ?? [];
$quick_actions = $args['quick_actions'] ?? [];

if (empty($cards) && empty($quick_actions)) {
    return;
}
?>
<div class="ct-info">
    <?php if (! empty($cards)) : ?>
        <?php foreach ($cards as $card) :
            $rows = $card['rows'] ?? [];
            ?>
            <div class="card">
                <?php if (! empty($card['eyebrow'])) : ?><div class="eyebrow"><?php echo esc_html($card['eyebrow']); ?></div><?php endif; ?>
                <?php if (! empty($card['title'])) : ?><h4><?php echo esc_html($card['title']); ?></h4><?php endif; ?>
                <?php if (! empty($rows)) : ?>
                    <?php foreach ($rows as $row) : ?>
                        <div class="row">
                            <span class="k"><?php echo esc_html($row['k'] ?? ''); ?></span>
                            <span class="v"><?php echo wp_kses_post($row['v'] ?? ''); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (! empty($quick_actions)) : ?>
        <div class="channels" aria-label="Liên hệ nhanh">
            <?php foreach ($quick_actions as $action) :
                $link = underscores_child_acf_link($action['link'] ?? []);
                if (! $link) {
                    continue;
                }
                ?>
                <a class="ch"
                   href="<?php echo esc_url($link['url']); ?>"
                   <?php echo $link['target'] ? ' target="' . esc_attr($link['target']) . '" rel="noopener"' : ''; ?>>
                    <b><?php echo esc_html($action['label'] ?? ''); ?></b>
                    <?php if (! empty($action['hint'])) : ?>
                        <small><?php echo esc_html($action['hint']); ?></small>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
