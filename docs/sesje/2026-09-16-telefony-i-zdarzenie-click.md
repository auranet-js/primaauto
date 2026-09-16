# Skąd więcej telefonów + czym jest zdarzenie `click` — 2026-09-16

> Część 1 promptu `2026-09-16-PROMPT-recheck-meta-i-telefony.md`, odczyt ok. 12:00–14:00.
> Część 2 (Meta po przebudowie) — 17.09, dokończenie źródeł — 18.09 (przypomnienia w kalendarzu „Auranet Claude”).
> Na kontach nic nie zmieniane.

## Telefony

Janek zauważył wzrost na dashboardzie „na żywo” od 15.09.

**Wzrost jest prawdziwy, ale jednodniowy.** GA4 15.09: 11 × `click_phone` (10 osób) + 4 × `click_whatsapp` = 15,
wobec średnio 8,5/dobę w 28 dobach 18.08–14.09 (4,5 telefon + 4,0 WhatsApp). Najwięcej kliknięć w telefon w 30 dniach.
14 z 15 z komórki. 16.09 do 12:00: 5 — normalne tempo.

**Nie przez przebudowę Mety.** 8 z 11 kliknięć w telefon było przed 17:15 (start `[KAT]`), w tym 4 na `/kontakt/`
między 13 a 16. Po 17:15: 3 telefony + 2 WhatsApp.

**Dashboard pokazał wzrost także przez zmiany pomiaru z 15.09:** licznik kontaktów Mety liczy Contact od 15.09
(wcześniej zawsze 0), a piksel po GTM v15 widzi prawie wszystkie kontakty (15.09: 16 Contact w pikselu wobec 15 w GA4;
08–14.09 średnio 4,3 Contact/dobę, ok. 60% GA4).

| Kanał | Przed (śr./doba) | 15.09 | 16.09 do 12:00 | Pewność |
|---|---:|---:|---:|---|
| Strona — wszystkie kanały (GA4) | 8,5 | 15 | 5 | wysoka |
| ↳ źródło/medium | — | 71% sesji bez przypisania | 66% | niedomknięte — 18.09 |
| Meta `[KAT]`, Contact 1d_click | — | 3 | 1 | Meta przypisuje, GA4 niepotwierdzone |
| Meta `[RMKT]` (GA4, UTM) | ~0 | 1 WhatsApp | 0 | wysoka |
| Google Ads — konwersje importowane z GA4 | ok. 2,4 | 4 tel. + 1 WA | 0 | atrybucja Google |
| Google Ads — połączenia z reklamy | 0 | 0 | 0 | wysoka (brak rozszerzeń połączeń) |
| Profil Firmy — `CALL_CLICKS` | 0,5 | brak (opóźnienie ~4 doby) | — | niska |
| Strona FB | niemierzalne | — | — | — |

Meta i Google Ads mogą przypisywać sobie te same osoby — nie sumować. „Wzrost przez Metę” — niezweryfikowane.

**Zauważone obok:** Profil Firmy w Google (`locations/2525874446424610771`) ma numer **783 807 381**, strona 721 730 507.
Do potwierdzenia, czy celowo. GBP Performance API działa (wcześniej quota 0).

## Zdarzenie `click` w GA4

W raporcie Zdarzenia `click` (131 w 28 dniach) stoi obok `click_phone` / `click_whatsapp` i sugerowało „Zadzwoń” z reklamy.
**Nie jest.** To automatyczny pomiar linków wychodzących (`outboundClicksEnabled: true` w strumieniu), nie tag GTM,
nie kluczowe zdarzenie GA4 i nie akcja konwersji w Google Ads.

- 30 dni: 137 × `click`, z tego 105 do **wa.me** — to samo dotknięcie co `click_whatsapp`. Dopasowanie minuta + strona + miasto:
  99 ze 122 `click_whatsapp` ma `click` do wa.me w tej samej chwili; `click_phone` 1 ze 127, `generate_lead` 1 z 12.
- Reszta: facebook.com 14, maersk.com 7 (śledzenie kontenera z `/w-drodze/`), youtube 5, instagram 3, Mapy, auranet.com.pl.
- `tel:` nie generuje `click`. „Zadzwoń” z reklamy Google nie przechodzi przez stronę — liczy je `Clicks to call` (zawsze 0).
- W raporcie pozycja wynika z sortowania po liczbie zdarzeń. Kontakty = tylko `click_phone`, `click_whatsapp`, `generate_lead`.

## Jak odtworzyć

```
# GA4 — scripts/ga4_query.py
date × eventName (click_phone, click_whatsapp) · dateHour × pagePath × sessionCampaignName
eventName=click: linkDomain × linkUrl (wymiary standardowe) · dateHourMinute × pagePath × city × eventName × linkDomain
Admin API: properties/534017542/keyEvents · dataStreams/14410105949/enhancedMeasurementSettings
# Meta
{act}/insights?level=ad&time_increment=1&action_attribution_windows=["1d_click","7d_click","1d_view","28d_click"]
{pixel}/stats?aggregation=event (paginacja przez paging.next)
# Google Ads 9506068500
conversion_action (lista) · campaign: metrics.phone_calls, conversions × segments.conversion_action_name
# GBP
businessprofileperformance: locations/2525874446424610771:fetchMultiDailyMetricsTimeSeries CALL_CLICKS
```
