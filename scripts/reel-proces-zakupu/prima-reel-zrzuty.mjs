// T-265: zrzuty ekranow primaauto.com.pl w widoku telefonu do filmu/karuzeli procesu zakupu.
// pptr prima-reel-zrzuty.mjs <katalog_wyjscia> <auto.json> [tylko=nazwa,nazwa]
// auto.json: { "url": "https://primaauto.com.pl/oferta/<slug>-<id>/", "zdjecia": [1,3,5] } — jedyne miejsce, gdzie wskazuje sie auto.
// Wynik: zrzuty PNG + auto-N.<ext> (zdjecia z ogloszenia) + dane.js (window.D: nazwa, cena, marka, pozycje klikniec z DOM).
import puppeteer from 'puppeteer-core';
import fs from 'fs';
const OUT = process.argv[2]; const CFG = JSON.parse(fs.readFileSync(process.argv[3], 'utf8'));
const TYLKO = (process.argv[4] || '').replace('tylko=', '').split(',').filter(Boolean);
fs.mkdirSync(OUT, { recursive: true });
const B = 'https://primaauto.com.pl';
const AUTO = CFG.url;
const D = fs.existsSync(`${OUT}/dane.json`) ? JSON.parse(fs.readFileSync(`${OUT}/dane.json`, 'utf8')) : {};
const zapiszD = () => { fs.writeFileSync(`${OUT}/dane.json`, JSON.stringify(D, null, 1)); fs.writeFileSync(`${OUT}/dane.js`, `window.D = ${JSON.stringify(D)};\n`); };
const CSS = `#cmplz-cookiebanner-container,.cmplz-cookiebanner,#cmplz-manage-consent,.pa-news,.grecaptcha-badge{display:none!important}`;
const sleep = ms => new Promise(r => setTimeout(r, ms));
const b = await puppeteer.launch({ executablePath: process.env.CHROME_BIN, headless: true,
  args: ['--no-sandbox','--disable-dev-shm-usage','--disable-gpu','--no-zygote','--single-process'] });
