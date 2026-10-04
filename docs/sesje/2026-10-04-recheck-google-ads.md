# Recheck Google Ads — 2026-10-04

> Tryb: **tylko odczyt.** Na koncie nic nie zmienione (statusy, budżety, stawki, feedy, GTM).
> Konto `9506068500`. Okna: **A14** = 20.09–03.10, **B14** = 06.09–19.09, **A30** = 04.09–03.10, **B30** = 05.08–03.09.
> Konwersje = wyłącznie `click_phone` + `click_whatsapp` + `generate_lead` (sprawdzone per akcja, YouTube
> follow-on views — 31 sztuk w `[DG]` — są w `all_conversions`, **nie** w kolumnie „Konwersje").
> **Aktualizacja 04.10 po rozmowie z Jankiem:** feed `[DSA]` naprawiony i wgrany (99 → 83 żywe oferty), `gclid` przywrócony w GTM (wersja 16). Porady dla `[Topic]` i `[Brand]` wycofane — patrz §5.
> Poprzedni recheck: 07.09 (`docs/ads/mapa-kampanii.md`), oceny z 14.09 nie zostały zapisane.

## 0. Najważniejsze w pięciu punktach

1. **Feed `[DSA]` stoi od 10.08** — `scripts/dsa-offer-feed-refresh.py` ma `API="v21"` na sztywno (linia 35),
   od tamtej pory każdy bieg crona (co 3 dni) kończy się `HTTP Error 404` — 17 razy w logu
   `~/.claude/dsa-offer-feed.log`. Ten sam mechanizm co awaria RMKT z 07.09, w drugim skrypcie.
   Skutek: **69 z 99 URL-i feedu przekierowuje (301)** — oferty dawno zrotowały, feed reklamuje huby
   zamiast ofert; w feedzie wciąż jest `xiaomi-su7-ultra` mimo wycofania marki 07.09.
   `[DSA]` w A14: **198 zł, 0 kontaktów**; w A30: 454 zł, 2 kontakty (CPA 227 zł).
2. **Dwie z trzech kampanii Search działają na jednej reklamie.** Google 19/20.09 odrzucił
   (`DISAPPROVED`, `GOVERNMENT_DOCUMENTS_AND_OFFICIAL_SERVICES`) starą reklamę `[Brand]` 806602181652
   i nową reklamę `[DSA]` 822835403980 — od 20.09 obie mają 0 wyświetleń. W `[Brand]` serwuje sama
   822849281734 (`APPROVED_LIMITED`), w `[Topic]` sama 811967380201 (`APPROVED_LIMITED`; nowa reklama
   824750658447 z 15.09 stoi `PAUSED`). Kolejne odrzucenie = kampania bez reklam.
3. **`[DG]` straciło najlepszą kreację** — wideo Exeed VX (818269813589) odrzucone ~27.09 za
   `MISLEADING_AD_DESIGN`, ostatnie wyświetlenia 26.09. 07.09 miało CPA 11,42 zł, najlepsze w stawce.
   Mimo to `[DG]` w A14 zrobiło 36 kontaktów za 477 zł (CPA **13 zł**, poprzednio 22 zł) — ciągnie
   Leopard 5 i Lynk & Co 900. **Karuzele nie mają ani jednego wyświetlenia od 20.09** (skutek
   wyłączenia in-feed 07.09 — karuzela nie ma gdzie się emitować w Shorts/In-Stream).
4. **`[Topic]` — liczby, bez rekomendacji** (kampania trzyma 80–99% pozycji rynkowej, nie rozliczamy jej CPA; kontakty zaniżone przez wycinanie `gclid` do 04.10): 531 zł i **1 kontakt**; A30 1 053 zł / 7 kontaktów
   (CPA 150 zł, w B30 94 zł, 31.08 było 77 zł). IS 97%, budżet nie jest ograniczeniem.
5. **GA4 nie widzi `[RMKT]` i `[DG]`** — GTM v15 nadal wycina `gclid`/`wbraid`/`gbraid`
   z `page_location` (odczyt wersji live 04.10). Sesje `google / cpc` w GA4: `[RMKT]` tydz. 36 → 38:
   **321 → 48 → 10**, `[DG]` **251 → 30 → 1**, przy niezmienionych kliknięciach w Ads (~400/tydz. RMKT).
   Ruch ląduje w `youtube.com / referral` (40 kontaktów w 30 dni), `googleads.g.doubleclick.net`
   (204 sesje) i dziesiątkach `*.safeframe.googlesyndication.com`. **Naprawione 04.10:** wycięcie `gclid` było błędem wdrożenia z 07.09 (polecenie dotyczyło tylko `fbclid`), nie decyzją Janka — GTM v16 czyści już tylko `fbclid`.

## 1. Kampanie — liczby

| kampania | budżet/dz | A14 zł | A14 kliki | A14 kontakty | zł/klik | CPA A14 | B14 zł / kontakty | CPA B14 | A30 zł / kontakty | CPA A30 | B30 zł / kontakty | CPA B30 |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| [Brand] | 25 (TIS 90%) | 134 | 451 | 17,0 | 0,30 | **8** | 145 / 22,7 | 6 | 289 / 49,7 | **6** | 270 / 32,8 | 8 |
| [DG] | 35 | 477 | 685 | 36,0 | 0,70 | **13** | 517 / 24,0 | 22 | 1 089 / 61,0 | **18** | 696 / 28,0 | 25 |
| [RMKT] | 17 | 236 | 778 | 12,3 | 0,30 | **19** | 244 / 8,1 | 30 | 515 / 20,5 | **25** | 491 / 10,6 | 46 |
| [Topic] | 35 | 581 | 285 | 2,0 | 2,04 | **290** | 417 / 5,0 | 83 | 1 053 / 7,0 | **150** | 874 / 9,3 | 94 |
| [DSA] | 15 | 198 | 251 | 0,0 | 0,79 | **—** | 223 / 2,0 | 112 | 454 / 2,0 | **227** | 440 / 7,0 | 63 |
| [SKAG-2], [VID] | — | 0 | 0 | 0 | — | — | 0 | — | 0 | — | 1 024 / 4,5 | — |
| **RAZEM** | 127 | **1 626** | 2 450 | **67,3** | | **24** | 1 546 / 61,8 | 25 | 3 401 / 140,2 | **24** | 3 795 / 82,2 | 46 |

Tygodnie (zł / kliki / kontakty; tydzień od 28.09 zawiera niepełną dobę 04.10):

| kampania | 31.08 | 07.09 | 14.09 | 21.09 | 28.09 |
|---|---|---|---|---|---|
| [Brand] | 43 / 186 / 23,2 | 69 / 210 / 5,5 | 73 / 243 / 15,7 | 75 / 254 / 8,0 | 56 / 174 / 9,0 |
| [DG] | 329 / 1480 / 8,9 | 254 / 490 / 9,1 | 249 / 361 / 14,0 | 242 / 321 / 23,0 | 205 / 322 / 10,0 |
| [RMKT] | 120 / 388 / 1,4 | 122 / 384 / 3,0 | 122 / 408 / 8,2 | 120 / 397 / 3,2 | 102 / 330 / 6,0 |
| [Topic] | 172 / 88 / 0,0 | 217 / 100 / 4,5 | 232 / 115 / 1,5 | 265 / 129 / 0,0 | 266 / 126 / 1,0 |
| [DSA] | 104 / 132 / 0,0 | 112 / 145 / 1,0 | 112 / 144 / 1,0 | 113 / 143 / 0,0 | 74 / 94 / 0,0 |

**Konto jako całość poprawiło się:** CPA 46 → 24 zł (30 vs 30), przy podobnym wydatku. Robią to
`[DG]` (od 07.09 po wyłączeniu in-feed i pauzie słabych kreacji) i `[RMKT]` (feed bez WebP od 10.09).
Tracą `[Topic]` i `[DSA]` — razem 779 zł w A14 przy 2 kontaktach, czyli **48% wydatku konta na 3% kontaktów**.

## 2. Reklamy — per kreacja

| kampania | reklama | status | A14 zł / kliki / kontakty | CPA A14 | B14 zł / kontakty | CPA B14 | A30 CPA |
|---|---|---|---|---|---|---|---|
| [Brand] | 822849281734 (nowa z 31.08) | ENABLED, APPROVED_LIMITED | 134 / 451 / 17,0 | 8 | 111 / 17,6 | 6 | 6 |
| [Brand] | 806602181652 (stara) | ENABLED, **DISAPPROVED** od ~20.09 | 0 | — | 34 / 5,1 | 7 | 8 |
| [DG] | wideo Leopard 5 czarny | APPROVED | 323 / 441 / 23,0 | **14** | 201 / 12,2 | 17 | 16 |
| [DG] | wideo Lynk & Co 900 | APPROVED | 66 / 121 / 8,0 | **8** | 63 / 1,8 | 34 | 14 |
| [DG] | wideo Exeed VX | **DISAPPROVED** od ~27.09 (`MISLEADING_AD_DESIGN`) | 49 / 59 / 5,0 | 10 | 138 / 6,1 | 23 | 17 |
| [DG] | wideo BYD Shark 6 | APPROVED | 39 / 64 / 0 | — | 73 / 0 | — | 0 kontaktów za 116 zł |
| [DG] | karuzela „auta na placu" | APPROVED | **0 wyświetleń** | — | 4 / 1,0 | 4 | — |
| [DG] | karuzela „nowa dostawa" | APPROVED | **0 wyświetleń** | — | 3 / 0 | — | — |
| [RMKT] | RDA 823752322983 (nowa z 07.09) | APPROVED | 165 / 541 / 12,1 | **14** | 132 / 6,1 | 22 | 16 |
| [RMKT] | RDA 811557389705 (stara) | APPROVED | 71 / 237 / 0,2 | **356** | 112 / 2,0 | 56 | 95 |
| [Topic] | 811967380201 | ENABLED, APPROVED_LIMITED | 581 / 285 / 2,0 | 290 | 417 / 5,0 | 83 | 150 |
| [Topic] | 824750658447 (z panelu 15.09) | **PAUSED**, APPROVED | — | — | — | — | — |
| [DSA] | 817108048038 | APPROVED | 198 / 251 / 0 | — | 120 / 0 | — | 0 kontaktów za 335 zł |
| [DSA] | 822835403980 (z 31.08) | **DISAPPROVED** od ~20.09 (GOV_DOCS) | 0 | — | 103 / 2,0 | 51 | 60 |

Zapauzowane w `[DG]` od 07.09 (bez zmian): Denza Z9 GT, BYD Leopard 7, Deepal G318, Denza N9.

### Propozycje (nic nie wykonane)

| co | dlaczego, liczbą |
|---|---|
| `[RMKT]` pauza starej RDA 811557389705 | 14 dni: 71 zł, 0,2 kontaktu; nowa w tym samym czasie 12,1 kontaktu za 165 zł. Kampania zostaje z dwiema? — tylko jeśli dołożymy trzecią, inaczej znów pojedynczy punkt awarii (patrz 22.08 DSA) |
| `[DG]` pauza Shark 6 | 116 zł / 30 dni, 0 kontaktów, w obu oknach. Ostatnia kreacja ze starym wzorcem (cena na sztukę) |
| `[DG]` Exeed VX — nowa wersja zamiast odwołania | odrzucenie za projekt reklamy (`MISLEADING_AD_DESIGN`, nie tekst) — prawdopodobnie miniatura/kadr lub nakładka ceny w filmie. Najtańsza kreacja konta; wraca jako nowa reklama z tym samym Shortem i tekstem katalogowym (wzorzec 05.09) albo odwołanie w panelu |
| `[DG]` decyzja o karuzelach | od 20.09 zero wyświetleń — bez in-feed nie mają gdzie się emitować. Albo in-feed wraca (07.09: CPA 79 zł), albo karuzele do pauzy dla porządku (nic nie kosztują) |
| `[DSA]` — ocena po naprawie feedu | feed wgrany 04.10 13:25; ocena CPA dopiero na oknie po tej dacie |

## 3. Zdrowie

### Feed `[RMKT]` — zdrowy
- Cron niedzielny chodzi: 13.09, 20.09, 27.09, 04.10 — wszystkie `OK` w `~/.claude/rmkt-feed-refresh.log`.
- 04.10: 255 wpisów, **0 WebP** (160 JPG z konwersji), **192 APPROVED, 63 bez werdyktu** (wgrane dziś
  o 06:00, w recenzji), **0 DISAPPROVED**. Problem WebP nie wrócił.
- Trend kontaktów potwierdza naprawę z 10.09: tydzień 31.08 1,4 → 14.09 8,2 → 28.09 6,0; CPA 46 → 25 zł (30 vs 30).

### Feed `[DSA]` — ZEPSUTY od 10.08
- `scripts/dsa-offer-feed-refresh.py:35` — `API="v21"` (hardkod). Ostatni udany bieg **10.08 06:15**,
  potem 17 × `HTTP Error 404`. Błąd ląduje tylko w logu, którego nikt nie czyta — `checki-pomiaru.py`
  pilnuje wieku feedu RMKT, DSA nie.
- Stan na koncie: 99 wpisów (pierwotnie 133), etykieta `dsa2026` na 100%, wszystkie APPROVED.
  HEAD na 99 URL-ach: **30 × 200, 69 × 301**. Wśród 301: `xiaomi-su7-ultra-2025-365581` (marka wycofana 07.09).
- Wyszukiwane hasła 30 dni: 311 fraz, 361 zł, 1 kontakt. Czoło: „jetour t2" (12,53 zł), „xpeng g9",
  „ford bronco", „jetour t2 cena", „byd xia".

### Odrzucenia polityk (aktywne kampanie)
| reklama | kampania | status | temat | od |
|---|---|---|---|---|
| 806602181652 | [Brand] | DISAPPROVED | GOVERNMENT_DOCUMENTS_AND_OFFICIAL_SERVICES | ~20.09 (ostatnie wyświetlenia 19.09) |
| 822835403980 | [DSA] | DISAPPROVED | GOVERNMENT_DOCUMENTS_AND_OFFICIAL_SERVICES | ~20.09 (ostatnie 19.09) |
| 818269813589 | [DG] Exeed VX | DISAPPROVED | MISLEADING_AD_DESIGN | ~27.09 (ostatnie 26.09) |
| 822849281734 | [Brand] | APPROVED_LIMITED | GOVERNMENT_DOCUMENTS… | — serwuje |
| 811967380201 | [Topic] | APPROVED_LIMITED | GOVERNMENT_DOCUMENTS… | — serwuje |

Historia zmian konta od 14.09: jedna operacja (15.09, panel — utworzenie reklamy `[Topic]` 824750658447)
plus cotygodniowe pushe feedu RMKT. **Odrzucenia z 20.09 i 27.09 to ponowne recenzje Google, nie nasze edycje.**
Zgodnie z sekcją 7 mapy: `APPROVED_LIMITED` nie ruszamy, `DISAPPROVED` = reklama martwa.

### Ograniczenia budżetem
- `[DG]` — `BUDGET_CONSTRAINED`, i to jedyna kampania, gdzie to ma sens (CPA 13 zł w A14).
- `[RMKT]` — `BUDGET_CONSTRAINED`, CPA 19 zł w A14.
- `[DSA]` — `BUDGET_CONSTRAINED`, IS 10% (90% tracone przez budżet) — ale przy zepsutym feedzie
  podnoszenie budżetu kupiłoby więcej przekierowań.
- `[Brand]` IS 95% (górna 93%), `[Topic]` IS 97% — budżet nie wiąże.

### Wyszukiwane hasła — propozycja wykluczeń (nic nie dodane)
| kampania | fraza | 30 dni | propozycja |
|---|---|---|---|
| [DSA] | „jetour olx", „jetour t2 olx" (+ wszystko z „olx") | 3,39 zł, 4 kl., 0 | wykluczenie PHRASE `olx` — intencja rynku wtórnego |
| [DSA] | „xiaomi su7", „xiaomi su7 ultra", „xiaomi car" | 7,68 zł, 10 kl., 0 | wykluczenie PHRASE `xiaomi` — spójnie z wycofaniem marki z RMKT 07.09 (do decyzji ws. maila o marce) |
| [DSA] | „ford bronco", „bronco", „ford bronco cena w polsce" | 11,38 zł, 14 kl., 0 | kandydat — Ford ma 4 oferty publish; intencja raczej salonowa (Bronco US) |
| [DSA] | „byd benzyna", „byd elektryczny", „byd seal" | ~7,4 zł, 0 | obserwacja — ogólne zapytania o markę z salonem w PL; bez decyzji przy 0 danych o ofertach |
| [Brand] | „auto prima" | 12,98 zł, 12 kl., 0 | wykluczenie EXACT — inny brand (ten sam profil co „bełchatów" 31.08) |
| [Topic] | „auta z chin import" | 221,67 zł, 108 kl., **0** | nie wykluczać (rdzeń), ale to 26% wydatku `[Topic]` bez kontaktu — argument za zejściem z budżetu |

## 4. Pomiar

**Konwersje się liczą.** Per akcja (A30): `click_phone` 71,2, `click_whatsapp` 84,0, `generate_lead` 0 —
razem 140,2 (+ 31 YouTube follow-on views poza kolumną). GA4 u źródła (`eventCount`, 30 dni, wszystkie
źródła, 04.09–03.10): `click_whatsapp` 221 (keyEvents 198), `click_phone` 162 (130), `generate_lead` 7 (7) —
zdarzenia i keyEvents żyją.

**Rozjazd Ads ↔ GA4 urósł z 50% (19.08) do 164%** (140,2 vs 53 zdarzenia `google / cpc`). Struktura z mapy
(sekcja 3) tłumaczy 50%, resztę tłumaczy `gclid`:

| kampania | Ads kontakty A30 | GA4 kontakty `google / cpc` A30 | GA4 sesje `google / cpc` tyg. 36 → 37 → 38 |
|---|---|---|---|
| [Brand] | 49,7 | 41 | 140 → 168 → 152 |
| [Topic] | 7,0 | 7 | 39 → 42 → 52 |
| [DSA] | 2,0 | 0 | 58 → 55 → 70 |
| [RMKT] | 20,5 | **0** | **321 → 48 → 10** |
| [DG] | 61,0 | **5** | **251 → 30 → 1** |

- **GTM live = wersja 15**, zmienna „URL bez fbclid" wycina `["fbclid","gclid","wbraid","gbraid","msclkid","ttclid"]`
  (odczyt przez Tag Manager API 04.10). Wycięcie weszło z v13 (07.09), od tego tygodnia Display i YouTube
  znikają z `google / cpc` w GA4 — w tych kampaniach nie ma UTM-ów, atrybucja wisi wyłącznie na
  `gclid`/`gbraid`/`wbraid`. Search trzyma się lepiej (część sesji GA4 nadal przypisuje).
- Gdzie trafił ruch: `youtube.com / referral` — 33 WhatsApp + 7 telefonów w 30 dni; `googleads.g.doubleclick.net / referral`
  204 sesje; kilkadziesiąt `*.safeframe.googlesyndication.com / referral`.
- **Kolumna „Konwersje" w Ads nie ucierpiała** (DG 61, RMKT 20,5 — wyżej niż przed v13). Mechanizm, którym
  import z GA4 nadal dowozi kliknięcie, jest **niezweryfikowany** (prawdopodobnie cookie `_gcl_*` z
  Conversion Linkera). Skutek praktyczny: decyzje budżetowe z Ads są nadal w porządku, ale **żadne
  porównanie Google ↔ Meta w GA4 i żaden raport dla Ruslana z GA4 nie pokazuje Display/YouTube**.
- Naprawa (opisana 11.09, niewykonana): w `CZYSC` zostawić tylko `fbclid` → `scripts/gtm-fbclid.py --zbuduj`
  → `--publikuj`. Działa od publikacji w przód.

## 5. Do decyzji Janka

**Wykonane 04.10 (błędy po naszej stronie, naprawione bez decyzji):**
- Feed `[DSA]`: wersja API z `ads-config.json` + model odtwarzany ze sluga oferty skasowanej przez rotację; `--apply` 13:25 — 69 usuniętych, 53 następców, feed 83 ofert, bieg kontrolny „wszystkie sztuki żyją”.
- GTM v16: `page_location` czyści tylko `fbclid`; potwierdzone w produkcyjnym `gtm.js`.

**Wycofane (Janek 04.10):** cięcie/pauza `[Topic]` — kampania trzyma 80–99% pozycji rynkowej; druga reklama w `[Brand]` — kampania działa, a zasada z 31.08 brzmi „APPROVED_LIMITED nie ruszamy”.

**Do omówienia (kolejność z rozmowy):**
1. **`[DG]` Exeed VX** — nowa reklama z tym samym Shortem czy odwołanie w panelu.
2. **`[DG]` pauza Shark 6, `[RMKT]` pauza starej RDA 811557389705** (przy tej drugiej — czy dokładamy trzecią RDA).
3. **`[DG]` karuzele** — zostawić bez emisji, zapauzować, czy przywrócić in-feed (07.09 CPA 79 zł).
4. **Wykluczenia:** `olx` i `xiaomi` w `[DSA]`, „auto prima" w `[Brand]`; Ford Bronco do decyzji.
5. Zaległe z mapy: właściwa pauza `[VID]` w panelu.

## Dowody

- `python3 scripts/ads-recheck.py` (04.10) — tabela + strażnik (4 × DISAPPROVED, 89 landingów reklam: 74 × 200, 15 × 301, 0 problemów).
- GAQL (read-only): `campaign`/`ad_group_ad` po oknach, `segments.conversion_action_name`, `segments.conversion_attribution_event_type`,
  `policy_summary.policy_topic_entries`, dzienne wyświetlenia odrzuconych reklam, `change_event` od 06.09,
  `asset_set_asset` (RMKT/DSA), `search_term_view`, impression share.
- GA4 Data API (property 534017542): `sessionSourceMedium × sessionCampaignName`, `eventName × sessionSourceMedium`, tygodnie 31–39.
- `~/.claude/dsa-offer-feed.log` (ostatni sukces 10.08, 17 × 404), `~/.claude/rmkt-feed-refresh.log` (04.10 OK).
- Tag Manager API: `versions:live` = 15, zmienna „URL bez fbclid".
- Robocze skrypty zapytań w scratchpadzie sesji (nie w repo).
