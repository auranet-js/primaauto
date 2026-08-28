# Meta Ads — plan kampanii, cele, budżety, rotacja

> **Ustalone 2026-08-28** w sesji z Jankiem. Cały stan poniżej sprawdzony wywołaniami API
> tego samego dnia — nic nie jest przepisane z wcześniejszych notatek.
>
> **Korekta wieczorna 28.08.** Przegląd modułowy pod pismo zamykające obalił dwie tezy
> z porannej wersji: grupy odbiorców z piksela i zaangażowania **nie są zablokowane**
> (sekcja 5), a regulamin pikseli **nie dotyka Conversions API** (sekcja 6). Doszły trzy
> nowe prośby: weryfikacja domeny, limit wydatków wraz z rolą `MANAGE`, uprawnienia
> do statystyk Strony.
> Google Ads ma osobny dokument: `docs/ads/mapa-kampanii.md`.

---

## 1. Cele — jedno źródło prawdy dla obu systemów

Konwersje przenosimy 1:1 z Google Ads (konto `9506068500`), gdzie kampania stoi od miesięcy.
Cztery akcje główne, wszystkie w kategorii kontaktu:

| Cel | Akcja w Ads | Zdarzenie GA4 / piksela | Konwersje w Ads / 30 dni |
|---|---|---|---|
| Klik w telefon na stronie | `Prima-Auto.pl (web) click_phone` | `click_phone` → piksel `Contact` | **34,0** |
| Klik w WhatsApp na stronie | `Prima-Auto.pl (web) click_whatsapp` | `click_whatsapp` → piksel `Contact` | **43,3** |
| Formularz zamówienia | `Prima-Auto.pl (web) generate_lead` | `generate_lead` → piksel `Lead` | **0** |
| „Zadzwoń" z samej reklamy | `Clicks to call` (Google-hosted) | — | **0** |

**Dwa cele nie dowożą.** W GA4 `generate_lead` to 12 zdarzeń na 30 dni z całej witryny,
z płatnego wyszukiwania 1. Kontakt idzie telefonem i WhatsAppem, w większości z organiku
(57 i 52 ze 100 i 108). Formularz nie jest kanałem, na którym da się optymalizować.

Trzy akcje „Local actions" mają w Ads `primary_for_goal: true`, choć nie są naszymi
konwersjami. Zerowe, więc dziś nie szkodzą — do posprzątania przy okazji.

### Co to znaczy dla optymalizacji w Meta

Piksel `1634147041766916` za 7 dni (21–28.08), cała witryna:

```
PageView      14 364
ViewContent    3 343
Contact           38
Lead               4
```

Meta potrzebuje ~50 konwersji tygodniowo **na jeden zestaw reklam**, żeby wyjść z fazy uczenia.
Przy 25 zł/dzień z samej Mety `Contact` tego progu nie dobije.

**Decyzja:** start na `ViewContent`, przejście na `Contact` dopiero gdy Meta zacznie dowozić
własny wolumen. `Lead` jako cel optymalizacyjny odpada na tym etapie — zostaje jako miernik.

---

## 2. Struktura kampanii i budżety

```
[VID]  Prospecting — wideo z sesji                    25 zł/dzień
       cel: OUTCOME_LEADS, optymalizacja ViewContent
       odbiorcy: PL, szeroko (bez zawężeń demograficznych na starcie)
       wykluczenia: wszyscy odwiedzający 180 dni + kontakt 180 dni + widzowie 75%
       kreacje: 9:16 z sesji, rotacja tygodniowa

[POST] Promowanie postów Andrzeja                     10–15 zł/dzień
       cel: ruch albo zaangażowanie
       1–2 posty równolegle, rotacja co 3–4 dni
       wszystkie posty mają is_eligible_for_promotion: true

[RMKT] Dynamiczny remarketing z katalogu              do ustalenia po starcie
       cel: konwersje, katalog 2936723456671912
       odbiorcy: oglądający oferty 14 i 30 dni, bez tych co się skontaktowali
       BEZ ROTACJI — kreacje generuje katalog, odświeża się sam co dobę
```

`spend_cap` konta wynosi **1 000 zł**, a konto zasilane jest ręcznie. Kontrola wydatków
leży po stronie Ruslana i Janka — **nie jest to nasza pozycja do pilnowania ani temat
do pism**, patrz sekcja 6.

---

## 3. Rotacja — harmonogram

