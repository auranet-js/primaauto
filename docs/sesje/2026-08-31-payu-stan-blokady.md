# PayU — stan blokady, pomiar 31.08.2026

Sprawdzenie po sygnale Janka: „widziałem, że były jakieś próby klientów z płatnością".

## Pomiar POS-u

`GET /api/v2_1/paymethods` na produkcyjnym POS **4436802** (`secure.payu.com`, OAuth
z `~/secrets/payu/prod-primaauto.env`) → **19 z 19 metod `DISABLED`**. Bez zmian wobec
25.08. Sprawdzenie jest bezkosztowe i nie wymaga generowania transakcji „na próbę".

Flagi po naszej stronie bez zmian i zgodnie z decyzją z 25.08 — bramka zostaje włączona,
bo PayU ma zweryfikować działającą ścieżkę zakupową:

| Opcja | Wartość |
|---|---|
| `asiaauto_payu_enabled` | `1` |
| `asiaauto_payu_mode` | `prod` |

## Trzeci odbity klient — zamówienie 453465

31.08, 11:08–11:10. User 153 („Jan Pietrzak"), listing 448113 — BYD Leopard 5 (Denza B5),
depozyt **6 150 zł**. Cztery próby pod rząd:

| # | Metoda | Wynik | `payu_order_id` |
|---|---|---|---|
| 1 | BLIK | `failed` — `OPENPAYU_PAYMENT_CREATE_BLOCKED_CHECKOUT_PAY_METHOD` | — |
| 2 | BLIK | to samo | — |
| 3 | redirect | `expired` | `C95W9CWGPD260831GUEST000P01` |
| 4 | redirect | `pending` u nas, `NEW` w PayU, `payMethod` pusty | `CSHCQ1TBNB260831GUEST000P01` |

Nasza strona zadziałała poprawnie — odmowa jest po stronie PayU już na tworzeniu płatności.
**Pieniądze nigdzie nie wiszą**: retrieve na obu transakcjach zwraca `NEW` bez metody
płatności, nie ma czego odbierać ani zwracać.

## Wzorzec obejścia: depozyty księgowane ręcznie

Nowa obserwacja, której nie było w notatce z 25.08. Ruslan (user 7) księguje wpłaty
ręcznie w panelu — klienci płacą poza bramką:

| Zam. | Depozyt | `deposit_paid` | Kiedy | `deposit_paid_source` |
|---|---|---|---|---|
| 453465 | 6 150 zł | 1 | 31.08 13:16 | `manual` |
| 438216 | 6 150 zł | 1 | 28.08 | `manual` (po 7 odbitych próbach 26–27.08) |
| 438241 | 1 zł | 1 | 22.08 | `manual` |
| **423100** | **6 150 zł** | **0** | — | — (odbity 21.08, pieniądz nie wpłynął wcale) |

To jest sprzężenie zwrotne warte odnotowania: każdy ręcznie zaksięgowany depozyt umacnia
argument analityka AML, że „nie widzimy możliwości zakupu usług ani produktów fizycznych".

**Bilans blokady od 21.08:** trzech klientów, ~18 odbitych prób, trzy razy po 6 150 zł.
Jedno zamówienie (423100) nierozliczone do dziś.

## Sprawa AML — potwierdzone przez Janka

Ruslan **wysłał** odpowiedź na pismo AML (Karolina Rogalska). PayU odpisało, że **nadal
bada sprawę**. Skrzynka `claude@auratest.pl` korespondencji nie widzi — poszła kanałem
Ruslana, więc nie mierzymy jej u siebie i status znamy tylko z relacji.

## Otwarte, zaproponowane 25.08 i nadal nie zrobione

- Strona o modelu płatności (czym jest depozyt, za co klient płaci, kiedy wraca)
  podlinkowana ze stopki.
- Magic link dla analityka PayU, żeby zobaczył blok płatności w kroku 3–4 kreatora
  bez zakładania zgłoszenia. To jest przyczyna strukturalna sprawy: analityk do tego
  kroku nie dociera.
