# BYD Tang L → „Tang L (Atto 8)" — plan, czeka na decyzje

**Źródło:** Ruslan, WhatsApp 13.10.2026 13:35: „BYD Tang L (Atto 8) — tak napisz, bo wchodzi do EU pod tą nazwą".
**Status:** nic nie zmienione na produkcji. Czeka na decyzje Janka (niżej).

**Sprzeczność z wcześniejszą decyzją:** 14.05.2026 Ruslan potwierdził „Tang L = Sealion 8" (memory
`reference_byd_tang_aliases.md`). Nowsza decyzja wygrywa: Tang L = Atto 8, „Sealion 8" znika z nazw.

## Stan w bazie (13.10)

| term_id | nazwa | slug | ofert | `_serie_full_title` |
|---|---|---|---|---|
| 3746 | Sealion 8 DM-I (Tang L) | `sealion-8-dm-i` | 14 | BYD Sealion 8 |
| 3703 | Sealion 8 (Tang L) EV | `sealion-8-ev` | 6 | BYD Sealion 8 EV |
| 3700 | Tang DM-i | `tang-dm-i` | 31 | BYD Tang — **nie ruszamy** (stary Tang, nie Tang L) |

Część ofert che168 ma już tytuł „BYD Tang L 2025 …" (niespójność). Mapowanie: `data/brand-mapping-v6.1.php`,
`data/che168-model-map.php`, `includes/class-asiaauto-redirects.php`.

## Docelowo (wzorzec „Tiggo 9 (Jaecoo 8)", 14.09)

| Miejsce | Teraz | Po zmianie |
|---|---|---|
| Tytuł oferty | BYD Sealion 8 DM-I (Tang L) 2025 DM-p 175KM 4WD Edition | BYD Tang L DM-i (Atto 8) 2025 DM-p 175KM 4WD Edition |
| Tytuł oferty EV | BYD Sealion 8 (Tang L) EV 2025 600KM 4WD … | BYD Tang L EV (Atto 8) 2025 600KM 4WD … |
| H1 huba (`_serie_full_title`) | BYD Sealion 8 / BYD Sealion 8 EV | BYD Tang L (Atto 8) / BYD Tang L EV (Atto 8) |
| Nazwa termu = etykieta filtra | Sealion 8 DM-I (Tang L) | Tang L DM-i / Tang L EV (bez nawiasu) |

## Kroki

1. Backup: mysqldump termów + tytułów ofert obu serii, `.bak` 3 plików mapowania.
2. Rename termów 3746 / 3703.
3. `_serie_full_title` obu termów.
4. `serie_eu` w obu mapowaniach (nowe importy dongchedi + che168).
5. Tytuły 20 istniejących ofert skryptem `$wpdb` (dry-run → lista do wglądu). NIE `odswiez-tytuly-ofert.php` — zdejmuje alias.
6. Treść obu hubów: Sealion 8 → Tang L (Atto 8); „Sealion 8" ewentualnie jako wzmianka w treści (frazy wyszukiwania).
7. Memory: poprawka `reference_byd_tang_aliases.md`.

## Decyzje do podjęcia

- **Slugi URL** — rekomendacja: zostają (`sealion-8-dm-i`, `sealion-8-ev`); alternatywa `tang-l-*` + 301.
- **Dwie serie (DM-i / EV) czy jedna** — rekomendacja: zostają dwie.
- **„Sealion 8" w treści huba** — rekomendacja: tak, po sprawdzeniu w GSC, czy fraza zbiera ruch.
- **Reklamy** — sprawdzić, czy Ads/Meta celują w „Sealion 8" (tylko odczyt, zmiany osobno).

## GSC — czego dotyka zmiana (13.10, okres 13.07–11.10)

Ruslan zaakceptował tabelkę „po zmianie". Sprawdzenie rankingu przed wdrożeniem:

- Hub `/samochody/byd/sealion-8-dm-i/` = **7. strona serwisu**: 1 454 klik / 11 333 wyśw (ok. 2,5% kliknięć całej witryny, 58 748).
  Miesięcznie: lip 199, sie 625, wrz 525, paź (1–11) 105 klik.
