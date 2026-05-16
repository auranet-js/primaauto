# Ochrona ręcznie zarządzanych listings + fix duplikacji rezerwacji

**Data:** 2026-05-16
**Autor:** Janek (Auranet)
**Klient:** Ruslan Prima (PRIMA-AUTO)
**Status:** Plan zatwierdzony, do wdrożenia w nowym wątku
**Pliki dotknięte:** `asiaauto-sync/includes/class-asiaauto-sync.php`, `asiaauto-sync/includes/class-asiaauto-listing-editor.php`, ewentualnie nowy `class-asiaauto-admin-listings-views.php`

---

## 1. Kontekst (skąd ten plan)

Sesja diagnostyczna 2026-05-16. Klient zgłosił dwa problemy:

1. **„Klient ręcznie dodaje ogłoszenia z Dongchedi, sync mu je wycofuje"** — wykryto w DB 71 listings z flagą `_asiaauto_manual_import=1`, z czego 17 jest w stanie draft/trash, 12 z nich z `_asiaauto_removed_at` ustawionym przez sync (`removal_reason='sold'`).

2. **„Zduplikowane ogłoszenie zablokowało sprzedaż, twierdzi że zamówienie też się zduplikowało"** — przykład: order 330128 / kopia 330122 (BYD Leopard 7). Diagnoza wykryła że `AsiaAuto_Listing_Editor::handleDuplicate()` kopiuje meta `_asiaauto_reservation_status` i `_asiaauto_reservation_order_id` (brak ich w `DUP_BLOCKED_META` linie 80-103). Dowody w DB:
   - 303534 (oryginał 2026-05-06) + 314155 (kopia 2026-05-10) — **ten sam** `res_order_id=303657` (Denza Z9 GT DM-i)
   - 317106 (oryginał 2026-05-11) + 324822 (kopia 2026-05-13) — **ten sam** `res_order_id=317400` (BYD Leopard 7)

---

## 2. Trzy wątki do wdrożenia

### W1 — Sync guard: pomijaj ręcznie zarządzane listings (priorytet P2)

**Plik:** `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-sync.php`

**Bug:** linie 134-163 — `updateListing()` przy `changed` event i `markRemoved()` przy `removed` event sprawdzają tylko `_asiaauto_reservation_status`, ignorują flagi `_asiaauto_manual_import` i `_asiaauto_manual_entry`.

**Fix:** dodać prywatną metodę:

```php
private function isManuallyManaged(int $post_id): bool {
    if ((string) get_post_meta($post_id, '_asiaauto_manual_import', true) === '1') {
        return true;
    }
    if ((string) get_post_meta($post_id, '_asiaauto_manual_entry', true) === '1') {
        return true;
    }
    return false;
}
```

Wstrzelić ją **przed** `updateListing()` (linia 144) i **przed** `markRemoved()` (linia 160). Gdy zwróci `true` → log `info("Sync skip: listing #{$post_id} ({$inner_id}) is manually managed, skipping {$type}")` + `$total_skipped++`.

**Zasięg ochrony (81 unique aktywnych listings):**
- `_asiaauto_manual_import=1` → import z UI „Dodaj z Dongchedi" (71 sztuk)
- `_asiaauto_manual_entry=1` → klient edytował metabox „Dane pojazdu" choć raz (75 sztuk)
- Duplikaty bez `inner_id` i Add New bez `inner_id` — chronione **implicite** (sync nie znajduje przez `findByInnerId`)

**Konkretne listings które wzbudziły potrzebę:**
- 249638 (BYD Yangwang U7) — Ruslan edytował metabox 2026-05-09 11:14
- 306890 (Denza Z9 DM-i) — Ruslan edytował metabox 2026-05-16 08:59 (dziś)

**Test po deployu:**
1. Wymuś sync dla `dongchedi` (lub poczekaj na cron)
2. Sprawdź log `wp-content/plugins/asiaauto-sync/logs/asiaauto-sync.log` — szukaj `Sync skip: listing #X (inner_id: Y) is manually managed`
3. Otwórz dowolny listing z `manual_import=1` lub `manual_entry=1` w admin → status nie zmienia się po sync

---

### W2 — Fix duplikacji rezerwacji (priorytet P1 — krytyczne)

