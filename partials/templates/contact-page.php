<?php

/**
 * Contact page body — Pixel Cam.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$contact_acf = function_exists('get_fields') ? (get_fields() ?: []) : [];

$heading   = $contact_acf['heading'] ?? '';
$meta      = $contact_acf['meta'] ?? '';
$cards     = $contact_acf['info_cards'] ?? [];
$form_eb   = $contact_acf['form_eyebrow'] ?? '';
$form_h    = $contact_acf['form_heading'] ?? '';
$form_lead = $contact_acf['form_lead'] ?? '';
$form_sc   = $contact_acf['form_shortcode'] ?? '';
$map_h     = $contact_acf['map_heading'] ?? '';
$map_embed = $contact_acf['map_embed'] ?? '';
$faq_h     = $contact_acf['faq_heading'] ?? '';
$faq_meta  = $contact_acf['faq_meta'] ?? '';
$faq_items = $contact_acf['faq_items'] ?? [];

// Fall back to the shop-wide CF7 shortcode if the page has none.
if (! $form_sc && function_exists('underscores_get_option')) {
    $dpf = underscores_get_option('detail_post_form_section') ?: [];
    $form_sc = $dpf['shortcode_cf7'] ?? '';
}
?>
<div class="wrap">
    <nav class="crumb" aria-label="Breadcrumb">
        <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Trang chủ', 'underscores'); ?></a>
        <span class="sep">/</span>
        <span class="cur"><?php the_title(); ?></span>
    </nav>
</div>

<section style="padding-top:0;padding-bottom:24px"><div class="wrap page-head">
    <h1><?php echo esc_html($heading ?: get_the_title()); ?></h1>
    <?php if ($meta) : ?><p class="meta"><?php echo wp_kses_post($meta); ?></p><?php endif; ?>
</div></section>

<section style="padding-top:0"><div class="wrap">
    <div class="contact-grid">
        <div class="ct-info">
            <?php foreach ($cards as $card) :
                $rows = $card['rows'] ?? [];
                ?>
                <div class="card">
                    <?php if (! empty($card['eyebrow'])) : ?><div class="eyebrow"><?php echo esc_html($card['eyebrow']); ?></div><?php endif; ?>
                    <?php if (! empty($card['title'])) : ?><h4><?php echo esc_html($card['title']); ?></h4><?php endif; ?>
                    <?php foreach ($rows as $row) : ?>
                        <div class="row">
                            <span class="k"><?php echo esc_html($row['k'] ?? ''); ?></span>
                            <span class="v"><?php echo wp_kses_post($row['v'] ?? ''); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="ct-form">
            <?php if ($form_eb) : ?><div class="eyebrow"><?php echo esc_html($form_eb); ?></div><?php endif; ?>
            <?php if ($form_h) : ?><h3><?php echo esc_html($form_h); ?></h3><?php endif; ?>
            <?php if ($form_lead) : ?><p class="lead"><?php echo wp_kses_post($form_lead); ?></p><?php endif; ?>
            <?php if ($form_sc) :
                echo do_shortcode($form_sc);
            else :
                get_template_part('partials/components/contact-fallback-form');
            endif; ?>
        </div>
    </div>

    <?php if ($map_h || $map_embed) : ?>
        <div class="ct-map">
            <?php if ($map_h) : ?><div class="head"><h4><?php echo esc_html($map_h); ?></h4></div><?php endif; ?>
            <?php if ($map_embed) : ?>
                <div class="canvas"><?php echo wp_kses($map_embed, ['iframe' => ['src' => [], 'width' => [], 'height' => [], 'style' => [], 'loading' => [], 'allowfullscreen' => [], 'referrerpolicy' => [], 'frameborder' => []]]); ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (! empty($faq_items)) : ?>
        <div class="ct-faq">
            <?php if ($faq_h) : ?><h3><?php echo esc_html($faq_h); ?></h3><?php endif; ?>
            <?php if ($faq_meta) : ?><p class="head-meta"><?php echo esc_html($faq_meta); ?></p><?php endif; ?>
            <?php foreach ($faq_items as $i => $item) : ?>
                <details class="item"<?php echo $i === 0 ? ' open' : ''; ?>>
                    <summary><?php echo esc_html($item['question'] ?? ''); ?></summary>
                    <div class="answer"><?php echo esc_html($item['answer'] ?? ''); ?></div>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div></section>
