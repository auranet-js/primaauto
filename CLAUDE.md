# CLAUDE.md — Prima Auto (asiaauto.pl)

> Ostatnia aktualizacja: 2026-04-15. Bootstrap repo.

---

## 1. Tożsamość projektu

- **Repo:** `primaauto` (GitHub: `auranet-js/primaauto`)
- **Produkcja:** `asiaauto.pl`
- **Plugin:** `asiaauto-sync` v0.29.0-wip (header; faktycznie zawiera patche v0.30.6)
- **Child theme:** `asiaauto` (Hello Elementor parent)
- **Klient:** Ruslan Prima, PRIMA-AUTO — agencja importu aut z Chin

**Dlaczego nazwy się rozjeżdżają:** Repo `primaauto` odzwierciedla markę agencji. Kod produkcyjny żyje pod `asiaauto-sync` / `asiaauto.pl`. Klasy `AsiaAuto_*`, CPT `listings`/`asiaauto_order`, meta `_asiaauto_*`, shortcody `[asiaauto_*]`, role `asiaauto_customer`/`primaauto` — wszystko zostaje. Rename pluginu/CPT/meta = osobny, świadomy projekt — **nigdy mimochodem**.

---

## 2. Source of truth

**Serwer produkcyjny** jest jedynym źródłem kodu. Repo jest kontekstowe (dokumentacja, skrypty, kolejka).

- Plugin: `~/domains/asiaauto.pl/public_html/wp-content/plugins/asiaauto-sync/`
- Theme: `~/domains/asiaauto.pl/public_html/wp-content/themes/asiaauto/`
- DB prefix: `wp7j_`

**Workflow zmian:**
1. Czytaj aktualny plik z serwera (`cat`/`Read`)
2. Edytuj lokalnie lub generuj artefakt
3. `php -l` przed wgraniem
4. `.bak` z datą przed nadpisaniem (przy większych modach)
5. Deploy na serwer
6. Smoke test
7. Podbij wersję w headerze jeśli bump warranted
8. Aktualizuj `docs/VERSIONS.md` + commit w repo kontekstowym

---

## 3. Strefy kruche

Nie ruszaj bez wyraźnej zgody. Szczegóły w `tmp/SKILL.md`.

1. **Pipeline cenowy** — `class-asiaauto-price.php`, 9 kroków, dual-mode CNY+CIF
2. **Importer / sync** — dual storage pattern, reservation guard, slug generation z `$api_value`
3. **Image pipeline** — Dongchedi URL-e wygasają, SEO naming, raw meta ops na `_thumbnail_id`
4. **Statusy zamówień** — 11 statusów, `LEGACY_STATUS_MAP`, reservation logic (stock vs customer)
5. **Umowa PDF** — `class-asiaauto-contract.php`, rework agency model w toku (Krok 1–4 DONE)
6. **MCP server** — `asiaauto.pl/mcp-test/mcp.php`, nie ruszaj z Claude Code

---

## 4. Czego NIE wolno bez pytania

- Zmieniać nazw klas / CPT / meta keys / shortcodów / ról
- Dotykać reservation logic, slug generation w importerze, image SEO naming
- Bumpować `ASIAAUTO_VERSION` bez deploya
- Pisać do produkcyjnej bazy bez backupu (`mysqldump` najpierw)
- Kasować plików z `wp-content/uploads/asiaauto/`
- Modyfikować `.htaccess` na produkcji
- Edytować `mcp-test/mcp.php`

---

## 5. Konwencja commitów

Format: `[scope] krótki opis`

Scope: `price:`, `order:`, `import:`, `contract:`, `theme:`, `infra:`, `docs:`, `queue:`

Przykłady:
- `[docs] bootstrap CLAUDE.md + QUEUE.md`
- `[price:] transient cache na COUNT queries`
- `[contract:] krok 5 — maile statusów pod narrację depozytu`

---

## 6. Deploy checklist

1. Backup pliku na serwerze (`.bak` z datą) przy większych zmianach
2. `php -l` na nowym pliku
3. Wgraj przez `cp` / edycję in-place
4. Flush cache jeśli dotyczy (OPcache, transient)
5. Smoke test (otwórz stronę, sprawdź admin, sprawdź log)
6. Tag git w repo kontekstowym + `docs/VERSIONS.md`

---

## 7. Kolejka zadań

Patrz `docs/QUEUE.md`.

---

## 8. Pending: podbicie wersji na v0.30.6

Patche A/B/C (override resilience + CIF widget + profiler) **są już wgrane na produkcję**, ale header pluginu nadal mówi `0.29.0-wip`. Do zrobienia:
- Zweryfikować obecność zmian w plikach na serwerze
- Podbić `ASIAAUTO_VERSION` w `asiaauto-sync.php`: `0.29.0-wip` → `0.30.6`
- Zaktualizować `docs/VERSIONS.md`

---

## 9. Materiały referencyjne

- `tmp/SKILL.md` — pełna architektura, decyzje, changelog (~4500 linii). Gitignored.
- `tmp/umowa-sprzedawcy.docx` — umowa Ruslana, kontekst dla Kroku 5. Gitignored.
- Globalny `~/.claude/CLAUDE.md` — tożsamość, stack, konwencje.
- Cross-project `~/projekty/CLAUDE.md` — zasady pracy cross-repo.
