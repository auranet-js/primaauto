# T-213 — Kalkulator kosztów importu (magnes na ruch)

> Status: **WARIANT B ZATWIERDZONY** (decyzja Janka 2026-07-14) · Rozmiar: M
> Godziny realnie: **14–18 h** (Janek ~3 h, AI ~11–15 h) · Rynkowo: 35–45 h
> Zakres: **osobny kalkulator + wpis** ⭐ · **plus** jedno zdanie zaufania na stronie oferty (bez rozbicia kosztów)
> Wariant A (rozbicie kosztów pod każdą ofertą) — **odrzucony**: ujawnia strukturę naszej ceny i nie daje ruchu z Google. Opis zostawiony niżej dla kontekstu decyzji.

## Po co

Trzy rzeczy naraz:
1. **Ruch z Google** — „ile kosztuje sprowadzenie auta z Chin", „cło na auto z Chin", „akcyza samochód elektryczny" to intencje wysoko w lejku. Konkurencyjny **blog** (nie dealer!) zbiera na takich treściach ~14 800 wejść/mc.
2. **Zaufanie** — pokazujemy, że znamy temat lepiej niż konkurencja (patrz dowód niżej).
3. **Uzasadnienie ceny** — tłumaczy, dlaczego cena „pod klucz" wygląda, jak wygląda.

## 🔍 DOWÓD: kalkulator konkurenta (azjaauto.com) zaniża koszt o ~7,8%

Rozłożyliśmy ich kalkulator na czynniki (oferta Forthing T5 2022, odczyt 14.07.2026).

**Ich liczby dodają się poprawnie — ale podstawa opodatkowania jest zaniżona.**

Odwrócenie ich wzorów pokazuje jednoznacznie:
- **Cło liczą od samej ceny auta** (26 500 zł), nie od wartości celnej.
- Akcyzę i VAT — od ceny plus cło.

A powinno być: **podstawą cła jest wartość celna = cena auta + transport do granicy UE** (u nich: 26 500 + 12 088 = **38 588 zł**). Transport wypada z podstawy całkowicie — mimo że sami go w kalkulatorze pokazują.

| Pozycja | Ich kalkulator | Poprawnie | Różnica |
|---|---|---|---|
| Cło 10% | 2 650 | **3 859** | +1 209 |
| Akcyza 3,1% | 904 | **1 316** | +412 |
| VAT 23% | 6 705 | **10 065** | +3 360 |
| **Podatki razem** | **10 259** | **15 240** | **+4 981** |
| **Koszt całkowity** | **63 732** | **68 713** | **+4 981 (7,8%)** |

### I druga rzecz: ich „prowizja 1999 zł" to fasada

Prowizja wygląda uczciwie i konkurencyjnie. Ale obok, w „opłatach", siedzi:

| Pozycja | Kwota | Komentarz |
|---|---|---|
| Tłumacz przysięgły + opłata dokumentowa | **2 826 zł** | realnie tłumaczenie dokumentów auta: 200–500 zł |
| Licencja eksportowa | **2 652 zł** | |
| Agencja celna | **2 391 zł** | |
| Odprawa celna importowa | **2 218 zł** | **drugi raz to samo, co agencja celna** |
| **Razem** | **10 087 zł** | pozycje, których klient nie zweryfikuje |

Marża siedzi w rozdmuchanych opłatach i we frachcie, nie w prowizji. *(Nasza uwaga wewnętrzna: my też mamy część marży w CIF — różnica jest taka, że nie udajemy transparentności, której nie ma.)*

**Materiał źródłowy:** `docs/analizy/2026-07-14-kalkulator-azjaauto-rozbior.md`

---

## ⚠️ Zasada nadrzędna: kalkulator NIE MOŻE wołać naszego pipeline'u cenowego

W `class-asiaauto-price.php` siedzi **multiplikator CIF — czyli część naszej marży**. Podpięcie publicznego kalkulatora pod ten silnik wystawiłoby na zewnątrz naszą strukturę cenową.

**Kalkulator ma własny, jawny zestaw stawek** (konfigurowalny w panelu): fracht, opłaty portowe, agencja celna, cło 10%, akcyza wg napędu, VAT 23%, homologacja, transport krajowy. Stawki publiczne i urzędowe — niczyja tajemnica.

---

## WARIANT A — kalkulacja na stronie każdej oferty (8–12 h)

**Tak robi azjaauto.com:** pod każdą ofertą pełne rozbicie „skąd ta cena".

