# `[FOTO]` — kampania ze zdjęć z sesji. Projekt, nic nie uruchomione

> Projekt z 2026-09-04, do decyzji Janka. Nic z tego nie istnieje na koncie —
> ani kampania, ani kreacje, ani wgrane obrazy. Materiał: **197 zdjęć z dwóch sesji**
> na Dysku (`Prima Auto/sesje/`), dziesięć modeli. Kontaktówka do wyboru kadrów:
> `https://auratest.pl/fe4f58fec53ctmp/primaauto-sesje-kontaktowka-2026-09-04.html`

## Po co osobna kampania, skoro mamy katalog

**Zdjęcia z sesji to jedyny materiał, którego nie ma nikt inny.** Feed produktowy niesie
zdjęcia ze źródła chińskiego — te same, co u każdego, kto importuje z tych samych giełd,
robione w hali pod świetlówką. Sesja to nasze auto, nasz kadr, polskie tło. Katalog i feed
mają w tej kampanii **zero udziału**: wszystkie kadry idą z Dysku, wgrywane jako `adimages`.

Wideo z tych samych sesji już pracuje w `[VID]`. Zdjęcia leżą niewykorzystane od lipca.

## Trzy formaty, każdy odpowiada na inne pytanie

### A. Karuzela „karty samochodów" — przekrój oferty

Jedna karuzela, dziesięć kart, na każdej **inny model** i jego najlepszy kadr z sesji.
Karta prowadzi na hub swojej serii (`/samochody/<marka>/<seria>/#oferty`), nagłówek karty
to model, opis — cena wejścia w serię.

Odpowiada na pytanie „co wy właściwie macie". Człowiek, który nie zna Prima-Auto, w jednym
przesunięciu widzi dziesięć aut i dziesięć poziomów cenowych. Meta pokazuje wyniki
**per karta**, więc po tygodniu wiadomo, który model klika — to jest darmowy sondaż
zainteresowania, którego nie da się kupić inaczej.

Kolejność kart ma znaczenie: pierwsza karta zbiera najwięcej. Proponuję najtańsze na przodzie.

### B. Karuzela jednego modelu — budowanie pożądania

Sześć do ośmiu kadrów **jednego auta**: przód, profil, wnętrze, konsola, bagażnik, detal.
Wszystkie karty na ten sam hub. To jest odpowiednik obejścia auta dookoła na placu.

Materiał wystarcza na **dziesięć takich karuzel** (13-31 kadrów na model), czyli tę samą
bibliotekę co przy wideo: budujemy raz, rotujemy pauzowaniem. Deepal G318 ma 31 kadrów,
Lynk & Co 900 — 27, Byd Shark 6 — 22.

### C. Kolekcja — wideo z sesji plus kafle ze zdjęć

Format `Collection`: na górze wideo z sesji (to samo, które pracuje w `[VID]`), pod nim
cztery kafle ze zdjęciami tego auta. Klik otwiera pełnoekranowe Instant Experience,
dopiero stamtąd wychodzi na stronę.

Najmocniejszy na telefonie, a telefon to **79,6% naszego ruchu**. Kosztuje najwięcej pracy
(osobny szablon Instant Experience) i jego sensownie robić dopiero, gdy A albo B pokaże,
który model niesie.

## Rekomendacja

Start od **A + dwie sztuki B** dla modeli, które wyjdą najtaniej i mają zapas.
A daje rozeznanie w zainteresowaniu, B pogłębia dwa modele. C dopiero po pierwszych danych.

```
[FOTO] Zdjęcia z sesji — karuzele                    15 zł/dzień
       cel: OUTCOME_TRAFFIC, optymalizacja LANDING_PAGE_VIEWS
       odbiorcy: PL 25-65, jak w [VID]
       wykluczenia: Wszyscy odwiedzający 180 dni + Kontakt 180 dni
       kreacje: A (10 kart, przekrój) + B ×2 (jeden model, 6-8 kadrów)
       reszta karuzel B powstaje od razu i czeka wstrzymana — jak biblioteka wideo
```

Osobna kampania, nie nowy zestaw w `[VID]`: inny cel optymalizacji (ruch, nie ViewContent)
i inny format, więc mieszanie zaburzyłoby naukę tamtego zestawu.

## Co trzeba zrobić technicznie

1. **Wybór kadrów** — z kontaktówki. Przy A dziesięć sztuk, przy B po sześć na model.
   To jedyna rzecz, której nie zrobię sam: który kadr jest dobry, widać okiem.
2. **Kadrowanie do 1:1** — sesja jest w poziomie, karuzela chce kwadratu (1080×1080).
   Crop centralny przez ffmpeg, bez rekompresji poza skalowaniem.
3. **Wgranie jako `adimages`** — zwraca `image_hash`, którym posługuje się kreacja.
4. **Kreacja** `link_data.child_attachments` — po jednej pozycji na kartę: `image_hash`,
   `link`, `name` (model), `description` (cena od).
5. **Sprawdzenie landingów** przed każdym wgraniem — te same pułapki co zawsze
   (Denza działa wyłącznie na slugach `z9-gt-dm-i` i `n9-dm-i`).

