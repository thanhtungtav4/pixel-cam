<?php
/**
 * Footer — Section 1: Brand (logo + description + social).
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$description  = $args['description'] ?? '';
$social_links = $args['social_links'] ?? [];
?>
<div class="foot-col foot-col--brand">
    <div class="foot-logo">
        <?php if (has_custom_logo()) : ?>
            <div class="foot-logo-img">
                <?php the_custom_logo(); ?>
            </div>
        <?php else : ?>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="foot-logo-text">
                <span class="mark">P</span> <?php echo esc_html(get_bloginfo('name')); ?>
            </a>
        <?php endif; ?>
    </div>

    <?php if ($description) : ?>
        <p class="foot-desc"><?php echo esc_html($description); ?></p>
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
