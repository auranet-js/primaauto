# ADR: Dedup listingów + optymalizacja bazy

**Data:** 2026-04-22
**Status:** Przyjęte (wdrożone)
**Uczestnicy:** Jan Schenk, Claude Code

## Kontekst

Klient zgłosił, że kilka ostatnich importów ogłoszeń pojawia się podwójnie na stronie. Diagnoza wykazała:

**Race condition w cron sync.** `class-asiaauto-sync.php` używa transient locka z TTL 10 min. Jeśli sync trwa dłużej (dużo `added` → dużo tłumaczeń Gemini + downloadów obrazków), lock wygasa w trakcie, kolejny cron startuje równolegle z tym samym `change_id`. Oba procesy wywołują `findByInnerId()` zanim któryś zdąży zrobić `wp_insert_post`, oba wstawiają rekord → duplikat.

Przykład 2026-04-22:
- 06:19:53 sync #2 start (change_id=2991941)
- 06:29:53 transient lock wygasa (sync trwa dalej)
- 06:33:19 sync #3 start (ten sam change_id)
- 06:39:12 i 06:39:17 oba kończą (identyczny zakres 2991941→2992717)
- Efekt: 9 par duplikatów

Ten sam pattern wystąpił 2026-04-17 (15 par) i 2026-04-16 (3 pary). **Nie ma związku z cutoverem 2026-04-21** — pattern pre-dates migrację.

Druga warstwa problemu: **baza była spuchnięta do 640 MB**. Rozkład bloatu:
- 96 051 attachments (vs. ~22k realnie potrzebnych)
- 3 512 listingów w trashu (cron `asiaauto_daily_cleanup` przycina, ale nie do zera)
- 7 201 revisions (brak limitu `WP_POST_REVISIONS` → każdy sync update listingu produkował revision)
- 157 368 postmeta dla trash postów (balast ciągany w każdym backupie)

Root cause bloatu: **importer nie ustawia `post_parent` na attachments** przy `wp_insert_attachment`. Gdy listing trafia do trasha/delete, jego zdjęcia zostają sierotami — w DB i na dysku. Cron cleanup obsługuje tylko listings, nie ich attachments.

## Decyzja

**Wykonane 2026-04-22:**

1. **Cleanup duplikatów** (36 par, od 2026-03-31 do 2026-04-22). Strategia: zachować `MIN(post_id)`, skasować `MAX(post_id)` przez `wp post delete --force`. Weryfikacja: żaden duplikat nie miał zamówienia ani rezerwacji. 301 redirecty dla skasowanych slugów — odpuszczone (listingi nieźaindeksowane).

2. **Optymalizacja bazy:**
   - A. 7 201 revisions skasowanych + `define('WP_POST_REVISIONS', 3)` w `wp-config.php`
   - B. 3 512 trashed listings skasowanych na stałe
   - C. 72 731 orphan attachments skasowanych (cross-check po `_thumbnail_id` + `gallery` meta wszystkich żyjących postów)
   - `wp db optimize` na wszystkich tabelach

**Wynik:**

| Metryka | Przed | Po |
|---|---|---|
| DB total | ~640 MB | 141 MB (−78%) |
| `wp7j_posts` | 176 MB / 110 774 wierszy | 21 MB / 25 475 |
| `wp7j_postmeta` | 455 MB / 708 216 | 111 MB / 171 531 |
| Attachments | 96 051 | 23 533 (21 400 publish + 2133 draft) |
| `mysqldump` trzech tabel | 441 MB | 97 MB |

## Pending (nie wdrożone dzisiaj)

**Plan A — fix race condition.** Zastąpić transient lock w `class-asiaauto-sync.php:52-58` przez MySQL advisory lock `GET_LOCK('asiaauto_sync_dongchedi', 0)`. Plusy: brak TTL (trzyma się dopóki żyje PHP), auto-release przy crashu procesu, zero zmian schematu. User zdecydował: **trigger dopiero gdy duplikaty się powtórzą** (nie fixować proaktywnie bo może być rzadkie).

**Plan D — prewencja bloatu.**
- Importer (`class-asiaauto-media.php`): ustawiać `post_parent = $listing_id` przy `wp_insert_attachment`. Wtedy `wp_delete_post($listing, true)` kaskadowo zabiera attachments + pliki.
- Enhanced cron cleanup: `asiaauto_daily_cleanup` powinno kasować trashed listings starsze niż 30 dni na stałe (teraz tylko ticknie do trasha, nigdy nie kasuje).

## Konsekwencje

- **Backupy szybsze** — dump 97 MB zamiast 441 MB, codzienny backup Hostido skończy się w sekundy zamiast minuty.
- **Admin WP szybszy** — mniejsze JOINy na postmeta.
- **Brak rollbacku dupli** — 36 skasowanych duplikatów nie wróci. Backup: `~/backups/primaauto/2026-04-22-pre-dedup.sql` (442 MB).
- **Brak rollbacku optymalizacji** — 72k attachmentów skasowanych + pliki z dysku. Backup DB: `~/backups/primaauto/2026-04-22-pre-optimize.sql` (441 MB). Plików z uploads nie backupowałem.
- **Nowe revisions będą limit 3** — nie odrośnie 7k revisions w miesiąc.
- **Jeśli nowe duplikaty się pojawią:** root cause znany, Plan A gotowy do wdrożenia.

## Ścieżki

- Backup DB pre-dedup: `~/backups/primaauto/2026-04-22-pre-dedup.sql`
- Backup DB pre-optimize: `~/backups/primaauto/2026-04-22-pre-optimize.sql`
- Backup wp-config: `~/domains/primaauto.com.pl/public_html/wp-config.php.bak-2026-04-22`
