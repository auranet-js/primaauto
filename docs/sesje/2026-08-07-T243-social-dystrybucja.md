# Sesja 2026-08-07 — T-243: dystrybucja treści na kanały społecznościowe

> **PROMPT KONTYNUACYJNY** — w nowym wątku powiedz „przeczytaj `docs/sesje/2026-08-07-T243-social-dystrybucja.md`".
> Memory projektu: `reference_stara_strona_fb_zywy_kanal.md` (START dla starej Strony FB),
> `feedback_przeszukaj_historie_zanim_ogloszisz_odkrycie.md`.
> Spec: `docs/roadmapa/T-243-social-dystrybucja-tresci.md` · Instrukcja dla klienta:
> `docs/roadmapa/T-243a-instrukcja-dostepy-dla-ruslana.md` (**wysłana Ruslanowi 07.08**).

---

## 1. Co powstało

| Artefakt | Co to |
|---|---|
| `docs/roadmapa/T-243-social-dystrybucja-tresci.md` | Spec parasola: 8 decyzji, architektura 6 modułów, 7 faz, 87–116 h realnie / 220–292 h rynkowo, pierwsza fala 57–75 h |
| `docs/roadmapa/T-243a-instrukcja-dostepy-dla-ruslana.md` | Instrukcja wykonawcza dla klienta, wzorzec T-205. **Wysłana 07.08** |
| `docs/QUEUE.md` | T-243 wpięty nad T-241 (który staje się fazą c) |
| `docs/kosztorys/dane/postep.json` + `postep.html` | Pozycja na dole listy planowanych, opisana dla Ruslana. Deploy: `python3 build_postep.py --deploy` |
| HTML instrukcji | `https://auratest.pl/fe4f58fec53ctmp/primaauto-instrukcja-dostepy-social-2026-08-07.html` (generowany z MD przez `python3 -c` + `markdown`, szablon w tym pliku) |

## 2. Decyzje (Janek, 07.08) — nie otwierać ponownie bez nowej przesłanki

- **D1 — Pipeline modułów, NIE agent.** Model wołany w jednym kroku (copy), reszta deterministyczna.
  Uzasadnienie: publikacja nieodwracalna i zewnętrzna, ograniczenia to bramki a nie instrukcja,
  5 z 6 kroków to mechanika, konto na limicie LVE. Wzorzec dowieziony w `scripts/kb/`.
