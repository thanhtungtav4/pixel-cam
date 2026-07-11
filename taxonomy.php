<?php
/**
 * Generic taxonomy archive for blog posts — matches pixel-cam/blog.html layout.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$term = get_queried_object();
if (! $term instanceof WP_Term) {
    wp_safe_redirect(home_url('/'));
    exit;
}

$blog_page_id = (int) get_option('page_for_posts');
$blog_title   = $blog_page_id ? get_the_title($blog_page_id) : __('Tin tức', 'underscores');

get_header();
get_template_part('partials/templates/blog-archive', null, [
    'title'       => $term->name,
    'description' => $term->description,
    'show_chips'  => true,
    'breadcrumb'  => [
        ['label' => $blog_title, 'url' => $blog_page_id ? get_permalink($blog_page_id) : home_url('/')],
        ['label' => $term->name],
    ],
]);
get_footer();
