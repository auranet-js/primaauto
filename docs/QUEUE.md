# Kolejka zadań — Prima Auto

> Aktualizacja: 2026-08-07 (**T-243 SPEC 07.08** — dystrybucja treści na kanały społecznościowe, parasol 7 pozycji, 87–116 h, pierwsza fala 57–75 h; instrukcja dostępów dla Ruslana gotowa; **T-241 wchłonięty** jako faza c. Wcześniej: **T-121 ODBLOKOWANY 06.08** — creds produkcyjne PayU, pakiet wykonawczy gotowy, 27–36 h; **T-220 DONE 06.08** — aneks VIN, v0.34.22; **T-242 DONE 06.08** — edycja VIN w zamówieniu, v0.34.21; **T-229 DONE 05.08** — mechanizm rankingów; **T-230 — 7 rankingów LIVE**; **T-238 DONE 05.08** — ceny i tytuły hubów; **T-239 DONE 06.08** — promocja Terenwizja na stronie głównej; **T-240 zaparkowany** — indywidualizacja hubów z własnymi materiałami; **T-241** — procedura „nowe materiały z sesji → hub + reklamy", pierwszy przebieg Leopard 5 na 3 h. Wcześniej: T-217 WDROŻONY 30.07 — v0.34.14; T-219 ZAMKNIĘTY 03.08)

---

## 🔴 WĄTEK AKTYWNY — umowy, depozyt, płatności (2026-07-27)

> Geneza: analiza umowy leasingowej #072426-1 (Agw Moto, 24.07) + wzorca aneksu VIN z Google Drive, konfrontacja z regulaminami na produkcji. Decyzje zapadły 2026-07-27 w rozmowie z Jankiem — **te taski mają spec, nie są już pomysłami**.
>
> **Do Ruslana (czeka na odpowiedź):** `docs/biznes/2026-07-27-punkty-do-weryfikacji-ruslan.md` — 7 punktów (numer konta, Załącznik nr 2, depozyt, zakres zwrotu, brakujący regulamin usługi, model 30/70 na `/finansowanie/`, zakres aneksu).

**Kolejność wykonania:**

```
T-221 (regulamin) ──→ wniosek do PayU ✅ ──→ T-121 (płatność online) ← gotowy do budowy
T-217 (wzorzec leasingowy) ──┬──→ T-220 (aneks VIN)
                             └──→ T-113 (ścieżka finansowania w kreatorze)
```

| Task | Stan | Blokuje / zależy | Godziny |
|---|---|---|---|
| **T-221** — pakiet prawny pod PayU | 🟡 wniosek do PayU ZAŁATWIONY 06.08 | **nie blokuje już T-121**; zostają 3 zadania porządkowe | 2–3 h |
| **T-217** — drugi wzorzec umowy (leasing) | ✅ **DONE 2026-07-30** (v0.34.14 LIVE) | odblokował T-220 i T-219 | — |
| **T-219** — landing „Leasing samochodu z Chin" + blok na hubach | ✅ **ZAMKNIĘTY 03.08** — landing LIVE (treść po korekcie Ruslana), blok na 317 hubach (theme 1.2.0), claim na **2174 ofertach** podlinkowany (plugin 0.34.16), stopka, `/finansowanie/`, llms, sitemapa, indexing | — | ~7 h |
| **T-224** — formularz „Zapytaj o leasing" | 🔵 plan wykonawczy gotowy | wydzielony z T-219 C; pod niego wejdzie T-189 | 5–6 h |
| **T-223** — rozjazd depozytu: strona vs §4 umowy | ⏸️ czeka na Ruslana (punkt 8 w liście weryfikacyjnej) | wynik decyduje, czy ruszamy §4 wzorca (strefa krucha) | 1–4 h |
| **T-242** — edycja VIN w zamówieniu (umowa leasingowa) | ✅ **DONE 2026-08-06** (v0.34.21 LIVE) | raportujemy razem z T-220 | ~1 h |
| **T-220** — aneks VIN | ✅ **DONE 2026-08-06** (v0.34.22 LIVE) — generator + guard + znacznik + mail z załącznikiem; D6: umowa pośrednictwa bez VIN-u zapowiada aneks | raportujemy razem z T-242 | ~5 h |
| **T-121** — płatność PayU za depozyt | 🔵 **ODBLOKOWANY 06.08** — pakiet wykonawczy gotowy | creds produkcyjne są; gate zdjęty | 27–36 h |
| **T-113** — ścieżka finansowania w kreatorze | ⏸️ gate częściowo zdjęty | po T-217 | 30–40 h |

- [ ] **T-221 — Pakiet prawny pod PayU: regulamin usługi + strona `/depozyt/`** 🔵 GOTOWE DO STARTU, **blokuje T-121** — pomiar 2026-07-27: prawnik dał dwa regulaminy i **oba dotyczą wyłącznie strony WWW** (`/regulamin/` żyje i mówi wprost, że usługi są *nieodpłatne*; `Regulamin strony internetowej` nigdy nie wdrożony). **Brak dokumentu regulującego usługę** — depozyt, zwrot, odstąpienie konsumenta, reklamacja usługi. Decyzje: nie rozszerzamy istniejących, piszemy **trzeci dokument w konwencji prawnika**; treść przygotowujemy my, Ruslan daje swojemu prawnikowi tylko do weryfikacji; struktura = jeden `/regulamin-uslugi/` (zwroty jako rozdział z kotwicą `#zwrot`) + osobna sprzedażowa `/depozyt/` z narracją „co to znaczy dla Ciebie / dla nas" + PayU dopisany do polityki prywatności. **Zakres regulaminu depozytu = sytuacja, gdy nie dojdzie do rezerwacji auta** (= §4 ust. 4–5 umowy: 3 dni bez kosztów / 7 dni po potrąceniu) — czyli moment płatności online; dalsze etapy reguluje umowa i ich nie dublujemy. Umowa leasingowa nietknięta (kształt wynika z wymogów leasingodawcy). Spec: `docs/roadmapa/T-221-pakiet-prawny-payu.md`. **AKTUALIZACJA 06.08:** wniosek do PayU załatwiony (Janek ma dane produkcyjne) — T-221 **przestaje blokować T-121**. Zostają 3 zadania porządkowe, 2–3 h: PayU w polityce prywatności (wymóg RODO od chwili pobierania płatności), zdjęcie `noindex` + podlinkowanie `/regulamin-uslugi/` i `/depozyt/` z kreatora, sprzeczność starego `/regulamin/` (§4 ust. 3 „usługi nieodpłatne"). Korekta `/finansowanie/` nadal czeka na Ruslana.
- [ ] **T-219 — Landing „Leasing samochodu z Chin" + pokrycie leasingu na hubach** 🟡 **CZĘŚĆ A + D WDROŻONE 31.07** — strona `/leasing-samochodu-z-chin/` (ID 398850) LIVE po przeglądzie Ruslana, schema `FAQPage` + `Service`, w sitemapie (wymagało `wp rankmath sitemap generate` — cache jak w T-192), link ze stopki i z `/finansowanie/`, wpis w `llms.txt` / `llms-full.txt` (w generatorach, nie ręcznie), 1 URL do Indexing API. **Zostaje B** (blok na 317 hubach, nagłówek `{model} leasing` — szyk potwierdzony GSC 54:4, priorytet Zeekr 9X / Geely Monjaro) **i C** (formularz leadowy — dziś CTA idzie do `mailto:`, czyli landing bez mierzalnej konwersji). Rozjazd depozytu wydzielony do **T-223**. Stan wykonania: §10 specu. *(poniżej pierwotny opis)* — nowa strona `/leasing-samochodu-z-chin/` (osobna, `/finansowanie/` **zostaje** i dostanie link po publikacji) + nowe H2 „Leasing {model}" na **317 hubach serie** (marki pomijamy) + **formularz leadowy budowany od zera** (w serwisie nie ma dziś żadnego komponentu formularza — `/kontakt/` to `mailto:`, jedyny formularz to kreator `/zamow/`). **Uzasadnienie nie jest wolumenowe** — fraza nie ma dziś wolumenu i to nie jest kontrargument (D5): cel to pokrycie encji dla LLM-ów („czy Zeekr 9X da się wziąć w leasing"), zdjęcie bariery cenowej na hubie i zajęcie niszy zanim popyt przyjdzie. **Źródło treści = wzorzec umowy z T-217** (§1–§16, egz. #072426-1) — każde zdanie o pieniądzach i terminach mapowane na paragraf, żeby nie powtórzyć rozjazdu 30/70 z `/finansowanie/`. ⚠️ **Leasingodawcę proponuje Prima-Auto**, nie klient — Ruslan wskazuje firmę, z którą pracuje; narracja strony to „załatwiamy finansowanie", a nie „przyjdź ze swoim leasingiem". Jedyne realne ryzyko odmowy = zdolność leasingowa klienta. Do potwierdzenia u Ruslana: z kim konkretnie pracuje i czy wolno nazwać go na stronie (na start bez nazwy). Dotyka `taxonomy-serie.php` = **strefa ZAWSZE PYTAJ**. Kosztorys Ruslana ma tu 3–4 h (wycena samej strony, sprzed bloku na 317 hubach i formularza) → do aktualizacji. Spec: `docs/roadmapa/T-219-landing-leasing.md`.
- [x] **T-217 — Drugi wzorzec umowy (leasing) w generatorze PDF** ✅ **DONE 2026-07-30, v0.34.14 LIVE** (42/42 testy wariantu + 22/22 E2E, regresja pośrednictwa zero; log: `docs/sesje/2026-07-30-T-217-wdrozenie-umowa-leasingowa.md`) — „Umowa organizacji importu samochodu" §1–§16 + Załącznik nr 1, obok istniejącego szablonu pośrednictwa. **Treści prawnej NIE zmieniamy** — odtwarzamy 1:1, dostosowujemy wyłącznie skład i szatę. ⚠️ **Dwa różne depozyty, nie mylić:** *zabezpieczający* (w umowie, % wartości auta — w Agw Moto 10% = 23 200 zł) zostaje bez zmian i **nie** czyta kwoty z configu; *zwrotny* (panel, stała kwota, PayU — T-121) jest wpłacany wcześniej i przy realizacji umowy zostaje od zabezpieczającego **odliczony**. Załącznik nr 2 **pomijamy** (cała kwota wynika z umowy). Opłata wstępna leasingu = osobne pole. Typ umowy wybiera Ruslan w panelu, front bez zmian. **Rachunek:** osobne pole w configu `bank_account_number_leasing` = `72 2490 0005 0000 4530 0075 1603` (z podpisanej umowy); istniejące `bank_account_number` (`17 2490 1057…`) nietknięte — w obiegu były trzy różne numery. Spec: `docs/roadmapa/T-217-umowa-leasingowa-szablon.md`, analiza: `docs/biznes/2026-07-17-umowa-organizacji-importu-leasing-analiza.md`.
- [x] **T-242 — Edycja VIN w zamówieniu skutkuje w umowie leasingowej** ✅ **DONE 2026-08-06, v0.34.21 LIVE** (15/15 asercji, regresja pośrednictwa zero) — **raportujemy Ruslanowi razem z T-220**, bo to ta sama sprawa widziana z dwóch stron: tu chodzi o *usunięcie* błędnego VIN-u przed podpisaniem, tam o *dopisanie* prawdziwego po weryfikacji auta. Zgłoszenie z rozmowy 06.08: Ruslan wyczyścił VIN w zamówieniu, a numer dalej drukował się w umowie i wracał przy każdej regeneracji — bo `getVehicleData()` liczyło `vin_verified` z fallbackiem na `vin_number` ogłoszenia, więc puste pole znaczyło „weź z oferty", a nie „brak numeru". Działało wyłącznie skasowanie VIN-u na ogłoszeniu, co psuje dane oferty pracującej w reklamach i tak czy tak wraca przy najbliższym sync z che168 (`class-asiaauto-importer.php:460-462`); obejściem awaryjnym było kasowanie zamówienia, co wysyła klientowi maile o nieistniejącym zamówieniu. **Umowa leasingowa czyta teraz VIN wyłącznie z pola karty zamówienia** — puste = klauzula wzorca o aneksie. **Pośrednictwo nietknięte** (klucz `vin` ze starym fallbackiem, 150 zamówień renderuje się identycznie; zamaskowane VIN-y z che168 w pośrednictwie = otwarta decyzja D4 z T-217). W karcie zamówienia pole przestało wypełniać się samo numerem oferty (dowolny zapis karty po cichu go utrwalał), a pod nim stanął stały pasek **„VIN OFERTY: … [Wstaw]"** z notą, że numer jest niezweryfikowany. Test wielokrotnego użytku: `tmp/test-vin-guard-2026-08-06.php` (read-only, Reflection). Log: `docs/sesje/2026-08-06-vin-edycja-w-zamowieniu.md`.
- [ ] **T-220 — Aneks VIN do umowy (generator + status + wysyłka)** 🔵 GOTOWE DO BUDOWY — umowa bywa podpisywana bez VIN-u (w leasingowej literalnie: „zostanie dodany aneksem po weryfikacji auta"). Osobny przycisk „Generuj aneks VIN" przy umowach (guard: VIN wypełniony), generowanie **nie zmienia statusu**; nowy status **„Aneks do umowy"** przełączany ręcznie przez Ruslana → powiadomienie klienta; aneks do pobrania tam, gdzie umowa (kreator krok 4). Zakres aneksu: **wyłącznie VIN** (ustalenie Ruslana). Dotyczy **obu** wzorców — inna jednostka redakcyjna (`§1 ust.1 pkt 1 lit. c` vs `§2 lit. e` + Załącznik nr 1). **Dotyka `TRANSITIONS` = strefa krucha**; rezerwacja listingu bezpieczna (status spoza `LISTING_RESERVATION_MAP` to no-op, `:661-664`) — do potwierdzenia testem. Spec: `docs/roadmapa/T-220-aneks-vin.md`.
- [ ] **T-121 — Płatność PayU za depozyt** 🔵 **ODBLOKOWANY 2026-08-06, pakiet wykonawczy gotowy** — 27–36 h (`docs/sesje/2026-08-06-T-121-PAKIET-WYKONAWCZY.md`, makiety: `tmp/t121-makiety-payu.html`). **Janek ma produkcyjne creds PayU** (`~/secrets/payu/prod-primaauto.env`, 06.08) — wniosek akceptanta załatwiony, „bloker zgody PayU" z opisu specu jest nieaktualny. Zakres ucięty decyzją Janka: **żadnych cronów** (przypomnienia ani reconkiliacja) — PayU wysyła Ruslanowi własny mail o każdej transakcji, więc brak naszego maila przy obecnym mailu z PayU sam jest sygnałem awarii webhooka. Doszedł **BLIK z kodem wpisywanym na naszej stronie** (`BLIK_AUTHORIZATION_CODE`, +3–4 h) — przy 79,6 % mobile najkrótsza ścieżka; redirect na bramkę zostaje dla kart i portfeli. Widget `payu-widget.js` odrzucony (schyłkowy). Otwarte, nie blokuje: zwrot depozytu przez API vs ręczny przelew; numer i status zamówienia przy kwocie (§8a pakietu). Klient wpłaca zwrotny depozyt online zamiast przelewu ręcznego. Decyzje 27.07: przycisk pojawia się już przy statusie **`potwierdzone` (krok 3 kreatora)** i zostaje do opłacenia; pod nim komunikat „Zgłoszenia z opłaconym depozytem zwrotnym realizujemy w pierwszej kolejności" + „Poczytaj więcej" → `/depozyt/`; kwota z configu, **zamrażana na zamówieniu** (`:452`), ta sama dla pośrednictwa i leasingu. PayU sandbox ograny (`~/secrets/payu/`, damianchen+digit). **Dotyka strefy kruchej `class-asiaauto-order.php`** — wyłącznie `markDepositPaid()`. Spec: `docs/roadmapa/T-121-platnosc-online-depozyt.md`.
- [ ] **T-113 — Ścieżka finansowania/leasingu w kreatorze** ⏸️ gate „brak partnera" **częściowo zdjęty** — partner znany (EFL, umowa o współpracy przeanalizowana: `docs/biznes/2026-07-17-umowa-efl-leasing-analiza.md`), wzorzec umowy jest (→ T-217). Zostaje do ustalenia: czy ścieżkę wybiera klient w kreatorze czy Ruslan w panelu (na start: panel), kto zgłasza leada do Systemu EFL, oraz nowe bramki statusów (decyzja leasingowa → umowa trójstronna → uruchomienie finansowania) = **strefa krucha**. Spec: `docs/roadmapa/T-113-finansowanie-leasing.md`.

---

## ⭐ ROADMAPA FUNKCJI — pomysły Janka na rozwój (produkt/UX)

> Pomysły Janka na rozwój platformy, zbierane od 2026-06-11. Wszystkie **bez terminu, najpierw spec/brainstorm przed buildem** (priorytet per-task niżej). **Source of truth = TEN plik** (`_ops` zwinięty 2026-06-16 → polityka per-projekt v0.14; numery T-NNN zachowane historycznie z dawnego `_ops/BACKLOG.md`). **To jest „roadmapa pomysłów na rozwój" — gdy pytam o roadmapę funkcji, pokazuj TĘ sekcję, nie listę zadań SEO/Ads niżej.**

- [ ] **T-243 — Dystrybucja treści na kanały społecznościowe (parasol, 8 pozycji)** 🔵 **SPEC GOTOWY 2026-08-07** — `docs/roadmapa/T-243-social-dystrybucja-tresci.md`. **89–119 h realnie / 225–300 h rynkowo; pierwsza fala 57–75 h; zużyte 4 h** (rozpoznanie + spec + instrukcja, 07.08 — w changelogu postępu). Produktem jest **dystrybucja, nie treść**: materiał ma natywnie istnieć na każdej platformie, bo dopiero wtedy jest czym promować (film na YT → kampania wideo, post na FB → podbicie, rolka → Spark Ads). Ruslan wskazuje auto w panelu przy ofercie → system przygotowuje media (ffmpeg/magick: kadry, WebP z nazwami pod frazy, miniatury z ceną), pisze copy per platforma (`claude -p`, abonament) i wypycha jako **zaplanowane** na FB/IG/YT/TikTok; Ruslan zarządza natywnie w Business Suite i Studio, my trzymamy wspólny rejestr, bo Janek nie ma dostępu do kanałów. **Mechanizm: pipeline modułów, NIE agent** (D1) — publikacja jest nieodwracalna i zewnętrzna, ograniczenia są bramkami a nie instrukcją, 5 z 6 kroków to czysta mechanika, konto siedzi na limicie LVE. Kod w `scripts/social/` wzorem `scripts/kb/`, ekran `class-asiaauto-admin-social.php` wzorem `-che168-import.php`, dashboard statycznym `build.py --deploy` wzorem kosztorysu. **Wchłania T-241** jako pierwszy przebieg fazy c. **Fact-check liczbowy wchodzi od razu** — post rozchodzi się szerzej niż hub i nie da się go skorygować po publikacji (T-208 nazywa ten brak największym ryzykiem pipeline'u contentowego).
  **Fazy:** a) dostępy i konta 6–9 h · b) kolejka i panel WP 14–18 h · c) materiały z sesji (watcher Drive + prep) 13–17 h · d) generator treści + 4 bramki 13–17 h · e) dystrybucja FB/IG/YT/TikTok 21–27 h · f) rejestr i wyniki 11–15 h · g) rotacja w suszy + crony + skille 9–13 h · **h) czujnik kanału FB + baseline kosztu 2–3 h** (cron tygodniowy na Organic Social w GA4 z alarmem `send-to-jan` + jednorazowy pomiar faktycznego wydatku RMKT w Ads; niezależny od reszty, im wcześniej tym dłuższy szereg pomiarowy).
  🚀 **PAKIET WYKONAWCZY: `docs/sesje/2026-08-07-T243-PROMPT-WYKONAWCZY.md`** — gotowe prompty per podtask + drzewo gałęzi zależne od odpowiedzi Ruslana (A: Meta odblokowana · B: YouTube · C: TikTok · D: stare portfolio wróciło · E: cisza). **Pięć z ośmiu faz nie czeka na nikogo — bez ani jednej odpowiedzi da się zbudować 62–83 h.** Start domyślny: h → d → c → b (kolejność d przed b zależy od decyzji D-2).
  📋 **Otwarte punkty decyzyjne D-1…D-4:** `docs/decyzje/2026-08-07-T243-otwarte-punkty-decyzyjne.md` — D-1 (czujnik) i D-3 (koszt RMKT) mają już swoją pozycję godzinową jako faza h; D-2 to kolejność faz (czy generator gotowców dla Andrzeja idzie przed b i e), D-4 rekomendowane do odłożenia. **Janek świadomie czeka — nie ponaglać.**
  🔴 **BLOKUJE WSZYSTKO: T-243a — dostępy.** Instrukcja dla Ruslana gotowa: `docs/roadmapa/T-243a-instrukcja-dostepy-dla-ruslana.md` (wzorzec T-205, ta forma zadziałała 29.07). ⚠️ **Kontekst Meta — bez powtarzania rozstrzygniętego.** Stara strona `prima1auto` = `106659808679582`, **16 676 obserwujących**, ~1 post ofertowy dziennie, siedzi w **przejętym** portfolio `145818221430407`; decyzja z 13.06 (`project_meta_account_hijack_2026_06_11`) to **Tor B — czysty start** na `1486783363131026`, bo strony nie da się przepiąć bez full control. Dzisiejsze reklamy, piksel i katalog stoją na nowej stronie `1146829831857839` (26 fanów). 🔥 **POMIAR 07.08 ODWRACA PRIORYTET: stara strona NIE jest martwa.** GA4/90 dni: Organic Social **1 310 sesji / 30 zdarzeń kluczowych** — *tyle samo co płatny remarketing* (`[RMKT] Dynamic Remarketing — Model-huby`, google/cpc, 17 zł/dz: 2 302 / 32), za darmo. ⚠️ Kanał GA4 „Display" = **wyłącznie ta kampania RMKT**, nie żadne „reklamy graficzne" — nie używaj tej etykiety. **Zero płatnego social w tym okresie** (sprawdzone), więc nic nie zawyża przypisania; Instagram: **4 sesje/90 dni**. Rozbicie: `lm.facebook.com` 479, `l.facebook.com` 440, `facebook.com` 244, `m.facebook.com` 138 — czyli praktycznie w całości stara strona i jej codzienne posty ofertowe (nowa ma 26 fanów). Notatka z maja „organiczny reach martwy, ~2 reakcje/post" dotyczyła **reakcji, nie kliknięć** — nie lajkują, ale wchodzą i kontaktują się. **Celem nie jest migracja ludzi na nową stronę (Facebook nie ma funkcji przeniesienia obserwujących), tylko żeby stara nie zamilkła.**
  🔒 **ODZYSKANIE STRONY ZAMKNIĘTE — nie otwierać ponownie** (łańcuch domknięty 07.08, szczegóły w [[reference_stara_strona_fb_zywy_kanal]]): pełną kontrolę nad Stroną mają **nasze 2 System Usery, nie osoby** (sprawdzone 12.06 z panelu Andrzeja — dlatego Ruslan nigdy nie widział, że mógłby coś na Stronie zrobić); SU żyją w przejętym portfolio; ich tokeny **martwe** (test 07.08: `primaauto-mktg-admin` i `primaauto-mktg` → `API access blocked`, `primaauto` → `/me` OK ale `business_management` odmawia); re-mint wymaga admina starego BM = konto Janka **trwale usunięte** 3.06. **NIE składać prośby o dostęp ani sporu o własność** — nie zadziała, a powiadomienie idzie do napastnika i grozi odebraniem Andrzejowi ostatniego dostępu. ⚠️ **Nie ma żadnej toczącej się ścieżki odzyskania** (korekta Janka 07.08): self-service wyczerpany 12.06, zgłoszenie w BSH zamknięte bez rozstrzygnięcia 7.07. Zapis „sprawa jest w kancelarii prawnej" z `T-205-instrukcja-dla-ruslana.md:17` (wysłana 29.07) jest **nieaktualny — nie powtarzać**. Stara Strona = **strata trwała**, planujemy bez niej.
  ❓ **Jedyny otwarty wątek:** 13.06 mieliśmy sprawdzić, czy auto-rescan Meta przywrócił Ruslanowi admina w starym BM (konto wyczyszczone 12.06, Account Quality „brak problemów") — **odpowiedzi nigdy nie zapisano**. Pytanie do Ruslana: czy widzi na `business.facebook.com` portfolio `145818221430407`. „Widzę" = wraca wszystko, „nie ma" = zamknięte.
  **Plan operacyjny:** (1) Andrzej **publikuje dalej na starej Stronie** — T-243 dowozi mu gotowce do wklejenia (ręcznie, bo API tam nie mamy); (2) **post migracyjny** przypięty, powtarzany co 2–3 tyg. — jedyny mechanizm dosięgający tych 16,7 tys., bo Facebook nie potrafi przenieść obserwujących; (3) **stopka `class-asiaauto-contact.php:133` ZOSTAJE** na starej Stronie dopóki Andrzej publikuje — przepinamy dopiero gdy zamilknie; (4) sprawdzić, czy przy Stronach nie wisi obce konto IG (napastnik podpiął `@gillesmeralda988507`). ✅ **Dowód 07.08 (Biblioteka Reklam Meta, publiczna, działa BEZ logowania w Chrome):** z Strony `106659808679582` wyszło **12 reklam, wszystkie nieaktywne, ostatnia zakończona 9.04.2026** (PL); w **Rumunii — kraju docelowym napastnika, objętym DSA — ZERO reklam, nigdy**. Czyli **napastnik nigdy nie reklamował się z tej Strony** — kampania z 3.06 poszła z konta reklamowego pod inną tożsamością; chodziło mu o konto i kartę, nie o publiczność. **Nie ma śladu, by Stronę tknął** — nie strasz „może odebrać w każdej chwili" (twierdzenie nieudowadnialne, wycofane 07.08). Zostaje realny fakt: dostęp Andrzeja opiera się na przejętym portfolio, **nie mamy nad nim wglądu**, więc post migracyjny wcześniej niż później + czujnik ruchu z FB. 📌 Zakładka „Informacje" w Bibliotece (kraje osób zarządzających Stroną) wymaga logowania — Janek nie ma konta FB (skasowane 3.06), zadanie dla Ruslana/Andrzeja.
  ⚠️ **Ryzyka:** (1) migracja kanału YT `UCsfcUP3uwrDxPI_mDIle8Pw` na Brand Account może się nie udać — plan B to publikacja przez Chrome jak 24.07; (2) TikTok Content Posting API wymaga audytu aplikacji (tygodnie, wynik nie zależy od nas) — plan B to szkice + kliknięcie Ruslana; (3) **autorstwo TikToka `@primaauto.pl` nierozstrzygnięte od 14.07** (127 filmów, 319 obs.) — rozmowa z Ruslanem PRZED fazą a; (4) regeneracja tokenu System Usera Meta unieważnia stary — kopia i weryfikacja `build-meta-vehicle-feed.php` oraz skryptów Ads po zmianie scope; (5) `videos.insert` kosztuje 1600 z dobowej puli 10 000 = **maks. 6 wgrań/dobę** (zgodne z regułą „po jednej pozycji dziennie, nie paczką").
  ⚠️ Miarą powodzenia **nie może być zasięg organiczny** (FB 26 fanów, TikTok 319 obs.) — właściwa miara to koszt kontaktu w kampaniach promujących te materiały vs kampanie bez nich.
- [ ] **T-241 — Nowe materiały z sesji → hub + konta reklamowe (procedura powtarzalna)** 🔵 **PIERWSZY PRZEBIEG GOTOWY: Leopard 5, 3 h** — ⚠️ **wchłonięty przez T-243 fazę c** (2026-08-07): zostaje jako pierwszy przebieg na materiałach z 23.07, ale mechanizm powtarzalności buduje T-243 — wyzwalacz: **pojawienie się nowych materiałów w `PrimaAuto/sesje/` na Dysku**. Nie jest to zadanie jednorazowe: każda sesja u Ruslana daje zdjęcia i filmy konkretnych egzemplarzy, a wtedy chcemy za jednym zamachem (a) wzbogacić hub modelu, (b) odświeżyć kreacje w kontach reklamowych, (c) zaplanować huby pod modele objęte sesją. Wykonanie T-240 rozstrzyga tylko, jak głęboko idzie część (a).
  **Kroki przebiegu:**
  1. **Inwentaryzacja** — co przyszło: model, kolor, liczba zdjęć, formaty wideo. Struktura katalogu: `sesje/RRRR-MM-DD/<Model>/<Kolor>/`.
  2. **Przygotowanie assetów** — zdjęcia na WebP z nazwami plików i altami pod frazy ([[feedback_obrazy_webp_nazwa_i_opisy_pod_seo]]); wideo w obu kadrach (16:9 i 9:16) zgodnie z `METADANE — jak opisywać te filmy (v2).txt` w katalogu `sesje/`.
  3. **Hub modelu** — galeria realnych egzemplarzy + wideo + treść pod frazy modelu; zabezpieczenie warstwy ręcznej przed automatami (patrz T-240 i ⚠️ niżej).
  4. **Konta reklamowe** — nowe kreacje do kampanii wideo i Demand Gen (Google Ads, konto 9506068500), obrazy na poziomie **grupy reklam** (działają też w search — [[reference_gads_group_level_images_work_in_search]]), materiały do Meta (piksel/katalog — [[project_meta_dostep_odzyskany_2026_07_31]]).
  5. **YouTube** — publikacja **po jednej pozycji dziennie, nie paczką**; w trakcie fali NIE ruszamy tytułów ani miniatur, bo ruch Shortsów jest organiczny i reset modelu CTR ucina falę ([[project_yt_metadane_przebudowa_2026_07_25]]).
  6. **Planowanie hubów** — które modele z sesji mają hub, które wymagają nowego, co z podażą ofert.
  **PIERWSZY PRZEBIEG — Leopard 5, 3 h.** Hub `/samochody/byd/leopard-5/` (term 5523, 23 oferty) robi w GSC 07.07–03.08 **228 kliknięć / 3 709 wyświetleń** (≈⅓ ruchu strony głównej), ale rozkład jest nierówny: frazy z ceną stoją wysoko i konwertują (`denza bao 5 cena` poz. 2,7 przy CTR 13%, `denza b5 cena` poz. 1,1), natomiast dwie **największe** frazy modelu leżą odłogiem — **`leopard 5`: 813 wyświetleń, 13 kliknięć, poz. 8,8** i **`byd leopard 5`: 812 wyświetleń, 10 kliknięć, poz. 5,1**. Razem 1 625 wyświetleń przy 23 kliknięciach. Materiał gotowy (`sesje/2026-07-23/Leopard5/`, uporządkowany 06.08): 16 zdjęć czarnego, 13 niebieskiego, 4 filmy mp4 pracujące dziś w kampaniach, do tego test Terenwizji (394 tys. subskrybentów, 249 tys. wyświetleń, auto kupione u Ruslana — mówi to sam właściciel) i dane techniczne z importu. Rozbicie: 0,5 h assety · 1 h treść (realny egzemplarz, wideo, odniesienie do testu, FAQ pod `kiedy w polsce` i `wymiary` — obie frazy już w GSC) · 0,75 h wpięcie (schema, linkowanie z rankingów i strony głównej, Indexing API) · 0,75 h zabezpieczenie przed automatami.
  ⚠️ **Tych 45 minut nie ucinamy do wersji 2 h** — bez zabezpieczenia najbliższy przebieg generatora tytułów albo reworku hubów nadpisze robotę i zapłacimy drugi raz (T-238, 05.08: 12 hubów, 156 000 zł rozjazdu). To najprostszy wariant dla jednego termu, czyli **dowód działania, nie gotowy mechanizm** — jeśli okaże się, że ochrona wymaga zmian we wtyczce, żeby respektowały ją wszystkie automaty, ta część wypada z T-241 i staje się właściwą treścią **T-240**.
  ⚠️ Dotyka `taxonomy-serie.php` = **strefa ZAWSZE PYTAJ**. Landing budujemy **na hubie**, nie jako osobny wpis — hub już rankuje na te frazy, osobna strona by go kanibalizowała.
  **Materiały czekające na kolejne przebiegi** (sesja 2026-07-23): Exeed VX granatowy (20 zdjęć + 2 filmy), Jetour T2 PHEV srebrny (15 zdjęć + 2 filmy). Porządki na Dysku 06.08: folder `filmy` został pusty (do skasowania), katalog `Exceed-VX` ma literówkę (marka to **Exeed**), brak klatek SCH02423–02426 w ciągu sesji.
- [ ] **T-240 — Indywidualizacja hubów modeli, do których mamy własne materiały (pilot: Leopard 5)** 🅿️ **POMYSŁ, rzucony luźno 2026-08-06 — nie zadanie; przed budową spec i decyzja** — dziś wszystkie 317 hubów `serie` renderuje jeden szablon i jeden automat treści. Teza Janka: modele, przy których mamy **własne** aktywa, zasługują na osobne, bogatsze potraktowanie, a Leopard 5 jest naturalnym pilotem, bo mamy komplet: **4 filmy na Dysku** (czarny i niebieski, 16:9 i 9:16, folder paczki z 24.07) pracujące dziś na YouTube w kampaniach wideo i Demand Gen, zdjęcia z sesji (czarny egzemplarz — lokalizacja na Dysku do potwierdzenia), **test Terenwizji** (394 tys. subskrybentów, 249 tys. wyświetleń, auto kupione u Ruslana, właściciel mówi to w materiale), dane techniczne z importu oraz **23 oferty** na hubie. Konkurencja ma pod tą frazą wyłącznie generyczny opis katalogowy — my możemy postawić stronę z realnym autem, realnym testem i realną ceną.
  ⚠️ **Rdzeń do rozstrzygnięcia przed startem: kolizja z reworkami.** Huby są dziś masowo przebudowywane automatami (tytuły, opisy, blok leasingowy, ceny — T-219 B, T-238, generator tytułów). Ręcznie dopieszczony hub albo zostanie nadpisany przy najbliższym przebiegu, albo — jak przy fladze `_asiaauto_skip_title_regen` — wypadnie spod automatu i **zamrozi się razem z ceną** (T-238: 12 na 12 rozjechanych, 156 000 zł odchyleń). Trzy kierunki: (a) flaga „hub indywidualny" i automaty omijające wyłącznie sekcje ręczne, (b) **rework hybrydowy** — dane (cena, sztuki, parametry) dalej z automatu, warstwa narracyjna i multimedia ręczne, (c) osobna ścieżka reworku dla hubów indywidualnych. Wariant (b) wygląda najzdrowiej, bo nie odcina huba od danych, które muszą rotować. **Do ustalenia też:** ile takich hubów realnie chcemy (materiały mamy dziś do Leoparda 5, Exeed VX i Jetoura T2), czy filmy osadzamy czy linkujemy (na hubie ten sam problem co na stronie głównej: iframe = skrypty zewnętrzne, zgoda Complianz, CWV) oraz czy indywidualizacja obejmuje sam szablon, czy tylko treść. Spina się z wpisem o Leopardzie (T-239, „otwarte") i z sesjami zdjęciowymi u Ruslana (T-225e).
- [ ] **T-114 — Parkowanie aut (ulubione / „koszyk")** — zalogowany user zapisuje listingi do własnej listy „schowanych" aut do przeglądania. Wymusza logowanie/rejestrację. Ikonka w headerze obok kontaktu. Per-user storage (user meta / CPT), spina się z UX rejestracji + mailingiem.
- [ ] **T-115 — Porównywarka aut** — „dodaj do porównania" + tabela porównawcza specyfikacji. Ikonka w headerze obok parkingu/kontaktu. Bazuje na danych spec listingu (Dongchedi); stateless (cookie/localStorage) lub per-user jak T-114.
- [ ] **T-113 / T-121 / T-217 / T-220 / T-221 — umowy, depozyt, płatności** → przeniesione do sekcji **🔴 WĄTEK AKTYWNY** na górze pliku (mają spec i decyzje z 2026-07-27, nie są już pomysłami).
- [ ] **T-189 — Kalkulator finansowania na stronie oferty** — widget na single listingu (i ew. w wizardzie `/zamow/`): klient wpisuje wpłatę własną / okres / typ (leasing / kredyt / pożyczka) i widzi szacunkową ratę miesięczną od ceny katalogowej auta. Cel: obniżenie bariery „całość z góry" + lead gen („zapytaj o finansowanie" → kontakt/wizard). Spina się z T-113 (umowa o finansowanie — kalkulator to frontowa zajawka tej samej oferty). Do ustalenia przed spec: (1) czy Ruslan ma partnera finansującego i jego realne stawki (oprocentowanie/prowizja) — bez tego rata tylko „orientacyjna" z widocznym disclaimerem; (2) leasing dot. firm — czy pokazywać netto/brutto przełącznik; (3) czysty frontend JS (stateless, bez strefy kruchej) vs zapis parametrów do leada. Dodane 2026-07-03.
- [ ] **T-116 — Zaawansowana wyszukiwarka fasetowa** — filtrowanie po atrybutach technicznych: liczba miejsc, przyspieszenie 0-100, długość (zakres mm) itd. Cel: precyzyjny dobór auta po parametrach. Wymaga audytu jakie pola spec mamy w meta z Dongchedi + ew. dociągnięcie brakujących; range-slidery.
- [ ] **T-187 — Single (mobile): pasek kafli „inne ogłoszenia tego samego modelu" + „Pokaż wszystkie"** — na pojedynczym ogłoszeniu, **tylko na telefonie**, NAD istniejącym modułem „Inne modele {marka}" (pills do hubów — `class-asiaauto-single.php::relatedModels()`, render linia ~94) dodać pasek kafli z fotami pozostałych ogłoszeń **tej samej `serie`** (wykluczając bieżący post) + CTA „Pokaż wszystkie" → hub modelu (`get_term_link(serie)`). **Reuse istniejących komponentów:** kafel `AsiaAuto_Inventory::renderCard()` (`class-asiaauto-inventory.php:926`, public static) + pasek `renderListingsCompact()` z `[asiaauto_hub_listings]` (`class-asiaauto-brand-hub.php:277`, nagłówek+CTA, filtr make+serie). **Do dorobienia:** (1) `post__not_in`/`exclude` w `renderListingsCompact` (dziś brak); (2) wywołanie z single z jawnym make/serie (shortcode czyta query vary, których na single nie ma) — publiczny wrapper lub mały własny `WP_Query`+`renderCard()`; (3) wrapper `--mobile` + media query (wzorzec `aa-info--mobile`); (4) CSS — pasek hubowy używa klas `aa-home__latest`/`aa-home__section-*`, single ładuje tylko `asiaauto-single.css` → doładować style albo reuse `aa-card` z gridem w CSS single; (5) edge: 0 kafli po wykluczeniu → nie renderować. **Dotyka `class-asiaauto-single.php` = strefa ZAWSZE PYTAJ** ([[feedback_no_edit_homepage_without_ok]]) → najpierw spec/OK.
- [ ] **T-188 — Zapisane wyszukiwania + alerty intent-based (newsletter)** — dla zalogowanych/zarejestrowanych: zapis nazwanego zestawu filtrów (np. „czarny Leopard 5") i mail gdy pojawi się nowe pasujące auto. **Rdzeń = alerty oparte na intencji, nie masowy newsletter** (klient sam definiuje czego szuka → wyższy CTR). **Model danych:** CPT `asiaauto_saved_search` (post_author=user), meta `_params` (serializowany schema filtrów = mapa `class-asiaauto-inventory.php:509-521`: marka/model/paliwo/nadwozie/rocznik/kolor/naped/cena_min/max/sort — jeden source of truth, ten sam co URL `/samochody/`), `_alert_enabled`, `_alert_freq`, `_last_notified_gmt`, `_label`. **UX:** przycisk „🔔 Zapisz to wyszukiwanie" na `/samochody/` obok licznika; niezalogowany → lekki modal „podaj e-mail" (= **wpięcie newslettera/leada, double opt-in**, pełne konto opcjonalne, nie blokować konwersji); panel „Moje wyszukiwania" w koncie `asiaauto_customer` (dokładka do panelu zamówień, spina się z Parkowaniem T-114). **Silnik dopasowań = newsletter:** WP-cron → dla każdego saved_search z alertem uruchom **istniejący query builder `handleListings`** (zero nowej logiki) → listingi `date > _last_notified_gmt` (tylko nowe) → mail z **kaflami `AsiaAuto_Inventory::renderCard()`** + link + opt-out. **Newsletter 2 poziomy:** (a) alert per-wyszukiwanie [faza 1], (b) ogólny newsletter nowości [faza 2, osobny double opt-in]. **Wysyłka — decyzja:** wysyła SYSTEM do usera który sam się zapisał (funkcja produktowa, ≠ ad-hoc mail do klienta z globalnego NIGDY); start = WP-cron + transactional SMTP `noreply@primaauto`, przy skali → ESP (Brevo/Mailgun, unsubscribe/deliverability). **Reuse:** schema paramów + query builder + renderCard + konto/panel customer = nowy kod głównie CPT+UI+cron-matcher+szablon maila+warstwa zgód. **Decyzje przed spec:** (1) próg wejścia: sam e-mail [rekom] vs pełna rejestracja; (2) zakres fazy 1: tylko alerty [rekom] vs +ogólny newsletter; (3) wysyłka: SMTP [rekom] vs ESP. **Prawne:** zgoda marketingowa + double opt-in + opt-out/NAP/DSA w mailu (podpiąć politykę prywatności z `/informacje/`). Spina się z T-114 (parking, wspólny header „Moje konto").
- [ ] **T-152 — Wystawianie ogłoszeń na Otomoto** — nowy kanał dystrybucji: automatyczny eksport ofert z CPT `listings` do Otomoto (feed XML/API dla dealerów). Cel: zasięg poza własną stroną. Do rozpoznania: format integracji (CSV/XML vs API), mapowanie pól, polityka cenowa na portalu, model rozliczeń.
- [x] **T-162 — Sekcja kontentowa/blogowa (baza wiedzy)** 🟢 **ZAMKNIĘTY 2026-08-04 jako wchłonięty przez T-214** (jeden wątek = jeden numer; strategia D2/D3 obowiązuje dalej w T-214 i w tasach T-225…T-234) — własny dział treści: rankingi (np. „Top 20 SUV-ów w Chinach 2026"), porównania z europejskim premium (AITO M9 vs Mercedes GLS / BMW X7 itd.), poradniki importu. Cel: **AEO/SEO** (treści cytowalne przez LLM-y + long-tail porównawczy), autorytet, ruch top-funnel → konwersja na listingi. Do ustalenia: CPT/kategorie vs natywne WP posts, szablon rankingu (tabela + linki do hubów), template „X vs Y" (side-by-side z danych Dongchedi + dane konkurenta EU), Schema (Article/ItemList), pipeline (ręczny vs n8n). Spina się z porównywarką T-115. **→ Wchłonięte jako filar C przez T-214 (dział wiedzy) — strategia D2/D3 z T-162 obowiązuje dalej.**
- [ ] **T-214 — Dział wiedzy: Leksykon `/wiki/` + Magazyn `/aktualnosci/` + Blog `/rankingi|porownania|poradniki/` (wspólny silnik Claude, bez n8n)** — plan kompletny, **decyzje D1–D6 rozstrzygnięte 2026-07-21, gotowy do startu F0**: ~120 haseł technologii z `_asiaauto_extra_prep` (LFP/PSM/EREV/800V/LiDAR/Devialet…, liczności zbadane) + auto-linker dwukierunkowy (filtr `asiaauto_autolink_html` w 4 punktach renderu) + codzienne newsy z rynku CN (radar RSS carnewschina+cnevpost ~14/dz, cron 06:45, **mail akceptacyjny z tokenowym „Opublikuj"**, byline „Redakcja Prima-Auto", NewsArticle + news sitemap RankMath, Google News/Discover) + rankingi data-driven. Magazyn+Blog = natywne wpisy WP z kategoriami; CPT tylko Leksykon. Silnik F0–F4: 74–100 h; koszt AI ~$6–12/mc. Spec: `docs/roadmapa/T-214-dzial-wiedzy.md` + pełny plan HTML na auratest. Nadrzędny wobec T-162, buduje fact-check z T-208 §1. **STAN 2026-08-04:** F0–F3 live — Słownik **74 hasła** (40 klik./2330 wyśw. w 28 dni), Aktualności **38 wpisów** (413 klik./3578 wyśw. — więcej niż 1228 stron ofert), `/rankingi/` HTTP 200 z 0 wpisów. ⚠️ Magazyn milczy od 22.07 (cron 06:45 do sprawdzenia). F4 rozpisane, pillar wydzielony do **T-225**.

- [ ] **T-225 — Rework `/marki/`: strona filarowa działu wiedzy** — dziś `/marki/` to lista 58 nazw z licznikami (H1 + jedno zdanie + 2 nagłówki + kafelki; **zero obrazków, zero akapitów, brak `ItemList`**) i robi **631 wyświetleń / 2 kliknięcia w 90 dni** przy pozycjach 15,6–43,3 na frazę **5 400/mc**. Rework: klasyfikacja marek (`_asiaauto_brand_origin`: chinese / chinese_owned / global_jv) → 3 nazwane sekcje zamiast dwóch bezimiennych (dziś **Volkswagen z 8 ofertami stoi w „Najpopularniejszych" przed Hongqi 116 i Li Auto 116** — hardkod slugów w szablonie), wstęp merytoryczny + mapa koncernów, **teasery z 49 gotowych `term.description` (śr. 8,8 tys. znaków), które dziś nigdzie się nie renderują**, uzupełnienie 9 brakujących opisów, logotypy (fraza „logo chińskich samochodów" **poz. 7,4** przy zerze obrazków), FAQ + `ItemList`/`FAQPage`, sekcja „Najnowsze rankingi" podpięta do `/rankingi/` (ukryta do pierwszych wpisów). ⚠️ **D1 do potwierdzenia:** czy marki `global_jv` (VW, Toyota, Mazda…) zostają w sekcji z wyjaśnieniem, czy znikają — ich huby robią **864 klik./16 460 wyśw. w 90 dni**. Dotyka `page-marki.php` = **ZAWSZE PYTAJ**. Rozbite na **T-225a–d** (patrz fala niżej). Spec: `docs/roadmapa/T-225-strona-marki-pillar.md`; pomiar wejściowy: `docs/analizy/2026-08-04-rankingi-porownania-recon.md`.

- [ ] **T-237 — Domknięcie audytu che168 z 03.08** 🟡 **CZĘŚCIOWO WYKONANE 04.08 (v0.34.19)** — ✅ kontaminacja naprawiona (Galaxy M9 i Galaxy L7 zdjęte z hubów AITO i Li Auto; przy okazji odzyskane ceny „od": AITO M9 224→312 tys., Li Auto L7 99→211 tys.), ✅ duplikat `LS9`+`IM LS9` scalony (3 szt. na jednym hubie, 301 przez `V62_SERIE_REDIRECTS`), ✅ ustalone, że `Smart #5` vs `Smart #5 EHD` to **różne auta**, nie duplikat. **ZOSTAJE:** sieroty (`Lotus|Emira` 11 szt. przy 8100/mc — największa niewykorzystana podaż, `奥迪E5 Sportback` 5 szt./880 mc + 4 warianty nazw) oraz `LS8` w mapowaniu (dziś term `IM LS8`, mapa produkuje `LS8` → przy następnym imporcie powstanie duplikat). Skrypt naprawy: `scripts/fix-t237-kontaminacja-ls9.php`. — punkty „do decyzji" z `docs/sesje/2026-08-03-filtry-che168-zaciag-i-audyt-nazw.md`, wszystkie **nadal aktualne**:
  • **Kontaminacja hubów (najpilniejsze, ~15 min):** post 387316 „Galaxy M9" siedzi na termie `M9` (5304 — należy do **AITO**), post 390248 „Geely Galaxy L7" na termie `L7` (5739 — **Li Auto**). Marka poprawna (Geely), zła seria → auta Geely wyświetlają się na cudzych hubach. Naprawa: przepiąć `term_relationships` na `Galaxy M9` (6550) i `Galaxy L7`, potem `wp term recount`.
  • **Sieroty — podaż przechodzi filtry, brak wpisu w mapie:** `Lotus|Emira` **11 szt. przy 8100 wyszukań/mc** (w bazie 0 aut — największa niewykorzystana podaż), `奥迪AUDI|奥迪E5 Sportback` 5 szt. przy 880/mc (hub `E5 Sportback` już istnieje), plus `昊铂A800`, `银河M9`, `仰望U8 L`, `FOR ME` — warianty nazw aut, których huby mamy. ⚠️ Alias w `che168-model-map.php` jest martwy bez pary `mark_eu|serie_eu` w `brand-mapping-v6.1.php`.
  • **Duplikaty termów:** `LS9` (2 auta) obok `IM LS9` (1) — wolumen `im ls9` 90/mc; `#5` (1) obok `smart #5` (0) — `smart 5` 720/mc; `IM LS8` (1), mapa produkuje `LS8`.
  Nazwy potwierdzone jako dobre i **nie do ruszania**: Hyptec, M-Hero, Galaxy Starship 8, IM LS6, WEY 07.

### FALA „treść i linkowanie wewnętrzne" — 15 tasków, 58–76 h (zatwierdzona 2026-08-04)

> Plan i kolejność: `docs/roadmapa/2026-08-04-fala-tresc-i-linkowanie.md` · Audyt słownika: `docs/analizy/2026-08-04-slownik-luki-audyt.md`
> **Kluczowe ustalenie:** `AsiaAuto_Autolink` działa od 22.07 i jest wpięty w `tech_specs`/`equipment` — **każde nowe hasło `/wiki/` = automatyczny link w setkach ofert, bez dotykania kodu**. Dlatego słownik idzie pierwszy.

- [x] **T-233a — Słownik: 15 haseł o pokryciu >1000 aut** 🟢 **WYKONANE 2026-08-04** — 15 haseł LIVE (`/wiki/`, 935–1298 słów, DefinedTerm+FAQPage, FAQ 5 z PAA), słownik 74 → **89 haseł**, wszystkie zgłoszone do indeksacji (15/100 budżetu ad-hoc), llms.txt + sitemapa przeliczone. Fix w generatorze: `db_examples()` gubił ~24% rekordów (szukał tylko escapowanej formy CJK). ⚠️ `hamulce-tarczowe-wentylowane` przegenerowane — pierwszy przebieg poszedł bez researchu (`_wiki_sources` puste), duplikat usunięty. **Pomiar: linki w ofertach BEZ zmian (nadal 6) — limity `tech_specs`/`equipment` 3+3 są wysycone.** Pierwotny opis: — zawieszenie (wielowahaczowe 1449, McPherson 958, dwuwahaczowe 886, pięciowahaczowe 345, regulacja twardości/wysokości 579), hamulce (tarcze wentylowane 2001, EPB 2029), struktura nadwozia (samonośna 1933 / **ramowa 103** = terenówki), mapy Amap 857, radar mmWave 1061, skrzynia stałoprzełożeniowa w EV 1079, wtrysk bezpośredni 1101, turbo 806. Pipeline `scripts/kb/wiki_generate.py` + nowy `wiki_tier5.json`. ⚠️ warianty zapisu CN (`双叉臂式独立悬架` 682 vs `…悬挂` 204) muszą trafić do `term_keys`, inaczej hasło złapie połowę aut. 6–8 h.
- [x] **T-226 — Dane podstawowe na ofercie: marka, model, wersja, kolory** 🟢 **WYKONANE 2026-08-04, v0.34.18** — sekcja zaczyna się teraz od Marka → Model → Wersja → Rok modelowy → Kolor nadwozia (+ Kolor wnętrza gdy jest). Napęd, skrzynia, liczba miejsc i typ nadwozia **już były** w tabeli — dodano tylko to, czego brakowało. Zweryfikowane na 4 ofertach (EV, EREV, benzyna, che168). ⚠️ Doszło 5 wierszy, więc próg „pierwsze 8 wierszy przed «Więcej»" chowa teraz część wymiarów — do rozważenia podniesienie progu. — sekcja otwiera się dziś wymiarami (Rok modelowy → Typ nadwozia → Długość…), brak identyfikacji pojazdu. 3–4 h.
- [x] **T-227b — Priorytet niszy w autolinkerze** 🟢 **WYKONANE 2026-08-04, v0.34.17** — limit BEZ zmian (3+3), zmieniona selekcja. Mockup zaakceptowany przez Janka. Efekt: `CLTC · LFP · FinDreams` zamiast `Rok modelowy · CLTC · LFP`; na 12 ofertach 33/72 slotów dla niszy chińskiej/EV zamiast 13. Wydajność: 1,94 ms (taniej niż przed — przebieg 2 iteruje po 3 aliasach zamiast 271). ⚠️ Sama zmiana sortowania mapy NIE wystarczyła — pętla idzie po segmentach HTML z zewnątrz, więc każda komórka linkowała pierwsze trafienie; potrzebny był globalny przebieg `pickWinners()`. *(pierwotny opis)* — pomiar 2026-08-04 na ofercie XPeng Mona M03: w tabelę specyfikacji trafia **10 haseł, a limit przepuszcza 3** — i przepuszcza najmniej wartościowe (`rok modelowy`, `CLTC`, `LFP`), bo wygrywa kolejność wierszy, nie istotność. Odpadają: McPherson, Belka skrętna, Wentylowane tarczowe, hamulec postojowy, Samonośne, stałe przełożenie, FinDreams — czyli 6 z 15 haseł T-233a. Zmiana = dwie liczby w `class-asiaauto-shortcodes.php` (`tech_specs` 3→10, `equipment` 3→6); przy 13 926 znakach tabeli to ~1 link na 1400 znaków. Osobno do rozważenia: linkować wartości zamiast etykiet (render ma klasy `aa-tech__label` / `aa-tech__value`) — to już zmiana w autolinkerze. Dotyka renderu oferty = **ZAWSZE PYTAJ**, diff czeka na akcept. 1 h.
- [~] **T-227 — Parametry jako linki do hubów i katalogu** ⚪ **WYKREŚLONE 2026-08-04, bez wykonania** — analiza przed startem pokazała, że zadanie się nie broni: (1) ten sam wydatek na budżecie linków, przed którym sami się bronimy (oferta ma **40 linków w `<main>`**); (2) katalog z filtrem musi być `noindex` (index bloat, wymóg z T-115) → **zero wartości SEO**, zostaje sam UX; (3) UX-owo cofa użytkownika w lejku — kto ogląda konkretny egzemplarz, już wybrał model, a „inne egzemplarze tego modelu" i „inne modele marki" **już są na stronie** (T-187); (4) **nie ma dokąd linkować** — `/samochody/?nadwozie=suv` zwraca te same 72 oferty co katalog bez filtra, w HTML zero pól filtrowania. Zadanie zaczynałoby się od nieznanej roboty w katalogu. **Wrócić przy T-230** (rankingi segmentowe): wtedy „Typ nadwozia: SUV" może prowadzić do rankingu, czyli do treści — i raczej z hubów oraz `/marki/` niż ze strony konwersyjnej.
- [ ] **T-225a — `/marki/`: wstęp + mapa koncernów + 9 brakujących opisów marek** (bez dotykania kodu — szablon ma hook na `post_content`). 3–4 h.
- [x] **T-235 — Audyt marek + uzupełnienie treści hubów** 🟢 **WYKONANE 2026-08-04** — **Liczba 58 marek potwierdzona dwoma drogami** (term.count == zliczenie z bazy po ofertach publish) — ręczne importy jej nie rozjechały. **55 marek ma własny hub**, 3 to sub-marki scalone redirectem V61 (jetour-shanhai→jetour, dongfeng-yipai→dongfeng, dongfeng-fengxing→dongfeng) — ich kafle w siatce `/marki/` prowadzą przez 301, do poprawy w T-225b. **Luki znalezione:** 6 marek bez `wiki_body`, 9 bez FAQ i opisu termu, 11 bez leadu i sufiksu H1; title i meta description miały komplet (0 braków). **DFS dla marek z lukami:** Maxus **9900/mc** i Foton **8100/mc** przy zerowej/niepełnej treści — najostrzejsze luki; BAW 1300, Maextro 1300. Te marki obsługują segmenty z popytem: Foton = pickupy, BAW = terenówki (Beijing 212), Shangjie = kombi EV (Z7T). **Zrobione:** nowy generator `scripts/kb/make_hub_generate.py` (research + dane z bazy + gates: diakrytyki T-193, liczby tylko z bazy, backup, zapis źródeł) → treść dla **5 marek** (Maxus, Foton, BAW, Shangjie, Dongfeng Fengxing), huby 1185–1208 słów, FAQPage parsuje się. ⚠️ Foton wymagał powtórki (niepełna odpowiedź modelu za pierwszym razem). ⚠️ Treść dla Dongfeng Fengxing leży w bazie, ale hub jest pod 301 — zadziała dopiero po zdjęciu redirectu. **Do generatora dopisana reguła** [[T-236]]: nie opisujemy procesu importu (organ, miejsce, etapy, miasta, porty, terminy) — tylko rezultat dla klienta.
- [x] **T-236 — Twierdzenia o homologacji i procesie importu w treściach hubów** 🟢 **ZAMKNIĘTE 2026-08-04** — **Wykryte:** treści twierdziły, że dopuszczenie odbywa się „w certyfikowanej stacji diagnostycznej w Polsce" (błąd — to decyzja Dyrektora TDT, stacja robi tylko badanie techniczne), obiecywały auto „z polskimi tablicami" (a strona informacyjna mówi, że wniosek składa klient) i podawały transport 4–6 tygodni (strona Ruslana: 6–8). **Naprawione łącznie 372 pola** w 3 przebiegach (`scripts/fix-homologacja-tresci.php`, `fix-proces-zgodnosc.php`): 22 pola z opisem procedury, 350 pól z terminami, tablicami i rejestracją. **Źródło prawdy = strony `/informacje/*`**, które utrzymuje Ruslan — nie wymagały dopytywania, bo są jego deklaracją. „Koszt wliczony w cenę" zostawiony bez zmian (zgodny ze stroną „Pod dom do rejestracji"). ⚠️ **Incydent po drodze:** w regule użyto `$16-8` — PHP odczytał `$16` jako nieistniejącą grupę i zjadł prefiks „transport morski…" w 77 polach; drugi przebieg nadpisał backup pierwszego (ta sama nazwa pliku). Naprawione `scripts/fix-uszkodzone-tygodnie.php` przez odtworzenie z 37 wariantów kontekstu; kontrola końcowa 0 uszkodzeń, 0 błędnych FAQ. **Lekcje:** `${1}` zamiast `$1` przed cyfrą, unikalna nazwa backupu per przebieg. **Kraj wystawienia dokumentów świadomie NIE jest ustalany ani publikowany** — decyzja Janka: trasa jest zmienna (dziś Niemcy, jutro Holandia), więc treść mówi o rezultacie, nie o procesie. **Zasada trwała:** publikujemy REZULTAT dla klienta, nie OPIS PROCESU — konkurencja nie dostaje instrukcji, a treść nie dezaktualizuje się przy zmianie trasy.
- [ ] **T-225e — `/marki/`: wymienić zdjęcia na własne z sesji u Ruslana** — dziś w galerii pod mapą koncernów stoi 6 kadrów z galerii ofert (Yangwang U9, LEVC L380, Tank 700, AITO M9, SU7 Ultra, Voyah Dream) — zdjęcia z chińskich placów, poprawne licencyjnie, ale nie nasze. **Po zaplanowanych sesjach zdjęciowych u Ruslana na placu** podmienić na własne kadry: realne auta, nasze otoczenie, spójny kadr i światło. Notatka zapisana na prośbę Janka 2026-08-04. Zmiana dotyczy wyłącznie `post_content` strony 263572 (blok `<figure>` w siatce) — zero kodu. Przy okazji rozważyć te same kadry jako `og:image` strony i materiał do rankingów segmentowych (T-230).
- [x] **T-225b + T-225d — `/marki/`: siatka, TOP z danych, grupa kapitałowa, FAQ i schema** 🟢 **WYKONANE 2026-08-04, motyw v1.2.1** — 50 kafli zamiast 58 (marki `global_jv` poza stroną, huby nietknięte); TOP liczone z `count` (Volkswagen z 8 ofertami przestał stać przed Hongqi ze 118); w kaflu **grupa kapitałowa zamiast teasera** (76% opisów zaczyna się od „<Marka> to…", kafle urosłyby 3× przy 79,6% mobile); FAQ 5 pytań + `ItemList`(50) + `FAQPage`; sekcja „Najnowsze rankingi" warunkowa. Klasyfikacja: `scripts/set-brand-origin-group.php`. ⚠️ CSS wymagał bumpa `PRIMAAUTO_THEME_VERSION` — cache-busting idzie po stałej, nie `filemtime`. *(pierwotny opis)* ⚠️ **D1 do potwierdzenia** (czy `global_jv` zostaje). ZAWSZE PYTAJ. 3–4 h.
- [~] **T-232 — Aktualności: rzekomo stojący cron** ⚪ **FAŁSZYWY ALARM, wykreślone 2026-08-04** — newsy publikują się codziennie, ostatnie z 05.08 (04:52 i 04:56). Mój błąd odczytu: wypisując listę wpisów użyłem `tail` na liście sortowanej malejąco po dacie, więc zobaczyłem najstarsze wpisy i wziąłem je za najnowsze. Radar, cron i autopublikacja działają. Stan na 04.08: **40 newsów opublikowanych** — CNEVPost 21, CarNewsChina 18, electrive 1.
- [x] **T-229 — Szablon rankingu rynkowego + warstwa naszej dostępności** ✅ **DONE 2026-08-05** — wszystkie 5 tasków, patche PHP na produkcji (autolinker + `ItemList`/`FAQPage` dla `rankingi`), pierwszy ranking opublikowany. Źródłem liczb jest **API rankingowe Dongchedi** per model (feedy zostają jako weryfikacja krzyżowa D9); pipeline `ranking_market → ranking_stock → ranking_generate → ranking_refresh` + tablica nazw `ranking_names.json`. Efekt: **https://primaauto.com.pl/rankingi/chinskie-suvy-ranking-sprzedazy/** (30 chińskich SUV-ów za czerwiec 2026, 15 mamy w ofercie). D9 potwierdzone: NIO ES8 czerwiec — Dongchedi 8 966 vs cnevpost 8 969 (0,03%). Wykonanie i wnioski: `docs/roadmapa/T-229-szablon-rankingu.md` (sekcja „Wykonanie — 2026-08-05"). ⚠️ **API bez `month` oddaje ostatni dostępny miesiąc bez ostrzeżenia** — okres zawsze ustalać `dcd_ostatni_miesiac()`, nigdy zakładać. SPEC v2 2026-08-04 — `docs/roadmapa/T-229-szablon-rankingu.md`, 5 zadań, 6–8 h. ⚠️ **Wersja 1 zakładała ranking naszego magazynu — to było odwrócenie sensu** (korekta Janka): punktem wyjścia były rankingi rynkowe ze zrzutów WeChat, a nasza oferta ma być ilustracją, nie tematem. Decydujący argument: **oferta rotuje, a my budujemy wiedzę** — ranking magazynu dezaktualizowałby się przy każdej rotacji. **Architektura dwuwarstwowa:** warstwa rynkowa (pełna lista modeli z rynku CN, źródła: carnewschina + cnevpost cytujące CPCA, parametry z katalogu Autohome — **sprawdzone na żywo, oba feedy HTTP 200**; `cpcadata.com.cn` nie odpowiada) = treść trwała; warstwa naszej dostępności (sztuki, cena od, zdjęcie z galerii, link) = blok `<!--OFERTA:START/END-->` odświeżany automatem, bo to ONA rotuje. **Zasady:** każda liczba ma źródło i datę (zrzuty WeChat = trop, nie źródło — twórcy nie podają metodologii, dwa zrzuty tej samej listy miały różne wartości); zdjęcia z naszych 2135 galerii tam, gdzie mamy model; brak dopasowania → blok pusty, **bez** „nie mamy" (to reklama konkurencji); sekcja „Skąd te dane" obowiązkowa. ⚠️ Autolinker i schema `NewsArticle` obsługują dziś **tylko** kategorię `aktualnosci` — rankingi wymagają rozszerzenia warunku i dodania `ItemList`.
- [x] **T-238 — Odmrożenie cen w tytułach hubów + AEO na Denzie Z9 GT** ✅ **DONE 2026-08-05, 1 h** — wyszło przy mierzeniu fraz pod rankingi. Flaga `_asiaauto_skip_title_regen` chroni ręczny `rank_math_title` przed cronem `AsiaAuto_HubTitleGenerator` i przy okazji **zamraża cenę w tytule**: 12 termów z flagą, **wszystkie 12 rozjechane**, suma odchyleń **156 000 zł**, w obie strony (Zeekr 8X 420 000 wobec 343 000 w bazie; Denza N9 255 000 wobec 275 000). Zrobione **jako dane w bazie, bez zmian w kodzie** (decyzja Janka): 12 tytułów zsynchronizowanych z `min(price)`, 3 brakujące H1 (Z9 GT, Zeekr 9X, Zeekr 8X — Google przepisywał `<title>` na H1 „— import z Chin" i pokazywał SERP **bez ceny**), 24 opisy, hub Z9 GT pod AEO (usunięty fałsz o „dopiero wchodzi do dystrybucji" — Denza jest w konfiguratorze `denza.com/pl` za **526 320 zł** wobec naszych 197 000; nowe FAQ pod PAA), 4 huby z przedziałami cenowymi zastąpionymi ceną wejścia. **Zostaje:** 8 hubów z nieaktualnymi przedziałami w treści — automat by je uszkodził, do ręcznej poprawki. Log: `docs/sesje/2026-08-05-t238-huby-ceny-aeo.md`, memory `reference_skip_title_regen_zamraza_ceny`.
- [x] **T-239 — Promocja Terenwizja na stronie głównej + sekcja z testem Leoparda 5** ✅ **DONE 2026-08-06, 1 h** (wtyczka 0.34.20) — propozycja Ruslana z 05.08. `renderPromo()` — karta promocyjna **pod** akapitem answer-first (nie nad: home walczy z pozycji 7,7–8,8 na „import aut z chin"/„auta z chin", więc pierwszy blok treści po H1 musi zostać tematyczny), CTA → `/samochody/?nadwozie=suv&naped=awd` (670 ofert). `renderTerenwizja()` — dowód społeczny: test Leoparda 5 (kanał 394 tys. subskrybentów, film 249 tys. wyświetleń), auto kupione w Prima-Auto, co właściciel mówi w materiale. Miniatura = statyczny WebP w `uploads/asiaauto/`, **bez iframe** (skrypty zewnętrzne, zgoda Complianz, CWV); jeden przycisk (do ofert), odnośniki do Terenwizji z `rel="sponsored noopener"`. Wyłącznik: `wp option update aa_promo_terenwizja 0`. Dane materiału w stałych `TW_*` — przy nowym filmie podmiana identyfikatora, tytułu, miniatury i liczb. ⚠️ **Zaliczona pułapka a11y:** `.aa-home a { color: inherit }` bije selektor jednoklasowy → przyciski miały **2,47:1** zamiast 4,5:1; naprawa wymagała podbicia specyficzności **także w media query**, inaczej `width:100%` rozpychało przycisk. Zmierzone po naprawie: 4,85 / 4,85 / 5,75. **Otwarte:** wpis o Leopardzie 5 — **nie czeka na nic**, materiał Terenwizji wisi od grudnia 2025, a my mamy własne aktywa (patrz T-240); po publikacji strona główna linkuje wewnętrznie do wpisu zamiast wprost na YouTube. ⚠️ **Poniedziałkowy film to co innego** (sprostowanie Janka 06.08): relacja ze **zlotu Terenwizji** z chińskimi terenówkami, na który Ruslan był zaproszony — materiał ma być lekko sponsorowany przez Prima-Auto. Osobna okazja contentowa, nie kontynuacja testu Leoparda. Przy okazji pomiaru wyszły **zastane** braki kontrastu poza zakresem: `.aa-home__section-sub` i `.aa-home__stat-label` — **3,71:1** na całej stronie głównej, do osobnego przeglądu.
- [~] **T-230 — Pierwsze rankingi.** 🟡 **7 wpisów LIVE (stan 2026-08-06)** — sprzedażowe: SUV-y (#406121), sedany (#406144), vany i MPV (#406146); parametryczne z katalogu Autohome: zasięg (#406151), bateria (#406167), najszybsze (#406195), najdroższe (#406191). Daty publikacji rozsunięte 16.07–05.08, żeby dział nie wyglądał na wrzucony jednego dnia. Kolumna „u nas" (sztuki + cena od + link) działa na wszystkich. Kolejność zmieniona wobec pierwotnej (terenówki → pickupy → vany): zaczynamy od **SUV-ów (6 280/mc, największa fraza, 172 modele po naszej stronie)** — decyzja z sesji 08-04/08-05 po pomiarze DFS. Dalej: terenówki i 4×4 (3 450/mc, nikt w PL tego nie obsługuje — ale API nie rozróżnia konstrukcji ramowej, filtr trzeba złożyć po naszej stronie), pickupy (2 480), najtańsze chińskie samochody (910), elektryki-bestsellery (1 880 — API nie ma filtra „tylko BEV", do rozstrzygnięcia). Definicje: `scripts/kb/rankingi.json`. 4–5 h.
- [ ] **T-233b — Słownik: ~20 haseł średniego pokrycia** — producenci baterii (CALB 125, SVOLT 49, EVE 26, Sunwoda 24, Gotion 22), systemy kokpitu (DiLink 221, Leapmotor OS, Flyme Auto, Galaxy OS, Lion), chipy (Snapdragon 8295/8155, NVIDIA Orin X/N, TOPS), systemy ADAS (DiPilot 190, Leapmotor Pilot, NOP, Li AD Max). 6–8 h.
- [ ] **T-233c — Słownik: ~15 haseł uzupełniających** — audio (Sony 69, BOSE 33, Infinity 29, Yamaha, Pioneer, KEF, Alpine), technologie baterii (Huawei Juwhale 40, Shield 29, Xiaoyao 16, Amber 9), ogniwa mieszane LFP+NMC 81, układ asynchroniczny przód + PMSM tył 345, materiały foteli. 5–6 h.
- [~] **T-225c — `/marki/`: logotypy marek** ⚪ **ZDJĘTE Z FALI 2026-08-04** — pomiar DFS: „logo chińskich samochodów" **260/mc** (frazy generyczne „logo samochodów"/„znaczki samochodowe" 3600 to nie nasz teren), czyli ~20 klik./mc przy dobrej pozycji. Wobec rankingów (terenówki 3450, pickupy 2480) słaby zwrot z 3 h + zbieranie 58 plików i rejestr licencji znaków towarowych. Jedyny mocny argument jest UX-owy (50 kafli tekstowych gorzej się skanuje) — wrócić, gdy skończą się większe tematy. — „logo chińskich samochodów" poz. 7,4 przy zerze obrazków. 3 h.
- [x] **T-225d** — wykonane razem z T-225b (jeden diff szablonu). Zostaje: zgłoszenie do indeksacji + baseline pomiaru po domknięciu T-225c.
- [ ] **T-228 — Huby marek ↔ pillar ↔ rankingi** — link powrotny do `/marki/`, logo w hero, sekcja „rankingi z tą marką" (ukryta przy zerze). 3 h.
- [ ] **T-231 — Rankingi: kombi (480), sportowe (~520), SUV-y (~6500 — największy, po pilocie).** 4–5 h.
- [x] **T-234 — Słownik: sekcje „auta z tą technologią" pod hasłami** 🟢 **JUŻ WYKONANE 22.07** (weryfikacja 2026-08-04 — spec T-214 i moja fala niesłusznie trzymały to jako otwarte). `class-asiaauto-wiki-cars.php` działa: hasło LFP pokazuje „W ofercie: 1089 aut z technologią Bateria LFP" + 4 karty ofert, 800V — 232 auta. Dopasowanie przez indeks (`json_decode`, pole-po-polu), nie LIKE. Cron twicedaily + lazy po 12 h → **15 nowych haseł T-233a dostanie sekcje automatycznie po najbliższym biegu** (do weryfikacji 05.08).
- [ ] **T-201 — Kanał YouTube: otwarcie + kreacja filmów + publikacja + kampanie reklamowe YT** — nowy kanał marketingowy: (1) **otwarcie kanału** Prima-Auto (Brand Account — decyzja: konto Ruslana czy nowe firmowe; branding z assetów brand); (2) **kreacja filmów** — do ustalenia format i pipeline: prezentacje aut z placu (materiał od Ruslana?) vs slideshow z fot listingów + lektor vs AI-generated (Kling przez Freepik API już dostępny) — uwaga na prawa do materiałów źródłowych; (3) **publikacja** — ręczna vs automatyzacja (YouTube Data API, ew. n8n pipeline analogicznie do content pipeline hubów); (4) **kampanie reklamowe YT** — konto Google Ads 9506068500 już działa → kampanie Video (in-stream/Shorts) lub Demand Gen z assetami wideo; wideo wzmacnia też istniejące RMKT/DSA. Do ustalenia przed spec: kto nagrywa/występuje, budżet produkcji + budżet media, cel (brand awareness vs leady — jak mierzymy), częstotliwość publikacji. Spina się z T-162 (baza wiedzy — filmy jako drugi format tych samych treści: rankingi, porównania, poradniki importu). Dodane 2026-07-12.
- [ ] **T-202 — Rozbudowa strony zamówienia (`/zamow/`): galeria sprzedanych aut + wzmacniacze zaufania** — wizard `/zamow/` to moment największej niepewności klienta (wpłata depozytu za auto z Chin) → dołożyć social proof i trust signals: (1) **galeria sprzedanych aut** — sekcja/karuzela z fot z `/klienci/` (47 zdjęć już live, reuse — to jest odłożony punkt „Cross-link `/zamow/` kafelek/banner" z zadania Galeria klientów 2026-05-28); (2) **wzmacniacze zaufania** — do wyboru/kombinacji: licznik sprowadzonych aut + „od kiedy działamy" (wymaga konkretu od Ruslana — ten sam blocker co „liczby w hero galerii"), kroki procesu z gwarancjami (depozyt zwrotny, umowa pośrednictwa — spina się z dokumentami prawnymi), opinie klientów (realne — NIE fabrykować; ew. Google Reviews z GBP), badge'e (NIP/dane firmy, płatność depozytu przez PayU gdy wejdzie T-121), FAQ „czy to bezpieczne" (reuse wzorca FAQPage z hubów). Do ustalenia przed spec: które elementy, kolejność w wizardzie (nie zaburzyć konwersji — najpierw pomiar GA4 gdzie klienci porzucają), skąd opinie. UWAGA: `/zamow/` jest noindex (audyt B2) — zmiany czysto konwersyjne, bez wątku SEO. Dodane 2026-07-12.
- [ ] **T-185 — Che168 Faza 1: ręczny import pojedynczego ogłoszenia** (P2, todo, **NIEZALEŻNY od T-182**) — kurator (Ruslan/Janek) wybiera per auto, na wzór istniejącego manual-import dongchedi. 3 komponenty: (1) `data/che168-model-map.php` — translacje w pliku, ~110 par naszej oferty pokrytych (51 gotowych + 59 już trafia; propozycja `tmp/che168-mapping-proposal-2026-06-05`); (2) manual import UI (`class-asiaauto-admin-manual-import.php`) — przyjmuje URL che168 + numer + `source` (dziś hardcoded dongchedi, linie ~413/520; graceful fallback: model bez mapowania → draft + „przypisz hub ręcznie"); (3) adapter importera — city z `address` (explode), `extra.configuration`→`extra_prep`, nowe meta `vin`+`first_registration`, obrazy permanent (`2sc2.autoimg.cn`, bez `x-expires`). **Spec kompletny, gotowy do budowy** (`docs/superpowers/specs/2026-06-01-che168-second-source-design.md`, rewizja 06-05); **plan wdrożeniowy** `docs/superpowers/plans/2026-06-16-che168-manual-import.md` + prompt `tmp/PROMPT-che168-manual-import-2026-06-16.md` (gate ukryty tylko-Janek, pełna tabela zgodności, log wdrożeniowy do kalibracji). Dostęp Che168 dev opłacony do 15.07.2026 (patrz T-182). Pomost przy padniętym feedzie dongchedi. Narzędzia diag/translator są source-agnostic. Powiązane: T-186 (pełny dual-source).
- [ ] **T-186 — Che168 jako równoległe drugie źródło (dual-source) — pełne dopięcie** (P3, **czeka-jan**, **GATED**) — tryb **HYBRYDOWY** (oba źródła aktywne jednocześnie + dedup heurystyczny po polach rocznik+przebieg+VIN; **NIE** toggle wyłącz/włącz, **NIE** fallback). Fazy (sekcja 8 spec): Source Manager refactor (3 klasy `AsiaAuto_Source_Registry`/`_Source_Manager`/`_Dedup_Service`, cron przez SM, BEZ Che168, behavior bit-for-bit jak dziś + testy regresji) → Che168 stub (`enabled=false`, CLI `wp asiaauto sync --source=che168 --dry-run`) → dedup + admin strona „AsiaAuto → Sources" + `enabled=true` go-live. Per-source `enabled` + stała `ASIAAUTO_SOURCES` = kill-switch operacyjny, NIE tryb pracy. **GATED na:** (a) ~~decyzja dongchedi vs Che168 = T-182~~ → rozstrzygnięte 16.06: dongchedi główny, Che168 dywersyfikacja (T-186 rusza dopiero gdyby dongchedi padł trwale LUB ręczny import T-185 się sprawdzi i pojawi potrzeba automatu); (b) decyzja segmentu Ruslana (Wariant B/C — recon: Che168 = premium używane z całych Chin, inny rynek, overlap egzemplarzy 0/30 → dedup marginalny). Spec sekcje 1–14 (wizja docelowa, odłożona 06-05). Powiązane: T-185 (manual import, near-term).

---

## T-191 — Domapowanie 27 modeli z popytem (huby bez wpisu w mapowaniu) 🔵 GOTOWE DO STARTU

> Geneza: audyt DFS T-190 (2026-07-07) — 101 hubów „db-only" (istnieją w bazie, brak wpisu w mapowaniu → import leci fallbackiem `translateModel`), z czego **27 z realnym wolumenem** (zweryfikowane grepem, false-positive'y odrzucone: Volvo XC70, Exeed Yaoguang, Avatr 07 są zmapowane). 69 pozostałych = 0 vol, pomijamy (guard pilnuje wpięcia).

**Wykonanie per pozycja:** klucz CN z meta listingów (`_asiaauto_mark/model_che168_raw` dla che168, tytuł oryginalny dla dongchedi) → wpis z CZYSTĄ EU-nazwą (np. „Bronco" nie „Ford Bronco") → przy okazji rename termu + normalizacja redundantnego sluga z 301 (zamyka resztkę „49 slugów" dla hubów z popytem).

**Grupa A → `brand-mapping` (chińskie, klucze Dongchedi):** Yangwang U9 (4400; jest U7/U8, brak U9!) · Zeekr 8X (2900; źródło dzisiejszego dup) · Tank 500 + Hi4-Z (480) · smart #1/#3 (480/390; uwaga `#` w slugify) · HiPhi Z (480) · **Geely Galaxy M9/M7 (390; korzeń fragmentacji M9 ×3)** · Haval H9 (260) · Changan UNI-K/UNI-T (210/140) · Leapmotor B01 (170; 13 aut!) / C01 (110) · Li Auto MEGA (140; 8 aut) · Jetour X90 PLUS (110) · NIO EC7/ES7 (70/40).

**Grupa B → `che168-model-map` (EU-marki, ręczne importy):** Nissan Qashqai (**165 000/mc**) · Toyota Corolla Cross (49 500) · Nissan X-Trail (40 500) · Ford Bronco (27 100) · Mazda MX-5 (22 200) · Nissan Pathfinder (8 100) · MG Cyberster (6 600) · MG 6 (1 600) · Ford EVOS (480) · Nissan Teana (320).

Dane źródłowe: raport `auratest:primaauto-t190-dfs-audyt-2026-07-07.md` sekcja 4. Warunki: mapowanie = strefa krucha (`.bak` + diff), wzorzec edycji z T-190 (`scratchpad/t190-map-patch.py`).

---

## AUDYT 2026-07-09 — T-192…T-199 (SEO/GEO/AEO/Ads) 🔵 GOTOWE DO STARTU

> Geneza: pełny audyt 4-obszarowy 2026-07-09 + faza weryfikacji każdego findingu o stan faktyczny i historię (4 agenty: serwer+DB+git+docs, GAQL, curl live). Pełny raport z werdyktami (POTWIERDZONY/OBALONY/ZNIUANSOWANY): `auratest:primaauto-taski-po-audycie-2026-07-09.md`. **Każdy task ma samowystarczalny prompt startowy w `tmp/PROMPT-t19X-*.md`** — odpalać w OSOBNYCH wątkach. Wspólne wymogi każdego taska: (1) re-weryfikacja stanu przed działaniem, (2) zakończenie = testy + dowody (raport na auratest) + **recheck wrzucony do Google Calendar** („Auranet Claude"), (3) subagenty z routingiem modeli: najbardziej wymagające=fable, średnie=opus, rutynowe=sonnet.

**Kolejność wg dźwigni:**

- [x] **T-192 (P1) — Sitemapy: sieroty cache RankMath + cron regeneracji** 🟢 WYKONANE 2026-07-09 — 5×404 (listings-sitemap17-21) + 2 stęchłe (22-23, ghosty 301/410 = błędy GSC listings-sitemap17/18) + index z cache 06-21; korzeń: pliki cache poza rejestrem `rank_math_sitemap_cache_files` (14 na dysku vs 4 w rejestrze, w tym 2 listings w rejestrze których NIE MA na dysku → rozjazd). **Fix:** backup 14 plików → `~/backups/primaauto/2026-07-09/`, `rm uploads/rank-math/rank_math_*.xml` (czysty cache), `wp rankmath sitemap generate`. Po fixie index = 16 stron (1–16, `CEIL(3049/200)`), 17–24 = 404, próbka URL z ostatniej strony = 200 (zero ghostów), suma URL ≈ publish. GSC resubmit `sitemap_index.xml` OK (204); listings 1–16 err=0; stare osobno-zgłoszone 17/18 (err=1, sub 05-06) znikną po recrawlu (~tydzień). **Cron dzienny (05:40) DODANY do crontab bezpośrednio** (crontab jest edytowalny z Claude Code — DirectAdmin „Safe to edit"; założenie z promptu o blokadzie klasyfikatora było błędne); backup `~/backups/primaauto/2026-07-09/crontab-before.txt`. T-195 llms (05:30) NIE dodany tu — osobny task (regen+sanity-gate niewykonane). Dowody: `auratest:primaauto-t192-dowody-2026-07-09.md`. Recheck +7d (kalendarz). Prompt: `tmp/PROMPT-t192-sitemapy-cache-2026-07-09.md`.
- [ ] **T-197 (P1) — Ads: pakiet decyzyjny + sync SKAG ze stanem magazynowym + recurring recheck** — odwrócone dławienie: DSA/SKAG-1/SKAG-2 ~2600 zł/30d przy 10 miękkich konw. i lost IS budget ~90%, Brand (CPA 6 zł) NIE dławiony budżetem (5,3%). 7 decyzji do akceptu + skrypt `ads-skag-stock-sync.py` (rotacja placu/transportu vs statyczne KW — wymóg Janka 07-09). Recheck CYKLICZNY co 2 tyg. w kalendarzu. Prompt: `tmp/PROMPT-t197-ads-pakiet-2026-07-09.md`.
- [x] **T-195 (P2) — llms.txt: regen + cron 05:30** 🟢 WYKONANE 2026-07-09 — drift 4673 vs 3049 publish (−35%), pliki z 06-13. **Regen:** oba generatory `scripts/build-llms{,-full}.php` przez `wp eval-file` → llms.txt 3049 ofert / 56 marek / 301 modeli, „Stan na: 2026-07-09", struktura identyczna (diff = tylko liczby+ranking; VW wypadł z top20, MG wszedł). Backup poprzednich: `~/backups/primaauto/2026-07-09/llms*.before-2026-07-09`. **Cron 05:30 DODANY do crontab** (`cd public_html && wp eval-file …build-llms.php && …build-llms-full.php`; `cd` konieczny — bez niego wp nie bootuje WP → złapane i naprawione w trakcie; test exit 0, log `~/.claude/llms-regen.log`). **Sanity-gate** wstawiony do OBU generatorów: skip nadpisania gdy publish < 50% poprzedniej deklarowanej liczby (ochrona przed snapshotem w środku awarii feedu/DB), `php -l` OK, negatywny test = blokada exit 2. Live curl: llms.txt/llms-full.txt HTTP 200, „Stan na 2026-07-09", 3049. Dowody: `auratest:primaauto-t195-dowody-2026-07-09.md`. Prompt: `tmp/PROMPT-t195-llms-cron-2026-07-09.md`. **✅ RECHECK 2026-07-16 ZIELONY — task ZAMKNIĘTY:** cron odpalił 4/4 dni (13–16.07), log bez błędów, mtime 07-16 05:30, plik **3058 ofert = publish count co do sztuki**, „Stan na: 2026-07-16", sanity-gate ani razu nie zablokował (spadki dzienne 1–3 szt.). Drift −35% zlikwidowany trwale. Szczegóły: `docs/sesje/2026-07-16-rechecki-t195-skag1.md`.
- [x] **T-193 (P2) — FAQ hubów: backfill diakrytyk 152 termów (36 make + 116 serie)** 🟢 WYKONANE 2026-07-09 — `asiaauto_faq_json` zepsute w bazie (widoczny FAQ + JSON-LD, render czysty); korzeń: batche 05-30/06-08 nadinterpretowały „ASCII quotes" jako „cały tekst ASCII". **Mechanizm:** 10 subagentów sonnet, gate STRICT `strip(out)==in` (zero dryfu treści, źródłowe literówki zostają); dry-run 5 → OK Janka; master-walidacja 152/152; adwersarz opus (12 próba, 1 błąd 5016 naprawiony); skan residualny korpusu czysty; zapis `update_term_meta`+re-read 152/152; SQL after=0 zepsutych; spot-check 5 hubów PASS (FAQPage+widoczny diakr). Backup `~/backups/primaauto/2026-07-09/faq-json-before.sql`. Reguła utrwalona w `docs/seo/hub-rework-method-2026-05-30.md`. **Kontrola siostrzana (RAPORT, do decyzji): `_asiaauto_lead` 132 + `asiaauto_wiki_body` 42 serie zepsute tak samo — poza scope FAQ → ew. T-193b.** Dowody: `auratest:primaauto-t193-dowody-2026-07-09.md`. Recheck +14d. Prompt: `tmp/PROMPT-t193-faq-diakrytyki-2026-07-09.md`.
- [x] **T-193b — backfill diakrytyk lead + wiki_body** 🟢 WYKONANE 2026-07-09 (zgoda Janka) — `_asiaauto_lead` serie 132 + `asiaauto_wiki_body` serie 42 = 174 pola bez diakrytyków (make czyste). Mechanizm identyczny z T-193: 12 subagentów sonnet, gate STRICT `strip(out)==in` (wiki HTML + token `{{LISTINGS_BAR}}` nietknięte przez konstrukcję gate'a); master-walidacja 174/174; skan residualny (1 błąd 3699 „homologacje"→„homologację" naprawiony); adwersarz opus (12 próba, 1 błąd 4539 „jedyna droga"→„jedyną drogą" naprawiony); zapis+re-read 174/174; SQL after 0+0 zepsutych; spot-check 2 huby PASS. Backup `~/backups/primaauto/2026-07-09/lead-wiki-before.sql`. **Indexing API:** top 40 hubów (make + top serie) zgłoszone (40 OK, budżet 24→64/100). Dowody: dopisane do `auratest:primaauto-t193-dowody-2026-07-09.md` (sekcja T-193b).
- [ ] **T-194 (P2, GATE) — asiaauto.pl: PRZYWRÓCIĆ 301** ⏸️ ZAPARKOWANE 2026-07-09 (decyzja Janka: „asiaauto na razie nieaktywne") — gate demo-vs-301 rozstrzygnięty na rzecz zostawienia wydmuszki; NIE przywracamy 301 teraz. Odwiesić gdy zapadnie decyzja o demie albo o pełnym wygaszeniu domeny. Kontekst niezmienny: fix istniał (v0.32.33, 04.05, smoke PASS), skasowany 14.05; dziś 200/0 B. Prompt: `tmp/PROMPT-t194-asiaauto-301-2026-07-09.md`.
- [x] **T-198 (P2) — Striking distance** 🟢 WYKONANE 2026-07-09 — **prompt okazał się nieaktualny:** 4 wskazane huby (shark-6, monjaro, z9-gt, yu7) były JUŻ zrobione 07-07 (`v2-striking`, cena+skip_regen); okno GSC audytu kończy się PRZED fixem 07-07, więc CTR 1% to pomiar starego tytułu (nowy live, niezmierzony → recheck). Skan pełnej puli (`tmp/gsc-striking-inventory`): 20 hubów striking, 11 już zrobionych; z 9 pozostałych 3 już wygrywają główne frazy (Leopard 3/Sealion 8/Exeed VX, poz 3-5, 8-9% CTR — NIE ruszane). **Realnie zmienione = 3:** Zeekr 001 (4823), Li Auto (5733), Mazda EZ-6 (5291) — title „cena w Polsce 2026" + skip_regen=1 + rework=v3-striking; smoke 200+title w HTML; Indexing API 3 URL (67/100). Backup `termmeta-pre-t198.sql`. Log: `docs/seo/striking-distance-2026-07-09.md`. Wartość konserwatywnie ~+9 klik/28d (dowód: własna krzywa CTR). Osobny track wykryty: „prima auto" homepage poz 9,8/0% CTR. Recheck +14d. Prompt: `tmp/PROMPT-t198-striking-distance-2026-07-09.md`.
- [ ] **T-196 (P3) — Homepage: dynamiczna liczba ofert w meta description** — widoczna treść OK (hero dynamiczne „3 048 ofert"), statyczne „1841" TYLKO w meta description (snippet SERP) + schema; string z 28.04 w `rank_math_description` posta 93629. Fix: filtr `rank_math/frontend/description` z transient. Prompt: `tmp/PROMPT-t196-homepage-metadesc-2026-07-09.md`. Recheck +10d.
- [ ] **T-199 (P3) — Mikro `class-asiaauto-single.php`: „Prima Auto"→„Prima-Auto" (filterTitle l.603-613 + renderMeta) + drugi H1 sticky-headera → div role=heading** — decyzja o pisowni już zapadła (NAP v0.32.25), listingi nie objęte; zero kolizji z B1 (szablony mają unset site). Prompt: `tmp/PROMPT-t199-single-branding-h1-2026-07-09.md`. Recheck +7d.

**Poza taskami (przy najbliższym commicie/okazji):** sprostowanie CLAUDE.md §1 (public_html asiaauto.pl istnieje — w T-194); dopisek do memory `project_dongchedi_feed_frozen_2026_07_07` (kurczący się katalog rozstroił sitemapy/llms/homepage-count — T-192/195/196 naprawiają trwale).

---

## T-190 — Diagnostyka + porządek duplikatów serie 🟢 WYKONANE 2026-07-07 wieczór (guard+v6.2+merge+routing; resztki: slugi redundantne → częściowo w T-191, e2e przy odmrożeniu feedu — patrz docs/seo/t190-log.md)

> Geneza: sesja audytu SEO 2026-07-07 (naprawa hubów bez treści). Janek: „to nie Ruslan, to problem przy imporcie/mapowaniu, dotyczy dużo większej liczby". Potwierdzone.

**KORZEŃ (systemowy):** `data/brand-mapping-v6.1.php` (ostatnia zmiana 18.06) **nie zsynchronizowany z merge'ami T-019 (19.06)**. Importer bierze `slug` z mapowania → dla modeli zmergowanych mapowanie wciąż trzyma STARY slug (`galaxy-starship-8-phev` zamiast kanonicznego `starship-8-phev`) → **każdy import dongchedi ODTWARZA duplikat termu**, merge cofa się sam. Dowód: listing 361496 (dongchedi) meta `serie=galaxy-starship-8-phev`; mapping v6.1 linia 843 `'slug' => 'galaxy-starship-8-phev'`. **NIE wina Ruslana / ręcznego importu** — oba listingi (dup i kanoniczny) są z `dongchedi`.

**SKALA (2026-07-07, orientacyjnie — część JUŻ naprawiona ręcznie dziś, więc snapshot duplikatów jest mylący):** ~26 grup duplikatów nazw serie / 52 termy w momencie pomiaru; 14/273 slugów mapowania make-prefiksowanych (galaxy-/li-/itd.). Realna skala driftu = do ustalenia PEŁNĄ rekoncyliacją (niżej), nie po nazwie.

**FIX (durable, 3 części):**
1. **PEŁNA rekoncyliacja mapowanie↔stan faktyczny dla WSZYSTKICH ~273 wpisów (kilkuset hubów)** — NIE wykrywanie duplikatów po nazwie (dużo już naprawione ręcznie → dałoby fałszywy obraz). Dla każdego wpisu mapowania (`mark_eu|serie_eu|slug`): (a) czy term o tym slug istnieje; (b) czy jego parent-make = mark_eu z wpisu; (c) czy nie istnieje OSOBNY kanoniczny term tego samego modelu (inny slug, ma treść/auta) = drift do scalenia; (d) czy slug w mapowaniu = slug realnego kanonicznego termu (rozjazd = importer odtworzy duplikat). Raport per-hub: OK / drift-slug / drift-parent / duplikat-kanoniczny. To jest właściwy miernik, nie snapshot nazw.
2. **Sync mapowania** — 14 make-prefiksowanych slugów → kanoniczne (drop `galaxy-` itd., dopasować do targetów T-019). Bump v6.1→v6.2. **KRUCHE (importer strefa 2)** — pokaż diff, backup.
3. **Merge bieżących duplikatów** (make-aware) + **guard w importerze**: przed utworzeniem termu serie dopasuj istniejący kanoniczny po (make + znormalizowany model), zamiast tworzyć nowy po surowym slug. To zapobiega nawrotom (dziś nawet po merge wracają).

**Decyzje sub-brand (1b) podjęte 2026-07-07 — wsad do fixu routingu (ZASADA: sub-marka z prawdziwym rodzicem = fold pod markę-rodzica, nazwa sub-marki zostaje w modelu, jak Leopard→BYD):**
- Yangwang U9 → marka **BYD**, model „Yangwang U9"
- Maextro S800 → **własna marka** (wyjątek: 301 szedł do Luxeed = zły producent) — zdjąć 301 z V61
- Hyper (cała linia GT/HL/HT/SSR/A800) → marka **GAC**, model „Aion Hyper X" (Aion=0 aut, uśpiony)
- Beijing BJ30 → marka **BAIC**, model „Beijing BJ30"
- Dongfeng Fengxing (Xinghai T5, Lingzhi EV) → marka **Dongfeng**
- Galaxy → zostaje **Geely** — **POTWIERDZONE 2026-07-07 wieczór (Janek), wzorzec BYD Leopard**: make listingów=geely, nazwa „Galaxy X" w modelu, make term `galaxy` (6579) do wygaszenia, V61 301 zostaje. Zastępuje sprzeczny punkt otwarty z ADR T-019. ADR: `docs/decyzje/2026-07-07-t190-galaxy-pod-geely.md`

**Warunki:** backup 4 tabel taksonomii (wzór `~/backups/primaauto/2026-07-07/taxonomy-pre-hubfix.sql`). Uruchomić gdy feed dongchedi aktywny (obecnie zamrożony, patrz [[project_dongchedi_feed_frozen_2026_07_07]]) — inaczej nie zweryfikujemy że guard trzyma. Skrypt-wzór merge: `tmp/hubfix-2026-07-07.php` + `tmp/honda-s7-fix-2026-07-07.php`.
**Wymóg wykonawczy (Janek 07-07 wieczór):** strefa krucha — KAŻDY krok logowany w `docs/seo/t190-log.md` (dry-run output, term_id/listingi, plik backupu, skrypt, smoke), diff przed edycją importera/mapowania/redirects. Cel: przy regresji natychmiast widać, który krok ją wprowadził.

**DIAGNOZA 2026-07-07 wieczór (sesja badawcza, raport: `auratest:primaauto-t190-diagnoza-2026-07-07.md`):**
- Mechanizm potwierdzony w kodzie: `setTaxonomyAndMeta()` (importer l.622-640) — lookup termu po slugu GLOBALNIE (bez marki), nowy term BEZ parenta (sieroty), slug ze stale mapowania. Ręczny import = ta sama ścieżka + `force=true` omija filtr konfiguracji (modele spoza mapowania → fallback translateModel+slugify → nowy sierocy term). 13 listingów „bez źródła" z ostatnich 200 = duplikacje Ruslana (akcja Duplikuj czyści `_asiaauto_source`) — dziedziczą termy, NIE tworzą hubów.
- **Nawroty T-019 potwierdzone:** galaxy-starship-8-phev ORPHAN 11 aut, galaxy-e5 7, galaxy-m9 6; kontaminacja AITO M8→GAC wróciła ×26, Denza N7→Nissan ×2.
- **Skala złych przypisań (cała baza publish): 19 wzorców, ~106 aut.** Top: AITO→„Trumpchi M8" 26, Galaxy A7 EM-i (make galaxy vs parent geely) 21, Avatr→WEY „07" 9. Galaxy M9 rozbite na 3 termy.
- **Kolizje slugów wpisane w mapowanie (6):** m8 (AITO+GAC), 07 (Avatr+WEY), n7 (Denza+Nissan), et5 (Exeed+NIO), h6, h5 (Haval+Hongqi) — kontaminacja gwarantowana do czasu guarda. W bazie 10 par termów serie o identycznym slugu (legalne przy różnych parentach — importer musi być make-aware).
- **DFS (koszt $0.012, saldo $43.18):** „baic bj30" **3600/mc** (BJ30 siedzi źle jako „Beijing Off-road BJ30" — najwyższy priorytet SEO routingu), „baic bj40" 2900 vs „beijing bj40" 140 (BJ40 pod `baic` nazwany DOBRZE — czysty „BJ40"), wszystkie warianty „212" ~0 (212→BAW = porządek merytoryczny, bez presji SEO; UWAGA: puste marki `baw` 5547 i `212` 5687 już istnieją — kolizja przy konsolidacji).
- **Kolejność fixu wg dźwigni: guard importera NAJPIERW** (bez niego każdy merge cofa się przy pierwszym imporcie), potem rekoncyliacja+v6.2, potem merge ~106 aut, potem routing (BAIC ×4 + BJ30→BAIC + 212→BAW + reszta decyzji), na końcu 49 redundantnych slugów.

**Dodatkowe fasety wykryte 2026-07-07 (ten sam korzeń — fragmentacja termów):**
- **49 hubów count>0 z make-prefiksowanym slugiem** (redundantne `/make/make-model/`, np. `haval/haval-h6l`, `mg/mg-cyberster`, `li-auto/li-auto-mega`). Normalizacja slugów (drop prefiks marki) — UWAGA: część zaindeksowana z treścią (Leapmotor Lafa5 14, MG Cyberster 8) → wymaga 301, NIE masowy rename na ślepo.
- **Wiele termów MARKI dla jednej marki** — BAIC rozbite na 4: `baic`(9)/`beijing-off-road`(2)/`beijing`(0)/`beijing-212`(8). Konsolidacja do jednej + 301.
- **Model-jako-marka / zła atrybucja:** `Beijing 212` (make, 8 aut, tytuły „Beijing 212 T01") — producent to **Beijing Auto Works (BAW / 北京汽车制造厂)**, wydzielony z BAIC = OSOBNA marka „BAW" (lub „212" standalone), **NIE model BAIC** (decyzja Janka 2026-07-07). Potwierdzić prezentację: make „BAW", model „212 T01". (Prawdopodobnie więcej złych atrybucji make — sprawdzić w rekoncyliacji.)
- **Normalizacja nazw wg uzusu:** gdzie internet używa marki-rodzica, zdejmij sub-markę z nazwy modelu — np. **„Beijing BJ30" → „BJ30" pod BAIC** (= „BAIC BJ30"), bo tak szuka rynek. ~20 pustych sierot „Beijing *" do sprzątnięcia.

**Powiązane:** T-019 (te merge'e), memory `feedback_additive_not_fragile_zone`, `reference_spec_hub_detector_dimension_variance`, ZADANIE 14 diag panel.

---

## auto-api / feed dongchedi — T-182 DOMKNIĘTE 2026-06-16 ✅

- [x] **T-182 — reklamacja do auto-api.com (feed dongchedi 3× padł w czerwcu)** — mail kooperacyjny **wysłany 15.06 17:33** (4 osie: status/timeline crawlera / Che168 50% warunkowo / credit ~100 EUR z faktury EST-257244). **Odpowiedź dostawcy 15.06 17:58:** *Che168 w okresie dev = 100 USD, dostęp **opłacony do 15.07.2026***; crawler dongchedi (pkt 1/2) i credit (pkt 4) **milcząco pominięte**. **Decyzja Janka 16.06 — dać dostawcy spokój:** nie wysyłamy kolejnego maila (crawler dongchedi to ICH nawracający problem, sami przyznali → presja nic nie wymusi; dali Che168 dev opłacony = dobra wola; 16.06 dongchedi znów dowozi trochę ogłoszeń). **Kierunek:** dongchedi zostaje głównym źródłem, Che168 budujemy równolegle jako ręczny pomost/dywersyfikację (T-185 — opłacony dostęp dev do 15.07 pokrywa fazę kalibracji). Credit ~100 EUR nieodpuszczony na zawsze — wraca tylko jeśli następna faktura znów naliczy pełną stawkę za martwy miesiąc. ADR `docs/decyzje/2026-06-15-autoapi-dongchedi-eskalacja.md`; memory `project_autoapi_dongchedi_outage_recurrence_2026_06_15` + `reference_autoapi_correspondence_history`.
- [ ] **T-183 — watchdog „0 added > X h" dla syncu dongchedi** (P2) — awaria feedu jest cicha (change_id rośnie, zero błędów, ale 0 nowych ofert); dowiadujemy się po fakcie. Konkretyzuje stary „monitoring auto-api health". Pozostaje otwarty.

---

## SESJA 2026-06-09 — domknięcie reworku marek + pomiar ✅

**Zrobione:**
1. **Pomiar GSC hub reworku (przebieg B)** — gate `tmp/gsc-inspect.py`: tylko 8/21 hubów przecrawlowane po reworku (recrawl wolniejszy niż Indexing API prosił). **Pełny pomiar przedwczesny → ponowić ~16-18.06.** Tier B `denza/z9-gt-dm-i` POTWIERDZONY BEZPIECZNY („import" poz 2,5/33% CTR nienaruszone). Memory `project_session_2026_05_30_hub_rework_pilot` (sekcja PRZEBIEG B).
2. **Indexing marek count≥12** — 23 pozostałe zgłoszone (12 z 06-08 + 23 = cały rework 35 live zaindeksowany). `tmp/make-index-rest23-2026-06-08.txt`.
3. **Długi ogon marek count<12** — **12 hubów zreworkowanych+live+zaindeksowanych**, 7 zablokowanych redirectem V61. Wzorzec subagentów jak 06-08. Memory `project_make_hubs_rework_2026_06_08` (sekcja KROK 3). Backup `~/backups/primaauto/2026-06-09/`. Lista `tmp/make-longtail-index-2026-06-09.txt`.
4. **META** — status firmy `rejected` → `pending` (ruszyło, patrz sekcja META niżej).
5. **GSC full audit (P1)** — zweryfikowane: już zamknięte 07.06 (P1+P2 wdrożone, reszta odrzucona).

**Budżet Indexing dziś: 35/100 ad-hoc** (rezerwa PrimaAuto 100 nietknięta).

**PLAN / pozostałe (czeka na czas lub decyzję Janka):**
- ⏳ **80 URL re-test** — automat `at` job 11.06 09:00 (`tmp/gsc-reindex-retest-2026-06-07.py` zmaila).
- ⏳ **Pomiar skuteczności hub reworku** — ~16-18.06 (`tmp/gsc-inspect.py` gate → `tmp/gsc-hub.py` per hub). Decyzja o 3 destrukcyjnych taksonomicznych PO tym.
- ⏳ **META verified** — SLA Meta 1-3 dni, check `meta_call.sh`.
- ⏳ **16 resztkowych 404** — recheck ~27.06.
- 🔲 **DECYZJA Janka — taksonomia (sub-marki scalone redirectem V61):** 7 z długiego ogona (dongfeng-yipai→dongfeng, jetour-shanhai→jetour, beijing-off-road→baic, chery-fengyun→chery-fulwin, yangwang→byd, dongfeng-fengxing→dongfeng, maextro→luxeed) + galaxy/gac-aion-hyper + 3 destrukcyjne (galaxy-starship-8/GAC S7/galaxy-e5). Plan: `docs/seo/taksonomia-destrukcyjna-plan-2026-06-06.md`. Rozdzielić sub-markę na osobny hub czy zostawić scalone?
- 🔲 **Pominięte z długiego ogona** (count≥1): toyota/iveco/foton/jmc-ev (globalne/użytkowe), great-wall (dup gwm), lotus+lotus-cars (dup), auxun (CJK). ~237 marek count=0 — pomijamy (nie rankują).

---

## SEO — REWORK STRONY GŁÓWNEJ (osobny track) ⏳ treść DONE, info+llms pending

> Dodane 2026-06-07. Geneza: B1 (06-06) zmienił tylko title homepage; treść NIE ruszona. **Track SEO homepage = osobny** ([[feedback_homepage_seo_separate_track]]). Research DataForSEO 2026-06-07 przewartościował topic: `chińskie samochody` 22 200/mc >> `import aut z chin` 260. Decyzja Janka: import=główny intent, resztę pokryć wtórnie. Patrz [[project_homepage_seo_topic_2026_06_07]], ADR `docs/decyzje/2026-06-07-homepage-seo-rework.md`.

**Zakres:**
- [x] **Strona główna — treść pod head termy** ✅ **v0.32.71 (2026-06-07):** hero-sub + lead answer-first + przeróbka 7 H2 + opisy pod gridami + sekcja prozy „Import aut z Chin — jak to działa" + 5×FAQ (FAQPage, dedup vs huby/info). H1 nietknięte. Fix NAP w H2 USP. Smoke 11/11, FAQPage waliduje. Pomiar GSC ~06-20.
- [x] **Strony informacyjne — mapa keywordsów** ✅ **(2026-06-07):** audyt GSC+DFS per strona → rm_title dla 9 stron (4 nowe: gwarancja/homologacja/jezyk/regulamin — ciągnęły blogname=korzeń B1) + de-kanibalizacja intentu (proces=„jak sprowadzić", pod-dom=„koszt/dostawa") + intro proza na /informacje/ (post_content, nad kafelkami) + fix 3×H1→1 na proces. Smoke PASS. Strony=zwykłe WP (NIE Elementor). DFS: informacyjny long-tail „z chin" thin (<40 vol) → gra=konsolidacja+AEO. Memory `project_info_pages_seo_2026_06_07`, skrypty `tmp/gsc-info-pages-*` + `tmp/dfs-info-pages-*`. Pomiar GSC ~06-20.
- [x] **Proces importu dla LLM-ów** ✅ **(2026-06-07):** llms.txt/llms-full.txt zweryfikowane SPÓJNE po reworku (linkują strony info, sekcja „Jak działa import" = model pośrednictwa/depozyt/7 kroków OK; nie mirrorują H2/tytułów). Zregenerowane → **4460 ofert / 65 marek / 371 modeli** (drift z 4445/64). Generatory `scripts/build-llms{,-full}.php` działają. ~~**CRON DZIENNY 05:30 — DO DODANIA RĘCZNIE PRZEZ JANKA**~~ ✅ **cron dodany 2026-07-09 w ramach T-195** (crontab okazał się edytowalny z Claude Code; założenie o blokadzie klasyfikatora było błędne — patrz T-195 wyżej + memory `reference_crontab_editable_from_claude`).

**Uwaga:** `class-asiaauto-homepage.php` + `front-page.php` = strefa **ZAWSZE PYTAJ** ([[feedback_no_edit_homepage_without_ok]]).

---

## SEO — PONOWNA ANALIZA 16 resztkowych 404 (odłożone 2026-06-07) 🔲

> Stan 2026-06-07: raport GSC „Nie znaleziono (404)" = 697 adresów, ale **675 to duchy** (już 301/200, raport nieaktualny). Janek zgłosił **„Sprawdź poprawkę"** w GSC → raport powinien spaść do ~16. **Realnie zepsute = 16** (lista: `tmp/gsc-404-realne-2026-06-07.txt`): śmieci techniczne (wpgmza/datatables/cdn-cgi/?page_id=3/complianz-css), stary system `/cars/*` + `/category-cars/` + `/landing/buying/` (dawno usunięte auta), literówka `/samochodos/byd/...`, 3× stare `/model/` (sea-lion-06ev, c-dm, lafa5). Decyzja Janka: **zostawić, recheck za jakiś czas**.
- [ ] **Po przetworzeniu „Sprawdź poprawkę" przez Google (~2-3 tyg, ~koniec 06.2026):** ponownie pobrać listę 404 (Sheets API, ID `1tMwUUmhwR1yD0wT2RJ31mTJUMPhwGrQhBmftN0fX6zc` lub świeży eksport) → re-test HTTP (`tmp/` skrypty). Sprawdzić: czy raport spadł do ~16, czy doszły NOWE realne 404 (istotne, z ruchem), czy resztki nadal to śmieci. Naprawiać tylko jeśli coś z ruchem/wartością. Kosmetyka (literówka `/samochodos/`, 3× `/model/`) opcjonalna — strefa redirectów, ZAWSZE PYTAJ.

---

## META — odblokowanie kampanii FB (BLOCKED przez Ruslana) ⛔

> Status `act_1083673765606618` na **2026-05-27**: karta podpięta (Mastercard *3519), waluta PLN, balance 0; kampania `120248507523010111` PAUSED, 0 zestawów/0 reklam; **firma `145818221430407` `verification_status: rejected`** (gorzej niż piątkowe `not_verified` — Ruslan próbował, Meta odrzuciła). API nie zwraca powodu odrzucenia.
> **2026-06-09:** status zmienił się `rejected` → **`pending`** — Ruslan ponowił zgłoszenie, Meta rozpatruje (SLA 1-3 dni rob.). Czekamy na `verified`, wtedy budujemy zestaw. Check `bash ~/secrets/meta/meta_call.sh --project primaauto-mktg "/145818221430407?fields=verification_status"`.
> Pełne tło: memory `project_meta_campaign_build_2026_05_25.md` + `project_meta_pixel_capi_setup.md`.

### Akcje po stronie Ruslana (admin firmy, Janek NIE może tego zrobić)
- [ ] **Account Quality recheck:** `facebook.com/accountquality` → portfolio „Prima Auto" `145818221430407` → odczytać dokładny **powód odrzucenia weryfikacji UE/DSA** + poprawić dokument/dane firmy + ponowne zgłoszenie. SLA Meta: 1-3 dni roboczych.
- [ ] **Katalog pojazdów (AIA) — gate niezależny od weryfikacji UE:** `business.facebook.com` → Katalogi → „Utwórz katalog" typ **Pojazdy** → dopiąć System Usera `61590035266690` jako admina katalogu. ALBO nadać temu SU rolę **Admin firmy** (wtedy wszystko z API). Po tym ja dopinam feed URL+pixel+harmonogram przez API (feed CSV 4045 aut już live, cron 04:30).

### Akcje po stronie Janka (gdy Ruslan odblokuje weryfikację)
- [ ] Dokończyć zestaw (`dsa_beneficiary`/`dsa_payor` = nazwa do biblioteki UE — potwierdzić z Ruslanem) + 5 reklam (existing posts: Xiaomi SU7 Ultra / DongFeng M-Hero 917 / BYD Leopard 8 / Volvo XC70 / BYD Shark)
- [ ] 2 reguły automatyczne dayparting (PAUSE 21:00 / UNPAUSE 08:00, entity ad set)
- [ ] `spend_cap` na koncie (proponowane ~16 000 UAH/mies. przed zmianą waluty — przeliczyć na PLN, ~1600 zł/mies. = 50 zł/dz × 32)
- [ ] Wpiąć katalog jako asset CAPI (pixel+feed) gdy Ruslan utworzy

### Check (codziennie szybkie ~30s)
```bash
bash ~/secrets/meta/meta_call.sh --project primaauto-mktg \
  "/145818221430407?fields=verification_status"
```
Gdy `verified` → odblokowane, budujemy zestaw.

---

## ZADANIE — Galeria klientów (social proof) ✅ DONE 2026-05-28

> **Wdrożone v0.32.57:** `/klienci/` jako zwykła strona WP z blokiem **Gutenberg Gallery** — 47 zdjęć, square crop (`imageCrop:true`), wbudowany lightbox (`lightbox.enabled:true`, Interactivity API od WP 6.4+), OG image #001. Zarządzanie: wp-admin → Strony → Klienci → edytor (drag&drop dodaj/usuń/przestaw). Wieczorny rollback z custom template (overengineered).

### Co zrobione
- [x] **WP page** `/klienci/` (ID 350745) z natywnym blokiem `wp:gallery` Gutenberga, 47 zdjęć, `imageCrop:true` (square crop), per-image `lightbox:{enabled:true}` (Interactivity API WP 6.4+)
- [x] **Menu** — pozycja 5 w `header` (po „Marki", przed „Informacje"), `db_id=350746`
- [x] **SEO meta** — RankMath title/description/facebook_title/description/og_image_id, twitter_use_facebook
- [x] **Lightbox** — natywny WP (swipe + klawiatura + ESC out-of-the-box)
- [x] **Smoke test** — HTTP 200, 0.12s, 47 `wp-block-image`, lightbox triggers obecne
- [x] **Rollback custom template** (wieczorem 2026-05-28) — usunięty `themes/primaauto2026/page-klienci.php` (overengineered), strona przełączona na zwykły `page.php` z Gallery block w content

### Kosztem prostoty pominięte (do późniejszej iteracji)
- ~~**Cross-link z single listing**~~ — DONE w v0.32.58 (infoBox + USP strip kolumna „Informacje" → „Galeria sprzedanych aut")
- **Cross-link homepage** — sekcja proof z 3-4 miniaturkami + CTA do `/klienci/`
- **Cross-link `/zamow/`** — kafelek/banner „Nasi klienci ich zaufali"
- **OG image dedykowany 1200×630** — obecnie #001 (~3:4)
- **Liczby w hero galerii** (ile aut sprowadzonych, od kiedy działacie) — wymaga konkretu od Ruslana
- **ImageGallery JSON-LD** — Gallery block sam się indeksuje w Google Images, schema dodajemy jeśli za 1-2 mies. okaże się brak signalu

### Zarządzanie galerią dla Ruslana (3 kroki)
1. `wp-admin → Strony → Klienci → Edytuj`
2. Klik w blok Galeria → toolbar: `+` (dodaj z biblioteki) / drag (reorder) / `×` na zdjęciu (usuń)
3. „Aktualizuj"

### Co świadomie pomijamy (scope guard)
- Brak CPT „klient" / case studies — 47 statycznych zdjęć nie uzasadnia.
- Brak ratingów / gwiazdek — nie mamy realnych ocen.
- Brak podpisów (marka/model/miasto) — decyzja Janek 2026-05-27 (zbyt osobiste).
- Brak integracji z Google Reviews / Trustpilot — osobny temat.

---

## CHECK — GSC weryfikacja 404 (po redirectach v0.32.53) ⏳

> **Weryfikacja „Sprawdź poprawkę" w GSC uruchomiona 2026-05-20** (raport „Nie znaleziono (404)", status: Weryfikacja → Rozpoczęto). Po wdrożeniu redirectów 677/683 (99,1%) martwych URL → 301.
> **Sprawdzić ~2026-06-03** (po ~2 tyg): czy GSC zamknął błędy (weryfikacja PASS) + czy raport „Nie znaleziono" opadł. Jeśli URL-e wciąż wiszą → `curl -I` na próbce + log. **Indexing API NIE używać do tego** (zżera wspólną quotę 200/dz per GCP project).
> Powiązane: `docs/decyzje/2026-05-20-redirecty-404-cleanup-gsc.md`.

### Opcjonalnie przy okazji (zdiagnozowane 2026-05-20)
- 1876/2239 hubów modeli ma count=0 (puste). NIE w sitemapie (RankMath wyklucza), więc nie pilne. Rozważyć `noindex` gdy count=0 (samonaprawiające).
- rewrite slug taksonomii `serie` = `model` → część `get_term_link` daje 2-hop chain. Kosmetyka.

---

## GA4 — eksploracje analityczne (kontynuacja sesji 2026-05-25) ⏳

> Property `534017542`. Custom channel group „Prima-Auto — Kanały" (primary). Skrypty: `tmp/ga4_query.py` (events/click/source), `tmp/ga4_channel_group.py` (list/create/primary). Spec eksploracji (drop): `auratest.pl/fe4f58fec53ctmp/primaauto-ga4-raporty-2026-05-20.md`. Memory: `project_ga4_channel_group_and_click.md`.

### DONE w sesji 2026-05-25
- **RAPORT 3 „Landing per kanał — Prima-Auto"** — Free form, zapisany w GA4 ✅. Wiersze = Strona docelowa + ciąg zapytania; Wartości = Sesje / Współczynnik zaangażowania / Średni czas zaangażowania na sesję / Współczynnik kluczowych zdarzeń w sesji / Najważniejsze wydarzenia; Filtr = Sesja – główna grupa kanałów = **Paid Search**; 30 dni, 50 wierszy. **Wniosek:** `/` (home) = 11 z 15 konwersji (6,1%), `/samochody/` 302 płatne sesje → 2 konw (0,7%), model-huby `/samochody/...` i single `/oferta/...` ≈ 0 → potwierdza „single landing conv issue (KORZEŃ)" z [[project_session_2026_05_19_ads_revision]].

### DONE — RAPORT 2 (Funnel „Lejek leada — kanały") ✅ (2026-05-25)
Funnel zbudowany i zapisany w GA4. Technika „Eksploracja ścieżki" (= funnel), OPEN FUNNEL ON, PODZIAŁ = Sesja – główna grupa kanałów, 30 dni. Kroki:
1. **Wejście** = `page_view`
2. **Obejrzał ofertę** = `page_view` + param `page_location` zawiera `/oferta/` — **substytut za `view_item`, bo `view_item` NIE istnieje jako zdarzenie GA4** (jest tylko w dataLayer, brak tagu GA4 konwertującego go na event).
3. **Lead** = `generate_lead` (pominięto `form_start` — generyczny EM łapałby inne formularze)
4. **Kontakt** = `click_phone` LUB `click_whatsapp`

**Wyniki 30d (open funnel):** Wejście 1920 → Obejrzał ofertę 938 (−51%) → Lead 5 (**−99,5%**) → Kontakt 0. Per kanał krok 1→2: Paid Search realizacja **39,9%** (najgorsza) vs Direct 66%, Organic Search 57%. Potwierdza ten sam korzeń co RAPORT 3.

⚠️ **Krok 4 = 0 to artefakt, nie błąd:** `generate_lead` i `click_phone/whatsapp` to RÓWNOLEGŁE metody kontaktu, nie sekwencyjne — nikt nie robi leada, a POTEM dzwoni. Realna wartość funnela = kroki 1–3 (zabójczy drop „obejrzał ofertę → lead"). Jeśli chcesz sensowny ostatni krok: zrób 3-krokowy funnel z połączonym krokiem „Kontakt/Lead" = `generate_lead` LUB `click_phone` LUB `click_whatsapp` (edycja ~2 min w edytorze kroków).

### TODO — re-check przeliczenia channel group (~2026-05-21+, 24h po zmianie)
- [ ] Zapytanie o `sessionPrimaryChannelGroup` (inline wariant `tmp/ga4_query.py`). Na 2026-05-20 reprocessing **częściowy**: `Facebook / Meta` 5, `AI Search` 1, reszta FB wciąż `Organic Social`. Oczekiwany stan docelowy: `Organic Social` znika → ~330 do `Facebook / Meta`, `AI Search` ~34.

### Gotchy GA4 picker/UI (oszczędność czasu przy kontynuacji)
- „Key events" w tej property = **„Najważniejsze wydarzenia"** (NIE „Kluczowe zdarzenia"). Session key event rate = „Współczynnik kluczowych zdarzeń w sesji".
- Nietypowe nazwy technik: **Funnel = „Eksploracja ścieżki"**, Path = „Eksploracja sekwencji ścieżki".
- Custom CG w pickerze: „Sesja – główna grupa kanałów (Prima-Auto — Kanały)".
- Okno przeglądarki max ~1920×945 (ekran 1080, nie da się 1400). Edytor warunku filtra renderuje się pod krawędzią panelu Ustawień → obejście: `document.body.style.zoom='0.6'` przez javascript_tool, na koniec reset `='1'`.
- Dodawanie wymiarów/metryk do Wierszy/Wartości/Filtrów: klik w „Upuść lub wybierz…" daje dropdown wyboru — pewniejsze niż drag&drop.

---

## ZADANIE 15 — Fix mocy KM dla PHEV (single + inventory cards) ✅ DONE (0.32.30, 2026-05-01)

> Wdrożone 2026-05-01 wg planu poniżej. Helper `AsiaAuto_Inventory::resolvePower($post_id, $ep)` jako fuel-aware źródło prawdy, `class-asiaauto-single.php::power()` jako cienki wrapper. Weryfikacja klienta: wszystkie 8 testów PASS (Han DM-i, Z9 DM-i Ultra, N9 DM-i Premium, Leopard 7 PHEV, AITO M7 EREV, Volvo S90 T8 combined, Z9 GT EV bez regresji, benzynowiec bez regresji). Etykieta „Moc łączna" → „Moc" dynamicznie z helpera.

### Pierwotne zgłoszenie (2026-04-30)
> Status: zgłoszony przez klienta 2026-04-30 — „dla PHEV liczy źle, EV i benzyna OK". Analiza w pełni potwierdzona na próbce ~25 modeli (BYD, Denza, AITO, Geely, Chery, Hongqi, WEY, Changan, Volvo, Zeekr) + statystyka 614 PHEV w bazie. Kierunek poprawki uzgodniony.

### Diagnoza

Trzy renderery, dwa aktywne błędne:
- `class-asiaauto-inventory.php::parseSystemPower()` (linia 1159) — karty na `/samochody/`
- `class-asiaauto-single.php::power()` (linia 245) — strona pojedynczego ogłoszenia
- `class-asiaauto-shortcodes.php::resolvePower()` (linia 1066) — **logika wzorcowa**, ale shortcode nieaktywny w motywie primaauto2026

Pierwsze dwa opierają się głównie na polu `energy_elect_max_power` które dla PHEV jest niespójne lub puste. Fallback do `_asiaauto_horse_power` (meta) zwraca moc silnika SPALINOWEGO, nie systemu.

Statystyka PHEV (614 listings):
- `energy_elect_max_power`: 193 (32%) ⚠️ niespójne, czasem combined w KM, czasem kW, czasem null
- `electric_max_power`: 392 (64%) ✓ format `"{kW}({KM}Ps)"`
- `front_electric_max_horsepower` + `total_electric_power`: 613 (99,8%) ✓ **najbardziej wiarygodne**
- `engine_max_horsepower`: 613 ✓ moc samego silnika spalinowego

### Konkretne błędy zaobserwowane

| Auto (post ID) | Aktualnie (single) | Powinno być | Manufacturer |
|---|---|---|---|
| Denza Z9 DM-i Ultra (94535) | **152 kW (207 KM)** | 640 kW (870 KM) | 870 PS ✓ |
| Denza N9 DM-i Premium (145822) | 710 kW (965 KM) ⚠️ | 680 kW (925 KM) | 925 PS |
| BYD Han DM-i (96111) | **115 kW (156 KM)** | 200 kW (272 KM) | 272 PS |
| BYD Sealion 8 DM-p 4WD (111353) | **115 kW (156 KM)** | 400 kW (544 KM) | 544 PS |
| BYD Leopard 7 PHEV (168147) | **115 kW (156 KM)** | 360 kW (490 KM) | 490 PS |
| Volvo S90 T8 PHEV (242003) | 228 kW (310 KM) ⚠️ edge | 335 kW (455 KM) combined | 455 KM |

EV-y i benzynowe działają dobrze — bo dla EV `energy_elect_max_power = "{kW}({KM}Ps)"` zawiera moc systemu, a dla benzynowych zawiera moc silnika. PHEV wpada między te dwa wzorce.

### Plan wdrożenia

**Krok 1** — Backup obu plików z datą:
```bash
cd ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes/
cp class-asiaauto-single.php class-asiaauto-single.php.bak-2026-04-30-power
cp class-asiaauto-inventory.php class-asiaauto-inventory.php.bak-2026-04-30-power
```

**Krok 2** — Wspólny helper `resolvePower(int $post_id, array $ep): array`. Najlepiej w `class-asiaauto-inventory.php` jako `public static`, a `class-asiaauto-single.php::power()` go używa (DRY). Sygnatura zwraca `['kw'=>int|null, 'km'=>int|null, 'display'=>string, 'label'=>string]`.

**Krok 3** — Logika (kolejność prób):

```
fuel_slug = get_the_terms($pid, 'fuel')[0]->slug
fuel_name = ...

is_phev_like = in_array(fuel_slug, ['phev','erev','hev']) 
            || str_contains(fuel_name, 'PHEV') 
            || str_contains(fuel_name, 'EREV') 
            || str_contains(fuel_name, 'HEV')
            || str_contains(fuel_name, 'Hybryda')
is_ev = in_array(fuel_slug, ['ev','bev']) || str_contains(fuel_name,'Elektryczny')

# PHEV/EREV/HEV/EV → moc systemu elektrycznego
if (is_phev_like || is_ev):
    front_hp  = (int) ($ep['front_electric_max_horsepower'] ?? 0)
    total_kw  = (int) ($ep['total_electric_power'] ?? 0)
    engine_kw = (int) ($ep['engine_max_power'] ?? 0)
    
    # Edge case: ICE dominuje (Volvo S90 T8, niektóre europejskie PHEV)
    # — pokaż combined zamiast samej elektrycznej
    if (is_phev_like && engine_kw > 0 && total_kw > 0 
        && engine_kw * 1.5 > total_kw):
        combined_kw = engine_kw + total_kw
        combined_km = (int) round(combined_kw * 1.36)
        return [kw=>combined_kw, km=>combined_km, 
                display=>"{combined_kw} kW ({combined_km} KM)", 
                label=>'Moc']
    
    # Standard: moc napędu elektrycznego (DM-i, EM-i, EREV, EV)
    if (front_hp > 0 && total_kw > 0):
        return [kw=>total_kw, km=>front_hp, 
                display=>"{total_kw} kW ({front_hp} KM)", 
                label=>'Moc']
    if (front_hp > 0):
        return [kw=>null, km=>front_hp, 
                display=>"{front_hp} KM", label=>'Moc']
    
    # Fallback 1: electric_max_power "kW(KMPs)"
    raw = $ep['electric_max_power'] ?? ''
    if preg_match('/^(\d+)\((\d+)Ps\)$/', trim(raw), m):
        return [kw=>(int)m[1], km=>(int)m[2], 
                display=>"{m[1]} kW ({m[2]} KM)", label=>'Moc']
    
    # Fallback 2: energy_elect_max_power TYLKO w formacie (NPs)
    # NIE używać raw (niejednoznaczne kW vs KM combined)
    raw = $ep['energy_elect_max_power'] ?? ''
    if preg_match('/^(\d+)\((\d+)Ps\)$/', trim(raw), m):
        return [kw=>(int)m[1], km=>(int)m[2], 
                display=>"{m[1]} kW ({m[2]} KM)", label=>'Moc']

# Benzyna/Diesel/inne — moc silnika
engine_hp = (int) ($ep['engine_max_horsepower'] ?? 0)
engine_kw = (int) ($ep['engine_max_power'] ?? 0)
if (engine_hp > 0 && engine_kw > 0):
    return [kw=>engine_kw, km=>engine_hp, 
            display=>"{engine_kw} kW ({engine_hp} KM)", label=>'Moc']

# Ostateczny fallback: meta `_asiaauto_horse_power` 
# (UWAGA: dla PHEV zawiera ICE-only — używać tylko gdy fuel = benzyna/diesel)
if (!is_phev_like && !is_ev):
    meta_hp = (int) get_post_meta($pid, '_asiaauto_horse_power', true)
    if (meta_hp > 0):
        kw = (int) round(meta_hp / 1.3596)
        return [kw=>kw, km=>meta_hp, 
                display=>"{kw} kW ({meta_hp} KM)", label=>'Moc']

return [kw=>null, km=>null, display=>'', label=>'']
```

**Krok 4** — `class-asiaauto-single.php`:
- Linia 230: `$pw = $this->power($d['ep']);` → zostawić sygnaturę, zmienić ciało
- Linia 231: etykieta `'Moc łączna'` → zmienić na `$pw['label']` (czyli `'Moc'`) — bo to nie jest combined
- Linia 245-256: zastąpić logikę nową (wywołanie helpera lub inline)
- Sprawdzić linia 535 i 687 (też używają `power()`) — powinno działać bez zmian

**Krok 5** — `class-asiaauto-inventory.php`:
- Linia 1124: `$hp = self::parseSystemPower($ep);` → zmienić na `$resolved = self::resolvePower($postId, $ep); $hp = $resolved['km'];`
- Linia 1159-1193: zastąpić `parseSystemPower($ep)` nową `resolvePower($postId, $ep)`
- Sygnatura zmienia się — przyjmuje też `$postId` żeby czytać taksonomię fuel

**Krok 6** — Testy weryfikacyjne (otworzyć w przeglądarce):
- `/samochody/byd/han/` — karta + single dla BYD Han DM-i (oczekiwane: 200 kW / 272 KM)
- Single Denza Z9 DM-i (post 94535) — oczekiwane: 640 kW / 870 KM
- Single Denza N9 DM-i (post 145822) — oczekiwane: 680 kW / 925 KM
- Single BYD Leopard 7 PHEV (168147) — oczekiwane: 360 kW / 490 KM
- Single AITO M7 EREV 4WD (244737) — oczekiwane: 330 kW / 449 KM (regresja test)
- Single dowolny EV np. Z9 GT EV (211197) — bez zmian: 710 kW / 966 KM
- Single dowolny benzynowiec — bez zmian
- Single Volvo S90 T8 PHEV (242003) — edge case combined: oczekiwane: 335 kW / 455 KM

**Krok 7** — Bump wersji + commit:
- `ASIAAUTO_VERSION` → bump (sprawdzić aktualną w `asiaauto-sync.php`, np. `0.32.29` → `0.32.30`)
- Commit message: `[fix:][single:][inventory:] vX.Y.Z — moc PHEV z front_electric_max_horsepower zamiast ICE-only`
- Update `docs/VERSIONS.md`

### Czego NIE robić
- ❌ NIE zmieniać `_asiaauto_horse_power` w bazie (614 listings × engine_hp; działa jako fallback dla benzynowych)
- ❌ NIE zmieniać importera (`class-asiaauto-importer.php:434` — `update_post_meta('_asiaauto_horse_power', $data['horse_power'])` — to celowo zapisuje ICE HP z API)
- ❌ NIE ruszać shortcode `resolvePower()` w `class-asiaauto-shortcodes.php` — jest poprawny, służy jako wzorzec; można go skonsolidować z nowym helperem później
- ❌ NIE ruszać MCP, contractu, statusów zamówień — to izolowana zmiana frontu

### Edge case'y do akceptacji
- **Zeekr 9X Ultra PHEV** (post 174380): Dongchedi nie ma combined power (1305 PS wg producenta), pokażemy 660 kW / 898 KM (sam napęd elektryczny). Akceptowalne — manufacturer combined niedostępne w API.
- **Hongqi HS7/HQ9** mają combined w `energy_elect_max_power` (358/300 KM) ale heuristyka wybierze `front_electric_max_horsepower` (324/286 KM). Różnica ~10%, nadal pokazuje moc realną. Akceptowalne.

### Memory
- Po wdrożeniu: dopisać do `project_session_2026_04_30_power_fix.md` (analiza pól Dongchedi PHEV, wnioski).
- Wzbogacić memory `reference_dongchedi_api_quirks.md` o sekcję power fields (`energy_elect_max_power` niespójne, `front_electric_max_horsepower` wiarygodne, `_asiaauto_horse_power` meta = ICE only).

---

## ZADANIE 14 — Panel diagnostyczny admina (asiaauto-sync) ✅ DONE (0.32.3, 2026-04-28)

Pluggable rejestr **10 checków** + Admin UI + WP-CLI + AJAX. Spec: `docs/superpowers/specs/2026-04-28-diagnostyka-admin-panel-design.md`. Plan: `docs/superpowers/plans/2026-04-28-diagnostyka-admin-panel.md`.

### Klastry (10)
- **Integralność (4):** missing-images, chinese-chars, broken-extra-prep, duplicate-listings
- **Pokrycie SEO (6):** make/serie-without-wiki, listings-without-mapping, mapping-without-term, serie-broken-parent, duplicate-serie-terms

### Workflow „dojdzie nowy model" (3 narzędzia w panelu)
1. **Popraw mapping** → `listings-without-mapping` (per-item form → append do `data/brand-mapping-v6.1.php`)
2. **Dodaj hub** → `mapping-without-term` (auto wp_insert_term) + `serie-broken-parent` (heurystyka parenta z listingów)
3. **Wygeneruj opis** → `make/serie-without-wiki` (POST do n8n webhook → wiki_body async ~30-60s)

### Punkty wejścia
- UI: WP admin → Listings → Diagnostyka
- CLI: `wp asiaauto diag list | run | run-all | preview-fix | apply-fix`
- AJAX: `asiaauto_diag_run | preview | apply` z capability `manage_options`

### Live findings (pierwszy run-all 2026-04-28)
- 32 listings bez zdjęć (auto-fix dostępny)
- 370 miejsc CN: 117 tytułów (re-translate) + 1 aktywny term (manual) + 252 orphany (bulk delete) — split w 0.32.3 + edytor mapy
- 1 złamany JSON extra_prep
- 4 aktywne marki bez wiki_body (po fixie meta_key w 0.32.2): Changan Qiyuan, Dongfeng Fengshen, GAC Aion Hyper, Wuling
- 66 aktywnych modeli bez wiki_body (po fixie 0.32.2; było mylone „303")
- **65 orphan termów serie** (parent=0 z listingami) — m.in. Zeekr 9X #6532 (artefakt migracji v6.1)
- **37 grup duplikatów serie** — m.in. Zeekr 9X jako `9x` #4824 + `zeekr-9x` #6532

### Pending v2
- **Rotator + cleanup ogłoszeń (osobny projekt + dokumentacja)** — wykryte 2026-04-28 przy testowaniu panelu. Trzy luki w `class-asiaauto-rotation.php`: (1) `trashOldDrafts` filtruje przez meta `_asiaauto_removed_at` — drafty bez tej meta (manual status change, legacy) ignorowane (z 64 overdue tylko 30 złapane), (2) `cleanOrphanedImages` filtruje przez `_asiaauto_source_url` — z 350 obecnych orphans 0 pasuje, (3) brak Plan D w importerze (`class-asiaauto-media.php` nie ustawia `post_parent` na `wp_insert_attachment` → przyszłe deletions zostawią sieroty). Realne tempo rotacji: ~220 nowych/dzień (mediana 14d, zakres 38-295), ~80/dzień do trash. Steady state: 700-1100 w trashu rolling 7d. Plan: A) fallback `post_modified_gmt`, B) usunąć filter source_url, C) Plan D, D) cleanup historyczny + ADR `2026-04-28-rotator-cleanup.md`. Akceptowano: zostać przy cron 1×/dziennie, TTL 7d trash do rozważenia.
- Klaster lifecycle (rotacja, orphan attachments, trash >30d permanent delete) — Plan D
- Klaster ops (filter cleanup, race detection alerts) — Plan A
- UI form-input modal dla `listings-without-mapping` (applyFix czeka na `$_POST['mappings']` ale JS go nie generuje — count=0 więc niegrający)
- Cron `asiaauto_diag_daily` z mailem alertem

- `duplicate-serie-terms`: heurystyka prefix 3-słów daje false-positives dla intencjonalnych sub-wariantów (EV vs DM-I) — można dodać whitelist po batch review
- **RankMath Pro integracja** (2026-04-28+, user instaluje teraz). Po deploy: weryfikacja konfliktów z `class-asiaauto-seo.php` (sitemap/robots/Car schema dublowanie), dezaktywacja naszych filtrów na rzecz RankMath. Pełen plan: memory `project_rankmath_pro_planning.md`. **n8n batch hub generation PAUZOWANY do tego czasu** — brakujące huby (`tmp/missing-hubs-2026-04-28.md`: 4 marki + 70 modeli, z czego 70 to orphan parent → fix `serie-broken-parent` najpierw) user dodaje ręcznie.
- **Chinese fragments intelligent analysis** (2026-04-28). 84 unmapped fragmenty CN — zamiast batch wpisywania user wymaga per-fragment analizy: gdzie używany, jak globalnie nazywa się, czy podobne istnieje w istniejącej mapie, propozycja EN. Raport: `tmp/chinese-fragments-analysis-2026-04-28.md`.

---

## ZADANIE 12 — Rollout SEO + Google Ads v2 (2026-04-22, aktualizowane 2026-04-24)

> Status: **PRAWIE DOMKNIĘTE** — mapowanie v6.1 DONE w 0.31.2, widoki + content pipeline DONE w 0.31.5, batch contentu DONE (45/47 make + 246/284 serie wiki coverage), AEO (llms.txt+full) DONE 2026-04-24, schema duplikat fix DONE 2026-04-24, Google Ads v2 SKAG/KI DONE w 2026-04-24 (memory `project_ads_ki_architecture.md`).

### Krok 1 — Migracja mapowania ✅ DONE (0.31.2, 2026-04-23)
- [x] Backup 4 tabel w `~/backups/primaauto/2026-04-23-v6.1-taxonomy/terms-112846.sql` (540KB)
- [x] 208 rename serie, 258 move_parent, 510 termmeta, 2 create serie, 8 nowych marek (BAIC, Beijing 212, Chery Fulwin, Exlantix, GAC, GWM, Luxeed, Nevo)
- [x] `class-asiaauto-mapping.php` singleton + `data/brand-mapping-v6.1.php` (260 pozycji) — importer mapuje CN→EU na wejściu
- [x] 301 redirects `redirectV61Brands()` (16 par: fangchengbao/yangwang→byd, galaxy→geely, itd.)
- [x] Batch update 930 post_title (`tmp/update-listing-titles.php`)
- [x] Smoke test 6 URL hubów 200 OK

### Krok 2 — SEO huby marek i modeli
**2a — widoki PHP ✅ DONE (0.31.5, 2026-04-23):**
- [x] `/marki/` — page ID 263572, template `page-marki.php` (grid 29 marek Top 8 + reszta alfabetycznie)
- [x] `taxonomy-make.php` — hero, wiki_body z `{{LISTINGS_BAR}}`, pigułki modeli (`aa-brand-card`), USP box, FAQ
- [x] `taxonomy-serie.php` — dwupoziomowy URL via `template_include`, tabelka 14+ wierszy specs, lista wyposażenia, drugi listing bar „Inne oferty marki", USP box, FAQ
- [x] Child theme `asiaauto/` rozszerzony (functions.php, style.css z wrapperami aa-home__*)
- [x] Strona główna: linki marek → huby (`get_term_link`) zamiast filtered inventory; „Wszystkie marki →" → `/marki/`

**2b — content pipeline n8n ✅ DONE (0.31.5, 2026-04-23):**
- [x] Nowa klasa `AsiaAuto_REST_Hub` — 5 GET + 1 POST endpointów (`facts-for-make/serie`, `latest-by-make/serie`, `aliases-for-make/serie`, POST `hub-content/{tax}/{id}`)
- [x] `factsForSerie` parsuje `_asiaauto_extra_prep` JSON (200+ chińskich kluczy) → specs + features_standard + features_optional + notable + taxonomies breakdown (fuel/drive/body/ca-year)
- [x] 2 workflow n8n (make + serie) na witold140-20140.wykr.es, pipeline: Webhook → 3× HTTP → Code build prompt → Claude Sonnet 4.6 (max 8000 tok, system prompt 4k) → Parse+Lint (regex wycina FAQ/„Modele"+ JSON safety) → Resolve term_id → POST save
- [x] Generator `tmp/generate-n8n-workflows.py` (gitignored JSON — klucze inline). Docelowo: klucze do n8n credentials + czysty JSON do `workflows/`
- [x] Pilot Voyah + Voyah FREE zweryfikowany (3 iteracje promptu)
- [x] Batch ~175 (45 marek + ~130 modeli), `tmp/batch-hub-parallel.sh` z xargs -P3, szacowany czas 60 min, koszt ~$25
- [x] Po batch: coverage 45/47 make + 246/284 serie (z 124/275 wieczór 23-04 — retry zadziałał na ~120 modeli). Pending: 2 marki + 38 modeli bez wiki_body (pewnie świeżo dodane lub failed)

**2c — Schema.org + llms.txt (pending):**
- [x] **Vehicle Schema + BreadcrumbList** na single listings (Car + BreadcrumbList JSON-LD w `class-asiaauto-single.php::renderMeta()` wp_head, Schema #1 z `render()` usunięta 2026-04-24 jako duplikat). OfferShippingDetails — pending, nice-to-have.
- [x] **llms.txt** (122 linie, top 20 marek + top 30 modeli + 7-krokowy proces + kontakt + AI hints) i **llms-full.txt** (667 linii / 48 KB, 47 marek z opisami + wszystkie modele). Deploy 2026-04-24. Generator `tmp/build-llms-full.php`. Patrz memory `reference_aeo_llms_files.md`.
- [x] **Prompt caching n8n** — wdrożone w v0.31.12 (memory `project_hub_pipeline_fix_2026_04_24.md`). Koszt €0,060/hub.
- [ ] OfferShippingDetails w Car schema na single listing (uzupełnienie do gap vs west-motors)
- [ ] Schema #2 (`renderMeta()`) wzbogacić o pola które miała Schema #1: `vehicleEngine` (KW), `vehicleTransmission`, `driveWheelConfiguration`, `color`, `itemCondition` — usunięte przy dedup 2026-04-24, można dograć

### Krok 3 — Google Ads v2 (równolegle z Krokiem 2, po podpięciu API)
- [x] **Google Ads API podpięte** (Basic Access od 2026-04-23, konto Prima-Auto 9506068500 direct, v21). Patrz memory `reference_google_ads_api_client.md`.
- [x] **3 ENABLED kampanie** (Brand 10/Topic 30/SKAG 60 zł/dz, suma 100 zł/dz). 37 grup SKAG, 262 KW, 37 RSA. Patrz memory `reference_google_seo_stack.md` i `project_ads_campaign_structure.md`.
- [x] **Negatywy** — 503 BROAD historyczne + 14 PHRASE gapiowe per Topic/SKAG (opinie/test/recenzja/parametry/specyfikacja/wymiary/premiera/youtube itd.).
- [x] **Architektura SKAG → KI** (2026-04-24): 37 per-model grup → 1 grupa „Długi ogon" z `{KeyWord}` H1 + per-KW finalUrl. Patrz memory `project_ads_ki_architecture.md`.
- [ ] Import konwersji GA4 → Ads (`click_phone`/`click_whatsapp`/`generate_lead`) — pending user-side w UI Ads (Conversions → Import from GA4). Czas propagacji 9h pierwszy raz.
- [ ] n8n workflow: eksport feedu produktowego do Google Merchant Center (decyzja architektoniczna pending)
- [ ] Po 7 dniach: search terms review w GAQL → dosypywać KW/negs

### Zależności i kolejność
```
Ruslan OK → Krok 1 (migracja DB+importer) → Krok 2a (templates) ─┐
                                          → Krok 2b (n8n content)┤→ uruchomienie huby
                                          → Krok 3 (Ads v2)      ┘ równolegle z 2
```

### Ryzyka
- ~~**Bez Kroku 1 Krok 3 jest bez sensu**~~ DONE — Krok 1 zrobiony 2026-04-23, Ads regenerowane pod nowe slugi
- ~~**Krok 2a bez Kroku 1**~~ DONE — kolejność dotrzymana
- ~~**Google Ads API developer_token**~~ DONE — Basic Access 2026-04-23, limit 15k ops/dobę

---

## ZADANIE 13 — Sesja SEO/AEO post-Elementor (2026-04-24) ✅ DONE

Pełen audyt + AEO wdrożenia po przełączeniu na motyw primaauto2026. Patrz memory `project_seo_aeo_session_2026_04_24.md`.

### Wykonane (8 punktów)
- [x] PSI mediana z 3 runów mobile + 1 desktop. Wniosek: regres mobile lab nie jest realnym problemem (desktop 99/0,6s, real users mają błyskawicznie). CrUX field brak (origin <28d).
- [x] GSC indeksacja audyt: 1/10 → 5/10 PASS (z 23-04 wieczór). Brand `primaauto` SERP #4. 4 huby (Chery/Voyah/byd-Seal/informacje) w cache crawl history Google, czekają na pełny index.
- [x] DataForSEO SERP baseline ($0,06 / 17 KW) — primaauto vs west-motors mapping. JSON `tmp/dfs-serp-2026-04-24.json`.
- [x] Internal linking audit: nic do zmiany. 3 ścieżki home/menu/footer do `/marki/`, stamtąd 47/47 marek.
- [x] llms.txt rozbudowane 36 → 122 linii. llms-full.txt nowe (667 linii / 48 KB). Generator `tmp/build-llms-full.php`.
- [x] Numer +48 605 335 559 (prywatny Janka) wywalony z 3 miejsc: llms.txt, header.php nowego motywu, fallback w wizardzie. Zastąpiony firmowym 721 730 507.
- [x] Author archive disclosure: `/?author=ID` + `/author/<login>/` → 301 → home + `is_author()` noindex + `author_link` filter zwraca home_url. Eliminuje login disclosure 9 userów.
- [x] Schema duplikat 2× `@type=Car` na single listing fix: `class-asiaauto-single.php:40` `echo $this->schema($d)` usunięty (Schema #2 z wp_head wystarczy + ma BreadcrumbList). Wynik: 2 JSON-LD zamiast 3.

### Pliki utworzone/zmodyfikowane
- Plugin: `class-asiaauto-seo.php` (+blockAuthorArchive, +filterAuthorLink, +filterRobots is_author), `class-asiaauto-single.php:40` (schema dedup), `class-asiaauto-order-wizard.php:452` (numer 605→721)
- Theme: `themes/primaauto2026/header.php:4-6` (numer 605→721 + use shortcode)
- Domain root: `llms.txt`, `llms-full.txt`
- Repo: `tmp/build-llms-full.php`, `tmp/dfs-serp-2026-04-24.json`, `tmp/psi-after-theme-2026-04-24*/`

### Pozostałe pending z dzisiejszego audytu
- [ ] Hub aa-hub vs aa-serie różnica mobile + horizontal scroll (memory `project_hub_layout_issues.md` częściowo nieaktualne — padding fix już wdrożony w v0.31.8). Wymaga screenshotu.
- [ ] 4 CSS chain blocking scalenie motywu (header+base+footer+hub w 1 plik) — kosmetyka pod mobile lab PSI, real user nie odczuje. Robić jako ostatnie gdy motyw się ustabilizuje.
- [ ] Schema #2 wzbogacić o vehicleEngine KW + transmission + drive + color + itemCondition (utracone przy dedup 2026-04-24).
- [ ] OfferShippingDetails w Car schema (gap vs west-motors).

---

## ZADANIE 11 — Strony frontowe marek + widoki hubów ✅ DONE (0.31.5, 2026-04-23)

Zrealizowane jako PHP templates w child theme `asiaauto/` (nie Elementor — pragmatyka: theme był pusty, PHP szybsze niż konfiguracja Theme Buildera). Szczegóły w ZADANIE 12 Krok 2a.

---

## GRUPA 9.5 — performance ✅ DONE (0.30.10)

- [x] Zidentyfikowano: 3× COUNT na postmeta (~600ms) + JOINy w `renderPricePreview` (~400ms)
- [x] Transient `asiaauto_listing_counts` (10min TTL), invalidate po `ajaxBulkRecalc`
- [x] Trash TTL 30d → 7d (2534 listings w koszu się samooczyści szybciej)

---

## ZADANIE 6 — Filtr miast + aktualizacja marek (NOWE)

> Status: **w planowaniu**. Realizacja w nowym wątku po zamknięciu aktualnej sesji.

### Kontekst biznesowy

Prima Auto ma ludzi na miejscu w południowych Chinach (Guangdong, Fujian, Guangxi, Hainan).
Mogą fizycznie sprawdzać i doglądać auta tylko w wybranych miastach. Import ogłoszeń powinien
być ograniczony do tych lokalizacji — żeby klient widział tylko auta, które faktycznie można
zweryfikować na miejscu.

Jednocześnie klient prześle zaktualizowaną listę marek — obecne orphaned termy (Li Auto, NIO,
Volvo) mogą wrócić lub zostać usunięte.

### Lista miast od klienta

| Region | Miasto (PL) | Miasto (ZH) |
|---|---|---|
| Guangdong | Guangzhou | 广州 |
| Guangdong | Shenzhen | 深圳 |
| Guangdong | Foshan | 佛山 |
| Guangdong | Dongguan | 东莞 |
| Fujian | Xiamen | 厦门 |
| Fujian | Fuzhou | 福州 |
| Guangxi | Beihai | 北海 |
| Guangxi | Nanning | 南宁 |
| Hainan | Haikou | 海口 |
| Hainan | Sanya | 三亚 |

> Uwaga: klient podał też regiony (Fujian, Guangxi, Hainan) — interpretowane jako prowincje,
> z których wybrano konkretne miasta. Filtr dotyczy miast, nie prowincji.

### Podzadania — Krok A: weryfikacja dostępności ogłoszeń ✅ DONE (0.30.10)

- [x] Sprawdzone: API `getFilters()` NIE ma filtra `city` — filtr tylko po stronie PHP
- [x] Scan 80 stron dongchedi (1600 ofert), pole `city` = chińskie znaki (广州, 深圳...)
- [x] Zmapowane wszystkie miasta z 4 prowincji z co najmniej 1 ofertą
- [x] Lista finalna: **31 miast** (15 Guangdong + 6 Fujian + 8 Guangxi + 2 Hainan)
- [x] Nadgorliwość Ruslana skorygowana — dodane m.in. 惠州 (19 ofert), 泉州 (9), 南平 (8), 柳州 (5)

### Podzadania — Krok B: filtr w panelu admina ✅ DONE (0.30.10)

- [x] Opcja `city_filter_enabled` (bool) + `city_filter_cities` (array `[{zh,pl,province}]`) w `asiaauto_import_config[source]`
- [x] UI w zakładce "Filtry": toggle + przycisk "Konfiguruj miasta" otwierający modal
- [x] Modal: 4 sekcje prowincji z checkboxami, per-prowincja "wszystkie/żadne", sekcja "Dodaj miasto" (ZH + PL + prowincja), licznik zaznaczonych
- [x] Filtr w `AsiaAuto_Importer::isAllowedByConfig()` — skip oferty której `city` nie ma na liście `zh`

### Podzadania — Krok C: aktualizacja marek ✅ DONE (0.30.12)

- [x] Klient wybrał 66 marek z panelu — zrzuty ekranu z panelu admina
- [x] Orphaned termy (Li Auto, NIO, Volvo, Lynk & Co) — zostają, część wróci z importem
- [x] Brakujące marki dodane jako terminy taksonomii przez sync

### Podzadania — Krok D: re-import (po finalizacji filtrów) ✅ DONE (0.30.12)

- [x] Filtr miast przetestowany i zatwierdzony przez klienta (31 miast)
- [x] Backup bazy przed purge (`mysqldump` listings + postmeta + options do `~/backups/primaauto/pre-purge-20260417-1524.sql`, 445MB)
- [x] Purge: 2905 listings → trash (marki OR miasta poza listą, rezerwacje chronione)
- [x] Bulk-import przez `diag/bulk-import-by-brand.php`: reverse-order pages, MAX_PAGES=50, probe [50,40,30,20,10,5,2,1], parametr API `mark=X` (nie `brand=`)
- [x] Fix-missing-images: 45/45 (15 OK, 30 TRASH ghost-offers)
- [x] Cron bug fix: `add_action('asiaauto_sync_changes')` + `cron_schedules` filter — sync faktycznie działa (change_id 2868663 → 2870663 po pierwszym handler fire)

### Cena — zmiana filtru

- Klient obniżył `price_from` 120000 → 75000 CNY (2026-04-17 ~16:10); v4 bulk-import wczytał świeży config, zmiana obowiązuje dla cron syncu.

### Zależności i uwagi

- Filtr miast ma być domyślnie **wyłączony** (toggle OFF) — obecne ogłoszenia nie znikają od razu
- Toggle OFF = sync działa jak dotychczas (bez filtra geograficznego)
- Toggle ON = sync pobiera tylko ogłoszenia z wybranych miast
- Krok D dopiero po: Krok A (weryfikacja) + Krok B (filtr gotowy) + Krok C (marki potwierdzone)

---

## ZADANIE 8 — n8n pipeline opisów marek i modeli (NOWE)

> Status: **w planowaniu**. Baza: workflow Victorini (`workflows/VICTORINI PRODUCT DESC TECHNISTONE V4.json`, 33 nody, multi-agent LangChain, V4 = dopracowany).

### Kontekst biznesowy

Generacja długich opisów SEO dla **66 marek** (taksonomia `make`) + **~150 modeli** (taksonomia `serie`). Zastąpienie obecnych krótkich tekstów z B2 SEO (10 marek + 75 modeli) bogatszą treścią z inventory snippets (wstawki aktualnych ofert między paragrafami). **Publikacja od razu** (bez manual review), zabezpieczona deterministycznym fact-check + anti-spam guard. Po stabilizacji — adaptacja wzorca do bloga (ZADANIE 16) i bazy wiedzy (ZADANIE 15).

### Podzadania — Krok A: segmenty EU (prerequisite)

- [ ] Klasyfikacja 66 marek + ~150 modeli do segmentów EU: B, C, D, SUV B, SUV C/D, Premium, Van, Pickup
- [ ] Hybryda: algorytm (długość/szerokość/cena z Dongchedi) → propozycja → ręczna akceptacja (Ruslan/Jan)
- [ ] Mapowanie zapisane w term_meta `_asiaauto_eu_segment` + dublet w Google Sheet
- [ ] **Blokuje dalsze kroki** — brand guidelines odwołują się do segmentu

### Podzadania — Krok B: custom REST endpoints w asiaauto-sync

- [ ] `/wp-json/asiaauto/v1/latest-by-make/{slug}?n=8` — 8 najnowszych listings (ID, tytuł, cena PL, obrazek, permalink)
- [ ] `/wp-json/asiaauto/v1/latest-by-serie/{slug}?n=8` — analogicznie dla modelu
- [ ] `/wp-json/asiaauto/v1/facts-for-make/{slug}` — Fact Block: liczba modeli, zakres cen PL, top 3 modele, segment EU, zakres lat
- [ ] `/wp-json/asiaauto/v1/facts-for-serie/{slug}` — moc, przyspieszenie, zasięg EV (min/max/median), liczba ogłoszeń, zakres cen
- [ ] `/wp-json/asiaauto/v1/desc-queue` — lista termów kwalifikujących się do generacji (reguły invalidation)

### Podzadania — Krok C: klon workflow Victorini

- [ ] Kopia → `workflows/primaauto-brand-desc-v1.json`
- [ ] Podmiana endpointów WC Products → `/wp-json/wp/v2/make/{id}` + `/wp-json/wp/v2/serie/{id}` (term `description` jako target, nie post_content)
- [ ] Dual-LLM (GPT-4o + Gemini) → **Claude Sonnet 4.6** (treść) + **Claude Haiku 4.5** (meta description)
- [ ] Prompt caching na system prompt + Brand Guidelines (oszczędność 2–3× przy 216 generacjach)
- [ ] Node „Fetch Inventory Snippet" — wołanie endpointów z Kroku B, sklejanie HTML bloku wstawianego między paragrafami w Final Assembly

### Podzadania — Krok D: fact-check walidator (deterministyczny)

- [ ] JavaScript node „Fact Block Validator" po każdym agencie generującym treść
- [ ] Regex po liczbach w outpucie → porównanie z Fact Block → tolerancja ±1%
- [ ] Named entity check: tekst marki nie zawiera innych marek poza sekcją porównania (whitelist z segment EU)
- [ ] FAIL → retry max 2× z dopiskiem „Użyłeś liczb spoza DANE, popraw"
- [ ] Po 2 nieudanych próbach → `status=failed` + error message

### Podzadania — Krok E: anti-spam lint (Google E-E-A-T)

- [ ] Lista „AI tells" w regex (kopia Victorini + rozszerzenia: „Warto wspomnieć", „Bez wątpienia", „W dzisiejszym dynamicznym świecie", „Niezrównana jakość")
- [ ] Wykrycie → regeneracja (1 retry)
- [ ] Meta `_asiaauto_desc_author = "Zespół Prima Auto"` + `_asiaauto_desc_updated_ui` renderowane widocznie na archive page
- [ ] Uniqueness seed (hash marki) → różnicowanie kolejności sekcji i punktu startu narracji
- [ ] Obowiązkowe wstawki E-E-A-T w prompcie („Z naszego doświadczenia z importem X…", „Klienci Prima Auto najczęściej pytają o…")

### Podzadania — Krok F: status tracking + admin UI

- [ ] Meta fields na termie:
  - `_asiaauto_desc_status` (pending | generating | published | failed | skipped)
  - `_asiaauto_desc_generated_at`, `_asiaauto_desc_prompt_version`
  - `_asiaauto_desc_inventory_hash`, `_asiaauto_desc_facts_hash`
  - `_asiaauto_desc_backup`, `_asiaauto_desc_error`
- [ ] Meta box na edit term screen (make/serie): status badge, ostatnia generacja, przyciski Wygeneruj/Regeneruj/Przywróć backup/Zablokuj/Odblokuj
- [ ] Kolumna „Opis AI" w taxonomy list + bulk actions (zakolejkuj / zablokuj / odblokuj)
- [ ] Dashboard widget „Pipeline opisów" z licznikami per status
- [ ] Globalna opcja `asiaauto_desc_daily_quota` (domyślnie 20) — ochrona przed runaway

### Podzadania — Krok G: Brand Guidelines

- [ ] Google Sheet „PrimaAuto Brand Guidelines" — kolumny: make_slug, tone, USP, target, zakazane_zwroty, segment_eu, referencyjny_eu_model, flagship_models
- [ ] Wypełnienie 66 marek (research + decyzje Ruslan)

### Podzadania — Krok H: iteracja + batch

- [ ] Dry-run 10 sztuk (5 marek + 5 modeli) → publikacja
- [ ] Spot-check Jan/Ruslan w 24h, backup w `_desc_backup` umożliwia rollback
- [ ] Kalibracja promptów na bazie obserwacji
- [ ] Pełny batch marek (~1h) + batch modeli (~2h)
- [ ] Cron `asiaauto_desc_pipeline_hourly` — quota 20/dzień, invalidation: prompt_version bump, inventory_hash change (7d cooldown), facts_hash change, segment_eu change, kwartalny pełny rerun

### Kolejność wdrożenia (2026-04-21)

1. **Faza 1 — marki** (66 termów `make`): Krok A → B → C → D → E → F → G → H dry-run 5 marek → spot-check → batch 66
2. **Faza 2 — modele** (~150 termów `serie`): po stabilizacji marek. Adaptacja workflow (mniej paragrafów, węższy fact block), batch ~150

### Zależności i uwagi

- **Prerequisite Krok A:** bez segmentacji EU brand guidelines są kalekie
- **Koszt LLM:** ~$5–15 pełny batch (Sonnet 4.6 + prompt caching), reruny tańsze dzięki cache
- **Safety valve:** `_desc_backup` pozwala rollback każdego termu
- **Replacement B2 SEO:** obecne krótkie teksty zostają jako lead paragraph (pierwsze 2–3 zdania), długi AI-content jako rozwinięcie
- **Synergia z ZADANIEM 10:** GSC invalidation trigger — spadek pozycji >20%/tydz → flag `_desc_status=pending`

---

## ZADANIE 10 — API integracje: GSC + GA4 + Google Ads + Meta (NOWE)

> Status: **w planowaniu**. Setup OAuth i tokenów na **osobnym wątku** (Jan ma dostęp do Google Cloud Console + Meta Business Manager).

### Kontekst biznesowy

Podłączenie 4 zewnętrznych API: **GSC** (monitoring organic), **GA4** (attribution + funnel), **Google Ads** (performance + conversion upload), **Meta Marketing** (FB/IG kampanie). Cel read-first: mierzenie → input do iteracji (ZADANIE 8 invalidation trigger, SEO roadmap ZADANIE 18). Write operations na dalszych etapach (Q2+ po 3 miesiącach iteracji).

### Priorytet TERAZ — aplikacje o tokeny (timer 2–4 tyg biegnie równolegle)

- [ ] **Google Ads Developer Token** — aplikacja w Google Cloud Console (1–4 tyg akceptacji)
- [ ] **Meta App Review** (Business Manager → Advanced Access) — developers.facebook.com (1–2 tyg akceptacji)

### Podzadania — Krok A: GSC (najszybszy ROI)

- [ ] OAuth 2.0 credentials w GCP, scope `webmasters.readonly`
- [ ] Tokeny w `~/.bash_profile`: `GSC_CLIENT_ID`, `GSC_CLIENT_SECRET`, `GSC_REFRESH_TOKEN`
- [ ] n8n credentials + workflow „GSC weekly report" — top 20 queries/pages, CTR, pozycja, Δ tydzień do tygodnia
- [ ] Output → Google Sheet + alert Slack/mail

### Podzadania — Krok B: GA4 readonly

- [ ] Service Account w GCP z dostępem do GA4 property, scope `analytics.readonly`
- [ ] Token w `~/.bash_profile`: `GA4_SERVICE_ACCOUNT_JSON` (ścieżka), `GA4_PROPERTY_ID`
- [ ] n8n workflow „GA4 weekly KPI" — sesje per źródło, konwersje, funnel rezerwacje→umowa

### Podzadania — Krok C: integracja z ZADANIEM 8

- [ ] Job „GSC → desc-queue" (n8n, raz/tydz) — detekcja stron ze spadkiem pozycji >20% → flag `_asiaauto_desc_status=pending` → regeneracja w następnym cyklu pipeline
- [ ] Detekcja nowych queries do top 20 → wpis do brand guidelines (nowy keyword do uwzględnienia w prompcie)

### Podzadania — Krok D: Google Ads API (po tokenie)

- [ ] OAuth + Developer Token + Manager Account (MCC) setup
- [ ] Tokeny: `GADS_DEVELOPER_TOKEN`, `GADS_REFRESH_TOKEN`, `GADS_MCC_ID`, `GADS_CUSTOMER_ID`
- [ ] Raporty readonly: kampanie, keywords, CPL, CPC, CTR per kampania
- [ ] **Offline conversion upload:** rezerwacja auta → webhook → Google Ads conversion (label `reservation`) — bidder optymalizuje pod realne rezerwacje, nie clicks

### Podzadania — Krok E: Meta Marketing API (po App Review)

- [ ] OAuth + Advanced Access permissions
- [ ] Token w `~/.bash_profile`: `META_ACCESS_TOKEN`, `META_AD_ACCOUNT_ID`
- [ ] Raporty readonly: kampanie, audiences, creative performance
- [ ] Custom audience „odwiedzili parking (ZADANIE 12) ale nie zalogowani" → remarketing

### Podzadania — Krok F: skonsolidowany dashboard

- [ ] Workflow n8n łączący GSC + GA4 + Ads + Meta
- [ ] Export do Google Sheet / Looker Studio
- [ ] KPI: CPL per source, ROAS, funnel conversion rate, organic traffic growth MoM

### Zależności i uwagi

- **Prerequisite:** osobny wątek na setup OAuth + tokeny (Jan ma Google Cloud Console)
- **Tokeny:** wszystkie w `~/.bash_profile` na Elarze, NIC w repo (wzorzec identyczny jak MCP_TOKEN)
- **Consent Mode v2** — wymagany w EU od 2024; Prima Auto musi mieć poprawnie skonfigurowany cookie banner, inaczej dane GA4 niepełne
- **Rate limits:** GSC 50k/dzień OK, GA4 1250 tokens/h/property OK, Ads 15k ops/dzień (basic access), Meta 200 calls/h
- **NIE robimy na starcie:** automated bidding, auto-kreacje, bulk-edycja kampanii — dopiero po 3 mies stabilnego readonly trackingu
- **Status Prima Auto aktywności kampanii:** do potwierdzenia — GA4 już działa w GTM? Ads/Meta aktywne z budżetem czy planowane? (zmienia priorytety)

---

## Backlog (niski priorytet)

- [ ] **Plan A — fix race condition w sync** (reaktywne, trigger: gdy znów pojawią się duplikaty). Zastąpić transient lock w `class-asiaauto-sync.php:52-58` przez MySQL `GET_LOCK('asiaauto_sync_dongchedi', 0)`. Bez TTL, auto-release na disconnect. ADR: `docs/decyzje/2026-04-22-dedup-i-optymalizacja-bazy.md`.
- [ ] **Plan D — prewencja bloatu bazy**:
  - [ ] `class-asiaauto-media.php`: ustawiać `post_parent = $listing_id` przy `wp_insert_attachment` (bez tego każdy trashowany listing zostawia 10-15 sierot)
  - [ ] `asiaauto_daily_cleanup` cron: kasować na stałe trashed listings starsze niż 30 dni (teraz tylko drafts→trash, nigdy nie kasuje)
- [ ] SKILL/CLAUDE.md: zasada „MCP tylko gdy lokalnie nie da się" — MCP http round-trip marnuje tokeny, jeśli uruchamiamy Claude Code na Elarze mającej bezpośredni dostęp do plików i `wp` CLI. Dotyczy: `read_file`, `list_dir`, `query_db` (zastąpić `wp db query`), `options` (zastąpić `wp option get`), `stats` (własny `wp eval`). MCP sens zachowuje tylko dla zewnętrznych projektów bez local shell (Claude.ai web).
- [ ] Krok 4 manual editor — metabox extra_prep (18 zakładek)
- [ ] Email HTML templates (maile są plain text)
- [ ] Homepage + Contact CSS → pliki zewnętrzne (z inline)
- [ ] Archive/taxonomy pages dla marek (B5 — duże zadanie, osobny projekt)
- [ ] Orphaned terms (Li Auto, Volvo, NIO, Lynk &amp; Co — count=0, czekają na nową listę marek od klienta)

---

## Zrealizowane (archiwum)

- [x] **Audyt widoczności/SEO 2026-07-07** — GSC+GA4+Ads+DB; kliki +114% (1 563→3 347), Organic = kanał #1. Raport `tmp/seo-audyt-2026-07-07.md`. Rekomendacje: #1 homepage „importer" ✅ (commit 4ad8c08, v0.33.15), #2 striking-distance ✅, #3 huby bez opisu ✅.
- [x] **#2 striking-distance — title/meta „cena" (2026-07-07)** — 4 huby (monjaro/z9-gt-dm-i/yu7/shark-6) na format „{Model} cena w Polsce 2026 — od X PLN | Prima-Auto" + `skip_regen=1` + re-index. leopard-7 pominięty (już zrobiony pilot). Log: `docs/seo/striking-distance-2026-07-07.md`, backup `~/backups/primaauto/2026-07-07/termmeta-pre-striking-distance.sql`. Otwarte: linkowanie wewnętrzne do tych hubów.
- [x] **#3 huby bez opisu — wiki+FAQ+lead (2026-07-07)** — 14 modeli (byd-e7, changan-qiyuan-a06, dajiangjun-ev-pickup, es, ford-bronco, gx, haishi-07-dm, haval-h6l, honda-s7, lieshou, li-i6, li-l7→5739, song-ultra-ev, z7t), `rework=v1-2026-07-07`, content `tmp/content/*`.
- [x] Pending 0: bump wersji 0.29.0-wip → 0.30.7 (2026-04-16)
- [x] Pending 1: pipeline USD-centric — `calculateFromCifUsd()`, `BREAKDOWN_VERSION=2` (2026-04-16)
- [x] ZADANIE 2: załączniki PDF do umowy (renderAttachment1/2), token bezpieczeństwa, nr umowy w tytule przelewu (0.30.8, 2026-04-17)
- [x] ZADANIE 3: maile statusów, etykiety "depozyt zabezpieczający" (2026-04-16)
- [x] ZADANIE 4: smoke test E2E — flow zamówień, PDF, maile, statusy (2026-04-17)
- [x] ZADANIE 5: rework workflow zamówień — model agencyjny, nowe statusy, wizard, panel admina (0.30.7, 2026-04-16)
- [x] B2 SEO: meta/OG/title dla single i inventory, Schema.org, term meta opisów, 10 marek + 75 modeli, llms.txt (0.30.9, 2026-04-17)
- [x] Panel klienta `/klient/` — shortcode, logout, auto-redirect (2026-04-16)
- [x] Fix ghost-crona `asiaauto_daily_cleanup` — handler w bootstrapie + jednorazowy cleanup (trash 5470 → 3559, delete 2077, drafts→trash 166) (0.30.13, 2026-04-20)
- [x] Dedup 36 par listingów + optymalizacja DB 640 MB → 141 MB (−78%): revisions, trashed listings, orphan attachments; `WP_POST_REVISIONS=3` (2026-04-22) — ADR: `docs/decyzje/2026-04-22-dedup-i-optymalizacja-bazy.md`
- [x] **SEO hub rework — rollout cena/AEO (2026-05-30/31).** Lead AEO answer-first + H1 „+ cena w Polsce i import z Chin" (Tier A) + wiki 7 H2 (dane z DB) + FAQPage 5 Q per hub; mechanizmy per-term `_asiaauto_{lead,h1_suffix,pl_availability,seo_rework=v1-2026-05-30}`. **~78 hubów** (pilot Leopard 5 → batch 1+2 → rozdzielone → normalna kolejka count≥18 → start ogona count 12-17: chińskie VW Teramont/Talagon/Tharu). PRAWDZIWOŚĆ per model: import_only / import_only-niuans / salon_available / forward (web-recheck dla marek obecnych w PL: BYD/VW/Chery/Leapmotor/MG itd.). Metoda: `docs/seo/hub-rework-method-2026-05-30.md`, triage: `docs/seo/hub-rework-preflight-triage-2026-05-30.md`, narzędzia: `tmp/deploy-hub.php` + `tmp/recon-hub.php` + `tmp/gsc-hub.py`. Pomiar GSC efektu ≈2026-06-09.
- [x] **Dekontaminacja taksonomii (2026-05-30/31)** — split brand-level mislabeled listings do właściwych termów (make był poprawny, serie błędna): Haval H6/H5↔Hongqi (Hongqi H5=102, H6=12), GAC M8↔AITO M8(43), Avatr 07↔WEY 07(17), Geely Galaxy L6/L7↔Li Auto(42/31), VW Jetta↔Lavida(8), Nissan N7↔Denza N7(3). Porządki: Galaxy A7 CJK cleanup + make unifikacja, Geely Galaxy L6/L7 dedup (6587→3399, 6589→3401), 46 Geely Preface→6558(151). Wszystkie URL-e 200, redirecty 301 dla skasowanych slugów. Backupy `~/backups/primaauto/2026-05-30/`. ⚠️ panelowy `duplicate-serie-terms` Napraw NIE używać hurtem (canonical heuristic odwrotny dla kontaminacji).
- [ ] **SEO hub rework — ogon count 12-17 (w toku, kolejna sesja)** — start zrobiony (3 chińskie VW); pozostałe wg count w memory `project_session_2026_05_30_hub_rework_pilot` (Tank 700, Jetour Dashing, XPeng G6/G9/X9/P7, Leapmotor C16/B10, Nissan N6/Qashqai, Volvo S90, Haval Big Dog, BYD Atto 2/Han EV/Sealion 7, Deepal S07, Mazda CX-5, Voyah FREE, Chery Fulwin A9L, Denza Z9 DM-i). SPECJALNE: Denza Z9 GT = Tier B ochronny; Galaxy Starship 8 / Galaxy E5 = dedup; GAC S7 pre-flight + `duplicate-serie-terms` 37 grup audyt canonical-safety + make „Galaxy"(6579) scalić z Geely? + CJK w `stm_car_location` (nazwy miast) systemowy fix translacji
