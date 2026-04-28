# Spec: Panel diagnostyczny admina (asiaauto-sync)

> Data: 2026-04-28. Autor: Jan + Claude (brainstorming session).
> Status: design zatwierdzony, gotowy do implementacji.

---

## 1. Cel i kontekst

Wbudowany w plugin `asiaauto-sync` pulpit diagnostyczny dla administratora (`manage_options`), umożliwiający:

1. Jednym kliknięciem przeskanować system pod kątem znanych problemów operacyjnych (zdjęcia, chińskie znaki, mapping, hub coverage, duplikaty).
2. Zobaczyć raport per-check z listą issues.
3. Uruchomić naprawę dla wybranych issues — z dry-run preview (modal) i osobnym apply.
4. Korzystać z tych samych narzędzi z linii poleceń (`wp asiaauto diag …`) — przewidziane jako kanał pracy Claude w sesjach Bash.

Architektura ma być **rozszerzalna**: dodanie nowego checku = jeden plik + jedna linia rejestracji.

### Motywacja

- Mamy 25+ skryptów `diag/*.php` rozsianych, każdy uruchamiany ręcznie. Brak overview „co teraz nie gra".
- Stałe potrzeby operacyjne (po imporcie, po batch n8n, po zmianie filtrów sprzedawcy) wymagają wykonania serii sprawdzeń ręcznie.
- Klient zgłasza problemy ad-hoc (BYD Shark, brakujące huby) — bez panelu odpowiedź wymaga manualnego SQL.
- Claude w sesjach pracuje przez Bash; potrzebuje CLI wrappera bo browser flow z poziomu sesji nie istnieje.

---

## 2. Out of scope (v1)

- Klastry **lifecycle** i **operations** (rotacja listingów, orphan attachments, filter cleanup, race detection) — przeniesione do v2. Powód: wyższe ryzyko biznesowe (destruktywne), wymaga staranniejszego UX (per-item checkbox + preview).
- Generacja contentu hubów — `wiki_body` powstaje przez n8n pipeline (poza UI).
- Cron / harmonogram skanów — v1 jest na żądanie. v2 może dodać `asiaauto_diag_daily` z mailem alertem.
- Historia raportów — v1 trzyma tylko **ostatni** wynik per check (`option('asiaauto_diag_last_runs')`). v2 może dodać archiwum.
- Auto-fix dla `make-without-wiki`/`serie-without-wiki` — content jest workflowem n8n, nie z UI.

---

## 3. Architektura

```
                          ┌──────────────────────┐
                          │ AsiaAuto_Diag_Registry│
                          └──────────┬───────────┘
            ┌────────────┬───────────┼─────────────┬────────────┐
            ▼            ▼           ▼             ▼            ▼
   Check_MissingImages  Check_Chinese  Check_Broken  Check_Dup  …8 checków
            │            │           │             │            │
            └────────── implements AsiaAuto_Diag_Check ─────────┘

         ┌───────────────┬─────────────────┬─────────────────┐
         ▼               ▼                 ▼                 ▼
   Admin UI page    AJAX endpoints    WP-CLI command   (przyszłość: cron)
   (wp-admin/...)   (wp_ajax_*)       (wp asiaauto diag …)
```

### Pliki nowe

```
plugins/asiaauto-sync/
├── includes/
│   ├── class-asiaauto-diag.php                  # registry + AJAX handlers
│   ├── class-asiaauto-admin-diag.php            # admin page render + assets enqueue
│   └── diag-checks/
│       ├── interface-check.php                   # AsiaAuto_Diag_Check
│       ├── class-check-base.php                  # abstract: nonce, time_limit, logger
│       ├── class-check-missing-images.php
│       ├── class-check-chinese-chars.php
│       ├── class-check-broken-extra-prep.php
│       ├── class-check-duplicate-listings.php
│       ├── class-check-make-without-wiki.php
│       ├── class-check-serie-without-wiki.php
│       ├── class-check-listings-without-mapping.php
│       └── class-check-mapping-without-term.php
└── assets/
    ├── admin-diag.js
    └── admin-diag.css
```

### Pliki modyfikowane

- `asiaauto-sync.php` — bump wersji + require nowych klas + init `AsiaAuto_Diag_Registry`.
- `includes/class-asiaauto-admin.php` — `add_submenu_page()` dla podstrony „Diagnostyka".
- `cli/class-asiaauto-cli.php` — registracja `wp asiaauto diag …` subcommands.
- `diag/*.php` (8 z 25) — refaktor top-level body → eksportowana funkcja `asiaauto_diag_<id>(bool $apply=false): array`. Stary `wp eval-file diag/X.php` ścieżka nadal działa (wywołuje funkcję na końcu). Klasy checków wołają funkcje inline (bez shell-exec, bez drugiego PHP boota).

