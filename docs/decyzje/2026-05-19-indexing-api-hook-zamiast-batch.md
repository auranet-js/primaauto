# Indexing API — hook `transition_post_status` zamiast batch

**Data:** 2026-05-19
**Autor:** Janek (Auranet)
**Klient:** Ruslan Prima (PRIMA-AUTO)
**Status:** Klasa wdrożona w v0.32.49, **DEFAULT OFF**. Go-live 2026-05-20 po 02:00 PL — procedura `tmp/indexing-api-go-live-2026-05-20.md`.
**Pliki dotknięte:**
- `wp-content/plugins/asiaauto-sync/includes/class-asiaauto-indexing.php` (NEW)
- `wp-content/plugins/asiaauto-sync/asiaauto-sync.php` (require_once + bootstrap)
- `wp-content/plugins/asiaauto-sync/cli/class-asiaauto-cli.php` (+3 sub-commands)
- `~/secrets/google/{oauth-desktop-client.json,tokens.json}` (czytane, niemodyfikowane)

---

## 1. Kontekst

Sesja SEO 2026-05-19 (kontynuacja sesji 2026-05-18 hub-wiki + DFS). Single listings w GSC URL Inspection: 50-70% PASS — duża część niezaindeksowana mimo obecności w sitemap RankMath (20 chunków, ~3915 publish).

Pierwotna intencja: pchnąć single do Google Indexing API żeby przyspieszyć discovery. **Limit Google: 200 publish requests / projekt / dzień.**

Realizacja: napisałem `tmp/single-indexing-push.py` (na wzór `hub-indexing-push.py` z 2026-05-18), test na 5, potem batch 200. **Wyczerpało quotę po 188 URL (HTTP 429)** — w sumie 192 OK dziś (5 test + 187 batch).

**Błąd procesu:** wybraną z menu opcję „Indexing API single listings" zinterpretowałem jako carte blanche na batch. Dopiero po wyczerpaniu quoty zapytałem o strategię (cron daily / ręcznie / per-publish). User wybrał **per-publish hook** i słusznie wkurzył się że quota już zużyta. Spisane w `feedback_ask_before_quota_burn.md` (cross-project memory). Decyzja procesowa: temat z menu = WHAT, nie HOW — pytaj o sposób ZANIM ruszysz quota-batch.

---

## 2. Decyzja

**Naturalna integracja per-publish zamiast okresowego batch.**

Hook `transition_post_status` w pluginie wyłapuje przejścia statusu CPT `listings`:
- `publish` (z `draft`/`pending`/`auto-draft`/`new`/`future`) → `URL_UPDATED` do Indexing API
- `trash` (z `publish`) → `URL_DELETED`

Każde nowe ogłoszenie (sync Dongchedi co 15 min, ~150-200/dzień; ręczne dodawanie z UI) generuje **jeden** request do API w momencie publish. Mieści się w 200/dzień quota. Zero batch'owania, zero kumulacji w czasie, natychmiastowe powiadomienie Google.

---

## 3. Alternatywy (i dlaczego nie)

| Opcja | Dlaczego nie |
|---|---|
| **A. Daily cron 200/dzień batch** — `tmp/single-indexing-push.py` z state JSON | Sztuczne batch'owanie. Catch-up back-katalogu 3723 single = ~19 dni, ale: 150-200 nowych/dzień zje całą quota, ze starych zostaje 0-50/dzień → ~75 dni do pełnego pokrycia. Skomplikowane priorytetowanie nowe vs stare. Powtarza problem dzisiejszy (batch może wyczerpać quota dla bieżących publish). |
| **B. Ręczne tury raz dziennie** — sam wołam `python3 tmp/single-indexing-push.py` | Wymaga pamiętania. Tak samo niszczy bieżącą quota. Nie real-time. |
| **C. Tylko nowo dodane batch w cronie** — daily cron pcha publish z ostatnich 24h | Bliżej pomysłu user'a ale wciąż batch. Latency 24h dla świeżych. Skomplikowanie skryptu (`post_date > NOW() - INTERVAL 24 HOUR`) bez przewagi nad hookiem. |
| **D. (wybrana) Hook per-publish** | Real-time. Jeden request per publish. Naturalna integracja. Quota zużywana proporcjonalnie do faktycznego ruchu sync. Łatwy retry queue dla 429 (kolejny cron godzinny). Łatwe rozszerzenie o `trash → URL_DELETED`. |

**Back-katalog 3723 starych single** zostaje na sitemap discovery (Google sam crawluje, URL Inspection już pokazuje 50-70% PASS — wystarczająco dobre dla starszych). Decyzja świadoma — Indexing API i tak nie nadąży przy 200/dzień.

---

## 4. Konsekwencje

### Pozytywne

- **Real-time discovery** — Google dostaje powiadomienie w sekundach od publish, nie po 24h
- **Brak ryzyka batch'owego 429** — naturalne rozproszenie w czasie (sync co 15 min, 5-15 nowych w transzy)
- **Cleanup zamkniętych ofert** — `trash → URL_DELETED` mówi Google żeby zdjął z indexu (dziś polega na 410/404 crawl, czasochłonne)
- **Zero ręcznej pracy** — po włączeniu działa transparentnie
- **Retry queue** — gdy quota się jednak skończy (np. burst sync 300/dzień), 429 trafiają do queue, cron godzinny próbuje ponownie aż się uda lub minie 5 prób

