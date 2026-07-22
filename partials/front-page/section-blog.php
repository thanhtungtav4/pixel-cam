<?php

/**
 * Front page — Blog teaser (latest posts: 1 lead + up to 4 list items).
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$heading = $args['heading'] ?? '';
$link    = underscores_child_acf_link($args['link'] ?? []);
$mode    = $args['mode'] ?? 'auto';
$count   = max(1, min(6, (int) ($args['count'] ?? 6)));
$manual  = $args['manual_posts'] ?? [];

if ($mode === 'manual') {
    // Hand-picked posts, in the chosen order.
    $post_ids = array_map('intval', (array) $manual);
    $post_ids = array_values(array_filter($post_ids, static fn ($id) => get_post_status($id) === 'publish'));
} else {
    $post_ids = get_posts([
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => $count,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
        'fields'              => 'ids',
    ]);
}

if (empty($post_ids)) {
    return;
}

$lead_id  = (int) array_shift($post_ids);
$lead     = get_post($lead_id);
if (! $lead) {
    return;
}
$posts = array_filter(array_map('get_post', $post_ids));
?>
<section><div class="wrap">
    <?php if ($heading || $link) : ?>
        <div class="sec-head">
            <?php if ($heading) : ?><h2><?php echo esc_html($heading); ?></h2><?php endif; ?>
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
                <a class="hb-lead__img" href="<?php echo esc_url(get_permalink($lead->ID)); ?>" aria-hidden="true" tabindex="-1">
                    <?php
                    // 16:9 hard-cropped via pxc_lead_16_9. Eager + high priority: this
                    // is the first (and only) lead on the home — definitely the LCP
                    // candidate among blog content.
                    echo wp_get_attachment_image(get_post_thumbnail_id($lead->ID), 'pxc_lead_16_9', false, [
                        'loading'       => 'eager',
                        'fetchpriority' => 'high',
                        'sizes'         => '(max-width:900px) 100vw, 651px',
                    ]);
                    ?>
                </a>
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
                        <?php echo get_the_post_thumbnail($post_item->ID, 'pxc_thumb_sq', ['loading' => 'lazy']); ?>
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
