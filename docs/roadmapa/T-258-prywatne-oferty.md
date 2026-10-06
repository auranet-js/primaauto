# T-258 „prywatne oferty na link” — spec

> Zgłoszenie: Ruslan przez Janka, 2026-10-06. Wycena: **4 h** (wpis w postępie prac).
> Status: spec do potwierdzenia, kod nieruszony.

## Problem

Ruslan czasem wystawia oferty, którymi nie chce się chwalić publicznie (pojedynczy klient, model spoza oferty, cena indywidualna). Potrzebuje strony auta ze zdjęciami, parametrami i ceną, którą wyśle klientowi bezpośrednim linkiem i z której klient może złożyć zamówienie — ale która nie pojawia się nigdzie na stronie ani w Google.

## Odrzucone warianty

- **Draft** (propozycja Ruslana) — widoczny tylko dla zalogowanych w adminie; dodatkowo rotacja traktuje draft jak ofertę zdjętą ze źródła (draft → kosz po 48 h → trwałe usunięcie po 7 dniach, `class-asiaauto-rotation.php`). Prywatna oferta przepadłaby po tygodniu.
- **Flaga w meta + status `publish`** — wymagałaby wykluczenia oferty w każdym miejscu, które czyta opublikowane oferty (listing, huby, wyszukiwarka, porównywarka, podobne oferty, sitemapa, feedy Meta/DSA/RMKT, licznik ofert, llms.txt). Każde przeoczone miejsce = wyciek. Za kruche.
- **Hasło WP (`post_password`)** — oferta dalej widoczna w listingu i hubach.

## Rozwiązanie: własny status `asiaauto_private` + link z kluczem

Cały kod czyta oferty warunkiem `post_status = 'publish'`, więc nowy status wypada ze wszystkiego bez łatania każdego zapytania. Zweryfikowane miejsca: `class-asiaauto-single.php:944–973` (podobne oferty), `scripts/build-meta-vehicle-feed.php:23`, `scripts/build-dsa-offer-feed.php:52`; listing/huby/wyszukiwarka idą przez `WP_Query` z domyślnym `publish`. Pełny grep po `'publish'` w kroku 1.

### Kroki

1. **Rejestracja statusu** — `register_post_status('asiaauto_private', ['public' => false, 'exclude_from_search' => true, 'show_in_admin_all_list' => true, 'show_in_admin_status_list' => true, 'label_count' => …])`. Nazwa w adminie: „Prywatna (na link)”. Grep `'publish'` po pluginie, motywie `primaauto2026` i `scripts/` — potwierdzić, że nic nie czyta listingów bez warunku statusu.
2. **Dostęp po kluczu** — meta `_asiaauto_private_key` (losowe 20 znaków, `wp_generate_password(20, false)`). URL: `/oferta/<slug>/?k=<klucz>`. Filtr na zapytaniu pojedynczej oferty (`posts_results` / `pre_get_posts`) wpuszcza post o statusie prywatnym tylko przy zgodnym kluczu (`hash_equals`). Brak lub zły klucz → 404. Zalogowany admin widzi bez klucza.
3. **Noindex na trzech warstwach** (wzorzec `class-asiaauto-compare.php`): nagłówek `X-Robots-Tag: noindex, nofollow`, `wp_robots`, meta RankMath. Do tego: bez schema `Offer`/`Vehicle`, bez canonicala, bez piksela `ViewContent` do katalogu (sprawdzić, czy feed katalogu Meta nie dostaje ID przez piksel — oferty i tak nie ma w feedzie, więc dopasowanie nie zajdzie).
4. **Zamówienie** — dopuścić status prywatny w trzech bramkach: `class-asiaauto-order-api.php:167`, `:250`, `class-asiaauto-order-wizard.php:605`. Klucz musi przejść przez kreator (parametr lub sesja), żeby zamówienie dało się złożyć tylko z linku. Rezerwacja i statusy zamówienia — bez zmian.
5. **Rotacja i sync** — `markRemoved()` i `restore()` w `class-asiaauto-rotation.php:25,54` zmieniają status bez patrzenia, jaki jest. Prywatna oferta z importu (ma `_asiaauto_inner_id`) zostałaby przestawiona na `draft`, gdy auto zniknie ze źródła, albo na `publish` przy przywróceniu — **czyli opublikowana publicznie**. Guard: oba wywołania pomijają `asiaauto_private` (tylko log).
6. **Indexing API** — `resolveNotificationType()` (`class-asiaauto-indexing.php:189`) zgłasza wyłącznie przejście do `publish`, więc prywatny status nie trafia do Google. Przejście prywatna → publish (Ruslan upublicznia ofertę) zostanie zgłoszone jak zwykła publikacja tylko po dopisaniu `asiaauto_private` do `$publishable_from` — dopisać.
7. **Panel** — w metaboxie oferty: przełącznik „Prywatna (na link)”, przy włączonym — pole z gotowym linkiem do skopiowania + „Wygeneruj nowy klucz” (unieważnia stary link). Na liście ofert w adminie kolumna/etykieta statusu.
8. **Smoke test na produkcji** — oferta testowa: brak w listingu, hubie, wyszukiwarce, sitemapie i świeżo zbudowanym feedzie Meta; link z kluczem → strona ze zdjęciami i ceną + nagłówek noindex; bez klucza → 404; zamówienie z linku przechodzi do kreatora; stary klucz po regeneracji → 404. Oferta testowa usunięta po teście.

## Skąd prywatna oferta się bierze

Zakres 4 h: **istniejąca oferta** przestawiona na prywatną albo **auto dodane importem ręcznym** (`class-asiaauto-admin-manual-import.php`) i od razu przestawione.

**Poza zakresem: przycisk „Duplikuj ofertę”.** Klon dziedziczy `_asiaauto_inner_id` i `_asiaauto_source`, a `findByInnerId()` (`class-asiaauto-importer.php:333`) zwraca pierwszy trafiony post — sync nadpisywałby klona albo oryginał. Duplikacja wymaga odpięcia klona od źródła (usunięcie `inner_id`, zamrożenie ceny) — osobna wycena, jeśli Ruslan będzie jej potrzebował.

## Decyzje do potwierdzenia (rekomendacje)

| Pytanie | Rekomendacja |
|---|---|
| Czy link wygasa? | Nie. Ruslan zdejmuje ofertę albo generuje nowy klucz. |
| Czy prywatna oferta podlega syncowi cen ze źródła? | Nie — cena indywidualna Ruslana. Przy przestawieniu na prywatną ustawiamy istniejącą flagę `skip_title_regen` (zamraża cenę, memory `reference_skip_title_regen_zamraza_ceny`), bez nowego mechanizmu. |
| Co po zamówieniu? | Obecna logika rezerwacji bez zmian. |
| Co, gdy auto znika ze źródła? | Oferta zostaje prywatna (guard z kroku 5), dostaje flagę `_asiaauto_api_removed` do wglądu Ruslana. |

## Strefy kruche dotknięte

Rotacja (krok 5), bramki zamówień (krok 4) — zmiany addytywne (dodatkowy warunek), bez refaktoru. Pipeline cenowy, importer, slugi, image pipeline — nietknięte.
