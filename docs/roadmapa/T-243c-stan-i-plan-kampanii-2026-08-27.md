# T-243c — z czym startujemy i co budujemy: plan kampanii na wszystkie kanały

> **Data:** 27 sierpnia 2026 · Wszystko poniżej sprawdzone dziś bezpośrednio na kontach
> **Parasol:** `T-243-social-dystrybucja-tresci.md` (89–119 h, zużyte 4 h)
> **Styka się z:** T-205 (Meta), T-201 (YouTube), T-207 (dashboard), T-240 (huby)
> **Zastępuje pytanie „co dalej"** postawione w `docs/sesje/2026-08-27-PROMPT-social-domkniecie.md` §5

---

## 1. Co się zmieniło dziś — faza a ruszyła z miejsca

Faza T-243a („Dostępy i konta") była blokadą całego parasola od 7 sierpnia. Dziś odblokowały
się trzy z pięciu punktów.

| | Punkt z listy dla klienta | Stan 26.08 | Stan 27.08 |
|---|---|---|---|
| 1 | Token z uprawnieniami Instagrama | ⛔ brak | ✅ **wpięty**, 11 scope'ów, nie wygasa |
| 2 | Karta + limit na koncie reklamowym | ⛔ brak | 🟡 `spend_cap` **1 000 zł** — Ruslan potwierdza kartę, API jej nie pokazuje |
| 3 | Kanał YouTube na konto marki | ⛔ brak | 🟡 **w toku** — Ruslan zajmuje się |
| 4a | Link do strony w bio TikToka | ⛔ brak | ⛔ bez zmian |
| 4b | Konto firmowe + Business Center TikTok | ⛔ brak | ⛔ bez zmian |

**Token — mechanizm wart zapamiętania.** Uprawnienia tokenów `SYSTEM_USER` nie są zamrożone
w chwili wystawienia: generacja nowego tokenu rozszerza uprawnienia **całego użytkownika
systemowego**, a wcześniej wystawione tokeny tej samej SU dziedziczą scope'y bez regeneracji.
Stary token z 31.07 miał dziś komplet, zanim wpięliśmy nowy. Nie diagnozuj z teorii — `debug_token`.

**Czego w tokenie NADAL nie ma:** `pages_manage_posts`. Instagram publikuje, Facebook nie —
przy następnej regeneracji dołożyć, inaczej faza e (Facebook) stoi.

---

## 2. Z czym startujemy — inwentarz na dzień dzisiejszy

### Dostępy i infrastruktura

| Zasób | Identyfikator | Stan |
|---|---|---|
| Konto reklamowe Meta | `act_1038563008906171` | aktywne, PLN, Europe/Warsaw, limit 1 000 zł, **0 kampanii** |
| Piksel / dataset | `1634147041766916` | zbiera, ViewContent przez GTM |
| Katalog pojazdów | `2936723456671912` | **3 236 pojazdów**, feed `build-meta-vehicle-feed.php` |
| Strona FB (nowa) | `1146829831857839` | 153 fanów, mamy MANAGE / CREATE_CONTENT / ADVERTISE. **Andrzej publikuje tu 20 postów / 30 dni, wszystkie gotowe do promowania** |
| Instagram | `17841457773875233` `@prima_auto.pl` | 103 obs., 435 postów, **publikacja przez API działa** |
| Google Ads | `9506068500` | 10 kampanii, 4 154 zł / 30 dni |
| YouTube | `UCsfcUP3uwrDxPI_mDIle8Pw` | 12 filmów z fali 2 planuje się do 02.09 |

### Materiał gotowy do wykorzystania

**Materiał autorski na Dysku Google** — policzony 27.08 przez API, folder
`Prima Auto/sesje/` (`1m-8F99y79rIyAhb3zTViOvvmZgCCe87l`):

| Model | Zdjęcia | Filmy | Kadry |
|---|---:|---:|---|
| BYD Shark 6 | 22 | 2 | 9:16 + 16:9 |
| Deepal G318 | 31 | 2 | 9:16 + 16:9 |
| Denza N9 | 21 | 2 | 9:16 + 16:9 |
| Denza Z9 GT | 16 | 2 | 9:16 + 16:9 |
| Exeed VX | 20 | 2 | 9:16 + 16:9 |
| Jetour T2 | 15 | 2 | 9:16 + 16:9 |
| BYD Leopard 5 | 29 | 4 | 9:16 + 16:9 |
| BYD Leopard 7 | 15 | 2 | 9:16 + 16:9 |
| Lynk & Co 900 | 27 | 2 | 9:16 + 16:9 |
| **RAZEM** | **196** | **20** | 1,0 GB |

**Dziewięć modeli, 196 własnych zdjęć i 20 filmów w obu kadrach.** Do tego opisy, ceny,
deep-linki i hashtagi fali 2 już napisane (`tmp/social/metadane-fala2.md`). To jest inwentarz
reklamowy, za który zapłaciliśmy pracą — sesja w Rzeszowie, montaż dwunastu filmów, obróbka
130 zdjęć — i który dziś pracuje w jednym miejscu, na YouTube.

**Zdjęcia autorskie są warte osobnej uwagi.** Andrzej publikuje dziś zdjęcia z importu
(Dongchedi, che168) — te wymagają maskowania chińskich tablic i znaków wodnych, a nasze nie.
Reguła D7 ze specu daje im pierwszeństwo. 196 zdjęć to materiał na karuzele Meta, na miesiące
postów i na wzbogacenie ofert w katalogu.

**Dostęp mamy dziś:** `~/secrets/google/tokens.json` ma pełny scope `drive`, listowanie
i pobieranie działa (`tmp/social/drive_sesje.py`). Publikacja Reels przez Graph API wymaga
publicznego URL, więc plik idzie z Dysku na serwer i stamtąd do Meta — **żadnej blokady już tu nie ma.**

**Kod, którego nie budujemy drugi raz:** `scripts/kb/kb_lib.py` (generator + lint + proofread),
wzorzec pipeline'u `news_daily.py`, `run-job.sh` z kill-switchem, `gads_client.py`,
`ga4_query.py`, `build-meta-vehicle-feed.php`, wzorzec publikacji statycznej `build.py --deploy`.

**Czego nie ma:** `scripts/social/` nie istnieje, tabela kolejki nie istnieje,
`class-asiaauto-admin-social.php` nie istnieje. Fazy b–g są nietknięte.

### Stan kampanii Google Ads (30 dni)

```
[Brand] Prima-Auto              330 zł   22,0 konw   CPA  15    ← najtańszy kontakt
[DG] Demand Gen (YouTube)       596 zł   25,0 konw   CPA  24    ← najlepszy skalowalnie
[Topic] Import z Chin           980 zł   17,0 konw   CPA  58
[RMKT] Dynamic Remarketing      551 zł   13,2 konw   CPA  42
[VID] Placementy konkurencji    437 zł    7,0 konw   CPA  62    ← 34 kliknięcia, 0 sesji GA4
[DSA] Import modele z Chin      465 zł    0   konw     —
[SKAG-2] W drodze               759 zł    0   konw     —        ← 759 zł bez konwersji
RAZEM                         4 154 zł   84,2 konw   CPA  49
```

**Rozstrzygnięcie D-3 (koszt kontaktu — remarketing kontra Facebook), liczba zdobyta:**
`[RMKT]` dowozi **10 zdarzeń kluczowych GA4 na 550 zł miesięcznie**. Stara Strona Andrzeja
dowozi **30 zdarzeń na 90 dni, czyli 10 miesięcznie, za zero złotych.** Tyle samo kontaktów,
różnica 550 zł. To nie jest argument za wyłączeniem remarketingu — to argument za tym,
że kanał Andrzeja jest wart osobnej pozycji w planie, a nie przypisu.

---

## 3. Kombajn — jedna pętla, nie sześć osobnych narzędzi

To, co budujemy, nie jest ani „pipeline'em contentowym", ani „kampaniami na Meta". To jeden
obieg, w którym **każdy materiał przechodzi tę samą drogę od oferty w bazie do zmierzonego
kontaktu**, a każdy krok karmi następny. Sedno zapisane w specu 7 sierpnia:
**post, którego nie ma, nie jest kandydatem na reklamę.** Publikacja natywna na kanale nie jest
celem samym w sobie — jest produkcją inwentarza reklamowego.

```
   KATALOG                    Ruslan w panelu WP: „do kolejki"
   3 236 ofert  ─────────────────────────┐
   CPT listings                          │
   REST hub (fakty: cena, moc, zasięg)   ▼
   huby modeli (landing)          ┌──────────────┐
                                  │   KOLEJKA    │  tydzień × kanał, limity, cooldown
   MATERIAŁ                       │  (tabela WP) │
   Dysk: sesje/RRRR-MM-DD/  ─────►└──────┬───────┘
   zdjęcia importu (maskowanie)          │
                                         ▼
                                 ┌───────────────┐
                                 │  GENERATOR    │  claude -p + fakty z bazy
                                 │  copy per     │  bramki: fakty, lint, limity, cudzysłowy
                                 │  platforma    │
                                 └───────┬───────┘
                                         │
                    ┌────────────────────┼────────────────────┐
                    ▼                    ▼                    ▼
            ┌───────────────┐   ┌────────────────┐   ┌────────────────┐
            │  PUBLIKACJA   │   │   GOTOWCE      │   │   KREACJE      │
            │  natywna, jako│   │  dla Andrzeja  │   │  do Ads        │
            │  zaplanowana  │   │  (stara Strona)│   │                │
            │  FB IG YT TT  │   │  ręczne wklej. │   │  DG / wideo    │
            └───────┬───────┘   └────────┬───────┘   └────────┬───────┘
                    │                    │                    │
                    └────────────────────┼────────────────────┘
                                         ▼
                                 ┌───────────────┐
                                 │   PROMOTOR    │  ◄── OGNIWO, KTÓREGO NIE MA W SPECU
                                 │ post → reklama│
                                 │ Meta: promuj  │
                                 │ TikTok: Spark │
                                 │ YT: DG asset  │
                                 │ katalog: DABA │
                                 └───────┬───────┘
                                         ▼
                                 ┌───────────────┐
                                 │    POMIAR     │  piksel · GA4 · GSC brand · Graph Insights
                                 │  magazyn data/│  YouTube Analytics · Ads
                                 └───────┬───────┘
                                         │
                     ┌───────────────────┴───────────────────┐
                     ▼                                       ▼
              REJESTR (T-243f)                        DASHBOARD (T-207)
          co poszło, co padło                    ile kosztuje kontakt
                     │                                       │
                     └──────────────► ROTACJA ◄──────────────┘
                          co zadziałało wraca z nowym ujęciem,
                          co nie zadziałało schodzi z kolejki
```

### Trzy spięcia, które robią z tego kombajn, a nie zestaw skryptów

**Spięcie pierwsze — oferta jest jedynym punktem prawdy.** Copy nie powstaje „o modelu",
tylko o konkretnej sztuce z bazy: cena, wersja, moc, zasięg, liczba dostępnych egzemplarzy,
deep-link. Fact-check liczbowy jest bramką blokującą, nie sugestią — post z błędną ceną auta
za 230 tysięcy idzie pod marką Ruslana i po publikacji jest praktycznie nie do odkręcenia.
Sprzedane listingi robią 301 na hub modelu, więc żaden opublikowany link nie umiera w 404.

**Spięcie drugie — publikacja i reklama to jeden krok, nie dwa projekty.** Tu jest luka
w dotychczasowym specu: fazy a–h opisują dystrybucję i rejestr, ale **nie opisują momentu,
w którym opublikowany materiał staje się reklamą.** A to jest cały sens: rolka na Instagramie
jest kreacją dla M2, post Andrzeja jest kandydatem do promowania (M4), film na YouTube jest
zasobem dla `[DG]`, a oferta w katalogu jest pozycją w dynamicznych reklamach (M1). Jeden
materiał, cztery role. Moduł `promotor.py` to domyka i **dopisuję go do parasola jako fazę i.**

**Spięcie trzecie — pomiar wraca do kolejki.** Rejestr i dashboard nie są raportem na koniec,
tylko wejściem do rotacji: model, który złapał, wraca z innym ujęciem; model, który nie
złapał, schodzi. Bez magazynu w `data/` ta pętla się nie zamyka, bo nie ma czego porównać —
i dlatego kolektor metryk jest w fali startowej, a nie na końcu.

### Co ten kombajn zastępuje

Dziś obieg jest ręczny w całości i widać to na liczbach: sesja z 24.08 dała dwanaście filmów,
których opublikowanie zajęło osobną sesję roboczą z przeklikiwaniem metadanych w Studio przez
Chrome, ściągę z tytułami i opisami trzeba było napisać ręcznie, na Facebooku posty powstają
ręcznie do dziś, na Instagramie nie powstają wcale, a na TikToku nic nie wyszło od 14 lipca.
**Fala 2 poszła całkowicie poza pipeline'em** — bo pipeline'u nie ma.

---

## 4. Gdzie jest nasza rola, skoro Andrzej publikuje sam

Pytanie postawione przez Janka 27.08, po zobaczeniu, że Andrzej publikuje na obu Stronach
i mógłby równie dobrze publikować na Instagramie. Odpowiedź musi być uczciwa, bo od niej
zależy, co w ogóle budujemy.

**Andrzej robi publikację. Publikacja to nie dystrybucja.** Post organiczny na Stronie
ze 153 fanami dociera do kilkudziesięciu osób; na starej, przy zasięgu organicznym Facebooka
rzędu kilku procent, do kilkuset z 16 676 obserwujących. Ta sama treść jako reklama dociera
do tysięcy właściwie dobranych ludzi, którzy o Prima-Auto nigdy nie słyszeli. Różnica nie leży
w tym, kto wciska „opublikuj".

**Jeśli Andrzej może publikować na Instagramie — niech publikuje.** Walka o to, kto klika
przycisk, jest walką o niewłaściwą rzecz. Nasza wartość zaczyna się w momencie, w którym post
już istnieje, i sprowadza się do trzech rzeczy, których Andrzej nie robi i nie ma powodu robić:

1. **Zamiana posta w reklamę** — targetowanie, budżet, wykluczenia, pomiar kosztu kontaktu.
   Dwadzieścia gotowych kreacji miesięcznie leży dziś niewykorzystanych.
2. **Budowa audiencji** — aktywo, które zostaje i rośnie niezależnie od pojedynczego posta.
   Post ma żywotność dwóch dni; audiencja pracuje miesiącami.
3. **Sprzężenie zwrotne** — który model niesie, który nie, co wraca do rotacji, co schodzi.

Punkt drugi jest najważniejszy i najbardziej zaniedbany, co widać na liczbie poniżej.

---

## 5. Filmy razem z katalogiem — silnik audiencji, którego dziś nie ma

**Zmierzone 27.08: kanał YouTube nie jest połączony z kontem Google Ads.** Zapytanie
o `account_link` na koncie `9506068500` zwraca pustkę. Konsekwencja jest dotkliwa:

> **63 556 wyświetleń na kanale nie zbudowało ani jednej audiencji. Każdy widz przepadł.**

Na koncie Ads jest 27 list odbiorców — wszystkie z ruchu na stronie (największa:
„Wszyscy odwiedzający — 90 dni", 6 200 osób w Display). **Ani jednej listy widzów wideo.**
Filmy pracują dziś wyłącznie jako treść, a mogłyby pracować jako maszyna profilująca.

### Co to znaczy w praktyce — sekwencja zamiast pojedynczych kampanii

```
   film o Denzy Z9 GT (YouTube, już opublikowany)
        │
        ▼  obejrzał ≥ 50%
   AUDIENCJA „widzowie Denzy"          ← wymaga połączenia kanału z Ads
        │
        ▼  reklama z katalogu: konkretne egzemplarze Denzy, cena, dostępność
   WEJŚCIE NA OFERTĘ
        │
        ▼  tag Ads + piksel Meta: ViewContent
   AUDIENCJA „oglądał ofertę Denzy"
        │
        ▼  domknięcie: remarketing z tą samą sztuką i ceną
   KONTAKT: telefon · WhatsApp · formularz
        │
        └──► LOOKALIKE z tych, którzy się skontaktowali → nowi odbiorcy o tym samym profilu
```

Ten sam mechanizm na Meta, z jedną różnicą: **wideo trzeba tam wgrać jako reklamę**, bo Meta
nie importuje widzów z YouTube. Za to daje precyzyjniejsze progi — audiencje z obejrzenia
25%, 50%, 75% i ThruPlay, osobno per film, czyli per model.

### Materiał, który już mamy, pokrywa katalog

Dwanaście filmów z fali 2 to sześć modeli: **BYD Shark 6, Deepal G318, BYD Leopard 7,
Denza Z9 GT, Denza N9, Lynk & Co 900.** Starsze filmy dokładają Exeed VX, Jetour T2,
BYD Leopard 5 i Voyah Passion. Każdy z tych modeli ma oferty w katalogu i własny hub.
Mamy więc komplet: materiał budujący zainteresowanie, audiencję, do której trafia, i ofertę,
którą się domyka. Brakuje wyłącznie połączeń między tymi trzema.

### Nowa pozycja dla klienta — dwie minuty, nie wymaga konta marki

**To jest osobna rzecz niż przeniesienie kanału na konto marki i znacznie prostsza.**
W Google Ads wysyłamy prośbę o powiązanie kanału; właściciel kanału akceptuje ją w YouTube
Studio → Ustawienia → Kanał → Ustawienia zaawansowane → Powiązane konta. Działa na zwykłym
koncie Google, nie czeka na nic, nie wymaga niczyjej migracji.

**Od chwili akceptacji widzowie wpadają do list i można ich reklamować.** Przeniesienie
na konto marki dalej jest potrzebne — dla bezpieczeństwa kanału, automatycznej publikacji
i dostępu do Analytics — ale **nie blokuje zbierania audiencji.** Ten argument jest w rozmowie
z klientem mocniejszy niż „żebyśmy nie klikali ręcznie": bez tego każdy widz kanału przepada
bezpowrotnie, a kanał ma 63,5 tysiąca wyświetleń.

---

## 6. Projekt kampanii — cztery na Meta, dwie zmiany w Google

Konto Meta jest **puste**. To nie jest „włączanie wyłączonych kampanii", jak zakładał
prompt §5 — to budowa od zera. Poniżej struktura docelowa.

### M1 · Katalog — dynamiczne reklamy pojazdów
**Priorytet 1. Startuje pierwsza, bo nie wymaga ani jednej kreacji ręcznej.**

3 236 pojazdów w katalogu, piksel zbiera ViewContent. Kampania sama dobiera auto do człowieka:
retargeting osobom, które oglądały konkretną ofertę, i prospecting z szerokiego targetu.
Sprzedane listingi robią 301 na hub modelu, więc reklamy nie prowadzą w 404.

- Cel: Sprzedaż / konwersje (`click_phone`, `click_whatsapp`, formularz)
- Umieszczenia: automatyczne (Feed, Reels, Stories, Marketplace, Audience Network)
- Budżet startowy: **20 zł/dz** · Oczekiwanie: to powinien być najtańszy kontakt na Meta
- Zależność: żadna. **Można uruchomić w dniu potwierdzenia karty.**

### M2 · Wideo z naszych filmów — nowi odbiorcy
**Priorytet 2. To jest odpowiedź na „reklamy do nowych na wszystkich mediach".**

Sześć pionów 9:16 z fali 2 jako Reels i Stories, sześć poziomów 16:9 jako Feed i In-stream.
Jeden zestaw reklam na auto, żeby dało się odczytać, który model niesie.

- Cel: **ruch i zdarzenia kontaktowe, nie ThruPlay** — obejrzenia nie są konwersją
  (reguła `reference_ads_konwersje_tylko_kontakt`)
- Target: Polska, 25–55, zainteresowania motoryzacyjne + import; **nie odznaczać „Nieznany" przy wieku**
  (72% ruchu `[DG]` to `AGE_RANGE_UNDETERMINED`)
- Budżet startowy: **25 zł/dz**, po 14 dniach lookalike z piksela
- Zależność: pliki idą z Dysku na serwer (scope `drive` mamy) — Graph API publikuje z publicznego URL

### M3 · Remarketing z ruchu strony
**Priorytet 3. Po zebraniu audiencji.**

Audiencje z piksela: oglądający oferty 30 dni, oglądający huby 90 dni, porzucone zamówienia.
`tmp/social/audiencje_utworz.py` jest napisany, `audiencje_id.json` pusty — nic nie utworzono.

- Budżet: **15 zł/dz** · Zależność: audiencje muszą uzbierać minimum ludzi (zwykle 2–3 tygodnie)

### M4 · Promowanie postów Andrzeja — WYKONALNE OD RĘKI, najkrótsza droga do efektu
**Priorytet 0. Sprawdzone 27.08 na screenie od Janka i potwierdzone w Graph API.**

Wcześniejsza diagnoza w tym dokumencie była błędna. Andrzej **publikuje na naszej nowej
Stronie** `1146829831857839`, nie tylko na starej. Zmierzone page tokenem:

```
20 postów w ostatnich 30 dniach — średnio co półtora dnia
wszystkie:  is_eligible_for_promotion = true
format:     object_story_id = 1146829831857839_<post_id>
w tym Reels: BYD DaHan 25.08, Voyah Passion 18.08
```

Posty są kompletne i lepsze niż to, co byśmy wygenerowali na start: model, rocznik, przebieg,
cena brutto „w Polsce po wszystkich opłatach", pełna specyfikacja, lista wyposażenia,
**deep-link do konkretnej oferty** (`primaauto.com.pl/oferta/denza-n8l-dm-2026-449699/`),
hashtagi per model. Do tego blok migracyjny kierujący obserwujących starej Strony na nową —
i to działa, nowa Strona urosła ze 145 do 153 fanów w ciągu jednego dnia.

**Co z tego wynika dla planu:** nie potrzebujemy prosić Andrzeja o nic, nie dotykamy starej
Strony, nie budujemy generatora gotowców, żeby ruszyć. Materiał reklamowy powstaje codziennie
sam. Brakuje wyłącznie ostatniego kroku — zamiany posta w reklamę.

- Kampania: konwersje, `object_story_id` istniejącego posta jako kreacja
- Wybór: auta z realnym zapasem i marżą, nie wszystko jak leci — decyzja Ruslana albo reguła z bazy
- Budżet startowy: **15 zł/dz** na dwa–trzy posty tygodniowo
- Zależność: **wyłącznie karta.** Zero pracy po stronie klienta

> **Stara Strona `prima1auto` zostaje nietknięta.** Andrzej ma tam Managera, publikuje na obu
> i przy okazji ofert kieruje ludzi na nową. Nie prosimy o rolę reklamodawcy, nie składamy
> wniosku o dostęp, nie przepinamy stopki. Ustalenie Janka z 27.08.

### G1 · Kreacje z fali 2 do `[DG]` — Google Ads
`[DG]` ma CPA 24 zł i jest najlepszą skalowalną kampanią na koncie. Materiał z fali 2 wchodzi
tam jako nowe zasoby wideo. **Po pierwszych odczytach**, zaczynając od tego Shorta, który złapie.

### G2 · Przestawienie budżetów — skrypt gotowy, nieodpalony
`tmp/social/ads_przestaw.py`: pauza `[VID]` (437 zł, 34 kliknięcia, zero sesji w GA4),
`[DG]` z 20 na 35 zł/dz. Osobno do rozstrzygnięcia: `[SKAG-2] W drodze` pali **759 zł
na 30 dni przy zerze konwersji** — to najdroższa pozycja bez wyniku na koncie.

---

## 7. Dashboard — co to ma być i dlaczego dwa razy go zapisaliśmy

Dashboard występuje w dwóch miejscach i to **nie jest ta sama rzecz**:

**T-243f „Rejestr i wyniki"** (11–15 h) — kalendarz tygodnia razy kanał: co zaplanowane,
co poszło, co padło, plus wyniki postów z Graph Insights i YouTube Analytics. Odpowiada
na pytanie „co się dzieje na kanałach". Janek nie ma bezpośredniego dostępu do kont,
Ruslan musi widzieć całość, żeby nie zdublować tematu.

**T-207 „Dashboard marketingowy"** (22–28 h) — koszt, konwersje, ROAS, trendy z Ads,
GA4, GSC i z bazy. Odpowiada na pytanie „ile nas kosztuje klient".

**Wspólny problem obu: historia nie istnieje.** Katalog `data/` jest pusty, zero cronów
raportowych, jedyny szereg czasowy martwy od 19 maja. Każdy raport liczymy ad hoc i nigdzie
nie zostaje — dziś potrafimy zapytać o dowolną metrykę, ale nie wiemy, jaka była miesiąc temu.

**Propozycja: rozdzielić magazyn od prezentacji i zacząć od magazynu.** Kolektor dobowy
(Ads + GA4 + GSC + liczby z bazy → JSONL w `data/`) to **3–4 h** i od dnia uruchomienia
buduje szereg czasowy, na którym stanie i rejestr, i dashboard. Bez niego oba są migawką.
Backfill z Ads i GSC da historię wstecz.

⚠️ Zastrzeżenie, które musi iść razem z każdym wykresem: **GA4 zaniża konwersje około
czterokrotnie** (11 rozpoczętych zamówień wobec 45 w bazie), a Ads liczy o 56% więcej
konwersji niż GA4 zdarzeń kontaktowych. Dashboard odziedziczy ten błąd.
I nie pokaże atrybucji per zamówienie — nie zapisujemy `gclid` ani `utm` przy zamówieniu.

---

## 8. Kolejność i godziny

### Rusza natychmiast, nie czeka na nikogo

| | Co | Realnie |
|---|---|---:|
| **S1** | **Reels z fali 2 na Instagram** — pliki z Dysku na serwer, publikacja przez Graph API. Token działa, materiał na Dysku, zero montażu. Jedyny kanał, na którym dziś nie ma nic, a mamy czym publikować | 3–4 h |
| **S2** | **Gotowce dla Andrzeja** — sześć pakietów z fali 2. **Zeszły z priorytetu 1 na 2:** Andrzej pisze sam i pisze dobrze, więc to nie jest droga do pierwszego efektu, tylko dołożenie materiału wideo tam, gdzie dziś idą same zdjęcia z importu | 4–6 h |
| **S3** | **Kolektor metryk do `data/`** — fundament rejestru i dashboardu, im wcześniej tym dłuższy szereg | 3–4 h |
| **S4** | **Prośba o powiązanie kanału YT z Ads** — wysyłamy z Ads, właściciel akceptuje w Studio (2 min). Od tej chwili 63,5 tys. wyświetleń przestaje przepadać i zaczyna budować listy odbiorców | 1 h |
| **S5** | **Czujnik kanału FB** (faza h, D-1 wariant A) — cron tygodniowy na Organic Social w GA4 z alarmem, gdy kanał Andrzeja przestanie dowozić. Najtańsza polisa w całym T-243 | 2–3 h |

### Rusza w dniu potwierdzenia karty

| | Co | Realnie |
|---|---|---:|
| **S6** | **M4 Promowanie postów Andrzeja** — 20 gotowych kreacji miesięcznie, zero pracy po stronie klienta, 15 zł/dz. **Najkrótsza droga od karty do pierwszej reklamy** | 2–3 h |
| **S7** | **M1 Katalog** — dynamiczne reklamy, 20 zł/dz | 3–4 h |
| **S8** | **M2 Wideo** — sześć zestawów z fali 2, 25 zł/dz | 5–7 h |
| **S9** | **G2 Przestawienie budżetów** w Google Ads (skrypt gotowy) | 1 h |

### Czeka na klienta

- **YouTube przez API** — konto marki plus `js@auranet.com.pl` jako menedżer (Ruslan w toku)
- **TikTok** — link w bio (2 minuty, 127 filmów bez ani jednego wejścia na stronę),
  konto firmowe, Business Center. Publikacja automatyczna i tak wymaga audytu aplikacji przez TikToka
- **`pages_manage_posts`** w tokenie — bez tego publikacja na nowej Stronie stoi

### Reszta parasola, po fali startowej

Kolejka i panel WP (14–18 h) → materiały z sesji (13–17 h) → generator pełny (13–17 h)
→ dystrybutor (21–27 h) → **promotor, nowa faza i (10–14 h)** → rejestr (11–15 h)
→ rotacja (9–13 h).

**Nowa faza i — `promotor.py`, most między publikacją a reklamą.** Bierze pozycję ze stanu
`opublikowany` i zamienia ją w kreację: na Meta reklama z `object_story_id` opublikowanego
posta, na TikToku Spark Ads (wymaga kodu autoryzacji od właściciela konta), na YouTube zasób
wideo do `[DG]` przez Ads API, dla ofert z katalogu pozycja w dynamicznych reklamach.
Idempotencja po `external_id`, `--dry-run` obowiązkowy. **Bez tej fazy kombajn nie domyka
pętli** — publikujemy i mierzymy, ale nie reklamujemy tego, co opublikowane.

**Fala startowa S1–S9: 24–33 h.**
**Parasol T-243 po dodaniu fazy i: 99–133 h realnie** (było 89–119 h), zużyte 4 h.

---

## 9. Pomiar — jedna reguła, bo łatwo o pomyłkę

**Miernikiem wideo jest brand search w GSC w oknie D+0…D+7 po publikacji, nie konwersje w Ads.**
8 sierpnia film Terenwizji z udziałem Ruslana dał 625 wyświetleń i 477 kliknięć brandowych
przy 63 i 22 dzień wcześniej — a w Ads nie zostawił śladu.

Narzędzia: `tmp/social/gsc_brand.py`, `tmp/social/brand_dziennie.py` (filtr `query contains "prima"`).
Pierwszy sensowny odczyt fali 2: **po 3 września**.

Dla kampanii Meta miarą jest koszt zdarzenia kontaktowego, porównywalny z Google Ads
(dziś średnia konta 49 zł, najlepsze `[Brand]` 15 zł i `[DG]` 24 zł). Zasięg organiczny
miarą nie jest — 153 fanów na nowej Stronie i 103 obserwujących na Instagramie to nie kanał,
to inwentarz reklamowy.
