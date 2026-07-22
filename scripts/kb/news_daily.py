#!/usr/bin/env python3
"""
Codzienny pipeline newsowy działu wiedzy (T-214 F1/F3).

Etapy: radar RSS -> dedup -> selekcja (Claude) -> research (pełny artykuł
źródłowy + kontekst naszej oferty + kurs CNY) -> draft PL (Claude) ->
weryfikacja faktów (Claude, drugi przebieg) -> lint -> publikacja w WP ->
mail informacyjny do Janka z linkami do opublikowanych newsów (decyzja
Janka 2026-07-22: bez akceptacji mailem — publikacja od razu, on zgłasza
uwagi post factum jeśli coś się rzuci w oczy).

Użycie:
  python3 news_daily.py --dry-run          # tylko radar + selekcja, bez generowania
  python3 news_daily.py --limit 2          # pełny bieg (default 2 newsy)
  python3 news_daily.py --no-mail          # bez wysyłki maila (debug)
Kill-switch: plik scripts/kb/state/DISABLED zatrzymuje bieg.
"""
import argparse
import datetime as dt
import email.utils
import json
import re
import sys
import xml.etree.ElementTree as ET
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import kb_lib as kb
from make_cover import make_cover

FEEDS = [
    ("CarNewsChina", "https://carnewschina.com/feed/"),
    ("CNEVPost", "https://cnevpost.com/feed/"),
    ("electrive", "https://www.electrive.com/feed/"),
    ("InsideEVs", "https://insideevs.com/rss/articles/all/"),
]
MAX_AGE_H = 36
SITE = "https://primaauto.com.pl"

SELECT_PROMPT = """Jesteś redaktorem prowadzącym polskiego serwisu o chińskiej motoryzacji (Prima-Auto — import aut z Chin).
Z listy kandydatów (newsy z ostatnich 36h z serwisów EN) wybierz {n} najlepsze tematy na dzisiejsze newsy.

Kryteria (w kolejności): (1) premiery/presale/ceny NOWYCH chińskich modeli, (2) marki obecne w Polsce lub w naszej ofercie: {makes}, (3) technologie (baterie, ładowanie, ADAS), (4) twarde dane rynkowe. Odrzucaj: USA-centryczne, opinie/felietony, tematy nie-chińskie, duplikaty tego samego wydarzenia (wybierz lepsze źródło).

JUŻ OPUBLIKOWALIŚMY (nie wybieraj tematów opisujących TO SAMO wydarzenie, nawet jeśli kandydat pochodzi z innego serwisu i ma inny tytuł):
{published}

Zwróć czysty JSON: {{"picks": [{{"idx": <numer kandydata>, "why": "1 zdanie"}}]}}"""

VERIFY_PROMPT = """Jesteś weryfikatorem faktów. Porównaj ARTYKUŁ z MATERIAŁEM ŹRÓDŁOWYM.
Sprawdź KAŻDĄ liczbę i fakt w artykule (ceny, moce, zasięgi, wymiary, daty, nazwy wersji):
- czy występuje w źródle lub jest z niego wprost przeliczalna (przeliczenie yuan->PLN po podanym kursie, zaokrąglone, ze słowem "około", jest OK),
- czy artykuł nie dopisuje faktów, których w źródle nie ma.
Zwróć czysty JSON: {"ok": true/false, "issues": ["konkretny problem 1", ...]}. Drobne zaokrąglenia i pominięcia to nie błąd; błędem jest liczba/fakt sprzeczny ze źródłem albo nieobecny w nim.
DOZWOLONE (nie zgłaszaj): końcowe zdanie o dostępności marki/modelu w ofercie Prima-Auto z linkiem — pochodzi z naszych danych, nie ze źródła; przeliczenia walut po podanym kursie; neutralne przeformułowania."""


def parse_feed(name, url):
    items = []
    try:
        raw = kb.http_get(url, as_text=True)
        root = ET.fromstring(raw.encode("utf-8"))
    except Exception as e:
        print(f"  [{name}] FEED ERROR: {e}", flush=True)
        return items
    for it in root.iter("item"):
        get = lambda tag: (it.findtext(tag) or "").strip()
        pub = get("pubDate")
        try:
            ts = email.utils.parsedate_to_datetime(pub)
        except Exception:
            continue
        items.append({
            "source": name,
            "guid": get("guid") or get("link"),
            "title": get("title"),
            "link": get("link"),
            "desc": kb.strip_html(get("description"))[:400],
            "ts": ts.isoformat(),
        })
    return items


