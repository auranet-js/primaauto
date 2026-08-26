/**
 * Przemiar dostepnosci przy waskiej rzutni (320 px) — axe + reflow.
 *
 * PO CO: probka audytowa (17 adresow) jest mierzona przy 1366 px i PRZEPUSZCZA
 * naruszenia widoczne tylko na telefonie: kontrast tekstu, ktory przy mniejszym
 * foncie wpada z progu 3:1 na 4,5:1, oraz tabele rozpychajace strone w poziomie.
 * 26.08.2026 ten skrypt znalazl 27 takich pozycji, ktorych przemiar desktopowy nie widzial.
 *
 * Uzycie (node_modules z axe-core i puppeteer-core siedzi w ~/projekty/auranet/scripts):
 *   cd ~/projekty/auranet/scripts
 *   cp ~/projekty/primaauto/docs/dostepnosc/dowody-2026-08-26/probka-79-adresy.txt tmp-probka.txt
 *   CHROME_BIN=~/.cache/ms-playwright/chromium-1223/chrome-linux64/chrome \
 *     node ~/projekty/primaauto/scripts/a11y-przemiar-320.mjs
 *
 * UWAGA: kazdy adres w osobnym try/catch i protocolTimeout 420 s — bez tego jeden
 * ciezki `axe.run` wywala caly przebieg (zaliczone 26.08).
 */
import puppeteer from 'puppeteer-core';
import fs from 'fs';
const AXE = fs.readFileSync(new URL('./node_modules/axe-core/axe.min.js', import.meta.url), 'utf8');
const URLE = fs.readFileSync('./tmp-probka.txt','utf8').split('\n').map(s=>s.trim()).filter(Boolean);
const b = await puppeteer.launch({ executablePath: process.env.CHROME_BIN, protocolTimeout: 420000,
  args: ['--no-sandbox','--disable-dev-shm-usage'] });
const zbior={}; const szcz=[]; let bledy=0, refl=0, zbadane=0;
for (const [i,u] of URLE.entries()) {
  let p;
  try {
    p = await b.newPage(); await p.setViewport({width:320,height:720});
    await p.goto(u,{waitUntil:'domcontentloaded',timeout:60000});
    await new Promise(r=>setTimeout(r,2400));
    const sw = await p.evaluate(()=>document.documentElement.scrollWidth);
    if (sw > 320) { refl++; szcz.push(u.replace('https://primaauto.com.pl','')+' | REFLOW sw='+sw); }
    await p.evaluate(AXE);
    const v = await p.evaluate(async () => {
      const res = await axe.run(document, { runOnly: ['wcag2a','wcag2aa','wcag21a','wcag21aa','wcag22aa'] });
      return res.violations.map(x=>({id:x.id,n:x.nodes.length,cel:x.nodes[0]?.target?.[0]||'',msg:(x.nodes[0]?.any?.[0]?.message||'').slice(0,100)}));
    });
    for (const x of v) { zbior[x.id]=(zbior[x.id]||0)+x.n; szcz.push(u.replace('https://primaauto.com.pl','')+' | '+x.id+' ×'+x.n+' | '+x.cel+' | '+x.msg); }
    zbadane++;
  } catch (e) {
    bledy++; szcz.push(u.replace('https://primaauto.com.pl','')+' | NIE ZBADANO: '+String(e).split('\n')[0].slice(0,90));
  } finally { try { await p?.close(); } catch {} }
  process.stdout.write((i+1)+' ');
}
await b.close();
console.log('\n\nadresy: '+URLE.length+', zbadane: '+zbadane+', nieudane: '+bledy+', reflow>320: '+refl);
console.log('axe @320px: ' + (Object.keys(zbior).length ? JSON.stringify(zbior) : 'ZERO naruszeń'));
console.log(szcz.length ? szcz.join('\n') : 'brak uwag');
