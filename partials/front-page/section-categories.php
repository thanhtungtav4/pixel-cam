<?php

/**
 * Front page — Featured category tiles.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$heading = $args['heading'] ?? '';
$link    = underscores_child_acf_link($args['link'] ?? []);
$tiles   = $args['tiles'] ?? [];

if (empty($tiles)) {
    return;
}
?>
<section><div class="wrap">
    <?php if ($heading || $link) : ?>
        <div class="sec-head">
            <?php if ($heading) : ?><h2><?php echo esc_html($heading); ?></h2><?php endif; ?>
            <?php if ($link) : ?><a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['title']); ?></a><?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="cat-slider" data-cat-slider>
        <button class="cat-slider__nav cat-slider__nav--prev" type="button" aria-label="Danh mục trước" data-cat-prev>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <div class="cat-slider__track tiles">
            <?php foreach ($tiles as $tile) :
                $image     = $tile['image'] ?? 0;
                $tile_link = underscores_child_acf_link($tile['link'] ?? []);
                $href      = $tile_link ? $tile_link['url'] : '#';
                ?>
                <a href="<?php echo esc_url($href); ?>" class="tile">
                    <?php if ($image) {
                        echo wp_get_attachment_image($image, 'pxc_tile', false, ['loading' => 'lazy']);
                    } ?>
                    <div class="t">
                        <?php if (! empty($tile['title'])) : ?><b><?php echo esc_html($tile['title']); ?></b><?php endif; ?>
                        <?php if (! empty($tile['desc'])) : ?><p><?php echo esc_html($tile['desc']); ?></p><?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <button class="cat-slider__nav cat-slider__nav--next" type="button" aria-label="Danh mục tiếp" data-cat-next>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </div>
</div></section>
