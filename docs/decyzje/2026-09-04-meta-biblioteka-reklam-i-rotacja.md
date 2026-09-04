# Meta Ads — biblioteka reklam budowana raz, rotacja przez pauzowanie

> Ustalenia Janka, 2026-09-04. Zastępują dotychczasowy tryb pracy opisany w
> `docs/meta/plan-kampanii.md` sekcja 3 („rotacja tygodniowa" jako budowanie kreacji
> na bieżąco). Sam harmonogram materiału zostaje — zmienia się sposób wykonania.

## Decyzja

**Wszystkie materiały wchodzą do Mety raz, jako reklamy wstrzymane. Rotacja to odtąd
pauzowanie i odpauzowywanie, nie budowanie od nowa.**

Do kampanii wracamy wyłącznie wtedy, gdy pojawi się **nowy materiał** — nowa sesja
zdjęciowa albo nowe posty Andrzeja. Wtedy tylko dolewamy pozycje do istniejącego zestawu.
Powtarzalna robota przez API (upload, kreacja, reklama) wykonuje się jeden raz na materiał,
nigdy drugi.

## Co z tego wynika w praktyce

- **`[VID]`** — dziesięć filmów z sesji lipcowej i sierpniowej wgranych i zbudowanych jako
  dziesięć reklam w jednym zestawie, wszystkie `PAUSED`. Reklama wideo kieruje na hub serii
  (`/samochody/<marka>/<seria>/#oferty`), a hub nie znika — auta się w nim wymieniają.
  Ta biblioteka nie starzeje się i nie wymaga odbudowy.
- **`[POST]`** — dziesięć postów z żywym landingiem zbudowanych tak samo, `PAUSED`.
  Różnica wobec wideo: post linkuje do **konkretnej oferty**, która zejdzie w rotacji
  (48 h draft → 7 dni kosz → usunięcie), więc reklama z posta się starzeje.
  **Przed każdym odpauzowaniem sprawdzamy kod odpowiedzi landingu** — 301 znaczy, że auto
  zeszło i reklama trafia na hub zamiast na obietnicę z kreacji.
- **Sterowanie hypem.** Rusza zainteresowanie Denzą — odpauzowujemy Denzę. Rusza Zeekr —
  odpauzowujemy Zeekra. Jedno kliknięcie, zero pracy przez API.

## Struktura: reklamy w jednym zestawie, nie zestaw per model

Budżet i faza uczenia siedzą na **zestawie reklam**, nie na reklamie. Dziesięć zestawów
po jednym modelu to dziesięć budżetów i dziesięć faz uczenia — przy 25 zł dziennie żaden
nie dobiłby progu ~50 zdarzeń tygodniowo. Jeden zestaw z dziesięcioma reklamami ma jedną
pulę i jedną naukę, a odpauzowana reklama dziedziczy to, czego zestaw już się nauczył.

**Nowy zestaw zakładamy tylko wtedy, gdy różni się odbiorca albo cel** — inny kraj, wiek,
zdarzenie optymalizacji, inne wykluczenia. Model auta nie jest innym odbiorcą.

Wyjątek: gdy chcemy **zagwarantować** wydatek na konkretny model niezależnie od optymalizatora
Mety (ta przy kilku aktywnych reklamach sypie budżet w zwycięzcę, nie dzieli po równo) —
wtedy ten model dostaje własny zestaw z własnym budżetem. Duplikat zestawu to jedno wywołanie.

## Parametry ustalone 04.09

```
[VID]   25 zł/dzień · OUTCOME_LEADS, optymalizacja ViewContent · PL 25-65
        wykluczenia: Wszyscy odwiedzający 180 dni + Kontakt tel./WhatsApp 180 dni
[POST]  10 zł/dzień · OUTCOME_TRAFFIC, optymalizacja LANDING_PAGE_VIEWS · PL 25-65
        wykluczenie: Kontakt tel./WhatsApp 180 dni
[RMKT]  wstrzymane ~7 dni — pula z piksela zbiera od 28.08 i jest poniżej 1000 osób
```

Grupy podobnych odbiorców (lookalike 1% PL z zaangażowanych FB i IG) zakładane, ale
**do żadnego zestawu nie wpinane** — przy 25 zł dziennie jeden zestaw ma się czego uczyć,
dwa nie mają. Mają być gotowe na moment, gdy szerokie targetowanie przestanie dowozić.

Lookalike z bazy klientów odpada: 68 kontaktów przy progu 100 dopasowanych osób, plus
niepodpisany regulamin listy klientów. Prośba do klienta wycofana (decyzja Janka 04.09).

## Wybór materiału startowego

Kryterium: **tańsze auta** (szersza grupa realnie rozważających zakup) przy żywym zapasie
w serii. Wideo w kolejności ceny wejścia: T2 145 · VX 163 · G318 166 · Leopard 5 183 ·
Leopard 7 198 · Z9 GT 215 · Shark 6 250 · Lynk 900 254 · N9 275 (tys. zł).
Shark 6 ma zapas 1 sztuki — do włączenia dopiero po uzupełnieniu serii.

Posty do włączenia jako pierwsze (wybór Janka): **Mazda EZ-6 140 tys.** i
**Denza Z9 DM-i 240 tys.** Okno kwalifikujące post do włączenia: **14 dni od publikacji**.

## Czego to nie zmienia

Włączenie z budżetem pozostaje publikacją i wymaga osobnej zgody na konkretny moment —
sekcja 9 planu kampanii obowiązuje bez zmian. Budowa biblioteki to praca, nie publikacja.
