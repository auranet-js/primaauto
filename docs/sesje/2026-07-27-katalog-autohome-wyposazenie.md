# 2026-07-27 — katalog Autohome jako źródło wyposażenia che168 (T-186)

> Sesja domknięta. Plugin: v0.34.3 (bez bumpa — zmiany addytywne w adapterze/importerze).
> Poprzednie domknięcia: `2026-07-22-che168-sync-wpiecie.md`, `2026-07-25-che168-sonda-mapowania.md`.

## Punkt wyjścia

T-186 czekał na odpowiedź auto-api w sprawie pełnej konfiguracji che168 (zapytanie 22.07).
Oferty che168 mają 6-7 grup technicznych i zero wyposażenia; dongchedi ma 340-370 atrybutów.

## Co ustaliliśmy

**1. auto-api odmówiło (mail [156], 27.07 14:38).** Pełnej konfiguracji che168 nie ma i nie będzie
na żadnym planie — nie zbierają tych grup, 404 są zgodne z projektem, oferty płatnej brak. Odesłali
do publicznego katalogu Autohome po `specid`. O obciętym `extra_prep` dongchedi (>inner_id ~24,34M)
nie odpowiedzieli — to pytanie zostaje otwarte.

**2. Katalog działa i obfuskację da się zdjąć automatycznie.** 23 grupy / 261-292 parametry wobec
6-7 grup / 74-107 z API. HTTP 200 wprost z Elary, bez proxy. Antyscraping (losowe klasy `hs_kwNN`
per żądanie) zdejmowany przez uruchomienie inline-JS w Node ze stubem DOM i przechwycenie reguł CSS:
**292 parametry, zero nieodszyfrowanych znaków**. Skrypt podmienia `getComputedStyle`, żeby zablokować
odczyt z automatyzacji przeglądarki, ale generatora reguł nie chroni.

**3. Przestrzenie ID są rozłączne** — most idzie po nazwie CN, nie po ID (szczegóły w T-186).

**4. Skala: 47,5% ofert che168 nie ma bliźniaka** w dongchedi (57 ze 120), czyli katalog jest dla nich
jedynym źródłem. 89 unikalnych `specid` na 99 ofert = 1,11 — argument dostawcy o „rzędach wielkości
mniej specidów" się nie potwierdza. Kontekst: napływ dongchedi runął (VI: 1809 ofert → VII: 81),
che168 wzrósł (21 → 105).

**5. auto-api zwraca błędne nazwy modeli.** Dla `岚图追光` (Voyah Passion) pole `model` = **„Zeekr"**.
Importer założył serię „Zeekr" pod marką Voyah. Katalog rozstrzygnął (`specid` 59292 → `岚图追光 2024款`,
seriesid 6915). Wniosek: tytuł strony katalogu daje darmowy test spójności przy każdym pobraniu.

**6. Dziedziczenie nie jest wpięte w automat** — `scripts/merge-spec-from-twin.php` odpala się ręcznie,
w kodzie pluginu nie ma po nim śladu. Wcześniejsze założenie, że „przy imporcie i tak najpierw
dziedziczymy", było nieprawdziwe.

## Co wdrożone na produkcji

| Zmiana | Plik |
|---|---|
| Stemplowanie `specid` przy imporcie | `class-asiaauto-che168-adapter.php` (`$data['spec_id']`), `class-asiaauto-importer.php` (2 ścieżki zapisu → `_asiaauto_spec_id`) |
| Fetch + deszyfracja katalogu | `scripts/autohome-catalog-fetch.js` (Node, bez przeglądarki) |
| Most nazwa CN → klucz `extra_prep` | `data/autohome-catalog-map.php` — 126 nazw → 129 kluczy |
| Etykiety PL + wartość „Opcja" | `data/translations-extra-prep.php` (+9 labeli, `选配`, klucze do 6 kategorii) |
| Dolewka do oferty | `scripts/autohome-catalog-merge.php` |
| Backfill `specid` | 99 ze 120 ofert (21 → 404, wygasły u źródła) |

**Efekt na produkcji:** oferta 390681 `extra_prep` 90 → 196 (**88 pozycji wyposażenia po polsku**),
oferta 390697: 104 → 173 (**71 pozycji**). Wcześniej obie miały zero. Bez dotykania szablonów —
dane weszły istniejącą ścieżką `translateExtraPrep()`.

## Przy okazji: rebranding Zhuiguang → Passion

追光 to międzynarodowo **Voyah Passion**; 追光L = **Passion L**, i to **osobny model** (premiera
XII 2025, seriesid 8259), nie wersja Passion (seriesid 6915). Decyzja oparta na GSC 90 dni: hub
`/voyah/voyah-zhuiguang-l/` miał **0 impresji**, fraza „zhuiguang" 1 impresję, „passion" 19 + klik.

Zmienione: termy 5081 → `passion-l`, 5078 → `passion-phev`, 5079 → `passion-ev` (+301 przez
`V62_SERIE_REDIRECTS`), treści hubów, tytuły 11 ofert, meta `serie`, alt 107 zdjęć, `post_content`
4 ofert, 210 plików zdjęć przemianowanych (CN i „zeekr" w nazwach), usunięty pusty term 7203.
Hub 5078 nie przeszedł reworku SEO fali v3 — uzupełniony (`_asiaauto_lead`, `_asiaauto_h1_suffix`,
`_asiaauto_pl_availability`). **Slugi ofert dongchedi zostawione** (mogą być zaindeksowane, zwracają 200).

Indexing API: oferty zgłoszone automatycznie przez plugin, 2 nowe URL-e hubów ręcznie przez
`index-submit --yes` (budżet ad-hoc był wyczerpany: 103/100, wejście w rezerwę o 5).

**Korekta:** to nie były pierwsze oferty che168 na produkcji — publikowane były już w czerwcu (18)
i lipcu (34, łącznie z dzisiejszymi).

## Otwarte

- **Backlog mapy:** przy tych ofertach 97-98 pozycji z katalogu pozostało niezmapowanych — mapa
  będzie się dopełniać przy kolejnych modelach, jak `che168-param-map`.
- **169 innych ofert ma chińskie znaki w nazwach plików zdjęć** (2114 plików) — starszy problem tej
  samej klasy, skrypt renamingu gotowy, nietknięte.
- Cache per `specid`, cron uzupełniania, wpięcie w automat — świadomie odłożone do obserwacji synca.
- Mail do Autohome o token oficjalnego MCP (`zhijieru@autohome.com.cn`) — draft gotowy, **nie wysłany**
  (decyzja Janka: na razie żadnych maili). Ich narzędzia działają po nazwie serii, nie po `specid`,
  więc mogą nie schodzić do poziomu wersji.

## Następny krok

Uruchomienie synca che168 i zebranie liczb, na których podejmiemy decyzję o automatyzacji.
Prompt: `docs/sesje/2026-07-27-PROMPT-KONTYNUACJA-che168-wyposazenie.md`.
