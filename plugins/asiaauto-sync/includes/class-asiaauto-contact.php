<?php
/**
 * [asiaauto_contact] — Contact page shortcode.
 *
 * SEO: single H1 in hero, H2 per section,
 * Schema.org LocalBusiness with address, phone, opening hours.
 *
 * Sections: Hero, contact info + map (2-col), company data + bank (2-col),
 * write to us, CTA buttons.
 *
 * Data source: get_option('asiaauto_order_config') — phone, email, company name,
 * address, NIP, REGON, bank account. Yard address hardcoded (different from reg. address).
 *
 * @since 0.26.0
 * @package Prima-Auto
 */
defined('ABSPATH') || exit;

class AsiaAuto_Contact {

    /** Yard address — different from company registration address */
    private const YARD_STREET = 'ul. Przemysłowa 13A';
    private const YARD_POSTAL = '35-105';
    private const YARD_CITY   = 'Rzeszów';

    /** Google Maps embed URL for the yard */
    private const MAPS_EMBED = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2564.0!2d22.018!3d50.025!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sPrzemys%C5%82owa%2013A%2C%2035-105%20Rzesz%C3%B3w!5e0!3m2!1spl!2spl!4v1';
    private const MAPS_LINK  = 'https://www.google.com/maps/search/Przemysłowa+13A,+35-105+Rzeszów';

    /** Opening hours */
    private const HOURS = [
        ['days' => 'Poniedziałek — Piątek', 'time' => '9:00 — 17:00', 'closed' => false,
         'schema_days' => ['Monday','Tuesday','Wednesday','Thursday','Friday'], 'schema_open' => '09:00', 'schema_close' => '17:00'],
        ['days' => 'Sobota',                 'time' => '10:00 — 14:00', 'closed' => false,
         'schema_days' => ['Saturday'], 'schema_open' => '10:00', 'schema_close' => '14:00'],
        ['days' => 'Niedziela',              'time' => 'Zamknięte',     'closed' => true,
         'schema_days' => ['Sunday'], 'schema_open' => null, 'schema_close' => null],
    ];

    public function __construct() {
        add_shortcode('asiaauto_contact', [$this, 'render']);
    }

    public function render(array $atts = []): string {
        $data = $this->gatherData();

        ob_start();
        $this->renderCSS();
        echo $this->renderSchema($data);
        ?>
        <div class="aa-contact">
            <?= $this->renderHero() ?>
            <?= $this->renderMainGrid($data) ?>
            <?= $this->renderCompanyGrid($data) ?>
            <?= $this->renderWriteToUs($data) ?>
            <?= $this->renderCTA($data) ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    //  DATA
    // =========================================================================

    private function gatherData(): array {
        $config  = get_option('asiaauto_order_config', []);
        $phone   = $config['company_phone'] ?? '';
        $phone_2 = $config['company_phone_2'] ?? '';

        return [
            'phone'         => $phone,
            'phone_clean'   => preg_replace('/[\s\-\(\)]/', '', $phone),
            'phone_wa'      => ltrim(preg_replace('/[\s\-\(\)]/', '', $phone), '+'),
            'phone_2'       => $phone_2,
            'phone_2_clean' => preg_replace('/[\s\-\(\)]/', '', $phone_2),
            'email'         => $config['admin_notification_email'] ?? '',
            'company_name'  => $config['company_name'] ?? '',
            'company_addr'  => $config['company_address'] ?? '',
            'nip'           => $config['company_nip'] ?? '',
            'regon'         => $config['company_regon'] ?? '',
            'bank_name'     => $config['bank_account_name'] ?? '',
            'bank_number'   => $config['bank_account_number'] ?? '',
        ];
    }

    // =========================================================================
    //  SCHEMA.ORG — LocalBusiness
    // =========================================================================

    private function renderSchema(array $data): string {
        $hours_spec = [];
        foreach (self::HOURS as $h) {
            if ($h['schema_open'] === null) continue;
            $hours_spec[] = [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => $h['schema_days'],
                'opens'     => $h['schema_open'],
                'closes'    => $h['schema_close'],
            ];
        }

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'LocalBusiness',
            '@id'         => home_url('/kontakt/') . '#business',
            'name'        => 'Prima-Auto',
            'legalName'   => $data['company_name'],
            'description' => 'Import samochodów elektrycznych i hybrydowych z Chin do Polski. Plac samochodowy w Rzeszowie.',
            'url'         => home_url('/'),
            'telephone'   => $data['phone'],
            'email'       => $data['email'],
            'address'     => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => self::YARD_STREET,
                'postalCode'      => self::YARD_POSTAL,
                'addressLocality' => self::YARD_CITY,
                'addressCountry'  => 'PL',
            ],
            'geo'         => [
                '@type'     => 'GeoCoordinates',
                'latitude'  => 50.025,
                'longitude' => 22.018,
            ],
            'openingHoursSpecification' => $hours_spec,
            'priceRange'  => '$$',
            'image'       => home_url('/wp-content/uploads/2026/03/primaauto-logo-round.png'),
            'areaServed'  => [
                '@type' => 'Country',
                'name'  => 'PL',
            ],
            'sameAs' => [
                'https://www.facebook.com/prima1auto/',
                'https://www.instagram.com/prima_auto.pl/',
                'https://www.tiktok.com/@primaauto.pl',
            ],
        ];

