# T-243 — Dystrybucja treści na kanały społecznościowe (parasol)

> Status: **spec gotowy do wykonania** (2026-08-07) · Rozmiar: XL — rozbity na 8 pozycji
> Godziny realnie: **89–119 h** · Rynkowo: **225–300 h** · Zużyte dotąd: **4 h** (rozpoznanie i spec, 07.08)
> Pierwsza fala (a+b+c+d + Facebook/Instagram z e): **57–75 h**
> Wchłania: **T-241** (procedura „nowe materiały z sesji") jako pierwszy przebieg fazy c
> Styka się z: T-201a (assety wideo), T-205 (Meta), T-207 (dashboard marketingowy), T-240 (huby indywidualne)

---

## 1. Po co — i czym to NIE jest

**Produktem jest dystrybucja, nie treść.** Ustalenie Janka z 2026-08-07: materiał ma natywnie istnieć
na każdej platformie, bo dopiero wtedy jest czym promować. Film wgrany na YouTube można wypromować
kampanią wideo; post na Facebooku można podbić; rolka na TikToku może iść w Spark Ads. Post, którego
nie ma, nie jest kandydatem na reklamę.

Dziś ten obieg jest ręczny w całości. Sesja z 23.07 dała 64 zdjęcia i 8 filmów; opublikowanie ich
na kanale zajęło osobną sesję roboczą 24.07, a ściągę „METADANE — jak opisywać te filmy (v2).txt"
(tytuły, opisy, hashtagi, przypisanie kadrów do platform) trzeba było napisać ręcznie. Na Facebooku
posty powstają ręcznie do dziś.

**Czym to nie jest:** to nie jest generator treści marketingowej „z niczego" ani automat wybierający,
o czym pisać. Wybór auta należy do Ruslana. System odpowiada za to, co po wyborze: przygotowanie
mediów, napisanie copy pod każdą platformę, rozłożenie w czasie, wypchnięcie jako zaplanowane
i pokazanie w jednym miejscu, co poszło, a co padło.

## 2. Decyzje (zapadły 2026-08-07 w rozmowie z Jankiem)

- **D1 — Mechanizm: pipeline modułów, nie agent.** Model wołany w jednym kroku (copy), reszta
  deterministyczna. Uzasadnienie: publikacja jest nieodwracalna i zewnętrzna (post z błędną ceną
  auta za 230 tys. zł idzie pod marką Ruslana), ograniczenia są twardymi bramkami a nie instrukcją
  (zakaz AI-wideo konkretnego egzemplarza, zakaz nazw dealerów konkurencji, maskowanie chińskich
  tablic), 5 z 6 kroków to czysta mechanika, a konto siedzi na limicie NPROC/PMEM w LVE. Wzorzec
  dowieziony w `scripts/kb/` — 38 aktualności, 7 rankingów, 74 hasła.
- **D2 — Wybór auta należy do człowieka.** Nie budujemy automatu selekcjonującego z katalogu.
  Przy 82–226 nowych ofertach na dobę (pomiar 18.07–07.08) każda reguła automatyczna i tak
  wymagałaby akceptacji, a Ruslan chce wskazywać konkretne sztuki.
- **D3 — Punkt wejścia w panelu WP przy ofercie.** Ruslan pracuje tam codziennie przy zamówieniach;
  zero nowych logowań, auto jednoznacznie zidentyfikowane wraz z ceną, zdjęciami i linkiem.
- **D4 — Planowanie natywnie na platformach, rejestr u nas.** Posty wychodzą jako **zaplanowane**,
  więc Ruslan zarządza nimi w Business Suite i Studio, gdzie i tak jest. Nasz rejestr istnieje,
  bo Janek nie ma bezpośredniego dostępu do kanałów i musi widzieć, co poszło, a Ruslan musi
  widzieć całość, żeby nie zdublować tematu.
- **D5 — Dashboard statyczny, wzorcem kosztorysu.** `build.py --deploy` na zahaszowany URL,
  bez logowania, bez frameworków — tak jak `docs/kosztorys/` i strona postępu prac.
- **D6 — Fact-check liczbowy wchodzi od razu, nie w wersji 2.** T-208 nazywa brak fact-checku
  największym ryzykiem pipeline'u contentowego. Post rozchodzi się szerzej i szybciej niż hub,
  a jego korekta po publikacji jest w praktyce niemożliwa.
- **D7 — Zdjęcia z sesji mają pierwszeństwo przed zdjęciami z importu.** Zdjęcia z Dongchedi
  i che168 wymagają maskowania tablic i znaków wodnych; wchodzą dopiero w fazie rotacji.
- **D8 — Kolejka nigdy nie stoi pusta.** W okresach bez nowych materiałów rotujemy opublikowane
  wcześniej oferty z cooldownem i nowym ujęciem.

## 3. Stan faktyczny (zweryfikowany 2026-08-07)

### Co już działa i czego nie budujemy drugi raz

| Element | Stan |
|---|---|
| Silnik generowania treści | `scripts/kb/kb_lib.py` — `call_model()` przez `claude -p` w abonamencie (zero kosztów API, decyzja Janka 21.07), `lint_text()` z 9 zakazanymi frazami, `proofread()`, `normalize_quotes()`, `wp()`, `load_state`/`save_state`, `send_mail()` |
| Wzorzec pipeline'u | `news_daily.py` (809 linii): radar → selekcja z dedupem → generowanie → walidacja → publikacja → mail. `ranking_*.py` (~1800 linii). `news_qa.py` — walidator po publikacji, pisze tylko gdy coś odstaje |
| Rytm cronowy | `run-job.sh` (PAUSE, flock, log START/END/SKIP, alert mailowy z dławikiem 6 h), `kb-news-daily.sh` (kill-switch `state/DISABLED`) |
| Wzorzec ekranu w adminie | `class-asiaauto-admin-che168-import.php`, `-manual-import.php`, `-che168-browse.php` — `add_submenu_page` w osobnej klasie per ekran |
| Wzorzec publikacji statycznej | `docs/kosztorys/build.py --deploy`, `build_postep.py --deploy` → zahaszowany katalog na auratest.pl |
| Fakty z bazy do promptu | REST `class-asiaauto-rest-hub.php` — karmi generator hubów prawdziwymi danymi |
| Narzędzia mediów | `ffmpeg 7.0.2` static (x264, drawtext, libwebp), `magick`, `yt-dlp` |
| Dostęp do Dysku | `~/secrets/google/tokens.json` ma pełny scope `drive`; `~/bin/google-access-token` odświeża access token |

### Źródło materiałów z sesji

Dysk Google → `Prima Auto/sesje/` (folder `1m-8F99y79rIyAhb3zTViOvvmZgCCe87l`).
Struktura potwierdzona przez API 2026-08-07:

```
sesje/2026-07-23/
  Exceed-VX/Granatowy/    20 zdjęć SCH024xx + 2 filmy (720x1280 pion, 1280x720 poziom)
  Jetour-T2/Srebrny/      15 zdjęć + 2 filmy (720x1280, 1280x720)
  Leopard5/Czarny/        16 zdjęć + 2 filmy (1280x720, 1080x1920)
  Leopard5/Niebieski/     13 zdjęć + 2 filmy (720x1280, 1280x720)
  filmy/                  METADANE — jak opisywać te filmy (v2).txt
```

Metadane wideo w Drive oddają `width`/`height`, więc **kadr rozpoznajemy z API, nie z nazwy pliku** —
nazwy są niekonsekwentne (`leopard5-black-pion.mp4` obok `BYD Leopard 5 (niebieski) – … – pion 9x16.mp4`).
Literówka w nazwie katalogu (`Exceed-VX` zamiast `Exeed`) potwierdza, że mapowanie na markę musi iść
przez normalizację i mapowanie marek, nie przez dosłowną nazwę folderu.

Ściąga METADANE definiuje obowiązujący wzorzec i to ona jest specyfikacją promptu:
tytuł = marka + model + **cena w złotych** + hak („dostępny od ręki w Rzeszowie");
pierwsza linia opisu = deep-link do konkretnej oferty (nie do strony głównej);
16:9 → YouTube i Facebook, 9:16 → Shorts, Reels, Rolki, TikTok;
stały blok kontaktowy na końcu każdego opisu; hashtagi per model.

### Katalog

2 313 ofert `publish`, 184 `draft`, 1 987 `trash`. Napływ 82–226 sztuk na dobę w ostatnich
trzech tygodniach. Rotacja: 48 h draft → 7 dni trash → delete. Sprzedane listingi robią 301
na hub modelu (`class-asiaauto-redirects.php`), więc **linki w postach są bezpieczne** — nie
prowadzą w 404 po sprzedaży auta.

### Kanały — stan i ograniczenia zapisu

| Kanał | Stan | Zapis |
|---|---|---|
| YouTube `@primaauto_com_pl` (`UCsfcUP3uwrDxPI_mDIle8Pw`) | kanał nie jest Brand Account, Janek ma tylko delegację ze Studio | **API niemożliwe** (udokumentowane 24.07). Odczyt działa (`youtube-tokens.json`: `youtube.force-ssl`, `youtube.readonly`, `yt-analytics.readonly`) |
| TikTok `@primaauto.pl` | 127 filmów, 319 obserwujących; **autorstwo nierozstrzygnięte od 14.07** | wymaga konta Business + audytu aplikacji w TikTok for Developers |
| Facebook „Prima-Auto" (`1146829831857839`) | 26 fanów, token System Usera czyta stronę (sprawdzone przez Graph API 2026-08-07) | scope ma dziś `ads_management`, `ads_read`, `business_management` — brakuje `pages_manage_posts` |
| Instagram | **brak konta powiązanego ze stroną** (pole `instagram_business_account` puste) | ścieżka nie istnieje |
| Google Ads (9506068500) / Meta Ads | działają; DG i karuzele pracują na materiałach z sesji | pełna automatyzacja poza kampaniami wideo (tylko UI) |

**To jest najsłabsze ogniwo całości** — nie generowanie treści, tylko możliwość jej wystawienia.
Dlatego faza a jest blokująca i idzie pierwsza.

## 4. Architektura

```
Dysk: sesje/RRRR-MM-DD/<Model>/<Kolor>/      Ruslan w panelu WP: „do kolejki"
            │                                           │
            ▼                                           ▼
   [c1] sesje_watch.py  ───────────────►  [b] KOLEJKA (tabela + ekran WP)
        wykrycie, inwentaryzacja,              tydzień × kanał, stany, limity
        mapowanie na ofertę                        ▲            │
            │                                      │            ▼
            ▼                                      │   [d] copy_generate.py
   [c2] media_prep.py                              │       claude -p + fakty z REST
        ffmpeg / magick                            │       bramki: fakty, lint, limity
            │                                      │            │
            └────────► manifest mediów ────────────┴────────────┘
                                    │
                                    ▼
                          [e] dystrybutor.py
              FB · IG · YouTube · TikTok — jako ZAPLANOWANE
                                    │
                                    ▼
                         [f] rejestr_build.py
        statyczny HTML: kalendarz tygodnia × kanał, stany, wyniki
```

Kod w `scripts/social/` (wzorem `scripts/kb/`), stan w `scripts/social/state/`,
ekran w `plugins/asiaauto-sync/includes/class-asiaauto-admin-social.php`.

### Moduły i odpowiedzialności

**`sesje_watch.py`** — jedyne wejście dla materiałów z Dysku. Stan w `state/sesje-seen.json`
(wzorzec `seen-news.json`). Inwentaryzuje folder sesji, rozpoznaje kadr z metadanych wideo,
normalizuje nazwę katalogu na markę i model przez `data/brand-mapping-v6.1.php`, mapuje na term
`serie` i na konkretne oferty (reużycie `match_our_model()` z `news_daily.py`). Wynik: pozycje
kandydackie w kolejce ze stanem „szkic" + mail do Janka.
*Nie robi:* nie generuje treści, nie dotyka mediów, nie publikuje.

**`media_prep.py`** — jedyne miejsce, które dotyka plików binarnych. Pobiera z Dysku, normalizuje
kadry i długości pod limity platform, koduje H.264/AAC, konwertuje zdjęcia na WebP z nazwami
i altami pod frazy (reguła `feedback_obrazy_webp_nazwa_i_opisy_pod_seo`), buduje miniatury
z ceną w rogu (`drawtext`). Wyjście do `wp-content/uploads/asiaauto/social/<sesja>/<model>/`
plus manifest JSON.
*Nie robi:* nie decyduje, co się publikuje.

**`copy_generate.py`** — jedyny krok modelowy. Wejście: fakty z REST-a (cena, wersja, moc, zasięg,
liczba sztuk, URL oferty, URL huba) plus manifest mediów. Wyjście per platforma: tytuł, post krótki,
opis długi, CTA, hashtagi, tagi. Prompt systemowy koduje wzorzec ze ściągi METADANE.
*Bramki przed zapisem, każda blokująca:*
1. **Fakty** — każda liczba wyekstrahowana z tekstu musi mieć odpowiednik w faktach z bazy.
   Rozbieżność = odrzucenie i regeneracja, po trzeciej próbie pozycja idzie do stanu „błąd".
2. **Lint** — `kb_lib.lint_text()` (9 zakazanych fraz) + zakaz nazw dealerów konkurencji
   (`feedback_no_competitor_dealer_names`).
3. **Limity** — długości per platforma, liczba hashtagów, obecność deep-linku w pierwszej linii.
4. **Cudzysłowy** — `normalize_quotes()`.
`proofread()` jako wzbogacenie, nie krok krytyczny.

**Kolejka** — tabela `wp7j_asiaauto_social_queue` + ekran. Kolumny: `id`, `listing_id`, `term_id`,
`sesja`, `platforma`, `kadr`, `stan`, `publikuj_o`, `tresc` (JSON), `media` (JSON), `external_id`,
`blad`, `utworzono`, `zmieniono`. Stany: `szkic` → `zaakceptowany` → `wyslany` → `opublikowany`,
plus `odrzucony` i `blad`.
*Reguły przeciw nadmiarowi (konfigurowalne, wartości startowe):* maks. 2 pozycje dziennie per kanał,
min. 3 dni odstępu między postami o tym samym modelu, cooldown 8 tygodni na powtórkę tego samego auta,
maks. 1 pozycja na sztukę w danym tygodniu.

**`dystrybutor.py`** — wypycha pozycje `zaakceptowany` z terminem w przyszłości jako **zaplanowane**:
Facebook `POST /{page_id}/videos` i `/feed` ze `scheduled_publish_time`; Instagram `POST /{ig_id}/media`
(REELS) → `media_publish`; YouTube `videos.insert` ze `status.publishAt`, miniatura, playlista;
TikTok Content Posting API. Idempotencja po `external_id` — powtórny bieg nie duplikuje.
`--dry-run` obowiązkowy przed każdym pierwszym biegiem (reguła projektu).

**`rejestr_build.py`** — statyczny HTML na zahaszowany URL. Kalendarz tygodnia razy kanał, stan
każdej pozycji, lista błędów. Warstwa wyników: `GET /{post_id}/insights` z Graph API, YouTube
Analytics (token do odczytu już jest), zestawione z ruchem na docelowej ofercie z GA4.

**`rotacja.py`** — gdy w oknie planowania brakuje pozycji, dobiera z puli opublikowanych ofert
z zachowaniem cooldownu, z innym ujęciem i innym hakiem. Zdjęcia z importu przechodzą przez
maskowanie tablic i znaków wodnych.

## 5. Fazy i budżet godzinowy

| Faza | Zakres | Realnie | Rynkowo |
|---|---|---:|---:|
| **T-243a** — Dostępy i konta | Migracja kanału YouTube na Brand Account. Ustalenie z Ruslanem autorstwa TikToka, konto Business, rejestracja aplikacji. Konto IG Business podpięte do strony FB. Regeneracja tokenu System Usera Meta o `pages_manage_posts`, `pages_read_engagement`, `instagram_basic`, `instagram_content_publish`. Weryfikacja `token-debug.sh`. | **6–9 h** | 15–22 h |
| **T-243b** — Kolejka i panel | Tabela + migracja. `class-asiaauto-admin-social.php` jako submenu. Przycisk „do kolejki" na liście i karcie oferty. Widok tygodnia z edycją, akceptacją i usuwaniem. Reguły przeciw nadmiarowi. | **14–18 h** | 35–45 h |
| **T-243c** — Materiały z sesji | `sesje_watch.py` + `media_prep.py`. **Pierwszy przebieg = T-241 na materiałach z 23.07** (Leopard 5 czarny i niebieski, Exeed VX, Jetour T2). | **13–17 h** | 33–43 h |
| **T-243d** — Generator treści | `copy_generate.py`, prompty per platforma, cztery bramki, w tym fact-check liczbowy. | **13–17 h** | 33–43 h |
| **T-243e** — Dystrybucja | FB (11–14 h z obsługą błędów i idempotencją), IG, YouTube (5–6 h, zależne od fazy a), TikTok (5–7 h, zależne od audytu aplikacji). | **21–27 h** | 53–68 h |
| **T-243f** — Rejestr i wyniki | Statyczny HTML + deploy. Warstwa wyników z Graph Insights, YouTube Analytics i GA4. | **11–15 h** | 28–38 h |
| **T-243g** — Rotacja i rytm | `rotacja.py`, maskowanie tablic dla zdjęć importowych, crony przez `run-job.sh`, kill-switch, skille `/sesja` i `/kolejka`. | **9–13 h** | 23–33 h |
| **T-243h** — Czujnik kanału FB + baseline kosztu | Cron tygodniowy na Organic Social w GA4 z alarmem przez `send-to-jan`, gdy ruch się załamie (wzorzec `run-job.sh`). Plus jednorazowy pomiar faktycznego wydatku kampanii RMKT w Google Ads, żeby porównanie „kontakt z Facebooka vs kontakt z remarketingu" miało liczbę, nie przymiotnik. | **2–3 h** | 5–8 h |
| | **Razem** | **89–119 h** | **225–300 h** |

**Pierwsza fala: a + b + c + d + Facebook i Instagram z e = 57–75 h.**
Od tego momentu Ruslan wskazuje auto, a posty na Facebooku i Instagramie planują się same
z materiałów z sesji. YouTube, TikTok, rejestr i rotacja dochodzą po kolei.

**Kolejność wykonania:** a → b → c → d → e(FB, IG) → f → e(YouTube, TikTok) → g.
Faza a jest blokująca dla części e. Fazy b, c, d można prowadzić równolegle do oczekiwania
na audyt aplikacji TikToka. **Fazę h można zrobić kiedykolwiek** — nie zależy od niczego
i nic od niej nie zależy, ale im wcześniej, tym dłuższy szereg pomiarowy.

**Zużyte dotąd: 4 h** — sesja 07.08: rozpoznanie projektu i kanałów, pomiary GA4, forensyka
tokenów Meta i Biblioteki Reklam, spec, instrukcja dostępów dla klienta, rejestr decyzji.

## 6. Strefy kruche

- **`class-asiaauto-admin-*.php`** — dokładamy **nową klasę i nowy ekran**, nie ruszamy istniejących.
  Reguła projektu: gate na wspólnym panelu = osobna strona (`feedback_shared_panel_gate_all_view_additions`).
- **Publikacja jest nieodwracalna.** Każdy moduł wypychający ma `--dry-run` i musi go przejść przed
  pierwszym biegiem na żywo (`feedback_dry_run_przed_batchem`).
- **Limity API** — Graph API i YouTube mają dobowe quoty. Przed każdym batchem sprawdzenie limitu
  (`feedback_check_api_limits_before_batch`). YouTube `videos.insert` kosztuje 1600 jednostek przy
  dobowej puli 10 000 — **maksymalnie 6 wgrań na dobę**, co i tak zgadza się z regułą „publikacja
  po jednej pozycji dziennie, nie paczką".
- **Zdjęcia ofert** — nie kasujemy niczego z `uploads/asiaauto/`; materiały social lądują w osobnym
  podkatalogu `uploads/asiaauto/social/`.
- **Token Meta** — regeneracja System Usera unieważnia stary token. Kampanie i feed katalogu chodzą
  na tym samym tokenie; zmiana scope idzie **po** weryfikacji, że `build-meta-vehicle-feed.php`
  i skrypty Ads dalej działają.

## 7. Czego system NIE robi (świadome granice)

- **Nie generuje wideo AI konkretnego egzemplarza.** Zakaz z T-201: to materiał dowodowy w modelu
  pośrednictwa. AI tylko do treści redakcyjnych.
- **Nie wybiera samodzielnie aut z katalogu.** Wybór należy do Ruslana (D2).
- **Nie publikuje natychmiast.** Wszystko wychodzi jako zaplanowane, z terminem w przyszłości
  i możliwością cofnięcia po stronie platformy.
- **Nie dotyka starych 11 Shortsów** z tytułami „11/13 июля 2026 г." — Janek wyraźnie zabronił.
- **Nie zmienia tytułów ani miniatur już opublikowanych filmów w trakcie fali** — reset modelu CTR
  ucina zasięg (`project_yt_metadane_przebudowa_2026_07_25`).
- **Nie wysyła niczego do klientów Ruslana.** System publikuje na profilach, nie mailuje.

## 8. Ryzyka

1. **Migracja kanału YouTube może się nie powieść.** Kanał powstał 03.12.2025 na koncie, do którego
   Janek ma wyłącznie delegację ze Studio; migracja leży po stronie właściciela konta i ma blokady
   czasowe. Plan awaryjny: YouTube zostaje przy automatyzacji Chrome na sesji Janka — faza e traci
   ok. 5 h pracy nad API, a zyskuje zależność od obecności człowieka.
2. **TikTok Content Posting API wymaga audytu aplikacji.** Bez zatwierdzenia można wrzucać wyłącznie
   do szkiców. Audyt trwa tygodnie i jego wynik nie zależy od nas. Plan awaryjny: publikacja do
   szkiców i ręczne zatwierdzenie przez Ruslana w aplikacji.
3. **Autorstwo TikToka nierozstrzygnięte od 2026-07-14.** 127 filmów ktoś produkuje. Jeśli robi to
   osoba z zewnątrz, wchodzimy jej w kanał — **rozmowa z Ruslanem przed fazą a, nie po niej.**
4. **Zasięg organiczny jest dziś znikomy** — Facebook 26 fanów, TikTok 319 obserwujących. To nie
   podważa sensu (celem jest istnienie materiału jako inwentarza reklamowego), ale znaczy, że
   **miarą powodzenia nie może być zasięg organiczny.** Miara właściwa: koszt kontaktu w kampaniach
   promujących te materiały, zestawiony z kampaniami bez nich.
5. **Regeneracja tokenu Meta może wywrócić działające kampanie i feed katalogu.** Kolejność:
   najpierw kopia zapasowa tokenu, potem regeneracja, potem weryfikacja feedu i skryptów Ads.

## 9. Testy

**Automatyczne**
- Fact-check: podłożyć copy z ceną różną od bazy → pozycja odrzucona i regenerowana.
- Idempotencja dystrybutora: dwa biegi na tej samej pozycji → jeden wpis na platformie.
- Limity kolejki: próba wstawienia trzeciej pozycji tego samego dnia na ten sam kanał → odmowa.
- Cooldown: to samo auto w oknie 8 tygodni → odmowa z komunikatem.
- `sesje_watch` uruchomiony dwa razy na tej samej sesji → brak duplikatów w kolejce.
- Rozpoznanie kadru: plik `1080x1920` i plik `720x1280` klasyfikowane jako pion mimo różnych nazw.

**Półautomatyczne**
- `--dry-run` na pełnym pakiecie sesji 23.07 — przegląd copy i mediów przed pierwszą publikacją.
- Weryfikacja po regeneracji tokenu Meta: `build-meta-vehicle-feed.php` i `gads_client.py` działają.
- Pierwsza publikacja: jedna pozycja na jeden kanał, obserwacja przez dobę, dopiero potem reszta.

## 10. Definicja zrobionego

- Ruslan zaznacza auto w panelu i po kilku minutach ma gotowy komplet postów w kolejce.
- Materiały z nowej sesji na Dysku same trafiają do kolejki jako propozycje, bez pytania.
- Żaden post z liczbą niezgodną z bazą nie wychodzi na zewnątrz.
- Posty wychodzą jako zaplanowane, a Ruslan może je zmienić lub usunąć natywnie na platformie.
- Kolejka nie stoi pusta w okresach bez nowych aut.
- Janek widzi w jednym miejscu, co poszło na każdy kanał, co padło i z jakim skutkiem —
  bez dostępu do kanałów.
- Wiadomo, czy kampanie promujące te materiały mają niższy koszt kontaktu niż kampanie bez nich.
