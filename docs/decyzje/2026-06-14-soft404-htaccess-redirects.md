# 2026-06-14 — Soft-404 hubów modeli: statyczne 301 w .htaccess (nie PHP)

**Status:** wdrożone na produkcji 2026-06-14.

## Kontekst

Raport GSC „Nie znaleziono (404)" pokazywał ~600 URL-i, a „Sprawdź poprawkę" (Validate Fix)
zwracało „nie udało się poprawić". Pełny żywy recheck listy 696 URL (eksport z 2026-06-07):

- **528 → 301** (redirect z v0.32.53 działa, cele lądują na realnej treści — 39/40 w próbce),
- **147 → 200**, z czego **145 to soft-404**: HTTP 200 + „Nie znaleziono modelu",
- **21 → 404**: ~16 trwałych śmieci (scrape `/cars/`, `/datatables`, literalne wildcardy `/*`,
  `/wp-*.php`, typo `/samochodos/`, placeholdery `{banner_id}`) + ~4 resolver `/model/`.

### Korzeń soft-404

`themes/primaauto2026/taxonomy-serie.php:34` — gdy term `serie` nie rozwiązuje się pod daną
marką (stary/scalony/wycofany slug), szablon echo'uje „Nie znaleziono modelu." i kończy **bez
`status_header(404)`** → HTTP 200. W całym motywie nie ma ani jednego `status_header`. Generyczny
brand-fallback w `class-asiaauto-redirects.php` jest bramkowany `is_404()` (linia 462), a te URL-e
**nie są `is_404`** (rewrite trafia w archiwum taksonomii) → samonaprawiający redirect nigdy nie
odpala. Stąd 200-soft-404 na stałe, których walidacja GSC nie akceptuje (all-or-nothing + osobny
bucket „Soft 404").

Dlaczego walidacja padała: zbiór miesza (a) ~16 trwale-niefixowalnych śmieci poprawnie dających
404 i (b) 145 soft-404. Redirect z maja NIE był winny.

## Rozważane warianty (i czemu odrzucone)

1. **PHP: nowa metoda `redirectUnresolvedSerieHub()`** w `redirects.php` reużywająca brand-fallback.
   Wdrożona i **wycofana** — `get_term_link(serie)` zwraca URL `/model/{slug}/` (2-hop przez buggy
   resolver `/model/` → zrzut na generyczne `/samochody/`), sub-brand-make (`gac/hyper-hl`,
   `dongfeng/yipai-007`) → **pętla 12 hopów**, a wiele termów `serie` ma `parent=0` (orphany) →
   detekcja po parent-SQL zawodna. Przy okazji odsłonięty pre-existing bug: `/samochody/leopard/leopard-5/`
   renderuje treść Smart #5 (osobny temat). Wniosek: auto-301 w PHP jest tu zaplątany w istniejące
   bugi routingu/danych.

2. **Twardy 404** w szablonie (status_header(404) zamiast 200). Bezpieczne i poprawne SEO, ale
   walidacja GSC i tak nie „przejdzie" (Google nadal widzi 404), licznik spada wolno.

## Decyzja

**Statyczne 301 w `.htaccess`** — 145 reguł `RewriteRule ^samochody/<make>/<oldslug>/?$ /samochody/<make>/ [R=301,L]`,
każdy stary slug → hub swojej marki. Powód wyboru (decyzja Janka): to **stare slugi, nowe się nie
ponawiają** (importer slug fix v0.32.42), więc nie trzeba samonaprawiania PHP; statyczne reguły są
przewidywalne, bez zaplątania w routing PHP i bez pętli.

### Gwarancje bezpieczeństwa

- cele = **28 hubów marek**, wszystkie żywe (200), żaden nie jest w zbiorze źródłowym → **zero pętli**;
- scalenia marek uwzględnione przy generowaniu (`galaxy→geely`, `gac-aion-hyper→gac`,
  `dongfeng-yipai→dongfeng` itd. — mapa V61);
- reguły anchored (`^...$` + opcjonalny `/`) → trafiają wyłącznie dokładny stary URL;
- wstawione **między `# END AsiaAuto Login Hide` a `# BEGIN WordPress`** — przed catch-all WP, po
  login-hide; smoke test potwierdził nietknięty login-hide, żywe huby, listingi, homepage.

### Zakres / poza zakresem

- Batch = 145 soft-404 z raportu GSC (lista: `docs/produkcja/htaccess-soft404-block-2026-06-14.txt`).
- **Poza batchem:** 4 huby render-bug sub-brand-make (hyper-hl, yipai-007, hyper-ht, jetour-shanhai-t1)
  — mają stock, NIE wolno kierować na markę; osobny temat z 2026-06-13.
- ~3 stare slugi z żywym odpowiednikiem (`tang-dm`→`tang-dm-i`, `denza-z9-gt-dm`→`z9-gt-dm`) celują
  w hub marki (decyzja: prościej/spójnie, nie hub modelu).

## Konsekwencje

- 145 soft-404 → 301 (1 hop), wypadną z błędów GSC przy recrawl (tygodnie). Akcja user-side:
  „Sprawdź poprawkę" w raporcie 404. NIE Indexing API (wspólna quota).
- Reszta ~16–21 śmieci zostaje 404 (norma, Google sam porzuca).
- `redirects.php` netto bez zmian (próba PHP wycofana, backup `*.bak-2026-06-14-soft404`).
- Backup `.htaccess`: `public_html/.htaccess.bak-2026-06-14-soft404`.

## Powiązane

- ADR `2026-05-20-redirecty-404-cleanup-gsc.md` (redirectHubMakePrefix is_404-gated — to on nie łapał soft-404).
- T-160: kontaminacja cross-brand (Trumpchi M8 pod AITO) — wykryta przy okazji, osobny task.
