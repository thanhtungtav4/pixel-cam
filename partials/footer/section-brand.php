<?php
/**
 * Footer — Section 1: Brand (logo + description + business info + social).
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$description  = $args['description'] ?? '';
$social_links = $args['social_links'] ?? [];
$business     = $args['business'] ?? [];

$biz_mst     = $business['mst'] ?? '';
$biz_issued  = $business['issued_date'] ?? '';
$biz_by      = $business['issued_by'] ?? '';
$biz_rep     = $business['representative'] ?? '';
$has_biz     = $biz_mst || $biz_issued || $biz_by || $biz_rep;
?>
<div class="foot-col foot-col--brand">
    <a href="<?php echo esc_url(home_url('/')); ?>" class="foot-logo-text" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?> — Trang chủ">
        <span class="mark">P</span> <?php echo esc_html(get_bloginfo('name')); ?>
    </a>

    <?php if ($description) : ?>
        <p class="foot-desc"><?php echo esc_html($description); ?></p>
    <?php endif; ?>

    <?php if ($has_biz) : ?>
        <ul class="foot-biz-brand" aria-label="<?php esc_attr_e('Thông tin doanh nghiệp', 'underscores'); ?>">
            <?php if ($biz_mst) : ?>
                <li><span class="k">MST</span> <span class="v"><?php echo esc_html($biz_mst); ?></span></li>
            <?php endif; ?>
            <?php if ($biz_issued) : ?>
                <li><span class="k">Cấp</span> <span class="v"><?php echo esc_html($biz_issued); ?><?php if ($biz_by) : ?> · <span class="by"><?php echo esc_html($biz_by); ?></span><?php endif; ?></span></li>
            <?php elseif ($biz_by) : ?>
                <li><span class="k">Nơi cấp</span> <span class="v"><?php echo esc_html($biz_by); ?></span></li>
            <?php endif; ?>
            <?php if ($biz_rep) : ?>
                <li><span class="k">Người ĐD</span> <span class="v"><?php echo esc_html($biz_rep); ?></span></li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>

    <?php if (! empty($social_links)) : ?>
        <ul class="foot-social" aria-label="<?php esc_attr_e('Mạng xã hội', 'underscores'); ?>">
            <?php foreach ($social_links as $social) :
                $url      = $social['url'] ?? '';
                if (! $url) {
                    continue;
                }
                $platform = $social['platform'] ?? '';
                $label    = $social['label'] ?: (ucfirst($platform) ?: __('Liên kết', 'underscores'));
                ?>
                <li>
                    <a class="foot-social-link foot-social-<?php echo esc_attr($platform ?: 'link'); ?>"
                       href="<?php echo esc_url($url); ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="<?php echo esc_attr($label); ?>">
                        <?php echo underscores_child_social_icon($platform); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- helper returns safe inline SVG. ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
