# PROMPT — nowa fala wideo: publikacja, reklamy, harmonogram

> **Jak zacząć nowy wątek:** „przeczytaj `docs/sesje/2026-08-26-PROMPT-wideo-publikacja.md`".
> Poprzednia fala (24–25.07): `docs/sesje/2026-07-25-yt-metadane-wykonanie.md`,
> memory `project_youtube_kanal_i_demand_gen_2026_07_24`, `project_yt_metadane_przebudowa_2026_07_25`.
> Dokument dla klienta: `docs/roadmapa/T-243b-czego-brakuje-2026-08-25.md`.
> Spec parasola: `docs/roadmapa/T-243-social-dystrybucja-tresci.md`.

Fala druga jest **prawie dwa razy większa od pierwszej**: 7 aut zamiast 4, każde w dwóch
kadrach i z kompletem zdjęć, materiał zabrandowany animowanym logo i sloganem na końcu.
Lipcowa paczka pokazała, co działa i co się marnuje — ten plik ma sprawić, żeby drugi raz
nie zmarnować.

---

## 1. Czego nauczyła nas pierwsza fala — pomiar z 25.08

**Wideo sprzedaje przez wyszukiwarkę, nie przez kliknięcie w film.** Zapytania brandowe
w Google (GSC), średnia dzienna:

| Okres | Wyświetleń/dz | Kliknięć/dz |
|---|---:|---:|
| maj | 29,6 | 3,2 |
| czerwiec | 34,3 | 5,6 |
| lipiec do 23 (przed falą) | 37,4 | 8,6 |
| 24.07–16.08 | **105,2** | **59,1** |
| po opadnięciu skoku (13–23.08) | ~90 | ~45 |

Baseline brandowy podniósł się trwale i **nie wrócił do poziomu sprzed fali**.

⚠️ **Ale gros skoku to nie nasze filmy, tylko cudzy.** 8 sierpnia Terenwizja (395 tys.
subskrypcji) opublikowała *„Pojedynek chińskich aut 4x4"* — 63 982 wyświetlenia, udział Ruslana.
Tego dnia: **625 wyświetleń i 477 kliknięć brandowych** (dzień wcześniej 63 i 22), `google/organic`
227 → 555 sesji, `youtube.com/referral` wskakuje do pierwszej piątki źródeł (35 sesji).
Ogon opadał przez tydzień: 324, 225, 171, 114, 92.

**Kontrdowód domykający tezę:** 18–19.08 Terenwizja wypuściła test Fotona Tunland
(121 853 + 43 962 wyświetlenia) — **brand nie drgnął** (100 i 96). Czyli nie działa „film
o chińskich autach na dużym kanale". Działa **film, w którym jesteśmy**.

### Dwie kampanie wideo — co dowiozły (26.07–24.08)

| | Koszt | Wyświetlenia | Kliknięcia | CTR | Realne kontakty | View-through |
|---|---:|---:|---:|---:|---:|---:|
| **[DG] Demand Gen** | 600 zł | 75 130 | 2 322 | 3,1% | **25** (8 tel. + 17 WA) | 0 |
| **[VID] Placementy** | 434 zł | 29 596 | **30** | **0,10%** | 0 | 0 |

Linki w [VID] **były** (hub Exeed VX i oferta Leoparda 270959) — więc nie o brak linku chodzi.
Konwersje obejrzeniowe są zerowe **na całym koncie**, więc nie ma ukrytego kredytu.

⚠️ **Pułapka odczytu:** [DG] w kolumnie „Wszystkie konwersje" pokazuje **64**, z czego **39 to
YouTube** (6 subskrypcji + 33 follow-on views). Prawdziwa liczba to **25** w kolumnie
„Konwersje". Cele YouTube zostały zdjęte 19.08 (`scripts/ads-zawez-cele.py`) i na [DG] naprawa
trzyma; **na [VID] nie trzyma** — tam 7 „konwersji" to nadal YouTube.

### Reguła pomiarowa na tę falę

**Miernikiem wideo jest brand search w GSC w oknie D+0…D+7 po publikacji, nie konwersje w Ads.**
Gdyby patrzeć na Ads, 8 sierpnia byłby dniem bez znaczenia — a był najlepszym dniem kwartału.
Narzędzie: `tmp/social/gsc_brand.py` i `tmp/social/brand_dziennie.py` (filtr `query contains "prima"`).

