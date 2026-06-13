Nowy wątek (primaauto, `cd ~/projekty/primaauto`): CONTENT answer-first dla hubów modeli bez lead/wiki.

## Kontekst
Spec-hub Faza 2 DONE (2026-06-13, v0.32.74) — tabela techniczna (`AsiaAuto_Spec`) renderuje się LIVE na 357 hubach modeli, nad barami stocku. **TO ZADANIE = osobna warstwa: answer-first CONTENT (lead AEO + wiki H2 + FAQ), NIE spec** (spec już jest, nie dotykaj generatora/tabeli).

## NA START przeczytaj
1. `docs/seo/hub-rework-method-2026-05-30.md` — METODA answer-first (lead nad barami stocku + H1 suffix + wiki 7×H2 + FAQ 5×Q FAQPage). Źródło prawdy dla tego tasku.
2. `docs/seo/spec-hub-rework-2026-06-13.md` — co zrobione w spec (kontekst).
3. Memory: `project_session_2026_05_30_hub_rework_pilot` (metoda + lista 3 destrukcyjnych specjalnych taksonomicznych CZEKAJĄCYCH NA ZGODĘ), `project_make_hubs_rework_2026_06_08`, `project_spec_hub_rework_2026_06_13`, MEMORY.md.

## KROK 1 — AUDYT (NIE zakładaj że to 4 huby!)
Janek myślał „4 świeże huby", ale audyt 2026-06-13 pokazał: **141 hubów count>0 BEZ `_asiaauto_lead`** (brak answer-first). Ruslan dodał m.in. **Toyota Corolla Cross**. Lista z liczbą ofert: `tmp/spec-hub-no-content-141-2026-06-13.tsv` (count / slug / name / term_id, sort malejąco).

Re-audytuj na świeżo (stock się zmienia):
```
wp eval 'foreach(get_terms(["taxonomy"=>"serie","hide_empty"=>true,"fields"=>"ids"]) as $t){ if(!get_term_meta($t,"_asiaauto_lead",true)){ $o=get_term($t,"serie"); echo $o->count."\t".$o->name."\t$t\n"; } }' | sort -rn
```

## KROK 2 — TRIAŻ (krytyczne, przed pisaniem czegokolwiek)
Podziel 141 na 3 koszyki (REGEXP name+slug+post_titles, obie taksonomie make/serie — wzorzec z KROK 1 §4 runbooka):

- **(A) SKAŻONE taksonomicznie — NIE pisać contentu, eskalować/naprawić first.** Z top listy znane: `luxeed/s7` („Trumpchi S7" = GAC S7 skażony Luxeed 12/14), Galaxy Starship 8 dup (`geely/galaxy-starship-8-phev` + `geely/starship-8-phev` + term 3406↔6582), Galaxy E5 dup (`geely/galaxy-e5` + `geely/e5`), `galaxy/galaxy-m9`, `aito/m6` („Trumpchi M6" — AITO vs Trumpchi mislabel), `gac-aion-hyper/hyper-hl`. **3 destrukcyjne specjalne z memory hub_rework_pilot CZEKAJĄ NA ZGODĘ Janka — NIE klikać panelu, NIE scalać bez „ok".**
- **(B) NOWE/czyste modele do contentu** — Toyota Corolla Cross, VW Golf/CC/ID.4 CROZZ/ID.4 X, smart #1, Changan CS75, Leapmotor C01/Lafa5, Voyah Taishan, Jetour X90 PLUS, Haval H9 itd. + 4 keepery z KROK 1 (`geely/l7`=7153, `geely/l6`=7155, `avatr/avatr-07`=6906, `haval/haval-h5`=6715).
- **(C) niski stock / nisza** — niżej w priorytecie.

**Pokaż triaż Jankowi i ustal próg** (30.05 robił count≥12) ZANIM zaczniesz pisać. Nie pisz 141 na ślepo.

## KROK 3 — CONTENT (metoda 30.05, koszyk B, wg stocku malejąco)
Per hub, meta per-term:
- `_asiaauto_lead` — answer-first, samodzielna odpowiedź (cena w PL + dostępność import/salon + kluczowy fakt), diakrytyki PL (NIE esc do ASCII).
- `_asiaauto_h1_suffix` — np. „cena w Polsce i import z Chin".
- `_asiaauto_pl_availability` + wiki 7×H2 + FAQ 5×Q (FAQPage JSON przez `asiaauto_faq_json`).
- `_asiaauto_seo_rework=v1-2026-06-XX`.

**Prawda per model:** web-recheck dostępności w salonach PL (Jetour/Omoda/MG/Zeekr/Leapmotor/smart/VW/Toyota SĄ w salonach PL → NIE pisać „wyłącznie z importu"; wzorzec salon_available z memory pilot). NIE wymieniaj nazw dealerów konkurencji (memory `feedback_no_competitor_dealer_names`). Smart quotes psują JSON FAQ (`feedback_smart_quotes_break_json`) — ASCII cudzysłowy.

Szablon `taxonomy-serie.php` (motyw **primaauto2026**, NIE asiaauto!) renderuje lead/wiki/FAQ automatycznie z meta. Strefa krucha — pokaż diff przed deploy jeśli ruszasz szablon (raczej nie trzeba, tylko meta).

## KROK 4 — INDEXING
Każdy zrobiony hub → `~/bin/index-submit --project primaauto --type URL_UPDATED --url <hub>`. **UWAGA: równolegle leci batch indexingu spec-hub** (189 hubów zostało, `tmp/spec-hub-indexing-remaining-2026-06-13.txt`) — wspólny budżet 100/dobę (Janek może podnieść). Sprawdź `index-submit --status` i skoordynuj, żeby nie przepalić puli. Pauza+test co 20.

## Zasady
- Slugi `asiaauto-*` ZOSTAJĄ (rebrand tylko user-facing). Backup przed zapisem produkcyjnym. Maile do klienta = NIGDY (tylko do Janka). Content przed Ads.
