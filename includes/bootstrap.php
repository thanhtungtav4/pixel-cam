<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Bootstrap the child theme.
 *
 * Namespaced classes (Theme\Child\) autoload via the parent composer PSR-4 map.
 * Procedural helpers + ACF loader are plain requires.
 */

// Procedural helpers (used as template tags / hook callbacks).
require_once UNDERSCORES_CHILD_THEME_INCLUDES_PATH . '/functions/common-functions.php';
require_once UNDERSCORES_CHILD_THEME_INCLUDES_PATH . '/functions/performance-functions.php';
require_once UNDERSCORES_CHILD_THEME_INCLUDES_PATH . '/functions/template-functions.php';

// Register hook + ACF classes.
\Theme\Child\Acf\LocalJson::register();
\Theme\Child\Hooks\PerformanceHook::register();
\Theme\Child\Hooks\ThemeHook::register();
\Theme\Child\Hooks\WooHook::register();
\Theme\Child\Hooks\SeoHook::register();
\Theme\Child\Hooks\AboutPageHook::register();
\Theme\Child\Hooks\ContactPageHook::register();

// WP-CLI scaffolder (global classes, dev tooling only).
// Scaffolds page template/partial/ACF/assets only; wire page hooks manually under app/Hooks/.
if (defined('WP_CLI') && WP_CLI) {
    require_once UNDERSCORES_CHILD_THEME_INCLUDES_PATH . '/classes/class-child-scaffold-generator.php';
    require_once UNDERSCORES_CHILD_THEME_INCLUDES_PATH . '/classes/class-child-cli.php';

    if (class_exists('Underscores_Child_CLI')) {
        Underscores_Child_CLI::get_instance();
    }
}
