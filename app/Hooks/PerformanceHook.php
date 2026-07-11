<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * Web Vitals: inline critical CSS + style/script loading strategies.
 */
final class PerformanceHook
{
    public static function register(): void
    {
        $self = new self();
        add_action('wp_head', [$self, 'output_critical_css'], 2);
        add_action('wp_head', [$self, 'output_font_fallback'], 1);
        add_filter('style_loader_tag', [$self, 'apply_style_loading_strategy'], 20, 4);
        add_filter('script_loader_tag', [$self, 'apply_script_loading_strategy'], 20, 3);

        // Head cleanup (ported from i-dent optimize.php).
        $self->clean_head();
        $self->disable_emoji();

        // Iframe lazyload in content + ACF wysiwyg.
        add_filter('the_content', [$self, 'lazyload_iframes'], 15);
    }

    /**
     * Zero-CLS Inter fallback: an Arial-based face with metric overrides sized
     * to Inter's box. Body CSS should use
     *   font-family: "Inter", "Inter-fallback", sans-serif;
     * so pre-swap text occupies the same space → no layout shift when Inter
     * (loaded from Google Fonts) arrives.
     */
    public function output_font_fallback(): void
    {
        if (is_admin()) {
            return;
        }
        echo '<style id="pxc-inter-fallback">@font-face{font-family:"Inter-fallback";src:local("Arial"),local("Helvetica"),local("Segoe UI");size-adjust:107.40%;ascent-override:90.20%;descent-override:22.48%;line-gap-override:0%}</style>' . "\n";
    }

    /**
     * Remove wp_head cruft (generator, RSD/WLW, oEmbed, shortlink, adjacent
     * rels, REST discovery, auto-sizes CSS fix). Safe on a WooCommerce site.
     */
    private function clean_head(): void
    {
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
        remove_action('wp_head', 'parent_post_rel_link');
        remove_action('wp_head', 'start_post_rel_link');
        remove_action('wp_head', 'rest_output_link_wp_head');
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        remove_action('template_redirect', 'wp_shortlink_header', 11);
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
    }

    /**
     * Fully disable the WordPress emoji script/style scan.
     */
    private function disable_emoji(): void
    {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        add_filter('tiny_mce_plugins', static function ($plugins) {
            return is_array($plugins) ? array_diff($plugins, ['wpemoji']) : $plugins;
        });
        add_filter('emoji_svg_url', '__return_false');
    }

    public function lazyload_iframes(string $content): string
    {
        if ($content === '') {
            return $content;
        }
        return (string) preg_replace(
            '/<iframe(?![^>]*\bloading=)(.*?)>/i',
            '<iframe loading="lazy"$1>',
            $content
        );
    }

    public function output_critical_css(): void
    {
        if (is_admin()) {
            return;
        }

        $critical_css = underscores_child_get_critical_css_contents();

        if ($critical_css === '') {
            return;
        }

        $critical_css_path = underscores_child_get_critical_css_path();
        $critical_source = $critical_css_path ? basename($critical_css_path) : 'inline';

        echo '<style id="underscores-child-critical-css" data-source="' . esc_attr($critical_source) . '">' . $critical_css . '</style>' . "\n";
    }

    public function apply_style_loading_strategy(string $tag, string $handle, string $href, string $media): string
    {
        if (is_admin()) {
            return $tag;
        }

        $strategies = underscores_child_get_style_loading_strategies();
        $strategy = $strategies[$handle] ?? null;

        if (! $strategy) {
            return $tag;
        }

        $href = esc_url($href);
        $media = $media && $media !== 'all' ? $media : 'all';
        $id = esc_attr($handle . '-css');

        if ($strategy === 'media') {
            return sprintf(
                "<link rel='stylesheet' id='%s' href='%s' media='print' onload=\"this.media='%s'\" />\n<noscript><link rel='stylesheet' id='%s-noscript' href='%s' media='%s' /></noscript>\n",
                $id,
                $href,
                esc_attr($media),
                $id,
                $href,
                esc_attr($media)
            );
        }

        return sprintf(
            "<link rel='preload' id='%s' href='%s' as='style' onload=\"this.onload=null;this.rel='stylesheet'\" />\n<noscript><link rel='stylesheet' id='%s-noscript' href='%s' media='%s' /></noscript>\n",
            $id,
            $href,
            $id,
            $href,
            esc_attr($media)
        );
    }

    public function apply_script_loading_strategy(string $tag, string $handle, string $src): string
    {
        if (is_admin()) {
            return $tag;
        }

        $strategies = underscores_child_get_script_loading_strategies();
        $strategy = $strategies[$handle] ?? null;

        if (! $strategy) {
            return $tag;
        }

        if (in_array($handle, underscores_child_get_protected_script_handles(), true)) {
            return $tag;
        }

        if (str_contains($tag, ' type="module"') || str_contains($tag, " type='module'")) {
            return $tag;
        }

        if (str_contains($tag, ' defer') || str_contains($tag, ' async')) {
            return $tag;
        }

        return str_replace('<script ', '<script ' . $strategy . ' ', $tag);
    }
}
