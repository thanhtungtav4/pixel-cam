<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('underscores_child_cache_version')) {
    /** Current version number for a cache group (monotonic, starts at 1). */
    function underscores_child_cache_version(string $group): int
    {
        return (int) get_option('pxc_ver_' . $group, 1);
    }
}

if (!function_exists('underscores_child_bump_cache_version')) {
    /**
     * Invalidate a whole cache group in O(1) by bumping its version — old
     * transient keys become unreachable and expire on their own TTL.
     */
    function underscores_child_bump_cache_version(string $group): void
    {
        update_option('pxc_ver_' . $group, underscores_child_cache_version($group) + 1, false);
    }
}

if (!function_exists('underscores_child_versioned_cache')) {
    /**
     * Read-through versioned transient cache. Key = group + version + subkey,
     * so bumping the group version (on catalog change) invalidates everything
     * in that group at once. $build runs only on a miss; its return is cached.
     *
     * @template T
     * @param callable():T $build
     * @return T
     */
    function underscores_child_versioned_cache(string $group, string $subkey, callable $build, int $ttl = 43200)
    {
        $ver = underscores_child_cache_version($group);
        $key = 'pxc_' . $group . '_' . $ver . '_' . md5($subkey);

        $hit = get_transient($key);
        // Treat null/false/'' as a cache miss. WP's transient API can only
        // store scalars + arrays, so an earlier buggy build() that returned
        // null got written as '' to the DB and would otherwise be returned
        // here as a string — breaking typed callers expecting ?array.
        if ($hit !== false && $hit !== null && $hit !== '' && $hit !== []) {
            return $hit;
        }
        // Stale bad value: drop it so the next read doesn't keep replaying
        // the broken cache. Bump the group version so all old keys (in any
        // form) become unreachable, then re-run the build.
        if ($hit !== false) {
            underscores_child_bump_cache_version($group);
            delete_transient($key);
        }

        $value = $build();
        // Don't cache null/false/empty — WP's transient API can't store null
        // (it ends up as '' in the DB, which then gets returned as a string
        // on the next hit and breaks typed callers like ?array signatures).
        // Just return the value and let the next call re-run the closure.
        if ($value === null || $value === false || $value === '') {
            return $value;
        }

        set_transient($key, $value, $ttl);
        return $value;
    }
}

if (!function_exists('underscores_child_mark_style_loading_strategy')) {
    function underscores_child_mark_style_loading_strategy(string $handle, string $strategy): void
    {
        if (!in_array($strategy, ['preload', 'media'], true)) {
            return;
        }

        if (!isset($GLOBALS['underscores_child_style_loading_strategies']) || !is_array($GLOBALS['underscores_child_style_loading_strategies'])) {
            $GLOBALS['underscores_child_style_loading_strategies'] = [];
        }

        $GLOBALS['underscores_child_style_loading_strategies'][$handle] = $strategy;
    }
}

if (!function_exists('underscores_child_get_style_loading_strategies')) {
    function underscores_child_get_style_loading_strategies(): array
    {
        $strategies = $GLOBALS['underscores_child_style_loading_strategies'] ?? [];
        $strategies = apply_filters('underscores_child_style_loading_strategies', $strategies);

        if (!is_array($strategies)) {
            return [];
        }

        $normalized = [];

        foreach ($strategies as $handle => $strategy) {
            if (!is_string($handle) || !is_string($strategy)) {
                continue;
            }

            if (!in_array($strategy, ['preload', 'media'], true)) {
                continue;
            }

            $normalized[$handle] = $strategy;
        }

        return $normalized;
    }
}

if (!function_exists('underscores_child_mark_script_loading_strategy')) {
    function underscores_child_mark_script_loading_strategy(string $handle, string $strategy): void
    {
        if (!in_array($strategy, ['defer', 'async'], true)) {
            return;
        }

        if (!isset($GLOBALS['underscores_child_script_loading_strategies']) || !is_array($GLOBALS['underscores_child_script_loading_strategies'])) {
            $GLOBALS['underscores_child_script_loading_strategies'] = [];
        }

        $GLOBALS['underscores_child_script_loading_strategies'][$handle] = $strategy;
    }
}

