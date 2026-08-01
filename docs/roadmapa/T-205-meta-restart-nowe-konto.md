# T-205 — Meta: restart na nowym koncie (Facebook/Instagram)

> Status: **W TOKU — pomiar i katalog wdrożone 2026-08-01, zostały kampanie** · Rozmiar: M
> Godziny: **12–16 h na całość** (z pierwotnych 16–22 h; Ruslan wykonał konfigurację portfolio wg naszej instrukcji)
> **Plan wykonawczy (rewizja 01.08): [`T-205-plan-wykonawczy-2026-07-31.md`](T-205-plan-wykonawczy-2026-07-31.md)**
>
> Wdrożone 01.08: piksel `1634147041766916`, GTM wersja 10 (stary piksel odcięty), katalog `2936723456671912` z feedem dobowym.
> Następny ruch: **audiencje** — zbierają dane dopiero od utworzenia, więc każdy dzień zwłoki jest nie do odrobienia.
> Zastępuje pozycję „Uruchomienie kampanii Facebook/Instagram" (17–20 h) — tamta zakładała, że mamy dostęp do konta.

## Stan na 2026-07-31 — dostęp odzyskany

Ruslan wykonał instrukcję ([`T-205-instrukcja-dla-ruslana.md`](T-205-instrukcja-dla-ruslana.md)) i przysłał token Systemu Użytkownika (dwoma mailami, połówki). Token sklejony i zapisany: `~/secrets/meta/tokens/primaauto-portfolio-ruslan.txt` (600, bezterminowy).

**Zweryfikowane na żywo (`debug_token` + wywołania Graph API v21.0):**

| Element | Wartość |
|---|---|
| System User | „Auranet API" `122094804249428678`, rola **ADMIN** |
| Aplikacja | **„Prima-Auto API" `1533997951805022`** — nowa, **Marketing API DZIAŁA** |
| Scope | `ads_management`, `ads_read`, `business_management`, `pages_show_list`, `pages_read_engagement`, `pages_manage_ads`, `catalog_management` |
| Portfolio | „Prima Auto" `1486783363131026` (to puste z 11.06) — `not_verified` |
| Konto reklamowe | `act_1038563008906171` „Prima-Auto" — utworzone 31.07, **PLN + Europe/Warsaw**, aktywne, 0 wydane, 0 kampanii |
| Strona | „Prima-Auto" `1146829831857839` — opublikowana, kat. „Sprzedaż samochodów", opis + `primaauto.com.pl` wpisane, **0 obserwujących** |

⚠️ **Blokada API dotyczyła aplikacji, nie tokena** — stara appka „Auranet API" `1012248818452132` pozostaje zablokowana i nie należy jej używać. Wszystko idzie przez nową `1533997951805022`. (Implikacja cross-project: przy Victorini w `~/secrets/meta/accounts.json` figuruje stara appka — tam Marketing API nadal będzie martwe.)

✅ Nieodwracalne nastawy — **waluta PLN i strefa Europe/Warsaw** — wyszły poprawnie. To był najgroźniejszy gotcha instrukcji (stare konto miało UAH/Berlin).

**Zerowe / brakujące:** dataset (piksel) `0`, katalog produktów `0`, Instagram `0`, metoda płatności brak (`funding_source` puste), weryfikacja firmy i UE niezrobione.

## Dlaczego restart, a nie odzyskiwanie

- **Konto przejęte 03.06.2026** — obcy użytkownik, obca kampania, wydane 54,12 zł, konto w stanie nieuregulowanym. Konto osobiste Janka usunięte permanentnie. Atak seryjny (ta sama noc: Prima Auto + inny klient).
- **Sprawa w Meta Business Support od 12.06 — miesiąc bez rozstrzygnięcia.**
- **Od 14.07 API zwraca `"API access blocked"` na wszystkich wywołaniach** — token System Usera, który w czerwcu jeszcze czytał dane, dziś nie czyta niczego. Zero dostępu programistycznego.

**Czekanie na Metę nie jest planem.** Restart jest.

## Plan restartu (ustalenie Janka 2026-07-14)

