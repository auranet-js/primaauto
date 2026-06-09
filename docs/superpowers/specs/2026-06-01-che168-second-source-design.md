# Che168 jako drugie źródło importu — design spec

**Status:** Recon completed (2026-06-01). **REWIZJA ZAKRESU 2026-06-05 — patrz niżej.**

> ## ⚠ REWIZJA ZAKRESU 2026-06-05 — najpierw RĘCZNY IMPORT, nie sync
>
> Po analizie field-level 4 marek + teście overlap egzemplarzy (`2026-06-05-che168-vs-dongchedi-field-analysis.md`) decyzja Janka: **NIE robimy automatycznego syncu / Source Managera / dedup cross-source teraz.** Zamiast tego — **ręczny import pojedynczego ogłoszenia Che168 na wzór istniejącego manual-import dongchedi**, gdzie kurator (Ruslan/Janek) decyduje per ogłoszenie czy je chcemy.
>
> **Uzasadnienie:**
> - Test 1F: overlap egzemplarzy Che168↔dongchedi = **0/30** → dedup cross-source marginalny, Source Manager przerost na ten etap.
> - Che168 = inny/unikalny katalog (używane, częściowo spoza naszej oferty) → **kurator-selektor** ma większy sens niż automat zalewający bazę.
> - Ręczny import = zero ryzyka masowego zaśmiecenia, natychmiastowa wartość, mały zakres.
>
> **Zakres Fazy 1 (manual import Che168) — 3 komponenty:**
> 1. **Translacje w plikach** (wybór Janka — nie resolver w runtime): nowy `data/che168-model-map.php` z parami `CheMark|CheModel → {mark_eu,serie_eu,title_eu,slug}`. Wygenerowane półautomatycznie (`tmp/gen-che168-mapping-2026-06-05.php`): **51 wpisów gotowych + 59 już trafia** = ~110 par naszej oferty pokrytych. Denza per-napęd → domyślnie DM-i. Propozycja do review: `tmp/che168-mapping-proposal-2026-06-05.{php,md}`.
> 2. **Manual import UI** (`class-asiaauto-admin-manual-import.php`): przyjąć `che168.com/...` URL + numer + source (dziś hardcoded dongchedi). Graceful fallback: model bez mapowania → import jako draft + ostrzeżenie „przypisz hub ręcznie".
> 3. **Importer adapter Che168** (`class-asiaauto-importer.php`): obsłużyć inny kształt `$data` Che168 — city z `address` (`explode`), `extra.configuration` (po `id`) → nasz `extra_prep`, `vin` + `first_registration` (nowe meta), obrazy permanent (`2sc2.autoimg.cn`, bez `x-expires`).
>
> **Ustalenia 2026-06-05 (weryfikacja w kodzie/API):**
> - **Denza napęd = NON-PROBLEM:** wszystkie Denza D9 (106×)/Z9/Z9GT/N8L/N9 na Che168 = `plug-in hybrid`; zero EV pod tym samym stringiem (tylko N7=electric, ma własny hub). Default `DM-i` w danych jest w 100% bezpieczny — bez logiki napędu.
> - **Narzędzia diag/translator są source-agnostic** (operują na `wp_posts`/meta/termach, nie na źródle): `check-chinese-chars`, `check-listings-without-mapping`, `check-mapping-without-term`, `check-duplicate-listings`, `translateModel/Complectation/ExtraPrep`, auto-extend `translations-models` — działają na importach Che168 jak na dongchedi. Hardcoded `dongchedi` jest tylko w skryptach *importu/debugu* (`bulk-import-*`, `debug-offer`), nie w obróbce. **Scenariusz Ruslana** (doda numer Che168 spoza bazy/huba → sierota/CN tytuł → czyszczenie tymi samymi narzędziami) = potwierdzony.
> - **Jedyny wyjątek: `extra_prep`.** dongchedi = płaskie klucze EN; Che168 = `extra.configuration` (grupy CN + `id`). `check-broken-extra-prep`/`translateExtraPrep` zakładają format dongchedi → **adapter importera (komponent 3) musi konwertować Che168 `configuration` → nasz płaski `extra_prep`**, wtedy narzędzia spec też działają. Sieroty/tytuły/wersje działają niezależnie.
> - **Brzegowe CN (~15-25 naszych nowych modeli): decyzja = fallback on-demand** (nie dorabiamy prewencyjnie). Import jako draft + ostrzeżenie „przypisz hub ręcznie"; wpis dorabiany gdy kurator faktycznie wskaże dany model.
>
> **Sekcje 1–14 poniżej (Source Manager / sync / dedup / Registry)** = wizja docelowa, ODŁOŻONA. Wracamy do niej tylko jeśli ręczny import się sprawdzi i pojawi potrzeba automatu. Sekcja 5A (adapter tłumaczeń + spec) pozostaje aktualna i zasila Fazę 1.
>
> **Blocker biznesowy z reconu (sekcja 0.1) NADAL obowiązuje** — ręczny import nie wymaga decyzji o segmencie (kurator wybiera pojedyncze auta), więc Fazę 1 można robić bez rozstrzygania Wariantu B/C.

