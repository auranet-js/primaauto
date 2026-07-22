#!/bin/bash
# Cron dzienny pipeline'u newsowego działu wiedzy (T-214 F3).
# Wzorzec guardów jak scripts/cron-index-retry.sh: flock + kill-switch + log + mail przy błędzie.
# Rejestracja w crontab (F3): 45 6 * * * /bin/bash -lc '/home/host476470/projekty/primaauto/scripts/kb/kb-news-daily.sh'

set -u
KB_DIR="/home/host476470/projekty/primaauto/scripts/kb"
LOG="$HOME/logs/primaauto-kb-news.log"
LOCK="/tmp/primaauto-kb-news.lock"

mkdir -p "$HOME/logs"

# Kill-switch: touch scripts/kb/state/DISABLED zatrzymuje pipeline
if [ -f "$KB_DIR/state/DISABLED" ]; then
    echo "[$(date '+%F %T')] kill-switch aktywny — skip" >> "$LOG"
    exit 0
fi

exec 9>"$LOCK"
if ! flock -n 9; then
    echo "[$(date '+%F %T')] poprzedni bieg trwa — skip" >> "$LOG"
    exit 0
fi

echo "[$(date '+%F %T')] start" >> "$LOG"
if python3 "$KB_DIR/news_daily.py" --limit 2 >> "$LOG" 2>&1; then
    echo "[$(date '+%F %T')] OK" >> "$LOG"
    # Kontrola jakości po publikacji (audyt 22.07): walidator sam sprawdza świeże wpisy
    # i pisze do Janka TYLKO gdy coś odstaje — bieg bez uwag nie generuje maila.
    python3 "$KB_DIR/news_qa.py" --limit 6 --mail >> "$LOG" 2>&1 \
        || echo "[$(date '+%F %T')] QA nie wykonało się (nie blokuje)" >> "$LOG"
else
    echo "[$(date '+%F %T')] BŁĄD (exit $?)" >> "$LOG"
    ~/bin/send-to-jan -s "[primaauto] kb-news-daily: BŁĄD biegu" \
        -b "Pipeline newsowy zakończył się błędem. Ostatnie linie logu:

$(tail -30 "$LOG")" 2>/dev/null
fi