**Za:**
- Buduje zaufanie w momencie decyzji zakupowej.
- Odpowiada na pytanie, które klient i tak sobie zadaje („czemu tak drogo?").
- Jest na 3056 stronach naraz — zero pracy contentowej.

**Przeciw — i to jest poważne:**
- ⚠️ **Ujawnia strukturę naszej ceny.** Klient zobaczy rozbicie i zacznie odejmować. A u nas część marży siedzi w CIF — więc albo pokazujemy prawdę (i widać pełną marżę), albo pokazujemy rozbicie „ładne" (i wtedy oszukujemy jak oni).
- ⚠️ **Zero wartości SEO.** Strony ofert i tak rankują na nazwę modelu. Rozbicie kosztów nie przyciągnie ani jednego nowego wejścia z Google.
- Musi zgadzać się co do złotówki z ceną oferty — inaczej klient znajdzie sprzeczność.

**Uczciwie: wariant A jest funkcją zaufania, nie magnesem na ruch.** I ma realny koszt w postaci odsłonięcia marży.

## WARIANT B — osobny kalkulator + wpis (14–18 h) ⭐ REKOMENDACJA

Samodzielne narzędzie: **klient wpisuje cenę auta w Chinach → dostaje rozbicie kosztów importu.** Plus obudowa contentowa.

**Za:**
- **To jest magnes na ruch.** Narzędzia zdobywają linki i są cytowane przez wyszukiwarki AI — artykuły rzadziej.
- **Nie ujawnia naszej marży** — liczy koszt importu (opłaty urzędowe i przewozowe), a nie cenę naszej usługi.
- Przechwytuje intencje wysoko w lejku, których dziś nie mamy wcale (dziś wygrywamy tylko frazy „{model} import").
- Naturalnie linkuje w głąb serwisu (mamy gotowe strony informacyjne o cle, akcyzie, homologacji).
- **Mocny hak contentowy: przy autach elektrycznych akcyzy nie ma wcale.** Mało kto o tym wie.

**Przeciw:**
- Wymaga treści (ale to i tak część bazy wiedzy — T-162).
- Klient może próbować porównać wynik z naszą ceną oferty (patrz niżej).

### Jak rozbroić pułapkę arytmetyki

Klient wyliczy koszt importu i porówna z naszą ceną. Trzy zabezpieczenia:
1. **Uczciwe, górne stawki.** Nie zaniżamy „żeby ładniej wyglądało" — pokazujemy realny koszt, z ryzykiem kursowym. Wtedy różnica wobec naszej ceny jest mała i wytłumaczalna.
2. **Wynik jako widełki**, nie jedna liczba („szacunkowo 115–135 tys.") — bo to jest prawda: nikt nie poda kwoty co do złotówki przed odprawą.
3. **Nie zestawiać kalkulatora z konkretną ofertą.** Żadnego „policz dla tego auta" obok ceny — to zaprasza do odejmowania.

Obok kalkulatora zdanie, które sprzedaje: *„Możesz przejść przez to sam — albo odebrać auto z placu w Rzeszowie, z homologacją, gwarancją i umową z polską firmą."*

### Wpis towarzyszący

> **„Dlaczego kalkulatory importu w internecie zaniżają koszt — i jak liczy się to naprawdę"**

Bez nazywania konkurencji (obowiązuje nasza zasada). Pokazujemy metodę: podstawą cła jest wartość celna **z transportem**, VAT nalicza się od ceny + cło + akcyza, akcyza zależy od napędu. **Kto liczy inaczej, ten dopłaci różnicę na odprawie.**

To ustawia nas jako tych, którzy znają temat — i tłumaczy, czemu nasza cena jest wyższa niż cudze wyliczenia: **bo jest prawdziwa.**

---

## ✅ DECYZJA (Janek, 2026-07-14): wariant B

Wariant A odrzucony. Konkurent swoim rozbiciem **zaniża podatki i ukrywa marżę w opłatach** — nie kopiujemy tego, a wersja uczciwa odsłaniałaby naszą marżę i nie dawałaby ani jednego wejścia z Google.

**Zakres do wykonania:**

1. **Kalkulator** (osobna strona): klient wpisuje cenę auta w Chinach → widełki kosztu importu z rozbiciem (fracht, opłaty portowe, cło, akcyza wg napędu, VAT, odprawa, homologacja, transport krajowy). Własne, jawne stawki — **NIE nasz pipeline cenowy**.
2. **Wpis towarzyszący**: „Dlaczego kalkulatory importu w internecie zaniżają koszt — i jak liczy się to naprawdę". Bez nazywania konkurencji.
3. **Zdanie zaufania na stronie oferty** (zamiast wariantu A):
   > *„Cena zawiera cło, VAT, akcyzę, transport, odprawę celną, homologację i przygotowanie — bez dopłat na odbiorze."* + link do kalkulatora.

   **Efekt zaufania bez odsłaniania kuchni.** Koszt: ~1 h, mieści się w tych 14–18 h.

## Testy

**Automatyczne**
- Wzór: 10 przypadków (różne ceny, napędy, pojemności) vs niezależne wyliczenie. Akcyza: 3,1% do 2000 cm³, 18,6% powyżej, **0% dla elektryków**.
- Zmiana stawek w panelu → kalkulator liczy po nowemu.

**Półautomatyczne**
- Porównanie wyniku z naszą realną ceną kilku ofert — czy różnica jest wytłumaczalna (jeśli kalkulator wychodzi **niżej** niż nasza cena o więcej niż kilkanaście procent, mamy problem komunikacyjny).
- Weryfikacja stawek z aktualnymi przepisami **przed publikacją** (piszemy o podatkach — błąd tu jest kosztowny wizerunkowo).

**MCP (Chrome)**
- Kalkulator na mobile, walidacja pól, CTA.

## Definicja zrobionego

- Kalkulator liczy poprawnie (podstawa celna z transportem) — i to jest nasza przewaga nad konkurencją.
- Nie ujawnia struktury naszej ceny (własne stawki, nie pipeline).
- Wpis wyjaśnia metodę i przechwytuje frazy informacyjne.
- Stawki konfigurowalne z panelu (kursy i opłaty się zmieniają).
- Wynik jako widełki, z uczciwym zastrzeżeniem.