**Blocker biznesowy ujawniony przez recon — patrz sekcja 0.1.**
**Sesja brainstormingowa:** 2026-06-01
**Autor:** Jan Schenk + Claude (brainstorming skill)
**Plugin:** `asiaauto-sync` na primaauto.com.pl
**Trigger biznesowy:** awaria feedu dongchedi od 2026-05-24 (patrz `~/.claude/projects/-home-host476470-projekty-primaauto/memory/project_sync_feed_added_stopped_2026_05_25.md`). Auto API udostępniło Che168 endpoint pod tym samym kluczem 2026-06-01 jako fallback.

---

## 0. Decyzje wejściowe (z brainstormingu)

- **Scope tej sesji:** Recon + design spec wdrożenia (Phase 0 + spec na półce).
- **Tryb dual-source:** permanent równolegle z deduplikacją heurystyczną po polach (rocznik + przebieg + co się da). Brak fallback-toggle.
- **Import filter Che168:** TBD post-recon — recon zwraca rekomendację po analizie próbki.
- **Architektura:** Source Manager abstraction (czyste warstwy) zamiast minimal touch. Świadoma decyzja użytkownika mimo wyższego kosztu refactoru.
- **Wykonanie planu:** wstrzymane do decyzji biznesowej. Jeśli dongchedi wraca do normy → spec na półkę. Jeśli nie → writing-plans → impl.

---

## 0.1. Recon results — TL;DR z raportu (2026-06-01)

Pełny raport: `docs/superpowers/specs/2026-06-01-che168-recon-report.md`. Próbka: 80 unikalnych iid, 50 pełnych `getOffer`.

**Technika ✅:**
- `getChanges` Che168 standardowy (added/changed/removed + monotonic change_id). Architektura Sync layer bez zmian.
- `getOffer` zwraca 34 pola (vs ~15 w dongchedi). Wszystkie nasze mapują się 1:1.
- **`vin` 100% coverage** + **`first_registration` 100%** — Che168 ma to czego dongchedi NIE ma. Dla wewnątrz-Che168 dedup: VIN-based (silniejsze niż heurystyka).
- Image URLs **PERMANENT** (host `2sc2.autoimg.cn`, brak `x-expires`). Preflight W1 nieaktywny dla Che168 → prostsze, brak ghost-offers.
- inner_id zakresy nie kolidują (Che168 43-56M, dongchedi 14-24M). Compound key wprowadzamy defensywnie.
- `getOffers` Che168 zwraca FORMAT EVENT-FEED (z `change_type/data`) zamiast spłaszczonych ofert — anomalia API, drobny refactor `class-asiaauto-admin-manual-import.php`.

**Biznes ⚠️ — RED FLAG:**
- Marki: tylko **5/22 (23%)** w naszej liście 66. Dominacja zachodnich premium używanych: Mercedes-Benz (28% próbki!), Porsche, BMW, Audi, Lexus, Land Rover, Tesla.
- Chińskie NEV-y (BYD, Voyah, Zeekr, NIO, XPeng, Avatr, Denza, Hongqi…) **praktycznie nieobecne w Che168** — bo Che168 to **C2C marketplace używanych aut**, chiński konsument kupuje nowe NEV-y od dealera (= dongchedi).
- Rocznik: mediana **2017**, year≥2024 tylko **8%** próbki.
- Przebieg: mediana **84,000 km**, km≤50,000 tylko **25%** próbki.
- Miasta: **26%** próbki w naszych 31 south Chinese; 74% reszta Chin.
- **Połączony filtr dongchedi-style → <1% pokrycia**. Z 50 ofert <1 by przeszło.

**Decyzja jaką recon wymusza (PRZED writing-plans):**

| Wariant | Strategia | Wdrożenie sensowne? |
|---|---|---|
| **A** — filtr klon dongchedi | utrzymujemy obecny segment | NIE (<1% pokrycia) |
| **B** — premium używane Chiny (Mercedes/BMW/Porsche…) ogólnonarodowo | ZMIANA segmentu klienta | TAK technicznie, ale Ruslan + jego klient muszą się zgodzić na inne pozycjonowanie |
| **C** — czekamy na dongchedi recovery | nic nie robimy | TAK — najtańsze, dongchedi już zaczął wracać częściowo |

**REKOMENDACJA:** **C** — spec na półkę, czekamy na dongchedi. Jeśli za 7-14 dni nadal nie wraca do >50% normalnej wydajności → wracamy z propozycją Wariantu B do Ruslana (dla niego to nowy biznes — premium używane import).

---

## 1. Architecture overview

Trzy nowe klasy abstrahujące koncept „source" + refactor istniejących `AsiaAuto_Sync` i `AsiaAuto_Importer`.

