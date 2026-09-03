/**
 * Test UI wyszukiwarki zaawansowanej (T-116; scenariusz pod układ z makiety I, 0.37.0).
 * Interakcja + axe (WCAG A/AA/2.1/2.2 AA) przy 320 px i 1366 px + zrzuty ekranu.
 *
 * Uzycie (node_modules z axe-core i puppeteer-core siedzi w ~/projekty/auranet/scripts,
 * dlatego skrypt trzeba tam skopiowac przed uruchomieniem — import rozwiazuje sie
 * wzgledem POLOZENIA pliku, nie katalogu roboczego):
 *   cp ~/projekty/primaauto/scripts/test-ui-wyszukiwarka.mjs ~/projekty/auranet/scripts/tmp-test.mjs
 *   cd ~/projekty/auranet/scripts
 *   CHROME_BIN=~/.cache/ms-playwright/chromium-1223/chrome-linux64/chrome node tmp-test.mjs
 *
 * Sprawdza to, czego testy z 02.09 nie sprawdzaly: model po marce, „Wyczysc", paginacje,
 * sortowanie, „Wiecej filtrow", kafle „Oferty" (klik + drugi klik = wszystkie), liste
 * rozwijana (otwarcie + Escape), deep-link po odswiezeniu, pole w metrach (5,0 → 5000 mm).
 * PULAPKI: baner Complianz przechwytuje klikniecia (odrzucamy cookies na starcie); checkboxy
 * w listach i pastylkach maja opacity 0 — klikamy etykiete, nie input.
 */
import puppeteer from 'puppeteer-core';
import fs from 'fs';
const AXE = fs.readFileSync(new URL('./node_modules/axe-core/axe.min.js', import.meta.url), 'utf8');
const URL_BASE = 'https://primaauto.com.pl/wyszukiwarka/';
const OUT = '/home/host476470/projekty/primaauto/tmp/t116e3/';
fs.mkdirSync(OUT, { recursive: true });

const b = await puppeteer.launch({ executablePath: process.env.CHROME_BIN, protocolTimeout: 420000, args: ['--no-sandbox', '--disable-dev-shm-usage'] });
const log = [];
const say = (s) => { console.log(s); log.push(s); };
const sleep = (ms) => new Promise(r => setTimeout(r, ms));
const TOTAL = '.aas__toolbar .aas__total';

