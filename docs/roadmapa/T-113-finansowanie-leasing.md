# T-113 — Finansowanie / leasing jako alternatywa w umowie

> Status: **GATED na partnera finansującego** (pytanie do Ruslana) · Rozmiar: L
> Godziny realnie: **30–40 h** (Janek ~6–8 h, AI ~24–32 h) · Rynkowo: 85–100 h
> Obniżone z 40–50 h — po zwiadzie wiadomo, że to **nie jest integracja z bankiem**, tylko ścieżka w kreatorze + wzorzec umowy.

## Rozgraniczenie z T-189 (ważne)

- **T-189 (kalkulator raty)** — pokazuje szacunkową ratę i zbiera leady. **Nie wymaga partnera**, parametry konfigurowalne. **Można zrobić od razu.**
- **T-113 (to zadanie)** — klient realnie **wybiera finansowanie zamiast płatności całością**, i to musi mieć pokrycie w umowie i w partnerze. **Bez partnera nie ma czego robić.**

Czyli T-189 generuje popyt („zapytaj o finansowanie" → lead), a T-113 ten popyt obsługuje. **T-189 jest testem, czy T-113 się opłaca** — jeśli po 2 miesiącach nikt nie klika „zapytaj o finansowanie", T-113 wypada z roadmapy.

## Po co

Otwiera sprzedaż klientom bez gotówki na całość. Przy autach 100–300 tys. zł to nie jest nisza — to jest większość rynku.

## ⚠️ Bloker — partner finansujący

**Do ustalenia z Ruslanem:**
1. Czy ma już firmę finansującą (leasing/kredyt samochodowy)? Jaką?
2. Jeśli nie — czy chce, żebyśmy pomogli wybrać? (To osobna praca, nie objęta tą wyceną.)
3. Jaki model: leasing operacyjny (firma) / najem / kredyt (osoba prywatna)? **Prawdopodobnie oba** — konkurent (azjaauto.com) ma przełącznik „Firma / Osoba prywatna".

**Czego potrzebujemy od partnera, żeby ruszyć:**
- Oprocentowanie / współczynniki rat, prowizja.
- Zakres wpłaty własnej i wykupu.
- Wymagane dokumenty od klienta.
- Czy finansują **auto importowane z Chin przed rejestracją w PL** ← **to jest kluczowe pytanie i realne ryzyko**. Wiele firm nie finansuje auta, którego jeszcze nie ma w kraju.
- Czy finansowanie obejmuje też koszty importu (cło, VAT, akcyza), czy tylko wartość auta.

## Stan faktyczny

- Kreator zamówienia: `class-asiaauto-order-wizard.php`, kroki mapowane ze statusów (`:618-633`). Krok 4 = umowa + depozyt.
- Umowa PDF: `class-asiaauto-contract.php` (1075 l., mPDF, §1–§9, **model agencyjny/pośrednictwo**). Meta: `_order_contract_commission_net`, `_order_vin`.
- Depozyt: 10% ceny, min. 30 tys. (`calculateDeposit()`).
- **Zero kodu finansowania.**

## Plan (po odblokowaniu)

1. **Ustalenia z partnerem** (Janek + Ruslan, ~6–8 h) — patrz wyżej. **To jest praca, nie formalność.**
2. **Ścieżka wyboru w kreatorze:** po kroku „potwierdzone" → „Jak finansujesz zakup?" → *Płacę całością* (dzisiejsza ścieżka, nietknięta) / *Finansowanie* (nowa gałąź).
3. **Gałąź finansowania:** formularz danych do wniosku → przekazanie do partnera (mail/API — zależy od partnera) → status „wniosek złożony" / „decyzja pozytywna" / „odmowa" → dopiero potem umowa.
4. **Wzorzec umowy dla finansowania** — inny niż dzisiejszy (pośrednictwo + cesja/trójstronność). **Wymaga prawnika.** Depozyt może działać inaczej albo w ogóle nie występować.
5. **Nowe statusy zamówienia** — ⚠️ **strefa krucha.** Rozszerzenie `TRANSITIONS` o gałąź finansowania. **To jest najbardziej ryzykowna część zadania** i powód, dla którego to nie jest 20 h.
6. **Panel admina** — podgląd wniosków, statusy.

## Strefy kruche

- **`class-asiaauto-order.php`** — dodanie nowej gałęzi statusów. Największe ryzyko regresji w całym zadaniu. Wymaga testów regresji na wszystkich 11 istniejących statusach.
- **`class-asiaauto-contract.php`** — drugi wzorzec umowy. **Nie modyfikować istniejącego** — dołożyć obok (wybór szablonu wg ścieżki).

## Testy

**Automatyczne**
- Regresja: wszystkie dzisiejsze przejścia statusów działają bez zmian (zamówienie bez finansowania = ścieżka nietknięta).
- Generowanie obu wzorców umowy → poprawny PDF, poprawne paragrafy.

**Półautomatyczne**
- Przejście pełnej ścieżki finansowania na zamówieniu testowym.
- Weryfikacja prawna umowy (prawnik, nie my).

**MCP (Chrome)**
- Kreator: wybór ścieżki, formularz wniosku, statusy.
- Mobile.

## Definicja zrobionego

- Klient wybiera w kreatorze: całość albo finansowanie.
- Ścieżka finansowania ma wzorzec umowy zweryfikowany prawnie.
- Wniosek trafia do partnera, statusy widoczne dla klienta i admina.
- **Zero regresji na dotychczasowej ścieżce płatności całością.**
