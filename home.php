<?php

/**
 * Blog archive (posts page) — Pixel Cam.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

get_header();
?>
<div class="wrap">
    <nav class="crumb" aria-label="Breadcrumb">
        <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Trang chủ', 'underscores'); ?></a>
        <span class="sep">/</span>
        <span class="cur"><?php echo esc_html(get_the_title(get_option('page_for_posts')) ?: __('Tin tức', 'underscores')); ?></span>
    </nav>
</div>

<section style="padding-top:0;padding-bottom:24px"><div class="wrap page-head">
    <h1><?php echo esc_html(get_the_title(get_option('page_for_posts')) ?: __('Tin tức', 'underscores')); ?></h1>
</div></section>

<section style="padding-top:0"><div class="wrap blog-layout">
    <div>
        <?php if (have_posts()) : ?>
            <?php
            // First post → featured, the rest → grid.
            the_post();
            $cats = get_the_category();
            $cat  = ! empty($cats) ? $cats[0]->name : '';
            ?>
            <article class="featured">
                <?php if (has_post_thumbnail()) : ?>
                    <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('large'); ?></a>
                <?php endif; ?>
                <div class="body">
                    <?php if ($cat) : ?><span class="cat-tag"><?php echo esc_html($cat); ?></span><?php endif; ?>
                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <?php if (has_excerpt()) : ?><p><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
                    <div class="meta">
                        <span><?php the_author(); ?></span><span>·</span><span><?php echo esc_html(get_the_date()); ?></span>
                    </div>
                </div>
            </article>

            <div class="blog-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <?php get_template_part('partials/components/post-card', null, ['post_id' => get_the_ID()]); ?>
                <?php endwhile; ?>
            </div>

            <?php
            the_posts_pagination([
                'mid_size'  => 2,
                'prev_text' => '‹',
                'next_text' => '›',
                'class'     => 'pager',
            ]);
            ?>
        <?php else : ?>
            <div class="empty-state empty-state--bordered">
                <div class="es-title"><?php esc_html_e('Chưa có bài viết', 'underscores'); ?></div>
            </div>
        <?php endif; ?>
    </div>

    <?php get_template_part('partials/components/blog-sidebar'); ?>
</div></section>

<?php
get_footer();
