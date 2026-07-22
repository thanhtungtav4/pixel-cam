<?php

/**
 * Front page — Newsletter / CTA banner.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$heading = $args['heading'] ?? '';
$desc    = $args['desc'] ?? '';
$button  = underscores_child_acf_link($args['button'] ?? []);

if (! $heading && ! $desc && ! $button) {
    return;
}
?>
<section><div class="wrap">
    <div class="cta">
        <div>
            <?php if ($heading) : ?><h2><?php echo esc_html($heading); ?></h2><?php endif; ?>
            <?php if ($desc) : ?><p><?php echo esc_html($desc); ?></p><?php endif; ?>
        </div>
        <?php if ($button) : ?>
            <a href="<?php echo esc_url($button['url']); ?>" class="btn"<?php echo $button['target'] ? ' target="' . esc_attr($button['target']) . '"' : ''; ?><?php echo ($button['target'] ?? '') === '_blank' ? ' rel="noopener noreferrer"' : ''; ?>><?php echo esc_html($button['title']); ?></a>
        <?php endif; ?>
    </div>
</div></section>
