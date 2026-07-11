<?php
/**
 * Blog post TOC — auto-generated from <h2> headings in the_content.
 * Markup matches pixel-cam/blog-post.html .post-toc
 *
 * Uses the same heading extraction as underscores_child_add_heading_ids()
 * so TOC anchors always match the rendered heading ids.
 *
 * @package Underscores
 */
defined('ABSPATH') || exit;

$headings = function_exists('underscores_child_extract_headings')
    ? underscores_child_extract_headings(get_the_content())
    : [];

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