if (!function_exists('underscores_child_get_script_loading_strategies')) {
    function underscores_child_get_script_loading_strategies(): array
    {
        $strategies = $GLOBALS['underscores_child_script_loading_strategies'] ?? [];
        $strategies = apply_filters('underscores_child_script_loading_strategies', $strategies);

        if (!is_array($strategies)) {
            return [];
        }

        $normalized = [];

        foreach ($strategies as $handle => $strategy) {
            if (!is_string($handle) || !is_string($strategy)) {
                continue;
            }

            if (!in_array($strategy, ['defer', 'async'], true)) {
                continue;
            }

            $normalized[$handle] = $strategy;
        }

        return $normalized;
    }
}

if (!function_exists('underscores_child_get_protected_script_handles')) {
    function underscores_child_get_protected_script_handles(): array
    {
        $handles = apply_filters('underscores_child_protected_script_handles', [
            'jquery-migrate',
            'wp-hooks',
            'wp-i18n',
            'contact-form-7',
            'swv',
            'wpcf7-recaptcha',
            'google-recaptcha',
        ]);

        if (!is_array($handles)) {
            return [];
        }

        return array_values(array_filter($handles, 'is_string'));
    }
}

if (!function_exists('underscores_child_get_current_template_slug')) {
    function underscores_child_get_current_template_slug(): ?string
    {
        if (is_front_page()) {
            return 'front-page';
        }

        if (!is_singular()) {
            return null;
        }

        $template = get_page_template_slug(get_queried_object_id());

        if (!$template) {
            return null;
        }

        $basename = basename($template, '.php');
        $slug = preg_replace('/^template-/', '', $basename);

        return $slug ?: null;
    }
}

if (!function_exists('underscores_child_get_critical_css_path')) {
    function underscores_child_get_critical_css_path(): ?string
    {
        $filtered_path = apply_filters('underscores_child_critical_css_path', null);

        if (is_string($filtered_path) && $filtered_path !== '' && file_exists($filtered_path)) {
            return $filtered_path;
        }

        $slug = underscores_child_get_current_template_slug();

        if (!$slug) {
            return null;
        }

        $critical_css_path = underscores_child_asset_path('assets/css/critical/' . $slug . '.css');

        if (!file_exists($critical_css_path)) {
            return null;
        }

        return $critical_css_path;
    }
}

if (!function_exists('underscores_child_get_critical_css_contents')) {
    function underscores_child_get_critical_css_contents(): string
    {
        static $critical_css = null;

        if ($critical_css !== null) {
            return $critical_css;
        }

        $critical_css_path = underscores_child_get_critical_css_path();

        if (!$critical_css_path) {
            $critical_css = '';
            return $critical_css;
        }

        $contents = file_get_contents($critical_css_path);
        $critical_css = is_string($contents) ? trim($contents) : '';

        return $critical_css;
    }
}

if (!function_exists('underscores_child_add_webp_to_srcset')) {
    /**
     * #14b — Auto-add WebP variants to image srcset.
     *
     * WP 6.x generates WebP on upload but does NOT inject existing .webp
     * files into the srcset of <img> tags that were uploaded before WebP
     * support was enabled. This filter checks each src in the srcset: if a
     * .webp sibling exists on disk (same path, .webp extension), it is
     * appended to the srcset so the browser can pick the modern format.
     *
     * Runs on `wp_calculate_image_srcset` (core filter) → applies to every
     * image, including Woo product cards and gallery.
     *
     * @param array  $sources
     * @param array  $size_array
     * @param string $image_src
     * @param array  $image_meta
     * @param int    $attachment_id
     * @return array
     */
    function underscores_child_add_webp_to_srcset(array $sources, array $size_array, string $image_src, array $image_meta, int $attachment_id): array
    {
        if (empty($sources)) {
            return $sources;
        }

        foreach ($sources as $i => $source) {
            $url = $source['url'] ?? '';
            if ($url === '') {
                continue;
            }
            // Already webp → skip.
            if (preg_match('/\.webp$/i', $url)) {
                continue;
            }
            // Convert URL → filesystem path.
            $path = str_replace(wp_get_upload_dir()['baseurl'], wp_get_upload_dir()['basedir'], $url);
            $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
            if (! file_exists($webp_path)) {
                continue;
            }
            // WebP exists → add as a source with same dimensions.
            $webp_url = preg_replace('/\.(jpe?g|png)$/i', '.webp', $url);
            $sources[$i . '-webp'] = [
                'url'        => $webp_url,
                'descriptor' => $source['descriptor'] ?? '',
                'value'      => $source['value'] ?? 0,
            ];
        }

        return $sources;
    }
}
add_filter('wp_calculate_image_srcset', 'underscores_child_add_webp_to_srcset', 10, 5);
