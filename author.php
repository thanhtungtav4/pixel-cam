<?php
/**
 * Author archive — Pixel Cam.
 *
 * Renders the author profile card + that author's posts. URL pattern is
 * WordPress default (/author/{user_nicename}/). Inspired by
 * cellphones.com.vn/sforum/author/*.
 *
 * Data sources (in order of precedence):
 *   1. ACF user fields (pxc_author_avatar / _badge / _bio / _facebook)
 *   2. WordPress user fields (display_name, description, user_email → Gravatar)
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$author = get_queried_object();
if (! $author instanceof WP_User) {
    wp_safe_redirect(home_url('/'));
    exit;
}

$post_count = (int) count_user_posts($author->ID, 'post', true);

get_header();
get_template_part('partials/author/header', null, [
    'author'     => $author,
    'post_count' => $post_count,
]);
get_template_part('partials/author/posts', null, [
    'author' => $author,
]);
get_footer();
