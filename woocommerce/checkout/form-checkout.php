<?php

/**
 * Checkout form — Pixel Cam .co-layout.
 *
 * Two-column layout (customer details | order review) matching the design, but
 * every Woo mechanism is kept verbatim via its hooks: billing/shipping fields,
 * order review (items + shipping methods + payment gateways + place order),
 * nonce, and all checkout actions. We only wrap + class them — no field or
 * gateway logic is rewritten.
 *
 * @package Underscores
 * @see https://woocommerce.com/document/template-structure/
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_checkout_form', $checkout);

if (! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in()) {
    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('Bạn cần đăng nhập để thanh toán.', 'underscores')));
    return;
}
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout co-layout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__('Thanh toán', 'underscores'); ?>">

    <div class="co-col">
        <?php if ($checkout->get_checkout_fields()) : ?>
            <?php do_action('woocommerce_checkout_before_customer_details'); ?>

            <div id="customer_details">
                <div class="co-section">
                    <div class="co-head">
                        <span class="co-num">1</span>
                        <h2><?php esc_html_e('Thông tin nhận hàng', 'underscores'); ?></h2>
                    </div>
                    <?php do_action('woocommerce_checkout_billing'); ?>
                </div>

                <?php do_action('woocommerce_checkout_shipping'); ?>
            </div>

            <?php do_action('woocommerce_checkout_after_customer_details'); ?>
        <?php endif; ?>

        <!-- Section 2: Phương thức vận chuyển (synced via JS) -->
        <div class="co-section ship-methods-section is-hidden">
            <div class="co-head">
                <span class="co-num">2</span>
                <h2><?php esc_html_e('Phương thức vận chuyển', 'underscores'); ?></h2>
            </div>
            <div class="ship-options-placeholder"></div>
        </div>

        <div class="co-section">
            <div class="co-head">
                <span class="co-num">3</span>
                <h2><?php esc_html_e('Phương thức thanh toán', 'underscores'); ?></h2>
            </div>
            <?php
            // Render payment methods and place order button here in the left column
            remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
            woocommerce_checkout_payment();
            ?>
        </div>

        <?php if (apply_filters('woocommerce_enable_order_notes_field', 'yes' === get_option('woocommerce_enable_order_notes', 'yes'))) : ?>
        <div class="co-section co-section--notes">
            <div class="co-head">
                <span class="co-num">4</span>
                <h2><?php esc_html_e('Ghi chú đơn hàng', 'underscores'); ?></h2>
            </div>
            <div class="woocommerce-additional-fields">
                <?php do_action('woocommerce_before_order_notes', $checkout); ?>
                <div class="woocommerce-additional-fields__field-wrapper">
                    <?php foreach ($checkout->get_checkout_fields('order') as $key => $field) : ?>
                        <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
                    <?php endforeach; ?>
                </div>
                <?php do_action('woocommerce_after_order_notes', $checkout); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <aside class="co-summary">
        <?php do_action('woocommerce_checkout_before_order_review_heading'); ?>
        <h3 id="order_review_heading"><?php esc_html_e('Đơn hàng của bạn', 'underscores'); ?></h3>

        <?php do_action('woocommerce_checkout_before_order_review'); ?>

        <div id="order_review" class="woocommerce-checkout-review-order">
            <?php do_action('woocommerce_checkout_order_review'); ?>
        </div>

        <?php do_action('woocommerce_checkout_after_order_review'); ?>
    </aside>

</form>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>