---

## 4. Kontrakt checku

### Interface

```php
interface AsiaAuto_Diag_Check {
    public function getId(): string;          // 'missing-images'
    public function getLabel(): string;        // 'Listings bez zdjęć'
    public function getCluster(): string;      // 'integrity' | 'seo-coverage'
    public function getEstimatedSeconds(): int; // do estimacji progress UI
    public function hasFix(): bool;
    public function getFixMode(): string;      // 'auto' | 'confirm' | 'per-item' | 'none'

    /**
     * @return array{
     *   status: 'ok'|'warn'|'error',
     *   count: int,
     *   issues: array<int, array{id: string, label: string, meta: array}>,
     *   summary: string,
     *   duration_ms: int,
     *   ts: int
     * }
     */
    public function run(): array;

    /**
     * @return array{
     *   actions: array<int, string>,
     *   estimated_changes: array<string, int>,
     *   apply_token: string,
     *   ttl_seconds: int
     * }
     */
    public function previewFix(array $issue_ids): array;

    /**
     * @return array{
     *   applied: int,
     *   failed: int,
     *   errors: array<int, string>
     * }
     */
    public function applyFix(array $issue_ids, string $apply_token): array;
}
```

### Abstract base

`AsiaAuto_Diag_Check_Base` daje gotowe:

- `set_time_limit(25)` w `run()` (Hostido proxy ~30s).
- `current_user_can('manage_options')` guard.
- Wrapper `delegate(string $function_name, bool $apply): array` — wywołuje funkcję eksportowaną z `diag/X.php` po refaktorze.
- `mintApplyToken(): string` (transient TTL 300s, scope = check_id + issue_ids hash).
- `validateApplyToken(string $token, array $issue_ids): bool`.
- `log(string $level, string $event, array $context)` → `AsiaAuto_Logger` → `logs/diag.log`.

---

## 5. Inwentarz checków v1

Klastry: `integrity` (1–4), `seo-coverage` (5–8).

| # | id | Label | Logika `run()` | hasFix | fixMode | Reuse |
|---|---|---|---|---|---|---|
| 1 | `missing-images` | Listings bez zdjęć | `posts WHERE post_type='listings' AND post_status IN ('publish','draft') AND meta_key='_thumbnail_id' IS NULL` | tak | `auto` | `diag/fix-missing-images.php` |
| 2 | `chinese-chars` | Chińskie znaki w tytułach/serie/make | regex `[\x{4e00}-\x{9fff}]` w `post_title` + term `name` (taxonomies: `make`, `serie`) | tak | `confirm` | `diag/check-chinese-models.php` + `fix-chinese-v23.php` |
| 3 | `broken-extra-prep` | Złamany JSON `_asiaauto_extra_prep` | `meta_key='_asiaauto_extra_prep' AND json_decode(value) IS NULL AND value NOT IN ('null','[]','{}')` | tak | `confirm` | `diag/fix-broken-json-v25.php` |
| 4 | `duplicate-listings` | Duplikaty po `_asiaauto_inner_id` | `SELECT inner_id, GROUP_CONCAT(post_id) FROM postmeta WHERE meta_key='_asiaauto_inner_id' GROUP BY inner_id HAVING COUNT(*)>1` | tak | `per-item` | `diag/cleanup-duplicates.php` |
| 5 | `make-without-wiki` | Marki bez `wiki_body` | `terms in 'make' WHERE NOT EXISTS termmeta key='wiki_body'` | nie | `none` | — |
| 6 | `serie-without-wiki` | Modele bez `wiki_body` | analogicznie dla `serie` | nie | `none` | — |
| 7 | `listings-without-mapping` | Listingi z marką/modelem CN spoza v6.1 mappingu | listing meta `_asiaauto_make_cn`/`_asiaauto_serie_cn` NOT IN keys of `data/brand-mapping-v6.1.php` | nie (v1) | `none` | — |
| 8 | `mapping-without-term` | Pozycje mappingu bez utworzonego termu | dla każdej (cn_make, cn_serie) z mapping → `term_exists(slug_eu)` w taksonomii | tak | `confirm` | `wp_insert_term` |

### Notatki implementacyjne per check

**#1 missing-images:** fix odpala `fix-missing-images.php` na całym zbiorze (tam logika probe + 8-poziomowy fallback szerokości). Auto-fix bo nie usuwa danych — dolewa zdjęcia z Dongchedi cache. Jeśli oferta jest „ghost" (URL zwraca 404), listing idzie do trash — to ścieżka już istnieje w skrypcie (15 OK + 30 trash w przeszłym uruchomieniu, patrz QUEUE.md ZADANIE 6 Krok D).

