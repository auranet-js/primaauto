<?php
/**
 * Odświeżenie tytułów i slugów 11 ofert z ręcznego zaciągu 28.07, po naprawie taksonomii.
 *
 * Tytuł oferty powstaje TYLKO przy wstawianiu posta (`AsiaAuto_Importer::importListing`),
 * z `getEuForCn()`. Te oferty weszły jako sieroty, więc dostały surowe nazwy che168
 * („MAXUS", „Foton Mars", „M-Hero 917" bez marki). Po naprawie termów i dopisaniu mapowań
 * trzeba je przeliczyć — ale z PRZYPISANYCH TERMÓW, nie z mapowania: 392329 jest ręcznie
 * przypięty do Tunland V7, a mapowanie che168 zwraca dla niego V9 (źródło nie odróżnia wersji).
 *
 * Stary slug obsługuje natywny mechanizm WP (`_wp_old_slug` → 301), więc URL-e z wczoraj żyją.
 *
 * Użycie: wp eval-file scripts/odswiez-tytuly-zaciagu-2026-07-29.php [apply]
 */

$APPLY = in_array('apply', $args ?? [], true);
$ids = [392216,392232,392298,392313,392329,392343,392359,392375,392391,392407,392440];

echo $APPLY ? "=== APPLY ===\n\n" : "=== DRY-RUN (dodaj: apply) ===\n\n";

foreach ($ids as $id) {
    $p = get_post($id);
    if (!$p) { continue; }
    $mk = wp_get_post_terms($id, 'make');
    $se = wp_get_post_terms($id, 'serie');
    if (!isset($mk[0], $se[0])) { echo "#$id — brak termów, pomijam\n"; continue; }

    $mark  = html_entity_decode($mk[0]->name, ENT_QUOTES, 'UTF-8');
    $serie = html_entity_decode($se[0]->name, ENT_QUOTES, 'UTF-8');
    $compl = (string) get_post_meta($id, '_asiaauto_complectation', true);

    // Rok BIERZEMY Z OBECNEGO TYTUŁU, nie z meta `ca-year`. Importer użył `$data['year']`
    // (rok modelowy — „2025款"), a `ca-year` niesie rok rejestracji/produkcji i potrafi się
    // różnić o jeden (Zeekr 9X: tytuł 2025, ca-year 2026). Kupujący szuka rocznika modelowego,
    // więc przeliczanie tytułu nie może go po cichu podmienić.
    // Patrz memory: reference_dongchedi_year_fields (rok modelowy ≠ produkcji).
    $year = preg_match('/\b(19|20)\d{2}\b/', $p->post_title, $m)
        ? $m[0]
        : (string) get_post_meta($id, 'ca-year', true);

    // Serie bywa już z marką w nazwie (fallback importera) — nie dublujemy.
    $serie_part = (stripos($serie, $mark) === 0) ? trim(substr($serie, strlen($mark))) : $serie;

    $title = trim(preg_replace('/\s+/u', ' ', "$mark $serie_part $year $compl"));
    $slug  = sanitize_title("$mark $serie_part $year") . '-' . $id;

    $t_zmiana = $title !== $p->post_title;
    $s_zmiana = $slug !== $p->post_name;
    if (!$t_zmiana && !$s_zmiana) { echo "#$id  bez zmian: {$p->post_title}\n"; continue; }

    echo "#$id\n";
    if ($t_zmiana) { echo "   tytuł: „{$p->post_title}\"\n       → „$title\"\n"; }
    if ($s_zmiana) { echo "   slug : {$p->post_name}\n       → $slug\n"; }

    if ($APPLY) {
        wp_update_post(['ID' => $id, 'post_title' => $title, 'post_name' => $slug]);
    }
}
echo "\n" . ($APPLY ? "GOTOWE.\n" : "Nic nie zmieniono.\n");
