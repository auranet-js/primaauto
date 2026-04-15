# ADR: Repo kontekstowe (bez mirrorowania kodu)

**Data:** 2026-04-15
**Status:** Przyjęte
**Uczestnicy:** Jan Schenk, Claude Code

## Kontekst

Bootstrapujemy repo `primaauto` na GitHub dla projektu asiaauto.pl. Plugin `asiaauto-sync` (~23k linii PHP) żyje na serwerze produkcyjnym Elara, do którego Claude Code ma natywny dostęp bash.

## Decyzja

Repo `primaauto` jest **kontekstowe** — zawiera dokumentację, skrypty i kolejkę zadań, ale **nie zawiera kodu pluginu ani theme**. Kod czytamy i edytujemy bezpośrednio na serwerze.

## Uzasadnienie

1. **Bezpośredni dostęp.** Claude Code ma bash na Elarze — może czytać, lintować i edytować pliki pluginu in-place. Mirror do repo tworzy niepotrzebną pętlę sync.
2. **Spójność z innymi projektami.** `plakatydlafirm` (wzorzec) działa tak samo — `src/plugins/` i `src/theme/` są puste, repo trzyma kontekst.
3. **Single source of truth.** Serwer produkcyjny jest jedynym źródłem kodu. Eliminuje ryzyko rozbieżności repo vs prod.
4. **Śledzenie zmian.** Git log repo kontekstowego + QUEUE.md + docs/VERSIONS.md dają pełną historię co i dlaczego się zmieniało. Przed większymi modami robimy `.bak` na serwerze.

## Konsekwencje

- Brak pełnej historii diffów kodu (tylko opisy zmian w commitach i docs/)
- Backup kodu = `.bak` pliki na serwerze + backup hostingowy Hostido
- Przy migracjach/cutover trzeba rsync z serwera, nie checkout z repo