- **D2 — Wybór auta należy do Ruslana**, nie do automatu (82–226 nowych ofert/dobę).
- **D3 — Punkt wejścia w panelu WP przy ofercie** (przycisk „do kolejki").
- **D4 — Planowanie natywnie na platformach, rejestr u nas.** Janek nie ma dostępu do kanałów.
- **D5 — Dashboard statyczny**, wzorcem `docs/kosztorys/build.py --deploy`.
- **D6 — Fact-check liczbowy od razu**, nie w wersji 2.
- **D7 — Zdjęcia z sesji przed zdjęciami z importu** (te wymagają maskowania tablic).
- **D8 — Kolejka nigdy nie stoi pusta** — rotacja w okresach suszy.

**Cel wg Janka, dosłownie:** „rozprowadzenie materiałów na wszystkie możliwe kanały — bo wtedy
można promować postem; jak już film jest na YT to można go promować". **Produktem jest
dystrybucja, nie treść.**

## 3. Stan wątku Meta/FB — domknięty, nie otwierać

Pełny zapis: memory `reference_stara_strona_fb_zywy_kanal.md`. Skrót:

- **Stara Strona `prima1auto` = `106659808679582`, 16 676 obserwujących** — w przejętym portfolio
  `145818221430407`. **Dowozi 1 310 sesji / 30 zdarzeń kluczowych na 90 dni**, czyli tyle samo
  co płatny remarketing (`[RMKT] Dynamic Remarketing — Model-huby`, 2 302 / 32).
- **Odzyskanie ZAMKNIĘTE:** pełną kontrolę nad Stroną mają nasze 2 System Usery (nie osoby,
  sprawdzone 12.06), ich tokeny martwe (test 07.08: `API access blocked`), re-mint wymaga admina
  starego BM = konto Janka trwale usunięte 3.06.
- **NIE składać prośby o dostęp ani sporu o własność** — powiadomienie idzie do napastnika.
- **Dowód 07.08 (Biblioteka Reklam, publiczna, działa bez logowania w Chrome):** z tej Strony
  12 reklam w PL, wszystkie nieaktywne, ostatnia zakończona **9.04.2026**; w Rumunii (cel
  napastnika, kraj UE objęty DSA) **zero reklam, nigdy**. Napastnik nigdy nie reklamował się
  z tej Strony — chodziło mu o konto i kartę.
- **Andrzej ma Managera i publikuje** — jego dostęp płynie z członkostwa w starym BM, więc
  opiera się na tym, co padło. Nie mamy wglądu.
- **Stopka `class-asiaauto-contact.php:133` ZOSTAJE** na starej Stronie, dopóki Andrzej publikuje.
- **Instagram:** `prima_auto.pl` przysłał **4 sesje / 90 dni** — budujemy kanał od zera, nie
  odzyskujemy. Obce `@gillesmeralda988507` przy starej Stronie: nie zgłaszać, nie odłączać,
  tylko pilnować Centrum kont prywatnych profili.

## 4. Czego czekamy od Ruslana (instrukcja wysłana 07.08)

| | Pytanie | Co odblokowuje |
|---|---|---|
| 0 | Czy widzi jeszcze stare portfolio `145818221430407`? | domknięcie formalności, nic od tego nie zależy |
| 0a | **Komu zlecił odzyskanie konta, kiedy, na jakim etapie** | żeby nie działać w poprzek + ocena ryzyka (firmy „odzyskujące konta FB" zwykle proszą o dostęp admina) |
| 1 | Czy `prima_auto.pl` jest jego i ma hasło | jeśli nie — zakładamy nowe |
| 2 | Centrum kont prywatnych profili — czy nic obcego | realne ryzyko po ataku |
| 3 | **Kto prowadzi TikToka `@primaauto.pl`** (127 filmów, 319 obs.) | wisi od 14.07; zanim wejdziemy w cudzy kanał |
| 4 | Na jakim koncie Google jest kanał YouTube | migracja na Brand Account |
| 5 | Zrzut zakładki „Informacje" w Bibliotece Reklam | kraje osób zarządzających starą Stroną |

**Faza T-243a jest blokująca dla części fazy e.** Fazy b, c, d można prowadzić równolegle.

## 5. Otwarte decyzje Janka — nierozstrzygnięte na koniec sesji

> **Rejestr z opcjami i rekomendacjami: `docs/decyzje/2026-08-07-T243-otwarte-punkty-decyzyjne.md`**
> (D-1…D-4, każdy z pustym polem „Decyzja" do uzupełnienia). Poniżej skrót.
> Wszystkie cztery są **niezależne od odpowiedzi Ruslana** — można je rozstrzygnąć wcześniej.

1. **Czujnik ruchu z Facebooka** — cron tygodniowy na Organic Social w GA4, alarm gdy się
   załamie. 1–2 h wzorcem `run-job.sh`. Uzasadnienie: nikt tej Strony nie pilnuje, jedyne
   co możemy, to zauważyć w porę, gdyby przestała działać. **Dorzucić do T-243 jako osobną
   pozycję czy nie?**
2. **Kolejność faz** — czy generator gotowców dla Andrzeja (część fazy d) idzie **pierwszy**,
   przed kolejką i dystrybutorem. Argument za: nie potrzebuje ani kolejki, ani dystrybutora,
   daje efekt w tydzień na kanale, który już dowozi kontakty, zamiast po miesiącu na kanale
   z 26 fanami.
3. **Koszt kontaktu: remarketing vs Facebook** — jedno zapytanie do Google Ads o faktyczny
   wydatek RMKT za 90 dni, żeby mieć twardą liczbę zamiast „kosztuje kontra nie kosztuje".
4. **Rejestracja w API Biblioteki Reklam** (`facebook.com/ads/library/api`) — jednorazowa
   weryfikacja tożsamości, potem odpytywanie reklam dowolnej strony programowo, także
   konkurencji. Wymaga konta FB → po stronie Ruslana. Drobiazg, ale wart pamięci.

## 6. Korekty z tej sesji — czego nie powtarzać

Cztery rzeczy, które Janek prostował w trakcie. Wszystkie zapisane w memory, tu dla porządku:

1. **Ogłosiłem „odkrycie", że kontrolujemy nie tę stronę FB** — było udokumentowane w trzech
   miejscach, razem z decyzją z 13.06, że przepięcie jest niemożliwe. → `feedback_przeszukaj_historie_zanim_ogloszisz_odkrycie`.
2. **„Sprawa jest w kancelarii"** — nieaktualne. Ruslan komuś zlecił, szczegółów nie znamy.
   To samo zdanie stoi w `T-205-instrukcja-dla-ruslana.md:17` (wysłanej 29.07) — świadomie
   nietknięte, żeby nie zacierać, co klient dostał.
3. **„Napastnik może odebrać Andrzejowi dostęp w każdej chwili"** — nieudowadnialne, wycofane.
   Biblioteka Reklam pokazała, że napastnik Strony nie tknął.
4. **Zaproponowałem Jankowi zalogowanie się na Facebooka** — godzinę po tym, jak sam napisałem,
   że konto zostało skasowane 3.06. **Janek nie ma konta na Facebooku.**
5. **„Reklamy graficzne"** jako etykieta kanału Display w GA4 — to wyłącznie kampania
   `[RMKT] Dynamic Remarketing — Model-huby`. Nie tłumaczyć „Display" na „reklamy graficzne".

Zasada wspólna dla 1, 3 i 4: **nie podawaj inferencji jako faktu i nie sprzedawaj hipotezy
jako tropu.** Heurystyka Janka, którą sformułował dwa razy tego dnia: *gdyby dostęp wrócił,
człowiek już by o tym wiedział.*

## 7. Prompty wykonawcze podtasków

**`docs/sesje/2026-08-07-T243-PROMPT-WYKONAWCZY.md`** — gotowe prompty per podtask plus drzewo
gałęzi zależne od tego, co odpisze Ruslan (A: Meta odblokowana · B: YouTube · C: TikTok ·
D: stare portfolio wróciło · E: cisza). Kluczowa obserwacja: **pięć z ośmiu faz nie czeka
na nikogo — bez ani jednej odpowiedzi da się zbudować 62–83 h roboty.**

## 8. Prompt do nowego wątku (skopiuj)

```
Kontynuujemy T-243 — dystrybucja treści na kanały społecznościowe dla primaauto. Przeczytaj:
- docs/sesje/2026-08-07-T243-social-dystrybucja.md (ten plik — stan i decyzje)
- docs/roadmapa/T-243-social-dystrybucja-tresci.md (spec: architektura, fazy, godziny)
- memory: reference_stara_strona_fb_zywy_kanal.md (wątek starej Strony FB — ZAMKNIĘTY)

- docs/sesje/2026-08-07-T243-PROMPT-WYKONAWCZY.md (gotowe prompty podtasków + drzewo gałęzi)

Instrukcja dostępów poszła do Ruslana 07.08. Zapytaj mnie, co odpisał, i dobierz gałąź
z pakietu wykonawczego. Jeśli nie odpisał — NIE czekamy: pięć z ośmiu faz jest niezależnych,
ruszamy ścieżką domyślną (h → d → c → b).

Na koniec poprzedniej sesji zostały cztery moje decyzje nierozstrzygnięte (sekcja 5):
czujnik ruchu z FB, kolejność faz, koszt kontaktu RMKT vs Facebook, API Biblioteki Reklam.
Zapytaj o nie pojedynczo, quizem, nie hurtem.
```
