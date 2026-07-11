<?php

/**
 * Header — Pixel Cam.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$general      = function_exists('underscores_get_option') ? (underscores_get_option('general_section') ?: []) : [];
$header       = function_exists('underscores_get_option') ? (underscores_get_option('header_section') ?: []) : [];
$topbar_text  = $header['topbar_text'] ?? '';
$topbar_links = $header['topbar_links'] ?? [];

$has_woo   = class_exists('WooCommerce');
$cart_count = $has_woo && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php echo esc_attr(get_bloginfo('charset')); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_site_icon(); ?>
    <link rel="pingback" href="<?php echo esc_url(get_bloginfo('pingback_url')); ?>" />
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- TOPBAR -->
<div class="topbar"><div class="wrap">
    <span><?php echo esc_html($topbar_text); ?></span>
    <?php if (! empty($topbar_links)) : ?>
        <div class="links">
            <?php foreach ($topbar_links as $row) :
                $link = underscores_child_acf_link($row['link'] ?? []);
                if (! $link) {
                    continue;
                }
                ?>
                <span><a href="<?php echo esc_url($link['url']); ?>"<?php echo $link['target'] ? ' target="' . esc_attr($link['target']) . '"' : ''; ?>><?php echo esc_html($link['title']); ?></a></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div></div>

<!-- HEADER -->
<header><div class="wrap hdr">
    <?php if (has_custom_logo()) :
        // the_custom_logo() prints its own <a class="custom-logo-link"> — do NOT
        // wrap it in another <a> (nested anchors are invalid HTML). Add .logo to
        // that link via the core filter so the design styles still apply.
        add_filter('get_custom_logo', 'underscores_child_logo_class');
        the_custom_logo();
        remove_filter('get_custom_logo', 'underscores_child_logo_class');
    else : ?>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="logo">
            <span class="mark">P</span> <?php echo esc_html(get_bloginfo('name')); ?>
        </a>
    <?php endif; ?>

    <?php if ($has_woo) : ?>
        <form class="hdr-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
            <input type="search" id="searchInput" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Tìm máy ảnh, lens, flycam, gimbal...', 'underscores'); ?>">
            <input type="hidden" name="post_type" value="product">
            <button type="submit"><?php esc_html_e('Tìm', 'underscores'); ?></button>
        </form>
    <?php else : ?>
        <form class="hdr-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
            <input type="search" id="searchInput" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Tìm kiếm...', 'underscores'); ?>">
            <button type="submit"><?php esc_html_e('Tìm', 'underscores'); ?></button>
        </form>
    <?php endif; ?>

    <div class="hdr-actions">
        <button class="ha hdr-burger" type="button" aria-label="<?php esc_attr_e('Mở menu', 'underscores'); ?>" aria-expanded="false" aria-controls="primary-nav">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
        <button class="ha mob-search-btn" type="button" aria-label="<?php esc_attr_e('Tìm kiếm', 'underscores'); ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
        </button>
        <a class="ha" href="<?php echo esc_url($has_woo ? wc_get_page_permalink('myaccount') : wp_login_url()); ?>" aria-label="<?php echo is_user_logged_in() ? esc_attr__('Tài khoản', 'underscores') : esc_attr__('Đăng nhập', 'underscores'); ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 8h-3V6a5 5 0 0 0-10 0v2H4l-1 12h18z"/></svg>
            <span>
                <?php
                if (is_user_logged_in()) {
                    esc_html_e('Tài khoản', 'underscores');
                } else {
                    esc_html_e('Đăng nhập', 'underscores');
                }
                ?>
            </span>
        </a>
        <?php if (defined('YITH_WCWL') && function_exists('YITH_WCWL')) :
            $wishlist_url = esc_url(YITH_WCWL()->get_wishlist_url());
            $wish_count   = function_exists('yith_wcwl_count_all_products') ? (int) yith_wcwl_count_all_products() : 0;
            ?>
            <a class="ha" href="<?php echo $wishlist_url; ?>" aria-label="<?php esc_attr_e('Yêu thích', 'underscores'); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8z"/></svg>
                <span><?php esc_html_e('Yêu thích', 'underscores'); ?></span>
                <span class="badge" id="wishBadge"><?php echo $wish_count; ?></span>
            </a>
        <?php endif; ?>
        <?php if ($has_woo) : ?>
            <div class="cart-wrap" id="cartWrap">
                <a class="ha" href="<?php echo esc_url(wc_get_cart_url()); ?>" aria-label="<?php esc_attr_e('Giỏ hàng', 'underscores'); ?>" aria-haspopup="true" aria-expanded="false">
                    <svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13h11l2.6-9H6"/></svg>
                    <span><?php esc_html_e('Giỏ hàng', 'underscores'); ?></span>
                    <span class="badge" id="cartBadge"><?php echo (int) $cart_count; ?></span>
                </a>
                <div class="mini-cart" aria-label="<?php esc_attr_e('Giỏ hàng', 'underscores'); ?>">
                    <div class="widget_shopping_cart_content">
                        <?php woocommerce_mini_cart(); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div></header>

<!-- NAV -->
<?php if (has_nav_menu('header-menu')) : ?>
<nav class="cat" id="primary-nav" aria-label="<?php esc_attr_e('Menu chính', 'underscores'); ?>">
    <button class="hdr-burger-close" type="button" aria-label="<?php esc_attr_e('Đóng menu', 'underscores'); ?>" aria-controls="primary-nav">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M6 18L18 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
    <div class="wrap">
        <?php
        wp_nav_menu([
            'theme_location' => 'header-menu',
            'container'      => false,
            'menu_class'     => 'cat-list',
            'fallback_cb'    => false,
            'depth'          => 3,
            'walker'         => new \Theme\Child\Menu\MegaWalker(),
        ]);
        ?>
    </div>
</nav>
<?php endif; ?>

<main <?php echo get_main_class(); ?>>
