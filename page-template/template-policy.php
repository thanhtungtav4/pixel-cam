<?php
/**
 * Template Name: Chính sách
 * Template Post Type: page
 *
 * @author Underscores
 */
declare(strict_types=1);
if (!defined('ABSPATH')) {
    exit;
}
underscores_child_set_main_class('page-policy');
get_header();
if (have_posts()) {
    while (have_posts()) {
        the_post();
        get_template_part('partials/templates/policy-page');
    }
}
get_footer();
