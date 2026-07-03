<?php

/**
 * Footer — Pixel Cam.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

$general      = function_exists('underscores_get_option') ? (underscores_get_option('general_section') ?: []) : [];
$footer       = function_exists('underscores_get_option') ? (underscores_get_option('footer_general_section') ?: []) : [];

$description  = $footer['description'] ?? '';
$copyright    = $footer['copyright'] ?? ($general['copyright'] ?? '');
$hotline      = $general['hotline'] ?? '';
$email        = $general['email'] ?? '';
$address      = $general['address'] ?? '';
?>
</main>

<footer><div class="wrap">
    <div class="foot-grid">
        <div>
            <div class="foot-logo"><span class="mark">P</span> <?php echo esc_html(get_bloginfo('name')); ?></div>
            <?php if ($description) : ?>
                <p class="foot-desc"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
        </div>

        <?php if (has_nav_menu('footer-products')) : ?>
        <div>
            <h5><?php esc_html_e('Sản phẩm', 'underscores'); ?></h5>
            <?php
            wp_nav_menu([
                'theme_location' => 'footer-products',
                'container'      => false,
                'menu_class'     => '',
                'fallback_cb'    => false,
                'depth'          => 1,
            ]);
            ?>
        </div>
        <?php endif; ?>

        <?php if (has_nav_menu('footer-support')) : ?>
        <div>
            <h5><?php esc_html_e('Hỗ trợ', 'underscores'); ?></h5>
            <?php
            wp_nav_menu([
                'theme_location' => 'footer-support',
                'container'      => false,
                'menu_class'     => '',
                'fallback_cb'    => false,
                'depth'          => 1,
            ]);
            ?>
        </div>
        <?php endif; ?>

        <?php if ($hotline || $email || $address) : ?>
        <div>
            <h5><?php esc_html_e('Liên hệ', 'underscores'); ?></h5>
            <ul>
                <?php if ($hotline) : ?><li><?php echo esc_html($hotline); ?></li><?php endif; ?>
                <?php if ($email) : ?><li><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></li><?php endif; ?>
                <?php if ($address) :
                    foreach (preg_split('/\r\n|\r|\n/', $address) as $line) :
                        $line = trim($line);
                        if ($line === '') {
                            continue;
                        }
                        ?>
                        <li><?php echo esc_html($line); ?></li>
                    <?php endforeach;
                endif; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($copyright) : ?>
        <div class="foot-bottom">
            <span><?php echo esc_html($copyright); ?></span>
        </div>
    <?php endif; ?>
</div></footer>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<?php wp_footer(); ?>
</body>
</html>
