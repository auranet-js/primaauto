# Stan kanałów importu — che168 i dongchedi (28.08.2026)

Diagnostyka read-only na produkcji: baza (`wp7j_posts` + `wp7j_postmeta`),
`asiaauto_sync_history`, opcje trybu, `scripts/che168-monitor.php`.
Nic nie zmieniane.

## Zapas (CPT `listings`)

| Źródło | publish | draft | trash |
|---|---|---|---|
| che168 | 1 864 | 360 | 762 |
| dongchedi | 1 130 | 199 | 230 |
| ręczne / bez `_asiaauto_source` | 110 | 13 | 3 |

## che168 — pracuje normalnie

- Tryb `full`, kursor `10932328`, **zaległość 0 zdarzeń**.
- Cron `asiaauto_sync_changes` co 15 min, wszystkie 50 zapisanych biegów `ok`.
- Nowe: 28.08 — 99, 27.08 — 76, 26.08 — 107 szt.
- **Wszystkie nowe wpadają w oknie 02:00–09:00 czasu PL** (08–15 CST, chińskie
  godziny robocze). Popołudniowe `added=0` w historii to cisza po stronie
  źródła, NIE awaria — nie diagnozuj strumienia po biegach z popołudnia.
- Aktualizacje: 297 ofert dotkniętych 28.08.
- Skok 21–23.08 (435 / 334 / 263 dziennie) = efekt domapowania z 18.08,
  potem powrót do normy ~80–110/dobę.

## dongchedi — stoi w trybie `verify`

- `asiaauto_sync_mode_dongchedi = verify`. Kod (T-222) w tym trybie świadomie
  odrzuca nowy towar; zostaje aktualizacja cen i wycofywanie zniknięć.
- **Ostatnia nowa oferta: 24.08.2026 08:02** (Li Auto L6 2024 Pro Edition).
- Wysokie `skipped` w każdym biegu (18–232) to właśnie odrzucane nowe — sam
  licznik nie jest objawem awarii.
- Aktualizacje żyją: 75–123 oferty dziennie, zapas 1 130 szt. nie gnije.

**Otwarte:** notatka z 16.08 (`2026-08-16-extra-prep-dongchedi-plan.md`) mówi
o przełączeniu na `full` i wznowieniu importu. Kiedy i przez kogo wróciło na
`verify` — NIE USTALONE: w repo brak śladu, opcje WP nie mają znacznika czasu.
Decyzja do podjęcia: wznawiamy dongchedi czy wygaszamy zgodnie z T-222.

## Luka po stronie che168 (pomiar magazynu, nie strumienia)

- **50 ofert do wzięcia od ręki** — zmapowane, w segmencie, brak w bazie.
  Najwięcej: WEY/Wey po 7, Zeekr 6, iCAR 6, eπ 4, XPeng 4.
- **65 ofert zablokowanych brakiem wpisu w mapie**, 29 modeli. Największe:
  Lotus Emira 11, Jetour Freer 6, 奥迪E5 Sportback 6, 大通 Dajia 7 — 5 szt.
- Rozjazdów mapowania brak: 161 serii kanonicznych, każda trafia w jeden hub.
- Liczby to DOLNA granica — limit 6 stron/markę wyczerpał się dla 33 marek
  (`--pages` wyżej przy kolejnym pomiarze).
- W `asiaauto_che168_unmapped` 202 wpisy `?|?` (ostatnio 28.08 09:12) — puste
  marka/model ze strumienia, osobny wątek.

## Zauważone obok, nie ruszane

- 52 martwe oferty dongchedi w `publish`, których cron gaszenia (04:25) nie
  bierze — od kilku dni `Do wygaszenia: 0`.
- `dolej-spec-z-banku.php` (04:45) zapisał ostatnio 0 ofert.
