<?php

/**
 * Single post (blog article) — Pixel Cam.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

// Keep the theme's CPT dispatch: non-post single types use their own partial.
if (get_post_type() !== 'post') {
    get_header();
    get_template_part('partials/templates/single', get_post_type());
    get_footer();
    return;
}

get_header();

while (have_posts()) :
    the_post();
    $cats = get_the_category();
    $cat  = ! empty($cats) ? $cats[0] : null;
    ?>
    <div class="wrap">
        <nav class="crumb" aria-label="Breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Trang chủ', 'underscores'); ?></a>
            <span class="sep">/</span>
            <?php $blog = get_option('page_for_posts'); if ($blog) : ?>
                <a href="<?php echo esc_url(get_permalink($blog)); ?>"><?php echo esc_html(get_the_title($blog)); ?></a>
                <span class="sep">/</span>
            <?php endif; ?>
            <?php if ($cat) : ?>
                <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>"><?php echo esc_html($cat->name); ?></a>
                <span class="sep">/</span>
            <?php endif; ?>
            <span class="cur"><?php the_title(); ?></span>
        </nav>
    </div>

    <section style="padding-top:0;padding-bottom:0"><div class="wrap post-hero">
        <?php if ($cat) : ?><span class="post-cat"><?php echo esc_html($cat->name); ?></span><?php endif; ?>
        <h1><?php the_title(); ?></h1>
        <?php if (has_excerpt()) : ?><p class="sub"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
        <div class="post-meta">
            <div class="post-author">
                <div class="av"><?php echo esc_html(mb_substr(get_the_author(), 0, 2)); ?></div>
                <div><b><?php the_author(); ?></b></div>
            </div>
            <span class="dot"></span>
            <span class="item"><?php echo esc_html(get_the_date()); ?></span>
        </div>
    </div></section>

    <?php if (has_post_thumbnail()) : ?>
        <div class="wrap"><div class="post-cover"><?php the_post_thumbnail('large'); ?></div></div>
    <?php endif; ?>

    <section style="padding-top:0"><div class="wrap post-layout">
        <div class="post-share">
            <h5><?php esc_html_e('Chia sẻ', 'underscores'); ?></h5>
            <?php
            $share_url = rawurlencode(get_permalink());
            ?>
            <a class="share-btn" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" target="_blank" rel="noopener">Facebook</a>
            <a class="share-btn" href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>" target="_blank" rel="noopener">X (Twitter)</a>
            <button class="share-btn" type="button" data-copy-link="<?php echo esc_url(get_permalink()); ?>"><?php esc_html_e('Sao chép link', 'underscores'); ?></button>
        </div>

        <article>
            <div class="prose"><?php the_content(); ?></div>

            <?php if (has_tag()) : ?>
                <div class="post-foot">
                    <div class="post-tags"><?php the_tags('', ''); ?></div>
                </div>
            <?php endif; ?>
        </article>

        <?php get_template_part('partials/components/post-toc'); ?>
    </div></section>

    <?php
endwhile;

get_footer();
