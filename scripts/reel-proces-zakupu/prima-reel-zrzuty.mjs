// T-265: zrzuty ekranow primaauto.com.pl w widoku telefonu do filmu/karuzeli procesu zakupu.
// pptr prima-reel-zrzuty.mjs <katalog_wyjscia> [tylko=nazwa,nazwa]
import puppeteer from 'puppeteer-core';
import fs from 'fs';
const OUT = process.argv[2]; const TYLKO = (process.argv[3]||'').replace('tylko=','').split(',').filter(Boolean);
fs.mkdirSync(OUT, { recursive: true });
const B = 'https://primaauto.com.pl';
const AUTO = `${B}/oferta/denza-z9-gt-dm-i-2025-303534/`;
const CSS = `#cmplz-cookiebanner-container,.cmplz-cookiebanner,#cmplz-manage-consent,.pa-news,.grecaptcha-badge{display:none!important}`;
const sleep = ms => new Promise(r => setTimeout(r, ms));
const b = await puppeteer.launch({ executablePath: process.env.CHROME_BIN, headless: true,
  args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu','--no-zygote','--single-process'] });
const p = await b.newPage();
await p.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 3 });
const idz = async u => { await p.goto(u, { waitUntil: 'networkidle2', timeout: 90000 }); await p.addStyleTag({ content: CSS }); await sleep(1500); };
const DLUGI = `.aa-mobile-cta,.pa-dock,[class*=cmp-dock],[class*=compare-dock]{display:none!important}`;
const shot = async (n, opt = {}) => { if (opt.clip) await p.addStyleTag({ content: DLUGI }); await p.screenshot({ path: `${OUT}/${n}.png`, ...opt }); console.log('zrzut', n); };
const tekst = async (sel, t) => { const ok = await p.evaluate((s, t) => { const e = [...document.querySelectorAll(s)].find(x => x.offsetParent && x.innerText.trim().startsWith(t)); if (e) { e.scrollIntoView({ block: 'center' }); e.click(); return true; } return false; }, sel, t); if (!ok) console.log('BRAK', sel, t); await sleep(1200); return ok; };
const klik = async sel => { await p.$eval(sel, e => { e.scrollIntoView({ block: 'center' }); e.click(); }).catch(() => console.log('BRAK', sel)); await sleep(1200); };
const pozycja = async sel => p.$eval(sel, e => { const r = e.getBoundingClientRect(); return { x: r.x, y: r.y, w: r.width, h: r.height }; }).catch(() => null);
const sceny = {
  async lista() { await idz(`${B}/samochody/`); await shot('lista-ekran'); await shot('lista-pelna', { clip: { x: 0, y: 0, width: 390, height: 2600 }, captureBeyondViewport: true }); },
  async filtr() {
    await idz(`${B}/samochody/`);
    await klik('.aa-inv__filter-toggle'); await shot('filtr-1-panel');
    const t = (await p.$$('.aa-filter__trigger'))[0]; await t.click(); await sleep(1200); await shot('filtr-2-marki');
    await tekst('label, li, button, [role=option]', 'Denza'); await shot('filtr-3-denza');
    fs.writeFileSync(`${OUT}/filtr-poz.json`, JSON.stringify({ toggle: await pozycja('.aa-inv__filter-toggle'), apply: await pozycja('.aa-inv__sidebar-apply') }));
    await klik('.aa-inv__sidebar-apply'); await sleep(1500); await shot('filtr-4-wynik');
  },
  async wyszukiwarka() { await idz(`${B}/wyszukiwarka/`); await shot('wyszuk-pelna', { clip: { x: 0, y: 0, width: 390, height: 2200 }, captureBeyondViewport: true }); },
  async specyfikacja() { await idz(AUTO);
    fs.writeFileSync(`${OUT}/spec-poz.json`, JSON.stringify(await p.evaluate(() => ({
      naglowki: [...document.querySelectorAll('h2,h3,h4,summary,.aa-spec__title,[class*=section-title]')].filter(e => e.offsetParent).map(e => ({ t: e.innerText.trim().slice(0, 50), y: Math.round(e.getBoundingClientRect().top + scrollY) })),
      wiersze: document.querySelectorAll('table tr').length, h: document.documentElement.scrollHeight }))));
    await shot('spec-pelna', { clip: { x: 0, y: 0, width: 390, height: 7600 }, captureBeyondViewport: true }); },
  async oferta() { await idz(AUTO); await shot('oferta-ekran'); await shot('oferta-pelna', { clip: { x: 0, y: 0, width: 390, height: 3200 }, captureBeyondViewport: true });
    fs.writeFileSync(`${OUT}/oferta-poz.json`, JSON.stringify(await p.evaluate(() => { const o = {}; for (const [k, s] of Object.entries({ cena: '.aa-single__price, [class*=price]' })) { const e = document.querySelector(s); if (e) { const r = e.getBoundingClientRect(); o[k] = { x: r.x, y: r.y + scrollY, w: r.width, h: r.height, cls: e.className }; } } const c = [...document.querySelectorAll('a,button')].find(e => e.offsetParent && /Chcę to auto/.test(e.innerText)); if (c) { const r = c.getBoundingClientRect(); o.chce = { x: r.x, y: r.y + scrollY, w: r.width, h: r.height, href: c.href || '' }; } const z = [...document.querySelectorAll('*')].find(e => e.children.length === 0 && /Cena zawiera cło/.test(e.textContent)); if (z) { const r = z.getBoundingClientRect(); o.zawiera = { x: r.x, y: r.y + scrollY, w: r.width, h: r.height }; } return o; }))); },
  async zgloszenie() { await idz(AUTO); const href = await p.evaluate(() => { const c = [...document.querySelectorAll('a')].find(e => /Chcę to auto/.test(e.innerText)); return c && c.href; });
    console.log('zgloszenie href', href); if (href) { await idz(href); fs.writeFileSync(`${OUT}/zgloszenie-poz.json`, JSON.stringify(await p.evaluate(() => { const o = {}; const z = [...document.querySelectorAll('div,p,section')].filter(e => /nie jest zamówieniem/.test(e.innerText)).pop(); if (z) { const r = z.getBoundingClientRect(); o.nota = { x: r.x, y: r.y + scrollY, w: r.width, h: r.height }; } const w = [...document.querySelectorAll('button')].find(e => /Wyślij zgłoszenie/.test(e.innerText)); if (w) { const r = w.getBoundingClientRect(); o.wyslij = { x: r.x, y: r.y + scrollY, w: r.width, h: r.height }; } return o; }))); await shot('zgloszenie-pelna', { clip: { x: 0, y: 0, width: 390, height: 2400 }, captureBeyondViewport: true }); } },
  async depozyt() { await idz(`${B}/depozyt/`); await shot('depozyt-pelna', { clip: { x: 0, y: 0, width: 390, height: 2000 }, captureBeyondViewport: true }); },
  async porownanie() { await idz(`${B}/porownywarka/denza-z9-dm-i-1100km-4wd-max-vs-denza-z9-gt-dm-i-1100-4wd-ultra/`);
    await shot('porownanie-pelna', { clip: { x: 0, y: 0, width: 390, height: 2400 }, captureBeyondViewport: true }); },
};
for (const [n, f] of Object.entries(sceny)) { if (TYLKO.length && !TYLKO.includes(n)) continue; try { await f(); } catch (e) { console.log('BLAD', n, e.message); } }
await b.close();