> **Korekta 28.08 (Janek).** Harmonogramy poniżej są *materiałem*, nie automatem.
> W każdej kampanii trzymamy **dwie–trzy kreacje równolegle**, a gdy przychodzi czas
> na wymianę, narzędzie kładzie obok siebie dwie tabele — co dziś pracuje i z jakim
> wynikiem, oraz co świeżego jest do wzięcia — i **decyzja „co wchodzi, co wychodzi"
> należy do Janka**. Skrypty nie wybierają kreacji samodzielnie ani nie włączają niczego
> z budżetem. Tabela tygodni `[VID]` zostaje jako lista dostępnego materiału i kolejność
> podpowiadana, nie jako kalendarz wykonywany automatycznie.


### [VID] — rotacja tygodniowa, jedna pozycja na tydzień

Materiał: dwie sesje, dziesięć pozycji, dwadzieścia plików.
**Faststart — zmierzone 28.08** na nagłówkach plików (kolejność atomów MP4), nie z domysłu:

```
sierpień Shark 6 pion          ftyp/free/mdat   BRAK faststart
sierpień Z9 GT pion            ftyp/free/mdat   BRAK faststart
lipiec  Leopard 5 czarny pion  ftyp/wide/mdat   BRAK faststart
lipiec  Leopard 5 nieb. pion   ftyp/moov        JEST
lipiec  Exeed VX pion          ftyp/moov        JEST
lipiec  Jetour T2 pion         ftyp/moov        JEST
```

Podział nie idzie po sesjach: **cała sesja sierpniowa wymaga remuksu, z lipcowej tylko
Leopard 5 czarny.** Reguła „lipcowe są gotowe" byłaby fałszywa. Remuks:
`ffmpeg -i wejscie.mp4 -c copy -movflags +faststart wyjscie.mp4`.

Sprawdzenie bez pobierania całego pliku — pierwsze 256 kB przez `Range` i odczyt kolejności
atomów: `moov` przed `mdat` znaczy, że faststart jest.

| Tydzień | Materiał | Sesja | Landing |
|---|---|---|---|
| 1 | BYD Leopard 5 czarny | lipiec | `/samochody/byd/leopard-5/#oferty` |
| 2 | BYD Shark 6 | sierpień | `/samochody/byd/shark-6/#oferty` |
| 3 | Denza Z9 GT | sierpień | `/samochody/denza/z9-gt-dm-i/#oferty` |
| 4 | Denza N9 | sierpień | `/samochody/denza/n9-dm-i/#oferty` |
| 5 | Deepal G318 | sierpień | `/samochody/deepal/g318/#oferty` |
| 6 | Lynk & Co 900 | sierpień | `/samochody/lynk-co/900/#oferty` |
| 7 | Exeed VX (Omoda 11) | lipiec | `/samochody/exeed/vx/#oferty` |
| 8 | BYD Leopard 7 | sierpień | `/samochody/byd/leopard-7/#oferty` |
| 9 | Jetour T2 PHEV | lipiec | `/samochody/jetour/t2/#oferty` |
| 10 | BYD Leopard 5 niebieski | lipiec | `/samochody/byd/leopard-5/#oferty` |

**Dziesięć pozycji, dwadzieścia plików** — każda w kadrze pionowym 9:16 i poziomym 16:9.
Inwentaryzacja Dysku 28.08, katalog `Prima Auto/sesje/` (`1m-8F99y79rIyAhb3zTViOvvmZgCCe87l`):

