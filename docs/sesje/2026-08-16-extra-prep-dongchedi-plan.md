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

### Krok 1 — dolać to, co da się dolać od ręki — WYKONANE 16.08

```
cd ~/domains/primaauto.com.pl/public_html
wp eval-file ~/projekty/primaauto/scripts/merge-spec-from-twin.php apply   # 22 oferty, +303 pola śr.
php ~/projekty/primaauto/scripts/dolej-spec-z-banku.php 100 0 apply        # 8 ofert,  +321 pól śr.
```

Razem **30 ofert** uzupełnionych. Bank dał mniej niż w dry-runie (8 zamiast 22), bo bliźniak
załatał część celów wcześniej — kolejność „najpierw bliźniak, potem bank" jest właściwa.

⚠️ **Oba skrypty wymagają jawnego `apply`** — bez tego robią dry-run i kończą się komunikatem
o sukcesie, który łatwo wziąć za zapis. `merge-spec-from-twin.php` czyta flagę z `$args`,
nie z `DRY=1`, mimo tego co mówi jego własny nagłówek. Po biegu weryfikuj w bazie
(`_asiaauto_spec_inherited_at`), a nie po komunikacie.

### Krok 2 — ~~odświeżyć bank~~ NIE ROBIĆ TEGO

⚠️ **Sprawdzone na produkcji 16.08 — przebudowa bank NISZCZY, nie wzbogaca.**

`zbuduj-bank-specyfikacji.php` buduje bank **od zera** z ofert żywych (`publish`, ≥ 200 pól)
i nadpisuje pliki marek. Nie scala z tym, co już w banku jest. Efekt biegu 16.08:

```
przed:  933 warianty
po:     574 warianty   (ubyło 367, przybyło 8)
```

Wypadły warianty z ofert w międzyczasie wygaszonych — czyli **dokładnie to, po co bank
powstał**: „odcina los danych od losu ogłoszenia" (komentarz w nagłówku samego skryptu).
Bank przywrócony z `~/backups/primaauto/2026-08-16/spec-bank-przed-przebudowa.tar.gz`,
wynik nieudanej przebudowy leży obok w `uploads/asiaauto/spec-bank.po-przebudowie-2026-08-16/`.

Wniosek: **bank przebudowujemy wyłącznie skryptem, który scala** (dziś takiego nie ma).
Przed każdym uruchomieniem `zbuduj-bank-specyfikacji.php` — tar katalogu i porównanie liczby
wariantów po biegu.

### Krok 3 — domknąć pętlę cronem — WYKONANE 16.08

Nocna sekwencja jest kompletna:

```
04:25  gaszenie martwych ofert
04:35  dolewanie z bliźniaka   ← NOWY WPIS
04:45  dolewanie z banku
```

Wpis dodany przez `~/bin/cron-install` (backup: `~/backups/crontab/crontab-2026-08-16-170834.bak`):

```
35 4 * * * /bin/bash -lc 'cd /home/host476470/domains/primaauto.com.pl/public_html && \
  wp eval-file /home/host476470/projekty/primaauto/scripts/merge-spec-from-twin.php apply' \
  >> /home/host476470/.claude/merge-spec-twin.log 2>&1
```

Wybrany osobny wpis, nie skrypt-orkiestrator: dwa kroki są niezależne (bliźniak = WP-CLI, bank =
czysty PHP), a rozdzielone wpisy dają osobne logi i osobne wyłączanie. Kolejność bliźniak → bank
jest istotna — bliźniak dowozi więcej i zdejmuje bankowi część celów (w biegu 16.08: 22 vs 8).

Przebudowy banku w tej sekwencji **nie ma** i być nie może, dopóki skrypt nie scala (patrz krok 2).

Pomiar biegu: 1,4 s wall-clock, 216 MB RSS, bez limitu porcji — skrypt czyta wszystkie oferty
`publish` do pamięci. Przy obecnej skali (~2 300 ofert) mieści się w LVE z zapasem; gdyby magazyn
urósł kilkukrotnie, trzeba będzie dołożyć porcjowanie.

Weryfikacja po pierwszej nocy: `tail ~/.claude/merge-spec-twin.log` — linia „Ofert zapisanych: N".
Log dopisywany, nie rotowany.

### Krok 4 — reszta, czyli ~155 ofert bez dawcy

Zostają warianty premierowe, których nie ma ani w bazie, ani w banku — najczęstsze braki:

```
8 × Xiaomi SU7 Ultra 2025        4 × XPeng P7+ Long Range Max 2025
6 × Li Auto i8 Standard 2025     3 × Leapmotor C11 BEV 580 Premium 2024
5 × Xiaomi SU7 RWD ULR 2025      3 × XPeng Mona M03 (515 / 620) 2025
```

Te załatają się **same**, gdy tylko wejdzie bogatszy egzemplarz tej samej wersji — od 17.08 robi to
nocny cron z kroku 3. Zanim dawca wejdzie, karty mają szkielet techniczny, bez listy wyposażenia.

Jeśli któryś model jest ważny sprzedażowo teraz, dwa wyjścia: opis ręczny Ruslana (wtedy narzędzia
go nie ruszą — pomijają wpisy ręczne) albo punktowe scalenie z bliźniaka che168
(`merge-spec-from-dongchedi-twin.php` działa w drugą stronę, ale wzorzec ten sam).

## Stan po biegu 16.08 (17:10, po włączeniu crona)

| źródło | chude (< 100 pól) | zdrowe | razem `publish` |
|---|---|---|---|
| dongchedi | 74 | 826 | 900 |
| che168 | 145 | 1161 | 1306 |
| wpisy ręczne | 9 | 93 | 102 |

Chude dongchedi to w większości sztuki, które weszły **po** uzupełnieniu. Dry-run bliźniaka po
biegu: 155 celów, 0 do zapisu — cała reszta czeka na dawcę, którego jeszcze nie ma w bazie ani
w banku (krok 4). Od 17.08 cron będzie je zbierał sam, w miarę jak dawcy wchodzą z importu.

## Do rozstrzygnięcia

1. ~~Krok 3 — cron czy dalej ręcznie po zaciągu.~~ **Rozstrzygnięte 16.08: cron, wpis 04:35.**
2. Czy chudy zapas (< 100 pól) ma w ogóle wisieć na `publish`, czy trzymać w `draft` do czasu
   uzupełnienia. Dziś wisi — 34 nowe oferty z dzisiaj są widoczne ze szkieletem.
3. Czy reklamować regresję u dostawcy — od 20.07 płacimy za dane, które są 8× uboższe niż przed.
