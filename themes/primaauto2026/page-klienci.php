<?php
/**
 * Template Name: Klienci — galeria social proof
 *
 * Page template dla /klienci/ — galeria 47 zdjęć klientów (mask: klienci-prima-auto-NNN.webp).
 * Kwadratowe miniatury (aspect-ratio 1/1, object-fit: cover), lightbox vanilla JS.
 * ImageGallery JSON-LD dla SEO. Lazyload native.
 *
 * Zdjęcia ze zgodami (Ruslan potwierdził 2026-05-27, batch 30+ szt., później rozszerzony do 47).
 * Polityka opt-out — patrz memory `project-client-gallery-consents`.
 */

defined('ABSPATH') || exit;

$gallery_attachments = get_posts([
    'post_type'      => 'attachment',
    'post_status'    => 'inherit',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'post_mime_type' => 'image/webp',
    's'              => 'klienci-prima-auto',
    'fields'         => 'ids',
]);

$gallery_attachments = array_values(array_filter($gallery_attachments, function ($id) {
    return strpos(get_post_field('post_name', $id), 'klienci-prima-auto-') === 0;
}));

$gallery_items = [];
foreach ($gallery_attachments as $att_id) {
    $thumb_src = wp_get_attachment_image_src($att_id, 'medium_large');
    $full_src  = wp_get_attachment_image_src($att_id, 'full');
    if (!$thumb_src || !$full_src) continue;

    $gallery_items[] = [
        'id'        => $att_id,
        'thumb_url' => $thumb_src[0],
        'full_url'  => $full_src[0],
        'full_w'    => $full_src[1],
        'full_h'    => $full_src[2],
        'srcset'    => wp_get_attachment_image_srcset($att_id, 'medium_large'),
        'sizes'     => '(max-width: 600px) 50vw, (max-width: 1024px) 33vw, 400px',
        'alt'       => 'Klient Prima-Auto z zakupionym samochodem z Chin',
    ];
}

$total_clients = count($gallery_items);

get_header();
?>
<main class="site-main aa-klienci">
    <div class="aa-container">

        <header class="aa-klienci__hero">
            <?php while (have_posts()) : the_post(); ?>
                <h1><?php echo trim(get_the_title()) !== '' ? esc_html(get_the_title()) : 'Klienci Prima-Auto — auta, które sprowadziliśmy'; ?></h1>
            <?php endwhile; rewind_posts(); ?>
            <p class="aa-klienci__subhero">
                Realne zdjęcia naszych klientów z samochodami sprowadzonymi z Chin —
                dowód, że <strong>import z Prima-Auto działa</strong>: kompletny proces,
                terminowe dostawy i auta odbierane bezpośrednio przez właścicieli.
            </p>
        </header>

        <?php while (have_posts()) : the_post(); ?>
            <?php if (trim(get_the_content()) !== '') : ?>
                <section class="aa-klienci__intro"><?php the_content(); ?></section>
            <?php endif; ?>
        <?php endwhile; ?>

        <?php if ($gallery_items) : ?>
        <section class="aa-klienci__gallery" aria-label="Galeria zdjęć klientów Prima-Auto">
            <div class="aa-klienci-grid" id="aa-klienci-grid">
                <?php foreach ($gallery_items as $i => $item) : ?>
                    <button
                        type="button"
                        class="aa-klienci-tile"
                        data-full="<?php echo esc_url($item['full_url']); ?>"
                        data-index="<?php echo (int) $i; ?>"
                        aria-label="Powiększ zdjęcie klienta #<?php echo (int) $i + 1; ?>">
                        <img
                            src="<?php echo esc_url($item['thumb_url']); ?>"
                            <?php if ($item['srcset']) : ?>srcset="<?php echo esc_attr($item['srcset']); ?>"<?php endif; ?>
                            sizes="<?php echo esc_attr($item['sizes']); ?>"
                            alt="<?php echo esc_attr($item['alt']); ?>"
                            loading="<?php echo $i < 6 ? 'eager' : 'lazy'; ?>"
                            decoding="async"
                            width="800"
                            height="800">
                    </button>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="aa-klienci-lightbox" id="aa-klienci-lightbox" aria-hidden="true" role="dialog" aria-label="Powiększone zdjęcie">
            <button type="button" class="aa-klienci-lightbox__close" aria-label="Zamknij">×</button>
            <button type="button" class="aa-klienci-lightbox__prev" aria-label="Poprzednie zdjęcie">‹</button>
            <button type="button" class="aa-klienci-lightbox__next" aria-label="Następne zdjęcie">›</button>
            <figure class="aa-klienci-lightbox__figure">
                <img src="" alt="" id="aa-klienci-lightbox-img">
                <figcaption class="aa-klienci-lightbox__caption" id="aa-klienci-lightbox-cap"></figcaption>
            </figure>
        </div>
        <?php else : ?>
        <p class="aa-klienci__empty">Galeria jest obecnie aktualizowana.</p>
        <?php endif; ?>

        <section class="aa-klienci__cta">
            <h2>Sprowadzimy auto także dla Ciebie</h2>
            <p>
                Sprawdź <a href="/samochody/">samochody dostępne od ręki</a> lub
                <a href="/zamow/">zamów konkretny model z Chin</a>. Pełna obsługa:
                dobór, kontrola jakości, transport, cło, homologacja.
            </p>
        </section>

    </div>
