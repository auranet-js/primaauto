# T-218 — przebudowa panelu zamówień — plan wdrożeniowy

> **Dla wykonawcy:** plan realizowany zadanie po zadaniu. Kroki mają checkboxy (`- [ ]`) do odhaczania.
> Spec: `docs/superpowers/specs/2026-07-20-t218-panel-zamowien-design.md`
> Mockup: `https://auratest.pl/fe4f58fec53ctmp/primaauto-t218-panel-zamowien-mockup-2026-07-20.html`

**Cel:** lista zamówień w adminie ma trzy łączące się filtry (typ / status / ukryj anulowane),
domyślnie pokazuje zamówienia klientów bez anulat, a na telefonie renderuje karty zamiast rozjeżdżającej się tabeli.

**Architektura:** cała zmiana w warstwie prezentacji panelu admina. `renderOrderList()` zostaje rozbita na
trzy metody: `parseListFilters()` (czyta `$_GET`, zwraca stan), `getCountsByTypeAndStatus()` (jedno zapytanie
`$wpdb` na wszystkie liczniki) i sam render. Widok mobilny to **ten sam markup w tej samej pętli** —
dodatkowy `<div class="aa-order-cards">` obok `<table>`, przełączany CSS-em na breakpoincie 782 px.

**Stack:** PHP 8.x, WordPress admin (`WP_Query`, `$wpdb`, `add_query_arg`), czysty CSS. Bez JS, bez AJAX, bez bibliotek.

## Ograniczenia globalne

- **`includes/class-asiaauto-order.php` NIE MOŻE być modyfikowany.** Strefa krucha (statusy, rezerwacje,
  `TRANSITIONS`, `LISTING_RESERVATION_MAP`). Kontrola na końcu: `git diff --stat` nie zawiera tego pliku.
- Modyfikowane pliki (produkcja): `~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/`
  → `includes/class-asiaauto-order-admin.php` + `assets/css/asiaauto-order-admin.css`.
- **Przed pierwszą edycją każdego pliku: kopia `.bak` z datą.** Przed wgraniem: `php -l`.
- Nazwy klas / CPT / meta keys / prefiksów `asiaauto_*` **nie ruszamy** (globalna reguła projektu).
- Statusy chowane przez „Ukryj anulowane": **`anulowane` + `odrzucone`** (etykieta tego drugiego to
  „Oferta niedostępna", ale to nadal anulata z punktu widzenia Ruslana).
- `AsiaAuto_Order::STATUSES` ma **13 pozycji** — lista rozwijana pokazuje wszystkie, także te z licznikiem 0.
- Domyślny stan wejścia (brak parametrów w URL): `typ=customer`, `status=` (wszystkie), anulowane ukryte.
- Projekt **nie ma frameworka testowego** (brak PHPUnit). Weryfikacja = `php -l` + zapytania kontrolne
  `wp db query` + oględziny w Chrome (MCP). Każde zadanie kończy się porównaniem liczby wierszy w panelu
  z liczbą z SQL — to jest tutaj odpowiednik testu.

**Liczby odniesienia (stan 2026-07-20, do porównań w testach):**

| Filtr | Oczekiwana liczba |
|---|---|
| customer, wszystkie statusy, anulowane ukryte | **41** |
| customer, wszystkie statusy, anulowane widoczne | 76 |
| stock, wszystkie statusy, anulowane ukryte | **61** |
| stock, wszystkie statusy, anulowane widoczne | 64 |
| wszystkie typy, anulowane ukryte | **102** |
| wszystkie typy, anulowane widoczne | 140 |
| customer + weryfikacja | 17 |
| status = anulowane (checkbox ignorowany) | 36 |

Zapytanie kontrolne (uruchamiane z `~/domains/primaauto.com.pl/public_html`):

```bash
wp db query "SELECT COALESCE(t.meta_value,'brak') typ, s.meta_value status, COUNT(*) c
 FROM wp7j_posts p
 JOIN wp7j_postmeta s ON s.post_id=p.ID AND s.meta_key='_order_status'
 LEFT JOIN wp7j_postmeta t ON t.post_id=p.ID AND t.meta_key='_order_type'
 WHERE p.post_type='asiaauto_order' AND p.post_status='publish'
 GROUP BY 1,2 ORDER BY 1,3 DESC"
```

---

## Struktura plików

| Plik | Odpowiedzialność | Zadania |
|---|---|---|
| `includes/class-asiaauto-order-admin.php` | `parseListFilters()` — stan filtrów z `$_GET`; `getCountsByTypeAndStatus()` — liczniki; `renderListControls()` — rząd kontrolek; `renderOrderList()` — render listy (tabela + karty); `renderPagination()` — paginacja z pełnym kompletem filtrów | 1–5 |
| `assets/css/asiaauto-order-admin.css` | style rzędu kontrolek, kolumny „Wpłaty", kart mobilnych, breakpoint 782 px | 4, 5 |

Kolejność zadań jest istotna: 1 i 2 to czysta logika (dane), 3–5 to warstwa widoku. Po każdym zadaniu
panel musi działać — nie ma stanu pośredniego, w którym lista jest zepsuta.

---

## Zadanie 1: Odczyt i sanityzacja filtrów (`parseListFilters`)

**Pliki:**
- Modyfikuj: `includes/class-asiaauto-order-admin.php` (nowa metoda prywatna przed `renderOrderList()`, ~linia 780)

