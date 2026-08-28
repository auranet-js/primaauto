# Prompt kontynuacyjny — jedna, kompletna lista próśb dla Ruslana

> Wątek otwarty 28.08.2026. Cel jest jeden i wąski: **zebrać wszystko, czego Meta wymaga
> od strony klienta, w jedno pismo — żeby nie wracać do Ruslana po raz czwarty.**

---

## Dlaczego ten wątek istnieje

W ciągu jednego tygodnia poszły do Ruslana trzy pisma, za każdym razem z inną listą,
bo za każdym razem sprawdzaliśmy tylko to, co akurat blokowało bieżący krok:

| Kiedy | Co poszło | Czego zabrakło |
|---|---|---|
| 26.08 | lista v3 — karta, uprawnienia | regulamin grup odbiorców opisany błędnie jako zaakceptowany |
| 28.08 | lista v4 — DSA, regulamin grup odbiorców, leadgen, `spend_cap` | scope `read_insights` na tokenie |
| — | (kolejna prośba **nie wysłana**) | to jest właśnie to, czego ten wątek ma nie dopuścić |

Wysłane pismo v4: `docs/meta/priorytety-dla-klienta-2026-08-28.html`
(kopia: `https://auratest.pl/fe4f58fec53ctmp/primaauto-priorytety-reklamy-v4-2026-08-28.html`).

**Reguła tego wątku:** nie piszemy do klienta, dopóki nie przejdziemy modułu po module
przez cały plan i nie wypiszemy dla każdego, czego wymaga po ich stronie. Lista niepełna
jest gorsza niż lista spóźniona o godzinę.

---

## Stan wiedzy — zmierzone, nie przepisane

Pełny obraz: `docs/meta/plan-kampanii.md` (sekcje 5–8). Poniżej tylko to, co dotyczy próśb.

### Wysłane w v4, czeka na Ruslana

1. **Weryfikacja reklamodawcy (DSA)** — bez tego żadna reklama do PL. Dowód: zestaw reklam
   na PL `100/3858196`, ten sam na US `{"success": true}`. Dotyczy **wszystkich trzech**
   kampanii, `[POST]` włącznie — promowanie gotowego posta nie jest osobną ścieżką.
   Sprawdzenie stanu: `python3 scripts/social/dsa_status.py`.
2. **Regulamin grup niestandardowych odbiorców** — `200/1870090`. Pułapka: konto ma
   `tos_accepted: {"web_custom_audience_tos": 1}`, co wygląda na komplet, ale to inny regulamin.
3. **Regulamin pozyskiwania kontaktów na Stronie** — potwierdzone dziś: `leadgen_tos_accepted: false`.
4. **`spend_cap`** — `100000` groszy, czyli 1 000 zł na całe życie konta. Przy 40–50 zł
   dziennie starcza na 20–25 dni.

### Wyszło PO wysłaniu v4 — do dopisania

5. **Metryki organiczne Facebooka.** Token System Usera (`primaauto-portfolio-ruslan.txt`,
   wygenerowany 27.08) **nie ma scope'u `read_insights` ani `pages_read_user_content`**.
   Ma za to `instagram_manage_insights`, więc dane instagramowe czytamy bez przeszkód.
   Skutek: przy rotacji `[POST]` nie widzimy, który post Andrzeja sam złapał zasięg —
   a decyzja „co wchodzi, co wychodzi" ma zapadać na danych (ustalenie Janka z 28.08).

   Dwie drogi, do rozstrzygnięcia w piśmie:
   - **klik Ruslana** — Ustawienia firmy → Użytkownicy systemowi → „Auranet API" →
     Generuj token → dodać `read_insights` i `pages_read_user_content`;
   - **app secret** appki „Prima-Auto API" `1533997951805022` — wtedy każdą przyszłą zmianę
     scope'ów robimy sami, bez angażowania klienta. **To jest lepsze rozwiązanie i warto
     je postawić jako rekomendację**, bo wycina z obiegu całą klasę przyszłych próśb.

   Uwaga techniczna: regeneracji **nie zrobimy z API** — wymaga app access tokenu
   (`app_id|app_secret`), a w `~/secrets/meta/app.json` siedzi secret starej, zablokowanej
   appki `1012248818452132`, nie tej z portfolio. Nasz System User ma rolę `ADMIN`
   w portfolio `1486783363131026` (`verified`), ale to nie wystarcza — UI wymaga konta człowieka.

   Wedle memory `reference_token_system_user_scopes_dynamiczne.md` regeneracja rozszerza
   **całą** SU i scope'y dziedziczą się na istniejące tokeny, więc po kliknięciu Ruslana
   **nie trzeba wymieniać pliku z tokenem** — wystarczy sprawdzić `debug_token`. Zweryfikuj to
   pomiarem, nie przyjmuj na słowo.

