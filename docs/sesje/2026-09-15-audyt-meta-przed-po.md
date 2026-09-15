# Audyt Meta Ads — przed i po zmianach, konwersje, pomiar — 2026-09-15

> Odczyt 15.09 przed południem. Graph API v25 (insighty dzienne per reklama, okna atrybucji,
> miejsca emisji, historia zmian konta, statystyki piksela), GA4 Data API (534017542),
> GTM API (wersja live), GSC. **Na koncie nic nie zostało zmienione** — audyt bez propozycji
> zmian, na polecenie Janka.
> GA4 liczę do **13.09 włącznie**: doby 14–15.09 mają 64–73% sesji bez źródła
> (opóźnienie przetwarzania, patrz sekcja 5). Mety liczę do 14.09 (fazy) albo do teraz (sumy).

## W skrócie

Od startu (04.09 ok. 18:50) wydane **644,90 zł**, kontakt **jeden** — WhatsApp 06.09
z `[FOTO] kadr 1`. Od 06.09 zero, zgodnie w Mecie (wszystkie okna, także 28 dni po kliknięciu)
i w GA4. Zmiany z 07.09 i 11.09 potaniły ruch: sesja GA4 z Mety **0,72 → 0,59 zł** (−18%),
wejście liczone przez Metę 0,80 → 0,38 zł. Kontaktów nie przybyło. Pomiar kontaktów
działa (tag, UTM-y, piksel), ma jednak cztery znane dziury i jedną nową anomalię
(ViewContent, sekcja 5).

## Oś zmian — z historii konta (`/activities`)

| Kiedy | Zmiana |
|---|---|
| 04.09 ok. 18:50 | start: `[VID]` z9-gt + leopard-5-czarny, `[FOTO]` kadr 1 + kadr 2, `[POST]` Denza Z9 + Mazda EZ-6 — 50 zł/dz |
| 07.09 ok. 16:30 | `[VID]` g318 za leoparda, `[POST]` Zeekr 8X za Mazdę, start `[RMKT]` (7 + 5 zł/dz) — 62 zł/dz |
| 10.09 ok. 18:05 | przygotowane na pauzie: `[VID]` lynk-900, `[FOTO]` Od 200 tys., `[POST]` Leopard 7 i Zeekr 8X Max — nie emitują |
| 11.09 17:38 | pauza `[VID]` g318 — cały budżet `[VID]` na z9-gt |
| 12–15.09 | bez zmian w kampaniach (tylko obciążenia karty po 40 zł) |

Fazy do porównań: **A** = 05–06.09 (przed rotacją), **B** = 08–10.09 (po rotacji, g318 żywy),
**C** = 12–14.09 (po pauzie g318; GA4 12–13.09). Doby ze zmianą (07.09, 11.09) pominięte.

## 1. Reklamy — 04–13.09, Meta + GA4

| Reklama | Wydane | CTR w link | Kliknięcia w link | zł / klik | Wejścia (LPV Meta) | Sesje GA4 | zł / sesja GA4 | Zaangażowanie | Kontakty |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| `[VID]` z9-gt | 159,50 zł | 6,3% | 765 | 0,21 | 156 | 217 | 0,74 | 80% | 0 |
| `[FOTO]` kadr 1 | 133,85 zł | 5,2% | 924 | 0,14 | 330 | 315 | **0,42** | 86% | **1** |
| `[POST]` Denza Z9 DM-i | 77,98 zł | 1,3% | 243 | 0,32 | 138 | 27 | 2,89* | 74% | 0 |
| `[VID]` g318 (pauza 11.09) | 64,48 zł | 5,6% | 297 | 0,22 | 43 | 84 | 0,77 | 81% | 0 |
| `[RMKT]` Oglądane — 30 dni | 47,30 zł | 11,8% | 337 | 0,14 | 273 | 89 | 0,53** | 80% | 0 |
| `[RMKT]` Na placu w Polsce | 33,87 zł | 7,3% | 172 | 0,20 | 145 | 63 | 0,54** | 84% | 0 |
| `[VID]` leopard-5-czarny (do 07.09) | 16,47 zł | 5,6% | 79 | 0,21 | 4 | 14 | 1,18 | 93% | 0 |
| `[POST]` Mazda EZ-6 (do 07.09) | 11,26 zł | 1,0% | 39 | 0,29 | 20 | 4 | 2,81* | 75% | 0 |
| `[FOTO]` kadr 2 | 8,68 zł | 4,4% | 53 | 0,16 | 11 | 13 | 0,67 | 77% | 0 |
| `[POST]` Zeekr 8X (od 07.09) | 4,52 zł | 3,3% | 20 | 0,23 | 16 | 6 | 0,75 | 50% | 0 |
| **razem** | **557,91 zł** | **4,4%** | **2 929** | **0,19** | **1 136** | **832** | **0,67** | **82%** | **1** |

