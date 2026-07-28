# che168 sync — prompt kontynuacyjny (stan na 2026-07-28, plugin v0.34.10)

Plik startowy dla **nowego wątku o synchronizacji che168**. Zawiera stan faktyczny zmierzony
28.07, nie plan. Wszystkie liczby pochodzą z produkcji (`host476470_wp521`, prefix `wp7j_`).

---

## 1. Sytuacja: che168 przejął podaż, bo dongchedi stoi

Nowe oferty `publish` wg dnia i źródła:

| dzień | dongchedi | che168 |
|---|---|---|
| 21.07 | 15 | 5 |
| 22.07 | 42 | 1 |
| 23–26.07 | **0** | 8 |
| 27.07 | 0 | 14 |
| **28.07** | **0** | **97** (92 backfill skryptem, 4 ręczne z panelu, **1 z synca**) |

> **Sprostowanie (28.07 wieczór, pomiar w `asiaauto_sync_history`):** te 92 oferty **NIE przyszły
> syncem** — wszystkie biegi doby 02:04–14:18 UTC mają `added=0`. To ręczny bieg
> `scripts/che168-domknij-luke.php` (81 postów w godzinie 11:00 UTC, `post_author=0`).
> Sync w tym samym oknie wpuścił **1 ofertę** (BYD Han L EV, post 392255, bieg 14:48 UTC).
> Wniosek metodyczny: skok w statystyce dobowej sprawdzaj w historii biegów, nie po `post_date`.

**Dongchedi nie dostarcza nic od 23.07.** Nawiązuje to do wcześniejszego zamrożenia feedu
(`project_dongchedi_feed_frozen_2026_07_07`) i serii awarii auto-api z czerwca.

**Bilans magazynu:** dongchedi **2915** publish (+7 draft), che168 **153** publish (+68 draft),
bez źródła 99 publish. Czyli **95% oferty to zapas dongchedi, który się starzeje** —
rotacja i wygasanie URL-i zdjęć (`x-expires`, 6 dni) będą go zjadać, a nic go nie uzupełnia.

**Pytanie operacyjne wątku:** czy che168 przy ~90 ofertach/dzień nadrobi ubytek zapasu
dongchedi, i czy w ogóle chcemy go nadrabiać (che168 to inny segment — patrz
`project_che168_recon_and_spec_2026_06_01`).

## 2. Co Janek zmienił 28.07 — odsłonięcie marek i filtrów

Rozszerzenie marek odblokowało 92 oferty, ale **wciągnął je ręczny backfill, nie sync**
(patrz sprostowanie wyżej). Rozkład marek z tego dnia: Denza 45, Lynk & Co 45, Volvo 3,
Mercedes-Benz 1, Zeekr 1, Deepal 1.

**Wąskie filtry są CELOWE** (decyzja Janka 28.07): to sonda jakości — mały strumień pozwala
sprawdzić, czy oferty poprawnie wpadają do hubów i czy generują się opisy/wyposażenie. Filtry
będą rozszerzane codziennie, w miarę eliminowania wyłapanych błędów. **Tempo 1–3 oferty/dobę
to stan pożądany, nie awaria** — nie diagnozować tego jako usterki.

**Stan konfiguracji `asiaauto_import_config['che168']` (odczyt 28.07):**

- `enabled` = true
- `marks` [11]: BAW, BYD, Deepal, Denza, Fangchengbao, Lynk & Co, Volvo, Voyah, WEY, Zeekr, iCAR
- `model_blacklist`: głównie Volvo (modele europejskie/importowane)
- `year_from` 2024 · `km_to` 40000 · `price_from` 85000 · `year_to`/`km_from`/`price_to` puste
- `city_filter_enabled` = true, `city_filter_cities` [31] — Guangdong (Shenzhen, Guangzhou, Dongguan…)
- kursor kanału: `asiaauto_last_change_id_che168` = **10069461**
- cron `asiaauto_sync_changes` — co 15 min, działa (ostatni sync 28.07 13:32)

