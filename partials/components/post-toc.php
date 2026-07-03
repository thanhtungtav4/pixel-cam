<?php
/**
 * Blog post TOC — auto-generated from <h2> headings in the_content.
 * Markup matches pixel-cam/blog-post.html .post-toc
 *
 * @package Underscores
 */
defined('ABSPATH') || exit;

$content = get_the_content();
$headings = [];

// Match <h2>...</h2> or <h2 class="...">...</h2>
if (preg_match_all('#<h2[^>]*>(.*?)</h2>#is', $content, $matches)) {
    foreach ($matches[1] as $heading_html) {
        $text = trim(wp_strip_all_tags($heading_html));
        if ($text === '') {
            continue;
        }
        $id = sanitize_title($text);
        $headings[] = ['id' => $id, 'text' => $text];
    }
}

if (empty($headings)) {
    return;
}
?>
<aside class="post-toc" aria-label="<?php esc_attr_e('Mục lục bài viết', 'underscores'); ?>">
    <h5><?php esc_html_e('Mục lục', 'underscores'); ?></h5>
    <ol>
        <?php foreach ($headings as $h) : ?>
            <li><a href="#<?php echo esc_attr($h['id']); ?>"><?php echo esc_html($h['text']); ?></a></li>
        <?php endforeach; ?>
    </ol>
</aside>
