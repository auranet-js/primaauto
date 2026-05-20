# Generyczne redirecty 404 — porządkowanie GSC (301 zamiast 410 dla hubów)

- **Data:** 2026-05-20
- **Status:** przyjęta, wdrożona
- **Obszar:** SEO / `class-asiaauto-redirects.php`
- **Wersja wdrożenia:** v0.32.53

## Kontekst

Googlebot generował ~1300 trafień 404/dzień (≈3,6% crawla): 209 unikalnych martwych hubów
`/samochody/<make>/<serie>/` + ~1100 sprzedanych listingów `/oferta/`. Przyczyny:
- huby: stare slugi sprzed importer slug fix (v0.32.42) — prefiks marki (`changan-uni-z` zamiast
  `uni-z`) oraz zmiany zapisu (`sea-lion-07dm` → `sealion-7-dm`);
- listingi: trwale usunięte posty (>30d po sprzedaży), poza zasięgiem `detectListingNotFound`
  (czytał terms z `wp_get_object_terms($post_id)`, co wymaga istnienia posta).

Istniejące mapy V61/V62/V63 to hardcode konkretnych przypadków — nie skalowały się.

## Decyzja

Redirecty generyczne, **samonaprawiające**, bez hardcode i — kluczowe — **bez 410 dla hubów**.

### Huby: 301, nigdy 410

`redirectHubMakePrefix()` (template_redirect prio 1, tylko na `is_404`), trzy warstwy:
1. prefiks marki → odetnij → 301 na żywy term (`get_term_link`),
2. normalized match (usuń myślniki + wiodące zera) → 301 na żywy term o tej samej formie,
3. brand fallback: model martwy/zmieniony, marka żyje → 301 na hub marki.

### Listingi: 301 na hub, 410 jako ostateczność

`resolveHubFromSlug()` parsuje markę+model ze slugu URL-a (longest-prefix po slugach marek),
działa po trwałym usunięciu. 301 na hub modelu → hub marki. `send410()` tylko gdy nawet marki
nie da się rozpoznać.

## Dlaczego huby NIE dostają 410 (sedno decyzji)

Modele w primaauto **wracają** — dziś brak Geely Binyue, jutro import → term powstaje →
`/samochody/geely/binyue/` znów = 200. 410 (ani hardcode 301) nie może blokować tego powrotu.
Rozwiązanie: redirect **warunkowany `is_404`** — gdy model wróci (URL=200), redirect się nie
odpala. To samonaprawiające, bez ręcznej ingerencji.

Uwaga: 410 samo w sobie NIE banuje URL-a na zawsze (Google re-indeksuje gdy wróci 200) — ale
hardcodowanie celu blokowałoby powrót. Stąd podejście generyczne, nie mapa.

Listingi to inny przypadek: konkretny egzemplarz (unikalny inner_id) po sprzedaży nie wraca,
więc 410 jest tam semantycznie poprawne — ale w praktyce ~0 (prawie każdy slug ma rozpoznawalną
markę → brand fallback 301).

## Konsekwencje

- **677/683 (99,1%) martwych URL-i → 301** (pomiar curl na liście z logów maja). Zostaje 5 śmieci
  (U+2060 w URL, listing bez roku) jako 404 — to nie realne strony.
- Regression zero: żywe huby/listingi/feed bloga = 200; `super-v23` ożył (200, nowy import).
- Feed taksonomii (`/samochody/<x>/feed/`) → 301 na hub; główny `/feed/` bloga nietknięty.
- Backupy: `class-asiaauto-redirects.php.bak-2026-05-20-pre-generic-404`, `*-pre-brand-fallback`.

## Zdiagnozowane przy okazji (osobne taski)

- **1876/2239 serie z count=0** (puste huby) — NIE w sitemapie (RankMath wyklucza puste), nie
  zgłaszane Google. Do rozważenia: `noindex` gdy count=0.
- rewrite slug taksonomii `serie` = `model` → część `get_term_link` daje 2-hop chain
  (`/model/<slug>/` → `/samochody/<make>/<slug>/`). Kosmetyka.

## Cleanup GSC

20.05.2026 uruchomiono w GSC „Sprawdź poprawkę" dla raportu „Nie znaleziono (404)".
NIE używać Indexing API do tego (zżera wspólną quotę 200/dz per GCP project).