\* `[POST]` — link siedzi w treści posta Andrzeja, UTM-y łapią się na ok. 20% wejść (sekcja 5).
Realny koszt sesji jest niższy, ale nie da się go policzyć.
\** `[RMKT]` — GA4 widzi 33–43% wejść raportowanych przez Metę, przyczyna nieustalona (sekcja 5).

Wydatek 14–15.09 (87 zł) jest poza tabelą, bo GA4 tych dób jeszcze nie domknął.
Suma od startu: 644,90 zł.

## 2. Przed i po zmianach — per kampania

| Kampania | Miara | A 05–06.09 | B 08–10.09 | C 12–14.09 |
|---|---|---:|---:|---:|
| `[VID]` | zł / dobę | 27,09 | 24,97 | 29,81 |
| | CTR w link | 6,7% | 5,9% | 5,5% |
| | sesje GA4 / dobę | 41,0 | 30,3 | 39,0 |
| | **zł / sesja GA4** | **0,66** | **0,82** | **0,78** |
| | zł / wejście (Meta) | 1,43 | 1,29 | 1,03 |
| `[FOTO]` | zł / dobę | 14,94 | 15,90 | 17,23 |
| | CTR w link | 4,3% | 5,7% | 5,3% |
| | sesje GA4 / dobę | 29,5 | 37,0 | 50,0 |
| | **zł / sesja GA4** | **0,51** | **0,43** | **0,35** |
| `[POST]` | zł / dobę | 10,92 | 11,10 | 9,80 |
| | CTR w link | 1,1% | 1,5% | 1,1% |
| | zł / wejście (Meta) | 0,59 | 0,51 | 0,53 |
| `[RMKT]` | zł / dobę | — | 12,29 | 12,22 |
| | CTR w link | — | 7,5% | 11,8% |
| | **zł / sesja GA4** | — | **0,57** | **0,51** |
| | zł / wejście (Meta) | — | 0,25 | 0,14 |
| **razem** | zł / dobę | 52,95 | 64,26 | 69,06 |
| | sesje GA4 / dobę | 73,5 | 92,7 | 117,5 |
| | **zł / sesja GA4** | **0,72** | **0,69** | **0,59** |
| | zł / wejście (Meta) | 0,80 | 0,49 | 0,38 |
| | kontakty | 1 | 0 | 0 |

**Rotacja 07.09.** Tańszy ruch po 07.09 to w większości `[RMKT]` (nowy, tani) i `[FOTO]`,
który sam z siebie tanieje. g318 kosztował tyle co z9-gt (0,82 wobec 0,83 zł za sesję w fazie B),
ale zabrał mu 70% budżetu `[VID]`. Leopard wypadł słusznie: 4 wejścia na 79 kliknięć.
`[POST]` Zeekr 8X praktycznie nie wystartował (sekcja 4).