def source_post_date(cand, max_days_back=5):
    """
    Data wpisu = data publikacji artykułu ŹRÓDŁOWEGO (czas lokalny serwera).

    Uczciwsza niż losowy rozrzut: news o wydarzeniu z 19.07 dostaje 19.07, więc
    kolejność w archiwum odpowiada kolejności wydarzeń. Przycięte do okna
    [dziś - max_days_back, teraz - 1h] — nic z przyszłości i nic starszego niż okno.
    """
    ts = dt.datetime.fromisoformat(cand["ts"]).astimezone()
    now = dt.datetime.now().astimezone()
    oldest = now - dt.timedelta(days=max_days_back)
    newest = now - dt.timedelta(hours=1)
    ts = max(oldest, min(ts, newest))
    return ts.strftime("%Y-%m-%d %H:%M:%S")


def recent_published_titles(n=40):
    """Tytuły ostatnich newsów — dla selekcji, żeby nie opisać drugi raz tego samego
    wydarzenia z innego serwisu (dedup po GUID feedu tego nie łapie)."""
    try:
        out = kb.wp("post", "list", "--post_type=post", "--post_status=publish",
                    "--category_name=aktualnosci", f"--posts_per_page={n}",
                    "--fields=post_title", "--format=json")
        return [p["post_title"] for p in json.loads(out)]
    except Exception as e:
        print(f"    UWAGA: nie udało się pobrać listy opublikowanych ({e})", flush=True)
        return []


def get_our_makes():
    out = kb.wp("term", "list", "make", "--fields=name,slug,count", "--format=json", "--number=0")
    return json.loads(out)


def get_cny_rate():
    try:
        data = kb.http_get("https://api.nbp.pl/api/exchangerates/rates/a/cny/?format=json")
        return float(data["rates"][0]["mid"])
    except Exception:
        return 0.55  # fallback przybliżony


def match_our_offer(title, makes):
    """Zwraca (make_name, hub_url) jeśli marka z tytułu jest w naszej taksonomii."""
    tl = title.lower()
    hits = []
    for m in makes:
        name = m["name"].lower()
        pos = None
        mm = re.search(r"\b" + re.escape(name) + r"\b", tl)
        if len(name) >= 3 and mm:
            pos = mm.start()
        if pos is not None and int(m.get("count") or 0) > 0:
            hits.append((pos, m))
    if not hits:
        return None, None
    # Tytuł często zawiera DWIE nasze marki: koncern i submarkę ("BYD Denza Z9S",
    # "Great Wall Motors' Tank 300"). U nas Denza i Tank są osobnymi markami, więc
    # link ma iść do nich, nie do koncernu. Konwencja nazewnicza stawia submarkę tuż
    # przed modelem — bierzemy więc trafienie stojące NAJPÓŹNIEJ w tytule (przy remisie
    # to o większym stanie). Bez tego news o Denzie Z9S linkował do hubu BYD, a news
    # o Tanku 300 do hubu GWM (realne przypadki 20-21.07).
    # ...ale tylko wśród marek stojących na POCZĄTKU tytułu (podmiot newsa). Bez tego
    # "Voyah Passion S rival to Xiaomi YU7" wybrałoby Xiaomi, bo stoi później — a news
    # jest o Voyah. Ta sama reguła okna co w match_our_model().
    head = [(p, m) for p, m in hits if len(tl[:p].split()) <= 3]
    pool = head or hits
    best_pos = max(p for p, _ in pool)
    best = max((m for p, m in pool if p == best_pos), key=lambda m: int(m.get("count") or 0))
    return best["name"], f"{SITE}/samochody/{best['slug']}/"


def get_our_series():
    """Modele (taksonomia `serie`) z liczbą aut i URL-em hubu — do gate'u D2."""
    php = ('$out=[];'
           'foreach (get_terms(["taxonomy"=>"serie","hide_empty"=>true]) as $t) {'
           '  $l = get_term_link($t); if (is_wp_error($l)) continue;'
           '  $mk = ""; $p = wp_get_object_terms('
           '    get_objects_in_term($t->term_id,"serie",["number"=>1]) ?: [0], "make");'
           '  if (!is_wp_error($p) && $p) $mk = $p[0]->name;'
           '  $out[] = ["name"=>$t->name,"make"=>$mk,"url"=>$l,"count"=>(int)$t->count];'
           '} echo wp_json_encode($out);')
    try:
        return json.loads(kb.wp("eval", php))
    except Exception as e:
        print(f"    UWAGA: nie udało się pobrać listy modeli ({e}) — gate D2 nieaktywny", flush=True)
        return []