**GAC nie ma na liście marek** — dlatego rodzina Hyptec nie mogła przyjść syncem.

## 3. Rzecz, która wyszła 28.07 i dotyczy synca

**Ręczny import che168 z panelu NIE MA guarda mapowania.**
`AsiaAuto_Admin_Che168_Import::ajaxImport()` liczy flagę `mapped` i wyświetla „❌ sierota",
ale **nie blokuje** — bramkami są tylko `importEnabled` (stała `ASIAAUTO_CHE168_IMPORT_ENABLED`),
duplikat i `isEmptyShell`. Sync ma guard (`AsiaAuto_Sync::isMappedForImport`).

Skutkiem tego oferta `hyper-ht-2025-390660` utworzyła osobną markę „Hyper" z surowych mark/model.
Naprawione w v0.34.9/0.34.10 (patrz `docs/VERSIONS.md`), ale **sama luka w panelu została**.
Do decyzji w nowym wątku: czy ręczny import ma ostrzegać/blokować przy sierocie.

**Uwaga o danych che168:** podaje marki zlatynizowane, czasem STARĄ nazwą
(`mark='Hyper'` dla 昊铂/Hyptec). Klucze w `che168-model-map.php` muszą zostać w formie z API.

## 3b. Pomiary z wieczora 28.07 (weryfikacja stanu faktycznego)

**Sync żyje, ale wpuszcza rzadko.** 51 biegów w historii (02:04–14:48 UTC, ~2000 zdarzeń),
suma `added` = 1. Odsiew leci na `isAllowedByConfig()` PRZED `getOffer()` (T-186), czyli filtry
działają: z 1098 ofert w magazynie 962 odsiane jako poza segmentem, 136 przechodzi,
135 zmapowanych, **0 rozjazdów hubów** (40 serii kanonicznych, każda w jeden hub).

**Luka = zapas, nie problem.** 100 ofert zmapowanych i przechodzących filtry leży poza bazą
(Zeekr 27, Voyah 23, iCAR 18, WEY 14, Deepal 11, BYD 7). Nie generują `added`, więc sync ich
nie weźmie — czekają na rozszerzenie filtrów. Nie domykać z własnej inicjatywy. Monitor
ostrzega o wyczerpanym limicie stron dla 9 z 11 marek, więc to dolna granica.

**Zamknięte 28.07 (nie wpisywać ponownie jako zadania):** rozwalony JSON `extra_prep` — 0 błędnych
z 3199; `tr_val` — fix wgrany (częściowe dopasowanie tylko dla kluczy z CJK); mapowania
Lynk & Co — 8 kluczy w `che168-model-map.php`.

