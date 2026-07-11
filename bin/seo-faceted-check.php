<?php

declare(strict_types=1);

/**
 * Self-check for SeoHook::params_are_faceted (noindex/canonical faceted guard).
 * Pure predicate, no WordPress needed. Run: php bin/seo-faceted-check.php
 */

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}
if (! defined('RANK_MATH_VERSION')) {
    // no-op; class only references it inside methods we don't call here.
}

require __DIR__ . '/../app/Hooks/SeoHook.php';

use Theme\Child\Hooks\SeoHook;

$cases = [
    'clean category'          => [[], false],
    'sort'                    => [['orderby' => 'price'], true],
    'price min'               => [['min_price' => '100'], true],
    'price max'               => [['max_price' => '900'], true],
    'rating'                  => [['rating_filter' => '4'], true],
    'search'                  => [['s' => 'sony'], true],
    'attribute filter'        => [['filter_pa_material' => 'metal'], true],
    'layered query_type'      => [['query_type_pa_material' => 'or'], true],
    'variation attribute'     => [['attribute_pa_mau' => 'xanh'], true],
    'pagination stays index'  => [['paged' => '2'], false],
    'empty orderby ignored'   => [['orderby' => ''], false],
    'utm passes (not facet)'  => [['utm_source' => 'fb'], false],
];

$failed = 0;
foreach ($cases as $name => [$get, $want]) {
    $got = SeoHook::params_are_faceted($get);
    if ($got !== $want) {
        $failed++;
        fwrite(STDERR, "FAIL $name: want " . var_export($want, true) . " got " . var_export($got, true) . "\n");
    }
    assert($got === $want, $name);
}

if ($failed === 0) {
    echo 'OK: all ' . count($cases) . " faceted cases pass\n";
    exit(0);
}
fwrite(STDERR, "$failed failed\n");
exit(1);
