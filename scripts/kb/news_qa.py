#!/usr/bin/env python3
"""
Kontrola jakości opublikowanych newsów (T-214). Sprawdza ostatnie N wpisów kategorii
`aktualnosci` i raportuje WYŁĄCZNIE odchyłki — nikt nie ma czytać każdego newsa ręcznie.

Powstało po audycie biegu archiwalnego 2026-07-22, w którym ręczny przegląd 12 newsów
wykrył 9 wzorców defektów. Każdy z nich ma tu swój automatyczny check, żeby nocny cron
pilnował się sam.

Checki:
  L1 zepsuty HTML — zescapowane cudzysłowy w atrybutach (href=\\" => link nie działa)
  L2 link do hubu, który nie istnieje albo nie ma aut
  L3 link do koncernu, gdy w tytule jest nasza submarka z ofertą (BYD->Denza, GWM->Tank)
  L4 brak linku, choć news dotyczy modelu, który mamy (dopasowanie po tytule PL + treści)
  C1 podpis "materiały prasowe X", gdzie X to portal/serwis źródłowy, nie producent
  N1 news negatywny (recall/wada/awaria) z linkiem do naszej oferty
  N2 skrót żargonowy bez rozwinięcia przy pierwszym użyciu
  N3 nazewnictwo napędów niezgodne z taksonomią serwisu (BEV zamiast EV, REEV zamiast EREV)
  D1 podejrzenie duplikatu tematu (podobny tytuł w ostatnich 60 dniach)

Użycie:
  python3 news_qa.py                 # ostatnie 15 newsów
  python3 news_qa.py --limit 40
  python3 news_qa.py --mail          # raport do Janka, tylko gdy są odchyłki
"""
import argparse
import json
import re
import sys
from difflib import SequenceMatcher
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import kb_lib as kb
import news_daily as nd

SITE = "https://primaauto.com.pl"

NEGATIVE_RE = re.compile(
    r"\b(recall|wycofuj|wycofanie|wada|wady|usterk|awari|defekt|pozew|śledztw|"
    r"dochodzeni|pożar|zapłon|reklamacj|skarg)\w*", re.I)

# Skróty, które muszą być rozwinięte przy pierwszym użyciu.
JARGON = ["NEV", "BEV", "CLTC", "WLTP", "CPCA", "MIIT", "ADAS", "EREV", "PHEV", "V2L", "NOA"]

# Konwencja serwisu (taksonomia `fuel`): lewe = błędne w newsie, prawe = nasze.
NAMING = {"BEV": "EV", "REEV": "EREV"}


def wp_json(*args):
    out = kb.wp(*args)
    return json.loads(out) if out.strip() else []


def hub_stats():
    """Mapa URL hubu -> liczba aut, dla marek i modeli."""
    php = ('$o=[];'
           'foreach (get_terms(["taxonomy"=>"make","hide_empty"=>false]) as $t){'
           '  $l=get_term_link($t); if(!is_wp_error($l)) $o[$l]=(int)$t->count; }'
           'foreach (get_terms(["taxonomy"=>"serie","hide_empty"=>false]) as $t){'
           '  $l=get_term_link($t); if(!is_wp_error($l)) $o[$l]=(int)$t->count; }'
           'echo wp_json_encode($o);')
    return json.loads(kb.wp("eval", php))


