# PROMPT — domknięcie dystrybucji na pozostałe kanały (po fali wideo)

> **Start nowego wątku:** „przeczytaj `docs/sesje/2026-08-27-PROMPT-social-domkniecie.md`".
> Poprzedni wątek: publikacja fali 2 na YouTube (27.08, wykonana — patrz sekcja 2).
> Parasol: `docs/roadmapa/T-243-social-dystrybucja-tresci.md`.

---

## 1. Jak te taski się łączą — mapa, bo rozjechała się na cztery pliki

Cztery numery opisują **jedną robotę** widzianą z różnych stron. Kolejność zależności:

| Task | Czym jest | Stan 27.08 |
|---|---|---|
| **T-205** | Odbudowa Meta po przejęciu konta (czerwiec). Nowe portfolio, nowa Strona, piksel, katalog | Infrastruktura **stoi**. Zostało: karta na koncie reklamowym |
| **T-243a** | Instrukcja dostępów dla Ruslana i Andrzeja — brama do wszystkiego | Wysłana 07.08, **bez odpowiedzi**. Zastąpiona przez T-243b |
| **T-243b** | Lista „co zostało do zrobienia", wersja 3 z 27.08 | **Aktualna**, `docs/roadmapa/T-243b-czego-brakuje-2026-08-25.md` + HTML na auratest |
| **T-243** | Parasol: pipeline dystrybucji na FB/IG/YT/TikTok, 8 faz, 89–119 h | **ZAPARKOWANY** (decyzja Janka 07.08). Wchłonął T-241 |
| **T-241** | Procedura „nowe materiały z sesji → hub + reklamy" | Wchłonięty przez T-243 jako faza c |
| **T-201** | Kanał YouTube — otwarcie, kreacja, publikacja, kampanie | Realizowany **ręcznie**, poza pipeline'em. Fale 1 i 2 wykonane |

**Sedno:** T-243 miał zbudować mechanizm, który robi to, co dziś robimy rękami. Nie ruszył,
bo faza a (dostępy) nie została domknięta. **Fala 2 na YouTube poszła całkowicie poza
pipeline'em** — Janek wgrał pliki, model przeklikał metadane w Studio przez Chrome.

---

## 2. Co wykonane 27.08 — nie powtarzać

**Dwanaście filmów z sesji 24.08 zaplanowanych na kanale Prima-Auto** (`UCsfcUP3uwrDxPI_mDIle8Pw`).
Sześć aut × dwa kadry. Publikują się same.

| Data | Short 16:00 | Poziomy 18:00 |
|---|---|---|
| 27.08 | BYD Shark 6 `crWB8tGy9zs` | — |
| 28.08 | Deepal G318 `Ku-Sbs9J5As` | BYD Shark 6 `zB_bIQPCm8c` |
| 29.08 | BYD Leopard 7 `60GjiT8HDIg` | Deepal G318 `M40E56narKg` |
| 30.08 | Denza Z9 GT `kwHaiADCVps` | BYD Leopard 7 `B9_Tm_ObRFg` |
| 31.08 | Denza N9 `ZeZGJ8-iA9I` | Denza Z9 GT `lrSQG3s0UYw` |
| 01.09 | Lynk & Co 900 `1xS5HBbJgXU` | Denza N9 `WM25drIlPAA` |
| 02.09 | — | Lynk & Co 900 `n9KN2RR-v8M` |

Każdy: cena w tytule, deep-link do oferty w pierwszej linii opisu, link do huba modelu
z kotwicą `#oferty`, 10–12 tagów, playlista, „nie dla dzieci", język polski.
**BYD Shark 6 jako jedyny ma „pierwszy w Polsce"** (potwierdzone przez Ruslana; Deepal G318 — nie).
**Lynk & Co** opisany jako auto w drodze, playlista „W drodze do Polski".

Raport do klienta wysłany `send-to-jan` 27.08.

---

## 3. Stan kanałów — punkt wyjścia

| Kanał | Stan | Co blokuje |
|---|---|---|
| **YouTube** | ✅ 12 filmów zaplanowanych, kanał 63,5 tys. wyświetleń | Nic. Publikacja ręczna działa. API zapisu **niemożliwe** — kanał nie jest Brand Accountem |
| **Facebook (stara `prima1auto`)** | ŻYWY kanał: 16 676 obs., Andrzej publikuje niemal codziennie, 1 310 sesji / 30 zdarzeń kluczowych na 90 dni | Nie mamy API. Gotowce do ręcznego wklejenia przez Andrzeja |
| **Facebook (nowa Strona)** | 145 fanów, piksel, katalog 2 517 aut | Reklamy: **karta płatnicza** (jedyny bloker) |
| **Instagram `@prima_auto.pl`** | Konto **jest w portfolio**, `Auranet API` ma pełny dostęp (sprawdzone 27.08) | **Token bez scope'ów IG** — trzeba przegenerować, patrz niżej |
| **TikTok `@primaauto.pl`** | 334 obs., 127 filmów, stoi od 14.07, **brak linku w bio** | Link w profilu + Business Center. Automatyczna publikacja wymaga audytu aplikacji przez TikToka (tygodnie) |

---

## 4. Instagram — najbliższy do odblokowania, konkretny brakujący element

Token `~/secrets/meta/tokens/primaauto-portfolio-ruslan.txt` **jest tożsamością Auranet API**
(`me` → `122094804249428678`), ale ma **osiem uprawnień i żadnego instagramowego**:

