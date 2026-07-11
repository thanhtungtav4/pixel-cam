<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * Security hardening (ported + adapted from i-dent for WooCommerce).
 *
 *   - Disable XML-RPC + pingback, block sensitive files.
 *   - Hide REST user-enumeration endpoints.
 *   - Security response headers (HSTS, nosniff, X-Frame, Referrer,
 *     Permissions-Policy) with long-cache headers for static assets.
 *
 * WooCommerce notes:
 *   - Permissions-Policy keeps payment=(self) so Woo/Stripe/PayPal payment
 *     request APIs keep working (i-dent used payment=() which breaks them).
 *   - CSP is OFF by default. A store-wide CSP that isn't tuned to your payment
 *     gateway WILL break checkout, so it is opt-in: return a policy string from
 *     the `underscores_security_csp` filter to enable it.
 *   - Author archives are NOT redirected (Woo has real customer accounts) —
 *     only the REST users list is hidden.
 */
final class SecurityHook
{
    public static function register(): void
    {
        $self = new self();

        add_filter('xmlrpc_enabled', '__return_false');
        add_filter('wp_headers', [$self, 'strip_pingback_header']);
        add_filter('xmlrpc_methods', [$self, 'strip_pingback_methods']);

        add_action('init', [$self, 'block_sensitive_pagenow']);
        add_action('template_redirect', [$self, 'block_sensitive_paths']);

        add_filter('rest_endpoints', [$self, 'hide_user_endpoints']);

        if (! is_admin()) {
            add_action('send_headers', [$self, 'send_security_headers'], 1);
        }
    }

    /**
     * @param array<string,string> $headers
     * @return array<string,string>
     */
    public function strip_pingback_header(array $headers): array
    {
        unset($headers['X-Pingback']);
        return $headers;
    }

    /**
     * @param array<string,mixed> $methods
     * @return array<string,mixed>
     */
    public function strip_pingback_methods(array $methods): array
    {
        unset($methods['pingback.ping'], $methods['pingback.extensions.getPingbacks']);
        return $methods;
    }

    public function block_sensitive_pagenow(): void
    {
        global $pagenow;
        $blocked = ['xmlrpc.php', 'wp-trackback.php', 'wp-links-opml.php'];
        if (! empty($pagenow) && in_array($pagenow, $blocked, true)) {
            status_header(404);
            nocache_headers();
            include get_404_template();
            exit;
        }
    }

    public function block_sensitive_paths(): void
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        foreach (['/readme.html', '/license.txt'] as $path) {
            if ($uri === $path || strpos($uri, $path) === 0) {
                status_header(404);
                nocache_headers();
                include get_404_template();
                exit;
            }
        }
    }

    /**
     * @param array<string,mixed> $endpoints
     * @return array<string,mixed>
     */
    public function hide_user_endpoints(array $endpoints): array
    {
        foreach (['/wp/v2/users', '/wp/v2/users/(?P<id>[\d]+)'] as $route) {
            unset($endpoints[$route]);
        }
        return $endpoints;
    }

    public function send_security_headers(): void
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';
        $ext  = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        $static = ['css', 'js', 'mjs', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'otf', 'mp4', 'webm', 'mp3', 'ogg', 'pdf', 'map'];

        header('X-Content-Type-Options: nosniff');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-Frame-Options: SAMEORIGIN');

        if (in_array($ext, $static, true)) {
            header('Cache-Control: public, max-age=31536000, immutable');
            return;
        }

        // Keep payment=(self) so WooCommerce/Stripe Payment Request works.
        header("Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=(self)");

        // CSP is opt-in — an untuned policy breaks checkout. Provide one via:
        //   add_filter('underscores_security_csp', fn() => "default-src 'self'; ...");
        $csp = (string) apply_filters('underscores_security_csp', '');
        if ($csp !== '') {
            $report_only = (bool) apply_filters('underscores_security_csp_report_only', false);
            header(($report_only ? 'Content-Security-Policy-Report-Only: ' : 'Content-Security-Policy: ') . $csp);
        }
    }
}
