#!/bin/bash
# PHP lint na pluginie asiaauto-sync (bez vendor/)
PLUGIN_DIR="$HOME/domains/asiaauto.pl/public_html/wp-content/plugins/asiaauto-sync"

echo "PHP lint: $PLUGIN_DIR"
echo "---"

ERRORS=0
while IFS= read -r -d '' file; do
    OUTPUT=$(php -l "$file" 2>&1)
    if [ $? -ne 0 ]; then
        echo "FAIL: $file"
        echo "  $OUTPUT"
        ERRORS=$((ERRORS + 1))
    fi
done < <(find "$PLUGIN_DIR" -name "*.php" ! -path "*/vendor/*" ! -name "*.bak*" -print0)

echo "---"
if [ $ERRORS -eq 0 ]; then
    echo "OK — zero błędów."
else
    echo "BŁĘDY: $ERRORS plik(i) z problemami."
    exit 1
fi
