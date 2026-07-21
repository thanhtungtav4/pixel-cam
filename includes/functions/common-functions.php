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

if (!function_exists('underscores_child_social_icon')) {
    /**
     * Inline SVG icon for a known social platform. Single-color, sized via CSS.
     * Returns safe inline SVG; no user input is interpolated.
     *
     * @param string $platform One of: facebook|instagram|youtube|tiktok|zalo|x|linkedin|shopee|lazada.
     * @return string SVG markup, or a generic arrow link for unknown platforms.
     */
    function underscores_child_social_icon(string $platform): string
    {
        switch ($platform) {
            case 'facebook':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12a10 10 0 1 0-11.5 9.9V14.9H8v-3h2.5V9.5c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12H17l-.4 3h-2.6v7A10 10 0 0 0 22 12z" fill="currentColor"/></svg>';
            case 'instagram':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7zm10 1.5a1 1 0 1 1 0 2 1 1 0 0 1 0-2zM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6z" fill="currentColor"/></svg>';
            case 'youtube':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23 7.2a3 3 0 0 0-2.1-2.1C19 4.5 12 4.5 12 4.5s-7 0-8.9.6A3 3 0 0 0 1 7.2 31 31 0 0 0 .5 12 31 31 0 0 0 1 16.8a3 3 0 0 0 2.1 2.1c1.9.6 8.9.6 8.9.6s7 0 8.9-.6a3 3 0 0 0 2.1-2.1A31 31 0 0 0 23.5 12 31 31 0 0 0 23 7.2zM9.8 15.5V8.5l6 3.5-6 3.5z" fill="currentColor"/></svg>';
            case 'tiktok':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 8.5a7 7 0 0 1-4-1.3v7.6a5.7 5.7 0 1 1-5-5.7v3a2.7 2.7 0 1 0 2 2.6V2h3a4 4 0 0 0 4 4v2.5z" fill="currentColor"/></svg>';
            case 'zalo':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C6.5 2 2 5.9 2 10.6c0 2.6 1.4 5 3.7 6.6l-.9 2.7c-.1.3.2.6.5.4l3.1-1.8a11 11 0 0 0 3.6.6c5.5 0 10-3.9 10-8.6S17.5 2 12 2zm-2.4 11.4-2.8-3 5-3.5h-3.2L13 8l-1.2-1.5H6.5v1.2l3.6 3.7-3.6 3v1.2h6.2l1.2-1.5-1.5-1.5H9.6zm6.4-3.6c-.6 0-1.1-.5-1.1-1.1s.5-1.1 1.1-1.1 1.1.5 1.1 1.1-.5 1.1-1.1 1.1z" fill="currentColor"/></svg>';
            case 'x':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.2 3H21l-6.5 7.4L22 21h-5.8l-4.5-6-5.2 6H3.5l7-7.9L2 3h5.9l4 5.6L18.2 3zm-1 16.4h1.6L7 4.5H5.3l11.9 14.9z" fill="currentColor"/></svg>';
            case 'linkedin':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 3a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM3 9h3v12H3V9zm6 0h3v1.7c.6-1 1.8-1.9 3.7-1.9 4 0 4.7 2.6 4.7 6V21h-3v-5.4c0-1.3 0-3-1.8-3s-2.1 1.4-2.1 2.9V21H10V9z" fill="currentColor"/></svg>';
            case 'shopee':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h2.5l.5 2h13l-2 12H7L4 4zm5 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z" fill="currentColor"/></svg>';
            case 'lazada':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h18l-2 8a9 9 0 0 1-17 1L3 3zm9 16a7 7 0 0 0 6.9-6H5.1A7 7 0 0 0 12 19zm-2-5h4v-2h-4v2z" fill="currentColor"/></svg>';
            default:
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 6L8.6 7.4 13.2 12l-4.6 4.6L10 18l6-6-6-6z" fill="currentColor"/></svg>';
        }
    }
}

if (!function_exists('underscores_child_wishlist_button')) {
    /**
     * Icon-only wishlist toggle button (.wish). Single source for the heart
     * markup + YITH add-URL/state, shared by the product card and the PDP.
     *
     * @param int    $product_id  Product ID.
     * @param string $extra_class Extra class(es) on the button (e.g. 'ic' on PDP).
     * @return string Escaped button HTML.
     */
    function underscores_child_wishlist_button(int $product_id, string $extra_class = ''): string
    {
        $has_yith    = defined('YITH_WCWL') && function_exists('YITH_WCWL');
        $in_wishlist = $has_yith && YITH_WCWL()->is_product_in_wishlist($product_id);
        // YITH's no-JS form handler (hooked on init) requires a `_wpnonce`
        // param on the add URL — without it, GET visits to ?add_to_wishlist=ID
        // are silently dropped. The plugin helper adds the nonce, but defaults
        // to the current REQUEST_URI as the base, so we pass base_url explicitly
        // to anchor the add to the product's own permalink (used in card loops
        // where the request is a category/shop page).
        $add_url     = $has_yith
            ? YITH_WCWL()->get_add_to_wishlist_url($product_id, ['base_url' => get_permalink($product_id)])
            : '';

        $classes = trim('wish ' . $extra_class . ($in_wishlist ? ' on' : ''));

        return sprintf(
            '<button class="%s" type="button" data-product-id="%d"%s aria-label="%s" aria-pressed="%s">'
            . '<svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8z"/></svg>'
            . '</button>',
            esc_attr($classes),
            $product_id,
            $add_url ? ' data-add-url="' . esc_url($add_url) . '"' : '',
            esc_attr__('Thêm vào yêu thích', 'underscores'),
            $in_wishlist ? 'true' : 'false'
        );
    }
}
