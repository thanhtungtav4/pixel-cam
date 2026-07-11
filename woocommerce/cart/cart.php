<?php

/**
 * Cart page — Pixel Cam layout.
 *
 * Overrides Woo's default table with the .cart-layout / .cart-item design, but
 * keeps every Woo mechanism: the cart form + nonce, cart[key][qty] inputs (so
 * "Update cart" works), remove URLs, coupon form, and all cart hooks. Totals +
 * summary come from cart/cart-totals.php via woocommerce_cart_collaterals.
 *
 * @package Underscores
 * @see https://woocommerce.com/document/template-structure/
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart');
?>

<form class="woocommerce-cart-form cart-layout" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
    <?php do_action('woocommerce_before_cart_table'); ?>

    <div id="cartItems" class="woocommerce-cart-form__contents">
        <?php do_action('woocommerce_before_cart_contents'); ?>

        <?php
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
            $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
            $visible    = apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key);

            if (! ($_product instanceof WC_Product && $_product->exists() && $cart_item['quantity'] > 0 && $visible)) {
                continue;
            }

            $product_name      = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);
            $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
            $thumbnail         = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image('pxc_thumb_sq'), $cart_item, $cart_item_key);

            $brand_terms = get_the_terms($product_id, 'product_brand');
            $brand       = (! is_wp_error($brand_terms) && ! empty($brand_terms)) ? $brand_terms[0]->name : '';

            $remove_link = apply_filters(
                'woocommerce_cart_item_remove_link',
                sprintf(
                    '<a role="button" href="%s" class="remove ci-remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
                    esc_url(wc_get_cart_remove_url($cart_item_key)),
                    esc_attr(sprintf(__('Xóa %s khỏi giỏ', 'underscores'), wp_strip_all_tags($product_name))),
                    esc_attr($product_id),
                    esc_attr($_product->get_sku())
                ),
                $cart_item_key
            );

            $qty_input = woocommerce_quantity_input(
                [
                    'input_name'   => "cart[{$cart_item_key}][qty]",
                    'input_value'  => $cart_item['quantity'],
                    'max_value'    => $_product->is_sold_individually() ? 1 : $_product->get_max_purchase_quantity(),
                    'min_value'    => $_product->is_sold_individually() ? 1 : 0,
                    'product_name' => $product_name,
                ],
                $_product,
                false
            );
            ?>
            <div class="cart-item woocommerce-cart-form__cart-item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">
                <div class="ci-thumb">
                    <?php
                    if ($product_permalink) {
                        printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail); // phpcs:ignore
                    } else {
                        echo $thumbnail; // phpcs:ignore
                    }
                    ?>
                </div>

                <div class="ci-info">
                    <?php if ($brand) : ?><div class="ci-brand"><?php echo esc_html($brand); ?></div><?php endif; ?>
                    <div class="ci-name">
                        <?php
                        if ($product_permalink) {
                            echo wp_kses_post(sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $product_name));
                        } else {
                            echo wp_kses_post($product_name);
                        }
                        do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);
                        echo wc_get_formatted_cart_item_data($cart_item); // phpcs:ignore
                        ?>
                    </div>
                    <div class="ci-price"><?php echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key); // phpcs:ignore ?></div>
                </div>

                <div class="ci-qty">
                    <?php echo apply_filters('woocommerce_cart_item_quantity', $qty_input, $cart_item_key, $cart_item); // phpcs:ignore ?>
                </div>

                <div class="ci-total"><?php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); // phpcs:ignore ?></div>

                <?php echo $remove_link; // phpcs:ignore ?>
            </div>
            <?php
        }
        ?>

        <?php do_action('woocommerce_cart_contents'); ?>

        <div class="cart-actions">
            <?php if (wc_coupons_enabled()) : ?>
                <div class="coupon">
                    <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e('Mã giảm giá', 'underscores'); ?>" />
                    <button type="submit" class="button btn btn-ghost" name="apply_coupon" value="<?php esc_attr_e('Áp dụng', 'underscores'); ?>"><?php esc_html_e('Áp dụng', 'underscores'); ?></button>
                    <?php do_action('woocommerce_cart_coupon'); ?>
                </div>
            <?php endif; ?>

            <button type="submit" class="button update-cart btn btn-ghost" name="update_cart" value="<?php esc_attr_e('Cập nhật giỏ', 'underscores'); ?>"><?php esc_html_e('Cập nhật giỏ', 'underscores'); ?></button>
            <?php do_action('woocommerce_cart_actions'); ?>
            <?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
        </div>

        <?php do_action('woocommerce_after_cart_contents'); ?>
    </div>

    <?php do_action('woocommerce_after_cart_table'); ?>

    <?php do_action('woocommerce_before_cart_collaterals'); ?>
    <div class="cart-collaterals">
        <?php
        /**
         * @hooked woocommerce_cross_sell_display
         * @hooked woocommerce_cart_totals - 10 (→ cart/cart-totals.php override)
         */
        do_action('woocommerce_cart_collaterals');
        ?>
    </div>
</form>

<?php do_action('woocommerce_after_cart'); ?>
