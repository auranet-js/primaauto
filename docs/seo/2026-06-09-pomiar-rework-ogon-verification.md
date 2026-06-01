# PROMPT — Weryfikacja reworku hubów OGON (indeksacja + skuteczność GSC)

> **Jak użyć:** w nowym wątku Claude Code (w `~/projekty/primaauto`) napisz:
> „Wykonaj `docs/seo/2026-06-09-pomiar-rework-ogon-verification.md`".
> Prompt jest samowystarczalny — poniżej masz pełny kontekst, listę URL-i, baseline'y i protokół.

---

## Cel

Sprawdzić, czy reworki hubów cena/AEO z sesji **2026-05-31** (OGON count 12-17 + Tier B Denza Z9 GT):
1. **Zostały zaindeksowane** przez Google (Indexing API zgłoszony 2026-05-31, `--yes`, indeksacja 122/150).
2. **Działają pozytywnie** — pozycje/CTR na intent **cena / cena w polsce / gdzie kupić** (GSC = realne PL), bez regresji.
3. **Tier B nie ucierpiał** — `denza/z9-gt-dm-i` musi utrzymać pozycję na frazie **„import"** (baseline poz 2,5 / 33% CTR). Jeśli spadek → cofnąć lead/cena-elementy z tego huba.

**Timing:** indeksację można sprawdzić od razu. **Sygnał GSC (pozycja/CTR) wymaga ~7-10 dni** od reworku — czyli sensowny pomiar od **~2026-06-09**. Jeśli odpalasz wcześniej, zrób tylko część indeksacyjną i zaznacz, że GSC jeszcze nie dojrzało.

---

## NAJPIERW przeczytaj kontekst