**Po stronie Ruslana:**
1. Zakłada **nową Stronę** Prima-Auto i **nowe portfolio biznesowe** (Business Manager).
2. Na **starej Stronie** (16 676 obserwujących — tam **Andrzej wciąż ma dostęp managera**) publikuje post informacyjny z **zaproszeniem do nowej strony**. To jest sposób na przeniesienie zasięgu bez odzyskiwania konta.
3. Nadaje nam dostęp partnera do nowego portfolio.
4. Przechodzi **weryfikację firmy w UE (DSA)** — bez niej nie powstanie żaden zestaw reklam. ⚠️ To jest twardy bloker, w całości po jego stronie.

**Po naszej stronie (te 16–22 h):**
5. Konfiguracja nowego portfolio: role, dostępy, weryfikacja domeny `primaauto.com.pl`.
6. **Nowy Pixel/dataset** + repoint w GTM (dziś: `GTM-T4GTZ2JB`, piksel `2053908905193274` — stary, na przejętym koncie). Zdarzenia są już zmapowane i przetestowane: ViewContent, Contact (telefon), Contact (WhatsApp), Lead — wszystkie za zgodą Complianz.
7. **Katalog pojazdów** — feed CSV **już istnieje** (`scripts/build-meta-vehicle-feed.php` → `meta-vehicles.csv`, 4074 wiersze). ⚠️ **ALE: cron regeneracji zniknął — plik stoi na 01.06.** Do naprawy przy okazji (patrz „Znaleziska").
8. **Audiencje od zera** — WCA (odwiedzający, ViewContent, Kontakt+Lead) i LAL. ⚠️ **Wymagają tygodni na zebranie danych** — to jest realny koszt restartu, którego nie da się skrócić godzinami pracy.
9. **Kampanie:** prospecting (ruch → landing) + remarketing. Targeting jest już rozpisany z poprzedniego podejścia (geo: 8 miast + Śląsk, wiek 35–60, 4 grupy zainteresowań) — do reużycia.

## Co przetrwało i jest gotowe do reużycia

| Element | Stan |
|---|---|
| Mapowanie zdarzeń (Pixel) + wdrożenie przez GTM | ✅ gotowe, przetestowane na żywym ruchu |
| Feed katalogu pojazdów (generator + CSV) | ✅ gotowy (⚠️ cron do naprawy) |
| Targeting (geo, wiek, zainteresowania) | ✅ rozpisany |
| 5 reklam wytypowanych z istniejących postów | ✅ |
| Token CAPI (konwersje serwerowe) | ⚠️ istnieje, ale **niepodpięty** — wymagałby dotknięcia strefy kruchej zamówień. **Poza zakresem.** |

## ⚠️ Czego restart NIE odzyska

- **16 676 obserwujących** starej Strony — nowa zaczyna od zera. Post z zaproszeniem przeniesie ułamek.
- **Audiencje remarketingowe** — do odbudowania (tygodnie zbierania).
- Historii wyników i optymalizacji kampanii.

To trzeba Ruslanowi powiedzieć wprost: restart jest wykonalny, ale **kosztuje zasięg**. To cena przejęcia konta, nie naszej pracy.

## Testy

**Półautomatyczne**
- Po repoincie GTM: capture zdarzeń na żywo (Events Manager) — czy ViewContent/Contact/Lead docierają do nowego piksela.
- Katalog: czy 3056 ofert zaciąga się poprawnie (ceny, zdjęcia, dostępność).
- Weryfikacja domeny: zielony status w nowym portfolio.

**MCP (Chrome)**
- Podgląd nowej Strony, nowej kampanii, poprawności linków docelowych.

## Definicja zrobionego

- Nowe portfolio + Strona, z naszym dostępem partnera.
- Piksel przepięty, zdarzenia potwierdzone na żywym ruchu.
- Katalog pojazdów zasilany aktualnym feedem (z działającym cronem).
- Kampanie gotowe do startu (czekają tylko na weryfikację UE, jeśli jeszcze nie przeszła).
- Ruslan wie, że obserwujący i audiencje startują od zera.
