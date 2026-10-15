# T-265 „prezentacja procesu zakupu — wideo + karuzela”

> Zgłoszone 2026-10-14 (Ruslan → Janek, Telegram). Decyzje Janka 14.10 w quizie.
> Status: spec, do realizacji teraz (równolegle z W1/W2), ponowny render sceny panelu po W4.

## Geneza

Ruslan przesłał karuzelę konkurenta **M1LE** (`@m1le.pl`, TikTok photo, 09.10.2026,
https://vm.tiktok.com/ZN8SNbAQ9/) — 9 slajdów 4:5, „Zakup za gotówkę — 7 prostych kroków”.
Jego prośba (wiadomość głosowa, transkrypcja):

> „Przesuwasz palcem w prawo i ona tak krok po kroku pokazuje cały proces zakupu. A my możemy taką samą zrobić,
> ale jeszcze jaką mamy stronę taką. […] najpierw szukamy auta, filtra wybieramy, jest porównanie […]
> Potem następny krok: wpłacasz zaliczkę, podpisujesz umowę, […] u ciebie w pulpicie pokazuje się to, co kupione auto.
> […] żeby to wyglądało najlepiej w całej Polsce.”

Kanały wg Ruslana: Telegram, TikTok, Facebook, YouTube — „w dwóch formatach”.

## Co robi konkurent (analiza 9 slajdów)

- Czarne tło, jeden kolor akcentu (miętowy), wyłącznie typografia — **zero zrzutów strony**.
- Wzór: okładka → `KROK 01/07` … `07/07` → podsumowanie „Cały proces w trzech liczbach”.
- Elementy, które niosą przekaz: **ciągła oś czasu przechodząca przez wszystkie slajdy** (panorama — slajd urywa się
  w środku linii, palec sam przesuwa dalej), duża cyfra kroku w tle, **pasek „Twoje wpłaty” 0% → 30% → 100%**
  z jednym zdaniem pod spodem („Do inspekcji nie płacisz nic”).
- Wynik: 315 wyświetleń, 0 polubień po 5 dniach — format nie jest dowodem skuteczności, wartość jest w strukturze.

**Nasza przewaga, którą Ruslan chce pokazać:** strona — wyszukiwarka, filtry, porównywarka, zgłoszenie, panel.
Statyczny zrzut słabo pokazuje interakcję, stąd decyzja: wideo jako główny materiał.

## Decyzje (Janek, 14.10)

| # | Decyzja | Wybór |
|---|---|---|
| D1 | Forma | **Wideo 9:16 jako materiał główny + karuzela 4:5 ze stopklatek tych samych scen** (jedno źródło, dwa wyniki) |
| D2 | Kiedy | **Teraz, na obecnym panelu.** Scenariusz i render są skryptem — po W4 (nowy panel) uruchamiamy go ponownie i scena panelu aktualizuje się sama |
| D3 | Model płatności na slajdach (Janek 14.10: depozyt uruchamia szukanie i sprawdzenie auta, kontakt z klientem i dostawcą, przeprowadzenie zakupu i przygotowanie formalności) | **Depozyt → 30% → 70%** (potwierdzone przez Janka jako realna praktyka): 0 zł przy wyborze i zgłoszeniu → zwrotny depozyt 6 150 zł przy weryfikacji egzemplarza → 30% ceny po umowie → 70% najpóźniej 10 dni przed portem → odbiór. Słowo „zaliczka” tylko tam, gdzie używa go `/proces-zamawiania/` (30% ceny), nigdy o depozycie |
| D4 | „Dwa formaty” | **Karuzela 4:5** (FB/IG/TikTok tryb zdjęć) + **wideo 9:16 30–45 s** (Reels, Shorts, TikTok, Telegram) |

## Scenariusz (szkic do akceptu na starcie wątku)

Słownictwo zgodne z umową i T-264: „zgłoszenie rezerwacji”, „Chcę to auto”, „umowa zlecenia”, „zwrotny depozyt”,
Zleceniodawca kupuje auto, Prima-Auto organizuje. **Nie** „zaliczka”, „kupno-sprzedaż”, „zamów”.

| Scena | Ekran (żywa strona) | Ruch | Pasek „Twoje wpłaty” |
|---|---|---|---|
| 0 Okładka | plansza | — | 0 zł |
| 1 Szukasz auta | `/samochody/` lub wyszukiwarka | przewijanie listy, liczba ofert | 0 zł |
| 2 Filtrujesz | wyszukiwarka, arkusz filtra na telefonie | kliknięcie marki, zasięgu, wyposażenia, licznik wyników maleje | 0 zł |
| 3 Porównujesz | `/porownywarka/` | dodanie 2 aut, przewinięcie tabeli | 0 zł |
| 4 Wybierasz | karta oferty: galeria, cena, specyfikacja | zbliżenie na cenę „na gotowo” | 0 zł |
| 5 „Chcę to auto” | formularz zgłoszenia rezerwacji | wypełnienie, „bezpłatne, do niczego nie zobowiązuje” | 0 zł |
| 6 Depozyt uruchamia pracę | `/depozyt/` + plansza z listą: szukamy i sprawdzamy auto, kontakt z Tobą, kontakt z dostawcą, przeprowadzenie zakupu, przygotowanie formalności | lista pojawia się punkt po punkcie | **6 150 zł zwrotnego depozytu** |
| 7 Umowa i 30% | kreator: dane do umowy, umowa PDF | zbliżenie na umowę | **+30% ceny auta** |
| 8 Twój panel | panel klienta: status zamówienia, auto | przejście statusów | 30% |
| 9 Transport, 70% i odbiór | plansza / zdjęcie z placu w Rzeszowie | — | **+70% przed portem → 100%** |
| 10 Podsumowanie + CTA | plansza: primaauto.com.pl, telefon | — | — |

Karuzela = 10 stopklatek scen 0–9 + CTA, z ciągłą osią czasu przez slajdy (jak u konkurenta).
**Do potwierdzenia w wątku:** jak pokazać rozliczenie depozytu (zwrot / zaliczenie wg umowy) i czy cło + VAT idą w „cenie na gotowo” na pasku, czy osobnym zdaniem.

## Wykonanie

- **Silnik:** `~/projekty/auranet/scripts/video_prezentacja.py` (reguła: wideo tylko tym silnikiem, nigdy `record_video`).
  Dziś robi 16:9 z marką Auranet → rozszerzenie o **tryb pionowy 1080×1920 / 1080×1350** i **motyw Prima-Auto**
  (praca w repo `auranet`, commit tam osobno). Przeglądarka przez `pptr` na Mikrusie, nie na Elarze.
- Scenariusz: `scripts/video-proces-zakupu.json` w primaauto.
- Dane: prawdziwe ogłoszenie z placu (atrakcyjne auto, wybór Ruslana albo nasz), scena panelu/umowy na
  **zamówieniu testowym 410903** — bez danych prawdziwych klientów, zrzuty sprawdzone pod RODO.
- Telefon: sceny renderowane w widoku mobilnym (79,6% ruchu).
- Dźwięk: bez muzyki w pliku (licencje) — muzykę dodaje się natywnie na platformie; napisy wtopione, bez lektora w v1.
- Pliki wynikowe: MP4 9:16 + 10–11 × JPG 1080×1350, wystawione na auratest do akceptu Janka, potem do Ruslana przez Janka.

## v4 — uwagi Ruslana (15.10)

- 70% bez terminu: scena „fin” = „70% dopłacasz później — bez odsetek” (decyzja Janka; bez słowa „kredyt” — wymogi reklamy kredytu konsumenckiego).
- Nowa scena „koniec” (5,6 s): klip AI „Twoje auto czeka” (klient obejmuje maskę auta z czerwoną kokardą) + CTA; przed outro.
  Generacja: `scripts/reel-proces-zakupu/koncowka.py` (Gemini kadr → Veo 3.1 Fast), prompty i wybrane ujęcie w `koncowka-prompt.md`.
- Auto: **Zeekr 8X** (`/oferta/zeekr-8x-2026-540080/`, wybór Ruslana).
- **Podmiana auta = `scripts/reel-proces-zakupu/auto.json`** (URL + numery zdjęć). `prima-reel-zrzuty.mjs` robi zrzuty, pobiera zdjęcia
  i mierzy z DOM pozycje kliknięć (filtr marki przez „Szukaj…”, cena, „Chcę to auto”, pola formularza) do `img/dane.js`;
  `reel.html` bierze z niego nazwę, rok, cenę, zdjęcia i współrzędne. Karta auta jest wstawiana na górę listy i wyników filtra
  (prawdziwa karta z `asiaauto/v1/listings`). Potem tylko nowa końcówka AI z nowym zdjęciem referencyjnym.
- **Lektor** (Janek/Ruslan 15.10: głos „Charon”): `lektor.py` (Gemini `gemini-3.1-flash-tts-preview`) + `lektor.json` (kwestie,
  start i okno w sekundach gotowego filmu). `lektor.py sceny Charon <kat>` → `lektor.py miks <kat> reel-v4.mp4 <wyj>.mp4`
  (cisza obcięta, tempo dopasowane do okna, −16 LUFS). Wszystkie kwestie zmieściły się bez przyspieszania; wymowa sprawdzona
  transkrypcją Gemini. Wersja bez lektora zostaje (platformy, gdzie dodaje się muzykę). Przy zmianie auta lektor zostaje bez zmian.

## Poza zakresem

- Publikacja na kanałach (robi Ruslan/Andrzej; automat = T-243, odłożony).
- `/proces-zamawiania/` nie wspomina o depozycie (opisuje płatność za auto po umowie, 30/70), a `/depozyt/` opisuje wpłatę przed umową — obie strony poprawne i zaakceptowane przez Ruslana, ale czytane razem mogą mylić („na start 30%” vs „na start 6 150 zł”). Ewentualny dopisek o depozycie — decyzja Janka/Ruslana, nie część T-265.
- Wersja 16:9 na YouTube (nie zamówiona).

## Wycena

**16–22 h realnie** (scenariusz + teksty 2–3 · rozszerzenie silnika: pion + motyw 5–7 · sceny i dane demo 4–5 ·
karuzela ze stopklatek 1–2 · montaż i poprawki po review 4–5) **+ 1–2 h ponowny render po W4**.
