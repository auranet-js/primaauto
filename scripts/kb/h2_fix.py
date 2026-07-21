#!/usr/bin/env python3
"""
Jednorazowa łata H2 dla Tier 1: generyczne śródtytuły → H2 z frazą w odmianie.
(Tier 2 dostaje to już w prompcie — wiki_system.txt zasada 8.)
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import kb_lib as kb

# slug -> (H2 "jak działa", H2 "na co uważać", H2 FAQ)
H2 = {
    "silnik-psm": ("Jak działa silnik PSM (PMSM)", "Na co uważać, kupując auto z silnikiem PSM", "Najczęstsze pytania o silnik PSM"),
    "silnik-asm": ("Jak działa silnik asynchroniczny", "Na co uważać, kupując auto z silnikiem ASM", "Najczęstsze pytania o silnik asynchroniczny"),
    "bateria-lfp": ("Jak działa bateria LFP", "Na co uważać, kupując auto z baterią LFP", "Najczęstsze pytania o baterię LFP"),
    "bateria-nmc": ("Jak działa bateria NMC", "Na co uważać, kupując auto z baterią NMC", "Najczęstsze pytania o baterię NMC"),
    "blade-battery": ("Jak działa Blade Battery", "Na co uważać, kupując auto z Blade Battery", "Najczęstsze pytania o Blade Battery"),
    "erev": ("Jak działa napęd EREV z range extenderem", "Na co uważać, kupując auto EREV", "Najczęstsze pytania o EREV i range extender"),
    "hybryda-plug-in-phev": ("Jak działa hybryda plug-in (PHEV)", "Na co uważać, kupując chińską hybrydę plug-in", "Najczęstsze pytania o hybrydy plug-in"),
    "platforma-800v": ("Jak działa platforma 800V", "Na co uważać, kupując auto na platformie 800V", "Najczęstsze pytania o platformę 800V"),
    "lidar": ("Jak działa LiDAR w samochodzie", "Na co uważać, kupując auto z LiDAR-em", "Najczęstsze pytania o LiDAR"),
    "v2l": ("Jak działa V2L", "Na co uważać, wybierając auto z V2L", "Najczęstsze pytania o V2L"),
}


def main():
    for slug, (h2_how, h2_buy, h2_faq) in H2.items():
        post_id = kb.wp("post", "list", "--post_type=asiaauto_wiki", f"--name={slug}",
                        "--post_status=publish", "--field=ID").strip()
        if not post_id:
            print(f"{slug}: brak posta"); continue
        content = kb.wp("post", "get", post_id, "--field=post_content")
        new = (content
               .replace("<h2>Jak to działa</h2>", f"<h2>{h2_how}</h2>")
               .replace("<h2>Najczęstsze pytania</h2>", f"<h2>{h2_faq}</h2>"))
        # "Na co zwrócić uwagę przy zakupie" — wariant generyczny (z ewentualnym dopiskiem)
        import re
        new = re.sub(r"<h2>Na co zwrócić uwagę przy zakupie[^<]*</h2>", f"<h2>{h2_buy}</h2>", new)
        if new == content:
            print(f"{slug}: nic do podmiany (H2 już z frazą?)"); continue
        f = kb.STATE_DIR / f"_h2fix-{slug}.html"
        f.write_text(new)
        kb.wp("post", "update", post_id, str(f))
        f.unlink(missing_ok=True)
        print(f"{slug}: H2 podmienione")


if __name__ == "__main__":
    main()
