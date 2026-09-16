# Recheck po przebudowie Meta + skąd więcej telefonów — prompt wykonawczy, 2026-09-16

> **Dwa pytania Janka:** (1) jak idą kampanie Meta po przebudowie z 15.09, (2) **zwiększył się ruch
> telefoniczny — skąd przychodzi, czy mamy źródło/medium.**
>
> Przeczytaj przed startem:
> - `docs/meta/plan-kampanii.md` — sekcja **„Mapa kampanii — stan na 15.09”** (aktualny układ konta)
> - `docs/sesje/2026-09-15-audyt-meta-przed-po.md` — audyt, diagnoza piksela, naprawa GTM v15, przebudowa
> - memory: `project_meta_przebudowa_2026_09_15`, `reference_piksel_meta_tylko_na_ofertach`,
>   `feedback_audyt_kampanii_od_reklam_nie_od_statystyki`, `feedback_meta_limit_1000_nie_jest_horyzontem`,
>   `feedback_meta_tylko_przez_api_janek_bez_fb`, `feedback_nie_wylaczaj_zywych_reklam_sam`

## Zakres

**Robisz:** odczyt i raport. **Nie ruszasz:** kampanii, budżetów, reklam, GTM, strony — każda zmiana
to osobna decyzja Janka (quiz). Tura Janka „sprawdź” = raport, nie działanie.

## Stan wyjściowy (15.09 ok. 17:30)

| Kampania | Zestaw | Budżet | Cel | Od kiedy |
|---|---|---:|---|---|
| `[KAT]` Oferta — nowi odbiorcy (katalog) | `[KAT] PL M 30-60 — cały katalog` | 35 zł | ViewContent | 15.09 ~17:15 |
| `[FOTO]` Zdjęcia z sesji — karuzele | `Karuzele PL M 30-60 — Contact` (nowy, kadr 1 + kadr 2) | 15 zł | **Contact** | 15.09 ~17:15 |
| `[RMKT]` Remarketing dynamiczny — katalog | `Oglądane — 30 dni` | 15 zł (z 7) | ViewContent | zmiana 15.09 ~17:15 |
| `[VID]`, `[POST]`, `[RMKT]` Na placu, stary zestaw `[FOTO]` | — | pauza | — | 15.09 ~17:15 |

Konto 65 zł/dz. Wydane do 15.09 17:30: 662,66 zł, kontakt z Mety: 1 (WhatsApp 06.09, `[FOTO]` kadr 1).

**Trzy zmiany pomiaru 15.09, które zmieniają odczyt danych:**
1. **GTM v15 (ok. 18:30)** — piksel Meta działa na wszystkich stronach u osób ze zgodą, a nie wyłącznie
   na ofertach i bez zgody. **Liczb piksela sprzed i po 15.09 18:30 nie porównuj wprost** (ViewContent
   spada, PageView i Contact rosną).
2. **Dashboard** (`https://auratest.pl/pa-live-8aaf08d6ece0db176603/`) — licznik „kontakty” Mety liczy
   od 15.09 Contact (max z etykiet `fb_pixel_contact` / `contact_website` / `contact_total` / `fb_pixel_custom`)
   + Lead. Wcześniej liczył tylko Lead i zawsze pokazywał 0.
3. **GTM nadal wycina `gclid` / `wbraid` / `gbraid` z `page_location`** (od v13, 07.09; v15 tego nie ruszał).
   Skutek: `google / cpc` w GA4 ok. 3× zaniżone od 08.09, a część ruchu z Google Ads **może lądować jako
   `google / organic`** (niezweryfikowane). **To bezpośrednio dotyka pytania o źródło telefonów.**

**Opóźnienie GA4:** ostatnie 2–3 doby mają 60–70% sesji `(not set)` / `(data not available)` — uzupełniają
się same (zmierzone 10–11.09). Źródła/medium oceniasz **wyłącznie na dobach z udziałem bez przypisania < 10%**.
Doby świeższe podajesz osobno, z oznaczeniem „niedomknięte”.

---

## Część 1 — telefony: skąd więcej połączeń

### 1a. Najpierw quiz do Janka (AskUserQuestion, pojedynczo)

Zanim policzysz, ustal, co znaczy „więcej telefonów” — odpowiedź zmienia, gdzie szukać:
- **Skąd to wiadomo?** (Ruslan mówi / widać w GA4 / dashboard / inne) — z podpowiedziami.
- **Od kiedy?** (od 15.09 / od ok. tygodnia / dłużej).
- **Na który numer?** 721 730 507 (strona, Strona FB, WhatsApp) czy 721 730 512 (drugi numer na stronie).
- **Czy dzwoniący mówili, skąd są / co widzieli** (reklama na FB, Google, strona, konkretne auto).

