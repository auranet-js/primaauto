# 2026-07-25 — [DG] i [VID] naprawa: diagnoza wycieku budżetu + domknięcie kampanii wideo

> Kontynuacja `2026-07-24-youtube-kanal-demand-gen.md` i `2026-07-25-kontynuacja-yt-ads.md`.
> Stan zweryfikowany przez Google Ads API v21 + YouTube Data API v3, 25.07.2026.

---

## 1. Diagnoza [DG] 24069066886 — co się okazało

**Objaw z briefu („zjadła cały budżet w godzinę") był fałszywy.** Rozkład godzinowy 24.07: h18 = 12,32 zł, potem prawie stop, h23 dobiło 2,43 zł. Suma **15,55 zł z 20 zł** — budżet nie został wyczerpany. To normalny rozbieg Demand Gen.

**Prawdziwy problem: gdzie się wyświetlała.** Raport miejsc docelowych (227 pozycji, pokrycie 1235 z 2632 wyświetleń):

| Kategoria | Wyśw. | Udział | Koszt | Udział kosztu |
|---|---:|---:|---:|---:|
| youtube.com (feed/Shorts) | 473 | 38,3% | 1,00 zł | 13,9% |
| **Bajki i treści dla dzieci** | **368** | **29,8%** | **2,83 zł** | **39,2%** |
| Inne (mix, reality, clickbait) | 344 | 27,9% | 3,11 zł | 43,0% |
| Polityka / newsy | 49 | 4,0% | 0,28 zł | 3,9% |
| **Motoryzacja** | **1** | **0,1%** | **0,01 zł** | **0,1%** |

Czołówka wydatku: Śpiewające Brzdące, Peppa Pig, Disney Junior, Nick Jr., Masza i Niedźwiedź, Bing, Psi Patrol, Diana i Roma, Like Nastya.

**Korzeń — `optimizedTargetingEnabled = true`.** Twardy dowód z `ad_group_audience_view`:

| Źródło ruchu | Wyświetlenia |
|---|---:|
| Custom segment „Import aut z Chin — hasła + strony konkurencji" | **6** |
| Optymalizowane kierowanie (poza segmentem) | **2 626** |

Segment budowany z 8 haseł i 6 URL-i konkurencji dostał **0,2%** ruchu. Same wykluczenia kanałów by nie wystarczyły — długi ogon bajek jest nieskończony.

**Co działało poprawnie:** sieć 100% YOUTUBE (zero wycieku do Discover/Gmail/GDN), **zero aplikacji mobilnych** (`placement_type` tylko WEBSITE i YOUTUBE_VIDEO), wykluczenie listy „Wszyscy odwiedzający (suma)" aktywne.

**Demografia — 72% ruchu bez profilu** (użytkownicy wylogowani):

| Wiek | Wyśw. | Klik. | Koszt |
|---|---:|---:|---:|
| Nieokreślony | 1 830 | 32 | 8,72 zł |
| 25–34 | 172 | 3 | 1,42 zł |
| 35–44 | 170 | 7 | 1,49 zł |
| 65+ | 163 | 2 | 1,39 zł |
| 45–54 | 142 | 3 | 1,48 zł |
| 55–64 | 120 | 0 | 0,78 zł |
| 18–24 | 35 | 0 | 0,26 zł |

Płeć analogicznie: 1 829 nieokreślonych / 495 M / 308 K. **Wniosek: nie odznaczać „Nieznany" przy wieku — to odcięłoby trzy czwarte kampanii.**

**Urządzenia:** MOBILE 2 225 wyśw. / 44 klik. (84%), DESKTOP 243 wyśw. / **0 klik.** / 1,53 zł, TABLET 132 / 1, CONNECTED_TV 32 / 2.

**Jakość ruchu (GA4 24–25.07):** `(cross-network)` — 149 sesji, engagement rate **1,3%**, 1,62 odsłony/sesja, 0 kluczowych zdarzeń. Organic dla porównania: 4,3% i 3,33 PV/sesja. **Konwersje: zero, również `all_conversions`.**

---

## 2. Wykonane zmiany

### [DG] 24069066886
| Zmiana | Kanał |
|---|---|
| `optimized_targeting_enabled` → **false** | API |
| **47 kanałów dziecięcych** wykluczonych (negatywne placementy kampanii) | API |
| Reklama Exeed: 1 → **5 nagłówków**, 1 → **4 opisy** | API |
| Reklama Leopard: 3 → **5 nagłówków**, 2 → **4 opisy** | API |
| CTA „Dowiedz się więcej" na obu reklamach wideo | API |
| CTA „Więcej informacji" na karuzeli (i 4 kartach) | API |
| Harmonogram **6:00–24:00 + 0:00–1:00** ×7 dni | API |
| Wiek zawężony do **25–65+** (z „Nieznany" ON) | UI |

Z listy wykluczeń świadomie **usunięto Dorotę Zawadzką** — to treść dla rodziców, nie dla dzieci.

### [VID] 24060052062
| Zmiana | Kanał |
|---|---|
| CPV **0,07 → 0,10 zł** (decyzja Janka; w API było 0,01, w UI 0,07 — rozjazd) | UI |
| Limit częstotliwości **3 wyświetlenia / tydzień** | UI |
| Harmonogram **6:00–24:00 + 0:00–1:00** ×7 dni | UI |
| Reklama #1 przepisana: **0 → 5 nagłówków**, 3 opisy sprzedażowe, CTA „Sprawdź" | API |
| Nowa reklama **„VID — Leopard 5 czarny (placementy)"** (2 filmy, CTA, deep-link do oferty) | UI |
| Placementy: **13 kanałów** (było 10) | UI |

**Wykluczenia treści w [VID] świadomie pominięte** — kampania celuje wyłącznie w 13 wskazanych kanałów, placementy są twardym ograniczeniem, reklama fizycznie nie trafi na bajki ani gry.

---

## 3. Placementy [VID] — finalne 13 kanałów (wszystkie PL)

| Kanał | ID | Suby | Wyśw./mies | Rola |
|---|---|---:|---:|---|
| EVadam (Adam Borusewicz) | `UCEIa8kCZ5nFjYYxqAgOkZFw` | 40,4 tys. | **1 112 tys.** | EV, 45 filmów/mies |
| CHIŃSKIE AUTO CENTRUM Damian Chen | `UC8qoEv552CgX2_EwnGdYz5A` | 54,2 tys. | 415 tys. | konkurencja bezpośrednia |
| Wahacz TV | `UCC2YlQ-_bcTaw6b08YuG6gg` | 54,9 tys. | 243 tys. | moto, **44% treści CN/EV** |
| AutoMotoVlog | `UC1k5dShRgRkBn6NFA4hAVmA` | 44,6 tys. | 226 tys. | moto ogólne |
| AutoMadeInChina | `UCY3mFXRAq_zDZZbn7JSM97w` | 20,5 tys. | 212 tys. | nisza chińska |
| Eco-driver | `UCaQev114kBqVvMbaoXfxQ5A` | 33,5 tys. | 120 tys. | EV |
| ROOTBLOG.it | `UCotbpp8jxMclJ0s1kNHkXOw` | 51,3 tys. | 110 tys. | tech/lifestyle |
| PIOTR CHINY | `UCVigOS7bbFCHfZaYvfoHgjw` | 25,9 tys. | 107 tys. | nisza chińska |
| Autopunktmlawa_pl | `UCk2YrZO2Y1Rnb3UBHwH1aeA` | 9,3 tys. | 67 tys. | konkurencja (import USA) |
| Import z Chin – ChinskiRaport | `UCHrxqi2c-zGLDWYTe8KPAkw` | 14,6 tys. | 21 tys. | nisza chińska |
| Auta z Chin TV | `UCbi0rYMTf7OVki-UOZ1N5zw` | 1,87 tys. | 14 tys. | nisza chińska |
| TnT Cars | `UCWe0CxHcUdDd-LGlNOYuxCA` | 66 | 2,9 tys. | konkurencja (śladowy inwentarz) |
| Autoklasa | `UCE0Ds1aPUka9-hO6oVpJR4A` | 1,5 tys. | 0,9 tys. | konkurencja (śladowy inwentarz) |

**Usunięci:** Zachar OFF (458 tys. subów), Pertyn Ględzi (128 tys.), Daniel Grzyb (117 tys.).
**Powód (decyzja Janka):** przy budżecie 10 zł/dz i CPV 0,10 zł kupujemy ~100 obejrzeń dziennie — w kanale o 600 tys. wyświetleń/mies utoniemy, płacąc najwyższe stawki. Ten sam budżet daje 2× więcej obejrzeń na małych kanałach tematycznych.

**Autoklasa i TnT Cars zostają mimo martwego inwentarza** — to bezpośrednia konkurencja, chcemy być przy ich widzach niezależnie od zasięgu (poprawka Janka; przy 66 subach i tak nie zjedzą budżetu).

**Kryteria doboru kolejnych kanałów:** PL, 10–60 tys. subskrybentów, wysoka częstotliwość publikacji ważniejsza niż liczba subów (EVadam ma mniej subów niż Wahacz, a 4,5× większy inwentarz), tematyka chińskie marki / EV / import, konkurenci-importerzy cenni niezależnie od zasięgu.

---

## 4. Godziny publikacji na kanałach docelowych (11 kanałów × 50 filmów, Europe/Warsaw)

| Okno | Udział publikacji |
|---|---|
| **15:00–19:00** | **41,4%** (szczyt 16:00 = 12%, 18:00 = 11,8%) |
| 9:00–12:00 | 26% (10:00 = 10,5%) |
| 20:00–24:00 | 8,8% |
| **1:00–7:00** | **2,2%** — martwa strefa |

Per kanał: ROOTBLOG.it 18:00 (24/50), Wahacz TV 19:00 (28/50), AutoMadeInChina i Eco-driver 15–16:00, AutoMotoVlog 10:00 (20/50), PIOTR CHINY 8:00. Wyjątek: TnT Cars publikuje wieczorami i po północy. Najmocniejszy dzień: **niedziela 20,5%**, najsłabszy: sobota 10,7%.

**Uwaga interpretacyjna:** reklama wyświetla się przy *oglądaniu*, nie przy publikacji. Godzina publikacji mówi tylko, kiedy pojawia się świeży inwentarz (film zbiera najwięcej wyświetleń w pierwsze 24–48 h). Stąd harmonogram odcina tylko noc, gdzie martwe są obie strony.

---

## 5. Gotchas API (zweryfikowane, korekta wcześniejszych założeń)

**Kampania VIDEO jest niemal całkowicie zamknięta dla Ads API v21** — `MUTATE_NOT_ALLOWED` dla: `campaigns:mutate` (create i update), `adGroups:mutate` (**CPV!**), `campaignCriteria:mutate` (content labels), `adGroupCriteria:mutate` (placementy), `adGroupAds:mutate` **create**. Frequency cap → `OPERATION_NOT_PERMITTED_FOR_CAMPAIGN_TYPE`.
**Działa wyłącznie `ads:mutate` update istniejącej reklamy** (teksty, CTA). Wcześniejszy zapis „edycja istniejącej działa, CPV/freq/labels powinny przejść" był **BŁĘDNY**.

Limity pól:
- `videoResponsiveAd.callToActions` — **tekst, max 10 znaków** (nie asset; „Dowiedz się więcej" odrzucone → użyto „Sprawdź")
- `demandGenVideoResponsiveAd.callToActions` — **asset** CALL_TO_ACTION, **max 1 sztuka**
- karuzela DG `callToActionText` — tylko wybrane stringi (OK: „Więcej informacji", „Learn more"; odrzucone: „Dowiedz się więcej", „Zobacz więcej", „LEARN_MORE")
- `ads:mutate` — pole `name` jest **immutable** (nazwę ustawia się tylko przy tworzeniu)

**Demand Gen blokuje mutację demografii** (`adGroupCriteria` age range → `MUTATE_NOT_ALLOWED`) — tylko UI.
**ReachPlanService niedostępny** — Basic Access → `Developer Token not enabled`. Brak prognoz CPM/CPV z Google; wycena kanałów tylko z inwentarza YouTube Data API.
**`geographic_view` odrzucany dla Demand Gen** — nie da się potwierdzić rozkładu geo danymi.
**`campaignCriteria:mutate` z `partialFailure`** działa dobrze do masowych wykluczeń (47 kanałów jednym wywołaniem).

**UI Google Ads:** layout zwija poprzednią sekcję przy otwieraniu nowej → współrzędne z poprzedniego zrzutu natychmiast tracą ważność. Klikać przez `find` + `ref`, nie przez stałe koordynaty. Zapis reklamy może żądać potwierdzenia tożsamości (klika Janek).

---

## 6. Stan kampanii po sesji

Suma ENABLED bez zmian: **152 zł/dz** (realokacja, zero nowego budżetu).

| ID | Nazwa | Status | Budżet | Stan |
|---|---|---|---|---|
| 24069066886 | [DG] Demand Gen — auta z Chin | ENABLED | 20 zł | naprawiona, dziś 6,10 zł do h9 |
| 24060052062 | [VID] Placementy — widzowie konkurencji | ENABLED | 10 zł | ruszy przy CPV 0,10 zł |

**Do sprawdzenia jutro rano:**
1. Czy [VID] w ogóle ruszyła przy 0,10 zł (przez 2 dni: 0 wyświetleń, 0 zł).
2. Czy [DG] nie zdławiła się po wyłączeniu optymalizowanego kierowania — custom segment jest wąski (6 wyświetleń w 2 dni), może nie wydać 20 zł/dz. Jeśli stanie: poszerzyć segment albo wrócić do optymalizacji z twardszymi wykluczeniami.
3. Desktop w [DG]: 243 wyświetlenia, 0 kliknięć — jeśli utrzyma zerowy CTR, wyciąć.
4. Reklama „VID — Leopard 5 czarny" — status był „w trakcie sprawdzania".
5. Karuzela DG `818158234980` — od 24.07 `REVIEW_IN_PROGRESS`, 0 wyświetleń. Jeśli nadal wisi, sprawdzić powód w UI.

**Nakładanie [DG] × [VID]:** obie serwują na YouTube PL na tych samych kreacjach. Google nie dopuści dwóch reklam z jednego konta do tej samej aukcji, więc nie płacimy podwójnie. Realne ryzyko to **częstotliwość** — cap 3/tydz. w [VID] nie obejmuje [DG] (Demand Gen nie wspiera capów). Rozwiązanie, gdyby dane pokazały problem: wykluczyć w [DG] te 13 kanałów, na które celuje [VID]. Na razie zostawione — przecięcie inwentarza minimalne.
