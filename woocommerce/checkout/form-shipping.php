<?php
/**
 * Shipping address only — order notes are rendered separately in
 * form-checkout.php so the numbered sections (1→2→3→4) stay in the
 * right visual order.
 *
 * @package Underscores
 * @version 3.6.0
 */

defined('ABSPATH') || exit;
?>
<div class="woocommerce-shipping-fields">
    <?php if (true === WC()->cart->needs_shipping_address()) : ?>
        <div id="ship-to-different-address">
            <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                <input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" <?php checked(apply_filters('woocommerce_ship_to_different_address_checked', 'shipping' === get_option('woocommerce_ship_to_destination') ? 1 : 0), 1); ?> type="checkbox" name="ship_to_different_address" value="1" /> <span><?php esc_html_e('Giao hàng đến địa chỉ khác?', 'underscores'); ?></span>
            </label>
        </div>

        <div class="shipping_address">
            <?php do_action('woocommerce_before_checkout_shipping_form', $checkout); ?>
            <div class="woocommerce-shipping-fields__field-wrapper">
                <?php
                $fields = $checkout->get_checkout_fields('shipping');
                foreach ($fields as $key => $field) {
                    woocommerce_form_field($key, $field, $checkout->get_value($key));
                }
                ?>
            </div>
            <?php do_action('woocommerce_after_checkout_shipping_form', $checkout); ?>
        </div>
    <?php endif; ?>
</div>
