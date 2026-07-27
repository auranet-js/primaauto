#!/usr/bin/env node
/**
 * autohome-catalog-fetch.js — pobiera i DEKODUJE stronę konfiguracji z katalogu Autohome.
 *
 * Po co: auto-api dla che168 oddaje 6-7 grup technicznych (~100 parametrów) i zero wyposażenia.
 * Katalog Autohome dla tego samego `specid` ma 23 grupy / ~290 parametrów — z fotelami,
 * bezpieczeństwem, asystentami i multimediami. To jedyne źródło wyposażenia dla ofert che168
 * bez bliźniaka w dongchedi (47,5% wg pomiaru 2026-07-27).
 *
 * Antyscraping i jak go zdejmujemy:
 *   ~46% nazw parametrów i ~4% wartości jest podmienione na <span class='hs_kwNN_xxx'></span>,
 *   a numer i sufiks klasy są LOSOWE przy każdym żądaniu (configbc/configmy, optionIA/optionaj/…),
 *   więc słownika znaków nie da się zbudować raz. Deszyfratorem jest zaciemniony inline-JS, który
 *   w przeglądarce dopisuje reguły `.hs_kwNN_xxx::before { content:"znak" }`. Skrypt podmienia
 *   `getComputedStyle` (obrona przed odczytem z automatyzacji), ale samego generatora reguł nie
 *   chroni — wystarczy uruchomić te bloki ze stubem DOM i przechwycić, co wstawiają do arkusza.
 *   Efekt: 292 parametry, zero nieodszyfrowanych znaków. Bez przeglądarki i bez headless Chrome.
 *
 * WAŻNE: ID parametrów w katalogu to INNA przestrzeń niż ID auto-api (1 wspólny ID na ~270,
 * i to z inną semantyką). Most do naszych kluczy idzie po NAZWIE CN — patrz
 * `wp-content/plugins/asiaauto-sync/data/autohome-catalog-map.php`.
 *
 * Użycie:
 *   node scripts/autohome-catalog-fetch.js <specid> [plik-wyjsciowy.json]
 *
 * Wyjście (JSON): [{"space":"config|option","id":123,"group":"座椅配置","name":"座椅材质","value":"真皮"}]
 * Kod wyjścia: 0 OK, 1 błąd pobrania/parsowania, 2 dane niekompletne (zostały nieodszyfrowane znaki).
 *
 * @since 2026-07-27 (T-186)
 */
'use strict';

const https = require('https');
const fs = require('fs');
const vm = require('vm');

const SPECID = process.argv[2];
const OUT = process.argv[3] || '';
if (!SPECID || !/^\d+$/.test(SPECID)) {
  console.error('Użycie: node autohome-catalog-fetch.js <specid> [plik.json]');
  process.exit(1);
}

const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

function fetchPage(specid) {
  return new Promise((resolve, reject) => {
    const req = https.get(
      `https://car.autohome.com.cn/config/spec/${specid}.html`,
      { headers: { 'User-Agent': UA, 'Accept-Language': 'zh-CN,zh;q=0.9' }, timeout: 30000 },
      (res) => {
        if (res.statusCode !== 200) { res.resume(); return reject(new Error('HTTP ' + res.statusCode)); }
        let body = '';
        res.setEncoding('utf8');
        res.on('data', (c) => { body += c; });
        res.on('end', () => resolve(body));
      }
    );
    req.on('timeout', () => { req.destroy(new Error('timeout')); });
    req.on('error', reject);
  });
}

/** Wycina zbalansowany obiekt JSON po `var <name> = {` (świadomy stringów i escape'ów). */
function grabJson(html, varName) {
  const at = html.indexOf('var ' + varName + ' = {');
  if (at < 0) return null;
  const start = html.indexOf('{', at);
  let depth = 0, inStr = false, esc = false;
  for (let i = start; i < html.length; i++) {
    const c = html[i];
    if (inStr) {
      if (esc) esc = false;
      else if (c === '\\') esc = true;
      else if (c === '"') inStr = false;
      continue;
    }
    if (c === '"') inStr = true;
    else if (c === '{') depth++;
    else if (c === '}' && --depth === 0) {
      try { return JSON.parse(html.slice(start, i + 1)); } catch (e) { return null; }
    }
  }
  return null;
}

