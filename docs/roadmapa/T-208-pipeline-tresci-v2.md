# T-208 — Pipeline treści v2: kontrola jakości i odświeżanie

> Status: gotowy do odpalenia · Rozmiar: L (rozbity na 4 podpozycje)
> Godziny realnie: **40–55 h** (Janek ~6 h, AI ~34–49 h) · Rynkowo: 110–140 h
> **Obniżone z 70–80 h.** Kosztorys traktował to jak jedną wielką bryłę „przepiszemy wszystko". Po zwiadzie: fundamenty są, brakuje trzech konkretów.

## Co JUŻ działa (i czego nie wyceniamy ponownie)

| Element | Stan |
|---|---|
| Treści hubów | ✅ **455 hubów ma wiki, 452 ma FAQ** |
| Pokrycie żywych hubów (z ofertami) | ✅ **296/302 serie, 53/56 marek** — do dogenerowania realnie **9 hubów**, nie setki |
| Bramka strukturalna (min. długość, wymagane sekcje, ≥4 pytania FAQ) | ✅ twarda — błąd = brak zapisu |
| Lint anty-slop (9 zakazanych fraz: „bez wątpienia", „game-changer", „rewolucyjn"…) | ✅ działa |
| Znaczniki `generated_at` i `prompt_version` | ✅ zapisywane |
| REST z faktami z bazy (karmi generator prawdziwymi danymi) | ✅ działa |
| Koszt generowania | ✅ **~$0,07 / tekst** |

## Czego brakuje — i to jest te 40–55 h

### 🔴 1. Fact-check (nie istnieje wcale) — ~14–18 h
Generator **pobiera** fakty z bazy i wkłada je do promptu, ale **nigdy nie sprawdza, czy liczby w wygenerowanym tekście się z nimi zgadzają**. Halucynacja zasięgu, mocy albo ceny **przejdzie walidację bez problemu**.

Piszemy o cenach aut za 100–300 tys. zł. **To jest największe ryzyko całego pipeline'u contentowego** — i jedyna rzecz, która naprawdę uzasadnia „v2".

Do zrobienia: warstwa porównująca liczby wyekstrahowane z tekstu z faktami z REST → rozbieżność = odrzucenie i regeneracja.

### 🔴 2. Odświeżanie nieaktualnych treści (nie istnieje) — ~12–16 h
- Najnowsza treść wygenerowana **30.05** — **nic nie było odświeżane od 6 tygodni**.
- Hub napisany w kwietniu twierdzi, że model kosztuje X i jest 12 sztuk. Dziś cena i stan są inne. **Treść kłamie, bo się zestarzała.**
- Znacznik `generated_at` **jest zapisywany, ale nikt na nim nie działa** — brak crona, brak reguły „nieaktualne po N dniach".

*(Uwaga: tytuły i meta hubów odświeżają się codziennie — to działa. Treść wiki/FAQ nie jest ruszana.)*

Do zrobienia: cron wykrywający nieaktualność (wiek + zmiana ceny/stanu) → kolejka do regeneracji.

### 🔴 3. Panel statusów w adminie (nie istnieje) — ~8–12 h
Dziś jedyny ślad po jakości treści to wydruk na ekranie skryptu i pliki w `tmp/`. Ruslan ani Janek nie widzą nigdzie, które huby mają treść, która jest stara, która nie przeszła walidacji.

### 🟡 4. Ujednolicenie promptów — ~6–9 h
**~80% treści (362 huby) leci na promptach z kwietnia**, tylko 56 na nowszym. Niespójność stylistyczna między pokoleniami jest wbudowana. Regeneracja starszych na aktualnym prompcie (koszt AI: ~$25).

### 🟡 5. Przeniesienie generatora do produktu — wliczone
Generator to dziś **jednorazowy skrypt w `tmp/`**, nie część pluginu. Nie jest wersjonowany, nie ma crona. Do przeniesienia (`scripts/` + wersjonowanie).

## ⚠️ Ważne sprostowanie do kosztorysu

Kosztorys mówił o „kontroli jakości treści AI" tak, jakby opisy ofert były generowane przez AI. **Nie są.**

- **Opisy ofert (3056 szt.) to tłumaczenia maszynowe** chińskich opisów z API (Gemini, fallback DeepL). Średnia długość **518 znaków** — bo chińskie oryginały są krótkie.
- **335 ofert (11%) nie ma opisu w ogóle.**
- Treści generowane przez AI to **wyłącznie huby** (wiki, FAQ).

To znaczy, że „pipeline opisów v2" dotyczy hubów, a osobnym (nieujętym) tematem jest **to, że co dziesiąta oferta nie ma żadnego opisu**.

## Strefy kruche

- REST zapisujący treść (`class-asiaauto-rest-hub.php`) — dokładamy walidację **po stronie serwera** (dziś kto ma token, wrzuci dowolny śmieć).
- Cron regeneracji **nie może** ruszać treści hubów ręcznie poprawionych — potrzebna flaga „nie nadpisuj".

## Testy

**Automatyczne**
- Fact-check: podstaw tekst z zawyżonym zasięgiem → musi zostać odrzucony. Tekst zgodny z bazą → przechodzi.
- Walidacja serwerowa: próba zapisu treści bez wymaganych sekcji → 400, nie zapis.
- Regeneracja nie nadpisuje treści oznaczonej jako ręczna.

**Półautomatyczne**
- Regeneracja 362 hubów: **dry-run najpierw** (reguła „dry-run przed batchem"), sprawdzić koszt i próbkę 10 tekstów przed puszczeniem całości.
- Po wdrożeniu fact-checku: przepuścić przez niego **istniejące 455 hubów** → ile z nich zawiera nieprawdziwe liczby. **To jest pomiar, którego nigdy nie zrobiliśmy.**

## Definicja zrobionego

- Żadna treść z nieprawdziwą liczbą nie przejdzie do publikacji.
- Nieaktualne huby są wykrywane i regenerowane automatycznie.
- W adminie widać stan treści (jest / stara / błąd walidacji).
- Wszystkie huby na jednym pokoleniu promptu.
- Wiadomo, ile z istniejących 455 hubów zawierało błędy faktograficzne.