**Interfejsy:**
- Konsumuje: `AsiaAuto_Order::STATUSES`, `AsiaAuto_Order::TYPE_CUSTOMER`, `AsiaAuto_Order::TYPE_STOCK`
- Produkuje: `parseListFilters(): array` zwracające dokładnie te klucze — używane przez zadania 2, 3, 4:
  ```
  'typ'          => 'customer'|'stock'|'all'
  'status'       => ''|<klucz z STATUSES>
  'hide_cancel'  => bool
  'paged'        => int (>=1)
  'query_status' => ''|string|array   // gotowe do getOrders()
  'query_type'   => ''|'customer'|'stock'
  ```

- [ ] **Krok 1: Backup pliku**

```bash
cd ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes
cp class-asiaauto-order-admin.php class-asiaauto-order-admin.php.bak-2026-07-20
ls -la class-asiaauto-order-admin.php.bak-2026-07-20
```

Oczekiwane: plik `.bak` istnieje, rozmiar ~135 KB.

- [ ] **Krok 2: Dodaj stałą z listą statusów anulowanych**

W klasie `AsiaAuto_Order_Admin`, obok istniejących pól (`$page_slug`, ~linia 37), dodaj:

```php
    /** Statusy traktowane jako „anulowane" przez filtr listy (T-218). */
    private const CANCELLED_STATUSES = ['anulowane', 'odrzucone'];
```

- [ ] **Krok 3: Dodaj metodę `parseListFilters()`**

Wstaw bezpośrednio przed `private function renderOrderList(): void {`:

```php
    /**
     * Czyta stan filtrów listy zamówień z $_GET i przygotowuje argumenty do getOrders().
     * Jedno miejsce, w którym mieszka logika domyślnych wartości i wykluczeń (T-218).
     */
    private function parseListFilters(): array {
        $allowed_types = [AsiaAuto_Order::TYPE_CUSTOMER, AsiaAuto_Order::TYPE_STOCK, 'all'];
        $typ = sanitize_text_field($_GET['typ'] ?? AsiaAuto_Order::TYPE_CUSTOMER);
        if (!in_array($typ, $allowed_types, true)) {
            $typ = AsiaAuto_Order::TYPE_CUSTOMER;
        }

        $status = sanitize_text_field($_GET['status'] ?? '');
        if ($status !== '' && !isset(AsiaAuto_Order::STATUSES[$status])) {
            $status = '';
        }

        // Brak parametru = checkbox zaznaczony (stan domyślny bez śmieci w URL).
        $hide_cancel = ($_GET['anul'] ?? '1') !== '0';

        // Gdy user jawnie wybrał status anulowany, checkbox musi ustąpić — inaczej wynik zawsze pusty.
        if (in_array($status, self::CANCELLED_STATUSES, true)) {
            $hide_cancel = false;
        }

        if ($status !== '') {
            $query_status = $status;
        } elseif ($hide_cancel) {
            $query_status = array_values(array_diff(
                array_keys(AsiaAuto_Order::STATUSES),
                self::CANCELLED_STATUSES
            ));
        } else {
            $query_status = '';
        }

        return [
            'typ'          => $typ,
            'status'       => $status,
            'hide_cancel'  => $hide_cancel,
            'paged'        => max(1, (int) ($_GET['paged'] ?? 1)),
            'query_status' => $query_status,
            'query_type'   => $typ === 'all' ? '' : $typ,
        ];
    }
```

- [ ] **Krok 4: Sprawdź składnię**

```bash
cd ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes
php -l class-asiaauto-order-admin.php
```

Oczekiwane: `No syntax errors detected in class-asiaauto-order-admin.php`

- [ ] **Krok 5: Sprawdź zachowanie metody na żywych danych**

```bash
cd ~/domains/primaauto.com.pl/public_html
wp eval '
$r = new ReflectionMethod("AsiaAuto_Order_Admin", "parseListFilters");
$r->setAccessible(true);
$a = new AsiaAuto_Order_Admin();
$_GET = [];
$f = $r->invoke($a);
echo "domyslne: typ={$f["typ"]} hide=" . var_export($f["hide_cancel"], true) . " statusow=" . count((array) $f["query_status"]) . "\n";
$_GET = ["status" => "anulowane"];
$f = $r->invoke($a);
echo "status=anulowane: hide=" . var_export($f["hide_cancel"], true) . "\n";
$_GET = ["typ" => "nieistniejacy"];
$f = $r->invoke($a);
echo "smieciowy typ -> {$f["typ"]}\n";
'
```

Oczekiwane dokładnie:
```
domyslne: typ=customer hide=true statusow=11
status=anulowane: hide=false
smieciowy typ -> customer
```

(11 = 13 statusów minus `anulowane` i `odrzucone`.)

Jeśli konstruktor `AsiaAuto_Order_Admin` wymaga argumentów lub rejestruje hooki, użyj zamiast tego
`$a = (new ReflectionClass("AsiaAuto_Order_Admin"))->newInstanceWithoutConstructor();`.

- [ ] **Krok 6: Commit**

```bash
cd ~/projekty/primaauto
git add -A && git commit -m "[order:] T-218 krok 1 — parseListFilters, stan filtrow listy zamowien"
```

---

## Zadanie 2: Liczniki per typ i status (`getCountsByTypeAndStatus`)

**Pliki:**
- Modyfikuj: `includes/class-asiaauto-order-admin.php` (metoda za `parseListFilters()`)

