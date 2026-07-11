<?php

/**
 * Lost password form — Pixel Cam .auth-narrow.
 *
 * Keeps Woo's reset mechanism (user_login field, wc_reset_password flag, nonce,
 * hooks); only the markup is the design's narrow auth card.
 *
 * @package Underscores
 * @see https://woocommerce.com/document/template-structure/
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_lost_password_form');
?>
<div class="auth-narrow">
    <h1><?php esc_html_e('Quên mật khẩu', 'underscores'); ?></h1>
    <p class="auth-sub"><?php esc_html_e('Nhập email hoặc tên đăng nhập. Chúng tôi sẽ gửi liên kết đặt lại mật khẩu.', 'underscores'); ?></p>

    <form method="post" class="woocommerce-ResetPassword lost_reset_password auth-form">
        <label class="field">
            <span class="field-label"><?php esc_html_e('Email hoặc tên đăng nhập', 'underscores'); ?></span>
            <input class="woocommerce-Input input-text" type="text" name="user_login" id="user_login" autocomplete="username" required />
        </label>

        <?php do_action('woocommerce_lostpassword_form'); ?>

        <input type="hidden" name="wc_reset_password" value="true" />
        <?php wp_nonce_field('lost_password', 'woocommerce-lost-password-nonce'); ?>
        <button type="submit" class="btn btn-primary btn-block" value="<?php esc_attr_e('Gửi liên kết', 'underscores'); ?>"><?php esc_html_e('Gửi liên kết đặt lại', 'underscores'); ?></button>
    </form>

    <p class="auth-foot"><a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="auth-link">&larr; <?php esc_html_e('Quay lại đăng nhập', 'underscores'); ?></a></p>
</div>
<?php do_action('woocommerce_after_lost_password_form'); ?>
