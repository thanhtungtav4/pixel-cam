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
        add_filter('style_loader_tag', [$self, 'apply_style_loading_strategy'], 20, 4);
        add_filter('script_loader_tag', [$self, 'apply_script_loading_strategy'], 20, 3);
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
