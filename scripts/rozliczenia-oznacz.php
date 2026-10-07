<?php
/**
 * rozliczenia-oznacz.php — T-261: flaga [A] „rozliczone z Auranetem” na zamówieniach.
 *
 * Ustawia dwie meta na `asiaauto_order`:
 *   _order_auranet_settled_at  (Y-m-d)
 *   _order_auranet_invoice     (numer FV Auranet)
 * Pin A na liście zamówień czyta je wprost (class-asiaauto-order-admin.php, renderAuranetPin).
 * Niczego nie wyzwala — statusy, maile, umowa, rezerwacje bez zmian.
 *
 * Użycie (z ~/domains/primaauto.com.pl/public_html):
 *   wp eval-file ~/projekty/primaauto/scripts/rozliczenia-oznacz.php ids=387071,397416 data=2026-09-02 fv=FS/1/09/2026
 *   ... apply=1     # zapis; bez tego DRY-RUN z tabelą
 *
 * Odmawia (pozycja pominięta, reszta idzie): nie-`asiaauto_order`, konto testowe z rejestru
 * (docs/rozliczenia/ruslan.md), zamówienie testowe 410903, flaga z innym numerem FV.
 * Ta sama data i FV = bez zmian (idempotentnie).
 */

const RO_TEST_USERS  = [10, 12, 13, 157];
const RO_TEST_ORDERS = [410894, 410903]; // 410903 = Seal U Jan Schenk (rejestr podaje 410894 — takiego posta nie ma)

$opt = [];
foreach ($args as $a) {
    if (strpos($a, '=') !== false) {
        [$k, $v] = explode('=', $a, 2);
        $opt[$k] = trim($v);
    }
}

$ids   = array_values(array_unique(array_filter(array_map('intval', explode(',', $opt['ids'] ?? '')))));
$data  = $opt['data'] ?? '';
$fv    = $opt['fv'] ?? '';
$apply = ($opt['apply'] ?? '') === '1';

$d = DateTime::createFromFormat('!Y-m-d', $data);
if (!$ids || !$d || $d->format('Y-m-d') !== $data || $fv === '') {
    WP_CLI::error('Wymagane: ids=ID[,ID…] data=RRRR-MM-DD fv=NUMER [apply=1]');
}

printf("=== T-261 oznaczenie rozliczenia — %s ===\ndata %s · FV %s · pozycji %d\n\n",
    $apply ? 'APPLY' : 'DRY-RUN', $data, $fv, count($ids));
printf("%-7s | %-40s | %-24s | %-6s | %-10s | %-26s | %s\n",
    'ID', 'Auto', 'Klient', 'Dep.', 'Dep. data', 'Flaga teraz', 'Akcja');
echo str_repeat('-', 140), "\n";

$cut = static function (string $s, int $n): string {
    return mb_strlen($s) > $n ? mb_substr($s, 0, $n - 1) . '…' : $s;
};

$n_set = $n_same = $n_skip = 0;
foreach ($ids as $id) {
    $post = get_post($id);
    if (!$post || $post->post_type !== 'asiaauto_order') {
        printf("%-7d | %s\n", $id, 'ODMOWA: to nie jest zamówienie asiaauto_order');
        $n_skip++;
        continue;
    }

    $cust_id  = (int) get_post_meta($id, '_order_customer_id', true);
    $user     = $cust_id ? get_userdata($cust_id) : null;
    $customer = $user ? $user->display_name : '—';
    if (($t = get_post_meta($id, '_order_type', true)) === 'stock') $customer = 'stock';
    $dep      = get_post_meta($id, '_order_deposit_paid', true) === '1' ? 'tak' : 'nie';
    $dep_at   = substr((string) get_post_meta($id, '_order_deposit_paid_at', true), 0, 10) ?: '—';
    $cur_at   = (string) get_post_meta($id, '_order_auranet_settled_at', true);
    $cur_fv   = (string) get_post_meta($id, '_order_auranet_invoice', true);
    $cur      = $cur_at === '' ? '—' : "$cur_at $cur_fv";

    if (in_array($id, RO_TEST_ORDERS, true) || in_array($cust_id, RO_TEST_USERS, true)) {
        $action = "ODMOWA: konto/zamówienie testowe (user $cust_id)";
        $n_skip++;
    } elseif ($cur_at !== '' && $cur_fv !== $fv) {
        $action = "ODMOWA: już rozliczone innym FV ($cur_fv)";
        $n_skip++;
    } elseif ($cur_at === $data && $cur_fv === $fv) {
        $action = 'bez zmian';
        $n_same++;
    } else {
        $action = $apply ? 'ZAPISANO' : 'do zapisu';
        if ($apply) {
            update_post_meta($id, '_order_auranet_settled_at', $data);
            update_post_meta($id, '_order_auranet_invoice', $fv);
        }
        $n_set++;
    }

    printf("%-7d | %-40s | %-24s | %-6s | %-10s | %-26s | %s\n",
        $id, $cut(html_entity_decode($post->post_title), 40), $cut($customer, 24), $dep, $dep_at, $cur, $action);
}

printf("\n%s: %d · bez zmian: %d · odmowa: %d%s\n",
    $apply ? 'zapisano' : 'do zapisu', $n_set, $n_same, $n_skip,
    $apply ? '' : "\nDRY-RUN — nic nie zapisano. Zapis: dopisz apply=1");
