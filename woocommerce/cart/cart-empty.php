<?php

/**
 * Empty cart — Pixel Cam .empty-state.
 *
 * @package Underscores
 * @see https://woocommerce.com/document/template-structure/
 */

defined('ABSPATH') || exit;

do_action('woocommerce_cart_is_empty');
?>
<div class="empty-state empty-state--bordered cart-empty-state">
    <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13h11l2.6-9H6"/>
    </svg>
    <div class="es-title"><?php esc_html_e('Giỏ hàng đang trống', 'underscores'); ?></div>
    <div class="es-sub"><?php esc_html_e('Bạn chưa thêm sản phẩm nào. Khám phá cửa hàng và chọn thiết bị yêu thích nhé.', 'underscores'); ?></div>
    <?php if (wc_get_page_id('shop') > 0) : ?>
        <a class="btn btn-primary" href="<?php echo esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop'))); ?>">
            <?php esc_html_e('Tiếp tục mua sắm', 'underscores'); ?>
        </a>
    <?php endif; ?>
</div>
