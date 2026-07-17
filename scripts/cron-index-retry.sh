#!/bin/bash
# =====================================================================
# TYMCZASOWY cron reindeksacji ofert (status ok_retry) — 100/dobę.
# Działa TYLKO póki feed Dongchedi/Che168 martwy. Auto-wygasa.
# Zabezpieczenia: kill-switch (EXPIRE), lock, feed-guard, tylko ad-hoc
# (bez --yes → wrapper zatrzyma na 100, nie rusza rezerwy).
# Monitoring: log + mail-alert do Janka przy guardzie/wygaśnięciu.
# =====================================================================
set -uo pipefail
WP="/home/host476470/domains/primaauto.com.pl/public_html"
LOG="/home/host476470/logs/primaauto-index-retry.log"
SUBMIT="/home/host476470/bin/index-submit"
MAILER="/home/host476470/bin/send-to-jan"
URLS="/tmp/prima-index-retry-urls.txt"
LOCK="/tmp/prima-index-retry.lock"
EXPIRE="2026-08-17"          # po tej dacie cron sam się wyłącza (tymczasowość)
FEED_THRESHOLD=5             # >tyle nowych ofert/24h = feed ożył → stop
DRY=""; [ "${1:-}" = "--dry-run" ] && DRY="--dry-run"
TS() { date '+%F %T'; }
log() { echo "$(TS) $*" >> "$LOG"; }
alert() {  # $1=treść, $2=temat — loguje ORAZ raz/dobę mailuje do Janka
  log "$1"
  local flag="/tmp/prima-index-retry-alert-$(date +%F).flag"
  if [ ! -f "$flag" ] && [ -z "$DRY" ]; then
    "$MAILER" -s "[primaauto cron] $2" -b "$1" >/dev/null 2>&1 && touch "$flag"
  fi
}

# --- lock: nie nakładaj biegów ---
exec 9>"$LOCK" || exit 1
flock -n 9 || { log "SKIP: poprzedni bieg trwa"; exit 0; }

# --- kill-switch: wygaśnięcie ---
if [[ "$(date '+%F')" > "$EXPIRE" ]]; then
  alert "Cron reindeksacji WYGASŁ (minął $EXPIRE) i nie działa. Po rewizji: usuń z crontab lub przesuń EXPIRE." "WYGASŁ — wymaga decyzji"
  exit 0
fi

cd "$WP" || { log "ERR: brak katalogu WP"; exit 1; }

# --- feed-guard: czy feed ożył ---
NEW=$(wp db query "SELECT COUNT(*) FROM wp7j_posts WHERE post_type='listings' AND post_status='publish' AND post_date > (NOW() - INTERVAL 24 HOUR)" --skip-column-names 2>/dev/null)
if [ "${NEW:-0}" -gt "$FEED_THRESHOLD" ]; then
  alert "Feed OŻYŁ ($NEW nowych ofert/24h > $FEED_THRESHOLD). Cron reindeksacji WSTRZYMANY, żeby zostawić pulę Indexing dla auto-indexingu pluginu. Zrewiduj strategię lub wyłącz crona." "feed ożył — cron wstrzymany"
  exit 0
fi

# --- zaległość + wybór 100 najstarszych ok_retry ---
REM=$(wp db query "SELECT COUNT(*) FROM wp7j_posts p JOIN wp7j_postmeta pm ON pm.post_id=p.ID AND pm.meta_key='_asiaauto_indexing_status' AND pm.meta_value='ok_retry' WHERE p.post_type='listings' AND p.post_status='publish'" --skip-column-names 2>/dev/null)
if [ "${REM:-0}" -eq 0 ]; then
  alert "Zaległość ok_retry NADROBIONA (0 ofert). Cron zrobił swoje — usuń go z crontab." "zaległość nadrobiona"
  exit 0
fi
wp db query "SELECT CONCAT('https://primaauto.com.pl/oferta/', p.post_name, '/') FROM wp7j_posts p JOIN wp7j_postmeta pm ON pm.post_id=p.ID AND pm.meta_key='_asiaauto_indexing_status' AND pm.meta_value='ok_retry' WHERE p.post_type='listings' AND p.post_status='publish' ORDER BY p.post_modified ASC LIMIT 100" --skip-column-names 2>/dev/null > "$URLS"
N=$(wc -l < "$URLS")

log "START: feed martwy ($NEW/24h), zaległość ok_retry=$REM, zgłaszam $N (tylko ad-hoc, bez rezerwy)"
"$SUBMIT" --project primaauto --type URL_UPDATED --urls-file "$URLS" $DRY >> "$LOG" 2>&1
log "KONIEC (szacowana zaległość po dziś ~$((REM - N)))"
