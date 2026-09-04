# PROMPT STARTOWY — sesja Google Ads (materiał z rechecku SEO 04.09)

> Wątek osobny, **nie mieszać z SEO**. Ten plik jest wejściem: co zmierzone, czego nie sprawdzałem,
> co rozstrzygnąć. Dane pochodzą z GA4 Data API (property `534017542`), nie z panelu Ads —
> **pierwszy krok sesji to potwierdzenie po stronie Ads**, bo GA4 i Ads liczą inaczej.

## 0. Jak zacząć (kolejność obowiązkowa)

1. `python3 scripts/ads-recheck.py` — stan konta jednym biegiem.
2. Przeczytaj **całe** `docs/ads/mapa-kampanii.md` — to jest źródło prawdy o strukturze konta.
3. Pamięć projektu: `project_ads_mapa_kampanii_2026_08_19`, `reference_ads_konwersje_tylko_kontakt`,
   `reference_ads_etykieta_polityki_nie_sledzi_tekstu`, `feedback_budzet_sprawdz_dni_pod_sufitem`,
   `feedback_verify_status_and_activity_before_work`.
4. Dopiero potem zmiany. `ENABLED` ≠ serwuje.

Konto: **950-606-8500** (direct access, `login-customer-id = customer_id`, NIE MCC), API v21,
klient `scripts/gads_client.py`, sekrety `~/secrets/google/ads-config.json`.

---

## 1. Co zmierzone — dwa sygnały do wyjaśnienia

Okno **A = 2026-08-07 → 2026-09-03**, **B = 2026-07-10 → 2026-08-06** (GA4, kanały):

| kanał | sesje A | sesje B | zmiana | konwersje A | konwersje B |
|---|---|---|---|---|---|
| **Display** | 1 217 | 1 320 | −8% | **4** | **15** |
| **Paid Search** | 889 | 1 485 | **−40%** | 21 | 27 |
| Cross-network | 633 | 236 | +168% | 18 | 5 |
| (odniesienie) Organic | 10 449 | 6 847 | +53% | 105 | 84 |

### Sygnał 1 — Display: konwersje 15 → 4 przy niemal tym samym ruchu
Ruch spadł o 8%, konwersje o 73%. To nie jest proporcjonalne, więc nie tłumaczy tego sam wolumen.
**Do sprawdzenia:** czy zmieniły się placementy / audiencje / kreacje w oknie B→A; czy nie doszło
do przesunięcia na słabsze miejsca (aplikacje mobilne); czy konwersje nie zostały przepięte
lub przeliczone inaczej.

### Sygnał 2 — Paid Search: −40% sesji
**Najpierw sprawdź dni pod sufitem budżetu, nie metrykę utraconego IS** — `feedback_budzet_sprawdz_dni_pod_sufitem`
(31.08 przy [Brand] IS pokazywał „57%", a limit był wyczerpany w 17 z 30 dni). Dopiero potem
hipotezy o stawkach i konkurencji.

**Kontekst, który może tłumaczyć część spadku:** ruch organiczny w tym samym oknie urósł +53%,
a brand organicznie eksplodował (`prima auto` 1 222 kliknięcia, +1 025). Jeśli brand jest dziś
brany organicznie, część płatnych kliknięć brandowych mogła po prostu przestać być potrzebna —
**to byłby dobry wynik, nie problem**. Rozstrzygnąć danymi: rozbić Paid Search na brand vs non-brand
i porównać z organicznym brandem.

---

## 2. Czego NIE sprawdzałem (nie zakładaj, że to zrobione)

- Nie wchodziłem do panelu ani API Ads w tej sesji. **Wszystkie liczby wyżej są z GA4.**
- Nie sprawdzałem search terms, stawek, jakości, statusów reklam ani polityk.
- Nie sprawdzałem DSA ani feedów. Ostatni stan: `docs/ads/mapa-kampanii.md` + `project_dsa_start_na_ofertach_2026_07_17`.
- Nie ruszałem niczego na koncie.

---

## 3. Materiał z SEO, który może się przydać w Ads

- **Fałszywy trop do wykluczenia w kampaniach:** `monjaro cena` (3 076 imp/mc w organicu) to **lek
  Mounjaro** (tirzepatyd), nie Geely Monjaro — SERP to apteki. Jeśli gdziekolwiek w kampaniach
  jest szerokie dopasowanie na „monjaro", **sprawdź search terms pod kątem zapytań medycznych
  i dołóż negatywy** (mounjaro, tirzepatyd, apteka, ulotka, dawkowanie, kwikpen, otyłość, cukrzyca).
  Szczegóły: `reference_gsc_striking_measurement_gotchas_2026_07_09`.
- **Frazy, gdzie organik jest już mocny** (rozważyć obniżenie nakładu płatnego): `geely monjaro`
  poz 4,7 CTR 5,10%, `zeekr 8x`, `byd sealion 8`, `zeekr 007 gt`, `mg 7`, `jetour g700` —
  wszystkie z dużymi wzrostami klików w oknie A.
- **Frazy, gdzie organik jest słaby mimo impresji** (kandydaci na wsparcie płatne): `byd han`
  (4 983 imp / 22 kliki, poz 7,5), `zeekr` ogólna (2 603 imp, CTR 0,61%), `gwm` (645 imp, CTR 0,16%).
- **Nowy kanał w GA4: AI Assistant +151%** (179 → 450 sesji, 11 konwersji). Nie jest to Ads,
  ale warto wiedzieć przy dzieleniu zasług między kanały.
- **Konwersja organiczna spadła** 1,23% → 1,00% przy +53% ruchu — przyrost ruchu jest bardziej
  informacyjny niż transakcyjny. Ta sama uwaga może dotyczyć oceny kampanii świadomościowych.

**Definicja konwersji zostaje bez zmian:** telefon / WhatsApp / formularz. YouTube subs to NIE
konwersja — `reference_ads_konwersje_tylko_kontakt`. Wyłączenie akcji nie wystarcza,
sprawdź `campaign_conversion_goal`.

---

## 4. Pytania do rozstrzygnięcia w tej sesji

1. Display: naprawiać czy wygaszać? (4 konwersje / 1 217 sesji to dziś najsłabszy płatny kanał)
2. Paid Search −40%: budżet, konkurencja czy przejęcie brandu przez organik?
3. Czy brand w Ads nadal ma uzasadnienie przy `prima auto` na pozycji 1,1 organicznie
   z 1 222 klikami?

Pełny recheck SEO, z którego to wynika: `docs/seo/recheck-2026-09-04.md`.
