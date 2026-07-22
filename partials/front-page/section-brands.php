<?php

/**
 * Front page — Brand strip.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$heading = $args['heading'] ?? '';
$brands  = $args['brands'] ?? [];

if (empty($brands)) {
    return;
}
?>
<div class="brands"><section><div class="wrap">
    <?php if ($heading) : ?>
        <div class="sec-head"><h2><?php echo esc_html($heading); ?></h2></div>
    <?php endif; ?>
    <div class="brand-grid">
        <?php foreach ($brands as $brand) :
            $logo = $brand['logo'] ?? 0;
            $name = $brand['name'] ?? '';
            $link = underscores_child_acf_link($brand['link'] ?? []);
            $tag  = $link ? 'a' : 'div';
            ?>
            <<?php echo $tag; ?> class="b"<?php echo $link ? ' href="' . esc_url($link['url']) . '"' : ''; ?>>
                <?php if ($logo) {
                    echo wp_get_attachment_image($logo, 'thumbnail', false, ['loading' => 'lazy']);
                } else {
                    echo esc_html($name);
                } ?>
            </<?php echo $tag; ?>>
        <?php endforeach; ?>
    </div>
</div></section></div>
