# T-205 — plan wykonawczy po odzyskaniu dostępu (2026-07-31)

> Kontekst i stan zasobów: [`T-205-meta-restart-nowe-konto.md`](T-205-meta-restart-nowe-konto.md)
> Token: `~/secrets/meta/tokens/primaauto-portfolio-ruslan.txt` · appka **`1533997951805022`** (nie stara `1012248818452132`)
> Konto rekl. `act_1038563008906171` · portfolio `1486783363131026` · Strona `1146829831857839`
> Szacunek pozostałej pracy: **12–16 h**

---

## Zasada kolejności

Trzy tory biegną **równolegle**, bo mają różne zależności:

- **Tor A (F0–F1)** — nie wymaga niczego od Ruslana. Ruszamy od razu.
- **Tor B (F3–F4)** — buduje się „na sucho", startuje dopiero po weryfikacji.
- **Tor R** — po stronie Ruslana. Nie kontrolujemy tempa, ale bez R2+R3 nic nie wystartuje.

**Najpilniejsze w całym zadaniu: F0.2 + F0.3.** Każda doba zwłoki to ruch primaauto.com.pl oddawany obcemu portfolio.

---

## F0 — Fundament pomiarowy (~3–4 h) · brak blokerów

### F0.1 · Weryfikacja domeny `primaauto.com.pl` w nowym portfolio
Meta-tag w `<head>` (theme `primaauto2026`) albo rekord DNS TXT. Bez tego nie ma własności zdarzeń ani prawa do konfiguracji priorytetu Aggregated Event Measurement.
**Zrobione =** zielony status własności domeny w portfolio.

### F0.2 · Nowy dataset (piksel) ⚠️ PILNE
Utworzenie przez API (`POST /{business-id}/adspixels`), przypisanie do konta reklamowego, zapis ID do `~/secrets/meta/accounts.json`.
**Zrobione =** dataset istnieje, widoczny na `act_1038563008906171`.

### F0.3 · Przepięcie GTM ⚠️ PILNE
Kontener **`GTM-T4GTZ2JB`**. Stan potwierdzony 31.07 na `gtm.js` z CDN: **przejęty piksel `2053908905193274` nadal żyje w opublikowanej wersji.** 5 tagów Meta.
Podmiana ID pikselu na nowy w: tagu bazowym + ViewContent + Contact (telefon) + Contact (WhatsApp) + Lead. Zgody Complianz bez zmian.
Praca przez **API na Default Workspace** — patrz `feedback_gtm_default_workspace_api_only`; recon przed edycją, wersja + publish, rollback = republish poprzedniej wersji.
**Zrobione =** `gtm.js` z CDN nie zawiera już `2053908905193274`, zawiera nowy ID.

### F0.4 · Walidacja zdarzeń na żywym ruchu
Cztery zdarzenia (ViewContent, Contact ×2, Lead) muszą dotrzeć do nowego datasetu. Kontrola przez Events Manager oraz `GET /{pixel-id}` → `last_fired_time`.
**Zrobione =** wszystkie cztery mają świeży `last_fired_time`, zero zdarzeń trafia do starego.

---

## F1 — Katalog pojazdów (~3–4 h) · brak blokerów

### F1.1 · Utworzenie katalogu
`POST /{business-id}/owned_product_catalogs`, vertical **vehicles**. Przypięcie do konta reklamowego.

### F1.2 · Feed CSV — naprawa pipeline'u
Stan faktyczny (sprawdzony 31.07): generator `scripts/build-meta-vehicle-feed.php` **istnieje i jest sprawny**, ale:
- ścieżkę wyjściową bierze z `$argv[2]`, domyślnie `scripts/meta-vehicles-sample.csv`,
- **na `public_html` nie ma żadnego `meta-vehicles*.csv`** — plik, o którym mówiła poprzednia notatka, już nie istnieje,
- **w crontabie nie ma wpisu feedu Meta** (potwierdzone odczytem pełnej tabeli).

