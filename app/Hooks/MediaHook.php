<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * Media / image optimization pipeline (ported from the i-dent theme).
 *
 *   - AVIF + WebP sidecar generation on upload (Imagick) + WP-CLI bulk command.
 *   - <picture> rewrite of content images when sidecars exist.
 *   - SVG upload sanitizer (DOMDocument) + SVG dimension extraction.
 *   - Custom-logo LCP attributes (no lazy, explicit w/h → no CLS).
 *   - Meaningful alt-text auto-fill for content images (SEO + a11y).
 *
 * Self-contained: no plugin dependency. AVIF needs Imagick built with libheif;
 * it degrades gracefully to WebP (or the original) when unavailable.
 */
final class MediaHook
{
    /** Sidecar quality knobs. */
    private const WEBP_Q_ALPHA = 85;
    private const WEBP_Q       = 82;
    private const AVIF_Q_ALPHA = 60;
    private const AVIF_Q       = 55;

    public static function register(): void
    {
        $self = new self();

        // Allow modern formats in the media library.
        add_filter('upload_mimes', [$self, 'allow_modern_mimes']);
        add_filter('wp_check_filetype_and_ext', [$self, 'fix_modern_mime_detection'], 10, 4);

        // Generate sidecars on upload.
        add_filter('wp_generate_attachment_metadata', [$self, 'generate_sidecars'], 10, 2);

        // Rewrite content <img> → <picture> when sidecars exist.
        add_filter('the_content', [$self, 'transform_content_images'], 20);

        // SVG: sanitize on upload, real dimensions, admin thumbnail.
        add_filter('wp_handle_upload_prefilter', [$self, 'sanitize_svg_upload']);
        add_filter('wp_get_attachment_image_src', [$self, 'svg_image_src'], 10, 4);
        add_filter('wp_prepare_attachment_for_js', [$self, 'svg_media_thumbnail'], 10, 2);

        // Custom logo LCP attrs (no lazy, explicit dimensions).
        add_filter('get_custom_logo_image_attributes', [$self, 'optimize_logo_attrs']);

        // Alt-text auto-fill for content images.
        add_filter('wp_get_attachment_image_attributes', [$self, 'autofill_alt'], 10, 3);

        // `sizes` attribute for responsive images. Without this, WP strips
        // `sizes` from the rendered <img> and the browser defaults to 100vw,
        // which always picks the full-size variant (wasteful on mobile, and
        // can also push grid items past their column on desktop).
        add_filter('wp_calculate_image_sizes', [$self, 'theme_image_sizes'], 10, 4);

        if (defined('WP_CLI') && \WP_CLI) {
            \WP_CLI::add_command('pxc generate-modern-formats', [$self, 'cli_bulk_generate']);
        }
    }

    /* ------------------------------------------------------------------ *
     * MIME
     * ------------------------------------------------------------------ */

    /** @param array<string,string> $mimes @return array<string,string> */
    public function allow_modern_mimes(array $mimes): array
    {
        $mimes['avif'] = 'image/avif';
        $mimes['webp'] = 'image/webp';
        // SVG (sanitized on upload by sanitize_svg_upload). Needed for menu icons.
        $mimes['svg']  = 'image/svg+xml';
        return $mimes;
    }

