# 2026-06-02 — Wdrożenie dokumentów prawnych od prawnika (regulamin śude + polityka)

## Kontekst

Spór z poprzednich sesji (memory `project_legal_docs_model_mismatch_2026_05_30`): zakładaliśmy, że 3 docx od prawnika Ruslana operują na błędnym modelu **sprzedaży**, niezgodnym z faktycznym modelem **pośrednictwa** (umowa zlecenia sprowadzenia / Zleceniodawca↔Zleceniobiorca / zwrotny depozyt gwarancyjny / prowizja — `class-asiaauto-contract.php`, `kupna-sprzedaż` 0×).

Prawnik odpisał (2026-06-02), że dokumenty są OK; Ruslan nie polemizuje. Janek zlecił ocenę merytoryczną zamiast automatycznego przyjęcia/odrzucenia.

## Ustalenie (zweryfikowane na treści dokumentów)

**Założenie było błędne — prawnik miał rację co do swoich dokumentów:**

- `Regulamin śude.docx` — model „Usługodawca", reguluje wyłącznie *nieodpłatne* usługi elektroniczne (przeglądanie, formularz kontaktowy, zapytania); „umowa" = umowa o świadczenie usług drogą elektroniczną. Zero „Sprzedawcy", „kupna-sprzedaży", „zaliczki".
- `Regulamin strony.docx` — też „Usługodawca", charakter informacyjno-kontaktowy. Zero modelu sprzedaży.
- `Polityka.docx` — „sprzedaż" pada raz, jako zabezpieczone wyliczenie podstaw RODO („importu, sprzedaży, pośrednictwa lub innej usługi", art. 6 ust. 1 lit. b) — dokładnie jak twierdził prawnik. Poprawnie opisuje transfer poza EOG do USA (SCC) — lepiej niż ówczesna live.

Model sprzedaży siedział w **starych żywych stronach**, nie w docx. Nasz zarzut celował w zły obiekt (rozjazd zakresu). Rozróżnienie regulamin śude ≠ regulamin strony też poprawne (śude wymagany ustawą z 18.07.2002; regulamin strony opcjonalny, ~80% pokrycia → pominięty zgodnie z rekomendacją prawnika).

## Wdrożenie

| Cel (live) | Strona | Źródło | Plik źródłowy w repo |
|---|---|---|---|
| `/informacje/regulamin/` | ID 153866 | `Regulamin śude.docx` | `docs/legal/regulamin-sude-2026-06-02.blocks.html` |
| `/polityka-prywatnosci/` | ID 198526 | `Polityka.docx` | `docs/legal/polityka-prywatnosci-2026-06-02.blocks.html` |

- Tytuł widoczny 153866 zostaje „Regulamin" (krótko); pełna nazwa „Regulamin świadczenia usług drogą elektroniczną" w H2 treści.
- `Regulamin strony.docx` — **pominięty** (decyzja Janka).
- Backup poprzedniej treści: `~/backups/primaauto/legal-2026-06-02-1910/page-{153866,198526}-content.bak.html`.

### Decyzje redakcyjne na polityce (placeholdery prawnika)

1. Notka „do weryfikacji technicznej: czy aktywne są GA/GTM/Ads/Meta/TikTok Pixel" → zastąpiona faktem (audyt trackerów 2026-05-30): aktywne **Google Analytics, Google Tag Manager, Google Ads, Meta Pixel (z Conversions API)**. **TikTok wykreślony** (nie używany do śledzenia — tylko profil społecznościowy).
2. Notka „do uzupełnienia po audycie technicznym: lista dostawców/ID/okresy/linki" → usunięta; sekcja „Narzędzia analityczne i marketingowe" przepisana na konkretną listę. Szczegółowa tabela ID/okresów — opcjonalne rozszerzenie później.
3. Snippety „Klauzula pod formularz kontaktowy" + „Checkbox marketingowy" → **pominięte** w treści polityki (to wsady do formularza, nie do strony). Otwarte: zweryfikować, czy formularz kontaktowy ma klauzulę RODO.
4. Sekcja transfer poza EOG — usunęła fałszywą deklarację „nie przekazujemy poza EOG" z poprzedniej live (sprzeczną z GA4/Ads/Meta+CAPI → USA).

### Fix powiązany w kodzie (serwer)

`class-asiaauto-order-wizard.php:188` — checkbox akceptacji regulaminu: „serwisu **AsiaAuto.pl**" → „serwisu **Prima-Auto**"; link `/regulamin/` (301) → `/informacje/regulamin/` (bez przeskoku). `php -l` czysty, backup `*.bak-2026-06-02-legal-links`. Bez bumpa `ASIAAUTO_VERSION` (zmiana stringa UI).

## Otwarte (NIE domknięte przez prawnika — pytanie o fakty, nie o redakcję)

Czy pośrednictwo jest **realne czy pozorne** — rozstrzygają fakty operacyjne, których nie widać z dokumentów: kto figuruje na **SAD jako importer**, na czyją firmę wystawiona **chińska faktura**, kto fizycznie **płaci chińskiemu sprzedawcy**, czyją **własnością** jest auto w transporcie. Jeśli klient → pośrednictwo realne, podpisywana umowa PDF poprawna. Jeśli Prima-Auto kupuje i odsprzedaje → to **kod umowy** (`class-asiaauto-contract.php`) wymaga rewizji.

Regulamin śude celowo nie dotyka komercji (stosunek handlowy reguluje indywidualnie podpisywana umowa PDF — czysty rozdział, nie hybryda). Prawnik tej umowy PDF nie widział — warto mu ją pokazać.
