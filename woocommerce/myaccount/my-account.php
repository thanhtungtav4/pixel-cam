<?php

/**
 * My Account — Pixel Cam 2-column layout (nav | content).
 *
 * Keeps Woo's account navigation + content hooks intact; only wraps them in the
 * design's .account-layout grid.
 *
 * @package Underscores
 * @see https://woocommerce.com/document/template-structure/
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$name         = $current_user->display_name ?: $current_user->user_login;
$email        = $current_user->user_email;
$first_letter = strtoupper(substr($current_user->user_login, 0, 1));
?>
<div class="account-layout">
    <aside class="acc-side">
        <div class="acc-user">
            <span class="acc-avatar"><?php echo esc_html($first_letter); ?></span>
            <div class="acc-info">
                <b><?php echo esc_html($name); ?></b>
                <small><?php echo esc_html($email); ?></small>
            </div>
        </div>
        <?php do_action('woocommerce_account_navigation'); ?>
    </aside>

    <div class="woocommerce-MyAccount-content">
        <?php do_action('woocommerce_account_content'); ?>
    </div>
</div>
