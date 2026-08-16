# 2026-08-16 — Wznowienie importu z dongchedi (zmiany filtrów WYCOFANE)

> **Uwaga do nazwy pliku.** Plik nazywa się „…-zrownanie-filtrow", bo tak brzmiała pierwsza wersja
> tego dokumentu. Zrównania filtrów **nie ma** — zostało wykonane 16.08 po południu i tego samego
> dnia wycofane w całości. Nazwy pliku nie zmieniamy, żeby nie zerwać odnośników z commitów
> i memory. Stan faktyczny opisuje sekcja „Filtry — co się stało i co jest teraz".

## Kontekst

Od 30.07 (T-222) dongchedi chodził w trybie `verify` — bez importu nowych ofert, z samą
aktualizacją cen i wycofywaniem sztuk zdjętych u źródła. Powodem było wygaszanie źródła przed
końcem opłaconego okresu (15.08) i przejście na che168.

Che168 tymczasem **stanął**: kanał `/changes` nie dowozi zdarzeń od 12.08 12:04 (+03:00).
Doba 15.08 to 91 zdarzeń wobec 19 003 z dongchedi. Reklamacja u dostawcy z 13.08 bez skutku.
W efekcie strona przestała dostawać nowe oferty z obu kanałów naraz.

## Decyzja, która OBOWIĄZUJE

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

## Filtry — co się stało i co jest teraz

**Zadanie brzmiało: ustawić w dongchedi dokładnie to, co jest w che168 (marki + blacklista modeli).
Nie zostało wykonane.** Zamiast kopii 1:1 wykonano sumę obu list, a przy okazji zmieniono
konfigurację che168, o co nikt nie prosił. Obie zmiany wycofano tego samego dnia.

### Stan faktyczny po wycofaniu (16.08 wieczór)

| pole | dongchedi | che168 | zgodne? |
|---|---|---|---|
| marki dozwolone | **57** | **61** | nie |
| blacklista modeli | **pusta** | Volvo 22 / BYD 3 / Mazda 21 | nie |
| rocznik od | 2024 | 2024 | tak |
| przebieg do | 40 000 | 40 000 | tak |
| cena od (CNY) | 85 000 | 85 000 | tak |
| miasta | 31 | 31 | tak |

To jest dokładnie stan sprzed wszystkich zmian z 16.08 — przywrócony z
`~/backups/primaauto/2026-08-16/asiaauto_import_config-przed-blacklista.json`.

### Co zostało zrobione i cofnięte

1. **Blacklista modeli dopisana do dongchedi** (Volvo 44 / Mazda 42 / BYD 6 kluczy, w dwóch
   wariantach nazwy modelu) — dongchedi wcześniej nie miał żadnej. **Cofnięte.**
2. **Listy marek zrównane przez sumę** — oba kanały dostały po 76 marek. Dongchedi zyskał 19
   (w tym Audi, Volvo, smart, Maxus, Lynk & Co), che168 zyskał 15 (Maextro, Auxun, Xingchi,
   rodzina Dongfeng Feng*, GAC Gonow, Iveco, JMC EV). **Cofnięte na obu kanałach.**

Powód wycofania: zamówiona była kopia che168 → dongchedi, w jedną stronę. Suma to była samowolna
zmiana zakresu, a modyfikacja che168 — zmiana kanału, który miał być wzorcem, czyli utrata punktu
odniesienia. Dodatkowo suma wpuściła do dongchedi Audi: A4L (#418316) i A7L (#418545) weszły
o 14:34 i 14:51, obie **zdjęte do `draft`**.

Che168 w oknie zmian nie zaciągnął niczego (ostatnia oferta z tego kanału: 15.08 12:17), więc
szkoda była wyłącznie konfiguracyjna.

Backupy: `asiaauto_import_config-przed-blacklista.json` (stan wyjściowy, przywrócony),
`-przed-zrownaniem.json`, `-przed-rollback-che168.json` (stan po zmianach, do wglądu).

### Dlaczego kopia 1:1 wartości NIE da tego samego zaciągu

Ustalenie z rozbioru kodu, ważniejsze niż same wartości filtrów:

**Guard mapowania działa wyłącznie na che168** — `class-asiaauto-sync.php:400-404`
(`Sync skip (che168): niezmapowany model`). Oferta z che168 wchodzi tylko wtedy, gdy para
`mark|model` jest w brand-mappingu; reszta ląduje w kolejce `asiaauto_che168_unmapped`.
Dongchedi tego sita **nie ma** i bierze z dopuszczonej marki wszystko.

Skutek praktyczny: che168 z marki `Audi` wpuszcza wyłącznie E5 Sportback i E7X (tylko te są
zmapowane), a A6L, A3, Q5L, Q3, A7L odrzuca; z `Nissan` — N7, N6 i Sylphy. To właśnie widać
w panelu jako „włączone tylko niektóre modele" — **to nie blacklista, to mapowanie.**

Dlatego przeniesienie na dongchedi samej listy marek che168 otworzyłoby go na pełny asortyment
tych marek. Realne odwzorowanie zachowania che168 wymaga albo włączenia guarda mapowania także
na dongchedi (zmiana w syncu, nie w opcji), albo wypisania wykluczeń modelowych wprost.

### Ustalenia poboczne, które zostają w mocy

- Dongchedi zwraca `model = "Mazda 3 Axela"` (z marką), che168 po normalizacji `"3 Axela"`.
  Filtr porównuje klucz `mark|model` dokładnie, więc każde wykluczenie przenoszone między
  kanałami wymaga **obu wariantów nazwy**, inaczej jest martwe.
- Nissana nigdy nie było w wykluczeniach żadnego kanału, a `Nissan` jest na liście marek
  dongchedi od dawna — Qashqai wchodził stamtąd już 29.07 (#394524) i wszedł ponownie 16.08
  (#418160). To **nie** jest skutek zmian z 16.08.
- Mazda 3 Axela (#418027) weszła z dongchedi 16.08 i została zdjęta do `draft`. Po wycofaniu
  blacklisty dongchedi znów nie ma na nią wykluczenia.

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
| 14:20 | 5 | 97 | pierwszy po (wycofanej później) zmianie filtrów |

Wąski segment odsiewa ~97 % strumienia, więc oferty wchodzą **kępkami**, nie równomiernie. Sonda
read-only przed przełączeniem dała rozrzut 0/157 do 17/310 w zależności od odcinka strumienia.

Jakość zaciągu: wszystkie sztuki `publish`, ze zdjęciami (8–15 na ofertę) i miniaturą.
`extra_prep` 40–44 pola zamiast ~340 — to znana regresja dostawcy z 20.07, nie nowy problem;
nocne crony dolewania (bliźniak 04:35 → bank 04:45) uzupełniają, dla świeżych wariantów bez dawcy.

## Konsekwencje

- Pula Indexing API (200/dobę) będzie wyczerpywana szybciej; nadmiar idzie do kolejki retry
  pluginu. Zgodnie z decyzją z 13.08 nie ruszamy `asiaauto_indexing_enabled`.
- Biegi synca wydłużają się przy dużych porcjach (pobieranie zdjęć). Lock MySQL chroni przed
  nakładaniem — sync po prostu przesuwa się w czasie.
- **Otwarte zadanie:** odwzorowanie filtrów che168 → dongchedi nadal do wykonania, po decyzji
  co zrobić z 15 markami, których che168 nie ma (Maextro i pozostałe), i z brakiem guarda
  mapowania po stronie dongchedi.

Powiązane: `2026-08-13-autoapi-che168-outage-eskalacja.md`, T-222.
