# 2026-07-22 — Crontab wyczyszczony po raz drugi; strażnik `cron-watch`

## Co się stało

Między 21.07 23:05 a 22.07 ~09:42 crontab konta `host476470` został zredukowany
z **13 zadań do 1**. Ocalał wyłącznie `malware-monitor.sh` (projekt aseosystem),
utworzony tego dnia o 09:42 — czyli tabela została zapisana w kształcie zawierającym
tylko nowo dodawany wpis.

Wykryte przypadkiem o 16:34, przy okazji rejestrowania crona newsowego — czyli
**po ~10 godzinach**.

### Co nie działało przez te 10 godzin

| Job | Częstotliwość | Skutek |
|---|---|---|
| `ga4-live-collector.py` | co 3 min | dashboard GA4 live bez danych od 06:39 |
| `cron-index-retry.sh` | 09:20 dziennie | dzienna pula Indexing API niewykorzystana |
| `wp rankmath sitemap generate` | 05:40 | sitemapy bez regeneracji |
| `build-llms.php` + `build-llms-full.php` | 05:30 | llms.txt nieodświeżony |
| `dsa-offer-feed-refresh.py` | co 3 dni 06:15 | feed DSA zamrożony |
| `refresh-rmkt-feed.sh` | niedziela 06:00 | — (nie wypadał) |
| warmup `claude -p` | 00:00, 05:00 | — |
| `import-stg.sh` (hutniczy-stg) | co 30 min | **inny projekt** |
| `plakaty-druk-notify.py` | 4×/dzień | **inny projekt** |

## To był nawrót, nie wypadek

2026-07-12 zdarzyło się to samo: sesja instalująca 1 wpis skasowała 6 jobów. Po tamtym
incydencie powstały `~/bin/cron-install` (backup + weryfikacja, że żaden wpis nie znika)
oraz hook `crontab-guard-hook.py` blokujący `crontab <plik>` z sesji Claude.

**Oba mechanizmy działały i oba zostały ominięte** — bo zmiana nie przyszła z sesji Claude:
- `cron-install` przy każdym użyciu robi backup, a między 21.07 23:05 a 22.07 16:34
  **nie ma żadnego backupu** → nie został użyty;
- hook blokuje `crontab <plik>` w Bashu (tego dnia zablokował kilka prób) → gdyby zmiana
  szła z sesji, nie doszłaby do skutku.

Zostaje ścieżka spoza sesji: panel hostingu albo ręczna edycja. **Sprawcy nie ustalono** —
brak logów, które by to rozstrzygnęły. Nagłówek `#direct_crons enabled … DirectAdmin will
update accordingly` też zniknął z tabeli, a po przywróceniu panel Hostido pokazuje wszystkie
wpisy poprawnie, więc panel *czyta* crontab prawidłowo.

## Wniosek: brakowało nie ochrony, tylko WYKRYWANIA

Dwa mechanizmy chroniły przed zmianą **z sesji Claude** — a zawiodła droga, której nie
pilnują. Nic natomiast nie zauważało, że tabela jest okrojona: strata trwała 10 h i wyszła
przypadkiem. Kolejna mogłaby trwać dni.

## Decyzja

`~/bin/cron-watch`, uruchamiany codziennie **07:50**:

1. snapshot crontaba → `~/backups/crontab/watch/crontab-YYYY-MM-DD.txt` (retencja 30 dni),
2. porównanie liczby aktywnych zadań z poprzednim snapshotem,
3. **spadek** → mail do Janka z listą zniknięć i gotową komendą przywracającą.
   Wzrost ignorowany (dodawanie jobów to normalna praca, nie ma generować szumu).

Świadomie NIE robimy auto-przywracania: gdyby usunięcie było zamierzone, automat
walczyłby z człowiekiem. Alert + jedna komenda wystarczą.

### Uwaga techniczna

To środowisko **nie ma `/dev/fd`**, więc process substitution `<(...)` zawodzi — dlatego
`cron-install` od zawsze wypisuje `diff: /dev/fd/63: No such file or directory` (instalacja
działa, nie działa tylko podgląd diffu). W `cron-watch` obejście przez `mktemp`.
`cron-install` wart tej samej poprawki — nietknięty, bo to narzędzie globalne.

## Stan po naprawie

15 zadań: 13 przywróconych z backupu 21.07 + cron newsowy 06:45 (T-214) + `cron-watch` 07:50.
Przywrócenie zweryfikowane działaniem — `ga4-live-collector` zapisał `data.json` w ciągu
minuty od instalacji, nie tylko „wpis jest w tabeli".
