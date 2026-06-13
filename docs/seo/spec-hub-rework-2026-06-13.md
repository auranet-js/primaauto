# Spec-Hub Rework — runbook / handoff (2026-06-13)

> **Cel:** każdy hub modelu (`taxonomy=serie`) ma rankować na frazy parametryczne (spalanie, wymiary, przyspieszenie, zasięg, bateria, dane techniczne, 0-100, „kiedy w Polsce") — dla wyszukiwarek **i** LLM-ów — przez jednolitą, weryfikowalną tabelę spec + `Car`/`Vehicle` schema + blok llms, generowane **z danych Dongchedi**, nie z zamrożonego tekstu LLM.
>
> **Status na 2026-06-13:** Faza 1 + 1b **DONE** (generator dry-run zahartowany, 53/60 modeli czystych). Faza 2 (produkcja) **NIE rozpoczęta** — czeka na (a) rozwiązanie kontaminacji taksonomii, (b) świadomy start w nowym, przetestowanym wątku.

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

## 4. BLOKER przed batchem: kontaminacja taksonomii

Lint wariancji rozstawu osi/długości wyłapał termy, gdzie pod jednym `serie` siedzą **różne modele/generacje** (tabela spec byłaby bełkotem):

| Term | Sygnał | Hipoteza |
|---|---|---|
| **Li Auto „Galaxy L7"** (slug `l7`) | rozstaw Δ220 mm, długość Δ350 mm | **Li Auto L7 + Geely Galaxy L7** pod jednym termem |
| **AITO M7** | rozstaw Δ210 mm | generacje / 6 vs 7 miejsc / mix |
| Jetour T2 | długość Δ249 mm | mix generacji |
| Geely Atlas Pro | długość Δ220 mm | mix generacji |
| NIO ES8 | długość Δ181 mm | mix generacji |
| AITO M8 (×2 termy) | długość Δ123 mm | borderline |

**Do zrobienia PRZED batchem:** przejrzeć te termy (REGEXP po name+slug+post_title, jak w `feedback_market_gap_check_thoroughly`), rozdzielić/scalić, zsynchronizować z T-019. Detektor: `SAMPLE_N=228 wp eval-file diag/spec-hub-dryrun.php` → sekcja KRYTYKA.

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
