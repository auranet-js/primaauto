<?php
/**
 * Audyt parametrów che168 na próbce z sondy (T-186) — READ-ONLY, ZERO zapisów.
 *
 * Wejście: JSON z `che168-sonda-kanalu.php` (lista ofert przechodzących filtr).
 * Dla każdego UNIKALNEGO modelu bierze jedną ofertę → getOffer() → adapter → zlicza:
 *   - nieznane klucze `param_*` z częstością i przykładową wartością (kandydaci do
 *     che168-param-map.php),
 *   - pokrycie: ile wierszy specyfikacji wychodzi na ofertę,
 *   - grupy `paramtypeitems`, które API zwraca (kontrola blokady wyposażenia).
 *
 * NIE pobiera zdjęć, NIE tłumaczy opisów, NIE zapisuje postów ani meta.
 *
 * Użycie: wp eval-file scripts/che168-audyt-parametrow.php <plik.json> [max_ofert]
 */

$file = (string) ($args[0] ?? '');
$max  = (int) ($args[1] ?? 100);

if ($file === '' || !is_readable($file)) {
    echo "Podaj plik JSON z sondy: wp eval-file scripts/che168-audyt-parametrow.php /tmp/sonda.json 100\n";
    return;
}

$data = json_decode((string) file_get_contents($file), true);
$offers = $data['offers'] ?? [];
if (!$offers) {
    echo "Brak ofert w pliku.\n";
    return;
}

// Jedna oferta na unikalny model — maksymalna różnorodność przy minimum wywołań API.
$sample = [];
foreach ($offers as $o) {
    $k = (string) ($o['key'] ?? '?');
    if (!isset($sample[$k])) {
        $sample[$k] = $o;
    }
}
$sample = array_slice($sample, 0, $max, true);

printf("=== AUDYT PARAMETRÓW che168 (read-only) ===\n");
printf("ofert w pliku: %d | unikalnych modeli: %d | badam: %d\n\n",
    count($offers), count(array_unique(array_column($offers, 'key'))), count($sample));

$api = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);

$unknown = $unknown_val = $groups = [];
$rows_total = 0;
$done = 0;
$failed = 0;
$t0 = time();

foreach ($sample as $key => $o) {
    $inner = (string) ($o['inner_id'] ?? '');
    if ($inner === '') { continue; }

    $r = $api->getOffer('che168', $inner);
    if (!$r) { $failed++; continue; }

    $d = $r['data'] ?? ($r['result'][0]['data'] ?? $r);

    // Kontrola blokady: jakie grupy konfiguracji API w ogóle oddaje.
    foreach (($d['extra']['configuration']['paramtypeitems'] ?? []) as $g) {
        $name = (string) ($g['name'] ?? '?');
        $groups[$name] = ($groups[$name] ?? 0) + 1;
    }

    $norm = AsiaAuto_Che168_Adapter::normalize($d);
    $ep   = $norm['extra_prep'] ?? [];
    if (!is_array($ep)) { $ep = []; }
    $rows_total += count($ep);

    foreach ($ep as $k => $v) {
        if (str_starts_with((string) $k, 'param_')) {
            $unknown[$k] = ($unknown[$k] ?? 0) + 1;
            if (!isset($unknown_val[$k])) {
                $unknown_val[$k] = is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    $done++;
    if ($done % 20 === 0) {
        printf("  ... %d/%d ofert, %ds\n", $done, count($sample), time() - $t0);
    }
    usleep(150000);
}

printf("\n=== WYNIK (%.1f min) ===\n", (time() - $t0) / 60);
printf("zbadanych ofert: %d (błędy API: %d)\n", $done, $failed);
printf("średnio wierszy specyfikacji na ofertę: %.1f\n", $done ? $rows_total / $done : 0);
printf("nieznanych kluczy param_*: %d różnych, %d wystąpień\n",
    count($unknown), array_sum($unknown));

echo "\n--- GRUPY KONFIGURACJI oddawane przez API (kontrola blokady wyposażenia) ---\n";
arsort($groups);
foreach ($groups as $g => $n) {
    printf("  %-30s w %d ofertach\n", $g, $n);
}

echo "\n--- NIEZNANE PARAMETRY (do che168-param-map.php) ---\n";
arsort($unknown);
foreach ($unknown as $k => $n) {
    printf("  %-16s %-4d przykład: %s\n", $k, $n, mb_substr((string) ($unknown_val[$k] ?? ''), 0, 60));
}

echo "\n(zero zapisów do bazy)\n";
