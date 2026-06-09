# Analiza KW + recon hubów MAREK (answer-first lead rework) — 2026-06-08

> Track: rework hubów MAREK (`/samochody/<marka>/`), domknięcie wzorca answer-first lead z pilota modeli (2026-05-30).
> Status: **RECON + DANE GOTOWE, ZERO WDROŻEŃ.** Czeka na: deploy template (ZAWSZE PYTAJ) + akcept metody generacji.
> Powiązane: `hub-rework-method-2026-05-30.md`, memory `project_session_2026_05_30_hub_rework_pilot`, `project_session_2026_05_19_make_titles_pivot`.
> Źródła danych: `tmp/dfs-make-hubs-kw-2026-06-08.{py,json}`, `tmp/gsc-make-hubs-2026-06-08.{py,json}`.

## 1. Stan faktyczny (zweryfikowany na serwerze 2026-06-08)

- Aktywny theme: **`primaauto2026`** (`themes/asiaauto/` = martwy child, nie ruszać).
- **Mechanizm leada (wzorzec):** `taxonomy-serie.php:107-111` — `_asiaauto_lead` → `<p class="aa-hub__lead">` (esc_html), NAD `.aa-hub__stock`, po `</header>`. Render tylko gdy meta niepuste.
- **Hub marki (`taxonomy-make.php`) NIE renderuje leada.** H1 sztywne `<h1>{Make} z Chin</h1>` (linia 63) — **brak mechanizmu `_asiaauto_h1_suffix`** (modele go mają). Punkt wstawienia leada: między `</header>` (l.69) a `.aa-hub__stock` (l.71).
- **Pokrycie meta (make):** `_asiaauto_lead`=0, `_asiaauto_h1_suffix`=0, `_asiaauto_pl_availability`=0, `_asiaauto_seo_rework`=0. ALE `asiaauto_wiki_body`≈60 i `asiaauto_faq_json`≈62 (marki dostały kiedyś content n8n, nigdy reworku cena/lead — liczby w wiki bywają stale).
- **CSS:** `.aa-hub__lead` **nie ma żadnego stylu** w theme — lead renderuje się jako goły `<p>` (poza `.aa-hub__body`). Dotyczy też żywych hubów modeli. Decyzja 2026-06-08: **zostawić bez CSS** (mirror serie).
- **65 marek aktywnych** (count>0), 302 termów make ogółem. 5 marek aktywnych bez wiki: `beijing-off-road`(4), `dongfeng-fengxing`(3), `chery-fengyun`(3), `toyota`(1), `foton`(1).

## 2. DataForSEO — brand-level KW (location 2616, lang pl; koszt $0,018)

**Kluczowy wniosek: `{marka} import` i `{marka} z chin` = ~0 wolumenu** (tylko `volvo import` 50). Brand-level „import" nie ma popytu → H1/lead marek **nie** ścigają importu. Winnable intent = **`{marka} cena` (commercial)** + **`{marka} polska`** + bare brand (trudny, brand-defense SERP).

| Marka | bare | `cena` | `polska` | uwaga |
|---|---:|---:|---:|---|
| volkswagen | 301000 | 170 | 2900 | globalna — bare zdominowana przez EU VW |
| xiaomi | 165000 | 90 | 1900 | globalna (elektronika!) |
| byd | 135000 | **2900** | **5400** | najsilniejszy cena+polska |
| volvo | 110000 | 320 | 4400 | globalna |
| mg | 90500 | **1300** | 1900 | |
| mazda | 90500 | 210 | 1900 | globalna |
| nissan | 60500 | 110 | 880 | globalna |
| leapmotor | 33100 | 320 | 320 | |
| geely | 27100 | 480 | 1600 | |
| jetour | 27100 | **720** | 1000 | |
| baic | 27100 | 390 | 1000 | |
| hongqi | 27100 | 390 | 720 | |
| chery | 22200 | 140 | 3600 | |
| galaxy | 18100 | — | — | |
| xpeng | 14800 | 390 | 880 | |
| zeekr | 8100 | 140 | 720 | |
| nio | 6600 | 90 | 390 | |
| aion | 6600 | 40 | 40 | (gac-aion-hyper) |
| icar | 6600 | — | — | |
| tank | 6600 | — | — | |
| changan | 2900 | 40 | 390 | |
| voyah | 2900 | 90 | 140 | |
| haval | 1600 | 70 | 50 | |
| exeed | 1300 | 20 | 90 | |
| li-auto | 1000 | 40 | 70 | |
| gac | — | 170 | 880 | |
| gwm | 880 | 10 | 90 | |

