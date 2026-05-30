# Tier B — huby-klejnoty „import" (chronić przy reworku cena-forward)

> Data: 2026-05-30 · Źródło: GSC 90 dni (2026-02-26 .. 2026-05-27), dimensions [page, query], filtr query contains „import"
> Kontekst: rework hubów pod intent „cena/gdzie kupić" (Faza 1). Cena ma ~25× większy popyt niż import (5 542 vs 223 imp/28d). Import-edge jest WĄSKI — tylko 3 huby warte ochrony.

## Reguła rolloutu

- **Tier A** (~337 hubów, znikoma ekspozycja „import"): pełny rework cena-forward — H1 `+ cena w Polsce` (per-term override), FAQ/wiki pod intent. Zero ryzyka import.
- **Tier B** (3 huby poniżej): wariant OCHRONNY — **title/H1 zostają import-led** (nie ruszać), cenę dokładamy tylko w treści/FAQ, bez degradacji importu. Osobny, ostrożny przebieg z kontrolą GSC przed/po. `per-term override` = chirurgicznie.

## Lista Tier B (poz ≤3,5 i imp ≥3 na frazę „import", 90d)

| Hub | Fraza | Poz | Imp (90d) | Klik | CTR |
|---|---|---|---|---|---|
| `/samochody/denza/z9-gt-dm-i/` | denza z9 gt import | 2,5 | 6 | 2 | 33% |
| `/samochody/zeekr/8x/` | zeekr 8x import | 2,4 | 5 | 0 | 0% |
| `/samochody/zeekr/9x/` | zeekr 9x import | 3,5 | 5 | 1 | 25% |

Term IDs (do `_asiaauto_h1_suffix` / kontroli): Z9 GT = 4660, Zeekr 8X = 4824 (slug `8x`), Zeekr 9X = 6584.

## Szum (NIE chronić — brak pozycji do stracenia, 1–2 imp/90d, poz 5–40)

`jetour/t2` (poz 5) · `byd/xia-summer` · `denza/n8l-dm` · `jmc-ev/fushun` · `mg/mg-4` · `gwm/cannon` + `great-wall/cannon` („paoimportz" — literówka) · `mg/mg-7` · `byd/leopard-3` (poz 40 na „import wyposażenia garażu z chin" — nie nasza fraza).

Łącznie 12 hubów modelowych z jakąkolwiek impresją „import"; tylko 3 to klejnoty.

## Uwaga

Nawet klejnoty to niski wolumen (~2 imp/mc). Import jako kanał jest mały — ale Z9 GT/Zeekr 9X konwertują (33%/25% CTR) i siedzą poz 2–3,5, więc ochrona = tanie ubezpieczenie (kosztuje zero: zostawiamy im title/H1). Przy rolloucie zweryfikować listę ponownie świeżym GSC (pozycje import mogą się zmienić).