def match_our_model(title, series):
    """
    Gate antykanibalizacyjny (zasada D2 z T-162: jedna fraza = jeden URL).

    Wykrywa, czy news dotyczy modelu, który MAMY w ofercie — bo wtedy jego hub
    /samochody/{marka}/{model}/ już odpowiada na zapytania "cena / import / gdzie kupić",
    a news napisany w tym samym ramowaniu odbierałby mu te frazy.

    Krótkie nazwy modeli (E5, 007, L6) wymagają obecności marki w tytule — inaczej
    "E5" złapałoby się w dowolnym ciągu znaków (patrz reguła marka≠model w PL).
    Zwraca najdłuższe dopasowanie albo None.
    """
    tl = title.lower()
    best, best_len = None, 0
    for s in series:
        name = (s.get("name") or "").strip().lower()
        make = (s.get("make") or "").strip().lower()
        if len(name) < 2:
            continue

        # Szukamy najpierw PEŁNEJ frazy "marka model" ("tank 300"), bo nazwa terminu bywa
        # samym numerem. Bez tego news "Great Wall Motors' Tank 300 expands lineup" nie
        # wykrywał naszego modelu: "300" stało za czterema słowami i wypadało na regule
        # pozycji, choć "Tank 300" stoi tuż na początku (realny przypadek, 21.07).
        phrases = []
        if make and not name.startswith(make):
            phrases.append(f"{make} {name}")
        phrases.append(name)

        for phrase in phrases:
            m = re.search(r"(?<![\w-])" + re.escape(phrase) + r"(?![\w-])", tl)
            if not m:
                continue
            # Model musi być PODMIOTEM newsa, nie wzmianką w tle. W nagłówkach branżowych
            # podmiot stoi na początku ("BYD Qin L facelift..."), a modele porównawcze dalej
            # ("Voyah Passion S rival to Xiaomi YU7" — news jest o Voyah, nie o YU7).
            if len(tl[:m.start()].split()) > 3:
                continue
            # Sam numer/skrót bez marki w tytule = za słabe dopasowanie.
            if phrase == name and len(name) < 4 and not (make and make in tl):
                continue
            if len(phrase) > best_len:
                best, best_len = s, len(phrase)
            break
    return best