/** Uruchamia bloki deszyfrujące ze stubem DOM i zwraca mapę { hs_kwNN_xxx: 'znaki' }. */
function decodeKeywordMap(html, specid) {
  const map = {};
  const blocks = [];
  const re = /<script>(\(function\(\w+_\)[\s\S]*?)<\/script>/g;
  let m;
  while ((m = re.exec(html)) !== null) {
    if (m[1].includes('hs_') || m[1].includes('kw')) blocks.push(m[1]);
  }
  for (const code of blocks) {
    const captured = [];
    const mkEl = (tag) => ({
      tagName: String(tag || '').toUpperCase(), style: {}, children: [], attributes: {},
      setAttribute(k, v) { this.attributes[k] = v; },
      getAttribute(k) { return this.attributes[k]; },
      appendChild(c) { this.children.push(c); if (typeof c === 'string') captured.push(c); if (c && c._tc) captured.push(c._tc); return c; },
      insertBefore(c) { return this.appendChild(c); },
      set innerHTML(v) { captured.push(v); }, get innerHTML() { return ''; },
      set textContent(v) { this._tc = v; captured.push(v); }, get textContent() { return this._tc || ''; },
      sheet: { cssRules: [], insertRule(r) { captured.push(r); return 0; }, addRule(s, r) { captured.push(s + '{' + r + '}'); return 0; } },
      styleSheet: { set cssText(v) { captured.push(v); }, get cssText() { return ''; } },
    });
    const head = mkEl('head'), body = mkEl('body');
    const document = {
      head, body, documentElement: mkEl('html'), styleSheets: [],
      createElement: mkEl,
      createTextNode: (t) => { captured.push(t); return { textContent: t, _tc: t }; },
      getElementsByTagName: (t) => (t === 'head' ? [head] : [body]),
      querySelector: () => null, querySelectorAll: () => [], getElementById: () => null,
      write(s) { captured.push(s); }, addEventListener() {}, cookie: '',
    };
    const win = {
      document,
      navigator: { userAgent: UA },
      location: { href: `https://car.autohome.com.cn/config/spec/${specid}.html`, host: 'car.autohome.com.cn' },
      screen: { width: 1920, height: 1080 },
      getComputedStyle: () => ({ getPropertyValue: () => '' }),
      hs_fuckyou: () => ({ getPropertyValue: () => '' }),
      addEventListener() {},
      decodeURIComponent, encodeURIComponent, escape, unescape, String, parseInt,
    };
    // Nieznane właściwości window → no-op, żeby zaciemniony kod nie wywracał się na feature-detekcji.
    const winProxy = new Proxy(win, {
      get(t, k) { if (typeof k === 'string' && !(k in t)) return function () {}; return t[k]; },
      has() { return true; },
    });
    const sandbox = { window: winProxy, document, navigator: win.navigator, location: win.location };
    sandbox.window.eval = (s) => vm.runInNewContext(s, sandbox);
    try {
      vm.createContext(sandbox);
      vm.runInContext(code, sandbox, { timeout: 5000 });
    } catch (e) {
      // Blok, który padł, po prostu nie wnosi reguł — reszta bloków może wystarczyć.
    }
    const rules = captured.join('\n').match(/hs_kw\d+_\w+::before\s*\{\s*content:"[^"]*"\s*\}/g) || [];
    for (const r of rules) {
      const mm = r.match(/(hs_kw\d+_\w+)::before\s*\{\s*content:"([^"]*)"/);
      if (mm) map[mm[1]] = mm[2];
    }
  }
  return map;
}

(async () => {
  let html;
  try {
    html = await fetchPage(SPECID);
  } catch (e) {
    console.error('BŁĄD pobrania specid ' + SPECID + ': ' + e.message);
    process.exit(1);
  }

  const kw = decodeKeywordMap(html, SPECID);
  const SPAN = /<span class='(hs_kw\d+_\w+)'><\/span>/g;
  const dec = (s) => String(s || '').replace(SPAN, (_, cls) => (cls in kw ? kw[cls] : '□')).replace(/&nbsp;/g, ' ').trim();

  const rows = [];
  for (const [varName, gKey, iKey] of [['config', 'paramtypeitems', 'paramitems'], ['option', 'configtypeitems', 'configitems']]) {
    const src = grabJson(html, varName);
    if (!src || !src.result || !src.result[gKey]) continue;
    for (const g of src.result[gKey]) {
      for (const it of (g[iKey] || [])) {
        // Strona porównuje kilka wersji — bierzemy wartość TYLKO dla naszego specid.
        const v = (it.valueitems || []).find((x) => String(x.specid) === String(SPECID));
        rows.push({
          space: varName,
          id: typeof it.id === 'number' ? it.id : null,
          group: dec(g.name),
          name: dec(it.name),
          value: dec(v ? v.value : ''),
        });
      }
    }
  }

  if (!rows.length) {
    console.error('BŁĄD: nie znaleziono danych konfiguracji (zmiana formatu strony?)');
    process.exit(1);
  }

  const left = rows.filter((r) => r.name.includes('□') || r.value.includes('□')).length;
  const json = JSON.stringify(rows, null, 1);
  if (OUT) fs.writeFileSync(OUT, json); else process.stdout.write(json);
  console.error(`specid ${SPECID}: ${rows.length} parametrów, ${new Set(rows.map(r => r.group)).size} grup, ` +
    `${Object.keys(kw).length} odszyfrowanych klas, nieodszyfrowanych pozycji: ${left}`);
  process.exit(left ? 2 : 0);
})();
