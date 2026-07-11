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
        add_action('after_setup_theme', [$self, 'register_image_sizes'], 11);
        add_action('widgets_init', [$self, 'register_shop_filters_sidebar']);
        add_action('wp_enqueue_scripts', [$self, 'enqueue_common_css_assets'], 10);
        add_action('wp_enqueue_scripts', [$self, 'enqueue_common_js_assets'], 10);
        add_action('wp_enqueue_scripts', [$self, 'dequeue_parent_obsolete_assets'], 999);
    }

    /**
     * Theme image sizes, hard-cropped to match each slot's aspect ratio so the
     * CSS `object-fit:cover` never upscales or distorts, and the browser only
     * downloads what it needs. Dimensions are ~2× the display slot for retina.
     */
    public function register_image_sizes(): void
    {
        add_image_size('pxc_side_banner', 640, 480, true); // 4:3 hero side banner (unused; kept)
        add_image_size('pxc_tile', 500, 500, true);        // 1:1 category tile
        add_image_size('pxc_hero', 1600, 700, true);       // 16:7 hero slider
        add_image_size('pxc_card_16_10', 720, 450, true);  // 16:10 blog card / lead teaser
        add_image_size('pxc_cover_16_9', 1280, 720, true); // 16:9 post cover / showroom
        add_image_size('pxc_lead_4_3', 800, 600, true);    // 4:3 featured lead / story photo
        add_image_size('pxc_thumb_sq', 160, 160, true);    // 1:1 small square (avatar, list thumb)
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
            add_query_arg('v', underscores_child_asset_version('assets/css/pixel-cam.css'), underscores_child_asset_uri('assets/css/pixel-cam.css')),
            ['pixel-cam-fonts'],
            null
        );

        do_action('underscores_after_common_css');
    }

    public function enqueue_common_js_assets(): void
    {
        do_action('underscores_before_common_js');

        wp_enqueue_script(
            'pixel-cam',
            add_query_arg('v', underscores_child_asset_version('assets/scripts/pixel-cam.js'), underscores_child_asset_uri('assets/scripts/pixel-cam.js')),
            [],
            null,
            true
        );

        do_action('underscores_after_common_js');
    }

    public function dequeue_parent_obsolete_assets(): void
    {
        wp_dequeue_style('swipper');
        wp_dequeue_style('news');
        wp_dequeue_script('underscores-template-swiper');
    }
}
