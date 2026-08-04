# 2026-08-03 — Filtry che168: naprawa nazw, zaciąg ratunkowy hubów, audyt nazw pod DFS

> Sesja autonomiczna (Janek wyjechał, zgoda na przebieg do raportu końcowego).
> Punkt wyjścia: pytanie o markę Roewe → przegląd luk marek/modeli → naprawa filtrów.

---

## 1. Roewe — decyzja: NIE uruchamiamy

Trzy niezależne pomiary dały ten sam wynik:

- **Podaż:** 400 ofert w magazynie che168, z tego **0–1** przechodzi filtry Ruslana. Odsiew: 346 na roczniku <2024, 47 na cenie <85k ¥. Roewe to w Chinach segment tani i stary (`i5` 112 ofert po 15,8–57,9k ¥, `RX5` 99 ofert po 18,5–74k ¥). Jedyne w segmencie: `iMAX8 New Energy` (146–153k ¥, 3 szt.).
- **Roewe M7 DMH:** 1 sztuka, 2025, 79 000 ¥ — tuż pod progiem 85k ¥. Fraza „roewe m7" **poniżej progu pomiaru** w Google Ads.
- **Popyt PL:** „roewe" 590/mc (ostatni mies. 260) wobec voyah 2900, zeekr 9900, denza 18100. Google Trends 12 mies.: średnia 5,2 przy denza 23,0 i leapmotor 37,2, ostatnie punkty `3, 18, 0, 0` — szum.
- **GSC:** 0 zapytań z „roewe" na primaauto.com.pl przez 90 dni.
- **Portale contentowe:** CarNewsChina tag `roewe` — ostatni artykuł **06/2025**, ponad rok ciszy (tag `mg` żyje, 04/2026). W bieżących feedach (110 pozycji) 0 wzmianek.

Taksonomia Roewe (`make/roewe` + 31 serii, `count=0`) zostaje pusta. `/marka/roewe/` odpowiada 200, ale nie ma go w sitemapie.

---

## 2. Prawdziwe znalezisko: nazwy marek w filtrach che168 nie łapały

Filtr `isAllowedByConfig()` porównuje `in_array(..., true)` — **ściśle, z wielkością liter** — wartość `mark` **po normalizacji adaptera**, nie surową nazwę z API. Rozjazd między jedną a drugą formą = marka wypada cicho, w kubełku „odsiane filtrami / poza segmentem". Monitor tego nie pokazuje jako błędu, bo `getOffers()` po nazwie z configu zwraca oferty (API jest case-insensitive) — dopiero filtr je zjada.

**Pięć martwych wpisów** (marki w bazie, sync ich nigdy nie brał):

| wpis w filtrach | adapter zwraca | auta w bazie |
|---|---|---|
| `WEY` | `Wey` | 9 |
| `Smart` | `smart` | 5 |
| `IM Motors` | `IM` | 9 |
| `Jetour Shanhai` | `Shanhai` | 3 |
| `Lotus` | `Lotus Cars` | 4 |

**Pułapka druga (kosztowała jedną iterację):** adapter zwraca **oba warianty nazwy, zależnie od konkretnej oferty**, nie od marki. Pomiar na 4 stronach magazynu:

```
Shanhai  -> 'Jetour Shanhai' ×75  |  'Shanhai' ×5
IM       -> 'IM Motors'      ×66  |  'IM'      ×14
Wey      -> 'Wey'            ×70  |  'WEY'     ×10
smart    -> 'smart'          ×64  |  'Smart'   ×16
Lotus    -> 'Lotus Cars'     ×51  |  'Lotus'   ×29
Hyper    -> 'GAC Aion Hyper' ×73  |  'Hyper'   ×2
eπ       -> 'Dongfeng Yipai' ×58  |  'eπ'      ×22
奥迪AUDI -> '奥迪AUDI'       ×39  |  'Audi'    ×2
```

Dlatego poprawka to **dopisanie obu form**, nie podmiana. Pierwsze podejście (podmiana) urwało ścieżkę `Jetour Shanhai` i dało 0 ofert — złapane w dry-runie przed `--apply`.

