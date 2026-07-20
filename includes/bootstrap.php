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
// Woo concerns split by surface area (see PR notes):
\Theme\Child\Hooks\WooTemplateHook::register();   // wrappers, page head, sort bar, view toggle, no-results
\Theme\Child\Hooks\WooProductHook::register();    // gallery, PDP blocks, tabs, swatches, related/upsell args
\Theme\Child\Hooks\WooCartHook::register();       // cart fragments, cross-sells, inline string translate
\Theme\Child\Hooks\WooAccountHook::register();    // my-account menu + auth-toggle JS
\Theme\Child\Hooks\PaymentVietQrHook::register();  // bank-transfer (bacs) VietQR thank-you block
\Theme\Child\Hooks\SeoHook::register();
\Theme\Child\Hooks\MediaHook::register();
\Theme\Child\Hooks\SecurityHook::register();
\Theme\Child\Hooks\MenuHook::register();
\Theme\Child\Hooks\FilterHook::register();
\Theme\Child\Product\Versions::register();
\Theme\Child\Hooks\AboutPageHook::register();
\Theme\Child\Hooks\ContactPageHook::register();
\Theme\Child\Hooks\AuthorPageHook::register();

// WP-CLI scaffolder (global classes, dev tooling only).
// Scaffolds page template/partial/ACF/assets only; wire page hooks manually under app/Hooks/.
if (defined('WP_CLI') && WP_CLI) {
    require_once UNDERSCORES_CHILD_THEME_INCLUDES_PATH . '/classes/class-child-scaffold-generator.php';
    require_once UNDERSCORES_CHILD_THEME_INCLUDES_PATH . '/classes/class-child-cli.php';

    if (class_exists('Underscores_Child_CLI')) {
        Underscores_Child_CLI::get_instance();
    }
}
