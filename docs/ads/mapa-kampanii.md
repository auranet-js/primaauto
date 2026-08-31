# Mapa kampanii Google Ads — Prima-Auto

> **Po co ten plik:** żeby recheck konta nie zaczynał się od przekopywania wszystkich kampanii.
> Tu siedzi trwałe: rola każdej kampanii, jej historia i ustalenia. Świeże liczby dowozi
> `python3 scripts/ads-recheck.py` (dodaj `--md`, żeby wkleić tabelę niżej).
>
> **Konto:** `9506068500` (direct access, **nie** pod MCC AURANET — `login-customer-id` = to samo ID)
> **GA4:** property `534017542` · **Ostatni recheck:** 2026-08-31

---

## 1. Tabela kampanii — kto czym jest

| kampania | ID | typ | rola | historia / ustalenia |
|---|---|---|---|---|
| **[Brand] Prima-Auto** | 23779860635 | Search | obrona marki, najtańsze leady | od 22.04, oryginalna trójka kampanii |
| **[Topic] Import z Chin** | 23779860638 | Search | intencje generyczne („import aut z Chin") | od 22.04, oryginalna trójka |
| **[DSA] Import modele z Chin** | 23896725555 | Search DSA | page-feed, długi ogon modeli | rework 12.07 (`dsa-rework-2026-07-12.md`); T-200 rekomendował PAUZĘ 09.07 przy CPA 204 zł; 16.07 feed przestawiony z hubów na najtańszą ofertę per model (`docs/decyzje/2026-07-16-dsa-feed-na-oferty.md`), kampania została włączona. Feed odświeża cron co 3 dni (`scripts/dsa-offer-feed-refresh.py`, lepki — podmiana tylko gdy sztuka zeszła z publish) |
| **[RMKT] Dynamic Remarketing — Model-huby** | 23897599362 | Display | powrót niedoszłych, feed model-hubów | recon + optymalizacja 12.07 (`rmkt-optymalizacja-2026-07-12.md`), konwersje ×2 po zmianach; feed odświeżany tygodniowo (`scripts/refresh-rmkt-feed.sh`) |
| **[DG] Demand Gen — auta z Chin (YouTube)** | 24069066886 | Demand Gen | zasięg wizualny na YouTube | z T-200 „visual-first" (09.07) — teza: auta kupuje się oczami, 3 000 ogłoszeń ze zdjęciami jako paliwo |
| **[VID] Placementy — widzowie konkurencji** | 24060052062 | Video | placementy pod kanałami konkurencji | **reklamy PAUSED 31.08** (0 kontaktów za 444 zł/30 dni, CPC 12,32 zł). Kampania nie przyjmuje mutacji przez API — pauza samej kampanii tylko w panelu. Do 19.08 liczyła YouTube jako konwersje — cele zawężone, patrz 3a |
| **[SKAG-1] Na placu** | 23803851563 | Search | auta fizycznie na placu | PAUSED. Rework 11.07 (2 RSA/grupę, zdjęcia grupowe); recheck 16.07 potwierdził, że **zdjęcia grupowe działają w Search** (`docs/sesje/2026-07-16-rechecki-t195-skag1.md`); T-200 rekomendował pauzę przy 0 konw. / 612 zł |
| **[SKAG-2] W drodze** | 23803851566 | Search | auta w drodze do Polski | **PAUSED 31.08** — 8 tygodni zera przy ~180 zł/tydz. Rework 11.07 wzorcem SKAG-1 (`skag2-rework-plan-2026-07-11.md`, 60 ops); 18 grup modelowych. Przy odmrażaniu: sprawdzić landingi (oferty rotują, jeden już 410) |
| **[SKAG-3] Popularne sprowadzenie** | 23803851569 | Search | modele „pod sprowadzenie" | PAUSED, zero wydatku |
| **[SKAG] Marki-Modele** | 23779860641 | Search | pierwotny SKAG z 22.04 | PAUSED, zastąpiony przez SKAG-1/2/3 |

**Poza kampaniami:** `Campaign #1` (23779355912) — default po założeniu konta, status `REMOVED` (zweryfikowane 19.08).

## 2. Stan na 2026-08-31 (po realokacji)

| kampania | stan | budżet/dz | 7d koszt | 7d konw | 30d koszt | 30d kliki | 30d konw | CPA 30d | GA4 sesje | GA4 phone+wa |
|---|---|---|---|---|---|---|---|---|---|---|
| [Brand] Prima-Auto | ENABLED | 25 zł | 55 zł | 2.0 | 297 zł | 733 | 17.5 | 17 zł | 427 | 13 |
| [DG] Demand Gen — auta z Chin (YouTube) | ENABLED | 45 zł | 130 zł | 3.0 | 591 zł | 2453 | 26.0 | 23 zł | 494 | 17 |
| [DSA] Import modele z Chin | ENABLED | 15 zł | 92 zł | 0.0 | 450 zł | 572 | 4.0 | 112 zł | 172 | 0 |
| [RMKT] Dynamic Remarketing — Model-huby | ENABLED | 17 zł | 110 zł | 2.0 | 504 zł | 1586 | 10.2 | 49 zł | 1363 | 9 |
| [SKAG-1] Na placu | PAUSED | 20 zł | 0 zł | 0.0 | 0 zł | 0 | 0.0 | — zł | 15 | 0 |
| [SKAG-2] W drodze | **PAUSED 31.08** | 25 zł | 122 zł | 0.0 | 748 zł | 424 | 0.0 | — zł | 121 | 0 |
| [SKAG-3] Popularne sprowadzenie | PAUSED | 25 zł | 0 zł | 0.0 | 0 zł | 0 | 0.0 | — zł | 0 | 0 |
| [SKAG] Marki-Modele | PAUSED | 50 zł | 0 zł | 0.0 | 0 zł | 0 | 0.0 | — zł | 0 | 0 |
| [Topic] Import z Chin | ENABLED | 35 zł | 206 zł | 2.0 | 924 zł | 435 | 12.0 | 77 zł | 187 | 11 |
| [VID] Placementy — widzowie konkurencji | ENABLED, **reklamy PAUSED 31.08** | 15 zł | 98 zł | 0.0 | 444 zł | 36 | 6.0 | 74 zł | 0 | 0 |
| **RAZEM** | | | 814 zł | 9.0 | 3958 zł | | 75.7 | 52 zł | | 50 |

**Liczby są sprzed realokacji** — pokazują, na jakiej podstawie zapadły decyzje z 31.08. Suma budżetów
kampanii wyświetlających się = 137 zł/dz, bez zmian wobec 19.08.

### Realokacja 2026-08-31 (decyzja Janka, sesja audytu Ads)

Skrypt: `scripts/gads-realokacja-2026-08-31.py` (validate_only domyślnie, `--apply` wykonuje).

| co | z | na | dlaczego |
|---|---|---|---|
| [SKAG-2] W drodze | ENABLED | **PAUSED** | 748 zł/30 dni przy **zerze konwersji przez 8 tygodni z rzędu**, mimo reworku z 11.07 |
| [VID] Placementy | ENABLED | **reklamy PAUSED** | 444 zł/30 dni, **0 kontaktów**, CTR 0,11%, CPC 12,32 zł |
| [Brand] budżet | 10 zł | **25 zł** | traci **57% wyświetleń przez budżet** przy CPC 0,41 zł i CPA 17 zł — najtańsza skala na koncie |
| [DG] budżet | 20 zł | **45 zł** | CPA spada (tydzień 17–23.08: 14 konwersji za 137 zł = **10 zł**), jedyny nośnik wideo |

**[VID] nie da się zapauzować przez API** — kampania VIDEO zwraca `MUTATE_NOT_ALLOWED` na
`campaigns:mutate` (potwierdzone ponownie 31.08, zgodnie z pomiarem z 25.07). Obejście: zapauzowane
obie reklamy przez `adGroupAds:mutate` (to **działa**), więc kampania nie ma czego wyświetlać i nie
wydaje. Formalnie zostaje `ENABLED` z budżetem 15 zł — **do właściwej pauzy w panelu**.

## 2a. Czy wideo napędza ruch brandowy — zmierzone 31.08

Pytanie brzmiało: czy [VID] nie zwraca się pośrednio, przez wyszukiwania brandowe. **Nie ma na to
dowodu — i pomiar pokazał, że robi to co innego.**

Udział zapytań brandowych w klikach z GSC: **maj 15% → lipiec 4,5% → sierpień 8%**. Brand rośnie
w liczbach bezwzględnych, ale **wolniej niż cała witryna** — gdyby [VID] budowała markę, ten udział
by rósł.

Za to **8.08 brand skoczył z 21 do 477 kliknięć dziennie** i osiadł na trwale ~2× wyżej (35–40/dz.
wobec ~21 wcześniej). Tego samego dnia referale z `youtube.com` skoczyły z 0–6 do 35 sesji. To fala
**organicznych Shortsów**, nie reklama: Exeed VX ma 43 269 wyświetleń, Leopard 5 — 13 607, a kanał
urósł z 3 832 wyświetleń (25.07) do **71 883** (31.08). [VID] w tym samym miesiącu: 32 580 wyświetleń
przy CTR 0,11% i 36 kliknięciach.

**Wniosek: wideo działa i napędza markę — ale organiczne, nie płatne placementy pod konkurencją.**
Dlatego budżet [VID] poszedł do [DG], która nosi te same filmy do ludzi, tylko taniej (CPC 0,24 zł).

Praktyczna konsekwencja: skok popytu brandowego trafił na kampanię [Brand] zdławioną budżetem 10 zł/dz
(57% wyświetleń traconych). Stąd podbicie do 25 zł.

## 3. Rozjazd Ads ↔ GA4 — czytaj, zanim zaraportujesz liczbę leadów

**Ads pokazuje 90,2 konwersji z płatnych za 30 dni. GA4 widzi w tych samych kampaniach 60 zdarzeń `click_phone` + `click_whatsapp`. Ads liczy o 50% więcej.**

| kampania | Ads konw. | GA4 phone+wa | Ads klików | GA4 sesji |
|---|---|---|---|---|
| [Brand] | 30,0 | 26 | 707 | 442 |
| [Topic] | 17,0 | 13 | 494 | 224 |
| [RMKT] | 17,2 | 12 | 1 691 | 1 463 |
| [DG] | 16,0 | 9 | 1 909 | 423 |
| [DSA] | 3,0 | **0** | 1 006 | 331 |
| [SKAG-1/2] | 0 | 0 | 505 | 167 |
| [VID] | 7,0 (same YouTube, od 19.08 będzie 0) | 0 | 20 | 0 |

**Skąd różnica — trzy przyczyny, wszystkie strukturalne, nie do „naprawienia":**

1. **Moment zapisu.** Ads przypisuje konwersję do dnia **kliknięcia** (okno do 30 dni wstecz), GA4 do dnia **zdarzenia**. Przy ruchomym oknie 30-dniowym brzegi się nie pokrywają.
2. **Model atrybucji.** Ads: data-driven, cross-device, z modelowaniem przy braku zgody. GA4 w tym raporcie: session-scope — zdarzenie liczy się kampanii **tej sesji**, więc lead, który wrócił z organica, ląduje w organiku (widać to w skrajnym przypadku DSA: 3 konwersje w Ads, 0 zdarzeń w GA4).
3. **Co jest liczone.** W Ads do kolumny „Konwersje" wchodzą `click_phone` (43) i `click_whatsapp` (40,2). `generate_lead` ma w oknie 30 dni **0**, ale **mechanizm działa** — w 90 dniach zliczył 9 konwersji; po prostu w tym miesiącu formularz nie dowiózł leada z płatnych (GA4 widzi 8 zdarzeń, z tego 7 z direct/organic). `purchase` 0 w Ads, 3 w GA4 — to testy PayU z 10.08, nie sprzedaż.

**Praktyka:**
- **Do decyzji budżetowych używaj Ads** — ranking kampianii jest w obu źródłach ten sam (Brand > Topic ≈ RMKT > DG >> DSA/SKAG), a Ads ma pełne koszty.
- **Do raportu dla Ruslana nie podawaj liczby leadów z Ads** jako faktycznej — realny kontakt telefoniczny/WhatsApp jest bliżej liczby GA4. Bezpieczna formuła: „X kontaktów zmierzonych w analityce, Y konwersji raportowanych przez Google Ads (z modelowaniem)".
- Organic wciąż dowozi najwięcej: 8 813 sesji i 98 zdarzeń kontaktowych w 30 dni — **więcej niż wszystkie kampanie płatne razem** (60).

## 3a. Co liczymy jako konwersję — stan celów (sprawdzone 2026-08-19)

**Ustalenie Janka: konwersją jest telefon, WhatsApp i wypełnienie formularza zamówienia. Nic innego.**
(Dojdzie osobny formularz kontaktowy na `/kontakt/` — wtedy dopisujemy go do tej listy.)
Subskrypcje YouTube i „follow-on views" **nie są konwersją** — wskoczyły same przy uruchomieniu kampanii
wideo i Janek je wyłączył.

**Stan po sprawdzeniu: naprawa trzyma na 6 z 7 wydających kampanii, ale nie na [VID].**

Akcje konwersji na poziomie konta:

| akcja | status | w kolumnie „Konwersje" | 90 dni |
|---|---|---|---|
| `click_phone` | ENABLED, primary | **tak** | 99,3 |
| `click_whatsapp` | ENABLED, primary | **tak** | 102,8 |
| `generate_lead` | ENABLED, primary | **tak** | 9,0 |
| `purchase` | ENABLED | nie | 0 |
| `YouTube channel subscriptions` | ENABLED | **nie** | 7,0 |
| `YouTube follow-on views` | ENABLED | **nie** | 35,0 |
| `close_convert_lead`, `qualify_lead` | HIDDEN | nie | 0 |
| 4 × „Local actions / Działania lokalne" | ENABLED, primary | nie | 0 |

Co realnie wpadło do kolumny „Konwersje" w 30 dniach:

| kampania | kontaktowe (telefon/WhatsApp) | YouTube |
|---|---|---|
| [Brand] | 30,0 | — |
| [Topic] | 17,0 | — |
| [RMKT] | 17,2 | — |
| [DG] | 16,0 | — |
| [DSA] | 3,0 | — |
| **[VID]** | **0** | **7,0** |
| **RAZEM** | **83,2** | **7,0** |

### NAPRAWIONE 2026-08-19 — cele zawężone na wszystkich kampaniach

Wyłączenie akcji na poziomie konta **nie wystarczało** — decyduje zestaw celów przypisany do kampanii,
a cel `biddable` nadpisuje ustawienie akcji. Cele `ENGAGEMENT/YOUTUBE_HOSTED` i
`YOUTUBE_FOLLOW_ON_VIEWS/YOUTUBE_HOSTED` wisiały na **9 z 10 kampanii**. Skutek był widoczny na `[VID]`:
całe 7 „konwersji" tej kampanii to 5 follow-on views + 2 subskrypcje, za 353 zł/30 dni, przy zerze kontaktów.

**Wykonane:** `python3 scripts/ads-zawez-cele.py --apply` — 18 operacji, zdjęte `biddable` z obu celów
YouTube na wszystkich 9 aktywnych kampaniach. Backup stanu sprzed:
`~/backups/primaauto/2026-08-19/ads-campaign-conversion-goals-przed.json`.

Stan po zmianie (zweryfikowany odczytem):

| kampania | cele biddable |
|---|---|
| [DG] Demand Gen | `CONTACT/WEBSITE`, `SUBMIT_LEAD_FORM/WEBSITE` |
| pozostałe 8 | `CONTACT/WEBSITE`, `SUBMIT_LEAD_FORM/WEBSITE`, `PURCHASE/WEBSITE`, `UNKNOWN/GOOGLE_HOSTED` |

**`PURCHASE/WEBSITE` zostawiony świadomie** — to realna transakcja PayU. Dziś 0 konwersji (płatności
wyłączone flagą `asiaauto_payu_enabled=0`), ale gdy ruszą, ma być liczona.

**`UNKNOWN/GOOGLE_HOSTED` został, bo API go nie przyjmuje** — `BAD_RESOURCE_ID`, „'UNKNOWN' part of the
resource name is invalid". To cel-widmo: cztery akcje `GOOGLE_HOSTED` („Local actions", „Clicks to call",
„Działania lokalne") mają `include_in_conversions_metric=False` i **0 konwersji przez całe 90 dni**.
Nieszkodliwy; gdyby kiedyś zaczął cokolwiek zliczać — do zdjęcia ręcznie w panelu.

**Czego oczekiwać:** `[VID]` będzie odtąd raportować **0 konwersji** zamiast 7. To nie regres — kampania
nigdy nie dowoziła kontaktów, tylko zliczała aktywność na YouTubie. Teraz jej wynik jest widoczny wprost.

### Weryfikacja 2026-08-31 — naprawa trzyma, dowód rozstrzygający

Sprawdzone przez porównanie `metrics.all_conversions` (łapie też akcje spoza kolumny „Konwersje")
z `metrics.conversions`, segmentowane po dacie:

| kampania | akcja | all_conversions po 19.08 | z tego w kolumnie „Konwersje" |
|---|---|---|---|
| [DG] | YouTube follow-on views | 8 | **0** |
| [DG] | YouTube channel subscriptions | 1 | **0** |
| [VID] | YouTube follow-on views | 5 | **0** |

Zdarzenia YouTube nadal się zliczają jako `all_conversions` (i tak ma być — to nie jest konwersja,
tylko aktywność), ale **żadne nie wchodzi do kolumny „Konwersje"**. Do kolumny wchodzą wyłącznie
`click_phone`, `click_whatsapp` i `generate_lead`.

**Jedyne zanieczyszczenie: 6 sztuk w [VID] z dni 1–9.08** (5 follow-on views + 1 subskrypcja) —
sprzed naprawy, nadal siedzą w ruchomym oknie 30-dniowym. **Wypadną 9.09.** Do tego czasu każdy
recheck pokaże [VID] z 6 konwersjami, które nie są kontaktami — realny wynik tej kampanii to **0**.

**Zasada na przyszłość:** przed przełączeniem dowolnej kampanii z Manual CPC na strategię automatyczną
sprawdź `campaign_conversion_goal` tej kampanii. Nowe kampanie dostają domyślny zestaw celów konta,
w którym YouTube wraca.

## 4. Jak odświeżyć te liczby

```bash
python3 scripts/ads-recheck.py          # tabela na stdout + linia rozjazdu Ads/GA4
python3 scripts/ads-recheck.py --md     # ta sama tabela w Markdown, do wklejenia w sekcję 2
python3 scripts/ads-recheck.py --json tmp/ads-dump.json   # surowe dane
```

Skrypt jest read-only. Zmiany na koncie robi się osobnymi skryptami mutującymi (wzorzec: `scripts/gads_dsa_rewrite_desc_2026_07_16.py`).

**Gotchy API (kosztowały czas 19.08):**
- Wersję API bierz z `~/secrets/google/ads-config.json` (pole `api_version`, dziś `v25`). **Hardkod `v21` zwraca 404** — tak było w `scripts/gads_client.py` do 19.08 i w kilku skryptach jednorazowych w `tmp/`.
- **`campaign.start_date` nie istnieje w v25** — całe zapytanie leci na 400 INVALID_ARGUMENT. Daty startu kampanii trzymaj tutaj, w mapie.
- `login-customer-id` = `9506068500`, nie MCC. Konto jest direct-access.
- Klient GA4 (`scripts/ga4_query.py`) odtworzony 19.08 — poprzednia kopia w `tmp/` przepadła w czystce 14.07. **Nie przenoś go z powrotem do `tmp/`.**

## 5. Otwarte decyzje (nie wykonane — czekają na Janka)

1. **[VID] — właściwa pauza w panelu.** Reklamy zapauzowane przez API 31.08, ale sama kampania
   formalnie `ENABLED` (API nie przyjmuje mutacji kampanii VIDEO). Do zamknięcia ręcznie.
2. **Reklamy z ograniczeniem zasięgu.** `[Brand]` (ad 806602181652) i `[Topic]` (ad 811967380201)
   mają `APPROVED_LIMITED` z tematem **`GOVERNMENT_DOCUMENTS_AND_OFFICIAL_SERVICES`** — Google czyta
   nasze „homologacja / rejestracja / cło" jako usługi urzędowe. Do rozstrzygnięcia: przepisać teksty
   czy odwołać się od decyzji. Dotyczy też części reklam w `[SKAG-2]` (zapauzowana).
3. **[DSA] ma jedną reklamę `DISAPPROVED`** (ad 816552895918, ten sam temat) — druga (817108048038)
   serwuje, więc kampania działa, ale na połowie mocy.
4. **Pozostałe ślady marki Xiaomi** (po usunięciu dwóch reklam 31.08, patrz sekcja 6):
   - aktywna RSA `[Topic]` (ad 811967380201) ma nagłówek **„Leopard, Geely, iCAR, Xiaomi"**,
   - `[DSA]` łapie przez page-feed zapytanie **„xiaomi su7 ultra"** (12,67 zł/30 dni),
   - słowa kluczowe `xiaomi *` żyją w `[SKAG]` i `[SKAG-1]` — obie kampanie PAUSED, zero wydatku.
   Do decyzji, czy mail ws. marki Xiaomi obejmuje też te miejsca.
5. **Wykluczenie „auto prima bełchatów"** z `[Brand]` — 31 zł za 4 kliknięcia, 0 konwersji, obcy brand.
6. **[Topic] jest nasycone** (IS 98%, utracone przez budżet 0%) przy CPA 77 zł i rosnącym CPC
   (2,12 zł). Nie ma gdzie rosnąć — pytanie brzmi, czy nie zabrać stąd budżetu na [DG].
7. **[RMKT] słabnie** — z 6–8 konwersji/tydz. na 1–2 przy ~110 zł/tydz. Drugi miesiąc z rzędu.
   Kandydat do reworku feedu albo cięcia.
8. **Landing `galaxy-yizhen-l380-2025-251809` zwraca 410** (reklama w zapauzowanej grupie `[SKAG-2]`).
   Sygnał szerszy: reklamy SKAG celują w konkretne oferty, a te rotują — przy odmrażaniu SKAG-2
   trzeba dołożyć kontrolę landingów.

## 6. Dziennik zmian na koncie

| data | co | skrypt / dowód |
|---|---|---|
| 2026-08-19 | zawężenie celów konwersji na 9 kampaniach (18 operacji) | `scripts/ads-zawez-cele.py --apply`, backup `~/backups/primaauto/2026-08-19/` |
| 2026-08-31 | usunięte 2 reklamy Xiaomi YU7 z `[SKAG-1]` (816416026504 „Xiaomi YU7 — 320 000 zł", 816416026507 „Xiaomi YU7 od ręki") — mail ws. marki Xiaomi | `scripts/gads-usun-reklamy-xiaomi-2026-08-31.py --apply`, zweryfikowane odczytem: oba `REMOVED` |
| 2026-08-31 | pauza `[SKAG-2]`, pauza reklam `[VID]`, budżety Brand 10→25, DG 20→45 | `scripts/gads-realokacja-2026-08-31.py --apply`, zweryfikowane odczytem |
| 2026-08-31 | **DO WYKONANIA** — 7 filmów z 27–30.08 → assety + 4 reklamy wideo w `[DG]` (Shark 6, Deepal G318, Leopard 7, Denza Z9 GT) | `scripts/gads-dg-nowe-filmy-2026-08-31.py --apply` — zwalidowane, `--apply` jeszcze nie puszczone |
