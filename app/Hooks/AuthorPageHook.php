<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * Author archive page (author.php).
 *
 * Enqueues the page-specific stylesheet on /author/{user_nicename}/ pages.
 * Data fields live in `acf-json/group_user_author.json` and are read directly
 * from the partial — no server-side render hook needed.
 */
final class AuthorPageHook
{
    public static function register(): void
    {
        $self = new self();
        add_filter('main_class', [$self, 'main_class']);
        add_action('wp_enqueue_scripts', [$self, 'enqueue_assets'], 30);
    }

    public function main_class(array $classes): array
    {
        if (! is_author()) {
            return $classes;
        }

        $classes[] = 'underscores-author-page';

        return $classes;
    }

    public function enqueue_assets(): void
    {
        if (! is_author()) {
            return;
        }

        $css_relative_path = 'assets/css/pages/author.css';

        if (! file_exists(underscores_child_asset_path($css_relative_path))) {
            return;
        }

        // No deps: 'underscores-child-style' is not registered (child theme uses 'pixel-cam' as
        // the common stylesheet handle). Passing a non-existent dependency handle causes WP to
        // silently skip outputting the stylesheet — confirmed on live 2026-07-12.
        wp_enqueue_style(
            'underscores-page-author-style',
            underscores_child_asset_uri($css_relative_path),
            [],
            underscores_child_asset_version($css_relative_path)
        );
    }
}
