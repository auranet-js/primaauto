# Publikacja na socialach i reklamy poza Google Ads — pełny stan

> **Zebrane 2026-08-27.** Wszystko sprawdzone na żywych kontach albo w dokumentacji Meta.
> Google Ads celowo POZA tym dokumentem — ma osobny wątek (`docs/ads/mapa-kampanii.md`).
> Ten plik zastępuje szukanie po `tmp/` i pytanie o rzeczy już ustalone.

---

## 1. Kanały — co jest i kto tam pracuje

| Kanał | Identyfikator | Stan | Kto publikuje |
|---|---|---|---|
| **Strona FB (nowa)** | `1146829831857839` | 153 fanów, **20 postów / 30 dni** | Andrzej, ręcznie |
| **Strona FB (stara)** | `106659808679582` `prima1auto` | 16 676 obs., **poza naszym zasięgiem** | Andrzej, ręcznie |
| **Instagram** | `17841457773875233` `@prima_auto.pl` | 103 obs., 435 postów, **8 postów od 28.07** | Andrzej + (27.08) my |
| **YouTube** | `UCsfcUP3uwrDxPI_mDIle8Pw` | 63 556 wyśw., 12 filmów zaplanowanych do 02.09 | ręcznie przez Studio |
| **TikTok** | `@primaauto.pl` | 334 obs., 127 filmów, **stoi od 14.07**, brak linku w bio | nieustalone |

**Stara Strona zostaje nietknięta** — decyzja Janka 27.08. Tylko Andrzej ma tam Managera,
publikuje na obu równolegle i przy okazji ofert kieruje ludzi na nową. Nie prosimy o rolę
reklamodawcy, nie składamy wniosku o dostęp, nie przepinamy stopki
(`class-asiaauto-contact.php:133`).

**Andrzej publikuje NA NASZEJ Stronie i na Instagramie.** Żaden z tych kanałów nie jest martwy.
Posty są kompletne: model, rocznik, przebieg, cena brutto „w Polsce po wszystkich opłatach",
specyfikacja, wyposażenie, **deep-link do konkretnej oferty**, hashtagi, plus blok kierujący
obserwujących starej Strony na nową. Wszystkie mają `is_eligible_for_promotion: true`.

---

## 2. Uprawnienia — co mamy, czego brakuje

**Token:** `~/secrets/meta/tokens/primaauto-portfolio-ruslan.txt` (SU `Auranet API`
`122094804249428678`, app `Prima-Auto API` `1533997951805022`, nie wygasa).

### Scope'y w tokenie — mamy 11 z 85 istniejących

```
public_profile  pages_show_list  pages_read_engagement  business_management
instagram_basic  instagram_content_publish  instagram_manage_insights
ads_management  ads_read  pages_manage_ads  catalog_management
```

### Role na zasobach — sprawdzone 27.08

```
Konto reklamowe   DRAFT · ANALYZE · ADVERTISE          (MANAGE ma tylko Ruslan — i dobrze)
Katalog           ADVERTISE · MANAGE
Strona FB         MANAGE · CREATE_CONTENT · MODERATE · MESSAGING
                  ADVERTISE · ANALYZE · VIEW_MONETIZATION_INSIGHTS
Instagram         dziedziczy przez Stronę (nie ma osobnych)
```

**Rola ≠ scope i trzeba sprawdzać oba.** `read_insights` nie figuruje w `debug_token`,
a statystyki Strony działają — przez rolę `ANALYZE`. Odwrotnie: `CREATE_CONTENT` na Stronie
mamy, ale bez scope'u `pages_manage_posts` nic tam nie opublikujemy.

### Brakuje — lista do jednej, ostatecznej prośby

| Uprawnienie | Po co |
|---|---|
| `instagram_manage_contents` | **usuwanie postów IG** — dziś nie umiemy posprzątać po błędzie |
| `pages_manage_posts` | szkice i harmonogram na Stronie FB (rolę już mamy) |
| `read_insights` | statystyki Strony — dziś na roli, którą można zdjąć |
| `pages_read_user_content` + `pages_manage_engagement` | komentarze pod postami i reklamami na FB |
| `instagram_manage_comments` | to samo na IG |
| `pages_manage_metadata` | webhooki + odczyt ról na Stronie (dziś endpoint odmawia) |
| `pages_manage_cta` | przycisk „Zadzwoń" / „Napisz" na Stronie |
| `instagram_shopping_tag_products` | tagowanie aut z katalogu w postach IG |
| `instagram_manage_events` | zdarzenia konwersji z IG |
| `leads_retrieval` | zgłoszenia z formularzy w reklamach |
| `attribution_read` | raporty atrybucji (dashboard, koszt kontaktu) |
| `read_audience_network_insights` | statystyki z Audience Network |
| `instagram_branded_content_ads_brand` + `instagram_branded_content_brand` + `facebook_branded_content_ads_brand` | promowanie cudzych materiałów o nas (współprace z twórcami — film Terenwizji dał 625 wyświetleń i 477 kliknięć brandowych) |

