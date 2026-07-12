<?php
/**
 * Author profile card — Pixel Cam.
 *
 * @param array $args {
 *   WP_User $author     The queried user object.
 *   int     $post_count Number of published posts by this author.
 * }
 * @package Underscores
 */

defined('ABSPATH') || exit;

$author     = $args['author'] ?? null;
$post_count = (int) ($args['post_count'] ?? 0);

if (! $author instanceof WP_User) {
    return;
}

$author_id = (int) $author->ID;
$name      = (string) $author->display_name;

// Avatar: ACF custom upload (return URL) > Gravatar from user_email.
$avatar_url = '';
if (function_exists('get_field')) {
    $avatar_field = get_field('pxc_author_avatar', 'user_' . $author_id);
    if (is_array($avatar_field) && ! empty($avatar_field['url'])) {
        $avatar_url = (string) $avatar_field['url'];
    } elseif (is_string($avatar_field) && $avatar_field !== '') {
        $avatar_url = $avatar_field;
    }
}
if ($avatar_url === '') {
    $avatar_url = get_avatar_url($author_id, ['size' => 200]);
}

// Badge text (ACF) — e.g., "QTV", "Biên tập viên", "CTV".
$badge = function_exists('get_field') ? trim((string) get_field('pxc_author_badge', 'user_' . $author_id)) : '';

// Bio: ACF override > WP Biographical Info (user description).
$bio = function_exists('get_field') ? (string) get_field('pxc_author_bio', 'user_' . $author_id) : '';
if ($bio === '') {
    $bio = (string) $author->description;
}
$bio = trim($bio);

// Social link (ACF, URL field).
$facebook = function_exists('get_field') ? (string) get_field('pxc_author_facebook', 'user_' . $author_id) : '';

// First-name accent (cellphones pattern: bold first word, the rest plain).
$name_parts     = preg_split('/\s+/u', $name, 2);
$name_first     = (string) ($name_parts[0] ?? $name);
$name_rest      = (string) ($name_parts[1] ?? '');

// Description for SEO/og: trimmed bio, fallback to site tagline.
$site_tagline  = (string) get_bloginfo('description');
$seo_desc      = $bio !== '' ? wp_trim_words(wp_strip_all_tags($bio), 28, '…') : $site_tagline;
?>
<section class="author-hero"><div class="wrap">
    <?php
    $crumb_items = [
        ['label' => (string) get_bloginfo('name'), 'url' => home_url('/')],
        ['label' => sprintf(__('Tác giả: %s', 'underscores'), $name)],
    ];
    get_template_part('partials/components/breadcrumb', null, ['items' => $crumb_items]);
    ?>

    <div class="author-card">
        <div class="author-card__id">
            <div class="author-card__avatar<?php echo $badge !== '' ? ' has-badge' : ''; ?>">
                <?php if ($avatar_url !== '') : ?>
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($name); ?>" width="120" height="120" loading="eager" fetchpriority="high" decoding="async" />
                    <?php if ($badge !== '') : ?>
                        <span class="author-card__badge" aria-label="<?php echo esc_attr(sprintf(__('Vai trò: %s', 'underscores'), $badge)); ?>"><?php echo esc_html($badge); ?></span>
                    <?php endif; ?>
                <?php else : ?>
                    <span class="author-card__avatar-fallback" aria-hidden="true"><?php echo esc_html(mb_substr($name_first, 0, 1)); ?></span>
                <?php endif; ?>
            </div>

            <div class="author-card__id-meta">
                <h1 class="author-card__name">
                    <span class="first"><?php echo esc_html($name_first); ?></span>
                    <?php if ($name_rest !== '') : ?>
                        <span class="rest"><?php echo esc_html($name_rest); ?></span>
                    <?php endif; ?>
                </h1>
                <p class="author-card__count">
                    <?php esc_html_e('Bài đã đăng:', 'underscores'); ?>
                    <strong><?php echo esc_html(number_format_i18n($post_count)); ?></strong>
                </p>
            </div>
        </div>

        <?php if ($bio !== '' || $facebook !== '') : ?>
            <div class="author-card__body">
                <?php if ($bio !== '') : ?>
                    <div class="author-card__quote" aria-label="<?php esc_attr_e('Giới thiệu tác giả', 'underscores'); ?>">
                        <svg class="author-card__qmark author-card__qmark--top" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M7 7h4v4H7v6H3V11a4 4 0 0 1 4-4zm10 0h4v4h-4v6h-4V11a4 4 0 0 1 4-4z" fill="currentColor"/>
                        </svg>
                        <div class="author-card__bio"><?php echo wp_kses_post(wpautop($bio)); ?></div>
                        <svg class="author-card__qmark author-card__qmark--bot" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M17 17h-4v-4h4v-6h4v6a4 4 0 0 1-4 4zm-10 0H3v-4h4v-6h4v6a4 4 0 0 1-4 4z" fill="currentColor"/>
                        </svg>
                    </div>
                <?php endif; ?>

                <?php if ($facebook !== '') : ?>
                    <div class="author-card__social">
                        <a class="author-card__fb" href="<?php echo esc_url($facebook); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr(sprintf(__('Facebook của %s', 'underscores'), $name)); ?>">
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path fill="currentColor" d="M13.5 21v-7.5h2.5l.5-3h-3V8.5c0-.9.3-1.5 1.6-1.5H17V4.3c-.3 0-1.4-.1-2.6-.1-2.6 0-4.4 1.6-4.4 4.5v2.3H7.5v3h2.5V21h3.5z"/>
                            </svg>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div></section>
