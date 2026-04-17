# Kolejka zadań — Prima Auto

> Aktualizacja: 2026-04-17 (sesja 6: ZADANIE 6 Krok C+D done, cron bug fixes, bump 0.30.12)

---

## GRUPA 9.5 — performance ✅ DONE (0.30.10)

- [x] Zidentyfikowano: 3× COUNT na postmeta (~600ms) + JOINy w `renderPricePreview` (~400ms)
- [x] Transient `asiaauto_listing_counts` (10min TTL), invalidate po `ajaxBulkRecalc`
- [x] Trash TTL 30d → 7d (2534 listings w koszu się samooczyści szybciej)

---

## ZADANIE 6 — Filtr miast + aktualizacja marek (NOWE)

> Status: **w planowaniu**. Realizacja w nowym wątku po zamknięciu aktualnej sesji.

### Kontekst biznesowy

Prima Auto ma ludzi na miejscu w południowych Chinach (Guangdong, Fujian, Guangxi, Hainan).
Mogą fizycznie sprawdzać i doglądać auta tylko w wybranych miastach. Import ogłoszeń powinien
być ograniczony do tych lokalizacji — żeby klient widział tylko auta, które faktycznie można
zweryfikować na miejscu.

Jednocześnie klient prześle zaktualizowaną listę marek — obecne orphaned termy (Li Auto, NIO,
Volvo) mogą wrócić lub zostać usunięte.

### Lista miast od klienta

| Region | Miasto (PL) | Miasto (ZH) |
|---|---|---|
| Guangdong | Guangzhou | 广州 |
| Guangdong | Shenzhen | 深圳 |
| Guangdong | Foshan | 佛山 |
| Guangdong | Dongguan | 东莞 |
| Fujian | Xiamen | 厦门 |
| Fujian | Fuzhou | 福州 |
| Guangxi | Beihai | 北海 |
| Guangxi | Nanning | 南宁 |
| Hainan | Haikou | 海口 |
| Hainan | Sanya | 三亚 |

> Uwaga: klient podał też regiony (Fujian, Guangxi, Hainan) — interpretowane jako prowincje,
> z których wybrano konkretne miasta. Filtr dotyczy miast, nie prowincji.

### Podzadania — Krok A: weryfikacja dostępności ogłoszeń ✅ DONE (0.30.10)

- [x] Sprawdzone: API `getFilters()` NIE ma filtra `city` — filtr tylko po stronie PHP
- [x] Scan 80 stron dongchedi (1600 ofert), pole `city` = chińskie znaki (广州, 深圳...)
- [x] Zmapowane wszystkie miasta z 4 prowincji z co najmniej 1 ofertą
- [x] Lista finalna: **31 miast** (15 Guangdong + 6 Fujian + 8 Guangxi + 2 Hainan)
- [x] Nadgorliwość Ruslana skorygowana — dodane m.in. 惠州 (19 ofert), 泉州 (9), 南平 (8), 柳州 (5)

### Podzadania — Krok B: filtr w panelu admina ✅ DONE (0.30.10)

- [x] Opcja `city_filter_enabled` (bool) + `city_filter_cities` (array `[{zh,pl,province}]`) w `asiaauto_import_config[source]`
- [x] UI w zakładce "Filtry": toggle + przycisk "Konfiguruj miasta" otwierający modal
- [x] Modal: 4 sekcje prowincji z checkboxami, per-prowincja "wszystkie/żadne", sekcja "Dodaj miasto" (ZH + PL + prowincja), licznik zaznaczonych
- [x] Filtr w `AsiaAuto_Importer::isAllowedByConfig()` — skip oferty której `city` nie ma na liście `zh`

### Podzadania — Krok C: aktualizacja marek ✅ DONE (0.30.12)

- [x] Klient wybrał 66 marek z panelu — zrzuty ekranu z panelu admina
- [x] Orphaned termy (Li Auto, NIO, Volvo, Lynk & Co) — zostają, część wróci z importem
- [x] Brakujące marki dodane jako terminy taksonomii przez sync

