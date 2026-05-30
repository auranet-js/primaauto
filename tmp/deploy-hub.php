<?php
// wp eval-file deploy-hub.php <config.json> — generyczny deploy reworku hubu
$cfg = json_decode(file_get_contents($args[0]), true);
if (!$cfg || empty($cfg['term_id'])) { WP_CLI::error('zły config'); }
$tid = (int)$cfg['term_id'];
$wiki = file_get_contents($cfg['wiki_file']);
$faq  = json_decode(file_get_contents($cfg['faq_file']), true);
if (!is_array($faq) || count($faq) < 3) WP_CLI::error('FAQ invalid');
if (strpos($wiki, '{{LISTINGS_BAR}}') === false) WP_CLI::error('wiki bez tokenu');
foreach ($faq as $q) if (preg_match('/[\x{201E}\x{201D}\x{201C}]/u', $q['q'].$q['a'])) WP_CLI::error('smart quotes w FAQ!');
$faq_json = wp_json_encode($faq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
update_term_meta($tid, 'asiaauto_wiki_body', $wiki);
update_term_meta($tid, 'asiaauto_faq_json', $faq_json);
update_term_meta($tid, '_asiaauto_lead', $cfg['lead']);
update_term_meta($tid, '_asiaauto_pl_availability', $cfg['pl_availability']);
update_term_meta($tid, '_asiaauto_seo_rework', $cfg['rework_version']);
if (!empty($cfg['h1_suffix'])) update_term_meta($tid, '_asiaauto_h1_suffix', $cfg['h1_suffix']); // Tier B: pomijamy
WP_CLI::success("term $tid: wiki ".strlen($wiki)."B, faq ".count($faq)."Q, lead ".mb_strlen($cfg['lead'])."zn, h1=".($cfg['h1_suffix']??'(brak/TierB)'));
