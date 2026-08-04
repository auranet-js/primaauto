<?php
/**
 * Usunięcie z treści hubów niezweryfikowanych twierdzeń o procedurze homologacji.
 *
 * Powód (2026-08-04): treści twierdzą, że dopuszczenie do ruchu odbywa się „w certyfikowanej
 * stacji diagnostycznej w Polsce". Weryfikacja u źródła (TDT, biznes.gov.pl): dopuszczenie
 * jednostkowe to decyzja Dyrektora Transportowego Dozoru Technicznego, a stacja diagnostyczna
 * wykonuje wyłącznie badanie techniczne — inny organ, inny etap. Dodatkowo realna ścieżka
 * Prima-Auto nie jest potwierdzona (dokumenty mogą powstawać w innym państwie UE).
 *
 * Zasada podmiany: zostaje REZULTAT dla klienta (auto dopuszczone, formalności po naszej
 * stronie, koszt w cenie), znika OPIS PROCEDURY (organ, miejsce, kraj) — bo jest niepotwierdzony
 * i jest know-how, którego nie publikujemy.
 *
 * Uruchomienie: wp eval-file fix-homologacja.php          (dry-run)
 *               wp eval-file fix-homologacja.php --apply
 */

$apply = in_array('apply', $args ?? [], true);

$PODMIANY = [
    // druga tura (2026-08-04): sformułowania pominięte przez pierwszy zestaw
    'Homologację przeprowadzamy indywidualnie w polskiej stacji diagnostycznej'
        => 'Dopuszczeniem auta do ruchu zajmujemy się my',
    'Homologację indywidualną przeprowadzamy w wybranej przez nas stacji diagnostycznej, posiadającej uprawnienia do badań pojazdów sprowadzanych spoza UE'
        => 'Dopuszczeniem auta do ruchu zajmujemy się my — obsługujemy wszystkie wymagane badania i formalności',
    'Następnie pojazd trafia do wybranej przez nas stacji diagnostycznej, gdzie przeprowadzamy homologację indywidualną zgodnie z wymogami polskiego prawa'
        => 'Następnie auto przechodzi wymagane badania i uzyskuje dopuszczenie do ruchu',
    'homologacja indywidualna przeprowadzana w polskiej stacji diagnostycznej'
        => 'dopuszczenie auta do ruchu wraz z kompletem dokumentów',
    'Homologacja indywidualna przeprowadzana jest przez Prima-Auto w polskiej stacji diagnostycznej – koszt wliczony w cenę końcową'
        => 'Dopuszczeniem auta do ruchu i kompletem dokumentów zajmujemy się my, a koszt jest wliczony w cenę końcową',
    // wskazanie miejsca/organu — nieprawdziwe i zdradza proces
    'homologację indywidualną w certyfikowanej stacji diagnostycznej w Polsce'
        => 'dopuszczenie do ruchu wraz z kompletem dokumentów',
    'homologacji indywidualnej w stacji diagnostycznej'
        => 'dopuszczenia do ruchu',
    'homologację indywidualną w certyfikowanej stacji diagnostycznej'
        => 'dopuszczenie do ruchu wraz z kompletem dokumentów',
    'homologację indywidualną w akredytowanej stacji diagnostycznej'
        => 'dopuszczenie do ruchu wraz z kompletem dokumentów',
    'homologacji indywidualnej w certyfikowanej stacji diagnostycznej'
        => 'dopuszczenia do ruchu',
    'w certyfikowanej stacji diagnostycznej w Polsce' => '',
    'w akredytowanej stacji diagnostycznej w Polsce'  => '',
    'w certyfikowanej stacji diagnostycznej'          => '',
    'w akredytowanej stacji diagnostycznej'           => '',
    'w stacji diagnostycznej'                         => '',
];

global $wpdb;
$rows = $wpdb->get_results("SELECT tm.meta_id, tm.term_id, tm.meta_key, t.name
    FROM {$wpdb->termmeta} tm JOIN {$wpdb->terms} t ON t.term_id = tm.term_id
    WHERE tm.meta_value LIKE '%stacji diagnostycznej%' OR tm.meta_value LIKE '%stacja diagnostyczna%'");

$backup_dir = '/home/host476470/backups/primaauto/' . date('Y-m-d');
if ($apply && !is_dir($backup_dir)) { mkdir($backup_dir, 0755, true); }

$zmienione = 0;
$backup = [];

foreach ($rows as $r) {
    $meta = get_metadata_by_mid('term', $r->meta_id);
    $old  = (string) $meta->meta_value;
    $new  = $old;

    foreach ($PODMIANY as $from => $to) {
        $new = str_replace($from, $to, $new);
    }
    // sprzątanie po usunięciu wtrąceń: podwójne spacje i spacja przed kropką/przecinkiem
    $new = preg_replace('/ {2,}/u', ' ', $new);
    $new = preg_replace('/ ([.,])/u', '$1', $new);

    if ($new === $old) { printf("  bez zmian: %s / %s\n", $r->name, $r->meta_key); continue; }

    $backup[$r->meta_id] = ['term' => $r->name, 'key' => $r->meta_key, 'value' => $old];
    $zmienione++;

    // pokaż różnicę na poziomie zdania
    foreach (preg_split('/(?<=[.!?])\s+/u', wp_strip_all_tags($old)) as $s) {
        if (mb_stripos($s, 'stacj') !== false) {
            $s_new = $s;
            foreach ($PODMIANY as $from => $to) { $s_new = str_replace($from, $to, $s_new); }
            $s_new = preg_replace('/ {2,}/u', ' ', $s_new);
            $s_new = preg_replace('/ ([.,])/u', '$1', $s_new);
            printf("\n  %s / %s\n    PRZED: %s\n    PO:    %s\n", $r->name, $r->meta_key,
                mb_substr(trim($s), 0, 190), mb_substr(trim($s_new), 0, 190));
        }
    }

    if ($apply) {
        update_metadata_by_mid('term', $r->meta_id, $new);
    }
}

if ($apply && $backup) {
    file_put_contents("$backup_dir/homologacja-stacja-diagnostyczna.before.json",
        json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

printf("\n%s: %d pól do zmiany z %d znalezionych%s\n",
    $apply ? 'ZASTOSOWANO' : 'DRY-RUN', $zmienione, count($rows),
    $apply ? " (backup: $backup_dir/homologacja-stacja-diagnostyczna.before.json)" : '');

/* Kontrola: czy po podmianie zostały jeszcze wzmianki o stacji */
if ($apply) {
    $left = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->termmeta}
        WHERE meta_value LIKE '%stacji diagnostycznej%' OR meta_value LIKE '%stacja diagnostyczna%'");
    printf("Pozostało pól ze wzmianką o stacji diagnostycznej: %d\n", (int) $left);
}
