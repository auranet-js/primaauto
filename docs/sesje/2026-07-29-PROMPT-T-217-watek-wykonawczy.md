# T-217 — prompt dla wątku wykonawczego

> Przygotowany 2026-07-29 na podstawie wątku analitycznego.
> Podstawa: `docs/sesje/2026-07-29-T-217-rewizja-planu-i-testy.md`
>
> **Przed odpaleniem:** makiety mają być zaakceptowane (PDF §1–§16 + karta zamówienia).
> Trzy decyzje w bloku „Decyzje" są moimi rekomendacjami — jeśli któraś Ci nie pasuje,
> popraw ją w tekście przed wklejeniem. Uzasadnienia poniżej promptu.

---

```
Wdrażamy T-217 — drugi wzorzec umowy (leasing) w generatorze PDF. WYŁĄCZNIE T-217;
nie ruszasz T-220 (aneks VIN), T-221 (pakiet prawny) ani T-113.

Przeczytaj najpierw: docs/sesje/2026-07-29-T-217-rewizja-planu-i-testy.md — tam jest
rewizja planu, ustalenia U1-U7, kolejność wdrożenia (§6) i 30 scenariuszy testów (§7).
Do tego docs/roadmapa/T-217-umowa-leasingowa-szablon.md i pamięć projektu.

Treść wzorca: Google Drive, „GAC Hyper HL … Agw Moto … leasing.docx"
(fileId 1ikQ_tuzfdEVrLxIDQ9_ISI_W8Ypze8Ob). Treść prawna 1:1 — zmieniamy wyłącznie
skład i szatę. Gotowy skład §1-§16 + Załącznik nr 1 jest w tmp/makieta-umowa-leasingowa.php
— przenosisz go do pluginu, nie piszesz od nowa. Makieta jest zaakceptowana.

DECYZJE (rozstrzygnięte, nie pytaj o nie ponownie):
- Kwota depozytu zabezpieczającego NIE przelicza się przy zmianie ceny po wygenerowaniu
  umowy. Zamrażana na zamówieniu, zmiana wyłącznie przez ręczną edycję pola.
- Numer umowy raz nadany zostaje przy przełączeniu typu umowy. Przełączenie zmienia
  wzorzec, nie tożsamość dokumentu — liczniki AA i UL nie dostają dziur.
- Pusty Finansujący NIE blokuje generowania. W §3 zostaje sformułowanie wzorca
  o podmiocie wskazanym przez Zleceniodawcę.

Masz zielone światło na całość i pracujesz bez zatrzymywania się po każdym kroku.
Zatrzymujesz się tylko gdy: rzeczywistość rozjeżdża się z planem na tyle, że trzeba
zmienić decyzję; coś wykracza poza uzgodniony zakres; jest ryzyko utraty danych.

Zasady:
- Dokładasz OBOK. class-asiaauto-contract.php i class-asiaauto-order.php to strefy kruche:
  nowe metody obok istniejących, generate() pośrednictwa nietknięty poza rozgałęzieniem
  na typ umowy, TRANSITIONS i LISTING_RESERVATION_MAP bez zmian.
- Backup przed każdą zmianą (.bak z datą na serwerze, mysqldump przy zmianach w bazie).
  php -l przed każdym wgraniem.
- Po każdym wdrożonym elemencie odpalasz odpowiedni scenariusz z §7 i pokazujesz wynik.
  Zaczynasz od regresji T-01..T-04 — zero regresji na umowie pośrednictwa jest warunkiem,
  nie życzeniem. Nie deklarujesz „działa" bez uruchomienia testu.
- Bump wersji pluginu + docs/VERSIONS.md.
- Na koniec: zapis pamięci, dokument sesji w docs/sesje/, commit i push.

Raportuj zwięźle w trakcie, pełne podsumowanie na końcu: co wdrożone, jakie testy przeszły,
co zostało otwarte, czego nie zrobiłeś i dlaczego.
```

---

## Uzasadnienia trzech decyzji

**Depozyt nie przelicza się po wygenerowaniu umowy.** Klient ma podpisany dokument na konkretną kwotę. Automatyczne przeliczenie po zmianie ceny oznacza, że umowa i system mówią co innego — przy depozycie rzędu 23 tys. zł to spór, nie usprawnienie. Ta sama logika, którą T-121 wymusza dla depozytu zwrotnego (zamrożenie kwoty na zamówieniu).

**Numer zostaje przy zmianie typu.** Gdyby przełączenie typu nadawało numer z drugiej puli, każde przełączenie tam i z powrotem paliłoby po jednym numerze w każdym liczniku. Numeracja umów ma być ciągła — to dokument księgowy, nie identyfikator techniczny.

**Pusty Finansujący nie blokuje.** Wzorzec Ruslana nie wiąże umowy z konkretnym leasingodawcą (§3 ust. 1: „podmiot wskazany przez Zleceniodawcę albo uzgodniony przez Strony"), a w chwili podpisania klient często nie ma jeszcze decyzji leasingowej. Blokada wymuszałaby wpisywanie wartości pozornej.

## Czego ten wątek nie obejmuje

- Aneks VIN (T-220) — osobny task, poza zakresem.
- Pakiet prawny i PayU (T-221, T-121) — osobne taski.
- Ścieżka finansowania w kreatorze (T-113) — gated, poza zakresem.
- Przebudowa statusów pod bramki leasingowe — świadomie nie w tym zadaniu.

## Do zgłoszenia Ruslanowi (nie blokuje wdrożenia)

- Czy 10% depozytu to reguła, czy ustalenie z tym jednym klientem.
- Paliwo w podpisanej umowie („Hybryda plug-in / PHEV") rozjeżdża się z danymi z ogłoszenia
  („Elektryczny z range extenderem / EREV"). GAC Hyptec HL to EREV — błąd jest w dokumencie,
  nie w systemie. Pole edytowalne to obchodzi, ale warto, żeby wiedział.
- W §2 podpisanego egzemplarza lista parametrów zaczyna się od `e)` — brakuje punktów a–d.
  W makiecie zrekonstruowane jako marka i model, paliwo, kolor, rok produkcji (kolejność
  z Załącznika nr 1). Do potwierdzenia przy okazji.
