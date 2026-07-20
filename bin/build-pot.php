#!/usr/bin/env php
<?php
/**
 * Extract i18n strings from PHP source into a .pot (Portable Object Template).
 *
 * Scans the theme for calls to WordPress i18n functions and emits a .pot
 * file with one msgid per unique translatable string. Tracks source file
 * + line number so translators can locate context.
 *
 * Supports:
 *   - __(...)
 *   - _e(...)
 *   - _n(... ...)  (singular, plural, count) — keeps the singular as msgid
 *   - _x(... ...)  (text, context) — keeps the text as msgid, context as msgctxt
 *   - esc_html__(...)
 *   - esc_html_e(...)
 *   - esc_attr__(...)
 *   - esc_attr_e(...)
 *   - translate_nooped_plural(...)
 *
 * All functions take the form: fn( 'text' [, $domain ] ) or
 * fn( 'text', 'domain' ) for the _*() family. The text can be single OR
 * double-quoted; this script only handles single-quoted literal strings
 * (which is the WP coding standard).
 *
 * Usage:
 *   php bin/build-pot.php                    # writes languages/underscores.pot
 *   php bin/build-pot.php --domain=foo       # override text domain
 *   php bin/build-pot.php --out=path/to.pot  # custom output path
 *
 * @package underscores
 */

declare(strict_types=1);

$theme_path = dirname(__DIR__);
$domain     = 'underscores';
$out_path   = $theme_path . '/languages/' . $domain . '.pot';

// Parse CLI args.
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--domain=')) {
        $domain = substr($arg, 9);
        $out_path = $theme_path . '/languages/' . $domain . '.pot';
    } elseif (str_starts_with($arg, '--out=')) {
        $out_path = $arg;
    }
}

// 1. Collect translatable strings by scanning PHP files.
$strings = []; // msgid => ['msgctxt' => '', 'refs' => []]