**Pauza g318 11.09.** Po niej z9-gt dostaje cały budżet `[VID]` (29,81 zł/dz wobec 20,59 zł
w fazie A) i drożeje: **0,61 → 0,80 zł za sesję GA4**, CTR w link 6,9% → 5,5%, a 14.09 już 4,4%.
Koszt sesji `[VID]` po pauzie (0,78 zł) jest praktycznie taki sam jak przed nią (0,82 zł).
Meta pokazuje poprawę (1,29 → 1,03 zł za wejście), ale przy `[VID]` jej licznik wejść zaniża
ruch — GA4 widzi 1,4–2× więcej sesji niż Meta wejść (sekcja 5), więc tu wiarygodniejszy jest GA4.

## 3. Trend dzienny

| Doba | Wydane | Wejścia (Meta) | Sesje GA4 | zł / sesja | CTR link `[VID]` | CTR link `[FOTO]` | CTR link `[RMKT]` | CTR link `[POST]` | Kontakty |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 05.09 | 58,32 | 67 | 59 | 0,99 | 6,8% | 4,7% | — | 1,4% | 0 |
| 06.09 | 47,58 | 66 | 88 | 0,54 | 6,5% | 3,8% | — | 0,8% | **1** |
| 07.09 | 47,33 | 86 | 77 | 0,61 | 5,9% | 5,3% | 7,8% | 2,4% | 0 |
| 08.09 | 78,77 | 160 | 125 | 0,63 | 6,0% | 6,2% | 5,8% | 1,7% | 0 |
| 09.09 | 66,50 | 125 | 105 | 0,63 | 6,4% | 5,3% | 8,2% | 1,4% | 0 |
| 10.09 | 47,52 | 107 | 48¹ | — | 4,8% | 5,2% | 9,2% | 1,3% | 0 |
| 11.09 | 64,95 | 139 | 95 | 0,68 | 5,7% | 4,5% | 11,3% | 1,0% | 0 |
| 12.09 | 61,34 | 184 | 111 | 0,55 | 6,6% | 6,2% | 22,7% | 1,0% | 0 |
| 13.09 | 78,22 | 197 | 124 | 0,63 | 5,6% | 4,9% | 9,4% | 1,3% | 0 |
| 14.09 | 67,61 | 159 | ²| — | 4,4% | 5,0% | 9,4% | 1,1% | 0 |

¹ 10.09 w GA4 nadal ma 14% sesji bez źródła. ² 14.09 GA4 niedomknięte (64,5% bez źródła).

Częstotliwość bez sygnału zmęczenia: konto 1,74 przez 12 dób, zestawy 1,2–1,4 w fazie C.
Jedyna kreacja z wyraźnie spadającym CTR to z9-gt po przejęciu całego budżetu `[VID]`.

## 4. Co działa, co nie działa

**Działa**
- **`[FOTO]` kadr 1** — najtańsza sesja i tanieje z fazy na fazę (0,47 → 0,43 → 0,35 zł),
  CTR stabilny 5–6%, zaangażowanie 86%, jedyny kontakt kampanii.
- **`[RMKT]`** — CTR w link 9,7% (Oglądane w fazie C 15,5%), 84% kliknięć dochodzi według Mety,
  sesja GA4 ok. 0,5 zł. Kontaktów 0 przy 97 zł.
- **Ruch jest dobrej jakości na stronie**: zaangażowanie 74–93%, 2,1–3,5 odsłony na sesję,
  2–3 minuty. Ludzie oglądają huby i oferty.
- **Emisja poprawna**: 93% wydatku to mężczyźni, 58% mężczyźni 35–54 lat. Konto aktywne,
  wszystkie żywe reklamy `ACTIVE`, bez odrzuceń.

**Nie działa**
- **Kontakt.** 1 za 645 zł, zero od 06.09 (od 07.09 wydane 531,62 zł). Z 688 osób pozyskanych z Mety
  kontaktowała się jedna (**0,15%**). Ta sama strona i te same przyciski: `google / cpc`
  do **2,7%** osób (14 z 524), organik **1,0%** (34 z 3 233). Wejścia na `/kontakt/` i `/zamow/`
  04–13.09: Meta 7, `google / cpc` 41, organik 72.
  Punkt odniesienia z audytu 10.09: Google Ads 52 zł za konwersję w Ads, 79 zł za kontakt w GA4.
