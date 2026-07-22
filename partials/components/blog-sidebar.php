<?php
/**
 * Blog sidebar — popular posts, tags cloud, newsletter.
 * Markup matches pixel-cam/blog.html .bside
 *
 * @package Underscores
 */
defined('ABSPATH') || exit;
?>
<aside class="bside">
    <?php
    // Popular posts — by comment count (fallback: by date)
    $popular = new WP_Query([
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => 5,
        'orderby'             => 'comment_count',
        'order'               => 'DESC',
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ]);
    if ($popular->have_posts()) : ?>
        <div class="block">
            <h4><?php esc_html_e('Bài đọc nhiều', 'underscores'); ?></h4>
            <div class="pop">
                <?php $i = 0;
                while ($popular->have_posts()) : $popular->the_post(); $i++; ?>
                    <a href="<?php the_permalink(); ?>" class="pop-item">
                        <span class="num"><?php echo sprintf('%02d', $i); ?></span>
                        <div>
                            <b><?php echo esc_html(get_the_title()); ?></b>
                            <small><?php echo esc_html(get_the_date()); ?></small>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif;
    wp_reset_postdata();

    // Tags cloud
    $tags = get_tags(['number' => 15, 'orderby' => 'count', 'order' => 'DESC']);
    if (!empty($tags) && !is_wp_error($tags)) : ?>
        <div class="block">
            <h4><?php esc_html_e('Chủ đề', 'underscores'); ?></h4>
            <div class="tags-cloud">
                <?php foreach ($tags as $tag) : ?>
                    <a href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>">#<?php echo esc_html($tag->name); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="block block--newsletter">
        <h4><?php esc_html_e('Đăng ký nhận tin', 'underscores'); ?></h4>
        <p><?php esc_html_e('Tổng hợp bài hay mỗi tuần — và voucher khuyến mãi nội bộ.', 'underscores'); ?></p>
        <form onsubmit="event.preventDefault();this.querySelector('input').value='';this.querySelector('.ok').textContent='✓ <?php esc_attr_e('Đã đăng ký!', 'underscores'); ?>'">
            <label class="screen-reader-text" for="blogNewsletterEmail"><?php esc_html_e('Email nhận bản tin', 'underscores'); ?></label>
            <input type="email" id="blogNewsletterEmail" name="email" autocomplete="email" required placeholder="<?php esc_attr_e('Email của bạn', 'underscores'); ?>">
            <button type="submit"><?php esc_html_e('Đăng ký', 'underscores'); ?></button>
            <small class="ok"></small>
        </form>
    </div>
</aside>
