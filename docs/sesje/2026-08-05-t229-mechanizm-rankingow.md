# T-229 DONE — mechanizm rankingów + pierwszy wpis LIVE (2026-08-05)

> Kontynuacja sesji z 08-04. Wejście: spec `docs/roadmapa/T-229-szablon-rankingu.md` (D1–D9),
> lista 40 rankingów z typowaniem, Taski 1 i 2 zacommitowane.

## Co zrobione

Pipeline rankingowy w komplecie, wszystkie kroki przebiegnięte na żywych danych:

```
ranking_market.py --dongchedi   → 320 pozycji źródłowych, 105 marek obcych odsianych
ranking_stock.py                → 15 z 30 pozycji mamy w ofercie, wszystkie ze zdjęciem
ranking_generate.py --apply     → draft #406121, bramki przeszły
ranking_refresh.py --oferta     → 31 bloków, guard na spadek dopasowań ustawiony
```

Nowe pliki: `ranking_names.json` (tablica nazw), `rankingi.json` (definicje),
`ranking_generate.py`, `ranking_refresh.py`. Zmienione: `ranking_market.py` (źródło Dongchedi),
`ranking_stock.py` (strażnik marki), `build-llms.php` (sekcja rankingów).

## Czego się dowiedzieliśmy o źródle

| Rzecz | Stan |
|---|---|
| Najnowszy pełny miesiąc | **202606** (czerwiec 2026). Lipiec 05.08 jeszcze nie wszedł |
| Zapytanie o przyszły miesiąc | oddaje po cichu ostatni dostępny — **bez znacznika w odpowiedzi** |
| Uczciwy UA (`PrimaAutoBot`) | HTTP 200 i **puste ciało**; wygląda jak błąd parsera |
| Nazwy | wyłącznie CJK, także marek; resolver pluginu trafia 13/70 |
| Marki obce w rankingu | 105 z 320 pozycji (Toyota, VW, BMW…) — trzeba odsiać samemu, `nation` nie działa |
| Weryfikacja D9 | `理想i6 = 21 453`, `小米YU7 = 14 324` — zgodne ze zrzutami WeChat co do sztuki |

## Dwa błędy złapane po drodze

1. **„AITO M6" wchodziło w hub „M6", który jest vanem GAC-a.** Dopasowanie po samej nazwie
   modelu jest za słabe — nazwy krótkie powtarzają się między markami. Task 2 dostał
   `ta_sama_marka()`, tolerancyjny na submarki („Geely Galaxy" ↔ „Geely").
2. **Narracja cytowała stan naszej oferty** („mamy 5 sztuk od 240 000 zł"). To liczba, która
   rotuje codziennie, w warstwie, która się nie odświeża — za tydzień byłaby nieprawdziwa.
   Teraz: liczby oferty tylko w tabeli i w bloku `_podsumowanie`, a bramka wzorcowa blokuje
   ceny i sztuki w tekście. Zdanie „ten model mamy w ofercie" bez liczby przechodzi.

## Bramka D6 przed publikacją

GSC 06.07–04.08: fraza „chińskie suv"/„chiński suv" **nie ma u nas URL-a w top 20**. Widać
wyłącznie warianty superlatywne („największy", „najlepszy", „najmocniejszy chiński suv"),
obsługiwane przez `/samochody/aito/m9/` z pozycji 1–2,7 przy 2–9 wyświetleniach. Ranking celuje
w intent częstotliwościowy, nie jakościowy — stąd tytuł „najchętniej kupowane", a nie „najlepsze".
Kanibalizacji nie ma, ale przy kolejnych rankingach warto pamiętać, że **hub M9 zbiera dziś
superlatywy SUV-owe** i nie chcemy mu ich odbierać.

## Zamknięte w tej samej sesji (po akcepcie Janka)

Patche PHP wgrane, wpis **opublikowany**:
https://primaauto.com.pl/rankingi/chinskie-suvy-ranking-sprzedazy/

D9 zrobione przed publikacją — **NIO ES8, czerwiec: Dongchedi 8 966 vs cnevpost 8 969
(3 sztuki różnicy, 0,03%)**, plus zgodność sum per marka. Schema: `ItemList` 30 pozycji
+ `FAQPage` 5 pytań. Sekcja „Najnowsze rankingi" na `/marki/` włączyła się sama.
Sitemapa wymagała `wp rankmath sitemap generate` (ten sam cache co T-192/T-219).

⚠️ **RankMath wywala osobny węzeł `FAQPage` przy wpisach `post`** — trzeba go wpiąć
w `WebPage`, tak jak robi to sam plugin. Na CPT (Leksykon) działa inaczej, stąd mylące
„na wiki jest, tu nie ma". Wróci przy `porownania` i `poradniki`.

## Otwarte
- **Cron `ranking_refresh.py --oferta`** — spec mówi „dopiero po miesiącu obserwacji" (4.3).
- **63 nazwy modeli do uzupełnienia** w `ranking_names.json` (ogon zestawienia). Dziś nie blokują,
  bo są poniżej progu TOP-30; przy rankingach klasowych (terenówki, pickupy) zablokują.
- **Elektryki i terenówki mają w `rankingi.json` `gotowy: false`** — API nie ma filtra „tylko BEV"
  ani konstrukcji ramowej. Oba wymagają złożenia filtra po naszej stronie.

## Prompt kontynuacyjny

> Wracamy do rankingów. T-229 zamknięte — mechanizm działa, pierwszy ranking (SUV-y) jest LIVE:
> https://primaauto.com.pl/rankingi/chinskie-suvy-ranking-sprzedazy/
> Przeczytaj `docs/roadmapa/T-229-szablon-rankingu.md` (dwie sekcje „Wykonanie" i „Wdrożenie")
> oraz tę notatkę. Dalej T-230: kolejne tematy z `scripts/kb/rankingi.json`. Zanim ruszysz
> terenówki albo elektryki — one mają `gotowy: false`, bo API nie ma filtra konstrukcji ramowej
> ani „tylko BEV"; filtr trzeba złożyć po naszej stronie. Sprawdź też, czy weszły dane za lipiec
> (`ranking_market.py --dongchedi --klasy 11` pokaże okres) — jeśli tak, odśwież istniejący wpis
> komendą `ranking_refresh.py --ranking --wpis 406121` PRZED pisaniem nowych.
