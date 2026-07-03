<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * About page: body class + page-scoped assets.
 */
final class AboutPageHook
{
    private const TEMPLATE = 'page-template/template-about.php';

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

        $classes[] = 'underscores-about-page';

        return $classes;
    }

    public function enqueue_assets(): void
    {
        if (! is_page_template(self::TEMPLATE)) {
            return;
        }

        $css_relative_path = 'assets/css/pages/about.css';
        $js_relative_path = 'assets/scripts/pages/about.js';

        if (file_exists(underscores_child_asset_path($css_relative_path))) {
            wp_enqueue_style(
                'underscores-page-about-style',
                underscores_child_asset_uri($css_relative_path),
                ['underscores-child-style'],
                underscores_child_asset_version($css_relative_path)
            );
        }

        if (file_exists(underscores_child_asset_path($js_relative_path))) {
            wp_enqueue_script(
                'underscores-page-about-script',
                underscores_child_asset_uri($js_relative_path),
                ['underscores-child-script'],
                underscores_child_asset_version($js_relative_path),
                true
            );
        }
    }
}
