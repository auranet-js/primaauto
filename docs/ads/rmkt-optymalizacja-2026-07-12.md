# RMKT Dynamic Remarketing — recon + optymalizacja (2026-07-12)

> Kampania `[RMKT] Dynamic Remarketing — Model-huby` (23897599362), DISPLAY, Manual CPC
> Konto Ads 9506068500 (direct) · grupa 200662928841 · AssetSet 9118300013
> Poprzedni wątek: [DSA rework](dsa-rework-2026-07-12.md) (ten sam dzień)

---

## 1. Werdykt

Kampania jest **zdrowa** — nie generuje śmieciowego ruchu. Ale raportowane 21 konwersji było
zawyżone ~2×, a feed reklamował ceny, których nie ma. Naprawione w 4 krokach (wszystkie tego dnia).

**Realny koszt kontaktu: ~26 zł** (238 zł / ~9 unikalnych osób wg GA4), czyli **drugi najlepszy
kanał na koncie po Brandzie** — i rząd wielkości tańszy niż DSA (432 zł) czy SKAG-1 (611 zł).

---

## 2. Recon — co ustalono (KROK 0, wszystko z API)

### Kampania nie jest fraudem — dwa niezależne dowody

Podejrzenie było zasadne: **CTR 3,18%** na display to 4–6× powyżej normy (0,5–0,8%), a część
placementów miała CTR >100% (kliki bez zliczonej impresji).

1. **Zero aplikacji mobilnych.** 100% wyświetleń to typ `WEBSITE` (200/200 grup placementów).
   Ani jednego `MOBILE_APPLICATION`, zero MFA, zero YouTube-dla-dzieci.
2. **GA4 potwierdza realne zaangażowanie** — engagement rate **78,2%**, śr. **230 s**, **3,87** odsłon,
   bounce 21,8%. To **2. wynik na koncie po Brandzie** (81,8%), przed DSA (59,1%) i SKAG-1 (44,9%).
   Do tego 1081 sesji GA4 vs 1235 klików Ads = **87% dopasowania** (fat-finger clicks dałyby 40–50%).
3. **View-through conversions = 0.** Żadna konwersja nie jest doliczona „za obejrzenie banera".

Wysoki CTR ma prozaiczne wyjaśnienie: to remarketing — ci ludzie już byli na stronie.

### Ale „21 konwersji" ≠ 21 osób

Wszystkie 3 akcje konwersji mają `MANY_PER_CLICK` — **każdy klik w WhatsApp liczy się osobno**.

| Akcja | Ads liczy | GA4: zdarzeń | GA4: **unikalnych osób** |
|---|---|---|---|
| click_whatsapp | 14 | 12 | **5** |
| click_phone | 4 | 4 | **3** |
| generate_lead | 3 | 1 | **1** |
| **Razem** | **21** | **17** | **≈9** |

### Koszt kontaktu — całe konto tą samą miarą (30d)

| Kampania | Koszt | Osób (GA4) | Koszt/osobę |
|---|---|---|---|
| [Brand] | 146 zł | 14 | **10 zł** |
| **[RMKT]** | **238 zł** | **9** | **26 zł** |
| [SKAG-2] | 761 zł | 3 | 254 zł |
| [DSA] | 1 297 zł | 3 | 432 zł |
| [SKAG-1] | 611 zł | 1 | 611 zł |
| [Topic] | 630 zł | 0 | — |

> **Zastrzeżenie:** GA4 = last-click, więc kampanie górnego lejka (DSA, Topic) są **zaniżone** —
> zaczynają ścieżkę, a lead wpada później z organica/brandu. Ads (data-driven) daje DSA 5 konw.,
> Topic 5. Prawda pośrodku. Ale nawet z poprawką RMKT i Brand są bezkonkurencyjne.

### Mechanizm dynamic remarketingu — DZIAŁA (zweryfikowany end-to-end)

```
single (oferta)  →  dataLayer: listing.serie_id = "5150"
GTM (live v8)    →  tag "GAds - Dynamic Remarketing (offerdetail)" [type=sp, trigger CE - view_item]
                 →  dynx_itemid = {{DLV - serie_id}}, dynx_pagetype=offerdetail, dynx_totalvalue
feed AssetSet    →  wpis id=5150 (= serie term_id)  →  baner z tym autem
```

