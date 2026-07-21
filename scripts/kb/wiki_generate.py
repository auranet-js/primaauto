#!/usr/bin/env python3
"""
Generator haseł Leksykonu (T-214 F2). Wejście: wiki_tier1.json (konfiguracja
haseł: tytuł, slug, kategoria, aliasy, klucze extra_prep, kontekst faktograficzny).
Generowanie przez claude -p (abonament), draft CPT asiaauto_wiki + meta,
mail zbiorczy do Janka z tokenowymi linkami "Opublikuj".

Użycie:
  python3 wiki_generate.py --limit 10            # pierwsze N z konfiguracji (pomija istniejące slugi)
  python3 wiki_generate.py --slugs silnik-psm    # konkretne
  python3 wiki_generate.py --no-mail             # bez maila (debug)
"""
import argparse
import json
import secrets as pysecrets
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import kb_lib as kb

SITE = "https://primaauto.com.pl"

REVIEW_PROMPT = """Jesteś sceptycznym recenzentem technicznym motoryzacji elektrycznej. Dostajesz hasło encyklopedyczne o technologii w chińskich autach oraz KONTEKST FAKTOGRAFICZNY, na którym miało bazować.
Sprawdź twierdzenia techniczne: (1) sprzeczne z kontekstem lub powszechną wiedzą inżynierską = błąd; (2) konkretna liczba/parametr (moc, cena, pojemność, data), którego nie ma w kontekście i nie jest powszechnie ustaloną wiedzą = błąd (zmyślenie); (3) mylące uproszczenie istotne dla kupującego = błąd. NIE zgłaszaj: ogólników, poprawnych uproszczeń dydaktycznych, typowych zastosowań i przykładów wynikających z ogólnej wiedzy inżynierskiej, ani modeli aut wymienionych w sekcji PRZYKŁADOWE AUTA kontekstu.
Zwróć czysty JSON: {"ok": true/false, "issues": ["konkretny problem", ...]}."""


def existing_slugs():
    out = kb.wp("post", "list", "--post_type=asiaauto_wiki", "--post_status=any",
                "--field=post_name", "--posts_per_page=-1")
    return set(s for s in out.split("\n") if s)


RESEARCH_PROMPT = """Jesteś researcherem redakcji motoryzacyjnej. Zbierz z sieci AKTUALNE fakty o podanej technologii w kontekście chińskiej motoryzacji: generacje/wersje z datami, kluczowe parametry, producenci/dostawcy, przykładowe modele aut, znane problemy/kontrowersje. Priorytet: rzeczy, które zmieniały się w czasie (generacje!) i które kupujący auto z Chin musi wiedzieć.
Zwróć czysty JSON: {"fakty": ["fakt z datą/parametrem", ...], "zrodla": ["url", ...]} — 6-12 faktów, tylko zweryfikowane w źródłach."""


def research_facts(cfg):
    """Research w sieci przed pisaniem hasła (źródło prawdy ≠ pamięć modelu)."""
    try:
        text, _ = kb.call_model(
            RESEARCH_PROMPT,
            f"TECHNOLOGIA: {cfg['title']} (kontekst wyjściowy: {cfg['context'][:400]})",
            tools="WebSearch,WebFetch",
        )
        data = kb.parse_json_response(text)
        return data.get("fakty", []), data.get("zrodla", [])
    except Exception as e:
        print(f"    research nieudany ({e}) — piszę bez", flush=True)
        return [], []


def get_paa(keyword):
    """Realne pytania People Also Ask z SERP (DataForSEO, ~$0.002/zapytanie)."""
    import urllib.request
    try:
        auth = (Path.home() / "secrets/dataforseo/basic-auth-b64.txt").read_text().strip()
        body = json.dumps([{"keyword": keyword, "location_code": 2616, "language_code": "pl",
                            "people_also_ask_click_depth": 2, "depth": 10}]).encode()
        req = urllib.request.Request(
            "https://api.dataforseo.com/v3/serp/google/organic/live/advanced", data=body,
            headers={"Authorization": f"Basic {auth}", "Content-Type": "application/json"})
        with urllib.request.urlopen(req, timeout=40) as r:
            d = json.loads(r.read())
        questions = []
        for res in d["tasks"][0].get("result") or []:
            for item in res.get("items") or []:
                if item.get("type") == "people_also_ask":
                    for q in item.get("items") or []:
                        t = (q.get("title") or "").strip()
                        if t and t not in questions:
                            questions.append(t)
        return questions[:6]
    except Exception as e:
        print(f"    PAA nieudane ({e}) — FAQ bez PAA", flush=True)
        return []


