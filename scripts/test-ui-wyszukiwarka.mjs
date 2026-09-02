/**
 * Test UI wyszukiwarki zaawansowanej (T-116 etap 3, krok 5).
 * Interakcja + axe (WCAG A/AA/2.1/2.2 AA) przy 320 px i 1366 px + zrzuty ekranu.
 *
 * Uzycie (node_modules z axe-core i puppeteer-core siedzi w ~/projekty/auranet/scripts,
 * dlatego skrypt trzeba tam skopiowac przed uruchomieniem — import rozwiazuje sie
 * wzgledem POLOZENIA pliku, nie katalogu roboczego):
 *   cp ~/projekty/primaauto/scripts/test-ui-wyszukiwarka.mjs ~/projekty/auranet/scripts/tmp-test.mjs
 *   cd ~/projekty/auranet/scripts
 *   CHROME_BIN=~/.cache/ms-playwright/chromium-1223/chrome-linux64/chrome node tmp-test.mjs
 *
 * DWIE PULAPKI zaliczone 02.09:
 *  - baner zgody Complianz przykrywa panel filtrow przy waskiej rzutni i przechwytuje
 *    klikniecia — stad odrzucenie zbednych cookies na starcie;
 *  - panel filtrow jest wlasnym obszarem przewijanym, wiec puppeteer nie doscrolluje
 *    do kontrolki sam — potrzebny jawny scrollIntoView przed klikiem.
 */
import puppeteer from 'puppeteer-core';
import fs from 'fs';
const AXE = fs.readFileSync(new URL('./node_modules/axe-core/axe.min.js', import.meta.url), 'utf8');
const URL_BASE = 'https://primaauto.com.pl/wyszukiwarka/';
const OUT = '/home/host476470/projekty/primaauto/tmp/t116e3/';

const b = await puppeteer.launch({
  executablePath: process.env.CHROME_BIN, protocolTimeout: 420000,
  args: ['--no-sandbox', '--disable-dev-shm-usage'],
});
const log = [];
const say = (s) => { console.log(s); log.push(s); };

for (const [nazwa, w, h] of [['mobile', 320, 720], ['desktop', 1366, 900]]) {
  const p = await b.newPage();
  const bledy = [];
  p.on('pageerror', e => bledy.push('pageerror: ' + e.message));
  p.on('console', m => { if (m.type() === 'error') bledy.push('console: ' + m.text()); });
  await p.setViewport({ width: w, height: h });
  await p.goto(URL_BASE, { waitUntil: 'networkidle2', timeout: 120000 });

  // Baner zgody Complianz przykrywa panel filtrów na wąskiej rzutni — odrzucamy
  // zbędne cookies (wariant najbardziej prywatnościowy), żeby testować UI, nie baner.
  await p.evaluate(() => {
    const deny = document.querySelector('.cmplz-deny, .cmplz-btn.cmplz-deny, [data-cmplz-action="deny"]');
    if (deny) deny.click();
  });
  await new Promise(r => setTimeout(r, 700));

  say(`\n===== ${nazwa} (${w}×${h}) =====`);
  const start = await p.$eval('.aas__count-num', el => el.textContent.trim());
  say(`Start: ${start} ofert`);

  // przewijanie poziome = reflow (WCAG 1.4.10)
  const reflow = await p.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
  say(`Przewijanie poziome: ${reflow ? 'JEST (błąd reflow)' : 'brak'}`);

  // mobile: otwórz szufladę
  if (w < 769) {
    await p.click('.aas__drawer-open');
    await new Promise(r => setTimeout(r, 400));
    const otwarta = await p.$eval('.aas', el => el.classList.contains('is-open'));
    const focusW = await p.evaluate(() => document.querySelector('.aas__panel').contains(document.activeElement));
    say(`Szuflada otwarta: ${otwarta}, focus wewnątrz panelu: ${focusW}`);
  }

  // klik pierwszej flagi wyposażenia (lidar)
  await p.evaluate(() => {
    const s = [...document.querySelectorAll('.aas__group-toggle')].find(b => b.textContent.includes('Kamery'));
    if (s && s.getAttribute('aria-expanded') === 'false') s.click();
  });
  await new Promise(r => setTimeout(r, 200));
  // panel filtrów jest własnym obszarem przewijanym — puppeteer nie scrolluje w nim sam
  await p.evaluate(() => document.querySelector('input[name="wyposazenie[]"][value="lidar"]')
      .scrollIntoView({ block: 'center' }));
  await new Promise(r => setTimeout(r, 200));
  await p.click('input[name="wyposazenie[]"][value="lidar"]');
  await new Promise(r => setTimeout(r, 1800));
  const poLidar = await p.$eval('.aas__count-num', el => el.textContent.trim());
  const urlPoLidar = p.url();
  say(`Po zaznaczeniu „Lidar": ${poLidar} ofert`);
  say(`URL: ${urlPoLidar.replace(URL_BASE, '…/')}`);
  const kart = await p.$$eval('.aa-card', els => els.length);
  say(`Kart w wynikach: ${kart}`);

  // dorzuć zakres mocy
  await p.evaluate(() => {
    const s = [...document.querySelectorAll('.aas__group-toggle')].find(b => b.textContent.includes('Osiągi'));
    if (s && s.getAttribute('aria-expanded') === 'false') s.click();
  });
  await new Promise(r => setTimeout(r, 200));
  await p.evaluate(() => document.querySelector('input[name="moc_min"]').scrollIntoView({ block: 'center' }));
  await new Promise(r => setTimeout(r, 200));
  await p.type('input[name="moc_min"]', '500');
  await new Promise(r => setTimeout(r, 2000));
  const poMocy = await p.$eval('.aas__count-num', el => el.textContent.trim());
  say(`Po dołożeniu „moc od 500 KM": ${poMocy} ofert`);

  // deep-link: przeładuj bieżący URL i sprawdź, czy stan wrócił
  const url2 = p.url();
  await p.goto(url2, { waitUntil: 'networkidle2', timeout: 120000 });
  const poReload = await p.$eval('.aas__count-num', el => el.textContent.trim());
  const lidarZazn = await p.$eval('input[name="wyposazenie[]"][value="lidar"]', el => el.checked);
  const mocVal = await p.$eval('input[name="moc_min"]', el => el.value);
  say(`Po odświeżeniu: ${poReload} ofert, lidar=${lidarZazn}, moc_min=${mocVal}`);
  say(`Deep-link odtwarza stan: ${poReload === poMocy && lidarZazn && mocVal === '500' ? 'TAK' : 'NIE'}`);

  await p.screenshot({ path: `${OUT}zrzut-${nazwa}.png`, fullPage: false });

  // axe
  await p.evaluate(AXE);
  const wynik = await p.evaluate(async () => await axe.run(document, {
    runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'] }
  }));
  say(`axe: ${wynik.violations.length} typów naruszeń, ${wynik.passes.length} reguł zdanych`);
  for (const v of wynik.violations) {
    say(`  [${v.impact}] ${v.id}: ${v.help} (${v.nodes.length}×)`);
    say(`      np. ${v.nodes[0].target.join(' ')} — ${(v.nodes[0].failureSummary || '').split('\n')[1] || ''}`);
  }
  say(`Błędy JS: ${bledy.length ? bledy.join(' | ') : 'brak'}`);
  await p.close();
}
await b.close();
fs.writeFileSync(OUT + 'test-ui-wynik.txt', log.join('\n'));
