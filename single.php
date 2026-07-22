<?php

/**
 * Single post (blog article) — Pixel Cam.
 *
 * Mobile layout matches sforum-style: featured image edge-to-edge with
 * darkened filter, category tag + title overlay on the image, and a white
 * card that overlaps the image bottom with author + "Ngày cập nhật" row.
 * Desktop: image normal, H1 + meta live inside the white card.
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
    $author_id = (int) get_the_author_meta('ID');

    $acf_fields = function_exists('get_fields') ? (get_fields() ?: []) : [];
    $related_posts_is_show    = ! empty($acf_fields['related_posts_is_show']);
    $related_products_is_show = ! empty($acf_fields['related_products_is_show']);
    $related_posts            = ! empty($acf_fields['related_posts']) ? array_map('intval', (array) $acf_fields['related_posts']) : [];
    $related_products         = ! empty($acf_fields['related_products']) ? array_map('intval', (array) $acf_fields['related_products']) : [];

    // Pre-compute TOC headings once here so the post-toc partial doesn't have
    // to call get_the_content() + run the_content filter + regex inside the
    // render pass. Same data, zero re-work.
    $toc_headings = function_exists('underscores_child_extract_headings')
        ? underscores_child_extract_headings(get_the_content())
        : [];
    ?>
    <div class="wrap">
        <?php
        $crumb_items = [];
        $blog = get_option('page_for_posts');
        if ($blog) {
            $crumb_items[] = ['label' => get_the_title($blog), 'url' => get_permalink($blog)];
        }
        if ($cat) {
            $crumb_items[] = ['label' => $cat->name, 'url' => get_category_link($cat->term_id)];
        }
        $crumb_items[] = ['label' => get_the_title()];
        get_template_part('partials/components/breadcrumb', null, ['items' => $crumb_items]);
        ?>
    </div>

    <div class="wrap post-head">
        <?php if (has_post_thumbnail()) : ?>
            <div class="post-cover">
                <?php the_post_thumbnail('pxc_cover_16_9', ['fetchpriority' => 'high']); ?>

                <?php if ($cat || get_the_title()) : ?>
                    <div class="post-cover__overlay">
                        <?php if ($cat) : ?>
                            <a class="post-tag" href="<?php echo esc_url(get_category_link($cat->term_id)); ?>">
                                <?php echo esc_html($cat->name); ?>
                            </a>
                        <?php endif; ?>
                        <p class="post-cover__title" aria-hidden="true"><?php the_title(); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="post-card">
            <div class="post-card__head">
                <?php if ($cat) : ?>
                    <a class="post-tag post-tag--inline" href="<?php echo esc_url(get_category_link($cat->term_id)); ?>">
                        <?php echo esc_html($cat->name); ?>
                    </a>
                <?php endif; ?>
                <h1 class="post-card__title"><?php the_title(); ?></h1>
                <?php if (has_excerpt()) : ?>
                    <p class="post-card__sub"><?php echo esc_html(get_the_excerpt()); ?></p>
                <?php endif; ?>
            </div>

            <div class="post-author">
                <span class="post-author__av">
                    <?php
                    echo get_avatar($author_id, 80, '', (string) get_the_author(), [
                        'class' => 'post-author__img',
                        'loading' => 'lazy',
                    ]);
                    ?>
                </span>
                <span class="post-author__meta">
                    <a class="post-author__name" href="<?php echo esc_url(get_author_posts_url($author_id)); ?>"><?php the_author(); ?></a>
                    <small class="post-author__date">
                        <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true" focusable="false">
                            <rect x="3" y="5" width="18" height="16" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/>
                            <path d="M3 10h18M8 3v4M16 3v4" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round"/>
                        </svg>
                        <span><?php
                            printf(
                                /* translators: %s: post publish/update date in d/m/Y format */
                                esc_html__('Ngày cập nhật: %s', 'underscores'),
                                esc_html(get_the_date('d/m/Y'))
                            );
                        ?></span>
                    </small>
                </span>
            </div>
        </div>
    </div>

    <section><div class="wrap post-layout<?php echo empty($toc_headings) ? ' no-toc' : ''; ?>">
        <div class="post-share">
            <h5><?php esc_html_e('Chia sẻ', 'underscores'); ?></h5>
            <?php
            $share_url = rawurlencode(get_permalink());
            $share_url_plain = get_permalink();
            ?>
            <a class="share-btn" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                Facebook
            </a>
            <a class="share-btn" href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 4.01a8 8 0 0 1-2.36.65A4.07 4.07 0 0 0 21.4 2.4a8.2 8.2 0 0 1-2.6 1 4.1 4.1 0 0 0-7 3.74A11.6 11.6 0 0 1 3 3.16a4.1 4.1 0 0 0 1.27 5.5A4.07 4.07 0 0 1 2.4 8v.05a4.1 4.1 0 0 0 3.3 4 4 4 0 0 1-1.85.07 4.1 4.1 0 0 0 3.83 2.85A8.23 8.23 0 0 1 2 16.54a11.62 11.62 0 0 0 6.29 1.84c7.55 0 11.67-6.25 11.67-11.67v-.53A8.3 8.3 0 0 0 22 4z"/></svg>
                X (Twitter)
            </a>
            <a class="share-btn" href="https://www.facebook.com/dialog/send?link=<?php echo $share_url; ?>&app_id=0" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12a10 10 0 1 0-11.6 9.87v-7H8v-2.9h2.4V9.5c0-2.37 1.4-3.68 3.57-3.68 1.03 0 2.1.19 2.1.19v2.31h-1.18c-1.17 0-1.53.73-1.53 1.47v1.77h2.6l-.42 2.9h-2.18v7A10 10 0 0 0 22 12z"/></svg>
                Messenger
            </a>
            <button class="share-btn" type="button" data-copy-link="<?php echo esc_url($share_url_plain); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>
                <?php esc_html_e('Sao chép link', 'underscores'); ?>
            </button>
        </div>

        <article>
            <div class="post-share-mobile" aria-label="<?php esc_attr_e('Chia sẻ bài viết', 'underscores'); ?>">
                <a class="share-btn share-btn--icon" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e('Chia sẻ Facebook', 'underscores'); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                </a>
                <a class="share-btn share-btn--icon" href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e('Chia sẻ X (Twitter)', 'underscores'); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 4.01a8 8 0 0 1-2.36.65A4.07 4.07 0 0 0 21.4 2.4a8.2 8.2 0 0 1-2.6 1 4.1 4.1 0 0 0-7 3.74A11.6 11.6 0 0 1 3 3.16a4.1 4.1 0 0 0 1.27 5.5A4.07 4.07 0 0 1 2.4 8v.05a4.1 4.1 0 0 0 3.3 4 4 4 0 0 1-1.85.07 4.1 4.1 0 0 0 3.83 2.85A8.23 8.23 0 0 1 2 16.54a11.62 11.62 0 0 0 6.29 1.84c7.55 0 11.67-6.25 11.67-11.67v-.53A8.3 8.3 0 0 0 22 4z"/></svg>
                </a>
                <a class="share-btn share-btn--icon" href="https://www.facebook.com/dialog/send?link=<?php echo $share_url; ?>&app_id=0" target="_blank" rel="noopener" aria-label="<?php esc_attr_e('Chia sẻ Messenger', 'underscores'); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12a10 10 0 1 0-11.6 9.87v-7H8v-2.9h2.4V9.5c0-2.37 1.4-3.68 3.57-3.68 1.03 0 2.1.19 2.1.19v2.31h-1.18c-1.17 0-1.53.73-1.53 1.47v1.77h2.6l-.42 2.9h-2.18v7A10 10 0 0 0 22 12z"/></svg>
                </a>
                <button class="share-btn share-btn--icon" type="button" data-copy-link="<?php echo esc_url($share_url_plain); ?>" aria-label="<?php esc_attr_e('Sao chép link', 'underscores'); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>
                </button>
            </div>

            <div class="prose"><?php the_content(); ?></div>

            <?php if (has_tag()) : ?>
                <div class="post-foot">
                    <div class="post-tags"><?php the_tags('', ''); ?></div>
                </div>
            <?php endif; ?>
        </article>

        <?php get_template_part('partials/components/post-toc', null, ['headings' => $toc_headings]); ?>
    </div></section>

    <?php if ($related_posts_is_show) : ?>
        <?php get_template_part('partials/components/post-related', null, [
            'post_id'        => get_the_ID(),
            'selected_posts' => $related_posts,
        ]); ?>
    <?php endif; ?>

    <?php if ($related_products_is_show) : ?>
        <?php get_template_part('partials/components/post-related-products', null, [
            'post_id'           => get_the_ID(),
            'selected_products' => $related_products,
        ]); ?>
    <?php endif; ?>

    <?php
endwhile;

get_footer();
