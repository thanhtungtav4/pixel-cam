<?php
/**
 * Single Product Reviews — Pixel Cam custom template.
 *
 * Overrides woocommerce/templates/single-product-reviews.php with a custom
 * summary block (avg rating + histogram) and custom review cards.
 *
 * @package underscores
 */

defined('ABSPATH') || exit;

global $product;

if (! comments_open()) {
    return;
}

// Rating distribution from WC post meta.
$rating_counts   = $product->get_rating_counts(); // [1=>n, 2=>n, ...]
$review_count    = (int) $product->get_review_count();
$average         = (float) $product->get_average_rating();

// Re-query reviews with the page-number param (default 10/page).
$per_page     = (int) apply_filters('pxc_reviews_per_page', 10);
$current_page = max(1, (int) ($_GET['review-page'] ?? 1));
$offset       = ($current_page - 1) * $per_page;

$comments_query = new WP_Comment_Query();
$reviews        = $comments_query->query([
    'post_id' => get_the_ID(),
    'status'  => 'approve',
    'type'    => 'review',
    'number'  => $per_page,
    'offset'  => $offset,
    'orderby' => 'comment_date_gmt',
    'order'   => 'DESC',
]);
$total_pages = max(1, (int) ceil($review_count / $per_page));

/* -------- Star SVG (reused) -------- */
$star_svg = '<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';

/* -------- Build summary block -------- */
$histogram = [];
$total     = 0;
for ($s = 5; $s >= 1; $s--) {
    $c               = (int) ($rating_counts[$s] ?? 0);
    $histogram[$s]   = $c;
    $total          += $c;
}
$avg_display = $review_count > 0 ? number_format($average, 1) : '0.0';
?>