**Świadomie pomijane:** wiadomości (Messenger, DM na IG), WhatsApp (brak konta WABA
w portfolio — uprawnienia byłyby martwe), Threads (brak konta), Commerce (auta na zamówienie,
nie ma koszyka), dane osobowe użytkowników (dotyczą logowania osoby, nie System Usera).

**Żadne z nich nie dotyka pieniędzy ani zarządzania ludźmi** — sprawdzone: linie kredytowe
puste, endpoint metod płatności nie istnieje dla naszego tokenu, `MANAGE` na koncie
reklamowym pozostaje wyłącznie u Ruslana.

---

## 3. Konto reklamowe Meta — co działa, co nie

**`act_1038563008906171`** · aktywne · PLN · Europe/Warsaw · `spend_cap` 1 000 zł ·
wydatki 0 zł · **0 kampanii**

**Karta jest podłączona** — Ruslan potwierdził 27.08, limit wydatków ustawiony na 1 000 zł.
API jej nie pokazuje (`funding_source` i `funding_source_details` wracają puste we wszystkich
wersjach od v18 do v23), bo użytkownik systemowy nie ma roli finansowej na koncie — `MANAGE`
ma wyłącznie Ruslan. **Puste `funding_source` NIE jest dowodem braku karty** i nie należy
tego tak czytać; jedynym śladem widocznym z API było pojawienie się `spend_cap`.

### Sprawdzone i DZIAŁA

| Operacja | Dowód |
|---|---|
| **Wgranie zdjęcia** do biblioteki (`/adimages`) | 27.08, hash `2410ca18…`, 2048×1365 |
| **Wgranie wideo** do biblioteki (`/advideos`) | 27.08, id `4040666229569687`, prywatność `SELF` |
| **Utworzenie kampanii** | 25.08 — powstała `120248752872730243`, potem skasowana |
| Odczyt statystyk Strony | metryki `page_views_total`, `page_daily_follows`, `page_post_engagements` |
| Odczyt postów Strony (page token) | 20 pozycji / 30 dni |
| Publikacja na Instagramie | 6 Reelsów 27.08 (incydent) |

**Wideo w bibliotece NIE jest publikacją** — prywatność `SELF`, nie pojawia się ani w feedzie
Strony, ani na profilu. Sprawdzone trzema sposobami.

### JEDYNY realny bloker reklam: weryfikacja beneficjenta i płatnika (DSA)

**Ustalone 27.08 na kampanii docelowej, nie na teście.** Zbudowana została prawdziwa kampania
`[VID] Auta z Chin — nowi odbiorcy` (BYD Leopard 5, wideo 9:16, 25 zł/dz, PL 25–55,
cel: zdarzenie `Contact`). Doszła do drugiego kroku i tam stanęła.

Sekwencja błędów — każdy rozwiązany, aż do ostatniego:

```
1. campaigns  → 100/4834011  „określ is_adset_budget_sharing_enabled"   → dodane, PRZESZŁO
2. adsets     → 100/3858081  „Nie wskazano reklamodawcy"                 → dodane dsa_beneficiary
                                                                            + dsa_payor, ZMIENIŁO BŁĄD
3. adsets     → 100/3858196  „Nie można opublikować tego zestawu reklam.
                              Dowiedz się, jak poprosić o weryfikację"    → ŚCIANA
```

**Trzeci błąd to brak weryfikacji beneficjenta i płatnika**, wymaganej przez unijny DSA
we wszystkich reklamach kierowanych do UE. Meta musi zweryfikować tożsamość podmiotu,
który korzysta z reklamy i który za nią płaci. Sprawdzone dwiema nazwami — własną
i dokładną nazwą zweryfikowanego portfolio — ten sam błąd, więc **problemem nie jest
brzmienie nazwy, tylko nieprzeprowadzona weryfikacja**.

