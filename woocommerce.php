<?php

/**
 * WooCommerce wrapper.
 *
 * header.php already opens <main>, footer.php closes it. The default Woo
 * content wrappers are unhooked in WooHook and replaced with a plain
 * container so we don't nest <main> inside <main>.
 *
 * @package Underscores
 */

defined('ABSPATH') || exit;

get_header();

if (function_exists('woocommerce_content')) {
    woocommerce_content();
}

get_footer();