**`AsiaAuto_Source_Registry`** (singleton) — single source of truth o aktywnych sourcach. Zwraca listę zarejestrowanych źródeł (`dongchedi`, `che168`) z konfiguracjami (filter, brand mapping reference, cron schedule, enabled/disabled). Czyta `asiaauto_import_config` + opcjonalne `define('ASIAAUTO_SOURCES', [...])` z wp-config.

**`AsiaAuto_Source_Manager`** — orkiestrator. `syncAll()` iteruje po aktywnych z Registry i wywołuje `Sync::run($source)` sekwencyjnie, każdy w swoim advisory locku. `getStatus($source)` zwraca last_change_id + last_run + counts — dla admin UI. Wszystkie publiczne ścieżki wywołania syncu (cron, CLI `wp asiaauto sync`, admin button) przechodzą przez Source Manager.

**`AsiaAuto_Dedup_Service`** — wywoływany przez `Importer::importListing()` PRZED `wp_insert_post`. `findDuplicate($data, $source): ?array` lookup po heurystyce. Match → `['post_id'=>N, 'source'=>X, 'matched_fields'=>[...]]`. Policy w importerze: same source → update jak dziś; inny source + preferowany ma listing → draft + meta `_asiaauto_duplicate_of`; inny source + preferowany NIE ma → publish normalny.