- **Efektu okrężnego nie widać.** Kontakty całej witryny wzrosły z 6,6 do 8,3 dziennie
  (28 dób przed startem wobec 04–13.09), ale przyrost jest w organiku, wejściach bezpośrednich
  i `(not set)`, nie w kanałach Facebooka (organik Strony 0,21 → 0,30 dziennie). Wyszukiwania
  marki w GSC stoją (fraza „prima”: 61,7 wyświetleń i 19,1 kliknięć dziennie przed startem,
  61,9 i 21,5 po starcie). Z 688 osób z Mety ok. 20 sesji wróciło innym kanałem, bez kontaktu.
  Organik jest przy tym zanieczyszczony ruchem z Google Ads — sekcja 5, punkt 5.
- **`[VID]` z9-gt przy pełnym budżecie drożeje** (0,61 → 0,80 zł za sesję, CTR spada).
  Z kliknięć w wideo na stronę dochodzi 25–29% (liczone sesjami GA4), bez zmian od startu.
- **`[POST]` chodzi na jednym poście.** Denza Z9 wzięła 90 z 107 zł; Zeekr 8X przez 8 dni
  dostał 5,54 zł. 27% budżetu (08–14.09: Stories FB 14,82 zł + nakładki Reels 4,26 zł) daje
  **0 wejść** — tak samo jak w audycie 10.09.
- **Każda kampania chodzi faktycznie na jednej kreacji.** `[FOTO]` kadr 2 dostał w fazie C
  0,14 zł, Zeekr 8X 1,31 zł, a w `[VID]` po pauzie g318 jest tylko z9-gt. Zasada „dwie–trzy
  kreacje równolegle” (plan, sekcja 3) jest spełniona w ustawieniach, nie w emisji.

## 5. Czy konwersje mierzą się prawidłowo

| # | Element | Stan | Dowód |
|---|---|---|---|
| 1 | UTM-y z reklam → GA4 | **TAK** dla `[VID]` i `[FOTO]` | sesje GA4 / wejścia Meta: 0,95 (`[FOTO]`) – 1,4–2,0 (`[VID]`), kampania i reklama na miejscu |
| 2 | UTM-y `[POST]` | **CZĘŚCIOWO** | 0,20 — link wklejony w treść posta, `url_tags` łapią się na część kliknięć |
| 3 | UTM-y `[RMKT]` | **CZĘŚCIOWO, przyczyna nieustalona** | 0,33–0,43; adresy z feedu: 36 z 36 najczęściej klikanych zwraca 200 bez przekierowania, więc UTM-y nie giną w redirectach |
| 4 | Piksel `Contact` na telefonie i WhatsApp | **TAK, w granicach zgody** | GTM v13: tagi na `click_phone` / `click_whatsapp`; 49 `Contact` w pikselu wobec ok. 80 kontaktów w GA4 (04–13.09, 61%, jak 10.09) — piksel widzi tylko osoby ze zgodą marketingową |
| 5 | Jedyny kontakt z Mety w raportach | **Meta go widzi, nasze narzędzia nie** | jest jako `offsite_conversion.fb_pixel_custom` (06.09, kadr 1), nie `Contact`; kolektor dashboardu liczy tylko `fb_pixel_lead`, `recheck_start.py` — `contact_website` / `lead`. Bez zmian od 10.09 |
| 6 | GA4 — `(not set)` z 10–11.09 | **ROZWIĄZANE samo** — opóźnienie Google | 10.09: 68,8% → 14,1%, 11.09: 67,2% → 1,0%; ten sam wzorzec teraz na 14.09 (64,5%) i 15.09 (72,7%) — ostatnich 2–3 dób GA4 nie oceniamy |
| 7 | GTM v13 wycina `gclid` / `wbraid` / `gbraid` z `page_location` | **NADAL ŻYWE** | wersja live 13 (07.09); `google / cpc` w GA4 33–49 sesji dziennie od 08.09 zamiast 103–133. Psuje porównanie Meta ↔ Google w GA4 i prawdopodobnie przerzuca część ruchu Ads do organiku (niezweryfikowane). Decyzja z 11.09 otwarta |
| 8 | **ViewContent** — cel optymalizacji `[VID]`, `[FOTO]`, `[RMKT]` | **ANOMALIA, przyczyna nieustalona** | od naprawy GTM v11 (25.08) ViewContent w pikselu to 93–95% PageView, a oferty to ok. 37% odsłon w GA4. 12–13.09: 3 699 ViewContent w pikselu wobec 2 224 odsłon ofert w GA4. Kod wysyła `view_item` raz i tylko na ofercie (HTML huba, strony głównej i oferty sprawdzony). Liczba ViewContent nie jest więc liczbą otwartych ofert. Na wnioski o kontaktach to nie wpływa |
| 9 | Kontakty poza stroną (Messenger, telefon ze Strony FB) | **NIEMIERZALNE** | brak uprawnienia `pages_messaging`; jedyne źródło to Ruslan i Andrzej |

