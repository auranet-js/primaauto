# T-186 — pomiar kontrolny migracji na Che168 + mail statusowy do Ruslana

**Data:** 2026-08-03
**Plugin:** 0.34.16 (bez zmian w kodzie — sesja pomiarowa i komunikacyjna)

## Po co

Sprawdzenie, jak pracuje automatyczne źródło Che168 kilka dni po przełączeniu, oraz
poinformowanie Ruslana o stanie prac i o tym, jak sam konfiguruje wykluczenia modeli.
Zmian w kodzie ani w konfiguracji **nie było**.

## Stan faktyczny (odczyt z produkcji)

| co | wartość |
|---|---|
| tryb dongchedi | `verify` — `added=0` w każdym biegu, ostatnie nowe auto 30.07 |
| weryfikacja dongchedi | pracuje: 03.08 wygaszonych 210 (draft) + 254 (kosz) |
| che168 opublikowane | 593 |
| przyrost che168 | 84–98/dobę (31.07: 98, 01.08: 84, 02.08: 96) |
| podział bazy (publish) | dongchedi 1489, che168 593, bez źródła 104 |
| filtry | parytet: rocznik ≥2024, km ≤40 000, cena ≥85 000 ¥, 31 miast |
| marki | che168 45, dongchedi 57 — różnica to marki, których słownik Che168 nie zna pod tymi nazwami |
| wykluczenia modeli | Volvo 22, Mazda 21, BYD 3 |

Jakość nazewnictwa: na żywych ofertach po chińsku został **1 tytuł** (BYD 唐L) i **1 wartość
paliwa** (汽油+48V轻混系统). 108 termów `serie` z chińskimi znakami to puste wpisy ze słownika —
na stronie niewidoczne. Rozjazdy przypisania ofert do modeli wyłapujemy na bieżąco.

## Komunikacja

Mail do Ruslana (przez Janka, `send-to-jan` → `js@auranet.com.pl` → `china@primaauto.com.pl`)
z dwoma zrzutami panelu: zakładka „Filtry importu" dla źródła Che168 oraz sekcja
„Modele — wykluczenia" z rozwiniętą marką BYD. Treść: co działa, co ze starym źródłem,
jak działa wykluczanie modeli, czego się jeszcze spodziewamy, rozliczenie godzin.

Rozliczenie podane klientowi: **62 h** z wyceny 85–100 h, plus kilkanaście godzin rezerwy
na dostrojenie po jego testach.

## Ustalenia

1. **Strony „Postęp prac" nie aktualizujemy** — task jest otwarty, rezerwa na testy klienta
   jeszcze nie zeszła. Wpis do `docs/kosztorys/dane/postep.json` przy domknięciu T-186.
2. **Panel nie pokazuje trybu weryfikacji.** Ramka „Synchronizacja API" wyświetla Dongchedi
   jako „aktywne", bo czyta samo `enabled`; tryb `verify` siedzi w osobnej opcji. W mailu
   wyjaśnione słownie. Dorobienie etykiety „tylko weryfikacja" w UI — propozycja, czeka na
   decyzję (zmiana kodu).
3. Wpis **T-186 w `docs/QUEUE.md:58` pozostaje przeterminowany** („P3, GATED, dongchedi
   główny") — realny stan opisuje T-222. Do uporządkowania przy domknięciu.
