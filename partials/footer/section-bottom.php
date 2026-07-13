<?php
/**
 * Footer — Section 5: Bottom bar (copyright only).
 * Business info (MST/issued/...) was moved up to section-brand.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$copyright = $args['copyright'] ?? '';

if (! $copyright) {
    return;
}
?>
<div class="foot-bottom">
    <div class="wrap foot-bottom-inner">
        <span class="foot-copy"><?php echo esc_html($copyright); ?></span>
    </div>
</div>
