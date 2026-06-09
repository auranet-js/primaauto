# 2026-06-09 — Rotation: kasowanie zdjęć przy permanent delete + backfill sierot

**Wersja:** asiaauto-sync 0.32.73
**Strefa:** krucha (#4 rotacja/statusy zamówień — dotknięty `deleteOldTrash`, `isReserved` nietknięte)

## Problem

Audyt inode konta: `wp-content/uploads/asiaauto/` urósł do ~575 tys. plików / 13,2 GB — największy pojedynczy konsument inode na koncie LVE. Rozkład per miesiąc rósł lawinowo (03 ~4k → 04 ~253k → 05 ~282k plików).

## Diagnoza (korzeń)

Cron `asiaauto_daily_cleanup` chodził poprawnie (84 uruchomienia od 19.03, codziennie 03:00) i **usuwał listingi** (`deleteOldTrash()` → `wp_delete_post($pid, true)`), ale w logu **„0 orphaned images removed" przy KAŻDYM uruchomieniu**.

Przyczyna w WP core, `wp-includes/post.php:3861`:
```php
// Point all attachments to this post up one level.
$wpdb->update( $wpdb->posts, $parent_data, $parent_where + array( 'post_type' => 'attachment' ) );
```
To wykonuje się **bezwarunkowo** (nie tylko dla CPT hierarchicznych). `$parent_data = post_parent listingu = 0`. Więc gdy `wp_delete_post()` kasuje listing, WP przepina jego attachmenty na `post_parent=0`. A `cleanOrphanedImages()` szuka wyłącznie `post_parent>0 AND parent_gone` → przepięte na 0 nigdy nie wpadają → pliki + miniatury + wiersze attachment zostają na dysku na zawsze.

Skutek: **54 508 osieroconych attachmentów** (`post_parent=0` + `_asiaauto_source_url`, referencjonowane przez 0 galerii — w próbce 3000: 94% to `inner_id` usuniętych aut, reszta nieaktualne duplikaty po re-syncu zdjęć; 0 zdjęć ręcznych bo te nie mają `_asiaauto_source_url`).

## Decyzja

**Forward fix:** w `deleteOldTrash()`, przed `wp_delete_post()`, wywołać `$media->removeImages($pid, true)` — istniejąca (dotąd nieużywana) metoda `AsiaAuto_Media`, która czyta meta `gallery` i kasuje attachmenty + pliki PÓKI listing istnieje (zanim WP zdąży je przepiąć na 0).

**Bez manual-skip w rotacji** — świadomie. Listingi ręczne (`_asiaauto_manual_import`/`_asiaauto_manual_entry`) nie mogą wejść w pipeline kasowania przez rotację: jedyne wejście to meta `_asiaauto_removed_at`, ustawiana wyłącznie przez `markRemoved()`, wołane tylko z:
1. sync (`class-asiaauto-sync.php` case 'removed') — z guardem `isManuallyManaged()`,
2. order-cancel (`class-asiaauto-order.php`) — za bramką `_asiaauto_api_removed='true'`, której ręczne nigdy nie dostają.

Ręczne listingi w koszu trafiają tam tylko ręcznie (człowiek w wp-admin) — wtedy trwałe usunięcie wraz ze zdjęciami jest zamierzone.

**Backfill** istniejących 54 508 sierot — `wp_delete_attachment(force)` w chunkach, z re-weryfikacją 0 referencji galerii i pominięciem `_asiaauto_manual_upload` przy każdym uruchomieniu.

## Konsekwencje

- Odzysk **~5,7 GB / ~254 tys. inode** (03/04/05 do 0 sierot; 06 nie miał sierot — auta jeszcze nieusunięte).
- Od teraz każde auto usuwane przez rotację traci zdjęcia razem z postem — koniec narastania.
- Backup DB przed kasowaniem: `~/backups/primaauto/2026-06-09/posts-postmeta-pre-orphan-cleanup.sql`. Uwaga: backup pokrywa wiersze DB, NIE pliki — kasowanie plików nieodwracalne, bezpieczeństwo oparte na weryfikacji 0-referencji przed kasowaniem.

## Dług / follow-up (nie w tym wdrożeniu)

- `class-asiaauto-rotation.php`: `$trash_ttl_days = 7` vs komentarz „> 30 days" (linia 86) — fix komentarza.
- 5 ręcznych listingów w koszu + 7 publish ręcznych z legacy `_asiaauto_removed_at` — rotacja ich nie ruszy (potwierdzone), ale do rozważenia defensywny manual-skip.
- Rozmiary miniatur: wszystkie 4 dominujące używane w theme; domyślne WP `medium_large`/`1536`/`2048` rzadko generowane (źródła 640px) — <300 MB, niski priorytet.
