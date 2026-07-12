<?php
/**
 * Footer — Section 5: Bottom bar (copyright + business info / MST).
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$copyright    = $args['copyright'] ?? '';
$business     = $args['business'] ?? [];

$biz_mst     = $business['mst'] ?? '';
$biz_issued  = $business['issued_date'] ?? '';
$biz_by      = $business['issued_by'] ?? '';
$biz_rep     = $business['representative'] ?? '';

$has_business = $biz_mst || $biz_issued || $biz_by || $biz_rep;

if (! $copyright && ! $has_business) {
    return;
}
?>
<div class="foot-bottom">
    <div class="wrap foot-bottom-inner">
        <?php if ($copyright) : ?>
            <span class="foot-copy"><?php echo esc_html($copyright); ?></span>
        <?php endif; ?>

        <?php if ($has_business) : ?>
            <ul class="foot-biz" aria-label="<?php esc_attr_e('Thông tin doanh nghiệp', 'underscores'); ?>">
                <?php if ($biz_mst) : ?>
                    <li><span class="k">MST:</span> <span class="v"><?php echo esc_html($biz_mst); ?></span></li>
                <?php endif; ?>
                <?php if ($biz_issued) : ?>
                    <li><span class="k">Ngày cấp:</span> <span class="v"><?php echo esc_html($biz_issued); ?></span></li>
                <?php endif; ?>
                <?php if ($biz_by) : ?>
                    <li><span class="k">Nơi cấp:</span> <span class="v"><?php echo esc_html($biz_by); ?></span></li>
                <?php endif; ?>
                <?php if ($biz_rep) : ?>
                    <li><span class="k">Người ĐD:</span> <span class="v"><?php echo esc_html($biz_rep); ?></span></li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
