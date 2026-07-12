<?php
/**
 * Footer — Section 2: Products menu (location: footer-products).
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

if (! has_nav_menu('footer-products')) {
    return;
}
?>
<div class="foot-col foot-col--products">
    <h5><?php esc_html_e('Sản phẩm', 'underscores'); ?></h5>
    <?php
    wp_nav_menu([
        'theme_location' => 'footer-products',
        'container'      => false,
        'menu_class'     => 'foot-list',
        'fallback_cb'    => false,
        'depth'          => 1,
    ]);
    ?>
</div>
