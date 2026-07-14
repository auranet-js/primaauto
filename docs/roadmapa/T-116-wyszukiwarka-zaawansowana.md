# T-116 — Zaawansowana wyszukiwarka (parametry techniczne + wyposażenie)

> Status: **gotowy do odpalenia**, ale patrz „Ryzyko źródła" · Rozmiar: L
> Godziny realnie: **40–55 h** (Janek ~5–6 h, AI ~35–48 h) · Rynkowo: 100–130 h
> Zakres: **pełny** (decyzja Janka 2026-07-14 — z wyposażeniem, nie tylko liczby)

## Po co

Konkurent (azjaauto.com) ma filtry, których my nie mamy: nadwozie, paliwo, skrzynia, rocznik od-do, przebieg od-do, cena od-do **plus wyposażenie** (szyberdach, skórzana tapicerka, podgrzewane/wentylowane fotele, felgi aluminiowe, kamera 360, adaptacyjny tempomat, ABS…). Klient precyzyjnie dobiera auto zamiast przewijać 3056 ofert.

## Stan faktyczny — lepszy, niż zakładał kosztorys

Dzisiejsza wyszukiwarka (`class-asiaauto-inventory.php`, 1798 linii + `asiaauto-inventory.js`) **już ma pełne faceted search**: AJAX, REST, autocomplete, **liczniki zależne** (każda taksonomia liczona względem pozostałych aktywnych filtrów).

**Filtruje dziś (7):** marka, model, paliwo, nadwozie, rocznik, kolor, napęd + zakres ceny. Sort: data, cena ↑↓.

## Dane — dwie warstwy o zupełnie różnym koszcie

### Warstwa A — gotowa, czysta, kompletna (tanio)
| Pole | Pokrycie | Uwaga |
|---|---|---|
| `mileage` (przebieg) | 100% | 0–58 000 |
| `_asiaauto_horse_power` (moc) | 100% | 0–1548 |
| `engine` (pojemność) | 100% | cm³ |
| `transmission` (skrzynia) | 99,9% | **ma już taksonomię** — wystarczy dopisać do mapy |
| `condition` (nowy/używany) | 100% | **ma już taksonomię** |
| `_asiaauto_owners_count` | 100% | 0–5 |
| `interior-color` | 3,7% ⚠️ | za rzadkie, pomijamy (poprawi się przy Che168 — tam 63%) |

**To jest ~10–14 h.** Dopisanie do `$tax_map` + `parseParams()` (`:505-535`) + zakresy NUMERIC analogicznie do ceny + suwaki w UI + sortowanie po przebiegu/mocy/roku.

### Warstwa B — wyposażenie i parametry techniczne (drogo, wymaga sprzątania)
Dane siedzą w `_asiaauto_extra_prep` (JSON, 95,5% ofert, śr. **342 klucze** na ofertę).

**Dwa blokery:**

