<?php
declare(strict_types=1);

if (!defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

class Underscores_Child_Scaffold_Generator
{
    private string $theme_path;

    private string $config_path;

    private string $stub_path;

    public function __construct(string $theme_path, string $config_path, string $stub_path)
    {
        $this->theme_path = rtrim($theme_path, '/');
        $this->config_path = rtrim($config_path, '/');
        $this->stub_path = rtrim($stub_path, '/');
    }

    public function generate_page(string $raw_slug, array $options = []): array
    {
        $slug = $this->sanitize_slug($raw_slug);

        if ($slug === '') {
            throw new RuntimeException('Page slug is invalid after sanitizing.');
        }

        $title = isset($options['title']) && is_string($options['title'])
            ? trim($options['title'])
            : $this->humanize_slug($slug);

        if ($title === '') {
            $title = $this->humanize_slug($slug);
        }

        $with_acf = !empty($options['acf']);
        $with_assets = !empty($options['assets']);
        $force = !empty($options['force']);
        $dry_run = !empty($options['dry_run']);

        $template_file = 'page-template/template-' . $slug . '.php';
        $partial_file = 'partials/templates/' . $slug . '-page.php';
        $acf_file = 'acf-json/group_page_' . str_replace('-', '_', $slug) . '.json';
        $css_file = 'assets/css/pages/' . $slug . '.css';
        $js_file = 'assets/scripts/pages/' . $slug . '.js';

        $tokens = [
            'TITLE' => $title,
            'TEMPLATE_FILE' => $template_file,
            'PARTIAL_SLUG' => 'partials/templates/' . $slug . '-page',
            'PAGE_CLASS' => 'underscores-' . $slug . '-page',
            'SNAKE_SLUG' => str_replace('-', '_', $slug),
            'CSS_FILE' => $css_file,
            'JS_FILE' => $js_file,
            'FIELD_GROUP_KEY' => 'group_page_' . str_replace('-', '_', $slug),
            'FIELD_GROUP_NAME' => 'page_' . str_replace('-', '_', $slug),
            'FIELD_PREFIX' => str_replace('-', '_', $slug),
        ];

        $operations = [
            [
                'path' => $template_file,
                'stub' => 'cli/page-template.php.stub',
            ],
            [
                'path' => $partial_file,
                'stub' => 'cli/page-partial.php.stub',
            ],
        ];

        if ($with_acf) {
            $operations[] = [
                'path' => $acf_file,
                'stub' => 'cli/page-acf.json.stub',
            ];
        }

        if ($with_assets) {
            $operations[] = [
                'path' => $css_file,
                'stub' => 'cli/page-style.css.stub',
            ];
            $operations[] = [
                'path' => $js_file,
                'stub' => 'cli/page-script.js.stub',
            ];
        }

        $result = [
            'slug' => $slug,
            'title' => $title,
            'dry_run' => $dry_run,
            'created' => [],
            'updated' => [],
            'skipped' => [],
        ];

        foreach ($operations as $operation) {
            $absolute_path = $this->theme_path . '/' . $operation['path'];
            $status = $this->write_stub_file($absolute_path, $operation['stub'], $tokens, $force, $dry_run);

            if ($status === 'created') {
                $result['created'][] = $operation['path'];
                continue;
            }

            if ($status === 'updated') {
                $result['updated'][] = $operation['path'];
                continue;
            }

            $result['skipped'][] = $operation['path'];
        }

        return $result;
    }

    private function sanitize_slug(string $slug): string
    {
        if (function_exists('sanitize_title')) {
            return sanitize_title($slug);
        }

        $slug = trim($slug);
        $transliterated = function_exists('iconv')
            ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug)
            : $slug;

        if (!is_string($transliterated) || $transliterated === '') {
            $transliterated = $slug;
        }

        $transliterated = strtolower($transliterated);
        $transliterated = preg_replace('/[^a-z0-9]+/', '-', $transliterated) ?? '';

        return trim($transliterated, '-');
    }

    private function write_stub_file(string $absolute_path, string $stub_relative_path, array $tokens, bool $force, bool $dry_run): string
    {
        $exists = file_exists($absolute_path);

        if ($exists && !$force) {
            return 'skipped';
        }

        $stub_content = $this->get_stub_contents($stub_relative_path);
        $rendered = $this->replace_tokens($stub_content, $tokens);

        if ($dry_run) {
            return $exists ? 'updated' : 'created';
        }

        $this->ensure_directory(dirname($absolute_path));

        if (file_put_contents($absolute_path, $rendered) === false) {
            throw new RuntimeException(sprintf('Could not write file: %s', $absolute_path));
        }

        return $exists ? 'updated' : 'created';
    }

    private function get_stub_contents(string $stub_relative_path): string
    {
        $stub_path = $this->stub_path . '/' . ltrim($stub_relative_path, '/');

        if (!file_exists($stub_path)) {
            throw new RuntimeException(sprintf('Missing stub file: %s', $stub_path));
        }

        $content = file_get_contents($stub_path);

        if (!is_string($content)) {
            throw new RuntimeException(sprintf('Could not read stub file: %s', $stub_path));
        }

        return $content;
    }

    private function replace_tokens(string $content, array $tokens): string
    {
        foreach ($tokens as $key => $value) {
            $content = str_replace('{{' . $key . '}}', (string) $value, $content);
        }

        return $content;
    }

    private function ensure_directory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Could not create directory: %s', $path));
        }
    }

    private function humanize_slug(string $slug): string
    {
        return ucwords(str_replace('-', ' ', $slug));
    }
}
