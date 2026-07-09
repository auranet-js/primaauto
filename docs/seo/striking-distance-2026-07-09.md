# Striking-distance — fala 3 (T-198, 2026-07-09)

Kontynuacja `striking-distance-2026-07-07.md`. Geneza: audyt 2026-07-09 → T-198. Priorytet P2.

## Ustalenie kluczowe (dlaczego NIE 4 z promptu)

Prompt T-198 wskazywał 4 huby (shark-6, monjaro, z9-gt, yu7) — ale **wszystkie 4 były już zrobione 07-07** (`v2-striking`, mają `cena` w title + `skip_regen=1`). Okno GSC audytu (2026-06-09→07-06) **kończy się PRZED** fixem 07-07, więc niskie CTR (byd shark 1,04%) to pomiar STAREGO tytułu. Nowy już live, niezmierzony → weryfikacja przez recheck, nie ponowny fix.

## Skan pełnej puli striking (dowód, `tmp/gsc-striking-inventory-2026-07-09.py`)

Krzywa CTR primaauto (własna, 28d): poz 1-2 = 12,3% | 3-5 = 5,5% | 6-10 = 2,2% | 11-20 = 0,9%.

20 hubów w striking (poz 5,5-20, impr≥100). Z tego **11 już zrobionych** (cena+skip), 9 stary format. Z 9 tylko 6 „floor>0", a po sprawdzeniu CAŁOŚCIOWEJ wydajności per hub **3 już wygrywają główne frazy** (Leopard 3 poz 3,6/13%, Sealion 8 poz 3,2/11%, Exeed VX poz 1,9/18% — striking-query to poboczny wariant pisowni; NIE ruszane). **Realne = 3 huby.**

## Co zmienione (3 huby, term meta prod `wp7j_`)

Format jak 07-07: `{fraza} cena w Polsce 2026 — od {min} PLN | Prima-Auto` + `_asiaauto_skip_title_regen=1` + `_asiaauto_seo_rework=v3-striking-2026-07-09`. Meta: `rank_math_title`, `rank_math_description`, `asiaauto_seo_desc`. Min = liczba z dotychczasowego tytułu (generator). Backup: `~/backups/primaauto/2026-07-09/termmeta-pre-t198.sql`.

| term_id | hub | baseline GSC (28d, przed) | nowy title |
|---|---|---|---|
| 4823 | Zeekr 001 (serie) | „zeekr 001 cena" 211 impr / poz 7,5 / **1,4%**; hub 632/3,5%/poz 8,1 | Zeekr 001 cena w Polsce 2026 — od 195 000 PLN \| Prima-Auto |
| 5733 | Li Auto (make) | „li auto" 308 impr / poz 8,8 / **1,0%**; hub 2095/2,7%/poz 7,3 | Li Auto cena w Polsce 2026 — od 191 000 PLN \| Prima-Auto |
| 5291 | Mazda EZ-6 (serie) | „mazda ez-6" 121 impr / **0%** / poz 6,2; hub 1059/4,3%/poz 5,6 | Mazda EZ-6 cena w Polsce 2026 — od 132 000 PLN \| Prima-Auto |

Smoke: 3× HTTP 200, title renderuje się w HTML. Indexing API: 3 URL zgłoszone (67/100). skip_regen=1 zweryfikowany odczytem.

## Uwagi

- `skip_regen=1` zamraża min-cenę w title → okresowy ręczny refresh (jak wszystkie piloty).
- Cofnięcie: usunąć `_asiaauto_skip_title_regen` na 3 termach → cron odbuduje stary format.
- **Osobny track (nie striking):** „prima auto" (marka!) na `/samochody/` poz 9,8 / **0% CTR** — brand SERP / homepage, do rozważenia osobno.
- Wartość: konserwatywnie ~+9 klik/28d podłogi (3 huby); realny sygnał dopiero po recrawlu (+14d recheck).
