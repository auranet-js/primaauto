#!/bin/bash
# Batch hub generation: 4 marki + 60 modeli przez n8n webhooki.
# Run: nohup bash tmp/batch-hub-2026-04-28.sh > tmp/n8n-batch-2026-04-28.log 2>&1 &

set -u
LOG=/home/host476470/projekty/primaauto/tmp/n8n-batch-2026-04-28.log
MAKE_WEBHOOK="https://witold140-20140.wykr.es/webhook/primaauto-make-desc"
SERIE_WEBHOOK="https://witold140-20140.wykr.es/webhook/primaauto-serie-desc"
WP_PATH=/home/host476470/domains/primaauto.com.pl/public_html

ts() { date "+%Y-%m-%d %H:%M:%S"; }
log() { echo "[$(ts)] $*"; }

log "=== Batch start ==="

# Pobierz aktualne listy
MAKES=$(wp --path=$WP_PATH asiaauto diag run make-without-wiki --format=json | python3 -c "import sys,json; d=json.loads(sys.stdin.read()); print('\n'.join(i['meta'].get('slug','') for i in d.get('issues',[]) if i['meta'].get('slug')))")
SERIES=$(wp --path=$WP_PATH asiaauto diag run serie-without-wiki --format=json | python3 -c "
import sys,json
d=json.loads(sys.stdin.read())
for i in d.get('issues',[]):
    m=i.get('meta',{})
    s=m.get('serie_slug') or m.get('slug') or ''
    ms=m.get('make_slug') or m.get('parent_make_slug') or ''
    if s and ms:
        print(f'{ms}|{s}')
")

n_makes=$(echo "$MAKES" | grep -c .)
n_series=$(echo "$SERIES" | grep -c .)
log "Marki do generacji: $n_makes"
log "Modele do generacji: $n_series"

# === 4 marki, parallel 2 ===
log "--- MAKE batch ---"
echo "$MAKES" | xargs -I{} -P2 bash -c '
  slug="{}"
  if [ -z "$slug" ]; then exit 0; fi
  start=$(date +%s)
  http_code=$(curl -s -m 240 -o /tmp/n8n-make-$slug.out -w "%{http_code}" -X POST "'"$MAKE_WEBHOOK"'" \
    -H "Content-Type: application/json" \
    -d "{\"make_slug\":\"$slug\"}")
  dur=$(( $(date +%s) - start ))
  echo "[$(date "+%H:%M:%S")] make=$slug http=$http_code time=${dur}s"
'

# === 60 modeli, parallel 3 ===
log "--- SERIE batch ---"
echo "$SERIES" | xargs -I{} -P3 bash -c '
  pair="{}"
  ms="${pair%|*}"
  ss="${pair#*|}"
  if [ -z "$ms" ] || [ -z "$ss" ]; then exit 0; fi
  start=$(date +%s)
  http_code=$(curl -s -m 240 -o /tmp/n8n-serie-$ss.out -w "%{http_code}" -X POST "'"$SERIE_WEBHOOK"'" \
    -H "Content-Type: application/json" \
    -d "{\"make_slug\":\"$ms\",\"serie_slug\":\"$ss\"}")
  dur=$(( $(date +%s) - start ))
  echo "[$(date "+%H:%M:%S")] serie=$ms/$ss http=$http_code time=${dur}s"
'

log "=== Batch complete ==="
log "Coverage check:"
wp --path=$WP_PATH asiaauto diag run make-without-wiki --format=count
wp --path=$WP_PATH asiaauto diag run serie-without-wiki --format=count
