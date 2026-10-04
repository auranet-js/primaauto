# Wzrost liczby ofert i chude dongchedi ← katalog Autohome (04.10.2026)

## Skąd 4400 ofert zamiast ~3200

Stan 04.10: **4439 ofert publish** (che168 2879, dongchedi 1433, ręczne 127). Połowa weszła po 23.09:

| dodane | che168 | dongchedi | inne | razem |
|---|---|---|---|---|
| przed 01.09 | 631 | 375 | 103 | 1109 |
| 01–22.09 | 1098 | 2 | 15 | 1115 |
| od 23.09 | 1150 | 1056 | 9 | 2215 |

1. **dongchedi `verify` → `full` (23.09)** — przedtem 0–1 nowych/dobę, potem 50–200/dobę.
2. **che168 dowozi coraz więcej** — 15–35/dobę pod koniec sierpnia, 60–100 w połowie września.
3. **Nadrabianie po zastoju che168** — 25.09 jednorazowo 325 ofert.

Rotacja działa (2–4.10 po 100–160 do draftu dziennie), ale wpływ przewyższa ubytek. Dokładnej krzywej
publish/dzień nie da się odtworzyć — kosz czyści się po 7 dniach.

## Chude oferty dongchedi

Pomiar (niepuste pola `extra_prep`, publish, dodane od 23.09, 1056 ofert): **162 chude (<100)**, 286 średnich,
608 pełnych (≥250). Chude rozłożone równo po dniach — nocna dolewka ich nie ratuje: bliźniak nie ma dawcy,
bank (19:25) w ostatnim biegu zapisał 0 (brak nowych wersji: Haval Xiaolong Max, Avatr 07/12, Tang DM-p, Denza D9…).

## Dolanie z katalogu Autohome — 134 z 162

Oferty dongchedi nie mają `_asiaauto_spec_id`, więc trzeba znaleźć `specid` po chińskiej nazwie wersji
(`extra_prep['name']`).

| partia | jak dopasowane | ofert | pola po (mediana) |
|---|---|---|---|
| 1 | nazwa = `车型名称` z pobranych katalogów / `param_93` ofert che168 | 42 | 235 |
| 2 | nowy skrypt, pierwsze 10 z 120 | 10 | ~280 |
| 3 | skrypt, 110 pozostałych | 74 | 266 |
| 4 | po aliasie ZEEKR → 极氪, 奕派008 → eπ008 | 8 | ~297 |

**Kontrola każdego dopasowania:** rozstaw osi i długość z oferty = katalog (wszystkie 134 zgodne co do mm).
Luźne dopasowanie (rocznik + wersja + podobny model) odrzucone — Denza D9 trafiała w Z9GT i N8L.
Ryzyko, które zostaje: przy dopasowaniach „przybliżonych” wymiary nie odróżniają wersji wyposażenia tego
samego modelu (to samo nadwozie).

Po wszystkim (dodane od 23.09): **chude 28**, średnie 338, pełne 689.

**28 bez pary:** 9 z kilkoma kandydatami o tych samych wymiarach (瑞虎8 ×4, 捷途旅行者, 长安CS55 PLUS,
红旗HS5 旗享Pro, 海豹06GT), 19 bez wersji pod danym rocznikiem (秦PLUS EV 智驾版, 星纪元ET, 蔚来ES9,
Zeekr 001 Ultra+ 2026 / 009 七座过道版, 零跑C16 2027, 银河E8…). Zostawione — część wejdzie sama, gdy
Autohome dopisze wersje.

Kopie sprzed zapisu + listy dopasowań: `~/backups/primaauto/2026-10-04/dcd-{42,10,74,8}-*`.

## Nowe skrypty (commity 0957565, 2df88ee)

- `scripts/autohome-specid-szukaj.js` — nazwa CN → `specid`: menu marek Autohome (GBK) → model →
  `config/series/<id>.html` (w sprzedaży, nazwy zaciemnione → wieloznacznik) + `www.autohome.com.cn/<id>/sale.html`
  (wycofane, nazwy czyste). Typy: `dokladne`, `sklad-znakow` (inna kolejność słów), `przyblizone`, `+dopisek`
  (człon z nazwy modelu rozstrzyga DM-i vs EV), `rok±1`. Warianty modelu: `唐DM` → `唐新能源`. Cache
  `uploads/asiaauto/autohome-catalog/_lookup/` (indeks modeli 30 dni, listy wersji 7 dni). Nic nie zapisuje.
- `scripts/dolej-spec-autohome-dongchedi.php [limit] [apply]` — orkiestracja: kandydaci → szukanie →
  pobranie katalogu → kontrola wymiarów → kopia JSON → `autohome-catalog-merge.php`.
- **Cron 19:45** `dolej-spec-autohome-dongchedi.php 60 apply` (po 19:35 katalogu che168, przed 19:55
  `zbuduj-specs` — ten łapie stempel `_asiaauto_spec_catalog_at`). Log `~/.claude/dolej-spec-autohome-dongchedi.log`.
  Kopia crontaba: `~/backups/primaauto/2026-10-04/crontab-przed-dolej-autohome-dongchedi.txt`.
- Dry-run nocnego (obejmuje też chude sprzed 23.09): 51 kandydatów → 17 do zapisu, 1 odrzucone na
  wymiarach (#446295, 4590 vs 4600), 33 bez pary.

## Do sprawdzenia

- 06.10 9:00 (kalendarz „Auranet Claude”): log pierwszego biegu, odrzucone na wymiarach, przebudowa wyszukiwarki.
- Otwarte od 16.08: chude oferty dongchedi na `publish` czy `draft` do czasu dolewki.
- Mapa katalogu (`data/autohome-catalog-map.php`) gubi 9–15 parametrów z wartością na ofertę — dlatego po dolaniu
  ~235–300 pól, nie ~342.