### Reguła publikacyjna z lipca — nie łamać

**Jedna pozycja na dzień, nigdy paczka.** 24.07 poszło 8 filmów jednego dnia: pierwszy Short
zebrał **1 436 wyświetleń**, trzy ostatnie po **8–10**. Feed Shortsów nie obsłuży ośmiu pozycji
z jednego kanału w jedną dobę.

**Shorts to nośnik zasięgu, poziome to paliwo reklamowe.** 75% całego zasięgu kanału zrobiły
Shorts; filmy poziome bez wsparcia płatnego praktycznie nie startują. Rekordzista fali 1 —
Short Exeed VX (Omoda 11): **36 714 wyświetleń**.

**Nie ruszać tytułu ani miniatury filmu w trakcie fali** — reset modelu CTR ucina zasięg.
Opis i tagi są dla feedu neutralne, tam zmiany bezpieczne.

---

## 2. Materiał — 7 aut, wszystko zmapowane

Dysk: `PrimaAuto/sesje/<Model>/`, folder nadrzędny `1m-8F99y79rIyAhb3zTViOvvmZgCCe87l`.
Każdy folder: `*-pion.mp4` (9:16) + `*-poziom.mp4` (16:9) + zdjęcia `SCH027xx`/`SCH028xx`.

| Auto | Cena | Oferta (na placu) | Hub modelu |
|---|---:|---|---|
| **Denza N9 DM-i** | 344 000 zł | `/oferta/denza-n9-dm-i-2026-270999/` (4 szt. na placu: 389970, 387824, 351079, 270999) | `/samochody/denza/n9-dm-i/` |
| **Denza Z9 GT DM-i** | 291 000 / 295 000 zł | `/oferta/denza-z9-gt-dm-i-2025-314155/` (2 szt.: 314155, 303534) | `/samochody/denza/z9-gt-dm-i/` |
| **BYD Shark 6** | 250 000 zł | `/oferta/byd-shark-6-premium-awd-2026-phev-new/` (272113) | `/samochody/byd/shark-6/` |
| **BYD Leopard 7 (Tai 7)** | 234 000 zł | `/oferta/byd-leopard-7-tai-7-fcb-phev-2026-317106/` | `/samochody/byd/leopard-7/` |
| **BYD Leopard 5 (Denza B5)** | 233 000 zł | `/oferta/byd-leopard-5-denza-b5-2025-270959/` | `/samochody/byd/leopard-5/` |
| **Deepal G318** | 196 000 zł | `/oferta/deepal-g318-2026-272010/` (272010) | `/samochody/deepal/g318/` |
| **Lynk & Co 900** | — | ⚠️ **żadna sztuka nie ma statusu „na placu"** — 4 szt. `in_transit` (292582, 387288, 360158, 387540) | `/samochody/lynk-co/900/` (sprawdzić) |

⚠️ **Lynk & Co 900 — rozstrzygnąć PRZED publikacją.** Auto było na sesji, więc stoi na placu,
ale w bazie wszystkie cztery sztuki są „w drodze". Albo Ruslan/Andrzej poprawia status
(punkt A5 w dokumencie dla klienta), albo piszemy o nim jako o aucie w drodze. Reklama „od ręki"
prowadząca na ofertę „w drodze do Polski" kosztuje zaufanie.

📌 **Leopard 5 ma materiał z obu sesji** (23.07 i 24.08) — sprawdzić, czy nowy nie duplikuje
opublikowanego 24.07 (`JREnGhOomqI` Short, `ptYgxcc99ok` i `7UTtfN7WaII` poziome).

### Branding — temat zamknięty, nie otwierać go ponownie

**Materiał ma już animowane logo i slogan na końcu** — jako outro, nie intro. Widz od pierwszej
sekundy widzi auto, branding domyka. Nie ma więc problemu „13% Shorta oddane logo, zanim
pojawi się samochód" i **nie proponuj wycinania ani skracania czołówki pod Shorty**.

W reklamach dodatkowo **nazwa kanału, tytuł i atrybucja źródła serwowane są z automatu przez
platformę** — brandingu nie trzeba tam dokładać ręcznie.

Silnik jest w `scripts/czolowka/` (warianty 9:16 i 16:9, wariant A „Medal", 3,9 s;
`python3 scripts/czolowka/render.py --kadr 9x16|16x9 --tlo light|dark`), gotowe pliki na Dysku
w `PrimaAuto/video/logo` — do przerenderowania, gdyby zmieniał się kadr, tło albo treść sloganu.

