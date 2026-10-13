# Recheck po zmianach z 04.10 — Google Ads, Meta, rolki

> Przypomnienie z 11.10, wykonane 13.10. Prompt: `docs/przypomnienia/2026-10-11-recheck-po-zmianach-04-10.md`.
> **Tylko odczyt.** W kampaniach, feedach i GTM nic nie zmieniłem.
> Okno: pełne doby **05–12.10** (8 dób). Bazy porównawcze: Meta **28.09–03.10** (6 dób), DSA **04.09–03.10** (30 dób), atrybucja GA4 przed błędem GTM **24–31.08**.

## Najważniejsze

1. **`gclid` wrócił, ale nie w pełni.** Liczba sesji `google / cpc` w GA4 wzrosła z 27–43 na dobę (28.09–03.10) do **65–95 na dobę** (średnio 81). [RMKT] i Search mają znów normalny udział sesji w kliknięciach. **[DG] nadal nie.** Po stronie GA4 widać 4% jego kliknięć, a przed błędem było to 12%.
2. **Kontakty z Ads i z GA4 się zgadzają:** Ads ma 12 konwersji, GA4 11 kontaktów z `google / cpc`.
3. **Cron feedu [DSA] chodzi:** biegi 07.10, 10.10 i 13.10 przeszły bez `BLAD`, feed ma 186–188 ofert.
4. **[DSA] wydaje tyle samo co przed zmianami (15,59 zł/dz wobec 15,14), ale wydatek przesunął się zgodnie z korektami.** Na 25–34 wydano 0 zł, największa grupa to 45–54 (38% wydatku). Godziny 9–17 to 66% wydatku, 7–9 tylko 3%. Jeden kontakt w 8 dniach (telefon 08.10). Po tygodniu to obserwacja, nie ocena.
5. **Meta [KAT] po wykluczeniach:** dzienny zasięg się podwoił (≈1 400 → ≈3 000 osób), a dzienna częstotliwość spadła z 1,6–1,9 do 1,40–1,54. W zwykłe dni [KAT] wydaje średnio **45,25 zł**. Drożej wychodzą jednak wejścia: **0,26 zł/LPV** wobec 0,17 zł, a 0,35 zł na sesję GA4 wobec 0,28 zł. Kontakty w GA4: 4 (wcześniej 3).
6. **Meta [RMKT] bez rozszerzania listy:** w zwykłe dni wydaje średnio **14,50 zł** z 15 zł, ale wydatek spada (16,28 → 11,53 zł w ciągu 05–10.10). Dzienna częstotliwość wzrosła z 1,3–1,6 do 2,05–2,25. Koszt sesji GA4 spadł z 0,34 do **0,23 zł**, a kontakty w GA4 wzrosły z 0 do **4**.
7. **11.10 Meta prawie nie emitowała (1,15 zł).** Konto było w statusie „Wymagana płatność” od 11.10 00:42 do 12.10 00:38 czasu polskiego, czyli przez całą dobę 11.10. Potem przeszły obciążenia karty i status wrócił na „Aktywne”. To **nie** był limit wydatków. 12.10 Meta nadrobiła: [KAT] 64,06 zł, [RMKT] 24,03 zł.
8. **Rolki na FB:** w oknie 05–12.10 są 4 rolki. Tylko pierwsza (Zeekr 8X, 07.10 o 21:00) weszła w okno 20–21. Wyświetlenia: 86–125. TikToka nie odczytam, bo konto jest zwykłe, bez API.

## 1. `gclid` — czy Google Ads wróciło do GA4

Sesje `google / cpc` w GA4 per doba (GTM v16 od 04.10):

| 28.09 | 29.09 | 30.09 | 01.10 | 02.10 | 03.10 | 04.10 | **05.10** | **06.10** | **07.10** | **08.10** | **09.10** | **10.10** | **11.10** | **12.10** |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| 27 | 36 | 38 | 43 | 28 | 35 | 45 | **79** | **87** | **74** | **91** | **73** | **86** | **95** | **65** |

Przed błędem (do 07.09) było 103–133 sesji na dobę. Dziś jest mniej, więc porównałem kliknięcia Ads z sesjami GA4 per kampania. Tydzień 24–31.08 to stan przed błędem:

| Kampania | Kliknięcia Ads 05–12.10 | Sesje GA4 `google / cpc` | % | Ten sam % 24–31.08 |
|---|---|---|---|---|
| [RMKT] Model-huby | 498 | 353 | 71% | 82% |
| [Brand] | 267 | 165 | 62% | 59% |
| [Topic] | 110 | 46 | 42% | 32% |
| [DSA] | 156 | 56 | 36% | 21% |
| **[DG]** | 551 | **24** | **4%** | 12% |

Search i [RMKT] wróciły do poziomu sprzed błędu albo go przekraczają. Mniejsza łączna liczba sesji wynika z tego, że jest mniej kliknięć: 1 582 teraz wobec 1 770 w tygodniu sierpniowym, w tym [DG] 551 wobec 879. **[DG] dalej trafia do GA4 słabo:** 4% obecnie wobec 12% w sierpniu. Co dzień zostaje też 3–15 sesji `youtube.com / referral`. Przyczyny tu nie diagnozowałem, odnotowuję sam fakt. Sesje `(not set)` w oknie: 626, czyli ok. 7,5% ruchu.

**Kontakty Ads vs GA4 (05–12.10):**

| Kampania | Ads (konwersje) | GA4 `google / cpc` |
|---|---|---|
| [Brand] | 2 (telefon + formularz) | 2 (telefon + `generate_lead`) |
| [Topic] | 1 telefon | 1 telefon |
| [DSA] | 1 telefon | 1 telefon |
| [RMKT] | 3 WhatsApp | 3 WhatsApp |
| [DG] | 5 WhatsApp | 4 WhatsApp |
| **Razem** | **12** | **11** |

Zdarzenia `click` nie liczę, bo to linki wychodzące, a nie kontakt.

## 2. Cron feedu [DSA]

Fragment `~/.claude/dsa-offer-feed.log`; wpis `BLAD` w całym logu nie występuje ani razu:

| Bieg | Feed | Żywe bez zmian | Usunięte | Dodane | Wynik |
|---|---|---|---|---|---|
| 07.10 06:15 | 188 | 181 | 7 | 6 | usunięcie OK, podpięcie OK |
| 10.10 06:15 | 187 | 168 | 19 | 20 | OK / OK |
| 13.10 06:15 | 188 | 164 | 24 | 22 | OK / OK |

Biegu kontrolnego `dsa-offer-feed-refresh.py` w trybie dry-run nie wykonałem, bo sesja zablokowała to uprawnieniem. Dzisiejszy bieg z crona (13.10) przeszedł poprawnie, co wystarcza za kontrolę. Feed rotuje coraz szybciej: przy każdym biegu podmienia się 4%, 10% i 13% ofert.

## 3. [DSA] po zmianach profilu

| Okno | Dni | Wydatek | zł/dz | Kliknięcia | Wyświetlenia/dz | CPC | Konwersje Ads |
|---|---|---|---|---|---|---|---|
| 04.09–03.10 | 30 | 454,20 zł | 15,14 | 580 | 230 | 0,78 zł | 4 |
| **05–12.10** | 8 | **124,75 zł** | **15,59** | 156 | 228 | 0,80 zł | **1** (telefon 08.10) |

Dzienny wydatek 05–12.10 wynosi 14,82–16,22 zł. **Budżet wyczerpuje się każdego dnia**, więc korekty stawek nie obniżają kosztu, tylko przesuwają go między grupami.

**Wiek** (korekty: 45–54 ×1,30, 65+ ×1,15, 55–64 ×1,10, 35–44 ×0,80, 25–34 wykluczone):

| Wiek | Wydatek | % | Kliknięcia | Konwersje |
|---|---|---|---|---|
| 45–54 | 46,90 zł | 38% | 52 | 1 |
| 65+ | 23,31 zł | 19% | 29 | 0 |
| 55–64 | 21,22 zł | 17% | 26 | 0 |
| nieokreślony | 28,16 zł | 23% | 40 | 0 |
| 35–44 | 5,16 zł | 4% | 9 | 0 |
| 25–34 | 0 zł | 0% | 0 | 0 |
| 18–24 | 0 zł | 0% | 0 | 0 |