$iter = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($theme_path, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iter as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();

    // Skip the build-pot.php itself (it has regex examples) and vendor.
    if (str_ends_with($path, 'build-pot.php') || str_contains($path, '/vendor/')) {
        continue;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        continue;
    }

    // Tokenize the file so we can find the line numbers of msgid occurrences.
    $tokens = token_get_all($content);

    // i18n functions to look for. Each entry maps the function name to the
    // index of the msgid token (after the opening paren).
    $funcs = [
        '__'              => 1,
        '_e'              => 1,
        '_x'              => 1,
        '_ex'             => 1,
        '_n'              => 1,  // singular; plural is at index 3 (4th token after open paren)
        'esc_html__'      => 1,
        'esc_html_e'      => 1,
        'esc_html_x'      => 1,
        'esc_attr__'      => 1,
        'esc_attr_e'      => 1,
        'esc_attr_x'      => 1,
    ];

    $i = 0;
    $count = count($tokens);
    while ($i < $count) {
        $token = $tokens[$i];
        if (is_array($token) && in_array($token[1], array_keys($funcs), true)) {
            $func = $token[1];
            $msgid_idx = $funcs[$func];

            // Walk to the msgid string literal. The standard pattern is:
            //   FN( WS 'msgid' [WS, 'domain' [WS, 'plural' [WS, 'count']]] )
            // We just need the next string literal at the expected position.
            $j = $i + 1;
            // Skip whitespace + (.
            while ($j < $count) {
                $t = $tokens[$j];
                if (is_array($t) && $t[0] === T_WHITESPACE) { $j++; continue; }
                if (is_string($t) && $t === '(') { $j++; break; }
                break;
            }
            // Now the next non-whitespace should be the string literal.
            $msgid = null;
            $msgid_line = null;
            while ($j < $count) {
                $t = $tokens[$j];
                if (is_array($t) && $t[0] === T_WHITESPACE) { $j++; continue; }
                if (is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING) {
                    // Strip surrounding quotes + unescape \' \\ for single-quoted.
                    $raw = $t[1];
                    $first = $raw[0];
                    $literal = substr($raw, 1, -1);
                    if ($first === "'") {
                        $literal = str_replace(["\\'", "\\\\"], ["'", "\\"], $literal);
                    } elseif ($first === '"') {
                        $literal = str_replace(['\\"', '\\\\'], ['"', '\\'], $literal);
                    }
                    $msgid = $literal;
                    $msgid_line = $t[2];
                    break;
                }
                break; // not a string literal — skip
            }

            if ($msgid !== null && $msgid !== '') {
                // For _x / _ex / esc_attr_x etc., grab the context (2nd string).
                $msgctxt = '';
                if (in_array($func, ['_x', '_ex', 'esc_html_x', 'esc_attr_x'], true)) {
                    $k = $j + 1;
                    // skip whitespace + comma
                    while ($k < $count) {
                        $t = $tokens[$k];
                        if (is_array($t) && $t[0] === T_WHITESPACE) { $k++; continue; }
                        if (is_string($t) && $t === ',') { $k++; break; }
                        break;
                    }
                    while ($k < $count) {
                        $t = $tokens[$k];
                        if (is_array($t) && $t[0] === T_WHITESPACE) { $k++; continue; }
                        if (is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING) {
                            $raw = $t[1];
                            $first = $raw[0];
                            $ctx = substr($raw, 1, -1);
                            if ($first === "'") {
                                $ctx = str_replace(["\\'", "\\\\"], ["'", "\\"], $ctx);
                            } elseif ($first === '"') {
                                $ctx = str_replace(['\\"', '\\\\'], ['"', '\\'], $ctx);
                            }
                            $msgctxt = $ctx;
                            break;
                        }
                        break;
                    }
                }

                $key = $msgctxt . "\x04" . $msgid; // context + msgid composite
                $rel = ltrim(str_replace($theme_path, '', $path), '/');
                $ref = $rel . ':' . $msgid_line;
                if (! isset($strings[$key])) {
                    $strings[$key] = [
                        'msgid'   => $msgid,
                        'msgctxt' => $msgctxt,
                        'refs'    => [],
                    ];
                }
                if (! in_array($ref, $strings[$key]['refs'], true)) {
                    $strings[$key]['refs'][] = $ref;
                }
            }
        }
        $i++;
    }
}

// 2. Sort strings by msgid (case-sensitive, natural order).
ksort($strings);

// 3. Emit .pot file.
$dir = dirname($out_path);
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$now = date('Y-m-d H:i+0000');
$pot = <<<POT
# Copyright (C) {$now} Pixel Cam
# This file is distributed under the GNU General Public License v2 or later.
msgid ""
msgstr ""
"Project-Id-Version: Pixel Cam Child Theme\\n"
"Report-Msgid-Bugs-To: https://github.com/thanhtungtav4/Omnichannel\\n"
"POT-Creation-Date: {$now}\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n"
"Language-Team: Vietnamese\\n"
"X-Generator: underscores/bin/build-pot.php\\n"
"X-Domain: {$domain}\\n"

POT;

foreach ($strings as $entry) {
    $pot .= "\n";
    if ($entry['msgctxt'] !== '') {
        $pot .= 'msgctxt ' . format_pot_string($entry['msgctxt']) . "\n";
    }
    $pot .= 'msgid ' . format_pot_string($entry['msgid']) . "\n";
    $pot .= 'msgstr ""' . "\n";
    foreach ($entry['refs'] as $ref) {
        $pot .= '#: ' . $ref . "\n";
    }
}

file_put_contents($out_path, $pot);
echo "Wrote " . count($strings) . " strings to {$out_path}\n";

/**
 * Format a PHP string into PO-style quoted form. The PO format uses:
 *   "msgid"
 *   "with ""escaped"" quotes"
 *   "and newlines\n"
 *   "and multiline"
 *   "concatenation"
 */
function format_pot_string(string $s): string
{
    $lines = explode("\n", $s);
    $out = '';
    foreach ($lines as $i => $line) {
        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $line);
        $out .= '"' . $escaped . '"' . ($i < count($lines) - 1 ? "\n" : '');
    }
    return $out;
}
