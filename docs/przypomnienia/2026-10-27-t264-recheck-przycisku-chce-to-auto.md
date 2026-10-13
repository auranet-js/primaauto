# Przypomnienie: T-264 „Zamów → zgłoszenie rezerwacji” — recheck pomiaru przycisku „Chcę to auto”

> Terminy: **27.10.2026** (14 dób po zmianie) i **10.11.2026** (28 dób po). Ten sam plik dla obu.
> Kontekst: `docs/analizy/2026-10-13-t264-pomiar-przycisku-chce-to-auto.md` (metoda, punkt odniesienia, czynniki zakłócające), spec `docs/roadmapa/T-264-zamow-na-zgloszenie-rezerwacji.md`, wdrożenie `docs/VERSIONS.md` 0.45.2 (13.10 ok. 13:30, karty katalogu ok. 14:00).

## Co zrobić

1. `cd ~/projekty/primaauto && python3 scripts/pomiar-cta-zgloszenie.py --zgloszenia --ga4 2026-09-15` (~70 s, logi + GA4 + baza).
2. Porównać okresy „przed” i „po” (doba 13.10 = przejście, poza obydwoma):
   - GA4: % wejść na oferty → `/zamow/` (baza **0,85%**, 15.09–12.10);
   - logi: kliki z karty oferty i z katalogu **na dobę** (baza ~6,2 i ~2,4 dziennie, 30.09–12.10). Uwaga: archiwum logów jest miesięczne — po 1.11 pliki października mogą zniknąć z `~/domains/primaauto.com.pl/logs/`; 27.10 zapisać wynik z logów w dokumencie analizy, zanim przepadnie;
   - baza: zgłoszenia na dobę (baza ~0,6) + ile z nich doszło do „potwierdzone”.
3. Sprawdzić czynniki zakłócające od 13.10: zmiany w Meta/Google Ads (`docs/meta/plan-kampanii.md`, `scripts/ads-recheck.py`), zaciąg ofert, inne zmiany na karcie oferty i `/zamow/` (`git log --since=2026-10-13`). Dopisać do tabeli w dokumencie analizy.
4. Wpisać wynik do sekcji „Recheck 27.10” / „Recheck 10.11” dokumentu analizy: liczby, kierunek, czy GA4 i logi zgadzają się co do kierunku, ocena (zmiana ≥ ±30% w klikach = sygnał; zgłoszenia — rozstrzygnięcie dopiero przy ≥ ±50%).

## Zrobione =

Sekcja rechecku w `docs/analizy/2026-10-13-t264-pomiar-przycisku-chce-to-auto.md` uzupełniona, commit + push, event w kalendarzu `✅`. Bez wniosków „działa / nie działa” przy różnicach w granicy szumu — wtedy wprost „za mało danych”.

## Dostępy

GA4 Data API (property 534017542, `~/secrets/google/tokens.json`, `scripts/ga4_query.py`), WP-CLI na primaauto (`wp db query`), logi `~/domains/primaauto.com.pl/logs/`. Wszystko lokalne, bez cross-project.