| Katalog na Dysku | Pion | Poziom |
|---|---|---|
| `Byd-Shark-6/` | `Byd-Shark-6-pion.mp4` 46,8 MB | `Byd-shark-6-poziom.mp4` 36,2 MB |
| `Deepal-G318/` | `Deepal-G318-pion.mp4` 44,6 MB | `Deepal-G318-poziom.mp4` 41,4 MB |
| `Denza-N9/` | `Denza-N9-Pion.mp4` 32,1 MB | `Denza-N9-poziom.mp4` 48,0 MB |
| `Denza-Z9GT/` | `Denza-Z9-GT-pion.mp4` 42,3 MB | `Denza-Z9-GT-poziom.mp4` 48,6 MB |
| `Leopard-7/` | `Leopard-7-pion.mp4` 52,4 MB | `Leopard-7-poziom.mp4` 42,0 MB |
| `Lynk-Co-900/` | `Lynk-co-900-pion.mp4` 47,2 MB | `Lynk-Co-900-poziom.mp4` 63,7 MB |
| `Leopard-5/Czarny/` | `leopard5-black-pion.mp4` 49,5 MB | `BYD Leopard 5 (czarny)… poziom 16x9` 16,8 MB |
| `Leopard-5/Niebieski/` | `…(niebieski)… pion 9x16` 11,7 MB | `…(niebieski)… poziom 16x9` 20,9 MB |
| `Exceed-VX/Granatowy/` | `Exeed VX… pion 9x16.mp4` 11,9 MB | `Exeed VX… poziom 16x9.mp4` 16,4 MB |
| `Jetour-T2/Srebrny/` | `Jetour T2 PHEV… pion 9x16.mp4` 20,0 MB | `Jetour T2 PHEV… poziom 16x9.mp4` 15,4 MB |

**Uwaga na strukturę katalogów:** cztery pozycje z sesji lipcowej leżą w **podkatalogach
kolorystycznych** (`Czarny`, `Niebieski`, `Granatowy`, `Srebrny`), a nie bezpośrednio
w katalogu modelu. Skanowanie tylko pierwszego poziomu je pomija — tak zniknęły
z wcześniejszych zestawień, w których materiał liczono na sześć modeli zamiast dziesięciu.

**Leopard 5 był już publikowany organicznie** — dla reklamy to bez znaczenia. Kampania
`[VID]` celuje w ludzi, którzy Prima-Auto nie znają, a ci tego materiału nie widzieli.
Zastrzeżenie „nie wypuszczamy drugi raz" dotyczyło publikacji na profilu, nie płatnego
dotarcia do nowych odbiorców.

**Sześć rolek z 27.08 nadaje się do promowania** — Meta potwierdza `boost_eligibility_info:
{"eligible_to_boost": true}`. Zebrały łącznie 578 wyświetleń organicznie (Z9 GT 157,
N9 126, Lynk & Co 111, Leopard 7 82, G318 53, Shark 6 49). Promowany post to ten sam
obiekt co organiczny — reklama dokłada mu płatne wyświetlenia, nie tworzy kopii, a dorobek
i komentarze zostają. Wchodzą do rotacji `[POST]` obok postów Andrzeja.

Wszystkie landingi sprawdzone HTTP 28.08 — zwracają 200. **Uwaga na Denzę:**
intuicyjne `/samochody/denza/z9-gt/` i `/samochody/denza/n9/` dają 301 na hub marki,
podobnie jak warianty z prefiksem (`denza-z9-gt-dm`, `denza-n9-dm`). Działają wyłącznie
slugi bez prefiksu marki: `z9-gt-dm-i` i `n9-dm-i`. Przed każdą podmianą kreacji sprawdź
kod odpowiedzi landingu — reklama kierująca na 301 marnuje budżet i psuje jakość.

Po tygodniu 10 cykl wraca do początku — do tego czasu przybędzie materiału z kolejnych sesji.
Kreacji nie kasujemy: wyłączamy poprzednią, włączamy następną w tym samym zestawie reklam,
żeby nie resetować fazy uczenia.

**Zasada:** zmiana kreacji w istniejącym zestawie, nigdy nowy zestaw co tydzień.

### [POST] — rotacja co 3–4 dni, dwa posty równolegle

Andrzej publikuje na Stronie ok. 20 postów miesięcznie i wrzuca Reelsy na Instagram.
Do promowania bierzemy posty z ostatnich 14 dni, dwa naraz, wymiana co 3–4 dni:

```
pon.  → wchodzą posty A i B
czw.  → A wychodzi, wchodzi C          (B zostaje)
pon.  → B wychodzi, wchodzi D          (C zostaje)
czw.  → C wychodzi, wchodzi E          (D zostaje)
```

Zakładka jednego postu na jeden cykl — dzięki temu w każdym momencie jeden post ma już
zebrane dane, a drugi dopiero startuje. Post promujemy raz; do drugiego obiegu wraca
tylko taki, który wyraźnie dowiózł.

**Promowanie postów podlega temu samemu blokerowi DSA co wideo** — sprawdzone 28.08 sondą
`validate_only` na zestawie reklam pod boost, nie założone:

```
PL · LINK_CLICKS + dsa_beneficiary/payor  → 100/3858196
US · LINK_CLICKS (kontrola)               → {"success": true}
```

