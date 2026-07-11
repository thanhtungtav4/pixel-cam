<?php
/**
 * Template Name: Sơ đồ trang
 * Template Post Type: page
 *
 * @author Underscores
 */
declare(strict_types=1);
if (!defined('ABSPATH')) {
    exit;
}
underscores_child_set_main_class('page-sitemap');
get_header();
the_post();
?>
<div class="wrap">
    <?php get_template_part('partials/components/breadcrumb', null, ['items' => [['label' => get_the_title()]]]); ?>
</div>

<div class="wrap page-main">
    <h1 class="page-title"><?php the_title(); ?></h1>

    <div class="blog-layout">
        <div class="prose">
            <?php
            // Pages by slug (not hardcoded ID — ID thay đổi theo môi trường)
            $page_sections = [
                [
                    'title'  => __('Trang chính', 'underscores'),
                    'slugs'  => ['trang-chu', 'gioi-thieu', 'lien-he', 'tin-tuc'],
                ],
                [
                    'title'  => __('Cửa hàng', 'underscores'),
                    'slugs'  => class_exists('WooCommerce')
                        ? ['shop', 'cart', 'checkout', 'my-account']
                        : [],
                ],
            ];
            foreach ($page_sections as $sec) :
                if (empty($sec['slugs'])) {
                    continue;
                }
                $links = [];
                foreach ($sec['slugs'] as $slug) {
                    $page = get_page_by_path($slug);
                    if ($page && $page->post_status === 'publish') {
                        $links[] = '<li><a href="' . esc_url(get_permalink($page)) . '">' . esc_html($page->post_title) . '</a></li>';
                    }
                }
                if (empty($links)) {
                    continue;
                }
                ?>
                <section style="margin-bottom:36px">
                    <h2 style="font-size:18px;margin-bottom:14px"><?php echo esc_html($sec['title']); ?></h2>
                    <ul style="list-style:none;display:flex;flex-direction:column;gap:8px">
                        <?php echo implode('', $links); ?>
                    </ul>
                </section>
            <?php endforeach;

            // Product categories
            $cats = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0, 'number' => 12]);
            if (!empty($cats) && !is_wp_error($cats)) : ?>
                <section style="margin-bottom:36px">
                    <h2 style="font-size:18px;margin-bottom:14px"><?php esc_html_e('Danh mục sản phẩm', 'underscores'); ?></h2>
                    <ul style="list-style:none;display:flex;flex-direction:column;gap:8px">
                        <?php foreach ($cats as $cat) : ?>
                            <li><a href="<?php echo esc_url(get_term_link($cat)); ?>"><?php echo esc_html($cat->name); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif;

            // Latest posts
            $posts = get_posts(['post_type' => 'post', 'numberposts' => 10, 'post_status' => 'publish']);
            if (!empty($posts)) : ?>
                <section style="margin-bottom:36px">
                    <h2 style="font-size:18px;margin-bottom:14px"><?php esc_html_e('Tin tức', 'underscores'); ?></h2>
                    <ul style="list-style:none;display:flex;flex-direction:column;gap:8px">
                        <?php foreach ($posts as $p) : ?>
                            <li><a href="<?php echo esc_url(get_permalink($p)); ?>"><?php echo esc_html($p->post_title); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        </div>

        <aside class="bside">
            <div class="block">
                <h4><?php esc_html_e('Liên kết nhanh', 'underscores'); ?></h4>
                <ul style="list-style:none;display:flex;flex-direction:column;gap:10px">
                    <li><a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Trang chủ', 'underscores'); ?></a></li>
                    <?php
                    $shop_page = class_exists('WooCommerce') && wc_get_page_id('shop')
                        ? get_permalink(wc_get_page_id('shop'))
                        : home_url('/shop/');
                    $about_page = get_page_by_path('gioi-thieu');
                    $contact_page = get_page_by_path('lien-he');
                    ?>
                    <li><a href="<?php echo esc_url($shop_page); ?>"><?php esc_html_e('Cửa hàng', 'underscores'); ?></a></li>
                    <?php if ($about_page) : ?><li><a href="<?php echo esc_url(get_permalink($about_page)); ?>"><?php esc_html_e('Giới thiệu', 'underscores'); ?></a></li><?php endif; ?>
                    <?php if ($contact_page) : ?><li><a href="<?php echo esc_url(get_permalink($contact_page)); ?>"><?php esc_html_e('Liên hệ', 'underscores'); ?></a></li><?php endif; ?>
                </ul>
            </div>
        </aside>
    </div>
</div>
<?php
get_footer();
