#!/usr/bin/env node
/**
 * autohome-specid-szukaj.js — znajduje `specid` Autohome dla oferty po pełnej chińskiej nazwie wersji.
 *
 * Po co: oferty dongchedi nie mają `_asiaauto_spec_id`, więc katalog Autohome (autohome-catalog-fetch.js
 * + autohome-catalog-merge.php) nie ma do czego się podpiąć. Ręcznie to 3 requesty na wersję
 * (memory reference_autohome_specid_recznie_dla_dongchedi) — ten skrypt robi to samo automatem:
 *
 *   1. indeks modeli: menu marek Autohome (GBK) → {seriesid, nazwa}; cache `_series-index.json`;
 *   2. model oferty: część nazwy przed „YYYY款" ↔ nazwa modelu w indeksie;
 *   3. wersje modelu: config/series/<id>.html (w sprzedaży; nazwy częściowo zaciemnione — span
 *      traktujemy jako wieloznacznik) + www.autohome.com.cn/<id>/sale.html (wycofane, nazwy czyste);
 *      cache `_series-<id>.json`;
 *   4. wersja: ten sam rocznik i nazwa równa (`dokladne`) albo jedna nazwa zawiera drugą jako
 *      podciąg znaków i kandydat jest jeden (`przyblizone`). Wieloznaczne i brak → bez specid.
 *
 * Skrypt NICZEGO nie zapisuje w bazie — wypisuje TSV do przeglądu. Zapis robi dalej
 * autohome-catalog-merge.php.
 *
 * Użycie:  node autohome-specid-szukaj.js <wejscie.tsv> [katalog-cache]
 *          wejście: `post_id<TAB>nazwa CN` w każdej linii
 *          wyjście (stdout): post_id, specid|-, typ, seriesid|-, nazwa Autohome, nazwa oferty
 *
 * @since 2026-10-04
 */
'use strict';

const https = require('https');
const http = require('http');
const fs = require('fs');
const path = require('path');

const IN = process.argv[2];
const CACHE = process.argv[3] || '/home/host476470/domains/primaauto.com.pl/public_html/wp-content/uploads/asiaauto/autohome-catalog/_lookup';
if (!IN) { console.error('Użycie: node autohome-specid-szukaj.js <wejscie.tsv> [katalog-cache]'); process.exit(1); }
fs.mkdirSync(CACHE, { recursive: true });

const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';
// Cache wygasa: nowe modele (indeks) i nowe wersje (listy modeli) muszą kiedyś wejść w nocnym biegu.
const DZIEN = 86400000;
const swiezy = (f, dni) => fs.existsSync(f) && Date.now() - fs.statSync(f).mtimeMs < dni * DZIEN;
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function get(url, enc = 'utf8') {
  try { return await get1(url, enc); } catch (e) { await sleep(3000); return get1(url, enc); }   // jedno ponowienie (timeouty sale.html)
}

function get1(url, enc = 'utf8') {
  return new Promise((resolve, reject) => {
    const lib = url.startsWith('https') ? https : http;
    const req = lib.get(url, { headers: { 'User-Agent': UA, 'Accept-Language': 'zh-CN,zh;q=0.9' }, timeout: 30000 }, (res) => {
      if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
        res.resume(); return resolve(get1(new URL(res.headers.location, url).href, enc));
      }
      if (res.statusCode !== 200) { res.resume(); return reject(new Error('HTTP ' + res.statusCode + ' ' + url)); }
      const chunks = [];
      res.on('data', (c) => chunks.push(c));
      res.on('end', () => resolve(new TextDecoder(enc).decode(Buffer.concat(chunks))));
    });
    req.on('timeout', () => req.destroy(new Error('timeout ' + url)));
    req.on('error', reject);
  });
}

/** Dongchedi → Autohome dla nazwy modelu (pomiar 2026-10-04: ZEEKR 001/009, 奕派008 = eπ008). */
const ALIASY = [[/^ZEEKR\s*/i, '极氪'], [/^奕派(008)/, 'eπ$1']];

/** Normalizacja nazw: bez spacji, małe litery, pełnoszerokie nawiasy → zwykłe. */
const norm = (s) => String(s || '').replace(/[（]/g, '(').replace(/[）]/g, ')').replace(/[\s　·•\-_]+/g, '').toLowerCase();
/** „红旗HS5 2025款 2.0T xxx" → ['红旗HS5', '2025', '2.0T xxx'] */
const split = (s) => { const m = String(s).match(/^(.*?)\s*(20\d\d)款\s*(.*)$/); return m ? [m[1], m[2], m[3]] : null; };

