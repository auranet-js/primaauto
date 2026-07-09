# Metoda reworku hubów modeli (cena-intent + AEO) — rollout

> Data: 2026-05-30 · Pilot: Leopard 5 (term 5523, WDROŻONY) · Kontekst: memory `project_session_2026_05_30_hub_rework_pilot`
> Cel: domknąć intent „cena/gdzie kupić/kiedy w Polsce" na hubach + citowalność AEO, bez psucia import-edge (Tier B).

## Mechanizmy per-term (render w `themes/primaauto2026/taxonomy-serie.php`)

| Meta | Rola | Uwaga |
|---|---|---|
| `_asiaauto_lead` | Lead AEO answer-first, render NAD barami stocku | `esc_html`; krótki (2-3 zdania); answer-first (cena+dostępność+zaufanie) |
| `_asiaauto_h1_suffix` | Override sufiksu H1 (fallback `import z Chin`) | Tier A: `cena w Polsce i import z Chin`; Tier B: NIE ustawiać (zostaje import-led) |
| `_asiaauto_pl_availability` | `import_only` / `salon_available` — prawdziwość leada | Ręcznie, wiedza rynkowa. Borderline → potwierdzić z Janek/Ruslan |
| `_asiaauto_seo_rework` | Flaga wersji `v1-2026-05-30` | Do przyszłej analizy: które huby zmienione |
| `asiaauto_wiki_body` | Treść (7 H2 intent-led, dane prawdziwe) | `{{LISTINGS_BAR}}` po intro; wp_kses_post |
| `asiaauto_faq_json` | FAQ 5 Q (PAA), FAQPage JSON-LD | ASCII dotyczy **WYŁĄCZNIE cudzysłowów** (`"` — smart quotes „" łamią `json_decode`); **tekst po polsku Z DIAKRYTYKAMI** (ą ć ę ł ń ó ś ź ż). Walidacja `REGEXP [ąćęłńóśźż]` przed `update_term_meta`. Patrz T-193 niżej. |

> ⚠️ **Reguła ASCII = tylko cudzysłowy, NIGDY cały tekst** (utrwalone po T-193, 2026-07-09). Skrót „FAQ JSON: ASCII" z 05-30/06-08 był nadinterpretowany przez subagenty jako „cały tekst bez polskich znaków" → 152 termy `asiaauto_faq_json` (+ 132 `_asiaauto_lead` + 42 `asiaauto_wiki_body`) zapisane bez diakrytyk. Zawsze: polszczyzna z diakrytykami, ASCII tylko dla `"`. Twardy gate przy backfillu: zdejmij diakrytyki z wyniku → musi być znak-w-znak == wejście (gwarancja braku dryfu treści). Batche od `v1-2026-06-19` już czyste.

## Procedura per hub (sekwencyjnie, self-review przed deployem)

1. **GSC** frazy hubu (intent gaps cena/kiedy/wymiary/spalanie/wersje) — `tmp/gsc-*` (reusable).
2. **DB**: zakres cen (`price` meta), count, wersje (post_title), stock (`stm_car_location`), specy (z obecnego wiki).
3. **pl_availability**: import_only (domyślnie dla marek bez salonu PL) / salon_available (Jetour/Omoda/MG/Zeekr/Leapmotor i in. z dystrybucją). Borderline → flaguj, nie strzelaj.
4. **Generuj**: lead (answer-first, PRAWDZIWY wg pl_availability) + h1_suffix (Tier A; Tier B pomiń) + FAQ (5 Q z PAA) + wiki (7 H2 skonsolidowany, dane z DB).
5. **Self-review**: fakty zgodne z DB? brak fałszywego „wyłącznie z importu"? brak over-repeat cena? smart quotes? title nieruszany jeśli wygrywa frazę?
6. **Deploy**: `mysqldump` termmeta termu → `~/backups/primaauto/<data>/`; zapis przez `wp eval-file`; smoke test (curl: H1, lead, FAQPage parsuje, brak literalnego tokenu, dane).
7. **Flaga**: `_asiaauto_seo_rework=v1-<data>`.
8. **Index**: `~/bin/index-submit --project primaauto --type URL_UPDATED --url <hub>` (po smoke OK).

## Kolejność rolloutu

1. **14 hubów ze stockiem** (Rzeszów/w drodze — szablon pokazuje bary, intent dostępności żywy), wg stock desc:
   leopard-7, n9-dm-i, vx, ez-6, ~~leopard-5 (DONE)~~, leopard-3, g700, sealion-8-dm-i, g318, monjaro, t2-c-dm, 9x⚠️TierB, n8l-dm, shark-6.
2. **Reszta hubów wg liczby ogłoszeń** (count desc).

## Tier B — wariant OCHRONNY (NIE psuć importu)

3 huby (`docs/seo/tier-b-import-jewels-2026-05-30.md`): `denza/z9-gt-dm-i`, `zeekr/8x`, `zeekr/9x`.
- Title + H1 **zostają import-led** (NIE ustawiać `_asiaauto_h1_suffix`).
- Cenę dokładamy tylko w **lead + body + FAQ**, bez degradacji „import".
- Kontrola GSC import-frazy przed i po.

## Budżet indexing

`~/bin/index-submit` (wrapper, ledger Pacific). Ad-hoc 100/dobę + rezerwa 100 PrimaAuto. **2026-05-30: zgoda do 150** (sync dongchedi down = autoapi naprawiają → hook auto-index nie zużywa rezerwy). Pokazywać `--status` przed/po. Bottleneck = jakość per hub, nie budżet — nie ścigać się do limitu.

## Klasyfikacja pl_availability — zweryfikowana web (2026-05-30)

| Hub | Marka — stan PL (web) | Tryb |
|---|---|---|
| t2-c-dm | Jetour oficjalnie w PL (AADC), T2 ~174 900 zł | **salon_available** (my: wariant C-DM PHEV z importu, taniej) |
| vx | Exeed nie w PL (tylko Exlantix ES/ET) | import_only |
| ez-6 | Mazda ma salony, EZ-6 nie wchodzi (stawiają na CX-6e) | import_only (fraza per-model) |
| 9x (Zeekr 9X) | Zeekr jeszcze nie w PL (H2 2026) | import_only + **Tier B** (H1 import-led) |
| n9-dm-i, n8l-dm | Denza wchodzi VI.2026 ale Z9 GT+D9, nie N9/N8L | neutral |
| sealion-8-dm-i | BYD w PL, ale Sealion 08 dopiero 2027 | neutral |
| g318 | Deepal oficjalnie w PL od 7.05.2026 (S05/S07), G318 nie | neutral |
| monjaro | Geely w PL (Jameel), Monjaro dopiero nadchodzi | neutral |
| g700 | Jetour w PL, G700 (Zongheng) nie w ofercie | neutral |

**KRYTYCZNE dla Tier B:** Denza Z9 GT = model startowy premiery PL (VI.2026) → lead z9-gt-dm-i NIE może mówić „wyłącznie z importu" (marka wchodzi). Użyć neutral/competitive. Zweryfikować ponownie świeżym web przy Tier B.
