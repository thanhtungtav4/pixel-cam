<?php

/**
 * Front page — Hero slider + side banners.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$slides = $args['slides'] ?? [];
$side   = $args['side_banners'] ?? [];

if (empty($slides) && empty($side)) {
    return;
}
?>
<section class="hero"><div class="wrap hero-grid">
    <?php if (! empty($slides)) : ?>
    <div class="slider" id="slider">
        <?php $has_primary_heading = false; ?>
        <?php foreach ($slides as $i => $slide) :
            $image = $slide['image'] ?? 0;
            $link  = underscores_child_acf_link($slide['link'] ?? []);
            ?>
            <div class="slide<?php echo $i === 0 ? ' on' : ''; ?>">
                <?php if ($image) : ?>
                    <?php echo wp_get_attachment_image($image, 'pxc_hero', false, ['class' => 'slide-img', 'fetchpriority' => $i === 0 ? 'high' : 'low', 'loading' => $i === 0 ? 'eager' : 'lazy']); ?>
                <?php endif; ?>
                <div class="cap">
                    <?php if (! empty($slide['kicker'])) : ?>
                        <div class="kick"><?php echo esc_html($slide['kicker']); ?></div>
                    <?php endif; ?>
                    <?php if (! empty($slide['heading'])) : ?>
                        <?php $heading_tag = $has_primary_heading ? 'h2' : 'h1'; $has_primary_heading = true; ?>
                        <<?php echo $heading_tag; ?>><?php echo esc_html($slide['heading']); ?></<?php echo $heading_tag; ?>>
                    <?php endif; ?>
                    <?php if ($link) : ?>
                        <a href="<?php echo esc_url($link['url']); ?>" class="btn"<?php echo $link['target'] ? ' target="' . esc_attr($link['target']) . '"' : ''; ?><?php echo ($link['target'] ?? '') === '_blank' ? ' rel="noopener noreferrer"' : ''; ?>><?php echo esc_html($link['title']); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="dots" id="dots"></div>
    </div>
    <?php endif; ?>

    <?php if (! empty($side)) : ?>
    <div class="hero-side">
        <?php foreach ($side as $banner) :
            $image = $banner['image'] ?? 0;
            $link  = underscores_child_acf_link($banner['link'] ?? []);
            $label = $banner['label'] ?? '';
            $tag   = $link ? 'a' : 'div';
            ?>
            <<?php echo $tag; ?> class="card"<?php echo $link ? ' href="' . esc_url($link['url']) . '"' : ''; ?>>
                <?php if ($image) {
                    // 'large' keeps the image's real aspect ratio (pxc_side_banner
                    // hard-crops to 4:3 → would distort/crop non-4:3 uploads).
                    echo wp_get_attachment_image($image, 'large', false, ['loading' => 'lazy']);
                } ?>
                <?php if ($label) : ?><span class="lbl"><?php echo esc_html($label); ?></span><?php endif; ?>
            </<?php echo $tag; ?>>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div></section>