**Nadal otwarte:** `Lynk & Co|09` i `BAW|Ruisheng Ace M7` bez wpisu w mapie (przy 09 najpierw
sprawdzić w API, czy to realny model); `ajaxImport()` w panelu ręcznego importu **bez guarda
mapowania** (liczy flagę `mapped`, pokazuje „❌ sierota", ale przepuszcza — tą drogą powstała
marka „Hyper").

**Dongchedi — zapas się starzeje, ale API żyje.** `getOffer()`/`getChanges()`/`getOffers()`
odpowiadają; martwy jest tylko strumień zmian. Z 2915 ofert `publish` **2376 (81%) nietkniętych
>14 dni**, najświeższy `last_sync` = 22.07, zero biegów dongchedi w historii mimo `enabled=true`.
Narzędzie do odświeżania po `inner_id` przeanalizowane i **świadomie odłożone** (decyzja Janka
28.07 — che168 kompletuje ofertę przez miesiąc-dwa): memory
`project_dongchedi_refresh_magazynu_narzedzie`.

**Dedup cross-source nie istnieje** — `findByInnerId()` sprawdza wyłącznie parę `(inner_id, source)`,
więc ta sama sztuka z dongchedi i che168 wejdzie dwa razy (~25 par o zbieżnym przebiegu i cenie CNY).
VIN się do tego nie nada: w dongchedi jest w 12 z 2915 ofert i pochodzi z ręcznego wpisu Ruslana
przy zamówieniu, nie z API.

## 3c. Zmiana dostępu — Ruslan widzi che168 (28.07)

`ASIAAUTO_CHE168_PREVIEW` w `wp-config.php`: `'js'` → `'js,primaauto'` (backup:
`~/backups/primaauto/2026-07-28/wp-config.php.bak-che168-preview`). Ta jedna stała bramkuje
**dwie** rzeczy: przeglądarkę „Przeglądaj Che168" **oraz zakładkę źródła che168 w konfiguratorze
importu**. Ruslan (login `primaauto`, ID 7) może więc od 28.07 także **zmieniać filtry sondy**.

Świadome ryzyka, zgłoszone i przyjęte: (1) zmiany filtrów **nigdzie nie są logowane** —
`update_option('asiaauto_import_config', …)` w `class-asiaauto-admin.php:261` bez autora,
czasu i diffu, więc po fakcie nie odróżnisz zmiany Janka od zmiany Ruslana; (2) z przeglądarki
prowadzi deep-link do importu, a ten nie ma guarda mapowania.

## 4. Punkty startowe (memory)

- `project_che168_kanaly_wejscia_i_klucz_serii` — **START dla synca**: kanały wejścia, klucz serii 车型名称
- `project_che168_do_zrobienia_2026_07_28` — zaległości: 14 ofert z rozwalonym JSON, tr_val, Lynk & Co
- `reference_che168_alias_wymaga_sygnatury_brandmapping` — alias bez sygnatury w brand-mappingu = martwy
- `reference_che168_api_obcina_wyposazenie` + `project_che168_pierwsze_oferty_live_voyah_passion_2026_07_27`
- `project_hyptec_rename_i_otwarte_2026_07_28` — co zrobiono 28.07

## 5. Parking — NIE zadania

Obserwacje uboczne z 28.07. Nie zamawiane, bez decyzji, **nie wyciągać z własnej inicjatywy**:

- `_asiaauto_spec_snapshot` — JSON nie parsuje się w żadnym termie (`wp_unslash` zjada backslashe
  z `wp_json_encode`; ten sam mechanizm co przy escape'ach unicode). Stan zastany, gate
  indeksacji nietknięty (mierzy `strlen`).
- `_asiaauto_pl_availability=import_only` na Hyptec HT/HL — nieprawda, auta są w dystrybucji PL.
- Puste huby AION (S/Y/V/UT/LX/RT/i60, count=0); „aion v" ma wolumen w PL.
- Nazwa marki „Lynk &amp; Co" trzymana z encją HTML w termie.

---

## Prompt do wklejenia w nowym wątku

> Kontynuujemy prace nad synchronizacją che168 w projekcie primaauto.
> Przeczytaj `docs/sesje/2026-07-28-che168-sync-prompt-kontynuacyjny.md` — jest tam zmierzony
> stan z 28.07: dongchedi nie dostarcza nic od 23.07, magazyn to w 95% jego starzejący się zapas,
> a che168 dokłada 1–3 oferty na dobę, bo filtry są **celowo** wąskie (sonda jakości, rozszerzana
> codziennie w miarę eliminowania błędów) — to NIE jest awaria i nie diagnozuj tego jako usterki.
> Zacznij od weryfikacji, czy oferty poprawnie wpadają do hubów i czy nie zostawiają sierot
> w mapowaniach (bieg `scripts/che168-monitor.php`, sekcje [3] i [3b]). Luki 100 ofert nie domykaj
> bez polecenia — to zapas czekający na szersze filtry. Sekcji „Parking" nie ruszaj.