- `ls ~/.claude/projects/-home-host476470-projekty-primaauto/memory/` → `MEMORY.md`
  + `project_session_2026_05_30_hub_rework_pilot.md` (PEŁNY STAN: metoda, lista done, baseline'y, sekcja „POMIAR GSC — DO ZROBIENIA")
  + `reference_google_seo_stack.md` (START dla SEO — OAuth scopes, skrypty GSC/GA4, sekrety w `~/secrets/google/`)
  + `feedback_no_decisions_without_data.md` (pozycje TYLKO z danych, nie z intuicji)
- `docs/seo/hub-rework-method-2026-05-30.md` (metoda per hub)

Środowisko: WP w `~/domains/primaauto.com.pl/public_html`, DB `host476470_wp521` prefix `wp7j_`. `cd` do public_html dla `wp eval`.

---

## NARZĘDZIA (gotowe, w tmp/)

- **`tmp/gsc-hub.py <hub_path> <seed...>`** — GSC frazy per hub (pozycja+CTR+impresje+kliknięcia), czyta `~/secrets/google/`. Reusable. Przykład:
  `python3 tmp/gsc-hub.py samochody/byd/han-ev "han ev cena" "byd han cena" "han ev gdzie kupić"`
- **`~/bin/index-submit --status`** — stan liczydła indexing (doba Pacific). NIE zgłaszaj ponownie tych samych URL-i bez powodu — to marnuje pulę 150.
- Lista wszystkich zreworkowanych termów (flaga):
  `cd ~/domains/primaauto.com.pl/public_html && wp eval 'echo implode(",",$GLOBALS["wpdb"]->get_col("SELECT term_id FROM wp7j_termmeta WHERE meta_key=\"_asiaauto_seo_rework\" AND meta_value=\"v1-2026-05-30\""));'`
  (powinno zwrócić ~93 termów; w tym 21 z sesji 2026-05-31 — lista niżej)

---

## Sprawdzenie indeksacji (część A — można od razu)

Dla każdego z 21 URL-i sesji 2026-05-31 sprawdź status w Google. Dwie metody:

1. **GSC URL Inspection API** (preferowane, dokładne) — przez OAuth z `~/secrets/google/`. Sprawdź `coverageState` / `indexStatusResult.verdict` per URL. Jeśli jest gotowy skrypt w `reference_google_seo_stack.md` (URL Inspection) — użyj; jeśli nie, dopisz krótki `tmp/gsc-inspect.py` (endpoint `urlInspection/index:inspect`, property `sc-domain:primaauto.com.pl` lub `https://primaauto.com.pl/`).
2. **Fallback** — `curl -s "https://www.google.com/search?q=site:primaauto.com.pl/samochody/<make>/<slug>/"` (orientacyjne, Google może rate-limitować; URL Inspection jest źródłem prawdy).

Wynik: tabela `hub | indexed? (verdict) | last crawl | uwagi`. Zgłoś które NIE są jeszcze zaindeksowane (jeśli >7 dni od submitu i wciąż „Discovered/Crawled - not indexed" → rozważ ponowny `index-submit`, pytając najpierw o budżet).

## Pomiar skuteczności GSC (część B — od ~2026-06-09)

Dla każdego huba odpal `tmp/gsc-hub.py` z seedami cena/intent. Porównaj **pozycję i CTR na frazy cenowe** z baseline (poniżej, tam gdzie udokumentowany) i — jeśli da się — z oknem **przed reworkiem** (GSC pozwala porównać zakresy dat: weź 14 dni przed 2026-05-31 vs 14 dni po).

Agreguj: czy segment 21 hubów `v1` poprawił pozycje/CTR na intent cenowy. Pamiętaj: **GSC nie mierzy AEO** (AI Overview/Perplexity/ChatGPT) — to limit metody, odnotuj.

### TIER B — kontrola krytyczna (NIE psuć importu)

`denza/z9-gt-dm-i` (term 4660): sprawdź frazy **„denza z9 gt import", „z9 gt import", „denza z9 gt"** — baseline **poz 2,5 / 33% CTR na „import"**. Wariant ochronny miał H1/title nietknięte (import-led), cena tylko w lead/body/FAQ.
- Jeśli pozycja/CTR na „import" **spadła** → rework zaszkodził: cofnij lead + cena-elementy z 4660 (backup: `~/backups/primaauto/2026-05-30/termmeta-4660-pre-rework.sql`).
- Jeśli stabilna/lepsza → wariant ochronny zadziałał, zostaw.

---

## Lista 21 hubów sesji 2026-05-31 (URL + term + status + seedy GSC)

Wszystkie pod `https://primaauto.com.pl`. Backup termmeta: `~/backups/primaauto/2026-05-30/termmeta-<TID>-pre-rework.sql`.

| # | URL | TID | status PL | seedy GSC (cena/intent) |
|---|---|---|---|---|
| 1 | /samochody/tank/700-hi4-t/ | 3981 | import_only-forward | „tank 700 hi4-t cena", „tank 700 cena" |
| 2 | /samochody/jetour/dashing/ | 4530 | salon_available | „jetour dashing cena", „jetour dashing" |
| 3 | /samochody/xpeng/g6/ | 4761 | salon_available | „xpeng g6 cena", „xpeng g6 cena w polsce" |
| 4 | /samochody/leapmotor/c16/ | 5154 | import_only-niuans | „leapmotor c16 cena", „leapmotor c16" |
| 5 | /samochody/nissan/n6/ | 6514 | import_only-niuans | „nissan n6 cena", „nissan n6" |
| 6 | /samochody/volvo/s90/ | 3997 | salon_available (LWB) | „volvo s90 cena", „volvo s90 long wheelbase" |
| 7 | /samochody/haval/big-dog/ | 4419 | import_only-forward | „haval big dog cena", „haval dargo cena" |
| 8 | /samochody/byd/atto-2/ | 3758 | salon_available | „byd atto 2 cena", „byd atto 2 cena w polsce" |
| 9 | /samochody/deepal/s07/ | 4212 | salon_available | „deepal s07 cena", „deepal s07 erev" |
| 10 | /samochody/denza/z9-dm-i/ | 4654 | import_only neutral | „denza z9 cena", „denza z9 dm-i" |
| 11 | /samochody/leapmotor/b10/ | 5153 | salon_available | „leapmotor b10 cena", „leapmotor b10" |
| 12 | /samochody/byd/han-ev/ | 3707 | salon_available | „byd han cena", „byd han ev cena" |
| 13 | /samochody/xpeng/g9/ | 4763 | salon_available | „xpeng g9 cena", „xpeng g9 cena w polsce" |
| 14 | /samochody/xpeng/x9/ | 4767 | salon_available | „xpeng x9 cena", „xpeng x9 erev" |
| 15 | /samochody/mazda/cx-5/ | 5276 | salon_available-niuans | „mazda cx-5 cena", „mazda cx-5 chiny" |
| 16 | /samochody/byd/sealion-7/ | 3760 | salon_available | „byd sealion 7 cena", „sealion 7 cena" |
| 17 | /samochody/nissan/qashqai/ | 3941 | salon_available-niuans | „nissan qashqai cena", „qashqai chiny" |
| 18 | /samochody/xpeng/p7/ | 4765 | salon_available | „xpeng p7 cena", „xpeng p7" |
| 19 | /samochody/voyah/free/ | 5075 | salon_available | „voyah free cena", „voyah free" |
| 20 | /samochody/chery-fulwin/a9l/ | 5185 | import_only | „chery fulwin a9l cena", „fulwin a9l" |
| 21 | /samochody/denza/z9-gt-dm-i/ | 4660 | **Tier B import-jewel** | **„denza z9 gt import", „z9 gt import"** (NIE cena — kontrola importu!) |

## Smoke-recheck (szybki, opcjonalny — czy huby nadal renderują poprawnie)

Dla próbki (lub wszystkich) sprawdź, że nic się nie zepsuło od deploya:
```bash
for u in tank/700-hi4-t jetour/dashing xpeng/g6 denza/z9-gt-dm-i byd/han-ev; do
  echo "== $u =="
  curl -s "https://primaauto.com.pl/samochody/$u/" | grep -oc "LISTINGS_BAR"   # ma być 0 (brak wycieku tokenu)
done
```
Plus: HTTP 200, H1 obecny, FAQPage JSON-LD parsuje. Dla Tier B (z9-gt-dm-i) H1 MUSI nadal być „— import z Chin" (BEZ „cena").

---

## Output końcowy (czego oczekuję)

1. **Tabela indeksacji** 21 hubów (indexed / nie / data crawl).
2. **Tabela GSC** intent cenowy: pozycja+CTR per hub, porównanie przed/po (jeśli okno dojrzałe).
3. **Werdykt Tier B** (z9-gt-dm-i): import utrzymany czy spadek → decyzja.
4. **Rekomendacja:** (a) co działa, (b) huby do poprawy/re-index, (c) czy ruszać 3 destrukcyjne specjalne taksonomiczne (GAC S7 Luxeed-split / Galaxy Starship 8 dedup / Galaxy E5 ft — opisane w memory, czekają na zgodę Janka).
5. Po pomiarze — **zaktualizuj memory** `project_session_2026_05_30_hub_rework_pilot.md` (sekcja POMIAR GSC) wynikami.

**Zasady:** żadnych decyzji „lepsze/gorsze" bez danych GSC. Nie zgłaszaj ponownie do Indexing API bez sprawdzenia budżetu (`index-submit --status`) i zgody Janka przy wejściu w rezerwę. Komunikacja po polsku, „My" nie „ja".
