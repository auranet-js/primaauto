# T-264 — pomiar: „Zamów” → „Chcę to auto” + adnotacja „zgłoszenie nie jest zamówieniem”

> Zmiana na produkcji: **13.10.2026 ok. 13:30** (v0.45.2, karty katalogu ok. 14:00). Doba 13.10 = przejściowa, nie wliczamy jej do żadnego okresu.
> Skrypt: `python3 scripts/pomiar-cta-zgloszenie.py --zgloszenia --ga4 2026-09-15` (logi ~70 s).
> Recheck: **27.10** (14 dób po) i **10.11** (28 dób po — porównanie równych okresów z bazą GA4 poniżej).

## Pytanie

Czy nowa etykieta głównego przycisku i dodatkowe informacje zmieniają częstotliwość wejść w zgłoszenie rezerwacji (klik przycisku) i wysłanych zgłoszeń?

Dwie hipotezy działają w przeciwne strony:
- **więcej klików** — „Chcę to auto” brzmi lżej niż „Zamów”, a podpis „Bezpłatne zgłoszenie — nie jest zamówieniem” zdejmuje obawę przed zobowiązaniem;
- **mniej wysłanych formularzy, ale lepszych** — ramka na `/zamow/` mówi wprost, że umowa i depozyt są później. Klient, który chciał „kupić od razu”, może zrezygnować; ten, który się bał, może wysłać.

Dlatego mierzymy osobno trzy etapy: **klik → rozpoczęcie formularza → wysłanie**.

## Co liczymy i skąd

| Etap | Źródło | Metryka | Uwagi |
|---|---|---|---|
| Wejście na ofertę | GA4 `page_view` `/oferta/*` | mianownik | GA4 widzi ~1/3 ruchu (zgoda), ale zaniża licznik i mianownik tak samo |
| Klik przycisku | GA4 `page_view` `/zamow/*` | **% z wejść na ofertę — metryka główna** | zawiera też powroty z linku w mailu (zgłoszenia w weryfikacji) — mały szum |
| Klik przycisku | logi serwera: `GET /zamow/?listing_id=N` z Refererem `/oferta/…-N/` (z oferty) albo `/samochody/`, `/w-rzeszowie/`, `/w-drodze/` (z listy) | **liczba bezwzględna na dobę** — niezależna od zgody | scrapery (proxy rezydencjalne, Referer = ten sam `/zamow/` albo `/`) odcięte; mianownik z logów skażony crawlem katalogu i wejściami z Meta bez Referera, CTR z logów tylko orientacyjnie |
| Rozpoczęcie formularza | GA4 `zamowienie_start` | liczba | tylko ze zgodą — małe liczby |
| Wysłane zgłoszenie | baza: `asiaauto_order`, `_order_type=customer`, bez kont testowych (10, 12, 13, 157) | **liczba — metryka wyniku** | niezależna od zgody; GA4 `generate_lead` widzi ok. połowę |

## Punkt odniesienia (przed zmianą)

**GA4, 15.09–12.10 (28 dób):** 34 095 wejść na oferty → 291 wejść na `/zamow/` = **0,85%** · `zamowienie_start` 9 · `generate_lead` 6.
Rozrzut dzienny 0,16–2,25% — pojedyncza doba nic nie znaczy, porównujemy sumy okresów.

**Logi serwera, 30.09–12.10 (13 pełnych dób):** **80 klików z karty oferty** (3–11 dziennie, średnio ~6) · **31 klików z kart w katalogu** (0–6 dziennie). Logi starsze niż 30.09 nie są dostępne (archiwum miesięczne) — dla porównania 28 dób po zmianie mamy w logach tylko 13 dób bazy, liczymy średnią dzienną.

**Zgłoszenia w bazie, 15.09–12.10:** 18 (01–14.09: 4; 01–12.10: 7). Średnio **~0,6 dziennie**.

## Jak czytać wynik

- Klik przycisku: ~6 dziennie z oferty → po 14 dobach mamy ~80 zdarzeń na okres. Zmianę o **±30% i więcej** da się odróżnić od szumu; mniejszą nie (różnica rzędu ±10 klików mieści się w rozrzucie dziennym).
- Zgłoszenia: ~0,6 dziennie → po 28 dobach ~17 zdarzeń. To za mało, żeby rozstrzygnąć zmianę mniejszą niż ~50%. Wynik czytamy razem z jakością zgłoszeń (ile przeszło do „potwierdzone”), nie z samej liczby.
- Porównujemy **% w GA4** (kliki / wejścia) i **kliki z logów na dobę** — jeśli oba idą w tę samą stronę, wynik jest wiarygodny.

## Czynniki zakłócające (zapisywać na bieżąco)

- Kampanie Meta i Google Ads — zmiana budżetu albo odbiorców zmienia skład ruchu na ofertach. Przy rechecku sprawdzić, czy między 13.10 a datą pomiaru coś przestawiano (`docs/meta/plan-kampanii.md`, `scripts/ads-recheck.py`).
- Podaż ofert (zaciąg, rotacja) i oferty „Na placu” — inne auta, inna skłonność do zgłoszeń.
- Inne zmiany na karcie oferty i `/zamow/` po 13.10 — każdą dopisać tutaj z datą.
- Sezon (koniec roku) — porównanie przed/po nie odróżni zmiany przycisku od trendu, jeśli trend jest silny.

| Data | Zmiana poza T-264 | Wpływ na pomiar |
|---|---|---|
| — | — | — |

## Wyniki

### Recheck 27.10 (14 dób po)

_do uzupełnienia_

### Recheck 10.11 (28 dób po)

_do uzupełnienia_