## Zauważone obok, nie ruszam

- 13 z 24 wstrzymanych reklam nie ma `url_tags` (`[VID]` n9, exeed-vx, leopard-7,
  leopard-5-niebieski, shark-6; `[FOTO]` Do 200 tys.; 7 postów). Po ponownym włączeniu GA4
  nie przypisze ich ruchu. Cztery kreacje przygotowane 10.09 UTM-y mają.

## Jak odtworzyć

```
# Meta — dziennie per reklama
{act}/insights?level=ad&time_increment=1&time_range={"since":"2026-09-04","until":"2026-09-15"}
  &fields=campaign_name,ad_name,spend,impressions,reach,clicks,inline_link_clicks,actions,video_thruplay_watched_actions
# Meta — kontakty w oknach atrybucji
{act}/insights?level=ad&action_attribution_windows=["1d_click","7d_click","1d_view","28d_click"]&fields=ad_name,actions
# Meta — historia zmian, zasięg per faza, miejsca, demografia
{act}/activities?since=2026-09-03 · level=adset&fields=reach,frequency · breakdowns=publisher_platform,platform_position · breakdowns=age,gender
# Piksel
{pixel}/stats?aggregation=event   (paginacja! kubełki godzinowe)
# GA4 — scripts/ga4_query.py
date × sessionSourceMedium (udział (not set)) · sessionCampaignName × sessionManualAdContent (facebook / paid_social)
firstUserSourceMedium = facebook / paid_social × eventName · pagePath BEGINS_WITH /oferta/ (odsłony ofert)
# GTM — accounts/6351095501/containers/250095450/versions:live  (tagi 27–31)
```

---

## Aktualizacja 15.09 po południu — diagnoza ViewContent (punkt 8 z sekcji 5)

**Przyczyna ustalona.** Od publikacji GTM v11 (25.08 15:48) tag bazowy piksela Meta siedzi na
wyzwalaczu *Inicjalizacja*. Na stronie oferty `view_item` jest pierwszym wpisem w `dataLayer`,
**przed** domyślną zgodą Complianz, więc GTM inicjalizuje się, gdy zgoda jeszcze nie istnieje
(`ad_storage: implicit`), i piksel odpala. Na każdej innej stronie pierwszym wpisem jest
`consent:default` (odmowa) i inicjalizacja wypada przed aktualizacją zgody, więc tag bazowy
zostaje zablokowany i już nie wraca. Przed v11 tag bazowy stał na *All Pages* i działał poprawnie
(tylko ViewContent wtedy nie strzelał).

Zmierzone przeglądarką bezgłową (Playwright, Chromium, telefon 390 px):