const p = await b.newPage();
await p.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 3 });
const idz = async u => { await p.goto(u, { waitUntil: 'networkidle2', timeout: 90000 }); await p.addStyleTag({ content: CSS }); await sleep(1500); };
const DLUGI = `.aa-mobile-cta,.pa-dock,[class*=cmp-dock],[class*=compare-dock]{display:none!important}`;
const shot = async (n, opt = {}) => { if (opt.clip) await p.addStyleTag({ content: DLUGI }); await p.screenshot({ path: `${OUT}/${n}.png`, ...opt }); console.log('zrzut', n); };
const r2 = r => r && ({ x: Math.round(r.x), y: Math.round(r.y), w: Math.round(r.w), h: Math.round(r.h) });
// element widoczny, ktorego tekst zaczyna sie od t — prostokat w ukladzie ekranu (bez przewijania)
const znajdz = (sel, t) => p.evaluate((s, t) => { const e = [...document.querySelectorAll(s)].filter(x => x.offsetParent && x.innerText.trim().startsWith(t)).pop(); if (!e) return null; const r = e.getBoundingClientRect(); if (r.bottom > innerHeight || r.top < 0) { e.scrollIntoView({ block: 'center' }); } const q = e.getBoundingClientRect(); return { x: q.x, y: q.y, w: q.width, h: q.height }; }, sel, t);
const klikXY = async r => { await p.mouse.click(r.x + r.w / 2, r.y + r.h / 2); await sleep(1200); };
const klik = async sel => { await p.$eval(sel, e => { e.scrollIntoView({ block: 'center' }); e.click(); }).catch(() => console.log('BRAK', sel)); await sleep(1200); };
const pozycja = async sel => p.$eval(sel, e => { const r = e.getBoundingClientRect(); return { x: r.x, y: r.y, w: r.width, h: r.height }; }).catch(() => null);
// Karta wybranego auta na górze listy — lista sortuje po dacie, więc bez tego na zrzucie byłoby dowolne najnowsze auto.
// Prawdziwa karta z endpointu listy (asiaauto/v1/listings, marka+model z linku powrotu na stronie oferty).
let KARTA = null;
const kartaAuta = async () => {
  if (KARTA !== null) return KARTA;
  const id = AUTO.match(/-(\d+)\/?$/)[1];
  const hub = await (await b.newPage()).goto(AUTO, { waitUntil: 'domcontentloaded', timeout: 90000 }).then(r => r.text());
  const m = hub.match(/href="[^"]*\/samochody\/([a-z0-9-]+)\/([a-z0-9-]+)\/#oferty"/);
  KARTA = '';
  for (let s = 1; m && s <= 10 && !KARTA; s++) {
    const j = await p.evaluate(async u => (await fetch(u)).json(), `${B}/wp-json/asiaauto/v1/listings?marka=${m[1]}&model=${m[2]}&strona=${s}`);
    const html = j.html || '';
    const k = html.split('<article class="aa-card"').find(x => x.includes(`-${id}/`));
    if (k) KARTA = '<article class="aa-card"' + k.slice(0, k.lastIndexOf('</article>') + 10);
    if (s >= (j.pages || 1)) break;
  }
  console.log(KARTA ? 'karta auta OK' : 'BRAK karty auta');
  return KARTA;
};
const wstawKarte = async () => {
  const k = await kartaAuta(); if (!k) return;
  await p.evaluate(async (k, id) => {
    const first = document.querySelector('.aa-card'); if (!first) return;
    [...document.querySelectorAll('.aa-card')].filter(c => c.querySelector(`a[href*="-${id}/"]`)).forEach(c => c.remove());
    first.insertAdjacentHTML('beforebegin', k);
    const nowa = document.querySelector('.aa-card');
    for (const i of nowa.querySelectorAll('img')) { i.loading = 'eager'; if (i.dataset.src) i.src = i.dataset.src; await i.decode().catch(() => 0); }
  }, k, AUTO.match(/-(\d+)\/?$/)[1]);
  await sleep(800);
};
const sceny = {
  async dane() { await idz(AUTO);
    Object.assign(D, await p.evaluate(() => {
      const t = s => (document.querySelector(s) || {}).innerText?.trim() || '';
      const ld = [...document.querySelectorAll('script[type="application/ld+json"]')].map(s => { try { return JSON.parse(s.textContent); } catch { return {}; } });
      const car = ld.flatMap(x => x['@graph'] || [x]).find(x => /Car|Vehicle|Product/.test(x['@type'])) || {};
      const cena = (document.querySelector('.aa-single__price-box')?.innerText.match(/[\d\s]{5,}(?=\s*(PLN|zł))/) || [''])[0].trim().replace(/\s+/g, ' ');
      return { nazwa: t('h1').split(' - ')[0].replace(/\s\d{4}$/, ''), marka: car.brand?.name || car.brand || '', rok: String(car.vehicleModelDate || car.modelDate || ''), cena: cena ? cena + ' zł' : '' };
    }));
    // zdjecia z galerii: <baza>-<id>-<n>.<ext>
    const baza = await p.evaluate(() => { const m = (document.querySelector('meta[property="og:image"]')?.content || '').match(/^(.*)-\d+(\.\w+)$/); return m && [m[1], m[2]]; });
    if (!baza) console.log('BRAK galerii'); else {
      D.zdj = [];
      for (const n of CFG.zdjecia || [1, 3, 5]) {
        const u = `${baza[0]}-${n}${baza[1]}`, plik = `auto-${n}${baza[1]}`;
        const b64 = await p.evaluate(async u => { const r = await fetch(u); const a = new Uint8Array(await r.arrayBuffer()); let s = ''; for (const c of a) s += String.fromCharCode(c); return btoa(s); }, u);
        fs.writeFileSync(`${OUT}/${plik}`, Buffer.from(b64, 'base64')); D.zdj.push(plik); console.log('zdjecie', u);
      }
    }
    if (CFG.marka) D.marka = CFG.marka; if (CFG.nazwa) D.nazwa = CFG.nazwa;
    zapiszD(); console.log('dane', JSON.stringify(D)); },
  async lista() { await idz(`${B}/samochody/`); await wstawKarte(); await shot('lista-ekran'); await shot('lista-pelna', { clip: { x: 0, y: 0, width: 390, height: 2600 }, captureBeyondViewport: true }); },
  async filtr() {
    const marka = D.marka || CFG.marka; if (!marka) throw new Error('brak marki — uruchom najpierw scene dane');
    await idz(`${B}/samochody/`); await wstawKarte();
    D.fToggle = r2(await pozycja('.aa-inv__filter-toggle'));
    await klik('.aa-inv__filter-toggle'); await shot('filtr-1-panel');
    D.fMarka = r2(await p.$$eval('.aa-filter__trigger', e => { const r = e[0].getBoundingClientRect(); return { x: r.x, y: r.y, w: r.width, h: r.height }; }));
    await (await p.$$('.aa-filter__trigger'))[0].click(); await sleep(1200);
    // lista marek przewija się — wpisujemy markę w „Szukaj…”, żeby opcja była na ekranie
    D.fSzukaj = r2(await p.evaluate(() => { const e = [...document.querySelectorAll('input')].find(x => x.offsetParent && /Szukaj/i.test(x.placeholder)); if (!e) return null; const r = e.getBoundingClientRect(); return { x: r.x, y: r.y, w: r.width, h: r.height }; }));
    if (D.fSzukaj) { await p.mouse.click(D.fSzukaj.x + 20, D.fSzukaj.y + D.fSzukaj.h / 2); await p.keyboard.type(marka, { delay: 60 }); await sleep(900); }
    const opcja = await znajdz('label', marka); await sleep(400);
    if (!opcja) console.log('BRAK marki', marka);
    await shot('filtr-2-marki'); D.fOpcja = r2(opcja);
    await klikXY(opcja); await shot('filtr-3-denza');
    D.fApply = r2(await pozycja('.aa-inv__sidebar-apply'));
    D.fApplyTxt = await p.$eval('.aa-inv__sidebar-apply', e => e.innerText.trim()).catch(() => '');
    await klik('.aa-inv__sidebar-apply'); await sleep(1500); await wstawKarte(); await shot('filtr-4-wynik');
    zapiszD();
  },
  async wyszukiwarka() { await idz(`${B}/wyszukiwarka/`); await shot('wyszuk-pelna', { clip: { x: 0, y: 0, width: 390, height: 2200 }, captureBeyondViewport: true }); },
  async specyfikacja() { await idz(AUTO);
    await shot('spec-pelna', { clip: { x: 0, y: 0, width: 390, height: 7600 }, captureBeyondViewport: true }); },
  async oferta() { await idz(AUTO); await shot('oferta-ekran');
    Object.assign(D, await p.evaluate(() => {
      const o = {}, rr = e => { const r = e.getBoundingClientRect(); return { x: Math.round(r.x), y: Math.round(r.y), w: Math.round(r.width), h: Math.round(r.height) }; };
      // kwota ceny: najwęższy element w boksie ceny, który zawiera liczbę z „PLN/zł”
      // sama kwota „314 000 PLN” (bez „z VAT”) — prostokąt pierwszego węzła tekstu
      const pr = document.querySelector('.aa-single__price');
      if (pr) { const rg = document.createRange(); rg.selectNodeContents(pr.firstChild); const r = rg.getBoundingClientRect(); o.oCena = { x: Math.round(r.x), y: Math.round(r.y), w: Math.round(r.width), h: Math.round(r.height) }; }
      // „Chcę to auto” widoczne na pierwszym ekranie (dolny pasek telefonu)
      const c = [...document.querySelectorAll('a,button')].filter(e => e.offsetParent && /Chcę to auto/.test(e.innerText)).map(e => [e, e.getBoundingClientRect()]).find(([, r]) => r.top >= 0 && r.bottom <= innerHeight);
      if (c) o.oChce = rr(c[0]);
      return o;
    }));
    zapiszD(); },
  async zgloszenie() { await idz(AUTO); const href = await p.evaluate(() => { const c = [...document.querySelectorAll('a')].find(e => /Chcę to auto/.test(e.innerText)); return c && c.href; });
    console.log('zgloszenie href', href); if (!href) return; await idz(href);
    Object.assign(D, await p.evaluate(() => { const o = { zPola: {}, zZgody: [] }; const rr = e => { const r = e.getBoundingClientRect(); return { x: Math.round(r.x), y: Math.round(r.y + scrollY), w: Math.round(r.width), h: Math.round(r.height) }; };
      for (const n of ['first_name', 'last_name', 'email', 'phone']) { const e = document.querySelector(`[name=${n}]`); if (e) o.zPola[n] = rr(e); }
      o.zZgody = [...document.querySelectorAll('input[type=checkbox]')].filter(e => e.offsetParent).map(rr);
      const w = [...document.querySelectorAll('button')].find(e => /Wyślij zgłoszenie/.test(e.innerText)); if (w) o.zWyslij = rr(w);
      return o; }));
    zapiszD();
    await shot('zgloszenie-pelna', { clip: { x: 0, y: 0, width: 390, height: 2400 }, captureBeyondViewport: true }); },
  async depozyt() { await idz(`${B}/depozyt/`); await shot('depozyt-pelna', { clip: { x: 0, y: 0, width: 390, height: 2000 }, captureBeyondViewport: true }); },
};
for (const [n, f] of Object.entries(sceny)) { if (TYLKO.length && !TYLKO.includes(n)) continue; try { await f(); } catch (e) { console.log('BLAD', n, e.message); } }
await b.close();
