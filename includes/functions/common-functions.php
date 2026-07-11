<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('underscores_get_option')) {
    function underscores_get_option(string $field_name, $default = null)
    {
        // ACF must be booted: get_field('option') resolves the options post id via
        // acf(), which is only a real object after acf/init has fired.
        if (!function_exists('get_field') || !function_exists('acf') || !is_object(acf()) || !did_action('acf/init')) {
            return $default;
        }

        $value = get_field($field_name, 'option');

        if ($value === null || $value === '' || $value === []) {
            return $default;
        }

        return $value;
    }
}

if (!function_exists('underscores_child_acf_link')) {
    /**
     * Normalize an ACF link (return_format=array) into url/title/target.
     * Returns null when the link is empty so callers can hide the block.
     *
     * @param mixed $link
     * @return array{url:string,title:string,target:string}|null
     */
    function underscores_child_acf_link($link, string $default_title = ''): ?array
    {
        if (empty($link['url'])) {
            return null;
        }

        return [
            'url'    => esc_url($link['url']),
            'title'  => $link['title'] !== '' ? $link['title'] : $default_title,
            'target' => $link['target'] ?? '',
        ];
    }
}

if (!function_exists('underscores_child_sale_percent')) {
    /**
     * Sale discount as a whole-number percent, or null when not a real markdown.
     * Guards against zero/negative/inverted prices (variable products, bad data).
     */
    function underscores_child_sale_percent(\WC_Product $product): ?int
    {
        if (!$product->is_on_sale()) {
            return null;
        }
        $regular = (float) $product->get_regular_price();
        $sale    = (float) $product->get_sale_price();
        if ($regular <= 0 || $sale <= 0 || $sale >= $regular) {
            return null;
        }
        return (int) round((1 - $sale / $regular) * 100);
    }
}

if (!function_exists('underscores_child_product_badge')) {
    /**
     * Map a product's Woo state → the design's .bd badge variant.
     * Priority: sale (percent, else "SALE") → featured (HOT) → new (MỚI, opt-in).
     * Returns ['class'=>'', 'label'=>''] when no badge applies.
     *
     * @return array{class:string,label:string}
     */
    function underscores_child_product_badge(\WC_Product $product, bool $with_new = false): array
    {
        if ($product->is_on_sale()) {
            $pct = underscores_child_sale_percent($product);
            return [
                'class' => 'sale',
                'label' => $pct !== null ? '-' . $pct . '%' : __('SALE', 'underscores'),
            ];
        }
        if ($product->is_featured()) {
            return ['class' => 'hot', 'label' => 'HOT'];
        }
        if ($with_new) {
            $ts = get_post_time('U', true, $product->get_id());
            if ($ts && (time() - $ts) < 30 * DAY_IN_SECONDS) {
                return ['class' => 'new', 'label' => __('MỚI', 'underscores')];
            }
        }
        return ['class' => '', 'label' => ''];
    }
}

if (!function_exists('underscores_child_asset_path')) {
    function underscores_child_asset_path(string $relative_path): string
    {
        return UNDERSCORES_CHILD_THEME_PATH . '/' . ltrim($relative_path, '/');
    }
}

if (!function_exists('underscores_child_asset_uri')) {
    function underscores_child_asset_uri(string $relative_path): string
    {
        return UNDERSCORES_CHILD_THEME_URI . '/' . ltrim($relative_path, '/');
    }
}

if (!function_exists('underscores_child_asset_version')) {
    function underscores_child_asset_version(string $relative_path): string
    {
        $asset_path = underscores_child_asset_path($relative_path);

        if (file_exists($asset_path)) {
            return (string) filemtime($asset_path);
        }

        return UNDERSCORES_CHILD_THEME_VERSION ?: '1.0.0';
    }
}