**Interfejsy:**
- Konsumuje: `$wpdb`, `AsiaAuto_Order::POST_TYPE`, `AsiaAuto_Order::META_STATUS`, `AsiaAuto_Order::META_ORDER_TYPE`
- Produkuje: `getCountsByTypeAndStatus(): array` — macierz `[typ][status] => int`, gdzie typ ∈ `customer|stock`,
  plus klucze zbiorcze. Zadanie 3 używa jej do wszystkich liczników kontrolek.

- [ ] **Krok 1: Dodaj metodę**

Wstaw za `parseListFilters()`:

```php
    /**
     * Liczniki zamówień w dwóch wymiarach (typ × status) — jedno zapytanie na render (T-218).
     * Zwraca ['customer' => [status => n], 'stock' => [...], '_all' => [status => n]].
     * Zamówienia bez ustawionego _order_type liczone są jako 'customer' (tak działa domyślnie getOrders).
     */
    private function getCountsByTypeAndStatus(): array {
        global $wpdb;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT COALESCE(t.meta_value, %s) AS typ, s.meta_value AS status, COUNT(*) AS cnt
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} s ON s.post_id = p.ID AND s.meta_key = %s
             LEFT JOIN {$wpdb->postmeta} t ON t.post_id = p.ID AND t.meta_key = %s
             WHERE p.post_type = %s AND p.post_status = 'publish'
             GROUP BY typ, status",
            AsiaAuto_Order::TYPE_CUSTOMER,
            AsiaAuto_Order::META_STATUS,
            AsiaAuto_Order::META_ORDER_TYPE,
            AsiaAuto_Order::POST_TYPE
        ));

        $out = [
            AsiaAuto_Order::TYPE_CUSTOMER => [],
            AsiaAuto_Order::TYPE_STOCK    => [],
            '_all'                        => [],
        ];

        foreach ($rows as $row) {
            $typ = isset($out[$row->typ]) ? $row->typ : AsiaAuto_Order::TYPE_CUSTOMER;
            $out[$typ][$row->status]  = ($out[$typ][$row->status] ?? 0) + (int) $row->cnt;
            $out['_all'][$row->status] = ($out['_all'][$row->status] ?? 0) + (int) $row->cnt;
        }

        return $out;
    }

    /**
     * Suma zamówień danego typu, opcjonalnie z pominięciem anulowanych (T-218).
     * $typ: 'customer' | 'stock' | 'all'
     */
    private function sumCounts(array $counts, string $typ, bool $hide_cancel): int {
        $bucket = $typ === 'all' ? ($counts['_all'] ?? []) : ($counts[$typ] ?? []);
        $sum = 0;
        foreach ($bucket as $status => $n) {
            if ($hide_cancel && in_array($status, self::CANCELLED_STATUSES, true)) {
                continue;
            }
            $sum += (int) $n;
        }
        return $sum;
    }
```

- [ ] **Krok 2: Sprawdź składnię**

```bash
cd ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes
php -l class-asiaauto-order-admin.php
```

Oczekiwane: `No syntax errors detected`

- [ ] **Krok 3: Porównaj liczniki z SQL-em (to jest test tego zadania)**

```bash
cd ~/domains/primaauto.com.pl/public_html
wp eval '
$c = new ReflectionMethod("AsiaAuto_Order_Admin", "getCountsByTypeAndStatus");
$s = new ReflectionMethod("AsiaAuto_Order_Admin", "sumCounts");
$c->setAccessible(true); $s->setAccessible(true);
$a = (new ReflectionClass("AsiaAuto_Order_Admin"))->newInstanceWithoutConstructor();
$counts = $c->invoke($a);
foreach (["customer","stock","all"] as $t) {
  printf("%-9s ukryte=%3d  wszystkie=%3d\n", $t, $s->invoke($a,$counts,$t,true), $s->invoke($a,$counts,$t,false));
}
echo "customer/weryfikacja = " . ($counts["customer"]["weryfikacja"] ?? 0) . "\n";
'
```

