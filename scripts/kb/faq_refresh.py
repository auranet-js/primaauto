#!/usr/bin/env python3
"""
Odświeżenie FAQ opublikowanych haseł Leksykonu na realne pytania z Google (PAA).
Jednorazowa łata dla Tier 1 (generowane zanim PAA weszło do pipeline'u);
Tier 2 dostaje PAA już w wiki_generate.py.
"""
import json
import re
import sys
from concurrent.futures import ThreadPoolExecutor, as_completed
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import kb_lib as kb
from wiki_generate import get_paa

FAQ_PROMPT = """Jesteś redaktorem Leksykonu chińskiej motoryzacji Prima-Auto. Dostajesz treść hasła oraz REALNE pytania użytkowników Google (People Also Ask).
Napisz nowe FAQ (4-5 pozycji): pytania przede wszystkim z listy PAA (możesz przeredagować stylistycznie, scal duplikaty), max 1 własne. Odpowiedzi 2-4 zdania, spójne z treścią hasła i wiedzą inżynierską, praktyczne dla kupującego auto z Chin, bez marketingu. Jeśli pytanie brzmi "które auta mają X" — odpowiedz ogólnie o klasach/markach z treści hasła i zakończ zdaniem, że aktualną listę aut z X w naszej ofercie znajdziesz poniżej hasła.
Zwróć czysty JSON: {"faq": [{"q": "...", "a": "..."}]}"""


def refresh_one(slug, main_kw):
    post_id = kb.wp("post", "list", "--post_type=asiaauto_wiki", f"--name={slug}",
                    "--post_status=publish", "--field=ID").strip()
    if not post_id:
        return f"{slug}: brak posta"
    content = kb.wp("post", "get", post_id, "--field=post_content")
    paa = get_paa(main_kw)
    if not paa:
        return f"{slug}: brak PAA — pomijam"
    body = re.split(r"<h2>Najczęstsze pytania</h2>", content)[0].rstrip()
    text, _ = kb.call_model(
        FAQ_PROMPT,
        f"TREŚĆ HASŁA:\n{kb.strip_html(body)[:6000]}\n\nPYTANIA PAA:\n- " + "\n- ".join(paa),
        max_tokens=2500,
    )
    faq = kb.parse_json_response(kb.normalize_quotes(text))["faq"]
    if len(faq) < 3:
        return f"{slug}: FAQ za krótkie — pomijam"
    faq_html = "".join(f"<details><summary>{f['q']}</summary><p>{f['a']}</p></details>" for f in faq)
    new_content = body + f"\n<h2>Najczęstsze pytania</h2>\n{faq_html}"
    body_file = kb.STATE_DIR / f"_faqref-{slug}.html"
    body_file.write_text(new_content)
    kb.wp("post", "update", post_id, str(body_file))
    kb.wp("post", "meta", "set", post_id, "_kb_faq_json", json.dumps(faq, ensure_ascii=False))
    body_file.unlink(missing_ok=True)
    return f"{slug}: OK ({len(faq)} pytań PAA)"


def main():
    cfgs = json.loads((kb.KB_DIR / "wiki_tier1.json").read_text())
    with ThreadPoolExecutor(max_workers=3) as pool:
        futures = [pool.submit(refresh_one, c["slug"], c.get("main_kw") or c["title"]) for c in cfgs]
        for fut in as_completed(futures):
            try:
                print(fut.result(), flush=True)
            except Exception as e:
                print(f"FAIL: {e}", flush=True)


if __name__ == "__main__":
    main()
