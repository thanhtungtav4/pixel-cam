<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * Contact page: body class + page-scoped assets.
 */
final class ContactPageHook
{
    private const TEMPLATE = 'page-template/template-contact.php';

    public static function register(): void
    {
        $self = new self();
        add_filter('main_class', [$self, 'main_class']);
        add_action('wp_enqueue_scripts', [$self, 'enqueue_assets'], 30);
    }

    public function main_class(array $classes): array
    {
        if (! is_page_template(self::TEMPLATE)) {
            return $classes;
        }

        $classes[] = 'underscores-contact-page';

        return $classes;
    }

    public function enqueue_assets(): void
    {
        if (! is_page_template(self::TEMPLATE)) {
            return;
        }

        $css_relative_path = 'assets/css/pages/contact.css';
        $js_relative_path = 'assets/scripts/pages/contact.js';

        if (file_exists(underscores_child_asset_path($css_relative_path))) {
            wp_enqueue_style(
                'underscores-page-contact-style',
                add_query_arg('v', underscores_child_asset_version($css_relative_path), underscores_child_asset_uri($css_relative_path)),
                ['pixel-cam'],
                null
            );
        }

        if (file_exists(underscores_child_asset_path($js_relative_path))) {
            wp_enqueue_script(
                'underscores-page-contact-script',
                add_query_arg('v', underscores_child_asset_version($js_relative_path), underscores_child_asset_uri($js_relative_path)),
                ['pixel-cam'],
                null,
                true
            );
        }
    }
}
