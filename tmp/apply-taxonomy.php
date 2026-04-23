<?php
/**
 * Apply taksonomii v6.1 — tryb DRY_RUN (domyślnie) lub APPLY.
 * Uruchomienie:
 *   php apply-taxonomy.php          -> DRY_RUN, generuje tmp/v6.1-apply.sql
 *   php apply-taxonomy.php APPLY    -> wykonuje na bazie (wymaga potwierdzenia)
 */

require_once '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';

$mode = ($argv[1] ?? 'DRY_RUN') === 'APPLY' ? 'APPLY' : 'DRY_RUN';
echo ">>> MODE: $mode\n";

global $wpdb;
$pfx = $wpdb->prefix;
$sqlFile = __DIR__ . "/v6.1-apply.sql";
$logFile = __DIR__ . "/v6.1-apply.log";

$plan = json_decode(file_get_contents(__DIR__ . "/v6.1-taxonomy-plan.json"), true);
if (!$plan) { echo "ERR: brak plan JSON\n"; exit(1); }

$sqlLines = [];
$log = [];
$stats = ['make_create' => 0, 'serie_rename' => 0, 'serie_move_parent' => 0, 'termmeta_set' => 0, 'serie_create' => 0, 'skip' => 0, 'unmapped' => 0, 'relationships_update' => 0];

// --- 1. Zbierz nowe marki do utworzenia ---
$newMakes = [];
foreach ($plan as $p) {
    if (($p['new_make_id'] ?? '') === 'NEW') {
        $eu = explode(' / ', $p['eu'])[0];
        $newMakes[$eu] = true;
    }
}

$newMakeIds = [];
foreach (array_keys($newMakes) as $makeName) {
    $slug = sanitize_title($makeName);
    // Sprawdzamy czy nie istnieje już (może być różnica w casingu)
    $existing = $wpdb->get_row($wpdb->prepare("SELECT t.term_id FROM {$pfx}terms t JOIN {$pfx}term_taxonomy tt ON t.term_id=tt.term_id WHERE tt.taxonomy='make' AND t.slug=%s", $slug));
    if ($existing) {
        $newMakeIds[$makeName] = (int)$existing->term_id;
        $log[] = "[make] '$makeName' już istnieje jako term_id={$existing->term_id} (slug=$slug)";
        continue;
    }
    if ($mode === 'APPLY') {
        $result = wp_insert_term($makeName, 'make', ['slug' => $slug]);
        if (is_wp_error($result)) { $log[] = "[make] ERR tworzenie $makeName: " . $result->get_error_message(); continue; }
        $newMakeIds[$makeName] = (int)$result['term_id'];
        $log[] = "[make] CREATED '$makeName' term_id={$result['term_id']}";
    } else {
        $sqlLines[] = "-- CREATE make '$makeName' slug='$slug'";
        $sqlLines[] = "INSERT INTO {$pfx}terms (name, slug, term_group) VALUES ('" . esc_sql($makeName) . "', '" . esc_sql($slug) . "', 0);";
        $sqlLines[] = "INSERT INTO {$pfx}term_taxonomy (term_id, taxonomy, description, parent, count) VALUES (LAST_INSERT_ID(), 'make', '', 0, 0);";
        $newMakeIds[$makeName] = "@@MAKE_$makeName@@"; // placeholder
        $log[] = "[make] DRY_RUN create '$makeName'";
    }
    $stats['make_create']++;
}

// Odśwież cache wszystkich marek
$allMakes = [];
$rows = $wpdb->get_results("SELECT t.term_id, t.name FROM {$pfx}terms t JOIN {$pfx}term_taxonomy tt ON t.term_id=tt.term_id WHERE tt.taxonomy='make'");
foreach ($rows as $r) $allMakes[mb_strtolower($r->name)] = (int)$r->term_id;
foreach ($newMakeIds as $name => $id) {
    if (is_int($id)) $allMakes[mb_strtolower($name)] = $id;
}

// --- 2. Przetwórz każdy wiersz planu ---
foreach ($plan as $p) {
    $action = $p['action'] ?? '';
    $no = $p['no'];

    if ($action === 'SKIP' || $action === 'SKIP_EMPTY') { $stats['skip']++; continue; }

    [$markaEU, $serieEU] = explode(' / ', $p['eu'] . ' / ');
    [$markaCN, $modelCN] = explode(' / ', $p['cn'] . ' / ');
    $markaEU = trim($markaEU); $serieEU = trim($serieEU);
    $markaCN = trim($markaCN); $modelCN = trim($modelCN);
    $slugFull = $p['slug'] ?? '';
    $slugModel = basename(rtrim($slugFull, "/"));
    $newMakeId = is_numeric($p['new_make_id'] ?? null) ? (int)$p['new_make_id'] : ($allMakes[mb_strtolower($markaEU)] ?? null);

    if (!$newMakeId) {
        $log[] = "[#$no] ERR: brak target_make_id dla '$markaEU'";
        continue;
    }

    // Pobierz dane wiersza z v6.1
    // (title i api_value z JSON planu nie są dostępne — doładuj)
    // Zostawiam na później — w tym skrypcie pobieramy z v6.1 CSV
}

// Załaduj v6.1 dla title i api_value
$rowsCsv = [];
$fp = fopen(__DIR__ . "/mapowanie-marek-modeli-v6.1.csv", "r");
fgetcsv($fp);
while (($r = fgetcsv($fp)) !== false) $rowsCsv[$r[0]] = $r;
fclose($fp);