    /**
     * Show SVGs as real thumbnails in the media library grid/list.
     *
     * @param array<string,mixed> $response
     * @return array<string,mixed>
     */
    public function svg_media_thumbnail(array $response, $attachment): array
    {
        if (($response['mime'] ?? '') === 'image/svg+xml') {
            $url                 = wp_get_attachment_url($attachment->ID);
            $response['image']   = ['src' => $url, 'width' => 150, 'height' => 150];
            $response['thumb']   = ['src' => $url, 'width' => 150, 'height' => 150];
            $response['sizes']['full']      = ['url' => $url, 'width' => 150, 'height' => 150, 'orientation' => 'landscape'];
            $response['sizes']['thumbnail'] = ['url' => $url, 'width' => 150, 'height' => 150, 'orientation' => 'landscape'];
        }
        return $response;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function fix_modern_mime_detection($data, $file, $filename, $mimes): array
    {
        $ext = strtolower((string) pathinfo((string) $filename, PATHINFO_EXTENSION));
        if ($ext === 'avif') {
            $data['ext']  = 'avif';
            $data['type'] = 'image/avif';
        } elseif ($ext === 'webp') {
            $data['ext']  = 'webp';
            $data['type'] = 'image/webp';
        } elseif ($ext === 'svg') {
            $data['ext']  = 'svg';
            $data['type'] = 'image/svg+xml';
        }
        return (array) $data;
    }

    /* ------------------------------------------------------------------ *
     * AVIF / WebP generation
     * ------------------------------------------------------------------ */

    public function generate_one(string $file_path): void
    {
        if (! extension_loaded('imagick') || ! is_readable($file_path)) {
            return;
        }

        try {
            $image  = new \Imagick($file_path);
            $format = strtolower($image->getImageFormat());

            if ($format === 'gif' || $format === 'avif') {
                $image->clear();
                return;
            }

            $alpha_flags = [];
            if (defined('Imagick::ALPHACHANNEL_SET')) {
                $alpha_flags[] = \Imagick::ALPHACHANNEL_SET;
            }
            if (defined('Imagick::ALPHACHANNEL_BLEND')) {
                $alpha_flags[] = \Imagick::ALPHACHANNEL_BLEND;
            }
            $has_alpha = in_array($image->getImageAlphaChannel(), $alpha_flags, true) || $format === 'png';

            // WebP sidecar.
            $webp_path = $file_path . '.webp';
            if (! file_exists($webp_path)) {
                $webp = clone $image;
                $webp->setImageFormat('webp');
                $webp->setOption('webp:method', '6');
                $webp->setImageCompressionQuality($has_alpha ? self::WEBP_Q_ALPHA : self::WEBP_Q);
                $webp->stripImage();
                $webp->writeImage($webp_path);
                $webp->clear();
            }

            // AVIF sidecar (needs libheif).
            $avif_path = $file_path . '.avif';
            if (! file_exists($avif_path) && in_array('AVIF', $image->queryFormats(), true)) {
                $avif = clone $image;
                $avif->setImageFormat('avif');
                $avif->setImageCompressionQuality($has_alpha ? self::AVIF_Q_ALPHA : self::AVIF_Q);
                $avif->stripImage();
                $avif->writeImage($avif_path);
                $avif->clear();
            }

            $image->clear();
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[pxc] Imagick error for ' . $file_path . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * @param array<string,mixed> $metadata
     * @return array<string,mixed>
     */
    public function generate_sidecars($metadata, $attachment_id): array
    {
        $mime = get_post_mime_type((int) $attachment_id);
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return (array) $metadata;
        }

        $file = get_attached_file((int) $attachment_id);
        if (! $file || ! is_readable($file)) {
            return (array) $metadata;
        }

        $dir = trailingslashit(dirname($file));
        $this->generate_one($file);

        if (! empty($metadata['sizes']) && is_array($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size_data) {
                if (! empty($size_data['file'])) {
                    $this->generate_one($dir . $size_data['file']);
                }
            }
        }

        return (array) $metadata;
    }

    /**
     * WP-CLI: bulk-generate sidecars for existing attachments.
     * Usage: wp pxc generate-modern-formats [--dry-run] [--limit=N] [--offset=N]
     */
    public function cli_bulk_generate(array $args, array $assoc_args): void
    {
        $dry    = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'dry-run', false);
        $limit  = (int) \WP_CLI\Utils\get_flag_value($assoc_args, 'limit', 0);
        $offset = (int) \WP_CLI\Utils\get_flag_value($assoc_args, 'offset', 0);

        $ids = get_posts([
            'post_type'      => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/png', 'image/webp'],
            'post_status'    => 'inherit',
            'fields'         => 'ids',
            'posts_per_page' => $limit > 0 ? $limit : -1,
            'offset'         => $offset,
        ]);

        \WP_CLI::log(sprintf('Processing %d attachments%s...', count($ids), $dry ? ' (dry-run)' : ''));

        foreach ($ids as $id) {
            $file = get_attached_file((int) $id);
            if (! $file) {
                continue;
            }
            if ($dry) {
                \WP_CLI::log('  would process: ' . $file);
                continue;
            }
            $meta = wp_get_attachment_metadata((int) $id);
            $this->generate_sidecars($meta ?: [], $id);
            \WP_CLI::log('  done: ' . $file);
        }

        \WP_CLI::success('Modern formats generated.');
    }

    /* ------------------------------------------------------------------ *
     * <picture> content transform
     * ------------------------------------------------------------------ */

    public function transform_content_images(string $content): string
    {
        if ($content === '') {
            return $content;
        }

        static $cache = [];

        // Protect existing <picture> from double-wrapping.
        $pictures = [];
        $content  = preg_replace_callback(
            '/<picture\b[^>]*>.*?<\/picture>/is',
            static function ($m) use (&$pictures) {
                $key            = '%%PXC_PIC_' . count($pictures) . '%%';
                $pictures[$key] = $m[0];
                return $key;
            },
            $content
        );

        $content = preg_replace_callback(
            '/<img\s[^>]*\/?\s*>/i',
            function ($matches) use (&$cache) {
                $img = $matches[0];

                if (! preg_match('/\bloading\s*=/i', $img)) {
                    $img = preg_replace('/<img\b/i', '<img loading="lazy"', $img, 1);
                }
                if (! preg_match('/\bdecoding\s*=/i', $img)) {
                    $img = preg_replace('/<img\b/i', '<img decoding="async"', $img, 1);
                }
                if (preg_match('/\bloading\s*=\s*["\']lazy["\']/i', $img)) {
                    $img = preg_replace('/\s+fetchpriority\s*=\s*(["\'])[^"\']*\1/i', '', $img);
                }

                if (preg_match('/\bsrc=["\']([^"\']+)["\']/i', $img, $sm)) {
                    $src = $sm[1];
                    if (strpos($src, 'data:') === 0 || ! $this->is_local_image_url($src)) {
                        return $img;
                    }
                    if (! isset($cache[$src])) {
                        $cache[$src] = $this->build_picture($img, $src);
                    }
                    return $cache[$src];
                }
                return $img;
            },
            $content
        );

        if ($pictures) {
            $content = str_replace(array_keys($pictures), array_values($pictures), $content);
        }

        return $content;
    }

    private function is_local_image_url(string $url): bool
    {
        $home = wp_parse_url(home_url(), PHP_URL_HOST);
        $host = wp_parse_url($url, PHP_URL_HOST);
        return ! $host || $host === $home;
    }

    private function build_picture(string $img_tag, string $original_src): string
    {
        $file_path = $this->url_to_path($original_src);
        if (! $file_path || ! file_exists($file_path)) {
            return $img_tag;
        }

        $info      = pathinfo($file_path);
        $avif_side = trailingslashit($info['dirname']) . $info['filename'] . '.avif';
        $webp_side = trailingslashit($info['dirname']) . $info['filename'] . '.webp';

        $avif_exists = file_exists($avif_side);
        $webp_exists = file_exists($webp_side);
        if (! $webp_exists && file_exists($file_path . '.webp')) {
            $webp_exists = true;
        }
        if (! $avif_exists && ! $webp_exists) {
            return $img_tag;
        }

        $srcset = preg_match('/\bsrcset=["\']([^"\']+)["\']/i', $img_tag, $m) ? $m[1] : '';
        $sizes  = preg_match('/\bsizes=["\']([^"\']+)["\']/i', $img_tag, $m) ? $m[1] : '';
        $sizes_attr = $sizes ? ' sizes="' . esc_attr($sizes) . '"' : '';

        $url_info  = pathinfo($original_src);
        $avif_src  = trailingslashit($url_info['dirname']) . $url_info['filename'] . '.avif';
        $webp_src  = trailingslashit($url_info['dirname']) . $url_info['filename'] . '.webp';

        $sources = '';
        if ($avif_exists) {
            $ss = $srcset ? $this->convert_srcset($srcset, '.avif') : esc_url($avif_src);
            $sources .= '<source type="image/avif" srcset="' . ($srcset ? esc_attr($ss) : $ss) . '"' . ($srcset ? $sizes_attr : '') . '>';
        }
        if ($webp_exists) {
            $ss = $srcset ? $this->convert_srcset($srcset, '.webp') : esc_url($webp_src);
            $sources .= '<source type="image/webp" srcset="' . ($srcset ? esc_attr($ss) : $ss) . '"' . ($srcset ? $sizes_attr : '') . '>';
        }

        return '<picture>' . $sources . $img_tag . '</picture>';
    }

    private function convert_srcset(string $srcset, string $ext): string
    {
        $out = array_map(function ($part) use ($ext) {
            $trim   = trim($part);
            $pieces = preg_split('/\s+/', $trim, 2);
            $path   = $this->url_to_path($pieces[0]);
            if (! $path) {
                return $trim;
            }
            $pi = pathinfo($path);
            if (empty($pi['dirname']) || empty($pi['filename'])) {
                return $trim;
            }
            $side = trailingslashit($pi['dirname']) . $pi['filename'] . $ext;
            if (file_exists($side)) {
                $url = trailingslashit(dirname($pieces[0])) . $pi['filename'] . $ext;
                return $url . (isset($pieces[1]) ? ' ' . $pieces[1] : '');
            }
            if ($ext === '.webp' && file_exists($path . '.webp')) {
                return $pieces[0] . '.webp' . (isset($pieces[1]) ? ' ' . $pieces[1] : '');
            }
            return $trim;
        }, explode(',', $srcset));

        return implode(', ', $out);
    }

    private function url_to_path(string $url): ?string
    {
        $up = wp_upload_dir();
        if (strpos($url, $up['baseurl']) === 0) {
            return $up['basedir'] . substr($url, strlen($up['baseurl']));
        }
        $turi = get_stylesheet_directory_uri();
        if (strpos($url, $turi) === 0) {
            return get_stylesheet_directory() . substr($url, strlen($turi));
        }
        $curl = content_url();
        if (strpos($url, $curl) === 0) {
            return WP_CONTENT_DIR . substr($url, strlen($curl));
        }
        return null;
    }

    /* ------------------------------------------------------------------ *
     * SVG
     * ------------------------------------------------------------------ */

    /**
     * @param array<string,mixed> $file
     * @return array<string,mixed>
     */
    public function sanitize_svg_upload($file)
    {
        if (($file['type'] ?? '') !== 'image/svg+xml') {
            return $file;
        }
        $content = file_get_contents($file['tmp_name']);
        if ($content === false || $content === '') {
            return $file;
        }

        $bad_tags  = ['script', 'foreignObject', 'set', 'animate', 'animateTransform', 'animateMotion', 'handler', 'listener'];
        $bad_attrs = ['onclick', 'ondblclick', 'onmousedown', 'onmouseup', 'onmouseover', 'onmousemove', 'onmouseout', 'onkeypress', 'onkeydown', 'onkeyup', 'onload', 'onerror', 'onunload', 'onfocus', 'onblur', 'onchange', 'onsubmit', 'onreset', 'onselect', 'onabort', 'onresize', 'onscroll'];

        libxml_use_internal_errors(true);
        $dom                     = new \DOMDocument();
        $dom->formatOutput       = false;
        $dom->preserveWhiteSpace = true;

        if (! $dom->loadXML($content, LIBXML_NONET | LIBXML_NOENT)) {
            $file['error'] = __('Không đọc được file SVG.', 'underscores');
            libxml_clear_errors();
            return $file;
        }

        foreach ($bad_tags as $tag) {
            $nodes = $dom->getElementsByTagName($tag);
            for ($i = $nodes->length - 1; $i >= 0; $i--) {
                $node = $nodes->item($i);
                $node->parentNode?->removeChild($node);
            }
        }

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//*') as $el) {
            /** @var \DOMElement $el */
            foreach ($bad_attrs as $attr) {
                if ($el->hasAttribute($attr)) {
                    $el->removeAttribute($attr);
                }
            }
            $xlink = $el->getAttributeNS('http://www.w3.org/1999/xlink', 'href');
            if ($xlink && preg_match('/^\s*(javascript|data):/i', $xlink)) {
                $el->removeAttributeNS('http://www.w3.org/1999/xlink', 'href');
            }
            if ($el->hasAttribute('href') && preg_match('/^\s*(javascript|data):/i', $el->getAttribute('href'))) {
                $el->removeAttribute('href');
            }
        }

        $clean = $dom->saveXML();
        libxml_clear_errors();
        if ($clean === false || $clean === '') {
            $file['error'] = __('Làm sạch SVG thất bại.', 'underscores');
            return $file;
        }

        file_put_contents($file['tmp_name'], $clean);
        return $file;
    }

