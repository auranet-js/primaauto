#!/usr/bin/env python3
"""
Codzienny pipeline newsowy działu wiedzy (T-214 F1/F3).

Etapy: radar RSS -> dedup -> selekcja (Claude) -> research (pełny artykuł
źródłowy + kontekst naszej oferty + kurs CNY) -> draft PL (Claude) ->
weryfikacja faktów (Claude, drugi przebieg) -> lint -> draft w WP ->
mail akceptacyjny do Janka z tokenowym linkiem "Opublikuj".

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
import secrets as pysecrets
import sys
import xml.etree.ElementTree as ET
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import kb_lib as kb

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

Zwróć czysty JSON: {{"picks": [{{"idx": <numer kandydata>, "why": "1 zdanie"}}]}}"""

VERIFY_PROMPT = """Jesteś weryfikatorem faktów. Porównaj ARTYKUŁ z MATERIAŁEM ŹRÓDŁOWYM.
Sprawdź KAŻDĄ liczbę i fakt w artykule (ceny, moce, zasięgi, wymiary, daty, nazwy wersji):
- czy występuje w źródle lub jest z niego wprost przeliczalna (przeliczenie yuan->PLN po podanym kursie, zaokrąglone, ze słowem "około", jest OK),
- czy artykuł nie dopisuje faktów, których w źródle nie ma.
Zwróć czysty JSON: {"ok": true/false, "issues": ["konkretny problem 1", ...]}. Drobne zaokrąglenia i pominięcia to nie błąd; błędem jest liczba/fakt sprzeczny ze źródłem albo nieobecny w nim."""


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


def get_our_makes():
    out = kb.wp("term", "list", "make", "--fields=name,slug", "--format=json", "--number=0")
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
    for m in makes:
        name = m["name"].lower()
        if len(name) >= 3 and re.search(r"\b" + re.escape(name) + r"\b", tl):
            return m["name"], f"{SITE}/samochody/{m['slug']}/"
    return None, None


def build_draft(cand, makes, rate, system_prompt):
    """Research + draft + verify. Zwraca dict albo rzuca."""
    try:
        page = kb.http_get(cand["link"], as_text=True)
        source_text = kb.strip_html(page)[:14000]
    except Exception as e:
        source_text = cand["desc"]
        print(f"    fetch źródła nieudany ({e}) — używam opisu RSS", flush=True)

    make_name, hub_url = match_our_offer(cand["title"], makes)
    offer_ctx = (f"Markę {make_name} MAMY w ofercie — hub: {hub_url}"
                 if make_name else "Tego modelu/marki NIE mamy w ofercie — nie wspominaj o ofercie.")

    user_msg = (f"MATERIAŁ ŹRÓDŁOWY ({cand['source']}, {cand['link']}):\n{source_text}\n\n"
                f"KURS NBP: 1 CNY = {rate:.3f} PLN\n"
                f"NASZA OFERTA: {offer_ctx}\n\n"
                "Napisz artykuł zgodnie z instrukcją systemową. Zwróć czysty JSON.")

    text, usage = kb.call_model(system_prompt, user_msg)
    draft = kb.parse_json_response(kb.normalize_quotes(text))

    for attempt in range(2):
        vtext, vusage = kb.call_model(
            VERIFY_PROMPT,
            f"MATERIAŁ ŹRÓDŁOWY:\n{source_text[:12000]}\n\nKURS: 1 CNY = {rate:.3f} PLN\n\n"
            f"ARTYKUŁ:\nTYTUŁ: {draft['title']}\nLEAD: {draft['lead']}\n{kb.strip_html(draft['body_html'])}",
            max_tokens=1500,
        )
        verdict = kb.parse_json_response(vtext)
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

    lint = kb.lint_text(draft["title"] + " " + draft["lead"] + " " + draft["body_html"])
    if lint:
        raise RuntimeError("Lint: " + "; ".join(lint))

    draft["_source_url"] = cand["link"]
    draft["_source"] = cand["source"]
    return draft