---

## Czego jeszcze NIE sprawdziliśmy — zrób to PRZED napisaniem pisma

To jest sedno zadania. Każda z tych pozycji może okazać się kolejną prośbą, a wtedy ma
trafić do tego samego pisma, nie do następnego.

| # | Do sprawdzenia | Jak | Dlaczego to może być prośba |
|---|---|---|---|
| A | **Weryfikacja domeny** `primaauto.com.pl` w portfolio | `owned_domains` nie istnieje w v25 — znajdź aktualną ścieżkę (Business → Brand Safety) | bez niej Meta ogranicza przypisanie konwersji (AEM) i priorytet zdarzeń |
| B | **Aggregated Event Measurement** — czy zdarzenia mają ustawiony priorytet | endpoint pikselowy, `shared_accounts` wymaga parametru `business` | 8 zdarzeń iOS, `Contact` musi być wysoko; bez tego tracimy część konwersji |
| C | **Regulamin pikseli w portfolio** | `POST /{business}/adspixels` zwracał `1784018` (zapis w `~/secrets/meta/accounts.json`) | dziś obeszliśmy tworząc piksel przez konto reklamowe — sprawdź, czy nie wróci przy CAPI |
| D | **Rola `MANAGE` na koncie reklamowym** | dziś mamy `DRAFT · ANALYZE · ADVERTISE` | bez niej nie ustawimy `spend_cap` ani metody płatności sami — każda zmiana budżetu to prośba |
| E | **Kanał YouTube ↔ Google Ads** | `account_link` na koncie `9506068500` zwracał pustkę (T-243c, 27.08) | 63 556 wyświetleń nie zbudowało żadnej audiencji; wymaga dostępu do kanału, czyli Andrzeja |
| F | **Konto Instagram — rola reklamowa** | `instagram_accounts` w portfolio | mamy publikację i insighty; sprawdź, czy promowanie rolek IG nie potrzebuje osobnej zgody |
| G | **Strona `verification_status: not_verified`** | zmierzone dziś | sprawdź, czy niebieski znacznik jest wymagany do czegokolwiek z planu, czy to kosmetyka |

Metoda: dla każdego modułu planu (`[VID]`, `[POST]`, `[RMKT]`, audiencje, CAPI, katalog,
pomiar) wypisz, czego wymaga od klienta, i **dopiero sumę tego zamień na pismo**.

---

## Narzędzia, które już są

W `scripts/social/`, commit `38c82ee`:

```
meta_api.py          wspólna warstwa; post() domyślnie validate_only
rotator_postow.py    kampania [POST] — dwie tabele do decyzji, --wstaw / --wyjmij
rotator_wideo.py     kampania [VID] — materiał z Dysku, faststart, --wgraj / --wstaw
dsa_status.py        czy PL przechodzi walidację; wariant US jako kontrola
```

Wywołanie bez argumentów nic nie wysyła. Reklama zawsze powstaje jako `PAUSED`.
Włączenie z budżetem nie jest czynnością żadnego z tych narzędzi.

Pułapki zmierzone 28.08, nie zgaduj ich ponownie:
- `published_posts` wymaga **page tokenu**, mimo że SU ma `pages_read_engagement`;
  `{page}/feed` odbija się o Page Public Content Access i nie jest potrzebny.
- filtr ceny w katalogu przyjmuje wyłącznie liczbę całkowitą w groszach.
- landingi Denzy działają tylko na slugach bez prefiksu marki (`z9-gt-dm-i`, `n9-dm-i`).

---

## Jak wygląda „zrobione"

