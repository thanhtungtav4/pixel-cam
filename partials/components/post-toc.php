<?php
/**
 * Blog post TOC — auto-generated from <h2> headings in the_content.
 * Markup matches pixel-cam/blog-post.html .post-toc
 *
 * Callers should pre-compute the headings via underscores_child_extract_headings()
 * and pass them via $args['headings'] so we don't re-run get_the_content() +
 * the_content filter + regex inside the render. Same data source as
 * underscores_child_add_heading_ids() so TOC anchors always match the
 * rendered heading ids.
 *
 * @param array $args {
 *   'headings' => list<array{id:string,text:string}>  Pre-computed headings.
 * }
 * @package Underscores
 */
defined('ABSPATH') || exit;

$headings = isset($args['headings']) && is_array($args['headings']) ? $args['headings'] : [];

// Fallback: if the caller didn't pre-compute (legacy include sites), extract
// here so the partial still works. The compute is cached by the_content
// upstream so this is cheap in practice.
if (empty($headings) && function_exists('underscores_child_extract_headings')) {
    $headings = underscores_child_extract_headings(get_the_content());
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
