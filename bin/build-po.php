#!/usr/bin/env php
<?php
/**
 * Build the .po (translation) file from the .pot template.
 *
 * Strategy: since the theme source strings are already in Vietnamese, this
 * generates a .po where msgstr == msgid (a "self-translation"). This means:
 *   - load_theme_textdomain('underscores') still works (find the .mo).
 *   - Translations can be overridden per-locale without changing source.
 *   - Adding a new language later is just a matter of producing a new .po
 *     and letting translators fill in msgstr.
 *
 * Usage:
 *   php bin/build-po.php                    # builds underscores-vi.po from underscores.pot
 *   php bin/build-po.php --pot=foo.pot      # custom input
 *   php bin/build-po.php --out=path/to.po   # custom output
 *
 * @package underscores
 */

declare(strict_types=1);

$theme_path = dirname(__DIR__);
$pot_path   = $theme_path . '/languages/underscores.pot';
$po_path    = $theme_path . '/languages/underscores-vi.po';

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--pot=')) {
        $pot_path = $arg;
    } elseif (str_starts_with($arg, '--out=')) {
        $po_path = $arg;
    }
}

if (! is_file($pot_path)) {
    fwrite(STDERR, "Missing POT file: {$pot_path}\n");
    exit(1);
}

// Parse the .pot file. The format is simple: each entry has msgid (and
// optionally msgctxt) + msgstr, separated from refs (#: ...) and other
// entries by blank lines. The very first entry (with msgid "") is the
// PO header — keep it as-is.
$raw = file_get_contents($pot_path);
$blocks = preg_split('/\R\s*\R/', $raw);

$header = '';
$entries = [];
foreach ($blocks as $block) {
    $block = trim($block);
    if ($block === '') {
        continue;
    }

    // Detect header: first block whose msgid is "".
    if ($header === '' && preg_match('/^msgid\s+""/m', $block)) {
        $header = $block . "\n";
        continue;
    }

    $entry = [
        'msgctxt' => '',
        'msgid'   => '',
        'msgstr'  => '',
        'refs'    => [],
    ];

    $lines = explode("\n", $block);
    $field = null; // 'msgctxt' | 'msgid' | 'msgstr' | null
    foreach ($lines as $line) {
        $trim = trim($line);
        if ($trim === '' || $trim[0] === '#') {
            continue;
        }
        if (str_starts_with($trim, 'msgctxt ')) {
            $field = 'msgctxt';
            $entry[$field] = unquote_po_line(substr($trim, strlen('msgctxt ')));
        } elseif (str_starts_with($trim, 'msgid ')) {
            $field = 'msgid';
            $entry[$field] = unquote_po_line(substr($trim, strlen('msgid ')));
        } elseif (str_starts_with($trim, 'msgstr ')) {
            $field = 'msgstr';
            $entry[$field] = unquote_po_line(substr($trim, strlen('msgstr ')));
        } elseif (str_starts_with($trim, '"')) {
            // Continuation of the current field.
            if ($field !== null) {
                $entry[$field] .= unquote_po_line($trim);
            }
        } else {
            // Unknown / reference comment line — ignore.
            $field = null;
        }
    }

    // Capture # references separately. We re-scan for #: lines.
    foreach ($lines as $line) {
        if (preg_match('/^#:\s*(.+)$/', trim($line), $m)) {
            foreach (preg_split('/\s+/', $m[1]) as $ref) {
                $entry['refs'][] = $ref;
            }
        }
    }

    $entries[] = $entry;
}

// Build the .po: header verbatim + entries where msgstr = msgid.
$out = '';
if ($header !== '') {
    $out .= $header . "\n";
}
foreach ($entries as $e) {
    $out .= "\n";
    if ($e['msgctxt'] !== '') {
        $out .= 'msgctxt ' . quote_po_string($e['msgctxt']) . "\n";
    }
    $out .= 'msgid ' . quote_po_string($e['msgid']) . "\n";
    // Self-translation: source is already Vietnamese, copy as msgstr.
    $out .= 'msgstr ' . quote_po_string($e['msgid']) . "\n";
    foreach ($e['refs'] as $ref) {
        $out .= '#: ' . $ref . "\n";
    }
}

$dir = dirname($po_path);
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}
file_put_contents($po_path, $out);
echo "Wrote " . count($entries) . " entries to {$po_path}\n";

/**
 * Take a single PO line like `"foo" "bar"` (after the keyword) and return
 * the concatenated, unescaped text.
 */
function unquote_po_line(string $line): string
{
    $line = trim($line);
    // Multiple quoted strings on one line? Concatenate.
    preg_match_all('/"(.*?)"/', $line, $m);
    $out = '';
    foreach ($m[1] as $part) {
        $out .= unescape_po($part);
    }
    return $out;
}

function unescape_po(string $s): string
{
    return str_replace(['\\n', '\\"', '\\\\', "\\'"], ["\n", '"', '\\', "'"], $s);
}

function quote_po_string(string $s): string
{
    $lines = explode("\n", $s);
    $out = '';
    foreach ($lines as $i => $line) {
        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $line);
        $out .= '"' . $escaped . '"';
        if ($i < count($lines) - 1) {
            $out .= "\n";
        }
    }
    return $out;
}
