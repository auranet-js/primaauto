# T-260 „huby bez ofert wypadają z indeksu” — pomiar (07.10.2026)

> Krok 1 z promptu `docs/sesje/2026-10-07-PROMPT-huby-bez-ofert-noindex.md`. Tylko odczyt — na produkcji nic nie zmienione.
> Źródła: baza `wp7j_` (stan 07.10), GSC Search Analytics (07.07–04.10), GSC URL Inspection (07.10, 112 URL-i), `curl` meta robots na żywo.

## Wnioski w skrócie

1. **`count` jest zgodny z rzeczywistością.** 0 hubów z `count=0`, które mają opublikowaną ofertę; serie nie mają hierarchii (0 termów-dzieci), a hub pyta wyłącznie o własny term. Zjawisko z lipca („88 hubów z towarem bez countu”) **dziś nie występuje** — punkt 2 promptu: pusto.
2. **Historii ofert nie da się odtworzyć z bazy.** Z 2 340 serii `count=0` tylko 23 mają jakikolwiek wiersz w `term_relationships` (draft/trash); reszta przepadła z rotacją. Log `index-submit` nie zapisuje URL-i. Jedynym wiarygodnym śladem „hub był żywy” jest **GSC** (wyświetlenia w historii).
3. Ze 2 581 termów `count=0` (make + serie) **162 mają jakąkolwiek historię w GSC**. Reszta to widma — reguła RankMath słusznie je trzyma poza indeksem.
4. Z tych 162: **45 to marki informacyjne** (VW/Audi/Volvo/BMW, `_asiaauto_info_only=1`) — 2 378 kl. / 42 715 wyśw. w 90 dni, noindex zgodny z decyzją 28.09. Zostaje **117 hubów chińskich** (112 z `noindex` na żywo, 5 to przekierowania).
5. Z 117 chińskich: **22 huby realnie warte indeksu** (399 kl. / 8 955 wyśw. w 90 dni), **14 to duplikaty żywych wariantów** (110 kl.), 31 z marginalnym ruchem, 42 bez ruchu w 90 dni.
6. **Kiedy wypadły:** ostatnie wyświetlenia grupy A rozkładają się od 01.08 do 04.10 — to nie jeden incydent, tylko ciągły odpływ przy każdej sprzedaży ostatniej sztuki. 4 huby z grupy A **wciąż są w indeksie** (ostatni crawl przed zejściem do 0) i wypadną przy najbliższym crawlu: `chery-fulwin/x3l`, `changan/changan-cs75`, `mazda/cx-5`, `byd/sealion-6-ev`.
7. Whitelist premierowa (`asiaauto_hub_index_whitelist`, 5 term_id) działa — Q06, GX7, Adamas zwracają `index` mimo `count=0`.

## Grupa A — wartościowe, do przywrócenia (22)

Kryterium pomiaru: ≥50 wyśw. lub ≥5 kl. w 90 dni, nie duplikat żywego wariantu.

| hub | typ | kl. 90 d | wyśw. 90 d | poz. | ost. wyśw. | indeks (URL Inspection) | spec | opis |
|---|---|---|---|---|---|---|---|---|
| `nio/et9/` | model | 75 | 1748 | 7.8 | 2026-09-05 | poza: noindex | tak | tak |
| `haval/h6/` | model | 50 | 1360 | 5.2 | 2026-09-03 | poza: noindex | tak | tak |
| `hiphi/` | marka | 41 | 1233 | 6.1 | 2026-09-26 | poza: noindex | nie | tak |
| `gac/gs8/` | model | 76 | 720 | 4.1 | 2026-10-02 | poza: noindex | tak | tak |
| `toyota/corolla-cross/` | model | 7 | 594 | 7.8 | 2026-08-12 | poza: noindex | tak | tak |
| `chery-fulwin/t9/` | model | 21 | 523 | 6.4 | 2026-09-09 | poza: noindex | tak | tak |
| `chery-fulwin/t11/` | model | 24 | 442 | 5.6 | 2026-09-20 | poza: noindex | tak | tak |
| `chery-fulwin/x3l/` | model | 24 | 362 | 4.9 | 2026-10-04 | w indeksie (wypadnie przy crawlu) | tak | tak |
| `byd/sealion-6-ev/` | model | 17 | 311 | 8.9 | 2026-10-04 | w indeksie (wypadnie przy crawlu) | nie | tak |
| `lynk-co/lynk-co-10-em-p/` | model | 11 | 267 | 4.7 | 2026-10-02 | poza: noindex | tak | tak |
| `changan/changan-cs75/` | model | 2 | 244 | 7.4 | 2026-10-04 | w indeksie (wypadnie przy crawlu) | tak | tak |
| `byd/seal-5-dm/` | model | 3 | 225 | 12.8 | 2026-09-24 | poza: noindex | tak | tak |
| `mazda/cx-5/` | model | 3 | 163 | 8.2 | 2026-10-04 | w indeksie (wypadnie przy crawlu) | nie | nie |
| `gac/m6/` | model | 10 | 160 | 5.6 | 2026-08-21 | poza: noindex | tak | tak |
| `hiphi/hiphi-z/` | model | 3 | 135 | 4.9 | 2026-09-27 | poza: noindex | tak | tak |
| `dongfeng-fengshen/dongfeng-fengshen-l8-phev/` | model | 6 | 91 | 9.3 | 2026-08-19 | poza: noindex | tak | tak |
| `chery-fulwin/x3-plus/` | model | 5 | 80 | 5.8 | 2026-09-27 | poza: noindex | tak | tak |
| `honda/honda-s7/` | model | 1 | 80 | 6.5 | 2026-08-29 | poza: noindex | nie | tak |
| `jetour/jetour-x90-plus/` | model | 7 | 70 | 3.8 | 2026-08-19 | poza: noindex | tak | tak |
| `mg/mg-6/` | model | 1 | 58 | 8.7 | 2026-08-01 | poza: noindex | tak | tak |
| `changan/changan-uni-t/` | model | 4 | 56 | 4.4 | 2026-09-17 | poza: noindex | tak | tak |
| `exeed/exeed-yaoguang/` | model | 8 | 33 | 4.3 | 2026-09-28 | poza: noindex | tak | tak |

