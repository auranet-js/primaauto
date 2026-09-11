# Audyt Meta Ads — dlaczego nie ma kontaktów, 2026-09-10

> Zlecenie: `docs/sesje/2026-09-10-PROMPT-audyt-meta-konwersje.md`. Odczyt 10.09, ok. 16:30.
> Wszystkie liczby z API w tej sesji: Graph API v25 (insighty, statystyki piksela), GA4 Data API
> (property 534017542), GTM API (wersja live 13), GSC. Na koncie nic nie zostało zmienione.
> Porównania Meta ↔ GA4 dotyczą dób zamkniętych **04–09.09**, wydatki Mety podaję do 09.09 włącznie.

## Werdykt w trzech zdaniach

Pomiar działa i obie strony widzą to samo: **jeden kontakt** (WhatsApp, 06.09, `[FOTO] kadr 1`),
który Meta też odnotowała, tylko pod etykietą `fb_pixel_custom`, a nie `Contact`.
Kontaktu nie ma, bo ruch z Mety nie dzwoni: **0,26% sesji kończy się kontaktem wobec 3,7%
w `google / cpc`** na tej samej stronie, z tymi samymi przyciskami. Nie ma też śladu,
że reklamy dowożą kontakt okrężną drogą — kontakty z organiku i wejść bezpośrednich po 04.09
spadły, a nie wzrosły.

## Stan konta — odczyt 10.09

`act_1038563008906171`: wydane **325,91 zł** z `spend_cap` 1 000 zł, zostało 674 zł.
Tempo z trzech zamkniętych dób (07–09.09): 47,33 / 78,77 / 66,50 zł, średnio **64 zł/dz**
przy budżetach 62 zł → sufit około **20–21.09**.

| Kampania | Cel / optymalizacja | Wydane 04–09.09 | Kliknięcia w link | LPV Meta | Sesje GA4 | zł / sesja GA4 | Kontakty |
|---|---|---:|---:|---:|---:|---:|---:|
| `[VID]` | LEADS / ViewContent | 135,20 zł | 693 | 102 | 167 | 0,81 | 0 |
| `[FOTO]` | LEADS / ViewContent | 83,60 zł | 574 | 183 | 162 | 0,52 | **1** |
| `[POST]` | TRAFFIC / LINK_CLICKS | 55,24 zł | 189 | 101 | 18 | 3,07* | 0 |
| `[RMKT]` (od 07.09) | SALES / ViewContent | 31,84 zł | 156 | 123 | 34 | 0,94 | 0 |
| **razem** | | **305,88 zł** | **1 612** | **509** | **381** | **0,80** | **1** |

\* `[POST]` — sesje GA4 niepełne, patrz hipoteza 4.

---

## Hipoteza 1 — Meta robi to, o co ją poprosiliśmy: **POTWIERDZONA co do mechanizmu, ale nie ma lepszego celu**

Trzy kampanie optymalizują na ViewContent i dostarczają dokładnie to: 831 ViewContent za 266 zł (z niepełną dobą 10.09)
(`[VID]` 346, `[FOTO]` 314, `[RMKT]` 171), ruch zaangażowany w 78% sesji. Algorytm znajduje
ludzi, którzy tanio otwierają ofertę — i nic więcej.

Przełączenie na `Contact` nie ma na czym stanąć:

| Sygnał | 04–09.09 | Źródło |
|---|---:|---|
| `Contact` z piksela, cała witryna, wszystkie źródła | **30** (≈35 / tydzień) | `/{pixel}/stats?aggregation=event` |
| `Contact` przypisany kampaniom Mety | **0–1** | insights, `action_attribution_windows` |
| Próg wyjścia z uczenia na jeden zestaw | ~50 / tydzień | dokumentacja Mety |

Zestaw skonsolidowany na `Contact` startowałby z zerem własnych konwersji i utknąłby
w „ograniczonym uczeniu". Zdarzenie pośrednie też nie ma wolumenu: wejścia na `/kontakt/`
i `/zamow/` z Mety to **5 odsłon w 6 dni**, `zamowienie_start` **0**, `form_start` 7 zdarzeń
od 2 osób (to filtry wyszukiwarki, nie zamiar kontaktu). Zaangażowanie nie odróżnia
dobrych od złych wizyt, bo zaangażowani są prawie wszyscy.

