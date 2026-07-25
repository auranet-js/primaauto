# Kontynuacja 2026-07-25 — YouTube + kampanie wideo (stan zweryfikowany przez API)

> Wklej ten plik (lub: „przeczytaj `docs/sesje/2026-07-25-kontynuacja-yt-ads.md`") w nowym wątku.
> Tło i historia: `docs/sesje/2026-07-24-youtube-kanal-demand-gen.md` + memory `project_youtube_kanal_i_demand_gen_2026_07_24.md`.
> Stan sprawdzony przez Google Ads API + YouTube Data API **25.07.2026, 08:09**.

---

## 1. Zadania na ten wątek (priorytet)

### A. Zweryfikować, czy [DG] robi to, co ma robić
**Objaw:** 24.07 kampania zjadła **cały dzienny budżet w ~1 godzinę** (15,55 zł z 20 zł przy starcie po południu). To może być normalny rozbieg Demand Gen, ale może też oznaczać, że system leje ruch w tanie, bezwartościowe miejsca.

Do sprawdzenia (raport miejsc docelowych + segmenty):
- gdzie realnie się wyświetlała (`detail_placement_view` / `group_placement_view`) — czy to sensowne kanały, czy „dzieci + gry + śmieci",
- segment `segments.ad_network_type` i format (In-Stream vs In-Feed vs Shorts),
- czy „optymalizowane kierowanie" (ON) nie wychodzi poza nasz custom segment,
- czy wykluczenie listy „Wszyscy odwiedzający (suma)" faktycznie działa,
- jakość ruchu: czas na stronie / bounce z GA4 dla źródła DG (skrypt `tmp/ga4_query.py`).

Decyzje do rozważenia: dodać wykluczenia treści (gry, dzieci), ograniczyć formaty, ewentualnie zbić budżet lub przejść na tCPA po nazbieraniu konwersji.

### B. Dokończyć kampanię [VID] — jest niekompletna
Janek dokończył ją „na szybko", żeby nie stracić zmian po wyczerpaniu limitu. Braki:
1. **Stawka CPV = 0,01 zł** (1 grosz) przy sugestii Google 0,07 zł → **0 wyświetleń, 0 zł wydane**. To główna przyczyna, że kampania stoi. Do podniesienia na 0,07–0,10 zł (**decyzja o kwocie: Janek**).
2. **Brak limitu częstotliwości** — miało być 3 wyświetlenia/tydzień.
3. **Brak wykluczeń treści** (gry, treści dla dzieci).
4. Do przemyślenia: nagłówki/CTA reklamy, docelowy URL (obecnie hub Exeed z kotwicą `#oferty` — czy nie lepiej karta konkretnej oferty albo `/w-rzeszowie/`).

### C. Przegląd 10 placementów — wymiana / modernizacja
**Dlaczego 10:** Google wymaga **minimum 10 miejsc docelowych**, żeby kampania z placementami mogła ruszyć (UI blokował: „Musisz mieć więcej miejsc docelowych"). Janek dobrał 6 dodatkowych ad hoc. Do przeglądu, czy zostawiamy, czy podmieniamy na lepiej dopasowane (np. kanały czysto o chińskich autach / EV, testy modeli, które faktycznie oglądają nasi klienci 45–64).

Obecne placementy (`ad_group` 205431496984):

| Kanał | ID | Subskrybenci | Trafność |
|---|---|---|---|
| PIOTR CHINY | `UCVigOS7bbFCHfZaYvfoHgjw` | 25,9 tys. | rdzeń (życzenie Janka) |
| Autopunktmlawa_pl | `UCk2YrZO2Y1Rnb3UBHwH1aeA` | 9,3 tys. | rdzeń (importer konkurencyjny) |
| Autoklasa | `UCE0Ds1aPUka9-hO6oVpJR4A` | 1,5 tys. | rdzeń |
| AutoMadeInChina | `UCY3mFXRAq_zDZZbn7JSM97w` | 20,4 tys. | rdzeń |
| Zachar OFF | `UCCY4fePCsrmoP7zib9KhvBA` | 458 tys. | dodane 24.07 — szeroki moto |
| Pertyn Ględzi | `UCbICoaifEiEEzZQXcVW_mKg` | 128 tys. | dodane 24.07 |
| Daniel Grzyb | `UCJoL0WKiqT_jrjuTIrLRRHg` | 117 tys. | dodane 24.07 |
| CHIŃSKIE AUTO CENTRUM Damian Chen | `UC8qoEv552CgX2_EwnGdYz5A` | 54,2 tys. | dodane 24.07 — trafne tematycznie |
| ROOTBLOG.it | `UCotbpp8jxMclJ0s1kNHkXOw` | 51,3 tys. | dodane 24.07 |
| TnT Cars | `UCWe0CxHcUdDd-LGlNOYuxCA` | 66 subów | dodane 24.07 — do wymiany (martwy) |

---

## 2. Stan faktyczny na 25.07 08:09 (zweryfikowany API)

### Kampanie — suma ENABLED = **152,00 zł/dz** (bez nowego budżetu, zgodnie z ustaleniem)

| ID | Nazwa | Status | Typ | Budżet |
|---|---|---|---|---|
| 23779860635 | [Brand] Prima-Auto | ENABLED | SEARCH | 10 zł |
| 23779860638 | [Topic] Import z Chin | ENABLED | SEARCH | 35 zł |
| 23779860641 | [SKAG] Marki-Modele | PAUSED | SEARCH | 50 zł |
| 23803851563 | [SKAG-1] Na placu | ENABLED | SEARCH | 20 zł |
| 23803851566 | [SKAG-2] W drodze | ENABLED | SEARCH | 25 zł |
| 23803851569 | [SKAG-3] Popularne sprowadzenie | PAUSED | SEARCH | 25 zł |
| 23896725555 | [DSA] Import modele z Chin | ENABLED | SEARCH | **15 zł** (było 45) |
| 23897599362 | [RMKT] Dynamic Remarketing | ENABLED | DISPLAY | 17 zł |
| **24060052062** | **[VID] Placementy — widzowie konkurencji** | ENABLED | VIDEO | **10 zł** |
| **24069066886** | **[DG] Demand Gen — auta z Chin (YouTube)** | ENABLED | DEMAND_GEN | **20 zł** |

### [DG] id 24069066886 — DZIAŁA
- Grupa reklam: `195605725661` „DG — świeży ruch PL (YouTube)".
- Wyniki 24.07 (dzień niepełny): **2632 wyśw., 47 klik., 249 obejrzeń, 15,55 zł, 0 konwersji**. CTR ~1,8%, CPC ~0,33 zł.
- Per reklama: Leopard 5 czarny 1867 wyśw./33 klik./11,12 zł · Exeed VX (Omoda 11) 765 wyśw./14 klik./4,43 zł · **karuzela 0 wyśw. — REVIEW_IN_PROGRESS** (dwie reklamy wideo: APPROVED/REVIEWED).

### [VID] id 24060052062 — UTWORZONA, ALE NIE WYDAŁA ANI GROSZA
- Status: ENABLED, `servingStatus: SERVING`, `primaryStatus: ELIGIBLE`, start 2026-07-24, strategia `TARGET_CPV`, sieć: tylko YouTube (partnerzy wideo OFF).
- Grupa reklam `205431496984` „Placementy — kanały konkurencji", **CPV 0,01 zł**.
- Reklama `818295409418` (VIDEO_RESPONSIVE_AD, APPROVED/REVIEWED), 2 filmy (assety `398872064193`, `398799205438`), final URL `https://primaauto.com.pl/samochody/exeed/vx/#oferty`.
- Kryteria kampanii: język 1030 (polski), geo 2616 (Polska), 4× DEVICE. **Brak frequency caps, brak content labels.**
- Metryki: **0 wyświetleń, 0 zł**.

### Kanał YouTube `UCsfcUP3uwrDxPI_mDIle8Pw` (@primaauto_com_pl)
19 filmów, 2 subskrybentów, 487 wyświetleń (statystyka kanału laguje względem sumy filmów).
Filmy z 24.07 (nasze publikacje):

| Film | Wyświetlenia |
|---|---|
| Short: Exeed VX (Omoda 11) 189 tys. | **1350** |
| Short: Leopard 5 czarny 233 tys. | **1179** |
| Film: BYD Leopard 5 czarny 233 000 zł | 606 |
| Film: Exeed VX (Omoda 11) 189 000 zł | 328 |
| Short: Jetour T2 PHEV 201 tys. | 194 |
| Short: Leopard 5 Flagship 226 tys. | 10 |
| Film: Leopard 5 Flagship 226 000 zł | 8 |
| Film: Jetour T2 PHEV 201 000 zł | 8 |

Wniosek: **Omoda 11 (Exeed VX) faktycznie najlepsza** — potwierdzone. Trzy ostatnie z serii praktycznie nie dostały zasięgu (możliwe zduszenie przez wrzut całej paczki naraz — rozważyć rozłożenie kolejnych publikacji w czasie).

---

## 3. Techniczne (nie tracić czasu na ponowne odkrywanie)

- **Kanał YT: zapis tylko przez Chrome/Studio.** Kanał nie jest kontem marki, Janek ma delegację Studio → OAuth nie pokaże kanału. Odczyt przez API działa (`~/secrets/google/youtube-tokens.json`).
- **Kampanii VIDEO nie da się utworzyć przez Ads API** (`MUTATE_NOT_ALLOWED`), ale **edycja istniejącej działa** — CPV (`ad_group.cpv_bid_micros`), frequency caps i content labels powinny przejść mutacją.
- **Demand Gen:** frequency cap odrzucany przez API (`OPERATION_NOT_PERMITTED_FOR_CONTEXT`); content label `JUVENILE` odrzucony (`INVALID_EXCLUDED_CATEGORY`); geo/język w v21 wymagają poziomu grupy (UI).
- Konwersje `GOOGLE_HOSTED` (Local actions, YT subs/views) — **niemutowalne przez API**; sterujemy celami per kampania.
- Helper API: `bash gads.sh "<endpoint>" '<json>'` w scratchpadzie sesji (odtworzenie: token z `~/secrets/google/tokens.json` + `ads-config.json`, header `login-customer-id: 5565057411`, wersja **v21**, customer **9506068500**).
- UI Ads: teksty przez `find` + `form_input` (samo `type` gubi fokus), `Escape` zamyka kreator, zapis może żądać potwierdzenia tożsamości (klika Janek).

## 4. Reguły Janka
Nie robić rzeczy, o które nie prosił. Przy zmianach na produkcji/koncie — najpierw powiedzieć co dokładnie, potem robić. „Działaj" = wykonuj bez dopytywania. Bez nowego budżetu — tylko realokacja w ramach 152 zł/dz.

---

## 5. Prompt do wklejenia w nowym wątku

```
Kontynuujemy YouTube/Ads dla primaauto. Przeczytaj docs/sesje/2026-07-25-kontynuacja-yt-ads.md
(stan zweryfikowany API 25.07 rano) — nie sprawdzaj wszystkiego od nowa, tylko to, co się
mogło zmienić.

Trzy zadania:
1. [DG] 24069066886 zjadła cały budżet w godzinę — zweryfikuj przez API, czy robi to, co ma:
   raport miejsc docelowych, formaty, sieci, czy optymalizowane kierowanie nie wychodzi poza
   nasz segment, czy wykluczenie listy odwiedzających działa, jakość ruchu w GA4.
2. [VID] 24060052062 stoi na 0 wyświetleń — CPV 0,01 zł (sugestia 0,07). Dokończyć: stawka,
   limit częstotliwości 3/tydz., wykluczenia treści (gry, dzieci), nagłówki/CTA, docelowy URL.
   Stawkę zatwierdza Janek.
3. Przegląd 10 placementów (minimum wymagane przez Google) — czy wymienić słabe (TnT Cars
   66 subów) na lepiej dopasowane kanały o chińskich autach/EV.

Najpierw diagnoza + rekomendacje, wykonanie po „działaj".
```
