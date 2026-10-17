# 2026-10-17 — auto-api: faktury EasyStaff = stała stawka w USD

## Kontekst

Faktura EST-299913 z 17.10.2026 (okres 17.10–17.11) przyszła na 151 EUR, poprzednie na 146 i 148 EUR.
Pytanie: czy dostawca podnosi cenę. Ustalenia z umowy: rozliczenie w USD.

## Ustalenie

Wszystkie faktury z Dysku (`!auranet/faktury`) przeliczone kursem EBC z dnia wystawienia:

| Faktura | Data | EUR | EUR/USD | ≈ USD |
|---|---|---|---|---|
| EST-241839 | 18.03 | 86,77 | 1,1525 (z faktury) | 100 |
| EST-249596 | 18.04 | 144,51 | 1,1797 | 170,5 |
| EST-257244 | 18.05 | 145,00 | 1,1648 | 168,9 |
| — | 06 | brak faktury | — | — |
| EST-272067 | 15.07 | 87,00 | 1,1406 | 99,2 |
| EST-281435 | 19.08 | 146,00 | 1,1605 | 169,4 |
| EST-290783 | 18.09 | 148,00 | 1,1460 | 169,6 |
| EST-299913 | 17.10 | 151,00 | 1,1253 | 169,9 |

Każda faktura = **170 USD** (cennik jednego źródła) albo **100 USD** (rabat dev), przeliczone na EUR
po bieżącym kursie i zaokrąglone. Wahania kwot w EUR to kurs, nie podwyżka. EST-299913 jest poprawna.

## Decyzja

Płacimy bez uwag (spójne z ADR 2026-08-19). Nie pytamy dostawcy o zakres stawki — korzystamy z dwóch
źródeł za cenę jednego.

## Otwarte

- **Kwiecień:** na Dysku tylko EST-249596 = 144,51 EUR (170 USD); korekty do 100 USD po proteście brak.
  Kosztorys (`docs/kosztorys/dane/koszty.json`) zakłada 100 USD — do potwierdzenia, ile zapłacono.
- **Czerwiec:** brak faktury (dostawca 15.06: „access paid through July 15"); kosztorys wpisuje 145 EUR.
- Kosztorys kończy się na lipcu — brak 08/09/10. Aktualizacja po wyjaśnieniu kwietnia.
- Przestój che168 w okresie 17.09–17.10: ~22.09 wieczór – 24.09 (≈2 dni, dongchedi działał).
  Nie reklamowany (≈5 EUR pro-rata). 15–16.10 che168 tylko 4 i 8 ofert — przyczyna niesprawdzona.
- Mail 17.10: „bank requisites updated" — przy przelewie porównać IBAN z PDF; płatność kartą bez zmian.