def build_draft(cand, makes, rate, system_prompt, series=None):
    """Research + draft + verify. Zwraca dict albo rzuca."""
    try:
        page = kb.http_get(cand["link"], as_text=True)
        source_text = kb.strip_html(page)[:14000]
    except Exception as e:
        source_text = cand["desc"]
        print(f"    fetch źródła nieudany ({e}) — używam opisu RSS", flush=True)

    make_name, hub_url = match_our_offer(cand["title"], makes)
    model_hit = match_our_model(cand["title"], series or [])

    # W7 (audyt 22.07): news o wadzie/wycofaniu/awarii NIE dostaje zdania o naszej ofercie.
    # Realny przypadek: "GAC Aion S — awarie baterii CALB w 213 tys. egzemplarzy" z linkiem
    # "u nas kupisz" do /samochody/gac/ (32 auta, w tym 10 Aionów). Technicznie poprawne,
    # reputacyjnie prowadzi klienta z tekstu o wadliwych ogniwach wprost do naszej oferty.
    negative = bool(re.search(
        r"\b(recall|recalls|recalled|defect|defects|fault|faulty|fire risk|catch fire|"
        r"lawsuit|sues|investigation|probe|halt|halts|suspend|scandal|complaint|"
        r"safety issue|malfunction|failure|failures)\b", cand["title"], re.I))

    if negative:
        offer_ctx = ("Ten news dotyczy problemu/wady/wycofania. NIE wspominaj o naszej ofercie, "
                     "NIE wstawiaj linku do katalogu ani zachęty do zakupu — relacjonuj wyłącznie fakty.")
    elif model_hit:
        # Gate D2: hub modelu ma już historię zapytań na "cena/import/gdzie kupić".
        # News o tym samym modelu musi opowiadać WYDARZENIE, nie powtarzać oferty,
        # i oddać frazę pieniężną hubowi anchorem.
        offer_ctx = (
            f"Model {model_hit['name']} MAMY w ofercie ({model_hit['count']} aut) — "
            f"hub modelu: {model_hit['url']}\n"
            "RAMOWANIE (obowiązkowe): pisz WYŁĄCZNIE o wydarzeniu — co konkretnie się zmieniło, "
            "kiedy, dla kogo, czym się różni od poprzednika. NIE opisuj procesu importu do Polski, "
            "NIE odpowiadaj na 'ile kosztuje sprowadzenie', NIE pisz 'gdzie kupić' — na te pytania "
            "odpowiada hub modelu i nie wolno mu tych fraz odbierać.\n"
            f"ZAKOŃCZ jednym zdaniem z linkiem do hubu, w którym anchor zawiera nazwę modelu "
            f"i słowo 'cena' lub 'oferta' (np. <a href=\"{model_hit['url']}\">"
            f"{model_hit['name']} — ceny i dostępne egzemplarze</a>)."
        )
    elif make_name:
        offer_ctx = f"Markę {make_name} MAMY w ofercie — hub: {hub_url}"
    else:
        offer_ctx = "Tego modelu/marki NIE mamy w ofercie — nie wspominaj o ofercie."

    user_msg = (f"MATERIAŁ ŹRÓDŁOWY ({cand['source']}, {cand['link']}):\n{source_text}\n\n"
                f"KURS NBP: 1 CNY = {rate:.3f} PLN\n"
                f"NASZA OFERTA: {offer_ctx}\n\n"
                "Napisz artykuł zgodnie z instrukcją systemową. Zwróć czysty JSON.")

    draft = None
    for attempt in range(2):
        text, usage = kb.call_model(system_prompt, user_msg)
        try:
            draft = kb.parse_json_response(kb.normalize_quotes(text))
            break
        except Exception as e:
            if attempt == 0:
                print(f"    zepsuty JSON draftu ({e}) — powtarzam wywołanie", flush=True)
            else:
                raise

    fact_check_note = None
    for attempt in range(2):
        vtext, vusage = kb.call_model(
            VERIFY_PROMPT,
            f"MATERIAŁ ŹRÓDŁOWY:\n{source_text[:12000]}\n\nKURS: 1 CNY = {rate:.3f} PLN\n\n"
            f"ARTYKUŁ:\nTYTUŁ: {draft['title']}\nLEAD: {draft['lead']}\n{kb.strip_html(draft['body_html'])}",
            max_tokens=1500,
        )
        try:
            verdict = kb.parse_json_response(vtext)
        except Exception as e:
            # Zepsuty JSON weryfikatora ≠ zły draft — nie wyrzucamy gotowego artykułu
            # z tego powodu. Publikacja i tak wymaga ręcznego kliknięcia Janka w mailu,
            # więc "niepotwierdzone" oznaczamy w mailu zamiast tracić cały bieg.
            print(f"    fact-check: zepsuty JSON weryfikatora ({e}) — pomijam, oznaczam jako niepotwierdzony", flush=True)
            fact_check_note = "fact-check nie zwrócił poprawnej odpowiedzi — sprawdź liczby ręcznie przed publikacją"
            break
        if verdict.get("ok"):
            break
        if attempt == 0:
            print(f"    fact-check: {len(verdict.get('issues', []))} problemów — regeneruję", flush=True)
            text, usage = kb.call_model(
                system_prompt,
                user_msg + "\n\nPOPRZEDNIA WERSJA MIAŁA BŁĘDY FAKTOGRAFICZNE — popraw je:\n"
                + "\n".join("- " + i for i in verdict.get("issues", [])),
            )
            draft = kb.parse_json_response(kb.normalize_quotes(text))
        else:
            raise RuntimeError("Fact-check nie przeszedł po regeneracji: " + "; ".join(verdict.get("issues", [])[:3]))
    if fact_check_note:
        draft["_fact_check_note"] = fact_check_note

    # Korekta wydawnicza (proofing)
    proofed, changes = kb.proofread({
        "title": draft["title"], "lead": draft["lead"],
        "body_html": draft["body_html"], "excerpt": draft["excerpt"],
    })
    draft.update(proofed)
    if changes:
        print(f"    korekta: {len(changes)} poprawek", flush=True)

    lint = kb.lint_text(draft["title"] + " " + draft["lead"] + " " + draft["body_html"])
    if lint:
        raise RuntimeError("Lint: " + "; ".join(lint))

    draft["_source_url"] = cand["link"]
    draft["_source"] = cand["source"]
    return draft