    /**
     * Give SVG attachments real dimensions (from width/height or viewBox) so
     * the browser can reserve space → no CLS.
     *
     * @param array{0:string,1:int,2:int,3:bool}|false $image
     * @return array{0:string,1:int,2:int,3:bool}|false
     */
    public function svg_image_src($image, $attachment_id, $size, $icon)
    {
        if (! $image || get_post_mime_type((int) $attachment_id) !== 'image/svg+xml') {
            return $image;
        }
        // Only fill when WP couldn't (0/absent dimensions).
        if (! empty($image[1]) && ! empty($image[2])) {
            return $image;
        }
        $path = get_attached_file((int) $attachment_id);
        $dims = $path ? $this->svg_dimensions($path) : null;
        if ($dims) {
            $image[1] = $dims['width'];
            $image[2] = $dims['height'];
        }
        return $image;
    }

    /** @return array{width:int,height:int}|null */
    private function svg_dimensions(string $file_path): ?array
    {
        if (! is_readable($file_path)) {
            return null;
        }
        $svg = file_get_contents($file_path, false, null, 0, 4096);
        if (! is_string($svg) || $svg === '') {
            return null;
        }
        $w = $h = null;
        if (preg_match('/<svg\b[^>]*\bwidth=["\']?([0-9.]+)(?:px)?["\']?/i', $svg, $m)) {
            $w = (float) $m[1];
        }
        if (preg_match('/<svg\b[^>]*\bheight=["\']?([0-9.]+)(?:px)?["\']?/i', $svg, $m)) {
            $h = (float) $m[1];
        }
        if ((! $w || ! $h) && preg_match('/\bviewBox=["\'](?:[-0-9.]+\s+){2}([0-9.]+)\s+([0-9.]+)["\']/i', $svg, $m)) {
            $w = $w ?: (float) $m[1];
            $h = $h ?: (float) $m[2];
        }
        if ($w && $h) {
            return ['width' => (int) round($w), 'height' => (int) round($h)];
        }
        return null;
    }