**Godziny** (dni robocze: 7–9 ×0,7, 9–17 ×1,2, 17–19 ×1,0, 19–22 ×0,8):

| Pasmo | Wydatek | % |
|---|---|---|
| 7–9 | 3,35 zł | 3% |
| 9–17 | 82,23 zł | 66% |
| 17–19 | 18,90 zł | 15% |
| 19–22 | 20,27 zł | 16% |

Dni tygodnia: niedziela 15,46 zł, sobota 15,94 zł. Pozostałe dni mają po ok. 15 zł; poniedziałek wynosi 31,44 zł, bo w oknie są dwa poniedziałki. Korekty ×0,9 i ×0,7 w weekend **nie zmniejszają wydatku**, bo budżet i tak się wyczerpuje.

**Region:** zapytanie `geographic_view` dla okna zwróciło pusty wynik, więc rozkładu na województwa nie mam.

**Reklamy:** [DSA] dalej działa na jednej reklamie. Druga, 822835403980, ma `DISAPPROVED` (`GOVERNMENT_DOCUMENTS`) od ~20.09, tak jak w rechecku z 04.10. Ten sam stan mają [Brand] 806602181652 i [DG] Exeed VX 818269813589. Nowych odrzuceń nie ma.

**Pozostałe kampanie Ads (same liczby):**

| Kampania | 05–12.10 zł | zł/dz | Kliknięcia | Konwersje | zł/dz 28.09–03.10 |
|---|---|---|---|---|---|
| [DG] | 314,59 | 39,32 | 551 | 5 | 33,48 |
| [Topic] | 252,32 | 31,54 | 110 | 1 | 42,95 |
| [RMKT] Model-huby | 138,85 | 17,36 | 498 | 3 | 16,34 |
| [DSA] | 124,75 | 15,59 | 156 | 1 | 11,46 |
| [Brand] | 67,26 | 8,41 | 267 | 2 | 9,09 |

Dla [Topic] i [Brand] podaję same liczby, bez rekomendacji.

## 4. Meta — tabela per reklama

Żywe są dwie reklamy. [FOTO] jest zapauzowany od 04.10 i w oknie wydał 0 zł. Zasięg i częstotliwość w tabeli dotyczą całego okna, a nie doby. „Contact (piksel)” to zdarzenie `fb_pixel_custom`. Kontakty GA4 liczę jako `click_phone` + `click_whatsapp` z UTM `facebook / paid_social`.

| Reklama | Okno | Wydane | Kliknięcia w link | LPV | zł/LPV | Sesje GA4 | zł/sesja GA4 | Zasięg | Częst. | Contact (piksel) | Kontakty GA4 |
|---|---|---|---|---|---|---|---|---|---|---|---|
| **[KAT] Oferta — nowi odbiorcy** | 28.09–03.10 | 207,21 zł | 2 095 | 1 195 | 0,17 | 752 | 0,28 | 4 150 | 3,73 | 3 | 3 (2 WA + 1 tel.) |
| | **05–12.10** | **336,38 zł** | 3 675 | 1 283 | **0,26** | 949 | **0,35** | **12 457** | **2,72** | 4 | **4** (3 WA + 1 tel.) |
| **[RMKT] Oglądane — 30 dni** | 28.09–03.10 | 86,64 zł | 602 | 410 | 0,21 | 252 | 0,34 | 1 763 | 2,42 | 1 | 0 |
| | **05–12.10** | **111,33 zł** | 1 343 | 1 198 | **0,09** | 490 | **0,23** | 1 305 | **6,30** | 4 | **4** (3 WA + 1 tel.) |
| [FOTO] kadr 1 + kadr 2 | 28.09–03.10 | 89,06 zł | 274 | 75 | 1,19 | 111 | 0,80 | 2 744 | 1,89 | 0 | 0 |
| | 05–12.10 | 0 zł | — | — | — | 18 (resztki) | — | — | — | 0 | 0 |

**Trend dzienny (wydane zł / zasięg / częstotliwość dzienna):**

