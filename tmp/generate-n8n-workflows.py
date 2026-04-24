#!/usr/bin/env python3
"""
Generator 2 workflow n8n dla content pipeline Prima-Auto:
- primaauto-make-desc  (hub marki)
- primaauto-serie-desc (hub modelu)

Output: tmp/primaauto-make-desc.json + tmp/primaauto-serie-desc.json
"""

import json
import uuid
from pathlib import Path

HERE = Path(__file__).parent
WP_BASE = "https://primaauto.com.pl/wp-json/asiaauto/v1"
ANTHROPIC_URL = "https://api.anthropic.com/v1/messages"

# Sekrety — wczytywane tutaj, wklejane do JSON workflow
HUB_TOKEN = (Path.home() / "secrets/primaauto/hub-token.txt").read_text().strip()
ANTHROPIC_KEY = (Path.home() / "secrets/anthropic/api-key.txt").read_text().strip()

MODEL_ID = "claude-sonnet-4-5"  # Sonnet 4.6 (API id: claude-sonnet-4-5 w Anthropic nomenclature)

SYSTEM_PROMPT_MAKE = """You are a Polish SEO content writer specializing in Chinese automobile brands.
Generate hub content (in Polish) for a Chinese car brand archive page on Prima-Auto (primaauto.com.pl).

Respond with PURE JSON only, no markdown code blocks, no preamble. Structure:
{
  "wiki_body": "<HTML string>",
  "faq_json": [{"q": "...", "a": "..."}, ...],
  "seo_desc": "<140-160 char string>"
}

### KONTEKST PRIMA-AUTO (WAŻNE — użyj w treści):
- Prima-Auto to polska agencja importu samochodów z Chin, mająca zespół stacjonarny w południowych Chinach (Kanton, Shenzhen, Foshan, Dongguan, Xiamen, Fuzhou, Nanning, Haikou i inne miasta prowincji Guangdong/Fujian/Guangxi/Hainan).
- **Każdy pojazd jest oglądany fizycznie przez naszego człowieka na miejscu** przed trafieniem do oferty.
- **USP: oferujemy głównie samochody praktycznie nowe — egzemplarze demo, poekspozycyjne lub używane o niewielkich przebiegach (typowo 500–30 000 km), często rocznikowe świeże (aktualny rok lub rok wstecz). To NIE są klasyczne "używane" auta z długim przebiegiem.**
- **Ceny w ofertach na stronie są KOŃCOWE** — zawierają cenę pojazdu + transport morski + cło + VAT + homologację indywidualną + rejestrację + obsługę dokumentacyjną. Klient nie dopłaca żadnych "ukrytych" kwot. NIE pisz nigdy kwot typu „dodatkowe 15-20k za import" — to jest NIEPRAWDA i wprowadza w błąd.
- Proces zamawiania: (1) rezerwacja z depozytem zwrotnym na stronie, (2) weryfikacja przez zespół w Chinach, (3) transport morski ~4-6 tygodni, (4) odprawa w PL, (5) homologacja indywidualna w stacji diagnostycznej, (6) rejestracja, (7) odbiór przez klienta w Rzeszowie lub transport dedykowany. Okres całkowity: typowo 8-12 tygodni.
- Model agencyjny: Prima-Auto działa jako agent — w umowie widnieje rola kupującego, co oznacza pełną przejrzystość i bezpieczeństwo prawne.

### wiki_body (HTML):
- Pierwszy paragraf (100-150 słów): intro — kim jest marka, kiedy powstała, pozycja w CN, profil produktu (EV/PHEV/ICE, SUV/sedan/minivan, segment). **Zakończ dokładnie znacznikiem {{LISTINGS_BAR}} zaraz po zamykającym </p> pierwszego paragrafu (nic więcej, żadnego prefiksu/sufiksu wokół tego tagu).**
- Kolejne sekcje H2 (w tej kolejności, wszystkie obowiązkowe):
  1. "Technologia i cechy szczególne [nazwa marki]" — platformy, napędy, technologie baterii/hybrydy, systemy asysty. Używaj faktów z DATA (fuel_breakdown, segment).
  2. JEŚLI has_aliases=true w DATA: "Sub-marki i powiązane marki [nazwa]" — wyjaśnij relację CN→EU dla każdej grupy z DATA.aliases (np. „Fangchengbao to off-roadowa sub-marka BYD — modele Leopard 3/5/7/8 w rynku CN mają oznaczenie Fangchengbao, u nas występują pod marką nadrzędną BYD"). Podaj konkretne modele z alias.models.
  3. "Import [marki] przez Prima-Auto" — opisz NASZ proces (7 kroków z KONTEKSTU), podkreśl że ceny są końcowe, akcent na „auta praktycznie nowe, nie klasyczne używane", średni przebieg z DATA.mileage_range_km (jeśli count>0).
  4. "[marka] w Europie — porównanie" — zestaw marki z najbliższym odpowiednikiem EU (np. BYD ↔ VW/Tesla; Voyah ↔ BMW premium EV; AITO ↔ Mercedes GLC/GLS). Co zyskuje klient wybierając CN? (cena, wyposażenie, nowa technologia).
- Dopuszczalne elementy HTML: `<p>`, `<h2>`, `<h3>`, `<ul>`, `<li>`, `<strong>`, `<em>`, `<a href="/samochody/?marka=...">`, `<table>`, `<thead>`, `<tbody>`, `<tr>`, `<th>`, `<td>`. Bez `<h1>`, bez CSS classes, bez `<h2>Najczęściej zadawane pytania</h2>` ani żadnej sekcji FAQ (FAQ jest renderowane osobno z pola faq_json).
- **NIE generuj sekcji "Modele [marki] dostępne w Prima-Auto"** — ta sekcja renderuje się automatycznie jako klikalne pigułki z liczbami ofert. Twoje opisy modeli byłyby duplikatem.
- Długość wiki_body: ~600-900 słów.

### faq_json (6-8 pytań):
Mix tematów (wykorzystaj DATA):
- Techniczne: zasięg, moc, ładowanie EV, konkretne modele
- Proceduralne (bazując na KONTEKŚCIE): "Ile trwa sprowadzenie [marki] z Chin do Polski?" → 8-12 tygodni; "Czy cena na stronie jest ostateczna?" → tak, zawiera wszystko; "Jak wygląda homologacja?" → indywidualna przez Prima-Auto w stacji diagnostycznej
- Porównawcze: "[marka] vs BMW/Mercedes/Audi?"
- O przebiegach/stanie: "Czy to używane auta?" — NIE, głównie demo i poekspozycyjne, przebiegi 500-30 tys. km, często świeży rocznik.
- Każda odpowiedź: 40-80 słów, konkretne liczby z DATA tam gdzie pasują.

### seo_desc:
140-160 znaków, zawiera: nazwa marki + "Prima-Auto" + liczba ofert + krótki profil (np. "premium EV SUV z Chin"). Przykład formatu: "Voyah w Prima-Auto — 18 ofert premium SUV i minivanów BEV/PHEV z Chin. Ceny od X PLN, homologacja wliczona."

### STYL:
- Merytoryczny, autentyczny. Pierwsza osoba liczba mnoga z perspektywy Prima-Auto („W naszej ofercie...", „Nasz zespół w Chinach...").
- Używaj realnych liczb z DATA (total_listings, price_range_pln, year_range, mileage_range_km, fuel_breakdown). NIE zmyślaj spec. technicznych (moc, zasięg) ponad to co jest w DATA — dla konkretnych modeli lepiej odesłać do karty modelu zamiast zgadywać.
- ZAKAZANE frazy: „Warto wspomnieć", „Bez wątpienia", „W dzisiejszym dynamicznym świecie", „Niezrównana jakość", „game-changer", „nieszablonowy", „rewolucyjny", „przełomowy", „innowacyjne rozwiązania", „bezkompromisowy".
- Bez emoji, bez markdown code blocks w outpucie JSON.

### KRYTYCZNE — poprawność JSON:
- **NIGDY nie używaj znaku " wewnątrz wartości stringowych JSON.** Nie pisz `12.3"` ani `ekran 15"`. Zamiast tego pisz słownie: `12,3 cala`, `ekran 15-calowy`. Dla cytatów w treści używaj polskich cudzysłowów „ ” albo apostrofów ' '.
- Jeśli musisz użyć cytatów w HTML wiki_body dla atrybutów — używaj pojedynczych: `<a href='/samochody/?marka=byd'>`, NIE podwójnych.
- Zwracasz jeden płaski obiekt JSON, zero prefiksu/sufiksu, zero ```json wrappera."""

