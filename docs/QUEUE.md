# Kolejka zadań — Prima Auto

> Aktualizacja: 2026-07-12 (roadmapa: dopisane pomysły T-201 kanał YouTube + T-202 rozbudowa /zamow/ trust)

---

## ⭐ ROADMAPA FUNKCJI — pomysły Janka na rozwój (produkt/UX)

> Pomysły Janka na rozwój platformy, zbierane od 2026-06-11. Wszystkie **bez terminu, najpierw spec/brainstorm przed buildem** (priorytet per-task niżej). **Source of truth = TEN plik** (`_ops` zwinięty 2026-06-16 → polityka per-projekt v0.14; numery T-NNN zachowane historycznie z dawnego `_ops/BACKLOG.md`). **To jest „roadmapa pomysłów na rozwój" — gdy pytam o roadmapę funkcji, pokazuj TĘ sekcję, nie listę zadań SEO/Ads niżej.**

- [ ] **T-114 — Parkowanie aut (ulubione / „koszyk")** — zalogowany user zapisuje listingi do własnej listy „schowanych" aut do przeglądania. Wymusza logowanie/rejestrację. Ikonka w headerze obok kontaktu. Per-user storage (user meta / CPT), spina się z UX rejestracji + mailingiem.
- [ ] **T-115 — Porównywarka aut** — „dodaj do porównania" + tabela porównawcza specyfikacji. Ikonka w headerze obok parkingu/kontaktu. Bazuje na danych spec listingu (Dongchedi); stateless (cookie/localStorage) lub per-user jak T-114.
- [ ] **T-121 — Płatność PayU za depozyt** — klient wpłaca zwrotny depozyt (model pośrednictwa) online przez PayU zamiast przelewu ręcznego. Spina się z lifecyclem zamówienia + typem umowy (T-113). PayU sandbox już ograny (`~/secrets/payu/`, damianchen+digit) → przeniesienie na prod. **Dotyka strefy kruchej `class-asiaauto-order.php`** (statusy/rezerwacja) → osobny świadomy projekt, najpierw spec. Decyzja prawna: depozyt zwrotny przez PayU a regulamin śude/zwroty.
- [ ] **T-113 — Umowa o finansowanie / leasing jako alternatywa dla umowy pośrednictwa** — klient generuje/wybiera typ umowy. **Dotyka strefy kruchej `class-asiaauto-contract.php` + wizard** → osobny świadomy projekt, najpierw spec. Wymaga wzorca umowy o finansowanie od Ruslana + decyzji prawnej (leasing/kredyt/pożyczka?).
- [ ] **T-189 — Kalkulator finansowania na stronie oferty** — widget na single listingu (i ew. w wizardzie `/zamow/`): klient wpisuje wpłatę własną / okres / typ (leasing / kredyt / pożyczka) i widzi szacunkową ratę miesięczną od ceny katalogowej auta. Cel: obniżenie bariery „całość z góry" + lead gen („zapytaj o finansowanie" → kontakt/wizard). Spina się z T-113 (umowa o finansowanie — kalkulator to frontowa zajawka tej samej oferty). Do ustalenia przed spec: (1) czy Ruslan ma partnera finansującego i jego realne stawki (oprocentowanie/prowizja) — bez tego rata tylko „orientacyjna" z widocznym disclaimerem; (2) leasing dot. firm — czy pokazywać netto/brutto przełącznik; (3) czysty frontend JS (stateless, bez strefy kruchej) vs zapis parametrów do leada. Dodane 2026-07-03.
- [ ] **T-116 — Zaawansowana wyszukiwarka fasetowa** — filtrowanie po atrybutach technicznych: liczba miejsc, przyspieszenie 0-100, długość (zakres mm) itd. Cel: precyzyjny dobór auta po parametrach. Wymaga audytu jakie pola spec mamy w meta z Dongchedi + ew. dociągnięcie brakujących; range-slidery.
- [ ] **T-187 — Single (mobile): pasek kafli „inne ogłoszenia tego samego modelu" + „Pokaż wszystkie"** — na pojedynczym ogłoszeniu, **tylko na telefonie**, NAD istniejącym modułem „Inne modele {marka}" (pills do hubów — `class-asiaauto-single.php::relatedModels()`, render linia ~94) dodać pasek kafli z fotami pozostałych ogłoszeń **tej samej `serie`** (wykluczając bieżący post) + CTA „Pokaż wszystkie" → hub modelu (`get_term_link(serie)`). **Reuse istniejących komponentów:** kafel `AsiaAuto_Inventory::renderCard()` (`class-asiaauto-inventory.php:926`, public static) + pasek `renderListingsCompact()` z `[asiaauto_hub_listings]` (`class-asiaauto-brand-hub.php:277`, nagłówek+CTA, filtr make+serie). **Do dorobienia:** (1) `post__not_in`/`exclude` w `renderListingsCompact` (dziś brak); (2) wywołanie z single z jawnym make/serie (shortcode czyta query vary, których na single nie ma) — publiczny wrapper lub mały własny `WP_Query`+`renderCard()`; (3) wrapper `--mobile` + media query (wzorzec `aa-info--mobile`); (4) CSS — pasek hubowy używa klas `aa-home__latest`/`aa-home__section-*`, single ładuje tylko `asiaauto-single.css` → doładować style albo reuse `aa-card` z gridem w CSS single; (5) edge: 0 kafli po wykluczeniu → nie renderować. **Dotyka `class-asiaauto-single.php` = strefa ZAWSZE PYTAJ** ([[feedback_no_edit_homepage_without_ok]]) → najpierw spec/OK.
- [ ] **T-188 — Zapisane wyszukiwania + alerty intent-based (newsletter)** — dla zalogowanych/zarejestrowanych: zapis nazwanego zestawu filtrów (np. „czarny Leopard 5") i mail gdy pojawi się nowe pasujące auto. **Rdzeń = alerty oparte na intencji, nie masowy newsletter** (klient sam definiuje czego szuka → wyższy CTR). **Model danych:** CPT `asiaauto_saved_search` (post_author=user), meta `_params` (serializowany schema filtrów = mapa `class-asiaauto-inventory.php:509-521`: marka/model/paliwo/nadwozie/rocznik/kolor/naped/cena_min/max/sort — jeden source of truth, ten sam co URL `/samochody/`), `_alert_enabled`, `_alert_freq`, `_last_notified_gmt`, `_label`. **UX:** przycisk „🔔 Zapisz to wyszukiwanie" na `/samochody/` obok licznika; niezalogowany → lekki modal „podaj e-mail" (= **wpięcie newslettera/leada, double opt-in**, pełne konto opcjonalne, nie blokować konwersji); panel „Moje wyszukiwania" w koncie `asiaauto_customer` (dokładka do panelu zamówień, spina się z Parkowaniem T-114). **Silnik dopasowań = newsletter:** WP-cron → dla każdego saved_search z alertem uruchom **istniejący query builder `handleListings`** (zero nowej logiki) → listingi `date > _last_notified_gmt` (tylko nowe) → mail z **kaflami `AsiaAuto_Inventory::renderCard()`** + link + opt-out. **Newsletter 2 poziomy:** (a) alert per-wyszukiwanie [faza 1], (b) ogólny newsletter nowości [faza 2, osobny double opt-in]. **Wysyłka — decyzja:** wysyła SYSTEM do usera który sam się zapisał (funkcja produktowa, ≠ ad-hoc mail do klienta z globalnego NIGDY); start = WP-cron + transactional SMTP `noreply@primaauto`, przy skali → ESP (Brevo/Mailgun, unsubscribe/deliverability). **Reuse:** schema paramów + query builder + renderCard + konto/panel customer = nowy kod głównie CPT+UI+cron-matcher+szablon maila+warstwa zgód. **Decyzje przed spec:** (1) próg wejścia: sam e-mail [rekom] vs pełna rejestracja; (2) zakres fazy 1: tylko alerty [rekom] vs +ogólny newsletter; (3) wysyłka: SMTP [rekom] vs ESP. **Prawne:** zgoda marketingowa + double opt-in + opt-out/NAP/DSA w mailu (podpiąć politykę prywatności z `/informacje/`). Spina się z T-114 (parking, wspólny header „Moje konto").
- [ ] **T-152 — Wystawianie ogłoszeń na Otomoto** — nowy kanał dystrybucji: automatyczny eksport ofert z CPT `listings` do Otomoto (feed XML/API dla dealerów). Cel: zasięg poza własną stroną. Do rozpoznania: format integracji (CSV/XML vs API), mapowanie pól, polityka cenowa na portalu, model rozliczeń.
- [ ] **T-162 — Sekcja kontentowa/blogowa (baza wiedzy)** — własny dział treści: rankingi (np. „Top 20 SUV-ów w Chinach 2026"), porównania z europejskim premium (AITO M9 vs Mercedes GLS / BMW X7 itd.), poradniki importu. Cel: **AEO/SEO** (treści cytowalne przez LLM-y + long-tail porównawczy), autorytet, ruch top-funnel → konwersja na listingi. Do ustalenia: CPT/kategorie vs natywne WP posts, szablon rankingu (tabela + linki do hubów), template „X vs Y" (side-by-side z danych Dongchedi + dane konkurenta EU), Schema (Article/ItemList), pipeline (ręczny vs n8n). Spina się z porównywarką T-115.
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