| Doba | [KAT] | [RMKT] |
|---|---|---|
| 28.09–03.10 (zakres) | 32,18–36,86 / 1 256–1 830 / 1,60–1,88 | 10,74–18,08 / 313–624 / 1,33–1,61 |
| 04.10 (dzień zmian) | 40,02 / 1 874 / 1,78 | 17,89 / 609 / 1,61 |
| 05.10 | 45,26 / 3 214 / 1,47 | 16,28 / 512 / 2,25 |
| 06.10 | 45,88 / 2 816 / 1,54 | 15,31 / 465 / 2,17 |
| 07.10 | 51,56 / 3 432 / 1,44 | 15,67 / 513 / 2,18 |
| 08.10 | 49,65 / 3 294 / 1,43 | 14,36 / 502 / 2,22 |
| 09.10 | 41,19 / 2 950 / 1,48 | 13,95 / 499 / 2,20 |
| 10.10 | 37,83 / 2 782 / 1,46 | 11,53 / 466 / 2,05 |
| 11.10 | 0,95 / 86 / 1,03 | 0,20 / 16 / 1,00 |
| 12.10 | 64,06 / 4 752 / 1,40 | 24,03 / 840 / 2,11 |

Odpowiedzi na pytania z przypomnienia:
- **Czy częstotliwość [KAT] spadła po wykluczeniach?** Tak, dzienna spadła z 1,6–1,9 do 1,40–1,54, a dzienny zasięg się podwoił. Odbiorcy są teraz faktycznie nowi.
- **Czy [KAT] wydaje 45 zł?** Tak, średnio 45,25 zł w dniach 05–10.10. Nadwyżka 12.10 to odrabianie po przestoju 11.10.
- **Czy [RMKT] po wyłączeniu rozszerzania wydaje 15 zł?** Prawie: średnio 14,50 zł, ale wydatek spada (16,28 → 11,53 zł). Lista „Oglądający oferty — 30 dni” jest wąska (1 305 osób w 8 dni), więc częstotliwość urosła do ok. 2,2 na dobę.
- **Koszt wejścia [KAT] wzrósł** (0,17 → 0,26 zł/LPV). Mniej kliknięć kończy się wczytaniem strony: 35% LPV z kliknięć wobec 57% wcześniej. Nowi odbiorcy są mniej zaangażowani niż powracający, których wykluczenie z [KAT] usunęło. W [RMKT] jest odwrotnie: 0,09 zł/LPV.

**Przestój 11.10.** Dziennik konta (`/activities`) pokazuje, że 10.10 o 22:42 UTC status zmienił się z „Aktywne” na „Wymagana płatność”. 11.10 o 22:37–22:38 UTC przeszły cztery obciążenia (39,06 + 78,12 + 156,24 + 39,12 zł) i status wrócił na „Aktywne”. Wcześniej, 10.10 o 22:57 UTC, przeszły dwa obciążenia (62,50 + 125,00 zł). Na dziś konto ma status aktywny (`account_status` 1).

## 5. Rolki Ruslana i Andrzeja na FB (dopisek z 07.10)

Plan był taki: 3 nowe filmy jako rolki, po jednej dziennie (08.10, 09.10, piątek albo weekend), o 20–21. Na Stronie (Graph API `video_reels`, pole `views`) w oknie są 4 rolki:

| Publikacja (czas PL) | Rolka | Długość | Wyświetlenia (13.10) | Okno 20–21? |
|---|---|---|---|---|
| 07.10 21:00 | Zeekr 8X DAWN | 24 s | 116 | tak |
| 08.10 18:20 | Lynk & Co 900 2.0T Ultra | 49 s | 86 | nie |
| 09.10 15:40 | Li Auto L8 | 18 s | 93 | nie |
| 10.10 11:24 | BYD Leopard 7 Ultra | 30 s | 125 | nie |

Oprócz rolek na Stronie są posty zdjęciowe z ofertami (05.10, 06.10, 09.10, 10.10) i udostępniona relacja (10.10).

**Ograniczenia odczytu.** Zasięgu ani wskaźników zaangażowania nie mam, bo `video_insights` i `insights` dla tych obiektów odrzucają metryki, a reakcje wymagają `pages_read_user_content`. Same wyświetlenia (86–125) są niskie i zbliżone do siebie, więc z czterech rolek nie da się ustalić, która kolejność albo pora działa najlepiej. **TikToka nie odczytam**: konto jest zwykłe, bez API (decyzja z 07.10), więc dane może podać tylko Ruslan.

