#!/usr/bin/env python3
"""
Kadry z galerii ofert do haseł słownika (T-214): dla każdego opublikowanego hasła
bez obrazu w treści znajduje auto z technologią (term_keys po _asiaauto_extra_prep),
bierze pierwsze zdjęcie galerii i wstawia z podpisem + linkiem do ogłoszenia.

Użycie: python3 wiki_photos.py --config wiki_tier2.json
"""
import argparse
import json
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import kb_lib as kb


def listing_for(cfg):
    parts = []
    for key, val in cfg.get("term_keys", {}).items():
        if val:
            esc = json.dumps(val, ensure_ascii=True)[1:-1].replace("\\", "\\\\\\\\")
            parts.append(f"m.meta_value LIKE '%\"{key}\":\"%{esc}%'")
        else:
            parts.append(f"(m.meta_value LIKE '%\"{key}\":\"%' AND m.meta_value NOT LIKE '%\"{key}\":\"\"%')")
    if not parts:
        return None
    sql = ("SELECT p.ID, p.post_title, p.post_name FROM wp7j_posts p "
           "JOIN wp7j_postmeta m ON m.post_id=p.ID AND m.meta_key='_asiaauto_extra_prep' "
           "WHERE p.post_type='listings' AND p.post_status='publish' AND ("
           + " OR ".join(parts) + ") ORDER BY p.post_date DESC LIMIT 1")
    out = kb.wp("db", "query", sql, "--skip-column-names").strip()
    if not out:
        return None
    pid, title, slug = out.split("\t")
    return pid, title, slug


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--config", default="wiki_tier2.json")
    args = ap.parse_args()
    cfgs = json.loads((kb.KB_DIR / args.config).read_text())

    for cfg in cfgs:
        wslug = cfg["slug"]
        wpid = kb.wp("post", "list", "--post_type=asiaauto_wiki", f"--name={wslug}",
                     "--post_status=publish", "--field=ID").strip()
        if not wpid:
            print(f"{wslug}: brak posta"); continue
        content = kb.wp("post", "get", wpid, "--field=post_content")
        if "wp-block-image" in content:
            print(f"{wslug}: obraz już jest"); continue
        hit = listing_for(cfg)
        if not hit:
            print(f"{wslug}: brak dopasowania w bazie (term_keys puste lub 0 aut)"); continue
        lid, ltitle, lslug = hit
        gal = kb.wp("eval", f'$g=get_post_meta({lid},"gallery",true); $ids=is_array($g)?array_values($g):[]; echo $ids? wp_get_attachment_url((int)$ids[0]) : "";').strip()
        if not gal:
            print(f"{wslug}: listing bez galerii"); continue
        model = " ".join(ltitle.split()[:4])
        hw = cfg.get("headword", cfg["title"])
        cap = f"{model} — przykład auta z {hw} z naszej oferty"
        fig = (f'<figure class="wp-block-image"><img src="{gal}" alt="{cap}" loading="lazy" decoding="async" />'
               f'<figcaption>{cap} — <a href="https://primaauto.com.pl/oferta/{lslug}/">zobacz ogłoszenie</a></figcaption></figure>')
        idx = content.find("<h2>", 10)
        if idx < 0:
            print(f"{wslug}: brak h2"); continue
        new = content[:idx] + fig + "\n" + content[idx:]
        f = kb.STATE_DIR / f"_img-{wslug}.html"
        f.write_text(new)
        kb.wp("post", "update", wpid, str(f))
        f.unlink()
        print(f"{wslug}: foto z {ltitle[:45]} OK")


if __name__ == "__main__":
    main()