- Frazy z „sealion 8": 759 klik / 4 340 wyśw, prawie wszystko na hubie DM-i, poz. 1,5–1,7
  („byd sealion 8" 2 218 wyśw / 400 klik; „…kiedy w polsce" 136 klik; „…cena" 69 klik).
- Frazy z „sealion 08" na tym samym hubie: ok. 250 klik („byd sealion 08" 1 272 wyśw / 167 klik, poz. 3,1).
  **Sealion 08 to osobny nowy model BYD (seria Ocean, premiera 09.2026)** — nasze 3 newsy z 08–09.2026.
  Ten ruch hub łapie „przy okazji" podobnej nazwy.
- „atto 8": 172 wyśw / 12 klik, już na hubie DM-i (poz. 1–5) — nazwa ma już popyt.
- „tang l": ~250 wyśw rozproszone na hub `tang-dm-i` (stary Tang) i oferty, prawie bez kliknięć — rename to porządkuje.
- Hub `sealion-8-ev`: 37 klik / 876 wyśw — marginalny.

**Ryzyko:** zmiana H1/meta title z „BYD Sealion 8" na „BYD Tang L (Atto 8)" może obniżyć pozycję 1,6 na „byd sealion 8"
(główne źródło kliknięć huba). Slug zostaje, więc URL i historia zostają.

**Rekomendacja:** slug bez zmian; „Sealion 8" zostaje w meta title/description i treści huba jako druga nazwa
(np. „BYD Tang L (Atto 8) — dawniej Sealion 8"). Pomiar po zmianie: `scripts/gsc-hub-pomiar.py` po 14 i 28 dniach.

Zauważone obok, nie ruszam: Sealion 08 nie ma własnego huba ani termu — osobny temat.

## Korekta 13.10 — Atto 8 w Europie to tylko EV

Premiera Atto 8 w Paryżu 12–13.10.2026: w Europie wyłącznie wersja elektryczna (Design RWD / Excellence AWD,
115 kWh), sprzedaż od I kw. 2027, brak polskiej daty i cennika. Hybrydy DM-p BYD do Europy nie zapowiedział.
Tabelka zaakceptowana przez Ruslana (oba warianty → Atto 8) była zrobiona przed sprawdzeniem tego faktu — **nieaktualna**.

Kierunek (decyzje Janka w toku):
- **Hub EV `sealion-8-ev`** — wariant **B** (Janek 13.10): nazwa „BYD Tang L EV (Atto 8)" (H1 + title), lead z Atto 8,
  sekcja „Import z Chin czy salon" przepisana (był błąd „chiński Sealion 8 to inny pojazd niż europejski Tang"),
  nowa sekcja „Sealion 8, Tang L, Atto 8 — to ten sam samochód?" (+ odróżnienie od Sealion 08, link do DM-i),
  FAQ 2 i 3 wymienione, literówki „przy średnie" / „jedyna droga".
- **Hub DM-i** — nazwa i H1 bez zmian; ewentualnie akapit + FAQ „Atto 8 w Europie to EV, hybrydy nie ma" z linkiem do EV.
- **Oferty** — „Tang L EV (Atto 8)" tylko dla EV; DM-i bez zmian (rekomendacja).

## WDROŻONE 13.10 (zgoda Janka na 1–3)

Backup: `~/backups/primaauto/2026-10-13-atto8/` (termmeta 3703+3746, posty EV, mapowanie) +
`data/brand-mapping-v6.1.php.bak-2026-10-13-atto8` na serwerze.

1. **Hub EV** (term 3703, slug `sealion-8-ev` bez zmian): `_serie_full_title` = „BYD Tang L EV (Atto 8)",
   focus keyword j.w., lead, akapit otwierający, sekcja „Import z Chin czy salon" + nowa sekcja
   „Sealion 8, Tang L, Atto 8 — to ten sam samochód?", FAQ 2 i 3, literówki; title/desc przez
   `AsiaAuto_HubTitleGenerator::regenerateForTerm(3703)`. Nazwa termu (filtr) bez zmian: „Sealion 8 (Tang L) EV".
2. **9 ofert EV**: „BYD Tang L EV (Atto 8) 2025 …" (post_title przez `$wpdb`, post_name bez zmian).
   Mapowanie `BYD|Tang L EV`: `serie_eu` = „Tang L EV (Atto 8)", `title_eu` = „BYD Tang L EV (Atto 8)".
3. **Hub DM-i** (term 3746): nazwa/H1/title bez zmian; akapit „A BYD Atto 8?" na końcu sekcji „Cena i dostępność"
   z linkiem do huba EV + FAQ (pozycja 3) „Kiedy BYD Atto 8 będzie w Polsce i czy to ten sam samochód?".

