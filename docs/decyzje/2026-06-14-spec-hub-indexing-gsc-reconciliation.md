# 2026-06-14 — Spec-hub indexing: dokończenie + rekoncyliacja ewidencji przez GSC lastCrawl (T-157)

## Kontekst

Faza 2 spec-hub (data-driven tabela techniczna na hubach modeli) była DONE z 2026-06-13
(v0.32.74, motyw `primaauto2026`, `AsiaAuto_Spec::renderTable()` w `taxonomy-serie.php`).
Zostało dokończyć indexing ~189 hubów z `tmp/spec-hub-indexing-remaining-2026-06-13.txt`
oraz zaplanować pomiar (T-157).

Janek zgłosił wątpliwość: „sprawdź co indeksowałeś, bo mam wrażenie że znowu nie zapisałeś".

## Diagnoza — ewidencja była zepsuta, ale indexing działał

- **Log `~/.claude/indexing-submit.log` zapisuje TYLKO liczby (`ok=N`) i czas — NIE które URL-e.**
  Czyli z samego logu nie da się odpowiedzieć „czy ten konkretny hub poszedł".
- **Pliki per-URL nie domykały się:** `day1` (80) + `remaining` (189) = 269 ≠ `ALL` (355).
  **86 hubów wisiało w „limbo"** — ani w day1, ani w remaining. Przyczyna: plik `day1`
  zapisał tylko pierwsze 80 alfabetycznie z faktycznie zgłoszonych ~166-179 z 06-13.
- **Sam indexing jednak szedł:** log kompletny (603 zgłoszenia w całej historii primaauto,
  ~179 dnia 06-13), a GSC potwierdza 312/355 hubów w indeksie.

## Decyzja — GSC `lastCrawlTime` jako jedyne wiarygodne źródło prawdy

Zamiast ufać niespójnym plikom, zrobiono **pełny audyt GSC URL Inspection na 355 spec-hubach**
(`tmp/spec-hub-gsc-lastcrawl-2026-06-14.txt`). Reframe celu kampanii:

> Huby są już zindeksowane (stara wiadomość). Cel = **re-crawl**, żeby Google zobaczył
> NOWĄ tabelę spec. Sukces = `lastCrawl ≥ 2026-06-13` (data wejścia spec), NIE „czy w indeksie".

Klasyfikacja 355:

| Stan | Liczba |
|---|---|
| Zindeksowane (PASS) | 312 |
| Spec WIDZIANY (lastCrawl ≥06-13) | 46 |
| Stale (zindeksowane, crawl <06-13 — spec niewidziany) | 286 |
| Redirect / soft-404 / noindex / 404 / nieznane | 43 |

Z 286 stale rozbito na realnie actionable:
- **124** = stale + live + **nigdy nie zgłoszone** (z kolejki remaining) → **PRIORYTET, zgłoszone dziś**
- **142** = stale, ale już zgłoszone 06-13 → czekać (Indexing API best-effort, re-crawl trwa dniami)
- **20** = stale, ale to redirecty/soft-404 (poprawnie wykluczone, NIE zgłaszać)

## Wykonane (2026-06-14)

- Zgłoszono **124 huby** przez `~/bin/index-submit` (7 chunków po 20), **0 błędów**.
- Budżet: 29 → **153/100 ad-hoc** = 71 ad-hoc + **53 z rezerwy PrimaAuto** (Janek autoryzował).
- **Trwały rekord zgłoszeń:** `tmp/spec-hub-SUBMITTED-2026-06-14.txt` (124 URL) — naprawa luki ewidencji.
- Smoke OK (render spec na Xiaomi SU7 / NIO ET7 / Li Auto i6).
- **Kampania kompletna** — 0 żywych hubów zostało do zgłoszenia (46 re-crawl + 142 + 124 = wszystkie live).

## Konsekwencje / na przyszłość

1. **Log indexing-submit nie wystarcza do audytu „co" — zawsze zachowuj listę URL per batch**
   (`tmp/spec-hub-SUBMITTED-YYYY-MM-DD.txt`). Rozważyć patch wrappera, by logował URL-e (osobny task).
2. **GSC `lastCrawlTime` = arbiter** dla „czy Google zobaczył zmianę". Skrypt `tmp/gsc-inspect.py`.
3. **GSC URL Inspection jest wolne** (~9 s/URL sekwencyjnie przez throttling) — dla >100 URL
   zrównoleglić (8 workerów × chunk → 355 w ~kilka min zamiast ~50 min). Limit 600/min/property
   daje duży zapas.
4. **Re-crawl ≠ re-index, Indexing API = best-effort** — zgłoszenie nie gwarantuje crawla;
   pomiar skuteczności dopiero ~14 dni po zgłoszeniu (T-017, ~28-30.06).

## Drobne otwarte (niski prio)
- 3 huby soft-404 (`dongfeng/e007`, `dongfeng/nami-06`, `foton/dajiangjun-f9`) — render
  „Nie znaleziono modelu" mimo HTTP 200 (term `parent=0`, resolver `taxonomy-serie.php`).
- Pomiar GSC long-tail technicznego ~14 dni po dziś (T-017).

## Artefakty
- `tmp/spec-hub-gsc-lastcrawl-2026-06-14.txt` — audyt 355 (verdict + coverage + lastCrawl)
- `tmp/spec-hub-SUBMITTED-2026-06-14.txt` — 124 zgłoszone dziś
- `tmp/spec-hub-priority-submit-2026-06-14.txt` — lista priorytetu (= submitted)
- `tmp/spec-hub-leftover-2026-06-15.txt` — 20 wykluczonych (redirect/soft-404)
- `tmp/spec-hub-verify-2026-06-14.tsv` / `spec-hub-limbo-verify-2026-06-14.tsv` — weryfikacja render LIVE
