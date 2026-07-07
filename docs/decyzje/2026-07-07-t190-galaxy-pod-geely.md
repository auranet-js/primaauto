# ADR — T-190: Galaxy zostaje pod Geely (wzorzec BYD Leopard)

> Data: 2026-07-07 (wieczór, sesja diagnostyczna T-190)
> Decyzja: **Janek**, po raporcie `auratest:primaauto-t190-diagnoza-2026-07-07.md`
> **ZASTĘPUJE** otwarty punkt „Galaxy jako osobna marka" z ADR `2026-06-19-T019-taksonomia-merge.md`

## Kontekst — sprzeczność do rozstrzygnięcia

Dwa zapisy stały w konflikcie:
- ADR T-019 (19.06): „DECYZJA Janka — Galaxy jako osobna marka/sub-brand, NIE mergować make `galaxy` do `geely`" (punkt otwarty).
- Decyzje routingu 07-07 (memory `project_hub_taxonomy_decisions_2026_07_07`): „Galaxy → zostaje Geely".

Baza wisiała w rozkroku: make `galaxy` (term **6579**, 17+ aut) istnieje, ale `/samochody/galaxy/` robi 301→geely (V61); serie Galaxy częściowo pod Geely (`e5` 3397, `starship-8-phev` 3406, `m9` 6550), częściowo pod make Galaxy (przypisania listingów `a7-em` 6539, dup `m9`).

## Decyzja (finalna)

**Galaxy = sub-marka Geely, wzorzec identyczny jak BYD Leopard** (`byd/leopard-5`):

1. **make listingów Galaxy = `geely`** — make term `galaxy` (6579) do wygaszenia: wszystkie listingi z make `galaxy` przepiąć na `geely`, term po wyzerowaniu skasować lub zostawić pusty do sprzątki sierot.
2. **Nazwa sub-marki zostaje w MODELU**: „Galaxy M9", „Galaxy E5", „Galaxy A7 EM-i" — parent = make `geely`.
3. **Slugi kanoniczne = targety T-019** (bez prefiksu `galaxy-`): `e5`, `starship-8-phev`, `m9`, `l6`, `l7`… Kolizje slugów z innymi markami (np. `m9` AITO) są legalne — parent disambiguuje, URL dwupoziomowy `/samochody/geely/m9/`.
4. **301 V61 `galaxy`→`geely` ZOSTAJE** (bez żywego huba `/samochody/galaxy/`).
5. **Mapowanie v6.2**: wpisy `Geely|Galaxy *` → `mark_eu: Geely`, slug kanoniczny (drop `galaxy-`), nazwa serie z „Galaxy " w środku.

## Stan PRZED zmianami (rollback reference)

| Obiekt | term_id | Stan 2026-07-07 wieczór |
|---|---|---|
| make `galaxy` | 6579 | istnieje, listingi z make=galaxy (m.in. 21× „Galaxy A7 EM-i", 4× M9, 1× M7) |
| make `geely` | 3626 (tt parent ref) | kanoniczny |
| serie `m9` „Galaxy M9" | 6550 | parent geely, count 4 |
| serie `galaxy-m9` „Galaxy M9" | (nowa sierota po T-019) | ORPHAN, 6 aut — nawrót importera |
| serie `galaxy-e5` | (nowa sierota) | ORPHAN, 7 aut — nawrót |
| serie `galaxy-starship-8-phev` | (nowa sierota) | ORPHAN, 11 aut — nawrót |
| serie `a7-em` „Galaxy A7 EM-i" | 6539 | parent geely; 21 listingów ma make=galaxy (mismatch) |

Backupy: `~/backups/primaauto/2026-07-07/taxonomy-pre-hubfix.sql` (wzorcowy z sesji dziennej); **przed każdym krokiem apply T-190 robimy świeży dump 4 tabel taksonomii** (`wp7j_terms`, `wp7j_term_taxonomy`, `wp7j_term_relationships`, `wp7j_termmeta`) z datą i nazwą kroku.

## Wymóg wykonawczy (polecenie Janka 2026-07-07)

**Strefa krucha — każdy krok T-190 dokładnie logowany**, żeby przy regresji dało się szybko znaleźć przyczynę:
- każdy apply poprzedzony dry-runem z zapisanym outputem,
- dziennik zmian per krok: `docs/seo/t190-log.md` (co, kiedy, które term_id/listingi, plik backupu, skrypt),
- diff przed każdą edycją plików strefy kruchej (importer, mapowanie, redirects),
- po kroku: smoke (render huba, `grep -c "Nie znaleziono modelu"`, 301-check).

## Konsekwencje / kolejność

Merge'e galaxy-* wykonywać dopiero PO wdrożeniu guarda importera (inaczej importer odtworzy sieroty po raz trzeci — dowód: nawroty wszystkich 3 merge'ów T-019).