Jedno pismo w `docs/meta/`, wzorowane na v4 (ten sam układ i ton), zawierające **komplet**
próśb — te cztery z v4 jako stan „czeka", plus wszystko, co wyjdzie z tabeli A–G. Pokazane
Jankowi w czacie w całości, wysłane wyłącznie na `js@auranet.com.pl` przez `~/bin/send-to-jan`.
**Nigdy bezpośrednio do Ruslana ani Andrzeja.**

Pismo ma jawnie mówić, że to lista zamykająca — i musi taka być, bo czwartej Janek nie chce.

---

# Wykonanie — 28.08 wieczorem

## Tabela A–G: co wyszło z pomiarów

| # | Wynik | Dowód |
|---|---|---|
| A | **JEST PROŚBĄ** — domena niezweryfikowana | brak `<meta name="facebook-domain-verification">` na `primaauto.com.pl`, brak TXT w DNS. Edge `owned_domains` niedostępny dla naszej appki w v19/v21/v23/v25 — ścieżki API nie ma, mierz przez stronę i DNS |
| B | **wchłonięte przez A** — AEM stoi za weryfikacją domeny; `aem_*` niedostępne dla appki. Osobno: `da_checks` piksela zgłasza `pixel_has_low_event_source_match_rate` i `pixel_missing_param_in_events` — **to nasza robota, nie prośba** |
| C | **NIE jest prośbą** — CAPI działa na tokenie SU: `capi_send.sh --project primaauto-2026 PageView TEST12345` → `{"events_received": 1}`. Regulamin pikseli (`1784018`) dotyczył wyłącznie zakładania piksela na poziomie portfolio |
| D | **JEST PROŚBĄ** — `assigned_users` konta: my `DRAFT·ANALYZE·ADVERTISE`, Ruslan `+MANAGE`. Bez `MANAGE` każda zmiana `spend_cap` to prośba |
| E | **JEST PROŚBĄ (Andrzej)** — GAQL `account_link` i `data_link` na `9506068500`: zero wierszy |
| F | **NIE jest prośbą** — kreacja z `instagram_user_id` → `{"success": true}`; `{IG}/insights` czyta (`reach` 368/26); profil w `owned_instagram_accounts` |
| G | **NIE jest prośbą** — `verification_status: not_verified`, ale nic z planu tego nie warunkuje. W piśmie wymienione w sekcji „o co świadomie nie prosimy" |

## Co obaliło tezę pisma v4

Grupy odbiorców **nie są zablokowane**. `200/1870090` dotyczy wyłącznie list z wgranego pliku.
Grupy z piksela i z zaangażowania utworzyły się bez żadnego regulaminu. Wcześniejsze
`2654/1870053` i `2654/1713151` to były błędy ładunku (`subtype` przy regule, zła nazwa
zdarzenia dla IG), wzięte za jeden objaw jednej przyczyny.

## Wpadka, którą trzeba znać

`/act_*/customaudiences` **ignoruje `execution_options=validate_only`** — sonda utworzyła trzy
prawdziwe grupy: `sonda-www` (120248811956940243), `sonda-fb` (120248811957410243),
`sonda-ig` (120248811957520243). Guard `GLUCHE_NA_WALIDACJE` dopisany do `scripts/social/meta_api.py`.
Do decyzji Janka: przemianować na docelowe nazwy i dołożyć pozostałe trzy, czy skasować.

## Produkty

- `docs/meta/prosby-koncowe-2026-08-28.html` — pismo v5, lista zamykająca
- `docs/meta/plan-kampanii.md` — sekcje 5, 6, 8 przepisane; nagłówek z korektą wieczorną
- `scripts/social/meta_api.py` — guard na endpointy głuche na walidację

## Zauważone obok, nie ruszane

Kliknięcie w WhatsApp to najmocniejszy kanał kontaktu (43,3 konwersji/30 dni), a Strona
**nie ma podpiętego numeru WhatsApp Business** (`whatsapp_number` puste page tokenem,
edge `owned_whatsapp_business_accounts` odbija `(#200)` — brak scope'u
`whatsapp_business_management`). Reklamy „kliknij, żeby napisać na WhatsAppie" wymagałyby
numeru na Stronie i rozszerzenia tokenu. **Nie ma tego w planie kampanii, więc nie ma tego
w piśmie** — jeśli ma wejść, to jako decyzja Janka, nie mimochodem.
