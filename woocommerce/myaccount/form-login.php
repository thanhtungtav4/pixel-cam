<?php

/**
 * Login + Register — Pixel Cam .auth-narrow tabbed/toggled layout.
 *
 * Overrides Woo's default display with a toggled single auth view.
 *
 * @package Underscores
 * @see https://woocommerce.com/document/template-structure/
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_customer_login_form');

$registration_enabled = 'yes' === get_option('woocommerce_enable_myaccount_registration');
$show_register = isset($_POST['register']) || (isset($_GET['action']) && $_GET['action'] === 'register');
?>

<div class="auth-narrow">

    <!-- LOGIN CONTAINER -->
    <div class="auth-login-box" style="display: <?php echo $show_register ? 'none' : 'block'; ?>;">
        <h1><?php esc_html_e('Đăng nhập', 'underscores'); ?></h1>
        <p class="auth-sub"><?php esc_html_e('Chào mừng trở lại. Đăng nhập để tiếp tục mua sắm.', 'underscores'); ?></p>

        <form class="woocommerce-form woocommerce-form-login login auth-form" method="post" novalidate>
            <?php do_action('woocommerce_login_form_start'); ?>

            <label class="field">
                <span class="field-label"><?php esc_html_e('Email hoặc tên đăng nhập', 'underscores'); ?></span>
                <input type="text" class="woocommerce-Input input-text" name="username" id="username" autocomplete="username"
                    value="<?php echo (! empty($_POST['username']) && is_string($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; // phpcs:ignore ?>" required />
            </label>

            <label class="field">
                <span class="field-label"><?php esc_html_e('Mật khẩu', 'underscores'); ?></span>
                <input class="woocommerce-Input input-text" type="password" name="password" id="password" autocomplete="current-password" required />
            </label>

            <?php do_action('woocommerce_login_form'); ?>

            <div class="auth-row">
                <label class="checkbox"><input type="checkbox" name="rememberme" id="rememberme" value="forever"> <?php esc_html_e('Nhớ tôi', 'underscores'); ?></label>
                <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="auth-link"><?php esc_html_e('Quên mật khẩu?', 'underscores'); ?></a>
            </div>

            <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
            <button type="submit" class="btn btn-primary btn-block woocommerce-form-login__submit" name="login" value="<?php esc_attr_e('Đăng nhập', 'underscores'); ?>"><?php esc_html_e('Đăng nhập', 'underscores'); ?></button>

            <?php do_action('woocommerce_login_form_end'); ?>
        </form>

        <?php if ($registration_enabled) : ?>
            <p class="auth-foot">
                <?php esc_html_e('Chưa có tài khoản?', 'underscores'); ?>
                <a href="#" class="auth-toggle-btn auth-link" data-target="register"><?php esc_html_e('Đăng ký ngay', 'underscores'); ?></a>
            </p>
        <?php endif; ?>
    </div>

    <!-- REGISTER CONTAINER -->
    <?php if ($registration_enabled) : ?>
        <div class="auth-register-box" style="display: <?php echo $show_register ? 'block' : 'none'; ?>;">
            <h1 class="auth-register-heading"><?php esc_html_e('Đăng ký', 'underscores'); ?></h1>
            <p class="auth-sub"><?php esc_html_e('Tạo tài khoản mới để tích điểm và nhận ưu đãi.', 'underscores'); ?></p>

            <form method="post" class="woocommerce-form woocommerce-form-register register auth-form" <?php do_action('woocommerce_register_form_tag'); ?>>
                <?php do_action('woocommerce_register_form_start'); ?>

                <?php if ('no' === get_option('woocommerce_registration_generate_username')) : ?>
                    <label class="field">
                        <span class="field-label"><?php esc_html_e('Tên đăng nhập', 'underscores'); ?></span>
                        <input type="text" class="woocommerce-Input input-text" name="username" id="reg_username" autocomplete="username"
                            value="<?php echo (! empty($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; // phpcs:ignore ?>" required />
                    </label>
                <?php endif; ?>

                <label class="field">
                    <span class="field-label"><?php esc_html_e('Email', 'underscores'); ?></span>
                    <input type="email" class="woocommerce-Input input-text" name="email" id="reg_email" autocomplete="email"
                        value="<?php echo (! empty($_POST['email'])) ? esc_attr(wp_unslash($_POST['email'])) : ''; // phpcs:ignore ?>" required />
                </label>

                <?php if ('no' === get_option('woocommerce_registration_generate_password')) : ?>
                    <label class="field">
                        <span class="field-label"><?php esc_html_e('Mật khẩu', 'underscores'); ?></span>
                        <input class="woocommerce-Input input-text" type="password" name="password" id="reg_password" autocomplete="new-password" required />
                    </label>
                <?php else : ?>
                    <p class="auth-note"><?php esc_html_e('Liên kết đặt mật khẩu sẽ được gửi tới email của bạn.', 'underscores'); ?></p>
                <?php endif; ?>

                <?php do_action('woocommerce_register_form'); ?>

                <?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
                <button type="submit" class="btn btn-primary btn-block woocommerce-form-register__submit" name="register" value="<?php esc_attr_e('Đăng ký', 'underscores'); ?>"><?php esc_html_e('Đăng ký', 'underscores'); ?></button>

                <?php do_action('woocommerce_register_form_end'); ?>
            </form>

            <p class="auth-foot">
                <?php esc_html_e('Đã có tài khoản?', 'underscores'); ?>
                <a href="#" class="auth-toggle-btn auth-link" data-target="login"><?php esc_html_e('Đăng nhập', 'underscores'); ?></a>
            </p>
        </div>
    <?php endif; ?>

</div>

<?php
// Login/register tab toggle JS is enqueued by Theme\Child\Hooks\WooAccountHook
// (assets/scripts/woocommerce/auth-toggle.js). Do not inline JS here — breaks
// strict CSP and violates WPCS.
do_action('woocommerce_after_customer_login_form');
?>