**Sprostowanie wcześniejszych ustaleń:** `Fangchengbao` i `Dongfeng Yipai` **nie były martwe** — źródłowe `Fang Cheng Bao` i `eπ` normalizują się do form z configu. Notatka „Fangchengbao: zero podaży pod tą nazwą" opisywała artefakt sondy (`getOffers` nazwą z configu), nie stan faktyczny. To samo dotyczy `AITO` (źródło `AITO 问界`) i `Voyah` (źródło `VOYAH`).

### Zmiana w configu

`asiaauto_import_config['che168']['marks']`: **45 → 61 nazw**.

- Dopisane warianty istniejących: `Wey`, `smart`, `IM`, `Shanhai`, `Lotus Cars`, `Hyper`, `Audi`, `eπ`, `Maxus`
- Dopięte marki nowe: `Galaxy`, `Yangwang`, `GAC Aion Hyper`, `Beijing Off-Road`, `Mengshi`, `奥迪AUDI`, `大通`
- Zachowane stare formy: `WEY`, `Smart`, `IM Motors`, `Jetour Shanhai`, `Lotus`

**Weryfikacja: wszystkie 61 wpisów przetestowane empirycznie** (`getOffers` → `normalize` → `in_array` strict). Cztery wpisy zwracają 0 ofert z API i to jest poprawne — to formy **po** normalizacji (`AITO`, `Fangchengbao`, `Dongfeng Yipai`, `GAC Aion Hyper`), do których API nie ma nazwy źródłowej.

### Skutek uboczny dla backfillu

`scripts/che168-domknij-luke.php` woła `getOffers(mark)` nazwą **z configu**, więc dla tych czterech marek dostaje pustkę i są dla niego niewidzialne. Nowy skrypt `scripts/che168-zaciag-per-hub.php` podaje nazwy źródłowe wprost.

---

## 3. Dlaczego Galaxy i Yangwang wygasały

Che168 trzyma je jako **osobne marki źródłowe**, których nie było w filtrach. Mapa i tak sprowadza je pod nasze marki:

```
Galaxy|Galaxy L7             -> Geely / Galaxy L7
Galaxy|银河A7 PHEV           -> Geely / Galaxy A7 PHEV
Yangwang|Yangwang U7 PHEV    -> BYD   / Yangwang U7
Yangwang|Yangwang U8         -> BYD   / Yangwang U8
```

Czyli dopięcie do filtrów **nie tworzy nowej marki** — auta lądują na istniejących hubach.

Cały dotychczasowy zapas przyszedł z **dongchedi**: Galaxy 48 aut (ostatnie 30.06), Yangwang 6 (ostatnie 25.06). Dongchedi zamrożony od 01.07, wygaszany do 15.08 (T-222) — bez tej poprawki huby dojechałyby do zera. A rankują: `geely galaxy` 366 imp / 12 klików, `geely galaxy a7` 184/18 (poz. 4,5), `geely galaxy l6` 157/13, `geely galaxy l7` 113/12, `yangwang u7` 135/4.

---

## 4. Zaciąg ratunkowy — `scripts/che168-zaciag-per-hub.php`

Nowy skrypt: kilka sztuk **na hub** (limit po `serie_eu`), dla wskazanych marek źródłowych. Ścieżka importu identyczna z syncem: `normalize()` → `isAllowedByConfig()` → guard mapowania → `getOffer()` → `importListing()`.

**Guard: import wyłącznie do hubów, które już istnieją.** Bez niego mapa założyłaby drugi hub na ten sam model:

| mapa wskazuje | w bazie już jest | efekt bez guarda |
|---|---|---|
| `LS8` | `IM LS8` | dwa huby IM LS8 |
| `#1`, `#3` | `smart #1`, `smart #3` | dwa huby smart |
| `Shanhai L7 PLUS` | `Jetour Shanhai L7 PLUS` | dwa huby |

Dodatkowo wyłączone huby **sporne**, gdzie rozdwojenie już istnieje i dosypanie aut by je pogłębiło: `LS9` (obok `IM LS9`) i `#5` (obok `smart #5` i `Smart #5 EHD Super Hybrid`).

### Wynik: 58 aut, 0 błędów, 24 huby

