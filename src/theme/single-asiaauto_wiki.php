<?php
/**
 * Hasło Leksykonu (/wiki/{haslo}/).
 * T-214. Sekcje dynamiczne (auta z technologią) dojdą w F2 wraz z auto-linkerem.
 */

defined('ABSPATH') || exit;

get_header();
?>
<main class="pa-main pa-kb">
    <div class="pa-container pa-kb__container">
        <?php while (have_posts()): the_post();
            $wiki_cat = (string) get_post_meta(get_the_ID(), '_wiki_category', true);
            $labels   = class_exists('AsiaAuto_Wiki') ? AsiaAuto_Wiki::CATEGORIES : [];
            $cat_label = $labels[$wiki_cat] ?? '';
        ?>
        <?php
        // Breadcrumb: HTML + JSON-LD z jednego źródła, żeby nie rozjechały się jak na hubach.
        $pa_kb_crumbs = [
            ['name' => 'Prima-Auto', 'url' => home_url('/')],
            ['name' => 'Słownik',    'url' => get_post_type_archive_link('asiaauto_wiki')],
            ['name' => get_the_title(), 'url' => get_permalink()],
        ];
        ?>
        <nav class="pa-kb__breadcrumb" aria-label="okruszki">
            <?php foreach ($pa_kb_crumbs as $pa_i => $pa_c): ?>
                <?php if ($pa_i > 0): ?><span aria-hidden="true">›</span><?php endif; ?>
                <?php if ($pa_i === count($pa_kb_crumbs) - 1): ?>
                    <span class="pa-kb__breadcrumb-current" aria-current="page"><?php echo esc_html($pa_c['name']); ?></span>
                <?php else: ?>
                    <a href="<?php echo esc_url($pa_c['url']); ?>"><?php echo esc_html($pa_c['name']); ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php
        $pa_kb_items = [];
        foreach ($pa_kb_crumbs as $pa_i => $pa_c) {
            $pa_kb_items[] = [
                '@type'    => 'ListItem',
                'position' => $pa_i + 1,
                'name'     => $pa_c['name'],
                'item'     => $pa_c['url'],
            ];
        }
        echo '<script type="application/ld+json">' . wp_json_encode([
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $pa_kb_items,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        ?>
        <div class="pa-kb__layout"><div class="pa-kb__main">

        <article <?php post_class('pa-article pa-article--wiki'); ?>>
            <header class="pa-article__header">
                <?php if ($cat_label): ?><span class="pa-article__eyebrow"><?php echo esc_html($cat_label); ?></span><?php endif; ?>
                <h1 class="pa-article__title"><?php the_title(); ?></h1>
            </header>

            <?php // Okładka brandowa celowo NIE renderowana (dubluje tytuł) — służy jako og:image i kafle. ?>

            <div class="pa-article__content">
                <?php the_content(); ?>
            </div>
        </article>

        <?php
        // T-214: linkowanie dwukierunkowe — „auta z tą technologią" liczone na żywo
        // z indeksu (AsiaAuto_Wiki_Cars). Zero dopasowań = sekcja się nie renderuje.
        echo do_shortcode('[asiaauto_wiki_cars cards="3"]');
        ?>

        <?php
        $related = $wiki_cat ? get_posts([
            'post_type'      => 'asiaauto_wiki',
            'posts_per_page' => 8,
            'post__not_in'   => [get_the_ID()],
            'meta_key'       => '_wiki_category',
            'meta_value'     => $wiki_cat,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]) : [];
        if ($related): ?>
            <aside class="pa-kb__related">
                <h2>Powiązane hasła</h2>
                <ul class="pa-wiki-group__list">
                    <?php foreach ($related as $rp): ?>
                        <li><a href="<?php echo esc_url(get_permalink($rp)); ?>"><?php echo esc_html(get_the_title($rp)); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </aside>
        <?php endif; ?>

        <?php endwhile; ?>
    </div>
<?php include get_template_directory() . "/kb-sidebar.php"; ?>
</div>
    </div>
</main>
<?php
get_footer();
