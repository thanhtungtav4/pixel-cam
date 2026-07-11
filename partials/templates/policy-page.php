<?php
/**
 * Policy page body — TOC + sections from ACF repeater.
 * Markup matches pixel-cam/policy.html .pol-layout
 *
 * @package Underscores
 */
defined('ABSPATH') || exit;

$policy_acf = function_exists('get_fields') ? (get_fields() ?: []) : [];
$lead      = $policy_acf['lead'] ?? '';
$sections  = $policy_acf['sections'] ?? [];
?>
<div class="wrap">
    <?php get_template_part('partials/components/breadcrumb', null, ['items' => [['label' => get_the_title()]]]); ?>
</div>

<div class="wrap page-main">
    <h1 class="page-title"><?php the_title(); ?></h1>
    <?php if ($lead) : ?><p class="page-lead"><?php echo esc_html($lead); ?></p><?php endif; ?>

    <?php if (!empty($sections)) : ?>
    <div class="pol-layout">
        <nav class="pol-toc" aria-label="<?php esc_attr_e('Mục lục chính sách', 'underscores'); ?>">
            <h2><?php esc_html_e('Mục lục', 'underscores'); ?></h2>
            <ul>
                <?php foreach ($sections as $sec) :
                    $sid = sanitize_title($sec['id'] ?? $sec['title'] ?? '');
                    if ($sid === '') {
                        continue;
                    }
                    ?>
                    <li><a href="#<?php echo esc_attr($sid); ?>"><?php echo esc_html($sec['title'] ?? ''); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="pol-body">
            <?php foreach ($sections as $sec) :
                $sid    = sanitize_title($sec['id'] ?? $sec['title'] ?? '');
                $intro  = $sec['intro'] ?? '';
                $items  = $sec['list_items'] ?? [];
                $faq_q  = $sec['faq_question'] ?? '';
                $faq_a  = $sec['faq_answer'] ?? '';
                if ($sid === '') {
                    continue;
                }
                ?>
                <section id="<?php echo esc_attr($sid); ?>" class="pol-sec">
                    <h2><?php echo esc_html($sec['title'] ?? ''); ?></h2>
                    <?php if ($intro) : ?><p><?php echo esc_html($intro); ?></p><?php endif; ?>
                    <?php if (!empty($items)) : ?>
                        <ul class="pol-list">
                            <?php foreach ($items as $item) : ?>
                                <li><?php echo esc_html($item['text'] ?? ''); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if ($faq_q) : ?>
                        <details class="item">
                            <summary><?php echo esc_html($faq_q); ?></summary>
                            <div class="answer"><?php echo esc_html($faq_a); ?></div>
                        </details>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else : ?>
        <div class="prose"><?php the_content(); ?></div>
    <?php endif; ?>
</div>
