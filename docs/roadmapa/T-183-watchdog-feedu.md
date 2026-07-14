# T-183 — Watchdog awarii feedu ogłoszeń

> Status: **gotowy do odpalenia** · Rozmiar: S
> Godziny realnie: **5–6 h** (Janek ~1 h, AI ~4–5 h) · Estymata potwierdzona po zwiadzie
> **Pilność podniesiona:** Dongchedi od ~01.07 dowozi ~2 auta/dobę zamiast ~680 — i dowiedzieliśmy się o tym przypadkiem, tydzień później. Dokładnie temu ma zapobiegać ten task.

## Po co

Awaria feedu jest **cicha**: licznik zmian rośnie, status `ok`, zero błędów — tylko nowych aut nie ma. Trzy awarie w czerwcu i zamrożenie od lipca wyszły po fakcie. To jest ubezpieczenie świeżości oferty.

## Stan faktyczny — dane JUŻ są zbierane, nikt ich nie czyta

| Element | Stan |
|---|---|
| `asiaauto_sync_history` (50 ostatnich przebiegów: ts, source, added, changed, removed, skipped, status) | ✅ zapisywane (`class-asiaauto-sync.php`, `logRun()` ~l. 272) |
| `AsiaAuto_Sync::getHistory()` / `getLastSync()` | ⚠️ **martwy kod — zero wywołań w całym pluginie** |
| Panel admina | Pokazuje tylko „kiedy następny sync", nie „czy poprzedni się udał" |
| Alerty | ❌ zero (`grep alert|watchdog|notify` → nic) |
| Diagnostyka (10 checków: brakujące zdjęcia, chińskie znaki, duplikaty…) | ✅ istnieje (`class-asiaauto-diag.php` + `includes/diag-checks/`), ale **odpalana tylko ręcznie z admina** |
| Cron syncu | WP-cron co 15 min (`asiaauto_15min`) |
| Log | `logs/asiaauto-sync.log` — **17,9 MB, bez rotacji** |

**Czyli 80% materiału jest gotowe.** Brakuje: czytelnika, progu i kanału.

## ⚠️ Kanał alertu — kluczowa decyzja architektoniczna

`wp_mail()` na tym koncie idzie przez lokalny sendmail, którego Hostido **blokuje bez autoryzacji**. Dlatego:

**Watchdog robimy jako skrypt CLI + cron systemowy, NIE wewnątrz WordPressa** — i alert wysyłamy przez `~/bin/send-to-jan` (autoryzowany SMTP `claude@auratest.pl`). To eliminuje cały problem transportu i mieści task w 5–6 h.

*(Wariant „w WP przez `wp_mail`" wymagałby najpierw dopięcia SMTP → +2–3 h i wtedy 5–6 h jest napięte.)*

## Plan

1. **Skrypt `scripts/sync-watchdog.php`** (CLI, uruchamiany z crona) — czyta `asiaauto_sync_history` (przez wp-cli `wp option get` albo bezpośrednio z DB).
2. **Reguły alertu** (progi do kalibracji na realnych danych):
   - 🔴 **Zero `added` przez > 24 h** → alarm „feed nie dowozi nowych aut" *(to jest reguła, która złapałaby obecną awarię)*
   - 🔴 **Brak jakiegokolwiek przebiegu syncu przez > 2 h** → alarm „sync nie chodzi" *(WP-cron odpala się na ruchu — potrafi zdryfować; to trzeba pilnować osobno)*
   - 🟡 `status != ok` w ostatnich 3 przebiegach → ostrzeżenie
   - 🟡 Nagły skok `removed` (> 10% katalogu w dobę) → ostrzeżenie o masowym wycofaniu
3. **Antyspam:** alert wysyłany raz na dobę per typ (stan zapisywany w pliku), nie co przebieg crona.
4. **Alert przez `~/bin/send-to-jan`** — treść: co się dzieje, od kiedy, ostatnie 5 przebiegów, link do panelu.
5. **Cron:** co godzinę (`0 * * * *`). ⚠️ Backup crontaba przed edycją — 12.07 był incydent nadpisania.
6. **Bonus (w tych samych godzinach):** rotacja `asiaauto-sync.log` (17,9 MB i rośnie) + odpalenie diagnostyki z crona raz na dobę z alertem, gdy któryś check przekroczy próg.

## Strefy kruche

Brak — watchdog jest **wyłącznie czytelnikiem**. Nie dotyka importera, syncu ani bazy (poza SELECT-ami).

## Testy

**Automatyczne**
- Symulacja: podstaw sztuczną historię (zero `added` od 30 h) → skrypt musi wygenerować alarm.
- Symulacja: historia zdrowa → cisza (żadnego maila).
- Antyspam: 5 uruchomień z rzędu przy trwającej awarii → dokładnie 1 mail.

**Półautomatyczne**
- Odpalić na **obecnych, realnych danych** (feed jest martwy) → watchdog musi krzyczeć od razu. To jest najlepszy możliwy test: mamy prawdziwą awarię pod ręką.
- Kalibracja progu: przejrzeć historię z czerwca (3 awarie) → czy reguły by je złapały i ile dni wcześniej.

## Definicja zrobionego

- Cron co godzinę sprawdza kondycję feedu.
- Zero nowych aut przez dobę = mail do Janka, nie odkrycie po tygodniu.
- Alert nie spamuje (max 1/dobę/typ).
- Log pluginu ma rotację.
- Reguły skalibrowane na realnych awariach z czerwca i lipca.