// Idź drugi raz — teraz z danymi
foreach ($plan as $p) {
    $action = $p['action'] ?? '';
    $no = $p['no'];
    if ($action === 'SKIP' || $action === 'SKIP_EMPTY' || $action === 'NOOP') continue;

    $csv = $rowsCsv[$no] ?? null;
    if (!$csv) { $log[] = "[#$no] ERR: brak wiersza w CSV"; continue; }

    $markaCN = trim($csv[2]);
    $markaEU = trim($csv[3]);
    $modelCN = trim($csv[4]);
    $serieEU = trim($csv[5]);
    $titleEU = trim($csv[6]);
    $slugFull = trim($csv[8]);
    $slugModel = basename(rtrim($slugFull, "/"));
    $newMakeId = $allMakes[mb_strtolower($markaEU)] ?? null;

    if (!$newMakeId) { $log[] = "[#$no] ERR: brak make_id dla '$markaEU'"; continue; }

    if ($action === 'RENAME_SERIE') {
        $oldId = (int)$p['old_serie_id'];
        $setClauses = [];
        $parentClause = [];
        // Sprawdź istniejące
        $cur = $wpdb->get_row($wpdb->prepare("SELECT t.name, t.slug, tt.parent FROM {$pfx}terms t JOIN {$pfx}term_taxonomy tt ON t.term_id=tt.term_id WHERE t.term_id=%d", $oldId));
        if (!$cur) { $log[] = "[#$no] ERR: brak termu id=$oldId"; continue; }
        if ($cur->name !== $serieEU) $setClauses['name'] = $serieEU;
        if ($cur->slug !== $slugModel) $setClauses['slug'] = $slugModel;
        if ((int)$cur->parent !== $newMakeId) $parentClause['parent'] = $newMakeId;

        if ($setClauses) {
            if ($mode === 'APPLY') {
                $wpdb->update("{$pfx}terms", $setClauses, ['term_id' => $oldId]);
            } else {
                $cols = [];
                foreach ($setClauses as $k => $v) $cols[] = "$k='" . esc_sql($v) . "'";
                $sqlLines[] = "UPDATE {$pfx}terms SET " . implode(", ", $cols) . " WHERE term_id=$oldId; -- #$no $markaCN/$modelCN → $markaEU/$serieEU";
            }
            $stats['serie_rename']++;
        }
        if ($parentClause) {
            if ($mode === 'APPLY') {
                $wpdb->update("{$pfx}term_taxonomy", $parentClause, ['term_id' => $oldId]);
            } else {
                $sqlLines[] = "UPDATE {$pfx}term_taxonomy SET parent=$newMakeId WHERE term_id=$oldId; -- #$no parent → $markaEU";
            }
            $stats['serie_move_parent']++;
        }
        // Termmeta: _serie_full_title, _serie_api_value
        foreach (['_serie_full_title' => $titleEU, '_serie_api_value' => $modelCN] as $mk => $mv) {
            if ($mv === '') continue;
            if ($mode === 'APPLY') {
                update_term_meta($oldId, $mk, $mv);
            } else {
                $sqlLines[] = "INSERT INTO {$pfx}termmeta (term_id, meta_key, meta_value) VALUES ($oldId, '$mk', '" . esc_sql($mv) . "') ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value); -- #$no";
            }
            $stats['termmeta_set']++;
        }
        continue;
    }

    if ($action === 'UNMAPPED_CN') {
        // Potrzeba utworzyć nowy term serie pod new_make_id i przypisać listingi
        if ($mode === 'APPLY') {
            $result = wp_insert_term($serieEU, 'serie', ['slug' => $slugModel, 'parent' => $newMakeId]);
            if (is_wp_error($result)) { $log[] = "[#$no] ERR create serie: " . $result->get_error_message(); continue; }
            $newSerieId = (int)$result['term_id'];
            update_term_meta($newSerieId, '_serie_full_title', $titleEU);
            update_term_meta($newSerieId, '_serie_api_value', $modelCN);
            // Przypisz listingi: szukaj po post_title pod marką CN
            // UWAGA: to jest najbardziej ryzykowny krok. Na APPLY robimy osobno.
            $log[] = "[#$no] CREATED serie term_id=$newSerieId '$serieEU' pod make_id=$newMakeId. LISTING ASSIGNMENT TODO (Etap 3b).";
        } else {
            $sqlLines[] = "-- #$no UNMAPPED: create serie '$serieEU' pod make_id=$newMakeId";
            $sqlLines[] = "INSERT INTO {$pfx}terms (name, slug, term_group) VALUES ('" . esc_sql($serieEU) . "', '" . esc_sql($slugModel) . "', 0);";
            $sqlLines[] = "INSERT INTO {$pfx}term_taxonomy (term_id, taxonomy, description, parent, count) VALUES (LAST_INSERT_ID(), 'serie', '', $newMakeId, 0);";
        }
        $stats['serie_create']++;
        continue;
    }
}

// Zapis
if ($mode === 'APPLY') {
    file_put_contents($logFile, implode("\n", $log));
    echo ">>> APPLY zakończony. Log: $logFile\n";
} else {
    $sqlHeader = "-- DRY_RUN apply v6.1 taksonomii (" . date("Y-m-d H:i:s") . ")\n-- Wygenerowane przez tmp/apply-taxonomy.php\n-- Backup DB: ~/backups/primaauto/2026-04-23-v6.1-taxonomy/terms-*.sql\n\n";
    file_put_contents($sqlFile, $sqlHeader . implode("\n", $sqlLines));
    file_put_contents($logFile, implode("\n", $log));
    echo ">>> DRY_RUN. SQL: $sqlFile\n";
    echo ">>> Log: $logFile\n";
}
echo "\nStatystyki:\n";
foreach ($stats as $k => $v) printf("  %-25s %d\n", $k, $v);
