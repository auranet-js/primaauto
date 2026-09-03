# T-250 — 23 hasła słownika pod pastylki wyszukiwarki (2026-09-03)

Pipeline `scripts/kb/wiki_generate.py --config scripts/kb/wiki_tier6.json` (model sonnet, research
zewnętrzny przez `claude -p`). Dwa biegi padły na limicie sesji (13:12 i 13:20 — `claude -p` nie
korzysta z trybu obniżonego priorytetu sesji interaktywnej); bieg z 15:09: **21 OK, 2 błędy**
(`hud`, `sterowanie-gestami` — model oddał niedomknięty JSON), dogrywka tych dwóch osobno.

**Decyzja Janka:** publikacja od razu, bez szkiców — pipeline sam ustawia frazę kluczową i opis
Rank Math (`wiki_generate.py:183-185`) i pisze z uwzględnieniem faktów, więc weryfikacja po
publikacji jest umowna. 21 szkiców przełączone na `publish` jedną komendą 15:31; indeks
`AsiaAuto_Wiki_Cars::buildIndex()` przebudowany — każde hasło linkuje do ofert.

| hasło | klucz `_wiki_term_keys` | ofert w indeksie |
|---|---|---:|
| /wiki/dach-panoramiczny/ | `skylight_type: 全景天窗` | 1 716 |
| /wiki/centrowanie-na-pasie/ | `lane_center` | — |
| /wiki/tylna-os-skretna/ | `overall_turn` | 195 |
| /wiki/tempomat-adaptacyjny/ | `cruise_system: 自适应` + 2 klucze | — |
| /wiki/ekran-pasazera/ | `copilot_screen_resolution*` | 223 |
| /wiki/ekran-tylny/ | `rear_lcd_screen` | — |
| /wiki/dolby-atmos/ | `dolby_panoramic_sound` | 871 |
| /wiki/glosniki-w-fotelach/ | `seat_speakers` | — |
| /wiki/mirroring-telefonu/ | `mobile_system_8/11/1` | — |
| /wiki/rozpoznawanie-znakow/ | `road_traffic_sign_recognition` | — |
| /wiki/ladowanie-indukcyjne/ | `mobile_wireless_charging` | — |
| /wiki/podgrzewane-fotele/ | `front_seat_heating_1` | — |
| /wiki/internet-5g/ | `data_network_2` | — |
| /wiki/wentylowane-fotele/ | `front_seat_ventilation_1` | — |
| /wiki/pamiec-fotela/ | `front_seat_memory_1` | — |
| /wiki/podgrzewana-kanapa/ | `rear_seat_heating` | — |
| /wiki/podgrzewana-kierownica/ | `steer_wheel_heating` | — |
| /wiki/ogrzewanie-lusterek/ | `external_mirror_heat` | — |
| /wiki/zdalny-rozruch/ | `engine_remote_start` | — |
| /wiki/hak-holowniczy/ | `drag_hook` | 443 |
| /wiki/masaz-tylnej-kanapy/ | `rear_seat_massage` | 978 |
| /wiki/hud/ | `header_display_system` | 967 |
| /wiki/sterowanie-gestami/ | `gesture_control_system` | 166 |

(„—" = licznik nie sprawdzany ręcznie; indeks liczy wszystkie.) Liczniki indeksu są wyższe niż
pastylek w wyszukiwarce (dach 1 716 vs 1 658, Dolby 871 vs 748), bo `AsiaAuto_Wiki_Cars` traktuje
`选配` (opcja) jako „ma", a tabela specs liczy tylko standard — świadoma różnica z 02.09.

Słownik: 89 → **112 haseł** publish (21 + 2 z dogrywki; dogrywka poszła od razu z `--status publish`). Sitemap i indeksowanie: jak dotąd
z sitemapy RankMath (cron 05:40), bez Indexing API (memory `reference_indexing_zakres_newsy_wiki`).

**Otwarte z T-250:** krok 4 (CTA pod hasłem → `/wyszukiwarka/?wyposazenie=<flaga>`), krok 5 (styl
autolinków + ikona „i" przy pastylkach — na makiecie, po pomiarze GA4: autolinki w ofertach dały
3 przejścia na 50 tys. odsłon).
