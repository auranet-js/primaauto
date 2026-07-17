# Umowa o współpracy z EFL (Credit Agricole) — analiza pod T-217/T-113

> Data analizy: 2026-07-17. Źródło: skan 34 str. (`2_5384554645924389298.pdf`, Google Drive
> primaauto/umowy, od mwbrothers.pl@gmail.com 16.07). Skan bez OCR, odczyt wizualny.
> Może przyjść lepsza wersja od Ruslana.
>
> **UWAGA:** to NIE jest wzorzec umowy pod T-217 (Janek wskazał go omyłkowo) — właściwy
> wzorzec umowy klienckiej analizuje
> [2026-07-17-umowa-organizacji-importu-leasing-analiza.md](2026-07-17-umowa-organizacji-importu-leasing-analiza.md).
> Ten dokument zostaje jako kontekst partnera finansującego (EFL) dla T-113/T-217.

## Co to za dokument

**Umowa o współpracy z 1.06.2026, nr 99/OSD/2093/26** — Ruslan Prima (PRIMA AUTO RUSLAN
PRIMA, NIP 8133898576, Rzeszów, Jerzego Pleśniarowicza 2A/38) jako **„Dostawca"** z
konsorcjum Credit Agricole:

| Podmiot | Produkt | Definicja |
|---|---|---|
| **EFL S.A.** | Umowa leasingu (też najem/dzierżawa) | „Umowa Leasingu" |
| **EFL Finance S.A.** | Umowa pożyczki | „Umowa Pożyczki" |
| **Carefleet S.A.** | Wynajem długoterminowy | „Umowa Wynajmu" |

Opiekun Salonu / przedstawiciel konsorcjum: **Tomasz Łuków**, tel. 570777233,
tomasz.Lukow@efl.com.pl. Przedstawiciel Dostawcy: Ruslan Prima, tel. 783807381,
mwbrothers.pl@gmail.com.

Struktura pliku: str. 1-14 umowa główna (14 str.); str. 15-20 **Aneks „Prowizja"** (6 str.);
str. 21-33 Załącznik nr 2 — umowa powierzenia przetwarzania danych (RODO, Prima Auto =
procesor); str. 34 Załącznik nr 5 — arkusz weryfikacji podmiotu przetwarzającego (Tak/Nie,
do wypełnienia).

**KLUCZOWE: to umowa partnerska Ruslan ↔ EFL, a NIE wzorzec umowy klienckiej.** Klient
NIE podpisuje umowy leasingu z Prima-Auto. Umowę leasingu przygotowuje i zawiera Opiekun
działający w imieniu EFL, w systemie informatycznym EFL (§1.2 aneksu). Prima-Auto
przekazuje wyłącznie **lead** („Informację o Zainteresowaniu").

## Model działania (aneks Prowizja)

1. Klient zainteresowany finansowaniem → Dostawca wypełnia w **Systemie EFL** (webowy)
   „Informację o Zainteresowaniu".
2. Opiekun EFL kontaktuje się z klientem, bada zdolność, przygotowuje i zawiera umowę.
3. Opiekun informuje Dostawcę o podpisaniu umowy i jej wartości najpóźniej następnego
   dnia roboczego.
4. Prowizja: **0,1% wartości ofertowej netto** (leasing/wynajem; pożyczka: 0,1% kwoty
   pożyczki, brutto), za zgodą mailową EFL do **3%**. Raport miesięczny od EFL w 7 dni po
   miesiącu → faktura VAT Dostawcy w **21 dni** (niewystawienie = zrzeczenie się prowizji!).
5. **Bez prowizji**, gdy klient w ciągu 30 dni przed zgłoszeniem dostał już ofertę EFL
   z innego źródła (System informuje automatycznie przy rejestracji leadu).

### Pola „Informacji o Zainteresowaniu" (§3.2 aneksu) — dane zbierane od klienta

a. imię i nazwisko osoby do kontaktu u klienta
b. nazwa firmy
c. adres korespondencyjny
d. numer telefonu
e. adres e-mail
f. NIP
g. opis przedmiotu leasingu / finansowania (u nas: auto)
h. rodzaj umowy: leasing / pożyczka / wynajem

Uwaga: pola „nazwa firmy" + NIP → produkty celowane w **B2B/przedsiębiorców** (leasing
klasyczny). Konsumenci: do potwierdzenia z EFL, czy konsorcjum obsłuży osobę fizyczną.

