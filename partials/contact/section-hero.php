<?php
/**
 * Contact page — Hero (heading + meta + trust pills).
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$heading = $args['heading'] ?? '';
$meta    = $args['meta'] ?? '';
$pills   = $args['pills'] ?? [];
?>
<section class="ct-hero section--flush"><div class="wrap page-head">
    <h1><?php echo esc_html($heading ?: get_the_title()); ?></h1>
    <?php if ($meta) : ?><p class="meta"><?php echo wp_kses_post($meta); ?></p><?php endif; ?>
    <?php if (! empty($pills)) : ?>
        <div class="pills">
            <?php foreach ($pills as $pill) : ?>
                <?php if (! empty($pill['text'])) : ?>
                    <span class="pill"><?php echo wp_kses($pill['text'], ['b' => []]); ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div></section>
