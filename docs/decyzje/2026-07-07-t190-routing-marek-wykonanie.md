# ADR — T-190: decyzje routingu marek + wykonanie (2026-07-07 wieczór)

> Decyzje: **Janek** („działaj, decyzje wg twoich rekomendacji, trumpchi dropujemy"), po raportach:
> rekoncyliacja + audyt DFS 364 hubów (`auratest:primaauto-t190-{rekoncyliacja,dfs-audyt}-2026-07-07.md`).
> Wykonanie: ta sama sesja, pełny trace per krok w `docs/seo/t190-log.md` (KROK 1-4).
> Komplement do ADR `2026-07-07-t190-galaxy-pod-geely.md` (Galaxy) — tu pozostałe marki.

## Decyzje (wszystkie WYKONANE 2026-07-07)

| # | Decyzja | Uzasadnienie / dane |
|---|---|---|
| 1 | **BJ30 → BAIC, model „BJ30"** (drop „Beijing Off-road") | DFS: `baic bj30` **3600/mc** vs `beijing bj30` 0 — najwyższy wolumen całego routingu |
| 2 | **„Beijing 212" → marka BAW**, model „212 T01" | Producent = Beijing Auto Works (BAW), wydzielony z BAIC; DFS 0/mc = porządek merytoryczny |
| 3 | **Konsolidacja marek Beijing ×4** → zostają `baic` + `baw` | `beijing`(0), `beijing-212`, `beijing-off-road`, `212` skasowane + 301 |
| 4 | **Yangwang → BYD** (wzorzec Leopard: sub-marka w nazwie modelu) | DFS: `yangwang u9` 4400 > `byd yangwang u9` 1900 — nazwa modelu „Yangwang U9" łapie oba |
| 5 | **Maextro = własna marka** — zdjęte 301 `maextro`→`luxeed` | 301 szło do złego producenta (JAC vs Chery); model „Zunjie S800"→„S800" |
| 6 | **Dongfeng Fengxing → Dongfeng** (Xinghai T5, Lingzhi EV) | Zasada fold pod prawdziwego rodzica |
| 7 | **Drop „Trumpchi" z nazw modeli GAC** (M8, M6, S7 + mapowanie) | DFS: `gac m8`/`gac s7` 110/mc vs `gac trumpchi *` 0 |
| 8 | **Lotus: jedna marka `lotus`** (merge `lotus-cars`) | Duplikat marki; rekomendacja zaakceptowana |
| 9 | **Changan Qiyuan → Changan**, modele „Qiyuan X" | Zasada fold; **naprawiony błędny V61 `changan-qiyuan`→`nevo`** (→`changan`) |
| 10 | **Chery Fengyun → Chery Fulwin** | Fulwin = eksportowa nazwa Fengyun (ten sam sub-brand); zgodne z istniejącym V61 |
| 11 | **Shangjie: jedna marka `shangjie`**, mapowanie mark_eu „Shangjie" | **ZASTĘPUJE** etykietę „SAIC Shangjie" z decyzji 2026-06-18 (auta siedziały pod `shangjie`, mapowanie tworzyłoby pustego bliźniaka `saic-shangjie`) |
| 12 | **Hyper → GAC, modele „Aion Hyper GT/HT/HL/SSR/A800"** | Decyzja z 07-07 rano, wykonana; DFS 0/mc = zero ryzyka |

## Zmienione obiekty (rollback reference)

- **Backup:** `~/backups/primaauto/2026-07-07/taxonomy-pre-T190-krok3.sql` (4 tabele) + `.bak`: `brand-mapping-v6.1.php.bak-2026-07-07-t190-v62`, `class-asiaauto-redirects.php.bak-2026-07-07-t190-krok3`, `class-asiaauto-importer.php.bak-2026-07-07-t190-guard`.
- **Marki skasowane (term_id):** galaxy 6579, lotus-cars 5665, gac-aion-hyper 5485, chery-fengyun 5181, saic-shangjie 7157, changan-qiyuan 4769, beijing-off-road 4779, beijing-212 6522, beijing 5254, 212 5687, yangwang 5640, dongfeng-fengxing 4675.
- **Serie kluczowe:** 6603→„BJ30"(bj30, baic), 5688→„212 T01"(baw), 5338→„S800"(maextro), 3381→„M8", 3377→„M6", nowy 7177 „Galaxy M7"(m7, geely).
- **Redirecty:** V61 ±5 wpisów (w tym USUNIĘTY maextro→luxeed), V62 +7.
- **Niezmienniki po wykonaniu:** mismatch make↔serie-parent = 0 · sieroty serie z autami = 0 · duplikaty po nazwie pod tym samym make = 0.

## Konsekwencje / obserwacja

- Guard importera (ADR Galaxy, KROK 1) chroni **serie**; marka nadal powstaje po `slugify(mark_eu)` — niezmapowany import (np. ręczny Che168 spoza `che168-model-map.php`) może odtworzyć skasowaną markę. Ochrona = kompletność mapowań; ewentualny mark-alias-guard = osobny mały task.
- Huby z odzyskanym/nowym stanem (BJ30, BAW, Maextro, Galaxy M7) — bez treści wiki/FAQ; kandydaci do pipeline'u contentowego przy następnej fali.
- Pomiar GSC efektów (BJ30 3600/mc!) — sensowny ~2-3 tyg. po zaindeksowaniu.
