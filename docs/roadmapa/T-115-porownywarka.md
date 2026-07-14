# T-115 — Porównywarka aut

> Status: **gated na T-116 krok 4** (tabela specs) — inaczej porównanie liczy się z blobów · Rozmiar: M
> Godziny realnie: **20–28 h** (Janek ~3 h, AI ~17–25 h) · Rynkowo: 50–65 h
> Podniesione z 17–20 h: dochodzi kuratorska selekcja parametrów i UX tabeli na mobile.

## Po co

Kilka aut obok siebie w tabeli specyfikacji — łatwiejszy wybór, dłuższy czas na stronie, mniej powrotów do Google. Naturalny partner ulubionych (T-114): „zaznacz 3 z parkingu → porównaj".

## Stan faktyczny

- **Zero kodu.** Zielone pole.
- Dane: `_asiaauto_extra_prep` — 105 parametrów wspólnych dla obu źródeł, z czego ~30 numerycznych (wymiary, masy, silnik, bateria, zasięg, przyspieszenie, zawieszenie, hamulce, opony).
- **Render specyfikacji już istnieje** — `[asiaauto_tech_specs]` na stronie oferty, z tłumaczeniami (`translations-extra-prep.php` — 1066 kluczy, `-values.php` — 216 wartości). **Nie tłumaczymy niczego od nowa.**
- ⚠️ Ten sam problem uszkodzonego unicode co w T-116 — wartości Dongchedi wymagają łatki przy odczycie. Po migracji z T-116 (tabela specs) problem znika.

## Zależność

Da się zrobić **bez** T-116 (czytając blob i przepuszczając przez istniejącą łatkę), ale wtedy:
- porównanie 4 aut = 4 × dekodowanie bloba po 342 klucze → wolno,
- powielamy dług zamiast go spłacić.

**Rekomendacja: po tabeli specs z T-116.** Wtedy porównywarka to głównie UI.

## Plan (kroki)

1. **Selekcja parametrów do porównania** (Janek + Ruslan, ~2 h) — ~25–35 wierszy tabeli pogrupowanych: *Podstawowe* (cena, rok, przebieg, moc), *Napęd i osiągi*, *Bateria i zasięg* (dla EV/PHEV), *Wymiary i przestrzeń*, *Wyposażenie kluczowe*. **Nie wrzucamy 342 wierszy** — to zabija użyteczność.
2. **Wybór aut do porównania:** checkbox „Porównaj" na karcie (ten sam helper karty co T-187/T-114) + pasek na dole ekranu „Porównujesz: 2/4 → Porównaj". Limit 4 (mobile nie uniesie więcej).
3. **Storage:** zalogowany → user meta; niezalogowany → sesja/localStorage (porównywarka **nie musi** wymuszać konta — to narzędzie do przeglądania, nie do budowy bazy; wymuszanie logowania tutaj zabiłoby użycie).
4. **Strona `/porownaj/`** — tabela: auta w kolumnach, parametry w wierszach. Wiersze, gdzie wszystkie wartości identyczne → domyślnie zwinięte („pokaż też 12 identycznych"). **Podświetlenie różnic** — to jest cała wartość porównywarki.
5. **Mobile:** kolumny przewijane poziomo z **przyklejoną pierwszą kolumną** (nazwy parametrów). To jest najtrudniejsza część UX i główny powód, dla którego to nie jest 17 h.
6. **Obsługa braków:** auto bez danego parametru → „—", nie pusta komórka.
7. **SEO:** strona `noindex` (kombinacje = nieskończoność URL-i). **Twardy wymóg** — inaczej robimy sobie index bloat.

## Strefy kruche

Brak — wszystko addytywne. Dotknięcie helpera karty (wspólnego z T-187/T-114).

## Testy

**Automatyczne**
- Porównanie 4 aut z różnych źródeł (Dongchedi + Che168) → brak krzaków, brak `u4e2d`, braki jako „—".
- Porównanie EV vs spalinowe → sekcja baterii pokazuje „—" dla spalinowego, nie znika i nie wywala widoku.
- `/porownaj/` zwraca `noindex` w nagłówku.

**Półautomatyczne**
- Czas renderu porównania 4 aut (cel: <500 ms).
- Sprawdzić, ile z ~30 wybranych parametrów ma realne pokrycie ≥90% — parametry z niskim pokryciem tylko zaśmiecają tabelę.

**MCP (Chrome)**
- Zaznacz 3 auta w katalogu → pasek → `/porownaj/` → tabela z podświetlonymi różnicami.
- Mobile 375px: przewijanie poziome z przyklejoną kolumną nazw — **to jest test, który decyduje o akceptacji**.
- Zaznacz 5. auto przy limicie 4 → komunikat, nie ciche zignorowanie.

## Definicja zrobionego

- Checkbox „Porównaj" na kartach, pasek z licznikiem, limit 4.
- Tabela z podświetlonymi różnicami, zwinięte wiersze identyczne.
- Mobile: użyteczna (przyklejona kolumna nazw).
- `noindex` na stronie porównania.
- Działa dla aut z obu źródeł, EV i spalinowych.