**Plik:** `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-listing-editor.php`

**Bug:** stała `DUP_BLOCKED_META` (linie 80-103) NIE zawiera meta rezerwacji i removed-tracking. `handleDuplicate()` linia 711-721 kopiuje wszystkie meta poza blockliście → kopia dziedziczy aktywną rezerwację oryginału.

**Fix:** dodać do `DUP_BLOCKED_META` (alfabetycznie, w odpowiednim miejscu):

```php
'_asiaauto_api_removed',
'_asiaauto_manual_import',          // flagi z UI Dodaj-z-Dongchedi — kopia to inny listing
'_asiaauto_manual_import_at',
'_asiaauto_manual_import_by',
'_asiaauto_removal_reason',
'_asiaauto_removed_at',
'_asiaauto_reservation_order_id',
'_asiaauto_reservation_status',
```

**Uzasadnienie:**
- `_asiaauto_reservation_*` → kopia powinna być wolna do nowej sprzedaży, nie dziedziczyć stanu rezerwacji oryginału
- `_asiaauto_api_removed`, `_asiaauto_removed_at`, `_asiaauto_removal_reason` → kopia to nowy byt, nie ma historii sync-removal oryginału
- `_asiaauto_manual_import*` → kopia NIE była importowana z UI Dodaj-z-Dongchedi, flaga przekłamywała statystyki

**Cleanup istniejących par — manualny (Ruslan potwierdza listę przed):**

Dwie pary wykryte (oryginał, kopia, ten sam `res_order_id`):
- 303534 + 314155 → `res_order=303657` (Denza Z9 GT DM-i)
- 317106 + 324822 → `res_order=317400` (BYD Leopard 7)

Plus potencjalnie 251826 `[Kopia] Galaxy Yizhen L380` (różny `res_order_id`, ale `res_status=in_transit` skopiowany).

Pełna lista do potwierdzenia — pokazać Ruslanowi przed cleanupem. Query:

```sql
SELECT
  pm1.meta_value AS res_order_id,
  GROUP_CONCAT(p.ID ORDER BY p.post_date) AS post_ids,
  GROUP_CONCAT(p.post_title ORDER BY p.post_date SEPARATOR '|') AS titles,
  GROUP_CONCAT(p.post_date ORDER BY p.post_date SEPARATOR '|') AS dates,
  COUNT(p.ID) AS dup_count
FROM wp7j_posts p
INNER JOIN wp7j_postmeta pm1 ON p.ID=pm1.post_id
  AND pm1.meta_key='_asiaauto_reservation_order_id'
  AND pm1.meta_value<>''
WHERE p.post_type='listings' AND p.post_status NOT IN ('trash','auto-draft')
GROUP BY pm1.meta_value
HAVING dup_count > 1;
```

Mechanizm cleanup per para: z młodszej kopii usunąć:
```sql
DELETE FROM wp7j_postmeta
WHERE post_id IN (<younger_ids>)
  AND meta_key IN (
    '_asiaauto_reservation_status',
    '_asiaauto_reservation_order_id',
    '_asiaauto_api_removed',
    '_asiaauto_removed_at',
    '_asiaauto_removal_reason'
  );
```

Przed cleanupem: `mysqldump wp7j_postmeta` z filtrem na ID-y kopii do `~/backups/primaauto/2026-05-16-pre-dup-cleanup/`.

**Test po deployu:**
1. Wybierz listing z `_asiaauto_reservation_status='in_transit'`
2. Klik „Duplikuj"
3. Sprawdź `wp post meta list <new_id>` → BRAK `_asiaauto_reservation_status` i `_asiaauto_reservation_order_id`
4. Spróbuj utworzyć zamówienie na kopię → powinno przejść bez blokady

---

### W3 — Filtr „Ręczny import" w admin views (priorytet P3 — nice-to-have)

**Plik:** propozycja `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-admin-listings-views.php` (nowy) lub dodanie do `class-asiaauto-listing-editor.php`.