SYSTEM_PROMPT_SERIE = """You are a Polish SEO content writer specializing in specific Chinese car models.
Generate hub content (in Polish) for a specific Chinese car model archive page on Prima-Auto (primaauto.com.pl).

Respond with PURE JSON only, no markdown code blocks, no preamble. Structure:
{
  "wiki_body": "<HTML string>",
  "faq_json": [{"q": "...", "a": "..."}, ...],
  "seo_desc": "<140-160 char string>"
}

### KONTEKST PRIMA-AUTO:
- Polska agencja importu CN, zespół na miejscu (Guangdong/Fujian/Guangxi/Hainan), każdy pojazd oglądany fizycznie.
- **USP: auta praktycznie nowe** — demo/poekspozycyjne, przebiegi 500-30k km, rocznik aktualny/rok wstecz. **NIE klasyczne używane.**
- **Ceny końcowe na stronie** (pojazd + transport + cło + VAT + homologacja + rejestracja). Bez dopłat. NIE pisz „+15-20k za import".
- Proces: 8-12 tyg (rezerwacja + depozyt → weryfikacja w CN → transport morski 4-6 tyg → odprawa PL → homologacja indywidualna → rejestracja → odbiór w Rzeszowie).

### DOSTĘPNE DANE (w DATA):
- `specs.length_mm/width_mm/height_mm/wheelbase_mm/curb_weight_kg` — wymiary i waga (z oferty chińskiej)
- `specs.range_wltc_km` — zasięg WLTC/NEDC dla EV/EREV/PHEV
- `specs.range_total_km` — łączny zasięg (dla EREV/PHEV z silnikiem benzynowym)
- `specs.battery_kwh`, `specs.acceleration_s`, `specs.max_speed_kmh`, `specs.engine_capacity_l`, `specs.engine_hp`, `specs.electric_hp`
- `body_breakdown` (SUV/sedan/hatchback), `drive_breakdown` (AWD/RWD/FWD), `fuel_breakdown` (EREV/EV/PHEV)
- `features_standard` — lista cech z PL labelem (kamera 360, keyless, ambient, masaż foteli itd.) + liczba listingów które mają dane feature
- `cn_refs` (z aliases) — jeśli model ma CN alias/sub-brand (np. BYD Leopard 5 = Fangchengbao / Denza B5)

### wiki_body (HTML):
- **Paragraf 1 (100-130 słów)**: intro o modelu. Co to za auto (typ nadwozia z body_breakdown, segment), dla kogo, **pozycjonowanie względem segmentu EU** — porównaj długość z konkretnym modelem EU (np. „Voyah FREE przy 4,9m długości lokuje się w segmencie SUV klasy średniej-wyższej, wymiary zbliżone do BMW X5 (4,93m) czy Audi Q7 (5,06m)"). Jeśli DATA.cn_refs zawiera alias sub-brandowy — wspomnij w tym paragrafie („W chińskich materiałach marketingowych model występuje także pod nazwą [cn_brand] [cn_model]"). **Zakończ {{LISTINGS_BAR}} zaraz po </p>.**

- **Sekcja H2 „Specyfikacja [full_title] — dane techniczne"** — OBOWIĄZKOWA TABELKA HTML `<table>` z AT NAJMNIEJ 10 WIERSZAMI. Używaj WSZYSTKICH dostępnych danych z `specs` i `*_breakdown`. Przykładowa struktura:
```html
<table>
<thead><tr><th>Parametr</th><th>Wartość</th></tr></thead>
<tbody>
<tr><td>Typ nadwozia</td><td>SUV (5-drzwiowy)</td></tr>
<tr><td>Napęd</td><td>AWD 3 szt / RWD 1 szt</td></tr>
<tr><td>Paliwo</td><td>EREV (elektryczny z range extenderem)</td></tr>
<tr><td>Długość × szerokość × wysokość</td><td>4905 × 1950 × 1645 mm</td></tr>
<tr><td>Rozstaw osi</td><td>2960 mm</td></tr>
<tr><td>Masa własna</td><td>2150-2270 kg</td></tr>
<tr><td>Moc układu (elektryczna)</td><td>272-490 KM</td></tr>
<tr><td>Przyspieszenie 0-100 km/h</td><td>4-7 s</td></tr>
<tr><td>Pojemność baterii</td><td>39-43 kWh</td></tr>
<tr><td>Zasięg EV (WLTC)</td><td>160-235 km</td></tr>
<tr><td>Zasięg całkowity</td><td>1201-1458 km</td></tr>
<tr><td>Rocznik</td><td>2024-2025</td></tr>
<tr><td>Przebieg w naszych egzemplarzach</td><td>20 000-48 900 km</td></tr>
<tr><td>Zakres cen w Prima-Auto</td><td>141 000 - 171 000 PLN</td></tr>
</tbody>
</table>
```
Po tabelce **krótki komentarz 2-3 zdania** — co wynika z liczb (np. „Przy długości blisko 5m Voyah FREE oferuje więcej miejsca niż BMW iX, przy wadze porównywalnej").

- **Sekcja H2 „Wyposażenie w egzemplarzach Prima-Auto"** — lista `<ul><li>` z PL-labelami features_standard (użyj DATA.features_standard, features które mają 100% pokrycie = w każdym listingu). TOP 8-12 cech. Rozdzielić na dwie kategorie jeśli są optional: „W każdym egzemplarzu" i „W niektórych egzemplarzach" (features_optional). Przykład listy: „Kamera 360°, bezkluczykowy dostęp, adaptacyjny tempomat pełnozakresowy, asystent pasa ruchu, automatyczne parkowanie, oświetlenie ambientowe, podgrzewane / wentylowane / masujące fotele przednie, HUD, ładowarka indukcyjna, zawieszenie pneumatyczne" (dostosuj do rzeczywistych features).

- **Sekcja H2 „[full_title] vs europejskie odpowiedniki"** — 2-3 konkretne porównania z modelami EU. Podaj typowe ceny EU (przybliżone, np. „BMW X5 xDrive45e startuje od ~380 tys. PLN w PL"). Tabelka porównawcza jest OK. Fokus na to co klient zyskuje wybierając CN (lista cech których EU nie daje w tej cenie).

- **Sekcja H2 „[full_title] w Prima-Auto — czego się spodziewać"** — realny stan ofert: przebiegi (DATA.mileage_range_km), roczniki, że to demo/poekspozycyjne, nie klasyczne używane. Wspomnij proces weryfikacji w CN (nasz zespół ogląda fizycznie).

- **Sekcja H2 „Import [full_title] przez Prima-Auto"** — proces 8-12 tyg, cena końcowa, homologacja wliczona. Link do filtra: np. `<a href='/samochody/?marka=<make_slug>&model=<serie_slug>'>Wszystkie egzemplarze [full_title]</a>`. Plus link do filtra paliwa: np. `<a href='/samochody/?marka=<make_slug>&paliwo=erev'>Wszystkie EREV [make_name]</a>` lub `paliwo=electric` dla EV. Filtr paliwa używa slugów: `petrol/diesel/electric/erev/hybrid/phev`. Filtr napędu: `naped=awd/rwd/fwd`. Używaj APOSTROFÓW w atrybutach HTML, nie `"` (żeby nie zepsuć JSON).

- Dopuszczalne HTML: `<p>`, `<h2>`, `<h3>`, `<ul>`, `<li>`, `<strong>`, `<em>`, `<a href>`, `<table>`, `<thead>`, `<tbody>`, `<tr>`, `<th>`, `<td>`. Bez `<h1>`, bez klas CSS, **bez sekcji FAQ w wiki_body**.
- Długość wiki_body: ~700-1000 słów.

### faq_json (6-8 pytań):
Mix tematów, wszystkie oparte na DATA:
- Techniczne: „Jaki zasięg elektryczny Voyah FREE w wersjach Prima-Auto?" (konkretne liczby z range_wltc_km)
- Wyposażenie: „Czy [model] w Prima-Auto ma kamerę 360° standardowo?" (sprawdź features_standard)
- Wymiary/praktyczne: „Czy [model] zmieści się w typowym polskim garażu?" (długość + szerokość)
- Porównawcze: „[model] vs [konkurent EU]?"
- Proceduralne (ceny końcowe, 8-12 tyg, demo auta)
- Każda odpowiedź: 40-80 słów, konkretne liczby.

### seo_desc:
140-160 znaków, full_title + „Prima-Auto" + liczba ofert + najkluczowa cecha + cena od.

### STYL:
- Merytoryczny, pierwsza osoba liczba mnoga Prima-Auto.
- **Używaj wyłącznie liczb z DATA.** NIE zmyślaj spec. (zasięg, 0-100, pojemność baterii) jeśli ich nie ma.
- ZAKAZANE frazy: „Warto wspomnieć", „Bez wątpienia", „W dzisiejszym dynamicznym świecie", „Niezrównana jakość", „game-changer", „rewolucyjny", „przełomowy", „innowacyjne rozwiązania".
- Bez emoji, bez markdown.

### KRYTYCZNE — poprawność JSON:
- **NIGDY nie używaj znaku " wewnątrz wartości stringowych JSON.** Zamiast `ekran 12.3"` → `12,3 cala`. Zamiast cytatów "..." → polskie „..." lub apostrofy '...'.
- Atrybuty HTML w wiki_body: pojedyncze apostrofy `<a href='/samochody/?marka=voyah&model=free'>`.
- Zwracasz jeden obiekt JSON, zero prefiksu/sufiksu, zero markdown wrappera."""