</main>

<?php if ($gallery_items) :
    $schema_images = [];
    foreach ($gallery_items as $item) {
        $schema_images[] = [
            '@type'       => 'ImageObject',
            'contentUrl'  => $item['full_url'],
            'thumbnailUrl'=> $item['thumb_url'],
            'width'       => (int) $item['full_w'],
            'height'      => (int) $item['full_h'],
        ];
    }
    $schema = [
        '@context' => 'https://schema.org',
        '@type'    => 'ImageGallery',
        'name'     => 'Klienci Prima-Auto — galeria zdjęć',
        'description' => 'Realne zdjęcia klientów Prima-Auto z samochodami sprowadzonymi z Chin.',
        'url'      => get_permalink(),
        'numberOfItems' => $total_clients,
        'image'    => $schema_images,
    ];
?>
<script type="application/ld+json"><?php echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
<?php endif; ?>

<style>
.aa-klienci { padding: 32px 0 64px; }
.aa-klienci__hero { text-align: center; margin-bottom: 32px; }
.aa-klienci__hero h1 { font-size: clamp(1.6rem, 4vw, 2.4rem); margin: 0 0 12px; line-height: 1.2; }
.aa-klienci__subhero { font-size: 1.05rem; color: #444; max-width: 760px; margin: 0 auto; line-height: 1.55; }
.aa-klienci__intro { max-width: 760px; margin: 0 auto 32px; color: #333; line-height: 1.6; }
.aa-klienci__empty { text-align: center; color: #777; padding: 48px 0; }

.aa-klienci-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin: 0 0 48px;
}
@media (max-width: 1024px) { .aa-klienci-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 640px)  { .aa-klienci-grid { grid-template-columns: repeat(2, 1fr); gap: 6px; } }

.aa-klienci-tile {
    position: relative;
    display: block;
    width: 100%;
    aspect-ratio: 1 / 1;
    padding: 0;
    border: 0;
    margin: 0;
    background: #f3f4f6;
    cursor: zoom-in;
    overflow: hidden;
    border-radius: 8px;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}
.aa-klienci-tile:hover, .aa-klienci-tile:focus-visible {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.12);
    outline: none;
}
.aa-klienci-tile img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: transform 0.4s ease;
}
.aa-klienci-tile:hover img { transform: scale(1.04); }

