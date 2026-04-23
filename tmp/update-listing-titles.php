<?php
/**
 * Batch update post_title listingów wg mapowania v6.1 (CN→EU).
 * Parsuje prefix "markCN modelCN" z post_title, zamienia na title_eu, zachowuje resztę (rok + complectation).
 * Dry-run domyślnie.
 */

require_once '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';
$mode = ($argv[1] ?? 'DRY_RUN') === 'APPLY' ? 'APPLY' : 'DRY_RUN';
echo ">>> MODE: $mode\n";

$mapFile = '/home/host476470/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/data/brand-mapping-v6.1.php';
$map = require $mapFile;

// Sortuj klucze po długości (dłuższe CN-nazwy najpierw, żeby "Chery Fengyun" matchowało przed "Chery")
$sortedKeys = array_keys($map);
usort($sortedKeys, fn($a, $b) => strlen($b) - strlen($a));

global $wpdb;
$pfx = $wpdb->prefix;

// Zbieramy listingi (publish + draft + pending)
$listings = $wpdb->get_results("
    SELECT ID, post_title
    FROM {$pfx}posts
    WHERE post_type='listings' AND post_status IN ('publish','draft','pending')
    ORDER BY ID
");

$stats = ['total' => count($listings), 'matched' => 0, 'no_match' => 0, 'no_change' => 0, 'updated' => 0];
$noMatchExamples = [];
$fp = fopen(__DIR__ . '/v6.1-title-diff.log', 'w');

foreach ($listings as $post) {
    $title = $post->post_title;
    $matched = null;
    foreach ($sortedKeys as $key) {
        [$markCN, $modelCN] = explode('|', $key, 2);
        // Pattern 1: pełny prefix "markCN modelCN ..."
        $p1 = '/^' . preg_quote($markCN, '/') . '\s+' . preg_quote($modelCN, '/') . '(\s+.*)?$/iu';
        if (preg_match($p1, $title, $m)) {
            $matched = [$key, $m[1] ?? ''];
            break;
        }
        // Pattern 2: gdy modelCN już zawiera markCN (dedupe w importerze) — szukaj tylko modelCN
        if (stripos($modelCN, $markCN) === 0) {
            $p2 = '/^' . preg_quote($modelCN, '/') . '(\s+.*)?$/iu';
            if (preg_match($p2, $title, $m)) {
                $matched = [$key, $m[1] ?? ''];
                break;
            }
        }
        // Pattern 3: markCN składa się z wielu słów, modelCN zaczyna się od ostatniego z nich
        // (np. mark="GAC Trumpchi", model="Trumpchi E8" → post_title="GAC Trumpchi E8")
        $markWords = preg_split('/\s+/', $markCN);
        if (count($markWords) > 1) {
            $lastMarkWord = end($markWords);
            if (stripos($modelCN, $lastMarkWord . ' ') === 0 || strcasecmp($modelCN, $lastMarkWord) === 0) {
                $modelStripped = trim(substr($modelCN, strlen($lastMarkWord)));
                if ($modelStripped !== '') {
                    $p3 = '/^' . preg_quote($markCN, '/') . '\s+' . preg_quote($modelStripped, '/') . '(\s+.*)?$/iu';
                    if (preg_match($p3, $title, $m)) {
                        $matched = [$key, $m[1] ?? ''];
                        break;
                    }
                }
            }
        }
    }
    if (!$matched) {
        // Fallback: może prefix markCN (tylko marka bez modelu) — pomijamy, post_title nie zmieniamy
        $stats['no_match']++;
        if (count($noMatchExamples) < 5) $noMatchExamples[] = "#{$post->ID}: $title";
        continue;
    }
    $stats['matched']++;
    [$key, $rest] = $matched;
    $eu = $map[$key];
    $newTitle = trim($eu['title_eu'] . $rest);
    if ($newTitle === $title) { $stats['no_change']++; continue; }

    fprintf($fp, "#%d\n  OLD: %s\n  NEW: %s\n\n", $post->ID, $title, $newTitle);
    if ($mode === 'APPLY') {
        $wpdb->update($pfx . 'posts', ['post_title' => $newTitle], ['ID' => $post->ID]);
    }
    $stats['updated']++;
}
fclose($fp);

echo "\nStatystyki:\n";
foreach ($stats as $k => $v) printf("  %-15s %d\n", $k, $v);
echo "\nNo-match examples:\n";
foreach ($noMatchExamples as $e) echo "  $e\n";
echo "\nDiff log: " . __DIR__ . "/v6.1-title-diff.log\n";