def nid():
    return str(uuid.uuid4())


def http_get(name, url_expr, pos, next_name=None):
    return {
        "parameters": {
            "url": url_expr,
            "options": {"response": {"response": {"responseFormat": "json"}}},
        },
        "type": "n8n-nodes-base.httpRequest",
        "typeVersion": 4.2,
        "position": pos,
        "id": nid(),
        "name": name,
    }


def build_make_workflow():
    webhook_path = "primaauto-make-desc"

    n_webhook = {
        "parameters": {
            "httpMethod": "POST",
            "path": webhook_path,
            "responseMode": "responseNode",
            "options": {},
        },
        "type": "n8n-nodes-base.webhook",
        "typeVersion": 2,
        "position": [240, 300],
        "id": nid(),
        "name": "Webhook",
        "webhookId": nid(),
    }

    n_facts = http_get(
        "Fetch Facts",
        f"={WP_BASE}/facts-for-make/{{{{ $json.body.make_slug }}}}",
        [460, 180],
    )
    n_latest = http_get(
        "Fetch Latest Listings",
        f"={WP_BASE}/latest-by-make/{{{{ $('Webhook').item.json.body.make_slug }}}}?n=6",
        [460, 300],
    )
    n_aliases = http_get(
        "Fetch Aliases",
        f"={WP_BASE}/aliases-for-make/{{{{ $('Webhook').item.json.body.make_slug }}}}",
        [460, 420],
    )

    n_merge = {
        "parameters": {
            "mode": "combine",
            "combineBy": "combineByPosition",
            "options": {},
        },
        "type": "n8n-nodes-base.merge",
        "typeVersion": 3,
        "position": [680, 300],
        "id": nid(),
        "name": "Merge Context",
    }

    # System prompt jako JS string literal (JSON.stringify robi escape)
    sys_prompt_js = json.dumps(SYSTEM_PROMPT_MAKE)

    build_prompt_code = f"""
const facts = $('Fetch Facts').first().json;
const latest = $('Fetch Latest Listings').first().json;
const aliases = $('Fetch Aliases').first().json;

const data = {{
  make_slug: facts.make_slug,
  make_name: facts.make_name,
  total_listings: facts.total_listings,
  models_count: facts.models_count,
  top3_models: facts.top3_models,
  price_range_pln: facts.price_range_pln,
  year_range: facts.year_range,
  segment_eu: facts.segment_eu || null,
  has_aliases: !!(aliases && aliases.has_aliases),
  aliases: aliases && aliases.groups ? aliases.groups : [],
  latest_titles: Array.isArray(latest) ? latest.map(l => l.title).slice(0, 6) : []
}};

const userMessage = `DATA:\\n${{JSON.stringify(data, null, 2)}}\\n\\nGenerate hub content per instructions.`;

const anthropicBody = {{
  model: {json.dumps(MODEL_ID)},
  max_tokens: 8000,
  system: [{{ type: 'text', text: {sys_prompt_js}, cache_control: {{ type: 'ephemeral' }} }}],
  messages: [{{ role: 'user', content: userMessage }}]
}};

return [{{
  json: {{
    data,
    user_message: userMessage,
    anthropic_body: anthropicBody
  }}
}}];
""".strip()

    n_build_prompt = {
        "parameters": {
            "jsCode": build_prompt_code,
        },
        "type": "n8n-nodes-base.code",
        "typeVersion": 2,
        "position": [900, 300],
        "id": nid(),
        "name": "Build Prompt",
    }

    n_anthropic = {
        "parameters": {
            "method": "POST",
            "url": ANTHROPIC_URL,
            "sendHeaders": True,
            "headerParameters": {
                "parameters": [
                    {"name": "x-api-key", "value": ANTHROPIC_KEY},
                    {"name": "anthropic-version", "value": "2023-06-01"},
                    {"name": "content-type", "value": "application/json"},
                ]
            },
            "sendBody": True,
            "specifyBody": "json",
            "jsonBody": "={{ JSON.stringify($json.anthropic_body) }}",
            "options": {"timeout": 120000},
        },
        "type": "n8n-nodes-base.httpRequest",
        "typeVersion": 4.2,
        "position": [1120, 300],
        "id": nid(),
        "name": "Claude Sonnet 4.6",
    }

    parse_code = """
const resp = $json;
const text = (resp.content && resp.content[0] && resp.content[0].text) || '';
let parsed;
try {
  parsed = JSON.parse(text);
} catch (e) {
  // Próba wycięcia JSON z ewentualnego prefiksu/sufiksu
  const m = text.match(/\\{[\\s\\S]*\\}/);
  if (m) parsed = JSON.parse(m[0]);
  else throw new Error('Nie udało się sparsować JSON z odpowiedzi Claude: ' + text.slice(0, 200));
}

// Fact-check (proste reguły)
const facts = $('Fetch Facts').first().json;
let body = String(parsed.wiki_body || '');

// Post-process 1: usuń sekcję FAQ jeśli Claude ją wstawił w wiki_body mimo instrukcji
body = body.replace(/<h2[^>]*>\s*(Najcz[eę][sś]ciej zadawane|Najcz[eę]stsze pytania|Cz[eę]sto zadawane|FAQ|Pytania i odpowiedzi)[\s\S]*$/i, '').trim();

// Post-process 2: usuń sekcję "Modele ... dostępne" jeśli Claude wstawił (template renderuje pigułki osobno)
body = body.replace(/<h2[^>]*>\s*Modele\s+[A-Za-z][\w\s\-&'.]{0,40}\s+dost[eę]pne[\s\S]*?(?=<h2|$)/i, '').trim();

parsed.wiki_body = body;

const forbidden = ['Warto wspomnieć', 'Bez wątpienia', 'game-changer', 'nieszablonow', 'Niezrównana jakość', 'rewolucyjn', 'innowacyjne rozwiązania', 'bezkompromisow'];
const hits = forbidden.filter(p => body.toLowerCase().includes(p.toLowerCase()));
if (hits.length > 0) parsed.lint_warnings = hits;

if (!body.includes('{{LISTINGS_BAR}}')) {
  parsed.lint_warnings = (parsed.lint_warnings || []).concat(['Brak placeholder {{LISTINGS_BAR}}']);
}

return [{
  json: {
    term_id: facts.make_slug,
    payload: parsed,
    make_slug: facts.make_slug,
    make_name: facts.make_name,
    prompt_version: 'make-v2-2026-04-23'
  }
}];
""".strip()

    n_parse = {
        "parameters": {"jsCode": parse_code},
        "type": "n8n-nodes-base.code",
        "typeVersion": 2,
        "position": [1340, 300],
        "id": nid(),
        "name": "Parse + Lint",
    }

    # term_id pobierany z Fetch Facts (`asiaauto/v1/facts-for-make` od 0.31.12 zwraca term_id)
    # — zero extra fetch, zawsze właściwy term (parent-aware resolution po stronie WP)

    n_save = {
        "parameters": {
            "method": "POST",
            "url": "=https://primaauto.com.pl/wp-json/asiaauto/v1/hub-content/make/{{ $('Fetch Facts').first().json.term_id }}",
            "sendHeaders": True,
            "headerParameters": {
                "parameters": [
                    {"name": "X-AsiaAuto-Token", "value": HUB_TOKEN},
                    {"name": "Content-Type", "value": "application/json"},
                ]
            },
            "sendBody": True,
            "specifyBody": "json",
            "jsonBody": "={{ JSON.stringify({ wiki_body: $('Parse + Lint').item.json.payload.wiki_body, faq_json: $('Parse + Lint').item.json.payload.faq_json, seo_desc: $('Parse + Lint').item.json.payload.seo_desc, prompt_version: $('Parse + Lint').item.json.prompt_version }) }}",
            "options": {},
        },
        "type": "n8n-nodes-base.httpRequest",
        "typeVersion": 4.2,
        "position": [1780, 300],
        "id": nid(),
        "name": "Save to WP",
    }

    n_respond = {
        "parameters": {
            "respondWith": "json",
            "responseBody": "={{ JSON.stringify({ ok: true, make_slug: $('Parse + Lint').item.json.make_slug, saved: $('Save to WP').item.json, lint_warnings: $('Parse + Lint').item.json.payload.lint_warnings || [] }) }}",
            "options": {},
        },
        "type": "n8n-nodes-base.respondToWebhook",
        "typeVersion": 1.1,
        "position": [2000, 300],
        "id": nid(),
        "name": "Respond",
    }

    nodes = [
        n_webhook, n_facts, n_latest, n_aliases, n_merge,
        n_build_prompt, n_anthropic, n_parse, n_save, n_respond,
    ]

    connections = {
        "Webhook": {"main": [[
            {"node": "Fetch Facts", "type": "main", "index": 0},
            {"node": "Fetch Latest Listings", "type": "main", "index": 0},
            {"node": "Fetch Aliases", "type": "main", "index": 0},
        ]]},
        "Fetch Facts": {"main": [[{"node": "Merge Context", "type": "main", "index": 0}]]},
        "Fetch Latest Listings": {"main": [[{"node": "Merge Context", "type": "main", "index": 1}]]},
        "Fetch Aliases": {"main": [[{"node": "Merge Context", "type": "main", "index": 2}]]},
        "Merge Context": {"main": [[{"node": "Build Prompt", "type": "main", "index": 0}]]},
        "Build Prompt": {"main": [[{"node": "Claude Sonnet 4.6", "type": "main", "index": 0}]]},
        "Claude Sonnet 4.6": {"main": [[{"node": "Parse + Lint", "type": "main", "index": 0}]]},
        "Parse + Lint": {"main": [[{"node": "Resolve Term ID", "type": "main", "index": 0}]]},
        "Resolve Term ID": {"main": [[{"node": "Save to WP", "type": "main", "index": 0}]]},
        "Save to WP": {"main": [[{"node": "Respond", "type": "main", "index": 0}]]},
    }

    return {
        "name": "primaauto-make-desc",
        "nodes": nodes,
        "connections": connections,
        "settings": {"executionOrder": "v1"},
    }


