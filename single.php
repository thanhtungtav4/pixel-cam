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
                        <h1 class="post-cover__title"><?php the_title(); ?></h1>
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
                    <b class="post-author__name"><?php the_author(); ?></b>
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