Wcześniejszy zapis w `docs/roadmapa/T-243c` („zależność: wyłącznie karta") był błędny —
powstał dzień przed wykryciem blokera i został tam skorygowany.

#### Skąd bierzemy kandydatów — kanał danych sprawdzony 28.08

| Co | Endpoint | Stan |
|---|---|---|
| Lista postów | `{page}/published_posts` **page tokenem** | działa; 8/8 ostatnich `is_eligible_for_promotion: true` |
| Ten sam odczyt | `{page}/feed` | **nie działa** — żąda `pages_read_engagement` albo Page Public Content Access |
| Kreacja z posta | `POST {act}/adcreatives` z `object_story_id` | waliduje się na `{"success": true}` |
| Wyniki organiczne posta | `{post}/insights` | **niedostępne** — System User nie ma scope'u `read_insights` |
| Reakcje i komentarze | `comments`/`reactions` w `fields` | **niedostępne** — brak `pages_read_user_content` |

Page token bierzemy z `me/accounts`; token System Usera do tych odczytów **nie wystarcza**,
mimo że ma `pages_read_engagement` na liście scope'ów. Zadania na Stronie mamy pełne:
`MANAGE`, `CREATE_CONTENT`, `MODERATE`, `MESSAGING`, `ADVERTISE`, `ANALYZE`.

**Konsekwencja dla doboru:** bez `read_insights` nie da się preselekcjonować postów po tym,
co samo złapało zasięg. Zostaje reguła z bazy — zapas, próg cenowy, żywy landing — a wyniki
organiczne wchodzą do gry dopiero, gdy rozszerzymy scope'y System Usera (regeneracja dotyka
całej SU, więc to osobna decyzja, nie efekt uboczny rotacji).

Promowany post to ten sam obiekt co organiczny: reklama dokłada mu płatne wyświetlenia,
nie tworzy kopii. Dark post (`published: false`) jest potrzebny wyłącznie dla kreacji wideo
z pliku, których na Stronie w ogóle nie ma.

### [RMKT] — bez rotacji

Kreacje buduje katalog z feedu, który odświeża się codziennie o 06:30. Rotacja ręczna
nie ma tu sensu i nie jest planowana. Wymiana dotyczy najwyżej zestawu produktów
(np. sezonowe zawężenie do jednego segmentu cenowego).

---

## 4. Co zostało zbudowane 28.08

### Katalog — piksel wpięty jako źródło zdarzeń

```
POST /2936723456671912/external_event_sources  external_event_sources=["1634147041766916"]
→ {"success": true}
```

Przed: `[]`. Po: `Prima-Auto Web`. Bez tego dynamiczny remarketing nie miał jak działać —
katalog nie wiedział, kto które auto oglądał.

Łańcuch danych jest domknięty i sprawdzony po obu stronach: `dataLayer` na stronie oferty
pcha `item_id: "57972308"`, feed ma dla tego samego auta `vehicle_id: 57972308`.
Piksel wysyła `content_ids`, `content_type`, `currency`, `value` — 624 razy na dobę.

### Zestawy produktów — pięć

| ID | Nazwa | Pozycji |
|---|---|---:|
| `2149568469325662` | Wszystkie pojazdy | 3 113 |
| `949000898245332` | Do 200 tys. zł | 1 857 |
| `1073735895101996` | SUV-y | 1 746 |
| `1900948867546168` | Elektryczne | 1 341 |
| `1039756215471433` | Hybrydy | 1 271 |

