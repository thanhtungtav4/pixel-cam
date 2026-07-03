<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * Child-owned CSS/JS pipeline for the Pixel Cam store.
 *
 * The parent theme (school template) shipped Swiper/GSAP/AOS/Select2/Fancybox
 * plus its own stylesheets. Pixel Cam's CSS/JS is self-contained, so we do NOT
 * enqueue any of that here — only Inter + pixel-cam.css + pixel-cam.js.
 *
 * Parent extension points still fire so context hooks keep working:
 *   underscores_before_common_css / underscores_after_common_css
 *   underscores_before_common_js  / underscores_after_common_js
 */
final class ThemeHook
{
    public static function register(): void
    {
        $self = new self();
        add_action('after_setup_theme', [$self, 'register_footer_menus'], 11);
        add_action('widgets_init', [$self, 'register_shop_filters_sidebar']);
        add_action('wp_enqueue_scripts', [$self, 'enqueue_common_css_assets'], 10);
        add_action('wp_enqueue_scripts', [$self, 'enqueue_common_js_assets'], 10);
    }

    public function register_shop_filters_sidebar(): void
    {
        register_sidebar([
            'name'          => __('Shop — Bộ lọc', 'underscores'),
            'id'            => 'shop-filters',
            'description'   => __('Widget bộ lọc hiển thị bên trái trang cửa hàng (Woo Layered Nav, Filter Products by Price...).', 'underscores'),
            'before_widget' => '<div class="fgroup %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h5>',
            'after_title'   => '</h5>',
        ]);
    }

    public function register_footer_menus(): void
    {
        register_nav_menus([
            'footer-products' => __('Footer — Sản phẩm', 'underscores'),
            'footer-support'  => __('Footer — Hỗ trợ', 'underscores'),
        ]);
    }

    public function enqueue_common_css_assets(): void
    {
        // Inter (design system font). Preconnect handled by WP resource hints.
        wp_enqueue_style(
            'pixel-cam-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            [],
            null
        );

        do_action('underscores_before_common_css');

        wp_enqueue_style(
            'pixel-cam',
            underscores_child_asset_uri('assets/css/pixel-cam.css'),
            ['pixel-cam-fonts'],
            underscores_child_asset_version('assets/css/pixel-cam.css')
        );

        do_action('underscores_after_common_css');
    }

    public function enqueue_common_js_assets(): void
    {
        do_action('underscores_before_common_js');

        wp_enqueue_script(
            'pixel-cam',
            underscores_child_asset_uri('assets/scripts/pixel-cam.js'),
            [],
            underscores_child_asset_version('assets/scripts/pixel-cam.js'),
            true
        );

        do_action('underscores_after_common_js');
    }
}