Do zrobienia: ustalić docelową ścieżkę publiczną, wygenerować pełny CSV z bieżącego stanu ofert, sprawdzić komplet pól wymaganych przez Meta dla `vehicles` (m.in. `vehicle_id`, `make`, `model`, `year`, `mileage`, `price`, `availability`, `condition`, `image_url`, `url`).

### F1.3 · Cron regeneracji
Wpis **wyłącznie przez `~/bin/cron-install`** (`feedback_crontab_only_via_cron_install`, po incydentach 12.07 i 22.07). Częstotliwość dobowa, spięta z rotacją oferty. Po stronie Meta: scheduled feed pull po URL.

### F1.4 · Kontrola jakości zaciągu
Ile pozycji weszło, ile odrzuconych i dlaczego, poprawność cen / zdjęć / dostępności.
**Zrobione =** katalog zasilany automatycznie, liczba pozycji zgodna z liczbą opublikowanych ofert.

---

## F2 — CAPI na nowym datasecie (~2 h) · DECYZJA JANKA

Stary token CAPI (`~/secrets/meta/tokens/primaauto.txt`) pisze do **przejętego** datasetu — jest bezużyteczny i powinien zostać wycofany z obiegu. Nowy SU pozwala postawić CAPI od nowa.

⚠️ Podpięcie zdarzeń serwerowych po stronie WP dotyka **strefy kruchej zamówień** — pierwotny zakres T-205 to wykluczał. Do świadomej decyzji, nie robimy mimochodem.

---

## F3 — Struktura kampanii, wszystko PAUSED (~4–5 h) · buduje się bez blokerów

- **Prospecting** — ruch/leady na landing, targeting do reużycia: 8 miast + Śląsk, wiek 35–60, 4 grupy zainteresowań.
- **Remarketing** — na audiencjach z F4, więc realnie startuje później.
- Kreacje z **5 wytypowanych postów**, UTM-y spójne z GA4.
- Wszystko zakładane jako `PAUSED` — konto nie wyda złotówki bez naszej akcji.

**Zrobione =** kampanie + zestawy + reklamy istnieją, przechodzą walidację Meta, czekają na jedno przełączenie.

---

## F4 — Audiencje · zależne od F0.4, mierzone w tygodniach

Zbieranie zaczyna się **dopiero od momentu, gdy nowy piksel łapie ruch**. Custom Audiences: wszyscy odwiedzający 30/180 dni, ViewContent, Contact+Lead. LAL dopiero po nazbieraniu sensownej próby.

⚠️ **Tego nie da się przyspieszyć godzinami pracy.** Im później F0.3, tym później startuje licznik.

---

## F5 — Start i monitoring · zależne od R2 + R3

Odpalenie kampanii, kontrola dostarczania i kosztu przez pierwsze 7 dni, korekta budżetów. `min_daily_budget` konta = 382 (3,82 PLN).

---

## Tor R — po stronie Ruslana (blokery twarde)

| # | Co | Blokuje |
|---|---|---|
| R1 | Weryfikacja firmy — dane **1:1 z CEIDG** (poprzednio literówka + 2 adresy = odrzucenie) | R2 |
| R2 | **Weryfikacja UE / DSA** | F5 — bez niej nie powstanie żaden zestaw reklam |
| R3 | Karta płatnicza + limit wydatków (konto ma dziś `funding_source` puste) | F5 |
| R4 | Konto Instagram + przypięcie do Strony | placementy IG |
| R5 | Post zapraszający na starej Stronie (16 676 obserwujących, publikuje Andrzej — o ile wciąż ma tam managera) | zasięg startowy |

---

## Definicja zrobionego (całość T-205)

- [ ] Zdarzenia lecą do **naszego** datasetu, stary piksel nie dostaje nic
- [ ] Domena zweryfikowana w portfolio
- [ ] Katalog pojazdów zasilany działającym cronem
- [ ] Kampanie zbudowane i zwalidowane, w stanie PAUSED
- [ ] Audiencje zbierają dane
- [ ] Ruslan przeszedł weryfikację UE i podpiął płatność
- [ ] Ruslan wie, że obserwujący i audiencje startują od zera — to koszt przejęcia konta, nie naszej pracy
