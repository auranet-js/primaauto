# ADR 2026-08-13 — Eskalacja do auto-api.com (kanał Che168 stanął) + backfill z magazynu

## Kontekst

Zgłoszenie Janka: „bardzo mało wchodzi nowych ofert". Diagnoza na produkcji (plugin v0.34.22) wykluczyła regresję po naszej stronie.

**Mechanika działa:** cron `asiaauto_sync_changes` co 15 min, `asiaauto_sync_enabled_che168=1`, `asiaauto_sync_status_che168=publish`, filtry bez zmian od parytetu 30.07 (61 marek, rocznik ≥2024, km ≤40 000, cena ≥85 000 ¥, 31 miast). Pliki synca/importera nietknięte od 30.07 — ostatnie edycje w pluginie to PayU (10.08).

**Kanał /changes che168 stoi.** Ostatnie zdarzenie: **2026-08-12 12:04 (+03:00)**. Kursor `asiaauto_last_change_id_che168 = 10505382`, `/changes` na nim zwraca `result: []` i `next_change_id: null`. `getChangeId('che168','2026-08-13')` = 10505371, czyli zero zdarzeń w dobie 13.08. Dongchedi tym samym kluczem w tych samych biegach dowozi 180–380 zdarzeń → to nie klucz, nie łącze, nie konfiguracja.

**Przed zastojem — degradacja wolumenu** (zdarzeń/dobę w kanale che168):

| data | zdarzeń |
|---|---|
| 7.08 | 52 487 |
| 8.08 | 26 429 |
| 9.08 | 22 057 |
| 10.08 | 18 479 |
| 11.08 | 29 552 |
| 12.08 | 18 395 (strumień kończy się o 12:04) |
| 13.08 | 0 |

Do tego udział `added` spadł z ~15–20% do ~8–10% (próbki 300–500 kolejnych zdarzeń w kilku punktach doby). Razem: ~7–8 tys. `added`/dobę → ~1,8 tys., czyli 4×.

**Efekt u nas** (import auto, bez ręcznych): 154 / 83 / 92 / 146 / 81 na 3–7.08, potem **12 / 9 / 9 / 20 / 16** na 8–12.08 i 0 dnia 13.08. Skuteczność przejścia przez filtry pozostała stała ~1% (próbka 285 zdarzeń `added` z 12.08 → 0 przez filtr; odrzuty na rocznik+przebieg+cena+miasto, czyli filtry pracują poprawnie). **Spadek jest proporcjonalny do podaży źródła, nie do regresji kodu.**

## Decyzja 1 — reklamacja do dostawcy, WYSŁANA

Mail przygotowany przez Claude, przekazany Jankowi przez `send-to-jan`, **wysłany przez Janka 13.08 15:10** na `access@auto-api.com` (wątek „RE: API inquiry Dongchedi + Che168 — client project evaluation", FW na `claude@auratest.pl` = mail [255]).

Cztery osie:

1. Czy crawler Che168 padł po ich stronie i jaki ETA naprawy.
2. Opłacony okres kończy się **15.08** — rozliczenie downtime (credit lub przedłużenie) **przed** wystawieniem faktury.
3. Niezależnie od naprawy: czy da się ponownie wyemitować `added` dla ofert już obecnych w ich bazie Che168 albo dostać pełny snapshot dla zestawu filtrów. Strumień z definicji dowozi wyłącznie to, co pojawi się po naszym kursorze; `/offers` jest dziś jedyną drogą i jest wolne oraz limitowane.
4. Status crawlera **Dongchedi** — 15.07 pisali, że go konfigurują; od 1.07 praktycznie zero `added`.

Kopia treści: `tmp/autoapi-che168-outage-mail-2026-08-13.md`, oryginał w `/tmp/claude-mails/255/`.

## Decyzja 2 — backfill z magazynu (drugi kanał wejścia)

Strumień `/changes` nie ma jak dowieźć ofert, których `added` padł przed naszym kursorem (retencja ~7 dni) albo przed rozszerzeniem filtrów 30.07. Furtka `changed` jest dla che168 zamknięta — `data` niesie jedno pole `new_price`, prefilter odrzuca na braku marki i `getOffer()` nie leci. Jedyna droga to przelot magazynu: `scripts/che168-domknij-luke.php`.

**Dry-run 13.08 (`--pages=10`, 61 marek):**

```
przejrzane:            7239
przeszły filtr:        761
orphany (brak mapy):    89
już w bazie:           143
luka (do wzięcia):     529
```

529 ofert przechodzi wszystkie filtry Ruslana, ma mapowanie i nie ma ich u nas w żadnym statusie — przy 853 publish che168 to +60% dla tego źródła. Liczba jest **dolną granicą** (10 stron na markę; część marek urwała się na limicie).

Decyzja Janka: **wchodzimy partiami**, pierwsza `--apply --limit=150`. Stan przed importem do ewentualnego cofnięcia: `MAX(post ID) = 411423`, che168 publish = 853.

⚠️ **Uwaga na wywołanie skryptu.** `wp eval-file skrypt.php --pages=10` **nie działa** — WP-CLI przechwytuje flagi jako własne parametry globalne („unknown --pages parameter"), separator `--` też nie pomaga. Obejście:

```
wp eval '$args=["--pages=10","--limit=150","--apply"]; include "/home/host476470/projekty/primaauto/scripts/che168-domknij-luke.php";'
```

## Konsekwencje / otwarte

- **Indexing API**: każda zaimportowana oferta odpala auto-zgłoszenie (hook na `listings`). Pula GCP „Piaskownica" to 200/dobę wspólnie dla wszystkich projektów, a 100 zeszło już 13.08 o 07:21Z z crona reindeksacji. Partia 150 przebiła limit — nadmiar wraca HTTP 429, wpada w `isRetryable()` i ląduje w kolejce retry pluginu. **Decyzja Janka 13.08: nie przejmujemy się limitem**, nie wyłączamy `asiaauto_indexing_enabled` na czas backfillu.
- **89 orphanów** z dry-runu = modele bez wpisu w `che168-model-map.php`. Guard mapowania je zatrzyma i zakolejkuje w `asiaauto_che168_unmapped`; domapowanie to osobna robota, każdy wpis odblokowuje kilkanaście sztuk.
- **502 od dostawcy** — pojedynczy `getOffer` poległ 3× na `502 Bad Gateway` (nie 429). Kolejny sygnał, że problem jest po ich stronie serwera, nie tylko w crawlerze.
- **Tło biznesowe**: dongchedi w trybie `verify` gaśnie ~54 publish/dobę (1489 na 3.08 → 953 na 13.08), che168 dokładał 0–20. Bez backfillu baza ofert kurczy się ~50/dobę. 15.08 kończy się opłacony okres auto-api — to samo posiedzenie co decyzja R1 o resztkach dongchedi (`docs/przypomnienia/2026-08-10-t222-decyzja-o-resztkach-dongchedi.md`).

Powiązane: `docs/decyzje/2026-06-15-autoapi-dongchedi-eskalacja.md`, spec T-222 `docs/superpowers/specs/2026-07-30-t222-migracja-dongchedi-che168-design.md`.
