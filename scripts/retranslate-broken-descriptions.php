<?php
/**
 * AsiaAuto — retranslacja opisów uszkodzonych przez limit tokenów Gemini
 *
 * Kontekst: do v0.34.23 translateViaGemini() wołał gemini-2.5-flash z maxOutputTokens=2048
 * i domyślnie włączonym thinkingiem. Myślenie (~1800 tok.) zjadało budżet wyjścia, przez co
 * opis wracał urwany w połowie zdania albo pusty (finishReason MAX_TOKENS). Przy pustym
 * plugin szedł na fallback DeepL, a ten był wyłączony — zapisywał się surowy chiński.
 * v0.34.24 naprawia pipeline na przyszłość; ten skrypt nadrabia zaległości.
 *
 * Kryteria uszkodzenia (dowolne spełnione):
 *   1. opis zawiera >= 3 znaki CJK        -> tłumaczenie w ogóle nie zaszło
 *   2. PL/CN < 1.8                        -> ucięte (zdrowe tłumaczenie ma 2.9-5.5,
 *                                            mediana 4.41 na próbie 1209 opisów
 *                                            kończących się kropką)
 *   3. kończy się na , : ; -              -> ucięte w połowie wyliczenia
 *
 * Bezpieczeństwo:
 *   - stary post_content ląduje w _asiaauto_description_broken_backup (raz, nie nadpisuje)
 *   - nowe tłumaczenie przechodzi te same kryteria; jeśli nie przejdzie, post zostaje
 *     nietknięty a status leci na 'failed'
 *   - wp_update_post na publish->publish nie budzi Indexing API (resolveNotificationType
 *     zwraca null poza przejściem draft->publish), a handleSave edytora wymaga nonce
 *
 * Użycie:
 *   wp eval-file diag/retranslate-broken-descriptions.php                    (dry run)
 *   APPLY=1 wp eval-file diag/retranslate-broken-descriptions.php
 *   APPLY=1 LIMIT=50 SLEEP=400000 wp eval-file diag/retranslate-broken-descriptions.php
 *
 * @since 0.34.24
 * @package AsiaAuto
 */

global $wpdb;

$dry_run = empty(getenv('APPLY'));
$limit   = (int) (getenv('LIMIT') ?: 0);
$sleep   = (int) (getenv('SLEEP') ?: 400000);
$logfile = WP_CONTENT_DIR . '/uploads/asiaauto/retranslate-' . date('Y-m-d-Hi') . '.log';

const RATIO_MIN = 1.8;
const CJK_MIN   = 3;

/** Czy opis nosi ślady uszkodzenia przez limit tokenów. */
function aa_desc_is_broken(string $content, string $original): ?string {
    if (preg_match_all('/[\x{4e00}-\x{9fff}]/u', $content) >= CJK_MIN) {
        return 'chinski';
    }
    $plain = trim(html_entity_decode(strip_tags($content)));
    $len_o = mb_strlen($original);
    if ($len_o > 0 && (mb_strlen($plain) / $len_o) < RATIO_MIN) {
        return 'za_krotkie';
    }
    // Końcówka na przecinku/średniku znaczy ucięcie tylko wtedy, gdy oryginał tak się
    // NIE kończy. Chińskie opisy nagminnie kończą się na ；lub ，i wierne tłumaczenie
    // dziedziczy ten znak — bez tego wyjątku odrzucalibyśmy poprawne tłumaczenia
    // (np. #430197, oryginał kończy się na 完整；).
    $tail_pattern = '/[,:;\x{2013}\x{2014}\x{FF0C}\x{FF1B}\x{FF1A}\x{3001}-]$/u';
    $orig_plain   = trim(html_entity_decode(strip_tags($original)));
    if (preg_match($tail_pattern, $plain) && !preg_match($tail_pattern, $orig_plain)) {
        return 'urwane';
    }
    return null;
}