Weryfikacji **nie da się zrobić przez API** — to proces w Menedżerze Reklam, z dokumentami firmy.

**To samo zablokowało testy 25.08** — trzy warianty zestawu reklam, wszystkie odrzucone.
Wtedy komunikat nie został zapisany i przez dwa dni uchodziło to za problem z uprawnieniami
albo z kartą. Nie jest ani jednym, ani drugim.

⚠️ **To NIE jest weryfikacja firmy — ta jest zrobiona.** Portfolio `Prima Auto` ma
`verification_status: verified` (punkt R3 z listy dla klienta, potwierdzony 26.08).
Weryfikacja beneficjenta i płatnika wg DSA to **trzeci, osobny proces** — obok weryfikacji
firmy i obok podłączenia karty, które również jest zrobione. Dla porządku: Strona `Prima-Auto`
ma jeszcze własne `verification_status: not_verified`, ale to czwarta, niepowiązana rzecz.

### Pozostałe braki — realne, ale wtórne wobec powyższego

| Problem | Objaw | Skutek |
|---|---|---|
| **Katalog bez zestawów produktów** | `/catalog/product_sets` → `[]` | dynamiczne reklamy niemożliwe; do utworzenia po naszej stronie (mamy `MANAGE`) |
| **Brak audiencji** | `/act_*/customaudiences` → `[]` | brak remarketingu i lookalike; do zbudowania z piksela |
| **Strona niewidoczna jako zasób reklamowy** | `/act_*/promote_pages` → `[]` | do sprawdzenia po weryfikacji — może być skutkiem, nie przyczyną |

### Co przeszło w tej próbie

Utworzenie kampanii — **trzykrotnie potwierdzone**. Wgranie wideo Leopard 5
(`1389329476599667`, HEVC 10-bit HDR, 42 s — Meta przyjęła bez konwersji, prywatność `SELF`).
Pobranie miniatury wygenerowanej przez Meta. **Kreacja wideo** (dark post — sprawdzone,
że nie pojawia się na Stronie ani na profilu).

### Co stoi na koncie teraz — kampania docelowa, niedokończona

```
1. KAMPANIA       120248809387930243  [VID] Auta z Chin — nowi odbiorcy   PAUSED
2. ZESTAW REKLAM  —                   NIE POWSTAŁ  ← weryfikacja DSA
3. KREACJA        4545681435750921    Leopard 5 — wideo 9:16
4. REKLAMA        —                   NIE POWSTAŁA (nie ma czego spiąć z kreacją)

   wideo w bibliotece: 1389329476599667 (Leopard 5 czarny, pion 9:16, 42 s)
```

**Dwa poziomy z czterech. Reklama nie istnieje i nie ruszy.** Po weryfikacji DSA zostają
dwa wywołania — zestaw i reklama — reszta jest gotowa.
Skrypt: `scripts/social/meta_kampania_wideo.py` (`--dry-run` / `--buduj`).

### Niesprawdzone

Kreacja reklamowa (`/adcreatives`), sama reklama (`/ads`), promowanie istniejącego postu
przez `object_story_id`.

---

## 4. Czego API Meta NIE potrafi — z dokumentacji, nie z domysłu

- **Crossposting rolek z Facebooka na Instagram nie istnieje w API.**
  Dokumentacja: „You can only crosspost Reels to Facebook Pages". Opcja w Business Suite
  jest funkcją interfejsu — nie ustawimy jej ani nie sprawdzimy z zewnątrz.
- **Instagram nie ma planowania ani szkiców.** Kontener (`POST /{ig_id}/media`) żyje 24 h
  i albo zostaje opublikowany, albo wygasa. Harmonogram wyłącznie cronem o właściwej godzinie.
- **Instagram nie pozwala edytować opublikowanego postu** — podpisu zmienić się nie da,
  daty publikacji też nie. Jedyne, co przyjmuje endpoint medium, to włączanie i wyłączanie
  komentarzy (wymaga `instagram_manage_comments`).
- **Instagram POZWALA usuwać** posty, rolki i całe karuzele — wymaga `instagram_manage_contents`.
  Dziś `DELETE` zwraca `(#10) Insufficient permissions` właśnie z tego powodu.
- **Facebook Reels MA szkice i harmonogram:** `video_state` = DRAFT / SCHEDULED / PUBLISHED,
  `scheduled_publish_time` od 10 minut do 29 dni. Wymaga `pages_manage_posts`.
- Limit publikacji IG: **100 postów / 24 h**; stan: `GET /{ig_id}/content_publishing_limit`.

