<?php

declare(strict_types=1);

/**
 * Validate ACF Local JSON field groups in this directory.
 *
 * Checks (the 3 mistakes that silently lose data or break sync):
 *   1. Each .json parses as valid JSON.
 *   2. Every field `key` is unique across ALL groups (duplicate keys = ACF data loss).
 *   3. Within one group, sibling `name`s do not collide (duplicate names = unreadable data).
 *
 * Usage:
 *   php underscores-child/acf-json/validate.php            # validate (guard)
 *   php underscores-child/acf-json/validate.php --summary  # validate + print a field tree to review fast
 *
 * Exit code 0 = OK, 1 = problems found (safe to use in a pre-commit hook / CI).
 */

$summary = in_array('--summary', $argv, true);

$dir = __DIR__;
$files = glob($dir . '/group_*.json') ?: [];

if ($files === []) {
    fwrite(STDERR, "No group_*.json files found in {$dir}\n");
    exit(0);
}

$errors = [];
$all_keys = []; // key => first file that used it

/** Recursively collect field keys + per-parent name collisions. */
function walk(array $fields, string $file, string $group_key, array &$all_keys, array &$errors, string $path = 'root'): void
{
    $names_here = [];

    foreach ($fields as $field) {
        if (! is_array($field) || ! isset($field['key'])) {
            $errors[] = "{$file}: a field under {$path} is missing \"key\".";
            continue;
        }

        $key = $field['key'];

        // 2. Global key uniqueness.
        if (isset($all_keys[$key])) {
            $errors[] = "Duplicate key \"{$key}\": in {$file} and {$all_keys[$key]}. Keys must be unique site-wide (duplicates lose data).";
        } else {
            $all_keys[$key] = $file;
        }

        // 3. Sibling name collision (tabs have empty name, skip those).
        $name = $field['name'] ?? '';
        if ($name !== '') {
            if (isset($names_here[$name])) {
                $errors[] = "{$file}: duplicate field name \"{$name}\" under {$path} (siblings must have unique names).";
            }
            $names_here[$name] = true;
        }

        // Recurse into containers.
        foreach (['sub_fields', 'layouts'] as $child_key) {
            if (! empty($field[$child_key]) && is_array($field[$child_key])) {
                $child_path = $name !== '' ? "{$path} > {$name}" : "{$path} > {$key}";
                walk($field[$child_key], $file, $group_key, $all_keys, $errors, $child_path);
            }
        }
    }
}

foreach ($files as $file) {
    $short = basename($file);
    $raw = file_get_contents($file);
    $data = json_decode($raw, true);

    // 1. Valid JSON.
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = "{$short}: invalid JSON — " . json_last_error_msg();
        continue;
    }

    if (! isset($data['key'])) {
        $errors[] = "{$short}: missing group \"key\".";
    }
    if (! isset($data['fields']) || ! is_array($data['fields'])) {
        $errors[] = "{$short}: missing or invalid \"fields\" array.";
        continue;
    }

    $group_key = $data['key'] ?? $short;

    // Group key also counts toward global uniqueness.
    if (isset($all_keys[$group_key])) {
        $errors[] = "Duplicate group key \"{$group_key}\": {$short} and {$all_keys[$group_key]}.";
    } else {
        $all_keys[$group_key] = $short;
    }

    walk($data['fields'], $short, $group_key, $all_keys, $errors);
}

/** Print a compact field tree: name (type) [extra], nested by container. */
function print_tree(array $fields, int $depth = 1): void
{
    $pad = str_repeat('  ', $depth);
    foreach ($fields as $field) {
        if (! is_array($field) || ! isset($field['type'])) {
            continue;
        }
        $type = $field['type'];
        $name = $field['name'] ?? '';

        if ($type === 'tab') {
            echo "{$pad}— tab: " . ($field['label'] ?? '') . "\n";
            continue;
        }

        $extra = [];
        if (! empty($field['return_format'])) {
            $extra[] = $field['return_format'];
        }
        if (isset($field['default_value'])) {
            $extra[] = 'default=' . (is_bool($field['default_value']) ? ($field['default_value'] ? '1' : '0') : (string) $field['default_value']);
        }
        $suffix = $extra ? ' [' . implode(', ', $extra) . ']' : '';

        echo "{$pad}{$name} ({$type}){$suffix}\n";

        if (! empty($field['sub_fields']) && is_array($field['sub_fields'])) {
            print_tree($field['sub_fields'], $depth + 1);
        }
        if (! empty($field['layouts']) && is_array($field['layouts'])) {
            foreach ($field['layouts'] as $layout) {
                echo str_repeat('  ', $depth + 1) . '» layout: ' . ($layout['name'] ?? '') . "\n";
                if (! empty($layout['sub_fields']) && is_array($layout['sub_fields'])) {
                    print_tree($layout['sub_fields'], $depth + 2);
                }
            }
        }
    }
}

if ($errors === []) {
    $count = count($files);

    if ($summary) {
        foreach ($files as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            echo "\n" . basename($file) . "  →  " . ($data['title'] ?? '') . "\n";
            print_tree($data['fields'] ?? []);
        }
        echo "\n";
    }

    echo "OK: {$count} field group file(s) valid, all keys unique.\n";
    exit(0);
}

fwrite(STDERR, "ACF JSON validation FAILED:\n");
foreach ($errors as $e) {
    fwrite(STDERR, "  - {$e}\n");
}
exit(1);