Weryfikacja (curl na produkcji): H1/title obu hubów i 2 ofert (399762, 538664) zgodne; FAQPage schema zawiera nowe pytania.

**Uwaga:** NIE uruchamiać `scripts/odswiez-tytuly-ofert.php` na termie 3703 — liczy z nazwy termu i zdejmie „Tang L EV (Atto 8)".
Pomiar: GSC dla obu hubów po 14 i 28 dniach (`scripts/gsc-hub-pomiar.py`).

**Indexing API 13.10:** zgłoszenie obu hubów nieudane (`rate_limited=True`, pula „Piaskownica" wyczerpana
przez zgłoszenia PrimaAuto poza wrapperem). Do ponowienia po resecie (~09:00 PL):
`index-submit --project primaauto --type URL_UPDATED --url …/sealion-8-ev/ --url …/sealion-8-dm-i/`.

## Etap 2 — Sealion 08 zamiast „Sealion 8" (CZEKA na akcept Ruslana, 13.10)

**Skąd:** Ruslan dostaje telefony o nowego **BYD Sealion 08** (seria Ocean, premiera w Chinach 2.09.2026,
5115 mm, EV + PHEV, 5/6 miejsc, 229 900–279 900 CNY). Ludzie wpisują „sealion 8" bez zera.
To inny model niż Tang L i niż Datang (Great Tang, Dynasty, 5263 mm — hub `datang` bez zmian).

**Dane (GSC 13.07–11.10):** „sealion 08" na hubie DM-i: sie 993 wyśw / 143 klik (skok z premierą), „sealion 8"
rośnie od maja (368 → 1 110 w lip) — do lipca to był Tang L, od sierpnia ruch mieszany.
**GA4 (90 dni):** hub DM-i 748 wejść, wszystkie strony Tang L razem = **2 kontakty** (click_phone na hubie,
click_whatsapp na ofercie 468692; GA4 widzi ~1/3 przez zgodę) → przekierowanie zabiera mało leadów Tang L.
**Źródła:** BYD dopuszczony w filtrze che168/dongchedi, Sealion 08 nie na blackliście; brak w mapowaniu;
w `asiaauto_che168_unmapped` (rolling 200) niewidoczny — najpewniej brak podaży używanych w filtrze (≤40 tys. km, 31 miast).
Europejska nazwa 08 niepotwierdzona (prasa spekuluje „Sealion 8"); „Sealion 8" PHEV istnieje jako eksport (Australia).

**Decyzja Janka + Ruslana:** oba Tang L jako Atto 8 (hybryda z dopiskiem „w Europie Atto 8 tylko jako elektryk").

**Plan (tabela wysłana Ruslanowi 13.10):**

| Model | Teraz | Po zmianie |
|---|---|---|
| BYD Sealion 08 | brak strony | nowy hub `/sealion-08/` (wzorzec premierowy, bez ofert): cena w Chinach, wersje, kiedy w PL, sprowadzenie na zamówienie + mapowanie 08 |
| Tang L hybryda (14 ofert) | „BYD Sealion 8 DM-I (Tang L)", `/sealion-8-dm-i/` | nowy hub `/tang-l-dm-i/` „BYD Tang L DM-i (Atto 8)" + dopisek EV/PHEV; tytuły ofert + mapowanie `BYD\|Tang L DM` |
| Tang L EV (9 ofert) | „BYD Tang L EV (Atto 8)", `/sealion-8-ev/` | `/tang-l-ev/` (301), nazwa bez zmian |
| `/sealion-8-dm-i/` | hub Tang L hybrydy | 301 → `/sealion-08/`; na 08 ramka „Szukasz 7-osobowej hybrydy Tang L (Atto 8)? →" |

**Otwarte pytanie do Ruslana:** czy sprowadzamy Sealion 08 na zamówienie (nowe auto)? Od tego zależy treść huba 08.
**Ryzyko:** spadek pozycji na „sealion 8" przez kilka tygodni po 301 na stronę bez ofert.
**Uwagi wykonawcze:** puste termy `tang-l-dm` (6630) i `tang-l-ev` (6621) już istnieją — sprawdzić przed zakładaniem
nowych (duplikaty); redirect legacy w `class-asiaauto-redirects.php` (`sealion-8-dm-i-tang-l`, `sealion-8-tang-l-ev`)
przepiąć na nowe slugi; Indexing API po resecie puli; GSC po 14 i 28 dniach.