<div id="reviews" class="woocommerce-Reviews pxc-reviews">

    <?php if ($review_count > 0) : ?>
    <!-- ============== SUMMARY ============== -->
    <div class="pxc-review-summary">
        <div class="pxc-rs-left">
            <div class="pxc-rs-avg"><?php echo esc_html($avg_display); ?></div>
            <div class="pxc-rs-on">trên 5</div>
            <div class="pxc-rs-stars" aria-label="<?php echo esc_attr(sprintf('%s trên 5', $avg_display)); ?>">
                <?php
                $filled = (int) round($average);
                for ($i = 1; $i <= 5; $i++) {
                    echo $i <= $filled ? '<span class="on">' . $star_svg . '</span>' : '<span class="off">' . $star_svg . '</span>';
                }
                ?>
            </div>
            <div class="pxc-rs-count"><?php
                /* translators: %s = number of reviews */
                echo esc_html(sprintf(_n('%s đánh giá', '%s đánh giá', $review_count, 'underscores'), number_format_i18n($review_count)));
            ?></div>
        </div>

        <div class="pxc-rs-right">
            <?php for ($s = 5; $s >= 1; $s--) :
                $c   = $histogram[$s];
                $pct = $total > 0 ? round(($c / $total) * 100) : 0;
                ?>
                <div class="pxc-rs-row">
                    <span class="pxc-rs-label"><?php echo (int) $s; ?> ★</span>
                    <span class="pxc-rs-bar"><span class="pxc-rs-fill" style="width: <?php echo (int) $pct; ?>%"></span></span>
                    <span class="pxc-rs-num"><?php echo (int) $c; ?></span>
                </div>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============== LIST ============== -->
    <div id="comments" class="pxc-review-list">
        <?php if ($reviews) : ?>
            <?php foreach ($reviews as $review) :
                $rating   = (int) get_comment_meta($review->comment_ID, 'rating', true);
                $verified = (bool) get_comment_meta($review->comment_ID, 'verified', true);
                $variant  = (string) get_comment_meta($review->comment_ID, 'variant', true);
                $author   = $review->comment_author;
                // First letter of each word (max 2).
                $parts    = preg_split('/\s+/u', trim($author));
                $initials = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1));
                if (isset($parts[1]) && $parts[1] !== '' && mb_strlen($parts[1]) > 0) {
                    $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
                }
                $when = human_time_diff(strtotime($review->comment_date_gmt), current_time('timestamp'));
            ?>
                <div class="pxc-review-card" id="comment-<?php echo (int) $review->comment_ID; ?>">
                    <div class="pxc-rc-avatar" aria-hidden="true"><?php echo esc_html($initials); ?></div>
                    <div class="pxc-rc-body">
                        <div class="pxc-rc-head">
                            <span class="pxc-rc-name"><?php echo esc_html($author); ?></span>
                            <?php if ($verified) : ?>
                                <span class="pxc-rc-verified">✓ Đã mua tại Pixel Cam</span>
                            <?php endif; ?>
                            <span class="pxc-rc-time"><?php
                                /* translators: %s = time ago */
                                echo esc_html(sprintf(__('%s trước', 'underscores'), $when));
                            ?></span>
                        </div>
                        <?php if ($rating > 0) : ?>
                            <div class="pxc-rc-stars" aria-label="<?php echo esc_attr(sprintf('%d trên 5', $rating)); ?>">
                                <?php for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '<span class="on">' . $star_svg . '</span>' : '<span class="off">' . $star_svg . '</span>';
                                } ?>
                            </div>
                        <?php endif; ?>
                        <div class="pxc-rc-text">
                            <?php echo wpautop(esc_html($review->comment_content)); ?>
                        </div>
                        <?php if ($variant !== '') : ?>
                            <div class="pxc-rc-variant"><b>Phiên bản:</b> <?php echo esc_html($variant); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($total_pages > 1) : ?>
                <nav class="woocommerce-pagination pxc-review-pagination" aria-label="<?php esc_attr_e('Phân trang đánh giá', 'underscores'); ?>">
                    <?php
                    $base = add_query_arg('review-page', '%#%', get_permalink());
                    echo paginate_links([
                        'base'      => $base,
                        'format'    => '',
                        'current'   => $current_page,
                        'total'     => $total_pages,
                        'prev_text' => '&larr;',
                        'next_text' => '&rarr;',
                        'type'      => 'list',
                    ]);
                    ?>
                </nav>
            <?php endif; ?>
        <?php elseif ($review_count === 0) : ?>
            <p class="pxc-review-empty"><?php esc_html_e('Chưa có đánh giá nào. Hãy là người đầu tiên đánh giá sản phẩm này.', 'underscores'); ?></p>
        <?php endif; ?>
    </div>

    <!-- ============== REVIEW FORM ============== -->
    <?php
    $can_review = get_option('woocommerce_review_rating_verification_required') === 'no'
        || wc_customer_bought_product('', get_current_user_id(), $product->get_id());
    ?>
    <?php if ($can_review) : ?>
        <div id="review_form_wrapper" class="pxc-review-form-wrap">
            <button type="button" class="pxc-review-toggle" aria-expanded="false" aria-controls="pxcReviewForm">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                <?php esc_html_e('Viết đánh giá', 'underscores'); ?>
            </button>
            <div id="pxcReviewForm" class="pxc-review-form" hidden>
                <div class="pxc-rf-header">
                    <h3 class="pxc-rf-title"><?php esc_html_e('Chia sẻ trải nghiệm của bạn', 'underscores'); ?></h3>
                    <p class="pxc-rf-sub"><?php esc_html_e('Đánh giá giúp người mua khác hiểu sản phẩm thực tế. Chỉ mất 30 giây.', 'underscores'); ?></p>
                </div>
                <div class="pxc-rf-trust">
                    <span class="pxc-rf-trust-item">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 12l2 2 4-4"/><path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"/></svg>
                        <?php esc_html_e('Xác minh đã mua tại Pixel Cam', 'underscores'); ?>
                    </span>
                    <span class="pxc-rf-trust-item">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <?php esc_html_e('Email không công khai', 'underscores'); ?>
                    </span>
                </div>
                <?php
                $commenter    = wp_get_current_commenter();
                $comment_form = [
                    'title_reply'         => '',
                    'title_reply_before'  => '',
                    'title_reply_after'   => '',
                    'comment_notes_before'=> '',
                    'comment_notes_after' => '',
                    'label_submit'        => esc_html__('Gửi đánh giá', 'underscores'),
                    'logged_in_as'        => '',
                    'comment_field'       => '',
                    'class_form'          => 'pxc-rf',
                    'class_submit'        => 'pxc-rf-submit',
                ];
                $fields = [];
                if (! is_user_logged_in()) {
                    $fields['author'] = '<div class="pxc-rf-cell"><label for="author">' . esc_html__('Họ tên', 'underscores') . ' <span class="req">*</span></label><input id="author" name="author" type="text" required placeholder="' . esc_attr__('Nguyễn Văn A', 'underscores') . '" value="' . esc_attr($commenter['comment_author']) . '"></div>';
                    $fields['email']  = '<div class="pxc-rf-cell"><label for="email">' . esc_html__('Email', 'underscores') . ' <span class="req">*</span></label><input id="email" name="email" type="email" required placeholder="' . esc_attr__('email@example.com', 'underscores') . '" value="' . esc_attr($commenter['comment_author_email']) . '"></div>';
                }
                $comment_form['fields'] = $fields;

                // NOTE: input id is `pxc_rating` (NOT `rating`) to prevent
                // WC's single-product.js from auto-injecting its own <p class="stars">.
                $comment_field = '';
                if (wc_review_ratings_enabled()) {
                    $comment_field .= '<div class="pxc-rf-cell pxc-rf-rate-cell">';
                    $comment_field .= '<label class="pxc-rf-rate-label">' . esc_html__('Bạn chấm sản phẩm này', 'underscores') . ' <span class="req">*</span></label>';
                    $comment_field .= '<div class="pxc-rf-stars" data-stars role="radiogroup" aria-label="' . esc_attr__('Chấm điểm', 'underscores') . '">';
                    for ($i = 1; $i <= 5; $i++) {
                        $comment_field .= '<button type="button" data-val="' . $i . '" role="radio" aria-checked="false" aria-label="' . $i . ' sao">' . $star_svg . '</button>';
                    }
                    $comment_field .= '<span class="pxc-rf-rate-hint" aria-live="polite">' . esc_html__('Chạm để chấm', 'underscores') . '</span>';
                    $comment_field .= '<input type="hidden" name="rating" id="pxc_rating" value="" required>';
                    $comment_field .= '</div></div>';
                }
                $comment_field .= '<div class="pxc-rf-cell pxc-rf-text-cell">';
                $comment_field .= '<label for="comment">' . esc_html__('Nhận xét chi tiết', 'underscores') . ' <span class="req">*</span></label>';
                $comment_field .= '<textarea id="comment" name="comment" rows="5" required placeholder="' . esc_attr__('Chia sẻ trải nghiệm thực tế: dùng cho mục đích gì, ấn tượng / điểm chưa hài lòng, so sánh với sản phẩm cũ...', 'underscores') . '"></textarea>';
                $comment_field .= '<div class="pxc-rf-tips"><b>Mẹo:</b> ' . esc_html__('ít nhất 30 ký tự, không quảng cáo sản phẩm khác, dùng ngôn ngữ lịch sự.', 'underscores') . '</div>';
                $comment_field .= '</div>';
                $comment_form['comment_field'] = $comment_field;

                comment_form(apply_filters('woocommerce_product_review_comment_form_args', $comment_form));
                ?>
                <button type="button" class="pxc-rf-cancel" aria-controls="pxcReviewForm">
                    <?php esc_html_e('Hủy', 'underscores'); ?>
                </button>
            </div>
        </div>
        <?php
        // The review form toggle + star picker is enqueued by
        // Theme\Child\Hooks\WooProductHook::enqueue_review_form_asset() and
        // lives in assets/scripts/woocommerce/review-form.js. Do not inline
        // JS here — breaks strict CSP and violates WPCS.
        ?>
    <?php else : ?>
        <p class="woocommerce-verification-required pxc-review-locked"><?php esc_html_e('Chỉ khách đã mua sản phẩm mới có thể viết đánh giá.', 'underscores'); ?></p>
    <?php endif; ?>

    <div class="clear"></div>
</div>
