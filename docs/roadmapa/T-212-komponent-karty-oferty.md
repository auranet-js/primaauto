# T-212 — Wspólny komponent karty oferty (fundament)

> Status: **gotowy do odpalenia** · Rozmiar: S
> Godziny realnie: **3–4 h** (Janek ~0,5 h, AI ~3 h) · Rynkowo: 8–10 h
> **Odblokowuje i potania:** T-187 (blok „inne egzemplarze"), T-114 (serce/ulubione), T-189 (rata na karcie), T-115 (checkbox „porównaj")

## Po co

Karta oferty (zdjęcie, tytuł, cena, parametry, badge dostępności) jest dziś **zduplikowana w trzech miejscach**:

| Plik | Linia |
|---|---|
| `class-asiaauto-brand-hub.php` | ~357 (`renderListingsCompact()`) |
| `class-asiaauto-homepage.php` | ~216 |
| `class-asiaauto-inventory.php` | ~934 |

**Konsekwencja:** cztery zaplanowane zadania chcą coś dołożyć do karty — serce (ulubione), ratę miesięczną, checkbox porównania, blok innych egzemplarzy. **Bez scalenia każde z nich musiałoby zrobić to samo trzy razy**, w trzech kopiach, z ryzykiem, że gdzieś się rozjadą.

To jest klasyczny fundament: 3–4 h teraz, żeby cztery kolejne taski były tańsze i spójne.

## Stan faktyczny

- **CSS karty już jest globalny** (`themes/primaauto2026/assets/css/hub.css:161-270`, ładowany bezwarunkowo w `functions.php:32`) — karta wyrenderuje się poprawnie wszędzie, bez dokładania stylów.
- Markup: `a.aa-home__car` > `.aa-home__car-img` (obrazek 350×250, rozmiar `asiaauto-card`, badge `on_lot`/`in_transit`/`Nowe`) + `.aa-home__car-body` (tytuł, cena, parametry: paliwo · rok · przebieg).
- Wersja z huba (`renderListingsCompact`) jest **najbogatsza** — bierzemy ją za wzorzec.

## Plan

1. **Nowy publiczny helper** — `AsiaAuto_Card::render(int $post_id, array $opts = [])`.
   `$opts`: `context` (hub/homepage/inventory/single/favorites), `show_price`, `show_badge`, `lazy`, `slots` (miejsca na dokładki: serce, rata, checkbox).
2. **Sloty rozszerzeń** — karta ma przewidziane punkty, w które kolejne taski wstrzykują swoje elementy, zamiast edytować sam komponent:
   - slot `corner` → serce (T-114)
   - slot `below_price` → rata miesięczna (T-189)
   - slot `footer` → checkbox „porównaj" (T-115)
3. **Trzy istniejące miejsca delegują do helpera** — zostają jako cienkie opakowania (zero zmian w ich sygnaturach i wywołaniach).
4. **Zero zmian wizualnych.** To jest refaktor, nie redesign.

## Strefy kruche

Dotyka **trzech plików produkcyjnych naraz** (huby, homepage, katalog) — czyli najbardziej widocznych miejsc na stronie.

⚠️ **Dlatego jedyny akceptowalny wynik to HTML identyczny co do znaku.** Osobny commit, nic więcej w nim.

## Testy

**Automatyczne (krytyczne)**
- **Diff HTML przed/po** dla: 5 hubów modeli, 3 hubów marek, homepage, katalog (strona 1 i 3, z filtrami i bez). **Musi być bit w bit identyczny.** To jest jedyny test, który ma znaczenie — jeśli cokolwiek się różni, refaktor jest zły.
- Karta dla oferty bez zdjęcia / bez ceny / zarezerwowanej → te same przypadki brzegowe co dziś.

**Półautomatyczne**
- Czas renderu katalogu przed/po (nie może zwolnić — helper nie może dokładać zapytań).

**MCP (Chrome)**
- Wizualne porównanie homepage, huba i katalogu przed/po — desktop i mobile 375px.

## Definicja zrobionego

- Jedna definicja karty oferty w kodzie, trzy miejsca ją wołają.
- HTML identyczny co do znaku (potwierdzony diffem).
- Sloty gotowe pod serce, ratę i checkbox porównania.
- Zero regresji wizualnych i wydajnościowych.
