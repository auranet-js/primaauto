# T-202 — Kreator zamówienia: galeria sprzedanych aut + elementy zaufania

> Status: **gotowy do odpalenia** · Rozmiar: M
> Godziny realnie: **12–16 h** (Janek ~2 h, AI ~10–14 h) · Rynkowo: 30–40 h
> Estymata **potwierdzona** — i uzasadniona twardą liczbą (poniżej).

## 🔴 Po co — liczba, która to uzasadnia

**33 zamówienia anulowane na 135 — to 24%. Co czwarty klient się wycofuje.**

Rozkład statusów (baza, 14.07):

| Status | Liczba |
|---|---|
| **anulowane** | **33** |
| w drodze | 25 |
| zakończone | 22 |
| na placu | 15 |
| weryfikacja | 13 |
| potwierdzone | 9 |
| umowa gotowa | 8 |
| zakupione | 6 |
| odrzucone | 2 |
| podpisane | 2 |

Nie wiemy, ilu z tych 33 odpadło z braku zaufania, a ilu z innych powodów. **Ale wiemy, że krok, w którym klient ma podpisać umowę i wysłać kilka tysięcy złotych, nie zawiera ani jednego dowodu, że jesteśmy wiarygodni.** Zero zdjęć. Zero licznika. Zero opinii. Sam formularz i ikona.

Jeśli elementy zaufania odzyskają choć **jedno na dziesięć** anulowanych zamówień, task zwraca się wielokrotnie.

## Stan faktyczny

### Kreator — 5 kroków, punkty zaczepienia gotowe
`class-asiaauto-order-wizard.php`:

| Krok | Co się dzieje |
|---|---|
| 1 | Dane kontaktowe |
| 2 | Weryfikacja dostępności |
| 3 | Dane do umowy |
| **4** | **Umowa + depozyt** ← szczyt niepewności |
| 5 | Realizacja (śledzenie) |

**Krok 4 (l. 234–344)** — cztery bloki: pobierz umowę → podpisz → przelej depozyt → wyślij potwierdzenie. Kończy się suchym komunikatem „Po zaksięgowaniu wpłaty samochód zostanie zarezerwowany."

**Miejsca wpięcia (istnieją, nie trzeba ich tworzyć):**
- **Sidebar** (`.aa-wiz__price-row`, l. 404–433) — widoczny przez **wszystkie 5 kroków**. Element zaufania wstawiony raz działa na całej ścieżce. **Najtańszy punkt integracji.**
- **`.aa-wiz__info-box`** w kroku 4 (l. 340) — gotowy kontener.
- Między blokiem przelewu a potwierdzeniem — dokładnie w momencie największego wahania.

### Dane — są, licznika nie ma

**Sprowadzone auta:** 22 (zakończone) + 15 (na placu) = **37 dostarczonych**. Plus **25 w drodze**.

Do komunikacji najuczciwiej: **„Sprowadziliśmy już 37 aut"** (dostarczone) albo **„62 auta sprowadzone i w drodze"**. ⚠️ Nie zawyżać — Ruslan zna te liczby, a klient może zapytać.

Licznik **nie istnieje nigdzie w kodzie** — do napisania (~2 h, zapytanie + cache).

### ⚠️ Galeria klientów — istnieje, ale w formie trudnej do reużycia

Strona `/klienci/` (ID 350745): **47 zdjęć** w natywnych blokach galerii Gutenberga, w treści strony. **Brak shortcode'a.** Zdjęcia **nie są powiązane** z zamówieniami ani ofertami — to luźne załączniki wklejone w blok.

Dwie drogi:
- **(a) Szybka:** wyciągnąć identyfikatory zdjęć z treści strony (~1–2 h). **Kruche** — Ruslan edytuje stronę i logika się sypie.
- **(b) Trwała:** powiązać zdjęcia z zamówieniami (zdjęcie odbioru przy zamówieniu). Czysta, ale **wypycha task poza 16 h**.

**Rekomendacja: (a) teraz, z jawnym zabezpieczeniem** (jeśli parsowanie zawiedzie → blok się nie renderuje, zamiast wywalać kreator). Wariant (b) jako osobna pozycja, jeśli galeria się sprawdzi.

## Plan

1. **Licznik sprowadzonych aut** (~2 h) — „37 aut dostarczonych klientom", z cache. W sidebarze kreatora → widoczny na wszystkich krokach.
2. **Mini-galeria klientów w kroku 4** (~4–5 h) — 4–6 zdjęć z `/klienci/` + link „zobacz wszystkie". To jest dowód, że auta naprawdę docierają.
3. **Kroki procesu z gwarancjami** (~2–3 h) — „co się stanie po wpłacie": rezerwacja → zakup → transport → odprawa → odbiór. Z jasnym zaznaczeniem, **że depozyt jest zwrotny**, jeśli zakup nie dojdzie do skutku. To jest najważniejsze zdanie w całym kroku 4.
4. **Sekcja „czy to bezpieczne"** (~3–4 h) — 4–5 pytań (co z moimi pieniędzmi, co jeśli auto okaże się inne, kto odpowiada za transport, co z gwarancją). Odpowiedzi **zgodne z umową** — nie marketing, tylko streszczenie zapisów.
5. **Elementy zaufania w sidebarze** (~1–2 h) — licznik + „umowa z polską firmą" + NIP + telefon.

## Strefy kruche

- `class-asiaauto-order-wizard.php` — dokładamy **wyłącznie prezentację**. Zero zmian w logice kroków, statusów, przesyłania plików.
- **Nie dotykamy** `class-asiaauto-order.php` (statusy, depozyt, rezerwacje).

## Testy

**Automatyczne**
- Licznik zwraca poprawną liczbę (zgodną z zapytaniem do bazy).
- Awaria parsowania galerii → blok znika, **kreator działa dalej** (nie może się wywalić przez ozdobnik).

**Półautomatyczne**
- Przejście pełnej ścieżki zamówienia — czy elementy zaufania nie przeszkadzają w wypełnianiu formularza.
- Weryfikacja treści „czy to bezpieczne" **z umową** — żadne zdanie nie może obiecywać więcej, niż mówi umowa.

**MCP (Chrome)**
- Krok 4 na desktopie i mobile: czy dowody zaufania są widoczne **przed** przyciskiem, nie pod nim.
- Mobile 375px — czy galeria nie rozpycha layoutu.

## Definicja zrobionego

- W kroku 4 klient widzi: ile aut sprowadziliśmy, zdjęcia klientów z odebranymi autami, co się stanie z jego pieniędzmi, i że depozyt jest zwrotny.
- Licznik i elementy zaufania widoczne w sidebarze przez całą ścieżkę.
- Żadne zdanie nie obiecuje więcej niż umowa.
- Kreator działa nawet gdy galeria zawiedzie.
- **Do zmierzenia po 60 dniach: czy wskaźnik anulowań (dziś 24%) spadł.**
