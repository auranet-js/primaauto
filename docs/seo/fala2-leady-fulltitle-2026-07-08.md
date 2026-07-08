# Fala 2 answer-first leady + fix H1 `_serie_full_title` (2026-07-08)

Kontynuacja audytu widoczności 2026-07-07 (`tmp/seo-audyt-2026-07-07.md`). Zadanie #1 z promptu („linkowanie 4 hubów striking") **odrzucone jako pozorna dźwignia** — zwiad potwierdził, że huby już są dolinkowane 3 sekcjami cross-link (`renderModelPills`/`relatedModels`, sort count DESC). Pivot na falę 2 answer-first lead (większa powierzchnia, udowodniony mechanizm = kliki GSC +114%).

## Answer-first leady (35 hubów)

Pole `_asiaauto_lead` (render nad barami stocku) + `_asiaauto_h1_suffix` = „cena w Polsce i import z Chin" + `_asiaauto_pl_availability` + flaga `_asiaauto_seo_rework`. Metoda: `docs/seo/hub-rework-method-2026-05-30.md`.

- **Partia ≥3 (Galaxy ×3 + 11):** Galaxy Starship 8 PHEV / E5 / M9, Voyah Zhuiguang L, Jetour Shanhai L7+, Yangwang U7, Chery Explorer 06 C-DM, smart #1, Forthing U-Tour V9, Haval Menglong, Mazda EZ-60, M-Hero 917, Volvo XC70, GAC M6. Flaga `v3-fala2-2026-07-07`.
- **Partia =2 (17):** Yangwang U8, Dongfeng Fengshen L8/L7 PHEV, Exeed Zhuifeng, LEVC L380, Haval Big Dog PLUS/Raptor PHEV, iCAR 03T, JMC Fushun, Lotus Emeya, Lynk&Co 03/06/07, NIO ES7, smart #5/#3, Tank 500 Hi4-Z. Flaga `v3-fala2-2026-07-08`.

### Reguła pl_availability (web check per model — marka w PL ≠ model w PL)
`salon_available` tylko gdy dokładnie ten model jest w polskim salonie (kąt cenowy import vs salon). Ustalenia:
- **Galaxy E5** = Geely EX5 (salon Jameel od 169 900 zł); **Mazda EZ-60** = CX-6e (od 206 100 zł, ale EU=BEV, nasz import=EREV); **M-Hero 917** (od 799 000 zł); **Forthing U-Tour V9** (HEV od 169 900 zł); **BJ30** (salon tylko DHT ~175k, nasza benzyna od 117k); **Lotus Emeya** (439–707k) — salon_available.
- Pułapki import_only: smart #1/#3/#5 (nowa gen tylko reimport DE), Dongfeng/Exeed/Chery/Jetour/GAC/Haval (marka w PL, ten model nie), Lynk&Co (EU tylko 01/02).

## 4 pełne huby (wiki 7×H2 + FAQ 5Q + lead)

BJ30, Lotus Eletre, Lotus ForMe (= w PL Eletre X), Maextro S800. Pipeline: 4 subagenty (research spec+pl_availability → config/wiki/faq wg wzorca `tmp/content/byd-e7-*`) → `wp eval-file tmp/deploy-hub.php <config>` → smoke (FAQPage+H1+lead) → index. Flaga `v1-2026-07-08`.
Gotcha: subagenci wygenerowali treść bez polskich diakrytyków (za szeroka interpretacja „ASCII cudzysłowy w FAQ") — wykryte w review, poprawione, zweryfikowane grepem przed deployem.

## Fix routingu GAC M6

Term 3377 miał `_asiaauto_primary_make_slug=aito` (błąd) → URL `/samochody/aito/m6/` kolidujący z AITO M6 (6557). Fix: `wp term meta update 3377 _asiaauto_primary_make_slug gac`. Rozdzielone: gac/m6=GAC M6, aito/m6=AITO M6. Gotcha: po zmianie stary render trzymał się w cache → `wp cache flush`.

## Fix H1 `_serie_full_title` (marka+model)

H1 huba serie (`themes/asiaauto/taxonomy-serie.php:44,84`) = `_serie_full_title ?: $term->name`. 68/301 hubów count≥1 nie miało mety → H1 bez marki („S800", „Eletre", „M6"). `<title>` był OK (osobny mechanizm). Fix jednorazowy: ustawiono `_serie_full_title` dla **35 termów** (33 marka+model + dedup Dongfeng Fengshen L7 + korekta Yangwang U7). Pominięto 33, których fallback `$term->name` już zawiera markę (zapis=no-op, ryzyko encoding `Lynk & Co`).

**Reguła DFS (search volume PL, $0,09) — marka-rodzic nie zawsze pomaga:**
- Yangwang (silna sub-marka): „yangwang u9" 4400 > „byd yangwang u9" 1900 → H1 bez „BYD" (skorygowano U7 „BYD Yangwang U7"→„Yangwang U7").
- Galaxy (słaba sub-marka): „geely galaxy e5" 110 > „galaxy e5" 20 → H1 „Geely Galaxy X".
- Zwykłe marki + kolizje generyczne (M6=BMW, S800) → marka konieczna.

Docelowo generator `full_title` wymaga podejścia indywidualnego per (sub)marka, nie mechanicznego `make+name`.

## Ślady

- Backupy: `~/backups/primaauto/2026-07-08/termmeta-pre-{fala2-batch3,fullhubs,fulltitle}.sql`, `~/backups/primaauto/2026-07-07/termmeta-pre-{fala2-galaxy,fala2-batch2,striking-distance}.sql`.
- Indexing: 35 hubów zgłoszonych (fix H1 NIE re-indexowany — drobna zmiana on-page).
- **Zostało w fali 2:** count=1 (30 modeli, najniższy ROI — 1 oferta/hub; prawie wszystkie mają wiki/faq → sam lead).
