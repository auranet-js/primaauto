# T-243 — pakiet wykonawczy: co otwieramy i kiedy

> Przygotowane 2026-08-07, zanim Ruslan odpisał na instrukcję dostępów.
> Kontekst: `docs/sesje/2026-08-07-T243-social-dystrybucja.md` · Spec: `docs/roadmapa/T-243-social-dystrybucja-tresci.md`
> Decyzje otwarte: `docs/decyzje/2026-08-07-T243-otwarte-punkty-decyzyjne.md`
>
> **Jak używać:** znajdź gałąź pasującą do tego, co odpisał Ruslan, skopiuj prompt z jej sekcji
> do nowego wątku. Prompty są samodzielne — każdy mówi, co przeczytać, co zbudować i co znaczy
> „zrobione". Jeden podtask = jeden wątek.

---

## 0. Najważniejsze: pięć z ośmiu faz nie czeka na nikogo

| Faza | Zależy od Ruslana? | h |
|---|---|---:|
| a — dostępy i konta | **TAK, blokująca dla części e** | 6–9 |
| **b — kolejka i panel WP** | nie | 14–18 |
| **c — materiały z sesji** | nie (materiały z 23.07 są na Dysku) | 13–17 |
| **d — generator treści + bramki** | nie | 13–17 |
| e — dystrybucja | **TAK** (FB/IG po a; YT po migracji; TikTok po audycie) | 21–27 |
| **f — rejestr i wyniki** | nie | 11–15 |
| **g — rotacja i rytm** | nie | 9–13 |
| **h — czujnik kanału FB** | nie | 2–3 |

**Bez ani jednej odpowiedzi da się zbudować 62–83 h roboty.** Cisza po stronie klienta nie jest
powodem, żeby stać — jest powodem, żeby zacząć od b, c, d.

**Rekomendowany start przy braku odpowiedzi:** h (2–3 h, natychmiastowa wartość) → d (generator,
bo z niego wypadają gotowce dla Andrzeja na żywy kanał) → c → b.
⚠️ Kolejność d przed b jest przedmiotem otwartej decyzji **D-2** — potwierdź z Jankiem, zanim
ruszysz.

---

## 1. Ścieżka domyślna — startuje od razu

### 1a. Prompt: faza h — czujnik kanału FB + baseline kosztu

```
Robimy T-243h dla primaauto — czujnik ruchu z Facebooka. Przeczytaj:
- docs/roadmapa/T-243-social-dystrybucja-tresci.md (faza h)
- memory: reference_stara_strona_fb_zywy_kanal.md

Dwie rzeczy:
1. Skrypt sprawdzający raz w tygodniu ruch Organic Social w GA4 (property 534017542),
   zapisujący szereg do pliku w data/ i alarmujący przez ~/bin/send-to-jan, gdy tygodniowa
   liczba sesji albo zdarzeń kluczowych spadnie poniżej progu względem średniej z ostatnich
   8 tygodni. Wzorzec: scripts/kb/kb-news-daily.sh (kill-switch + flock + log) albo
   ~/.claude/cron/run-job.sh. Cron dodać po backupie (incydenty 12.07 i 22.07 — używaj
   ~/bin/cron-install).
2. Jednorazowy pomiar: faktyczny wydatek kampanii [RMKT] Dynamic Remarketing — Model-huby
   za ostatnie 90 dni przez scripts/gads_client.py, konto 9506068500. Policz koszt zdarzenia
   kluczowego i zestaw z Facebookiem (1310 sesji / 30 zdarzeń, koszt zero). Wynik dopisz
   do docs/decyzje/2026-08-07-T243-otwarte-punkty-decyzyjne.md w polu Decyzja przy D-3.

Zrobione, gdy: cron chodzi, alarm przetestowany sztucznym progiem, liczba kosztu kontaktu
zapisana w dokumencie.
```

### 1b. Prompt: faza d — generator treści + bramki