def extract_gallery_images(html):
    """Zdjęcia treści artykułu (WP galeria/inline, klasa wp-image-N) — bez avatarów
    i kafli 'related posts'. Obsługuje zarówno klasyczny <img class="wp-image-N" src="...">
    (CarNewsChina), jak i lazy-load <picture class="wp-image-N">...</picture><noscript><img
    src="..."></noscript> (CNEVPost i inni z lazy-loadingiem — bez tego src to placeholder
    data:image/svg, nie prawdziwy URL). Zwraca URL-e w kolejności występowania, bez
    duplikatów (te same zdjęcie w różnych rozmiarach srcset liczy się raz)."""
    if not html:
        return []
    urls, seen = [], set()

    def add(url):
        if not url or url.startswith("data:"):
            return
        key = re.sub(r"-\d+x\d+(?=\.\w+$)", "", url)  # ignoruj sufiks rozmiaru (-800x450)
        if key in seen:
            return
        seen.add(key)
        urls.append(url)

    for m in re.finditer(r"<img\b[^>]*>", html):
        tag = m.group(0)
        cls = re.search(r'class="([^"]*)"', tag)
        if not cls or not re.search(r"\bwp-image-\d+\b", cls.group(1)):
            continue
        src = re.search(r'\bsrc="([^"]+)"', tag)
        if src:
            add(src.group(1))

    for m in re.finditer(r'<picture\b[^>]*class="[^"]*\bwp-image-\d+\b[^"]*"[^>]*>(.*?)</picture>', html, re.S):
        nm = re.search(r'<noscript>\s*<img\b[^>]*\bsrc="([^"]+)"', m.group(1))
        if nm:
            add(nm.group(1))

    return urls


# Portale/agregatory, które podpisują cudze zdjęcia własną nazwą po "Credit:".
# Kredyt od nich NIE jest materiałem prasowym producenta (audyt 22.07: 6 z 9 atrybucji
# "materiały prasowe X" było fałszywych — CnEVPost, CNC, Autohome, Xcar).
CREDIT_NOT_PRESS = {
    "cnevpost", "cnc", "carnewschina", "autohome", "xcar", "yiche", "dongchedi",
    "sohu", "weibo", "electrive", "insideevs", "reuters", "bloomberg", "ithome",
    "pcauto", "36kr", "cls", "jiemian", "cailianshe",
}


def _credit_from_text(text, source_name=None):
    m = re.search(r"credit:\s*(.+?)\s*$", text, re.I)
    if not m:
        return None
    credit = m.group(1).strip()
    if not credit:
        return None
    norm = re.sub(r"[^a-z0-9]", "", credit.lower())
    if norm in CREDIT_NOT_PRESS:
        return None
    # Kredyt równy nazwie serwisu źródłowego = serwis podpisał sam siebie
    # ("fot. materiały prasowe CnEVPost (via CNEVPost)" — masło maślane na produkcji).
    if source_name and norm == re.sub(r"[^a-z0-9]", "", source_name.lower()):
        return None
    return credit


def extract_verified_credit(html, image_url, source_name=None):
    """Sprawdza, czy DANY obraz ma jawny kredyt producenta w danych źródła — dwa wzorce
    branżowe: (1) schema.org JSON-LD ImageObject 'caption' (CarNewsChina), (2) klasyczny
    podpis WordPress <figcaption class="wp-caption-text"> zaraz po <img> (CNEVPost i inni).
    Bez zgadywania — jeśli źródło nie deklaruje wprost kto jest autorem/właścicielem zdjęcia,
    zwraca None (decyzja Janka 22.07: nie podpisujemy zdjęć jako 'materiały prasowe {marka}'
    bez potwierdzenia)."""
    if not html:
        return None
    key = re.sub(r"-\d+x\d+(?=\.\w+$)", "", image_url)
    for m in re.finditer(r'"contentUrl":"([^"]+)"[^{}]*?"caption":"([^"]*)"', html):
        url = m.group(1).replace("\\/", "/")
        if re.sub(r"-\d+x\d+(?=\.\w+$)", "", url) == key:
            credit = _credit_from_text(m.group(2), source_name)
            if credit:
                return credit
    for m in re.finditer(r'<img\b[^>]*\bsrc="([^"]+)"[^>]*>.{0,400}?<figcaption[^>]*>([^<]*)</figcaption>', html, re.S):
        url = m.group(1)
        if re.sub(r"-\d+x\d+(?=\.\w+$)", "", url) == key:
            credit = _credit_from_text(m.group(2), source_name)
            if credit:
                return credit
    return None