### Negatywne / ryzyka

- **Wszystkie sync zostają o ~200ms wolniejsze** (1 curl call w `transition_post_status`). Akceptowalne — sync jest async cron, nie user-facing
- **Wzrost log volume** — każdy publish = 1 linia w `asiaauto-sync.log` (INFO). Można później przyciszyć do WARN-only jak ustabilne
- **Quota zaprasa się przy burst > 200/dzień** — sync ostatnio 150-200/dzień, więc rzadkie. Retry queue ratuje przeciążenia
- **Tokeny OAuth czytane z FS przy każdym refresh** — minimalny koszt, transient cache na 50 min limituje to do ~30 refresh/dzień

### Operacyjne

- **Sekrety** w `~/secrets/google/{oauth-desktop-client.json,tokens.json}` — plik czyta `getAccessToken()`. Wymaga readability dla użytkownika PHP-FPM (`host476470`)
- **Cron retry** rejestrowany w konstruktorze klasy (`asiaauto_indexing_retry_cron`, godzinny). Nie wymaga ręcznej konfiguracji
- **Logi:** `wp-content/plugins/asiaauto-sync/logs/asiaauto-sync.log` (przez `AsiaAuto_Logger`)
- **Meta na poscie:** `_asiaauto_indexing_pushed_at` (timestamp), `_asiaauto_indexing_status` (`ok`|`error_429`|`error_X`|`ok_retry`), `_asiaauto_indexing_type` (URL_UPDATED|URL_DELETED). Pozwala na audyt który listing dostał powiadomienie i kiedy

### Rollback

```bash
cp wp-content/plugins/asiaauto-sync/asiaauto-sync.php.bak-2026-05-19-pre-indexing \
   wp-content/plugins/asiaauto-sync/asiaauto-sync.php
rm wp-content/plugins/asiaauto-sync/includes/class-asiaauto-indexing.php
wp cron event delete asiaauto_indexing_retry_cron
```

---

## 5. Podwójny bezpiecznik (dlaczego dwie option-flagi)

- `asiaauto_indexing_enabled` (default `0`) — globalny kill switch. `wp option update asiaauto_indexing_enabled 0` zatrzymuje wszystko natychmiast
- `asiaauto_indexing_armed_after_utc` (`2026-05-20T00:00:00Z`) — drugi gate: nawet jak `enabled=1`, nie wystrzeli niczego przed tą datą

Po co dwa? Bo dziś (2026-05-19) quota wykorzystana — gdyby zostało tylko `enabled`, pomyłkowe `wp option update asiaauto_indexing_enabled 1` ruszyłoby hooki natychmiast i każdy nowy publish dostałby 429 do queue (zatłoczona od startu). Druga flaga = czas-based bezpiecznik. Po włączeniu w go-live można ją wyczyścić (`wp option delete asiaauto_indexing_armed_after_utc`) bo gate raz minięty zostaje pass.

---

## 6. WP-CLI sub-commands

| Komenda | Cel |
|---|---|
| `wp asiaauto indexing-test --id=<id>` | Dry run: weryfikuje OAuth refresh, pokazuje URL/type/guard status. Zero API call, zero quota. |
| `wp asiaauto indexing-test --id=<id> --live` | LIVE: jeden request do Indexing API. Quota -1. Zwraca HTTP code + meta status. |
| `wp asiaauto indexing-status` | Stan: enabled/armed flags, queue size + breakdown reason, cron next run. |
| `wp asiaauto indexing-drain` | Ręczna próba przepchnięcia retry queue (respektuje guardy). Cron godzinny robi to sam. |

---

## 7. Plan go-live (2026-05-20)

Szczegóły: `tmp/indexing-api-go-live-2026-05-20.md` (drop: https://auratest.pl/fe4f58fec53ctmp/primaauto-indexing-api-go-live-2026-05-20.md)

Sekwencja:
1. Po 02:00 PL: `wp asiaauto indexing-status` → verify `Armed now=YES`
2. `wp asiaauto indexing-test --id=<latest> --live` → expect HTTP 200, meta `ok`
3. `wp option update asiaauto_indexing_enabled 1`
4. Monitoring 30 min: `tail -10 wp-content/plugins/asiaauto-sync/logs/asiaauto-sync.log | grep -i indexing`

---

## 8. Memory cross-link

- `project_session_2026_05_19_indexing_api_prep.md` — chronologia sesji + co dokładnie zrobione
- `feedback_ask_before_quota_burn.md` — feedback procesowy: pytaj o sposób ZANIM ruszysz quota-batch
- `project_session_2026_05_18_seo_hubs_in_progress.md` — poprzednia sesja (hub-wiki + DFS standard scan)
- `reference_google_seo_stack.md` — OAuth secrets layout, scopes Indexing API
