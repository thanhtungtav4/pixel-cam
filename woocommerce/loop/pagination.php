<?php

/**
 * Shop pagination — Pixel Cam (.pager style).
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$total   = isset($total) ? $total : wc_get_loop_prop('total_pages');
$current = isset($current) ? $current : wc_get_loop_prop('current_page');
$base    = isset($base) ? $base : esc_url_raw(str_replace(999999999, '%#%', remove_query_arg('add-to-cart', get_pagenum_link(999999999, false))));
$format  = isset($format) ? $format : '';

if ($total <= 1) {
    return;
}

$current  = max(1, (int) $current);
$next_url = $current < $total ? get_pagenum_link($current + 1, false) : '';
?>
<?php if ($next_url) : ?>
    <?php // Load-more button: JS appends the next page (data-next). Hidden when JS
          // enhances the numeric pager, which stays for no-JS + crawlers. ?>
    <div class="loadmore-wrap" data-next="<?php echo esc_url($next_url); ?>" data-page="<?php echo (int) $current; ?>" data-total="<?php echo (int) $total; ?>">
        <button type="button" class="loadmore button"><?php esc_html_e('Xem thêm sản phẩm', 'underscores'); ?></button>
    </div>
<?php endif; ?>

<nav class="pager" aria-label="<?php esc_attr_e('Phân trang', 'underscores'); ?>">
    <?php
    echo paginate_links(
        apply_filters(
            'woocommerce_pagination_args',
            [
                'base'      => $base,
                'format'    => $format,
                'add_args'  => false,
                'current'   => $current,
                'total'     => $total,
                'prev_text' => '‹',
                'next_text' => '›',
                'type'      => 'plain',
                'end_size'  => 1,
                'mid_size'  => 2,
            ]
        )
    );
    ?>
</nav>