ID zgadzają się po obu stronach (`term_id` taksonomii `serie`). **Huby NIE firują dynx** (tylko single) —
to OK, bo matching i tak idzie po serie_id oglądanej oferty.

---

## 3. Co było zepsute i co naprawiono

### KROK 1 — feed (największa dźwignia)

Feed zbudowany **2026-06-02** i **nigdy nie odświeżony**. Skutki:

| Problem | Skala |
|---|---|
| **Nieaktualna cena** | **252 z 254** wpisów (99%) |
| Wpisy → hub z 0 ofert / martwy term (po T-190 fold, T-019 merge) | 58 |
| Huby z ofertami brakujące w feedzie | 18 |

Rozjazd cen — **225 zaniżało** (klient klika „od 118 tys.", widzi 154 tys. → wychodzi),
**27 zawyżało** (klient nigdy nie kliknie). Mediana 6%, ogon długi:

| Model | Baner | Realnie | Δ |
|---|---|---|---|
| BYD Yangwang U9 | 1 288 000 | 1 047 000 | −241 000 |
| GAC M8 | 278 000 | 147 000 | −131 000 |
| AITO M9 | 307 000 | 219 000 | −88 000 |
| Xingchi Bochi Venus | 390 000 | 488 000 | +98 000 |
| Zeekr 001 | 149 000 | 195 000 | +46 000 |

**Wykonano:** pełna wymiana feedu **312 → 272** wpisy (`DynamicCustomAsset` jest immutable →
refresh ceny = remove + create). Transakcja **atomowa** (`partialFailure=false`), 856 operacji.

Skrypt: `tmp/gads_rmkt_feed_rebuild_2026_07_12.py`
Źródło: `tmp/build-gads-hub-feed-2026-06-02.php` (READ-ONLY, czyta prod DB)

> **UWAGA — nie „naprawiaj" tego:** builder **celowo wyklucza marki nie-chińskie**
> (`volkswagen, volvo, nissan, mazda, audi, mg, smart, lotus, toyota, iveco, mini`) — spójnie
> z feedem DSA (tam też 0 takich). MG 7, Mazda CX-5, Nissan N7 **NIE są brakami w feedzie**.

### KROK 2 — placementy i urządzenia

100% ruchu to legalne polskie portale, ale zwrot skrajnie nierówny:

| Placement | Koszt | % budżetu | Klików | Konw. | Decyzja |
|---|---|---|---|---|---|
| wp.pl | 58,06 zł | 24,3% | 288 | **0,7** | **wykluczony** |
| onet.pl | 23,65 zł | 9,9% | 130 | **0,0** | **wykluczony** |
| interia.pl | 27,38 zł | 11,5% | 133 | 5,5 | zostaje |
| rmf24.pl | 2,02 zł | 0,8% | 12 | 3,0 | zostaje |
| moto.pl | 1,97 zł | 0,8% | 9 | 3,0 | zostaje |

wp.pl: przy CVR kampanii (1,7%) 288 klików powinno dać ~5 konw., dało 0,7 → **p<0,05**, nie szum.

**Urządzenia:** wszystkie 21 konw. z **MOBILE** (WhatsApp/telefon = akcje mobilne).
Desktop 27,30 zł / 0 konw., tablet 6,07 zł / 0, TV 0,85 zł / 0.

**Wykonano:** wykluczone `wp.pl` + `onet.pl`; `CONNECTED_TV` −100%; `DESKTOP` i `TABLET` **−50%**
(nie −100%: user może oglądać na desktopie, a zadzwonić z komórki).

Skrypt: `tmp/gads_rmkt_placements_2026_07_12.py`

### KROK 3 — kreacja

Było: 1 RDA, **bez logo**, 4 przypadkowe zdjęcia z WhatsAppa (`WhatsApp Image 2026-04-29…`),
copy obiecujące **„Pełna dokumentacja i rejestracja w cenie"** (poza modelem pośrednictwa!)
i „Zamów online, dostawa całą Polską".

**Wykonano** (update reklamy 811557389705 — zachowuje ID i historię; status: **APPROVED** od razu):

- **Nagłówki:** Sprowadzimy Twoje auto · Auto z Chin na zamówienie · Sprawdzamy przed zakupem ·
  Zobacz cenę i dostępność · Import aut z Chin
- **Nagłówek długi:** Auto, które oglądałeś - sprowadzimy je dla Ciebie. Sprawdzamy każde przed zakupem.
- **Opisy:** Sprowadzamy auta z Chin na indywidualne zamówienie. Każde sprawdzamy przed zakupem. /
  Wybierasz model, my zajmujemy się resztą - od zakupu w Chinach po odbiór w Polsce. /
  Setki modeli elektrycznych i hybrydowych. Zobacz aktualne ceny i dostępność.
- **Grafiki:** 6 par kadrów placowych z DSA (1.91:1 + 1:1) zamiast WhatsAppa
- **Logo:** 1:1 1200×1200 (asset `391090171360`)

Skrypt: `tmp/gads_rmkt_ad_2026_07_12.py`

#### Gotchy API (kosztowały iteracje)

1. `updateMask` **musi wskazywać liście** — `responsive_display_ad.long_headline` → błąd
   `FIELD_HAS_SUBFIELDS`. Poprawnie: `responsive_display_ad.long_headline.text`.
   Cały `responsive_display_ad` jako maska **też nie przejdzie** (ten sam błąd).
2. **RDA ma DWA pola logo:** `logo_images` (**4:1**) i `square_logo_images` (**1:1**).
   Logo kwadratowe w `logo_images` → `ASPECT_RATIO_NOT_ALLOWED`.
3. Logo **400×400 jest za małe** (`DIMENSIONS_NOT_ALLOWED`) → upscale do 1200×1200 (PHP GD, brak PIL na LVE).
4. **Logo poziomego nie mamy** — `primaauto-logo-szerokie.png` to 200×55 (3,64:1), Google wymaga
   4:1 przy min 512×128. **TODO: eksport logo 4:1 (np. 1200×300).**

### KROK 4 — budżet: NIE podniesiony (wbrew pierwotnemu planowi)

Plan zakładał 17 → 22 zł (5 zł zaparkowane z DSA). **Dane to obaliły:**

```
średnia dzienna: 12,29 zł z budżetu 17,00 zł  =  72% wykorzystania
rekord (09.07):  15,54 zł                     =  91%   ← nigdy nie dobiło limitu
```

Budżet **nie jest wąskim gardłem**. Podniesienie do 22 zł nie kupiłoby ani jednego wyświetlenia.
Co więcej — wykluczenie wp.pl + onet.pl zabrało **39% impresji**, więc wykorzystanie spadnie jeszcze
bardziej (prognoza 7–8 zł/dz).

**Prawdziwe ograniczenie:** rozmiar listy (~2000 osób display) × frequency cap 4/dz.

**Wykonano zamiast tego:** stawka grupy **0,31 → 0,45 zł** (CPC realny był 0,19 zł — przegrywaliśmy
aukcje na portalach, które konwertują). Budżet **zostaje 17 zł**, 5 zł z DSA **nadal wolne**.

---

## 4. Pytanie Janka: audiencje w SKAG-1/2 — ROZSTRZYGNIĘTE: NIE

Pomysł: wykluczyć obecnych visitorów ze SKAG-1/2 (search tylko dla nowych).

**Dane (GA4, 30d, Paid Search, per newVsReturning):**

| | zdarzeń | osób | udział |
|---|---|---|---|
| Nowi | 15 | 13 | 52% |
| **Powracający** | **14** | **11** | **48%** |

Powracający = **48% leadów z Paid Search**. Wykluczenie wycięłoby połowę efektu kampanii,
których i tak nie stać na straty. Przy zakupie auta za 200–300 tys. cykl decyzyjny jest długi —
człowiek, który **wraca do Google i znowu szuka**, ma świeżą intencję i jest najbliżej kontaktu.
Display go nie zastąpi (łapie go, gdy czyta wiadomości; search — gdy o aucie myśli).

**Blokada techniczna (i tak zamyka temat):** listy dla search wymagają **1000 członków**.
Nasze: `All visitors` — **740**, `Wszyscy odwiedzający 90 dni` — **550**.
Nawet wariant „obserwacja z modyfikatorem −20%" **nie ruszyłby z miejsca**.
Lista converters (do ew. wykluczenia) ma **0** członków.

**Wracamy do tematu, gdy listy urosną powyżej 1000.**

---

## 5. Stan końcowy kampanii

| | przed | po |
|---|---|---|
| Wpisów w feedzie | 312 | **272** |
| — z nieaktualną ceną | 252 | **0** |
| — prowadzących na pusty hub | 58 | **0** |
| — brakujących modeli | 18 | **0** |
| Wykluczenia placementów | 0 | **wp.pl, onet.pl** |
| Modyfikatory urządzeń | brak | **desktop −50%, tablet −50%, TV −100%** |
| Reklama | 4× WhatsApp, bez logo | **6 par kadrów placowych + logo 1:1** |
| Copy | „rejestracja w cenie" | **zamówienie + weryfikacja** |
| Stawka | 0,31 zł | **0,45 zł** |
| Budżet | 17 zł | **17 zł** (zapas 28%) |
| Frequency cap | 4/dz | 4/dz (bez zmian — 120 imp/mc na osobę to już dużo) |

**Backupy (rollback):** `~/backups/primaauto/2026-07-12-rmkt/`
`feed-before.json` · `criteria-before.json` · `ad-before.json` · `adgroup-bid-before.json` + `*-apply-response.json`

---

## 6. Recheck D+7 (2026-07-19)

1. **Czy stawka 0,45 wykorzystała zapas budżetu?** Jeśli spend dobił do ~17 zł/dz → **wtedy** budżet 22 zł.
   Jeśli nadal ~8–10 zł → bottleneckiem jest lista, budżetu NIE ruszać.
2. **Czy wykluczenie wp.pl/onet nie zabiło zasięgu** — sprawdzić, gdzie przeszły impresje
   (`group_placement_view`); czy interia/moto.pl/rmf24 dostały więcej.
3. **Czy nowa kreacja + poprawione ceny podniosły CVR** — porównać konwersje/klik przed i po.
   Uwaga: mierzyć **unikalne osoby w GA4**, nie `all_conversions` (MANY_PER_CLICK zawyża).
4. **Odświeżanie feedu — ZAUTOMATYZOWANE (2026-07-12).** Cron tygodniowy, niedziela 06:00:
   ```
   0 6 * * 0 /bin/bash -lc '/home/host476470/projekty/primaauto/scripts/refresh-rmkt-feed.sh' \
     >> /home/host476470/.claude/rmkt-feed-refresh.log 2>&1
   ```
   Łańcuch: `scripts/build-gads-hub-feed.php` (READ-ONLY z prod DB) → `scripts/gads-rmkt-feed-refresh.py --apply`.
   **Bezpieczniki** (cron działa bez nadzoru): abort gdy feed < 200 wpisów (padnięta DB / pusty import),
   abort gdy wymiana usunęłaby >40% feedu, transakcja atomowa, dump before do `~/backups/primaauto/rmkt-feed/<data>/`.
   Log: `~/.claude/rmkt-feed-refresh.log`. Backup crontaba: `~/backups/crontab/`.

## 7. Otwarte / TODO

- [ ] **Logo 4:1** (min 512×128, opt. 1200×300) — eksport od Janka; wtedy `logo_images` w RDA
- [x] ~~Automatyczne odświeżanie feedu (cron)~~ — **ZROBIONE 2026-07-12**, niedziela 06:00
- [ ] Listy remarketingowe < 1000 dla search — wrócić do tematu audiencji w SKAG, gdy urosną
- [ ] Frequency cap 4/dz — zostawiony; przy wzroście listy rozważyć ponownie


---

## 8. Sesja 2026-07-12, część druga — SKAG tylko dla nowych, pomiar, wykluczenie własnego ruchu

### 8.1 KOREKTA wcześniejszej rekomendacji: powracających ze SKAG-1/2 JEDNAK wykluczamy

W sekcji 4 napisałem „NIE wykluczać" — **to było błędne**, bo agregowałem cały Paid Search.
Rozbicie per kampania pokazuje coś innego:

| Kampania | Leady od NOWYCH | Leady od POWRACAJĄCYCH |
|---|---|---|
| [Brand] | 7 | **11** |
| [SKAG-1] | 1 | **0** |
| [SKAG-2] | 4 | **0** |
| [DSA] | 2 | 2 |

**Powracający w SKAG-1/2 dają ZERO leadów.** Te 48% powracających z Paid Search siedzi w **Brandzie**
(którego nie ruszamy). Wykluczenie ich ze SKAG kosztuje 0 leadów.

Argument dobijający (Janek): **SKAG-1/2 tracą 90% impresji przez BUDŻET** (IS=10%).
Każdy klik powracającego (0 konwersji) zabiera budżet nowemu (który konwertuje). Plus: SKAG
przyprowadzający wyłącznie nowych **szybciej rozbudowuje listę remarketingową** → więcej paliwa dla RMKT.

**Wykonano:** wykluczone 4 listy na SKAG-1 i SKAG-2 (`All visitors` 9372622741, `Wszyscy odwiedzający`
9414602400, `All Users` 9371902633 + nowa suma). Usunięto pozytywną (obserwacyjną) 9414602400.

**Próg 1000 — obejście:** listy pojedynczo mają dla Search 740 / 550 / 970 (poniżej progu).
Utworzono LOGICAL user list **9430077897** „Wszyscy odwiedzający (suma) — wykluczenie SKAG" (ANY z trzech).
**TODO D+1: sprawdzić, czy przebiła 1000** (Google przelicza do 24h).

**Dlaczego listy < 1000 mimo 4407 użytkowników/30d:** Display łapie 1800 (~41% userów), ale **Search
tylko 740 (~17%)** — w wyszukiwarce Google musi rozpoznać zalogowane konto Google, w Display wystarczy
identyfikator przeglądarki. Plus Consent Mode odcina brak zgody. Lista Display **jest** > 1000.

Skrypt: `tmp/gads_skag_exclude_visitors_2026_07_12.py`

### 8.2 Pomiar konwersji — naprawiony u źródła

**Problem 1: liczenie.** Wszystkie key eventy w GA4 miały `ONCE_PER_EVENT` → user klikający WhatsApp 3×
w sesji = 3 konwersje w Ads (14 konw. = 5 realnych osób). CPA zawyżony ~2× we **wszystkich** kampaniach.

`counting_type` w Google Ads jest **IMMUTABLE** dla akcji `GOOGLE_ANALYTICS_4_CUSTOM` — naprawa musi iść
przez **GA4 Admin API** (`keyEvent.countingMethod`).
**Wykonano:** `generate_lead`, `click_whatsapp`, `click_phone` → **ONCE_PER_SESSION**.
NIE ruszono `purchase` (e-commerce, wiele zakupów = poprawnie wiele konwersji).

**Problem 2: wartość.** `generate_lead` miał wartość = **cenę auta** (tag wysyła `ecommerce.value`):
4 leady = 980 000 zł „wartości konwersji" (śr. 245 tys./lead). Lead ≠ sprzedaż. Przy Manual CPC tylko
zaburzało raporty, ale po przejściu na smart bidding Google optymalizowałby pod tę fikcję.
**Wykonano:** wszystkie 3 akcje → `always_use_default_value=true`, `default_value=1`.

**UWAGA: zmiany NIE są retroaktywne.** Porównania przed/po tylko na danych od 2026-07-12.

Skrypty: `tmp/gads_conversion_fix_2026_07_12.py` (Ads, wartości), `tmp/ga4_counting_fix_2026_07_12.py` (GA4, liczenie).

### 8.3 Wykluczenie własnego ruchu (Janek + Ruslan)

**IP z logów (736k linii, 14 archiwów):**

| IP | Geo (wg ip-api) | Operator | Kto | Stałe? |
|---|---|---|---|---|
| 85.28.166.159 | Tarnów | ZETO S.A. | **Janek** | TAK |
| 185.227.190.89 | **Radgoszcz** | lokalny ISP | **Janek** | TAK |
| 109.243.128.95 | „Chojnice" | P4 mobile | Ruslan? (10 045 wejść admin) | NIE |
| 77.222.230.137 | „Zabrze" | Vectra **CGNAT** | Ruslan? (3 055 wejść admin) | NIE |

> **Gotcha:** geolokalizacja IP mobilnych i CGNAT **kłamie** — adres jest przypisany do węzła operatora,
> nie abonenta. Stąd „Zabrze"/„Chojnice" dla kogoś z Rzeszowa. **Nie identyfikuj ludzi po geoIP.**
> CGNAT dodatkowo **współdzieli adres z obcymi ludźmi** → wykluczenie w Ads odcięłoby realnych klientów.

**Wykonano — dwutorowo:**

1. **GTM: blokada tagów dla zalogowanych do WP** (odporne na zmianę IP — działa dla Ruslana):
   - zmienna JS `Is WP Logged In` (34) — regex na ciasteczku `wordpress_logged_in_<hash>`
   - triggery-wyjątki: `BLOCK - WP zalogowany (pageview)` (35) i `(custom events)` (36, regex `.*`)
   - podpięte jako `blockingTriggerId` do **wszystkich 11 tagów** (GA4, GADS, Meta Pixel ×5, RMKT dynamic)
   - **opublikowane: wersja 9 LIVE**
2. **Google Ads: wykluczenie IP** — 2 stałe adresy Janka × 6 kampanii ENABLED = 12 wykluczeń.
   IP Ruslana **świadomie pominięte** (mobile/CGNAT — nieskuteczne i ryzykowne; jego łapie blokada z pkt 1).

Skrypt: `tmp/gtm_block_logged_in_2026_07_12.py`

### 8.4 Consent — sprawdzone, jeden realny problem

**Consent Mode v2 DZIAŁA** — `gtag('consent', 'default')` + `update`, wszystkie 4 sygnały
(`ad_storage`, `ad_user_data`, `ad_personalization`, `analytics_storage`), Complianz podpięty.
Ruch „Unassigned" w GA4 = 75 sesji (0,9%) — nieistotne.

**ALE — 70 ciasteczek jest NIESKLASYFIKOWANYCH w Complianz**, w tym kluczowe:
`_gcl_au` (linker konwersji Google Ads), `_gcl_ls`, `_ga`, `_ga_F1NCC3D2HZ`, `_fbp` (Meta).
Mają `serviceID = NULL` → brak przypisania do usługi i kategorii. Skategoryzowane są tylko 4 (jako „utility").

To **nie blokuje zbierania list** (to robi Consent Mode, który działa), ale jest **problemem zgodności
z RODO**: baner i polityka prywatności nie wymieniają, co realnie zapisujemy.
**TODO: uruchomić skan Complianz i przypisać usługi/kategorie** (Google Analytics → statistics,
Google Ads/Meta → marketing).

## 9. TODO / recheck (aktualizacja)

- [ ] **D+1 (2026-07-13):** czy lista sumaryczna 9430077897 przebiła 1000 członków dla Search
- [ ] **D+1:** czy konwersje przestały się dublować (click_whatsapp: zdarzenia GA4 ≈ osoby)
- [ ] **D+7 (2026-07-19):** czy stawka RMKT 0,45 wykorzystała zapas budżetu → dopiero wtedy budżet 22 zł
- [ ] **D+7:** czy SKAG-1/2 po wykluczeniu powracających mają więcej leadów od nowych (uwolniony budżet)
- [ ] **Complianz:** skan + kategoryzacja 70 ciasteczek (RODO)
- [ ] **GA4 internal traffic filter** — Google NIE wystawia tego w API, tylko UI:
      Administracja → Strumienie danych → Prima-Auto.pl → Ustawienia tagu → Zdefiniuj ruch wewnętrzny
      → dodaj 85.28.166.159 i 185.227.190.89; potem Ustawienia danych → Filtry danych → Internal Traffic → Aktywny
- [ ] **Logo 4:1** (min 512×128) — eksport od Janka
