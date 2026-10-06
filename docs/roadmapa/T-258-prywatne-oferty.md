# T-258 „prywatne oferty na link” — spec

> Zgłoszenie: Ruslan przez Janka, 2026-10-06. Wycena: **4 h** (wpis w postępie prac; realnie ~3 h + bufor na testy).
> Status: spec do potwierdzenia, kod nieruszony.
> Rewizja 2026-10-06: zamiast własnego statusu — wbudowany status WordPressa „Prywatny”.

## Problem

Ruslan czasem wystawia oferty, którymi nie chce się chwalić publicznie (pojedynczy klient, model spoza oferty, cena indywidualna). Potrzebuje strony auta ze zdjęciami, parametrami i ceną, którą wyśle klientowi bezpośrednim linkiem i z której klient może złożyć zamówienie — ale która nie pojawia się nigdzie na stronie ani w Google.

Oferty powstają **ręcznie**: import ręczny z che168/dongchedi albo dodanie przez edytor. Nie mają `_asiaauto_inner_id` / `_asiaauto_source`, więc sync i rotacja ich nie dotykają.

## Stan dziś

Ruslan już próbował: **Xiaomi YU7 2025 4WD Max (#387505)** — dodane ręcznie 07.07, przestawione na „Prywatny” 10.07 (jedyna oferta w zakładce „Prywatne”). Bez logowania link zwraca **404** — WordPress pokazuje prywatne wpisy tylko zalogowanym administratorom i redaktorom. Funkcja jest więc w połowie: ukrywanie działa, brakuje wpuszczenia klienta.

## Odrzucone warianty

- **Draft** — widoczny tylko dla zalogowanych w adminie; rotacja traktuje draft jak ofertę zdjętą ze źródła.
- **Hasło wpisu** — status zostaje `publish`, więc oferta jest w listingu, hubach, wyszukiwarce, sitemapie, feedach Meta/DSA/RMKT i zgłaszana do Indexing API. Do tego `single-listings.php` buduje stronę z meta i nigdzie nie woła `post_password_required()` (grep po pluginie i motywie: 0 trafień) — hasło nie zablokowałoby nawet strony oferty.
- **Własny status `asiaauto_private`** (pierwsza wersja specu) — działa tak samo, ale wymaga rejestracji statusu i dopisania go w indexingu; wbudowany „Prywatny” daje to za darmo, a Ruslan już umie go ustawić.

## Rozwiązanie: wbudowany status „Prywatny” + link z kluczem

Status `private` wypada ze wszystkiego, co czyta oferty warunkiem `post_status = 'publish'`: listing, huby, wyszukiwarka, porównywarka, podobne oferty (`class-asiaauto-single.php:944–973`), feedy (`scripts/build-meta-vehicle-feed.php:23`, `scripts/build-dsa-offer-feed.php:52`), sitemapa. Indexing API (`class-asiaauto-indexing.php:189`) zgłasza wyłącznie przejście do `publish` — prywatnych nie zgłasza.

### Kroki

1. **Dostęp po kluczu** — meta `_asiaauto_private_key` (20 znaków, `wp_generate_password(20, false)`). URL: `/oferta/<slug>/?k=<klucz>`. Filtr na zapytaniu pojedynczej oferty (`posts_results`) wpuszcza post `private` typu `listings` tylko przy zgodnym kluczu (`hash_equals`). Brak lub zły klucz → 404 jak dziś.
2. **Ładny link** — dla prywatnej oferty `get_permalink()` zwraca `?post_type=listings&p=387505` zamiast `/oferta/<slug>/`. Link w panelu i w zamówieniu ma mieć postać ze slugiem — filtr `post_type_link` dla ofert prywatnych + sprawdzenie, że rewrite rozwiązuje slug prywatnego posta.
3. **Noindex na trzech warstwach** (wzorzec `class-asiaauto-compare.php`): nagłówek `X-Robots-Tag: noindex, nofollow`, `wp_robots`, meta RankMath. Do tego bez schema `Offer`/`Vehicle` i bez canonicala.
4. **Zamówienie** — dopuścić `private` z poprawnym kluczem w trzech bramkach: `class-asiaauto-order-api.php:167`, `:250`, `class-asiaauto-order-wizard.php:605`. Klucz przechodzi przez kreator (parametr lub sesja). Zmiana addytywna — logika rezerwacji i statusów w `class-asiaauto-order.php` nietknięta.
5. **Panel** — w karcie oferty o statusie „Prywatny”: pole z gotowym linkiem do skopiowania + „Wygeneruj nowy klucz” (stary link przestaje działać). Klucz zakładany automatycznie przy pierwszym przestawieniu na prywatną.
6. **Zabezpieczenie rotacji (2 linie)** — gdyby Ruslan przestawił na prywatną ofertę z feedu, `markRemoved()` / `restore()` w `class-asiaauto-rotation.php` mogłyby ją przestawić na `draft` albo `publish` (czyli upublicznić). Oba wywołania pomijają `private`, tylko log.
7. **Smoke test na produkcji** na Xiaomi YU7 #387505:
   - brak w listingu, hubie, wyszukiwarce, sitemapie i świeżo zbudowanym feedzie Meta;
   - link z kluczem → pełna strona (zdjęcia, cena, parametry) + nagłówek noindex;
   - bez klucza / zły klucz → 404;
   - zamówienie z linku przechodzi przez kreator;
   - po „Wygeneruj nowy klucz” stary link → 404;
   - **zalogowany klient (rola `asiaauto_customer`) nie widzi prywatnych ofert w listingu** — prywatne wpisy WordPress pokazuje użytkownikom z `read_private_posts`; konto testowe usunięte po teście.

## Pliki dotykane

| Plik | Zmiana | Strefa |
|---|---|---|
| nowa klasa `class-asiaauto-private-offer.php` | dostęp po kluczu, link, noindex, metabox | nowa |
| `class-asiaauto-order-api.php` (×2), `class-asiaauto-order-wizard.php` | dodatkowy warunek w bramce | przedsionek „statusów zamówień”, addytywnie |
| `class-asiaauto-rotation.php` | 2-liniowy guard | krucha, addytywnie |

Nietknięte: importer, sync, pipeline cenowy, image pipeline, indexing, `class-asiaauto-order.php`.

## Poza zakresem: przycisk „Duplikuj ofertę” (osobno ~2–3 h)

Duplikat musi stracić `_asiaauto_inner_id` i `_asiaauto_source` (inaczej `findByInnerId()` w `class-asiaauto-importer.php:333` pomyli go z oryginałem przy syncu) **i dostać własne kopie plików zdjęć**. Jeśli wskazywałby na załączniki oryginału, rotacja po sprzedaży oryginału (kosz → trwałe usunięcie po 7 dniach, sprzątanie `orphaned_images`) skasuje je i duplikat zostanie bez galerii.

## Decyzje do potwierdzenia (rekomendacje)

| Pytanie | Rekomendacja |
|---|---|
| Czy link wygasa? | Nie. Ruslan zdejmuje ofertę albo generuje nowy klucz. |
| Cena | Oferty ręczne nie są synchronizowane — cena zostaje taka, jaką ustawi Ruslan. |
| Co po zamówieniu? | Obecna logika rezerwacji bez zmian. |
