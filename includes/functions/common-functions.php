<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('underscores_get_option')) {
    function underscores_get_option(string $field_name, $default = null)
    {
        // ACF must be booted: get_field('option') resolves the options post id via
        // acf(), which is only a real object after acf/init has fired.
        if (!function_exists('get_field') || !function_exists('acf') || !is_object(acf()) || !did_action('acf/init')) {
            return $default;
        }

        $value = get_field($field_name, 'option');

        if ($value === null || $value === '' || $value === []) {
            return $default;
        }

        return $value;
    }
}

if (!function_exists('underscores_child_acf_link')) {
    /**
     * Normalize an ACF link (return_format=array) into url/title/target.
     * Returns null when the link is empty so callers can hide the block.
     *
     * @param mixed $link
     * @return array{url:string,title:string,target:string}|null
     */
    function underscores_child_acf_link($link, string $default_title = ''): ?array
    {
        if (empty($link['url'])) {
            return null;
        }

        return [
            'url'    => esc_url($link['url']),
            'title'  => $link['title'] !== '' ? $link['title'] : $default_title,
            'target' => $link['target'] ?? '',
        ];
    }
}

if (!function_exists('underscores_child_asset_path')) {
    function underscores_child_asset_path(string $relative_path): string
    {
        return UNDERSCORES_CHILD_THEME_PATH . '/' . ltrim($relative_path, '/');
    }
}

if (!function_exists('underscores_child_asset_uri')) {
    function underscores_child_asset_uri(string $relative_path): string
    {
        return UNDERSCORES_CHILD_THEME_URI . '/' . ltrim($relative_path, '/');
    }
}

if (!function_exists('underscores_child_asset_version')) {
    function underscores_child_asset_version(string $relative_path): string
    {
        $asset_path = underscores_child_asset_path($relative_path);

        if (file_exists($asset_path)) {
            return (string) filemtime($asset_path);
        }

        return UNDERSCORES_CHILD_THEME_VERSION ?: '1.0.0';
    }
}
