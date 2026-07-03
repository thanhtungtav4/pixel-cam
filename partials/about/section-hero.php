<?php
defined('ABSPATH') || exit;

$kicker  = $args['kicker'] ?? '';
$heading = $args['heading'] ?? '';
$lead    = $args['lead'] ?? '';
$pills   = $args['pills'] ?? [];
?>
<section class="about-hero" style="padding-top:0"><div class="wrap">
    <?php if ($kicker) : ?><div class="kicker"><?php echo esc_html($kicker); ?></div><?php endif; ?>
    <?php if ($heading) : ?><h1><?php echo esc_html($heading); ?></h1><?php endif; ?>
    <?php if ($lead) : ?><p class="lead"><?php echo esc_html($lead); ?></p><?php endif; ?>
    <?php if (! empty($pills)) : ?>
        <div class="pills">
            <?php foreach ($pills as $pill) : ?>
                <span class="pill"><?php echo wp_kses($pill['text'] ?? '', ['b' => []]); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div></section>
