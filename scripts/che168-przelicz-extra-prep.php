<?php
// Przelicza extra_prep w draftach che168 z nową mapą parametrów. Dotyka WYŁĄCZNIE
// _asiaauto_extra_prep w draftach che168 z dzisiejszego biegu — nic innego.
$APPLY = in_array('apply', $args ?? [], true);
global $wpdb;
$api = new AsiaAuto_API(ASIAAUTO_API_KEY, ASIAAUTO_API_BASE_URL);
$ids = $wpdb->get_col("SELECT p.ID FROM {$wpdb->posts} p
  JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key='_asiaauto_source' AND pm.meta_value='che168'
  WHERE p.post_type='listings' AND p.post_status='draft' AND p.ID>=389290");
$sum_old = $sum_new = $unk_old = $unk_new = 0; $done = 0;
foreach ($ids as $pid) {
    $inner = get_post_meta($pid, '_asiaauto_inner_id', true);
    $r = $api->getOffer('che168', (string) $inner);
    if (!$r) { echo "#{$pid}: brak danych z API\n"; continue; }
    $d = $r['data'] ?? ($r['result'][0]['data'] ?? $r);
    $n  = AsiaAuto_Che168_Adapter::normalize($d);
    $ne = $n['extra_prep'] ?? [];
    if (!$ne) continue;
    $oe = get_post_meta($pid, '_asiaauto_extra_prep', true);
    $oe = is_string($oe) ? json_decode($oe, true) : $oe; $oe = is_array($oe) ? $oe : [];
    $sum_old += count($oe); $sum_new += count($ne);
    $unk_old += count(array_filter(array_keys($oe), fn($k) => str_starts_with($k, 'param_')));
    $unk_new += count(array_filter(array_keys($ne), fn($k) => str_starts_with($k, 'param_')));
    if ($APPLY) update_post_meta($pid, '_asiaauto_extra_prep', wp_slash(json_encode($ne, JSON_UNESCAPED_UNICODE)));
    $done++;
    usleep(150000);
}
printf("%s ofert: %d | pól %d → %d | nieznanych param_ %d → %d\n",
    $APPLY ? '[APPLY]' : '[DRY-RUN]', $done, $sum_old, $sum_new, $unk_old, $unk_new);