Oczekiwane dokładnie (zgodne z tabelą „Liczby odniesienia"):
```
customer  ukryte= 41  wszystkie= 76
stock     ukryte= 61  wszystkie= 64
all       ukryte=102  wszystkie=140
customer/weryfikacja = 17
```

Jeśli liczby się nie zgadzają — **zatrzymaj się i porównaj z zapytaniem kontrolnym z sekcji
„Ograniczenia globalne"**. Rozbieżność oznacza błąd w `COALESCE`/`GROUP BY`, nie w danych.

- [ ] **Krok 4: Commit**

```bash
cd ~/projekty/primaauto
git add -A && git commit -m "[order:] T-218 krok 2 — liczniki typ x status jednym zapytaniem"
```

---

## Zadanie 3: Rząd kontrolek zamiast kafli

**Pliki:**
- Modyfikuj: `includes/class-asiaauto-order-admin.php` — `renderOrderList()`, blok `<div class="aa-stats-row">`
  (obecnie linie ~813–841) zastąpiony wywołaniem nowej metody `renderListControls()`

**Interfejsy:**
- Konsumuje: `parseListFilters()` (zadanie 1), `getCountsByTypeAndStatus()` + `sumCounts()` (zadanie 2)
- Produkuje: `renderListControls(array $filters, array $counts, string $base_url, int $total): void`

- [ ] **Krok 1: Dodaj metodę renderującą kontrolki**

Wstaw za `sumCounts()`:

```php
    /** Rząd filtrów nad listą zamówień: typ / status / ukryj anulowane (T-218). */
    private function renderListControls(array $filters, array $counts, string $base_url, int $total): void {
        $types = [
            AsiaAuto_Order::TYPE_CUSTOMER => 'Klientów',
            AsiaAuto_Order::TYPE_STOCK    => 'Wewnętrzne',
            'all'                         => 'Wszystkie',
        ];

        // Zachowaj pozostałe filtry przy przełączaniu jednego z nich; zawsze wracaj na stronę 1.
        $keep = function (array $override) use ($filters, $base_url): string {
            $args = [
                'typ'    => $filters['typ'],
                'status' => $filters['status'],
                'anul'   => $filters['hide_cancel'] ? '1' : '0',
            ];
            $args = array_merge($args, $override);
            if ($args['status'] === '') unset($args['status']);
            if ($args['anul'] === '1')  unset($args['anul']);   // domyślne — nie zaśmiecaj URL
            return add_query_arg($args, $base_url);
        };

        $cancel_forced = in_array($filters['status'], self::CANCELLED_STATUSES, true);
        ?>
        <div class="aa-list-controls">
            <div class="aa-seg">
                <?php foreach ($types as $key => $label):
                    $n = $this->sumCounts($counts, $key, $filters['hide_cancel']);
                    $cls = ($filters['typ'] === $key) ? 'aa-seg__btn is-active' : 'aa-seg__btn';
                ?>
                    <a class="<?php echo $cls; ?>" href="<?php echo esc_url($keep(['typ' => $key])); ?>">
                        <?php echo esc_html($label); ?><span class="aa-seg__cnt"><?php echo (int) $n; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="get" class="aa-list-controls__form">
                <input type="hidden" name="post_type" value="listings">
                <input type="hidden" name="page" value="<?php echo esc_attr($this->page_slug); ?>">
                <input type="hidden" name="typ" value="<?php echo esc_attr($filters['typ']); ?>">
                <?php if (!$filters['hide_cancel']): ?>
                    <input type="hidden" name="anul" value="0">
                <?php endif; ?>

                <select name="status" onchange="this.form.submit()">
                    <option value="">Wszystkie statusy</option>
                    <?php
                    $bucket = $filters['typ'] === 'all'
                        ? ($counts['_all'] ?? [])
                        : ($counts[$filters['typ']] ?? []);
                    foreach (AsiaAuto_Order::STATUSES as $skey => $slabel):
                        $n = (int) ($bucket[$skey] ?? 0);
                    ?>
                        <option value="<?php echo esc_attr($skey); ?>" <?php selected($filters['status'], $skey); ?>>
                            <?php echo esc_html($slabel) . ' (' . $n . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <a class="aa-chk <?php echo $cancel_forced ? 'is-disabled' : ''; ?>"
               href="<?php echo esc_url($keep(['anul' => $filters['hide_cancel'] ? '0' : '1'])); ?>"
               <?php if ($cancel_forced): ?>title="Wybrany status jest anulowany — filtr nie ma zastosowania"<?php endif; ?>>
                <input type="checkbox" <?php checked($filters['hide_cancel']); ?> <?php disabled($cancel_forced); ?> tabindex="-1">
                Ukryj anulowane
            </a>

            <span class="aa-list-result"><?php
                // Polska liczba mnoga ma 3 formy — _n() ich nie obsłuży bez pliku .mo, a plugin tłumaczeń nie ma.
                $n10 = $total % 10; $n100 = $total % 100;
                if ($total === 1) {
                    $form = 'zamówienie';
                } elseif ($n10 >= 2 && $n10 <= 4 && ($n100 < 12 || $n100 > 14)) {
                    $form = 'zamówienia';
                } else {
                    $form = 'zamówień';
                }
                echo (int) $total . ' ' . $form;
            ?></span>
        </div>
        <?php
    }
```

Uwaga do implementacji: checkbox jest celowo `<a>` z atrapą `<input>` w środku — nie potrzebujemy JS,
a klik w cały element przełącza filtr. `disabled` na inpucie sygnalizuje stan „filtr nie ma zastosowania".

- [ ] **Krok 2: Podłącz kontrolki w `renderOrderList()`**

W `renderOrderList()` **usuń w całości** blok od `<div class="aa-stats-row">` do zamykającego `</div>`
(obecnie ~linie 813–841, razem z pętlą `foreach ($stat_items ...)` i zmienną `$shipping_count`),
a początek metody zastąp tak:

```php
    private function renderOrderList(): void {
        $filters = $this->parseListFilters();
        $counts  = $this->getCountsByTypeAndStatus();

        $result = AsiaAuto_Order::getOrders([
            'status'     => $filters['query_status'],
            'order_type' => $filters['query_type'],
            'per_page'   => 20,
            'page'       => $filters['paged'],
        ]);

        $base_url = add_query_arg([
            'post_type' => 'listings',
            'page'      => $this->page_slug,
        ], admin_url('edit.php'));

        $new_internal_url = add_query_arg('view', 'new-internal', $base_url);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Zamówienia Prima-Auto</h1>
            <a href="<?php echo esc_url($new_internal_url); ?>" class="page-title-action">Zamówienie wewnętrzne</a>
            <hr class="wp-header-end">

            <?php settings_errors('aa_order'); ?>

            <?php $this->renderListControls($filters, $counts, $base_url, (int) $result['total']); ?>
```

Reszta metody (tabela) na razie bez zmian — kolumny bierzemy w zadaniu 4.

- [ ] **Krok 3: Napraw paginację, żeby nie gubiła filtrów**

Zmień sygnaturę i ciało `renderPagination()` (obecnie linia ~907):

```php
    private function renderPagination(int $total_pages, int $current, string $base_url, array $filters): void {
        if ($total_pages <= 1) return;

        echo '<div class="tablenav bottom"><div class="tablenav-pages">';
        for ($i = 1; $i <= $total_pages; $i++) {
            $args = ['paged' => $i, 'typ' => $filters['typ']];
            if ($filters['status'] !== '')  $args['status'] = $filters['status'];
            if (!$filters['hide_cancel'])   $args['anul']   = '0';
            $url   = add_query_arg($args, $base_url);
            $class = ($i === $current) ? 'button button-primary' : 'button';
            echo '<a href="' . esc_url($url) . '" class="' . $class . '">' . $i . '</a> ';
        }
        echo '</div></div>';
    }
```

Oraz jej wywołanie na końcu `renderOrderList()`:

```php
            <?php $this->renderPagination($result['pages'], $filters['paged'], $base_url, $filters); ?>
```

- [ ] **Krok 4: Dodaj style kontrolek**

Do `assets/css/asiaauto-order-admin.css`, **za** blokiem `.aa-stat-box` (linia ~29), dopisz:

```css
/* === T-218: rząd filtrów listy zamówień === */
.aa-list-controls { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin:0 0 16px; }
.aa-list-controls__form { margin:0; }
.aa-seg { display:inline-flex; border:1px solid #c3c4c7; border-radius:4px; overflow:hidden; background:#fff; }
.aa-seg__btn { padding:6px 13px; font-size:13px; color:#2c3338; text-decoration:none;
    border-right:1px solid #e0e0e0; background:#fff; }
.aa-seg__btn:last-child { border-right:0; }
.aa-seg__btn:hover { background:#f6f7f7; color:#2c3338; }
.aa-seg__btn.is-active { background:#1B2A4A; color:#fff; font-weight:600; }
.aa-seg__cnt { opacity:.7; font-size:12px; margin-left:5px; }
.aa-list-controls select { min-width:200px; }
.aa-chk { display:inline-flex; align-items:center; gap:7px; font-size:13px; text-decoration:none;
    color:#2c3338; background:#FFF8E1; border:1px solid #E6C65C; border-radius:4px; padding:5px 11px; }
.aa-chk:hover { background:#FFF3CD; color:#2c3338; }
.aa-chk input { margin:0; pointer-events:none; }
.aa-chk.is-disabled { opacity:.5; }
.aa-list-result { margin-left:auto; font-size:12px; color:#718096; }
```

- [ ] **Krok 5: Sprawdź składnię i wyczyść cache**

```bash
cd ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes
php -l class-asiaauto-order-admin.php
cd ~/domains/primaauto.com.pl/public_html && wp cache flush
```

Oczekiwane: `No syntax errors detected` + `Success: The cache was flushed.`

- [ ] **Krok 6: Weryfikacja w przeglądarce (MCP Chrome, zalogowana sesja)**

Wejdź na `https://primaauto.com.pl/wp-admin/edit.php?post_type=listings&page=asiaauto-orders` i sprawdź:

| Co | Oczekiwane |
|---|---|
| kafle statystyk | **nie ma ich** |
| aktywny przycisk typu | „Klientów", licznik **41** |
| pozostałe liczniki | Wewnętrzne 61, Wszystkie 102 |
| lista rozwijana | „Wszystkie statusy" + 13 pozycji z licznikami |
| checkbox | „Ukryj anulowane", zaznaczony |
| licznik wyniku | „41 zamówień" |
| liczba wierszy | 20 (pierwsza z 3 stron) |

Następnie po kolei:
- Klik „Wewnętrzne" → 61, w kolumnie Typ same „Stock".
- Klik „Wszystkie" → 102.
- Odznacz „Ukryj anulowane" → liczniki 76 / 64 / 140, w liście pojawiają się „Anulowane".
- Wybierz status „Anulowane" → 36 pozycji, checkbox wyszarzony.
- Wybierz „Weryfikacja" przy typie „Klientów" → 17 pozycji.
- Wejdź na stronę 2 → **filtry zachowane w URL i w kontrolkach** (to jest pułapka starej paginacji).

- [ ] **Krok 7: Commit**

```bash
cd ~/projekty/primaauto
git add -A && git commit -m "[order:] T-218 krok 3 — rzad filtrow (typ/status/anulowane) zamiast kafli"
```

---

## Zadanie 4: Kolumny tabeli — „Wpłaty", szerokość ID, wyszarzenie anulat

**Pliki:**
- Modyfikuj: `includes/class-asiaauto-order-admin.php` — `<thead>` i `<tbody>` w `renderOrderList()`
- Modyfikuj: `assets/css/asiaauto-order-admin.css`

**Interfejsy:**
- Konsumuje: `AsiaAuto_Order::getOrderData()` — klucze `deposit_paid`, `client_cif_usd`, `cif_paid`, `status`
- Produkuje: markup wiersza używany w zadaniu 5 (karty korzystają z tych samych znaczników `.aa-pin`)

- [ ] **Krok 1: Zamień nagłówek tabeli**

Zastąp obecny `<thead>` (10 kolumn) tym (9 kolumn):

```php
                <thead>
                    <tr>
                        <th style="width:74px;">ID</th>
                        <th style="width:66px;"></th>
                        <th>Auto</th>
                        <th style="width:82px;">Typ</th>
                        <th style="width:190px;">Klient</th>
                        <th style="width:130px;">Status</th>
                        <th style="width:110px;">Cena PLN</th>
                        <th style="width:76px;">Wpłaty</th>
                        <th style="width:130px;">Data</th>
                    </tr>
                </thead>
```

- [ ] **Krok 2: Zamień komórki Depozyt + CIF na jedną „Wpłaty"**

W pętli `foreach ($result['orders'] ...)` **usuń** dwie komórki (`Depozyt` i `CIF`) i wstaw w ich miejsce:

```php
                        <td>
                            <span class="aa-pin <?php echo $data['deposit_paid'] ? 'is-ok' : 'is-no'; ?>"
                                  title="Depozyt: <?php echo $data['deposit_paid'] ? 'zapłacony' : 'niezapłacony'; ?> (<?php
                                  echo esc_attr(number_format($data['deposit_amount'], 0, ',', ' ')); ?> zł)">D</span>
                            <?php if ($data['client_cif_usd'] > 0): ?>
                                <span class="aa-pin <?php echo $data['cif_paid'] ? 'is-ok' : 'is-no'; ?>"
                                      title="CIF: <?php echo $data['cif_paid'] ? 'zapłacony' : 'niezapłacony'; ?>">C</span>
                            <?php else: ?>
                                <span class="aa-pin" title="CIF nie dotyczy tego zamówienia">C</span>
                            <?php endif; ?>
                        </td>
```

Kwota depozytu nie znika — jest w `title` (tooltip po najechaniu) i w karcie zamówienia.

- [ ] **Krok 3: Oznacz wiersze anulowane**

Zmień otwarcie wiersza z `<tr>` na:

```php
                    <tr class="<?php echo in_array($data['status'], self::CANCELLED_STATUSES, true) ? 'aa-row-cancelled' : ''; ?>">
```

- [ ] **Krok 4: Zaktualizuj `colspan` w komunikacie o pustej liście**

Było `colspan="10"`, ma być `colspan="9"`. Przy okazji popraw treść komunikatu, bo dziś mówi tylko o statusie:

```php
                        <tr><td colspan="9">Brak zamówień spełniających wybrane filtry.</td></tr>
```

- [ ] **Krok 5: Style kolumny i wierszy anulowanych**

Dopisz do `assets/css/asiaauto-order-admin.css`:

```css
/* === T-218: znaczniki wpłat + wiersze anulowane === */
.aa-pin { display:inline-flex; align-items:center; justify-content:center; width:22px; height:19px;
    font-size:10px; font-weight:700; border-radius:3px; border:1px solid #dcdcde;
    background:#fafafa; color:#a7aaad; margin-right:3px; cursor:default; }
.aa-pin.is-ok { background:#E6F7EE; border-color:#9CCFB0; color:#276749; }
.aa-pin.is-no { background:#FDECEC; border-color:#F0B4B4; color:#B32D2E; }
.aa-row-cancelled td { background:#fafafa; opacity:.72; }
.wp-list-table td:first-child { white-space:nowrap; }
```

- [ ] **Krok 6: Sprawdź składnię**

```bash
cd ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes
php -l class-asiaauto-order-admin.php
cd ~/domains/primaauto.com.pl/public_html && wp cache flush
```

- [ ] **Krok 7: Weryfikacja w przeglądarce**

| Co | Oczekiwane |
|---|---|
| liczba kolumn | 9 (nie ma osobnych „Depozyt" i „CIF") |
| kolumna ID | numer w **jednej linii**, np. `#387939` — nie `#38793 / 9` |
| kolumna Wpłaty | dwa znaczniki `D` `C`; przy zapłaconym depozycie `D` zielone |
| tooltip na `D` | pokazuje kwotę, np. „Depozyt: niezapłacony (6 150 zł)" |
| zamówienie #387823 (stock, w drodze) | `D` i `C` zielone |
| po odznaczeniu „Ukryj anulowane" | wiersze anulowane wyraźnie wyszarzone |
| pusty wynik (np. status „W dostawie") | komunikat „Brak zamówień spełniających wybrane filtry." bez rozjechanej tabeli |

- [ ] **Krok 8: Commit**

```bash
cd ~/projekty/primaauto
git add -A && git commit -m "[order:] T-218 krok 4 — kolumna Wplaty (D/C), fix szerokosci ID, wyszarzone anulaty"
```

---

## Zadanie 5: Widok mobilny — karty zamiast tabeli

**Pliki:**
- Modyfikuj: `includes/class-asiaauto-order-admin.php` — dodatkowy kontener kart w `renderOrderList()`
- Modyfikuj: `assets/css/asiaauto-order-admin.css` — sekcja `@media (max-width:782px)`

**Interfejsy:**
- Konsumuje: te same `$data` z pętli co tabela (bez drugiego zapytania)
- Produkuje: `<div class="aa-order-cards">` renderowany zawsze, widoczny tylko poniżej 782 px

- [ ] **Krok 1: Wyodrębnij pętlę do zmiennej, żeby nie odpytywać dwa razy**

Bezpośrednio przed `<table class="wp-list-table ...">` w `renderOrderList()` wstaw przygotowanie danych:

```php
            <?php
            $rows = [];
            foreach ($result['orders'] as $order) {
                $d = AsiaAuto_Order::getOrderData($order->ID);
                if ($d) $rows[] = $d;
            }
            ?>
```

Następnie w pętli tabeli zamień `foreach ($result['orders'] as $order): $data = AsiaAuto_Order::getOrderData($order->ID); if (!$data) continue;`
na `foreach ($rows as $data):` — reszta ciała wiersza bez zmian.

- [ ] **Krok 2: Dodaj kontener kart za tabelą**

Bezpośrednio po zamykającym `</table>`, przed wywołaniem `renderPagination()`:

```php
            <div class="aa-order-cards">
                <?php if (empty($rows)): ?>
                    <p class="aa-order-cards__empty">Brak zamówień spełniających wybrane filtry.</p>
                <?php else: foreach ($rows as $data):
                    $detail_url = add_query_arg(['view' => 'detail', 'order_id' => $data['id']], $base_url);
                    $is_cancelled = in_array($data['status'], self::CANCELLED_STATUSES, true);
                ?>
                    <a class="aa-ocard <?php echo $is_cancelled ? 'is-cancelled' : ''; ?>"
                       href="<?php echo esc_url($detail_url); ?>">
                        <?php if ($data['listing_thumbnail']): ?>
                            <img src="<?php echo esc_url($data['listing_thumbnail']); ?>" alt="" loading="lazy">
                        <?php else: ?>
                            <span class="aa-ocard__noimg dashicons dashicons-car"></span>
                        <?php endif; ?>
                        <span class="aa-ocard__main">
                            <span class="aa-ocard__top">
                                <span class="aa-ocard__id">#<?php echo (int) $data['id']; ?></span>
                                <span class="aa-ocard__date"><?php
                                    echo esc_html(wp_date('d.m.Y H:i', strtotime($data['created_at']))); ?></span>
                            </span>
                            <span class="aa-ocard__car"><?php echo esc_html($data['listing_title'] ?: '—'); ?></span>
                            <span class="aa-ocard__row">
                                <?php echo AsiaAuto_Order::statusBadgeHtml($data['status']); ?>
                                <span class="aa-ocard__price"><?php
                                    echo number_format($data['price_final'], 0, ',', ' '); ?> zł</span>
                            </span>
                            <span class="aa-ocard__meta">
                                <?php if (($data['order_type'] ?? '') === AsiaAuto_Order::TYPE_STOCK): ?>
                                    <span class="aa-type-badge aa-type-badge--stock">Wewnętrzne</span>
                                <?php else: ?>
                                    <?php echo esc_html($data['customer_name'] ?: '—'); ?>
                                    <span class="aa-ocard__sep">·</span>
                                    <span class="aa-type-badge aa-type-badge--customer">Klient</span>
                                <?php endif; ?>
                                <span class="aa-pin <?php echo $data['deposit_paid'] ? 'is-ok' : 'is-no'; ?>">D</span>
                                <?php if ($data['client_cif_usd'] > 0): ?>
                                    <span class="aa-pin <?php echo $data['cif_paid'] ? 'is-ok' : 'is-no'; ?>">C</span>
                                <?php endif; ?>
                            </span>
                        </span>
                    </a>
                <?php endforeach; endif; ?>
            </div>
```

- [ ] **Krok 3: Style kart + przełączenie widoków**

Dopisz do `assets/css/asiaauto-order-admin.css` (poza jakimkolwiek `@media`):

```css
/* === T-218: karty zamówień (widok mobilny) === */
.aa-order-cards { display:none; }
.aa-ocard { display:flex; gap:10px; padding:10px; background:#fff; border:1px solid #dcdcde;
    border-radius:6px; text-decoration:none; color:#1d2327; }
.aa-ocard:hover { background:#f6f7f7; color:#1d2327; }
.aa-ocard.is-cancelled { opacity:.62; background:#fafafa; }
.aa-ocard img { width:74px; height:56px; flex:0 0 auto; border-radius:4px; object-fit:cover; background:#dfe3e8; }
.aa-ocard__noimg { width:74px; height:56px; flex:0 0 auto; display:flex; align-items:center;
    justify-content:center; font-size:30px; color:#ccc; background:#f0f0f1; border-radius:4px; }
.aa-ocard__main { display:block; min-width:0; flex:1; }
.aa-ocard__top { display:flex; justify-content:space-between; align-items:baseline; gap:8px; margin-bottom:3px; }
.aa-ocard__id { font-size:11px; font-weight:600; color:#787c82; }
.aa-ocard__date { font-size:11px; color:#787c82; }
.aa-ocard__car { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
    font-size:13px; font-weight:600; line-height:1.3; margin-bottom:6px; }
.aa-ocard__row { display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:5px; }
.aa-ocard__price { font-weight:600; white-space:nowrap; }
.aa-ocard__meta { display:flex; flex-wrap:wrap; align-items:center; gap:6px; font-size:11px; color:#646970; }
.aa-ocard__sep { color:#c3c4c7; }
.aa-order-cards__empty { padding:16px; background:#fff; border:1px solid #dcdcde; border-radius:6px; }
```

Następnie w istniejącym bloku `@media screen and (max-width:782px)` **usuń wszystkie reguły dotyczące
`.wp-list-table`** (ukrywanie kolumn `nth-child`, `table-layout`, szerokości — to jest ~50 linii,
w tym reguła z błędnym komentarzem o dacie) i wstaw w ich miejsce:

```css
    /* T-218: poniżej 782 px tabela ustępuje kartom */
    .wp-list-table { display:none; }
    .aa-order-cards { display:flex; flex-direction:column; gap:9px; }

    /* Kontrolki: jedna pod drugą, segment na pełną szerokość */
    .aa-list-controls { flex-direction:column; align-items:stretch; gap:7px; }
    .aa-seg { width:100%; }
    .aa-seg__btn { flex:1; text-align:center; padding:9px 2px; font-size:12px; }
    .aa-list-controls select { width:100%; min-width:0; }
    .aa-list-controls__form { width:100%; }
    .aa-chk { justify-content:flex-start; padding:9px 11px; }
    .aa-list-result { margin-left:0; }
```

Reguły dotyczące karty zamówienia, konfiguracji i logu statusów **zostają bez zmian** — dotyczą innych widoków.

- [ ] **Krok 4: Sprawdź składnię**

```bash
cd ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/includes
php -l class-asiaauto-order-admin.php
cd ~/domains/primaauto.com.pl/public_html && wp cache flush
```

- [ ] **Krok 5: Weryfikacja na desktopie (regresja)**

Panel na szerokim ekranie ma wyglądać **dokładnie jak po zadaniu 4** — karty są niewidoczne.
Sprawdź, że liczba wierszy tabeli to nadal 20 i że nie pojawił się pusty odstęp pod tabelą.

- [ ] **Krok 6: Weryfikacja na telefonie**

W Chrome DevTools (lub MCP) ustaw szerokość **390 px** i sprawdź:

| Co | Oczekiwane |
|---|---|
| tabela | niewidoczna |
| karty | widoczne, jedna pod drugą |
| tytuł auta | maksymalnie **2 linie**, dłuższe ucięte wielokropkiem |
| „BYD Leopard 7 (Tai 7) FCB, PHEV 2025 190KM 4WD Ultra" | mieści się w karcie, nie rozpycha ekranu |
| przewijanie w bok | **żadne** — `document.documentElement.scrollWidth === clientWidth` |
| klik w kartę | otwiera kartę zamówienia (`&view=detail&order_id=…`) |
| kontrolki | pełna szerokość, wysokość ≥ 40 px (klikalne palcem) |
| liczba kart na ekran | ~6 |

Sprawdź też po zmianie filtra na „Wewnętrzne": karty pokazują plakietkę „Wewnętrzne" zamiast nazwiska klienta.

- [ ] **Krok 7: Commit**

```bash
cd ~/projekty/primaauto
git add -A && git commit -m "[order:] T-218 krok 5 — karty zamowien na telefonie zamiast tabeli"
```

---

## Zadanie 6: Domknięcie — wersja, dokumentacja, kontrola strefy kruchej

**Pliki:**
- Modyfikuj: `asiaauto-sync.php` (header wersji + `ASIAAUTO_VERSION`)
- Modyfikuj: `docs/VERSIONS.md`, `docs/kosztorys/dane/postep.json`

- [ ] **Krok 1: Potwierdź, że strefa krucha jest nietknięta**

```bash
cd ~/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync
md5sum includes/class-asiaauto-order.php
git -C ~/projekty/primaauto diff --stat
```

Oczekiwane: `class-asiaauto-order.php` **nie występuje** na liście zmienionych plików.
Jeśli występuje — zatrzymaj się i zgłoś Jankowi przed dalszymi krokami.

- [ ] **Krok 2: Podbij wersję pluginu**

W `asiaauto-sync.php` podnieś numer w nagłówku `Version:` oraz w stałej `ASIAAUTO_VERSION`
o jeden poziom patch względem stanu produkcyjnego (sprawdź `grep ASIAAUTO_VERSION asiaauto-sync.php`).

- [ ] **Krok 3: Wpis w `docs/VERSIONS.md`**

Dodaj sekcję wg istniejącego wzorca:

```markdown
## <nowa wersja> — 2026-07-20 (T-218: przebudowa listy zamówień)

- Filtry listy: typ (Klientów / Wewnętrzne / Wszystkie), status (lista 13 pozycji z licznikami),
  checkbox „Ukryj anulowane" (domyślnie ON). Domyślne wejście: zamówienia klientów bez anulat (41 z 140).
- Usunięte kafle statystyk (2 z 6 pokazywały stale 0, „Na placu" nie dotyczy zamówień).
- Kolumny „Depozyt" + „CIF" scalone w „Wpłaty" (znaczniki D/C, kwota w tooltipie).
- Fix: kolumna ID łamała numer zamówienia na dwie linie.
- Telefon (<782 px): karty zamiast tabeli — koniec rozjeżdżania się przy długich nazwach aut.
- `class-asiaauto-order.php` nietknięty — filtry oparte na istniejących parametrach `getOrders()`.
```

- [ ] **Krok 4: Zaktualizuj status T-218 na stronie postępu dla Ruslana**

W `docs/kosztorys/dane/postep.json` zmień `status` wpisu `T-218` z „do zaplanowania" na
„wdrożone 20.07.2026" i przebuduj stronę:

```bash
cd ~/projekty/primaauto/docs/kosztorys && python3 build_postep.py --deploy
```

- [ ] **Krok 5: Commit**

```bash
cd ~/projekty/primaauto
git add -A && git commit -m "[docs:] T-218 wdrozone — VERSIONS + strona postepu"
```

---

## Weryfikacja końcowa (przed zgłoszeniem „gotowe")

- [ ] `git diff --stat` nie zawiera `class-asiaauto-order.php`
- [ ] `php -l` czysty na obu zmienionych plikach PHP
- [ ] Panel na desktopie: 6 kombinacji filtrów z tabeli „Liczby odniesienia" daje zgodne liczby
- [ ] Paginacja zachowuje filtry na stronach 2 i 3
- [ ] Panel na 390 px: brak przewijania w bok, karty klikalne, tytuł maks. 2 linie
- [ ] Karta pojedynczego zamówienia i „Zamówienie wewnętrzne" działają jak przed zmianą (regresja)
- [ ] Kopie `.bak` obu plików istnieją na serwerze
