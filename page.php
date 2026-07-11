<?php

/**
 * Generic page — Pixel Cam.
 *
 * Renders the WP editor content inside the .prose reading column. Page
 * templates (template-about.php / template-contact.php) override this for
 * ACF-driven layouts; this is the fallback for policy and other static pages.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

get_header();

while (have_posts()) :
    the_post();
    ?>
    <div class="page-main"><div class="wrap">
        <?php get_template_part('partials/components/breadcrumb', null, ['items' => [['label' => get_the_title()]]]); ?>

        <h1 class="page-title"><?php the_title(); ?></h1>

        <div class="prose"><?php the_content(); ?></div>
    </div></div>
    <?php
endwhile;

get_footer();
