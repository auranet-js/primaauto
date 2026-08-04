<?php
/**
 * Uzgodnienie treści hubów z tym, co deklarujemy na stronach informacyjnych (T-236, 2026-08-04).
 *
 * Źródło prawdy: strony `/informacje/*`, które utrzymuje Ruslan.
 *   - „Proces zamawiania": transport morski **ok. 6–8 tygodni** (huby mówiły 4–6).
 *   - „Homologacja i rejestracja": klient **sam składa wniosek w wydziale komunikacji**,
 *     rejestracja przez nas tylko „na życzenie" (huby obiecywały auto z tablicami jako standard).
 *   - „Pod dom do rejestracji": cena końcowa zawiera wszystko — TO ZOSTAJE bez zmian.
 *
 * Zasada: nie opisujemy procesu ani nie obiecujemy rezultatu, którego strony informacyjne
 * nie deklarują. 747 unikalnych wariantów sformułowań ⇒ reguły regexowe, nie proste podmiany.
 *
 * Uruchomienie: wp eval-file fix-proces-zgodnosc.php        (dry-run)
 *               wp eval-file fix-proces-zgodnosc.php apply
 */

$apply = in_array('apply', $args ?? [], true);

$REGULY = [
    // 1) Transport morski: 4–6 → 6–8 tygodni (zgodnie z „Proces zamawiania").
    //    Dwa warianty, bo oryginał raz ma nawiasy, raz nie — doklejenie ich na siłę
    //    dawało „Transport morski zajmuje (6-8 tygodni)".
    'transport (4-6) w nawiasie' => [
        '/(transport\w*\s+morski\w*[^.]{0,40}?)\(\s*4\s*[-–]\s*6\s+tygodni\s*\)/iu',
        '$1(6-8 tygodni)',
    ],
    'transport 4-6 bez nawiasu' => [
        '/(transport\w*\s+morski\w*[^.]{0,40}?)\b4\s*[-–]\s*6\s+tygodni\b/iu',
        '$16-8 tygodni',
    ],
    'sam nawias 4-6 tyg.' => [
        '/\(\s*4\s*[-–]\s*6\s+tygodni\s*\)/u',
        '(6-8 tygodni)',
    ],

    // 2) Obietnica tablic — strony informacyjne mówią, że klient sam rejestruje
    'tablice + gotowe do jazdy' => [
        '/z\s+polskimi\s+tablicami,?\s*gotowe\w*\s+do\s+jazdy/iu',
        'z kompletem dokumentów do rejestracji',
    ],
    'tablice rejestracyjne' => [
        '/z\s+(polskimi\s+tablicami|tablicami\s+rejestracyjnymi)/iu',
        'z kompletem dokumentów do rejestracji',
    ],
    'odbiór gotowego auta z tablicami' => [
        '/odbiór\s+gotowego\s+auta\s+z\s+kompletem\s+dokumentów\s+do\s+rejestracji/iu',
        'odbiór auta z kompletem dokumentów do rejestracji',
    ],

    // 3) „rejestrujemy za Ciebie" jako standard → to jest opcja na życzenie
    'rejestrujemy pojazd/auto' => [
        '/\b(rejestrujemy)\s+(pojazd|auto|samochód)\s+na\s+klienta\b/iu',
        'na życzenie rejestrujemy $2 za klienta',
    ],
    'homologacja i rejestracja, a Ty odbierasz' => [
        '/(homologacj\w+\s+indywidualn\w+)\s+i\s+rejestracj\w+,\s*a\s+Ty\s+odbierasz/iu',
        '$1, a Ty odbierasz',
    ],

    // 4) Sprzątanie po podmianie: końcówki, które po zmianie przeczą same sobie
    //    („dokumenty do rejestracji, gotowy do użytkowania" — jedno wyklucza drugie).
    'gotowy do użytkowania po dokumentach' => [
        '/(z\s+kompletem\s+dokumentów\s+do\s+rejestracji),\s*gotow\w*\s+do\s+(użytkowania|jazdy)/iu',
        '$1',
    ],

    // 5) „realizujemy/wykonujemy … rejestrację" — wg stron informacyjnych wniosek
    //    składa klient, my przygotowujemy dokumenty (rejestracja tylko na życzenie).
    'realizujemy rejestrację' => [
        '/\b(realizujemy|wykonujemy|przeprowadzamy)\s+((?:[^.]{0,60}?)\s*)pierwsz\w+\s+rejestracj\w+\s+w\s+Polsce/iu',
        'realizujemy $2przygotowanie kompletu dokumentów do pierwszej rejestracji w Polsce',
    ],
    'oraz rejestrację w Polsce' => [
        '/\soraz\s+(pierwsz\w+\s+)?rejestracj\w+\s+w\s+Polsce\b/iu',
        ' oraz komplet dokumentów do rejestracji w Polsce',
    ],

    // 6) Druga tura: warianty pominięte przez reguły 1-5 (sprawdzone ręcznie — wszystkie
    //    wystąpienia dotyczą transportu morskiego i obietnicy tablic, więc podmiana jest bezpieczna).
    'aktywne tablice + dokumenty' => [
        '/z\s+aktywnymi\s+polskimi\s+tablicami\s+rejestracyjnymi\s+i\s+kompletem\s+dokumentów/iu',
        'z kompletem dokumentów do rejestracji',
    ],
    'dokumenty I tablice' => [
        '/\s*(,|\si)\s*(z\s+)?(pełnymi\s+|kompletnymi\s+|aktywnymi\s+)?polskimi\s+tablicami(\s+rejestracyjnymi)?/iu',
        '',
    ],
    'reszta 4-6 tygodni (transport)' => [
        '/\b4\s*[-–]\s*6\s+tygodni(ach|e)?\b/iu',
        '6-8 tygodni',
    ],
];