### Podzadania — Krok D: re-import (po finalizacji filtrów) ✅ DONE (0.30.12)

- [x] Filtr miast przetestowany i zatwierdzony przez klienta (31 miast)
- [x] Backup bazy przed purge (`mysqldump` listings + postmeta + options do `~/backups/primaauto/pre-purge-20260417-1524.sql`, 445MB)
- [x] Purge: 2905 listings → trash (marki OR miasta poza listą, rezerwacje chronione)
- [x] Bulk-import przez `diag/bulk-import-by-brand.php`: reverse-order pages, MAX_PAGES=50, probe [50,40,30,20,10,5,2,1], parametr API `mark=X` (nie `brand=`)
- [x] Fix-missing-images: 45/45 (15 OK, 30 TRASH ghost-offers)
- [x] Cron bug fix: `add_action('asiaauto_sync_changes')` + `cron_schedules` filter — sync faktycznie działa (change_id 2868663 → 2870663 po pierwszym handler fire)

### Cena — zmiana filtru

- Klient obniżył `price_from` 120000 → 75000 CNY (2026-04-17 ~16:10); v4 bulk-import wczytał świeży config, zmiana obowiązuje dla cron syncu.

### Zależności i uwagi

- Filtr miast ma być domyślnie **wyłączony** (toggle OFF) — obecne ogłoszenia nie znikają od razu
- Toggle OFF = sync działa jak dotychczas (bez filtra geograficznego)
- Toggle ON = sync pobiera tylko ogłoszenia z wybranych miast
- Krok D dopiero po: Krok A (weryfikacja) + Krok B (filtr gotowy) + Krok C (marki potwierdzone)

---

## Backlog (niski priorytet)

- [ ] SKILL/CLAUDE.md: zasada „MCP tylko gdy lokalnie nie da się" — MCP http round-trip marnuje tokeny, jeśli uruchamiamy Claude Code na Elarze mającej bezpośredni dostęp do plików i `wp` CLI. Dotyczy: `read_file`, `list_dir`, `query_db` (zastąpić `wp db query`), `options` (zastąpić `wp option get`), `stats` (własny `wp eval`). MCP sens zachowuje tylko dla zewnętrznych projektów bez local shell (Claude.ai web).
- [ ] Krok 4 manual editor — metabox extra_prep (18 zakładek)
- [ ] Email HTML templates (maile są plain text)
- [ ] Homepage + Contact CSS → pliki zewnętrzne (z inline)
- [ ] Archive/taxonomy pages dla marek (B5 — duże zadanie, osobny projekt)
- [ ] Orphaned terms (Li Auto, Volvo, NIO, Lynk &amp; Co — count=0, czekają na nową listę marek od klienta)

---

## Zrealizowane (archiwum)

- [x] Pending 0: bump wersji 0.29.0-wip → 0.30.7 (2026-04-16)
- [x] Pending 1: pipeline USD-centric — `calculateFromCifUsd()`, `BREAKDOWN_VERSION=2` (2026-04-16)
- [x] ZADANIE 2: załączniki PDF do umowy (renderAttachment1/2), token bezpieczeństwa, nr umowy w tytule przelewu (0.30.8, 2026-04-17)
- [x] ZADANIE 3: maile statusów, etykiety "depozyt zabezpieczający" (2026-04-16)
- [x] ZADANIE 4: smoke test E2E — flow zamówień, PDF, maile, statusy (2026-04-17)
- [x] ZADANIE 5: rework workflow zamówień — model agencyjny, nowe statusy, wizard, panel admina (0.30.7, 2026-04-16)
- [x] B2 SEO: meta/OG/title dla single i inventory, Schema.org, term meta opisów, 10 marek + 75 modeli, llms.txt (0.30.9, 2026-04-17)
- [x] Panel klienta `/klient/` — shortcode, logout, auto-redirect (2026-04-16)
