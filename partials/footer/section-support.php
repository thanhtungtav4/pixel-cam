<?php
/**
 * Footer — Section 3: Support menu (location: footer-support).
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

if (! has_nav_menu('footer-support')) {
    return;
}
?>
<div class="foot-col foot-col--support">
    <h2 class="foot-heading"><?php esc_html_e('Hỗ trợ', 'underscores'); ?></h2>
    <?php
    wp_nav_menu([
        'theme_location' => 'footer-support',
        'container'      => false,
        'menu_class'     => 'foot-list',
        'fallback_cb'    => false,
        'depth'          => 1,
    ]);
    ?>
</div>
