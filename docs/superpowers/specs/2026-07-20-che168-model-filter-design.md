# Spec: filtr modeli Che168 w konfiguratorze importu

> Data: 2026-07-20 · Status: zaakceptowany ustnie (quiz), implementacja przygotowana BEZ deployu
> Kontekst: element dopięcia Che168 = **T-186** (`docs/roadmapa/T-186-che168-automat.md`, pkt „Brak konfiguracji che168 w asiaauto_import_config")
> Analiza danych: `https://auratest.pl/fe4f58fec53ctmp/primaauto-che168-modele-2024-2026-raport-2026-07-20.md`

## 1. Cel

Ruslan chce odcinać **modele niesprzedajne** przy imporcie z Che168. Dziś `asiaauto_import_config` zna tylko poziom marek (dongchedi). Robimy osobne ustawienia `che168` w istniejącym konfiguratorze (`admin.php?...page=asiaauto-import-config`, zakładka „Filtry importu") — na tej samej zasadzie co dongchedi, plus nowy poziom: **blacklista modeli per marka**.

**Etap 2 (zaakceptowany 2026-07-20 po mockupie): „Przeglądaj Che168"** — przeglądarka ofert giełdy (sekcja 4.7). Mockup: `https://auratest.pl/fe4f58fec53ctmp/primaauto-che168-przegladarka-mockup-2026-07-20.html`.

**Poza zakresem:** automatyczny sync che168 — osobne fazy T-186 (cron nadal ma zaszyte dongchedi; ten spec przygotowuje konfigurację, którą automat kiedyś skonsumuje).

## 2. Decyzje (quiz 2026-07-20)

| Pytanie | Decyzja |
|---|---|
| Gdzie żyje filtr modeli | Konfigurator (`asiaauto_import_config['che168']`) — fundament pod automat T-186 |
| Semantyka | **Blacklist**: wszystkie modele marki wchodzą, odhaczamy niesprzedajne; nowe modele giełdy przechodzą same. Ręczny import per numer (`force=true`) **omija** blacklistę |
| Miasta | Ta sama lista 31 południowych miast co dongchedi (świadomie: ~18% podaży che168) |
| Start config | Kopia z dongchedi: year_from 2024, km_to 40 000, price_from 85 000 ¥, 31 miast, marki-odpowiedniki; blacklista pusta |
| Enabled | `enabled=true` od razu (automat i tak nie chodzi — zero zmiany behawioralnej dziś) |
| Deploy | **STOP przed deployem** — kod + diffy do review Janka |
| Domapowania orphanów | Tak, w tej samej sesji (~40 top wpisów, osobne pliki data/, do review w diffie) |

## 3. Fakty o API (zweryfikowane 2026-07-20)

- Dostęp che168 żyje (HTTP 200; zapis w T-186 o wygaśnięciu 15.07 nieaktualny).
- `getFilters('che168')` → pełny słownik `mark → {model: [...]}` jednym requestem (282 marki, 2524 pary, 35 kB). Obejmuje całą historię giełdy (też stare spalinowe).
- `getOffers` filtruje server-side po `mark` (NIE `brand`), `model`, `year_from/to`. Format: event-feed `result[].data` + `meta.next_page`, bez total. Miasto: brak parametru — filtr client-side po `address` (jak dziś w `isAllowedByConfig()`).
- Przekrój 2024-2026 (próbka 3550 ofert / 46 marek): 61% trafia w huby, 39% orphan; 18% w naszych 31 miastach.

## 4. Komponenty

### 4.1 Słownik modeli — `AsiaAuto_Che168_Dictionary` (NOWY plik `includes/class-asiaauto-che168-dictionary.php`)

Mała klasa-helper (konsument istniejących klas, zero dotykania strefy kruchej):

- `get(): array` — słownik z transientu `asiaauto_che168_filters_dict` (TTL 7 dni); miss → `AsiaAuto_API::getFilters('che168')` → zapis.
- `refresh(): ?array` — wymuszone odświeżenie (przycisk w UI).
- `canonicalized(): array` — pary raw przepuszczone przez `AsiaAuto_Mapping::canonicalKeyForSource(raw_mark, raw_model, '', 'che168')` + status `getEuForCn()`. Wynik: `[canonical_mark => [ ['key'=>'Mark|Model','raw'=>'raw|raw','mapped'=>bool], ... ]]`, deduplikacja po `key` (kilka raw wariantów → jeden klucz kanoniczny). Cache w transient (`asiaauto_che168_dict_canon`, TTL jak wyżej, invalidowany przy `refresh()`).

Klucz kanoniczny (kształt Dongchedi/brand-mappingu) jest **jedynym formatem** w storage i w egzekwowaniu — bo dane oferty che168 po adapterze też są kanoniczne (ADR normalize-at-entry 2026-06-17).

### 4.2 Storage — `asiaauto_import_config['che168']`

Pola jak dongchedi (`enabled`, `marks`, `year_from/to`, `km_from/to`, `price_from/to`, `city_filter_enabled`, `city_filter_cities`) **plus**:

```php
'model_blacklist' => [
    'BYD'   => ['BYD|Seagull', 'BYD|e2'],   // klucze kanoniczne "Mark|Model"
    'Chery' => ['Chery|Arrizo 8'],
],
```

`marks` dla che168 = nazwy **kanoniczne** (te same co dongchedi — „Xiaomi", nie „小米"). Seed startowy: przecięcie marek dongchedi z markami obecnymi w słowniku kanonicznym che168 (46 marek wg analizy).

### 4.3 UI — rozszerzenie `renderFiltersTab()` w `class-asiaauto-admin.php`

- Zakładka „Filtry importu" dostaje **przełącznik źródła** (subtabs `?source=dongchedi` / `?source=che168`); strona i save są już sparametryzowane per source — dochodzi tylko nawigacja.
- Dla `source=che168`: marki ze **słownika kanonicznego** (nie z taksonomii WP — obejmują marki spoza bazy), checkboxy jak dziś.
- Pod każdą **zaznaczoną** marką rozwijany (`<details>`) blok modeli: checkbox „wyklucz" per model (checked = na blacklistcie), badge 🆕 przy orphanach (brak huba). Zapis: hidden input JSON `model_blacklist` (wzorzec jak `city_filter_cities`).
- Przycisk „Odśwież słownik modeli z API" (admin-post lub AJAX, nonce, cap `IMPORT_CAP`) + data ostatniego pobrania.
- Dla `source=dongchedi`: **zero zmian** poza subtabs (dongchedi nie dostaje poziomu modeli — automat dongchedi filtruje tylko po markach jak dziś).

### 4.4 Egzekwowanie — `isAllowedByConfig()` w `class-asiaauto-importer.php` (edycja ADDYTYWNA)

Po istniejących checkach (marks/year/km/price/city), nowy blok:

```php
$mark = $data['mark'] ?? '';
if (!empty($source_config['model_blacklist'][$mark])) {
    $key = $mark . '|' . ($data['model'] ?? '');
    if (in_array($key, (array) $source_config['model_blacklist'][$mark], true)) {
        return false;
    }
}
```

Dane che168 wchodzą po adapterze (kanoniczne) → klucz spójny ze storage. Dongchedi: `model_blacklist` nie istnieje w configu → no-op. `importListing`, `buildPlan`, adapter — **nietknięte**. Ręczny import (`force=true`) nie woła `isAllowedByConfig()` → blacklista go nie dotyczy (zgodnie z decyzją).

### 4.5 Seed konfiguracji (skrypt jednorazowy przy deployu)

`wp eval-file` (w repo `scripts/seed-che168-config.php`): jeśli `asiaauto_import_config['che168']` nie istnieje → zapis kopii z dongchedi (limity + 31 miast + marki-przecięcie) z `enabled=true`, `model_blacklist=[]`. Idempotentny (istniejący config nie jest nadpisywany).

### 4.6 Domapowania orphanów (osobne pliki, ta sama sesja)

~40 wpisów wg zasad kalibracji czerwcowej („utwórz vs przemapuj", addytywnie):
- `data/che168-model-map.php` — aliasy nazw che168 → istniejące klucze (np. `Jetour|Traveller`, `Li Auto|Li L7/L8`, `smart|Smart #1/#3`, `iCAR|iCAR 超级V23`, `Shanhai|捷途旅行者C-DM`).
- `data/brand-mapping-v6.1.php` — realnie nowe modele (np. `Xiaomi|YU7`) jako czyste nowe wpisy.
- Modele spoza segmentu (Mazda 3 Axela, Nissan Qashqai — ICE) — **celowo bez mapowania** (kandydaci na blacklistę, nie na huby).

### 4.7 Przeglądarka „Przeglądaj Che168" (etap 2) — NOWY plik `includes/class-asiaauto-admin-che168-browse.php`

Osobna podstrona pod CPT Ogłoszenia (`edit.php?post_type=listings`), obok „Dodaj z Che168", cap `IMPORT_CAP`. Kurator (Ruslan) przegląda oferty giełdy bez znania numeru z che168.com. Mockup zaakceptowany 2026-07-20.

- **Filtry:** marka (select z **pełnego** słownika `AsiaAuto_Che168_Dictionary` — wszystkie ~282 marki giełdy, nie tylko nasze; z liczbą modeli) → po wyborze marki chipy modeli ze słownika (**również niezmapowane, oznaczone 🆕**; status mapped z `canonicalized()`); rocznik od/do (default `year_from` z configu che168); miasto (całe Chiny / tylko lista z configu); cena od (default z configu).
- **Zapytanie:** nowy AJAX `asiaauto_che168_browse` → `getOffers('che168', ['mark','model','year_from','year_to','page'])` (server-side), następnie po stronie serwera: rozpakowanie event-feedu (`result[].data`), filtr miast (pole `address` → miasto, tłumaczenie z `translations-cities.php`), filtr ceny, status mapowania per oferta (`canonicalKeyForSource`+`getEuForCn`). Paginacja „załaduj więcej" po `meta.next_page`. Model-filtr przy wielu chipach: jedno zapytanie per wybrany model (API przyjmuje jeden `model` na zapytanie), scalanie wyników po stronie serwera.
- **Karty wyników:** zdjęcie (permanentne URL-e che168), tytuł mark+model+rok, badge ✅ hub / 🆕 bez huba, miasto PL (⚠ gdy spoza listy), cena ¥, przebieg, 1. rejestracja, inner_id; akcje: **Podgląd (dry-run)** i **Importuj** — wywołują **istniejące** akcje `asiaauto_che168_preview` / `asiaauto_che168_import` (te same endpointy co „Dodaj z Che168", nonce `asiaauto_che168`) + link do oferty na che168.com.
- **Relacja do blacklisty:** przeglądarka to ścieżka ręczna — **nie blokuje** modeli z blacklisty (jak import per numer); pokazuje badge „⛔ na blackliście" informacyjnie.
- **Modele niezmapowane (🆕):** podgląd/import techniczne możliwe, ale dry-run pokaże `🆕 nowy` na termach — procedura jak w fazie kalibracji: najpierw wpis w `che168-model-map`/`brand-mapping` (wzorzec 尚界: romanizacja z VIN/WMI + web), potem import. Przeglądarka nie blokuje, sygnalizuje.
- **Strefa krucha:** zero zmian — czysty konsument istniejących klas (API, Dictionary, Adapter przez ajaxPreview, Importer przez ajaxImport).

## 5. Wersjonowanie i deploy (PO akceptacie Janka)

1. `.bak` z datą dla `class-asiaauto-admin.php`, `class-asiaauto-importer.php`, plików `data/`.
2. `php -l` wszystkie zmieniane pliki.
3. Wgranie + seed config + bump `ASIAAUTO_VERSION` → **0.33.37**.
4. Smoke: zakładka Filtry (oba źródła), zapis blacklisty, dry-run che168 per numer (blacklista NIE blokuje), `docs/VERSIONS.md`.

## 6. Ryzyka

- `isAllowedByConfig()` w pliku strefy kruchej — zmiana addytywna na końcu metody, zero wpływu na dongchedi (klucz nie istnieje w configu). Test: import dongchedi przed/po = identyczny.
- Słownik 2524 par w UI — renderujemy modele tylko dla zaznaczonych marek, w `<details>` (lazy w DOM, bez AJAX — 35 kB spokojnie wchodzi w stronę admina).
- Transient miss + API down → UI pokazuje ostatni cache lub komunikat, konfigurator dalej działa (blacklista edytowalna nawet bez słownika — pokazujemy zapisane klucze).

## 7. Kryteria akceptacji

- Zakładka Filtry importu ma subtabs dongchedi/che168; dongchedi wygląda i zapisuje się jak dotychczas.
- Che168: marki kanoniczne ze słownika, rozwijane listy modeli, zapis `model_blacklist` przeżywa round-trip.
- `isAllowedByConfig()` odrzuca ofertę che168 z modelem na blacklistcie; przepuszcza spoza; dongchedi bez zmian.
- Ręczny import per numer działa mimo blacklisty.
- Domapowania: top orphany z próbki trafiają w huby w dry-run (weryfikacja na numerach smoke).