---

## 3. Stan kanałów i co blokuje

| Kanał | Stan | Publikacja |
|---|---|---|
| **YouTube** | 19 filmów, 61 344 wyświetlenia, 15 subskrypcji. Ostatnia publikacja **24.07** | ✅ **Sprawdzony wzorzec: Janek wgrywa pliki, model przeklikuje metadane i publikację przez Studio (Chrome).** API niedostępne — kanał na prywatnym koncie Google, brak Brand Accounta |
| **Facebook (nowa Strona)** | 145 fanów, Andrzej publikuje co 1–3 dni | Reklamy: **zablokowane** do czasu R1–R4 (regulamin grup odbiorców, karta, weryfikacja firmy, tryb aplikacji) |
| **Instagram** | 3 sesje/90 dni, konto nieustalone | Czeka na odpowiedź A3 |
| **TikTok** | 334 obs., 127 filmów, **stoi od 14.07**, brak linku w bio | Czeka na A1 |

**Co da się zrobić bez nikogo:** YouTube (wzorzec ręczny) i kreacje do [DG] z materiałów, które
już są na koncie reklamowym Google.

---

## 4. Plan publikacji — 7 aut, 14 pozycji, nie na raz

Kolejność podyktowana dwiema rzeczami: **cena rosnąco od dołu środka stawki** (materiał tańszy
łatwiej łapie zasięg, drogi flagowiec lepiej wchodzi, gdy kanał ma rozpęd) oraz **gotowością
oferty** (Lynk & Co na końcu, bo wymaga rozstrzygnięcia statusu).

| Dzień | Short 9:16 (nośnik zasięgu) | Poziomy 16:9 |
|---|---|---|
| 1 | Deepal G318 — 196 000 zł | — |
| 2 | BYD Leopard 7 — 234 000 zł | Deepal G318 |
| 3 | BYD Shark 6 — 250 000 zł | — |
| 4 | Denza Z9 GT — 291 000 zł | BYD Shark 6 |
| 5 | Denza N9 — 344 000 zł | — |
| 6 | BYD Leopard 5 *(jeśli nowy materiał ≠ lipcowy)* | Denza N9 |
| 7 | Lynk & Co 900 *(o ile status rozstrzygnięty)* | Denza Z9 GT |
| 8–10 | — | Leopard 7, Leopard 5, Lynk & Co 900 |

**Zasady harmonogramu:**
- **Jedna pozycja Shorts dziennie.** Poziomy tego samego dnia jest dopuszczalny, bo nie konkuruje
  o ten sam feed — ale nigdy dwa Shorty jednego dnia.
- **Poziomy idzie dzień po swoim Shortcie**, nie razem: jeśli Short złapie, poziomy zbierze
  ruch z „więcej od tego kanału".
- **Limit YouTube API nie ma tu znaczenia** (publikujemy ręcznie), ale gdyby wrócić do API:
  `videos.insert` = 1 600 jednostek z dobowej puli 10 000, czyli **maks. 6 wgrań/dobę**.
- **Kalendarz uzgodniony z Andrzejem** (punkt A2 dokumentu klienckiego) — żeby jego post
  ofertowy i nasz film nie wychodziły tego samego dnia o tym samym aucie.
- **Publikacja w oknie 15:00–19:00** — 41% publikacji na kanałach docelowych wychodzi wtedy,
  noc 1:00–7:00 to 2,2% (pomiar z 25.07, stąd harmonogram [DG]).

**Pomiar:** po każdej publikacji notuj brand search D+0…D+7. Po siódmym dniu porównaj przyrost
z falą lipcową — wtedy będzie wiadomo, czy skala 7 aut daje więcej niż 4, czy tylko rozcieńcza.

---

## 5. Co zrobić jutro, w kolejności

