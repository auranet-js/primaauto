#!/bin/bash
# Backup pliku z datą przed edycją
# Użycie: ./scripts/backup-file.sh /pełna/ścieżka/do/pliku.php
#
# Tworzy kopię: plik.php → plik.php.bak-2026-04-15

if [ -z "$1" ]; then
    echo "Użycie: $0 <ścieżka_do_pliku>"
    exit 1
fi

FILE="$1"
if [ ! -f "$FILE" ]; then
    echo "Plik nie istnieje: $FILE"
    exit 1
fi

DATE=$(date +%F)
BACKUP="${FILE}.bak-${DATE}"

if [ -f "$BACKUP" ]; then
    echo "Backup już istnieje: $BACKUP"
    echo "Nadpisać? (t/n)"
    read -r CONFIRM
    if [ "$CONFIRM" != "t" ]; then
        echo "Anulowano."
        exit 0
    fi
fi

cp "$FILE" "$BACKUP"
echo "Backup: $BACKUP ($(stat -c%s "$BACKUP") bytes)"
