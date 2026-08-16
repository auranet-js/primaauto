# 2026-08-16 — Wznowienie importu z dongchedi + zrównanie filtrów obu kanałów

## Kontekst

Od 30.07 (T-222) dongchedi chodził w trybie `verify` — bez importu nowych ofert, z samą
aktualizacją cen i wycofywaniem sztuk zdjętych u źródła. Powodem było wygaszanie źródła przed
końcem opłaconego okresu (15.08) i przejście na che168.

Che168 tymczasem **stanął**: kanał `/changes` nie dowozi zdarzeń od 12.08 12:04 (+03:00).
Doba 15.08 to 91 zdarzeń wobec 19 003 z dongchedi. Reklamacja u dostawcy z 13.08 bez skutku.
W efekcie strona przestała dostawać nowe oferty z obu kanałów naraz.

## Decyzja

**Dongchedi wraca do pełnego importu**, oba kanały pracują równolegle.

```
wp option update asiaauto_sync_mode_dongchedi full
```

Kursor `asiaauto_last_change_id_dongchedi` został na głowie strumienia (5 140 156), więc sync
**nie ciąga zaległości** z okresu `verify` — wchodzą wyłącznie zdarzenia od momentu przełączenia.
Taka była intencja: zapas ma się uzupełniać na bieżąco, bez jednorazowego zalewu.

Che168 **nie wymagał żadnej zmiany** — jest włączony (`enabled=1`, tryb `full`, status `publish`).
Nie stoi po naszej stronie, tylko po stronie dostawcy. Backfillu katalogowego (luka 529 ofert)
świadomie nie odpalamy.

## Zrównanie filtrów

Kanały mają **osobne** zestawy filtrów w `asiaauto_import_config` i tak zostaje — na dziś są
ustawione identycznie, ale każdy da się zmienić niezależnie.

| co | przed | po |
|---|---|---|
| marki dozwolone | dongchedi 57, che168 61 | **76 na obu** (suma obu list) |
| wykluczenia modeli | dongchedi brak, che168 Volvo/BYD/Mazda | **identyczne na obu**: Volvo 44, Mazda 42, BYD 6 klucze |
| rocznik / przebieg / cena / miasta | już zgodne | bez zmian (2024+, ≤40 tys. km, ≥85 tys. CNY, 31 miast) |

Zrównanie zrobione przez **sumę** list marek, nie przez nadpisanie: kopia 1:1 z che168 zabrałaby
dongchedi 15 marek, których che168 nie zna pod tą nazwą (Maextro, Chery Fengyun, rodzina Dongfeng),
a Maextro/Luxeed właśnie wchodzi. Nazwy z obcego dialektu (`大通`, `奥迪AUDI`, `eπ`) są martwe na
drugim kanale — nigdy nie dopasują, nie szkodzą.

**Wykluczenia trzymamy w dwóch wariantach nazwy modelu**, bo kanały mówią innym dialektem:
dongchedi zwraca `model = "Mazda 3 Axela"` (z marką), che168 po normalizacji `"3 Axela"`.
Filtr porównuje klucz `mark|model` dokładnie, więc bez obu wariantów przeniesienie byłoby martwe.
Zweryfikowane na żywej ofercie: `Mazda 3 Axela` → ODRZUCONA, `Nissan Qashqai` → przechodzi
(Nissana nigdy nie było w wykluczeniach żadnego kanału).

Backupy opcji przed zmianami: `~/backups/primaauto/2026-08-16/asiaauto_import_config-przed-blacklista.json`
oraz `-przed-zrownaniem.json`.

## Czego NIE zrobiliśmy i dlaczego

**Pre-filtr na danych z `/changes` dla dongchedi — odrzucony.** Mechanizm istnieje od 22.07, ale
tylko dla che168 (`class-asiaauto-sync.php:333`): kryteria sprawdzane są na danych ze strumienia,
zanim padnie płatne `getOffer()`. Dongchedi tego nie ma, więc każde zdarzenie `added` kosztuje
jedno wywołanie — przy ~11–12 tys. `added` na dobę to znacznie więcej niż ~400 realnie potrzebnych.

Sprawdzone: werdykt filtra z `/changes` = werdykt z `/offer` w 60/60 par, czyli pre-filtr niczego
by nie zgubił. Mimo to **kodu nie ruszamy**: dongchedi chodził bez pre-filtra od zawsze, a teza,
że dostawca zacznie odbijać zapytania, to spekulacja bez dowodu. Wracamy do tematu dopiero, gdy
w logu synca pojawią się realne 429 / `Failed to fetch changes`.

## Pomiary z pierwszych biegów

| bieg (UTC) | added | skipped | uwaga |
|---|---|---|---|
| 13:02 | 0 | 126 | pierwszy w trybie `full` |
| 13:17 | 0 | 53 | |
| 13:33 | 2 | 350 | pierwsze oferty |
| 13:53 | 13 | 142 | bieg wydłużony — pobieranie zdjęć |
| 14:02 | 0 | 13 | |
| 14:20 | 5 | 97 | pierwszy po zrównaniu filtrów |

Wąski segment odsiewa ~97 % strumienia, więc oferty wchodzą **kępkami**, nie równomiernie. Sonda
read-only przed przełączeniem dała rozrzut 0/157 do 17/310 w zależności od odcinka strumienia.

Jakość zaciągu: wszystkie sztuki `publish`, ze zdjęciami (8–15 na ofertę) i miniaturą.
`extra_prep` 40–44 pola zamiast ~340 — to znana regresja dostawcy z 20.07, nie nowy problem;
nocny cron `dolej-spec-z-banku.php` (04:45) dolewa z banku, dla świeżych wariantów bez dawcy.

Mazda 3 Axela (post #418027) weszła przed zmianą wykluczeń — zdjęta do `draft`.

## Konsekwencje

- Pula Indexing API (200/dobę) będzie wyczerpywana szybciej; nadmiar idzie do kolejki retry
  pluginu. Zgodnie z decyzją z 13.08 nie ruszamy `asiaauto_indexing_enabled`.
- Biegi synca wydłużają się przy dużych porcjach (pobieranie zdjęć). Lock MySQL chroni przed
  nakładaniem — sync po prostu przesuwa się w czasie.
- Che168 przy starcie dostanie 15 marek więcej; guard mapowania nadal odsieje niezmapowane.

Powiązane: `2026-08-13-autoapi-che168-outage-eskalacja.md`, T-222.