(Marki count<12 oraz `{marka} import`/`z chin`≈0 pominięte. Pełne dane: JSON.)

## 3. GSC — co huby marek REALNIE zbierają (28d, 2026-05-08..06-04)

**48 hubów marek, 3650 imp / 96 klików.** Nasze realne PL (vs DFS=rynek).

| Hub | clk | imp | CTR | poz |
|---|---:|---:|---:|---:|
| li-auto | 21 | 832 | 2.5% | 6.3 |
| aito | 16 | 564 | 2.8% | 6.9 |
| byd | 15 | 544 | 2.8% | 10.3 |
| geely | 6 | 498 | 1.2% | 18.7 |
| changan | 5 | 209 | 2.4% | 16.5 |
| volkswagen | 5 | 133 | 3.8% | 8.8 |
| hongqi | 2 | 98 | 2.0% | 10.5 |
| xpeng | 1 | 73 | 1.4% | 13.9 |
| baic | 2 | 67 | 3.0% | 11.1 |
| hiphi | 3 | 65 | 4.6% | 4.5 |
| zeekr | 5 | 48 | 10.4% | 6.7 |

(reszta ogon ≤47 imp; pełne w JSON)

**Wzorce fraz (co hub marki łapie):**
- **`{marka} polska`** — silne i wysoko: „aito polska" 25/poz 4,8 (CTR 16%), „li auto polska" 14/poz 5,1.
- **`{marka} cena`** — realne, w tym model-spillover: „li auto l6 cena" 10, „aito cena" 5, „geely atlas cena" 4, „changan cs75 plus cena" 3.
- **`{marka} kiedy w Polsce`** — intent dostępności: „hongqi hs7 kiedy w polsce" **poz 1,0**.
- **Lokalne**: „geely rzeszów" 34/poz 8,5 (nasza lokalizacja!).
- **Globalne marki → `z chin`**: „volkswagen z chin" 7/poz 14,7 (różnicowanie od EU VW).
- **Model+cena spillover na hub marki**: hub byd łapie „byd leopard 3 cena" 8, „byd leopard 7 cena w polsce" 2 — hub marki absorbuje long-tail modeli.
- **`{marka} import` = ~0** (potwierdza DFS).

## 4. Wnioski strategiczne (data-driven)