## Twarde ograniczenia prawne dla platformy

- **§4.3 umowy:** Dostawca NIE może zawierać umów w imieniu konsorcjum, NIE może
  obiecywać zawarcia umowy, NIE może informować klientów o uprawnieniach/korzyściach
  nieprzewidzianych w umowach EFL, NIE pobiera od klienta żadnych należności związanych
  z produktami finansowymi. → Treści na stronie: „możliwość finansowania", nigdy
  „dostaniesz leasing"; decyzja kredytowa zawsze po stronie EFL.
- **§2.4 umowy:** informacja o współpracy na publicznych stronach internetowych wymaga
  **wzajemnej akceptacji treści w formie dokumentowej (mail)**. → Zanim opublikujemy
  sekcję „leasing z Credit Agricole EFL" / logo, treść musi zaakceptować EFL. Dotyczy też
  użycia logo (§3.1 — każdorazowa zgoda).
- **§4.4 umowy (najważniejsze dla naszej umowy pośrednictwa):** jeśli klient wpłacił
  Dostawcy **zaliczkę** na auto, a następnie finansuje je leasingiem EFL → umowa nabycia
  Dostawca–klient **zostaje rozwiązana**, a zaliczka (po potwierdzeniu EFL) **przechodzi
  na poczet ceny sprzedaży należnej Dostawcy od EFL** (auto kupuje EFL, nie klient).
  → Nasz depozyt rezerwacyjny i umowa pośrednictwa wymagają **wariantu/aneksu pod
  scenariusz leasingowy** — inne strony transakcji (EFL kupuje auto), inne rozliczenie
  depozytu, inne wartości w umowie. To jest to „w innych miejscach się musi zmienić".
- Załącznik nr 2 (powierzenie danych): Prima Auto jako procesor danych klientów EFL —
  obowiązki bezpieczeństwa, audyty, wsparcie praw RODO. Jeśli platforma będzie zbierać
  lead „chcę leasing", zakres danych i podstawy trzeba spiąć z tą umową.

## Konsekwencje dla tasków

- **T-217 (wybór rodzaju umowy przy zamówieniu):** wariant „leasing" ≠ drugi PDF do
  wygenerowania. Realny kształt: (1) ścieżka zamówienia z wyborem finansowania,
  (2) zbieranie 8 pól leadu i przekazanie do Systemu EFL (na start może ręcznie przez
  Ruslana), (3) **zmieniona umowa pośrednictwa/depozytu** dla wariantu leasingowego
  (§4.4). Szczegóły do ustalenia z Ruslanem — jak chce operacyjnie prowadzić leady.
- **T-113 (leasing w kreatorze):** partner finansujący JEST — konsorcjum EFL. Kluczowe
  pytanie z roadmapy („czy sfinansuje auto z Chin, którego nie ma w kraju") — nadal
  otwarte, do potwierdzenia z Opiekunem (Tomasz Łuków).
- **T-189 (kalkulator raty):** parametry (oprocentowanie, okresy) docelowo do uzgodnienia
  z EFL; „od X zł/mc" musi być opisane jako przykład, nie oferta (§4.3).
- Pasek zaufania „Możliwy leasing" (v0.33.31) — zgodny z umową (informuje o możliwości,
  niczego nie obiecuje).
- Prowizyjny pilnowacz terminów (raport EFL → faktura w 21 dni albo prowizja przepada) —
  proces po stronie Ruslana/księgowości, nie platformy; warto mu to powiedzieć wprost.

## Otwarte pytania (do Ruslana / EFL)

1. Czy EFL finansuje auta importowane z Chin bez homologacji/rejestracji w PL w momencie
   zamówienia? (blokada główna dla całej ścieżki)
2. Czy obsługują konsumentów (leasing konsumencki), czy tylko firmy?
3. Jak wygląda System EFL (webowy formularz?) — czy da się zautomatyzować przekazywanie
   leadów, czy Ruslan wpisuje ręcznie?
4. Treść informacji o współpracy na stronę — do akceptacji EFL (mail, §2.4).
5. Czy istnieje „lepsza" (edytowalna) wersja dokumentu — obecny skan jest podpisany
   tylko przez Ruslana (parafki), bez podpisów konsorcjum na str. 10/14.