Uwagi do listy:
- `hiphi/` i `hiphi/hiphi-z` — marka upadła w 2024, ofert nie było w historii bazy (0 relacji). Ruch jest informacyjny („HiPhi Z cena”), hub nie ma czego sprzedać. **Do decyzji**, nie automatycznie.
- `mazda/cx-5` — bez specyfikacji i bez opisu; ruch jest, ale strona jest pusta. Przywracać dopiero z treścią.
- Tytuły tych hubów nadal mówią „od X PLN, 1 sztuka” przy 0 ofert (odnotowane w `recheck-2026-10-04.md` §5.3). **Przywrócenie indeksu bez poprawy tytułu wystawi w SERP auto, którego nie ma.**

## Grupa B — duplikaty żywego wariantu (14)

Ruch powinien trafiać do wariantu z ofertami, a nie wracać do indeksu jako osobny hub. To robota na taksonomii (301 albo merge jak w T-019), nie na filtrze robots.

| hub | typ | kl. 90 d | wyśw. 90 d | poz. | ost. wyśw. | indeks (URL Inspection) | spec | opis | wariant z ofertami |
|---|---|---|---|---|---|---|---|---|---|
| `geely/a7-phev/` | model | 92 | 1040 | 3.9 | 2026-08-28 | poza: noindex | tak | tak | Galaxy A7 EM-i (3) |
| `changan/cs55-plus-phev/` | model | 3 | 133 | 5.7 | 2026-10-01 | poza: noindex | tak | tak | CS55 Plus (1) |
| `changan/uni-z/` | model | 5 | 117 | 5.1 | 2026-10-04 | w indeksie (wypadnie przy crawlu) | tak | tak | UNI-Z PHEV (3) |
| `chery/tiggo-9-tiggo-8l/` | model | 0 | 74 | 9.5 | 2026-10-04 | w indeksie (wypadnie przy crawlu) | tak | tak | Tiggo 9 (18) |
| `tank/tank-500/` | model | 6 | 73 | 4.3 | 2026-09-27 | w indeksie (wypadnie przy crawlu) | tak | tak | Tank 500 Hi4-T (19) |
| `chery/omoda/` | model | 3 | 69 | 5.7 | 2026-08-31 | poza: noindex | tak | tak | nazwa marki, nie model (widmo z lipca) |
| `byd/sealion-7-dm/` | model | 0 | 66 | 6.3 | 2026-08-23 | poza: noindex | tak | tak | Sealion 7 (4) |
| `icar/03t/` | model | 1 | 53 | 8.4 | 2026-10-04 | w indeksie (wypadnie przy crawlu) | tak | tak | 03 (14) |
| `byd/yangwang-u7-ev/` | model | 0 | 44 | 7.2 | 2026-08-12 | poza: noindex | tak | tak | Yangwang U7 (4) |
| `chery/tiggo-8-plus-c-dm/` | model | 0 | 22 | 10.8 | 2026-08-24 | poza: noindex | tak | tak | Tiggo 8 PLUS (3) |
| `changan/uni-v-idd/` | model | 0 | 11 | 5.5 | 2026-07-15 | poza: noindex | tak | tak | UNI-V (18) |
| `chery/explorer-06/` | model | 0 | 2 | 7 | 2026-08-10 | poza: noindex | tak | tak | Explorer 06 C-DM (1) |
| `chery/tiggo-7/` | model | 0 | 0 | 0 | 2026-06-13 | poza: noindex | nie | tak | Tiggo 7 C-DM (1) |
| `chery/tiggo-8-pro-phev/` | model | 0 | 0 | 0 | 2026-06-30 | poza: noindex | tak | tak | Tiggo 8 Pro (2) |