**Funkcjonalność:**
- W `edit.php?post_type=listings` dodać view link **„Ręczny import (X)"** obok natywnych „Wszystkie | Moje | Opublikowane | Szkice | Kosz".
- Filtruje po `_asiaauto_manual_import=1` (TYLKO z UI „Dodaj z Dongchedi", NIE łączyć z `manual_entry`).
- **Bez ograniczenia po autorze** — admin (js=1) i Ruslan (primaauto=7) widzą tę samą listę.
- Duplikaty i Add New zostają w natywnym „Moje" (bo Ruslan jest autorem) — tam pojawiają się naturalnie.

**Implementacja (zarys):**

```php
class AsiaAuto_Admin_Listings_Views {
    public function __construct() {
        add_filter('views_edit-listings', [$this, 'addManualImportView']);
        add_action('pre_get_posts',       [$this, 'filterManualImportQuery']);
    }

    public function addManualImportView(array $views): array {
        $count = $this->countManualImports();
        $url = admin_url('edit.php?post_type=listings&asiaauto_view=manual_import');
        $current = (isset($_GET['asiaauto_view']) && $_GET['asiaauto_view'] === 'manual_import') ? ' class="current"' : '';
        $views['asiaauto_manual_import'] = sprintf(
            '<a href="%s"%s>Ręczny import <span class="count">(%d)</span></a>',
            esc_url($url),
            $current,
            $count
        );
        return $views;
    }

    public function filterManualImportQuery(WP_Query $q): void {
        if (!is_admin() || !$q->is_main_query()) return;
        if (($q->get('post_type') ?? '') !== 'listings') return;
        if (!isset($_GET['asiaauto_view']) || $_GET['asiaauto_view'] !== 'manual_import') return;

        $q->set('meta_query', [[
            'key'     => '_asiaauto_manual_import',
            'value'   => '1',
            'compare' => '=',
        ]]);
    }

    private function countManualImports(): int {
        global $wpdb;
        $cnt = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID=pm.post_id
              AND pm.meta_key='_asiaauto_manual_import' AND pm.meta_value='1'
            WHERE p.post_type='listings' AND p.post_status NOT IN ('trash','auto-draft')
        ");
        return (int) $cnt;
    }
}
```

Rejestracja w głównym pliku pluginu `asiaauto-sync.php`:
```php
if (is_admin()) {
    require_once __DIR__ . '/includes/class-asiaauto-admin-listings-views.php';
    new AsiaAuto_Admin_Listings_Views();
}
```

**Test po deployu:**
1. Otwórz `https://primaauto.com.pl/wp-admin/edit.php?post_type=listings`
2. Powinno być widać link „Ręczny import (60)" (lub bieżąca liczba)
3. Klik → lista zawęża się do listings z `manual_import=1`
4. Liczba zgadza się z `SELECT COUNT(*) FROM wp7j_postmeta WHERE meta_key='_asiaauto_manual_import' AND meta_value='1'`

---

## 3. Restore 8 listings wybranych przez Ruslana

Lista do przywrócenia (klient wybrał świadomie 2026-05-16):

| ID | Status | Tytuł | inner_id | Attachmenty | Uwaga |
|---|---|---|---|---|---|
| 317231 | trash | Mengshi M-Hero 917 2023 EREV Edition | 23773434 | 15 | OK |
| 310740 | trash | Jetour T2 C-DM 2024 1.5TD DHT 208km 2WD Edition | 23730167 | 7 | OK |
| 310457 | draft | BYD Yangwang U8 2023 Luxury Edition | 23613286 | **0** | **BRAK ZDJĘĆ** — pobrać z Dongchedi lub skopiować z 310458 |
| 299535 | trash | iCAR Super V23 2026 V23S 501 4WD Peak Performance | 23505012 | 12 | OK |
| 265775 | trash | Wuling Yangguang 2025 400km Edition Comfort Version 6 Seater | 22642344 | 10 | OK |
| 260513 | trash | Zeekr 9X 2025 Ultra 55kWh | 23117200 | 15 | OK |
| 255171 | trash | Lynk & Co 900 2025 2.0T Ultra | 22963733 | 15 | OK |
| 255154 | trash | Lynk & Co 900 2025 2.0T Ultra | 22820393 | 15 | OK (duplikat tytułu z 255171, inny inner_id) |

**Mechanizm restore (per ID):**

```bash
# 1. Backup meta przed
mkdir -p ~/backups/primaauto/2026-05-16-restore-listings
cd /home/host476470/domains/primaauto.com.pl/public_html
/usr/bin/php /usr/local/bin/wp db query "
  SELECT * FROM wp7j_postmeta WHERE post_id IN (317231,310740,310457,299535,265775,260513,255171,255154)
" --format=csv > ~/backups/primaauto/2026-05-16-restore-listings/postmeta-before.csv

# 2. Restore status
for ID in 317231 310740 310457 299535 265775 260513 255171 255154; do
  STATUS=$(/usr/bin/php /usr/local/bin/wp post get $ID --field=post_status)
  if [ "$STATUS" = "trash" ]; then
    /usr/bin/php /usr/local/bin/wp post untrash $ID
  fi
  /usr/bin/php /usr/local/bin/wp post update $ID --post_status=publish
done

# 3. Wyczyść flagi removed
/usr/bin/php /usr/local/bin/wp db query "
  DELETE FROM wp7j_postmeta
  WHERE post_id IN (317231,310740,310457,299535,265775,260513,255171,255154)
    AND meta_key IN ('_asiaauto_removed_at','_asiaauto_removal_reason','_asiaauto_api_removed')
"

# 4. Upewnij się że mają flagę manual_import (żeby W1 ich chronił po wdrożeniu)
for ID in 317231 310740 310457 299535 265775 260513 255171 255154; do
  /usr/bin/php /usr/local/bin/wp post meta update $ID _asiaauto_manual_import 1
done

# 5. Weryfikacja zdjęć (310457 wymaga akcji manualnej)
for ID in 317231 310740 310457 299535 265775 260513 255171 255154; do
  GAL=$(/usr/bin/php /usr/local/bin/wp post meta get $ID gallery)
  echo "$ID: gallery=$(echo $GAL | head -c 100)"
done
```

**KOLEJNOŚĆ DEPLOYA: ten restore robimy DOPIERO PO W1**, żeby świeżo przywrócone listings od razu były chronione przed sync.

---

## 4. Rekomendowana kolejność deploya

1. **W2** (P1) — fix duplikacji rezerwacji — najpierw, bo blokuje sprzedaż TERAZ
   - Backup `class-asiaauto-listing-editor.php.bak-2026-05-16-pre-w2`
   - Patch `DUP_BLOCKED_META` (8 nowych kluczy)
   - `php -l` → deploy
   - Smoke test: duplikat listingu z rezerwacją → kopia bez `res_status`
   - Bump `ASIAAUTO_VERSION` do `0.32.46`
   - Commit `[contract:][order:] v0.32.46 — fix DUP_BLOCKED_META reservation/removed metas`
   - Cleanup par (po liście potwierdzonej przez Ruslana, z backupem mysqldump)

2. **W1** (P2) — sync guard — drugi
   - Backup `class-asiaauto-sync.php.bak-2026-05-16-pre-w1`
   - Dodać `isManuallyManaged()` + 2 guard'y
   - `php -l` → deploy
   - Smoke test: wymuś sync, sprawdź log dla `Sync skip: ... manually managed`
   - Bump do `0.32.47`
   - Commit `[sync:] v0.32.47 — guard manual_import/manual_entry against sync changed/removed`

3. **Restore 8 listings** — bezpośrednio po W1, jak opisano w sekcji 3

4. **W3** (P3) — filtr admin views — ostatni
   - Nowy plik `class-asiaauto-admin-listings-views.php`
   - Include w `asiaauto-sync.php`
   - `php -l` → deploy
   - Smoke test: link „Ręczny import (X)" widoczny, klik filtruje
   - Bump do `0.32.48`
   - Commit `[admin:] v0.32.48 — view filter „Ręczny import" in edit.php`

**Łącznie:** 3 osobne commity, 3 wersje, każdy z własnym smoke testem. Backup w `~/backups/primaauto/2026-05-16/` per krok.

---

## 5. Otwarte decyzje od Ruslana

1. **Cleanup par z dziedziczoną rezerwacją** — auto (zdejmij res_* z młodszych kopii) czy ręczne potwierdzenie listy?
2. **Inne listings wycofane przez sync** (poza wybranymi 8) — zostawiamy w trash? Wymienione w sekcji „Diagnostyka" raportu DB:
   - 303674 IM Motors IM LS9
   - 279761 Voyah Zhiyin
   - 278690 BYD Song Pro DM-i
   - 310458 BYD Yangwang U8 (z 9 zdjęciami — alternatywa dla 310457 bez zdjęć?)
3. **310457 BYD Yangwang U8** (z listy do restore, ale BEZ zdjęć) — pobieramy ze sklepu Dongchedi (URL-e mogły wygasnąć — risk W2 ghost) czy kopiujemy zdjęcia z 310458?

---

## 6. Stan obecny — fakty z DB (2026-05-16)

**Wszystkie aktywne listings:** 4221 (post_status NOT IN trash, auto-draft)

**Rozkład flag manualnych:**

| Scenariusz | inner_id | manual_import | manual_entry | Liczba |
|---|---|---|---|---|
| Sync z crona, nietknięte | TAK | NULL | NULL | ~4138 |
| Sync + edycja metabox (bez duplikacji) | TAK | NULL | 1 | **2** |
| UI „Dodaj z Dongchedi" + edycja metabox | TAK | 1 | 1 | 33 |
| UI „Dodaj z Dongchedi" bez edycji | TAK | 1 | NULL | 10 |
| Czysty duplikat / Add New (po edycji) | NULL | NULL | 1 | 38 |

**Listings z `_asiaauto_manual_import=1`:** 71 (publish 54, draft 8, trash 9)
**Listings z `_asiaauto_manual_entry=1`:** 75
**Listings z którąkolwiek flagą:** 81 unique aktywnych (publish/draft)

**Pary z odziedziczoną rezerwacją (ten sam `res_order_id` na 2 listings):**
- 303534 + 314155 → 303657 (Denza Z9 GT DM-i)
- 317106 + 324822 → 317400 (BYD Leopard 7)

---

## 7. Kontekst techniczny

**Mechanizm flag (kod):**

- `_asiaauto_manual_import=1` → ustawiana w `AsiaAuto_Admin_Manual_Import::ajaxImport()` linie 545-547 po pomyślnym imporcie z UI „Dodaj z Dongchedi"
- `_asiaauto_manual_entry=1` → ustawiana w `AsiaAuto_Listing_Editor::handleSave()` linia 583 przy PIERWSZYM zapisie listingu przez metabox „Dane pojazdu" (hook `save_post_listings` z prawidłowym nonce `asiaauto_listing_editor_nonce`)
- `wp_insert_post()` z crona NIE wywołuje `handleSave()` skutecznie (nonce check fails → early return) → sync nie ustawia tej flagi

**Mechanizm duplikacji (kod):**

- `AsiaAuto_Listing_Editor::handleDuplicate()` linia 682 — odpalana przez row action „Duplikuj" w liście Ogłoszeń
- `wp_insert_post()` z `post_author=get_current_user_id()` + status draft + tytuł `[Kopia] ...`
- Kopiowanie meta: `foreach ($all_meta as $key => $values)` (linia 712) z filtrem `!in_array($key, self::DUP_BLOCKED_META, true)`
- Kopiowanie taksonomii: wszystkie (linia 723-729)

**Mechanizm sync (kod):**

- `AsiaAuto_Sync::run()` linie 50-208 — cron job dla `dongchedi` i `che168`
- Pętla `foreach ($results as $change)` (linia 110) → switch po `change_type`:
  - `'added'` → `importWithFullData()` → `importer->importListing()`
  - `'changed'` → `importer->updateListing()` (linia 144) — **TU bug W1**
  - `'removed'` → `rotation->markRemoved()` (linia 160) — **TU bug W1**
- Sprawdzenie reservation tylko w `changed` (linia 138-142) — `manual_*` flagi NIE są sprawdzane

---

## 8. Komunikacja z klientem

**Stan rozmów na 2026-05-16:**
- Ruslan zna problem z sync wycofującym ręcznie importowane (zgłaszał wczoraj)
- Ruslan zna problem z duplikatem-rezerwacji (zgłaszał dziś 14:30)
- Ruslan wybrał 8 listings do restore (powyższa lista)
- Ruslan potwierdził wariant C dla W3 (filtr „Ręczny import" obok „Moje")
- Ruslan oczekuje wdrożenia „dziś/jutro" po osobne kroki

**Otwarta wiadomość do Ruslana (po wdrożeniu):**
- Potwierdzenie że W1+W2 działa
- Lista par do potwierdzenia cleanupu
- Komunikat że 8 listings przywrócone do publish (z noticem o 310457 bez zdjęć — opcja A/B)

---

## 9. Stan wykonania — wpis sesji 2026-05-16 wieczór

**Wszystkie 3 wątki wdrożone w kolejności W2 → W1 → restore → W3.**

### W2 — v0.32.46 (commit 159fad3)
- `class-asiaauto-listing-editor.php` — `DUP_BLOCKED_META` rozszerzona z 22 → 30 kluczy (8 nowych: api_removed/removed_at/removal_reason + manual_import×3 + reservation×2)
- Smoke test (Reflection + replikacja pętli `handleDuplicate()`): kopia #317106 (BYD Leopard 7 z aktywną rezerwacją) czysta z wszystkich 8 blocked meta
- Cleanup par (mysqldump backup w `~/backups/primaauto/2026-05-16-pre-dup-cleanup/`):
  - 314155 (kopia 303534, Denza Z9 GT DM-i): -2 wiersze (res×2)
  - 324822 (kopia 317106, BYD Leopard 7): -4 wiersze (res×2 + removed_at + removal_reason)
- Oryginały 303534/317106 nietknięte (zachowują rezerwację)

### W1 — v0.32.47 (commit 1115c92)
- `class-asiaauto-sync.php` — prywatna `isManuallyManaged(int $post_id): bool` + 2 guardy w `case 'changed'` (przed `updateListing`) i `case 'removed'` (przed `markRemoved`)
- Smoke test (Reflection): 5/5 case PASS — manual_import=1 → true, manual_entry=1 (bez import) → true, normalny sync-owy → false, planned-protect 249638 + 306890 → true
- Real `wp asiaauto sync --source=dongchedi`: brak fatal po patchu

### Restore 8 listings — sekcja 3 planu
- Backup: `~/backups/primaauto/2026-05-16-restore-listings/{posts,postmeta}-before.sql` (mysqldump)
- 8/8 → publish, post_name czyste (bez `__trashed` suffix)
- 16 wierszy meta usunięte (`_asiaauto_removed_at` + `_asiaauto_removal_reason` + `_asiaauto_api_removed` × 8)
- Wszystkie 8 z `_asiaauto_manual_import=1` (W1 ich chroni przed kolejnym sync)
- **Otwarte:** 310457 (BYD Yangwang U8) — 0 zdjęć (decyzja Ruslana sekcja 5 punkt 3: pobranie z Dongchedi vs kopia z 310458). 299535 (iCAR V23) — drobny rozjazd: 12 attach w `post_parent` ale 6 w `gallery` meta (do zgłoszenia, render działa)

### W3 — v0.32.48 (commit do utworzenia)
- NOWY `class-asiaauto-admin-listings-views.php` — `AsiaAuto_Admin_Listings_Views` (hooki `views_edit-listings` + `pre_get_posts`, count = 69 active manual_import=1 po restore)
- `asiaauto-sync.php` — `require_once` + `new AsiaAuto_Admin_Listings_Views()` w `if (is_admin())`
- Smoke test (6/6 PASS): klasa loaded, 2 hooki @10, count zgadza się z direct SQL, link HTML poprawny, meta_query setowanie działa pod `?asiaauto_view=manual_import`

### Otwarte komunikaty do Ruslana
- Potwierdzenie wdrożenia W1 + W2 (rezerwacja-na-kopiach naprawiona, sync nie wycofuje ręcznie dodanych)
- Restore 8 listings → publish (z notatką że 310457 BYD Yangwang U8 jest BEZ zdjęć — decyzja Ruslana: pobrać z Dongchedi czy skopiować z 310458 z 9 zdjęciami)
- 299535 (iCAR V23) ma 12 attachmentów ale `gallery` meta tylko 6 — do potwierdzenia czy uzupełnić
- Link „Ręczny import (69)" w admin listings — szybki dostęp do tej grupy

---

**Koniec dokumentu.**
