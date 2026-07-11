<?php

/**
 * Cart totals — Pixel Cam .cart-summary.
 *
 * Keeps all Woo total functions (subtotal / coupon / shipping / fee / tax /
 * total) + the proceed-to-checkout hook so gateways/plugins work; only the
 * markup is the design's .cart-summary with .sum-row rows + trust badges.
 *
 * @package Underscores
 * @see https://woocommerce.com/document/template-structure/
 */

defined('ABSPATH') || exit;
?>
<aside class="cart_totals cart-summary <?php echo WC()->customer->has_calculated_shipping() ? 'calculated_shipping' : ''; ?>">

    <?php do_action('woocommerce_before_cart_totals'); ?>

    <h3><?php esc_html_e('Tóm tắt đơn hàng', 'underscores'); ?></h3>

    <div class="sum-row cart-subtotal">
        <span><?php esc_html_e('Tạm tính', 'underscores'); ?></span>
        <span><?php wc_cart_totals_subtotal_html(); ?></span>
    </div>

    <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
        <div class="sum-row cart-discount coupon-<?php echo esc_attr(sanitize_title($code)); ?>">
            <span><?php wc_cart_totals_coupon_label($coupon); ?></span>
            <span><?php wc_cart_totals_coupon_html($coupon); ?></span>
        </div>
    <?php endforeach; ?>

    <?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
        <?php do_action('woocommerce_cart_totals_before_shipping'); ?>
        <div class="sum-row shipping-row"><?php wc_cart_totals_shipping_html(); ?></div>
        <?php do_action('woocommerce_cart_totals_after_shipping'); ?>
    <?php endif; ?>

    <?php foreach (WC()->cart->get_fees() as $fee) : ?>
        <div class="sum-row fee">
            <span><?php echo esc_html($fee->name); ?></span>
            <span><?php wc_cart_totals_fee_html($fee); ?></span>
        </div>
    <?php endforeach; ?>

    <?php if (wc_tax_enabled() && ! WC()->cart->display_prices_including_tax()) : ?>
        <div class="sum-row tax-total">
            <span><?php echo esc_html(WC()->countries->tax_or_vat()); ?></span>
            <span><?php wc_cart_totals_taxes_total_html(); ?></span>
        </div>
    <?php endif; ?>

    <?php do_action('woocommerce_cart_totals_before_order_total'); ?>

    <div class="sum-row total order-total">
        <span><?php esc_html_e('Tổng cộng', 'underscores'); ?></span>
        <span><?php wc_cart_totals_order_total_html(); ?></span>
    </div>

    <?php do_action('woocommerce_cart_totals_after_order_total'); ?>

    <div class="wc-proceed-to-checkout">
        <?php do_action('woocommerce_proceed_to_checkout'); ?>
    </div>

    <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="btn btn-ghost continue-shopping">&larr; <?php esc_html_e('Tiếp tục mua sắm', 'underscores'); ?></a>
    
    <?php if (wc_coupons_enabled()) : ?>
        <div class="voucher">
            <label style="font-size:13px;font-weight:600;margin-bottom:8px;display:block"><?php esc_html_e('Mã giảm giá', 'underscores'); ?></label>
            <div class="vch-row">
                <input type="text" name="coupon_code" class="input-text" id="coupon_code_summary" value="" placeholder="<?php esc_attr_e('Nhập mã...', 'underscores'); ?>" style="flex:1;padding:10px 14px;border:1px solid var(--border);border-radius:8px;font-size:14px" />
                <button type="submit" class="btn btn-ghost" name="apply_coupon" value="<?php esc_attr_e('Áp dụng', 'underscores'); ?>" style="padding:10px 14px"><?php esc_html_e('Áp dụng', 'underscores'); ?></button>
            </div>
        </div>
    <?php endif; ?>

    <div class="trust-mini">
        <div class="ti"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
        <div><b><?php esc_html_e('Thanh toán an toàn', 'underscores'); ?></b><br><small><?php esc_html_e('SSL 256-bit · Bảo mật', 'underscores'); ?></small></div>
    </div>
    <div class="trust-mini">
        <div class="ti"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
        <div><b><?php esc_html_e('Đổi trả dễ dàng', 'underscores'); ?></b><br><small><?php esc_html_e('7 ngày · Không phí', 'underscores'); ?></small></div>
    </div>

    <?php do_action('woocommerce_after_cart_totals'); ?>
</aside>
