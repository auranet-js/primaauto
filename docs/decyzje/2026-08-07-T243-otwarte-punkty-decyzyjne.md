# T-243 — otwarte punkty decyzyjne

> Status: **OTWARTE — czekamy** (zapisane 2026-08-07) · Rozstrzyga: Janek
> Kontekst: `docs/sesje/2026-08-07-T243-social-dystrybucja.md` · Spec: `docs/roadmapa/T-243-social-dystrybucja-tresci.md`
>
> Cztery punkty zostawione świadomie nierozstrzygnięte na koniec sesji 07.08. Każdy ma
> uzupełnione dane, opcje i rekomendację — brakuje wyłącznie decyzji. **Gdy zapadnie,
> dopisz ją w polu „Decyzja" wraz z datą; ten plik staje się wtedy zwykłym ADR-em.**
>
> Wszystkie cztery są **niezależne od odpowiedzi Ruslana** na instrukcję dostępów — można je
> rozstrzygnąć w dowolnym momencie, także zanim odpisze.
>
> **Aktualizacja 07.08:** D-1 i D-3 dostały swoją pozycję godzinową w kolejce jako
> **faza T-243h — czujnik kanału FB + baseline kosztu, 2–3 h realnie / 5–8 h rynkowo**.
> Suma T-243 po dodaniu: **89–119 h realnie / 225–300 h rynkowo**. Decyzja dotyczy teraz
> tego, **czy i kiedy fazę h wykonać**, a nie czy ją wycenić.

---

## D-1. Czujnik ruchu z Facebooka

**Co jest do rozstrzygnięcia:** czy dokładamy do T-243 osobną pozycję na cron monitorujący
ruch z Facebooka.

**Dane.** Stara Strona `prima1auto` dowozi **1 310 sesji / 30 zdarzeń kluczowych na 90 dni** —
tyle samo, co płatna kampania remarketingowa `[RMKT] Dynamic Remarketing — Model-huby`
(2 302 / 32). Ruch bierze się z codziennych postów Andrzeja. Jego dostęp opiera się na
członkostwie w **przejętym portfolio** `145818221430407`, do którego nie mamy żadnego wglądu —
potwierdzone testem tokenów 07.08. Nie dowiemy się z wyprzedzeniem, gdyby zniknął; bez czujnika
dowiedzielibyśmy się przy kolejnym przeglądzie kwartalnym.

**Opcje.**
- **A.** Osobna pozycja w T-243: cron tygodniowy czytający Organic Social w GA4, alarm mailem
  przez `send-to-jan`, gdy ruch spadnie poniżej progu. Wzorzec: `run-job.sh` + istniejące
  watchdogi. **1–2 h.**
- **B.** Nie budujemy nic, sprawdzamy ręcznie przy okazji innych prac.
- **C.** Wpinamy to w przyszły dashboard T-207 zamiast robić osobno.

**Rekomendacja: A.** Najtańsza polisa w całym T-243 — 1–2 h za wiedzę, że drugi
najskuteczniejszy kanał przestał działać, w ciągu tygodnia zamiast kwartału. Wariant C
uzależnia to od terminu T-207, który nie ma daty.

**Decyzja:** _(do uzupełnienia)_

---

## D-2. Kolejność faz — czy gotowce dla Andrzeja idą pierwsze

**Co jest do rozstrzygnięcia:** czy generator treści (część fazy d) wychodzi przed kolejką
i dystrybutorem, czy trzymamy kolejność a → b → c → d → e.

**Dane.** Gotowce dla Andrzeja — komplet: tytuł z ceną, opis, hashtagi, zdjęcia, link do oferty,
do skopiowania i wklejenia ręcznie — **nie potrzebują ani kolejki (faza b), ani dystrybutora
(faza e)**. Potrzebują wyłącznie generatora copy i miejsca, w którym się je wykłada. Działałyby
na kanale, który **już dziś dowozi 30 zdarzeń kluczowych na kwartał**, podczas gdy nowa Strona
ma 26 obserwujących, a Instagram 4 sesje na 90 dni.

**Opcje.**
- **A.** Wyodrębnić „generator gotowców" jako fazę **przed** b: kilkanaście godzin do pierwszego
  efektu, na żywym kanale. Reszta T-243 bez zmian.
- **B.** Zostawić kolejność a → b → c → d → e. Gotowce wychodzą przy okazji fazy d, po miesiącu.

**Rekomendacja: A.** Efekt w tydzień zamiast w miesiąc, i to tam, gdzie ruch faktycznie jest.
Ryzyko: generator zbudowany bez kolejki może wymagać drobnego przerobienia, gdy kolejka
powstanie — ale to kwestia interfejsu wywołania, nie logiki.

**Decyzja:** _(do uzupełnienia)_

---

## D-3. Koszt kontaktu — remarketing kontra Facebook

**Co jest do rozstrzygnięcia:** czy wyciągamy z Google Ads faktyczny wydatek kampanii RMKT
za 90 dni, żeby porównanie miało twardą liczbę.

**Dane.** Dziś porównanie brzmi „tyle samo kontaktów, tylko remarketing kosztuje, a Facebook
nie". Budżet RMKT z podziału 152 zł/dz wynosił **17 zł dziennie**, ale budżet to nie wydatek
i nie będę go podawał jako koszt. Jedno zapytanie przez `scripts/gads_client.py` daje realną
kwotę i pozwala policzyć koszt zdarzenia kluczowego w obu kanałach.

**Opcje.**
- **A.** Wyciągnąć teraz — kilkanaście minut, liczba wchodzi do specu i do rozmowy z Ruslanem.
- **B.** Zostawić do momentu, gdy będzie potrzebna w raporcie.

**Rekomendacja: A**, ale bez pośpiechu. To argument, który zmienia rozmowę o priorytetach
z jakościowej na liczbową, a koszt zdobycia jest znikomy.

**Decyzja:** _(do uzupełnienia)_

---

## D-4. Rejestracja w API Biblioteki Reklam Meta

**Co jest do rozstrzygnięcia:** czy występujemy o dostęp do `facebook.com/ads/library/api`.

**Dane.** 07.08 zapytanie do `/ads_archive` wróciło z *„Wymagana autoryzacja i logowanie"* —
API wymaga **jednorazowej rejestracji z weryfikacją tożsamości**. Interfejs webowy Biblioteki
działa bez logowania (tak zdobyliśmy dowód o reklamach starej Strony), ale ręcznie i bez
możliwości odpytywania seryjnego. Z dostępem do API moglibyśmy programowo sprawdzać reklamy
**dowolnej strony, w tym konkurencji** — west-motors, azjaauto, autopunktmlawa.

**Ograniczenie:** weryfikacja wymaga konta na Facebooku. **Janek konta nie ma** — zostało
trwale usunięte przy ataku 3 czerwca. Czyli rejestracja jest po stronie Ruslana albo Andrzeja,
co znaczy, że dostęp byłby przypięty do ich tożsamości.

**Opcje.**
- **A.** Poprosić Ruslana o rejestrację — zysk: stały wgląd w reklamy konkurencji.
- **B.** Zostawić; interfejs webowy wystarcza do doraźnych sprawdzeń.
- **C.** Odłożyć do momentu, gdy pojawi się konkretna potrzeba analizy konkurencji.

**Rekomendacja: C.** Dziś nie ma zadania, które by tego wymagało, a każda prośba do Ruslana
zużywa jego uwagę potrzebną na dostępy z T-243a. Wrócić, gdy ruszy temat analizy konkurencji.

**Decyzja:** _(do uzupełnienia)_
