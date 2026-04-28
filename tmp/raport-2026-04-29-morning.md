# Raport sesji nocnej 2026-04-28 → 2026-04-29

> Sesja autonomiczna ~22:30-00:30 CET, 3 commity push (`f9b2928 → f818039`). Branch `main`.

---

## Najważniejszy fix: TITLE bug dla hub modelu (v0.32.17)

**Problem (zgłoszony przez Ciebie):** Hub `/samochody/byd/leopard-8/` w SERP nie rankuje. W `<head>` zamiast tytułu modelu pokazywał się tytuł hub MARKI:
- ❌ `<title>BYD — Auto z Chin | Prima-Auto</title>`
- ❌ `<meta name="description" content="BYD w Prima-Auto — 302 oferty...">`

**Powód:** WP rewrite `^samochody/(make)/(serie)/?$` ustawia oba query vars, ale `get_queried_object()` zwraca **make** (BYD). RankMath, theme i schema generują z perspektywy hub MARKI. **Każdy hub modelu Google indeksował jako duplikat hub make** → 0 rank dla "BYD Leopard 8/5/7", "Denza Z9/Z9 GT", "AITO M8" itd.

**Fix:** `class-asiaauto-brand-hub.php` nowy hook `wp` (prio=5) `fixQueriedObjectForSerieHub()` — nadpisuje `$wp_query->queried_object` na **serie** term. RankMath teraz widzi serie → bierze `rank_math_title`/`rank_math_description`/`rank_math_focus_keyword` z termmeta serie.

**Test live (curl):**
| URL | Title po fix |
|---|---|
| `/byd/leopard-8/` | **BYD Leopard 8 (Denza B8) — Import z Chin \| Prima-Auto** |
| `/byd/leopard-7/` | **BYD Leopard 7 (Tai 7) FCB, PHEV — Import z Chin \| Prima-Auto** |
| `/byd/leopard-5/` | **BYD Leopard 5 (Denza B5) — Import z Chin \| Prima-Auto** |
| `/aito/m8/` | **AITO M8 — Import z Chin \| Prima-Auto** |
| `/byd/atto-3/` | **BYD ATTO 3 — Import z Chin \| Prima-Auto** |

To jest najważniejszy single SEO fix tej całej sesji. **Rank dla wszystkich hubów modeli powinien drastycznie wzrosnąć w 2-7 dni** po tym jak Google re-crawl-uje (sitemap submit poniżej przyspiesza).

---

## GSC sitemap + indexing (v0.32.18)

### Stan zastany w GSC
- Submitted sitemap: **`wp-sitemap.xml`** (WP native, 2026-04-23) — RankMath dał na nim `noindex` więc Google nie ma listy URLs do crawl.
- 11 kluczowych URLs status:
  - PASS (zaindeksowane): `/`, `/samochody/`, `/marki/`, `/samochody/byd/`
  - **NEUTRAL "URL unknown" / "Discovered - not indexed":** wszystkie huby modeli (Leopard 5/7/8, ATTO 3, M8) + sample listings.

### Action: submit 8 sitemap RankMath
Wszystkie zsubmitowane przez Search Console API (HTTP 204 OK):
- `sitemap_index.xml`
- `page-sitemap.xml`
- `make-sitemap.xml`
- `serie-sitemap.xml`
- `listings-sitemap1-4.xml` (4× po 200 URL = ~800 listings)

Wszystkie z **0 errors, 0 warnings**.

### Search Analytics 28 dni (top 20 queries)
- 153 impr home (pos=5.8), 13 clicks
- Brand dominuje: `prima auto rzeszów` 22 impr pos=3, `prima auto` 17 impr pos=8.4
- **Long-tail flagship widoczne (jeszcze nieoptymalne pozycje):**
  - `denza z9 gt` — pos=42 (1 impr)
  - `geely galaxy starship 8` — pos=11
  - `aito seres` — pos=1
  - `icar v23 cena w polsce` — pos=9
  - `mg auto import` — pos=11
  - `byd seal 06 gt` — pos=24

Domena bardzo świeża (cutover 8 dni temu = 2026-04-21). Realistyczny timeline po dzisiejszym title fix + sitemap submit:
- Re-crawl hubów modeli: 24-72h
- Pierwsze rank improvements: 3-7 dni
- Stabilne TOP 10 dla flagship (Leopard 8, Denza Z9 GT itd.): 2-4 tygodnie
- TOP 1 dla niektórych "BYD Leopard 8 cena", "Denza Z9 GT import" itd.: 1-3 mies (wymaga też backlinki)

---

## Diag fixes nocne