```
Robimy T-243d dla primaauto — generator treści na kanały społecznościowe. Przeczytaj:
- docs/roadmapa/T-243-social-dystrybucja-tresci.md (moduł copy_generate.py, sekcja 4)
- scripts/kb/kb_lib.py i scripts/kb/news_daily.py (wzorzec, reużywamy call_model/lint/proofread)
- ściągę METADANE z Dysku (Prima Auto/sesje/2026-07-23/filmy/) — ona jest specyfikacją promptu

Buduj scripts/social/copy_generate.py:
- wejście: listing_id + manifest mediów; fakty ciągnij z REST-a hubowego (ten sam, który karmi
  generator hubów) — cena, wersja, moc, zasięg, liczba sztuk, URL oferty, URL huba
- wyjście per platforma (facebook, instagram, youtube_long, youtube_short, tiktok): tytuł,
  post krótki, opis długi, CTA, hashtagi, tagi
- wzorzec redakcyjny ze ściągi: tytuł = marka + model + CENA + hak, pierwsza linia opisu =
  deep-link do konkretnej oferty, stały blok kontaktowy na końcu
- generowanie przez claude -p w abonamencie (kb_lib.call_model), NIE przez API Anthropic

Cztery bramki, każda blokująca:
1. FACT-CHECK — wyciągnij wszystkie liczby z tekstu i porównaj z faktami z REST-a; rozbieżność
   = odrzucenie i regeneracja, po trzeciej próbie stan "blad". To jest najważniejsza część
   tego zadania (D6 w specu) — post z błędną ceną auta za 230 tys. zł jest nieodwracalny.
2. LINT — kb_lib.lint_text() + zakaz nazw dealerów konkurencji
   (memory: feedback_no_competitor_dealer_names)
3. LIMITY — długości per platforma, liczba hashtagów, obecność deep-linku w pierwszej linii
4. CUDZYSŁOWY — kb_lib.normalize_quotes()

Plus tryb --gotowiec: wypisuje gotowy do wklejenia komplet dla JEDNEJ oferty na JEDEN kanał,
do ręcznej publikacji przez Andrzeja na starej Stronie FB (nie mamy tam API — patrz memory
reference_stara_strona_fb_zywy_kanal).

Zrobione, gdy: --dry-run na trzech autach z sesji 23.07 daje komplet przechodzący wszystkie
bramki, a podstawiony tekst z zawyżoną ceną zostaje odrzucony.
```

### 1c. Prompt: faza c — materiały z sesji

```
Robimy T-243c dla primaauto — wykrywanie i przygotowanie materiałów z sesji. Przeczytaj:
- docs/roadmapa/T-243-social-dystrybucja-tresci.md (moduły sesje_watch.py i media_prep.py)
- memory: feedback_obrazy_webp_nazwa_i_opisy_pod_seo

scripts/social/sesje_watch.py:
- Drive API (~/bin/google-access-token), folder sesje = 1m-8F99y79rIyAhb3zTViOvvmZgCCe87l
- struktura: sesje/RRRR-MM-DD/<Model>/<Kolor>/ ; kadr rozpoznawaj z videoMediaMetadata
  (width/height), NIE z nazwy pliku — nazwy są niekonsekwentne
- nazwa katalogu → marka i model przez plugins/asiaauto-sync/data/brand-mapping-v6.1.php
  (uwaga: w katalogu jest literówka "Exceed-VX", marka to Exeed) → term serie → konkretne oferty
  (reużyj match_our_model() z scripts/kb/news_daily.py)
- stan w scripts/social/state/sesje-seen.json, idempotentnie
- mail do Janka przez send-to-jan: co przyszło

scripts/social/media_prep.py:
- ffmpeg: normalizacja kadrów 16:9 i 9:16, docięcie do limitów platform, H.264/AAC
- magick: zdjęcia → WebP, nazwy i alty pod frazy (nigdy cover-123.webp; alt pełnym zdaniem)
- miniatury: klatka albo zdjęcie + cena w rogu (drawtext)
- wyjście: uploads/asiaauto/social/<sesja>/<model>/ + manifest JSON

Pierwszy przebieg = materiały z 23.07 (Leopard 5 czarny i niebieski, Exeed VX, Jetour T2) —
to jest wchłonięty T-241.

Zrobione, gdy: dwa biegi z rzędu nie dublują pozycji, a manifest zawiera oba kadry i komplet
zdjęć dla każdego auta.
```

### 1d. Prompt: faza b — kolejka i panel WP

```
Robimy T-243b dla primaauto — kolejka publikacji i ekran w panelu. Przeczytaj:
- docs/roadmapa/T-243-social-dystrybucja-tresci.md (sekcja 4, "Kolejka")
- plugins/asiaauto-sync/includes/class-asiaauto-admin-che168-import.php (wzorzec ekranu)
- memory: feedback_shared_panel_gate_all_view_additions, feedback_no_edit_homepage_without_ok

Tabela wp7j_asiaauto_social_queue: id, listing_id, term_id, sesja, platforma, kadr, stan,
publikuj_o, tresc (JSON), media (JSON), external_id, blad, utworzono, zmieniono.
Stany: szkic → zaakceptowany → wyslany → opublikowany, plus odrzucony i blad.

Nowa klasa class-asiaauto-admin-social.php, OSOBNE submenu (nie doklejamy do istniejących
ekranów). Przycisk "do kolejki" na liście ofert i na karcie oferty. Widok tygodnia:
co, kiedy, na jakim kanale, w jakim stanie; edycja treści, akceptacja, usunięcie.

Reguły przeciw nadmiarowi, konfigurowalne, wartości startowe: maks. 2 pozycje dziennie
per kanał, min. 3 dni odstępu między postami o tym samym modelu, cooldown 8 tygodni
na powtórkę tego samego auta, maks. 1 pozycja na sztukę w tygodniu.

⚠️ Strefa ZAWSZE PYTAJ: nie dotykaj istniejących ekranów admina ani renderu oferty.
Dokładamy addytywnie (memory: feedback_additive_not_fragile_zone).

Zrobione, gdy: Ruslan zaznacza auto, wybiera tydzień i widzi komplet pozycji w kolejce;
próba złamania każdej z reguł kończy się odmową z czytelnym komunikatem.
```