def db_examples(cfg, limit=6):
    """Przykładowe auta z daną technologią z bazy (LIKE po _asiaauto_extra_prep)."""
    conds = []
    for key, val in cfg.get("term_keys", {}).items():
        if val:
            esc = json.dumps(val, ensure_ascii=True)[1:-1].replace("\\", "\\\\\\\\")
            conds.append(f"m.meta_value LIKE '%\"{key}\":\"%{esc}%'")
        else:
            conds.append(f"(m.meta_value LIKE '%\"{key}\":\"%' AND m.meta_value NOT LIKE '%\"{key}\":\"\"%')")
    if not conds:
        return []
    sql = ("SELECT p.post_title FROM wp7j_posts p "
           "JOIN wp7j_postmeta m ON m.post_id=p.ID AND m.meta_key='_asiaauto_extra_prep' "
           "WHERE p.post_type='listings' AND p.post_status='publish' AND ("
           + " OR ".join(conds) + f") ORDER BY p.post_date DESC LIMIT {limit}")
    try:
        out = kb.wp("db", "query", sql, "--skip-column-names")
        return [t for t in out.split("\n") if t.strip()]
    except Exception as e:
        print(f"    db_examples: {e}", flush=True)
        return []


def build_entry(cfg, system_prompt):
    kw_ctx = ""
    if cfg.get("main_kw"):
        kw_ctx = (f"FRAZA GŁÓWNA (DataForSEO): \"{cfg['main_kw']}\" — użyj jej naturalnie w definicji "
                  f"i przynajmniej raz w treści; frazy powiązane z wolumenami: {cfg.get('kw_variants', '')}. "
                  "Sformułuj 1. pytanie FAQ w brzmieniu, jakim ludzie realnie pytają (np. 'Co to jest ...').\n")
    examples = db_examples(cfg)
    ex_ctx = ("PRZYKŁADOWE AUTA Z TĄ TECHNOLOGIĄ Z NASZEJ BAZY (możesz przywołać modele, bez rocznikowych dopisków):\n- "
              + "\n- ".join(examples) + "\n") if examples else ""
    facts, sources = research_facts(cfg)
    facts_ctx = ("FAKTY Z RESEARCHU W SIECI (zweryfikowane, użyj ich — zwłaszcza generacji/wersji i dat):\n- "
                 + "\n- ".join(facts) + "\n") if facts else ""
    paa = get_paa(cfg.get("main_kw") or cfg["title"])
    paa_ctx = ("REALNE PYTANIA UŻYTKOWNIKÓW GOOGLE (People Also Ask) — FAQ zbuduj przede wszystkim "
               "z nich (możesz przeredagować stylistycznie, dodaj max 1 własne pytanie):\n- "
               + "\n- ".join(paa) + "\n") if paa else ""
    user_msg = (f"HASŁO: {cfg['title']}\n{kw_ctx}"
                f"KONTEKST Z NASZEJ BAZY (fakty do wykorzystania, nie cytuj dosłownie):\n{cfg['context']}\n{facts_ctx}{ex_ctx}{paa_ctx}\n"
                "Napisz hasło zgodnie z instrukcją systemową (kroki 1-3 wewnętrznie). Zwróć czysty finalny JSON.")
    # Jeden przebieg: draft + samo-recenzja + korekta w jednym prompcie (optymalizacja
    # po feedbacku Janka 21.07 — 4 osobne procesy claude -p × narzut startu = kwadrans/hasło).
    text, _ = kb.call_model(system_prompt, user_msg, model="opus")
    entry = kb.parse_json_response(kb.normalize_quotes(text))
    entry["_sources"] = sources

    full_text = entry["definition"] + " " + entry["body_html"] + " " + json.dumps(entry["faq"], ensure_ascii=False)
    lint = kb.lint_text(full_text)
    if lint:
        raise RuntimeError("Lint: " + "; ".join(lint))
    if len(entry.get("faq", [])) < 3:
        raise RuntimeError("Za mało pytań FAQ")
    if len(entry["body_html"]) < 1200:
        raise RuntimeError(f"body_html za krótkie ({len(entry['body_html'])})")
    return entry


