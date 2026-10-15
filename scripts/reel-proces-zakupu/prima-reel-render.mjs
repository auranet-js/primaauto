// T-265: render kompozycji reel.html klatka po klatce (deterministycznie) na Mikrusie.
// pptr prima-reel-render.mjs <reel.html> <katalog_wyjscia> reel [fps] [od-do]   -> klatki JPG 1080x1920
// pptr prima-reel-render.mjs <reel.html> <katalog_wyjscia> karuzela             -> slajdy PNG 1080x1350
// pptr prima-reel-render.mjs <reel.html> <katalog_wyjscia> klatki 3.2,7.5       -> pojedyncze klatki QA (reel)
// pptr prima-reel-render.mjs <reel.html> <katalog_wyjscia> nakladka [fps] od-do   -> przezroczyste PNG sceny końcowej (na klip AI)
import puppeteer from 'puppeteer-core';
import fs from 'fs';
const [HTML, OUT, TRYB = 'reel', A4, A5] = process.argv.slice(2);
fs.mkdirSync(OUT, { recursive: true });
const karuzela = TRYB === 'karuzela', nakl = TRYB === 'nakladka';
const b = await puppeteer.launch({ executablePath: process.env.CHROME_BIN, headless: true,
  args: ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu', '--no-zygote', '--single-process', '--allow-file-access-from-files'] });
const p = await b.newPage();
await p.setViewport({ width: 1080, height: karuzela ? 1350 : 1920, deviceScaleFactor: 1 });
await p.goto(`file://${HTML}?mode=${karuzela ? 'karuzela' : 'reel'}${nakl ? '&nakladka=1' : ''}`, { waitUntil: 'load' });
await p.evaluate(() => window.gotowe);
const dur = await p.evaluate(() => window.DUR);
const kadr = async (t, plik, typ = 'jpeg') => {
  await p.evaluate(t => window.render(t), t);
  await p.screenshot({ path: plik, type: typ, ...(typ === 'jpeg' ? { quality: 92 } : { omitBackground: nakl }) });
};
const t0 = Date.now();
if (karuzela) {
  const still = await p.evaluate(() => window.STILL);
  let i = 0;
  for (const [id, t] of Object.entries(still)) await kadr(t, `${OUT}/slajd-${String(++i).padStart(2, '0')}-${id}.png`, 'png');
  console.log('slajdy', i);
} else if (TRYB === 'klatki') {
  for (const t of A4.split(',').map(Number)) await kadr(t, `${OUT}/qa-${t.toFixed(2)}.jpg`);
} else {
  const fps = Number(A4 || 30);
  const [od, doo] = (A5 || `0-${dur}`).split('-').map(Number);
  const n0 = Math.round(od * fps), n1 = Math.round(doo * fps);
  for (let n = n0; n < n1; n++) {
    await kadr(n / fps, `${OUT}/f${String(n).padStart(5, '0')}.${nakl ? 'png' : 'jpg'}`, nakl ? 'png' : 'jpeg');
    if (n % 150 === 0) console.log('klatka', n, '/', n1, Math.round((Date.now() - t0) / 1000) + 's');
  }
  console.log('klatek', n1 - n0, 'fps', fps, 'czas', Math.round((Date.now() - t0) / 1000) + 's');
}
await b.close();
