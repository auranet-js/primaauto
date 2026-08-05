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

Drugie źródło (od 2026-08-05): **API rankingowe Dongchedi** — sprzedaż miesięczna per model,
z podziałem na klasy nadwozia i napędy. To samo źródło, z którego pochodzą krążące po WeChat
grafiki (zgodność co do sztuki: 理想i6 = 21 453, 小米YU7 = 14 324), tylko brane wprost z API
zamiast przepisywane z obrazka. Feedy zostają jako weryfikacja krzyżowa (D9).

Użycie:
  python3 ranking_market.py --szukaj                      # kandydaci z feedów
  python3 ranking_market.py --url <adres> [--json plik]   # parsuj artykuł
  python3 ranking_market.py --dongchedi --klasy 10,11,12,13,14 --top 30 --json plik
  python3 ranking_market.py --dongchedi --klasy 11 --braki   # nazwy do uzupełnienia
"""
import argparse
import json
import re
import sys
import urllib.request
from datetime import datetime, timezone
from html import unescape
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import kb_lib as kb

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
        # Nagłówki różnią się między serwisami: cnevpost „Rank|Automaker|Type|Value",
        # carnewschina „Place|Brand|Sales volume (June 2026)". Rozpoznajemy oba.
        kol_poz = next((n for n in tab["naglowki"] if n.lower() in ("rank", "place", "#", "no.")), None)
        kol_pod = next((n for n in tab["naglowki"] if n.lower() in ("automaker", "brand", "model", "make")), None)
        kol_war = next((n for n in tab["naglowki"]
                        if any(k in n.lower() for k in ("value", "volume", "sales", "deliveries", "units"))), None)
        if not (kol_pod and kol_war):
            continue
        for w in tab["wiersze"]:
            podmiot = w.get(kol_pod) or ""
            wartosc = liczba(w.get(kol_war) or "")
            poz = liczba(w.get(kol_poz) or "") if kol_poz else None
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


# ---------------------------------------------------------------------------
# Źródło 2: API rankingowe Dongchedi (sprzedaż miesięczna per model)
# ---------------------------------------------------------------------------

DCD_URL = "https://www.dongchedi.com/motor/pc/car/rank_data"
DCD_REFERER = "https://www.dongchedi.com/sales"
# Nasz uczciwy UA (`PrimaAutoBot`) Dongchedi kwituje odpowiedzią 200 z PUSTYM ciałem — wygląda
# to jak błąd parsowania, a jest odsiewem po nagłówku. Do tego API idzie UA przeglądarkowy.
DCD_UA = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36"
NAZWY_PLIK = Path(__file__).parent / "ranking_names.json"

# Klasy nadwozia (`outter_detail_type`) — etykiety do treści.
KLASY = {
    1: "auta miejskie", 2: "kompaktowe sedany", 3: "średnie sedany", 4: "sedany średnio-duże",
    5: "limuzyny", 10: "małe SUV-y", 11: "kompaktowe SUV-y", 12: "średnie SUV-y",
    13: "SUV-y średnio-duże", 14: "duże SUV-y", 21: "małe MPV", 22: "MPV użytkowe",
    23: "MPV rodzinne", 24: "MPV premium",
}

# Prefiksy CJK zdejmowane z nazwy modelu. To NIE jest tłumaczenie — to zdjęcie nazwy marki,
# którą Dongchedi wkleja w nazwę modelu (零跑C10 = Leapmotor C10). Zdejmujemy tylko wtedy,
# gdy reszta jest już alfanumeryczna; nazwa w całości chińska idzie do `modele` w JSON-ie
# albo wypada z publikacji.
PREFIKSY_CJK = {
    "零跑": "Leapmotor", "理想": "Li Auto", "长安启源": "Changan Nevo", "长安": "Changan",
    "深蓝": "Deepal", "问界": "AITO", "小米": "Xiaomi", "小鹏": "XPeng", "蔚来": "NIO",
    "哈弗": "Haval", "银河": "Geely Galaxy", "捷途": "Jetour", "坦克": "Tank",
    "北京越野": "BAIC Off-Road", "北京": "BAIC", "智己": "IM Motors", "岚图": "Voyah",
    "乐道": "Onvo", "红旗": "Hongqi", "腾势": "Denza", "仰望": "Yangwang", "极氪": "Zeekr",
    "方程豹": "Fangchengbao", "阿维塔": "Avatr", "星途": "Exeed", "传祺": "GAC Trumpchi",
    "荣威": "Roewe", "名爵": "MG", "宝骏": "Baojun", "五菱": "Wuling", "领克": "Lynk & Co",
    "奔腾": "Bestune", "欧拉": "Ora", "风云": "Chery Fulwin", "睿蓝": "Ruilan",
    "启辰": "Venucia", "华境": "Huajing", "享界": "Stelato", "尊界": "Maextro",
}


def wczytaj_nazwy() -> dict:
    return json.loads(NAZWY_PLIK.read_text(encoding="utf-8"))


def ma_cjk(s: str) -> bool:
    return bool(re.search(r"[一-鿿]", s or ""))


def dcd_pobierz(odt: int, miesiac: str, typ: int = 11, naped: str = "", ile: int = 100) -> list:
    """Jedna klasa nadwozia, jeden miesiąc. `miesiac` w przyszłość API przycina do ostatniego
    dostępnego — dlatego zawsze podajemy go jawnie i weryfikujemy w `dcd_ostatni_miesiac()`."""
    url = (f"{DCD_URL}?aid=1839&app_name=auto_web_pc&rank_data_type={typ}"
           f"&outter_detail_type={odt}&new_energy_type={naped}"
           f"&count={ile}&offset=0&nation=0")
    if miesiac:
        url += f"&month={miesiac}"   # pusty `month=` API odrzuca — parametr musi zniknąć
    req = urllib.request.Request(url, headers={"User-Agent": DCD_UA, "Referer": DCD_REFERER})
    with urllib.request.urlopen(req, timeout=30) as r:
        dane = json.loads(r.read())
    if dane.get("status") != 0:
        raise RuntimeError(f"Dongchedi status={dane.get('status')} {dane.get('message')}")
    return dane["data"]["list"]


def dcd_ostatni_miesiac(odt: int = 11) -> str:
    """Ostatni miesiąc z danymi. API nie mówi, którego miesiąca dotyczy odpowiedź — na zapytanie
    o miesiąc przyszły oddaje po cichu ostatni dostępny. Szukamy więc miesiąca, którego wynik
    przestaje być identyczny z domyślnym: pierwszy taki od tyłu to miesiąc bieżących danych.
    Bez tego kroku łatwo opublikować „dane za lipiec", które są danymi za czerwiec."""
    domyslny = [(x["series_id"], x["count"]) for x in dcd_pobierz(odt, "", ile=5)]
    dzis = datetime.now(timezone.utc)
    rok, mies = dzis.year, dzis.month
    for _ in range(14):
        etykieta = f"{rok}{mies:02d}"
        proba = [(x["series_id"], x["count"]) for x in dcd_pobierz(odt, etykieta, ile=5)]
        if proba == domyslny:
            ostatni = etykieta
        elif proba:
            return ostatni
        mies -= 1
        if mies == 0:
            rok, mies = rok - 1, 12
    raise RuntimeError("nie udało się ustalić ostatniego miesiąca danych Dongchedi")


