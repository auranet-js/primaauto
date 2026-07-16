# Znaleziska: huby (2026-07-14)

> Osobny track od przebudowy ofert. Zapis znalezisk z datą — NIE plan wykonawczy.
> Nadrzędna zasada: **huby stoją #1–2 na frazach „cena", nie ruszać bez pilota + progów rollback**
> z `T-203-baseline-gsc-2026-07-13.md`.

---

## Z1 (2026-07-14) — 34 z 302 hubów mają H1 bez „cena w Polsce"

**Liczba:** 302 huby serie z ofertami (count>0). **268 ma „cena w Polsce" w H1, 34 NIE.**

**Przyczyna (z kodu, nie z domysłu):** H1 huba to `taxonomy-serie.php:98-99`:
```php
$aa_h1_suffix = get_term_meta($term->term_id, '_asiaauto_h1_suffix', true) ?: 'import z Chin';
<h1><?php echo esc_html($full_title); ?> — <?php echo esc_html($aa_h1_suffix); ?></h1>
```
H1 = `{model} — {suffix}`. Suffix z term_meta `_asiaauto_h1_suffix`; **gdy meta brak → fallback
„import z Chin"** (bez „cena w Polsce"). Wszystkie 34 huby mają `(brak→fallback)` — nigdy nie dostały
tego meta. Czyli batch/proces ustawiający suffix ominął te termy (najpewniej serie dodane po ostatnim
przebiegu batcha — top 3 to świeże modele).

**Istotne (reszta to huby z 1 ofertą — długi ogon, pomijalne):**

| ofert | serie | marka |
|---|---|---|
| 14 | **9X** | zeekr |
| 13 | **Z9 GT DM-i** | denza |
| 6 | **8X** | zeekr |
| 1 | MG 6, X-Trail, Tank 500, Cybertank 300, UNI-T, N8L, Galaxy M7, Yangwang U9, HiPhi Z … (31 szt.) | różne |

**Naprawa (gdy zapadnie decyzja):** ustawić `_asiaauto_h1_suffix` = „cena w Polsce i import z Chin" na
tych 34 termach (u źródła: dopisać do procesu, który to wypełnia, żeby nowe serie dostawały automatycznie).
Addytywne, nie dotyka generatora title. **Zeekr 9X stoi #1 na „zeekr 9x cena" — ruszać OSTATNI, osobno,
próg rollback >2 pozycje.** Full lista 34: `tmp/` (query w historii sesji 2026-07-14).

---

## Z2 (2026-07-14) — 3 huby z najgrubszych fraz TNT mają słabe H1/title

Audyt 9 hubów modeli, na których rankuje TNT (SU7, YU7, L9, MG7, 9X, G700, Galaxy M9, BAW, 8X).
Wszystkie istnieją (200), mają FAQ + dane techniczne + oferty + opisową sekcję wersji. Problemy:

1. **BAW 212 T01** (`baw` 1 300/mc TNT#12, `baw 212 gdzie kupić` 480/mc TNT#8):
   H1 = **`Beijing 212 T01`** — bez słowa „BAW", którego szuka rynek. Title = redundantne
   „BAW Beijing 212 T01" (po `ensureBrandPrefix` 07-13). Fraza „baw" u nas nie startuje.
2. **Zeekr 8X** (`zeekr 8x` 2 900/mc — druga najgrubsza fraza TNT): H1 = **`Zeekr 8X PHEV — import z Chin`**.
   „PHEV" (nikt nie szuka) rozmywa broad; brak „cena w Polsce" (to część Z1, term 8X w feedzie ma suffix pusty).
3. **Zeekr 9X** (stoimy #1 na „zeekr 9x cena"): H1 = `— import z Chin` (część Z1). Słabszy niż reszta,
   mimo najlepszej pozycji — najwyższe ryzyko regresji przy naprawie.

**Uwaga metodyczna:** pierwsza automatyczna detekcja (regex) dała FALSE-NEGATIVES — pokazała, że Galaxy M9
/ BAW / Zeekr 8X „nie mają sekcji wersji/ceny". Nieprawda — mają, pod innym szablonem nagłówków
(„Ile kosztuje" zamiast „Cena X", „Wersje i napęd" zamiast „Wersje w ofercie"). Zweryfikowane ręcznie.

---

## Z3 (2026-07-14) — cena per wersja: brak na wszystkich 9 hubach

Huby opisują wersje słownie (np. Zeekr 9X: „Ultra 55 kWh — bazowy… Hyper 70 kWh… 1400PS — topowa") i
podają **zakres** ceny (426–524 tys.), ale nie **min. cenę per wariant** (`Ultra od 426 tys.` itd.).
User szukający `zeekr 9x ultra` trafia na hub, ale nie widzi od razu ceny akurat Ultry. Dane są (MIN(price)
ofert danej wersji) — zero LLM. Rozszerzenie istniejącej sekcji, addytywne, na pilocie.

---

## Z4 (2026-07-16) — DWA huby z IDENTYCZNYM title: `_serie_full_title` gubi napęd

**Wykryte przy T-203 pkt 8** (anchor breadcrumbu), potwierdzone na żywo. Ten sam korzeń, który w anchorze
dawał kolizję (Sealion 5 DM i EV → oba „BYD Sealion 5") **już produkuje zduplikowane title hubów**:

| Hub | title (live 2026-07-16) |
|---|---|
| `/samochody/byd/sealion-5-dm/` | **BYD Sealion 5** — od 126 000 PLN, 1 sztuka |
| `/samochody/byd/sealion-5-ev/` | **BYD Sealion 5** — od 132 000 PLN, 13 sztuk |
| `/samochody/byd/han-dm-i/` | **BYD Han** — od 132 000 PLN, 44 sztuki |
| `/samochody/byd/han-ev/` | BYD Han EV — od 138 000 PLN, 9 sztuk |

**Mechanizm:** `_serie_full_title` bywa niesymetryczny — raz **dokleja** napęd, którego nie ma w nazwie
termu (`8X` → „Zeekr 8X PHEV", patrz Z2), raz **gubi** napęd, który w nazwie termu JEST
(`Han DM-i` → „BYD Han"). Skala zgubienia: **13 serii**, w tym Han DM-i (44 oferty), Qin L DM-i (22),
Sealion 8 DM-I (20), Song Pro DM-i (17), Song L DM-i (15), Sealion 5 EV (13).

**Skutek:** dwa huby konkurują o tę samą frazę własnym title (Sealion 5 = pełny duplikat), a hub
Han DM-i nie zawiera frazy „byd han dm-i", na którą powinien celować.

**Naprawa (gdy zapadnie decyzja, track hubów):** symetryczna reguła — token napędu obecny w nazwie
termu musi być w `_serie_full_title`. Logika gotowa i sprawdzona w
`AsiaAuto_Shortcodes::serieAnchor()` (v0.33.23) — do przeniesienia na generator hubów.
**UWAGA:** Han DM-i (44 oferty) i Sealion 5 EV (13) to huby z realnym stanem — zmiana title
wymaga baseline GSC i progu rollback, jak każdy hub.

**Stan pośredni po v0.33.23:** anchor breadcrumbu z ofert jest już poprawny („BYD Han DM-i"),
czyli **precyzyjniejszy niż title hubu, do którego prowadzi** („BYD Han"). To niespójność
in-plus (link celuje lepiej niż strona), ale docelowo hub powinien dogonić anchor.

---

## Dwa wzorce title (kosmetyka, niski priorytet)

`X — od NNN PLN, N szt. | Import z Chin | Prima-Auto` (generator, 6 hubów) vs
`X cena w Polsce 2026 — od NNN | Prima-Auto` (ręczny override: YU7, 9X, 8X). Do ujednolicenia kiedyś.

---

**Status: znaleziska zapisane, NIC nie wdrożone. Track hubów rusza dopiero po zamknięciu planu ofert.**