1. **H1 marek: orientacja cena/Polska, NIE import.** Suffix typu „— ceny w Polsce i import z Chin" (cena z przodu intentu). Spójne z pivotem tytułów marek (`project_session_2026_05_19_make_titles_pivot`).
2. **Lead answer-first marki** odpowiada: ile modeli + ile ogłoszeń + zakres cen PLN + dostępność w PL (salon/import/„kiedy") + zaufanie (oględziny w Chinach, homologacja, gwarancja importera).
3. **Lead marki absorbuje intent `{marka} polska` + `{marka} cena` + `kiedy w Polsce`** — wszystkie realne w GSC.
4. **Globalne marki (VW/Volvo/Mazda/Nissan/MG/Xiaomi)** — lead/framing wokół „chińskie modele {marka} spoza europejskiej gamy" (intent `{marka} z chin`).

## 5. PRAWDZIWOŚĆ per marka — najtrudniejsza część (brand-level ≠ model-level)

Dostępność salonowa jest **per-model**, nie per-marka. Lead marki NIE może mówić „wyłącznie z importu" jeśli choć jeden model marki jest w salonie PL. Framework (do web-recheck per marka przy generacji):

- **(A) marka w ogóle nie w PL** → „import jako jedyna droga" PRAWDZIWE.
  Kandydaci: AITO, Avatr, Li Auto, NIO, Voyah, Luxeed, Hongqi, Nevo, WEY, GAC, IM Motors, Aion, Tank/GWM(*forward — wchodzą 2026), Denza(*forward), Zeekr(*forward H2 2026).
- **(B) marka w PL, my = import** → framing „marka jest w PL, ale szerszy wybór wersji / modele spoza oferty / cena z importu", BEZ kłamstwa.
  Kandydaci: BYD, Chery, Geely, Jetour, Leapmotor, Deepal, MG, Volvo, Mazda, Nissan, VW, Xiaomi(*?).
- (*) status „forward" (marka wchodzi 2026) — web-recheck świeży przy generacji, NIE z pamięci.

Reguły treści: bez nazw dealerów konkurencji (generycznie „salony {marka} Polska"), ASCII quotes w FAQ JSON, slugi `asiaauto-*` zostają.

## 6. Plan wdrożenia (decyzje Janka 2026-06-08)

- **Kolejność:** cała kolejka od razu (subagenty jak 06-06), nie pilot.
- **Zakres:** PEŁNY rework jak modele = lead + h1_suffix „+cena w Polsce" + odświeżenie wiki/FAQ (przeliczone liczby z DB, 7 H2 intent-led).
- **CSS:** bez zmian (lead jak serie).

### Decyzje rozszerzone (2026-06-08, w trakcie sesji)
- **Podrasowanie:** pełne = lead + **fact strip** (box pod H1) + wiki intent-led. Fact strip TYLKO na markach (nie na 228 modelach — decyzja „tylko marki teraz").
- **Fact strip = dane STORED** (meta `_asiaauto_facts` JSON), nie live z DB (Janek: odświeżamy reworkiem co jakiś czas, nie chcemy per-request agregacji cen). Darmowe dynamiczne: „Stan oferty: {rok}" via `date('Y')`, schema `dateModified`.
- **Świeżość:** roczniki w strip + „Stan: {rok}" + `dateModified`. **BEZ newsów/premier** (osobny projekt na później — ew. n8n news pipeline Faza 2).
- **Kontrakt `_asiaauto_facts`:** `{models,count,price_min,price_max,price_median,year_min,year_max,avail_label,updated}`. `_asiaauto_pl_availability` (enum import_only/salon_available) zostaje OSOBNO — steruje prawdziwością leada.

### KROK 1 — TEMPLATE WDROŻONY 2026-06-08 (v0.32.72) ✅
3 pliki, backupy `*.bak-2026-06-08`:
- `taxonomy-make.php`: H1 suffix (`_asiaauto_h1_suffix`, fallback „z Chin") + fact strip (`_asiaauto_facts`) + „Stan oferty: {rok}" + lead (`_asiaauto_lead`, mirror serie:107-111). Wszystko render-if-meta (brak meta = stare zachowanie).
- `assets/css/hub.css`: `.aa-hub__facts` (+ `-note`) — nowa klasa, nieobecna na serie → 0 regresji modeli. Lead bez CSS (jak serie).
- `class-asiaauto-seo.php`: **`dateModified` przez filtr `rank_math/json_ld` (`addHubDateModified`), NIE przez `buildCollectionPage`** — KLUCZOWE: gdy RankMath aktywny, `buildCollectionPage()` jest DEAD CODE (renderSchema emituje tylko ItemList+Product; CollectionPage/@graph robi RankMath). dateModified czytane z `_asiaauto_facts.updated`, wpinane w node CollectionPage/WebPage.
- **Smoke PASS:** baic (test facts) → H1 suffix+strip 6 pól+lead+dateModified 2026-06-08 OK; byd (bez facts) → „BYD z Chin", brak strip/lead/dateModified, HTTP 200, modele (leopard-5) nietknięte. Test meta na baic wyczyszczone.

### KROK 2 — generacja treści (NASTĘPNY, PYTAĆ przed batch — quota)
direct-Sonnet/subagenty z per-marka faktami z DB (modele=child serie count>0, count, min/max/median `price`, min/max `ca-year`) + web-recheck salon PL per marka (framework §5). Zapis: `_asiaauto_facts` JSON + `_asiaauto_lead` + `_asiaauto_h1_suffix` („— ceny w Polsce i import z Chin") + `_asiaauto_pl_availability` + odświeżone `asiaauto_wiki_body` (7 H2 intent-led, front-load cena/dostępność/proces, fix stale liczby, „kiedy w Polsce" gdzie naturalne) + `asiaauto_faq_json` (5Q FAQPage, ASCII quotes) + flaga `_asiaauto_seo_rework=v1-make-2026-06-08`. Backup termmeta przed. Meta klucze: cena=`price`, rok=`ca-year`.

### KROK 2 — GENERACJA TREŚCI WYKONANA 2026-06-08 (37 marek count≥12) ✅
Batch subagentów (5 fal × ~6-8), każdy: recon DB + web-recheck salon PL + generacja (lead/h1_suffix/pl_availability/avail_label/wiki 7H2/faq 5Q/facts) → `deploy-hub.php` (rozszerzony o `_asiaauto_facts`) → smoke. Backup termmeta `~/backups/primaauto/2026-06-08/termmeta-make-37-pre-rework.sql`. Pliki `tmp/make-rework/<slug>-{wiki.html,faq.json,deploy.json}`.
- **Flaga `_asiaauto_seo_rework=v1-make-2026-06-08` = 37.** Wszystkie leady z polskimi diakrytykami (zweryfikowane; 7 leadów wymagało poprawki ASCII→PL po fali 1-2, naprawione).
- **35 live & osiągalne** (HTTP 200, H1 „+ceny w Polsce", fact strip 6 pól, lead, dateModified, FAQPage parsuje, 0× wyciek `{{LISTINGS_BAR}}`).
- **2 BLOKOWANE redirectem (content wgrany ale URL 301, czekają na decyzję):** `galaxy` (6579) → 301 `/samochody/geely` (`'galaxy'=>'geely'` w V61, class-asiaauto-redirects.php) ; `gac-aion-hyper` (5485) → 301 `/samochody/gac` (`'gac-aion-hyper'=>'gac'` v6.1). Oba z konsolidacji marek 2026-04-23, gdy były pod-markami; dziś samodzielne termy z żywymi ogłoszeniami (16 każdy). Fix = usunięcie wpisu z mapy redirectów (krucha strefa, NIE ruszane) — decyzja taksonomiczna Janka. Wpisuje się w `taksonomia-destrukcyjna-plan-2026-06-06.md`.
- **Korekty data-driven subagentów** (web-recheck nadpisał hinty): hongqi import_only→**salon_available** (AADC, salon też w Rzeszowie), gac import_only z niuansem (Jameel ma Aion/Hyptec, ale nasze M8/Empow spoza gamy), voyah salon_available potwierdzony (Auto Fus Group — korekta pilota).
- **Klasyfikacja końcowa:** salon_available (BYD/Geely/VW/Chery/Jetour/Leapmotor/Nissan/MG/Volvo/Mazda/Deepal/XPeng/Voyah/Hongqi), import_only nie-w-PL (AITO/Li Auto/NIO/Avatr/Luxeed/iCAR/IM Motors/Xiaomi/Hongqi→nie/Exeed/Fulwin/Lynk&Co/BAIC), forward 2026 (Tank/GWM/Haval/WEY/Denza/Zeekr/Changan-debiut/Nevo).

### KROK 3 — INDEXING (częściowy 2026-06-08)
12 najmocniejszych wg GSC zgłoszone (`tmp/make-index-top12-2026-06-08.txt`): li-auto/aito/byd/geely/changan/volkswagen/hongqi/xpeng/baic/zeekr/wey/denza. Budżet ad-hoc 100/100 wyczerpany (doba Pacific 06-07). **Reszta 23** (`tmp/make-index-rest23-2026-06-08.txt`) — jutro na świeżym budżecie lub rezerwa za zgodą. galaxy+gac-aion-hyper NIE zgłaszane (301).

### POZOSTAŁE (długi ogon + decyzje)
- Marki count<12 (~28 marek, długi ogon 1-11 szt) — niezrobione, ewentualna kolejna masówka.
- 5 marek bez wiki (beijing-off-road/dongfeng-fengxing/chery-fengyun/toyota/foton) — count<12.
- 2 redirecty (galaxy, gac-aion-hyper) — decyzja taksonomiczna Janka.
- Pomiar GSC skuteczności ~2 tyg. (recrawl≠reindeks).

## 7. Skrypty reużywalne
- `tmp/dfs-make-hubs-kw-2026-06-08.py` — brand KW (keyword_overview, modyfikowalna lista marek/modyfikatorów).
- `tmp/gsc-make-hubs-2026-06-08.py` — agregat GSC hubów marek (regex `^/samochody/<slug>/$`) + top frazy per hub.
- Saldo DFS 2026-06-08: $44,74. OAuth GSC: `~/secrets/google/`.