.aa-klienci__cta {
    text-align: center;
    padding: 32px 16px;
    background: #f7f8fa;
    border-radius: 12px;
    margin-top: 32px;
}
.aa-klienci__cta h2 { font-size: 1.4rem; margin: 0 0 8px; }
.aa-klienci__cta p { margin: 0; color: #333; }
.aa-klienci__cta a { color: #0d2e54; text-decoration: underline; }

/* Lightbox */
.aa-klienci-lightbox {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.92);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
}
.aa-klienci-lightbox.is-open { display: flex; }
.aa-klienci-lightbox__figure {
    margin: 0;
    max-width: min(94vw, 1400px);
    max-height: 90vh;
    text-align: center;
}
.aa-klienci-lightbox__figure img {
    display: block;
    max-width: 100%;
    max-height: 86vh;
    width: auto;
    height: auto;
    margin: 0 auto;
    border-radius: 4px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.5);
}
.aa-klienci-lightbox__caption {
    color: #ddd;
    font-size: 0.9rem;
    margin-top: 8px;
}
.aa-klienci-lightbox button {
    position: absolute;
    background: rgba(255,255,255,0.1);
    color: #fff;
    border: 0;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 28px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s;
}
.aa-klienci-lightbox button:hover { background: rgba(255,255,255,0.22); }
.aa-klienci-lightbox__close { top: 16px; right: 16px; }
.aa-klienci-lightbox__prev { left: 16px; top: 50%; transform: translateY(-50%); }
.aa-klienci-lightbox__next { right: 16px; top: 50%; transform: translateY(-50%); }
@media (max-width: 600px) {
    .aa-klienci-lightbox__close { top: 8px; right: 8px; width: 40px; height: 40px; font-size: 24px; }
    .aa-klienci-lightbox__prev { left: 6px; }
    .aa-klienci-lightbox__next { right: 6px; }
    .aa-klienci-lightbox__prev,
    .aa-klienci-lightbox__next { width: 40px; height: 40px; font-size: 24px; }
}
</style>

<?php if ($gallery_items) : ?>
<script>
(function(){
    var grid = document.getElementById('aa-klienci-grid');
    var lb   = document.getElementById('aa-klienci-lightbox');
    if (!grid || !lb) return;

    var img   = document.getElementById('aa-klienci-lightbox-img');
    var cap   = document.getElementById('aa-klienci-lightbox-cap');
    var btnX  = lb.querySelector('.aa-klienci-lightbox__close');
    var btnP  = lb.querySelector('.aa-klienci-lightbox__prev');
    var btnN  = lb.querySelector('.aa-klienci-lightbox__next');
    var tiles = Array.prototype.slice.call(grid.querySelectorAll('.aa-klienci-tile'));
    var current = 0;
    var lastFocus = null;

    function open(idx) {
        current = idx;
        lastFocus = document.activeElement;
        var tile = tiles[idx];
        img.src = tile.getAttribute('data-full');
        img.alt = tile.querySelector('img').alt || '';
        cap.textContent = (idx + 1) + ' / ' + tiles.length;
        lb.classList.add('is-open');
        lb.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        btnX.focus();
    }
    function close() {
        lb.classList.remove('is-open');
        lb.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        img.src = '';
        if (lastFocus) lastFocus.focus();
    }
    function next() { open((current + 1) % tiles.length); }
    function prev() { open((current - 1 + tiles.length) % tiles.length); }

    grid.addEventListener('click', function(e){
        var t = e.target.closest('.aa-klienci-tile');
        if (!t) return;
        open(parseInt(t.getAttribute('data-index'), 10) || 0);
    });
    btnX.addEventListener('click', close);
    btnN.addEventListener('click', next);
    btnP.addEventListener('click', prev);
    lb.addEventListener('click', function(e){
        if (e.target === lb) close();
    });
    document.addEventListener('keydown', function(e){
        if (!lb.classList.contains('is-open')) return;
        if (e.key === 'Escape') close();
        else if (e.key === 'ArrowRight') next();
        else if (e.key === 'ArrowLeft') prev();
    });

    // Swipe mobile
    var sx = 0, sy = 0;
    lb.addEventListener('touchstart', function(e){
        if (!e.touches.length) return;
        sx = e.touches[0].clientX;
        sy = e.touches[0].clientY;
    }, {passive: true});
    lb.addEventListener('touchend', function(e){
        if (!e.changedTouches.length) return;
        var dx = e.changedTouches[0].clientX - sx;
        var dy = e.changedTouches[0].clientY - sy;
        if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
            if (dx < 0) next(); else prev();
        }
    });
})();
</script>
<?php endif; ?>

<?php
get_footer();
