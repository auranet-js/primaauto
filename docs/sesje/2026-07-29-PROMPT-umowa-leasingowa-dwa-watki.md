# Umowa leasingowa (T-217 + T-220 + T-221) — prompty do dwóch wątków

> Przygotowane 2026-07-29. Wątek 1 kończy się **makietami do akceptu**.
> Wątek 2 startuje dopiero po Twoim „zielone światło" i idzie do końca bez zatrzymań.

---

## WĄTEK 1 — ANALIZA I PRZYGOTOWANIE (do skopiowania)

```
Bierzemy temat umowy leasingowej — taski T-217 (drugi wzorzec umowy w generatorze PDF),
T-220 (aneks VIN) i T-221 (pakiet prawny pod PayU). To jest wątek WYŁĄCZNIE analityczny:
nie wdrażasz niczego, nie dotykasz produkcji, nie piszesz kodu docelowego. Kończysz na
makietach i scenariuszach testów, które mam zaakceptować.

Wykonaj po kolei i po każdym kroku pokaż wynik, zanim przejdziesz dalej:

KROK 1 — co zostało zaprojektowane
Przeczytaj: docs/roadmapa/T-217-umowa-leasingowa-szablon.md, T-220-aneks-vin.md,
T-221-pakiet-prawny-payu.md oraz zależne T-113-finansowanie-leasing.md i
T-121-platnosc-online-depozyt.md. Do tego analizy źródłowe:
docs/biznes/2026-07-17-umowa-organizacji-importu-leasing-analiza.md,
docs/biznes/2026-07-17-umowa-efl-leasing-analiza.md,
docs/biznes/2026-07-27-punkty-do-weryfikacji-ruslan.md.
Odpowiedz: co dokładnie zaprojektowano, jakie decyzje już zapadły i czyje, gdzie plan jest
konkretny, a gdzie zostawia luki. Oceń JAKOŚĆ tego projektu — czy założenia się bronią,
czy kolejność zależności jest poprawna, czy szacunki godzin są realne. Nie streszczaj mi
tasków, tylko oceń je krytycznie i wskaż, co bym zmienił na Twoim miejscu.

KROK 2 — stan w pamięci
Przeczytaj MEMORY.md projektu i wpisy dotyczące umów, zamówień, PayU, depozytu i statusów.
Wypisz, co pamięć twierdzi o stanie tych obszarów.

KROK 3 — stan faktyczny w plikach (KROK 0 z feedback_verify_backlog_against_server)
Pamięć i backlog to nie jest dowód. Zweryfikuj na SERWERZE PRODUKCYJNYM
(~/domains/primaauto.com.pl/public_html/), bo to jedyne źródło prawdy dla kodu:
- class-asiaauto-contract.php — jak działa dziś generator, gdzie są punkty rozszerzenia,
  co znaczy „Contract Rework Krok 1-4 DONE, Krok 5 pending",
- class-asiaauto-order.php — statusy, TRANSITIONS, gdzie zamrażany jest depozyt,
- strony /regulamin-uslugi/ i /depozyt/ — czy istnieją, czy mają noindex, czy są podlinkowane,
- konfiguracja rachunków bankowych, meta zamówień, obecne szablony PDF.
Zestaw tabelarycznie: co backlog twierdzi ✕ co jest naprawdę. Rozjazdy nazwij wprost.

KROK 4 — oględziny przez Chrome MCP
Obejrzyj na żywo (zaloguj się do panelu, jeśli trzeba): kreator zamówienia krok po kroku,
kartę zamówienia w adminie, wygenerowany PDF umowy obecnego wzorca, strony /regulamin-uslugi/,
/depozyt/, /finansowanie/. Rób zrzuty. Interesuje mnie, jak to wygląda i zachowuje się
NAPRAWDĘ, nie jak wynika z kodu. Zgłoś każdą różnicę wobec tego, co ustaliłeś w kroku 3.

KROK 5 — rewizja planu
Na podstawie kroków 1-4 zrewiduj plan. Co odpada, co dochodzi, co zmienia kolejność, jakie
ryzyka widzisz, gdzie plan zakłada coś, co nie jest prawdą. Jeśli uważasz, że projekt jest
dobry i nie wymaga zmian — powiedz to wprost i uzasadnij. Przedstaw zrewidowaną kolejność
wdrożenia jako listę kroków z zależnościami.

KROK 6 — makiety
Przygotuj makiety wszystkiego, co zobaczy człowiek:
- PDF umowy leasingowej (§1-§16 + Załącznik nr 1) — pełny skład, realne dane przykładowego
  zamówienia, nasza szata graficzna. UWAGA: treści prawnej NIE zmieniamy ani o przecinek,
  odtwarzamy dokument Ruslana 1:1, zmieniamy wyłącznie skład i szatę (zasada Janka z 27.07).
  Uwagi merytoryczne zbierasz osobno, nie wprowadzasz.
- PDF aneksu VIN,
- widok karty zamówienia ze znacznikiem „Aneks VIN" obok statusu,
- ekrany kreatora tam, gdzie dochodzi wybór wzorca umowy,
- mail do klienta z aneksem.
Makiety wystaw na auratest jako klikalne linki. Mają być na tyle konkretne, żebym mógł
je zaakceptować bez dopytywania.

KROK 7 — scenariusze testów
Rozpisz scenariusze testowe pokrywające oba wzorce umowy, aneks VIN, zamówienie bez VIN,
zamówienie z VIN, przejścia statusów, wysyłkę do klienta i przypadki brzegowe. Dla każdego:
warunki wejściowe, kroki, oczekiwany wynik, jak zweryfikować. To będzie lista kontrolna
dla wątku wykonawczego.

NA KONIEC zapisz wszystko w docs/ (rewizja planu + scenariusze), zapisz pamięć projektu
i przygotuj prompt startowy dla wątku wykonawczego, uzupełniony o to, czego się dowiedziałeś.

Czego NIE robisz w tym wątku: nie piszesz kodu produkcyjnego, nie dotykasz
class-asiaauto-contract.php ani class-asiaauto-order.php (strefy kruche), nie zmieniasz
niczego na produkcji, nie wdrażasz makiet. Pytania kontrolne zadawaj pojedynczo.
```

