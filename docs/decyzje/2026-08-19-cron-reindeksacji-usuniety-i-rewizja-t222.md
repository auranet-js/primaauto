# ADR 2026-08-19 — cron reindeksacji usunięty; T-222 bezprzedmiotowy po opłaceniu auto-api

## 1. Cron reindeksacji `ok_retry` — USUNIĘTY z crontaba

### Stan zastany

Cron `20 9 * * *` (`scripts/cron-index-retry.sh`, ustawiony 17.07 na czas zamrożonego feedu) sam się wyłączył i od dwóch dni mailował:

```
2026-08-17 09:20  Feed OŻYŁ (92 nowych ofert/24h > 5). Cron WSTRZYMANY.
2026-08-18 09:20  Cron WYGASŁ (minął 2026-08-17) i nie działa.
2026-08-19 09:20  Cron WYGASŁ (minął 2026-08-17) i nie działa.
```

Oba guardy zadziałały zgodnie z projektem — feed-guard przy powrocie zaciągu, potem kill-switch `EXPIRE`.

### Pomiar, który rozstrzygnął decyzję

Zaległość jest **większa** niż przy ustawianiu crona: `ok_retry` = **809** (+ 10 `error_429`), wobec 1 097 na starcie i ~568 szacowanych 16.08. Rośnie od nowych ofert — 341 z backfillu 13.08 (442 oferty jednego dnia wyczerpały pulę).

Ale auto-indexing pluginu **nadąża sam**, odkąd wolumen wrócił do normy:

| doba | nowych publish | `ok` | `ok_retry` |
|---|---|---|---|
| 13.08 | 442 | 101 | 341 |
| 16.08 | 66 | 59 | 7 |
| 17.08 | 146 | 122 | 24 |
| 18.08 | 105 | **105** | **0** |
| 19.08 | 67 | **67** | **0** |

**Rozstrzygające:** próbka 30 losowych ofert w stanie `ok_retry`, przepuszczona przez GSC URL Inspection (`scripts/gsc-inspect.py`) — **27/30 zaindeksowanych** (90%), świeże `lastCrawl` (08-14, 08-17, 08-18, 08-19). Niezaindeksowane 3: dwa „Google nieznany", jedno „wykryta, niezindeksowana".

Czyli `ok_retry` **nie oznacza „poza indeksem"** — sitemapa dowozi te URL-e sama. Realna luka to ~10% z 809 ≈ 80 ofert, a nie 809.

### Decyzja

Wpis usunięty z crontaba (`~/bin/cron-install --allow-remove`, backup `~/backups/crontab/crontab-2026-08-19-172659.bak`, 27 → 26 aktywnych wpisów). Skrypt `scripts/cron-index-retry.sh` zostaje w repo — gdyby feed znów padł na tygodnie, wystarczy wrócić wpisem z nowym `EXPIRE`.

Puli Indexing (200/dobę, wspólna dla wszystkich projektów) nie zajmujemy nadrabianiem zaległości, bo idzie na świeże oferty — przy 100+ nowych ofertach dziennie plugin sam konsumuje niemal całą rezerwę.

**Do rewizji, jeśli:** udział `ok_retry` wśród świeżych ofert znów przekroczy ~20% przez kilka dni z rzędu (= pula za mała na bieżący zaciąg, wtedy problem jest budżetowy, nie retry'owy).

## 2. T-222 — decyzja o resztkach dongchedi: BEZPRZEDMIOTOWA

Przypomnienie `docs/przypomnienia/2026-08-10-t222-decyzja-o-resztkach-dongchedi.md` zakładało, że 15.08 kończy się opłacony okres auto-api i tracimy jedyny mechanizm weryfikacji żywotności zapasu (ryzyko R1). **Nie nastąpiło:** faktura EST-281435 (146 EUR, okres 15.08–17.09) opłacona, che168 przywrócone 19.08 (ADR `2026-08-19-autoapi-faktura-146eur-i-restart-che168.md`), dongchedi wznowione 16.08 w trybie `full`.

Stan faktyczny na 19.08:

- `asiaauto_sync_mode_dongchedi` = `full`, `asiaauto_sync_enabled_dongchedi` = 1, che168 enabled = 1
- zapas publish: **dongchedi 1 096**, che168 1 353 (razem 2 553 z 1 kontem prywatnym)
- **mechanizm `removed` żyje** — przejścia do `draft`: dongchedi 3 / 36 / 48 / 2 (16–19.08), che168 12 (19.08). To dokładnie ten kanał, o którego utratę chodziło w R1.
- wiek zapasu dongchedi: <30 d — 467, 30–60 d — 233, 60–90 d — 86, **>90 d — 310**

**Ryzyko R6 zostaje otwarte, ale zmalało:** 446 z 1 096 ofert dongchedi publish (41%) nie ma werdyktu `_asiaauto_source_check`, więc cron gaszenia (04:25) nigdy ich nie ruszy. Póki kanał `removed` działa, weryfikacja idzie online i werdykty przemiału są potrzebne tylko dla ofert, które umarły w czasie zastoju. Reguła obejmująca cały zapas — do rozważenia dopiero przy realnym odcięciu od API.

### Znalezione przy okazji: licznik w logu gaszenia kłamie

`~/.claude/gasz-martwe-dongchedi.log` codziennie pokazuje `WYGASZONO 0 z 0 === pozostalo martwych publish: 52`. Wygląda na zator, nie jest nim: zapytanie porcji stosuje trzy warstwy ochrony, a licznik `$zostalo` (`scripts/gasz-martwe-oferty.php:56`) — nie. Z tych 52 ofert: **49 to wpisy ręczne** Ruslana, 11 ma aktywne zamówienie, 8 rezerwację (warstwy się nakładają). Wszystkie chronione poprawnie.

Poprawka licznika (skopiowanie trzech `NOT EXISTS` do zapytania zliczającego) — drobiazg, nie ruszony bez zlecenia.
