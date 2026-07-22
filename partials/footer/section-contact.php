<?php
/**
 * Footer — Section 4: Contact + payment methods.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$hotline         = $args['hotline'] ?? '';
$email           = $args['email'] ?? '';
$address         = $args['address'] ?? '';
$payment_methods = $args['payment_methods'] ?? [];

$has_contact  = $hotline || $email || $address;
$has_payments = ! empty($payment_methods);

if (! $has_contact && ! $has_payments) {
    return;
}
?>
<div class="foot-col foot-col--contact">
    <h2 class="foot-heading"><?php esc_html_e('Liên hệ', 'underscores'); ?></h2>
    <?php if ($has_contact) : ?>
        <ul class="foot-contact">
            <?php if ($hotline) : ?>
                <li>
                    <span class="k" aria-hidden="true">📞</span>
                    <span class="v"><a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $hotline)); ?>"><?php echo esc_html($hotline); ?></a></span>
                </li>
            <?php endif; ?>
            <?php if ($email) : ?>
                <li>
                    <span class="k" aria-hidden="true">✉️</span>
                    <span class="v"><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></span>
                </li>
            <?php endif; ?>
            <?php if ($address) :
                foreach (preg_split('/\r\n|\r|\n/', $address) as $line) :
                    $line = trim($line);
                    if ($line === '') {
                        continue;
                    }
                    ?>
                    <li>
                        <span class="k" aria-hidden="true">📍</span>
                        <span class="v"><?php echo esc_html($line); ?></span>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    <?php endif; ?>

    <?php if ($has_payments) : ?>
        <div class="foot-payments">
            <div class="foot-payments-label"><?php esc_html_e('Thanh toán', 'underscores'); ?></div>
            <ul class="foot-payment-list" aria-label="<?php esc_attr_e('Phương thức thanh toán', 'underscores'); ?>">
                <?php foreach ($payment_methods as $pm) :
                    $icon_id = $pm['icon'] ?? 0;
                    $label   = $pm['label'] ?? '';
                    $link    = $pm['link'] ?? '';
                    if (! $icon_id && ! $label) {
                        continue;
                    }
                    // Prefer icon when present; fall back to a compact text label.
                    // For SVGs, 'thumbnail' size returns 1×1 — use 'full' to render at native dims.
                    $tag       = $link ? 'a' : 'span';
                    $attrs     = $link ? sprintf(' href="%s" target="_blank" rel="noopener noreferrer"', esc_url($link)) : '';
                    if ($icon_id) {
                        $mime = get_post_mime_type((int) $icon_id);
                        $size = ($mime === 'image/svg+xml') ? 'full' : 'thumbnail';
                        $icon_html = wp_get_attachment_image((int) $icon_id, $size, false, ['loading' => 'lazy', 'decoding' => 'async', 'alt' => $label]);
                    } else {
                        $icon_html = '';
                    }
                    $inner     = $icon_html !== ''
                        ? $icon_html
                        : '<span class="foot-payment-text">' . esc_html($label) . '</span>';
                    ?>
                    <li>
                        <<?php echo $tag; ?><?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $attrs already escaped. ?>
                            class="foot-payment <?php echo $icon_id ? 'has-icon' : 'is-text'; ?>"
                            <?php echo $label ? 'title="' . esc_attr($label) . '" aria-label="' . esc_attr($label) . '"' : ''; ?>>
                            <?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- image markup or pre-escaped span. ?>
                        </<?php echo $tag; ?>>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