/** Czy `a` jest podciągiem znaków `b` (zachowana kolejność). */
function subseq(a, b) {
  let i = 0;
  for (const ch of b) if (ch === a[i]) i++;
  return i === a.length;
}

/** Skład znaków bez „版/型" i wieloznaczników — „Ultra 四驱纯电版" ≡ „Ultra 纯电·四驱". */
const bag = (s) => [...s.replace(/[\u0001版型]/g, '')].sort().join('');

/** Wycina zbalansowany obiekt JSON po `var <name> = {` (jak w autohome-catalog-fetch.js). */
function grabJson(html, varName) {
  const at = html.indexOf('var ' + varName + ' = {');
  if (at < 0) return null;
  const start = html.indexOf('{', at);
  let depth = 0, inStr = false, esc = false;
  for (let i = start; i < html.length; i++) {
    const c = html[i];
    if (inStr) { if (esc) esc = false; else if (c === '\\') esc = true; else if (c === '"') inStr = false; continue; }
    if (c === '"') inStr = true;
    else if (c === '{') depth++;
    else if (c === '}' && --depth === 0) { try { return JSON.parse(html.slice(start, i + 1)); } catch (e) { return null; } }
  }
  return null;
}

async function seriesIndex() {
  const f = path.join(CACHE, '_series-index.json');
  if (swiezy(f, 30)) return JSON.parse(fs.readFileSync(f, 'utf8'));
  const brands = [...(await get('https://car.autohome.com.cn/AsLeftMenu/As_LeftListNew.ashx?typeId=1&brandId=0&fctId=0&seriesId=0', 'gbk'))
    .matchAll(/id='b(\d+)'>/g)].map((m) => m[1]);
  const idx = {};
  for (const b of brands) {
    try {
      const t = await get(`https://car.autohome.com.cn/AsLeftMenu/As_LeftListNew.ashx?typeId=2&brandId=${b}&fctId=0&seriesId=0`, 'gbk');
      for (const m of t.matchAll(/series_(\d+)'[^>]*>([^<]+)<em>/g)) idx[m[1]] = m[2].trim();
    } catch (e) { console.error('menu marki ' + b + ': ' + e.message); }
    await sleep(300);
  }
  fs.writeFileSync(f, JSON.stringify(idx));
  console.error(`indeks modeli: ${Object.keys(idx).length} z ${brands.length} marek`);
  return idx;
}

async function seriesVersions(sid) {
  const f = path.join(CACHE, `_series-${sid}.json`);
  if (swiezy(f, 7)) return JSON.parse(fs.readFileSync(f, 'utf8'));
  const out = [];
  try {
    const html = await get(`https://car.autohome.com.cn/config/series/${sid}.html`);
    const c = grabJson(html, 'config');
    const first = c && c.result && c.result.paramtypeitems && c.result.paramtypeitems[0];
    const row = first && first.paramitems && first.paramitems[0];
    for (const v of (row && row.valueitems) || []) {
      // span = zaciemniony fragment (1–2 znaki); zostawiamy znacznik \u0000 → wieloznacznik w dopasowaniu
      const name = String(v.value || '').replace(/<span[^>]*><\/span>/g, '\u0000').replace(/&nbsp;/g, ' ').trim();
      out.push({ specid: String(v.specid), name, zrodlo: 'sprzedaz' });
    }
  } catch (e) { console.error(`model ${sid} (sprzedaż): ${e.message}`); }
  await sleep(500);
  try {
    const html = await get(`https://www.autohome.com.cn/${sid}/sale.html`, 'gbk');
    for (const m of html.matchAll(/<a title='([^']*)' href='\/\/www\.autohome\.com\.cn\/spec\/(\d+)\/'/g)) {
      if (!out.some((o) => o.specid === m[2])) out.push({ specid: m[2], name: m[1].trim(), zrodlo: 'wycofane' });
    }
  } catch (e) { console.error(`model ${sid} (wycofane): ${e.message}`); }
  await sleep(500);
  fs.writeFileSync(f, JSON.stringify(out));
  return out;
}

/** Wersja z nazwy Autohome: część od „YYYY款"; zaciemnione znaki → wieloznacznik. */
function versionMatch(year, verNorm, list, extra = '') {
  const exact = [], approx = [], same = [];
  for (const v of list) {
    const p = split(v.name.replace(/\u0000/g, '\u0001'));
    if (!p || p[1] !== year) continue;
    const a = norm(p[2]);
    const hasWild = a.includes('\u0001');
    const re = hasWild ? new RegExp('^' + a.split('\u0001').map((x) => x.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('.{1,3}') + '$') : null;
    if (a === verNorm || (re && re.test(verNorm))) exact.push(v);
    else {
      const aClean = a.replace(/\u0001/g, '');
      if (bag(a) === bag(verNorm)) same.push(v);
      else if (subseq(verNorm, aClean) || subseq(aClean, verNorm)) approx.push(v);
    }
  }
  // Dopisek z nazwy modelu oferty („腾势D9 DM" vs model „腾势D9") rozstrzyga remis: DM-i vs EV.
  const pick = (arr) => (arr.length > 1 && extra ? arr.filter((v) => norm(v.name).includes(extra)) : arr);
  for (const [arr, typ] of [[exact, 'dokladne'], [same, 'sklad-znakow'], [approx, 'przyblizone']]) {
    if (!arr.length) continue;
    const a = pick(arr);
    if (a.length === 1) return [a[0], arr.length > 1 ? typ + '+dopisek' : typ];
    return [null, 'wiele:' + arr.map((v) => v.specid).join(',')];
  }
  return [null, 'brak-wersji'];
}

/** Model: dokładna nazwa, potem jedna zawiera drugą (najdłuższa, jeśli jednoznaczna). */
function seriesMatch(serNorm, idx) {
  const all = Object.entries(idx).map(([id, n]) => [id, norm(n), n]);
  const exact = all.filter(([, n]) => n === serNorm);
  if (exact.length === 1) return exact[0];
  if (exact.length > 1) return null;
  const part = all.filter(([, n]) => n.length >= 2 && (serNorm.includes(n) || n.includes(serNorm)));
  if (!part.length) return null;
  part.sort((x, y) => Math.abs(x[1].length - serNorm.length) - Math.abs(y[1].length - serNorm.length));
  if (part.length > 1 && Math.abs(part[0][1].length - serNorm.length) === Math.abs(part[1][1].length - serNorm.length)) return null;
  return part[0];
}

(async () => {
  const idx = await seriesIndex();
  const lines = fs.readFileSync(IN, 'utf8').split('\n').filter(Boolean);
  for (const l of lines) {
    const [postId, name] = l.split('\t');
    const p = split(name || '');
    if (!p) { console.log([postId, '-', 'zla-nazwa', '-', '', name].join('\t')); continue; }
    // Warianty nazwy modelu: jak w ofercie, bez dopisku napędu, z „新能源" (唐DM → 唐新能源).
    // Aliasy marek: dongchedi pisze łacinką albo nową nazwą, Autohome trzyma chińską / starą.
    for (const [re, to] of ALIASY) p[0] = p[0].replace(re, to);
    const base = p[0].replace(/\s*(DM-i|DM-p|DM|EV|PHEV|EREV|增程|纯电)$/i, '');
    const tries = [...new Set([p[0], base + '新能源', base])];
    let s = null, v = null, typ = 'brak-modelu';
    for (const t of tries) {
      const s1 = seriesMatch(norm(t), idx);
      if (!s1) continue;
      const extra = norm(p[0]).replace(s1[1], '');
      const versions = await seriesVersions(s1[0]);
      let [v1, t1] = versionMatch(p[1], norm(p[2]), versions, extra);
      // Rok modelowy dongchedi bywa przesunięty o rok względem Autohome — tylko dokładna nazwa / skład.
      if (!v1 && t1 === 'brak-wersji') {
        for (const y of [String(+p[1] + 1), String(+p[1] - 1)]) {
          const [v2, t2] = versionMatch(y, norm(p[2]), versions, extra);
          if (v2 && /^(dokladne|sklad-znakow)/.test(t2)) { v1 = v2; t1 = t2 + ',rok' + (y > p[1] ? '+1' : '-1'); break; }
        }
      }
      if (!s || v1) { s = s1; v = v1; typ = t1; }
      if (v1) break;
    }
    if (!s) { console.log([postId, '-', 'brak-modelu', '-', '', name].join('\t')); continue; }
    console.log([postId, v ? v.specid : '-', typ, s[0], v ? `${s[2]} ${v.name.replace(/\u0000/g, '·')}` : s[2], name].join('\t'));
  }
})();
