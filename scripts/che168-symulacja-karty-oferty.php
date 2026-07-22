<?php
/**
 * Symulacja: jak wygląda karta oferty che168 przy każdym wariancie dziedziczenia specyfikacji.
 * ZERO zapisów — wszystko liczone w pamięci, renderowane tymi samymi metodami co strona single.
 */

global $wpdb;

$sc  = new AsiaAuto_Shortcodes();
$ref = new ReflectionMethod('AsiaAuto_Shortcodes', 'buildTechSpecSections');
$ref->setAccessible(true);
$tr  = new AsiaAuto_Translator();

/** Etykieta PL dla klucza — z tego samego tłumaczenia, którego używa single. */
function labelsFor(AsiaAuto_Translator $tr, array $ep): array {
    $out = [];
    foreach ($tr->translateExtraPrep($ep) as $group) {
        foreach ($group['items'] as $it) {
            $out[$it['key']] = ['label' => $it['label'], 'value' => $it['value']];
        }
    }
    return $out;
}

$ids = $wpdb->get_col("SELECT p.ID FROM {$wpdb->posts} p
  JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key='_asiaauto_source' AND pm.meta_value='che168'
  WHERE p.post_type='listings' AND p.post_status='draft' AND p.ID>=389290 ORDER BY p.ID");

$cases = [];
foreach ($ids as $id) {
    $ep = get_post_meta($id, '_asiaauto_extra_prep', true);
    $ep = is_string($ep) ? json_decode($ep, true) : $ep;
    $ep = is_array($ep) ? $ep : [];
    $ser = wp_get_post_terms($id, 'serie', ['fields' => 'slugs']);
    if (!$ser) continue;
    $yr   = get_post_meta($id, 'ca-year', true);
    $comp = get_post_meta($id, '_asiaauto_complectation', true);

    $sid = $wpdb->get_col($wpdb->prepare("SELECT tr.object_id FROM {$wpdb->term_relationships} tr
       JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy='serie'
       JOIN {$wpdb->terms} t ON t.term_id=tt.term_id AND t.slug=%s", $ser[0]));

    $donors = [];
    foreach ($sid as $t) {
        if ((int) $t === (int) $id || get_post_status($t) !== 'publish') continue;
        if (get_post_meta($t, 'ca-year', true) !== $yr) continue;
        $tep = get_post_meta($t, '_asiaauto_extra_prep', true);
        $tep = is_string($tep) ? json_decode($tep, true) : $tep;
        if (!is_array($tep) || !$tep) continue;
        $donors[] = ['id' => $t, 'ep' => $tep, 'comp' => get_post_meta($t, '_asiaauto_complectation', true),
                     'src' => get_post_meta($t, '_asiaauto_source', true), 'title' => get_the_title($t)];
    }
    if (count($donors) < 2) continue;                   // do symulacji bierzemy oferty z dawcami
    $cases[] = compact('id', 'ep', 'comp', 'yr', 'donors');
    if (count($cases) >= 3) break;
}

$html = '<!doctype html><meta charset="utf-8"><title>Che168 — jak wygląda karta oferty przy każdym wariancie</title>
<style>
body{font:15px/1.6 system-ui,-apple-system,sans-serif;margin:0;padding:24px;background:#f7f8fa;color:#1B2A4A}
.wrap{max-width:1240px;margin:0 auto}h1{font-size:24px;margin:0 0 4px}h2{font-size:19px;margin:32px 0 4px}
h3{font-size:15px;margin:20px 0 8px;color:#4a5568}.m{color:#718096;font-size:13px}
.offer{background:#fff;border:1px solid #E1E4E8;border-radius:8px;padding:20px;margin:18px 0}
.cols{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-top:12px}
.col{border:1px solid #E1E4E8;border-radius:6px;overflow:hidden;background:#fff}
.col header{padding:10px 12px;font-weight:600;font-size:13px;color:#fff}
.c1 header{background:#718096}.c2 header{background:#2b6cb0}.c3 header{background:#276749}
.sec{border-top:1px solid #eef0f3}.sec b{display:block;padding:8px 12px;background:#f7fafc;font-size:12px;font-weight:600}
.row{display:flex;justify-content:space-between;gap:8px;padding:5px 12px;font-size:12px;border-top:1px solid #f2f4f7}
.row span:first-child{color:#718096}.row.new{background:#f0fff4}.row.risk{background:#fff5f5}
table{border-collapse:collapse;width:100%;background:#fff;font-size:13px}
th,td{border-bottom:1px solid #e8ebef;padding:7px 10px;text-align:left;vertical-align:top}
th{background:#f7fafc;font-size:12px;position:sticky;top:0}
code{font-size:11px;background:#f7fafc;padding:1px 4px;border-radius:3px;color:#4a5568}
.ok{color:#276749;font-weight:600}.bad{color:#c53030;font-weight:600}.dash{color:#cbd5e0}
.legend{background:#fff;border:1px solid #E1E4E8;border-radius:8px;padding:16px;margin:16px 0}
.legend li{margin-bottom:6px}.scroll{max-height:560px;overflow:auto;border:1px solid #E1E4E8;border-radius:6px}
</style><div class="wrap">
<h1>Che168 — jak wygląda karta oferty przy każdym wariancie</h1>
<p class="m">2026-07-22 · sekcje renderowane tą samą metodą, której używa strona oferty · zero zapisów do bazy</p>
<div class="legend"><strong>Trzy warianty, w skrócie:</strong><ul>
<li><strong>Ze źródła</strong> — to, co dziś pokaże strona: wyłącznie dane, które przysłał che168.</li>
<li><strong>Exact</strong> — dolewamy z innej sztuki <em>dokładnie tej samej wersji</em> i rocznika. Bezpieczne, ale trafia rzadko, bo che168 i dongchedi inaczej zapisują nazwę wersji.</li>
<li><strong>Luźne</strong> — dolewamy z dowolnej sztuki tego modelu i rocznika, także <em>innej wersji</em>. Dużo pól, ale część to cudze wyposażenie.</li>
<li><strong>Luźne + konsensus</strong> — jak wyżej, ale bierzemy tylko pola, w których <em>wszystkie</em> sztuki podają tę samą wartość. Rozbieżne (np. liczba głośników) zostają puste.</li>
</ul></div>';

foreach ($cases as $c) {
    $id = $c['id']; $ep = $c['ep']; $donors = $c['donors'];

    $u_exact = $ep; $n_exact = 0;
    foreach ($donors as $d) { if ($d['comp'] === $c['comp']) { $u_exact = $u_exact + $d['ep']; $n_exact++; } }

    $u_loose = $ep;
    foreach ($donors as $d) $u_loose = $u_loose + $d['ep'];

    $u_cons = $ep; $conflict = [];
    foreach ($donors[0]['ep'] as $k => $v) {
        if (isset($ep[$k])) continue;
        $vals = []; $all = true;
        foreach ($donors as $d) {
            if (!array_key_exists($k, $d['ep'])) { $all = false; break; }
            $vals[(string) (is_array($d['ep'][$k]) ? json_encode($d['ep'][$k]) : $d['ep'][$k])] = $d['id'];
        }
        if (!$all) continue;
        if (count($vals) === 1) $u_cons[$k] = $v; else $conflict[$k] = $vals;
    }

    // co che168 wnosi, a czego nie ma ŻADEN dawca (w większości dongchedi)
    $donorKeys = [];
    foreach ($donors as $d) $donorKeys += $d['ep'];
    $only_che = array_diff_key($ep, $donorKeys);

    $render = function (array $data) use ($ref, $sc, $id) {
        $sections = $ref->invoke($sc, $data, $id);
        $h = ''; $cnt = 0;
        foreach ($sections as $s) {
            $h .= '<div class="sec"><b>' . esc_html($s['title']) . '</b>';
            foreach ($s['rows'] as $r) {
                $cnt++;
                $h .= '<div class="row"><span>' . esc_html($r['label']) . '</span><span>' . esc_html((string) $r['value']) . '</span></div>';
            }
            $h .= '</div>';
        }
        return [$h, $cnt];
    };

    [$h_src, $n_src]   = $render($ep);
    [$h_loose, $n_l]   = $render($u_loose);
    [$h_cons, $n_c]    = $render($u_cons);

    $html .= '<h2>' . esc_html(get_the_title($id)) . '</h2>'
      . '<p class="m">#' . $id . ' · wersja: <em>' . esc_html($c['comp'] ?: '—') . '</em> · rocznik ' . esc_html($c['yr'])
      . ' · cena ' . number_format((int) get_post_meta($id, 'price', true), 0, ',', ' ') . ' PLN'
      . ' · sztuk tego modelu i rocznika w bazie: ' . count($donors) . '</p>'
      . '<div class="offer"><div class="m">Wiersze widoczne w sekcji „Dane techniczne" na stronie oferty:</div><div class="cols">'
      . '<div class="col c1"><header>Ze źródła che168 — ' . $n_src . ' wierszy (tak jest dziś)</header><div class="scroll">' . $h_src . '</div></div>'
      . '<div class="col c2"><header>Luźne — ' . $n_l . ' wierszy (część z innej wersji)</header><div class="scroll">' . $h_loose . '</div></div>'
      . '<div class="col c3"><header>Luźne + konsensus — ' . $n_c . ' wierszy</header><div class="scroll">' . $h_cons . '</div></div>'
      . '</div></div>';

    // Tabela porównawcza pól
    $lab_all = labelsFor($tr, $u_loose);
    $rows = '';
    $keys = array_keys($u_loose);
    foreach ($keys as $k) {
        $inSrc  = array_key_exists($k, $ep);
        $inEx   = array_key_exists($k, $u_exact) && !$inSrc;
        $inCons = array_key_exists($k, $u_cons) && !$inSrc;
        $isConf = isset($conflict[$k]);
        $lab = $lab_all[$k]['label'] ?? $k;
        $fmt = fn($x) => $x === null ? '<span class="dash">—</span>' : esc_html(mb_substr((string) (is_array($x) ? json_encode($x, JSON_UNESCAPED_UNICODE) : $x), 0, 40));
        $vals = [];
        foreach ($donors as $d) if (array_key_exists($k, $d['ep'])) $vals[(string) (is_array($d['ep'][$k]) ? json_encode($d['ep'][$k]) : $d['ep'][$k])] = 1;
        $rows .= '<tr' . ($isConf ? ' style="background:#fff5f5"' : ($inCons ? ' style="background:#f0fff4"' : '')) . '>'
          . '<td>' . esc_html($lab) . '</td><td><code>' . esc_html($k) . '</code></td>'
          . '<td>' . ($inSrc ? $fmt($ep[$k]) : '<span class="dash">brak</span>') . '</td>'
          . '<td>' . ($inSrc ? $fmt($ep[$k]) : ($inEx ? $fmt($u_exact[$k]) : '<span class="dash">—</span>')) . '</td>'
          . '<td>' . $fmt($u_loose[$k]) . ($isConf ? ' <span class="bad">(sztuki podają: ' . esc_html(implode(' / ', array_slice(array_keys($vals), 0, 3))) . ')</span>' : '') . '</td>'
          . '<td>' . (array_key_exists($k, $u_cons) ? $fmt($u_cons[$k]) : '<span class="bad">pomijamy</span>') . '</td></tr>';
    }
    $html .= '<h3>Pole po polu — skąd bierze się każda wartość</h3>'
      . '<p class="m">Zielone = konsensus dolewa (wszystkie sztuki zgodne). Czerwone = sztuki podają różne wartości, więc konsensus zostawia puste, a tryb luźny wpisałby jedną z nich.</p>'
      . '<div class="scroll"><table><tr><th>Nazwa na stronie</th><th>Klucz (konwencja dongchedi)</th><th>Che168 (źródło)</th><th>Exact (' . $n_exact . ' dawców)</th><th>Luźne (' . count($donors) . ' dawców)</th><th>Konsensus</th></tr>' . $rows . '</table></div>';

    // Co wnosi che168
    if ($only_che) {
        $r2 = '';
        foreach ($only_che as $k => $v) {
            $lab = $lab_all[$k]['label'] ?? $k;
            $r2 .= '<tr><td>' . esc_html($lab) . '</td><td><code>' . esc_html($k) . '</code></td><td>'
              . esc_html(mb_substr((string) (is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v), 0, 60)) . '</td>'
              . '<td>' . (str_starts_with($k, 'param_') ? '<span class="bad">parametr che168 bez odpowiednika — nie wyświetla się, siedzi w danych</span>' : '<span class="ok">wyświetla się na stronie</span>') . '</td></tr>';
        }
        $html .= '<h3>Co che168 wnosi, a czego nie ma żadna oferta dongchedi tego modelu (' . count($only_che) . ' pól)</h3>'
          . '<div class="scroll"><table><tr><th>Nazwa</th><th>Klucz</th><th>Wartość z che168</th><th>Status</th></tr>' . $r2 . '</table></div>';
    }
}

$html .= '</div>';
file_put_contents('/home/host476470/domains/auratest.pl/public_html/fe4f58fec53ctmp/primaauto-che168-karta-oferty-2026-07-22.html', $html);
echo "ofert w symulacji: " . count($cases) . "\n";
foreach ($cases as $c) echo "  #{$c['id']} " . get_the_title($c['id']) . " (dawców: " . count($c['donors']) . ")\n";
