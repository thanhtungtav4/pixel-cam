#!/usr/bin/env bash
#
# Build the theme's translation files (.pot, .po, .mo) from source.
#
# Usage:
#   bin/build-translations.sh                 # builds all three
#   bin/build-translations.sh --check         # check msgfmt is available, exit 1 if not
#
# Add to pre-commit hook so new strings are always captured. Pair with the
# `acf-json/validate.php` style: fails the commit if the build fails.
#
# @package underscores
set -euo pipefail

here="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
theme="$(cd "$here/.." && pwd)"

if [[ "${1:-}" == "--check" ]]; then
    if ! command -v msgfmt >/dev/null 2>&1; then
        echo "ERROR: msgfmt not found. Install gettext (brew install gettext)." >&2
        exit 1
    fi
    if ! command -v php >/dev/null 2>&1; then
        echo "ERROR: php not found." >&2
        exit 1
    fi
    echo "All tools present."
    exit 0
fi

cd "$theme"

echo "→ Building languages/underscores.pot ..."
php bin/build-pot.php

echo "→ Building languages/underscores-vi.po ..."
php bin/build-po.php

echo "→ Compiling languages/underscores-vi.mo ..."
msgfmt -o languages/underscores-vi.mo languages/underscores-vi.po

echo "Done. Translations ready."