```
przejrzane:             991
przeszły filtr:         146
orphany (brak mapy):     30
już w bazie:              3
pominięte (limit/hub):   41
pominięte (nowy hub):    14
ZAIMPORTOWANE:           58 (błędy: 0)
```

| hub | stan po | z dzisiaj | | hub | stan po | z dzisiaj |
|---|---:|---:|---|---|---:|---:|
| Geely Galaxy Starship 8 PHEV | 15 | 3 | | BAIC BJ40 | 5 | 3 |
| Jetour T2 C-DM | 13 | 3 | | Jetour Shanhai L9 | 5 | 3 |
| WEY 07 | 8 | 3 | | Lotus Emeya | 5 | 3 |
| GAC Hyptec HT | 7 | 3 | | BYD Yangwang U8 | 5 | 3 |
| BYD Yangwang U7 | 7 | 3 | | Geely Galaxy A7 PHEV | 4 | 3 |
| WEY Gaoshan | 7 | 3 | | GAC Hyptec GT | 4 | 3 |
| IM Motors IM LS6 | 6 | 3 | | IM Motors IM L6 | 4 | 3 |
| Jetour Shanhai T1 | 6 | 3 | | Geely Galaxy E5 | 7 | 2 |
| GAC Hyptec HL | 4 | 2 | | Jetour Shanhai L7 | 4 | 2 |
| Lotus Eletre | 3 | 2 | | Dongfeng M-Hero 917 | 5 | 1 |
| Geely Galaxy L7 | 5 | 1 | | BAIC BJ40 EREV | 3 | 1 |
| Audi E7X | 2 | 1 | | GAC Hyptec SSR | 2 | 1 |

Równolegle działający sync dorzucił 4 auta poza tym zaciągiem (Xiaomi SU7, Zeekr 007 GT, Li Auto i6, Haval Menglong Hi4) — łącznie 62 nowe oferty dziś.

### Kontrola jakości zaimportowanych ofert

Zmierzone na wszystkich 62 nowych ofertach (ID > 402739):

| kontrola | wynik |
|---|---|
| escape unicode (`u9a6cu529b`) | **0** |
| CJK w tytułach | **0** |
| brak zdjęcia wiodącego | **0** |
| brak specyfikacji (`_asiaauto_extra_prep`) | **0** |
| brak opisu (`post_content`) | **8** — źródło nie oddało treści, do dolania |
| rozjazd napęd/hub (`detektor-rozjazdu-napedu.php`) | **0 z dzisiejszego importu** (4 istniejące pochodzą sprzed, ID < 402739) |
| kontaminacja marka↔hub | **0 nowych** (2 istniejące — patrz sekcja 6) |

Tytuły i przypisania do marek/serii sprawdzone ręcznie — poprawne.

### Indexing API

Oferty poszły automatycznie (plugin zgłasza każdy import). **26 hubów** zgłoszonych ad-hoc przez `~/bin/index-submit` — wszystkie 24 odświeżone plus `foton/tunland-v9` i `luxeed/s7` (problemy indeksacji bez nowych aut). Wszystkie zweryfikowane kodem 200 przed wysyłką.

Zużycie puli dziś: **148 / 200** (plugin 122 przez auto-indexing ofert + 26 ad-hoc). Ad-hoc: 26/100.

**Uwaga na przyszłość:** plugin zgłasza jedną ofertę = jedno wywołanie, więc każdy większy zaciąg zjada pulę **wspólną dla wszystkich projektów**. Ten import sam w sobie kosztował 58 zgłoszeń. Przy kolejnych partiach warto sprawdzić `grep -c '^\[<data>.*\[Indexing\] OK' logs/asiaauto-sync.log` **przed** startem.

---

## 5. Audyt nazw pod DFS

Metoda: 310 zasiedlonych hubów × wolumen Google Ads (PL) + impresje GSC 90 dni + fraza wejściowa z GSC. **GSC jest prawdą, DFS wskazuje popyt, którego nie łapiemy.**

### A. Popyt jest, ruchu nie ma

