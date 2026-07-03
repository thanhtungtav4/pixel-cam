<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('UNDERSCORES_CHILD_THEME_VERSION')) {
    define('UNDERSCORES_CHILD_THEME_VERSION', (string) wp_get_theme(get_stylesheet())->get('Version'));
}

if (!defined('UNDERSCORES_CHILD_THEME_PATH')) {
    define('UNDERSCORES_CHILD_THEME_PATH', get_stylesheet_directory());
}

if (!defined('UNDERSCORES_CHILD_THEME_URI')) {
    define('UNDERSCORES_CHILD_THEME_URI', get_stylesheet_directory_uri());
}

if (!defined('UNDERSCORES_CHILD_THEME_APP_PATH')) {
    define('UNDERSCORES_CHILD_THEME_APP_PATH', UNDERSCORES_CHILD_THEME_PATH . '/app');
}

if (!defined('UNDERSCORES_CHILD_THEME_INCLUDES_PATH')) {
    define('UNDERSCORES_CHILD_THEME_INCLUDES_PATH', UNDERSCORES_CHILD_THEME_PATH . '/includes');
}

if (!defined('UNDERSCORES_CHILD_THEME_STUB_PATH')) {
    define('UNDERSCORES_CHILD_THEME_STUB_PATH', UNDERSCORES_CHILD_THEME_PATH . '/stubs');
}

// ponytail: config_path is a dead field on the scaffold generator; point it at stubs so CLI boots.
if (!defined('UNDERSCORES_CHILD_THEME_CONFIG_PATH')) {
    define('UNDERSCORES_CHILD_THEME_CONFIG_PATH', UNDERSCORES_CHILD_THEME_STUB_PATH);
}

if (!function_exists('get_main_class')) {
    function get_main_class($css_class = ''): string
    {
        $classes = ['main'];
        $registered_classes = $GLOBALS['underscores_child_main_class'] ?? '';

        if (!empty($registered_classes)) {
            if (!is_array($registered_classes)) {
                $registered_classes = preg_split('#\s+#', (string) $registered_classes);
            }

            $classes = array_merge($classes, $registered_classes);
        }

        if (!empty($css_class)) {
            if (!is_array($css_class)) {
                $css_class = preg_split('#\s+#', (string) $css_class);
            }

            $classes = array_merge($classes, $css_class);
        }

        $classes = apply_filters('main_class', $classes, $css_class);
        $classes = array_filter(array_map('sanitize_html_class', array_unique((array) $classes)));

        if ($classes === []) {
            return '';
        }

        return sprintf('class="%s"', esc_attr(implode(' ', $classes)));
    }
}

if (!function_exists('main_class')) {
    function main_class($css_class = ''): void
    {
        echo get_main_class($css_class);
    }
}

if (!function_exists('underscores_child_set_main_class')) {
    function underscores_child_set_main_class($css_class = ''): void
    {
        $GLOBALS['underscores_child_main_class'] = $css_class;
    }
}

function underscores_child_bootstrap(): void
{
    static $bootstrapped = false;

    if ($bootstrapped) {
        return;
    }

    $bootstrapped = true;

    $init_file = UNDERSCORES_CHILD_THEME_INCLUDES_PATH . '/bootstrap.php';

    if (file_exists($init_file)) {
        require_once $init_file;
    }
}

add_action('after_setup_theme', 'underscores_child_bootstrap', 0);
