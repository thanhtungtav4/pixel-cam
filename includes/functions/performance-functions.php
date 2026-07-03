<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('underscores_child_mark_style_loading_strategy')) {
    function underscores_child_mark_style_loading_strategy(string $handle, string $strategy): void
    {
        if (!in_array($strategy, ['preload', 'media'], true)) {
            return;
        }

        if (!isset($GLOBALS['underscores_child_style_loading_strategies']) || !is_array($GLOBALS['underscores_child_style_loading_strategies'])) {
            $GLOBALS['underscores_child_style_loading_strategies'] = [];
        }

        $GLOBALS['underscores_child_style_loading_strategies'][$handle] = $strategy;
    }
}

if (!function_exists('underscores_child_get_style_loading_strategies')) {
    function underscores_child_get_style_loading_strategies(): array
    {
        $strategies = $GLOBALS['underscores_child_style_loading_strategies'] ?? [];
        $strategies = apply_filters('underscores_child_style_loading_strategies', $strategies);

        if (!is_array($strategies)) {
            return [];
        }

        $normalized = [];

        foreach ($strategies as $handle => $strategy) {
            if (!is_string($handle) || !is_string($strategy)) {
                continue;
            }

            if (!in_array($strategy, ['preload', 'media'], true)) {
                continue;
            }

            $normalized[$handle] = $strategy;
        }

        return $normalized;
    }
}

if (!function_exists('underscores_child_mark_script_loading_strategy')) {
    function underscores_child_mark_script_loading_strategy(string $handle, string $strategy): void
    {
        if (!in_array($strategy, ['defer', 'async'], true)) {
            return;
        }

        if (!isset($GLOBALS['underscores_child_script_loading_strategies']) || !is_array($GLOBALS['underscores_child_script_loading_strategies'])) {
            $GLOBALS['underscores_child_script_loading_strategies'] = [];
        }

        $GLOBALS['underscores_child_script_loading_strategies'][$handle] = $strategy;
    }
}

if (!function_exists('underscores_child_get_script_loading_strategies')) {
    function underscores_child_get_script_loading_strategies(): array
    {
        $strategies = $GLOBALS['underscores_child_script_loading_strategies'] ?? [];
        $strategies = apply_filters('underscores_child_script_loading_strategies', $strategies);

        if (!is_array($strategies)) {
            return [];
        }

        $normalized = [];

        foreach ($strategies as $handle => $strategy) {
            if (!is_string($handle) || !is_string($strategy)) {
                continue;
            }

            if (!in_array($strategy, ['defer', 'async'], true)) {
                continue;
            }

            $normalized[$handle] = $strategy;
        }

        return $normalized;
    }
}

if (!function_exists('underscores_child_get_protected_script_handles')) {
    function underscores_child_get_protected_script_handles(): array
    {
        $handles = apply_filters('underscores_child_protected_script_handles', [
            'jquery-migrate',
            'wp-hooks',
            'wp-i18n',
            'contact-form-7',
            'swv',
            'wpcf7-recaptcha',
            'google-recaptcha',
        ]);

        if (!is_array($handles)) {
            return [];
        }

        return array_values(array_filter($handles, 'is_string'));
    }
}

if (!function_exists('underscores_child_get_current_template_slug')) {
    function underscores_child_get_current_template_slug(): ?string
    {
        if (is_front_page()) {
            return 'front-page';
        }

        if (!is_singular()) {
            return null;
        }

        $template = get_page_template_slug(get_queried_object_id());

        if (!$template) {
            return null;
        }

        $basename = basename($template, '.php');
        $slug = preg_replace('/^template-/', '', $basename);

        return $slug ?: null;
    }
}

if (!function_exists('underscores_child_get_critical_css_path')) {
    function underscores_child_get_critical_css_path(): ?string
    {
        $filtered_path = apply_filters('underscores_child_critical_css_path', null);

        if (is_string($filtered_path) && $filtered_path !== '' && file_exists($filtered_path)) {
            return $filtered_path;
        }

        $slug = underscores_child_get_current_template_slug();

        if (!$slug) {
            return null;
        }

        $critical_css_path = underscores_child_asset_path('assets/css/critical/' . $slug . '.css');

        if (!file_exists($critical_css_path)) {
            return null;
        }

        return $critical_css_path;
    }
}

if (!function_exists('underscores_child_get_critical_css_contents')) {
    function underscores_child_get_critical_css_contents(): string
    {
        static $critical_css = null;

        if ($critical_css !== null) {
            return $critical_css;
        }

        $critical_css_path = underscores_child_get_critical_css_path();

        if (!$critical_css_path) {
            $critical_css = '';
            return $critical_css;
        }

        $contents = file_get_contents($critical_css_path);
        $critical_css = is_string($contents) ? trim($contents) : '';

        return $critical_css;
    }
}