Próg: wolumen ≥200/mc przy <40 impresji w 90 dni. Zachodnie nameplate'y (Qashqai 165 tys./mc, X-Trail, Coolray, Teana, EVOS, Talagon) pominięte — mamy po 1–4 sztuki i nie ma z czym startować.

| marka | hub | aut | wol./mc | imp 90d | kliki | komentarz |
|---|---|---:|---:|---:|---:|---|
| Ford | Ford Bronco | 3 | 27 100 | 34 | 0 | wolumen dotyczy amerykańskiego Bronco — intencja nie nasza |
| **Hongqi** | **H5** | **66** | **4 400** | **32** | **0** | największy zapas w całej bazie, zerowy ruch, hub zindeksowany (crawl 03.08) |
| Lotus | Eletre | 1 | 2 400 | 2 | 0 | zindeksowany |
| **GAC** | **Hyptec HT** | 4 | **1 300** | **0** | 0 | **noindex przy crawlu 01.07** — patrz sekcja C |
| XPeng | GX | 3 | 720 | 27 | 4 | zindeksowany |
| GAC | Hyptec HL | 2 | 720 | 20 | 0 | zindeksowany |
| Foton | Tunland V9 | 1 | 720 | 0 | 0 | wykryta, niezindeksowana |
| Deepal | S07 | 2 | 590 | 19 | 2 | zindeksowany |
| Lotus | Emeya | 2 | 480 | 29 | 0 | zindeksowany |
| **Geely** | **Preface** | **60** | 320 | 32 | 3 | drugi największy zapas, ruch znikomy |
| NIO | ET5 Touring | 31 | 260 | 35 | 0 | 31 aut, 35 impresji |
| Luxeed | S7 | 11 | 260 | 39 | 2 | **zeskanowana, niezindeksowana** |
| Deepal | SL03 | 7 | 210 | 12 | 0 | zindeksowany |

Trzy pozycje wołają najgłośniej — **Hongqi H5 (66 aut), Geely Preface (60), NIO ET5 Touring (31)**. To razem 157 aut, czyli 7% publikowanego stanu, generujące łącznie 99 impresji w kwartał. Wszystkie trzy huby są zindeksowane i świeżo crawlowane, więc to nie kwestia indeksacji — to treść i konkurencyjność strony.

### B. Rozdwojone huby — nazwa ogólna vs wariant

Detektor par „nazwa A jest prefiksem nazwy B w tej samej marce" dał **24 pary**. Najostrzejsze:

| marka | hub ogólny | aut | wol./mc | imp | wariant | aut | imp |
|---|---|---:|---:|---:|---|---:|---:|
| Jetour | T2 | 14 | 40 500 | 181 | T2 C-DM | 10 | 3 894 |
| BYD | Seal | 4 | 33 100 | 123 | 8 wariantów | 49 | — |
| Chery | Tiggo 8 | 1 | 22 200 | 101 | Tiggo 8 Pro/PLUS | 12 | 151 |
| Chery | Tiggo 9 | 8 | 18 100 | 93 | Tiggo 9 C-DM | 3 | 49 |
| Jetour | X70 | 2 | 4 400 | 67 | X70 PLUS / C-DM | 8 | 363 |
| Hongqi | H5 | 66 | 4 400 | 32 | H5 PHEV | 1 | 3 |

Wzorzec: hub o nazwie ogólnej (tej, którą ludzie wpisują) ma mało aut i mało ruchu, a ruch zbiera wariant o nazwie technicznej. **Jetour T2 to najostrzejszy przypadek** — 40 500 wyszukań/mc, Trends 50,1 (dla porównania nasz najlepszy hub `zeekr 9x` ma 22,1 i rośnie), a hub `/jetour/t2/` z 14 autami ma 181 impresji na poz. 9,7.

### C. Indeksacja — rozdzielenie przyczyn

| hub | stan | wniosek |
|---|---|---|
| `gac/hyptec-ht` | **wykluczony noindex**, crawl 01.07 | hub był pusty przy crawlu (auta przyszły po renamie 28.07); noindex już zdjęty, czeka na re-crawl |
| `gac/hyptec-gt` | Google nieznany | do zgłoszenia |
| `gac/hyptec-ssr`, `foton/tunland-v9` | wykryta, niezindeksowana | do zgłoszenia |
| `luxeed/s7` | zeskanowana, niezindeksowana | do zgłoszenia |
| `hongqi/h5`, `geely/preface`, `nio/et5-touring`, `jetour/t2`, `byd/seal`, `chery/tiggo-8`, `chery/tiggo-9` | **zindeksowane** | ich słaby ruch to problem **treści/rankingu**, nie indeksacji |