---

## WĄTEK 2 — WYKONANIE (do skopiowania po akcepcie makiet)

```
Wdrażamy umowę leasingową — T-217 (drugi wzorzec w generatorze PDF), T-220 (aneks VIN),
T-221 (domknięcie pakietu prawnego). Makiety są zaakceptowane, plan zrewidowany w wątku
analitycznym. To jest wątek wykonawczy: masz zielone światło na CAŁOŚĆ i pracujesz bez
zatrzymywania się po każdym kroku.

Zacznij od przeczytania: docs/sesje/<plik z rewizją planu i scenariuszami z wątku 1>,
docs/roadmapa/T-217-umowa-leasingowa-szablon.md, T-220-aneks-vin.md, T-221-pakiet-prawny-payu.md
oraz pamięci projektu. Potem wykonaj plan w całości.

Zasady pracy w tym wątku:
- NIE pytasz o zgodę na kolejne kroki objęte planem — masz ją. Zatrzymujesz się WYŁĄCZNIE
  wtedy, gdy: (a) rzeczywistość rozjeżdża się z planem na tyle, że trzeba zmienić decyzję,
  (b) coś wymaga wejścia poza uzgodniony zakres, (c) trafiasz na ryzyko utraty danych.
  W takim wypadku zatrzymaj się, opisz problem i zaproponuj wyjście.
- Treść prawna umowy 1:1 z dokumentem Ruslana. Zmieniamy skład i szatę, nie słowa.
- Dokładasz OBOK, nie przebudowujesz. class-asiaauto-contract.php i class-asiaauto-order.php
  to strefy kruche: nowe metody obok istniejących, TRANSITIONS nietknięte, znacznik
  „Aneks VIN" obok statusu, a nie jako nowy status.
- Backup przed każdą znaczącą zmianą (mysqldump przy zmianach w bazie, .bak z datą przy
  nadpisywaniu plików na serwerze). php -l przed każdym wgraniem.
- Po każdym wdrożonym elemencie odpal odpowiedni scenariusz testowy ze scenariuszy z wątku 1
  i pokaż wynik. Nie deklaruj „działa" bez uruchomienia testu.
- Wersję pluginu bumpujesz świadomie, aktualizujesz docs/VERSIONS.md.
- Na koniec: zapis pamięci, dokument sesji w docs/sesje/, commit i push.

Raportuj postęp zwięźle w trakcie, pełne podsumowanie na końcu: co wdrożone, jakie testy
przeszły, co zostało otwarte, czego nie zrobiłeś i dlaczego.
```

---

## Dlaczego taki podział

Wątek 1 jest kosztowny poznawczo (czytanie kodu, Chrome MCP, konfrontacja backlogu z produkcją)
i musi kończyć się Twoją decyzją — makiety bez akceptu to zgadywanie. Wątek 2 jest kosztowny
wykonawczo i traci na przerwach: generator PDF, aneks i pakiet prawny mają wspólne punkty
styku, więc rozbicie ich na kilka sesji z pytaniami po drodze oznacza wielokrotne wczytywanie
tego samego kontekstu.

Granicą między wątkami jest **akcept makiet** — jedyny moment, w którym Twoja decyzja realnie
zmienia kierunek. Wszystko przed nią to zbieranie faktów, wszystko po niej to wykonanie.
