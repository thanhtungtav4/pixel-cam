<?php

/**
 * Reusable collapsible content block.
 *
 * Used for SEO intro/outro on term archives (product_cat, post category).
 * The full content is always rendered server-side (good for SEO +
 * screen readers), but visually clamped to `$max_lines` lines via
 * -webkit-line-clamp. A "Xem thêm" button toggles `.is-expanded` on
 * the wrapper to drop the clamp and reveal the rest.
 *
 * No JavaScript required to render — the button is just an <a> with
 * an href that points to a fragment selector. The actual toggle is
 * handled in pixel-cam.js via delegated click on `[data-collapsible-toggle]`.
 *
 * @param array $args {
 *   string  $content   Raw HTML to display. Pass the full content; the
 *                      CSS clamp decides how much to show initially.
 *   string  $label     Toggle button label. Default 'Xem thêm'.
 *   string  $min_label Collapsed label. Default 'Thu gọn'.
 *   int     $max_lines Line clamp count when collapsed. Default 4.
 *   string  $class     Extra class on the outer wrapper.
 * }
 * @package Underscores
 */

defined('ABSPATH') || exit;

$content = (string) ($args['content'] ?? '');
$label   = (string) ($args['label'] ?? __('Xem thêm', 'underscores'));
$min_label = (string) ($args['min_label'] ?? __('Thu gọn', 'underscores'));
$max_lines = max(1, (int) ($args['max_lines'] ?? 4));
$class    = trim('seo-block ' . (string) ($args['class'] ?? ''));

if ($content === '') {
    return;
}
?>
<section class="<?php echo esc_attr($class); ?>" data-collapsible data-collapsible-lines="<?php echo esc_attr((string) $max_lines); ?>">
    <div class="seo-block__inner prose" data-collapsible-content>
        <?php echo wp_kses_post($content); ?>
    </div>
    <button
        type="button"
        class="seo-block__toggle"
        data-collapsible-toggle
        aria-expanded="false"
        data-label-expanded="<?php echo esc_attr($label); ?>"
        data-label-collapsed="<?php echo esc_attr($min_label); ?>"
    >
        <span class="seo-block__toggle-text"><?php echo esc_html($label); ?></span>
        <svg class="seo-block__toggle-icon" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false">
            <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </button>
</section>
