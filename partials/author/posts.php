<?php
/**
 * Author's posts list — Pixel Cam.
 *
 * Renders the author's published posts in the standard 2-col blog grid.
 * Reuses `partials/components/post-card.php` for each card (and the existing
 * `.blog-grid` / `.pager` styles in pixel-cam.css).
 *
 * @param array $args { WP_User $author }
 * @package Underscores
 */

defined('ABSPATH') || exit;

$author = $args['author'] ?? null;
if (! $author instanceof WP_User) {
    return;
}

$author_id = (int) $author->ID;
$name      = (string) $author->display_name;
?>
<section class="wrap author-posts" aria-label="<?php echo esc_attr(sprintf(__('Bài viết của %s', 'underscores'), $name)); ?>">
    <h2 class="author-posts__title"><?php
        printf(
            /* translators: %s: author display name */
            esc_html__('Bài viết của %s', 'underscores'),
            '<span>' . esc_html($name) . '</span>'
        );
    ?></h2>

    <?php if (have_posts()) : ?>
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
            <div class="es-title"><?php esc_html_e('Tác giả chưa có bài viết', 'underscores'); ?></div>
        </div>
    <?php endif; ?>
</section>