**#2 chinese-chars:** dwa źródła osobno: post_title vs term names (make + serie). Issue.id = `post_<id>` lub `term_<taxonomy>_<id>`. Preview pokazuje dla każdego co zostanie zmienione (z mapy `data/translations-models.php`).

**#3 broken-extra-prep:** issue.id = post_id. Preview pokazuje surowy fragment + propozycję naprawy (jeśli `wp_unslash` rozwiązuje → automatycznie; jeśli nie → flag jako requires-manual).

**#4 duplicate-listings:** issue.id = inner_id; meta zawiera listę post_id w grupie. Per-item: użytkownik wybiera który zostawić (radio button per duplikat). Default suggestion: najnowszy `post_modified` zostaje, reszta do trash.

**#5/#6 make/serie-without-wiki:** raport pokazuje listę z linkami `edit-tags.php?taxonomy=make&tag_ID=…`. Generacja contentu jest poza UI (workflow n8n).

**#7 listings-without-mapping:** raport agreguje per `(make_cn, serie_cn)` z liczbą listingów. To są kandydaci do dopisania w `data/brand-mapping-v6.1.php` lub do zignorowania (np. niszowe modele <3 listingów).

**#8 mapping-without-term:** wykrywa np. „Zeekr 9X" w mappingu, którego nie ma w taksonomii (auto-create gdy klient zatwierdzi). Fix: `wp_insert_term($slug_eu, 'serie', ['parent' => $make_term_id])`.

---

## 6. AJAX endpointy

Wszystkie pod `admin-ajax.php`, capability `manage_options`, per-action nonce.

| Action | Body | Response |
|---|---|---|
| `asiaauto_diag_run` | `check_id`, `_wpnonce` | full `run()` payload |
| `asiaauto_diag_preview` | `check_id`, `issue_ids[]`, `_wpnonce` | `previewFix()` payload (z `apply_token`) |
| `asiaauto_diag_apply` | `check_id`, `issue_ids[]`, `apply_token`, `_wpnonce` | `applyFix()` result |

Throttle: `asiaauto_diag_run` dla tego samego `check_id` częściej niż co 30s wymaga `force=1` (przeciw przypadkowym double-clickom). UI przy double-click pokazuje toast „Ostatni run: 12s temu".

---

## 7. WP-CLI

W `cli/class-asiaauto-cli.php` rejestrujemy subcommandy:

```bash
wp asiaauto diag list
# Tabela: ID | Cluster | Label | Has fix | Fix mode | Last run | Last count

wp asiaauto diag run <check-id> [--format=table|json|count]
# Uruchamia jeden check, drukuje raport.

wp asiaauto diag run-all [--format=json]
# Wszystkie checki sekwencyjnie. JSON do parsowania.

wp asiaauto diag preview-fix <check-id> [--issue-ids=...]
# Bez --issue-ids → wszystkie z ostatniego run.

wp asiaauto diag apply-fix <check-id> [--issue-ids=...] --yes
# Wymuszone --yes; bez niego → error + sugestia preview.
```

Każda subkomenda mapuje się 1:1 na metodę `Check_X`. Format `count` dla `run` zwraca pojedynczą liczbę (do `if [ $(wp asiaauto diag run X --format=count) -gt 0 ]` w skryptach).

CLI nie używa AJAX nonce ani apply_token (działa przez WP-CLI auth = root/admin context). `apply_token` to zabezpieczenie tylko AJAX flow.

---

## 8. Frontend admin page

### Layout

Single page `/wp-admin/admin.php?page=asiaauto-diag`.

- Header: tytuł + przycisk **„Skanuj wszystko"** (primary).
- Sekcje per cluster (`integrity`, `seo-coverage`).
- W każdej sekcji tabela: Check | Status | Znaleziono | Akcje (`Skanuj` / `Raport…`).
- Footer: „Ostatni pełny skan: <data> (X znalezionych)".
- Modal/drawer „Raport <check>" — lista issues + przyciski fix wg `getFixMode()`.

### JS flow „Skanuj wszystko"

```
1. JS bierze listę check_id z DOM (rendered server-side z registry).
2. Iteracja sekwencyjnie:
   - badge → ⏳ spinner
   - POST /admin-ajax.php?action=asiaauto_diag_run&check_id=X
   - response → update badge (✓/⚠/✗) + count
3. Po zakończeniu: footer „Ostatni pełny skan: TS (sumacount)".
4. Per-check error → badge ✗ + tooltip z error msg, ale flow leci dalej.
```

### Modal fix flow

- `auto`: klik fix → POST apply bez modalu, toast „Naprawiono X".
- `confirm`: klik fix → POST preview → modal z listą zmian + apply_token → klik „Wykonaj" → POST apply.
- `per-item`: klik raport → drawer z checkboxami per issue → „Wykonaj zaznaczone" → preview → modal → apply.