def build_serie_workflow():
    webhook_path = "primaauto-serie-desc"

    n_webhook = {
        "parameters": {
            "httpMethod": "POST",
            "path": webhook_path,
            "responseMode": "responseNode",
            "options": {},
        },
        "type": "n8n-nodes-base.webhook",
        "typeVersion": 2,
        "position": [240, 300],
        "id": nid(),
        "name": "Webhook",
        "webhookId": nid(),
    }

    n_facts = http_get(
        "Fetch Facts",
        f"={WP_BASE}/facts-for-serie/{{{{ $json.body.serie_slug }}}}?make={{{{ $json.body.make_slug }}}}",
        [460, 180],
    )
    n_latest = http_get(
        "Fetch Latest",
        f"={WP_BASE}/latest-by-serie/{{{{ $('Webhook').item.json.body.serie_slug }}}}?n=6&make={{{{ $('Webhook').item.json.body.make_slug }}}}",
        [460, 300],
    )
    n_aliases = http_get(
        "Fetch Aliases",
        f"={WP_BASE}/aliases-for-serie/{{{{ $('Webhook').item.json.body.serie_slug }}}}?make={{{{ $('Webhook').item.json.body.make_slug }}}}",
        [460, 420],
    )

    n_merge = {
        "parameters": {"mode": "combine", "combineBy": "combineByPosition", "options": {}},
        "type": "n8n-nodes-base.merge",
        "typeVersion": 3,
        "position": [680, 300],
        "id": nid(),
        "name": "Merge",
    }

    sys_prompt_js = json.dumps(SYSTEM_PROMPT_SERIE)

    build_prompt_code = f"""
const facts = $('Fetch Facts').first().json;
const latest = $('Fetch Latest').first().json;
const aliases = $('Fetch Aliases').first().json;

const data = {{
  serie_slug: facts.serie_slug,
  serie_name: facts.serie_name,
  full_title: facts.full_title,
  make_slug: facts.make_slug,
  make_name: facts.make_name,
  total_listings: facts.total_listings,
  price_range_pln: facts.price_range_pln,
  year_range: facts.year_range,
  mileage_range_km: facts.mileage_range_km,
  fuel_breakdown: facts.fuel_breakdown || [],
  drive_breakdown: facts.drive_breakdown || [],
  body_breakdown: facts.body_breakdown || [],
  specs: facts.specs || {{}},
  features_standard: facts.features_standard || [],
  features_optional: facts.features_optional || [],
  notable: facts.notable || {{}},
  cn_refs: aliases && aliases.cn_refs ? aliases.cn_refs : [],
  has_sub_brand_alias: !!(aliases && aliases.has_aliases),
  latest_titles: Array.isArray(latest) ? latest.map(l => l.title).slice(0, 6) : []
}};

const userMessage = `DATA:\\n${{JSON.stringify(data, null, 2)}}\\n\\nGenerate hub content per instructions.`;

const anthropicBody = {{
  model: {json.dumps(MODEL_ID)},
  max_tokens: 8000,
  system: [{{ type: 'text', text: {sys_prompt_js}, cache_control: {{ type: 'ephemeral' }} }}],
  messages: [{{ role: 'user', content: userMessage }}]
}};

return [{{
  json: {{
    data,
    user_message: userMessage,
    anthropic_body: anthropicBody
  }}
}}];
""".strip()

    n_build_prompt = {
        "parameters": {"jsCode": build_prompt_code},
        "type": "n8n-nodes-base.code",
        "typeVersion": 2,
        "position": [900, 300],
        "id": nid(),
        "name": "Build Prompt",
    }

    n_anthropic = {
        "parameters": {
            "method": "POST",
            "url": ANTHROPIC_URL,
            "sendHeaders": True,
            "headerParameters": {
                "parameters": [
                    {"name": "x-api-key", "value": ANTHROPIC_KEY},
                    {"name": "anthropic-version", "value": "2023-06-01"},
                    {"name": "content-type", "value": "application/json"},
                ]
            },
            "sendBody": True,
            "specifyBody": "json",
            "jsonBody": "={{ JSON.stringify($json.anthropic_body) }}",
            "options": {"timeout": 120000},
        },
        "type": "n8n-nodes-base.httpRequest",
        "typeVersion": 4.2,
        "position": [1120, 300],
        "id": nid(),
        "name": "Claude Sonnet 4.6",
    }

    parse_code = """
const resp = $json;
const text = (resp.content && resp.content[0] && resp.content[0].text) || '';
let parsed;
try { parsed = JSON.parse(text); }
catch(e) {
  const m = text.match(/\\{[\\s\\S]*\\}/);
  if (m) parsed = JSON.parse(m[0]);
  else throw new Error('Nie udało się sparsować JSON: ' + text.slice(0, 200));
}

const facts = $('Fetch Facts').first().json;
let body = String(parsed.wiki_body || '');

// Usuń sekcję FAQ jeśli Claude ją wstawił w wiki_body
body = body.replace(/<h2[^>]*>\s*(Najcz[eę][sś]ciej zadawane|Najcz[eę]stsze pytania|Cz[eę]sto zadawane|FAQ|Pytania i odpowiedzi)[\s\S]*$/i, '').trim();

parsed.wiki_body = body;

const forbidden = ['Warto wspomnieć', 'Bez wątpienia', 'game-changer', 'Niezrównana jakość', 'rewolucyjn', 'innowacyjne rozwiązania', 'bezkompromisow'];
const hits = forbidden.filter(p => body.toLowerCase().includes(p.toLowerCase()));
if (hits.length > 0) parsed.lint_warnings = hits;
if (!body.includes('{{LISTINGS_BAR}}')) parsed.lint_warnings = (parsed.lint_warnings || []).concat(['Brak placeholder']);

return [{
  json: {
    serie_slug: facts.serie_slug,
    make_slug: facts.make_slug,
    payload: parsed,
    prompt_version: 'serie-v2-2026-04-23'
  }
}];
""".strip()

    n_parse = {
        "parameters": {"jsCode": parse_code},
        "type": "n8n-nodes-base.code",
        "typeVersion": 2,
        "position": [1340, 300],
        "id": nid(),
        "name": "Parse + Lint",
    }

    # term_id pobierany z Fetch Facts — parent-aware (filtruje po make) od 0.31.12.
    n_save = {
        "parameters": {
            "method": "POST",
            "url": "=https://primaauto.com.pl/wp-json/asiaauto/v1/hub-content/serie/{{ $('Fetch Facts').first().json.term_id }}",
            "sendHeaders": True,
            "headerParameters": {
                "parameters": [
                    {"name": "X-AsiaAuto-Token", "value": HUB_TOKEN},
                    {"name": "Content-Type", "value": "application/json"},
                ]
            },
            "sendBody": True,
            "specifyBody": "json",
            "jsonBody": "={{ JSON.stringify({ wiki_body: $('Parse + Lint').item.json.payload.wiki_body, faq_json: $('Parse + Lint').item.json.payload.faq_json, seo_desc: $('Parse + Lint').item.json.payload.seo_desc, prompt_version: $('Parse + Lint').item.json.prompt_version }) }}",
            "options": {},
        },
        "type": "n8n-nodes-base.httpRequest",
        "typeVersion": 4.2,
        "position": [1780, 300],
        "id": nid(),
        "name": "Save to WP",
    }

    n_respond = {
        "parameters": {
            "respondWith": "json",
            "responseBody": "={{ JSON.stringify({ ok: true, serie_slug: $('Parse + Lint').item.json.serie_slug, make_slug: $('Parse + Lint').item.json.make_slug, saved: $('Save to WP').item.json, lint_warnings: $('Parse + Lint').item.json.payload.lint_warnings || [] }) }}",
            "options": {},
        },
        "type": "n8n-nodes-base.respondToWebhook",
        "typeVersion": 1.1,
        "position": [2000, 300],
        "id": nid(),
        "name": "Respond",
    }

    nodes = [
        n_webhook, n_facts, n_latest, n_aliases, n_merge,
        n_build_prompt, n_anthropic, n_parse, n_save, n_respond,
    ]

    connections = {
        "Webhook": {"main": [[
            {"node": "Fetch Facts", "type": "main", "index": 0},
            {"node": "Fetch Latest", "type": "main", "index": 0},
            {"node": "Fetch Aliases", "type": "main", "index": 0},
        ]]},
        "Fetch Facts": {"main": [[{"node": "Merge", "type": "main", "index": 0}]]},
        "Fetch Latest": {"main": [[{"node": "Merge", "type": "main", "index": 1}]]},
        "Fetch Aliases": {"main": [[{"node": "Merge", "type": "main", "index": 2}]]},
        "Merge": {"main": [[{"node": "Build Prompt", "type": "main", "index": 0}]]},
        "Build Prompt": {"main": [[{"node": "Claude Sonnet 4.6", "type": "main", "index": 0}]]},
        "Claude Sonnet 4.6": {"main": [[{"node": "Parse + Lint", "type": "main", "index": 0}]]},
        "Parse + Lint": {"main": [[{"node": "Save to WP", "type": "main", "index": 0}]]},
        "Save to WP": {"main": [[{"node": "Respond", "type": "main", "index": 0}]]},
    }

    return {
        "name": "primaauto-serie-desc",
        "nodes": nodes,
        "connections": connections,
        "settings": {"executionOrder": "v1"},
    }


def main():
    out_make = HERE / "primaauto-make-desc.json"
    out_serie = HERE / "primaauto-serie-desc.json"

    out_make.write_text(json.dumps(build_make_workflow(), indent=2, ensure_ascii=False))
    out_serie.write_text(json.dumps(build_serie_workflow(), indent=2, ensure_ascii=False))

    print(f"✔ Wygenerowano: {out_make}")
    print(f"✔ Wygenerowano: {out_serie}")


if __name__ == "__main__":
    main()
