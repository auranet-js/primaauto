<?php
/**
 * Analizator mapowania v6.1 → istniejące termy w DB.
 * Wyjście: tmp/v6.1-taxonomy-plan.md (raport dla usera) + tmp/v6.1-taxonomy-plan.json (dla apply-skryptu).
 * Dry-run: NIE modyfikuje DB, tylko czyta.
 */

require_once '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';

global $wpdb;
$pfx = $wpdb->prefix;

// Wczytaj v6.1
$fp = fopen(__DIR__ . "/mapowanie-marek-modeli-v6.1.csv", "r");
fgetcsv($fp);
$rows = [];
while (($r = fgetcsv($fp)) !== false) $rows[] = $r;
fclose($fp);

// Cache: istniejące termy
$makeTerms = $wpdb->get_results("
    SELECT t.term_id, t.name, t.slug, tt.count
    FROM {$pfx}terms t
    JOIN {$pfx}term_taxonomy tt ON t.term_id=tt.term_id
    WHERE tt.taxonomy='make'", OBJECT_K);
$makeTermsByName = [];
foreach ($makeTerms as $t) $makeTermsByName[mb_strtolower(trim($t->name))] = $t;

$serieTerms = $wpdb->get_results("
    SELECT t.term_id, t.name, t.slug, tt.count, tt.parent
    FROM {$pfx}terms t
    JOIN {$pfx}term_taxonomy tt ON t.term_id=tt.term_id
    WHERE tt.taxonomy='serie'", OBJECT_K);
$serieTermsByName = [];
foreach ($serieTerms as $t) $serieTermsByName[mb_strtolower(trim($t->name))][] = $t;

$plan = [];
$targetMakes = []; // marka_EU_lowercase => [target_id | null]
$actions = ['rename_serie' => 0, 'create_serie' => 0, 'move_parent' => 0, 'merge_serie' => 0, 'create_make' => 0, 'skip' => 0, 'unmapped_cn' => 0];

foreach ($rows as $r) {
    $no = $r[0];
    $listings = $r[1];
    $markaCN = trim($r[2]);
    $markaEU = trim($r[3]);
    $modelCN = trim($r[4]);
    $serieEU = trim($r[5]);
    $titleEU = trim($r[6]);
    $slug = trim($r[8]);
    $uwagi = trim($r[12]);

    $row = [
        "no" => $no, "listings" => $listings,
        "cn" => "$markaCN / $modelCN",
        "eu" => "$markaEU / $serieEU",
        "slug" => $slug,
        "title" => $titleEU,
        "uwagi_short" => substr($uwagi, 0, 60),
    ];

    // SKIP (Q15b)?
    if (stripos($uwagi, "SKIP w imporcie") === 0) {
        $row["action"] = "SKIP";
        $row["reason"] = "Q15b — MINI × GWM JV wykluczone";
        $actions['skip']++;
        $plan[] = $row;
        continue;
    }

    // Puste wiersze bez SKIP flag (nie powinno się zdarzyć)
    if ($markaCN === "" || $modelCN === "") {
        $row["action"] = "SKIP_EMPTY";
        $row["reason"] = "Puste pola marka/model";
        $actions['skip']++;
        $plan[] = $row;
        continue;
    }

    // Znajdź istniejący term marki CN
    $oldMakeTerm = $makeTermsByName[mb_strtolower($markaCN)] ?? null;
    // Znajdź docelowy term marki EU
    $newMakeTerm = $makeTermsByName[mb_strtolower($markaEU)] ?? null;
    $newMakeId = $newMakeTerm ? $newMakeTerm->term_id : "NEW";

    if (!$newMakeTerm && !isset($targetMakes[mb_strtolower($markaEU)])) {
        $targetMakes[mb_strtolower($markaEU)] = null;
        $actions['create_make']++;
    }

    // Znajdź istniejący term serie dla modelu CN
    $oldSerieTerm = null;
    if (isset($serieTermsByName[mb_strtolower($modelCN)])) {
        $candidates = $serieTermsByName[mb_strtolower($modelCN)];
        // Jeśli jest dużo, preferuj tego z parent = old_make_id
        if ($oldMakeTerm) {
            foreach ($candidates as $c) {
                if ($c->parent == $oldMakeTerm->term_id) { $oldSerieTerm = $c; break; }
            }
        }
        if (!$oldSerieTerm) $oldSerieTerm = $candidates[0];
    }

    if (!$oldSerieTerm) {
        // CN model nie ma dopasowanego termu w DB
        $row["action"] = "UNMAPPED_CN";
        $row["reason"] = "Brak termu serie o nazwie '$modelCN' w DB — listingi mogą być pod innym termem";
        $row["old_make_id"] = $oldMakeTerm ? $oldMakeTerm->term_id : null;
        $row["new_make_id"] = $newMakeId;
        $actions['unmapped_cn']++;
        $plan[] = $row;
        continue;
    }

    // Target serie term — jeśli już istnieje pod new_make_id o tym samym slug
    $slugModel = basename(rtrim($slug, "/"));
    $targetSerieTerm = null;
    if (isset($serieTermsByName[mb_strtolower($serieEU)])) {
        foreach ($serieTermsByName[mb_strtolower($serieEU)] as $c) {
            if ($newMakeTerm && $c->parent == $newMakeTerm->term_id) { $targetSerieTerm = $c; break; }
        }
    }

    $action = null;
    $details = [];
    if ($targetSerieTerm && $targetSerieTerm->term_id != $oldSerieTerm->term_id) {
        $action = "MERGE_SERIE";
        $details[] = "Przenieś listings z term_id={$oldSerieTerm->term_id} ($modelCN, count={$oldSerieTerm->count}) → term_id={$targetSerieTerm->term_id} ($serieEU)";
        $actions['merge_serie']++;
    } else {
        // Rename in-place
        $changes = [];
        if ($oldSerieTerm->name !== $serieEU) $changes[] = "name: '{$oldSerieTerm->name}' → '$serieEU'";
        if ($oldSerieTerm->slug !== $slugModel) $changes[] = "slug: '{$oldSerieTerm->slug}' → '$slugModel'";
        if ($oldMakeTerm && $newMakeTerm && $oldSerieTerm->parent != $newMakeTerm->term_id) {
            $changes[] = "parent: {$oldSerieTerm->parent} → {$newMakeTerm->term_id} ($markaCN→$markaEU)";
            $actions['move_parent']++;
        }
        if ($changes) {
            $action = "RENAME_SERIE";
            $details = $changes;
            $actions['rename_serie']++;
        } else {
            $action = "NOOP";
            $details[] = "Brak zmian w termie";
        }
    }

    $row["action"] = $action;
    $row["details"] = $details;
    $row["old_serie_id"] = $oldSerieTerm->term_id;
    $row["old_serie_count"] = $oldSerieTerm->count;
    $row["target_serie_id"] = $targetSerieTerm ? $targetSerieTerm->term_id : null;
    $row["new_make_id"] = $newMakeId;
    $plan[] = $row;
}

// Zapis plan JSON
$json = json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if ($json === false) $json = json_encode(["error" => json_last_error_msg()]);
file_put_contents(__DIR__ . "/v6.1-taxonomy-plan.json", $json);

// Zapis raport MD
$md = "# Plan rebuildu taksonomii v6.1 — dry-run (2026-04-23)\n\n";
$md .= "Wiersze w v6.1: " . count($rows) . "\n\n";
$md .= "## Podsumowanie akcji\n\n";
$md .= "| Akcja | Liczba |\n|---|---|\n";
foreach ($actions as $a => $n) $md .= "| $a | $n |\n";
$md .= "\n## Docelowe nowe marki do utworzenia\n\n";
foreach ($targetMakes as $m => $_) $md .= "- `$m`\n";

$md .= "\n## Akcje per wiersz\n\n";
$md .= "| # | CN | EU | Listings | Akcja | Szczegóły |\n|---|---|---|---|---|---|\n";
foreach ($plan as $p) {
    $details = is_array($p['details'] ?? null) ? implode("; ", $p['details']) : ($p['reason'] ?? '');
    $details = str_replace("|", "\\|", $details);
    $md .= "| {$p['no']} | {$p['cn']} | {$p['eu']} | {$p['listings']} | {$p['action']} | $details |\n";
}
file_put_contents(__DIR__ . "/v6.1-taxonomy-plan.md", $md);

echo "Plan MD:   " . __DIR__ . "/v6.1-taxonomy-plan.md\n";
echo "Plan JSON: " . __DIR__ . "/v6.1-taxonomy-plan.json\n";
echo "\nPodsumowanie akcji:\n";
foreach ($actions as $a => $n) printf("  %-15s %d\n", $a, $n);
