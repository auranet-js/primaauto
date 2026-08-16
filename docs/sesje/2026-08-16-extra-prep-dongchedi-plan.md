# Extra prep dongchedi — stan i plan uzupełnienia

> Wątek otwarty 2026-08-16, po wznowieniu importu z dongchedi
> (ADR `docs/decyzje/2026-08-16-dongchedi-wznowienie-importu-i-zrownanie-filtrow.md`).
> Wszystkie liczby poniżej to pomiary z 16.08, nie szacunki.

## Problem

Od ~20.07 auto-api oddaje dla nowych ofert dongchedi **~40 pól zamiast ~340** — znika cała lista
wyposażenia, zostaje szkielet techniczny. To regresja po stronie dostawcy, nie nasza.

Dopóki dongchedi stał w trybie `verify`, problem nie rósł. Po wznowieniu importu **każda nowa
oferta wchodzi chuda**: 16.08 od przełączenia weszły 34 sztuki i **34 z 34 mają < 100 pól**.
Przy obecnym tempie (~30–40 ofert na 2 h) chudy zapas przyrasta szybciej, niż nadążają narzędzia
uruchamiane ręcznie.

## Stan magazynu (dongchedi, publish)

| pola `extra_prep` | ofert |
|---|---|
| 200+ (zdrowe) | 800 |
| 60–99 | 2 |
| 30–59 (regresja od 20.07) | 61 |
| 1–29 | 2 |
| brak w ogóle | 30 |

Zdrowa większość to zapas sprzed regresji. Chudy ogon rośnie od dziś.

## Narzędzia, które już mamy — i ile realnie dowożą

Zmierzone dry-runami 16.08 na obecnym magazynie (populacja: oferty `publish` z < 100 pól, 176–177 szt.):

| narzędzie | zasada dopasowania | trafia | efekt | w cronie? |
|---|---|---|---|---|
| `scripts/merge-spec-from-twin.php` | bliźniak w **naszej bazie**, exact `marka\|seria\|wersja\|rocznik` | **16 ofert** | +298 pól średnio | **nie** |
| `scripts/dolej-spec-z-banku.php` | bank `uploads/asiaauto/spec-bank/`, exact klucz wariantu | **22 oferty** | +313 pól średnio | tak, 04:45, limit 100/dobę |
| `scripts/zbuduj-bank-specyfikacji.php` | przebudowa banku z bogatych ofert | — | zasila powyższe | **nie** |
| `scripts/backfill-spec-autohome.php` | katalog Autohome po `spec_id` | **0 ofert** | — | nie dotyczy |

Wszystkie trzy pierwsze działają addytywnie: dolewają wyłącznie brakujące klucze, nigdy nie
nadpisują, stemplują ślad do rollbacku i pomijają wpisy ręczne Ruslana.

## Dlaczego katalog Autohome tu nie pomoże

To ścieżka zbudowana dla che168 i **dla dongchedi jest nieużywalna w obecnej postaci**:

- katalog jest kluczowany po `spec_id` (pliki `uploads/asiaauto/autohome-catalog/{specid}.json`, 555 szt.),
- **żadna z 873 ofert dongchedi nie ma `_asiaauto_spec_id`** — che168 ma go 1288 na 1306,
- w środku pliku wersja jest opisana chińską nazwą (`车型名称` → `新途V70 2023款 2.0T 精英版…`),
  a dongchedi podaje nazwy po angielsku — brak wspólnego klucza.

Użycie Autohome dla dongchedi wymagałoby wyszukiwania po nazwie po stronie Autohome, czyli nowej
roboty scrapingowej. **Nie wchodzi w ten plan** — odkładamy jako osobny temat, gdyby dwie ścieżki
poniżej okazały się niewystarczające.

## Plan

### Krok 1 — dolać to, co da się dolać od ręki (~38 ofert)

```
cd ~/domains/primaauto.com.pl/public_html
wp eval-file ~/projekty/primaauto/scripts/merge-spec-from-twin.php        # 16 ofert
php ~/projekty/primaauto/scripts/dolej-spec-z-banku.php 100 0 apply       # 22 oferty
```

Oba mają dry-run (`DRY=1` / brak `apply`) — przed każdym biegiem warto zobaczyć listę.

### Krok 2 — odświeżyć bank

Bank powstał **29.07 i od tego czasu nie był przebudowany**. Wszystko, co weszło bogate po tej
dacie, nie jest dawcą. Przebudowa `zbuduj-bank-specyfikacji.php` podniesie trafialność kroku 1
bez żadnego kontaktu z API.

### Krok 3 — domknąć pętlę cronem

Dziś w cronie jest wyłącznie dolewanie z banku (04:45, 100/dobę). Brakuje dwóch ogniw:
przebudowy banku i dolewania z bliźniaka. Docelowo nocna sekwencja:

```
przebuduj bank  →  dolej z bliźniaka  →  dolej z banku
```

Do decyzji: czy dokładamy to jako osobne wpisy w crontab (przez `~/bin/cron-install`), czy jeden
skrypt-orkiestrator. Bez tego kroku chudy zapas trzeba łatać ręcznie po każdym większym zaciągu.

### Krok 4 — reszta, czyli ~155 ofert bez dawcy

Zostają warianty premierowe, których nie ma ani w bazie, ani w banku — najczęstsze braki:

```
8 × Xiaomi SU7 Ultra 2025        4 × XPeng P7+ Long Range Max 2025
6 × Li Auto i8 Standard 2025     3 × Leapmotor C11 BEV 580 Premium 2024
5 × Xiaomi SU7 RWD ULR 2025      3 × XPeng Mona M03 (515 / 620) 2025
```

Te załatają się **same**, gdy tylko wejdzie bogatszy egzemplarz tej samej wersji — pod warunkiem
kroku 3. Zanim to nastąpi, karty mają szkielet techniczny, bez listy wyposażenia.

Jeśli któryś model jest ważny sprzedażowo teraz, dwa wyjścia: opis ręczny Ruslana (wtedy narzędzia
go nie ruszą — pomijają wpisy ręczne) albo punktowe scalenie z bliźniaka che168
(`merge-spec-from-dongchedi-twin.php` działa w drugą stronę, ale wzorzec ten sam).

## Do rozstrzygnięcia

1. Krok 3 — cron czy dalej ręcznie po zaciągu.
2. Czy chudy zapas (< 100 pól) ma w ogóle wisieć na `publish`, czy trzymać w `draft` do czasu
   uzupełnienia. Dziś wisi — 34 nowe oferty z dzisiaj są widoczne ze szkieletem.
3. Czy reklamować regresję u dostawcy — od 20.07 płacimy za dane, które są 8× uboższe niż przed.
