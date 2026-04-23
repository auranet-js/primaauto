#!/usr/bin/env bash
# Batch generator treści hubów (wszystkie marki + modele z count>0).
# Uruchomienie: ./batch-hub-content.sh
# Log: tmp/batch-hub-<timestamp>.log
# Każde wywołanie = 1 generacja. Spacing 5s między wywołaniami.

set -u

STAMP=$(date +%Y%m%d-%H%M%S)
LOG=/home/host476470/projekty/primaauto/tmp/batch-hub-${STAMP}.log
WP_PATH=/home/host476470/domains/primaauto.com.pl/public_html
WEBHOOK_MAKE="https://witold140-20140.wykr.es/webhook/primaauto-make-desc"
WEBHOOK_SERIE="https://witold140-20140.wykr.es/webhook/primaauto-serie-desc"

log() { echo "[$(date +%H:%M:%S)] $*" | tee -a "$LOG"; }

log "=== START batch $STAMP ==="

# Lista marek (count>0)
MAKES=$(wp --path="$WP_PATH" term list make --fields=slug --hide_empty=true --format=csv 2>/dev/null | tail -n+2)
MAKES_COUNT=$(echo "$MAKES" | grep -c .)
log "Marek do przetworzenia: $MAKES_COUNT"

# ───── MARKI ─────────────────────────────────────────────────────
log ""
log "=== MARKI ($MAKES_COUNT) ==="
i=0
ok_make=0
fail_make=0
for slug in $MAKES; do
    i=$((i+1))
    start=$(date +%s)
    resp=$(curl -s -X POST "$WEBHOOK_MAKE" -H "Content-Type: application/json" --data "{\"make_slug\":\"$slug\"}" --max-time 240 2>&1)
    elapsed=$(( $(date +%s) - start ))
    if echo "$resp" | grep -q '"ok":true'; then
        lint=$(echo "$resp" | python3 -c "import json,sys; d=json.load(sys.stdin); w=d.get('lint_warnings') or []; print(','.join(w) if w else 'clean')" 2>/dev/null || echo "?")
        log "[$i/$MAKES_COUNT] ✓ make=$slug (${elapsed}s, $lint)"
        ok_make=$((ok_make+1))
    else
        log "[$i/$MAKES_COUNT] ✗ make=$slug (${elapsed}s): $(echo "$resp" | head -c 250)"
        fail_make=$((fail_make+1))
    fi
    sleep 5
done
log "MARKI DONE: $ok_make OK, $fail_make FAIL"

# ───── MODELE (serie) ───────────────────────────────────────────
log ""
log "=== MODELE (serie) ==="
ok_serie=0
fail_serie=0
total_serie=0

# Policz total serie do przetworzenia
for make_slug in $MAKES; do
    make_id=$(wp --path="$WP_PATH" term get make "$make_slug" --field=term_id 2>/dev/null || echo "")
    [ -z "$make_id" ] && continue
    cnt=$(wp --path="$WP_PATH" term list serie --fields=slug --hide_empty=true --parent=$make_id --format=csv 2>/dev/null | tail -n+2 | grep -c .)
    total_serie=$((total_serie + cnt))
done
log "Modeli do przetworzenia: $total_serie"
log ""

j=0
for make_slug in $MAKES; do
    make_id=$(wp --path="$WP_PATH" term get make "$make_slug" --field=term_id 2>/dev/null || echo "")
    [ -z "$make_id" ] && continue
    SERIES=$(wp --path="$WP_PATH" term list serie --fields=slug --hide_empty=true --parent=$make_id --format=csv 2>/dev/null | tail -n+2)
    for serie_slug in $SERIES; do
        j=$((j+1))
        start=$(date +%s)
        resp=$(curl -s -X POST "$WEBHOOK_SERIE" -H "Content-Type: application/json" --data "{\"serie_slug\":\"$serie_slug\",\"make_slug\":\"$make_slug\"}" --max-time 240 2>&1)
        elapsed=$(( $(date +%s) - start ))
        if echo "$resp" | grep -q '"ok":true'; then
            log "[$j/$total_serie] ✓ $make_slug/$serie_slug (${elapsed}s)"
            ok_serie=$((ok_serie+1))
        else
            log "[$j/$total_serie] ✗ $make_slug/$serie_slug (${elapsed}s): $(echo "$resp" | head -c 250)"
            fail_serie=$((fail_serie+1))
        fi
        sleep 5
    done
done
log "MODELE DONE: $ok_serie OK, $fail_serie FAIL"

log ""
log "=== KONIEC $(date +%H:%M:%S) ==="
log "Podsumowanie: marki $ok_make/$MAKES_COUNT OK, modele $ok_serie/$total_serie OK"
log "Fail total: $((fail_make + fail_serie))"