### Assets

- `admin-diag.js` (~250 LOC): registry-aware orchestrator, modal logic, AJAX wrapper.
- `admin-diag.css` (~80 LOC): grid tabel, badge'e statusu, modal/drawer.
- Enqueue tylko na page=`asiaauto-diag` (warunek w `admin_enqueue_scripts`).

---

## 9. Bezpieczeństwo i izolacja

- Wszystkie AJAX + admin page pod `manage_options`.
- Nonce per-action (`wp_create_nonce('asiaauto_diag_<action>')`).
- `apply_token` z `previewFix()` ważny 5 min, scope = check_id + issue_ids hash. Eliminuje stale-state race („preview pokazał X, w międzyczasie sync zmienił bazę, apply by skasowało Y").
- `set_time_limit(25)` na `run()` (Hostido proxy ~30s).
- `applyFix()` zapis do `logs/diag.log` przez `AsiaAuto_Logger`: timestamp, user_id, check_id, issue_ids, result.
- Read-only checki (1–8 w trybie `run()`) nie blokują syncu — wszystko `SELECT`, MySQL read locks.
- Apply checków destruktywnych (#4 duplicate-listings) dodatkowo: wymóg backupu nie blokuje (hosting backupuje), ale logger drukuje WARNING przy każdym apply checku z trybem `per-item`.

---

## 10. Rozszerzalność

Dodanie nowego checku to:

1. Stwórz `includes/diag-checks/class-check-<nowy>.php` (extends `AsiaAuto_Diag_Check_Base`).
2. Implementuj `getId/getLabel/getCluster/run` (+ opcjonalnie `previewFix`/`applyFix`).
3. Dodaj 1 linię w `AsiaAuto_Diag_Registry::register()`:
   ```php
   $this->add( new AsiaAuto_Check_Nowy() );
   ```

Pojawia się automatycznie w UI, w AJAX, w `wp asiaauto diag list`.

---

## 11. Plan deploymentu

1. Implementacja off-prod (lokalny snapshot lub direct na prod ze starannym `.bak`).
2. Bump `ASIAAUTO_VERSION` (sugestia 0.31.13 → 0.32.0 — feature minor).
3. `php -l` na każdym nowym pliku.
4. Smoke: `wp asiaauto diag list` → musi wylistować 8 checków.
5. Smoke: `wp asiaauto diag run-all --format=json` → wszystkie zwracają status.
6. Smoke admin UI: zalogowany jako Jan, klik „Skanuj wszystko" — patrzy progress, otwiera 1 raport.
7. Backup pluginu w `~/backups/primaauto/<data>-pre-diag/`.
8. Deploy.
9. Update `docs/VERSIONS.md` + `docs/QUEUE.md` (nowy task „ZADANIE 14 — diagnostyka admin: DONE").

---

## 12. Testowanie

Manual test plan (post-deploy):

| # | Krok | Oczekiwany wynik |
|---|---|---|
| 1 | `wp asiaauto diag list` | 8 wierszy, każdy z lastrun=null |
| 2 | `wp asiaauto diag run missing-images --format=json` | JSON, count odzwierciedla rzeczywistość bazy |
| 3 | `wp asiaauto diag run-all` | 8 checków uruchomione, suma `count` rozsądna |
| 4 | Admin UI klik „Skanuj wszystko" | Progress live, finał = 8 status badge'y |
| 5 | Klik „Raport" na checku z >0 | Modal z listą issues, przyciski fix wg trybu |
| 6 | Preview fix dla `chinese-chars` | Modal pokazuje listę zmian, apply_token wygenerowany |
| 7 | Apply fix dla 1 issue | Toast „Naprawiono 1", logger zapisał wpis |
| 8 | Run tej samej akcji w obrębie 30s | Throttle: toast „Ostatni run: 12s temu" |
| 9 | Stale apply_token (>5min) | Apply zwraca 409, UI pokazuje „Token wygasł, odśwież preview" |
| 10 | CLI `apply-fix` bez `--yes` | Error + sugestia |

---

## 13. Otwarte pytania

Brak. Design domknięty, gotowy do `writing-plans`.

---

## 14. Linki

- Istniejące skrypty diag: `plugins/asiaauto-sync/diag/` (25 plików).
- Dokumentacja diag: `docs/architektura/diag-scripts.md`.
- Plan A (race condition): `docs/decyzje/2026-04-22-dedup-i-optymalizacja-bazy.md`.
- Memory: `project_sync_race_and_bloat.md`, `project_brand_model_mapping.md`, `project_hub_pipeline_fix_2026_04_24.md`.