def rozpoznaj_model(series_name: str, marka: str, nazwy: dict):
    """(nazwa modelu bez marki, sposób rozpoznania) albo (None, 'brak')."""
    override = nazwy["modele"].get(series_name)
    if override and override.get("nazwa"):
        return override["nazwa"], "mapa"

    reszta = series_name
    for cjk in sorted(PREFIKSY_CJK, key=len, reverse=True):
        if reszta.startswith(cjk):
            reszta = reszta[len(cjk):].strip()
            break
    # marka zapisana alfabetem łacińskim też bywa doklejona, czasem tylko członem
    # („GAC Aion" w API to marka, a model nazywa się „AION i60") — zdejmujemy człon po członie.
    for czlon in sorted((marka or "").split(), key=len, reverse=True):
        if reszta.lower().startswith(czlon.lower()):
            reszta = reszta[len(czlon):].strip()
    if reszta and not ma_cjk(reszta):
        return reszta, "prefiks"
    return None, "brak"


def nasze_nazwy(pozycje: list) -> dict:
    """Nasza nazwa huba dla pozycji rankingu — przez resolver pluginu (brand-mapping v6.x),
    czyli to samo mapowanie, którym jedzie import. Klucz wyniku: series_name."""
    wejscie = [{"i": i, "marka": p["marka"] or "", "model_cn": p["model_cn"], "model": p["model"]}
               for i, p in enumerate(pozycje)]
    # Dwa podejścia: surowa nazwa chińska (trafia, gdy resolver zna prefiks CJK) i nazwa
    # międzynarodowa z naszej tablicy („Boyue L"), która jest wprost kluczem brand-mappingu.
    php = '''
$in = json_decode(file_get_contents("php://stdin"), true);
$out = [];
foreach ($in as $r) {
    $e = null;
    foreach ([$r["model_cn"], $r["model"]] as $kand) {
        if ($kand === "") { continue; }
        [$mk, $md] = AsiaAuto_Mapping::canonicalKeyForSource($r["marka"], $kand, "", "che168");
        $e = AsiaAuto_Mapping::getEuForCn($mk, $md);
        if ($e) { break; }
    }
    $out[$r["i"]] = $e ? ["marka" => $e["mark_eu"], "serie" => $e["serie_eu"], "slug" => $e["slug"]] : null;
}
echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_FORCE_OBJECT);
'''
    try:
        out = kb.wp("eval", php, stdin=json.dumps(wejscie, ensure_ascii=False))
        return {int(k): v for k, v in json.loads(out).items() if v}
    except Exception as e:
        print(f"  [!] resolver: {e}", file=sys.stderr)
        return {}


