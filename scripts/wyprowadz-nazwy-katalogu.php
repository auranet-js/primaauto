<?php
/**
 * Wyprowadza mapowania „nazwa CN → nasz klucz" dla katalogu Autohome, po ID auto-api.
 *
 * Po co: `autohome-catalog-map.php` powstał 27.07 pod grupy WYPOSAŻENIA, bo parametry
 * techniczne miały przychodzić z auto-api. Dla pojazdów użytkowych (7-cyfrowy specid)
 * auto-api oddaje 1–12 pól, więc technika (wymiary, moc, rozstaw osi) też musi iść
 * z katalogu — a tych nazw w mapie nie ma.
 *
 * Metoda: odpowiedź auto-api niesie dla każdego parametru ZARÓWNO `id`, JAK I nazwę CN.
 * `che168-param-map.php` mapuje `id → nasz_klucz` (zweryfikowane ręcznie). Złączenie po `id`
 * daje `nazwa_CN → nasz_klucz` bez zgadywania po nazwie — mapowanie „na oko" dało 22.07
 * dwa błędy semantyczne (id 88 = moc układu ≠ silnika, id 84/105 = ładowanie szybkie ≠ wolne).
 *
 * Użycie: wp eval-file scripts/wyprowadz-nazwy-katalogu.php <inner_id,inner_id,...>
 * Wypisuje gotowy blok PHP do wklejenia do data/autohome-catalog-map.php.
 */

$inner_ids = array_filter(array_map('trim', explode(',', (string) ($args[0] ?? ''))));
if (!$inner_ids) { echo "Podaj inner_id oddzielone przecinkiem.\n"; return; }

$param_map   = require ASIAAUTO_PLUGIN_DIR . 'data/che168-param-map.php';
$catalog_map = require ASIAAUTO_PLUGIN_DIR . 'data/autohome-catalog-map.php';
$api = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);

$derived = [];   // nazwa_CN => [klucz => liczba potwierdzeń]
$seen_ids = [];

foreach ($inner_ids as $inner) {
    $r = $api->getOffer('che168', $inner);
    $d = $r['data'] ?? $r;
    $groups = $d['extra']['configuration']['paramtypeitems'] ?? [];
    foreach ($groups as $g) {
        foreach ($g['paramitems'] ?? [] as $item) {
            $id   = $item['id']   ?? null;
            $name = trim((string) ($item['name'] ?? ''));
            if ($id === null || $name === '') { continue; }
            $seen_ids[$id] = $name;
            $key = $param_map[$id] ?? null;
            if ($key === null) { continue; }          // parametr niezmapowany po stronie API
            if (isset($catalog_map[$name])) { continue; } // już mamy w mapie katalogu
            $derived[$name][$key] = ($derived[$name][$key] ?? 0) + 1;
        }
    }
    usleep(400000);
}

echo "widzianych parametrów API: " . count($seen_ids) . "\n";
echo "wyprowadzonych nowych nazw: " . count($derived) . "\n\n";

/* Konflikt WPRZÓD: jedna nazwa CN → kilka kluczy (np. 最大功率(kW) w grupie silnika i układu). */
$konflikty = [];
$czyste = [];
foreach ($derived as $name => $keys) {
    if (count($keys) > 1) { $konflikty[$name] = array_keys($keys); continue; }
    $czyste[$name] = array_key_first($keys);
}

/* Konflikt WSTECZ: kilka nazw CN → ten sam klucz. Merge dolewa tylko brakujące, więc druga
   nazwa przepadnie po cichu i mogłaby podstawić złą wartość pod dobrą etykietę. */
$po_kluczu = [];
foreach ($czyste as $name => $key) { $po_kluczu[$key][] = $name; }
$kolizje = array_filter($po_kluczu, fn($n) => count($n) > 1);
foreach ($kolizje as $key => $names) { foreach ($names as $n) { unset($czyste[$n]); } }

/* Klucz już obecny w mapie katalogu pod inną nazwą — też pomijamy. */
$zajete = [];
foreach ($catalog_map as $v) {
    foreach ((array) $v as $one) { if (is_string($one)) { $zajete[$one] = true; } }
}
foreach ($czyste as $name => $key) { if (isset($zajete[$key])) { unset($czyste[$name]); } }

$out = "\n  // === wyprowadzone 2026-07-29 ze złączenia po id auto-api ===\n"
     . "  // Metoda i powód: scripts/wyprowadz-nazwy-katalogu.php. Nazwy techniczne były poza mapą,\n"
     . "  // bo mapa z 27.07 pokrywała tylko wyposażenie — technika miała iść z auto-api, które dla\n"
     . "  // pojazdów użytkowych (7-cyfrowy specid) oddaje 1-12 pól.\n";
foreach ($czyste as $name => $key) {
    $out .= "  '" . str_replace("'", "\\'", $name) . "' => '$key',\n";
}
echo "do dopisania (czyste): " . count($czyste) . "\n";
echo $out;

if ($konflikty) {
    echo "\n!! KONFLIKT WPRZÓD (nazwa → kilka kluczy; pomijam, mapa jest bez grupy):\n";
    foreach ($konflikty as $n => $k) { echo "   $n → " . implode(', ', $k) . "\n"; }
}
if ($kolizje) {
    echo "\n!! KONFLIKT WSTECZ (kilka nazw → jeden klucz; pomijam obie):\n";
    foreach ($kolizje as $k => $n) { echo "   $k ← " . implode(', ', $n) . "\n"; }
}
