<?php

/**
 * Footer — Pixel Cam.
 *
 * Thin orchestrator: load Theme Settings, pass per-section slices to
 * dedicated partials under partials/footer/.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$general = function_exists('underscores_get_option') ? (underscores_get_option('general_section') ?: []) : [];
$footer  = function_exists('underscores_get_option') ? (underscores_get_option('footer_general_section') ?: []) : [];

$hotline         = $general['hotline'] ?? '';
$email           = $general['email'] ?? '';
$address         = $general['address'] ?? '';
$description     = $footer['description'] ?? '';
$copyright       = $footer['copyright'] ?? '';
$social_links    = $footer['social_links'] ?? [];
$payment_methods = $footer['payment_methods'] ?? [];
$business        = $footer['business_info'] ?? [];

// Skip the whole footer if literally nothing to show. Keeps output clean on empty config.
$has_brand       = $description || ! empty($social_links) || has_custom_logo() || get_bloginfo('name');
$has_products    = has_nav_menu('footer-products');
$has_support     = has_nav_menu('footer-support');
$has_contact     = $hotline || $email || $address || ! empty($payment_methods);
$has_bottom      = $copyright || ! empty($business);
$has_any_section = $has_brand || $has_products || $has_support || $has_contact || $has_bottom;
?>
</main>

<?php if ($has_any_section) : ?>
<footer>
    <?php if ($has_brand || $has_products || $has_support || $has_contact) : ?>
        <div class="wrap">
            <div class="foot-grid foot-grid--four">
                <?php
                if ($has_brand) {
                    get_template_part('partials/footer/section-brand', null, [
                        'description'  => $description,
                        'social_links' => $social_links,
                        'business'     => $business,
                    ]);
                }
                if ($has_products) {
                    get_template_part('partials/footer/section-products');
                }
                if ($has_support) {
                    get_template_part('partials/footer/section-support');
                }
                if ($has_contact) {
                    get_template_part('partials/footer/section-contact', null, [
                        'hotline'         => $hotline,
                        'email'           => $email,
                        'address'         => $address,
                        'payment_methods' => $payment_methods,
                    ]);
                }
                ?>
            </div>
        </div>
    <?php endif; ?>

    <?php
    get_template_part('partials/footer/section-bottom', null, [
        'copyright' => $copyright,
    ]);
    ?>
</footer>
<?php endif; ?>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<?php wp_footer(); ?>
</body>
</html>
