<?php

declare(strict_types=1);

namespace Theme\Child\Acf;

defined('ABSPATH') || exit;

/**
 * ACF Local JSON: save/load field groups from the child theme `acf-json/` dir,
 * and register the theme options page (Local JSON does not store options pages).
 */
final class LocalJson
{
    public static function register(): void
    {
        $self = new self();
        add_filter('acf/settings/save_json', [$self, 'save_path']);
        add_filter('acf/settings/load_json', [$self, 'load_paths']);
        add_action('acf/init', [$self, 'register_options_page']);
    }

    public function save_path(string $path): string
    {
        // ponytail: single save dir; split per-group only if the folder gets unwieldy.
        return UNDERSCORES_CHILD_THEME_PATH . '/acf-json';
    }

    /**
     * @param array<int,string> $paths
     * @return array<int,string>
     */
    public function load_paths(array $paths): array
    {
        $paths[] = UNDERSCORES_CHILD_THEME_PATH . '/acf-json';
        return $paths;
    }

    public function register_options_page(): void
    {
        if (! function_exists('acf_add_options_page')) {
            return;
        }

        acf_add_options_page([
            'page_title' => 'Theme Settings',
            'menu_title' => 'Theme Settings',
            'menu_slug'  => 'theme-setting',
            'capability' => 'edit_posts',
            'redirect'   => false,
        ]);
    }
}