**Kompatybilność wsteczna:** Phase 1 wdraża SAM Source Manager wokół istniejącego dongchedi (bez Che168). Wynik bit-for-bit identyczny ze stanem dzisiejszym (regression tests, sekcja #10). Phase 2 włącza Che168 jako drugi entry w Registry.

---

## 2. Recon — Phase 0 (executed 2026-06-01)

**Cel:** zamienić wszystkie `TBD post-recon` na konkrety + wykryć blockery techniczne.

**Co recon ustala (9 pytań):**
1. Schemat pól `getOffer` Che168 — mapping 1:1 do dongchedi (`mark`, `model`, `year`, `price`, `city`, `km_age`, `images`, `complectation`, `description`).
2. Schemat `getChanges` — istnieje? same event-types (added/changed/removed)? `change_id` monotonic? **Jeśli nie ma → blocker, alternatywna architektura.**
3. Lista miast — overlap z naszą listą 31 south Chinese.
4. Lista marek — overlap z naszą listą 66 + identyfikacja nowych bez mappingu CN→EU.
5. Rozkład cen / lat / kilometrów — pasuje filtr dongchedi-style czy Che168 to inny rynek.
6. Image pipeline — `x-expires` w URL-ach? CDN-y `byteimg.com`? Format danych.
7. `inner_id` collision check z dongchedi (compound key potrzebny?).
8. Rate limits + retry-logic kompat.
9. Kandydatury pól do dedup + sugerowane tolerancje.

**Metodologia:** read-only skrypt `tmp/che168-recon-2026-06-01.php` używający `new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL)`. getOffers strony [1, 10, 30, 60] (~80 ofert), getOffer dla 5-10 wybranych iid, getChanges od cid=0 dla 2-3 batchy. Próbki surowe `tmp/che168-recon-samples-*.json`.

**Deliverable:** `docs/superpowers/specs/2026-06-01-che168-recon-report.md` z odpowiedziami na 9 pytań + lista blockerów + rekomendacja filter + rekomendacja pól dedup.

**Kryterium zakończenia:** wszystkie TBD w sekcjach #5, #6, #11 wypełnione konkretami albo flagged jako blocker.

---

## 3. Components

**Nowe klasy:**

| Klasa | Public methods | Odpowiedzialność |
|---|---|---|
| `AsiaAuto_Source_Registry` | `getActiveSources()`, `getConfig($source)`, `isEnabled($source)` | Lista aktywnych źródeł + ich config |
| `AsiaAuto_Source_Manager` | `syncAll()`, `syncOne($source)`, `getStatus($source)` | Pojedynczy entrypoint syncu |
| `AsiaAuto_Dedup_Service` | `findDuplicate(array $data, string $source): ?array` | Lookup duplikatów cross-source |
| `AsiaAuto_Admin_Sources_Page` | render + handlers AJAX | Karta admin "AsiaAuto → Sources" |

**Refactor istniejących:**

- `AsiaAuto_Sync::run($source)` — sygnatura bez zmian, ale wywoływane wyłącznie z Source Managera (cron, CLI, admin → SM → Sync).
- `AsiaAuto_Importer::importListing($data, $source, $force)` — dodaje `$this->dedupService->findDuplicate()` PRZED `wp_insert_post`. Trzy ścieżki decyzyjne (pass / mark-duplicate / update-existing).
- `AsiaAuto_Admin_Manual_Import` — defaults `'dongchedi'` zmienione na `Registry::getActiveSources()[0]`. Select w UI gdy >1 source aktywny.
- Cron hook `asiaauto_sync_changes` — handler zmienia się z `Sync::run('dongchedi')` na `SourceManager::syncAll()`.

---

## 4. Data flow

```
cron / CLI / admin button
    ↓
AsiaAuto_Source_Manager::syncAll()
    ↓ (iteracja po Registry::getActiveSources())
AsiaAuto_Sync::run('che168')         AsiaAuto_Sync::run('dongchedi')
    ↓                                     ↓
API::getChanges('che168', cid)       API::getChanges('dongchedi', cid)
    ↓ event = 'added'                    ↓
API::getOffer('che168', iid)         API::getOffer('dongchedi', iid)
    ↓                                     ↓
Importer::importListing($data, $source)
    ↓
isAllowedByConfig($data, $source)   [per-source filter]
    ↓ pass
Dedup_Service::findDuplicate($data, $source)
    ↓
   ┌─ null match → wp_insert_post (publish)
   ├─ match same source → updateListing (jak dziś)
   └─ match other source + preferred=dongchedi obecny → draft + meta _asiaauto_duplicate_of=<post_id>,
                                                        total_skipped++ reason=duplicate-other-source
```

---

## 5. Dedup strategy

> **⚠ REWIZJA 2026-06-05 (test empiryczny):** overlap EGZEMPLARZY zmierzony na 30 ostatnich dongchedi × Che168 = **0 realnych duplikatów** (3 kandydatów = false positive, rozróżnione miastem). 70% naszych świeżych aut Che168 nie ma nawet modelu. **Dedup cross-source jest marginalny — Che168 to w przeważającej części unikalny katalog, nie lustro dongchedi.** Wniosek: dedup minimalny (VIN wewnątrz-Che + lekki guard z MIASTEM jako warunkiem wymaganym), bez zaawansowanej heurystyki. Resolver marka/model (5A.3) zostaje, ale jako narzędzie **taksonomii przy imporcie**, nie dedup. Pełny test: `2026-06-05-che168-vs-dongchedi-field-analysis.md` sekcja 1F.

**Strategia hybrydowa po reconie:**

1. **Wewnątrz Che168 → VIN exact match.** Che168 ma `vin` w 100% próbki, walidowalny (np. `LS5A2ABE7DA214395`). Dedup w obrębie Che168 jest deterministyczny — żadnych heurystyk.
2. **Cross-source Che168 ↔ dongchedi → heurystyka** (dongchedi nie ma VIN):

| Pole | Match type | Notatka |
|---|---|---|
| `mark` | exact | pole `mark` Che168 jest EN i pewne (Zeekr/Denza/Hongqi) |
| `model` | **po resolverze, NIE exact string** | ⚠ format `model` Che168 niespójny per marka (`001` vs `ZEEKR 001` dongchedi; `腾势N8L` CN). Porównanie po **znormalizowanym serie_eu** (patrz 5A), nie na surowym stringu |
| **`wheelbase` (rozstaw osi)** | **exact** | ⭐ najpewniejszy dyskryminator — konstrukcyjny, stały w obrębie modelu (zweryfikowane: N8L/D9/001 rozstaw identyczny cross-source) |
| `width` (szerokość) | exact | stabilna cross-source |
| `length`/`height` | tolerancja `±20 mm` | drgają między rocznikami/wariantami (Zeekr 001 2023 vs 2024: −7/+15 mm) |
| `year` | exact | rok modelowy z API |
| `km_age` | tolerancja `±5%` | **tie-breaker, NIE rdzeń** — różne egzemplarze mają różny przebieg |
| `price` | tolerancja `±10%` CNY | tie-breaker, nie rdzeń |
| **`city` (z `address` Che168 split)** | **WYMAGANY** | ⭐ zmieniony z „pomocniczy" na wymagany — test 1F: 3 false-positive miały identyczne wymiary+zbliżone km/price, rozróżniło je TYLKO miasto. Różne miasto = różny egzemplarz |

**Rdzeń dopasowania (rekomendacja po analizie 2026-06-05):** `mark + serie_eu(znormalizowany) + year + rozstaw osi(exact) + szerokość(exact)`. km/price jako tie-breaker, nie podstawa — bo to różne egzemplarze tego samego modelu. Pełne uzasadnienie: `2026-06-05-che168-vs-dongchedi-field-analysis.md` (sekcje 1B/1D/1E).

**Preferred source** = `dongchedi` (stabilniejsze, większa historia, znane mapowanie). Konfigurowalne via `asiaauto_source_priority`.

**Action gdy match cross-source:**
- Che168 listing → status `draft`
- Meta: `_asiaauto_duplicate_of=<dongchedi_post_id>`, `_asiaauto_dedup_matched_fields=mark,model,year,km_age`
- `total_skipped++` reason `duplicate-other-source` w sync history
- Listing NIE pojawia się publicznie, ale jest w bazie do audytu / przywrócenia gdyby dongchedi listing zniknął

**Recovery scenario:** jeśli dongchedi listing zostanie usunięty (`removed` event), Dedup_Service znajduje orphan duplicates → przesuwa Che168 listing z draft → publish (automatic promotion). Implementacja w `AsiaAuto_Rotation::markRemoved()` — sprawdza `_asiaauto_duplicate_of` w innych listingach przed kasacją.

**Monitoring:** option `asiaauto_dedup_stats` aktualizowane co sync per source: `{che168: {duplicates: N, unique: M, ratio: 0.X, last_24h: {...}}}`. Wyświetlane na Sources page (sekcja #7).

---

## 5A. Translation adapter + brand/model resolver dla Che168 (z analizy field-level 2026-06-05)

**Źródło:** `2026-06-05-che168-vs-dongchedi-field-analysis.md` — rozkład 4 marek (Denza N8L, Denza D9, Hongqi, Zeekr) + weryfikacja krzyżowa 3 warstw (surowe Che168 ↔ surowe dongchedi ↔ nasza baza po obróbce).

### 5A.1. Co reużywamy (warstwa wartości — TANIA)

Słowniki w `data/` zbudowane dla dongchedi pokrywają Che168 w ~70%:
- ✅ `translations-extra-prep-values.php` (216) — wartości CN spec wspólne dla obu źródeł (`标配`, `涡轮增压`, `磷酸铁锂电池`, `插电式混合动力`…). Douzupełnić ogon (część zawieszeń).
- ✅ `translations-complectations.php` (244) — wersje z `车型名称` Che168 (`尊荣型→Premium`, `旗舰型→Flagship`).
- ✅ pola top-level Che168 są EN (`plug-in hybrid`, `awd`, `crossover/suv`) → translacja zbędna.
- ❌ `translations-extra-prep.php` LABELS (1019) — NIE reuse: zbudowane dla **kluczy dongchedi** (`jb`,`length`…). Che168 ma chińskie nazwy parametrów **+ stabilne numeryczne `id`**.

### 5A.2. Adapter specyfikacji Che168 → mapuj po `id`, nie po nazwie CN

Che168 `extra.configuration.paramtypeitems[].paramitems[]` mają stabilne `id` (28=długość, 31=rozstaw, 91=segment, 90=typ energii…). Adapter = jednorazowa tabela **`che168_param_id → label PL`** (~50–100 ID) + reuse `translations-extra-prep-values` dla wartości. Opcjonalnie: grupy Che168 (`车身`/`发动机`/`电动机`…) → nasze `categories`. **Liczba grup zależy od napędu:** ICE+EV = 7 grup, czysty EV = 6 (brak `发动机`).

⚠ Strata vs dongchedi: Che168 ma ~92–109 param spec, dongchedi `extra_prep` = 361–405 pól (4× więcej wyposażenia: multimedia/asystenci/fotele). **Karty Che168 będą uboższe w sekcji wyposażenia.** Zysk Che168: VIN + `first_registration` (100%) + wydzielona grupa EV.

### 5A.3. Resolver marka+model = INDEKS ODWROTNY istniejącego brand-mappingu

**Kluczowe ustalenie (1E):** surowy dongchedi daje `model="ZEEKR 001"` (= klucz mappingu, OK), ale Che168 daje `model="001"` — co pokrywa się z naszym **`serie_eu="001"`**. Więc resolver = odwrócenie `brand-mapping-v6.1` po `(mark_eu, serie_eu)` i `(mark_eu, slug)`, NIE budowa nowego mappingu.

Normalizacja `model` Che168 przed lookupem (reguły, w kolejności):
1. **CN-prefix marki** strip: `腾势→Denza`, `极氪→Zeekr`, `红旗→Hongqi`, `深蓝→Deepal`, `理想→Li Auto`, `问界→AITO` (+ ustaw mark jeśli pusty).
2. **strip marki EN** z modelu: `"Denza D9"→"D9"`, `"Hongqi H5"→"H5"`.
3. **wariant napędu z `engine_type`** dla marek z serie rozbitym per napęd (Denza `D9 DM-i` vs `D9 EV`): Che168 `model="Denza D9"` bez napędu → `plug-in hybrid`→`DM-i`, `electric`→`EV`. **Bez tego Denza D9/Z9/N9 = miss** (zweryfikowane).
4. **format**: CAPS-insensitive, normalizacja spacji (`007GT`→`007 GT`).
5. **fallback**: dopasowanie po wymiarach (rozstaw osi) gdy niejednoznaczne.

**Pokrycie zmierzone (prototyp):** główny trzon każdej marki działa (Zeekr 100%, Hongqi główne, Denza N7/N8L). Przypadki brzegowe wymagające reguł 1–4: Denza serie per-napęd, formy CN świeżych premier, sub-brandy CN spoza mappingu (Hongqi `金葵花国耀`).

### 5A.3a. Walidacja prototypu na realnych numerach Che168 (2026-06-05)

Cel praktyczny (Janek): **ręczny import numeru Che168 → auto wskakuje we właściwy hub z właściwą nazwą.** Prototyp `tmp/resolver-proto-2026-06-05.php` (read-only) na żywych ofertach:

| Numer | surowy `model` | DZIŚ `getEuForCn` | resolver → hub |
|---|---|---|---|
| 57877387 | `腾势N8L` | ❌ NULL (orphan) | ✅ `/samochody/denza/n8l/` |
| 57888520 | `Denza D9` | ❌ NULL (orphan) | ✅ `/samochody/denza/d9-dm-i/` (z `engine_type`) |
| 55765635 | `001` | ❌ NULL (orphan) | ✅ `/samochody/zeekr/001/` |
| 57946822 | `Avatr 12` | ✅ OK (przypadkiem) | ✅ `/samochody/avatr/12/` |
| 55903341 | `红旗金葵花国耀` | ❌ NULL | ⚠ douzupełnić mapping (1 wpis) |

**Wniosek:** dziś ręczny import Che168 produkuje orphany (zła nazwa, brak huba) dla większości marek — pokrycie obecnego exact-key jest przypadkowe (Avatr trafia, Denza/Zeekr nie). Resolver naprawia 4/5 automatycznie. To jest **realny use-case napędzający implementację** — niezależny od dedup.

**Implementacja (do zrobienia, dotyka produkcji — wymaga zgody):**
1. `AsiaAuto_Mapping::resolveForSource($mark, $model, $engine, $source)` — dla `che168` reverse-index + reguły 1–4, fallback na obecne `getEuForCn`. Dla `dongchedi` bez zmian (exact key).
2. `class-asiaauto-admin-manual-import.php` — przyjąć `che168.com/...` URL + `source=che168` (dziś hardcoded dongchedi: linie ~413/520/598), wołać resolver.
3. Douzupełnić `brand-mapping`/`translations-models` o ogon CN (sub-brandy luksusowe).

### 5A.4. Co dorobić — podsumowanie kosztu

| Artefakt | Koszt | Uwaga |
|---|---|---|
| Indeks odwrotny brand-mappingu | mały | runtime z istniejącego pliku |
| Reguły normalizacji modelu (1–4) | średni | ~3 reguły + lista CN-prefiksów marek |
| Douzupełnienie `translations-models` o formy CN Che168 | mały | `极氪*`, `金葵花国耀`, `腾势*` bez ` DM` |
| Tabela `che168_param_id → label PL` (~50–100) | średni | jednorazowa |
| Douzupełnienie `extra-prep-values` (zawieszenia) | mały | |

**Warstwa wartości tania (reuse), warstwa marka+model to główny koszt** — ale dzięki reverse-index istotnie mniejszy niż budowa mappingu od zera.

---

## 6. Per-source config

**Rozszerzenie `asiaauto_import_config`:**

```json
{
  "dongchedi": {
    "enabled": true,
    "marks": [...],
    "year_from": 2024,
    "year_to": null,
    "km_from": null,
    "km_to": 50000,
    "price_from": 70000,
    "price_to": null,
    "city_filter_enabled": true,
    "city_filter_cities": [...]
  },
  "che168": {
    "enabled": false,
    "marks": ["Mercedes-Benz", "BMW", "Audi", "Porsche", "Lexus", "Land Rover", "Tesla", "MINI", "Toyota", "Bentley", "Honda", "Roewe", "Rising Auto", "Venucia"],
    "year_from": 2020,
    "year_to": null,
    "km_from": null,
    "km_to": 100000,
    "price_from": 100000,
    "price_to": null,
    "city_filter_enabled": false,
    "city_filter_cities": []
  }
}
```

**Pola `che168.*` — propozycja Wariant B (premium używane Chiny)** na podstawie reconu, do akceptu Ruslana. Trzy warianty z raportu reconu sekcja „Rekomendacje konkretne":

- **A** — klon dongchedi (year≥2024, km≤50k, price≥70k, south cities, lista 66 marek). Pokrycie <1% → testowy tylko.
- **B** ⭐ — premium używane Chiny: marki zachodnie + chińskie używane, year≥2020, km≤100k, price≥100k CNY, bez city filter. Sensowny ALE ZMIENIA segment klienta.
- **C** — lekki ekspand: dongchedi marks + nowe chińskie z Che168 (Roewe, Venucia, Rising Auto), bez city filter. Mały zysk.

**Nowe opcje:**
- `asiaauto_source_priority = ["dongchedi", "che168"]` — dla dedup preferred.
- `asiaauto_dedup_config = { fields: [...], tolerances: {...} }` — TBD post-recon.

**wp-config constants:**
- `define('ASIAAUTO_SOURCES', ['dongchedi', 'che168'])` — opcjonalna whitelist na poziomie pluginu (gdy chcemy szybko wyłączyć Che168 całkowicie przez deploy).

---

## 7. Admin UI

**Nowa strona „AsiaAuto → Sources":**

| source | enabled | last_change_id | last_sync (ago) | added 24h | changed 24h | duplicates 24h | actions |
|---|---|---|---|---|---|---|---|
| dongchedi | ☑ | 3810934 | 5 min | 0 | 7 | — | [Sync now] [Edit filter] |
| che168 | ☐ | — | — | — | — | — | [Edit filter] |

Toggle enabled → flip `asiaauto_import_config.<source>.enabled` + flush Registry cache.

„Edit filter" → otwiera istniejącą stronę config filter z preselected source w URL `?source=che168`.

**v2 (TBD jeśli potrzebne):** wykres overlap rate w czasie (linia chart.js z `asiaauto_dedup_stats.history`).

**Manual import UI:**

W `class-asiaauto-admin-manual-import.php` — gdy `Registry::getActiveSources()` zwraca >1, pokaż select „Source" w formularzu. Default = pierwszy z aktywnych (preserve obecne UX dongchedi gdy Che168 disabled).

---

## 8. Migration / rollout phases

| Phase | Zakres | Deliverable | Tag git | Trigger |
|---|---|---|---|---|
| **0 — Recon** | probe API Che168 + raport | `che168-recon-report.md` + TBD wypełnione | `che168-phase-0-recon` | wykonane 2026-06-01 |
| **1 — Source Manager refactor (BEZ Che168)** | 3 nowe klasy, cron przez SM, ale Registry zwraca tylko `[dongchedi]` | identyczny behavior dongchedi + testy regresji | `che168-phase-1-sm` | po decyzji „idziemy w Che168" |
| **2 — Che168 stub** | `[che168]` w Registry z `enabled=false`, cron job, CLI `wp asiaauto sync --source=che168 --dry-run` | dry-run pokazuje sensowne dane | `che168-phase-2-stub` | po Phase 1 stable 24h |
| **3 — Dedup + UI + go-live** | Dedup_Service, Admin Sources Page, `enabled=true` w config, monitoring 7 dni | overlap rate stable, no regression | `che168-phase-3-live` | po Phase 2 stub OK |

Każda Phase = własny git tag. Łatwy rollback przez `git revert <merge-commit>` na PHP plikach.

---

## 9. Error handling

- **Per-source isolation:** advisory lock `GET_LOCK('asiaauto_sync_<source>', 0)` — niezależne locki. Jeden source padł ≠ drugi też.
- **Per-source change_id:** option `asiaauto_last_change_id_<source>` — niezależne kursory.
- **Per-source sync history:** option `asiaauto_sync_history_<source>` (lub jedna globalna z polem source). Niezależne metryki.
- **API errors:** istniejąca `AsiaAuto_API::retry()` działa per-call → per-source automatycznie. Logger zapisuje context (`getChanges(che168, cid)`).
- **Dedup errors:** jeśli `findDuplicate()` rzuca exception → log warning, traktuj jako null-match (publish jako nowy). Lepiej duplikat niż utrata oferty.
- **Source Manager errors:** jeśli `Sync::run('che168')` rzuca → log + continue z `dongchedi` (one source down ≠ stop everything).

---

## 10. Testing strategy

**Phase 1 regression (krytyczne przed mergem):**

Skrypt `wp asiaauto regression-compare --hours=24` porównuje refactored branch vs main:
- Identyczne counts (added/changed/removed/skipped) per godzina
- Identyczne progress change_id
- Identyczne posty publish — sha256 hash treści (post_title + meta `_asiaauto_*` JSON-serialized)
- Identyczne attachments count per listing

Threshold: 0 różnic. Jakakolwiek różnica → block merge, debug.

**Phase 2 smoke Che168:**

`wp asiaauto sync --source=che168 --dry-run --limit=20`:
- API odpowiada, schemat danych zgodny z reconem
- `isAllowedByConfig` filtruje wg planu (ile pass, ile reject)
- `findDuplicate` wykrywa overlapy z dongchedi (na sample 20 powinien być 0-30% match, do walidacji)

**Phase 3 dedup correctness:**

Ręczny audyt 30 listingów oznaczonych `_asiaauto_duplicate_of`:
- Czy faktycznie ten sam samochód (zdjęcia, opis, VIN jeśli jest)
- False positive rate <5%
- False negative — sample 30 par (mark+model+year) z obu źródeł, sprawdzić czy Dedup_Service je łapie

**Image pipeline test:**

`wp eval-file tmp/che168-image-test.php` — download 5 obrazków z Che168 URL-i → save do `uploads/asiaauto/test/` → verify exist + non-zero size + W1 preflight nie odrzuca przedwcześnie.

---

## 11. Open questions / status post-recon

| # | Pytanie | Status | Wynik / decyzja |
|---|---|---|---|
| 1 | Schemat pól `getOffer` Che168 | ✅ DONE | 34 pola, 1:1 mapping do dongchedi + bonus vin/first_registration |
| 2 | `getChanges` schemat | ✅ DONE | identyczny model added/changed/removed + monotonic change_id |
| 3 | Miasta — overlap z south Chinese 31 | ✅ DONE | 26% próbki (Guangzhou, Dongguan, Shenzhen obecne; reszta Chin dominuje) |
| 4 | Marki — overlap z naszą 66 + nowe | ✅ DONE | tylko 5/22 (23%) overlap; dominują zachodnie premium używane |
| 5 | Rozkład cen/lat/km | ✅ DONE | mediana 2017/84k km/139k CNY — inny segment niż dongchedi |
| 6 | Image URLs — x-expires / CDN | ✅ DONE | PERMANENT, host `2sc2.autoimg.cn`, brak `x-expires` — prostsze |
| 7 | `inner_id` collision z dongchedi | ✅ DONE | Che168 43-56M, dongchedi 14-24M, brak kolizji dziś; compound key defensywnie |
| 8 | Rate limits + retry-logic kompat | ✅ DONE | bez throttle przy ~50 wywołań/120s; istniejąca retry-logic OK |
| 9 | Pola dedup + tolerancje | ✅ DONE | VIN dla wewnątrz-Che168; heurystyka mark+model+year+km(±5%)+price(±10%) cross-source |
| 10 | Ruslan: filter Che168 | ⚠ **DECISION-NEEDED** | rekomendacja Wariant B (premium używane Chiny) lub C (nie wdrażać) |
| 11 | Klient-facing badge „from Che168" | ⚠ DECISION-NEEDED | założenie domyślne: transparent (bez badge'a) |
| 12 | **Czy w ogóle wdrażamy Che168** | ⚠ **CORE BLOCKER** | obecny filtr daje <1% pokrycia. Wdrożenie sensowne TYLKO przy zmianie segmentu klienta. Patrz sekcja 0.1. |

**Dodatkowe wnioski techniczne (nie były pytaniami, wyszły z reconu):**

| # | Odkrycie | Konsekwencja w specu |
|---|---|---|
| 13 | `getOffers` Che168 zwraca event-feed format | drobny refactor `class-asiaauto-admin-manual-import.php` — dla Che168 bulk discovery wymaga 2 wywołań (getOffers iid → getOffer pełne) |
| 14 | Che168 ma `vin` i `first_registration` 100% | nowe meta `_asiaauto_vin` i `_asiaauto_first_registration`, eliminują obecny workaround dongchedi (`reference_dongchedi_year_fields`) |
| 15 | `address` zamiast `city` | parser w importerze: `explode(', ', $address)[0]` jako city dla city_filter |

---

## 12. Risk register

| Ryzyko | Prawd. | Wpływ | Mitygacja |
|---|---|---|---|
| `getChanges` Che168 nie istnieje | ⚠ Średnie | Krytyczny | Alternatywna architektura: pull pełnej listy + local diff. Nowa iteracja specu. |
| Phase 1 refactor uszkadza dongchedi sync | ⚠ Niskie-średnie | Krytyczny | Regression test 24h, tag git, rollback ready |
| `inner_id` Che168 koliduje z dongchedi | ⚠ Niskie | Wysoki | Compound key `(source, inner_id)` w `findByInnerId()`. Refactor +1 dzień. |
| Dedup heurystyka ma >10% false positive | ⚠ Średnie | Wysoki | Phase 3 audyt ręczny + tolerancje konserwatywne na start |
| Che168 image CDN bez `x-expires` ale wymaga session/auth | ⚠ Niskie | Średni | Recon to wykryje. Mitygacja: cache URL → download natychmiast. |
| Filtr Che168 zbyt szeroki → zalanie bazy draftami przez dedup | ⚠ Średnie | Średni | `enabled=false` start, włączenie z monitoringiem 7 dni przed pełnym go-live |
| Ruslan nie chce mixed catalog (brand positioning) | ⚠ Niskie | Wysoki | Decision gate przed Phase 3. Spec gotowy → łatwo wstrzymać. |
| **Che168 vs obecna strategia: pokrycie filtru dongchedi-style <1%** | ⚠ **WYSOKIE** (potwierdzone reconem) | **Krytyczny — blocker biznesowy** | Wariant B (zmiana segmentu na premium używane Chiny) lub Wariant C (nie wdrażamy, czekamy na dongchedi). Decyzja PRZED writing-plans. Patrz sekcja 0.1. |

---

## 13. Acceptance criteria (gdy realizujemy)

Po Phase 3:
- dongchedi sync działa bez zmian (regression 0 różnic)
- Che168 sync dostarcza N nowych listingów / dzień (N TBD po reconie + tygodniu monitoringu)
- Dedup rate raportowany na Sources page
- False positive rate dedup <5% (audyt 30 listingów)
- Admin Ruslana może toggle source on/off bez wsparcia dev
- 7 dni stable monitoring → go-live announcement

---

## 14. Powiązane dokumenty

- `~/.claude/projects/-home-host476470-projekty-primaauto/memory/project_sync_feed_added_stopped_2026_05_25.md` — diagnoza awarii dongchedi (root cause + playbook)
- `~/.claude/projects/-home-host476470-projekty-primaauto/memory/reference_dongchedi_api_quirks.md` — quirks API auto-api.com
- `docs/superpowers/specs/2026-06-01-che168-recon-report.md` — raport reconu (Phase 0)
- `~/projekty/primaauto/CLAUDE.md` — kontekst projektu primaauto
