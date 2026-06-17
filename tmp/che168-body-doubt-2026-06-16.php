<?php
/** PURE ANALYTICS: dla wątpliwych body_type che168 — jaki term 'body' MY nadajemy nakładającym się modelom.
 *  Zero propozycji. Tylko rozkład naszej istniejącej klasyfikacji. */
$api = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);

$doubt = ['coupe/roadster', 'sedan/hatchback', 'van', 'light truck'];

function ourBodyFor($mark, $model) {
    // dopasuj nasze ogłoszenie po mark (term) i serie (term) — najpierw dokładnie, potem luźno
    $q = new WP_Query([
        'post_type' => 'listings', 'post_status' => 'any', 'posts_per_page' => 3, 'fields' => 'ids',
        's' => trim($mark . ' ' . $model),
    ]);
    $out = [];
    foreach ($q->posts as $id) {
        $b = wp_get_post_terms($id, 'asiaauto_body', ['fields' => 'names']);
        $b = is_wp_error($b) ? [] : $b;
        $out[] = empty($b) ? '—' : implode('+', $b);
    }
    return $out;
}

foreach ($doubt as $bt) {
    echo "\n========== che168 body_type = '$bt' ==========\n";
    $tally = [];
    $hits = 0; $models = 0;
    for ($pg = 1; $pg <= 2; $pg++) {
        $r = $api->getOffers('che168', ['body_type' => $bt, 'page' => $pg]);
        $res = $r['result'] ?? [];
        $seen = [];
        foreach ($res as $row) {
            $o = $row['data'] ?? $row;
            $mark = $o['mark'] ?? '?'; $model = $o['model'] ?? '?';
            $key = "$mark|$model";
            if (isset($seen[$key])) continue;
            $seen[$key] = true; $models++;
            $ours = ourBodyFor($mark, $model);
            $ours = array_filter($ours, fn($x) => $x !== '—');
            if ($ours) {
                $hits++;
                foreach (array_unique($ours) as $term) $tally[$term] = ($tally[$term] ?? 0) + 1;
                printf("   %-30s -> u nas body: %s\n", mb_substr("$mark $model",0,30), implode(', ', array_unique($ours)));
            }
        }
    }
    arsort($tally);
    echo "   --- ROZKŁAD naszych termów (na $hits nakładających się z $models che168-modeli): ";
    echo ($tally ? json_encode($tally, JSON_UNESCAPED_UNICODE) : 'BRAK nakładających się — nie wozimy tej klasy') . "\n";
}
