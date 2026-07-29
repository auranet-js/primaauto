<?php
/**
 * Aktualizacja treści hubu M-Hero 917 po przeniesieniu spod marki Mengshi pod Dongfeng.
 *
 * Trzy rzeczy do naprawy w treści z 2026-07-07 (v3-fala2):
 *  1. linki filtrów `?marka=mengshi` — marka skasowana, filtr nic nie zwraca;
 *  2. nazwa marki w prozie — hub nazywa się teraz Dongfeng M-Hero 917 (DFS: m-hero 1600/mc,
 *     mengshi 10/mc), ale JEDNO wystąpienie „Mengshi" zostawiamy świadomie jako alias —
 *     GSC notuje zapytanie „mengshi 917" (6 imp, poz. 3,0). Wzorzec z renamu Hyptec,
 *     gdzie alias „Aion Hyper" został dopisany do wiki.
 *  3. liczby — hub miał 2 auta i widełki 279–404 tys.; po zaciągu 28.07 ma 4 auta, 294–546 tys.
 *
 * Użycie: wp eval-file scripts/aktualizuj-hub-mhero-2026-07-29.php [apply]
 */
$APPLY = in_array('apply', $args ?? [], true);
$TID = 5782;

$wiki = (string) get_term_meta($TID, 'asiaauto_wiki_body', true);
$lead = (string) get_term_meta($TID, '_asiaauto_lead', true);
$faq  = (string) get_term_meta($TID, 'asiaauto_faq_json', true);

$before = ['wiki' => strlen($wiki), 'mengshi_w_wiki' => substr_count($wiki, 'Mengshi'), 'linki' => substr_count($wiki, 'marka=mengshi')];

/* 1. Linki filtrów. */
$wiki = str_replace('marka=mengshi', 'marka=dongfeng', $wiki);

/* 2. Nazwa marki w prozie. Najpierw wszystko na Dongfeng… */
$wiki = str_replace('Mengshi M-Hero 917', 'Dongfeng M-Hero 917', $wiki);
$wiki = str_replace('marki Mengshi', 'marki M-Hero', $wiki);
$wiki = str_replace('sub-brandu Dongfeng Mengshi', 'terenowej sub-marki Dongfenga o nazwie Mengshi (eksportowo M-Hero)', $wiki);

/* 3. Liczby — 2 egzemplarze / 279-404 tys. → 4 egzemplarze / 294-546 tys. */
$wiki = str_replace('dwa egzemplarze M-Hero 917 rocznik 2023', 'cztery egzemplarze M-Hero 917 z roczników 2023 i 2025', $wiki);
$wiki = str_replace('od 100 do 54 000 km', 'od 10 do 54 000 km', $wiki);
$wiki = str_replace('279 000 do 404 000 PLN', '294 000 do 546 000 PLN', $wiki);
$wiki = str_replace('279 000–404 000 PLN', '294 000–546 000 PLN', $wiki);
$wiki = str_replace('oba egzemplarze', 'egzemplarze', $wiki);

$lead_new = str_replace(
    ['(Mengshi, grupa Dongfeng)', 'od 288 000 zł'],
    ['(sub-marka Mengshi, grupa Dongfeng)', 'od 294 000 zł'],
    $lead
);

$faq_new = str_replace(['Mengshi M-Hero 917', 'marka=mengshi'], ['Dongfeng M-Hero 917', 'marka=dongfeng'], $faq);

echo "── PRZED: wiki {$before['wiki']} B, „Mengshi\" ×{$before['mengshi_w_wiki']}, linków marka=mengshi ×{$before['linki']}\n";
echo "── PO   : wiki " . strlen($wiki) . " B, „Mengshi\" ×" . substr_count($wiki, 'Mengshi')
   . ", linków marka=mengshi ×" . substr_count($wiki, 'marka=mengshi') . "\n\n";
echo "lead PRZED: $lead\n";
echo "lead PO   : $lead_new\n\n";

if (substr_count($wiki, 'Mengshi') < 1) {
    echo "!! UWAGA: zniknął alias „Mengshi\" — zapytanie „mengshi 917\" ma w GSC 6 imp / poz. 3,0.\n";
}

if ($APPLY) {
    update_term_meta($TID, 'asiaauto_wiki_body', $wiki);
    update_term_meta($TID, '_asiaauto_lead', $lead_new);
    update_term_meta($TID, 'asiaauto_faq_json', $faq_new);
    update_term_meta($TID, '_asiaauto_seo_rework', 'v3-fala2-2026-07-07+dongfeng-2026-07-29');
    echo "ZAPISANE.\n";
} else {
    echo "DRY-RUN — dodaj: apply\n";
}
