<?php

/**
 * About page body — orchestration of ACF sections.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$about_acf = function_exists('get_fields') ? (get_fields() ?: []) : [];

$hero     = $about_acf['about_hero'] ?? [];
$stats    = $about_acf['stats_settings'] ?? [];
$story    = $about_acf['story_settings'] ?? [];
$values   = $about_acf['values_settings'] ?? [];
$team     = $about_acf['team_settings'] ?? [];
$showroom = $about_acf['showroom_settings'] ?? [];
?>
<div class="wrap">
    <?php get_template_part('partials/components/breadcrumb', null, ['items' => [['label' => get_the_title()]]]); ?>
</div>

<?php
if (! empty($hero['is_show'])) {
    get_template_part('partials/about/section-hero', null, $hero);
}
if (! empty($stats['is_show'])) {
    get_template_part('partials/about/section-stats', null, $stats);
}
if (! empty($story['is_show'])) {
    get_template_part('partials/about/section-story', null, $story);
}
if (! empty($values['is_show'])) {
    get_template_part('partials/about/section-values', null, $values);
}
if (! empty($team['is_show'])) {
    get_template_part('partials/about/section-team', null, $team);
}
if (! empty($showroom['is_show'])) {
    get_template_part('partials/about/section-showroom', null, $showroom);
}
