#!/usr/bin/env python3
"""
ranking_market.py — warstwa danych RYNKOWYCH dla rankingów (T-229, Task 1).

Ranking opisuje rynek chiński, nie nasz magazyn (D1) — bo oferta rotuje, a wiedza zostaje.
Liczby biorą się wyłącznie ze zweryfikowanych źródeł (D2): cnevpost i carnewschina, które
cytują CPCA i komunikaty producentów. Zrzuty z WeChat są tropem tematycznym, nie źródłem —
twórcy nie podają metodologii, a dwa zrzuty tej samej listy potrafią mieć różne wartości.

Skąd dane: artykuły typu „Roundup: July 2026 deliveries by major automakers in China"
zawierają gotowe tabele HTML — ranking miesięczny plus szeregi czasowe per marka
(pomiar 2026-08-04: 11 tabel w jednym artykule, ranking 16 pozycji + 10 szeregów 2024-2026).

Użycie:
  python3 ranking_market.py --szukaj                      # kandydaci z feedów
  python3 ranking_market.py --url <adres> [--json plik]   # parsuj artykuł
"""
import argparse
import json
import re
import sys
import urllib.request
from datetime import datetime, timezone
from html import unescape
from pathlib import Path

FEEDY = [
    "https://cnevpost.com/feed/",
    "https://carnewschina.com/feed/",
]

# Frazy w tytule, po których poznajemy artykuł z danymi liczbowymi
SYGNALY = ("roundup", "deliveries", "sales", "wholesale", "cpca", "ranking", "top ", "insurance registrations")

UA = "Mozilla/5.0 (compatible; PrimaAutoBot/1.0; +https://primaauto.com.pl)"

# Nazwy grup i marek u źródła → nasze nazewnictwo (taksonomia `make`).
# Puste = grupa kapitałowa bez odpowiednika w naszej taksonomii (zostaje jak jest).
NORMALIZACJA = {
    "byd": "BYD",
    "chery group": "Chery",
    "chery automobile": "Chery",
    "geely auto": "Geely",
    "geely automobile": "Geely",
    "saic-gm-wuling": "Wuling",
    "leapmotor": "Leapmotor",
    "huawei hima": "AITO",          # HIMA to sojusz; w naszej ofercie reprezentuje go AITO
    "aito": "AITO",
    "xpeng": "XPeng",
    "nio": "NIO",
    "li auto": "Li Auto",
    "zeekr": "Zeekr",
    "xiaomi": "Xiaomi",
    "xiaomi auto": "Xiaomi",
    "xiaomi ev": "Xiaomi",
    "great wall motor": "GWM",
    "gwm": "GWM",
    "changan": "Changan",
    "changan automobile": "Changan",
    "dongfeng": "Dongfeng",
    "gac": "GAC",
    "gac aion": "GAC",
    "faw": "Hongqi",
    "hongqi": "Hongqi",
    "denza": "Denza",
    "voyah": "Voyah",
    "avatr": "Avatr",
    "im motors": "IM Motors",
    "onvo": "Onvo",
    "firefly": "Firefly",
    "fang cheng bao": "Fangchengbao",
    "yangwang": "Yangwang",
    "tesla china": "Tesla",
}


def pobierz(url: str, timeout: int = 30) -> str:
    req = urllib.request.Request(url, headers={"User-Agent": UA})
    with urllib.request.urlopen(req, timeout=timeout) as r:
        return r.read().decode("utf-8", errors="replace")


def czysc(s: str) -> str:
    return re.sub(r"\s+", " ", unescape(re.sub(r"<[^>]+>", " ", s))).strip()


def znajdz_kandydatow():
    """Artykuły z feedów, które prawdopodobnie zawierają dane liczbowe."""
    out = []
    for feed in FEEDY:
        try:
            xml = pobierz(feed)
        except Exception as e:
            print(f"  [!] {feed}: {e}", file=sys.stderr)
            continue
        for item in re.findall(r"(?s)<item>(.*?)</item>", xml):
            tyt = re.search(r"(?s)<title>(.*?)</title>", item)
            link = re.search(r"(?s)<link>(.*?)</link>", item)
            data = re.search(r"(?s)<pubDate>(.*?)</pubDate>", item)
            if not (tyt and link):
                continue
            t = czysc(tyt.group(1))
            if any(s in t.lower() for s in SYGNALY):
                out.append({
                    "tytul": t,
                    "url": link.group(1).strip(),
                    "data": czysc(data.group(1)) if data else "",
                    "zrodlo": "cnevpost" if "cnevpost" in feed else "carnewschina",
                })
    return out


