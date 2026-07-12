<?php
/**
 * Contact page — Form section (eyebrow + heading + lead + form shortcode / fallback).
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$eyebrow   = $args['eyebrow'] ?? '';
$heading   = $args['heading'] ?? '';
$lead      = $args['lead'] ?? '';
$shortcode = $args['shortcode'] ?? '';

// Fall back to the shop-wide CF7 shortcode if the page has none.
if (! $shortcode && function_exists('underscores_get_option')) {
    $dpf = underscores_get_option('detail_post_form_section') ?: [];
    $shortcode = $dpf['shortcode_cf7'] ?? '';
}

if (! $shortcode && ! $eyebrow && ! $heading && ! $lead) {
    return;
}
?>
<div class="ct-form">
    <?php if ($eyebrow) : ?><div class="eyebrow"><?php echo esc_html($eyebrow); ?></div><?php endif; ?>
    <?php if ($heading) : ?><h3><?php echo esc_html($heading); ?></h3><?php endif; ?>
    <?php if ($lead) : ?><p class="lead"><?php echo wp_kses_post($lead); ?></p><?php endif; ?>
    <?php if ($shortcode) :
        echo do_shortcode($shortcode);
    else :
        get_template_part('partials/components/contact-fallback-form');
    endif; ?>
</div>