def pick_spaced(items, n):
    """Wybiera do n elementów rozłożonych równomiernie po liście (unika klastra duplikatów z początku)."""
    if not items:
        return []
    if len(items) <= n:
        return list(items)
    picks = []
    for k in range(n):
        idx = min(int((k + 1) * len(items) / (n + 1)), len(items) - 1)
        if items[idx] not in picks:
            picks.append(items[idx])
    return picks


def insert_figures(body_html, figures):
    """Wstawia bloki <figure> w treść, rozłożone po granicach akapitów (</p>), pomijając
    pierwszy i ostatni akapit żeby nie łamać leadu ani zakończenia."""
    if not figures:
        return body_html
    blocks = re.split(r"(</p>)", body_html)
    p_ends = [i for i, b in enumerate(blocks) if b == "</p>"]
    usable = p_ends[1:-1] if len(p_ends) > 2 else p_ends
    if not usable:
        return body_html + "".join(figures)
    step = max(1, len(usable) // (len(figures) + 1))
    slots = [usable[min((k + 1) * step - 1, len(usable) - 1)] for k in range(len(figures))]
    for slot, fig in sorted(zip(slots, figures), key=lambda x: -x[0]):
        blocks.insert(slot + 1, fig)
    return "".join(blocks)


def publish_wp_post(draft, post_date=None):
    """Publikuje news w WP od razu (bez etapu draft+akceptacja). Zwraca (post_id, url).

    post_date (str "YYYY-MM-DD HH:MM:SS", czas lokalny) — używane przy uzupełnianiu
    archiwum, żeby wpis miał datę wydarzenia, a nie datę wygenerowania."""
    source_html = None
    try:
        source_html = kb.http_get(draft["_source_url"], as_text=True)
    except Exception as e:
        print(f"    fetch źródła dla obrazków nieudany ({e})", flush=True)

    cover_url = None
    if source_html:
        m = re.search(r'property="og:image" content="([^"]+)"', source_html)
        cover_url = m.group(1) if m else None
    cover_credit = extract_verified_credit(source_html, cover_url, draft.get('_source')) if cover_url else None

    # Dodatkowe zdjęcia z galerii źródła (poza okładką) — do 2, rozłożone po treści,
    # żeby news miał więcej niż samo zdjęcie okładkowe (feedback Janka 22.07). Podpis
    # "materiały prasowe {marka}" TYLKO gdy źródło jawnie deklaruje kredyt (Credit: X w
    # danych źródła) — bez tego zdjęcie jest z artykułu, ale nie potwierdzone jako oficjalny
    # materiał prasowy (może być np. z leaku homologacyjnego), więc podpisujemy neutralnie
    # samym źródłem, żeby nie nadinterpretować pochodzenia (decyzja Janka 22.07).
    body_html = draft["body_html"]
    extra_att_ids = []
    if source_html:
        gallery = [u for u in extract_gallery_images(source_html) if u != cover_url]
        extra_figures = []
        slug = draft.get("slug") or "news"
        for i, url in enumerate(pick_spaced(gallery, 2), start=1):
            credit = extract_verified_credit(source_html, url, draft.get('_source'))
            caption = (f"fot. materiały prasowe {credit} (via {draft['_source']})" if credit
                       else f"fot. {draft['_source']}")
            webp = kb.download_webp(url, str(kb.STATE_DIR / f"extra-{slug}-{i}"))
            if not webp:
                continue
            att_id = kb.wp(
                "media", "import", webp,
                f"--title={draft['title']}", f"--alt={draft['title']}",
                f"--caption={caption}", "--porcelain",
            )
            Path(webp).unlink(missing_ok=True)
            att_url = kb.wp("post", "list", f"--post__in={att_id}", "--post_type=attachment", "--field=guid").strip()
            if att_url:
                extra_att_ids.append(att_id)
                extra_figures.append(
                    f'<figure class="wp-block-image size-large"><img src="{att_url}" alt="{draft["title"]}" />'
                    f"<figcaption>{caption}</figcaption></figure>"
                )
        if extra_figures:
            body_html = insert_figures(body_html, extra_figures)
            print(f"    +{len(extra_figures)} zdjęć z galerii źródła osadzonych w treści", flush=True)

    # Model bywa, że zwraca w JSON podwójnie zescapowane cudzysłowy w atrybutach HTML
    # (<a href=\\"URL\\">) — po json.loads zostaje href=\\" i link NIE DZIAŁA (przeglądarka
    # widzi href="\\"). Realny przypadek #389278 z biegu 22.07. Prostujemy przed zapisem.
    body_html = body_html.replace('\\"', '"')
    content = f"<!-- wp:paragraph --><p><strong>{draft['lead']}</strong></p><!-- /wp:paragraph -->\n" + body_html
    body_file = kb.STATE_DIR / "_post_body.html"
    body_file.write_text(content)
    create_args = [
        "post", "create", str(body_file),
        "--post_status=publish", "--post_type=post",
        "--post_category=aktualnosci", "--post_author=55",
        f"--post_title={draft['title']}",
        f"--post_excerpt={draft['excerpt']}",
        "--porcelain",
    ]
    if post_date:
        create_args.insert(-1, f"--post_date={post_date}")
    post_id = kb.wp(*create_args)
    for att_id in extra_att_ids:
        kb.wp("post", "update", att_id, f"--post_parent={post_id}")
    # Okładka: 1) oficjalne zdjęcie prasowe z artykułu źródłowego (og:image), podpis
    # "materiały prasowe {marka}" tylko przy potwierdzonym kredycie w źródle (Credit: X),
    # inaczej neutralnie "fot. {źródło}" — decyzja Janka 21.07/22.07: bierzemy oficjalne
    # materiały producentów z podpisem, jak cała branża, NIE zgadujemy pochodzenia,
    # 2) fallback: brandowa plansza typograficzna.
    cover_done = False
    cover_caption = (f"fot. materiały prasowe {cover_credit} (via {draft['_source']})" if cover_credit
                      else f"fot. {draft['_source']}")
    try:
        if cover_url:
            webp = kb.download_webp(cover_url, str(kb.STATE_DIR / f"press-{post_id}"))
            if webp:
                kb.wp("media", "import", webp, f"--post_id={post_id}", "--featured_image",
                      f"--title={draft['title']}", f"--alt={draft['title']}",
                      f"--caption={cover_caption}", "--porcelain")
                Path(webp).unlink(missing_ok=True)
                cover_done = True
    except Exception as e:
        print(f"    press-cover nieudany ({e}) — fallback typografia", flush=True)
    if not cover_done:
        try:
            cover = str(kb.STATE_DIR / f"cover-{post_id}.webp")
            make_cover(draft["title"], cover)
            kb.wp("media", "import", cover, f"--post_id={post_id}", "--featured_image",
                  f"--title={draft['title']} — aktualności Prima-Auto",
                  f"--alt={draft['title']}", "--porcelain")
            kb.wp("post", "meta", "set", post_id, "_kb_cover_auto", "1")
            Path(cover).unlink(missing_ok=True)
        except Exception as e:
            print(f"    okładka nieudana (nie blokuje): {e}", flush=True)

    kb.wp("post", "meta", "set", post_id, "_kb_source_name", draft["_source"])
    kb.wp("post", "meta", "set", post_id, "_kb_source_url", draft["_source_url"])
    url = kb.wp("post", "list", f"--post__in={post_id}", "--field=url").strip()
    return post_id, url


def build_mail(results, skipped_info):
    rows = []
    for r in results:
        d = r["draft"]
        warn = (f"<p style='background:#fff3cd;color:#856404;padding:8px 12px;border-radius:6px;font-size:13px;margin:0 0 10px'>"
                f"⚠️ {d['_fact_check_note']}</p>") if d.get("_fact_check_note") else ""
        rows.append(f"""
<div style="border:1px solid #ddd;border-radius:8px;padding:16px;margin:16px 0">
  <p style="margin:0 0 4px;color:#888;font-size:12px">{d['_source']} · post #{r['post_id']}</p>
  {warn}
  <h2 style="margin:0 0 8px;font-size:19px"><a href="{r['url']}">{d['title']}</a></h2>
  <p style="font-weight:bold">{d['lead']}</p>
  <p style="font-size:12px;color:#888">Źródło: <a href="{d['_source_url']}">{d['_source_url']}</a></p>
</div>""")
    return (f"<p>Newsy opublikowane dziś na primaauto.com.pl (bez akceptacji — zgłoś, jeśli coś się rzuci w oczy).</p>"
            + "".join(rows)
            + (f"<p style='color:#888;font-size:12px'>{skipped_info}</p>" if skipped_info else ""))


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--dry-run", action="store_true")
    ap.add_argument("--limit", type=int, default=2)
    ap.add_argument("--no-mail", action="store_true")
    ap.add_argument("--max-age-h", type=int, default=MAX_AGE_H,
                    help="okno wieku kandydatów w godzinach (default 36; większe = uzupełnianie archiwum)")
    ap.add_argument("--backdate", action="store_true",
                    help="data wpisu = data artykułu źródłowego (do uzupełniania archiwum)")
    args = ap.parse_args()

    if (kb.STATE_DIR / "DISABLED").exists():
        print("Kill-switch aktywny (state/DISABLED) — koniec.")
        return

    now = dt.datetime.now(dt.timezone.utc)
    print(f"[{now.strftime('%Y-%m-%d %H:%M')}] news_daily — radar RSS", flush=True)

    seen = kb.load_state("seen-news.json", {"guids": []})
    seen_set = set(seen["guids"])

    candidates = []
    for name, url in FEEDS:
        items = parse_feed(name, url)
        fresh = [i for i in items
                 if i["guid"] not in seen_set
                 and (now - dt.datetime.fromisoformat(i["ts"])).total_seconds() < args.max_age_h * 3600]
        print(f"  [{name}] {len(items)} w feedzie, {len(fresh)} świeżych nowych", flush=True)
        candidates += fresh

    if not candidates:
        print("Brak nowych kandydatów.")
        if not args.no_mail and not args.dry_run:
            kb.send_mail("[primaauto] Newsy: brak kandydatów dziś", "<p>Radar RSS nie znalazł dziś nowych tematów (36h).</p>")
        return

    makes = get_our_makes()
    make_names = [m["name"] for m in makes]
    series = get_our_series()   # gate D2 — patrz match_our_model()

    cand_list = "\n".join(f"{i}. [{c['source']}] {c['title']} — {c['desc'][:180]}" for i, c in enumerate(candidates))
    sel_text, sel_usage = kb.call_model(
        SELECT_PROMPT.format(
            n=args.limit,
            makes=", ".join(make_names[:60]),
            published="\n".join("- " + t for t in recent_published_titles()) or "(brak)",
        ),
        f"KANDYDACI:\n{cand_list}\n\nWybierz {args.limit}.",
        max_tokens=max(800, 220 * args.limit),
    )
    picks = kb.parse_json_response(sel_text)["picks"][:args.limit]
    print("\nSelekcja:", flush=True)
    for p in picks:
        print(f"  -> [{candidates[p['idx']]['source']}] {candidates[p['idx']]['title']}\n     {p['why']}", flush=True)

    if args.dry_run:
        return

    system_prompt = (kb.KB_DIR / "prompts" / "news_system.txt").read_text()
    rate = get_cny_rate()
    results, failed = [], []
    for p in picks:
        cand = candidates[p["idx"]]
        print(f"\n=== {cand['title']}", flush=True)
        try:
            draft = build_draft(cand, makes, rate, system_prompt, series)
            post_id, url = publish_wp_post(draft, source_post_date(cand) if args.backdate else None)
            results.append({"draft": draft, "post_id": post_id, "url": url})
            print(f"    OK opublikowano #{post_id} -> {url}", flush=True)
        except Exception as e:
            failed.append(f"{cand['title']}: {e}")
            print(f"    FAIL: {e}", flush=True)
        seen_set.add(cand["guid"])

    # Nieprzetworzeni kandydaci NIE lądują w seen — mogą wrócić jutro, jeśli nadal świeży
    kb.save_state("seen-news.json", {"guids": list(seen_set)[-2000:]})

    if results and not args.no_mail:
        skipped = f"Nieudane: {len(failed)}" if failed else ""
        kb.send_mail(f"[primaauto] Newsy opublikowane ({len(results)}) — {dt.date.today():%d.%m}",
                     build_mail(results, skipped))
        print(f"\nMail wysłany ({len(results)} newsów).", flush=True)
    elif failed and not args.no_mail:
        kb.send_mail("[primaauto] Newsy: bieg nieudany", "<p>Żaden news nie przeszedł pipeline'u:</p><ul>"
                     + "".join(f"<li>{f}</li>" for f in failed) + "</ul>")


if __name__ == "__main__":
    main()
