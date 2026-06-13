# Spec-Hub Rework — runbook / handoff (2026-06-13)

> **Cel:** każdy hub modelu (`taxonomy=serie`) ma rankować na frazy parametryczne (spalanie, wymiary, przyspieszenie, zasięg, bateria, dane techniczne, 0-100, „kiedy w Polsce") — dla wyszukiwarek **i** LLM-ów — przez jednolitą, weryfikowalną tabelę spec + `Car`/`Vehicle` schema + blok llms, generowane **z danych Dongchedi**, nie z zamrożonego tekstu LLM.
>
> **Status na 2026-06-13:** Faza 1 + 1b **DONE** (generator dry-run zahartowany, 53/60 modeli czystych). **KROK 1 / kontaminacja taksonomii (T-158) — DONE** (2026-06-13, patrz §4). Faza 2 (template, produkcja) **NIE rozpoczęta** — strefa krucha, czeka na świadomy start + go Janka.

---

## 1. Dlaczego (diagnoza z tej sesji)

- Pomiar 20 hubów (GSC + DataForSEO, `tmp/seo-param-rank-2026-06-13.*`): rankujemy **dobrze na „cena" i „kiedy w Polsce"** (poz 5-6, kliki — rework 30.05 działa), ale techniczny długi ogon (spalanie/wymiary/0-100/zasięg) łapiemy marginalnie (poz 8-11, **0 klików**).
- **DataForSEO ślepy na nasz long-tail** — zna 29 KW na całą domenę. Do pomiaru „czy rankujemy" ufamy **tylko GSC**.
- Treść techniczna **istnieje** (tabela + keyword-rich H2), ale była **zamrożonym HTML-em od LLM w `asiaauto_wiki_body`** → 133 różne etykiety, literówki diakrytyczne, brak schematu, 44% hubów bez tabeli, zero weryfikowalności.
- **Źródło prawdy istnieje:** `_asiaauto_extra_prep` (324 pola per listing, **99,6% pokrycia** — 4654/4673), angielskie klucze, + `data/translations-extra-prep.php` (1019 mapowań PL: labels/values/units/categories/skip) + agregacja `class-asiaauto-rest-hub.php::aggregateListings()` (mediana+zakresy). Single `/oferta/` już to renderuje.

**Wniosek:** nie brakuje treści ani danych — brakuje **spięcia istniejących danych w jednolity render na hubie modelu**. Najważniejsza dźwignia SEO: etykiety wersji = frazy longtail (np. „Qin L 210 km").

---

## 2. Architektura rozwiązania

**Źródło:** `_asiaauto_extra_prep` agregowane per model (wszystkie listingi huba).

**Klastrowanie wersji** (bo w jednym modelu są różne wersje/baterie/moce):
- Sygnatura = `(bateria zaokrąglona do 5 kWh, napęd)`; ICE bez baterii = `(moc/50, napęd)`.
- Cap: max 4 wersje + reszta → „Inne warianty".
- **Filtr mild-hybrid:** bateria <5 kWh = MHEV/48V → traktuj jak ICE (nie EV).

**Rozdział parametrów:**
- **Wspólne dla modelu:** nadwozie, wymiary (dł.×szer.×wys.), rozstaw osi, miejsca, drzwi, bagażnik.
- **Per-wersja:** napęd, moc (KM), moment, bateria (kWh), **zasięg elektryczny** + **zasięg całkowity** (osobne wiersze! nie mieszać EV-only z combined), 0-100, Vmax, spalanie, silnik, skrzynia.

**Etykiety wersji = atrybut, który je RÓŻNICUJE** (kluczowe dla longtail):
- trim distinct (Standard/Pro/Max/Ultra) → użyj trim;
- inaczej: PHEV/EREV → „{zasięg EV} km"; pure EV → „{bateria} kWh"; ICE → „{poj}T {moc} KM"; fallback napęd.

**Trzy wyjścia z jednej struktury:**
1. Tabela HTML — etykiety = frazy z query (Wymiary / Zasięg / Spalanie / Przyspieszenie 0-100 km/h / Pojemność baterii / Pojemność bagażnika).
2. `Car`/`Vehicle` JSON-LD — `additionalProperty` (wymiary/zasięg/bateria/0-100), `vehicleConfiguration` = lista wersji.
3. Blok llms — per-wersja („SU7 Max: 101 kWh, 800 km, 673 KM, 2,78 s").

**Moc systemowa PHEV/EREV:** bierz `electric_max_power` „640(870Ps)" → 870 KM (NIE `engine_max_horsepower` = sam spalinowy).

---

## 3. Faza 1b — wynik (DONE)

Dry-run: `wp-content/plugins/asiaauto-sync/diag/spec-hub-dryrun.php` (read-only, pisze tylko `/tmp/spec-dryrun.html`; `SAMPLE_N=60 wp eval-file ...`). Auto-dobiera top-N reworkowanych hubów wg liczby egzemplarzy, ma wbudowany **lint krytyki**.

**53/60 modeli czystych** (start 3/30). 5 fixów: etykiety wersji, dual-range, filtr mild-hybrid, tłumaczenie silnika+strip CJK, fallback spalania (`wltc_fuel_comprehensive`).

Pozostałe 7 flag = **realny sygnał** (patrz §4).

---

## 4. BLOKER przed batchem: kontaminacja taksonomii — ROZWIĄZANE (T-158, 2026-06-13)

Detektor `SAMPLE_N=228` → 204/227 czystych, 23 flagi. Analiza (REGEXP name+slug+post_title + obie taksonomie make/serie) rozbiła je na 3 grupy. **Kluczowe ustalenie: `make` był już POPRAWNY na każdym skażonym listingu — błędna była tylko `serie`** (zamrożone legacy sprzed naprawy mappingu importera; nowe importy lecą już dobrze → reassignment trwały).

### GRUPA A — obca marka pod termem (5 termów, NAPRAWIONE — 106 listingów przeniesionych)

| Term skażony (usunięty) | przeniesione | → keeper (serie) |
|---|---|---|
| 3401 Galaxy L7 (li-auto/l7) | 18× Geely Galaxy L7 / 4× Li Auto L7 | → 7153 geely (galaxy-l7, URL geely/l7) / 5739 li-auto/l7 |
| 3399 Galaxy L6 (li-auto/l6) | 15× Geely Galaxy L6 / 2× Li Auto L6 | → 7155 geely (galaxy-l6, URL geely/l6) / 5735 li-auto/l6 |
| 4812 WEY 07 (wey/07) | 9× Avatr 07 / 3× WEY 07 | → 6906 avatr/avatr-07 / 5388 wey/07 |
| 4409 Hongqi H5 (hongqi/h5) | 3× Haval H5 / 16× Hongqi H5 | → 6715 haval/haval-h5 / 5002 hongqi/h5 |
| 3372 AITO M8 (aito/m8) | 27× GAC M8 / 9× AITO M8 | → **3381** gac/m8 / 5302 aito/m8 |

Po: 5 pustych termów skażonych (3401/3399/4812/4409/3372) **usuniętych**. make_meta ustawione na 6906/6715. Smoke: 10/10 URL keeperów = 200, przeniesione oferty 200.

**GOTCHA GAC M8:** keeper to **3381 (gac/m8)**, NIE 6735 (trumpchi-m8) — `V62_SERIE_REDIRECTS` w `class-asiaauto-redirects.php:76` ma twardy `trumpchi-m8 → m8`, więc 6735 to slug-źródło-redirectu (auta byłyby niewidoczne). Backup bazy: `~/backups/primaauto/2026-06-13-spec-hub-taxonomy/wp521-pre-taxonomy-merge.sql`.

**GOTCHA parent=0 (regresja złapana przy smoke treści, nie HTTP):** szablon `themes/asiaauto/taxonomy-serie.php:19-24` rozwiązuje term po **`tt.parent`=make_term_id** (hierarchia), NIE po meta `_asiaauto_primary_make_slug`. 4 termy-cele (7153, 7155, 6906, 6715) miały `parent=0` (oderwane od marki) → po wrzuceniu listingów renderowały „Nie znaleziono modelu." mimo HTTP 200. Fix: `parent` ustawiony (geely/avatr/haval) + slug 7153/7155 `galaxy-l7/l6`→`l7/l6` (bezpośredni `$wpdb->update`, bo `wp_update_term` dodaje `-2`; kolizja slug per-parent jest OK — istniejący wzorzec: 3381 i 5302 oba slug `m8`). **WNIOSEK: weryfikuj RENDER (grep „Nie znaleziono"/H1), nie sam kod HTTP — 200 może być pustym szablonem.** Te 4 huby mają H1-fallback „— import z Chin" (brak reworku 30.05) → dostaną content w batchu. Drobiazg: gac/m8 (3381) H1 pokazuje „Xiangwang M8" (1 listing-kontaminant + stary tytuł) → do regen tytułu + przeniesienia 1 szt. do 6742.

### GRUPA B — mix generacji/wariantów (NIE kontaminacja, NIE ruszać taksonomii)

AITO M7 (2024 vs 2026 EREV, Δ210 rozstaw), GWM Cannon (krótka vs dubel kabina Pao, Δ240), NIO ES8, Geely Atlas Pro (Gen4), VW Teramont/Magotan, BAIC BJ40, XPeng P7, BYD Dolphin, Jetour T2/T2 C-DM — **jeden model, dwie generacje/nadwozia**. Tabela spec pokazuje uczciwy zakres. Detektor wciąż je flaguje (2 zostały: M7, Cannon) → **TODO Faza 2: podnieść próg LINT** (rozstaw 150→220 mm, długość 120→260 mm), żeby nie szumiał na faceliftach.

### GRUPA C — luka generatora/danych (NIE taksonomia, fix w Fazie 2)

3× nieprzetłumaczona skrzynia CJK: `6挡湿式双离合`=6-bieg. mokra DCT, `9挡双离合`=9-bieg. DCT (Jetour X70 PLUS, Haval Big Dog, Haval H6) → dodać mapowania do `data/translations-extra-prep.php`. 1× GWM Cannon King Kong diesel bez `fuel_comprehensive` (drobiazg).

### BONUS — dług taksonomiczny poza batchem (osobny T-019)

Kolizje slugów istnieją (kilka serie-termów slug `l7`/`l6`/`m8`, rozróżniane prefiksem make w URL). NIE blokuje batcha. Wart osobnego pełnego przejścia REGEXP po całej taksonomii `serie`.

---

## 5. Faza 2 — wdrożenie produkcyjne (NIE startować bez „go" Janka + backup)

Strefa krucha: template `taxonomy-serie.php` + treść hubów (`wiki_body`). Kroki:

1. **Wydzielić generator do klasy** `class-asiaauto-spec.php` (port logiki z dry-runu): `buildSpec($term_id)` → struktura (common + versions); `renderTable()`, `renderJsonLd()`, `buildLlmsBlock()`.
2. **`taxonomy-serie.php`** renderuje spec z `extra_prep` (zamiast echo tabeli z `wiki_body`), pod stałym H2 „Dane techniczne {model}".
3. **Usunąć zamrożoną tabelę** z `wiki_body` (surgical regex `<table>...</table>`), zostawić lead/FAQ/H2 narracyjne NIETKNIĘTE (one dają działający ranking cena/dostępność — patrz `project_session_2026_05_30_hub_rework_pilot`).
4. **Car/Vehicle JSON-LD** wstrzykiwane (uwaga: obecnie hub ma `Product`+`AggregateOffer`+`FAQPage` — dodać Car, nie dublować).
5. **Rebuild llms** z bloku spec (`scripts/build-llms.php` / `build-llms-full.php`).
6. **Dry-run na 1 hubie produkcyjnym** (before/after diff) przed bulk.

Zakres treści: **tylko tabela + schema + llms**; proza/lead/FAQ bez zmian (decyzja Janka 2026-06-13).

---

## 6. Batch — runbook wykonania (nowy wątek)

Wymagania Janka (2026-06-13):
- **Każdy hub od razu do Indexing API** po wgraniu spec.
- **Pauzy + test co 20 hubów** (smoke render + sprawdzenie, że spec się renderuje + ew. `gsc-inspect.py` status).
- Start dopiero po: (a) rozwiązaniu kontaminacji §4, (b) przejściu Fazy 2 i testach.

**TWARDE ograniczenie Indexing API** (globalny CLAUDE.md §10a): wspólna pula **200/dobę** na projekt GCP, ad-hoc budżet **100/dobę** przez `~/bin/index-submit` (hook blokuje surowe `curl`). Hub-termy NIE idą przez auto-hook publikacji listingów → wymagają **jawnego `index-submit`**. ~228 hubów → **rozłożyć na ≥3 dni** (lub uzgodnić wejście w rezerwę PrimaAuto). Przed każdym batchem: `index-submit --status`, pokazać Jankowi budżet przed/po.

**Pętla batcha (per hub):** zbuduj spec → wgraj render → smoke test → `index-submit --project primaauto --type URL_UPDATED --url <hub>`. Co 20: STOP, raport (ile done, budżet indexing, ew. błędy), czekaj/kontynuuj.

**Pomiar skuteczności:** powtórzyć `seo-param-rank` (GSC) ~14 dni po batchu; spięte z T-017.

---

## 7. Kluczowe pliki / artefakty

- Generator dry-run: `plugins/asiaauto-sync/diag/spec-hub-dryrun.php`
- Źródło danych: meta `_asiaauto_extra_prep` (per listing), `data/translations-extra-prep.php`
- Agregacja referencyjna: `class-asiaauto-rest-hub.php::aggregateListings()` (linie ~432-614)
- Render single (wzorzec): `class-asiaauto-single.php`
- Pomiar parametryczny: `tmp/seo-param-rank-2026-06-13.{py,json}` + raport `tmp/raport-param-rank-2026-06-13.md`
- Podglądy: `auratest.pl/fe4f58fec53ctmp/primaauto-spec-dryrun-{30,60}-2026-06-13.html`

## 8. Otwarte decyzje
- Kolejność: kontaminacja taksonomii **przed** Fazą 2 (ustalone — Janek), ale czy rozdzielać wszystkie 7 termów czy tylko twarde (L7/M7).
- Huby count=0 (bez stocku): fallback spec (ostatni snapshot) czy ukryć tabelę.
- Styl etykiety wersji ICE: silnikowy „2.0T 238 KM" (obecny) — do potwierdzenia na większej próbce.

## 9. Zasady (z CLAUDE.md / memory)
- Slugi `asiaauto-*` ZOSTAJĄ; rebrand tylko user-facing.
- `taxonomy-serie.php` / homepage = ZAWSZE PYTAJ przed Edit (`feedback_no_edit_homepage_without_ok`).
- Backup (`mysqldump` + `.bak`) przed zapisem produkcyjnym.
- Indexing przez `~/bin/index-submit`, NIGDY surowy curl (hook blokuje).
- Content przed Ads; native WP blocks > custom.
