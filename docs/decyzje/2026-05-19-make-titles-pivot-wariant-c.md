# Hub marek — pivot title na Wariant C (aggregator-style)

**Data:** 2026-05-19
**Autor:** Janek (Auranet)
**Klient:** Ruslan Prima (PRIMA-AUTO)
**Status:** Wdrożone w v0.32.50, 61/61 marek z `count>0` zaktualizowane.
**Pliki dotknięte:**
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-hub-title-generator.php` (rozszerzenie)
- `wp-content/plugins/asiaauto-sync/cli/class-asiaauto-cli.php` (nowy sub-command)
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` (version bump 0.32.49 → 0.32.50)

---

## 1. Kontekst

Sesja SEO 2026-05-19 (po Indexing API przygotowaniu). Z DFS standard scan 2026-05-18: **frazy `{brand} {model} import` mają 60% w top 10 vs 5,8% gołych `{brand} {model}`** — pivot na „import" działa na poziomie modelu. Naturalna hipoteza: pivot tego samego na poziomie marki.

**Weryfikacja danymi (GSC 28d, 10 top hubów marek):**

```
IMPORT impressions:   0 (0.0%)
bez import impr:      254 (100%)
```

**Wniosek:** user'zy NIE szukają `{marka} import` (np. „BYD import"). Szukają:
- `cena` / `ceny` (wszechobecne)
- `z chin` (BYD: pos 30, VW: pos 18, XPeng: pos 28)
- `polska` (AITO: pos 1.7)
- `rzeszów` / lokalne
- Sam brand + model

Pivot na słowo „import" w title hub-ów MAREK = brak data support.

**Stan przed:**
- 43 marki z title wzorca `{Make} — Auto z Chin | Prima-Auto` (40-50 znaków, niewiele info)
- 18 marek z `count > 0` ale BEZ title (RankMath fallback `{TermName} - {SiteTitle}`)
- 18 marek bez listings publish (Mercedes-Benz, Rolls-Royce itd.) — count=0, NIE w scope

---

## 2. Decyzja: Wariant C (spójny z hub modeli)

**Wzór title:** `{Make} — od {min_price} PLN, {count} sztuk | Import z Chin | Prima-Auto`

**Wzór description:** `{Make} w Prima-Auto — {count} ofert, ceny od {min} do {max} PLN. Import z Chin z homologacją indywidualną i gwarancją importera. Roczniki 2024-2026.`