| Scenariusz | Piksel Meta |
|---|---|
| oferta, **bez zgody**, baner widoczny | `fbevents.js` ładuje się, wysyła **PageView + ViewContent** |
| hub `/samochody/denza/z9-gt-dm-i/`, zgoda zapisana | `fbq` nie istnieje, **zero wywołań** |
| `/kontakt/`, zgoda zapisana | zero wywołań |
| strona główna, `/samochody/`, zgoda zapisana | zero wywołań |
| hub → klik „Akceptuj” | zero wywołań także po akceptacji |

**Skutki:**
1. **Piksel wysyła dane bez zgody** na stronach ofert (PageView + ViewContent). Complianz działa
   w trybie opt-in, więc to niezgodne z ustawieniem banera.
2. **Poza ofertami piksel nie działa wcale**, także u osób ze zgodą. Stąd ViewContent = 93–95%
   PageView: piksel widzi tylko oferty.
3. **`Contact` łapie tylko kliknięcia na ofertach.** 57,6% kliknięć w telefon/WhatsApp pada
   na ofertach (53 z 92 w 04–13.09); huby 16,3%, `/kontakt/` 10,9%, strona główna 5,4% — dla Mety
   niewidoczne. To tłumaczy rozjazd 49 wobec ok. 80–90. **Teza z audytu 10.09, że lukę robi zgoda,
   była błędna.**
4. `[VID]` i `[FOTO]` lądują na hubach, gdzie piksel milczy — Meta nie widzi wejścia ani kontaktu
   na stronie docelowej, dopóki człowiek nie wejdzie w ofertę. Stąd sesje GA4 > wejścia Meta przy `[VID]`,
   a przy `[RMKT]` (ląduje na ofercie, piksel bez zgody) wejścia Meta > sesje GA4.
5. Grupy „Wszyscy odwiedzający” i wykluczenie „Kontakt 180 dni” zawierają wyłącznie ruch z ofert.

**GA4 to nie dotyczy** — tagi Google działają na wszystkich stronach. Wniosek „z Mety 1 kontakt
na 688 osób” stoi.

Zauważone obok, niezweryfikowane: tag `33 GAds - Dynamic Remarketing (offerdetail)` stoi na tym
samym `view_item` z tym samym wymogiem zgody — najpewniej zachowuje się jak ViewContent.

Skrypty testu: scratchpad sesji (`piksel-test.js`, `piksel-test2.js`), Playwright z
`~/prywatne/wakacje-papillon-2026/node_modules`, przeglądarka `~/.cache/ms-playwright`.

### Naprawa — GTM v14 i v15 (15.09 wieczorem, zgoda Janka)

Skrypt: `scripts/gtm-piksel-meta.py`. Kopia v13 przed zmianą: `~/backups/primaauto/2026-09-15/gtm-live-v13.json`.
Zmienione wyłącznie tagi Meta 27–31 + nowy wyzwalacz `40 PV - oferta`. Tagi GA4 (4, 11, 12, 13, 38),
Google Ads (7), remarketing Google (33), wyzwalacz 26, zmienne — bez zmian (diff workspace wobec v13).
Wymóg zgody `ad_storage` + `ad_user_data` i wyzwalacze blokujące 35/36 zostały.

| Tag | v13 | v15 |
|---|---|---|
| 27 Base | Inicjalizacja | All Pages, „raz na zdarzenie”; `init` + PageView pod strażnikiem `window.__paMetaInit` (raz na odsłonę) |
| 28 ViewContent | CE `view_item` | `PV - oferta` (All Pages + `/oferta/`), tag 27 jako tag przygotowujący |
| 29–31 Lead / Contact | bez tagu przygotowującego | te same wyzwalacze, tag 27 jako tag przygotowujący |

v14 („raz na odsłonę”) nie przeszła scenariusza E: próba zablokowana zgodą przy starcie strony zużywała
limit i po akceptacji tag bazowy już nie wracał. v15 to poprawia.

Weryfikacja v15 (Playwright, telefon 390 px):