```
ads_management · ads_read · business_management · catalog_management
pages_manage_ads · pages_read_engagement · pages_show_list · public_profile
```

Zapytanie o konto `17841457773875233` zwraca `100/33 … cannot be loaded due to missing permissions`.
Uprawnienia zapisują się w tokenie w chwili tworzenia — dodanie zasobu później ich nie dokłada.

**Potrzebne:** przegenerowanie tokenu SU z dołożonymi `instagram_basic`,
`instagram_content_publish`, `instagram_manage_insights`.

⚠️ **Próbowaliśmy zrobić to sami — Facebook odmawia:** `This method must be called with
appsecret_proof`, a sekret aplikacji **Prima-Auto API** (`1533997951805022`) jest tylko
po stronie Ruslana. W dokumencie klienckim jest propozycja: jeśli Ruslan przekaże ten sekret
raz, każdą kolejną zmianę uprawnień zrobimy sami.

**Gdy token dojdzie:** sześć pionów 9:16 z fali 2 idzie na Instagram jako Reels bez żadnej
dodatkowej pracy montażowej — ten sam plik, drugi kanał.

---

## 5. Co robić w tym wątku, w kolejności

1. **Sprawdzić, czy przyszedł token IG i czy Ruslan wpiął kartę.** Jeśli tak — Reels z fali 2
   i kampanie na wyłączonych.
2. **Gotowce dla Andrzeja na Facebooka** — sześć pakietów tekstowych do ręcznego wklejenia
   na starej Stronie (tam nie mamy API i nie będziemy mieli). Treść wywodzi się z opisów YT,
   ale nie kopiuj ich żywcem — inny format, inna publiczność.
3. **TikTok** — dopytać, na co czekają (Andrzej coś wspominał, nie wiemy co). Link w bio
   to dwie minuty i największy zysk do wysiłku na całej liście: 127 filmów, **zero wejść na stronę**.
4. **Kreacje do `[DG]` z materiałów fali 2** — dopiero po pierwszych odczytach, zaczynając
   od tego Shorta, który złapał.
5. **`tmp/social/ads_przestaw.py`** — gotowy, nieodpalony: pauza `[VID]`, `[DG]` z 20 na 35 zł/dz.
   Podstawa: `[DG]` 25 realnych kontaktów przy CPA 24 zł, `[VID]` zero przy CTR 0,10%.

---

## 6. Pomiar — jedna reguła

**Miernikiem wideo jest brand search w GSC w oknie D+0…D+7 po publikacji, nie konwersje w Ads.**
8 sierpnia (film Terenwizji z udziałem Ruslana) dał 625 wyświetleń i 477 kliknięć brandowych
przy 63 i 22 dzień wcześniej — a w Ads nie zostawił śladu.
Narzędzia: `tmp/social/gsc_brand.py`, `tmp/social/brand_dziennie.py` (filtr `query contains "prima"`).

Pierwszy odczyt fali 2 sensowny **po 3 września**.

---

## 7. Pułapki — zaliczone, nie powtarzać

**YouTube Studio (Chrome):**
- **Pierwszy klik w pole tytułu po otwarciu okna nie łapie fokusu** — `ctrl+a` zaznacza wtedy
  całą stronę zamiast tekstu. Klik musi być w **osobnym wywołaniu**, dopiero potem `ctrl+a` + `type`.
- **Kolejność playlist w rozwijanej liście się zmienia** (sortowanie po ostatniej modyfikacji).
  Czytać etykiety, nigdy nie klikać „pierwszej pozycji" na pamięć.
- Opis to `DIV` contenteditable — `form_input` nie działa.
- Tagi: rozwinąć „Pokaż więcej", wpisać jednym ciągiem z przecinkami.

**Roszczenia Content ID:** cztery filmy z fali 2 mają roszczenie do utworu
„Inspiring Positivity" (Vasco Lourenço) z biblioteki CapCut. **Brak wpływu na zasięg i na kanał** —
licencja CapCut jest ważna, ale utwór jest osobno zarejestrowany w Content ID. Nie kwestionować:
kanał nie jest zmonetyzowany, więc korzyść zerowa, a przegrany spór to ostrzeżenie.

**Meta:**
- Kampanie VIDEO **zamknięte dla API** (`MUTATE_NOT_ALLOWED`); działa tylko `ads:mutate` update.
- **72% ruchu `[DG]` to `AGE_RANGE_UNDETERMINED`** — nie odznaczać „Nieznany" przy wieku.
- `videoResponsiveAd.callToActions` = max 10 znaków.

**Meta — tematy zamknięte, nie otwierać:**
- Odzyskanie starej Strony `prima1auto`: **strata trwała**, planujemy bez niej. Nie składać
  prośby o dostęp ani sporu — powiadomienie idzie do napastnika.
- Zapis „sprawa w kancelarii prawnej" z `T-205-instrukcja-dla-ruslana.md:17` jest **nieaktualny**.
- Stopka `class-asiaauto-contact.php:133` **zostaje** na starej Stronie, dopóki Andrzej publikuje.

**YouTube — API zapisu:** kanał nie jest Brand Accountem, delegacja ze Studio nie pojawia się
w OAuth chooser. **Nie proponować ponownie OAuth na kanał.** Zmieni się to dopiero, jeśli
właściciel przeniesie kanał na konto marki i doda `js@auranet.com.pl` jako menedżera
(punkt 3 w dokumencie klienckim) — wtedy dostajemy upload, metadane **i Analytics**.
