# Raport końcowy 2026-04-29 (sesja popołudniowa, Stock Highlights)

> Sesja po południu — kontynuacja po raporcie nocnym (`tmp/raport-2026-04-29-morning.md`). Auto mode + manual akceptacje na każdy edit homepage/template.

## Commity (4 push: 26291d5 → 711162a)

```
711162a v0.32.21 — stock bary na hub make/serie przed głównym contentem
aedad51 v0.32.20 — listing card badges priorytet on_lot/in_transit/nowe
452bbef v0.32.19 — Stock Highlights na home (W Polsce + W drodze)
26291d5 docs: raport sesji nocnej (z poprzedniej sesji nocnej)
```

## Zmiany dziś po południu — 3 warstwy "stock visibility"

User insight: **"sprzedawca wie co się powinno teraz sprzedawać"** + **"i tak ściąga auta na własny stock"** — auta z `_asiaauto_reservation_status ∈ {in_transit, on_lot}` to flagshipy biznesowe (sprzedawca już zainwestował w transport).

### Warstwa 1: Home — sekcja "Auta dostępne teraz" (v0.32.19)
- Lokalizacja: między `renderLatest` a `renderMakes`.
- Grid kafelek per model (deduplikowane), badge `🇵🇱 W Polsce: N` + `🚢 W drodze: N`, CTA do `/w-rzeszowie/` i `/w-drodze/`.
- 12 unique modeli, **12 nowych internal links z home do hub modeli (poprzednio 0)**.
- Cache transient 1h, auto-invalidacja przy `updated_post_meta` na `_asiaauto_reservation_status`.

### Warstwa 2: Hub make/serie — sekcja `aa-hub__stock` (v0.32.21)
- Lokalizacja: PRZED `aa-hub__body` (przed wiki/listings głównymi).
- Renderowane warunkowo — tylko jeśli są listings z `on_lot` lub `in_transit` dla danej marki/modelu.
- Test live: `/byd/sealion-8-dm-i/` → obie sekcje (1+1 listings); `/byd/` → agregat make (5+9); `/byd/tang-dm-i/` → 0 sekcji ✓.
- Shortcode `[asiaauto_hub_listings reservation_status="on_lot|in_transit"]` z whitelist + cichy fallback (puste = nic).

### Warstwa 3: Per-listing badge (v0.32.20)
- Priorytet: `on_lot` → `in_transit` → `is_new` (fallback).
- Badge: `🇵🇱 Na placu` (zielony) / `🚢 W drodze` (niebieski) / `Nowe` (żółty).
- Implementacja w 2 miejscach: `class-asiaauto-homepage.php::renderLatest` + `class-asiaauto-brand-hub.php::renderListings`.

## Decyzje architektoniczne

**Modele vs listingi w sekcji "Auta dostępne teraz" na home:**
- Wybrano modele (linki do hub `/samochody/{make}/{serie}/`).
- Powód: hub trwa wiecznie a single listing znika po sprzedaży = SEO equity przepada; linki do hub kumulują authority dla "BYD Leopard 5/8" itd.; badge "1 dostępne dziś" daje trust.

**Feedback memory zapisana:** `feedback_no_edit_homepage_without_ok.md` — NIE edytuj `class-asiaauto-homepage.php`, `taxonomy-make/serie.php`, `class-asiaauto-single.php` bez explicit OK user'a. Auto mode NIE override tej reguły.

## Stan finalny

| Check | Stan |
|---|---|
| serie-broken-parent | 0 ✓ |
| chinese-chars | 8 |
| make-without-wiki | 0 ✓ |
| serie-without-wiki | ~3-5 |
| missing-images | 0 ✓ |

## Pełen dorobek dnia 2026-04-29 (8 commitów: f721773 → 711162a)

```
v0.32.16 wiki+FAQ sync term description (RankMath)
v0.32.17 KRYTYCZNY title fix dla hub modelu (queried_object override)
v0.32.18 GSC sitemap submit (8 sitemaps) + 4 orphan + 20 chinese
v0.32.19 Stock Highlights na home
v0.32.20 Listing card badges priorytet on_lot/in_transit/nowe
v0.32.21 Stock bary na hub make/serie przed głównym contentem
+ Dongfeng Fengshen wiki manual (manual-v1-2026-04-29 prompt_version)
```

## TODO na nowy wątek

### Wysokie priorytet
1. **Monitoring rank po 48-72h** — sprawdzić czy title fix (v0.32.17) + sitemap submit (v0.32.18) + stock highlights (v0.32.19/20/21) dał już efekt w SERP. DataForSEO + GSC URL Inspection dla flagship: BYD Leopard 5/7/8, Denza Z9/Z9 GT, AITO M8.
2. **Hub schema enrichment** — dorzucić `Product` schema dla hub modelu z `aggregateOffer` (range cen + offerCount) → Product Snippet w SERP. CollectionPage od RankMath + nasz ItemList są OK, ale Product daje rich card.
3. **Backlinks strategy** — najsłabszy sygnał świeżej domeny (cutover 21-04, 8 dni temu). Plan: katalogi automotive PL, partnerstwo z chinskisamochod.com (#3 dla "Denza B8"), gościnne posty.
4. **Wzbogacić wiki dla flagship modeli** ręcznie — Leopard 5/7/8, Denza Z9, Z9 GT, AITO M8 — dodać video YouTube embed, więcej comparison tables, customer testimonials.

### Średnie
5. **8 zostawających chinese sub-fragmentów** (`为`, `然致远`, etc.) — TIER 5 corner cases.
6. **Performance audit (PSI)** po wszystkich zmianach v0.32.17-21 — sprawdzić czy Stock Highlights + nowe sekcje nie regresji LCP.
7. **Verify Stock Highlights na małych ekranach** — CSS responsywny ma 2 kolumny <600px, sprawdzić wizualnie.
8. **Internal linking** — dodać "Inne modele tej samej marki" sekcję na single listing (cross-link).

### Niskie / długie
9. **Rotator + cleanup ogłoszeń** (ZADANIE 14 v2 z QUEUE).
10. **Importer fixes** — dlaczego ciągle dorzuca orphan parents (Plan D + fix mapping).
11. **GA4 → Ads conversion import** — pending user-side w Ads UI.

## Pliki zmienione (na produkcji, NIE w repo)

- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.18 → 0.32.21
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-brand-hub.php` — listing badge priorytet w `renderListings` + `reservation_status` param w shortcode + cichy fallback
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-homepage.php` — `getStockHighlights()` + `renderStockHighlights()` + `invalidateStockOnMeta()` + listing badge priorytet w `getLatestListings`/`renderLatest` + CSS aa-stock + aa-home__car-badge--pl/--transit
- `wp-content/themes/primaauto2026/taxonomy-make.php` — sekcja `aa-hub__stock` przed `aa-hub__body`
- `wp-content/themes/primaauto2026/taxonomy-serie.php` — analogicznie

## Memory zapisane

- `project_session_2026_04_29.md` — pełna sesja (kontynuuje sesję z 2026-04-28)
- `feedback_no_edit_homepage_without_ok.md` — nowa zasada o homepage/template
- MEMORY.md updated z 2 nowymi entries
