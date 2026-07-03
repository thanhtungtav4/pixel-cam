<?php

declare(strict_types=1);

/**
 * Self-check for SeoHook::params_are_faceted (#1/#12 crawl guard).
 * Pure predicate, no WordPress needed. Run: php bin/seo-noindex-check.php
 */

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__); // let the guarded class file load standalone
}

require __DIR__ . '/../app/Hooks/SeoHook.php';

use Theme\Child\Hooks\SeoHook;

$cases = [
    // [query args, expected noindex?]
    'clean product URL'        => [[], false],
    'sort by price'            => [['orderby' => 'price'], true],
    'price filter'             => [['min_price' => '100'], true],
    'internal search'          => [['s' => 'sony a7'], true],
    'layered nav filter_color' => [['filter_color' => 'black'], true],
    'query_type_color'         => [['query_type_color' => 'or'], true],
    'add-to-cart param'        => [['add-to-cart' => '42'], true],
    'empty orderby ignored'    => [['orderby' => ''], false],
    'unrelated utm passes'     => [['utm_source' => 'fb'], false],
    'real pagination passes'   => [['paged' => '2'], false],
];

$failed = 0;
foreach ($cases as $name => [$get, $expected]) {
    $got = SeoHook::params_are_faceted($get);
    $ok  = $got === $expected;
    if (! $ok) {
        $failed++;
        fwrite(STDERR, sprintf("FAIL %s: expected %s got %s\n", $name, var_export($expected, true), var_export($got, true)));
    }
    assert($ok, $name);
}

if ($failed === 0) {
    echo "OK: all " . count($cases) . " noindex-param cases pass\n";
    exit(0);
}

fwrite(STDERR, "$failed case(s) failed\n");
exit(1);