---

## 2. Drzewo gałęzi — po odpowiedziach Ruslana

### Gałąź A — Meta odblokowana (token + IG)

**Wyzwalacz:** Ruslan przypisał Stronę i konto IG do użytkownika systemowego `Auranet API`.

**Kolejność:** najpierw regeneracja tokenu, potem dopiero cokolwiek innego.

```
T-243a dla primaauto — domknięcie dostępów Meta. Przeczytaj:
- docs/roadmapa/T-243a-instrukcja-dostepy-dla-ruslana.md (krok 2 i 3)
- ~/secrets/meta/README.md

1. KOPIA tokenu przed czymkolwiek: cp ~/secrets/meta/tokens/primaauto-portfolio-ruslan.txt
   ~/secrets/meta/tokens/primaauto-portfolio-ruslan.txt.backup-RRRR-MM-DD
2. Nowy token System Usera ze scope: pages_manage_posts, pages_read_engagement, pages_show_list,
   read_insights, instagram_basic, instagram_content_publish, instagram_manage_insights
   (generuje Janek w UI — przygotuj mu dokładną listę kroków)
3. WERYFIKACJA PO ZMIANIE, zanim uznasz to za zrobione:
   - scripts/build-meta-vehicle-feed.php nadal generuje feed
   - meta_call.sh --project primaauto-2026 /me działa
   - odczyt Strony i podpiętego konta IG działa
4. Zaktualizuj accounts.json (page_id jeśli się zmienił, instagram id)

⚠️ Regeneracja unieważnia stary token. Kampanie i feed katalogu chodzą na tym samym —
weryfikacja z punktu 3 jest obowiązkowa, nie opcjonalna.

Zrobione, gdy: token ma nowe scope, feed i Ads działają, a próbny odczyt Strony i IG przechodzi.
```

Po tym otwiera się **e (FB + IG)** — 11–14 h. Prompt w sekcji 3.

**Wariant A2 — `prima_auto.pl` nie należy do nich.** Zakładamy nowe konto firmowe, przepinamy
link w stopce (`class-asiaauto-contact.php`). Doliczyć ~1 h. Uprzedź Janka, że Instagram
robił 4 sesje na 90 dni — budujemy kanał od zera, nie odzyskujemy.

### Gałąź B — YouTube

| Odpowiedź | Co robimy | h |
|---|---|---:|
| **Migracja na Brand Account udana**, Janek dodany jako właściciel | `e(YT)` przez API: `videos.insert` ze `status.publishAt`, miniatura, playlisty. ⚠️ 1600 jednostek za wgranie przy puli 10 000 = **maks. 6/dobę** | 5–6 |
| **Nieudana albo nie próbowali** | Zostaje publikacja przez Chrome na sesji Janka, jak 24.07. System przygotowuje komplet, człowiek wrzuca. Faza e traci ścieżkę API, zyskuje tryb `--gotowiec` dla YT | 2–3 |

⚠️ W obu wariantach: **nie ruszamy tytułów ani miniatur już opublikowanych filmów w trakcie
fali** (reset modelu CTR ucina zasięg) i **nie dotykamy starych 11 Shortsów** z tytułami
„11/13 июля 2026 г." — Janek wyraźnie zabronił.

### Gałąź C — TikTok

| Odpowiedź | Co robimy |
|---|---|
| **Konto prowadzą sami**, przeszli na Business + Business Center | Rejestracja aplikacji w TikTok for Developers, wniosek o Content Posting API. **Audyt trwa tygodnie** — w międzyczasie budujemy ścieżkę do szkiców. 5–7 h + czekanie |
| **Prowadzi ktoś z zewnątrz** | ⛔ **STOP, nie wchodzimy w cudzy kanał.** To rozmowa biznesowa Janka z Ruslanem, nie zadanie techniczne. Zgłoś i czekaj |
| **Nie mają dostępu do konta** | Odradzaj zakładanie nowego: 127 filmów i 319 obserwujących zostaje po tamtej stronie. Najpierw próba odzyskania konta przez TikToka |

