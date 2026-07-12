<?php

/**
 * Contact page body — Pixel Cam.
 *
 * Thin orchestrator: load ACF, pass per-section slices to dedicated partials.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$contact_acf = function_exists('get_fields') ? (get_fields() ?: []) : [];
?>
<div class="wrap">
    <?php get_template_part('partials/components/breadcrumb', null, ['items' => [['label' => get_the_title()]]]); ?>
</div>

<?php
// 1. Hero
get_template_part('partials/contact/section-hero', null, [
    'heading' => $contact_acf['heading'] ?? '',
    'meta'    => $contact_acf['meta'] ?? '',
    'pills'   => $contact_acf['pills'] ?? [],
]);

// 2. Main grid: info cards + form
$info_args = [
    'cards'         => $contact_acf['info_cards'] ?? [],
    'quick_actions' => $contact_acf['quick_actions'] ?? [],
];
$form_args = [
    'eyebrow'   => $contact_acf['form_eyebrow'] ?? '',
    'heading'   => $contact_acf['form_heading'] ?? '',
    'lead'      => $contact_acf['form_lead'] ?? '',
    'shortcode' => $contact_acf['form_shortcode'] ?? '',
];

$has_grid = ! empty($info_args['cards'])
    || ! empty($info_args['quick_actions'])
    || ! empty($form_args['eyebrow'])
    || ! empty($form_args['heading'])
    || ! empty($form_args['lead'])
    || ! empty($form_args['shortcode']);
?>
<?php if ($has_grid) : ?>
    <section class="ct-main" style="padding-top:0"><div class="wrap">
        <div class="contact-grid">
            <?php get_template_part('partials/contact/section-info', null, $info_args); ?>
            <?php get_template_part('partials/contact/section-form', null, $form_args); ?>
        </div>
    </div></section>
<?php endif; ?>

<?php
// 3. Map (single iframe)
get_template_part('partials/contact/section-map', null, [
    'heading' => $contact_acf['map_heading'] ?? '',
    'caption' => $contact_acf['map_caption'] ?? '',
    'embed'   => $contact_acf['map_embed'] ?? '',
]);

// 4. FAQ
get_template_part('partials/contact/section-faq', null, [
    'heading' => $contact_acf['faq_heading'] ?? '',
    'meta'    => $contact_acf['faq_meta'] ?? '',
    'items'   => $contact_acf['faq_items'] ?? [],
]);