## Czego pilnować

**Ceny na kartach się starzeją.** „Od 145 tys." jest prawdą w dniu budowy kreacji; za miesiąc
najtańsza sztuka w serii może być droższa. Kreacji w Mecie nie da się edytować — trzeba
zbudować nową i podmienić. Dlatego albo odświeżamy karty raz w miesiącu skryptem, albo
rezygnujemy z cen na kartach i zostawiamy sam model. Rekomendacja: **ceny zostają**,
bo bez nich karta nie robi selekcji, a odświeżenie to jeden bieg skryptu.

**Kadr pionowy vs kwadrat.** Karuzela w Relacjach i Rolkach dostaje 9:16, w Aktualnościach
1:1. Zdjęcia z sesji są poziome, więc przy kadrowaniu do 9:16 traci się boki auta.
Proponuję **tylko 1:1** i placement bez Relacji — auto w poziomie nie mieści się w pionie.

## Czego ta kampania nie zrobi

Nie zastąpi dynamicznego remarketingu z katalogu (`[RMKT]`, odłożony do czasu, aż pula
z piksela urośnie). Tamten pokazuje **konkretny egzemplarz, który ktoś oglądał**.
Ta pokazuje **modele i naszą robotę zdjęciową** ludziom, którzy nas nie znają. Dwie różne
rzeczy i będą chodzić obok siebie.

---

# STAN WYKONANIA — 2026-09-04, kampania zbudowana

Zbudowana i wstrzymana. Struktura na koncie:

```
[FOTO] Zdjęcia z sesji — karuzele        120248942033610243   OUTCOME_TRAFFIC · PAUSED
 └ Karuzele PL 25-65 — zdjęcia z sesji   120248942042360243   15 zł/dz · LANDING_PAGE_VIEWS
      PL tylko mieszkańcy · 25-65 · advantage_audience 0
      FB: Aktualności, Marketplace, wyszukiwarka · IG: Aktualności
      wyklucza: Wszyscy odwiedzający 180 dni + Kontakt tel./WhatsApp 180 dni
      · Cała oferta — kadr 1     8 kart
      · Cała oferta — kadr 2     8 kart (te same modele, drugie zdjęcie z sesji)
      · Do 200 tysięcy           5 kart
      · Od 200 tysięcy           4 karty
```

27 kadrów z Dysku przyciętych do kwadratu 1080×1080 i wgranych jako `adimages`.
Karty mają włączoną automatyczną kolejność (`multi_share_optimized`), więc Meta sama
wypycha na przód model, który klika — to jest darmowy sondaż zainteresowania modelami.

## Teksty — z danych Google Ads, nie z wyczucia

Konto `9506068500`, ostatnie 30 dni, konwersja na klik (pomiar 04.09):

| Zasób | konw/klik | kliki |
|---|---:|---:|
| „Prima-Auto — bezpośredni importer aut z Chin. Ceny w ogłoszeniach…" | 3,25% | 727 |
| „Aktualne ogłoszenia z Chin — codziennie. Umowa agencyjna…" | 2,87% | 650 |
| „Import bezpośredni z Chin" | 2,75% | 409 |
| **„6-osobowy SUV premium"** | **5,30%** | 132 |
| **„Exeed VX (Omoda 11) — 189 000 zł"** | **7,32%** | 82 |

Stąd budowa karty: **cena w nagłówku** (najlepszy wzorzec na koncie), **typ nadwozia
z liczbą miejsc w opisie** (drugi najlepszy), a nazwa firmy z „bezpośrednim importerem"
otwiera tekst główny. Opisy o samym sprowadzaniu konwertują 0,3–0,6%, czyli dziesięciokrotnie
gorzej — dlatego zeszły na koniec.

Tekst główny liczy zapas przy każdej budowie kreacji (`tekst_glowny()`), liczba ofert
zaokrąglona **w dół do setek**, bo kreacji w Mecie nie da się edytować i liczba musi
zostać prawdziwa także za miesiąc.

## Dwie zasady, które wyszły przy budowie

**Karta = model + własny link.** Trzy kadry tego samego auta pod jednym linkiem to pokaz
slajdów, nie karuzela — pierwsza wersja miała dziewięć takich reklam i wszystkie poszły
do archiwum. Karuzela ma prezentować wybór, nie galerię jednego egzemplarza.

**Seria poniżej trzech sztuk nie wchodzi na kartę** (`MIN_ZAPAS`). Shark 6 z jedną sztuką
wypadł automatycznie — reklama serii, która schodzi pierwszego dnia, pali budżet.

## Oczekiwany koszt

Google Ads, 30 dni: średni CPC konta **0,55 zł**, Demand Gen (YouTube) 0,21 zł,
remarketing 0,32 zł, wyszukiwarka 2,11 zł. Przy 15 zł dziennie karuzela powinna dowozić
**25–50 kliknięć dziennie**. CPC powyżej 1 zł po tygodniu = wymiana kreacji, nie budżetu.
