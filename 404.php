<?php

/**
 * 404 — Pixel Cam.
 *
 * "Mất nét" / out-of-focus concept: the 0 in 404 is a camera aperture (iris),
 * the copy is framed like a viewfinder. Pure CSS (.e404 in pixel-cam.css) —
 * no image, no JS. Offers what a lost shopper actually wants: search the
 * catalog + jump to the shop.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

get_header();

$shop_url = (function_exists('wc_get_page_id') && wc_get_page_id('shop') > 0)
    ? get_permalink(wc_get_page_id('shop'))
    : '';
?>
<div class="page-main"><div class="wrap">
    <section class="e404" aria-labelledby="e404-title">
        <p class="e404-eyebrow"><?php esc_html_e('Mất nét rồi', 'underscores'); ?></p>

        <div class="e404-code" role="img"
             aria-label="<?php esc_attr_e('Lỗi 404 — không tìm thấy trang', 'underscores'); ?>">
            <span class="e404-digit">4</span>
            <span class="e404-aperture" aria-hidden="true">
                <?php // 6-blade iris — styled in .e404-aperture. ?>
                <span></span><span></span><span></span>
                <span></span><span></span><span></span>
            </span>
            <span class="e404-digit">4</span>
        </div>

        <h1 id="e404-title" class="e404-title"><?php esc_html_e('Không tìm thấy trang này', 'underscores'); ?></h1>
        <p class="e404-lead">
            <?php esc_html_e('Trang bạn tìm có thể đã dời ống kính đi nơi khác. Thử tìm sản phẩm, hoặc quay lại khung hình chính.', 'underscores'); ?>
        </p>

        <div class="e404-search">
            <?php // Reuse the header's product search (styled .search) so it matches. ?>
            <form class="search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <input type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>"
                       placeholder="<?php esc_attr_e('Tìm máy ảnh, lens, flycam, gimbal...', 'underscores'); ?>"
                       aria-label="<?php esc_attr_e('Tìm sản phẩm', 'underscores'); ?>">
                <input type="hidden" name="post_type" value="product">
                <button type="submit"><?php esc_html_e('Tìm', 'underscores'); ?></button>
            </form>
        </div>

        <div class="e404-actions">
            <a class="btn btn-primary" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Về trang chủ', 'underscores'); ?></a>
            <?php if ($shop_url) : ?>
                <a class="btn btn-ghost" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Xem tất cả máy ảnh', 'underscores'); ?></a>
            <?php endif; ?>
        </div>
    </section>
</div></div>
<?php
get_footer();