$rows = $wpdb->get_results(
    "SELECT p.ID, p.post_status, p.post_content AS c, o.meta_value AS o
     FROM {$wpdb->posts} p
     JOIN {$wpdb->postmeta} o ON o.post_id = p.ID AND o.meta_key = '_asiaauto_description_original'
     WHERE p.post_type = 'listings'
       AND p.post_status IN ('publish','draft')
       AND p.post_content <> ''
       AND o.meta_value <> ''
     ORDER BY p.post_date DESC"
);

$todo = [];
$stats = ['chinski' => 0, 'za_krotkie' => 0, 'urwane' => 0];
foreach ($rows as $r) {
    $why = aa_desc_is_broken($r->c, $r->o);
    if ($why === null) {
        continue;
    }
    $stats[$why]++;
    $todo[] = ['id' => (int) $r->ID, 'why' => $why, 'orig' => $r->o, 'status' => $r->post_status];
}

$total_found = count($todo);
if ($limit > 0) {
    $todo = array_slice($todo, 0, $limit);
}

WP_CLI::log("Przeskanowano listingów: " . count($rows));
WP_CLI::log("Uszkodzonych: {$total_found}  (chiński: {$stats['chinski']}, za krótkie: {$stats['za_krotkie']}, urwane: {$stats['urwane']})");
WP_CLI::log("Do przetworzenia w tym biegu: " . count($todo));

if ($dry_run) {
    WP_CLI::log("\n(dry run — APPLY=1 żeby wykonać)");
    foreach (array_slice($todo, 0, 10) as $t) {
        WP_CLI::log("  #{$t['id']} [{$t['status']}] {$t['why']}");
    }
    exit;
}

$translator = new AsiaAuto_Translator();
$ok = $skip = $err = 0;
$n  = count($todo);
$fh = fopen($logfile, 'a');

foreach ($todo as $i => $t) {
    $res = $translator->translateDescription($t['orig']);
    $new = (string) ($res['translated'] ?? '');

    if ($new === '') {
        $err++;
        update_post_meta($t['id'], '_asiaauto_description_status', 'failed');
        $line = sprintf("#%d BLAD %s", $t['id'], $res['error'] ?? 'brak tekstu');
    } elseif (($still = aa_desc_is_broken($new, $t['orig'])) !== null) {
        // nowe tłumaczenie nadal uszkodzone — nie podmieniamy, zostawiamy stare
        $skip++;
        update_post_meta($t['id'], '_asiaauto_description_status', 'failed');
        $line = sprintf("#%d ODRZUCONE (nadal %s)", $t['id'], $still);
    } else {
        if (get_post_meta($t['id'], '_asiaauto_description_broken_backup', true) === '') {
            $old = get_post_field('post_content', $t['id']);
            update_post_meta($t['id'], '_asiaauto_description_broken_backup', $old);
        }
        wp_update_post(['ID' => $t['id'], 'post_content' => $new]);
        update_post_meta($t['id'], '_asiaauto_description_status', 'ok');
        $ok++;
        $line = sprintf("#%d OK %s -> %d zn.", $t['id'], $t['why'], mb_strlen($new));
    }

    fwrite($fh, date('H:i:s') . " {$line}\n");
    if ((($i + 1) % 25) === 0 || $i + 1 === $n) {
        WP_CLI::log(sprintf("  [%d/%d] ok=%d odrzucone=%d bledy=%d", $i + 1, $n, $ok, $skip, $err));
    }
    if ($i < $n - 1) {
        usleep($sleep);
    }
}

fclose($fh);
WP_CLI::log("\n═══════════════════════════════════════");
WP_CLI::log("Przetłumaczono:  {$ok}");
WP_CLI::log("Odrzucone:       {$skip}");
WP_CLI::log("Błędy:           {$err}");
WP_CLI::log("Log:             {$logfile}");
WP_CLI::log("═══════════════════════════════════════");