for (const [nazwa, w, h] of [['mobile', 320, 720], ['desktop', 1366, 900]]) {
  const p = await b.newPage();
  const bledy = [];
  p.on('pageerror', e => bledy.push('pageerror: ' + e.message));
  p.on('console', m => { if (m.type() === 'error') bledy.push('console: ' + m.text()); });
  await p.setViewport({ width: w, height: h });
  await p.goto(URL_BASE, { waitUntil: 'domcontentloaded', timeout: 120000 });
  await p.waitForSelector(TOTAL, { timeout: 60000 });
  await p.evaluate(() => { const d = document.querySelector('.cmplz-deny, .cmplz-btn.cmplz-deny, [data-cmplz-action="deny"]'); if (d) d.click(); });
  await sleep(700);
  const total = async () => p.$eval(TOTAL, el => el.textContent.trim());
  const kafelN = async (k) => p.$eval('.aas__kafel-n[data-oferta="' + k + '"]', el => el.textContent.trim());

  say(`\n===== ${nazwa} (${w}×${h}) =====`);
  const start = await total();
  say(`Start: ${start} ofert; kafle: do sprowadzenia ${await kafelN('do-sprowadzenia')}, w drodze ${await kafelN('w-drodze')}, na placu ${await kafelN('na-placu')}`);
  say(`Przewijanie poziome: ${await p.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1) ? 'JEST (błąd reflow)' : 'brak'}`);

  // marka → model
  const modelPrzed = await p.$eval('.aas__pole--enum[data-col="serie"] .aas__sel', el => el.disabled);
  await p.evaluate(() => { const t = document.querySelector('.aas__pole--enum[data-col="make"] .aas__sel'); t.scrollIntoView({ block: 'center' }); t.click(); });
  await sleep(300);
  const listaOtwarta = await p.$eval('.aas__pole--enum[data-col="make"] .aas__sel', el => el.getAttribute('aria-expanded') === 'true');
  await p.evaluate(() => document.querySelector('.aas__pole--enum[data-col="make"] input[value="byd"]').closest('label').click());
  await sleep(1800);
  await p.evaluate(() => document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true })));
  const modelPo = await p.$eval('.aas__pole--enum[data-col="serie"] .aas__sel', el => el.disabled);
  const modeli = await p.$$eval('.aas__pole--enum[data-col="serie"] .aas__opt:not([hidden])', els => els.length);
  say(`Lista marki otwiera się: ${listaOtwarta}; po BYD: „${await p.$eval('.aas__pole--enum[data-col="make"] .aas__sel-text', el => el.textContent.trim())}", ${await total()} ofert`);
  say(`Model: wyłączony przed=${modelPrzed}, po=${modelPo}, widocznych modeli: ${modeli}`);

  // pastylka lidar
  await p.evaluate(() => { const l = document.querySelector('input[name="wyposazenie[]"][value="lidar"]').closest('label'); l.scrollIntoView({ block: 'center' }); l.click(); });
  await sleep(1800);
  const poLidar = await total();
  say(`Po „Lidar": ${poLidar} ofert; URL: ${p.url().replace(URL_BASE, '…/')}; kart: ${await p.$$eval('.aa-card', els => els.length)}`);

  // długość w metrach → mm
  await p.evaluate(() => { const el = document.querySelector('input[name="dlugosc_min"]'); el.scrollIntoView({ block: 'center' }); el.value = '5,0'; el.dispatchEvent(new Event('input', { bubbles: true })); });
  await sleep(2000);
  const poDl = await total();
  say(`Po „długość od 5,0 m": ${poDl} ofert; URL ma dlugosc_min=5: ${p.url().includes('dlugosc_min=5')}`);

  // deep-link
  const url2 = p.url();
  await p.goto(url2, { waitUntil: 'domcontentloaded', timeout: 120000 });
  await p.waitForSelector(TOTAL, { timeout: 60000 });
  await sleep(1200);
  const poReload = await total();
  const lidarZazn = await p.$eval('input[name="wyposazenie[]"][value="lidar"]', el => el.checked);
  const dlVal = await p.$eval('input[name="dlugosc_min"]', el => el.value);
  say(`Po odświeżeniu: ${poReload} ofert, lidar=${lidarZazn}, dlugosc_min="${dlVal}", Wyczyść: ${await p.$eval('.aas__clear', el => el.textContent.trim())}`);
  say(`Deep-link odtwarza stan: ${poReload === poDl && lidarZazn && dlVal === '5,0' ? 'TAK' : 'NIE'}`);

  // kafel „Do sprowadzenia": zawęża; drugi klik = wszystkie; kafel z zerem jest wyłączony
  const doSprN = await kafelN('do-sprowadzenia');
  await p.evaluate(() => { const k = document.querySelector('.aas__kafel input[value="do-sprowadzenia"]').closest('label'); k.scrollIntoView({ block: 'center' }); k.click(); });
  await sleep(1800);
  const poKaflu = await total();
  const aktywny = await p.$eval('.aas__kafel input[value="do-sprowadzenia"]', el => el.closest('label').classList.contains('is-active'));
  await p.evaluate(() => { const k = document.querySelector('.aas__kafel input[value="do-sprowadzenia"]').closest('label'); k.dispatchEvent(new MouseEvent('mousedown', { bubbles: true })); });
  await sleep(1800);
  const zeroWylaczony = await p.$$eval('.aas__kafel input', els => els.filter(i => i.closest('label').classList.contains('is-empty')).every(i => i.disabled));
  say(`Kafel „Do sprowadzenia": liczba ${doSprN} → wyników ${poKaflu} (=? ${doSprN === poKaflu}), aktywny=${aktywny}; drugi klik → ${await total()} (=${poReload}? ${(await total()) === poReload}); kafle z zerem wyłączone=${zeroWylaczony}`);
  // reguła UX: opcja z zerem szara i wyłączona, podpowiedź zależna
  const szareBody = await p.$$eval('.aas__pole--enum[data-col="body"] .aas__opt.is-empty input', els => els.length && els.every(i => i.disabled));
  say(`Opcje z zerem w „Rodzaj nadwozia": szare i wyłączone=${szareBody}; podpowiedź „Moc od" po filtrach: ${await p.$eval('input[name="moc_min"]', el => el.placeholder)}`);

  // liczba miejsc (lista, Nadwozie) + dach panoramiczny (pastylka)
  await p.evaluate(() => { const t = document.querySelector('.aas__pole--enum[data-col="seats"] .aas__sel'); t.scrollIntoView({ block: 'center' }); t.click(); });
  await sleep(300);
  await p.evaluate(() => document.querySelector('.aas__pole--enum[data-col="seats"] input[value="7"]').closest('label').click());
  await sleep(1800);
  await p.evaluate(() => document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true })));
  const poMiejscach = await total();
  await p.evaluate(() => { const l = document.querySelector('input[name="wyposazenie[]"][value="roof_panorama"]').closest('label'); l.scrollIntoView({ block: 'center' }); l.click(); });
  await sleep(1800);
  say(`Miejsca 7: ${poMiejscach} ofert; + Dach panoramiczny: ${await total()} ofert; URL: ${p.url().replace(URL_BASE, '…/')}`);

  // sortowanie
  await p.select('.aas__sort-select', 'year_desc');
  await sleep(1500);
  say(`Sortowanie Najnowszy rocznik: pierwsza karta ${await p.$eval('.aa-card .aa-card__specs', el => el.textContent.trim().replace(/\s+/g, ' ').slice(0, 12))}, URL sort=${p.url().includes('sort=year_desc')}`);
  await p.select('.aas__sort-select', 'price_asc');
  await sleep(1800);
  say(`Sortowanie „Najtańsze": pierwsza cena ${await p.$eval('.aa-card .aa-card__price', el => el.textContent.trim())}, URL sort=${p.url().includes('sort=price_asc')}`);

  // wyczyść
  await p.evaluate(() => { const c = document.querySelector('.aas__clear'); c.scrollIntoView({ block: 'center' }); c.click(); });
  await sleep(2000);
  say(`Po „Wyczyść": ${await total()} ofert (start ${start}), URL czysty=${!p.url().includes('?')}, lidar=${await p.$eval('input[name="wyposazenie[]"][value="lidar"]', el => el.checked)}`);

  // paginacja
  const t1 = await p.$eval('.aa-card .aa-card__title', el => el.textContent.trim());
  await p.evaluate(() => { const a = document.querySelector('.aas__page[data-page="2"]'); a.scrollIntoView(); a.click(); });
  await sleep(2200);
  const t2 = await p.$eval('.aa-card .aa-card__title', el => el.textContent.trim());
  say(`Paginacja: strona 2 aktywna=${await p.$eval('.aas__page--current', el => el.textContent.trim()) === '2'}, karty podmienione=${t1 !== t2}, URL strona=2: ${p.url().includes('strona=2')}, scrollY=${Math.round(await p.evaluate(() => window.scrollY))}`);

  // lista: otwiera się, Escape zamyka
  const dd = await p.evaluate(() => {
    const t = document.querySelector('.aas__pole--enum[data-col="body"] .aas__sel');
    t.scrollIntoView({ block: 'center' }); t.click();
    const o = t.getAttribute('aria-expanded') === 'true' && !document.getElementById(t.getAttribute('aria-controls')).hidden;
    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
    const z = t.getAttribute('aria-expanded') === 'false';
    return { o, z };
  });
  say(`Lista rozwijana: otwiera się ${dd.o}, Escape zamyka ${dd.z}`);

  await p.evaluate(() => window.scrollTo(0, 0));
  await p.screenshot({ path: `${OUT}zrzut-${nazwa}.png`, fullPage: false });

  await p.evaluate(AXE);
  for (const [opis, przygotuj] of [['strona', null], ['otwarta lista marki', () => document.querySelector('.aas__pole--enum[data-col="make"] .aas__sel').click()]]) {
    if (przygotuj) { await p.evaluate(przygotuj); await sleep(400); }
    const wynik = await p.evaluate(async () => await axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'] } }));
    say(`axe (${opis}): ${wynik.violations.length} typów naruszeń, ${wynik.passes.length} reguł zdanych`);
    for (const v of wynik.violations) {
      say(`  [${v.impact}] ${v.id}: ${v.help} (${v.nodes.length}×)`);
      say(`      np. ${v.nodes[0].target.join(' ')} — ${(v.nodes[0].failureSummary || '').split('\n')[1] || ''}`);
    }
  }
  say(`Błędy JS: ${bledy.length ? bledy.join(' | ') : 'brak'}`);
  await p.close();
}
await b.close();
fs.writeFileSync(OUT + 'test-ui-wynik.txt', log.join('\n'));