def create_wp_draft(draft):
    """Draft posta w WP + token publikacji. Zwraca (post_id, publish_url)."""
    content = f"<!-- wp:paragraph --><p><strong>{draft['lead']}</strong></p><!-- /wp:paragraph -->\n" + draft["body_html"]
    body_file = kb.STATE_DIR / "_post_body.html"
    body_file.write_text(content)
    post_id = kb.wp(
        "post", "create", str(body_file),
        "--post_status=draft", "--post_type=post",
        "--post_category=aktualnosci", "--post_author=55",
        f"--post_title={draft['title']}",
        f"--post_excerpt={draft['excerpt']}",
        "--porcelain",
    )
    token = pysecrets.token_hex(20)
    kb.wp("post", "meta", "set", post_id, "_kb_source_name", draft["_source"])
    kb.wp("post", "meta", "set", post_id, "_kb_source_url", draft["_source_url"])
    kb.wp("post", "meta", "set", post_id, "_kb_publish_token", token)
    publish_url = f"{SITE}/wp-json/asiaauto/v1/kb-publish?post={post_id}&token={token}"
    return post_id, publish_url


def build_mail(results, skipped_info):
    rows = []
    for r in results:
        d = r["draft"]
        rows.append(f"""
<div style="border:1px solid #ddd;border-radius:8px;padding:16px;margin:16px 0">
  <p style="margin:0 0 4px;color:#888;font-size:12px">{d['_source']} · post #{r['post_id']}</p>
  <h2 style="margin:0 0 8px;font-size:19px">{d['title']}</h2>
  <p style="font-weight:bold">{d['lead']}</p>
  {d['body_html']}
  <p style="font-size:12px;color:#888">Źródło: <a href="{d['_source_url']}">{d['_source_url']}</a></p>
  <p style="margin:14px 0 0"><a href="{r['publish_url']}" style="background:#1B2A4A;color:#fff;padding:10px 22px;border-radius:6px;text-decoration:none;font-weight:bold">✅ OPUBLIKUJ ten news</a></p>
</div>""")
    return (f"<p>Dzisiejsze newsy z chińskiego rynku — do akceptacji. Klik w przycisk = publikacja na primaauto.com.pl.</p>"
            + "".join(rows)
            + (f"<p style='color:#888;font-size:12px'>{skipped_info}</p>" if skipped_info else ""))


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--dry-run", action="store_true")
    ap.add_argument("--limit", type=int, default=2)
    ap.add_argument("--no-mail", action="store_true")
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
                 and (now - dt.datetime.fromisoformat(i["ts"])).total_seconds() < MAX_AGE_H * 3600]
        print(f"  [{name}] {len(items)} w feedzie, {len(fresh)} świeżych nowych", flush=True)
        candidates += fresh

    if not candidates:
        print("Brak nowych kandydatów.")
        if not args.no_mail and not args.dry_run:
            kb.send_mail("[primaauto] Newsy: brak kandydatów dziś", "<p>Radar RSS nie znalazł dziś nowych tematów (36h).</p>")
        return

    makes = get_our_makes()
    make_names = [m["name"] for m in makes]

    cand_list = "\n".join(f"{i}. [{c['source']}] {c['title']} — {c['desc'][:180]}" for i, c in enumerate(candidates))
    sel_text, sel_usage = kb.call_model(
        SELECT_PROMPT.format(n=args.limit, makes=", ".join(make_names[:60])),
        f"KANDYDACI:\n{cand_list}\n\nWybierz {args.limit}.",
        max_tokens=800,
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
            draft = build_draft(cand, makes, rate, system_prompt)
            post_id, publish_url = create_wp_draft(draft)
            results.append({"draft": draft, "post_id": post_id, "publish_url": publish_url})
            print(f"    OK draft #{post_id}", flush=True)
        except Exception as e:
            failed.append(f"{cand['title']}: {e}")
            print(f"    FAIL: {e}", flush=True)
        seen_set.add(cand["guid"])

    # Nieprzetworzeni kandydaci NIE lądują w seen — mogą wrócić jutro, jeśli nadal świeży
    kb.save_state("seen-news.json", {"guids": list(seen_set)[-2000:]})

    if results and not args.no_mail:
        skipped = f"Nieudane: {len(failed)}" if failed else ""
        kb.send_mail(f"[primaauto] Newsy do akceptacji ({len(results)}) — {dt.date.today():%d.%m}",
                     build_mail(results, skipped))
        print(f"\nMail wysłany ({len(results)} newsów).", flush=True)
    elif failed and not args.no_mail:
        kb.send_mail("[primaauto] Newsy: bieg nieudany", "<p>Żaden news nie przeszedł pipeline'u:</p><ul>"
                     + "".join(f"<li>{f}</li>" for f in failed) + "</ul>")


if __name__ == "__main__":
    main()