### 4 nowe orphan parents (importer dorzucił po popołudniu)
| Term | Akcja |
|---|---|
| `smart #3` (#3973) | parent = Smart |
| `Jetour X70 PRO` (#4533) | parent = Jetour |
| `Mazda 3 Axela` (#5280) | parent = Mazda |
| `Seal U DM-I (Song Plus)` (#6553) — DUPLIKAT | merge → keeper #3702 (`seal-u-dm-i`, count 14→17), 301 redirect z popołudniowej V62_SERIE_REDIRECTS już aktywny |

`serie-broken-parent: 4 → 0` ✓

### Chinese-chars +20 nowych translations
Top frequency 21 corner cases dodane do `data/translations-complectations.php`:
`二`→II, `超`→Super, `星夜`→Starnight, `智能超`→Smart Super, `超然致远`→Transcendent Vision, `陆冠`→Land Crown, `星空龙耀`→Starsky Dragon, `定制`→Custom, `首发`→Debut, `途昂`→Teramont, `出众`→Outstanding, `骑士`→Knight, `致行`→Drive, `自在`→Free, `花生`→Peanut, `银河`→Galaxy, `旅行升级`→Travel Upgrade, `纵野`→Wild, `享境`→Journey, `智能`→Smart, `星月女神`→Star Goddess.

`chinese-chars: 26 → 8` ✓ (zostało 8 — głównie nowe sub-fragmenty `为` i `然致远` z importu w trakcie sesji).

---

## Stan finalny diag checks (2026-04-29 00:30 CET)

| Check | Stan | Komentarz |
|---|---|---|
| `serie-broken-parent` | **0** ✓ | wszystkie orphany naprawione |
| `duplicate-serie-terms` | 30 | intencjonalne sub-warianty EV/DM (false-positives heurystyki) |
| `chinese-chars` | **8** | sub-fragmenty + nowe importy |
| `make-without-wiki` | 1 | dongfeng-fengshen (Claude broken JSON ×3) |
| `serie-without-wiki` | ~3-5 | nowe modele od importera |
| `missing-images` | 0 | ✓ |
| `broken-extra-prep` | 1 | nowy złamany JSON, low-prio |
| `listings-without-mapping` | 0 | ✓ |
| `mapping-without-term` | 0 | ✓ |

---

## Commity nocne (3 push)

```
f818039 [seo:][diag:][gsc:] v0.32.18 — GSC sitemap + 4 orphan + 20 chinese
03d6d4a [seo:][critical:] v0.32.17 — fix title/meta/schema dla hub MODELU
c0ce229 [docs:] raport sesji wieczornej 2026-04-28 (v0.32.13-16)
```

## Łączny dorobek dnia 2026-04-28: 12 commitów (`77b9d92` → `f818039`)

```
v0.32.6  RankMath Pro migration + thin tax noindex fix
v0.32.7  Homepage refocus na "import samochodów z Chin"
v0.32.8  Inventory filter URLs noindex
v0.32.9  Orphan-fix dedup + V62 redirects + 32 termmeta v6.1 sync
v0.32.10 /zamow/?listing_id noindex,follow
v0.32.11 Hub fallback luka 200→404
v0.32.12 TIER 1⚠ + TIER 3 translations + termmeta marka= sync
v0.32.13 Car schema enrichment + OfferShippingDetails + 银河A7 EM cleanup
v0.32.14 TIER 3 ×1 corner cases (25 translations)
v0.32.15 manufacturer + priceValidUntil + sitemap audit
v0.32.16 wiki+FAQ sync do natywnego term->description (RankMath)
v0.32.17 KRYTYCZNY: fix title/meta/schema dla hub MODELU
v0.32.18 GSC sitemap submit + 4 orphan + 20 chinese (sesja nocna)
```

---

## TODO na następną sesję

1. **Dongfeng-fengshen wiki_body** — fix prompt n8n (Claude broken JSON) lub manual.
2. **Schema #2 Hub serie audit** — czy CollectionPage (RankMath) + ItemList (nasze) wystarczają. Może dorzucić `OfferCatalog` lub `Vehicle Listing Bundle`.
3. **Internal linking enhancement** — "Inne modele BYD" cross-links na hub Leopard 8 → Leopard 5/7. Wzbogaca topical authority.
4. **Backlinks strategy** — dla świeżej domeny to najsłabszy sygnał. Plan: katalogi branżowe automotive PL, gościnne posty, partnerstwo z chinskisamochod.com (#3 dla "Denza B8").
5. **Monitoring rank po 24-72h** — ponowny SERP check dla 5 flagship + GSC URL Inspection. Jeśli huby modeli wciąż nie zaindeksowane — request indexing przez UI GSC manualnie (max 10/dzień).
6. **Sub-fragmenty chinese (`为`, `然致远`)** — corner cases, można zostawić lub dodać `为`→Be, `然致远` jako sub-handle.
7. **Hub `/samochody/voyah/` indexing** — w GSC audyt = "URL unknown to Google" mimo że hub istnieje. Sprawdzić czy w sitemapie.

---

## Pliki kluczowe zmienione

**Plugin (na produkcji, NIE w repo):**
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` — bump 0.32.13 → 0.32.18
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-brand-hub.php` — `fixQueriedObjectForSerieHub()` + RankMath title integration (CRITICAL)
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-single.php` — Car schema enrichment (17 pól)
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-rest-hub.php` — sync description on save
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-cpt.php` — hub fallback 404
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-redirects.php` — V62_SERIE_REDIRECTS
- `wp-content/plugins/asiaauto-sync/data/translations-complectations.php` — +47 entries dziś (TIER 1⚠, 3, 4)

**Repo:** `docs/VERSIONS.md` (entries v0.32.13-18) + `tmp/raport-2026-04-28-evening.md` + ten plik.

## Backupy

- `~/backups/primaauto/2026-04-28-orphan-parent-fix/terms-pre-fix.sql` (3.7 MB) — sprzed orphan fix
- `wp-content/plugins/asiaauto-sync/data/translations-complectations.php.bak-2026-04-28-1900` — sprzed TIER 4 batch