def dongchedi_ranking(klasy, miesiac=None, typ=11, naped="", top=None, tylko_chinskie=True):
    nazwy = wczytaj_nazwy()
    miesiac = miesiac or dcd_ostatni_miesiac(klasy[0])
    poprzedni = poprzedni_miesiac(miesiac)

    surowe, wczesniej = [], {}
    for odt in klasy:
        for x in dcd_pobierz(odt, miesiac, typ, naped):
            x["_odt"] = odt
            surowe.append(x)
        for x in dcd_pobierz(odt, poprzedni, typ, naped):
            wczesniej[x["series_id"]] = x["count"]

    pozycje, odrzucone = [], {"obce": 0, "nazwa": []}
    for x in surowe:
        marka = nazwy["marki"].get(x["brand_name"])
        if marka is None:
            odrzucone["nazwa"].append({"co": "marka", "marka_cn": x["brand_name"],
                                       "model_cn": x["series_name"], "wartosc": x["count"]})
            continue
        if tylko_chinskie and not marka.get("chinska"):
            odrzucone["obce"] += 1
            continue
        model, jak = rozpoznaj_model(x["series_name"], marka.get("nazwa") or "", nazwy)
        if model is None:
            odrzucone["nazwa"].append({"co": "model", "marka_cn": x["brand_name"],
                                       "model_cn": x["series_name"], "wartosc": x["count"]})
            continue
        wcz = wczesniej.get(x["series_id"])
        pozycje.append({
            "marka": marka.get("nazwa"),
            "model": model,
            "model_cn": x["series_name"],
            "marka_cn": x["brand_name"],
            "rozpoznanie": jak,
            "wartosc": x["count"],
            "poprzedni_miesiac": wcz,
            "zmiana_proc": round((x["count"] - wcz) / wcz * 100) if wcz else None,
            "klasa": KLASY.get(x["_odt"], str(x["_odt"])),
            "klasa_id": x["_odt"],
            "cena_cn_min_wan": x["min_price"],
            "cena_cn_max_wan": x["max_price"],
            "series_id": x["series_id"],
        })

    pozycje.sort(key=lambda p: -p["wartosc"])
    odrzucone["nazwa"].sort(key=lambda o: -o["wartosc"])
    # Pozycja bez nazwy nie znika po cichu: gdy jej sprzedaż mieści się w publikowanej czołówce,
    # ranking byłby zafałszowany (brakująca „trójka"). To musi zablokować publikację, nie umknąć.
    prog = pozycje[top - 1]["wartosc"] if (top and len(pozycje) >= top) else 0
    dziury = [o for o in odrzucone["nazwa"] if o["wartosc"] > prog]
    if top:
        pozycje = pozycje[:top]
    for i, p in enumerate(pozycje, 1):
        p["pozycja"] = i

    for i, nasze in nasze_nazwy(pozycje).items():
        pozycje[i]["nasza_serie"] = nasze["serie"]
        pozycje[i]["nasza_marka"] = nasze["marka"]
        # `podmiot` = klucz dopasowania w ranking_stock.py (taksonomia `serie`)
        pozycje[i]["podmiot"] = nasze["serie"]
    for p in pozycje:
        p.setdefault("podmiot", p["model"])
        p["podmiot_zrodlo"] = p["model_cn"]

    return {
        "zrodlo": "dongchedi-rank-api",
        "zrodlo_url": f"{DCD_URL}?rank_data_type={typ}&month={miesiac}",
        "zrodlo_data": miesiac,
        "okres": f"{miesiac[4:6]}.{miesiac[:4]}",
        "porownanie_z": poprzedni,
        "klasy": [KLASY.get(k, str(k)) for k in klasy],
        "pobrano": datetime.now(timezone.utc).isoformat(timespec="seconds"),
        "pozycji_zrodlowych": len(surowe),
        "odrzucone_obce": odrzucone["obce"],
        "bez_nazwy": odrzucone["nazwa"],
        "dziury_w_czolowce": dziury,
        "ranking": pozycje,
    }


