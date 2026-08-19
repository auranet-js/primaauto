# ADR 2026-08-19 — re-check scrapingu (miesiąc po blokadach): status quo

> Realizacja przypomnienia `docs/przypomnienia/2026-08-12-recheck-scraping.md` (termin 12.08, wykonane 19.08).
> Poprzedni stan: `docs/decyzje/2026-07-12-anty-scraping-htaccess.md`.
> Materiał: 8 dób logów `primaauto.com.pl.log` (11–19.08), rozpakowane do osobnych katalogów.

## Werdykt per punkt

### 1. Czy blokady działają — TAK, 100%

| doba | żądań UA `compatible; crawler` | z tego 403 | IP `84.40.222.42` |
|---|---|---|---|
| 11.08 | 175 | 175 | 0 |
| 12.08 | 187 | 187 | 0 |
| 13.08 | 216 | 216 | 0 |
| 14.08 | 184 | 184 | 0 |
| 15.08 | 418 | 418 | 0 |
| 16.08 | 175 | 175 | 0 |
| 17.08 | **3 650** | 3 650 | 0 |
| 18.08 | **5 044** | 5 044 | 0 |

Żadne żądanie nie przeszło. Tablet z Płocka: **zero żądań przez 8 dób** z zablokowanego IP.

**Skok 17–18.08 jest skorelowany z powrotem zaciągu** (146 i 105 nowych ofert w te doby, po tygodniu zastoju che168). Przeciwnik monitoruje katalog i uderza po nowe zdjęcia — 18.08 to 5 044 żądań z **4 891 unikalnych IP** (1 req/IP), cel: miniaturki `190x132`. Sygnatura identyczna jak w lipcu: rotujące proxy, zdjęcia, brak assetów.

### 2. Czy Płock wrócił z innego IP — BRAK DOWODU

Sprawdzone wg wzorca zachowania, nie IP. Kandydaci (>300 odsłon HTML/dobę, po odsianiu Googlebota `66.249.74.x`):

| IP | sieć | req | unikalnych ofert | wzorzec |
|---|---|---|---|---|
| 79.163.143.25 | Orange FTTH | 2 123 | 565 | 3 h, 1 przerwa >5 min |
| 88.156.186.77 | Vectra Wejherowo | 3 513 | 592 | sesje wieczorne, 2 doby |
| 104.28.80.63 | Cloudflare (WARP/Private Relay) | 2 413 | 49 | 2 h, **zero przerw >5 min** |
| 194.26.201.11 | — | 2 756 zdjęć | 580 | — |

Sam brak przerw nie wystarczył jako dyskryminator. Wszystkie te IP: **ładują banner Complianz i jego JS**, pobierają CSS/JS/fonty **raz** (cache przeglądarki), mają **wewnętrzne referery** (`/samochody/?marka=byd&strona=5`). To profil przeglądarki z włączonym JS, czyli człowieka — nie WebView-scrapera bez GA, jakim był tablet z Płocka (73% katalogu, DuckDuckGo). Najwięcej zebrało jedno IP: 592 z 2 553 ofert publish = 23%.

### 3. Czy operacja proxy zmieniła UA — NIE, warstwa HTML wciąż na frozen Chrome/142

| doba | req frozen UA `Macintosh … Chrome/142` | unikalnych IP |
|---|---|---|
| 11.08 | 106 | 25 |
| 12–15.08 | 14–47 | tyle samo co req |
| 16.08 | 88 | 88 |
| 17.08 | 67 | 67 |
| 18.08 | 72 | 72 |

1 request na IP, **zero assetów** (1 na 72) — Puppeteer bez cache. Wolumen spadł z 350–880/dobę (lipiec) do 14–106/dobę. Wersja UA **nie została podbita**. Sprawdzone też, czy operacja nie przeniosła się na nowszy frozen UA: `Chrome/151` na Macintoshu to 55 IP i 1 344 req z pełnym pobieraniem CSS/JS/fontów — realny ruch, nie operacja.

### 4. Cele monitoringu — ROZSZERZONE

Warstwa HTML proxy przez 8 dób odwiedza (poza `/oferta/*` — 35 razy):
`zeekr/9x` (13), `avatr` (11), `byd/sealion-8-dm-i` (10), `byd` (10), `li-auto` (9), `byd/shark-6` (9), `byd/leopard-7` (9), `byd/leopard-5` (9), `byd/atto-3` (9), `zeekr`, `xiaomi/su7`, `zeekr/7x`, `hongqi`, `geely`, `baic/bj40`, `zeekr/8x`, `lynk-co/900`.

Voyah Free i Chery Tiggo 8 z lipca **zniknęły z czołówki** — cele przesunęły się na BYD (5 hubów), Zeekr (4 huby) i nowości (Lynk & Co 900, Avatr). To śledzenie naszej oferty, nie stały zestaw modeli.

### 5. Czy treści wypłynęły — NIE

WebSearch po dwóch unikalnych frazach z `asiaauto_wiki_body` (Toyota Sienna, Toyota hub): zero trafień poza nami, w tym zero na west-motors.pl.

## Decyzja: (a) status quo

Blokady z 12.07 trzymają w 100% i nie wymagają korekty. Warstwa HTML proxy zostaje świadomie niezablokowana — frozen UA Chrome/142 jest zbyt podobny do realnych przeglądarek, a jej wolumen spadł ~10×. Watermark treści: nadal odłożony.

**Próg eskalacji na przyszłość:** warstwa HTML powyżej ~300 req/dobę albo pojawienie się operacji zbierającej >50% katalogu w jednej dobie z profilu bez JS.

**Do obserwacji:** korelacja skoku blokowanych żądań z zaciągiem — przeciwnik reaguje na nowe oferty w ciągu doby. Przy kolejnym rechecku porównać, czy 403 wracają do bazowych ~180/dobę po ustabilizowaniu zaciągu.