1. **🐛 USZKODZONY UNICODE (realny bug, nieznany do dziś).**
   Dongchedi zapisuje chińskie wartości jako **literalny ASCII bez ukośników** — w bazie leży `u4e2du5927u578bu8f66` zamiast `中大型车`. Przyczyna: `wp_slash(wp_json_encode())` w `class-asiaauto-importer.php:497` i `:930` — WordPress zjada `\` z `\uXXXX`.
   Na stronie tego nie widać, bo jest **łatka przy renderze** (`class-asiaauto-translator.php:321-330` + `fixBrokenFlatJson()` w `class-asiaauto-single.php:167`).
   **Konsekwencja: po tych wartościach nie da się filtrować w SQL.** Che168 zapisuje poprawnie — problem dotyczy tylko Dongchedi (96% bazy).

2. **JSON blob nie jest indeksowalny.** `meta_query` po `extra_prep` to skanowanie 3056 blobów. Przy 4–5 równoczesnych filtrach → zapytania sekundowe.

**Rozwiązanie (jedno, załatwia oba):** wypłaszczyć ~30 wybranych parametrów do **dedykowanej tabeli `wp7j_asiaauto_specs`** (post_id + kolumny typowane + indeksy). Migracja jest jednocześnie okazją do naprawy unicode.

## ⚠️ Ryzyko źródła — przeczytaj przed startem

Wybieramy parametry **z listy 105 wspólnych dla Dongchedi i Che168** — czyli takich, które przeżyją śmierć Dongchedi:

- ✅ **Przeżywa:** wymiary, masy, rozstaw osi, silnik, moc, pojemność, bateria, zasięg (CLTC/WLTC), przyspieszenie, prędkość max, zawieszenie, hamulce, opony, skrzynia, liczba miejsc/drzwi, bagażnik.
- ❌ **Ginie przy samym Che168:** większość **wyposażenia** — Dongchedi ma ~200+ flag (airbagi, kamery, radary, regulacje foteli, klimatyzacja, multimedia), Che168 daje **37**.

**Czyli: filtr „wyposażenie" jak u AzjaAuto będzie na samym Che168 pięciokrotnie uboższy.** To limit dostawcy, nie kodu. Jeśli auto-api potwierdzi, że Dongchedi umiera — warstwę wyposażenia trzeba przemyśleć (albo oprzeć na tych 37 flagach, albo odpuścić).

Dodatkowo: `che168-param-map.php` ma **84 wpisy**, przez co ~35 parametrów Che168 ląduje jako `param_XX` (m.in. `param_92` = wymiary, `param_133/134` = opony). **Rozszerzenie tej mapy to tani, wysoko-zwrotny task do zrobienia PRZED T-116.**

## Plan (kroki)

1. **Warstwa A** (10–14 h) — filtry po gotowych meta + sortowania. Wdrożyć **osobno i wcześniej**, bo daje efekt od razu.
2. **Wybór parametrów warstwy B** (Janek + Ruslan): ~30 z listy 105 wspólnych. Kryterium: czy klient realnie po tym szuka.
3. **Rozszerzyć `che168-param-map.php`** o brakujące `param_XX`.
4. **Tabela `wp7j_asiaauto_specs`** + migracja + naprawa unicode (regex `u([0-9a-fA-F]{4})` → prawdziwe znaki, wsteczna dla 2918 ofert).
5. **Wpiąć w importer** — zapis do tabeli specs przy każdym imporcie/update (addytywnie, obok istniejącego `extra_prep`; blob zostaje jako źródło prawdy).
6. **Filtry warstwy B w UI** — sekcja „Zaawansowane" (zwijana), checkboxy wyposażenia + suwaki parametrów. Liczniki zależne jak dziś.
7. **Wydajność:** liczniki po tabeli specs (JOIN), nie po postmeta.

## Strefy kruche

- **Importer** (`class-asiaauto-importer.php`) — strefa krucha. Dokładamy zapis do tabeli specs **obok** istniejącego (`extra_prep` nietknięty). Zero zmian w logice identyfikacji/slugów/rezerwacji.
- **Migracja danych na produkcji** — `mysqldump` przed. Naprawa unicode jest **odwracalna tylko z backupu**.

## Testy

**Automatyczne**
- Migracja: dla 100 losowych ofert porównaj wartość z tabeli specs vs wartość po przepuszczeniu bloba przez istniejącą łatkę renderującą. Muszą się zgadzać co do znaku.
- Filtry: zestaw 20 kombinacji → oczekiwana liczba wyników z niezależnego SQL.
- Regresja: 7 dzisiejszych filtrów zwraca **dokładnie te same wyniki** co przed zmianą.

**Półautomatyczne**
- Wydajność: 5 filtrów naraz + liczniki → czas zapytania przed/po (cel: <300 ms). Bez tego pomiaru nie wdrażamy.
- Po migracji: `SELECT` losowych 20 rekordów specs — czy chińskie znaki są prawdziwe (nie `u4e2d`).

**MCP (Chrome)**
- Filtrowanie po mocy + przebiegu + skrzyni → wyniki zgadzają się z licznikami.
- Sekcja zaawansowana na mobile 375px — czy da się jej używać (to jest największe ryzyko UX).
- Deep-link: URL z filtrami → odtwarza stan (potrzebne dla T-188, alerty zapisują kryteria z URL).

## Definicja zrobionego

- Warstwa A działa i jest wdrożona osobno.
- Tabela specs zasilana z importu, unicode naprawiony, `extra_prep` nietknięty.
- Filtry zaawansowane z licznikami zależnymi, <300 ms przy 5 filtrach.
- Stan filtrów w URL (deep-link) — fundament pod T-188.
- Wybrane parametry pochodzą z listy wspólnej Dongchedi ∩ Che168.
