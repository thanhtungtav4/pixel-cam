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

// Scaffold-generator CONST: the class-child-scaffold-generator.php constructor
// requires a $config_path arg but doesn't actually read it (kept for ABI
// stability with a future i-dent-derived release). Pointing it at the stubs
// directory satisfies the constructor while the field itself is dead — the
// scaffolder reads from the child theme root, not the config dir.
if (!defined('UNDERSCORES_CHILD_THEME_CONFIG_PATH')) {
    define('UNDERSCORES_CHILD_THEME_CONFIG_PATH', UNDERSCORES_CHILD_THEME_STUB_PATH);
}

// Template tags get_main_class()/main_class()/underscores_child_set_main_class()
// live in includes/functions/template-functions.php (loaded by bootstrap.php).
// functions.php stays constants + bootstrap only.

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

/**
 * Load the theme's text domain so __() / esc_html__() / etc. resolve from
 * languages/underscores-{locale}.mo.
 *
 * Priority 10 (default) — runs after parent theme's after_setup_theme (which
 * is also @10) so the child .mo wins for matching strings, but the parent
 * fallback is still available for strings the child didn't override.
 */
function underscores_child_load_textdomain(): void
{
    load_theme_textdomain(
        'underscores',
        UNDERSCORES_CHILD_THEME_PATH . '/languages'
    );
}
add_action('after_setup_theme', 'underscores_child_load_textdomain', 10);

add_action('after_setup_theme', 'underscores_child_bootstrap', 0);