**Wniosek dla sposobu pracy:** bezpiecznie testować da się wyłącznie na Facebooku, na szkicach.
Na Instagramie każda publikacja jest natychmiastowa i nieodwracalna.

---

## 5. TikTok

127 filmów, 334 obserwujących, 1 781 polubień — liczba filmów niezmienna od 14.07.
**W profilu nie ma linku do strony**, więc przy 127 filmach TikTok nie przysłał ani jednego
wejścia na witrynę. Wpisanie adresu to dwie minuty i największy zysk do wysiłku na całej liście.

Publikacja automatyczna wymaga konta Business, Centrum Biznesowego **oraz audytu aplikacji
przez TikToka** — kilka tygodni, wynik niezależny od nikogo z nas. Do tego czasu maksimum
to przygotowywanie szkiców, z ostatnim kliknięciem po stronie klienta.

Autorstwo konta **nierozstrzygnięte od 14.07** — ktoś te filmy produkuje. Rozmowa z Ruslanem
przed jakimkolwiek wejściem w ten kanał.

---

## 6. YouTube

Kanał **nie jest Brand Accountem** — wisi na prywatnym koncie Google osoby, która założyła go
3 grudnia. API zapisu niemożliwe, publikacja wyłącznie ręczna przez Studio w sesji Janka.
Odczyt działa (`~/secrets/google/youtube-tokens.json`).

**Kanał nie jest powiązany z Google Ads** (`account_link` → puste). Konsekwencja:
**63 556 wyświetleń nie zbudowało ani jednej audiencji.** Na koncie Ads jest 27 list odbiorców,
wszystkie z ruchu na stronie, ani jednej z widzów wideo.

**Powiązanie z Ads NIE wymaga konta marki** — prośbę wysyła się z Ads, właściciel akceptuje
w Studio → Ustawienia → Kanał → Powiązane konta. Przeniesienie na konto marki dalej jest
potrzebne (bezpieczeństwo kanału, automatyzacja, Analytics), ale nie blokuje zbierania widzów.

Pułapki Studio przez Chrome: pierwszy klik w pole tytułu nie łapie fokusu (klik musi być
w osobnym wywołaniu); kolejność playlist w liście się zmienia — czytać etykiety; opis to
`DIV` contenteditable, `form_input` nie działa; tagi wpisywać jednym ciągiem po „Pokaż więcej".

Cztery filmy fali 2 mają roszczenie Content ID do utworu z biblioteki CapCut — **bez wpływu
na zasięg**, nie kwestionować (kanał niezmonetyzowany, przegrany spór to ostrzeżenie).

---

## 7. Materiał — co mamy do dystrybucji

Dysk Google, `Prima Auto/sesje/` (`1m-8F99y79rIyAhb3zTViOvvmZgCCe87l`), scope `drive` mamy:

| Model | Zdjęcia | Filmy |
|---|---:|---:|
| BYD Shark 6 | 22 | 2 |
| Deepal G318 | 31 | 2 |
| Denza N9 | 21 | 2 |
| Denza Z9 GT | 16 | 2 |
| Exeed VX | 20 | 2 |
| Jetour T2 | 15 | 2 |
| BYD Leopard 5 | 29 | 4 |
| BYD Leopard 7 | 15 | 2 |
| Lynk & Co 900 | 27 | 2 |
| **RAZEM** | **196** | **20** |

Każdy film w dwóch kadrach (9:16 i 16:9), 1080×1920 / 1920×1080, HEVC, 41–65 s, 32–52 MB.
**Filmy nie mają faststart** — przed wysłaniem do Meta wymagają remuksu
(`ffmpeg -c copy -movflags +faststart`), inaczej API odrzuca.

Zdjęcia z sesji mają pierwszeństwo przed importowymi — importowe wymagają maskowania
chińskich tablic i znaków wodnych (reguła D7 ze specu T-243).

Opisy, ceny, deep-linki i hashtagi fali 2: `tmp/social/metadane-fala2.md`.

---

## 8. Pomiar

**Miernikiem wideo jest brand search w GSC w oknie D+0…D+7, nie konwersje w Ads.**
8 sierpnia film Terenwizji z Ruslanem dał 625 wyświetleń i 477 kliknięć brandowych
przy 63 i 22 dzień wcześniej — a w Ads nie zostawił śladu.
Narzędzia: `tmp/social/gsc_brand.py`, `tmp/social/brand_dziennie.py` (filtr `query contains "prima"`).

