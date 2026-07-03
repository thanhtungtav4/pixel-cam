<?php

/**
 * Front page — Pixel Cam home.
 *
 * Orchestration only: load ACF once, hand each section its raw settings array,
 * render in fixed order, hide sections that are toggled off.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

// NOTE: front-page.php is included at global scope, so a bare `$acf` here would
// clobber ACF's own $GLOBALS['acf'] singleton. Use a namespaced var name.
$home_acf = function_exists('get_fields') ? (get_fields() ?: []) : [];

$hero_settings       = $home_acf['hero_settings'] ?? [];
$feature_settings    = $home_acf['feature_settings'] ?? [];
$categories_settings = $home_acf['categories_settings'] ?? [];
$products_settings   = $home_acf['products_settings'] ?? [];
$blog_settings       = $home_acf['blog_settings'] ?? [];
$brands_settings     = $home_acf['brands_settings'] ?? [];
$cta_settings        = $home_acf['cta_settings'] ?? [];

get_header();

if (! empty($hero_settings['is_show'])) {
    get_template_part('partials/front-page/section-hero', null, $hero_settings);
}

if (! empty($feature_settings['is_show'])) {
    get_template_part('partials/front-page/section-feature', null, $feature_settings);
}

if (! empty($categories_settings['is_show'])) {
    get_template_part('partials/front-page/section-categories', null, $categories_settings);
}

if (! empty($products_settings['is_show'])) {
    get_template_part('partials/front-page/section-products', null, $products_settings);
}

if (! empty($blog_settings['is_show'])) {
    get_template_part('partials/front-page/section-blog', null, $blog_settings);
}

if (! empty($brands_settings['is_show'])) {
    get_template_part('partials/front-page/section-brands', null, $brands_settings);
}

if (! empty($cta_settings['is_show'])) {
    get_template_part('partials/front-page/section-cta', null, $cta_settings);
}

get_footer();
