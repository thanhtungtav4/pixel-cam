<?php
/**
 * Blog archive (posts page) — Pixel Cam.
 *
 * Reuses the shared blog-archive partial so home/category/tag/taxonomy all
 * share the same layout and the post-card component.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$blog_page_id = (int) get_option('page_for_posts');
$title        = $blog_page_id ? get_the_title($blog_page_id) : __('Tin tức', 'underscores');
$description  = $blog_page_id ? get_the_excerpt($blog_page_id) : '';

get_header();
get_template_part('partials/templates/blog-archive', null, [
    'title'       => $title,
    'description' => $description,
    'show_chips'  => true,
    'breadcrumb'  => [
        ['label' => $title],
    ],
]);
get_footer();
