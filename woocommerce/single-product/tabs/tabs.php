<?php
/**
 * Single product tabs — Pixel Cam.
 *
 * Overrides the Woo default tab template (`<ul class="wc-tabs">` + anchors)
 * with a vjshop-style horizontal pill nav + panel structure that the
 * `initPdpTabs()` JS hook can toggle without a hash-change.
 *
 * Tab definitions come from the same `woocommerce_product_tabs` filter as
 * Woo default — the callbacks we register in WooHook::product_tabs() are
 * invoked here exactly like Woo would.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

/**
 * @var array<string,array<string,mixed>> $product_tabs
 */
$product_tabs = apply_filters('woocommerce_product_tabs', []);

if (empty($product_tabs)) {
    return;
}
?>

<div class="pdp-tabs" data-pdp-tabs>
    <div class="tabbar" role="tablist" aria-label="<?php esc_attr_e('Thông tin sản phẩm', 'underscores'); ?>">
        <?php $first = true; ?>
        <?php foreach ($product_tabs as $key => $product_tab) :
            $tab_id    = 'tab-' . sanitize_key($key);
            $btn_id    = 'tabbtn-' . sanitize_key($key);
            $is_active = $first;
            $first     = false;
        ?>
            <button
                type="button"
                id="<?php echo esc_attr($btn_id); ?>"
                class="tabbar__btn<?php echo $is_active ? ' on' : ''; ?>"
                data-tab="<?php echo esc_attr($key); ?>"
                role="tab"
                aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                aria-controls="<?php echo esc_attr($tab_id); ?>"
                tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
            >
                <?php echo wp_kses_post(apply_filters('woocommerce_product_' . $key . '_tab_title', $product_tab['title'], $key)); ?>
            </button>
        <?php endforeach; ?>
    </div>

    <?php $first = true; ?>
    <?php foreach ($product_tabs as $key => $product_tab) :
        $tab_id    = 'tab-' . sanitize_key($key);
        $btn_id    = 'tabbtn-' . sanitize_key($key);
        $is_active = $first;
        $first     = false;
    ?>
        <div
            id="<?php echo esc_attr($tab_id); ?>"
            class="panel woocommerce-Tabs-panel--<?php echo esc_attr($key); ?><?php echo $is_active ? ' on' : ''; ?>"
            role="tabpanel"
            aria-labelledby="<?php echo esc_attr($btn_id); ?>"
            tabindex="0"
            <?php echo $is_active ? '' : 'hidden'; ?>
        >
            <?php
            if (isset($product_tab['callback'])) {
                call_user_func($product_tab['callback'], $key, $product_tab);
            }
            ?>
        </div>
    <?php endforeach; ?>

    <?php do_action('woocommerce_product_after_tabs'); ?>
</div>
