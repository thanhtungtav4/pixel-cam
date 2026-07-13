<?php
/**
 * Shared blog archive layout — matches pixel-cam/blog.html.
 *
 * @param array $args {
 *   string $title         Page heading.
 *   string $description   Optional subheading.
 *   bool   $show_chips    Show category filter chips (default true).
 *   array  $breadcrumb    Optional [['label','url'], ...]. If empty, generic crumb is used.
 * }
 * @package Underscores
 */

defined('ABSPATH') || exit;

$title       = (string) ($args['title'] ?? '');
$description = (string) ($args['description'] ?? '');
$show_chips  = (bool) ($args['show_chips'] ?? true);
$crumbs      = is_array($args['breadcrumb'] ?? null) ? $args['breadcrumb'] : [];

$blog_page_id = (int) get_option('page_for_posts');
$blog_url     = $blog_page_id ? get_permalink($blog_page_id) : home_url('/');
?>

<div class="wrap">
    <?php
    $crumb_items = ! empty($crumbs) ? $crumbs : [['label' => $title]];
    get_template_part('partials/components/breadcrumb', null, ['items' => $crumb_items]);
    ?>
</div>

<section class="section--flush"><div class="wrap page-head">
    <?php if ($title) : ?><h1><?php echo esc_html($title); ?></h1><?php endif; ?>
    <?php if ($description) : ?><p class="meta"><?php echo esc_html($description); ?></p><?php endif; ?>

    <?php if ($show_chips) :
        $categories = get_categories([
            'taxonomy'   => 'category',
            'hide_empty' => true,
            'exclude'    => [1], // Uncategorized
        ]);
        if (! empty($categories) && ! is_wp_error($categories)) :
            $current_cat = get_queried_object();
            $current_id  = $current_cat instanceof WP_Term ? $current_cat->term_id : 0;
            ?>
            <div class="chips" role="tablist" aria-label="<?php esc_attr_e('Chủ đề bài viết', 'underscores'); ?>">
                <a class="chip<?php echo is_home() || is_front_page() ? ' on' : ''; ?>"
                   href="<?php echo esc_url($blog_url); ?>"
                   role="tab"
                   aria-selected="<?php echo is_home() || is_front_page() ? 'true' : 'false'; ?>">
                    <?php esc_html_e('Tất cả', 'underscores'); ?>
                </a>
                <?php foreach ($categories as $category) : ?>
                    <a class="chip<?php echo $current_id === $category->term_id ? ' on' : ''; ?>"
                       href="<?php echo esc_url(get_category_link($category->term_id)); ?>"
                       role="tab"
                       aria-selected="<?php echo $current_id === $category->term_id ? 'true' : 'false'; ?>">
                        <?php echo esc_html($category->name); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div></section>

<section class="section--flush"><div class="wrap blog-layout">
    <div>
        <?php if (have_posts()) : ?>
            <?php if (! is_paged()) :
                the_post();
                $cats = get_the_category();
                $cat  = ! empty($cats) ? $cats[0]->name : '';
                $featured_thumb_id = get_post_thumbnail_id();

                // Fallback alt for the featured image — post title if alt is empty
                // or just an upload hash. Better than nothing for a11y/SEO.
                $featured_alt = '';
                if ($featured_thumb_id) {
                    $featured_alt = (string) get_post_meta($featured_thumb_id, '_wp_attachment_image_alt', true);
                    if ($featured_alt === '' || preg_match('/^[a-f0-9]{20,}\.?(jpe?g|png|webp)?$/i', $featured_alt)) {
                        $featured_alt = get_the_title();
                    }
                }
                ?>
                <article class="featured">
                <a class="featured__media<?php echo $featured_thumb_id ? '' : ' featured__media--empty'; ?>" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
                <?php if ($featured_thumb_id) :
                    // LCP image — eager load + high fetch priority, no lazy.
                    // Sizes: full-width on mobile, ~651px on desktop (1.4fr/2.4fr split
                    // inside a ~1116px content column with a 300px sidebar).
                    echo wp_get_attachment_image($featured_thumb_id, 'pxc_lead_16_9', false, [
                        'loading'       => 'eager',
                        'fetchpriority' => 'high',
                        'sizes'         => '(max-width:720px) 100vw, 651px',
                        'alt'           => $featured_alt,
                    ]);
                else :
                    echo '<svg class="featured__placeholder-icon" viewBox="0 0 24 24" aria-hidden="true">'
                       . '<path d="M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm0 11 5-5 4 4 3-3 5 5M9.5 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" '
                       . 'fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>'
                       . '</svg>';
                endif; ?>
                </a>
                <div class="body">
                    <?php if ($cat) : ?><span class="cat-tag"><?php echo esc_html($cat); ?></span><?php endif; ?>
                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <?php if (has_excerpt()) : ?><p><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
                    <div class="meta">
                        <span><?php the_author(); ?></span>
                        <span>·</span>
                        <span><?php echo esc_html(get_the_date()); ?></span>
                    </div>
                </div>
            </article>
            <?php endif; ?>

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
