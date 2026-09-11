# Recheck Meta Ads 11.09 — CTR przed/po rotacji, pauza g318, dwie awarie pomiaru GA4

> Odczyt 11.09 ok. 16:40–18:00. Graph API v25, GA4 Data API (534017542), GTM API, Google Ads API.
> Zmiana na koncie Meta: **jedna** — pauza `[VID] g318` (decyzja Janka, przez API).

## Stan konta

Wydane od startu (04.09) **385,38 zł**. Wydatek dzienny spada: 08.09 78,77 zł → 09.09 66,50 →
10.09 47,49 → 11.09 do 16:38 32,20 zł, przy budżetach zestawów 62 zł/dz.
Limitu konta (`spend_cap`) nie liczymy już jako horyzontu. Decyzja Janka 11.09, patrz memory
`feedback_meta_limit_1000_nie_jest_horyzontem`.

Kontakty przypisane Mecie od startu: **1** (WhatsApp 06.09, `[FOTO] kadr 1`), bez zmian.

## CTR przed i po rotacji 07.09 (ok. 14:30)

Rotacja: `[VID] leopard-5-czarny` → `g318`, `[POST] Mazda EZ-6` → `Zeekr 8X`, start `[RMKT]`.
Okna: przed = 05–06.09, po = 08–11.09 (07.09 pominięty, zmiana w trakcie doby).

| Kampania | CTR wszystkie | CTR w link | klik → wejście | zł / wejście |
|---|---:|---:|---:|---:|
| `[FOTO]` | 4,9% → 6,4% | 4,3% → 5,5% | 31% → 34% | 0,52 → 0,41 |
| `[POST]` | 3,1% → 4,9% | 1,1% → 1,4% | 51% → 64% | 0,59 → 0,54 |
| `[VID]` | 14,2% → 13,8% | 6,7% → 5,9% | 13% → 18% | 1,43 → 1,18 |
| `[RMKT]` (nowa) | 8,4% | 7,9% | 82% | 0,24 |
| prospecting razem | 6,8% → 8,3% | 3,66% → 4,18% | 24% → 30% | 0,80 → 0,66 |

- `[POST]` Zeekr 8X ma CTR w link 2,8% wobec 0,9% Mazdy, ale dostaje ok. 100 wyświetleń na dobę.
- `[VID]` g318 dowoził 14% kliknięć na stronę (1,54 zł / wejście). Leopard dowoził 6% (3,25 zł).
  Po rotacji g318 zabrał ok. 70% budżetu `[VID]`, a `z9-gt` spadł z 1 670 do 530 wyświetleń na dobę
  przy lepszym wyniku (26%, 0,80 zł). **→ g318 wstrzymany 11.09 ok. 18:00**, cały budżet
  `[VID]` idzie na z9-gt.

## Awaria pomiaru 1 — GA4 od 10.09 gubi źródło ok. 2/3 sesji

- `(not set)` + `(data not available)`: do 09.09 0,4–5% dziennie, **10.09 68,8%, 11.09 67,2%**.
  Przełom równo o północy 10.09, we wszystkich kanałach. 328 z 581 sesji nie ma strony docelowej.
- Po naszej stronie tej nocy nic się nie zmieniło: historia zmian usługi GA4 od 05.09 pusta,
  ostatnia wersja GTM (13) z 07.09, żadnego pliku zmienionego na serwerze 09.09 18:00 – 10.09 08:00.
- Hipoteza: opóźnienie przetwarzania po stronie Google. **Do sprawdzenia 12.09:** czy doba 10.09
  się uzupełniła. Jeśli nie — diagnoza dalej (brak `session_start` w sesjach `(not set)`).
- Skutek: GA4 pokazuje 9 sesji z Mety dziennie wobec 107 wejść w Mecie (10.09).

## Awaria pomiaru 2 — GTM v13 wycina `gclid` z `page_location`

- Wersja 13 (07.09 16:41, `scripts/gtm-fbclid.py`) usuwa z adresu wysyłanego do GA4 nie tylko
  `fbclid`, ale też `gclid`, `wbraid`, `gbraid`, `msclkid`, `ttclid`.
- Od 08.09 GA4 widzi **33–41 sesji `google / cpc` na dobę zamiast 103–133**, a kliknięcia Ads bez
  `[DG]` stoją (ok. 115/dobę). Spadek `[DG]` to celowe wyłączenie in-feed 07.09.
- Mety nie dotyczy (UTM-y zostają). Psuje punkt odniesienia `google / cpc` z audytu 10.09
  i być może konwersje Ads importowane z GA4 — niezweryfikowane.
- Naprawa: zostawić w liście tylko `fbclid`, publikacja GTM. **Decyzja Janka — nie wykonane.**

## Kontakty poza stroną — relacja Ruslana

Ruslan: kilku osobom musiał mówić o stronie, czyli kontaktowały się bez wejścia na WWW.
Strona FB ma przycisk Messengera i telefon **+48 721 730 507 — ten sam co na WWW**. Telefon
z profilu Strony nie zostawia śladu w GA4, pikselu ani raportach Mety. Audyt 10.09 („Meta nie
dowozi kontaktów”) liczył tylko kontakty ze strony — jest zaniżony o nieznaną liczbę.

- GA4: osoby klikające telefon tydzień 04–10.09 3,3/dobę wobec 3,4–3,7 wcześniej. WhatsApp 4,1
  wobec 3,1–3,5. Na stronach reklamowanych modeli 1,88/dz wobec 1,67 — tyle samo co reszta.
- Messenger przez API: **niemożliwy**. `(#100) Invalid Scopes: pages_messaging` — appka
  „Prima-Auto API” nie ma tego uprawnienia. Wymaga dodania Messengera w dashboardzie appki.
- Jedyne źródło liczby: Andrzej (skrzynka Business Suite) i Ruslan (telefony „z Facebooka”).

## Do zrobienia

1. 12.09 — `z9-gt` po pauzie g318: wyświetlenia, zł / wejście, klik → wejście, częstotliwość.
2. 12.09 — czy GA4 uzupełnił dobę 10.09.
3. Decyzja Janka: `gclid` / `wbraid` / `gbraid` z powrotem do `page_location` (GTM).
4. Dashboard `pa-live-8aaf08d6ece0db176603`: licznik kontaktów Meta nie liczy `fb_pixel_custom`,
   pasek i check limitu 1 000 zł do usunięcia — na polecenie.
