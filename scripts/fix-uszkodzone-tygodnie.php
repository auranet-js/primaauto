<?php
/**
 * NAPRAWA uszkodzenia wprowadzonego przez `fix-proces-zgodnosc.php` (2026-08-04).
 *
 * Przyczyna: w regule podmiany użyto `'$16-8 tygodni'`. PHP odczytał `$16` jako odwołanie
 * do 16. grupy przechwytującej (nie istnieje) i podstawił pusty ciąg, zjadając cały prefiks
 * złapany przez grupę 1 — czyli fragment „transport morski … 4-6 tygodni” zamienił się w
 * samo „-8 tygodni”. Poprawny zapis to `'${1}6-8 tygodni'`.
 *
 * Skala: 77 wystąpień w 37 wariantach kontekstu. Backup pierwszego przebiegu został nadpisany
 * przez drugi (ten sam plik), więc odtwarzamy z wzorca, nie z kopii — uszkodzenie jest
 * deterministyczne, a brakujący fragment zawsze zaczynał się od „transport morski”.
 *
 * Uruchomienie: wp eval-file fix-uszkodzone-tygodnie.php        (dry-run)
 *               wp eval-file fix-uszkodzone-tygodnie.php apply
 */

$apply = in_array('apply', $args ?? [], true);

$REGULY = [
    // po kropce — początek zdania, wielka litera
    'początek zdania' => [
        '/([.!?]\s+)-8\s+tygodni/u',
        '$1Transport morski zajmuje 6-8 tygodni',
    ],
    // po dwukropku — wyliczenie, mała litera
    'po dwukropku' => [
        '/(:\s*)-8\s+tygodni/u',
        '$1transport morski 6-8 tygodni',
    ],
    // po czasowniku organizującym, gdy dalej w zdaniu jest domykający nawias
    // („organizujemy (około 4-6 tygodni do portu w Gdańsku lub Gdyni)”) — nawiasu
    // nie domykamy sami, bo powstałby drugi.
    'po „organizujemy” z nawiasem' => [
        '/\b(organizujemy|następnie)\s+-8\s+tygodni([^.()]*\))/iu',
        '$1 transport morski (około 6-8 tygodni$2',
    ],
    'po „organizujemy/następnie” bez nawiasu' => [
        '/\b(organizujemy|następnie)\s+-8\s+tygodni/iu',
        '$1 transport morski 6-8 tygodni',
    ],
    // reszta: numerowane kroki, strzałki, wtrącenia po przecinku
    'pozostałe konteksty' => [
        '/(^|[^0-9])-8\s+tygodni/u',
        '$1transport morski 6-8 tygodni',
    ],
];

global $wpdb;
$rows = $wpdb->get_results("SELECT tm.meta_id, tm.meta_key, tm.meta_value, t.name
    FROM {$wpdb->termmeta} tm JOIN {$wpdb->terms} t ON t.term_id = tm.term_id
    WHERE tm.meta_value REGEXP '(^|[^0-9])-8 tygodni'");

printf("Uszkodzonych pól: %d\n\n", count($rows));

$backup_dir = '/home/host476470/backups/primaauto/' . date('Y-m-d');
if ($apply && !is_dir($backup_dir)) { mkdir($backup_dir, 0755, true); }

$trafienia = array_fill_keys(array_keys($REGULY), 0);
$zmienione = 0; $backup = []; $probki = []; $faq_bledy = [];

foreach ($rows as $r) {
    $old = (string) $r->meta_value;
    $new = $old;
    foreach ($REGULY as $nazwa => [$re, $rep]) {
        $n = 0;
        $new = preg_replace($re, $rep, $new, -1, $n);
        $trafienia[$nazwa] += $n;
        if ($n && count($probki) < 6) {
            foreach (preg_split('/(?<=[.!?])\s+/u', wp_strip_all_tags($new)) as $s) {
                if (mb_stripos($s, 'transport morski') !== false && mb_stripos($s, '6-8') !== false) {
                    $probki[] = $r->name . ': ' . mb_substr(trim($s), 0, 130);
                    break;
                }
            }
        }
    }
    if ($new === $old) { continue; }

    if ($r->meta_key === 'asiaauto_faq_json' && json_decode($new, true) === null) {
        $faq_bledy[] = $r->name; continue;
    }

    $zmienione++;
    if ($apply) {
        $backup[$r->meta_id] = ['term' => $r->name, 'key' => $r->meta_key, 'value' => $old];
        update_metadata_by_mid('term', $r->meta_id, $new);
    }
}

echo "TRAFIENIA:\n";
foreach ($trafienia as $n => $c) { printf("  %-28s %3d\n", $n, $c); }
echo "\nPRÓBKI PO NAPRAWIE:\n";
foreach ($probki as $p) { printf("  • %s\n", $p); }
if ($faq_bledy) { printf("\n⚠ POMINIĘTE (FAQ): %s\n", implode(', ', array_unique($faq_bledy))); }

if ($apply && $backup) {
    file_put_contents("$backup_dir/uszkodzone-tygodnie.before.json",
        json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

printf("\n%s: %d pól\n", $apply ? 'ZASTOSOWANO' : 'DRY-RUN', $zmienione);

if ($apply) {
    $left = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->termmeta} WHERE meta_value REGEXP '(^|[^0-9])-8 tygodni'");
    printf("Kontrola — zostało uszkodzonych: %d\n", (int) $left);
    $bad = 0;
    foreach ($wpdb->get_col("SELECT meta_value FROM {$wpdb->termmeta} WHERE meta_key='asiaauto_faq_json' AND meta_value<>''") as $f) {
        if (json_decode($f, true) === null) { $bad++; }
    }
    printf("Kontrola — FAQ niepoprawnych JSON-ów: %d\n", $bad);
}
