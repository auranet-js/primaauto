#!/usr/bin/env bash
# Równoległy batch (3 concurrent). Wywołuje przez xargs -P3.
set -u

STAMP=$(date +%Y%m%d-%H%M%S)
LOG=/home/host476470/projekty/primaauto/tmp/batch-hub-parallel-${STAMP}.log
WP_PATH=/home/host476470/domains/primaauto.com.pl/public_html
WEBHOOK_MAKE="https://witold140-20140.wykr.es/webhook/primaauto-make-desc"
WEBHOOK_SERIE="https://witold140-20140.wykr.es/webhook/primaauto-serie-desc"
CONCURRENCY=3

log() { echo "[$(date +%H:%M:%S)] $*" | tee -a "$LOG"; }

log "=== START parallel batch $STAMP (P=$CONCURRENCY) ==="

worker() {
    local line="$1"
    IFS='|' read -r type a b <<< "$line"
    local url body desc
    if [ "$type" = "make" ]; then
        url="$WEBHOOK_MAKE"
        body="{\"make_slug\":\"$a\"}"
        desc="make=$a"
    else
        url="$WEBHOOK_SERIE"
        body="{\"serie_slug\":\"$b\",\"make_slug\":\"$a\"}"
        desc="$a/$b"
    fi
    local start=$(date +%s)
    local resp
    resp=$(curl -s -X POST "$url" -H "Content-Type: application/json" --data "$body" --max-time 240 2>&1)
    local elapsed=$(( $(date +%s) - start ))
    if echo "$resp" | grep -q '"ok":true'; then
        echo "[$(date +%H:%M:%S)] ✓ $desc (${elapsed}s)" >> "$LOG"
    else
        echo "[$(date +%H:%M:%S)] ✗ $desc (${elapsed}s): $(echo "$resp" | head -c 200)" >> "$LOG"
    fi
}
export -f worker
export WEBHOOK_MAKE WEBHOOK_SERIE LOG

QUEUE_FILE=$(mktemp)

MAKES=$(wp --path="$WP_PATH" term list make --fields=slug --hide_empty=true --format=csv 2>/dev/null | tail -n+2)
MAKES_COUNT=$(echo "$MAKES" | grep -c .)
log "Marek: $MAKES_COUNT"

for slug in $MAKES; do
    echo "make|$slug" >> "$QUEUE_FILE"
done

for make_slug in $MAKES; do
    make_id=$(wp --path="$WP_PATH" term get make "$make_slug" --field=term_id 2>/dev/null || echo "")
    [ -z "$make_id" ] && continue
    SERIES=$(wp --path="$WP_PATH" term list serie --fields=slug --hide_empty=true --parent=$make_id --format=csv 2>/dev/null | tail -n+2)
    for serie_slug in $SERIES; do
        echo "serie|$make_slug|$serie_slug" >> "$QUEUE_FILE"
    done
done

TOTAL=$(wc -l < "$QUEUE_FILE")
log "Total wywołań: $TOTAL"
log "Estymowany czas: ~$(( TOTAL * 60 / CONCURRENCY / 60 )) min"
log ""

START_TS=$(date +%s)
cat "$QUEUE_FILE" | xargs -P $CONCURRENCY -I {} bash -c 'worker "$@"' _ {}
END_TS=$(date +%s)
ELAPSED=$(( END_TS - START_TS ))

ok_count=$(grep -c '^\[[^]]*\] ✓' "$LOG" 2>/dev/null || echo 0)
fail_count=$(grep -c '^\[[^]]*\] ✗' "$LOG" 2>/dev/null || echo 0)

log ""
log "=== KONIEC $(date +%H:%M:%S) ==="
log "Czas łączny: $(( ELAPSED / 60 ))m $(( ELAPSED % 60 ))s"
log "Wyniki: ✓ $ok_count / $TOTAL, ✗ $fail_count"

rm -f "$QUEUE_FILE"