def create_wiki_entry(cfg, entry):
    """Publikuje hasło od razu (sekcja /wiki/ jest noindex do akceptu Janka —
    review odbywa się na żywym layoutcie, nie w mailu-atrapie)."""
    from make_cover import make_cover
    faq_html = "".join(
        f"<details><summary>{f['q']}</summary><p>{f['a']}</p></details>"
        for f in entry["faq"]
    )
    content = (f"<p class=\"wiki-definition\"><strong>{entry['definition']}</strong></p>\n"
               + entry["body_html"]
               + f"\n<h2>Najczęstsze pytania</h2>\n{faq_html}")
    body_file = kb.STATE_DIR / f"_wiki_body-{cfg['slug']}.html"
    kb.STATE_DIR.mkdir(parents=True, exist_ok=True)
    body_file.write_text(content)
    post_id = kb.wp(
        "post", "create", str(body_file),
        "--post_status=publish", "--post_type=asiaauto_wiki",
        "--post_author=55",
        f"--post_title={cfg['title']}",
        f"--post_name={cfg['slug']}",
        f"--post_excerpt={entry['excerpt']}",
        "--porcelain",
    )
    kb.wp("post", "meta", "set", post_id, "_wiki_category", cfg["category"])
    kb.wp("post", "meta", "set", post_id, "_wiki_aliases", cfg["aliases"])
    if cfg.get("headword"):
        kb.wp("post", "meta", "set", post_id, "_wiki_headword", cfg["headword"])
    if cfg.get("main_kw"):
        kb.wp("post", "meta", "set", post_id, "rank_math_focus_keyword", cfg["main_kw"])
    if entry.get("excerpt"):
        kb.wp("post", "meta", "set", post_id, "rank_math_description", entry["excerpt"])
    kb.wp("post", "meta", "set", post_id, "_wiki_term_keys", json.dumps(cfg["term_keys"], ensure_ascii=False))
    kb.wp("post", "meta", "set", post_id, "_kb_faq_json", json.dumps(entry["faq"], ensure_ascii=False))
    if entry.get("_sources"):
        kb.wp("post", "meta", "set", post_id, "_wiki_sources", json.dumps(entry["_sources"], ensure_ascii=False))
    try:
        cover = str(kb.STATE_DIR / f"cover-wiki-{post_id}.webp")
        make_cover(cfg["title"], cover, "LEKSYKON")
        kb.wp("media", "import", cover, f"--post_id={post_id}", "--featured_image",
              f"--title={cfg['title']} — Leksykon Prima-Auto", f"--alt={cfg['title']}", "--porcelain")
        Path(cover).unlink(missing_ok=True)
    except Exception as e:
        print(f"    okładka nieudana (nie blokuje): {e}", flush=True)
    live_url = f"{SITE}/wiki/{cfg['slug']}/"
    return post_id, live_url


def build_mail(results, failed):
    rows = []
    for r in results:
        notes = r["entry"].get("_review_notes")
        notes_html = (f"<br><span style='color:#b07a00;font-size:12px'>uwagi recenzenta (poprawione punktowo): {len(notes)}</span>" if notes else "")
        rows.append(f"<li style='margin:8px 0'><a href='{r['live_url']}'>{r['cfg']['title']}</a>{notes_html}</li>")
    fail_html = ("<p style='color:#a00'>Nieudane: " + "; ".join(failed) + "</p>") if failed else ""
    return ("<p>Hasła Leksykonu opublikowane NA ŻYWO pod /wiki/ (sekcja noindex do Twojego akceptu) — "
            "obejrzyj layout, okładki i treść bezpośrednio na stronie:</p><ul>"
            + "".join(rows) + "</ul>"
            + f"<p>Indeks całości: <a href='{SITE}/wiki/'>{SITE}/wiki/</a></p>" + fail_html)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--limit", type=int, default=10)
    ap.add_argument("--slugs", help="comma-separated")
    ap.add_argument("--no-mail", action="store_true")
    ap.add_argument("--config", default="wiki_tier1.json", help="plik konfiguracji haseł")
    args = ap.parse_args()

    cfgs = json.loads((kb.KB_DIR / args.config).read_text())
    done = existing_slugs()
    if args.slugs:
        want = set(args.slugs.split(","))
        cfgs = [c for c in cfgs if c["slug"] in want]
    else:
        cfgs = [c for c in cfgs if c["slug"] not in done][:args.limit]

    if not cfgs:
        print("Nic do wygenerowania (wszystkie slugi istnieją).")
        return

    system_prompt = (kb.KB_DIR / "prompts" / "wiki_system.txt").read_text()
    results, failed = [], []

    def run_one(cfg):
        entry = build_entry(cfg, system_prompt)
        post_id, live_url = create_wiki_entry(cfg, entry)
        return {"cfg": cfg, "entry": entry, "post_id": post_id, "live_url": live_url}

    from concurrent.futures import ThreadPoolExecutor, as_completed
    with ThreadPoolExecutor(max_workers=3) as pool:
        futures = {pool.submit(run_one, cfg): cfg for cfg in cfgs}
        for fut in as_completed(futures):
            cfg = futures[fut]
            try:
                r = fut.result()
                results.append(r)
                print(f"OK  {r['live_url']} ({len(r['entry']['body_html'])}c, FAQ {len(r['entry']['faq'])})", flush=True)
            except Exception as e:
                failed.append(f"{cfg['slug']}: {e}")
                print(f"FAIL {cfg['slug']}: {e}", flush=True)

    print(f"\nGotowe: {len(results)} OK, {len(failed)} błędów.")
    if results and not args.no_mail:
        kb.send_mail(f"[primaauto] Leksykon: {len(results)} haseł do akceptacji",
                     build_mail(results, failed))
        print("Mail wysłany.")


if __name__ == "__main__":
    main()
