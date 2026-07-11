<?php

/**
 * Breadcrumb — the Pixel Cam .crumb trail.
 *
 * Always starts with "Trang chủ" (home). Pass the trail AFTER home:
 *
 *   get_template_part('partials/components/breadcrumb', null, [
 *       'items' => [
 *           ['label' => 'Tin tức', 'url' => $blog_url],  // linked crumb
 *           ['label' => get_the_title()],                // last = current, no url
 *       ],
 *   ]);
 *
 * The last item (or any item without a 'url') renders as the current .cur span.
 * Caller owns the surrounding .wrap.
 *
 * @param array $args { 'items' => list<array{label:string,url?:string}> }
 * @package Underscores
 */

defined('ABSPATH') || exit;

$items = array_values(array_filter(
    (array) ($args['items'] ?? []),
    static fn($it) => is_array($it) && ($it['label'] ?? '') !== ''
));
$last = count($items) - 1;
?>
<nav class="crumb" aria-label="Breadcrumb">
    <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Trang chủ', 'underscores'); ?></a>
    <?php foreach ($items as $i => $item) :
        $label = (string) $item['label'];
        $url   = (string) ($item['url'] ?? '');
        ?>
        <span class="sep">/</span>
        <?php if ($url !== '' && $i !== $last) : ?>
            <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
        <?php else : ?>
            <span class="cur"><?php echo esc_html($label); ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