1. **Rozstrzygnąć z Jankiem dwie rzeczy** (pojedynczo, quizem — `feedback_quiz_sequential`):
   Lynk & Co 900 (czekamy na poprawiony status czy piszemy „w drodze") · czy Leopard 5
   z nowej sesji to inny materiał niż lipcowy.
2. **Przygotować metadane dla wszystkich 14 pozycji** — wzorzec z 24–25.07: cena PLN w tytule,
   deep-link do oferty w pierwszej linii opisu, playlista „Auta na placu w Rzeszowie",
   nie-dla-dzieci, publiczny, 10–12 tagów. Shorts **bez pełnego bloku kontaktowego**
   (opis zwinięty, klika się ~1 link) — tylko `/w-rzeszowie/` + podpis.
3. **Janek wgrywa pliki, model przeklikuje** — Studio, Chrome. Gotchy niżej.
4. **Kreacje do [DG]** z materiałów fali 2 — reklama wideo per auto, prowadząca na hub modelu
   albo konkretną ofertę.
5. **Gotowce dla Andrzeja** na Facebooka — ten sam pakiet do ręcznego wklejenia.

---

## 6. Pułapki — zaliczone, nie powtarzać

**Studio (Chrome automation):**
- Pole opisu (kanał i film) to `DIV` contenteditable — `form_input` zwraca „not a supported form
  input". Trzeba: klik w pole → `ctrl+a` → `type`.
- **Pierwszy klik po `navigate` nie łapie fokusu**, i pierwszy klik w tym samym `browser_batch`
  też nie. Osobne wywołanie klik + `ctrl+a`, potwierdzić na zrzucie, że tekst jest podświetlony.
- Tagi: rozwinąć „Pokaż więcej", wpisać jednym ciągiem z przecinkami.
- Drag&drop linków na kanale **nie działa** pod automatyzacją.
- Zmiana nazwy kanału: **maks. 2 razy na 14 dni.**

**Google Ads:**
- Kampanie **VIDEO są zamknięte dla API** (`MUTATE_NOT_ALLOWED` na `campaigns`, `adGroups`,
  `campaignCriteria`, `adGroupCriteria`, `adGroupAds` create). Działa wyłącznie
  `ads:mutate` update istniejącej reklamy. Demand Gen blokuje mutację demografii.
- `videoResponsiveAd.callToActions` = tekst **max 10 znaków**.
- Karuzela DG `callToActionText` przyjmuje tylko wybrane stringi (OK: „Więcej informacji").
- **72% ruchu [DG] to `AGE_RANGE_UNDETERMINED`** (wylogowani) — nie odznaczać „Nieznany" przy
  wieku, to odcina 3/4 kampanii.
- `metrics.video_views` i `metrics.average_cpv` **nie istnieją** w v25 (`UNRECOGNIZED_FIELD`);
  `view_through_conversions`, `video_quartile_p100_rate`, `engagements` działają.
- GAQL **nie obsługuje `OR`** w `WHERE` — filtrować po stronie Pythona.
- `LAST_90_DAYS` nie jest prawidłowym literałem dla `DURING` — używać `BETWEEN`.

**Zapisy blokowane przez klasyfikator trybu auto (stan 25.08):** publikacja wersji GTM, mutacje
budżetów i statusów w Ads, tworzenie audiencji Meta, edycja własnych uprawnień. Tworzenie wersji
GTM, zapisy do workspace i tworzenie obiektów na koncie Meta przechodzą. Skrypty przygotowywać
gotowe do odpalenia przez Janka (`! python3 …`).

---

## 7. Otwarte, do rozstrzygnięcia

- **Lynk & Co 900** — status na placu.
- **[VID]** — skrypt `tmp/social/ads_przestaw.py` gotowy: pauza [VID] + [DG] z 20 na 35 zł/dz.
  Nie wykonany (blokada zapisów).
- **YouTube Analytics API niewłączone** w projekcie GCP (`youtubeanalytics.googleapis.com`,
  projekt 325733204269). Bez niego nie rozdzielimy, ile wyświetleń filmu przyszło z reklamy,
  a ile z organicznego feedu — czyli dokładnie tego, co trzeba wiedzieć przy każdym nowym
  materiale. Jedno kliknięcie w konsoli.
- **Sponsoring kanału** — Terenwizja pokazała, ile warte jest wystąpienie w cudzym materiale
  (~900 dodatkowych kliknięć brandowych w ogonie jednego filmu). Warto poznać cenę i porównać
  z 434 zł/mc, które szły w [VID].
- **Zdjęcia z sesji** — 7 kompletów `SCH027xx`/`SCH028xx` nietknięte. Kandydaci na miniatury
  YouTube, kreacje graficzne do [DG] i Meta, wzbogacenie hubów (T-240).
