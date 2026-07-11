<?php
/**
 * Shipping Methods Display — Pixel Cam clean override.
 *
 * Shows only the shipping method list (name + cost).
 * Removes the destination address text, the "Change address" link,
 * and the full shipping calculator form so the cart summary stays
 * clean.  Customers choose their address at Checkout instead.
 *
 * @package Underscores
 * @see     https://woocommerce.com/document/template-structure/
 * @version 8.8.0
 */

defined( 'ABSPATH' ) || exit;

$has_calculated_shipping = ! empty( $has_calculated_shipping );
?>
<span class="shipping-label"><?php echo wp_kses_post( $package_name ); ?></span>

<?php if ( ! empty( $available_methods ) && is_array( $available_methods ) ) : ?>
    <ul id="shipping_method" class="woocommerce-shipping-methods">
        <?php foreach ( $available_methods as $method ) : ?>
            <li>
                <?php
                if ( 1 < count( $available_methods ) ) {
                    printf(
                        '<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />',
                        $index,
                        esc_attr( sanitize_title( $method->id ) ),
                        esc_attr( $method->id ),
                        checked( $method->id, $chosen_method, false )
                    );
                } else {
                    printf(
                        '<input type="hidden" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" />',
                        $index,
                        esc_attr( sanitize_title( $method->id ) ),
                        esc_attr( $method->id )
                    );
                }
                printf(
                    '<label for="shipping_method_%1$s_%2$s">%3$s</label>',
                    $index,
                    esc_attr( sanitize_title( $method->id ) ),
                    wc_cart_totals_shipping_method_label( $method )
                );
                do_action( 'woocommerce_after_shipping_rate', $method, $index );
                ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php
elseif ( ! $has_calculated_shipping || ! isset( $formatted_destination ) || ! $formatted_destination ) :
    echo wp_kses_post(
        apply_filters(
            'woocommerce_shipping_may_be_available_html',
            __( 'Shipping options will be updated during checkout.', 'woocommerce' )
        )
    );
else :
    echo wp_kses_post(
        apply_filters(
            'woocommerce_cart_no_shipping_available_html',
            sprintf(
                esc_html__( 'No shipping options were found for %s.', 'woocommerce' ) . ' ',
                '<strong>' . esc_html( $formatted_destination ) . '</strong>'
            ),
            $formatted_destination
        )
    );
endif;