Dopasowanie ręczne na liście żywych serii danej marki — do potwierdzenia przy wdrożeniu (np. `icar/03t` vs `03` może być osobną wersją nadwozia).

## Grupa C — mały ruch (31), grupa D — brak ruchu w 90 dni (42)

C (<50 wyśw. i <5 kl. w 90 dni; w nawiasie wyśw.): 
`aito/m6/` (41), `byd/seagull/` (33), `byd/byd-e7/` (33), `lynk-co/06-em-p/` (32), `foton/dajiangjun-ev-pickup/` (31), `dongfeng/forting-u-tour-v9/` (31), `gac/hyptec-a800/` (28), `chery-fulwin/a9l/` (27), `tank/tank-500-hi4-z/` (25), `geely/starship-6/` (20), `gac/empow/` (19), `byd/song-ultra-ev/` (19), `leapmotor/leapmotor-c01/` (18), `byd/dolphin/` (18), `geely/ex2/` (18), `mazda/mazda-mx-5/` (16), `byd/haishi-06-ev/` (14), `geely/haoyue-l/` (14), `im-motors/im-ls7/` (14), `gac/e8-phev/` (13), `lynk-co/lynk-co-06/` (13), `leapmotor/leapmotor-lafa5/` (13), `iveco/` (11), `gac/e8/` (10), `gac/es9-phev/` (8), `chery-fulwin/a8l/` (7), `haval/haval-big-dog-plus/` (7), `wey/05-phev/` (6), `changan/x7-plus/` (6), `gac/trumpchi-xiangwang-m8/` (5), `xingchi/bochi-venus/` (5)

D: 42 huby z historią sprzed lipca i 0–1 wyśw. w 90 dni (m.in. MINI, Polestar, Auxun, XPeng P5/P7, Emgrand). Noindex jest dla nich właściwy.

## Poboczne (zauważone, nie ruszam)

- 76 URL-i hubów z historią GSC nie ma już termu: 63 zwraca 301, **12 zwraca 404** (np. stare warianty Hyper/Trumpchi, `/samochody/nevo/`). Lista w scratchpadzie sesji, do osobnej oceny.
- `HUB_INDEX_BLOCKED_MAKES` w kodzie = `volkswagen, mini, iveco`; flaga `_asiaauto_info_only` siedzi na VW/Audi/Volvo/BMW. Dwa źródła prawdy o „marce wyłączonej”.

## Kierunek na krok 2 (do akceptu, nic nie wdrożone)

1. **Trwały znacznik „hub miał ofertę”** — term meta (np. `_asiaauto_had_offers`) ustawiane przy publikacji oferty z tym termem; jednorazowy backfill z grupy A, bo bazy nie da się odtworzyć. W `filterRankMathRobots()` wymuszenie `index` dla `serie` **i** `make`, gdy: znacznik + specyfikacja + brak flagi `_asiaauto_info_only` + brak przypisania „duplikat”. Globalne `noindex_empty_taxonomies` zostaje — dalej trzyma 2 400 widm.
2. **Tytuł przy count=0** — wariant bez ceny i „sztuk” (wzorem marek informacyjnych) przed odblokowaniem.
3. **Duplikaty (grupa B)** — osobny task: 301 do wariantu z ofertami.
4. **Google:** 22 URL-e przez `~/bin/index-submit` (mieści się w dziennym budżecie ad-hoc 100); sitemapa bez zmian (N+1, osobny temat).
5. Dry-run: lista termów, które filtr zmieni, porównana z tą tabelą przed wdrożeniem.

Do rozstrzygnięcia przez Janka: lista marek trzymanych w noindex (czy jedynym źródłem ma być `_asiaauto_info_only`), HiPhi tak/nie, próg „wartościowy” (tu: 50 wyśw./5 kl. w 90 dni).

## Decyzja 07.10 — whitelista rozszerzona (wdrożone)

Janek: do `asiaauto_hub_index_whitelist` idą „pewne” (8) + „wydaje się” (6) + HiPhi Z („były oferty, klasyk motoryzacji”).
Opcja: 5 → 20 term_id. Backup przed zmianą: `~/backups/primaauto/2026-10-07/asiaauto_hub_index_whitelist.json`.