**Gotcha:** filtr ceny w katalogu przyjmuje wyłącznie **liczbę całkowitą w groszach**
(`{"price":{"lte":20000000}}`). Zapis `"200000 PLN"` daje `1798095` („nieprawidłowy filtr"),
a `200000` bez waluty — `1798130` („pusty zestaw produktów"), co myli, bo sugeruje problem
z danymi, a nie z jednostką.

### Katalog jest aktualizowany — potwierdzone

Feed `1398276155566551`, źródło `https://primaauto.com.pl/feeds/meta-vehicles.csv`,
harmonogram dobowy 06:30 Europe/Warsaw. Dziesięć ostatnich pobrań, wszystkie `Server Fetch`,
**zero błędów i zero ostrzeżeń**:

```
28.08  3 113 poz.  205 usuniętych      23.08  3 330 poz.  212 usuniętych
27.08  3 235       211                 22.08  3 178       135
26.08  3 360       176                 21.08  2 718       122
25.08  3 468        95                 20.08  2 681        80
24.08  3 467       161                 19.08  2 500        49
```

Feed **nie niesie sygnału „na placu"** — `address.city` to Rzeszów dla wszystkich 3 113 pozycji,
`availability` to `available` dla wszystkich. Jeśli chcemy osobno reklamować auta stojące
w Polsce, trzeba dołożyć to do generatora feedu (`scripts/build-meta-vehicle-feed.php`).

---

## 5. Grupy odbiorców — SPRAWA ZAWĘŻONA 28.08 wieczorem

Zaprojektowane i gotowe do założenia, wszystkie osiem:

| Grupa | Typ | Okno | Do czego |
|---|---|---|---|
| Wszyscy odwiedzający | website | 180 dni | wykluczenie z prospectingu |
| Oglądający oferty | website `ViewContent` | 30 dni | remarketing gorący |
| Oglądający oferty | website `ViewContent` | 90 dni | remarketing |
| Oglądający oferty | website `ViewContent` | 180 dni | lookalike |
| Kontakt tel./WhatsApp | website `Contact` | 180 dni | wykluczenie + źródło lookalike |
| Formularz | website `Lead` | 180 dni | wykluczenie |
| Zaangażowani ze Strony FB | engagement | 365 dni | remarketing z organiku Andrzeja |
| Zaangażowani z profilu IG | engagement | 365 dni | remarketing z organiku Andrzeja |

**Korekta wieczorna 28.08 — wcześniejszy zapis w tej sekcji był błędny.**
Sześć z ośmiu grup (wszystkie z piksela i obie z zaangażowania) **przechodzi bez żadnego
regulaminu**. Blokada dotyczy wyłącznie grup z **wgranej listy klientów** (`subtype=CUSTOM`
+ `customer_file_source`), których w planie ośmiu nie ma ani jednej.

Pomiar, ten sam endpoint, cztery warianty:

```
CUSTOM + customer_file_source (lista klientów)  → 200/1870090  regulamin
rule na pikselu, ViewContent, 180 dni           → utworzona    120248811956940243
rule na Stronie, page_engaged, 365 dni          → utworzona    120248811957410243
rule na profilu IG, 365 dni                     → utworzona    120248811957520243
```

**Skąd wzięła się pomyłka.** Wcześniejsze próby szły z parametrem `subtype` przy regule
(`2654/1870053` „podtyp nie jest obsługiwany") i z nieistniejącą nazwą zdarzenia dla IG
(`2654/1713151`). To były **błędy ładunku**, nie ślad wspólnej blokady — a zostały wzięte
za jeden objaw jednej przyczyny, bo obok stał prawdziwy błąd regulaminowy z listy klientów.
Reguła na przyszłość: trzy różne kody błędu to trzy różne przyczyny, dopóki nie udowodnisz,
że jest inaczej.

> **GOTCHA, kosztowna:** `/act_*/customaudiences` **ignoruje `execution_options=validate_only`**.
> Sonda z `waliduj=True` **utworzyła trzy prawdziwe grupy** na koncie klienta
> (`sonda-www`, `sonda-fb`, `sonda-ig`). Rozpoznanie po odpowiedzi: endpoint wspierający
> walidację zwraca `{"success": true}`, endpoint ją ignorujący zwraca `{"id": ...}` —
> **id w odpowiedzi na sondę znaczy, że obiekt powstał**. Zanim puścisz `post()` na nowy
> endpoint, sprawdź kształt odpowiedzi, nie ufaj domyślności warstwy.

Regulamin listy klientów zostaje na liście próśb — kosztuje jedno kliknięcie i przyda się,
gdy sięgniemy po bazę klientów z CRM jako źródło grup podobnych odbiorców. **Nie jest już
blokerem remarketingu ani wykluczeń.**

### Dlaczego to pilne — i dlaczego to już nasza robota, nie prośba

Grupy z ruchu na stronie zaczynają zbierać od chwili utworzenia. Każdy dzień zwłoki
to bezpowrotnie utracona pula do remarketingu **i do wykluczeń**, bez których kampanie
wideo będą dopłacać za dotarcie do ludzi, którzy już byli na stronie. Piksel zbiera
od 1 sierpnia — ten miesiąc ruchu nigdzie się nie odkłada.

Po korekcie z 28.08 wieczorem **nic nas przed tym nie powstrzymuje**: sześć grup z planu
możemy założyć od ręki, bez żadnego kliknięcia po stronie klienta. Trzy obiekty sondy
(`sonda-www`, `sonda-fb`, `sonda-ig`) już zbierają — do decyzji Janka, czy przemianować
je na docelowe nazwy i dołożyć pozostałe trzy, czy skasować i założyć komplet od zera.

Przy okazji nadany został naszemu System Userowi komplet zadań na pikselu
(`ADVERTISE`, `UPLOAD`, `ANALYZE`, `EDIT`) — wcześniej `assigned_users` piksela było puste.
Nie to było przyczyną blokady, ale i tak było do naprawienia.

---

## 6. Blokery i prośby — stan na 28.08 wieczorem, w kolejności ważności

| # | Rzecz | Dowód z API | Kto zdejmuje |
|---|---|---|---|
| 1 | **Weryfikacja reklamodawcy (DSA)** — żadna reklama do UE nie powstanie | `3858196` na PL i DE; ten sam zestaw z geo US i UA przechodzi `{"success": true}` | Ruslan, Menedżer Reklam, dokumenty firmy |
| 2 | **Weryfikacja domeny `primaauto.com.pl`** — brak przypisania konwersji (AEM), gorszy priorytet zdarzeń | brak `<meta name="facebook-domain-verification">` na stronie i brak wpisu TXT w DNS; edge `owned_domains` niedostępny dla naszej appki w v19–v25 | Ruslan dodaje domenę w portfolio i przekazuje kod, **wpięcie kodu robimy my** |
| 3 | **Scope `read_insights` + `pages_read_user_content`** — nie widzimy wyników organicznych postów | `debug_token`: 11 scope'ów, żadnego z tych dwóch; `{post}/insights` odbija | Ruslan (regeneracja tokenu SU) **albo** sekret appki `1533997951805022` przekazany nam raz na zawsze |
| 4 | **Regulamin listy klientów** — tylko grupy z wgranego pliku | `200/1870090` przy `subtype=CUSTOM` | Ruslan, jeden klik; **nie blokuje remarketingu** |
| 5 | **Regulamin pozyskiwania kontaktów** na Stronie — brak formularzy w reklamie | `leadgen_tos_accepted: false` (page tokenem) | Ruslan lub Andrzej, jeden klik |
| 6 | **Kanał YouTube niepowiązany z Google Ads** — 63 tys. wyświetleń nie buduje audiencji | GAQL `account_link` i `data_link` na koncie `9506068500`: **zero wierszy** | właściciel kanału (Andrzej) akceptuje, prośbę wysyłamy my |

**Bloker 1 obejmuje wszystkie trzy kampanie, `[POST]` włącznie.** Promowanie gotowego posta
nie jest osobną ścieżką omijającą weryfikację — to zwykły zestaw reklam z targetem PL.
Sprawdzenie stanu: `python3 scripts/social/dsa_status.py` (sonda `validate_only`, nic nie tworzy).

**Co przestało być blokerem po pomiarach 28.08 wieczorem:**

- **grupy odbiorców z piksela i zaangażowania** — działają, patrz sekcja 5;
- **regulamin pikseli w portfolio** (`1784018`) — dotyczył wyłącznie zakładania piksela
  na poziomie portfolio. Conversions API działa na tokenie System Usera:
  `bash ~/secrets/meta/capi_send.sh --project primaauto-2026 PageView TEST12345`
  → `{"events_received": 1}`. Nie jest prośbą do klienta;
- **rola reklamowa na Instagramie** — kreacja z `instagram_user_id` waliduje się
  na `{"success": true}`, insighty IG czytamy (`reach` 368/26). Nie jest prośbą;
- **niebieski znacznik Strony** (`verification_status: not_verified`) — sprawdzone,
  nie warunkuje niczego z planu. **Nie prosimy o to.**

**Poza naszym zakresem (decyzja Janka 28.08):** `spend_cap`, metoda płatności i rola `MANAGE`.
Ruslan zasila konto ręcznie, budżetu pilnują z Jankiem na bieżąco. **Nie podnoś tego
w pismach do klienta i nie traktuj `spend_cap: 100000` jako blokera** — to nastawa
operacyjna klienta, nie brakujące uprawnienie.

### Jak zweryfikować, że bloker DSA zniknął

Sonda bez tworzenia obiektów, `execution_options: ["validate_only"]`:

```
python3 scripts/social/dsa_status.py
# dziś: 3858196 · po weryfikacji oczekiwane: PRZECHODZI na wariantach PL
```

---

## 7. Stan konta reklamowego — sprawdzony 28.08

```
act_1038563008906171  „Prima-Auto"   account_status 1 (aktywne), disable_reason 0
PLN · Europe/Warsaw · business_country_code PL
amount_spent 0 · balance 0 · spend_cap 100000 gr (1 000 zł) · min_daily_budget 371 gr
capabilities zawierają HAS_VALID_PAYMENT_METHODS  → karta JEST podłączona
capabilities zawierają HAS_NEVER_CREATED_ADGROUP  → żaden zestaw reklam nigdy nie powstał
insights lifetime: puste  → zero wyświetleń w historii konta
```

`funding_source` i `funding_source_details` wracają puste, bo nasz System User ma na koncie
`DRAFT · ANALYZE · ADVERTISE` bez `MANAGE` (`MANAGE` ma tylko Ruslan). **Puste `funding_source`
nie jest dowodem braku karty** — dowodem jest `HAS_VALID_PAYMENT_METHODS` w `capabilities`.

Zbudowane, czeka na odblokowanie:

```
KAMPANIA  120248809387930243  [VID] Auta z Chin — nowi odbiorcy  PAUSED, bez budżetu
KREACJA   4545681435750921    Leopard 5 — wideo 9:16, dark post
WIDEO     1389329476599667    Leopard 5 czarny, 9:16, 42 s, prywatność SELF
ZESTAW    — nie istnieje (bloker 1)
REKLAMA   — nie istnieje
```

Dark post nie wycieka na Stronę: kopia wideo `958145193978736` ma `published: false`,
nie ma jej wśród 23 wideo Strony, a publiczny URL `/reel/958145193978736/` zwraca pustą
stronę bez treści. Sprawdzone trzema sposobami.

Do posprzątania w bibliotece reklamowej, gdy będzie po temacie:
`4040666229569687` i `2853400968357319` (wideo testowe), `2410ca18ffde1dc659606c50333c2b14`
i `e0672a35900e392f4c2b53c9986962ee` (zdjęcia testowe).

---

## 8. Zadania dla klienta — lista zamykająca

Zebrane modułem po module (`[VID]`, `[POST]`, `[RMKT]`, grupy odbiorców, CAPI, katalog, pomiar),
żeby nie wracać po raz czwarty. Pismo: `docs/meta/prosby-koncowe-2026-08-28.html`.

| | Co | Kto | Czas | Co odblokowuje |
|---|---|---|---|---|
| 1 | **Weryfikacja beneficjenta i płatnika reklam (DSA)** | Ruslan | 15 min + oczekiwanie | wszystkie trzy kampanie |
| 2 | **Domena `primaauto.com.pl` w portfolio** — dodać i przekazać kod | Ruslan | 5 min | przypisanie konwersji, priorytet zdarzeń |
| 3 | **Uprawnienia do statystyk Strony** — regeneracja tokenu **albo** sekret appki | Ruslan | 3 min | rotacja `[POST]` na danych, nie na oko |
| 4 | Regulamin grup odbiorców z listy klientów | Ruslan | 1 min | grupy podobnych odbiorców z bazy CRM |
| 5 | Regulamin pozyskiwania kontaktów na Stronie | Ruslan / Andrzej | 1 min | formularze w reklamie |
| 6 | Powiązanie kanału YouTube z Google Ads | Andrzej | 2 min | widzowie filmów jako odbiorcy |
| 7 | Instagram: adres strony w profilu, literówka „z Chin ta Korei" | Andrzej | 3 min | ruch z IG, dziś zerowy |
| 8 | TikTok: adres strony w profilu | Andrzej | 2 min | ruch z TikToka, dziś zerowy |

Pozycja 3 ma dwa warianty i **rekomendujemy sekret appki** — jednorazowo, po czym każdą
przyszłą zmianę uprawnień robimy sami. To wycina z obiegu całą klasę przyszłych próśb.

---

## 9. Zasada obowiązująca bezwzględnie

„Leć", „działaj", „rób" to zgoda na **pracę**, nie na **publikację**. Każde wypchnięcie
treści na zewnętrzny kanał klienta wymaga osobnej zgody na konkretną treść i konkretny termin.
Uruchomienie kampanii z budżetem to publikacja — wymaga zgody tak samo jak post.

---

## 10. Narzędzia — `scripts/social/`

Zbudowane 28.08. Rozłam między nimi nie idzie po „wideo kontra post", tylko po tym,
**skąd bierze się kreacja**: wideo trzeba wgrać do Mety z pliku, post już w niej jest
i wystarczy się do niego odwołać. Stąd dwa narzędzia, nie jedno z przełącznikiem.

| Plik | Do czego | Stan pracy |
|---|---|---|
| `meta_api.py` | wspólna warstwa: token, wersja Graph, tłumaczenie błędów, stan, sprawdzanie landingu | — |
| `rotator_postow.py` | kampania `[POST]` — kreacja z `object_story_id` gotowego posta | `state/meta-posty.json` |
| `rotator_wideo.py` | kampania `[VID]` — plik z Dysku, remuks, wgranie, kreacja | `state/meta-wideo.json` |
| `dsa_status.py` | czy bloker DSA nadal stoi | — |
| `meta_kampania_wideo.py` | builder pierwszej kampanii (istniejący, nietknięty) | `state/meta-kampanie.json` |

**Wywołanie bez argumentów = zestawienie do decyzji.** Nic nie wysyła:

```
python3 scripts/social/rotator_postow.py      # co pracuje + 10 ostatnich postów
python3 scripts/social/rotator_wideo.py       # co pracuje + materiał z Dysku
python3 scripts/social/dsa_status.py          # czy PL przechodzi walidację
```

Ruch wykonuje się dopiero jawnym poleceniem z identyfikatorem — `--wstaw`, `--wyjmij`,
`--wgraj`. Reklama zawsze powstaje jako `PAUSED`; **włączenie z budżetem nie jest
czynnością żadnego z tych narzędzi** i wymaga osobnej zgody, tak samo jak publikacja posta.

### Dwie domyślności, które chronią konto klienta

`meta_api.post()` domyślnie dopisuje `execution_options=["validate_only"]` — pomyłka
w skrypcie kosztuje komunikat, a nie kampanię w bibliotece reklamowej. Zapis wymaga
świadomego `waliduj=False`. `landing_zyje()` **nie podąża za przekierowaniem**: chcemy
zobaczyć 301 na złym slugu Denzy, a nie 200 z huba, na który Meta i tak nie kieruje.

### Co narzędzia mierzą, a czego nie

Górna tabela (co pracuje) ciągnie pełne Ads Insights: wyświetlenia, zasięg, kliknięcia,
CTR, koszt, zaangażowanie, a przy wideo dooglądalność do 75%. Dolna tabela (świeży materiał)
opisuje posty tym, co widać z bazy — seria, zapas w serii, cena, kod odpowiedzi landingu,
rezerwacja — **bez zasięgu organicznego**, bo System User nie ma scope'u `read_insights`.

To jest dziś największa dziura w danych do decyzji: nie widzimy, który post sam złapał
zasięg, zanim zdecydujemy o jego promowaniu. Naprawa to regeneracja tokenu System Usera
o `read_insights` (i `pages_read_user_content` dla reakcji i komentarzy). Regeneracja
rozszerza scope'y **całej** SU, więc to osobna decyzja, nie efekt uboczny rotacji.

### Materiał wideo — kiedy się zmienia

Katalog w `rotator_wideo.py` to dziesięć pozycji z dwóch sesji. Zmienia się rzadko,
sesja raz na kilka tygodni. Po nowej sesji dopisujemy pozycje do stałej `KATALOG`
(klucz, folder na Dysku, wariant kolorystyczny, landing) — reszta działa bez zmian.
Zdjęcia z sesji leżą na tym samym Dysku i nie są jeszcze wykorzystywane w żadnej kreacji.

Zmierzone 28.08 przez `rotator_wideo.py`: dziesięć pozycji w kadrze 9:16, wszystkie
landingi 200, **siedem wymaga remuksu faststart** — `leopard-5-czarny`, `shark-6`,
`z9-gt`, `n9`, `g318`, `lynk-900`, `leopard-7`. Gotowe bez remuksu: `leopard-5-niebieski`,
`exeed-vx`, `jetour-t2`. Remuks robi się sam przy `--wgraj`, bez rekompresji.