def check(post, makes, series, hubs, recent_titles):
    """Zwraca listę stringów-odchyłek dla jednego newsa."""
    issues = []
    title, content = post["post_title"], post["post_content"]
    text = re.sub(r"<[^>]+>", " ", content)

    # L1 — zepsuty HTML
    if '\\"' in content:
        issues.append(r'L1 zescapowane cudzysłowy w HTML (href=\" - link nie dziala)')

    links = re.findall(r'href="(' + re.escape(SITE) + r'/(?:samochody)/[^"]*)"', content)
    links = list(dict.fromkeys(links))

    # L2 — hub pusty/nieistniejący
    for u in links:
        n = hubs.get(u if u.endswith("/") else u + "/")
        if n is None:
            issues.append(f"L2 link do nieznanego hubu: {u}")
        elif n == 0:
            issues.append(f"L2 link do hubu bez aut: {u}")

    # L3 — koncern zamiast submarki obecnej w tytule
    best_make, best_url = nd.match_our_offer(title, makes)
    if links and best_url and not any(best_url.rstrip("/") in u for u in links):
        issues.append(f"L3 link {links[0]} — celniejszy byłby {best_url} ({best_make})")

    # L4 — mamy model, a linku brak
    model = nd.match_our_model(title, series)
    if model and not links:
        issues.append(f"L4 brak linku, a mamy {model['count']}× {model['name']}: {model['url']}")

    # C1 — fałszywa atrybucja materiałów prasowych
    for cap in set(re.findall(r"materiały prasowe ([^<(]+)", content)):
        norm = re.sub(r"[^a-z0-9]", "", cap.lower())
        if norm in nd.CREDIT_NOT_PRESS:
            issues.append(f'C1 "materiały prasowe {cap.strip()}" - to portal/serwis, nie producent')

    # N1 — negatywny news z CTA do oferty
    if NEGATIVE_RE.search(title) and links:
        issues.append(f"N1 news negatywny z linkiem do oferty ({links[0]})")

    # N2 — żargon bez rozwinięcia
    for abbr in JARGON:
        if re.search(rf"\b{abbr}\b", text):
            window = text[: text.find(abbr) + 260]
            if not re.search(r"\(|to |czyli |oznacza", window):
                issues.append(f"N2 skrót {abbr} bez rozwinięcia przy pierwszym użyciu")

    # N3 — nazewnictwo napędów
    for wrong, ours in NAMING.items():
        if re.search(rf"\b{wrong}\b", text):
            issues.append(f'N3 "{wrong}" zamiast naszego "{ours}" (taksonomia fuel)')

    # D1 — duplikat tematu
    for other in recent_titles:
        if other == title:
            continue
        if SequenceMatcher(None, title.lower(), other.lower()).ratio() > 0.72:
            issues.append(f'D1 możliwy duplikat: "{other}"')
            break

    return issues


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--limit", type=int, default=15)
    ap.add_argument("--mail", action="store_true")
    args = ap.parse_args()

    posts = wp_json("post", "list", "--post_type=post", "--post_status=publish",
                    "--category_name=aktualnosci", f"--posts_per_page={args.limit}",
                    "--fields=ID,post_title,post_date,post_content", "--format=json")
    if not posts:
        print("Brak newsów do sprawdzenia.")
        return

    makes, series, hubs = nd.get_our_makes(), nd.get_our_series(), hub_stats()
    titles = [p["post_title"] for p in posts]

    total, rows = 0, []
    for p in posts:
        issues = check(p, makes, series, hubs, titles)
        if not issues:
            continue
        total += len(issues)
        rows.append((p, issues))
        print(f"\n#{p['ID']} {p['post_date'][:10]} — {p['post_title'][:70]}")
        for i in issues:
            print(f"    {i}")

    clean = len(posts) - len(rows)
    print(f"\nSprawdzono {len(posts)} newsów: {clean} bez uwag, "
          f"{len(rows)} z uwagami ({total} odchyłek).")

    if args.mail and rows:
        html = [f"<p>Kontrola jakości newsów: {len(rows)}/{len(posts)} wpisów z uwagami.</p>"]
        for p, issues in rows:
            html.append(f"<p><b>{p['post_title']}</b><br>"
                        f"<a href='{SITE}/?p={p['ID']}'>#{p['ID']}</a><br>"
                        + "<br>".join(issues) + "</p>")
        kb.send_mail(f"[primaauto] QA newsów: {len(rows)} wpisów z uwagami", "".join(html))
        print("Raport wysłany.")


if __name__ == "__main__":
    main()