Piksel `1634147041766916` zbiera (ostatnie zdarzenie 26.08 23:11).

Zastrzeżenia do liczb: **GA4 zaniża konwersje ~4×** (11 rozpoczętych zamówień wobec 45
w bazie), Ads liczy o 56% więcej konwersji niż GA4 zdarzeń kontaktowych, atrybucji per
zamówienie nie ma (brak `gclid`/`utm` przy zamówieniu).

Metryki Page Insights `page_impressions` i `page_fans` **wycofane w v26** — zwracają
„not a valid insights metric", co łatwo wziąć za brak uprawnień.

---

## 9. Co stoi na koncie

**Zostawić — to kampania docelowa, do dokończenia po weryfikacji DSA:**
```
120248809387930243   kampania [VID] Auta z Chin — nowi odbiorcy   PAUSED
4545681435750921     kreacja  Leopard 5 — wideo 9:16
1389329476599667     wideo    Leopard 5 czarny, pion 9:16, 42 s
```

**Do posprzątania — pozostałości po testach:**
```
4040666229569687   wideo „[TEST] BYD Shark 6 pion"      27.08, biblioteka reklamowa
2853400968357319   wideo „TEST Auranet — do skasowania" 25.08, biblioteka reklamowa
1531739618705464   „TEST boost 2026-08-25-…"            25.08
2410ca18ffde1dc…   zdjęcie testowe (Shark 6)            27.08, biblioteka reklamowa
```

Kampanie testowe `120248752872730243`, `120248809207980243`, `120248809220940243`
i `120248809243700243` są `DELETED`.

Na Instagramie: sześć Reelsów z 27.08 (`DcjCnoIk8Gw`, `DcjCus-ghgw`, `DcjC5y1EhCK`,
`DcjC_Vgjzmo`, `DcjDDm_kqyo`, `DcjDPwIDZMz`) — do usunięcia przez Andrzeja albo przez nas,
gdy dojdzie `instagram_manage_contents`.

---

## 10. Do zrobienia po stronie klienta

| | Co | Kto | Czas |
|---|---|---|---|
| 1 | Token z pełnym kompletem uprawnień (lista w §2) | Ruslan / Andrzej | 5 min |
| 2 | Usunięcie sześciu Reelsów z 27.08 | Andrzej | 3 min |
| 3 | Link `primaauto.com.pl` w polu „Witryna" profilu IG + literówka „z Chin **ta** Korei" | Andrzej | 2 min |
| 4 | **Weryfikacja beneficjenta i płatnika reklam (DSA)** — bez tego żadna reklama na Meta nie powstanie; Menedżer Reklam, wymaga dokumentów firmy | Ruslan | 15 min + oczekiwanie |
| 5 | Akceptacja powiązania kanału YouTube z Google Ads | właściciel kanału | 2 min |
| 6 | Link do strony w bio TikToka | Andrzej | 2 min |
| 7 | Przeniesienie kanału YT na konto marki + `js@auranet.com.pl` jako menedżer | właściciel | 15 min |

---

## 11. Reguła obowiązująca bezwzględnie

**„Leć", „działaj", „rób" to zgoda na PRACĘ, nie na PUBLIKACJĘ.** Każde wypchnięcie treści
na zewnętrzny kanał klienta wymaga osobnej, wyraźnej zgody na konkretną treść i konkretny
termin. Wszystko najpierw `--dry-run`, na całej partii, nie na pierwszym elemencie.

Zabezpieczenie techniczne: `~/bin/social-publish-guard-hook.py` (PreToolUse w `~/.claude/settings.json`)
blokuje wywołania publikacyjne i skrypty bez `--dry-run`. Odczytów nie dotyka.
Furtka na jedno wywołanie: `ZGODA_PUBLIKACJA=<data>` przed komendą.

**Incydent 27.08:** sześć Reelsów opublikowanych w sześć minut po ogólnym „leć",
wbrew czterem regułom z T-243. Nie do cofnięcia.
Zapis: `docs/sesje/2026-08-27-incydent-reels-instagram.md`.

**Druga reguła, wyciągnięta z tego samego dnia:** `tmp/` jest gitignored i nie przeżywa sesji.
Wyniki testów z 25.08 zostały tam i przepadły — dlatego 27.08 zadawałem pytania o rzeczy
już sprawdzone. Przy domknięciu wątku przenieś z `tmp/` wszystko, co ma zostać.