| Scenariusz | Meta | GA4 |
|---|---|---|
| A oferta bez zgody | **zero wywołań** (wcześniej PageView + ViewContent) | page_view |
| B hub ze zgodą | PageView (wcześniej nic) | page_view |
| C oferta ze zgodą | PageView + ViewContent `["59860580"]` | page_view |
| D `/kontakt/` ze zgodą + klik telefon | PageView (jeden) + Contact | page_view, click_phone |
| E hub bez zgody → Akceptuj → klik WhatsApp | PageView + Contact | page_view, click_whatsapp |
| F hub bez zgody + klik telefon | zero wywołań | page_view, click_phone |

`Lead` (formularz) nie był klikany w teście — ten sam mechanizm co `Contact`.

**Konsekwencje dla danych od 15.09 ok. 18:30:** ViewContent spadnie (znikają osoby bez zgody na ofertach),
PageView i Contact wzrosną (huby, `/kontakt/`, strona główna). Liczb piksela sprzed i po naprawie nie
porównujemy wprost. Kampanie optymalizujące na ViewContent (`[VID]`, `[FOTO]`, `[RMKT]`) przez kilka dni
dostaną mniej zdarzeń. Grupy odbiorców z piksela zaczną łapać ruch spoza ofert. Rollback: publikacja v13.

---

## Przebudowa kampanii 15.09 wieczorem — decyzje Janka z quizu, wykonane na żywo

Skrypty: `scripts/social/buduj_kat.py` (nowa kampania), `scripts/social/przebudowa_2026_09_15.py`
(zmiany i pauzy). Każdy krok najpierw sondą `validate_only`.

| Kampania | Stan po zmianie |
|---|---|
| **`[KAT]` Oferta — nowi odbiorcy (katalog)** — nowa, `120249107889100243` | katalog „Wszystkie pojazdy” (3 009), Polska, rama 25–65 z **propozycją M 30–60** (Advantage+ — Meta nie przyjmuje min. wieku > 25 ani maks. < 65, kody 1870188/1870189), bez wykluczeń, tylko aktualności FB + IG, cel ViewContent (Contact odrzucony przy katalogu, 2446814), 35 zł/dz, tekst A + B, przycisk „Skorzystaj z oferty” |
| **`[FOTO]`** — nowy zestaw „Karuzele PL M 30-60 — Contact” `120249107902400243` | kadr 1 + kadr 2 (te same kreacje, UTM), M 30–60 twardo, tylko aktualności, **cel Contact**, okno 7 dni po kliknięciu, 15 zł. Stary zestaw (ViewContent) na pauzie — zdarzenia konwersji nie da się zmienić w opublikowanym zestawie (3260011) |
| **`[RMKT]` Oglądane — 30 dni** | 15 zł (z 7), tylko aktualności FB + IG, bez zmian listy i wykluczeń |
| `[RMKT]` Na placu w Polsce | pauza |
| `[VID]`, `[POST]` | pauza kampanii |

**Konto: 65 zł/dz.** Wszystkie cztery żywe reklamy `ACTIVE` (odczyt z API po zmianie).

Otwarte, nieruszone: zestaw `[RMKT]` ma `targeting_relaxation_types.custom_audience: 1` — Meta może
wychodzić poza listę oglądających. Nie było w quizie — decyzja Janka.
Przycisk Messengera na ofercie — makieta czeka na wybór wariantu i test gotowego tekstu na telefonie.

Wpadka po drodze: `krok()` z `buduj_rmkt.py` zapisuje stan do `buduj_rmkt.STAN`, więc pierwsze
`buduj_kat.py --wgraj` nadpisało `state/meta-rmkt.json`. Przywrócone (diff z gitem pusty),
`buduj_kat.py` przestawia teraz `buduj_rmkt.STAN` na `meta-kat.json`. Konta Meta to nie dotknęło.

**Recheck:** za 3 doby (18.09) — `[KAT]` wejścia i koszt sesji GA4, `[FOTO]` liczba Contact na nowym
pikselu, częstotliwość `[RMKT]` przy 15 zł.
