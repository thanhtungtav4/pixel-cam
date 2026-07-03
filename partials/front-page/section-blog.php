<?php

/**
 * Front page — Blog teaser (latest posts: 1 lead + up to 4 list items).
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$heading = $args['heading'] ?? '';
$link    = underscores_child_acf_link($args['link'] ?? []);

$query = new WP_Query([
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => 5,
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
]);

if (! $query->have_posts()) {
    wp_reset_postdata();
    return;
}

$posts = $query->posts;
$lead  = array_shift($posts);
?>
<section><div class="wrap">
    <?php if ($heading || $link) : ?>
        <div class="sec-head">
            <?php if ($heading) : ?><h3><?php echo esc_html($heading); ?></h3><?php endif; ?>
            <?php if ($link) : ?><a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['title']); ?></a><?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="home-blog">
        <?php
        $cats = get_the_category($lead->ID);
        $cat  = ! empty($cats) ? $cats[0]->name : '';
        ?>
        <article class="hb-lead">
            <?php if (has_post_thumbnail($lead->ID)) : ?>
                <?php echo get_the_post_thumbnail($lead->ID, 'large'); ?>
            <?php endif; ?>
            <div class="body">
                <?php if ($cat) : ?><span class="cat-tag"><?php echo esc_html($cat); ?></span><?php endif; ?>
                <h3><a href="<?php echo esc_url(get_permalink($lead->ID)); ?>"><?php echo esc_html(get_the_title($lead->ID)); ?></a></h3>
                <?php if (has_excerpt($lead->ID)) : ?>
                    <p><?php echo esc_html(get_the_excerpt($lead->ID)); ?></p>
                <?php endif; ?>
                <div class="meta">
                    <span><?php echo esc_html(get_the_author_meta('display_name', $lead->post_author)); ?></span>
                    <span>·</span>
                    <span><?php echo esc_html(get_the_date('', $lead->ID)); ?></span>
                </div>
            </div>
        </article>

        <ul class="hb-list">
            <?php foreach ($posts as $post_item) :
                $item_cats = get_the_category($post_item->ID);
                $item_cat  = ! empty($item_cats) ? $item_cats[0]->name : '';
                ?>
                <li>
                    <?php if (has_post_thumbnail($post_item->ID)) : ?>
                        <?php echo get_the_post_thumbnail($post_item->ID, 'medium'); ?>
                    <?php endif; ?>
                    <div class="meta-block">
                        <?php if ($item_cat) : ?><span class="cat-tag"><?php echo esc_html($item_cat); ?></span><?php endif; ?>
                        <h4><a href="<?php echo esc_url(get_permalink($post_item->ID)); ?>"><?php echo esc_html(get_the_title($post_item->ID)); ?></a></h4>
                        <small><?php echo esc_html(get_the_date('', $post_item->ID)); ?></small>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div></section>
<?php wp_reset_postdata(); ?>
