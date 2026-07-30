<?php
/**
 * odswiez-tytuly-ofert.php — przelicza tytuł i slug ofert z PRZYPISANYCH TERMÓW.
 *
 * Wersja parametryczna `odswiez-tytuly-zaciagu-2026-07-29.php` (tamten miał listę ID zaszytą).
 * Potrzebna po każdym scaleniu sierot: tytuł oferty powstaje TYLKO przy wstawianiu posta
 * (`AsiaAuto_Importer::importListing`) z `getEuForCn()`, więc oferta, która weszła jako sierota,
 * zostaje z surową nazwą źródła („Voyah Zhuiguang L", „Chery Fengyun Fengyun T10") nawet po
 * przepięciu termów i dopisaniu mapowania.
 *
 * Liczy z TERMÓW, nie z mapowania — oferta bywa ręcznie przypięta do wariantu, którego
 * mapowanie nie odróżnia (Foton Tunland V7 vs V9 dzieli jeden klucz che168).
 *
 * Stary slug obsługuje natywny `_wp_old_slug` → 301, więc dotychczasowe URL-e żyją.
 *
 * Użycie: wp eval-file scripts/odswiez-tytuly-ofert.php <id,id,...> [apply]
 */

$args  = $args ?? [];
$APPLY = in_array('apply', $args, true);
$lista = '';
foreach ($args as $a) {
    if ($a !== 'apply') { $lista = (string) $a; break; }
}
$ids = array_values(array_filter(array_map('intval', preg_split('/[,\s]+/', $lista))));

if (!$ids) {
    echo "Podaj listę ID: wp eval-file scripts/odswiez-tytuly-ofert.php \"394247,388819\" [apply]\n";
    return;
}

echo $APPLY ? "=== APPLY ===\n\n" : "=== DRY-RUN (dodaj: apply) ===\n\n";

foreach ($ids as $id) {
    $p = get_post($id);
    if (!$p) { echo "#$id — brak posta\n"; continue; }

    $mk = wp_get_post_terms($id, 'make');
    $se = wp_get_post_terms($id, 'serie');
    if (!isset($mk[0], $se[0])) { echo "#$id — brak termów, pomijam\n"; continue; }

    $mark  = html_entity_decode($mk[0]->name, ENT_QUOTES, 'UTF-8');
    $serie = html_entity_decode($se[0]->name, ENT_QUOTES, 'UTF-8');
    $compl = (string) get_post_meta($id, '_asiaauto_complectation', true);

    // Rok z OBECNEGO tytułu, nie z `ca-year`: importer użył rocznika modelowego („2025款"),
    // a `ca-year` niesie rok rejestracji i potrafi się różnić o jeden. Kupujący szuka
    // rocznika modelowego. Patrz memory: reference_dongchedi_year_fields.
    $year = preg_match('/\b(19|20)\d{2}\b/', $p->post_title, $m)
        ? $m[0]
        : (string) get_post_meta($id, 'ca-year', true);

    // Serie bywa z marką w nazwie (fallback importera) — nie dublujemy.
    $serie_part = (stripos($serie, $mark) === 0) ? trim(substr($serie, strlen($mark))) : $serie;

    $title = trim(preg_replace('/\s+/u', ' ', "$mark $serie_part $year $compl"));
    $slug  = sanitize_title("$mark $serie_part $year") . '-' . $id;

    $t_zmiana = $title !== $p->post_title;
    $s_zmiana = $slug !== $p->post_name;
    if (!$t_zmiana && !$s_zmiana) { echo "#$id  bez zmian: {$p->post_title}\n"; continue; }

    echo "#$id\n";
    if ($t_zmiana) { echo "   tytuł: „{$p->post_title}\"\n       → „$title\"  (" . mb_strlen($title) . " zn.)\n"; }
    if ($s_zmiana) { echo "   slug : {$p->post_name}\n       → $slug\n"; }

    if ($APPLY) {
        wp_update_post(['ID' => $id, 'post_title' => $title, 'post_name' => $slug]);
    }
}

echo "\n" . ($APPLY ? "GOTOWE.\n" : "Nic nie zmieniono.\n");
