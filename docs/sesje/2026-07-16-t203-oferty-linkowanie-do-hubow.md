# Sesja 2026-07-16 — T-203: strona oferty, linkowanie do hubów (v0.33.23-26)

> Domknięcie wątku. Kod na produkcji, docs w repo (commity `2632fba`…`3310a32`).
> Poprzednie: `docs/seo/T-203-plan-strony-ofertowej-2026-07-14.md` (plan, zaktualizowany),
> `docs/seo/T-203-baseline-gsc-2026-07-13.md` (baseline — NIE nadpisywać, to punkt odniesienia).

---

## 1. Co weszło na produkcję

| wersja | co |
|---|---|
| **0.33.23** | `serieAnchor()` — anchor pełną nazwą modelu; breadcrumb kompakt na mobile; JSON-LD |
| **0.33.24** | link do hubu w **sticky navrow** (w linii z badge'ami); cena „z VAT" |
| **0.33.25** | kotwica `#oferty` na hubie; `scroll-margin-top`; **cache-bust themu 1.0.8→1.0.9** |
| **0.33.26** | target 44px; tytuł 2 linie; cena 22px; **jeden H1**; desktop back→hub |

Szczegóły techniczne: `docs/VERSIONS.md`. Backupy: `.bak-2026-07-16-*` (5 plików).

**Problem, który to rozwiązywało:** 3 056 ofert linkowało do hubów breadcrumbem ukrytym
`display:none` na mobile (= **79,6% ruchu**), a anchor był surowym `$serie->name` — czyli
**258 z 302 modeli / 2 908 ofert** linkowało do hubu napisem **bez marki** („8X", „M9", „N8L DM").
Hub walczy o „zeekr 8x", a dostawał od 3 000 własnych podstron link „8X".

**Zasada (decyzja Janka):** „zeekr 8x → hub, zeekr 8x ultra → oferta". Anchor niesie frazę HUBA
(`{model}`), frazę wersyjną niesie title/H1 oferty (v3/v4 hoist z 0.33.21-22).

---

## 2. Decyzje, których NIE podważać bez nowych danych

- **Champion ODRZUCONY** (pomiar 2026-07-16, sekcja 3 planu). Kanibalizacja hub↔oferta = **28 klików
  /90d** (hub wygrywa 601). Oferty są **efemeryczne**: 53% fraz rankowało ofertą, której już nie ma
  (301 na hub) — champion promowałby stronę tymczasową. Wraca tylko, jeśli po 15.08 bilans urośnie.
- **Champion ≠ canonical.** Canonical oferta→oferta **wykluczony na zawsze** — to fizycznie różne
  auta (VIN/cena/przebieg); 30→1 wyrzuciłoby 29 realnych sztuk z long-taila.
- **Huby = osobny track.** Stoją #1–2 na frazach „cena". Nie ruszać bez baseline i progu rollback
  (>3 pozycje = rollback). Zmiany ofert ich nie dotykają — stosy title są **fizycznie rozłączne**
  (`serieAnchor`/`titleBaseV3`/`v4*` tylko w plikach ofert; `ensureBrandPrefix` tylko w
  `class-asiaauto-hub-title-generator.php`; `[asiaauto_tech_specs]`/`[asiaauto_equipment]` wołane
  wyłącznie z `class-asiaauto-single.php:93-94`).
- **GBP i FB nie ruszamy** — nazwa GBP z keywordem to decyzja zamknięta ([[feedback_gbp_name_keyword]]).
- **Oferta = strona KONWERSYJNA, nie akwizycyjna** — 26 978 odsłon vs **676 sesji organic**;
  405 klików/90d na 3 000 stron. Nie blokuj UX argumentem o kanibalizacji link equity między
  ofertami — ich PageRank jest znikomy. Memory: `reference_mobile_share_and_offers_are_conversion_pages`.

---

## 3. Terminy pomiarów (w kalendarzu)

- **~27.07** — pomiar title v3/v4 wg `T-203-baseline-gsc-2026-07-13.md`: frazy wersyjne ofert
  + **kontrola hubów na „cena"** (yu7 7,1 / zeekr 9x 2,6 / zeekr 8x 4,0 / g700 3,1 / aito m9 2,6 /
  arrizo 8 2,3). **Próg rollback: spadek >3 pozycje na dowolnym hubie** → `asiaauto_offer_title_v2_series` = `""`.
- **~15.08** — efekt linkowania oferta→hub (materializuje się tygodniami). Test hipotezy
  „hubowi brakowało sygnału z własnych podstron" → czy 30 hubów z sekcji 3b weszło do SERP.
  Skrypty gotowe: `scripts/seo/gsc_champion_check.py`, `gsc_hub_missing.py`, `inspect_hubs.py`.

---

## 4. Gotchy, które kosztowały czas w tej sesji

1. **Theme NIE ma cache-bustingu po `filemtime()`** — style idą z hardcodowanej
   `PRIMAAUTO_THEME_VERSION` w `themes/primaauto2026/functions.php`. **Każda zmiana w
   `themes/primaauto2026/assets/css/*` wymaga bumpu tej stałej**, inaczej przeglądarka serwuje
   starą kopię i „zmiany nie działają". Plugin busta się sam.
2. **Chrome (claude-in-chrome) nie da mobilnego viewportu** przy zmaksymalizowanym oknie —
   `resize_window` jest ignorowane, `innerWidth` zostaje 1920. Weryfikacja mobile = zrzut od Janka.
3. **`_serie_full_title` jest NIESYMETRYCZNY** — raz dokleja napęd, którego nie ma w nazwie termu
   (`8X` → „Zeekr 8X PHEV"), raz gubi ten, który jest (`Han DM-i` → „BYD Han"). Stąd 3 korekty
   w `serieAnchor()`. **Symulacja wyłapała kolizję PRZED wdrożeniem** (Sealion 5 DM i EV → oba
   „BYD Sealion 5"): kolizje 1 → 0.
4. **Oferty rotują** — URL z GSC bywa martwy (301 na hub). Przy analizie GSC zawsze sprawdź
   `curl -I`, zanim uznasz ofertę za żywą.

---

## 5. PROMPT KONTYNUACYJNY (nowy wątek)

```
Kontynuujemy pracę nad stroną OFERTY w primaauto (T-203). Przeczytaj najpierw:
- docs/sesje/2026-07-16-t203-oferty-linkowanie-do-hubow.md  (ta sesja — stan, decyzje, gotchy)
- docs/seo/T-203-plan-strony-ofertowej-2026-07-14.md        (plan: P1 zamknięty, 4b otwarty)
- docs/VERSIONS.md sekcje 0.33.23-26                        (co dokładnie weszło)
- memory: reference_mobile_share_and_offers_are_conversion_pages

STAN: P1 (linkowanie oferta→hub) ZAMKNIĘTY i live. Champion ODRZUCONY na danych — nie
wracaj do niego bez pomiaru 15.08. Huby = osobny track, nie ruszać bez baseline.

ZADANIE — wątek 4b (wszystko w renderMeta(), class-asiaauto-single.php, JEDEN deploy):

1. ANALIZA (najpierw, przed jakąkolwiek zmianą): GSC pokazuje tylko 79 poprawnych
   „opisów produktów" i są to HUBY — przy 3 056 ofertach. Ustal:
   a) Który to raport — „Merchant listings" czy „Product snippets"? Mają różne wymagania.
   b) Co dziś emitują oferty. Historycznie był fix multi-type [Product, Car] (memory
      project_session_2026_06_07_gsc_full_audit) — SPRAWDŹ, czy multi-type nie jest powodem,
      dla którego GSC nie liczy ofert jako Product. To główna hipoteza.
   c) Skąd akurat 79, a nie 302? Czy to nie jest po prostu „tyle, ilu Google zdążył uznać"?
   d) Dopiero potem decyzja: czy Product ma być na ofertach. UWAGA na kontrargument —
      oferty mają 405 klików/90d na 3 000 stron; rich result na stronie, która nie rankuje,
      nie da ruchu. To jest DO DYSKUSJI z Jankiem, nie do przesądzenia samodzielnie.

2. FIX koloru (T-211 ⚡2, 1 linia): kod pyta o taksonomię `color`, która NIE ISTNIEJE
   (są `exterior-color` / `interior-color`) → kolor nie trafia do schematu ŻADNEJ z 3 056 ofert.

3. Utracone pola schema (ZAD.12/13, przy dedupie 2026-04-24): vehicleEngine (KW),
   vehicleTransmission, driveWheelConfiguration, itemCondition. Plus brak OfferShippingDetails
   (gap vs west-motors).

4. T-199 resztka: „Prima Auto" → „Prima-Auto" w renderMeta():914 (title mówi „Prima-Auto",
   desc „Prima Auto" — niespójność w obrębie jednej strony). Zysk SEO ZEROWY, robimy tylko
   dlatego, że to ten sam plik. GBP/FB NIE ruszać.

ZASADY:
- class-asiaauto-single.php = strefa ZAWSZE PYTAJ → pokaż diff i czekaj na „ok" przed deployem.
- Backup .bak z datą + php -l + smoke na 3 ofertach RÓŻNYCH PALIW (EV / PHEV / benzyna —
  mają różne zestawy sekcji) + bump ASIAAUTO_VERSION + wpis w docs/VERSIONS.md.
- Zmiana CSS themu → bump PRIMAAUTO_THEME_VERSION (theme nie ma cache-bustingu!).
- Weryfikuj stan serwera PRZED scope (feedback_verify_backlog_against_server).
- Przy >20 iteracjach: dry-run najpierw (feedback_dry_run_przed_batch).

POZA SCOPE tego wątku (nie rozszerzaj):
- T-212 (wspólny komponent karty, 3-4h) → odblokowuje T-187 (box „inne oferty tego modelu")
  i T-189 (rata na karcie). Janek świadomie odłożył — box egzemplarzy robimy PO T-212.
- Znalezisko 3b (30 fraz, gdzie zdrowy hub nie rankuje) — czeka na pomiar 15.08, track hubów.
- Znalezisko Z4 (docs/seo/znaleziska-huby-2026-07-14.md): /byd/sealion-5-dm/ i /byd/sealion-5-ev/
  mają IDENTYCZNY title „BYD Sealion 5"; /byd/han-dm-i/ = „BYD Han" bez frazy „byd han dm-i"
  (44 oferty). 13 serii. Logika naprawy gotowa w serieAnchor() — do przeniesienia na generator
  hubów, ale to track HUBÓW z baseline i progiem rollback.
- 335 ofert (11%) bez żadnego opisu — nadal bez właściciela, do decyzji Janka.
```

---

## 6. Otwarte na stronie oferty (poza 4b)

| task | rozmiar | uwaga |
|---|---|---|
| **P3 higiena** | S | 21 niezaindeksowanych URL; oferta z `post_title` od `[`; podwójne „DM-i" w BYD Tang |
| **T-212** → T-187 / T-189 | 3-4h → 9-12h / 14-18h | fundament: karta oferty jest dziś w 3 kopiach |
| **T-210** edytor danych technicznych | 22-28h | sedno: blokada per-pole; dziś flaga wyrzuca auto z syncu **na zawsze** |
| **T-213** resztka | S | jedno zdanie zaufania na ofercie (wariant B, bez rozbicia kosztów) |
| **T-211 ⚡3** | 0,5-1h | 20 chińskich miast, 105 ofert z surowym znakiem |
| **335 ofert bez opisu** | ? | **bez właściciela** — opisy to tłumaczenia maszynowe, śr. 518 znaków |
| **SU7 „Standard Long Range"** bez hoistu | M | świadomy kompromis — guard chroni przed wymyśloną wersją („YU7 Ultra"). NIE ruszać bez słownika wersji per model |