    /* ------------------------------------------------------------------ *
     * Logo LCP + alt-text
     * ------------------------------------------------------------------ */

    /**
     * @param array<string,mixed> $attrs
     * @return array<string,mixed>
     */
    public function optimize_logo_attrs(array $attrs): array
    {
        unset($attrs['loading'], $attrs['fetchpriority']);
        $logo_id = (int) get_theme_mod('custom_logo');
        if ($logo_id) {
            $meta = wp_get_attachment_metadata($logo_id);
            if (! empty($meta['width']) && ! empty($meta['height'])) {
                $attrs['width']  = $meta['width'];
                $attrs['height'] = $meta['height'];
            }
        }
        return $attrs;
    }

    /**
     * Fill a meaningful alt for content images that have none (SEO + a11y),
     * skipping icons/logos/decorative and tiny images.
     *
     * @param array<string,mixed> $attr
     * @return array<string,mixed>
     */
    public function autofill_alt(array $attr, $attachment, $size): array
    {
        if (! empty(trim((string) ($attr['alt'] ?? '')))) {
            return $attr;
        }
        if (! $this->should_autofill_alt($attr)) {
            return $attr;
        }
        $alt = $this->meaningful_alt((int) $attachment->ID);
        if ($alt !== '') {
            $attr['alt'] = $alt;
        }
        return $attr;
    }