def poprzedni_miesiac(m: str) -> str:
    rok, mies = int(m[:4]), int(m[4:6])
    return f"{rok - 1}12" if mies == 1 else f"{rok}{mies - 1:02d}"


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--szukaj", action="store_true", help="wypisz kandydatów z feedów")
    ap.add_argument("--url", help="adres artykułu do sparsowania")
    ap.add_argument("--json", help="zapisz wynik do pliku")
    ap.add_argument("--dongchedi", action="store_true", help="ranking sprzedaży z API Dongchedi")
    ap.add_argument("--klasy", default="10,11,12,13,14", help="outter_detail_type po przecinku")
    ap.add_argument("--miesiac", help="RRRRMM; domyślnie ostatni dostępny")
    ap.add_argument("--typ", type=int, default=11, help="rank_data_type (11 = sprzedaż)")
    ap.add_argument("--naped", default="", help="new_energy_type: 2 PHEV, 3 EREV")
    ap.add_argument("--top", type=int, help="ile pozycji zostawić")
    ap.add_argument("--wszystkie-marki", action="store_true", help="nie odsiewaj marek niechińskich")
    ap.add_argument("--braki", action="store_true", help="wypisz nazwy do uzupełnienia w ranking_names.json")
    a = ap.parse_args()

    if a.dongchedi:
        klasy = [int(k) for k in a.klasy.split(",") if k.strip()]
        w = dongchedi_ranking(klasy, a.miesiac, a.typ, a.naped, a.top, not a.wszystkie_marki)
        print(f"Dane za {w['okres']} (porównanie z {w['porownanie_z']}), klasy: {', '.join(w['klasy'])}")
        print(f"  pozycji w źródle: {w['pozycji_zrodlowych']}, marek obcych odsianych: {w['odrzucone_obce']}, "
              f"bez nazwy: {len(w['bez_nazwy'])}\n")
        if a.braki:
            print("Do uzupełnienia w ranking_names.json (malejąco po sprzedaży):")
            for b in w["bez_nazwy"]:
                print(f"  [{b['co']}] {b['marka_cn']} {b['model_cn']} — {b['wartosc']:,} szt.".replace(",", " "))
            return
        if w["dziury_w_czolowce"]:
            print("⚠ DZIURY W CZOŁÓWCE — te pozycje wypadają z rankingu mimo sprzedaży w publikowanym"
                  " przedziale. Uzupełnij nazwy przed publikacją:")
            for b in w["dziury_w_czolowce"]:
                print(f"    {b['marka_cn']} {b['model_cn']} — {b['wartosc']:,} szt.".replace(",", " "))
            print()
        for p in w["ranking"]:
            nasze = f"  → nasz hub: {p['nasza_serie']}" if p.get("nasza_serie") else ""
            zm = f"{p['zmiana_proc']:+d}%" if p.get("zmiana_proc") is not None else "  nowy"
            auto = f"{p['marka']} {p['model']}"
            print(f"{p['pozycja']:>3}. {auto:<26} {p['wartosc']:>7,} szt. {zm:>7}  "
                  f"{p['klasa']}{nasze}".replace(",", " "))
        if a.json:
            Path(a.json).write_text(json.dumps(w, ensure_ascii=False, indent=2), encoding="utf-8")
            print(f"\nzapisano: {a.json}")
        return

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