**Wniosek:** decyzja z 28.08 była poprawna i nadal jest jedyną wykonalną. Kosztem jest to,
że Meta nie szuka dzwoniących — i przy tym budżecie szukać nie będzie.

## Hipoteza 2 — kontakt jest, ale ląduje gdzie indziej: **OBALONA w agregatach, nierozstrzygnięta na poziomie osoby**

**Zasięg użytkownika w GA4.** 303 osoby pozyskane przez `facebook / paid_social` odbyły
330 sesji z Mety i **4 sesje innymi kanałami** (2 `(not set)`, 1 `google / cpc`,
1 `l.facebook.com`). Kontakt: 1, ten sam co w zasięgu sesji.
Zapytanie: `firstUserSourceMedium = facebook / paid_social` × `sessionSourceMedium` × `eventName`.

**Okna atrybucji Mety.** `7d_click`, `1d_click`, `1d_view` na wszystkich czterech kampaniach —
żadnego `Contact`. View-through dołożył tylko ViewContent (`[VID]` 70, `[FOTO]` 37).

**Efekt okrężny w agregatach — nie ma go.** Kontakty GA4 (telefon + WhatsApp + formularz)
na dobę, 28 dni przed startem wobec 6 dób po:

| Źródło | przed (07.08–03.09) | po (04–09.09) |
|---|---:|---:|
| `google / organic` | 4,36 | 3,50 |
| `(direct) / (none)` | 0,79 | 0,50 |
| `google / cpc` | 1,75 | 3,33 |
| Facebook jako referral (organik Andrzeja) | 0,21 | 0,33 |
| `facebook / paid_social` | 0 | 0,17 |
| **cała witryna** | **7,82** | **9,00** |

