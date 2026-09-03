<?php
/**
 * Indeks Leksykonu (/wiki/) — hasła pogrupowane wg kategorii (_wiki_category).
 * T-214.
 */

defined('ABSPATH') || exit;

get_header();

$entries = get_posts([
    'post_type'      => 'asiaauto_wiki',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
]);

$labels = class_exists('AsiaAuto_Wiki') ? AsiaAuto_Wiki::CATEGORIES : [];

$groups = [];
foreach ($entries as $e) {
    $cat = (string) get_post_meta($e->ID, '_wiki_category', true);
    // Kategoria spoza listy = hasło do „Pozostałe", NIGDY do kosza. Pętla niżej iteruje po
    // etykietach, więc przed 03.09 hasła z `podwozie` i `nadwozie` (12 sztuk, w tym wszystkie
    // zawieszenia) nie pojawiały się na indeksie w ogóle — zgłoszenie Janka.
    $groups[isset($labels[$cat]) ? $cat : 'inne'][] = $e;
}
$labels['inne'] = 'Pozostałe';
?>
<main class="pa-main pa-kb">
    <div class="pa-container pa-kb__container">
        <nav class="pa-kb__breadcrumb" aria-label="okruszki">
            <a href="<?php echo esc_url(home_url('/')); ?>">Prima-Auto</a>
            <span aria-hidden="true">›</span>
            <span>Słownik</span>
        </nav>
        <div class="pa-kb__layout"><div class="pa-kb__main">

        <header class="pa-kb__header">
            <h1>Słownik chińskiej motoryzacji</h1>
            <div class="pa-kb__desc"><p>Technologie, systemy i pojęcia z chińskich samochodów — wyjaśnione na przykładach aut z naszej oferty.</p></div>
        </header>

        <?php if (!$entries): ?>
            <p class="pa-kb__empty">Hasła leksykonu pojawią się tu wkrótce.</p>
        <?php endif; ?>

        <?php foreach ($labels as $key => $label):
            if (empty($groups[$key])) continue; ?>
            <section class="pa-wiki-group">
                <h2><?php echo esc_html($label); ?></h2>
                <ul class="pa-wiki-group__list">
                    <?php foreach ($groups[$key] as $e):
                        $hw = get_post_meta($e->ID, '_wiki_headword', true) ?: get_the_title($e); ?>
                        <li><a href="<?php echo esc_url(get_permalink($e)); ?>"><?php echo esc_html($hw); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </div>
<?php include get_template_directory() . "/kb-sidebar.php"; ?>
</div>
    </div>
</main>
<?php
get_footer();
