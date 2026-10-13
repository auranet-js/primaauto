<?php
/**
 * Render makiety HTML → PDF tym samym mPDF, którego używa plugin (umowa). Tylko odczyt vendora pluginu.
 * Fonty Barlow / Barlow Condensed (OFL, static TTF) z tmp/makiety-zrodla/fonts — mPDF nie czyta woff2 z motywu.
 * Użycie: php render-mpdf.php <wejście.html> <wyjście.pdf> <tempDir>
 */
[$_, $in, $out, $tmp] = $argv + [null, null, null, sys_get_temp_dir()];
require '/home/host476470/domains/primaauto.com.pl/public_html/wp-content/plugins/asiaauto-sync/vendor/autoload.php';

$cfg   = (new \Mpdf\Config\ConfigVariables())->getDefaults();
$fonts = (new \Mpdf\Config\FontVariables())->getDefaults();

$mpdf = new \Mpdf\Mpdf([
    'mode'          => 'utf-8',
    'format'        => 'A4',
    'margin_left'   => 14,
    'margin_right'  => 14,
    'margin_top'    => 22,
    'margin_bottom' => 24,
    'margin_header' => 8,
    'margin_footer' => 7,
    'tempDir'       => $tmp,
    'shrink_tables_to_fit' => 1,  // bez tego mPDF zmniejsza zagnieżdżone tabele (kolumny danych) do nieczytelnych
    'fontDir'       => array_merge($cfg['fontDir'], [dirname(__DIR__, 2) . '/tmp/makiety-zrodla/fonts']),
    'fontdata'      => $fonts['fontdata'] + [
        'barlow'     => ['R' => 'Barlow-Regular.ttf', 'B' => 'Barlow-SemiBold.ttf'],
        'barlowmed'  => ['R' => 'Barlow-Medium.ttf', 'B' => 'Barlow-Bold.ttf'],
        'barlowcond' => ['R' => 'BarlowCondensed-SemiBold.ttf', 'B' => 'BarlowCondensed-Bold.ttf'],
    ],
    'default_font'  => 'barlow',
]);
$mpdf->SetTitle(basename($out, '.pdf'));
$mpdf->WriteHTML(file_get_contents($in));
$mpdf->Output($out, \Mpdf\Output\Destination::FILE);
echo $out, ' — stron: ', count($mpdf->pages), PHP_EOL;
