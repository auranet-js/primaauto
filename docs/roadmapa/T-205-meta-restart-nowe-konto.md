# T-205 — Meta: restart na nowym koncie (Facebook/Instagram)

> Status: **czeka na Ruslana** (założenie nowej strony + portfolio) · Rozmiar: M
> Godziny realnie: **16–22 h** (Janek ~4–5 h, AI ~12–17 h) · Rynkowo: 40–50 h
> Zastępuje pozycję „Uruchomienie kampanii Facebook/Instagram" (17–20 h) — tamta zakładała, że mamy dostęp do konta. Nie mamy.

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