Opcja „nie wiem / pytamy Ruslana” zawsze na liście. Do Ruslana idą tylko pytania, na które Janek nie zna odpowiedzi.

### 1b. Kliknięcia w telefon na stronie — GA4 (`scripts/ga4_query.py`, property 534017542)

Okno: **28 dób przed 15.09** vs **doby po 15.09** (tylko domknięte). Zdarzenia `click_phone`, osobno `click_whatsapp`.

| Zapytanie | Wymiary | Po co |
|---|---|---|
| dzień po dniu | `date` × `eventName` | czy wzrost w ogóle jest w danych ze strony i od którego dnia |
| źródło sesji | `date` × `sessionSourceMedium` × `eventName` | skąd przyszła sesja, w której kliknięto telefon |
| kampania i reklama | `sessionCampaignName` × `sessionManualAdContent` (filtr `facebook / paid_social` oraz `google / cpc`) | która kampania / reklama |
| pierwsze źródło użytkownika | `firstUserSourceMedium` × `eventName` | czy klikający przyszedł kiedyś z reklamy, a dzwoni z organiku / bezpośrednio |
| gdzie kliknięto | `pagePath` × `eventName` (grupuj: oferta / hub / `/kontakt/` / strona główna / inne) | typ strony |
| urządzenie | `deviceCategory` × `eventName` | telefon z komórki to prawdziwe połączenie, z desktopu — raczej odczyt numeru |

Pułapka do wyjaśnienia w raporcie: przyrost w `google / organic` może być ruchem z Google Ads z wyciętym
`gclid` (pkt 3 wyżej). Sprawdź to porównaniem: sesje `google / cpc` w GA4 vs kliknięcia w Google Ads API
na te same doby — jeśli Ads kliknięć jest stale ~3× więcej, różnica siedzi w innym kanale GA4.

### 1c. Połączenia, których strona NIE widzi — sprawdź wszystkie trzy

1. **Google Ads — połączenia z reklam i rozszerzeń połączeń** (konto `9506068500`, klient: memory
   `reference_google_ads_api_client`): `metrics.phone_calls`, `metrics.phone_impressions`,
   `metrics.phone_through_rate` per kampania per dzień; jeśli raportowanie połączeń jest włączone —
   zasób `call_view` (godzina, czas trwania, status). Konwersja „Clicks to call” miała 0 w 30 dniach do 28.08.
2. **Profil Firmy w Google (GBP)** — Business Profile Performance API, metryka `CALL_CLICKS` dzień po dniu
   (token `~/secrets/google/tokens.json` ma scope `business.manage`; narzędzia w `~/scratch/google-gbp-scripts/`).
   **Tylko odczyt** — GBP jest read-only po incydencie z 13.05 (projekt auranet).
3. **Strona na Facebooku** — przycisk telefonu i Messengera na Stronie (numer 721 730 507).
   **Przez API niedostępne** (brak `read_insights`, `pages_messaging`) — zapisz jako niemierzalne, źródło
   liczby: Andrzej (Business Suite) i Ruslan.

Plus Meta: `Contact` przypisany kampaniom w oknach `1d_click`, `7d_click`, `1d_view` od 15.09
(`{act}/insights?level=ad&action_attribution_windows=[...]`) oraz piksel `/{pixel}/stats?aggregation=event`
dzień po dniu (paginacja! kubełki godzinowe) — pamiętaj, że Contact w pikselu od 15.09 18:30 łapie więcej stron.

### 1d. Wynik części 1

Tabela **„skąd telefony”**: kanał | przed (śr./dobę, 28 dni) | po (śr./dobę) | zmiana | pewność pomiaru.
Kanały: strona `google / organic`, strona `google / cpc`, strona `facebook / paid_social` (z rozbiciem na kampanie),
strona `(direct)`, strona inne, Google Ads połączenia z reklamy, GBP `CALL_CLICKS`, Strona FB (niemierzalne).
Pod tabelą jedno zdanie: gdzie jest przyrost i czy da się go przypisać zmianie z 15.09.
Twierdzenie „wzrost przez Metę” wyłącznie z dowodem (kampania/reklama w GA4 albo Contact w Mecie) — bez niego „niezweryfikowane”.

---

## Część 2 — Meta po przebudowie

### 2a. Stan konta i alarmy

