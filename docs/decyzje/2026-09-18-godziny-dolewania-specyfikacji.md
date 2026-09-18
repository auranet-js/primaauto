# 2026-09-18 — cykl dolewania specyfikacji przeniesiony na wieczór

> **Status:** wdrożone (crontab, backup `~/backups/crontab/crontab-2026-09-18-174534.bak`)
> **Poprzednik:** `docs/decyzje/2026-09-02-katalog-autohome-trzecie-ogniwo-nocne.md`

## Problem — cykl jechał przed dniem, nie po nim

Zgłoszenie Janka: „oferty kończą wchodzić po południu i wtedy trzeba by je uzupełniać,
a nie rano kiedy wchodzą nowe". Pomiar potwierdził w całości.

⚠️ **Strefy: WP tej instalacji chodzi w UTC** (`gmt_offset = 0`, `timezone_string` pusty),
więc `post_date` = `post_date_gmt`, a log pluginu też stempluje UTC. **Crontab systemowy chodzi
w czasie serwera (CEST = UTC+2).** Pomiar z bazy trzeba przeliczyć, zanim ustawi się godzinę crona
— przy pierwszym podejściu tabela poniżej została opisana jako „czas PL", czym była w UTC.

Rozkład godzinowy wejść ofert (che168, 14 dni, średnio na dobę):

| UTC | 02 | 03 | 04 | 05 | 06 | 07 | 08 | 09 | 10 | 11 | 12 | 13 | 14 | 15 | 16 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| **PL (CEST)** | **04** | **05** | **06** | **07** | **08** | **09** | **10** | **11** | **12** | **13** | **14** | **15** | **16** | **17** | **18** |
| ofert | 1,7 | 10,1 | 9,6 | 9,4 | 12,4 | 15,6 | 12,7 | 12,6 | 7,6 | 7,1 | 3,0 | 3,5 | 2,8 | 1,3 | 1,6 |

Strumień pracuje **04:00–18:00 czasu polskiego**, szczyt 08–11, ogon do 18:00, potem cisza
(chińskie godziny robocze 10–24 CST). Cykl dolewania stał na 04:25–05:05 PL = 02:25–03:05 UTC,
czyli **przed całym dniem** — obsługiwał wczorajszy ogon, a nie dzisiejszy zaciąg.

Dowód wprost, pomiar 18.09 o 17:20:

| oferty z dnia | sztuk | pełne (6 kB+) |
|---|---|---|
| dziś (weszły 04:00–12:57 PL) | 110 | **0** |
| wczoraj (po nocnym cyklu) | 125 | 100 |

Każda oferta wisiała na stronie **~21 h z niepełną specyfikacją** — pusta tabela wyposażenia
dla klienta i dla Google.

## Drugi problem — limit dławił przy jednym kanale

`backfill-spec-autohome.php` miał limit **100 ofert na bieg**, log kończył się na
`--- 100/100 ---`. Podaż che168 to 74–127/dobę (średnio ~104). Saldo ujemne:
**kolejka 136 ofert, najstarsza z 21.06**.

## Decyzja

| ogniwo | było | jest |
|---|---|---|
| `merge-spec-from-twin.php` (bliźniak) | 04:35 | **17:15** |
| `dolej-spec-z-banku.php` | 04:45 | **17:25** |
| `backfill-spec-autohome.php` | 04:55, limit 100 | **17:35, limit 200** |
| `zbuduj-specs.php` | 05:05 | **17:55** |
| `gasz-martwe-oferty.php` | 04:25 | **bez zmian** |

Gaszenie zostaje rano — nie zależy od świeżych ofert, a wieczorem tylko wydłużałoby cykl.
Kolejność bliźniak → bank → katalog → specs zachowana (bliźniak zdejmuje bankowi cele,
tabela specs musi iść po dolewkach).

Godziny crona są w czasie serwera (CEST). 17:15 PL = 15:15 UTC łapie ~99% dnia; po tej godzinie
wchodzi jeszcze ~3 oferty na dobę (godziny 17–18 PL), które przeczekają do następnego biegu.
Dociągnięcie cyklu do 19:15–19:55 PL złapałoby dzień w całości — rozważane, decyzja Janka.

## Wynik pierwszego biegu (18.09, uruchomiony ręcznie o 17:45)

- katalog Autohome: **136 ofert, +11 491 pól** (śr. +84), 14 pobrań z sieci, 122 z cache, 0 błędów
- tabela specs: 711 wierszy
- bank: 0 ofert (brak dawców — wersje spoza banku)
- **kolejka Autohome: 136 → 0**

Dzisiejsze oferty: **0 → 84 pełnych** (25 średnich, 1 chuda).

## Co zostaje nieosiągalne

19 ofert publish poniżej 2 kB, każda sprawdzona co do przyczyny:

- **5 che168** — mają `spec_id`, katalog próbowany dla wszystkich; Autohome nie ma więcej danych
- **14 dongchedi** — **brak `spec_id`**, katalog bezradny z definicji; bank i bliźniak bez dawcy

25 dzisiejszych „średnich" przeszło przez katalog (25/25) — to ubogie wersje w samym katalogu,
nie zaległość. Ścieżka dla tych 14: ręczne nadanie `specid`
(`reference_autohome_specid_recznie_dla_dongchedi`) — nie podjęta.

## Konsekwencje

- Czas oczekiwania na pełną specyfikację: **~21 h → ~1–10 h** zależnie od godziny wejścia.
- Przepustowość 200/bieg przy podaży ~104/dobę daje zapas na drugi kanał (dongchedi).
- **Do rewizji, jeśli** wróci dongchedi w trybie `full`: podaż ~210/dobę zbliży się do limitu 200.
  Wtedy albo limit wyżej, albo drugi bieg (wariant rozważany: 11:00 + 17:30).
