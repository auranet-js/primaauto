# Bramka płatności za depozyty — wybór najtańszego operatora — prompt wykonawczy, 2026-09-16

> **Cel Janka:** wyłonić **najtańszą realną opcję** przyjmowania depozytów online (BLIK / karta / pay-by-link).
> Potem **Janek sam rejestruje konto w imieniu klienta** (Prima-Auto, Ruslan Prima) i dopiero wtedy
> podpinamy integrację. Ten wątek kończy się rekomendacją i paczką do rejestracji, nie kodem.
>
> Przeczytaj przed startem:
> - `docs/decyzje/2026-09-14-payu-odmowa-wylaczenie-bramki.md` — dlaczego PayU odpadło, stan kodu i kreatora
> - `docs/biznes/2026-09-14-autopay-rozpoznanie.md` — Autopay rozpoznany (stawki, branże zakazane, onboarding, mapowanie API)
> - `docs/roadmapa/T-121-platnosc-online-depozyt.md` — model depozytu (zwrotny, kwota z configu, zamrażana na zamówieniu)
> - memory: `project_payu_odmowa_2026_09_14`, `project_payu_pos_zablokowany_2026_08_25` (historia),
>   `reference_order_config_is_operational_knob`, `feedback_prosby_do_klienta_cala_sciezka_sprawdzona`,
>   `feedback_publikacja_zewnetrzna_wymaga_osobnej_zgody`

## Zakres

**Robisz:** research publicznych cenników i regulaminów, odczyt danych z bazy (tylko SELECT), porównanie,
rekomendacja, lista wymogów do rejestracji.
**Nie ruszasz:** rejestracji u operatorów, formularzy kontaktowych, maili do operatorów, kodu pluginu,
flagi `asiaauto_payu_enabled`, treści strony. Kontakt z operatorem (np. prośba o stawkę indywidualną)
= osobna zgoda Janka.

## Stan wyjściowy (16.09)

- PayU odmówiło 14.09 (AML nie widziało, za co klient płaci; blok płatności dopiero w kroku 3–4 kreatora).
  Bramka wyłączona, kod uśpiony jako szkielet — do wymiany tylko warstwa API.
- Depozyty idą przelewem, Ruslan księguje ręcznie (`_order_deposit_paid_source = manual`).
- **Depozyt zwrotny: dziś 6 150 zł** (`asiaauto_order_config`: `deposit_min`, `deposit_percent = 0`) —
  nastawa Ruslana, może się zmienić. Zawsze odczytaj bieżącą wartość, nie hardkoduj.
- Autopay (z 14.09): 1,19% + 0,34 zł → ok. **73,53 zł** od depozytu 6 150 zł, aktywacja 49 zł,
  zwroty 0 zł; ryzyko: „dropshipping” na liście zakazanych.
- Tpay: tylko lista wyłączeń (łagodniejsza na papierze), bez cennika i rozpoznania.

## Krok 1 — dane, na których liczymy koszt (baza, tylko odczyt)

DB `host476470_wp521`, prefix `wp7j_`, CPT `asiaauto_order`. Policz za ostatnie 3 pełne miesiące i za wrzesień:
- liczba opłaconych depozytów miesięcznie (`_order_deposit_paid_*`, źródło `manual` vs online),
- kwoty (`_order_deposit_amount`) — rozkład, nie tylko średnia,
- **ile depozytów wróciło do klienta** (zwroty) — przy depozycie zwrotnym koszt zwrotu i to, czy operator
  oddaje prowizję, może przeważyć o wyborze,
- ile zamówień doszło do kroku płatności, a depozytu nie ma (sygnał porzuceń).

Jeśli meta zwrotu nie istnieje — zapisz „niemierzalne z bazy” i dodaj do quizu (krok 4).

## Krok 2 — kandydaci i model kosztu

Minimum do porównania: **Autopay, Tpay, Przelewy24, Paynow (mBank), imoje (ING), Stripe (BLIK + karty PL),
Cashbill, Espago (tylko karty — dla kontrastu)**. Dopisz innego, jeśli wychodzi taniej.
Sprawdź też **bramkę banku, w którym Prima-Auto ma konto firmowe** — bank zna klienta (KYC już zrobione),
co zwykle obniża ryzyko odmowy AML. Który to bank — pytanie do Janka (krok 4), nie zgaduj.

Przy kwocie rzędu 6 000 zł **stała opłata za transakcję bije procent** — szukaj planów z opłatą stałą
lub progową, planów „dla dużych kwot” i stawek indywidualnych (zapisz, że wymagają negocjacji).

Dla każdego operatora, **tylko ze źródłem (URL + data odczytu)**:

