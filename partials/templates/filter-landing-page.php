<?php
/**
 * Filter landing page body — SEO landing for important product filters (#2).
 *
 * Markup: breadcrumb + H1 + meta + product grid (WP_Query filtered by ACF) +
 * intro content (prose) + FAQ accordion. Rank Math auto-emits FAQPage schema
 * from the repeater if the module is active.
 *
 * @package Underscores
 */
defined('ABSPATH') || exit;

if (! class_exists('WooCommerce')) {
    echo '<div class="wrap"><p>' . esc_html__('WooCommerce chưa kích hoạt.', 'underscores') . '</p></div>';
    return;
}

$fl = function_exists('get_fields') ? (get_fields() ?: []) : [];

$h1       = $fl['h1'] ?? '';
$meta     = $fl['meta'] ?? '';
$intro    = $fl['intro'] ?? '';
$cats     = $fl['categories'] ?? [];
$brands   = $fl['brands'] ?? [];
$min_price = $fl['min_price'] ?? '';
$max_price = $fl['max_price'] ?? '';
$on_sale  = ! empty($fl['on_sale']);
$count    = max(1, (int) ($fl['count'] ?? 12));
$orderby  = $fl['orderby'] ?? 'date';
$faq      = $fl['faq'] ?? [];

// Build WP_Query args from ACF filter settings.
$query_args = [
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => $count,
    'no_found_rows'  => false,
    'tax_query'      => ['relation' => 'AND'],
];

if (! empty($cats)) {
    $query_args['tax_query'][] = [
        'taxonomy' => 'product_cat',
        'field'    => 'term_id',
        'terms'    => array_map('intval', (array) $cats),
    ];
}
if (! empty($brands)) {
    $query_args['tax_query'][] = [
        'taxonomy' => 'product_brand',
        'field'    => 'term_id',
        'terms'    => array_map('intval', (array) $brands),
    ];
}
if (empty($query_args['tax_query'][0])) {
    unset($query_args['tax_query']);
}

// Price filter.
$meta_query = [];
if ($min_price !== '' && $min_price > 0) {
    $meta_query[] = ['key' => '_price', 'value' => (float) $min_price, 'compare' => '>=', 'type' => 'NUMERIC'];
}
if ($max_price !== '' && $max_price > 0) {
    $meta_query[] = ['key' => '_price', 'value' => (float) $max_price, 'compare' => '<=', 'type' => 'NUMERIC'];
}
if (! empty($meta_query)) {
    $query_args['meta_query'] = $meta_query;
}

// Order.
switch ($orderby) {
    case 'price_asc':
        $query_args['orderby'] = 'meta_value_num';
        $query_args['meta_key'] = '_price';
        $query_args['order'] = 'ASC';
        break;
    case 'price_desc':
        $query_args['orderby'] = 'meta_value_num';
        $query_args['meta_key'] = '_price';
        $query_args['order'] = 'DESC';
        break;
    case 'popularity':
        $query_args['orderby'] = 'meta_value_num';
        $query_args['meta_key'] = 'total_sales';
        $query_args['order'] = 'DESC';
        break;
    case 'rating':
        $query_args['orderby'] = 'meta_value_num';
        $query_args['meta_key'] = '_wc_average_rating';
        $query_args['order'] = 'DESC';
        break;
    default:
        $query_args['orderby'] = 'date';
        $query_args['order'] = 'DESC';
}

$products = new WP_Query($query_args);
$total = (int) $products->found_posts;
?>
<div class="wrap">
    <?php get_template_part('partials/components/breadcrumb', null, ['items' => [['label' => $h1 ?: get_the_title()]]]); ?>
</div>

<section class="section--flush"><div class="wrap page-head">
    <h1><?php echo esc_html($h1 ?: get_the_title()); ?></h1>
    <?php if ($meta) : ?><p class="meta"><?php echo esc_html($meta); ?> <b><?php echo $total; ?></b> <?php esc_html_e('sản phẩm', 'underscores'); ?></p><?php endif; ?>
</div></section>

<section class="section--flush"><div class="wrap">
    <?php if ($products->have_posts()) : ?>
        <div class="grid">
            <?php $i = 0;
            while ($products->have_posts()) : $products->the_post();
                get_template_part('partials/components/product-card', null, [
                    'product' => get_the_ID(),
                    'eager'   => $i === 0,
                ]);
                $i++;
            endwhile; ?>
        </div>
        <?php
        // Pagination (real /page/N/ URLs — crawlable).
        $big = 999999999;
        echo '<nav class="pager">';
        echo paginate_links([
            'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
            'format'    => '?paged=%#%',
            'current'   => max(1, get_query_var('paged')),
            'total'     => $products->max_num_pages,
            'prev_text' => '‹',
            'next_text' => '›',
            'type'      => 'plain',
            'end_size'  => 1,
            'mid_size'  => 2,
        ]);
        echo '</nav>';
        ?>
    <?php else : ?>
        <div class="empty-state empty-state--bordered">
            <div class="es-title"><?php esc_html_e('Chưa có sản phẩm phù hợp', 'underscores'); ?></div>
        </div>
    <?php endif;
    wp_reset_postdata(); ?>
</div></section>

<?php if ($intro) : ?>
    <section><div class="wrap">
        <div class="prose"><?php echo wp_kses_post($intro); ?></div>
    </div></section>
<?php endif; ?>

<?php if (! empty($faq)) : ?>
    <section><div class="wrap">
        <div class="ct-faq">
            <h3><?php esc_html_e('Câu hỏi thường gặp', 'underscores'); ?></h3>
            <?php foreach ($faq as $i => $item) : ?>
                <details class="item"<?php echo $i === 0 ? ' open' : ''; ?>>
                    <summary><?php echo esc_html($item['question'] ?? ''); ?></summary>
                    <div class="answer"><?php echo esc_html($item['answer'] ?? ''); ?></div>
                </details>
            <?php endforeach; ?>
        </div>
    </div></section>
<?php endif; ?>