**Kreacje [VID] z nowych filmów nie zostały przygotowane.** Ten odczyt ich nie obejmował.

## Do decyzji Janka

1. **[VID] z nowych filmów:** czy budujemy kreacje i z których filmów. Po wyświetleniach organicznych najmocniejsze są Leopard 7 (125) i Zeekr 8X (116), ale różnice są małe. Uruchomienie oznacza zmianę na koncie, więc wymaga quizu.
2. **Przestój płatności 10–11.10 (karta Mastercard \*3357):** warto sprawdzić, dlaczego Meta zażądała płatności. To kwestia rozliczeń konta, nie limitu wydatków.

Bez decyzji, do obserwacji przy następnym rechecku:
- [DG] w GA4: 4% kliknięć jako sesje.
- [KAT]: koszt LPV po wykluczeniach.
- [RMKT]: spadający wydatek przy wąskiej liście.

## Dowody

- GAQL (read-only, `api_version` z `ads-config.json`): `campaign` × `segments.date` 04.09–12.10, `age_range_view`, `segments.hour`, `segments.day_of_week`, `segments.conversion_action_name`, `ad_group_ad.policy_summary` (ENABLED), `geographic_view` (pusty wynik); kliknięcia 24–31.08 jako punkt odniesienia.
- GA4 Data API (534017542): `date × sessionSourceMedium`, `sessionCampaignName × sessionSourceMedium`, `eventName` (click_phone, click_whatsapp, generate_lead, form_submit) × źródło × kampania w dwóch oknach, `sessionManualAdContent` dla `facebook / paid_social`; `google / cpc` per kampania 24–31.08.
- Meta Graph v25: `insights` level=ad i adset, okna 28.09–03.10 i 05–12.10; adset × `time_increment=1`; `act/?fields=account_status,…`; `act/activities` od 09.10; `PAGE/video_reels`, `PAGE/published_posts`, `{video}?fields=views`.
- `~/.claude/dsa-offer-feed.log` (biegi 07.10, 10.10, 13.10; `grep -c BLAD` = 0).

## Dodatek 13.10 — [KAT]: dlaczego tylko 35% kliknięć to LPV

Pytanie Janka: czy [KAT] gubi ludzi między kliknięciem a stroną. Odczyt bez zmian w kampanii.

- **Miejsca i urządzenia nie są przyczyną.** 05–12.10: FB feed 329 zł / 3 594 kliknięć / LPV 35%, IG feed 7 zł / 81 / 28%. Android 36% (wcześniej 56%), iPhone 34% (wcześniej 60%) — spadek równy wszędzie.
- **Logi serwera (prawda bez piksela i zgody):** żądania z `utm_medium=paid_social` + `[KAT]` w URL, bez crawlera `facebookexternalhit`, 05–12.10 = **3 793** (FB in-app 2 398 FB_IAB + 1 118 FBAN, Instagram 76, reszta przeglądarki); unikalnych IP 2 008. Kliknięć w link wg Mety: **3 675**. Praktycznie **każde kliknięcie dochodzi do serwera**. Statusy: 200 = 3 741, 301 = 54 (zrotowane oferty → hub), 404 = 1. 429 dostaje wyłącznie crawler FB (89), ludzie nie.
- **Wniosek:** „strata” 65% to luka pomiaru, nie ucieczka. LPV (1 283) i sesje GA4 (949) widzą ok. 1/3 i 1/4 realnych wejść. Najbardziej prawdopodobna przyczyna: nowi odbiorcy nie mają jeszcze zgody na cookies, a piksel i GA4 czekają na zgodę; przed 04.10 w [KAT] byli też powracający ze zgodą (stąd 57%). Hipoteza zgody niezweryfikowana bezpośrednio (brak odczytu odsetka akceptacji banera).
- **Konsekwencje:** realny koszt wejścia [KAT] ≈ 336,38 zł / 3 793 = **0,09 zł**, nie 0,26. Porównania [KAT] vs [RMKT] po LPV/GA4 faworyzują [RMKT] (publiczność ze zgodą). Kontakty z [KAT] w GA4 i pikselu (po 4) też są zaniżone. W kampanii nie ma czego poprawiać.