        if ($data['nip']) {
            $schema['taxID'] = $data['nip'];
        }

        return '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
    }

    // =========================================================================
    //  HERO
    // =========================================================================

    private function renderHero(): string {
        ob_start();
        ?>
        <div class="aa-contact__hero">
            <h2 class="aa-contact__hero-title">Kontakt — Prima-Auto | Import samochodów z Chin</h2>
            <p class="aa-contact__hero-sub">Plac samochodowy w Rzeszowie — odpowiadamy w ciągu godziny</p>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    //  MAIN GRID — contact info + map (2 columns desktop)
    // =========================================================================

    private function renderMainGrid(array $data): string {
        ob_start();
        ?>
        <div class="aa-contact__main">
            <div class="aa-contact__info">
                <h2 class="aa-contact__section-title">Dane kontaktowe</h2>

                <?php if ($data['phone']): ?>
                <div class="aa-contact__row">
                    <span class="aa-contact__icon aa-contact__icon--navy">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6A19.79 19.79 0 012.12 4.18 2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.362 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0122 16.92z" stroke="currentColor" stroke-width="1.8"/></svg>
                    </span>
                    <span class="aa-contact__row-body">
                        <span class="aa-contact__label">Telefon</span>
                        <span class="aa-contact__value">
                            <a href="tel:<?= esc_attr($data['phone_clean']) ?>"><?= esc_html($data['phone']) ?></a><?php if ($data['phone_2']): ?>, <a href="tel:<?= esc_attr($data['phone_2_clean']) ?>"><?= esc_html($data['phone_2']) ?></a><?php endif; ?>
                        </span>
                    </span>
                </div>

                <a href="https://wa.me/<?= esc_attr($data['phone_wa']) ?>?text=<?= rawurlencode('Dzień dobry, piszę w sprawie oferty na primaauto.com.pl') ?>" target="_blank" rel="noopener" class="aa-contact__row">
                    <span class="aa-contact__icon aa-contact__icon--green">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                    </span>
                    <span class="aa-contact__row-body">
                        <span class="aa-contact__label">WhatsApp</span>
                        <span class="aa-contact__value aa-contact__value--green"><?= esc_html($data['phone']) ?></span>
                    </span>
                </a>
                <?php endif; ?>

                <?php if ($data['email']): ?>
                <a href="mailto:<?= esc_attr($data['email']) ?>" class="aa-contact__row">
                    <span class="aa-contact__icon aa-contact__icon--amber">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="1.8"/><path d="M22 6l-10 7L2 6" stroke="currentColor" stroke-width="1.8"/></svg>
                    </span>
                    <span class="aa-contact__row-body">
                        <span class="aa-contact__label">Email</span>
                        <span class="aa-contact__value"><?= esc_html($data['email']) ?></span>
                    </span>
                </a>
                <?php endif; ?>

                <div class="aa-contact__row">
                    <span class="aa-contact__icon aa-contact__icon--navy">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0112 2a8 8 0 018 8.2c0 7.3-8 11.8-8 11.8z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="1.8"/></svg>
                    </span>
                    <span class="aa-contact__row-body">
                        <span class="aa-contact__label">Plac samochodowy</span>
                        <span class="aa-contact__value"><?= esc_html(self::YARD_STREET) ?></span>
                        <span class="aa-contact__hint"><?= esc_html(self::YARD_POSTAL . ' ' . self::YARD_CITY) ?></span>
                    </span>
                </div>

                <div class="aa-contact__hours">
                    <h2 class="aa-contact__section-title">Godziny pracy</h2>
                    <table class="aa-contact__hours-table">
                        <?php foreach (self::HOURS as $h): ?>
                        <tr>
                            <td class="aa-contact__hours-day"><?= esc_html($h['days']) ?></td>
                            <td class="aa-contact__hours-time<?= $h['closed'] ? ' aa-contact__hours-time--closed' : '' ?>"><?= esc_html($h['time']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

            <div class="aa-contact__map-col">
                <h2 class="aa-contact__section-title">Lokalizacja placu</h2>
                <div class="aa-contact__map-wrap">
                    <iframe
                        class="aa-contact__map-iframe"
                        src="https://www.google.com/maps?q=<?= rawurlencode(self::YARD_STREET . ', ' . self::YARD_POSTAL . ' ' . self::YARD_CITY) ?>&output=embed"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Mapa — Prima-Auto plac samochodowy, <?= esc_attr(self::YARD_CITY) ?>"
                    ></iframe>
                </div>
                <a href="<?= esc_url(self::MAPS_LINK) ?>" target="_blank" rel="noopener" class="aa-contact__map-link">
                    Otwórz w Google Maps →
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    //  COMPANY + BANK (2 columns desktop)
    // =========================================================================

    private function renderCompanyGrid(array $data): string {
        ob_start();
        ?>
        <div class="aa-contact__bottom">
            <section class="aa-contact__company">
                <h2 class="aa-contact__section-title">Dane firmy</h2>
                <table class="aa-contact__data-table">
                    <?php if ($data['company_name']): ?>
                    <tr><td class="aa-contact__data-label">Firma</td><td class="aa-contact__data-value"><?= esc_html($data['company_name']) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($data['company_addr']): ?>
                    <tr><td class="aa-contact__data-label">Adres</td><td class="aa-contact__data-value"><?= esc_html($data['company_addr']) ?>, <?= esc_html(self::YARD_CITY) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($data['nip']): ?>
                    <tr><td class="aa-contact__data-label">NIP</td><td class="aa-contact__data-value"><?= esc_html($data['nip']) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($data['regon']): ?>
                    <tr><td class="aa-contact__data-label">REGON</td><td class="aa-contact__data-value"><?= esc_html($data['regon']) ?></td></tr>
                    <?php endif; ?>
                </table>
            </section>

            <?php if ($data['bank_number']): ?>
            <section class="aa-contact__bank">
                <h2 class="aa-contact__section-title">Rachunek bankowy</h2>
                <?php if ($data['bank_name']): ?>
                <div class="aa-contact__bank-label">Nazwa odbiorcy</div>
                <div class="aa-contact__bank-value"><?= esc_html($data['bank_name']) ?></div>
                <?php endif; ?>
                <div class="aa-contact__bank-label">Numer konta</div>
                <div class="aa-contact__bank-iban"><?= esc_html($data['bank_number']) ?></div>
            </section>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    //  WRITE TO US
    // =========================================================================

    private function renderWriteToUs(array $data): string {
        ob_start();
        ?>
        <section class="aa-contact__write">
            <h2 class="aa-contact__section-title">Napisz do nas</h2>
            <p class="aa-contact__write-sub">Wyślij wiadomość — odezwiemy się w ciągu godziny</p>
            <?php if ($data['email']): ?>
            <a href="mailto:<?= esc_attr($data['email']) ?>?subject=<?= rawurlencode('Zapytanie ze strony primaauto.com.pl') ?>" class="aa-contact__write-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="1.8"/><path d="M22 6l-10 7L2 6" stroke="currentColor" stroke-width="1.8"/></svg>
                Napisz email: <?= esc_html($data['email']) ?>
            </a>
            <?php endif; ?>
        </section>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    //  CTA
    // =========================================================================

    private function renderCTA(array $data): string {
        if (!$data['phone']) return '';

        ob_start();
        ?>
        <div class="aa-contact__cta">
            <h2 class="aa-contact__cta-title">Zadzwoń lub napisz</h2>
            <p class="aa-contact__cta-sub">Odpowiadamy w ciągu godziny, 7 dni w tygodniu</p>
            <div class="aa-contact__cta-btns">
                <a href="tel:<?= esc_attr($data['phone_clean']) ?>" class="aa-contact__cta-btn aa-contact__cta-btn--phone">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6A19.79 19.79 0 012.12 4.18 2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.362 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0122 16.92z" stroke="currentColor" stroke-width="1.8"/></svg>
                    Zadzwoń
                </a>
                <a href="https://wa.me/<?= esc_attr($data['phone_wa']) ?>?text=<?= rawurlencode('Dzień dobry, piszę w sprawie oferty na primaauto.com.pl') ?>" target="_blank" rel="noopener" class="aa-contact__cta-btn aa-contact__cta-btn--wa">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                    WhatsApp
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================================
    //  CSS
    // =========================================================================

    private function renderCSS(): void {
        ?>
        <style>
        /* === AA CONTACT — Design System === */
        .aa-contact {
            --pri: #1B2A4A; --txt: #2D3748; --sec: #718096; --accent: #D63031;
            --accent-h: #B52828; --bg: #F5F6F8; --surface: #FFFFFF; --border: #E1E4E8;
            --radius: 8px;
            --font: 'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
            font-family: var(--font); color: var(--txt); max-width: 1280px; margin: 0 auto;
        }
        .aa-contact *, .aa-contact *::before { box-sizing: border-box; }
        .aa-contact a { text-decoration: none; color: inherit; cursor: pointer; }

        /* === HERO === */
        .aa-contact__hero {
            background: var(--pri) !important; padding: 28px 20px; text-align: center;
        }
        .aa-contact__hero-title {
            color: #fff !important; font-size: 22px; font-weight: 700; margin: 0 0 6px; line-height: 1.3;
        }
        .aa-contact__hero-sub {
            color: rgba(255,255,255,.55) !important; font-size: 14px; margin: 0; font-weight: 400; line-height: 1.4;
        }

        /* === MAIN GRID — info + map === */
        .aa-contact__main {
            display: grid; grid-template-columns: 1fr; border: 1px solid var(--border); border-top: none;
        }
        .aa-contact__section-title {
            font-size: 18px; font-weight: 700; color: var(--pri); margin: 0 0 16px;
        }

        /* Contact rows */
        .aa-contact__info { padding: 28px 20px; }
        .aa-contact__row {
            display: flex; align-items: center; gap: 14px; margin-bottom: 14px;
            transition: opacity .15s;
        }
        a.aa-contact__row:hover { opacity: .75; }
        .aa-contact__icon {
            flex-shrink: 0; width: 40px; height: 40px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
        }
        .aa-contact__icon--navy  { background: #EBF0F7; color: #1B2A4A; }
        .aa-contact__icon--green { background: #E6F7EE; color: #276749; }
        .aa-contact__icon--amber { background: #FFF5E5; color: #8B6914; }
        .aa-contact__row-body { display: flex; flex-direction: column; }
        .aa-contact__label { font-size: 12px; color: var(--sec); line-height: 1.2; }
        .aa-contact__value { font-size: 15px; font-weight: 600; color: var(--pri); line-height: 1.4; }
        .aa-contact__value--green { color: #25D366; }
        .aa-contact__hint { font-size: 13px; color: var(--sec); }

        /* Hours */
        .aa-contact__hours { margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); }
        .aa-contact__hours-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .aa-contact__hours-day { color: var(--sec); padding: 5px 0; }
        .aa-contact__hours-time { text-align: right; padding: 5px 0; font-weight: 600; color: var(--txt); }
        .aa-contact__hours-time--closed { color: var(--accent); font-weight: 600; }

        /* Map column */
        .aa-contact__map-col { padding: 28px 20px; border-top: 1px solid var(--border); }
        .aa-contact__map-wrap {
            border-radius: var(--radius); overflow: hidden; aspect-ratio: 16/10;
            background: var(--bg);
        }
        .aa-contact__map-iframe {
            width: 100%; height: 100%; border: 0; display: block;
        }
        .aa-contact__map-link {
            display: block; margin-top: 10px; font-size: 13px; font-weight: 600;
            color: var(--pri); text-align: center;
        }
        .aa-contact__map-link:hover { color: var(--accent); }

        /* === BOTTOM GRID — company + bank === */
        .aa-contact__bottom {
            display: grid; grid-template-columns: 1fr;
            border: 1px solid var(--border); border-top: none;
        }
        .aa-contact__company { padding: 28px 20px; }
        .aa-contact__bank { padding: 28px 20px; border-top: 1px solid var(--border); }

        .aa-contact__data-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .aa-contact__data-label { color: var(--sec); padding: 5px 0; width: 80px; vertical-align: top; }
        .aa-contact__data-value { padding: 5px 0; font-weight: 600; color: var(--txt); }

        .aa-contact__bank-label { font-size: 12px; color: var(--sec); margin-bottom: 3px; }
        .aa-contact__bank-value { font-size: 15px; font-weight: 600; color: var(--txt); margin-bottom: 14px; }
        .aa-contact__bank-iban {
            font-size: 16px; font-weight: 600; color: var(--pri);
            font-family: 'JetBrains Mono', 'Fira Code', monospace; letter-spacing: .5px;
        }

        /* === WRITE TO US === */
        .aa-contact__write {
            padding: 28px 20px; background: var(--bg);
            border: 1px solid var(--border); border-top: none;
        }
        .aa-contact__write-sub { font-size: 14px; color: var(--sec); margin: 0 0 16px; }
        .aa-contact__write-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px; background: var(--surface); border: 1px solid var(--border);
            border-radius: 6px; font-size: 14px; font-weight: 600; color: var(--pri);
            font-family: var(--font); transition: border-color .15s, background .15s;
        }
        .aa-contact__write-btn:hover { border-color: var(--pri); background: #F8F9FA; }

        /* === CTA === */
        .aa-contact__cta {
            margin: 24px 0; background: var(--pri); border-radius: 12px;
            padding: 32px 24px; text-align: center;
        }
        .aa-contact__cta-title { color: #fff !important; font-size: 20px; font-weight: 700; margin: 0 0 6px; }
        .aa-contact__cta-sub { color: rgba(255,255,255,.5) !important; font-size: 13px; margin: 0 0 20px; }
        .aa-contact__cta-btns { display: flex; gap: 10px; max-width: 400px; margin: 0 auto; }
        .aa-contact__cta-btn {
            flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px;
            padding: 13px; border-radius: 6px; font-size: 15px; font-weight: 600;
            transition: opacity .15s; font-family: var(--font);
        }
        .aa-contact__cta-btn:hover { opacity: .9; }
        .aa-contact__cta-btn--phone { background: #fff; color: var(--pri); }
        .aa-contact__cta-btn--wa { background: #25D366; color: #fff; }

        /* === DESKTOP (768px+) === */
        @media (min-width: 768px) {
            .aa-contact__hero { padding: 44px 48px; }
            .aa-contact__hero-title { font-size: 32px; }
            .aa-contact__hero-sub { font-size: 15px; }

            .aa-contact__main { grid-template-columns: 1fr 1fr; }
            .aa-contact__info { padding: 32px; border-right: 1px solid var(--border); }
            .aa-contact__map-col { padding: 32px; border-top: none; }
            .aa-contact__map-wrap { aspect-ratio: auto; height: 100%; min-height: 300px; }

            .aa-contact__bottom { grid-template-columns: 1fr 1fr; }
            .aa-contact__company { padding: 28px 32px; border-right: 1px solid var(--border); }
            .aa-contact__bank { padding: 28px 32px; border-top: none; }

            .aa-contact__write { padding: 32px; }
            .aa-contact__cta { margin: 24px 0 32px; padding: 36px 48px; }
        }

        /* === LARGE DESKTOP (1024px+) === */
        @media (min-width: 1024px) {
            .aa-contact__section-title { font-size: 20px; }
            .aa-contact__data-label { width: 100px; }
        }
        </style>
        <?php
    }
}
