# Dongchedi API — quirks i wskazówki operacyjne

> Aktualizacja: 2026-04-17 (sesja bulk-import 75 → zakończyła się zebraniem tych uwag)
> Kontekst: `source=dongchedi` w pośredniku `auto-api.com` (SDK `autoapi/client`). Pełny wrapper — zobacz `api-reference.md`.

---

## 1. Parametr marki: `mark` (nie `brand`)

SDK README daje przykład `brand=BMW` dla `mobile.de`. Dla `dongchedi` ten parametr jest cicho ignorowany — API zwraca miks wszystkich marek, a my dostajemy ~80% odrzutów po stronie PHP.

**Poprawnie:**
```php
$api->getOffers('dongchedi', ['mark' => 'BYD', 'page' => 1, 'year_from' => 2024]);
```

**Weryfikacja:** porównanie dwóch requestów — `brand=BYD` zwraca miks marek, `mark=BYD` zwraca czystą listę BYD.

## 2. Sortowanie `getOffers` — rosnąco po `inner_id` (najstarsze pierwsze)

Page 1 to **najstarsze** oferty w katalogu dongchedi — często 2008-2016, ceny 6-11k CNY. Dla filtrów `year_from=2024` pierwsze strony to 100% odrzutów po stronie API lub PHP.

**Konsekwencja dla bulk-import:**
- Iteruj **DESC** (od wysokich stron w dół) jeśli chcesz nowsze oferty najpierw.
- Nowsze = świeższe `x-expires` na obrazkach CDN (patrz §4).

## 3. Meta odpowiedzi — brak `total_pages`

API zwraca:
```json
{"result": [...], "meta": {"page": 1, "next_page": 2, "limit": 20}}
```

Nie wiesz ile stron ma marka przed iteracją. Dwa sprawdzone podejścia:

**Probe na skokach** (użyte w `diag/bulk-import-by-brand.php`):
```php
$PROBE_PAGES = [50, 40, 30, 20, 10, 5, 2, 1];
// szukamy najwyższej non-empty, potem iteracja od niej w dół
```

**Binary search** — log₂(MAX) queries, wydajniejszy dla bardzo dużych zestawów.

## 4. MAX_PAGES — 50+ dla topowych marek

Zaobserwowane limity strony (filtry: `year_from=2024, price_from=75000, mileage_from=1000, mileage_to=50000`):

| Mark | Page 30 | Page 40 | Page 50 | Page 100 |
|------|---------|---------|---------|----------|
| AITO | 20 wyn. | 20 wyn. | pusto | pusto |

AITO: minimum 40 stron × 20 ofert = 800 ofert. Bezpieczny `MAX_PAGES=50` dla skryptów bulk. Nie zakładaj 30 — traci się najświeższe strony.

## 5. Filtr miasta — tylko po stronie PHP

API **nie wspiera** parametru `city`. Dostępne filtry query (Client.php):
```
brand/mark, model, configuration, complectation, transmission,
color, body_type, engine_type, year_from/to, mileage_from/to, price_from/to
```

Miasto (`data['city']`, chińskie znaki) filtrujemy sami w `isAllowedByConfig()`.

## 6. Obrazki: `x-expires` ~5-6 dni, 403 Forbidden po wygaśnięciu

URL-e ze struktury `data['images']`:
```
https://p9-dcd-sign.byteimg.com/.../...?rk3s=...&x-expires=1776094772&x-signature=...
```

`x-expires` to unix timestamp ~5-6 dni w przyszłość od **momentu wygenerowania przez dongchedi**. CDN `byteimg.com` odrzuca (HTTP 403) po wygaśnięciu.

**Dlaczego `getOffers` zwraca przeterminowane URL-e?**
auto-api.com cacheuje dane ofert (w tym pole `images`). Starsze listings = starsze cache = wygasłe URL-e. Świeższe oferty (na wyższych stronach dongchedi) mają ważne URL-e.

**Workaround w bulk-import:** po znalezieniu nowej oferty w `getOffers()`, wywołać `getOffer(inner_id)` żeby zmusić auto-api do "odświeżenia" URL-i. Skuteczność nie 100% — auto-api cacheuje też getOffer dla starszych listings.

**Ghost-offer detection:** jeśli fresh `getOffer()` ma URL-e ale wszystkie 10-15 downloadów = 403, oferta została pewnie usunięta z dongchedi (auto-api ma metadata ale CDN już nie serwuje plików). Heurystyka: `wp_trash_post`. Implementacja w `diag/fix-missing-images.php`.

## 7. `getFilters()` — klucze po chińsku, parametry po angielsku

`getFilters('dongchedi')` zwraca strukturę:
```php
['大通' => ['model' => ['New V80' => ['complectation' => [...]]]]]
```

Klucze marki po chińsku (`大通` = Maxus/LDV). Ale `getOffers(['mark' => 'BYD'])` z angielską nazwą **działa** — API ma mapping dwukierunkowy.

## 8. Rate-limit — nieudokumentowany

Na stronie `auto-api.com` brak informacji o rate-limitach. Wiadomo tylko:
- `Response time<200ms`
- `Access provided within 2 minutes`

**Nasze ustawienia:**
- `AsiaAuto_Sync::run()` (produkcja): `usleep(150_000)` — 150 ms między requestami
- Bulk-import sesja 2026-04-17: 500 ms (zapas bezpieczeństwa, brak problemów)

Kontakt w razie potrzeby: `access@auto-api.com`, Telegram `@autodatabase`.

## 9. Delta (`getChanges`) vs paginacja (`getOffers`)

| Use case | Właściwe narzędzie |
|----------|---------------------|
| Cron sync co 15 min | `getChanges(change_id)` |
| Pierwszy catch-up po przerwie | `getChanges(change_id_from_yesterday)` |
| Bulk import po marce | `getOffers(mark=X, page=N)` DESC |
| Pojedyncza oferta, pełne dane | `getOffer(inner_id)` |
| Bulk `getChanges` z 7-30 dni wstecz | **nieefektywne** — dużo 'removed' + expired images |

## 10. Struktura `data` w `result[]`

Klucze obecne w `getOffers['result'][N]['data']`:
```
id, inner_id, url, mark, model, complectation, year, color, price, km_age,
body_type, engine_type, transmission_type, address, is_dealer, displacement,
city, title, owners_count, drive_type, equipment, horse_power, reg_date,
section, seller, seller_type, salon_id, region, description, created_at,
images, extra_prep
```

**Ważne pola do importu:**
- `inner_id` — klucz unikalny per listing
- `mark` (ang.) — do filtra config.marks
- `city` (zh) — do filtra config.city_filter_cities
- `km_age` (NIE `km`, NIE `mileage`) — przebieg
- `images` — JSON-stringified lub array URL-i, `x-expires` patrz §6
- `extra_prep` — JSON-stringified tablica 18 grup metadanych (wyposażenie)
- `region` — czasem zgodne z `city`, czasem inne, czasem puste; niepolegać
