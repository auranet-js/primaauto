# Sieroty marek/modeli + treści hubów (2026-06-19)

> Kontynuacja po T-019. Prompt źródłowy: `tmp/PROMPT-orphany-huby-2026-06-19.md`. Napływ ~700 ofert w czerwcu (Dongchedi + ręczny Che168) dorzucił nowe nie-wpięte modele.

## Kontekst
Skany (produkcja, DB `host476470_wp521`, prefix `wp7j_`): serie `parent=0 count>0`, rozjazd `parent ↔ _asiaauto_primary_make_slug`, kontaminacja warstwy 2 (listing-make ↔ serie-pms), marki count>0 vs treść huba. Backup: `~/backups/primaauto/2026-06-19/taxonomy-pre-orphany-1440.sql` (terms+term_taxonomy+termmeta+term_relationships).

## Wykonane (APPLY 2026-06-19)

### 1. Dekontaminacja
3 listingi **Dongfeng Yipai eπ007** (271247, 272215, 272231) wisiały pod serie `007` (4829, Zeekr) → przepięte na `yipai-007` (6563). Zeekr `007` = 8 (czyste), `yipai-007` = 9.

### 2. Sieroty serie wpięte (9 → 0)
8 z pierwotnego skanu + 1 doniesiona w trakcie importu (Galaxy L7). Każda: `parent` = make + `_asiaauto_primary_make_slug` spójne, regen tytułu.

| term | model | parent (make) | uwaga |
|---|---|---|---|
| 4181 | Ford EVOS | ford | |
| 5425 | Dongfeng Rich 7 | dongfeng | |
| 6207 | Hongqi HS6 PHEV | hongqi | pms był NULL → ustawiony |
| 6840 | Foton Dajiangjun F9 | foton | |
| 6957 | IM LS8 | im-motors | |
| 6849 | Chery Fengyun X3L | **chery-fulwin** | przepięty z chery-fengyun (deprecated) |
| 6881 | Changan Qiyuan Q05 Classic | **nevo** | przepięty z changan-qiyuan (deprecated) |
| 6945 | GAC Aion Hyper A800 | **gac** | przepięty z gac-aion-hyper (deprecated) |
| 7159 | Geely Galaxy L7 | geely | |

**LEKCJA (krytyczna):** dla serie pod marką deprecated (klucz V61) wzorzec `parent/pms = marka-deprecated` jest **ZEPSUTY** — kanoniczny URL 301-uje na markę-cel, gdzie router nie rozwiązuje termu → strona 200 ale PUSTA (brak H1/lead/FAQ). Działający wzorzec to `parent/pms = marka-CEL` (jak `fengyun-x3` → chery-fulwin), slug z prefiksem zostaje. Większość rodzeństwa pod gac-aion-hyper/chery-fengyun/changan-qiyuan ma zepsuty wzorzec (pre-existing, część decyzji sub-brandowej — nie ruszane hurtowo).

### 3. Treść hubów (flaga `_asiaauto_seo_rework`)
- **9 hubów modeli** (`v1-2026-06-19`): lead AEO answer-first + H1 „cena w Polsce i import z Chin" + 6×H2 wiki (`{{LISTINGS_BAR}}`) + 5×FAQ (FAQPage). Metoda: `docs/seo/hub-rework-method-2026-05-30.md`. Wszystkie `import_only`, count=1.
- **3 huby marek** (`v1-make-2026-06-19`): `ford`, `foton`, `toyota` — wypadły z reworku 2026-06-08 (count≥12). lead + `_asiaauto_facts` (JSON) + wiki.
- **Uczciwość dostępności (web-recheck 2026-06-19):** Geely/GAC/Changan/Hongqi/Chery SĄ w PL (Jameel/AADC/Deepal/Omoda-Jaecoo) — lead mówi „ten model spoza polskiej oferty marki", NIE „marka niedostępna". Toyota Corolla Cross jest w PL → ujęte jako „wersja chińska z importu". Ford EVOS / Foton — modele rynku chińskiego spoza europejskiej gamy.
- Artefakty: `tmp/hub-content-2026-06-19.json`, `tmp/make-hub-content-2026-06-19.json`, skrypty `tmp/deploy-*hub-content-2026-06-19.php` (idempotentne, dry-run+apply, `html_entity_decode` defensywnie na wiki).

### 4. Indeksacja
12 URL-i (9 modeli + ford/foton/toyota) przez `~/bin/index-submit --type URL_UPDATED` → 12/12 OK, budżet ad-hoc 12/100 (rezerwa primaauto nietknięta). Lista: `tmp/index-urls-orphany-2026-06-19.txt`.

## Świadomie NIE zrobione (osobne decyzje)
- **Bug importera (parent-wiring nowych termów serie)** — realna przyczyna nawrotów sierot. Dowód: `IM LS8` i `Fengyun X3L` BYŁY w `brand-mapping-v6.1.php`, a mimo to powstały jako `parent=0`. Mapowanie ≠ wpięcie parenta. Fix = w `setTaxonomyAndMeta` ustawiać parent dla nowo tworzonego termu serie (krucha strefa importera).
- **brand-mapping 6 modeli** (EVOS, Rich 7, HS6 PHEV, Dajiangjun F9, Q05 Classic, Hyper A800) — niska wartość (nie zapobiega sieroctwu — patrz wyżej), wymaga surowych kluczy CN z `getOffer()`.
- **Restrukturyzacja sub-brandów** (Galaxy jako osobna marka, GAC S7/Luxeed split, merge eπ007 e007↔yipai-007, marki-cienie V61) — wg listy „NIE ruszaj" w prompcie.

## Smoke
Wszystkie 9 modeli + 3 marki: HTTP 200, H1 + lead + FAQ/fakty obecne, zero literalnych `{{LISTINGS_BAR}}` / `&lt;`. 3 deprecated make-huby (dongfeng-fengxing, chery-fengyun, beijing-off-road) potwierdzone jako poprawnie 301→cel z treścią (NIE luka).