def parsuj_tabele(html: str):
    """Wyciąga tabele HTML jako listy słowników (nagłówek → wartość)."""
    tabele = []
    for t in re.findall(r"(?s)<table.*?</table>", html):
        wiersze = re.findall(r"(?s)<tr.*?</tr>", t)
        if len(wiersze) < 2:
            continue
        naglowki = [czysc(c) for c in re.findall(r"(?s)<t[dh][^>]*>(.*?)</t[dh]>", wiersze[0])]
        dane = []
        for w in wiersze[1:]:
            komorki = [czysc(c) for c in re.findall(r"(?s)<t[dh][^>]*>(.*?)</t[dh]>", w)]
            if not any(komorki):
                continue
            dane.append(dict(zip(naglowki, komorki)) if len(komorki) == len(naglowki)
                        else {"_kolumny": komorki})
        if dane:
            tabele.append({"naglowki": naglowki, "wiersze": dane})
    return tabele


def liczba(s: str):
    """„419,211" → 419211. Zwraca None, gdy to nie liczba — nie zgadujemy."""
    s = (s or "").replace(" ", " ").strip()
    if not re.fullmatch(r"[\d,.\s]+", s) or not re.search(r"\d", s):
        return None
    try:
        return int(re.sub(r"[^\d]", "", s))
    except ValueError:
        return None


def normalizuj(nazwa: str) -> str:
    klucz = re.sub(r"\s+", " ", (nazwa or "")).strip().lower()
    klucz = re.sub(r"\s*\(.*?\)\s*", "", klucz)
    return NORMALIZACJA.get(klucz, nazwa.strip())


def parsuj_artykul(url: str):
    html = pobierz(url)
    tyt = re.search(r"(?s)<title>(.*?)</title>", html)
    tabele = parsuj_tabele(html)

    ranking = []
    for tab in tabele:
        h = [n.lower() for n in tab["naglowki"]]
        # tabela rankingowa: pozycja + podmiot + wartość
        if not ({"rank", "automaker"} <= set(h) or {"rank", "brand"} <= set(h)):
            continue
        for w in tab["wiersze"]:
            podmiot = w.get("Automaker") or w.get("Brand") or ""
            wartosc = liczba(w.get("Value") or w.get("Volume") or "")
            poz = liczba(w.get("Rank") or "")
            if not podmiot or wartosc is None:
                continue
            ranking.append({
                "pozycja": poz,
                "podmiot_zrodlo": podmiot,
                "podmiot": normalizuj(podmiot),
                "wartosc": wartosc,
                "typ": w.get("Type", ""),
            })
        break   # pierwsza tabela rankingowa wystarcza

    return {
        "url": url,
        "tytul": czysc(tyt.group(1)) if tyt else "",
        "pobrano": datetime.now(timezone.utc).isoformat(timespec="seconds"),
        "tabel_w_artykule": len(tabele),
        "ranking": ranking,
        "szeregi_czasowe": sum(1 for t in tabele if "Month" in t["naglowki"]),
    }


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--szukaj", action="store_true", help="wypisz kandydatów z feedów")
    ap.add_argument("--url", help="adres artykułu do sparsowania")
    ap.add_argument("--json", help="zapisz wynik do pliku")
    a = ap.parse_args()

    if a.szukaj:
        kand = znajdz_kandydatow()
        print(f"Kandydatów z danymi: {len(kand)}\n")
        for k in kand:
            print(f"  [{k['zrodlo']}] {k['tytul'][:82]}")
            print(f"      {k['url']}")
        return

    if not a.url:
        ap.error("podaj --url albo --szukaj")

    wynik = parsuj_artykul(a.url)
    print(f"{wynik['tytul'][:90]}")
    print(f"  tabel: {wynik['tabel_w_artykule']} (w tym {wynik['szeregi_czasowe']} szeregów czasowych)")
    print(f"  pozycji rankingu: {len(wynik['ranking'])}\n")
    for r in wynik["ranking"]:
        zmiana = "" if r["podmiot"] == r["podmiot_zrodlo"] else f"  ← {r['podmiot_zrodlo']}"
        print(f"   {str(r['pozycja'] or '?'):>3}. {r['podmiot']:<18} {r['wartosc']:>8,} {r['typ']:<12}{zmiana}".replace(",", " "))

    if a.json:
        Path(a.json).write_text(json.dumps(wynik, ensure_ascii=False, indent=2), encoding="utf-8")
        print(f"\nzapisano: {a.json}")


if __name__ == "__main__":
    main()