| Pole | Uwagi |
|---|---|
| prowizja BLIK / pay-by-link / karta | osobno, jeśli różne |
| **koszt jednego depozytu** przy bieżącej kwocie | liczba w zł |
| **koszt miesięczny** przy wolumenie z kroku 1 | razem z abonamentem |
| aktywacja, abonament, minimalna opłata | |
| zwrot: opłata, czy prowizja wraca, limit czasu (BLIK), czy wymaga salda | |
| limity kwot BLIK / karta / przelew | czy mieszczą depozyt z zapasem (np. 10 000 zł) |
| wypłata środków (D+0 / D+1), przelew na konto — koszt | |
| **ryzyko odmowy**: lista branż zakazanych — czy obejmuje pojazdy, import, zaliczki/depozyty, dropshipping, pośrednictwo | cytat punktu listy |
| onboarding: kto musi się weryfikować (reprezentant = Ruslan?), dokumenty, czas | ważne, bo rejestruje Janek w imieniu klienta |
| czy Janek może założyć konto i dokończyć weryfikację bez fizycznej obecności Ruslana | pełnomocnictwo? fotoweryfikacja reprezentanta? |
| sandbox przed akceptacją | |
| API: podobieństwo do PayU (REST/JSON, notify, BLIK level 0 na stronie) | szacunek zakresu przepisania warstwy API — **niezweryfikowane** do czasu sandboxa |
| gotowy moduł WooCommerce | nas nie dotyczy (autorski kreator), tylko jako sygnał jakości dokumentacji |

Twierdzenia o stawkach bez źródła = „niezweryfikowane”. Cennik z porównywarek/blogów tylko jako
wskazówka, liczy się strona operatora.

## Krok 3 — alternatywa bez operatora (dla kontrastu, jedna tabelka)

Czy tańsze niż bramka jest: przelew natychmiastowy z generowanym QR / danymi przelewu i automatycznym
dopasowaniem wpłaty (np. API banku / wyciąg), przelew BLIK na telefon. Koszt, wygoda klienta,
czy księgowanie przestaje być ręczne. Bez rekomendowania, jeśli wymaga integracji z bankiem, której nie znamy.

## Krok 4 — quiz do Janka PRZED listą pytań do klienta

Zasada globalna: wątpliwości najpierw `AskUserQuestion` (po jednym, z podpowiedziami, rekomendacja pierwsza,
zawsze „nie wiem / pytamy Ruslana”). Typowe:
- w jakim banku jest konto firmowe Prima-Auto,
- ile depozytów realnie wraca do klientów (jeśli baza nie mówi),
- czy BLIK wystarczy, czy karty są konieczne (karty = dłuższa weryfikacja i ostrzejsze wymogi),
- czy Ruslan może zrobić fotoweryfikację / mObywatel, czy potrzebne pełnomocnictwo dla Janka,
- czy akceptujemy abonament miesięczny w zamian za niższą prowizję.

Do Ruslana tylko to, na co Janek odpowie „nie wiem”.

## Krok 5 — wynik

1. **Tabela porównawcza** (pierwsza w raporcie): operator | koszt 1 depozytu | koszt/mies. przy naszym wolumenie |
   zwrot | ryzyko odmowy (niskie/średnie/wysokie + powód) | onboarding w imieniu klienta | źródło.
2. **Rekomendacja: jeden operator + jeden zapasowy**, każdy z jednym zdaniem „dlaczego”. Najtańszy
   z wysokim ryzykiem odmowy nie jest najtańszy — powtórka PayU kosztowała miesiąc i trzech odbitych klientów.
3. **Paczka do rejestracji dla Janka:** link do formularza, lista dokumentów i danych klienta, kroki
   weryfikacji, co wpisać w opis działalności (model agencyjny, depozyt zwrotny jako opłata za usługę,
   „nie trzymamy aut na stanie, kupujemy wskazane auto w imieniu klienta na podstawie umowy”),
   jakie strony wskazać analitykowi.
4. **Warunki na stronie przed wnioskiem** — sprawdź aktualny stan wobec listy z
   `docs/biznes/2026-09-14-autopay-rozpoznanie.md` (sekcja „Co przygotować”): co jest już zrobione (z dowodem: URL),
   czego brakuje. Braki tylko wypisz — zmiany na stronie to osobna decyzja.
5. Zapis: `docs/biznes/2026-09-XX-bramka-platnosci-porownanie.md` + kopia na
   `https://auratest.pl/fe4f58fec53ctmp/primaauto-bramka-porownanie-YYYY-MM-DD.md`, link w odpowiedzi.
6. Aktualizacja memory `project_payu_odmowa_2026_09_14` o wybór (jeśli Janek zdecyduje).
7. Commit + push `[docs] ...`.