`python3 scripts/social/recheck_start.py` + odczyt statusów zestawów: czy `[KAT]` wyszedł z weryfikacji,
czy któraś reklama ma `DISAPPROVED` / `WITH_ISSUES`, `account_status`. Budżet: wydatek dziś / wczoraj / od 15.09
wobec 65 zł/dz. **Bez dat wyczerpania limitu** (memory `feedback_meta_limit_1000_nie_jest_horyzontem`).
Limit wywołań `17/2446079`: zapytania zbiorcze, przy odbiciu odczekaj.

### 2b. Tabela per reklama (pierwsza tabela raportu)

Od 15.09 17:15 (i osobno pełne doby 16.09+): wydane | wyświetlenia | CTR w link | kliknięcia w link | zł/klik |
wejścia (LPV Meta) | sesje GA4 | zł/sesja GA4 | zaangażowanie GA4 | ViewContent | **Contact** | kontakty GA4.
Reklamy: `[KAT] Oferta — nowi odbiorcy`, `[FOTO] Cała oferta — kadr 1`, `[FOTO] Cała oferta — kadr 2`, `[RMKT] Oglądane — 30 dni`.

Do porównania z fazą C sprzed zmian (12–14.09, `docs/sesje/2026-09-15-audyt-meta-przed-po.md`, sekcja 2):
razem 69,06 zł/dz, 0,59 zł/sesja GA4, `[FOTO]` 0,35 zł/sesja, `[RMKT]` 0,51 zł/sesja, 0 kontaktów.

### 2c. Na co patrzeć per kampania

- **`[KAT]`** — czy Meta rozkłada wydatek na wiele aut (`breakdowns=product_id`), ile aut dostało kliknięcia;
  miejsca (tylko aktualności); demografia — ile poszło poza propozycję M 30–60 (`breakdowns=age,gender`);
  koszt sesji GA4 wobec `[VID]` (0,78 zł) i `[POST]`, które zastąpił.
- **`[FOTO]` nowy zestaw na Contact** — czy dostawa ruszyła (zestaw na rzadkim zdarzeniu potrafi się
  dławić: sprawdź wydatek wobec 15 zł i `learning_stage_info`); liczba Contact; kadr 1 vs kadr 2.
- **`[RMKT]` przy 15 zł** — częstotliwość (alarm ≥ 4 w dashboardzie), zasięg wobec listy (~2 tys.),
  CTR dzień po dniu; lista po naprawie piksela zawiera już tylko osoby ze zgodą.

### 2d. Trend dzienny

Doba | wydane | wejścia | sesje GA4 | zł/sesja | CTR link per kampania | Contact | kontakty GA4.
Od 12.09 (3 doby przed) do ostatniej doby — z oznaczeniem doby przebudowy (15.09) i dób niedomkniętych w GA4.

### 2e. Pomiar — krótko, TAK/NIE z dowodem

- GTM: wersja live nadal **15** (`accounts/6351095501/containers/250095450/versions:live`).
- Piksel po naprawie: PageView / ViewContent / Contact dzień po dniu — ViewContent powinien spaść poniżej ~50% PageView.
- Dashboard: `checki.json` (13 checków), `meta.json` — `konto.budzet_dzienny` = 65, `razem.kontakty` ≥ 1.
- UTM-y: 4 z 4 żywych kreacji z `url_tags`; sesje GA4 z `utm_content` = nazwa każdej reklamy.

---

## Raport i domknięcie

1. Kolejność w raporcie: **część 1 (telefony — tabela „skąd telefony”)** → tabela per reklama → trend dzienny → pomiar.
   Statystyka, progi i werdykty budżetowe tylko jako krótki dopisek, jeśli realnie psują odczyt.
2. Zapis: `docs/sesje/2026-09-16-recheck-meta-i-telefony.md`; kopia na
   `https://auratest.pl/fe4f58fec53ctmp/primaauto-recheck-meta-telefony-YYYY-MM-DD.md` z linkiem w odpowiedzi.
3. Propozycje zmian (kreacje, budżety, cele) — **osobno, na końcu, jako quiz**, nic nie wykonujesz bez odpowiedzi.
4. Otwarte z 15.09, do przypomnienia Jankowi (nie działać): rozszerzanie listy w `[RMKT]`
   (`targeting_relaxation_types.custom_audience: 1`); przycisk Messengera na ofercie (makieta
   `primaauto-makieta-messenger-oferta-2026-09-15.html`, wariant A/B + test gotowego tekstu na telefonie);
   `gclid` w GTM (decyzja z 11.09).
5. Commit + push raportu w konwencji `[docs] ...`.
