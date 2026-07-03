<?php
/**
 * Template Name: Giới thiệu
 * Template Post Type: page
 *
 * @author Underscores
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

underscores_child_set_main_class('page-about');

get_header();

if (have_posts()) {
    while (have_posts()) {
        the_post();
        get_template_part('partials/templates/about-page');
    }
}

get_footer();