global $wpdb;
$rows = $wpdb->get_results("SELECT tm.meta_id, tm.meta_key, tm.meta_value, t.name
    FROM {$wpdb->termmeta} tm JOIN {$wpdb->terms} t ON t.term_id = tm.term_id
    WHERE tm.meta_value REGEXP '4[[:space:]]*[-–][[:space:]]*6[[:space:]]+tygodni'
       OR tm.meta_value LIKE '%tablicami%'
       OR tm.meta_value LIKE '%rejestrujemy %'");

printf("Kandydatów: %d pól\n\n", count($rows));

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
        if ($n) {
            $trafienia[$nazwa] += $n;
            if (count($probki[$nazwa] ?? []) < 2) {
                $probki[$nazwa][] = $r->name . ' / ' . str_replace('asiaauto_', '', $r->meta_key);
            }
        }
    }
    if ($new === $old) { continue; }

    // FAQ musi się nadal parsować — inaczej znika schema FAQPage
    if ($r->meta_key === 'asiaauto_faq_json' && json_decode($new, true) === null) {
        $faq_bledy[] = $r->name;
        continue;
    }

    $zmienione++;
    if ($apply) {
        $backup[$r->meta_id] = ['term' => $r->name, 'key' => $r->meta_key, 'value' => $old];
        update_metadata_by_mid('term', $r->meta_id, $new);
    }
}

echo "TRAFIENIA REGUŁ:\n";
foreach ($trafienia as $nazwa => $n) {
    printf("  %-38s %4d  %s\n", $nazwa, $n,
        $n ? '(np. ' . implode(', ', $probki[$nazwa]) . ')' : '');
}

if ($faq_bledy) {
    printf("\n⚠ POMINIĘTE (FAQ przestałoby się parsować): %s\n", implode(', ', array_unique($faq_bledy)));
}

if ($apply && $backup) {
    file_put_contents("$backup_dir/proces-zgodnosc.before.json",
        json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

printf("\n%s: %d pól\n", $apply ? 'ZASTOSOWANO' : 'DRY-RUN', $zmienione);

if ($apply) {
    $left_tab = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->termmeta} WHERE meta_value LIKE '%polskimi tablicami%'");
    $left_tyg = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->termmeta}
        WHERE meta_value REGEXP '4[[:space:]]*[-–][[:space:]]*6[[:space:]]+tygodni'");
    printf("Kontrola — zostało: „polskimi tablicami\" %d pól, „4-6 tygodni\" %d pól\n", (int) $left_tab, (int) $left_tyg);

    // globalna walidacja FAQ po zapisie
    $bad = 0;
    foreach ($wpdb->get_col("SELECT meta_value FROM {$wpdb->termmeta} WHERE meta_key='asiaauto_faq_json' AND meta_value<>''") as $f) {
        if (json_decode($f, true) === null) { $bad++; }
    }
    printf("Kontrola — FAQ niepoprawnych JSON-ów w całej bazie: %d\n", $bad);
}
