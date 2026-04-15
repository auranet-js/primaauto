# Quick reference — diagnostyka i operacje

> Aktualizacja: 2026-04-15. Szybkie komendy do codziennej pracy.

## Ścieżki

```
PLUGIN=~/domains/asiaauto.pl/public_html/wp-content/plugins/asiaauto-sync
INCLUDES=$PLUGIN/includes
WP_ROOT=~/domains/asiaauto.pl/public_html
DB_NAME=host476470_wp521
DB_PREFIX=wp7j_
```

## Baza danych — przydatne queries

### Statystyki listingów
```sql
SELECT post_status, COUNT(*) FROM wp7j_posts WHERE post_type='listings' GROUP BY post_status;
```

### Statystyki zamówień
```sql
SELECT pm.meta_value AS status, COUNT(*) FROM wp7j_posts p
JOIN wp7j_postmeta pm ON pm.post_id=p.ID AND pm.meta_key='_order_status'
WHERE p.post_type='asiaauto_order' GROUP BY pm.meta_value;
```

### Ostatnie 10 listingów z CIF USD z breakdownu
```sql
SELECT p.ID, p.post_title, p.post_date,
  CAST(JSON_EXTRACT(bd.meta_value, '$.cif.amount_usd') AS DECIMAL(10,2)) AS cif_usd,
  CAST(JSON_EXTRACT(bd.meta_value, '$.total.cena_koncowa_pln') AS UNSIGNED) AS cena_pln
FROM wp7j_posts p
JOIN wp7j_postmeta bd ON bd.post_id=p.ID AND bd.meta_key='_asiaauto_price_breakdown'
WHERE p.post_type='listings' AND p.post_status='publish'
ORDER BY p.post_date DESC LIMIT 10;
```

### Listing po inner_id
```sql
SELECT p.ID, p.post_title, p.post_status FROM wp7j_posts p
JOIN wp7j_postmeta pm ON pm.post_id=p.ID AND pm.meta_key='_asiaauto_inner_id'
WHERE pm.meta_value='INNER_ID_HERE';
```

### Zamówienie — pełne meta
```sql
SELECT meta_key, meta_value FROM wp7j_postmeta
WHERE post_id=ORDER_ID AND meta_key LIKE '_order_%' ORDER BY meta_key;
```

### Listingi z rezerwacją
```sql
SELECT p.ID, p.post_title,
  MAX(CASE WHEN pm.meta_key='_asiaauto_reservation_status' THEN pm.meta_value END) AS res_status,
  MAX(CASE WHEN pm.meta_key='_asiaauto_reservation_type' THEN pm.meta_value END) AS res_type,
  MAX(CASE WHEN pm.meta_key='_asiaauto_reservation_order_id' THEN pm.meta_value END) AS order_id
FROM wp7j_posts p
JOIN wp7j_postmeta pm ON pm.post_id=p.ID
WHERE pm.meta_key IN ('_asiaauto_reservation_status','_asiaauto_reservation_type','_asiaauto_reservation_order_id')
GROUP BY p.ID;
```

### Listingi z override CIF USD
```sql
SELECT p.ID, p.post_title, pm.meta_value AS cif_usd
FROM wp7j_posts p
JOIN wp7j_postmeta pm ON pm.post_id=p.ID AND pm.meta_key='_asiaauto_cif_price_usd'
WHERE p.post_type='listings' AND p.post_status='publish' AND pm.meta_value > 0;
```

### Config cen
```sql
SELECT option_value FROM wp7j_options WHERE option_name='asiaauto_price_config';
```

### Config zamówień
```sql
SELECT option_value FROM wp7j_options WHERE option_name='asiaauto_order_config';
```

### Szablony maili
```sql
SELECT option_value FROM wp7j_options WHERE option_name='asiaauto_order_email_templates';
```

### Etykiety statusów
```sql
SELECT option_value FROM wp7j_options WHERE option_name='asiaauto_order_status_display';
```

### Config filtrów importu
```sql
SELECT option_value FROM wp7j_options WHERE option_name='asiaauto_import_config';
```

### Ostatni sync
```sql
SELECT option_value FROM wp7j_options WHERE option_name='asiaauto_last_change_id_dongchedi';
```

### Taksonomie — ilości termów
```sql
SELECT tt.taxonomy, COUNT(*) FROM wp7j_term_taxonomy tt
WHERE tt.taxonomy IN ('make','serie','body','fuel','transmission','drive','exterior-color','interior-color','ca-year','condition')
GROUP BY tt.taxonomy ORDER BY tt.taxonomy;
```

### Listingi per marka (top 10)
```sql
SELECT t.name AS marka, COUNT(*) AS cnt FROM wp7j_posts p
JOIN wp7j_term_relationships tr ON tr.object_id=p.ID
JOIN wp7j_term_taxonomy tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy='make'
JOIN wp7j_terms t ON t.term_id=tt.term_id
WHERE p.post_type='listings' AND p.post_status='publish'
GROUP BY t.name ORDER BY cnt DESC LIMIT 10;
```

### Listingi per miasto (top 10)
```sql
SELECT pm.meta_value AS miasto, COUNT(*) AS cnt FROM wp7j_posts p
JOIN wp7j_postmeta pm ON pm.post_id=p.ID AND pm.meta_key='stm_car_location'
WHERE p.post_type='listings' AND p.post_status='publish' AND pm.meta_value != ''
GROUP BY pm.meta_value ORDER BY cnt DESC LIMIT 10;
```

## WP-CLI — najczęstsze

```bash
# Status pluginu
wp asiaauto status

# Sync ręczny
wp asiaauto sync --source=dongchedi

# Przeliczenie cen
wp asiaauto recalculate-prices --dry-run
wp asiaauto recalculate-prices

# Sprawdzenie listingu
wp asiaauto inspect --inner-id=XXXXX --source=dongchedi

# Symulacja ceny
wp asiaauto price-check --cny=200000

# Diagnostyka
wp eval-file diag/check-data-quality.php
wp eval-file diag/check-translations.php
wp eval-file diag/check-chinese-models.php
```

## Logi

```bash
# Ostatnie 50 linii sync logu
tail -50 ~/domains/asiaauto.pl/public_html/wp-content/plugins/asiaauto-sync/logs/asiaauto-sync.log

# Szukanie błędów
grep -i "error\|fatal\|warning" ~/domains/asiaauto.pl/public_html/wp-content/plugins/asiaauto-sync/logs/asiaauto-sync.log | tail -20

# Debug log WP
tail -50 ~/domains/asiaauto.pl/public_html/wp-content/debug.log
```

## Cron — weryfikacja

```bash
# Lista zaplanowanych eventów AsiaAuto
wp cron event list --fields=hook,next_run_relative,recurrence | grep asiaauto

# Ręczne odpalenie sync
wp cron event run asiaauto_sync_changes

# Ręczne odpalenie cleanup
wp cron event run asiaauto_daily_cleanup
```

## Backup przed edycją

```bash
# Pojedynczy plik
~/projekty/primaauto/scripts/backup-file.sh ~/domains/asiaauto.pl/public_html/wp-content/plugins/asiaauto-sync/includes/CLASS.php

# PHP lint po edycji
~/projekty/primaauto/scripts/lint.sh
```
