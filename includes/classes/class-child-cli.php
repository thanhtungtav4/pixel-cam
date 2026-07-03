<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class Underscores_Child_CLI
{
    private static ?self $instance = null;

    private Underscores_Child_Scaffold_Generator $generator;

    public static function get_instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->generator = new Underscores_Child_Scaffold_Generator(
            UNDERSCORES_CHILD_THEME_PATH,
            UNDERSCORES_CHILD_THEME_CONFIG_PATH,
            UNDERSCORES_CHILD_THEME_STUB_PATH
        );

        if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
            WP_CLI::add_command('underscores make', $this);
        }
    }

    /**
     * Scaffold a child-theme page template, partial, ACF file, and optional asset files.
     *
     * ## OPTIONS
     *
     * <slug>
     * : Page slug, for example `about` or `contact-us`.
     *
     * [--title=<title>]
     * : Page template title shown in wp-admin.
     *
     * [--acf]
     * : Generate an ACF Local JSON field group (acf-json/) for this page template.
     *
     * [--assets]
     * : Create starter page CSS/JS files (assets/css/pages/, assets/scripts/pages/). Wire enqueue in a page hook class under app/Hooks/.
     *
     * [--force]
     * : Overwrite files if they already exist.
     *
     * [--dry-run]
     * : Preview created files without writing anything.
     *
     * ## EXAMPLES
     *
     *     wp underscores make page about --title="Giới thiệu" --acf --assets
     *     wp underscores make page contact-us --acf --assets --dry-run
     *
     * @when after_wp_load
     */
    public function page(array $args, array $assoc_args): void
    {
        if (!isset($args[0])) {
            WP_CLI::error('Missing page slug. Example: wp underscores make page about --acf --assets');
        }

        try {
            $result = $this->generator->generate_page($args[0], [
                'title' => $assoc_args['title'] ?? null,
                'acf' => isset($assoc_args['acf']),
                'assets' => isset($assoc_args['assets']),
                'force' => isset($assoc_args['force']),
                'dry_run' => isset($assoc_args['dry-run']),
            ]);
        } catch (RuntimeException $exception) {
            WP_CLI::error($exception->getMessage());
        }

        if (!empty($result['dry_run'])) {
            WP_CLI::log('Dry run only. No files were written.');
        }

        foreach ($result['created'] as $item) {
            WP_CLI::log('CREATE ' . $item);
        }

        foreach ($result['updated'] as $item) {
            WP_CLI::log('UPDATE ' . $item);
        }

        foreach ($result['skipped'] as $item) {
            WP_CLI::warning('SKIP ' . $item);
        }

        WP_CLI::success(sprintf('Page scaffold `%s` is ready.', $result['slug']));
    }
}
