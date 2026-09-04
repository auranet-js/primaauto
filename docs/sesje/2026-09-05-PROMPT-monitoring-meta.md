# Monitoring reklam i pomiaru — prompt do nowego wątku (start 2026-09-05)

> Zadanie: zbudować **jeden obraz monitoringu** (Meta + GA4 + Google Ads) i sprawdzić,
> czy każdy jego element naprawdę mierzy to, co ma mierzyć. Powód: Janek nie ma konta
> na Facebooku — Menedżera Reklam widzą Ruslan i Andrzej. Bez własnej strony nie ma
> jak zobaczyć, czy kampanie chodzą i czy pomiar nie kłamie.

## Co już stoi (04.09 wieczór)

| Element | Adres / plik | Odświeżanie |
|---|---|---|
| Dashboard Meta | `https://auratest.pl/pa-live-8aaf08d6ece0db176603/meta.html` | cron co 30 min |
| Kolektor Meta | `scripts/social/meta_live_collector.py` → `meta.json` | `*/30 * * * *` |
| Strona dashboardu | `scripts/social/meta_dashboard.html` (kopia w katalogu publicznym) | statyczna |
| Dashboard GA4 live | `https://auratest.pl/pa-live-8aaf08d6ece0db176603/` | cron co 3 min |
| Kolektor GA4 | `scripts/ga4-live-collector.py` → `data.json` | `*/3 * * * *` |
| Recheck Google Ads | `python3 scripts/ads-recheck.py` | na żądanie |
| Recheck Meta | `python3 scripts/social/recheck_start.py` | na żądanie |
| Makieta kreacji | `python3 scripts/social/mockup_kampanii.py --aktywne` | na żądanie |

Token Meta zostaje po stronie serwera; w publicznym JSON-ie są wyłącznie liczby.
Katalog jest zahaszowany i ma `noindex` — to zasłona, nie autoryzacja: **nie wkładać tam
danych osobowych ani niczego z sekretów**.

## Znaleziska do rozstrzygnięcia — zmierzone 04.09, nie hipotezy

1. **Kreacje Meta nie mają UTM-ów.** Wszystkie cztery żywe reklamy `[VID]` i `[FOTO]`
   prowadzą na czyste adresy (`…/samochody/byd/leopard-5/#oferty`). Meta nie taguje
   automatycznie, więc GA4 widzi ten ruch jako **`l.facebook.com / referral`** (3 sesje
   dziś) i `lm.facebook.com / referral` (1) — bez kampanii, bez rozróżnienia `[VID]`
   od `[FOTO]`, bez szansy na przypisanie leada do reklamy.
   Naprawa: `url_tags` na kreacji (`utm_source=facebook&utm_medium=paid_social&
   utm_campaign={{campaign.name}}&utm_content={{ad.name}}`), czyli **nowa kreacja
   i podmiana reklamy** — edycji kreacji Meta nie ma. Osobny przypadek to `[POST]`:
   reklama z gotowego posta prowadzi tam, gdzie link w treści posta Andrzeja — do
   ustalenia, czy w ogóle da się to otagować, czy zostaje pomiar po pikselu.
2. **W GA4 „na żywo" nie widać źródła — i to nie jest nasz błąd.** Realtime API Google
   nie zna wymiaru kampanii ani źródła (twarde ograniczenie). Kampanie na dashboardzie
   idą z Data API z zakresem „today" (opóźnienie kilku minut). Do potwierdzenia, czy
   strona wyraźnie rozdziela te dwie warstwy, żeby nikt nie czytał ich jak jednej.
3. **`(not set)` 221 sesji i `(data not available)` 97 w źródłach dziś** — łącznie
   ponad połowa ruchu bez przypisania, przy 79 `(direct)`, 60 `google / organic`,
   23 `google / cpc`. To trzeba zdiagnozować przed jakimkolwiek wnioskiem o skuteczności
   kanałów. Kandydaci: Consent Mode / Complianz (patrz `reference_gtm_blokada_zalogowanych_nie_dziala`),
   zdarzenia bez sesji, próbkowanie zakresu „dziś".
4. **Piksel Meta — czy zdarzenia w ogóle dochodzą.** Cel `[VID]` i `[FOTO]` to ViewContent;
   dziś przy 47 wyświetleniach jest 0 otwartych ofert, co przy tym ruchu nic nie znaczy,
   ale trzeba potwierdzić sondą (Test Events albo `/activities` na pikselu `1634147041766916`),
   że sygnał w ogóle płynie — inaczej optymalizacja uczy się na niczym.
5. **Rozjazd Meta vs GA4.** Meta liczy kliknięcia, GA4 sesje — po dobie ruchu porównać
   jedno z drugim i zapisać skalę różnicy, żeby przy raporcie dla Ruslana nie tłumaczyć
   tego od zera (analogia: rozjazd konwersji Ads vs zdarzeń GA4 opisany w `docs/ads/mapa-kampanii.md`).

## Co ma z tego wyjść

- **Jedna strona monitoringu**, na której widać naraz: Meta (dostawa, koszt, kliknięcia,
  ViewContent), GA4 (sesje z każdego kanału, zdarzenia kontaktu), Google Ads (koszt,
  konwersje kontaktowe) — plus jawny znacznik „dane z HH:MM" i alarm, gdy cron stanął.
  Do rozstrzygnięcia z Jankiem: dokładać zakładkę do `pa-live-…`, czy trzymać osobne strony.
- **Lista checków „czy to działa"** — jeden skrypt, który po kolei odpowiada TAK/NIE:
  cron chodzi, JSON świeży, piksel przyjmuje zdarzenia, UTM-y są na miejscu, reklamy nie
  wpadły w `DISAPPROVED`, landingi oddają 200, limit konta nie jest bliski wyczerpania.
- Wynik obu rzeczy zapisać w `docs/architektura/` — to ma być stan faktyczny pomiaru,
  a nie notatka z sesji.

## Zasady, które obowiązują w tym wątku

- **Nie wyłączaj i nie włączaj żywych reklam z własnej inicjatywy** — błąd zgłaszasz
  jednym zdaniem i czekasz na decyzję Janka.
- Zmiana treści reklamy = nowa kreacja obok + podmiana w tym samym zestawie ze statusem
  starej + stara do archiwum. Dostawa nie staje, faza uczenia zostaje.
- Teksty reklam wyłącznie z assetów Google Ads (`tmp/gads-assety-2026-09-04.txt`),
  nazwy aut z tytułów ofert (`_serie_full_title`) — patrz prompt Meta z 04.09.
- Konto Meta ma kroczący limit wywołań (`17/2446079`) — zapytania zbiorczo, nie w pętli.
- Budżet `[POST]` 10 → 15 zł: temat zamknięty przez Janka 04.09, nie wracać.
- Wynik pierwszej doby kampanii czeka na recheck **05.09 o 19:00** (event w kalendarzu
  „Auranet Claude", prompt: `docs/sesje/2026-09-04-PROMPT-meta-kampanie-kontynuacja.md`).