### Gałąź D — stare portfolio Meta wróciło (mało prawdopodobne)

**Wyzwalacz:** Ruslan widzi `145818221430407` na `business.facebook.com`.

To zmienia wszystko i **ma pierwszeństwo przed każdą inną fazą.** Kolejność:
1. Natychmiast zabezpieczyć: sprawdzić listę Osób, Partnerów i Użytkowników systemowych,
   usunąć wszystko nieznane, wymusić 2FA.
2. Odzyskać Stronę `106659808679582` i przypiąć do nowego portfolio albo odwrotnie.
3. Wygenerować nowe tokeny dla naszych System Userów.
4. Dopiero potem wracać do T-243 — ale wtedy **stara Strona z 16,7 tys. obserwujących wchodzi
   do systemu jako pełnoprawny kanał z API**, a nie jako ręczne wklejanie przez Andrzeja.
   Faza e zyskuje najcenniejszy kanał w całym zestawieniu.

⚠️ Zanim cokolwiek klikniecie — przeczytaj `memory: project_meta_account_hijack_2026_06_11`.
Wektorem wejścia był obcy podmiot na warstwie portfolio, nie złamane hasło.

### Gałąź E — cisza albo odpowiedzi wymijające

Nie czekamy. Idziemy ścieżką domyślną z sekcji 1 (h → d → c → b). Po dwóch tygodniach bez
odpowiedzi: Janek przypomina Ruslanowi, ale **osobno**, nie zatrzymując budowy.

Jedyne, czego bez Ruslana nie zrobimy, to faza e — czyli automatyczne wypchnięcie. Wszystko,
co ją poprzedza, powstaje niezależnie, a `--gotowiec` z fazy d daje wartość natychmiast,
bo Andrzej publikuje ręcznie na kanale, który już dowozi kontakty.

---

## 3. Prompt: faza e — dystrybucja (po gałęzi A)

```
Robimy T-243e dla primaauto — wypchnięcie pozycji z kolejki jako ZAPLANOWANYCH. Przeczytaj:
- docs/roadmapa/T-243-social-dystrybucja-tresci.md (moduł dystrybutor.py)
- ~/secrets/meta/README.md i meta_call.sh

scripts/social/dystrybutor.py — bierze pozycje w stanie "zaakceptowany" z terminem w przyszłości:
- Facebook Page: POST /{page_id}/videos oraz /feed ze scheduled_publish_time
- Instagram: POST /{ig_id}/media (REELS) → media_publish
- YouTube: videos.insert ze status.publishAt + miniatura + playlista (tylko gałąź B1)
- TikTok: Content Posting API, do szkiców jeśli audyt nieukończony

Wymagania twarde:
- IDEMPOTENCJA po external_id — powtórny bieg nie może zdublować publikacji
- --dry-run OBOWIĄZKOWY przed pierwszym biegiem na żywo (memory: feedback_dry_run_przed_batchem)
- sprawdzenie limitów API przed batchem (memory: feedback_check_api_limits_before_batch);
  YouTube: 1600 jednostek za wgranie, pula 10 000 = maks. 6/dobę
- błąd jednej pozycji nie zatrzymuje reszty; stan "blad" + treść błędu w tabeli
- pierwsza publikacja na żywo: JEDNA pozycja na JEDEN kanał, doba obserwacji, dopiero potem reszta

Zrobione, gdy: dry-run przechodzi na komplecie z sesji 23.07, dwa biegi nie dublują wpisów,
a pierwsza pozycja pojawia się w kalendarzu Business Suite jako zaplanowana i daje się tam
edytować.
```

---

## 4. Zasady wspólne — obowiązują w każdym podtasku

- **Jeden podtask = jeden wątek.** Nie łącz faz.
- **Krok 0 zawsze:** zweryfikuj stan na serwerze, zanim uwierzysz dokumentacji
  (memory: `feedback_verify_backlog_against_server`).
- **Dry-run przed każdym batchem**, bez wyjątków.
- **Nie rozszerzaj zakresu** — nazw klas, CPT, meta i shortcodów `asiaauto_*` nie ruszamy
  (memory: `feedback_no_scope_creep`).
- **Addytywnie, nie w strefie kruchej** — nowe metody i pliki obok istniejących
  (memory: `feedback_additive_not_fragile_zone`).
- **Po domknięciu podtaska:** wpis w `docs/sesje/`, aktualizacja `docs/QUEUE.md`, godziny
  do `docs/kosztorys/dane/postep.json` + `python3 build_postep.py --deploy`, commit i push.
- **Wycena T-243 ma świadomy bufor** (decyzja Janka 07.08) — nie „naprawiaj" jej w dół.