---

## 6. Do decyzji (nie ruszane)

### Sieroty — przechodzą filtry, brak wpisu w mapie

| klucz źródłowy | szt | uwaga |
|---|---:|---|
| `Lotus\|Emira` | 11 | **8 100 wyszukań/mc w PL** — największa niewykorzystana podaż |
| `Mengshi\|猛士M817` | 8 | `m-hero 917` 480/mc, nasza nazwa M-Hero poprawna (`mengshi 917` tylko 10/mc) |
| `ONVO\|ONVO L60` + `乐道L90` + `乐道L80` | 14 | popyt niski (`onvo l60` 40/mc) |
| `奥迪AUDI\|奥迪E5 Sportback` | 5 | **hub `E5 Sportback` istnieje (3 auta), 880 wyszukań/mc** — brakuje tylko mapowania |
| `Yangwang\|仰望U8 L` | 2 | wariant CN tego samego U8 |
| `Hyper\|昊铂A800` | 2 | hub `Hyptec A800` istnieje |
| `Galaxy\|银河M9` | 1 | hub `Galaxy M9` istnieje |
| `Aion\|AION RT` + `AION V` | 2 | osobna linia, nie mamy jej wcale |
| `Lotus\|FOR ME` | 1 | hub `ForMe` istnieje |

Pięć z tych pozycji (`奥迪E5 Sportback`, `昊铂A800`, `银河M9`, `仰望U8 L`, `FOR ME`) to **warianty nazw aut, których huby już mamy** — dopisanie mapowania wpuszcza je bez tworzenia czegokolwiek nowego. Uwaga: alias w `che168-model-map.php` jest martwy bez pary `mark_eu|serie_eu` w `brand-mapping-v6.1.php`.

### Kontaminacja hubów — 2 oferty na cudzych termach

| post | tytuł | siedzi na termie | term należy do |
|---|---|---|---|
| 387316 (05.07) | Galaxy M9 2025 210km 4WD Pilot Edition | `M9` (5304) | **aito** |
| 390248 (25.07) | Geely Galaxy L7 2024 1.5T 55km PRO | `L7` (5739) | **li-auto** |

Ta sama klasa co M8/N7/M6 z T-019. Oba sprzed dzisiejszego importu. Naprawa: przepiąć `term_relationships` na `Galaxy M9` (6550) i `Galaxy L7`, potem `wp term recount`.

### Duplikaty termów do rozstrzygnięcia

- `LS9` (1 auto) obok `IM LS9` (2) — wolumen: `im ls9` 90/mc
- `#5` (1) obok `smart #5` (0) i `Smart #5 EHD Super Hybrid` (1) — wolumen: `smart 5` 720/mc
- `LS8` — mapa produkuje `LS8`, w bazie `IM LS8`

### Nazwy potwierdzone jako dobre (nie ruszać)

- **Hyptec** — `gac hyptec ht` 1300 + `hyptec ht` 1000 vs `aion hyper ht` **10**. Decyzja z 28.07 potwierdzona.
- **M-Hero** — `m-hero 917` 480 vs `mengshi 917` 10.
- **Galaxy Starship 8** — `geely galaxy starship 8` 50 vs `geely xingyao 8` 10.
- **IM LS6** — `im ls6` 140 vs `im motors ls6` 20.
- **WEY 07 / Gaoshan** — wszystkie warianty ≤40/mc, nazwa bez znaczenia.

---

## 7. Rollback

- Config przed zmianą: `~/backups/primaauto/2026-08-03/asiaauto_import_config-przed.json`
- Taksonomia + opcje: `~/backups/primaauto/2026-08-03/taksonomia-opcje-przed.sql` (13 MB)
- Marker: wszystkie posty z dzisiejszego zaciągu mają **ID > 402739**
