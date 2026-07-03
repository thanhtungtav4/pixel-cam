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
        <nav class="crumb" aria-label="Breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Trang chủ', 'underscores'); ?></a>
            <span class="sep">/</span>
            <span class="cur"><?php the_title(); ?></span>
        </nav>

        <h1 class="page-title"><?php the_title(); ?></h1>

        <div class="prose"><?php the_content(); ?></div>
    </div></div>
    <?php
endwhile;

get_footer();
