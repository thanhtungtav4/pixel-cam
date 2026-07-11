<?php
/**
 * Template Name: Landing (Filter SEO)
 * Template Post Type: page
 *
 * Trang landing tĩnh cho filter quan trọng (#2). KHÔNG SEO bằng query URL
 * (?orderby, ?filter) — mỗi filter có URL riêng (/may-anh-mirrorless-fullframe/).
 *
 * @author Underscores
 */
declare(strict_types=1);
if (!defined('ABSPATH')) {
    exit;
}
underscores_child_set_main_class('page-filter-landing');
get_header();
if (have_posts()) {
    while (have_posts()) {
        the_post();
        get_template_part('partials/templates/filter-landing-page');
    }
}
get_footer();
