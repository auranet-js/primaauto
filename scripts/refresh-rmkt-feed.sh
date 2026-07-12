#!/bin/bash
# RMKT — cotygodniowe odświeżenie feedu dynamic remarketing (cron: niedziela 06:00).
#
# Po co: DynamicCustomAsset w Google Ads jest immutable — zmiana ceny w bazie NIE propaguje się
# do feedu. Bez tego skryptu feed reklamuje ceny sprzed tygodni (incydent 2026-07-12: 99% wpisów
# z błędną ceną, GAC M8 za 278 tys. przy realnych 147 tys.).
#
# Bezpieczniki są w gads-rmkt-feed-refresh.py (min. liczba wpisów, próg usunięć, transakcja atomowa).
# Ten wrapper tylko buduje feed z prod DB i podaje go dalej.

set -euo pipefail

REPO="/home/host476470/projekty/primaauto"
WP="/home/host476470/domains/primaauto.com.pl/public_html"
OUT="/home/host476470/.claude/rmkt-feed-$(date +%F).json"

echo "=== $(date '+%F %T') — RMKT feed refresh ==="

# 1) build z prod DB (READ-ONLY)
cd "$WP"
php "$REPO/scripts/build-gads-hub-feed.php" "$OUT" 2>&1 | head -3

if [ ! -s "$OUT" ]; then
  echo "ABORT: build nie wyprodukował feedu ($OUT pusty)" >&2
  exit 1
fi

# 2) push do Google Ads (skrypt sam odmówi, jeśli dane wyglądają podejrzanie)
python3 "$REPO/scripts/gads-rmkt-feed-refresh.py" "$OUT" --apply

# 3) sprzątanie starych zrzutów (>30 dni)
find /home/host476470/.claude -maxdepth 1 -name 'rmkt-feed-*.json' -mtime +30 -delete 2>/dev/null || true

echo "=== $(date '+%F %T') — OK ==="