Wzrost całości idzie z Google Ads, organik i wejścia bezpośrednie spadły. Wyszukiwania marki
w GSC („prima"): tydzień przed startem 61,7 wyświetleń / 19,0 kliknięć dziennie, 05–08.09
65,5 / 22,0 — różnica mieści się w dziennym rozrzucie 50–80. Człowiek, który zobaczył reklamę
i potem wpisał „prima auto" w Google, zostawiłby ślad właśnie tu.

**Czego GA4 nie widzi.** Przeglądarka w aplikacji Facebooka ma własne ciasteczka, więc GA4
nie połączy wizyty z reklamy z późniejszą wizytą w Safari czy Chrome. Dowód z tych danych:
**31 z 34 sesji `[RMKT]` GA4 oznacza jako nowych użytkowników**, choć remarketing trafia
wyłącznie do osób, które już były na stronie. Dlatego zasięg użytkownika jest dolnym
oszacowaniem — ale agregaty i wyszukiwania marki tej luki nie wypełniają.

## Hipoteza 3 — `Contact` nie strzela tam, gdzie powinien: **OBALONA; pokrycie ogranicza zgoda, nie tag**

**Tag jest wpięty poprawnie.** GTM, wersja live 13: `Meta Pixel - Contact (phone)` i
`(whatsapp)` odpalają się na tych samych zdarzeniach `click_phone` / `click_whatsapp`
co tagi GA4. Źródłem zdarzeń jest `asiaauto-tracking.js` — nasłuch kliknięć w `tel:` i `wa.me`
na całej stronie, bez rozróżnienia urządzeń.

**Luka 30 wobec 51 to zgoda na marketing.** Wszystkie tagi Meta w GTM mają
`consentStatus: needed` na `ad_storage` + `ad_user_data`; Complianz startuje z każdą zgodą
`denied` (tryb opt-in, region UE). Tagi GA4 w GTM nie mają blokady zgody i działają
w Consent Mode wbudowanym w tagi Google, a tagi Meta są twardo wstrzymane do zgody
marketingowej. Piksel widzi więc kontakty wyłącznie osób, które kliknęły zgodę na marketing —
ok. 59% tego, co GA4. Rozjazd tłumaczy konfiguracja, nie awaria.
To wymóg prawny, nie usterka. Conversions API tego nie obejdzie, bo zgoda dotyczy przetwarzania,
a nie kanału wysyłki — a CAPI na produkcji i tak nie ma (`capi_send.sh` wysyła wyłącznie
zdarzenie testowe).

**Meta ten jedyny kontakt zobaczyła.** Na całym koncie jest jedno zdarzenie niestandardowe:
`offsite_conversion.fb_pixel_custom` = 1, **06.09, `[FOTO] Cała oferta — kadr 1`**, okno
`1d_click`. W GA4 jedyny kontakt z Mety to WhatsApp z tego samego dnia i tej samej reklamy
(oferta `denza-n9-dm-i-2025-351079`, telefon). Własnych konwersji konto nie ma
(`customconversions` puste), a piksel zna tylko cztery zdarzenia standardowe. Najpewniej
to jest ten kontakt, zaraportowany pod etykietą niestandardową. **Nasze narzędzia szukają
`contact_website` i `lead`, więc go nie liczą** — stąd teza z promptu „Meta nie przypisała
ani jednego Contact".

**`fbc` — nie do sprawdzenia przez API.** Agregacja `match_keys` dla `Contact` pokazuje
`pdid` 11, `c_user_cookie` 5, `fr_cookie` 5 na 16 zdarzeń z próby. `fbc` nie pojawia się
w tej agregacji dla żadnego zdarzenia, także ViewContent, więc brak w odczycie niczego nie dowodzi.

**Pułapka po drodze:** agregacja `url` w statystykach piksela zwraca sam adres domeny dla
każdego zdarzenia — także dla ViewContent, które strzela wyłącznie na ofertach. „Wszystkie
kontakty ze strony głównej" to cecha raportu, nie pomiaru. W GA4 kontakty padają na ofertach
(30 z 54), `/kontakt/` (9) i hubach (4).

## Hipoteza 4 — kliknięcia są puste: **POTWIERDZONA, ale pieniędzy to prawie nie kosztuje (poza `[POST]`)**

Wszystkie zestawy rozliczają się za wyświetlenia (`billing_event: IMPRESSIONS`), więc puste
kliknięcie nie jest płatne samo w sobie. Liczy się koszt wejścia na stronę.

| Miejsce | wydane | kliknięcia w link | LPV | LPV / klik | zł / LPV |
|---|---:|---:|---:|---:|---:|
| `[VID]` Facebook feed, aplikacja | 79,46 zł | 319 | 55 | 17% | 1,44 |
| `[VID]` Facebook Reels | 31,70 zł | 284 | 25 | **9%** | 1,27 |
| `[FOTO]` Facebook feed, aplikacja | 61,72 zł | 436 | 132 | 30% | 0,47 |
| `[POST]` Facebook Stories | 12,91 zł | 37 | **0** | 0% | — |
| `[POST]` Marketplace | 14,27 zł | 61 | 44 | 72% | 0,32 |
| `[POST]` wyszukiwarka Facebooka | 13,66 zł | 49 | 34 | 69% | 0,40 |

Zapytanie: `insights?level=campaign&breakdowns=publisher_platform,platform_position,device_platform`.

- **`[VID]` Reels** mają puste kliknięcia (9% dochodzi), ale wejście kosztuje tyle co w feedzie.
  Wycięcie Reels nie obniżyłoby kosztu wejścia, a zresetowałoby uczenie. Audience Network
  i Messenger nie dostały ani złotówki — zestawy są ograniczone do Facebooka i Instagrama.
- **`[POST]` Stories to czysty odpad:** 21% wydatku kampanii, zero wejść.
- **`[POST]` w GA4 jest w większości niewidoczny.** Posty Andrzeja to zdjęcie z linkiem wklejonym
  w treść. `url_tags` z kreacji przyklejają się tylko do części kliknięć: GA4 widzi 10 sesji
  z UTM-ami na obu promowanych ofertach, Meta raportuje 101 wczytań strony. Wcześniejsze
  wyjaśnienie („Meta liczy rozwinięcia posta") było tylko częściowe — koszt sesji 3,07 zł
  jest zawyżony, a realnego nie da się policzyć.
- **`[RMKT]`: 123 LPV wobec 34 sesji GA4** — nierozstrzygnięte. Kreacje mają `url_tags`, feed
  ma czyste adresy ofert. Kontaktu nie ma w żadnym z systemów, więc werdyktu to nie zmienia.
- Demografia w porządku: 93% wydatku to mężczyźni, 55% całości to mężczyźni 35–54.

## Hipoteza 5 — landing nie prowadzi do kontaktu: **OBALONA jako przyczyna główna**

- Sześć landingów aktywnych reklam i dwie oferty z postów zwracają **200** (curl bez
  podążania za przekierowaniem). Denza idzie na działający slug `z9-gt-dm-i`.
- Na telefonie przycisk telefonu i WhatsApp jest widoczny bez przewijania: pigułka
  `pa-pill` siedzi w nagłówku `position: sticky` (hub i oferta), a na ofercie jest dodatkowo
  przyklejony pasek `aa-mobile-cta` (Zamów / Zadzwoń / WhatsApp). Sprawdzone w HTML i CSS,
  bez renderu w przeglądarce.
- Ruch z Mety zachowuje się na stronie jak ruch z wyszukiwania, tylko nie dzwoni:

| | `facebook / paid_social` | `google / cpc` |
|---|---:|---:|
| sesje 04–09.09 | 381 | 538 |
| zaangażowanie (mobile) | 78% | 60% |
| odsłony hubów / ofert | 53% / 29% | 44% / 34% |
| odsłony `/kontakt/` + `/zamow/` | 5 | 28 |
| kontakty | **1 (0,26%)** | **20 (3,7%)** |

Landingi się różnią (Meta → huby, Google → strona główna i oferty z DSA), więc porównanie
nie izoluje samego landingu. Ale przycisk jest na miejscu, ludzie oglądają oferty — nie sięgają
po telefon. To różnica zamiaru, nie strony.

## Hipoteza 6 — oczekiwania i skala: **tu leży odpowiedź**

Punkt odniesienia z Google Ads (`docs/ads/mapa-kampanii.md`, 30 dni do 31.08): **52 zł**
za konwersję w Ads, **79 zł** za kontakt w GA4 (3 958 zł / 50). Najbliższy odpowiednik
`[VID]` — `[DG]` wideo na YouTube do zimnych odbiorców — ma **35 zł** za kontakt w GA4.

Co mówi 1 kontakt za 306 zł (rozkład Poissona, przy założonym prawdziwym CPA):

| Gdyby Meta miała CPA | oczekiwane kontakty | szansa na ≤ 1 |
|---|---:|---:|
| 35 zł (jak `[DG]`) | 8,7 | 0,2% |
| 79 zł (jak całe Google Ads) | 3,9 | **10%** |
| 100 zł (2× Google Ads) | 3,1 | 19% |
| 160 zł | 1,9 | 43% |

Z ~90% pewnością Meta jest dziś droższa od Google Ads. Z 99,8% — nie jest tak dobra jak
wideo na YouTube. Rozstrzygnąć, czy mieści się w 100 zł, jeszcze się nie da.

### Proponowany próg werdyktu

Miara: kontakty przypisane Mecie — **większa z dwóch liczb**: GA4 `facebook / paid_social`
w zasięgu użytkownika albo Meta `Contact` + `fb_pixel_custom` w oknach `7d_click` i `1d_view`.
Dopuszczalny koszt: **100 zł za kontakt** (2× Google Ads, zapas na górę lejka).

| Kiedy | Wydatek łączny | Wyrok „nie dowozi" przy | Szansa pomyłki |
|---|---:|---:|---:|
| próg pośredni, ok. **15.09** | 650 zł | **≤ 2 kontakty** | 4% |
| sufit, ok. **20–21.09** | 1 000 zł | **≤ 5 kontaktów** | 7% |

„Szansa pomyłki" to prawdopodobieństwo, że kampania, która naprawdę mieści się w 100 zł,
dostanie wyrok przez pecha. Przy obecnym tempie (1 kontakt / 306 zł) spodziewany wynik przy
suficie to ok. **3 kontakty** — poniżej progu.

`[RMKT]` oceniamy osobno, bo to inny mechanizm i inny budżet: po **400 zł** i ≤ 1 kontakcie
(9%). Przy 12 zł/dz to ok. 33 dni — dłużej niż sufit.

## Hipoteza 7 — `[RMKT]` katalog: **za wcześnie, bez zarzutów do konstrukcji**

| Zestaw | produktów | lista (`delivery_estimate`) | wydane | kliknięcia w link | sesje GA4 |
|---|---:|---|---:|---:|---:|
| Oglądane — 30 dni | 3 019 | 1 800–2 100 (07.09: 1 400–1 700) | 22,00 zł | 103 | 19 |
| Na placu w Polsce | 19 | 2 600–3 000 (07.09: 2 200–2 600) | 15,33 zł | 72 | 15 |

- „Na placu" wydaje normalnie mimo 19 pozycji — 72 kliknięcia, 15 sesji GA4 na
  dziewięciu różnych autach z placu.
- „Oglądane" rozkłada 22 zł na **ponad 500 aut** (maks. 0,17 zł na auto). Tak ma działać
  remarketing dynamiczny — każdy dostaje auto, które sam oglądał. To, że auta stoją w Chinach,
  a nie na placu, jest modelem biznesowym, nie usterką reklamy.
- Listy rosną o ok. 400 osób w trzy doby — to jest realny produkt prospectingu.
- Kontaktów 0 przy 31,84 zł. Google RMKT płaci 49–56 zł za kontakt, więc zero w trzy doby
  jest oczekiwane.

---

## Decyzje dla Janka — w kolejności wpływu na wydatki

Koszt pozostawienia bez zmian liczony do sufitu (674 zł zostało, ok. 10,5 doby przy 64 zł/dz,
podział według udziału z 07–09.09).

| # | Decyzja | Rekomendacja | Koszt pozostawienia do sufitu |
|---|---|---|---:|
| 1 | **Próg werdyktu** — czy przyjmujemy 650 zł / ≤ 2 i 1 000 zł / ≤ 5 kontaktów jako wyrok na prospecting | przyjąć; po wyroku doładowanie tylko na `[RMKT]` | cały dalszy prospecting po 21.09 (ok. 1 500 zł / mies. przy 50 zł/dz) |
| 2 | **`[VID]`** 25 zł/dz — najwięcej wydaje, 0 kontaktów, najdroższe wejście (0,81 zł / sesja) | zostawić do progu pośredniego 15.09, bez zmian | ok. 270 zł |
| 3 | **`[POST]`** 10 zł/dz — cel „ruch", 0 kontaktów, w GA4 niemierzalny, 21% idzie w Stories bez wejść | wyłączyć | ok. 115 zł |
| 4 | **`[FOTO]`** 15 zł/dz — jedyny kontakt, najtańsze wejście (0,52 zł) | zostawić | ok. 175 zł |
| 5 | **`[RMKT]`** 12 zł/dz | zostawić, ocena po 400 zł | ok. 110 zł |

Poza wydatkami, bez dotykania reklam:

- **Liczenie kontaktu w narzędziach.** `recheck_start.py` i kolektor dashboardu szukają
  `contact_website` / `lead`, a Meta raportuje nasz kontakt jako `fb_pixel_custom`. Do poprawy
  po naszej stronie, zanim próg zacznie obowiązywać — inaczej miara z progu będzie zaniżona.
- **Przełączenie na optymalizację `Contact` — nie proponuję.** Zero własnych konwersji, zestaw
  utknąłby w uczeniu (hipoteza 1).
- WhatsApp jako cel reklamy obszedłby zgodę i przeglądarkę w aplikacji, ale został odrzucony
  07.09 — nie wracam do tematu.

## Jak odtworzyć odczyt

Skrypty robocze tej sesji leżały w scratchpadzie; wywołania wprost:

```
# Meta — insighty z oknami atrybucji
{act}/insights?level=campaign&time_range={"since":"2026-09-04","until":"2026-09-10"}
  &action_attribution_windows=["7d_click","1d_view","1d_click"]
  &fields=spend,impressions,reach,frequency,clicks,inline_link_clicks,outbound_clicks,actions
# Meta — miejsca emisji
{act}/insights?level=campaign&breakdowns=publisher_platform,platform_position,device_platform
# Piksel
{pixel}/stats?aggregation=event | match_keys&event=Contact | url&event=…
# RMKT — auta
{act}/insights?level=adset&breakdowns=product_id&filtering=[campaign.id = 120248991023540243]
# GTM — wersja live
accounts/6351095501/containers/250095450/versions:live   (tagi 27–31, consentSettings)
# GA4 — scripts/ga4_query.py
sessionSourceMedium | firstUserSourceMedium × eventName ∈ {click_phone, click_whatsapp, generate_lead}
```