    /** @param array<string,mixed> $attr */
    private function should_autofill_alt(array $attr): bool
    {
        $class = strtolower((string) ($attr['class'] ?? ''));
        foreach (['icon', 'arrow', 'close', 'search', 'social', 'flag', 'payment', 'logo'] as $kw) {
            if ($class !== '' && strpos($class, $kw) !== false) {
                return false;
            }
        }
        $filename = strtolower(wp_basename((string) wp_parse_url((string) ($attr['src'] ?? ''), PHP_URL_PATH)));
        foreach (['icon-', 'icon_', 'social', 'flag', 'logo', 'arrow'] as $kw) {
            if ($filename !== '' && strpos($filename, $kw) !== false) {
                return false;
            }
        }
        $w = (int) ($attr['width'] ?? 0);
        $h = (int) ($attr['height'] ?? 0);
        return max($w, $h) >= 80 || ($w * $h) >= 8000 || ($w === 0 && $h === 0);
    }

    private function meaningful_alt(int $attachment_id): string
    {
        if (! $attachment_id) {
            return '';
        }
        $stored = trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true));
        if ($stored !== '') {
            return $stored;
        }
        $caption = trim((string) wp_get_attachment_caption($attachment_id));
        if ($caption !== '') {
            return $caption;
        }
        $title = trim((string) get_the_title($attachment_id));
        return $title;
    }

    /**
     * Default `sizes` attribute when WP would otherwise emit a 100vw default.
     * Returns a sizes string matched to the image's actual layout slot.
     *
     *   pxc_lead_16_9  (800×450)  — blog-archive featured post card (1.4fr column)
     *   pxc_card_16_9   (720×405)  — blog-archive regular card (2-col, 50% wrap)
     *   pxc_cover_16_9  (1280×720) — single post featured (full-width)
     *   pxc_hero        (1600×700) — front-page hero slider
     *   pxc_card        (600×600)  — product card (4-col on desktop, 2-col tablet)
     *
     * WP will only ever call this when the size has a real registered width
     * (so the $size strings below are a closed set). Unknown sizes return
     * the WP default.
     *
     * Signature is intentionally loose (no type hints) — WP passes mixed
     * shapes depending on context (false when srcset is missing, array
     * otherwise), and strict hints would TypeError on the false path.
     */
    public function theme_image_sizes($default_sizes, $image_src, $image_meta, $attachment_id)
    {
        if (! is_array($image_meta)) {
            return $default_sizes;
        }
        $size_slug = (string) ($image_meta['size'] ?? '');

        if ($size_slug === 'pxc_lead_16_9') {
            return '(max-width:720px) 100vw, (max-width:980px) calc(50vw - 24px), 651px';
        }
        if ($size_slug === 'pxc_card_16_9') {
            return '(max-width:640px) 100vw, (max-width:980px) calc(50vw - 24px), 408px';
        }
        if ($size_slug === 'pxc_cover_16_9') {
            return '(max-width:720px) 100vw, 1100px';
        }
        if ($size_slug === 'pxc_hero') {
            return '(max-width:720px) 100vw, 1600px';
        }
        if ($size_slug === 'pxc_card') {
            return '(max-width:560px) 100vw, (max-width:860px) 50vw, 25vw';
        }
        return $default_sizes;
    }
}