**Argument za:**
- Spójność z hub modeli (te same elementy: cena/count/Import z Chin/Prima-Auto)
- Wykorzystanie znaków title (user wybór: „szkoda marnować ilość znaków")
- Pokrycie intentu „cena" (#1 w GSC dla queries marek)
- Element `Import z Chin` zachowany — nie szkodzi nawet bez search demand dla brand-level, wzmacnia tematyczność

**Argument przeciw:**
- 50/61 marek >60 znaków (Google ucina w SERP)
- 3/61 >70 znaków (Dongfeng Fengshen 73, Dongfeng Fengxing 73, Beijing Off-Road 72)

**Akceptacja:** title pełen jest indexowany, SERP-display obcięty ≠ rank obcięty.

---

## 3. Alternatywy (i dlaczego nie)

| Wariant | Title example | Dlaczego nie |
|---|---|---|
| **A: krótki** | `BYD z Chin — od 103 000 PLN, 569 ofert \| Prima-Auto` (53ch) | Bez „Import z Chin" — niespójne z hub modeli, traci tematyczność |
| **B: salonowy** | `BYD z Chin — 569 aut, ceny od 103 000 PLN \| Prima-Auto` (55ch) | „auta" zamiast „sztuk/ofert" — niezgodne z generatorem serie |
| **C: aggregator (wybrany)** | `BYD — od 102 000 PLN, 569 sztuk \| Import z Chin \| Prima-Auto` (60ch) | Najdłuższy, ale spójny z serie generator |

---

## 4. Implementacja

**Klasa `AsiaAuto_HubTitleGenerator`** rozszerzona o:
- `regenerateForMakeTerm(int $term_id)` — odpowiednik `regenerateForTerm` dla `make`
- `regenerateAllMakes()` — bulk z `get_terms(['taxonomy'=>'make', 'hide_empty'=>true])`
- `getMakePriceRange()` — SQL JOIN przez `make` taxonomy zamiast `serie`
- `buildMakeTitle()` / `buildMakeDescription()` — wzorce per Wariant C
- `pluralizeOferty()` — Polski plural dla „oferta"
- `brandSlugToDisplay()` zmiana visibility `private → public` (utility)

**Hook integration:**
- `asiaauto_after_set_taxonomies` (po imporcie listing'u) — regen + serie + make
- Daily cron `asiaauto_regen_hub_titles_daily` — woła `regenerateAll()` + `regenerateAllMakes()` (catch-up)

**WP-CLI:** `wp asiaauto regen-make-titles [--all|--term=<id>] [--dry-run]`

**Skip mechanism:** `_asiaauto_skip_title_regen=1` na termmeta — manual override per marka (jeśli kiedyś trzeba odlinkować markę od generatora).

---

## 5. Wynik wdrożenia

**Bulk regen 2026-05-19:** 61/61 marek z count>0 → updated (100%).

**Live verification:** 3 sample URL (BYD/Geely/Volkswagen) renderują nowy title + description natychmiast (zero RankMath cache).

**Dystrybucja długości title:**
- ≤60ch: 11
- 61-70ch: 47
- >70ch: 3
- Avg: 63, Max: 73

**Backup:**
- `class-asiaauto-hub-title-generator.php.bak-2026-05-19-pre-make`
- `~/backups/primaauto/2026-05-19-make-titles/before-bulk.tsv` (63 wierszy: term_id+slug+count+old_title+old_descr)

---

## 6. Co dalej

1. **Indexing API push 61 URL hub marek** — JUTRO (2026-05-20) po reset quota Google. Nowe title = nowe sygnał, warto powiadomić Google. Część integracji z `tmp/indexing-api-go-live-2026-05-20.md`.

2. **GSC monitoring** — za 4-7 dni sprawdzić:
   - Czy queries `{marka}` mają wyższy CTR po dodaniu ceny i count w title?
   - Czy nowe queries pojawiają się (np. `byd cena 2026`)?
   - Czy pozycje nie spadły dla `{marka} z chin` (BYD pos 30 itp.)?

3. **Empty hubów decision** — 18 marek bez listings (Mercedes-Benz, Rolls-Royce, Aston Martin, Bentley, Ferrari itd.). Aktualnie WP fallback `{TermName} - {SiteTitle}` w title, ale frontend pokazuje 0 listings. Decyzja: noindex (RankMath term meta) lub zostawić — osobny temat.

4. **RSA Ads pivot** — kolejny krok sesji. Dla model-level KW dodać warianty z „import" (DFS pokazało 60% w top 10).

---

## 7. Rollback

```bash
# Cofnij klasę
cp wp-content/plugins/asiaauto-sync/includes/class-asiaauto-hub-title-generator.php.bak-2026-05-19-pre-make \
   wp-content/plugins/asiaauto-sync/includes/class-asiaauto-hub-title-generator.php

# Przywróć termmeta (parse TSV i UPDATE każdy)
# UWAGA: WP-CLI sub-command "regen-make-titles" zostaje w cli/class-asiaauto-cli.php — nie szkodzi
```

Backup termmeta `before-bulk.tsv` ma TSV: `term_id slug count old_title old_descr`. Skrypt PHP do restore (jeśli potrzeba): parse TSV → `wp term meta update <id> rank_math_title <old_title>`.

---

## 8. Memory cross-link

- `project_session_2026_05_19_make_titles_pivot.md` — chronologia sesji
- `project_session_2026_05_07_seo.md` — kontekst v0.32.43 (serie generator)
- `project_session_2026_05_18_seo_hubs_in_progress.md` — DFS standard scan data
- `feedback_no_decisions_without_data.md` — przypomnienie: data check przed pivot
