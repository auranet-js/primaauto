<?php
/**
 * detektor-rozjazdu-napedu.php — READ-ONLY. Szuka ofert, których napęd (meta `fuel`)
 * kłóci się z napędem deklarowanym w nazwie serii, do której trafiły.
 *
 * Po co: nasze huby są rozbite po napędzie (Han EV / Han DM-i, Seal 6 EV / Seal 6 DM-i),
 * ale che168 trzyma warianty POD JEDNĄ nazwą modelu — rozbicie jest NASZE, nie źródłowe.
 * Płaski override w `che168-model-map.php` zawsze mylił wariant mniejszościowy (T-222, 2026-07-30:
 * 6 ofert BYD w cudzych hubach; naprawione kluczem `by_engine` w `resolveChe168()`).
 *
 * `che168-monitor.php` łapie rozjazdy hubów po `车型名称`, ale napędu nie sprawdza — ten detektor
 * domyka tę lukę. Kandydat do wciągnięcia do monitora.
 *
 * Ocenia TYLKO serie, których nazwa deklaruje napęd; reszta jest pomijana (brak podstawy do oceny).
 *
 * Użycie: php detektor-rozjazdu-napedu.php
 * Kod wyjścia: 0 = brak rozjazdów, 1 = są rozjazdy (nadaje się do crona/monitora).
 *
 * @since 2026-07-30 (T-222)
 */
define('WP_USE_THEMES', false);
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-load.php';

/** Czego nazwa serii się spodziewa: 'phev' | 'electric' | null (nie deklaruje). */
function aa_oczekiwany_naped(string $nazwa): ?string {
    $n = ' ' . mb_strtoupper($nazwa) . ' ';
    // Kolejność ma znaczenie — wariant hybrydowy sprawdzamy PRZED czystym EV.
    if (preg_match('/\b(DM-I|DM|EM-P|PHEV|EREV|HEV|I-DM|IDD)\b/u', $n)) return 'phev';
    if (preg_match('/\bEV\b/u', $n)) return 'electric';
    return null;
}

$rozjazdy = [];
$czyste = 0;
$sprawdzone = 0;

foreach (get_terms(['taxonomy' => 'serie', 'hide_empty' => false]) as $t) {
    $ocz = aa_oczekiwany_naped($t->name);
    if ($ocz === null) continue;

    foreach ((array) get_objects_in_term($t->term_id, 'serie') as $id) {
        $p = get_post($id);
        if (!$p || $p->post_type !== 'listings' || !in_array($p->post_status, ['publish', 'draft'], true)) continue;
        $fuel = (string) get_post_meta($id, 'fuel', true);
        if ($fuel === '') continue;   // brak danych = brak podstawy do oceny
        $sprawdzone++;

        $zgodne = ($ocz === 'electric' && $fuel === 'electric')
               || ($ocz === 'phev' && in_array($fuel, ['phev', 'hybrid', 'erev'], true));
        if ($zgodne) { $czyste++; continue; }

        $rozjazdy[$t->slug][] = [
            'id'    => $id,
            'st'    => $p->post_status,
            'fuel'  => $fuel,
            'ocz'   => $ocz,
            'tytul' => html_entity_decode($p->post_title),
            'src'   => (string) get_post_meta($id, '_asiaauto_source', true),
        ];
    }
}

$ile = array_sum(array_map('count', $rozjazdy));
printf("Sprawdzono %d ofert w seriach deklarujących napęd. Zgodnych: %d. ROZJAZDÓW: %d\n\n",
    $sprawdzone, $czyste, $ile);

$perZrodlo = [];
foreach ($rozjazdy as $slug => $lista) {
    printf("=== %s (seria mówi %s) — %d ofert ===\n", $slug, $lista[0]['ocz'], count($lista));
    foreach ($lista as $r) {
        printf("   #%-7d %-9s %-8s fuel=%-9s %s\n",
            $r['id'], $r['src'], $r['st'], $r['fuel'], mb_substr($r['tytul'], 0, 52));
        $perZrodlo[$r['src']] = ($perZrodlo[$r['src']] ?? 0) + 1;
    }
    echo "\n";
}

if ($ile) {
    echo 'Rozjazdy per źródło: ' . json_encode($perZrodlo) . "\n";
    echo "Naprawa: dopisz `by_engine` do override'u w data/che168-model-map.php, potem przepnij oferty.\n";
    exit(1);
}
exit(0);
