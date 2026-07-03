<?php
/**
 * Template Name: Liên hệ
 * Template Post Type: page
 *
 * @author Underscores
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

underscores_child_set_main_class('page-contact');

get_header();

if (have_posts()) {
    while (have_posts()) {
        the_post();
        get_template_part('partials/templates/contact-page');
    }
}

get_footer();