- pewne: 4326 NIO ET9, 3375 GAC GS8, 4398 Haval H6, 6233 Fulwin T11, 6234 Fulwin X3L, 5184 Fulwin T9, 6601 Lynk 10 EM-P, 3377 GAC M6
- wydaje się: 3327 Corolla Cross, 6501 Sealion 6 EV, 4527 Jetour X90 PLUS, 4795 Fengshen L8 PHEV, 6519 Fulwin X3 PLUS, 4037 Changan UNI-T
- HiPhi Z: 5461

Weryfikacja: `curl` meta robots → wszystkie 15 `follow, index`; kontrola `byd/seal-5-dm` (poza listą) → `noindex`.

HiPhi — wolumen PL (DataForSEO, 07.10): `hiphi z` 720/mies., `hiphi z cena` 170, `hiphi x` 140, `hiphi samochód` 50, `hiphi y` 30.
GSC (16 mies.): fraza `hiphi z` ląduje na hubie **marki** `/samochody/hiphi/` (329 wyśw., poz. 6,7), a nie na hubie modelu (5 wyśw.). Hub marki whitelista nie obejmuje (tylko `serie`) — zostaje `noindex`.

Otwarte: tytuły 15 hubów „od X PLN, 1 sztuka” przy 0 ofert; zgłoszenie przez `index-submit` czeka.

## Tytuły 15 hubów z whitelisty — przedział cen (wdrożone 07.10)

Decyzja Janka: bez liczników sztuk, fraza „cena w Polsce” zostaje, przedział „ceny wahają się X–Y” bez odniesienia czasowego.
Wzór: `{Marka Model} cena w Polsce: {X}–{Y} tys. zł | Prima-Auto` + opis `Ceny {Model} w Polsce wahają się od X do Y zł. Import z Chin pod klucz: …`.
Przedziały wyłącznie z naszych historycznych cen ofert (stary opis + FAQ), bez cen salonowych i porównań (Tesla, salon Toyoty).
Lynk & Co 10 EM-P ma jedną cenę → „ok. 185 tys. zł”.
Meta: `rank_math_title`, `rank_math_description`, `asiaauto_seo_desc`, `_asiaauto_skip_title_regen=1`, znacznik `_asiaauto_title_note=t260-zakres-cen-2026-10-07`.
Backup: `~/backups/primaauto/2026-10-07/termmeta-t260-tytuly.tsv`. Skrypt jednorazowy (nie w repo): wzór powyżej.

Uwaga: `skip_title_regen` zamraża tytuł także po powrocie ofert — przy powrocie towaru zdjąć znacznik (lista po `_asiaauto_title_note`).

## Mechanizm stały — v0.44.2 (wdrożone 07.10)

Zamiast samej whitelisty: znacznik „hub miał ofertę” zapisywany przez generator tytułów, filtr robots trzyma
w indeksie hub wyprzedany (znacznik + spec + marka nieinformacyjna + nie duplikat), tytuł przełącza się
automatycznie na „cena w Polsce: X–Y tys. zł”. Szczegóły i weryfikacja: `docs/VERSIONS.md` → 0.44.2.
Ręczne `skip_title_regen` z 15 hubów zdjęte (generator daje identyczne tytuły). Whitelist wrócił do
5 premierowych + Sealion 6 EV (bez specyfikacji — reguła by go nie wpuściła).
Duplikaty (13) oznaczone `_asiaauto_hub_duplicate_of` — 301 do wariantu z ofertami to osobny task.

Odrzucone: wyłączenie `noindex_empty_taxonomies` w RankMath (wpuszcza VW/Audi wbrew pismu, 228 pustych marek,
widma z lipca) oraz kasowanie pustych termów (strefa krucha, `syncAll` odtwarza widma).

Otwarte: zgłoszenie 15 URL-i przez `index-submit` — 07.10 pula GCP wyczerpana (429), ponowić po 9:00.

### Poprawka 07.10 — Audi wraca do indeksu

Błąd wdrożenia: filtr blokował indeks wszystkim markom z `_asiaauto_info_only` (VW/Audi/Volvo/BMW), choć w
`docs/QUEUE.md` (T-259) zapisano, że `noindex` Audi „NIE jest decyzją — to automat RankMath”. Teraz blokuje
wyłącznie lista `asiaauto_hub_index_blocked_makes` (VW/MINI/Iveco, pismo VW). Audi, E5 Sportback, E7X → `index`,
tytuły informacyjne bez zmian. Volvo/BMW: bez znacznika, nadal `noindex` — do decyzji Janka.
