<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * VietQR payment integration for the Bank Transfer (bacs) gateway.
 *
 *   - Registers an ACF options page + fields for bank details.
 *   - Renders a VietQR image + transfer details on the order thank-you page.
 *   - Relabels the bacs gateway title/description to Vietnamese.
 *
 * Extracted from WooHook to keep that class focused on shop/product/cart.
 */
final class PaymentVietQrHook
{
    public static function register(): void
    {
        $self = new self();
        add_action('acf/init', [$self, 'register_settings']);
        add_action('woocommerce_thankyou', [$self, 'render_thankyou'], 10);
        add_filter('woocommerce_gateway_title', [$self, 'gateway_title'], 20, 2);
        add_filter('woocommerce_gateway_description', [$self, 'gateway_description'], 20, 2);
    }

    public function register_settings(): void
    {
        if (function_exists('acf_add_options_page')) {
            acf_add_options_page([
                'page_title' => 'VietQR Settings',
                'menu_title' => 'VietQR Settings',
                'menu_slug'  => 'vietqr-settings',
                // Payment/bank config — restrict to store managers, not authors.
                'capability' => 'manage_woocommerce',
                'redirect'   => false,
            ]);
        }

        if (function_exists('acf_add_local_field_group')) {
            acf_add_local_field_group([
                'key'    => 'group_vietqr_settings',
                'title'  => 'Cấu hình VietQR',
                'fields' => [
                    [
                        'key'          => 'field_vietqr_bank_id',
                        'label'        => 'Mã ngân hàng (Bank ID)',
                        'name'         => 'vietqr_bank_id',
                        'type'         => 'text',
                        'instructions' => 'Ví dụ: MB, VCB, ICB, ACB, TCB, VIB...',
                        'required'     => 1,
                    ],
                    [
                        'key'      => 'field_vietqr_account_number',
                        'label'    => 'Số tài khoản',
                        'name'     => 'vietqr_account_number',
                        'type'     => 'text',
                        'required' => 1,
                    ],
                    [
                        'key'      => 'field_vietqr_account_name',
                        'label'    => 'Tên chủ tài khoản',
                        'name'     => 'vietqr_account_name',
                        'type'     => 'text',
                        'required' => 1,
                    ],
                    [
                        'key'           => 'field_vietqr_template',
                        'label'         => 'Template VietQR',
                        'name'          => 'vietqr_template',
                        'type'          => 'select',
                        'choices'       => [
                            'compact'  => 'Compact (Chi tiết)',
                            'compact2' => 'Compact 2 (Chi tiết dọc)',
                            'qr_only'  => 'QR Only (Chỉ mã QR)',
                            'print'    => 'Print (In ấn)',
                        ],
                        'default_value' => 'compact2',
                    ],
                ],
                'location' => [
                    [
                        [
                            'param'    => 'options_page',
                            'operator' => '==',
                            'value'    => 'vietqr-settings',
                        ],
                    ],
                ],
            ]);
        }
    }

    public function render_thankyou($order_id): void
    {
        $order_id = (int) $order_id;
        if (! $order_id) {
            return;
        }
        $order = wc_get_order($order_id);
        if (! $order) {
            return;
        }

        // Only for Bank Transfer (bacs).
        if ($order->get_payment_method() !== 'bacs') {
            return;
        }

        $get = static function (string $field, string $option_key, string $default = ''): string {
            $val = function_exists('get_field') ? get_field($field, 'option') : null;
            $val = $val ?: get_option($option_key);
            return (string) ($val ?: $default);
        };

        $bank_id        = $get('vietqr_bank_id', 'options_vietqr_bank_id');
        $account_number = $get('vietqr_account_number', 'options_vietqr_account_number');
        $account_name   = $get('vietqr_account_name', 'options_vietqr_account_name');
        $template       = $get('vietqr_template', 'options_vietqr_template', 'compact2');

        if ($bank_id === '' || $account_number === '' || $account_name === '') {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('VietQR: missing bank settings — QR block skipped on thankyou page.');
            }
            return;
        }

        $amount      = $order->get_total();
        $description = 'DH' . $order_id;

        // rawurlencode every interpolated field so a stray space/&/# can't break
        // the QR params or the URL.
        $qr_url = sprintf(
            'https://img.vietqr.io/image/%s-%s-%s.png?amount=%s&addInfo=%s&accountName=%s',
            rawurlencode($bank_id),
            rawurlencode($account_number),
            rawurlencode($template),
            rawurlencode((string) $amount),
            rawurlencode($description),
            rawurlencode($account_name)
        );
        ?>
        <div class="vietqr-payment-info">
            <h3><?php esc_html_e('Thanh toán quét mã VietQR', 'underscores'); ?></h3>
            <p><?php esc_html_e('Quét mã QR dưới đây bằng ứng dụng Ngân hàng để thanh toán nhanh chóng và chính xác số tiền.', 'underscores'); ?></p>

            <div class="vietqr-qr-wrap">
                <img class="vietqr-qr-img" src="<?php echo esc_url($qr_url); ?>" alt="<?php esc_attr_e('Mã VietQR thanh toán', 'underscores'); ?>" />
            </div>

            <div class="vietqr-details">
                <div><strong><?php esc_html_e('Ngân hàng:', 'underscores'); ?></strong> <?php echo esc_html($bank_id); ?></div>
                <div><strong><?php esc_html_e('Số tài khoản:', 'underscores'); ?></strong> <?php echo esc_html($account_number); ?></div>
                <div><strong><?php esc_html_e('Tên chủ tài khoản:', 'underscores'); ?></strong> <?php echo esc_html($account_name); ?></div>
                <div><strong><?php esc_html_e('Số tiền chuyển khoản:', 'underscores'); ?></strong> <span class="vietqr-amount"><?php echo wp_kses_post(wc_price($amount)); ?></span></div>
                <div><strong><?php esc_html_e('Nội dung chuyển khoản:', 'underscores'); ?></strong> <span class="vietqr-transfer-note"><?php echo esc_html($description); ?></span></div>
            </div>
        </div>
        <?php
    }

    public function gateway_title($title, $gateway_id): string
    {
        if ($gateway_id === 'bacs') {
            return __('Chuyển khoản ngân hàng', 'underscores');
        }
        return (string) $title;
    }

    public function gateway_description($description, $gateway_id): string
    {
        if ($gateway_id === 'bacs') {
            return __('Quét mã VietQR hoặc chuyển khoản trực tiếp vào tài khoản ngân hàng của chúng tôi. Vui lòng nhập đúng Mã đơn hàng làm nội dung chuyển khoản để đơn hàng được xác nhận tự động.', 'underscores');
        }
        return (string) $description;
    }
}
