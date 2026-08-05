#!/usr/bin/env python3
"""
ranking_specs.py — warstwa danych PARAMETRYCZNYCH dla rankingów (T-229, Task 1.3).

Rankingi sprzedażowe biorą liczby z API Dongchedi. Rankingi parametryczne (zasięg, bateria,
ładowanie, przyspieszenie) nie mają takiego źródła — dane techniczne siedzą w katalogu Autohome,
który zaciągamy per oferta do `_asiaauto_extra_prep`. Stan na 2026-08-05: 1599 ofert
z pojemnością baterii i 1528 z zasięgiem CLTC, razem 216 modeli.

Zakres jest inny niż w rankingach sprzedaży i trzeba to mówić wprost w treści (D2): to nie jest
pełen rynek chiński, tylko **modele osiągalne w imporcie** — czyli te, dla których mamy kartę
katalogową. Ranking sprzedaży opisuje, co Chińczycy kupują; ten opisuje, co da się sprowadzić.

Zasięg podajemy w **CLTC z jawną etykietą** (D5): chińska norma jest łagodniejsza od WLTP
mniej więcej o jedną czwartą i mieszanie ich w jednej kolumnie byłoby wprowadzaniem w błąd.

Użycie:
  python3 ranking_specs.py --parametr zasieg --top 20
  python3 ranking_specs.py --parametr bateria --top 20 --json plik
"""
import argparse
import json
import re
import sys
from datetime import date, datetime, timezone
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import kb_lib as kb

NAZWY_PLIK = Path(__file__).parent / "ranking_names.json"

PARAMETRY = {
    "zasieg": {
        "pole": "cltc_recharge_mileage",
        "jednostka": "km",
        "etykieta": "Zasięg CLTC",
        "napedy": ["bev"],            # zasięg elektryczny liczymy tylko dla aut w pełni elektrycznych
    },
    "bateria": {
        "pole": "battery_capacity",
        "jednostka": "kWh",
        "etykieta": "Bateria",
        "napedy": ["bev", "phev", "erev"],
    },
    "przyspieszenie": {
        "pole": "acceleration_time",
        "jednostka": "s",
        "etykieta": "0-100 km/h",
        "napedy": ["bev", "phev", "erev", "hev", "benzyna", "diesel"],
        "rosnaco": True,            # tu wygrywa wartość NAJMNIEJSZA
        "min": 1.5,                 # poniżej 1,5 s to błąd w karcie, nie osiąg
    },
    "cena": {
        "pole": "official_price",
        "jednostka": "万 CNY",
        "etykieta": "Cena w Chinach",
        "napedy": ["bev", "phev", "erev", "hev", "benzyna", "diesel"],
    },
}

# Kod napędu → slug w naszej taksonomii `fuel`. Dwa słowniki nazywają to samo inaczej
# („bev" vs „electric"), a bramka porównuje je wprost — bez tej mapy odrzuca wszystko.
KOD_NA_SLUG = {"bev": "electric", "phev": "phev", "erev": "erev", "hev": "hybrid",
               "benzyna": "petrol", "diesel": "diesel"}

# `fuel_form` z katalogu → nasze etykiety
NAPEDY = {
    "纯电动": ("bev", "elektryczny"),
    "插电式混合动力": ("phev", "hybryda plug-in"),
    "增程式": ("erev", "z range extenderem"),
    "油电混合": ("hev", "hybryda"),
    "汽油": ("benzyna", "benzyna"),
    "柴油": ("diesel", "diesel"),
}

# Producenci ogniw — chińskie nazwy w katalogu, u nas nazwy, pod którymi są znani
OGNIWA = {
    "宁德时代": "CATL", "比亚迪": "BYD (FinDreams)", "弗迪电池": "BYD (FinDreams)",
    "中创新航": "CALB", "国轩高科": "Gotion", "亿纬锂能": "EVE", "欣旺达": "Sunwoda",
    "蜂巢能源": "SVOLT", "力神": "Lishen", "孚能科技": "Farasis", "孚能": "Farasis",
    "蓝谷智慧": "Blue Valley", "正力新能": "Zenergy", "瑞浦兰钧": "REPT",
    "巨湾技研": "Greater Bay", "中航锂电": "CALB", "塔菲尔": "Tafel", "捷威动力": "Jeve",
}
TYPY_OGNIW = {
    "三元锂电池": "NMC", "磷酸铁锂电池": "LFP", "磷酸锰铁锂电池": "LMFP",
    "钠离子电池": "sodowo-jonowa", "半固态电池": "półstała", "固态电池": "stała",
    "锰酸锂电池": "LMO", "钛酸锂电池": "LTO",
}


def marki_niechinskie() -> set:
    """Marki europejskie/japońskie/amerykańskie produkowane w Chinach — poza rankingiem
    „chińskich" aut. Źródło to ta sama tablica, co przy rankingach sprzedaży."""
    nazwy = json.loads(NAZWY_PLIK.read_text(encoding="utf-8"))["marki"]
    obce = {v["nazwa"] for v in nazwy.values() if v.get("nazwa") and not v.get("chinska")}
    # Marki, których nie ma w tablicy CJK, bo nie wchodzą do rankingów sprzedaży,
    # a w naszej ofercie występują (auta z chińskich fabryk pod europejską marką).
    obce |= {"Volvo", "Polestar", "Smart", "Lotus", "Tesla", "Toyota", "Honda", "Nissan",
             "Mazda", "Volkswagen", "Audi", "Ford", "Mercedes-Benz", "BMW", "Kia", "Hyundai",
             "Skoda", "Buick", "Chevrolet", "Cadillac", "Lincoln", "MINI", "Citroen",
             "Peugeot", "Land Rover", "Lexus", "Infiniti", "Jetta"}
    return obce


def zbierz_dane() -> list:
    """Jedno wejście do bazy: wszystkie oferty publish z kartą katalogową."""
    php = '''
$q = new WP_Query(["post_type"=>"listings","post_status"=>"publish","posts_per_page"=>-1,
    "fields"=>"ids","no_found_rows"=>true]);
$out = [];
foreach ($q->posts as $id) {
    $raw = get_post_meta($id, "_asiaauto_extra_prep", true);
    if (!$raw) { continue; }
    $d = json_decode($raw, true);
    if (!is_array($d)) { continue; }
    $bat = (float) ($d["battery_capacity"] ?? 0);
    $cltc = (float) ($d["cltc_recharge_mileage"] ?? 0);
    if ($bat <= 0 && $cltc <= 0) { continue; }
    $serie = wp_get_post_terms($id, "serie", ["fields" => "all"]);
    $make  = wp_get_post_terms($id, "make", ["fields" => "names"]);
    if (!$serie) { continue; }
    $fuel = wp_get_post_terms($id, "fuel", ["fields" => "slugs"])[0] ?? "";
    $out[] = [
        "id" => $id,
        "marka" => $make[0] ?? "",
        "model" => $serie[0]->name,
        "slug" => $serie[0]->slug,
        "url" => get_term_link($serie[0]),
        "bateria" => $bat,
        "zasieg" => $cltc,
        "naped" => (string) ($d["fuel_form"] ?? ""),
        "naped_tax" => $fuel,
        "ogniwa" => (string) ($d["battery_brand"] ?? ""),
        "typ_ogniw" => (string) ($d["battery_type"] ?? ""),
        "ladowanie" => (string) ($d["fast_charge_time"] ?? ""),
        "przyspieszenie" => (float) ($d["acceleration_time"] ?? 0),
        "predkosc" => (float) ($d["max_speed"] ?? 0),
        "moc_el" => (float) ($d["electric_total_horsepower"] ?? 0),
        "moc_sp" => (float) ($d["engine_max_horsepower"] ?? 0),
        "cena_cn" => (string) ($d["official_price"] ?? ""),
        "cena" => (int) get_post_meta($id, "price", true),
        "wersja" => get_the_title($id),
    ];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
'''
    return json.loads(kb.wp("eval", php) or "[]")


def wartosc_parametru(parametr: str, o: dict):
    """Wartość, po której ustawiamy ranking. Cena z karty katalogowej przychodzi jako
    „81.80万" — bierzemy liczbę, jednostkę (dziesiątki tysięcy juanów) niesie nagłówek."""
    if parametr == "zasieg":
        return o["zasieg"] or None
    if parametr == "bateria":
        return o["bateria"] or None
    if parametr == "przyspieszenie":
        return o["przyspieszenie"] or None
    if parametr == "cena":
        m = re.match(r"([\d.]+)", (o.get("cena_cn") or "").replace(",", ""))
        return float(m.group(1)) if m else None
    return None


def ranking_parametryczny(parametr: str, top: int = 20, min_wartosc: float = 0) -> dict:
    cfg = PARAMETRY[parametr]
    obce = marki_niechinskie()
    oferty = zbierz_dane()

    najlepsze, odrzucone = {}, []
    for o in oferty:
        if o["marka"] in obce:
            continue
        kod, etykieta = NAPEDY.get(o["naped"], ("inny", o["naped"]))
        # Napęd musi zgadzać się w DWÓCH źródłach: karcie katalogowej i naszej taksonomii.
        # Pomiar 2026-08-05: rozjazd dotyczy 6 z 1528 ofert, ale trafia dokładnie tam, gdzie
        # boli — Denza Z9 GT ma w katalogu „纯电动" i CLTC 1036 km, co jest zasięgiem
        # ŁĄCZNYM hybrydy plug-in, nie elektrycznym. Bez tej bramki plug-in stanąłby
        # na czele rankingu zasięgu elektryków.
        oczekiwany = KOD_NA_SLUG.get(kod, kod)
        if o.get("naped_tax") and o["naped_tax"] != oczekiwany:
            odrzucone.append(f"{o['marka']} {o['model']}: katalog {kod} vs taksonomia {o['naped_tax']}")
            continue
        if kod not in cfg["napedy"]:
            continue
        # Hub nazwany po hybrydzie (DM-i, PHEV, EM-i) nie wchodzi do rankingu elektryków,
        # nawet gdy trafiła do niego wersja EV — nazwa modelu w tabeli byłaby myląca.
        if cfg["napedy"] == ["bev"] and re.search(r"\b(DM-i|DM|PHEV|EM-i|EM-P)\b", o["model"]):
            odrzucone.append(f"{o['marka']} {o['model']}: hub hybrydowy, wersja EV")
            continue
        wartosc = wartosc_parametru(parametr, o)
        if wartosc is None or wartosc <= max(min_wartosc, cfg.get("min", 0)):
            continue
        klucz = (o["marka"], o["model"])
        lepsza = (klucz not in najlepsze or
                  (wartosc < najlepsze[klucz]["wartosc"] if cfg.get("rosnaco")
                   else wartosc > najlepsze[klucz]["wartosc"]))
        if lepsza:
            najlepsze[klucz] = {
                "marka": o["marka"], "model": o["model"], "podmiot": o["model"],
                "podmiot_zrodlo": o["model"], "nasza_serie": o["model"], "nasza_marka": o["marka"],
                "wartosc": round(wartosc, 1) if cfg.get("rosnaco") else int(round(wartosc)),
                "jednostka": cfg["jednostka"],
                "naped": etykieta,
                "bateria": int(round(o["bateria"])) if o["bateria"] else None,
                "zasieg": int(round(o["zasieg"])) if o["zasieg"] else None,
                "ogniwa": OGNIWA.get(o["ogniwa"], o["ogniwa"] if not re.search(r"[一-鿿]", o["ogniwa"]) else ""),
                "typ_ogniw": TYPY_OGNIW.get(o["typ_ogniw"], ""),
                "klasa": etykieta,
                "wersja": o["wersja"],
                "przyspieszenie": o["przyspieszenie"] or None,
                "predkosc": int(o["predkosc"]) if o["predkosc"] else None,
                "moc": int(max(o["moc_el"], o["moc_sp"])) if max(o["moc_el"], o["moc_sp"]) else None,
                "cena_cn": o["cena_cn"],
            }

    pozycje = sorted(najlepsze.values(),
                     key=lambda p: p["wartosc"] if cfg.get("rosnaco") else -p["wartosc"])[:top]
    for i, p in enumerate(pozycje, 1):
        p["pozycja"] = i
        p["zmiana_proc"] = None

    dzis = date.today()
    return {
        "zrodlo": "katalog-autohome",
        "zrodlo_url": "https://www.autohome.com.cn/",
        "zrodlo_data": f"{dzis.year}{dzis.month:02d}",
        "okres": dzis.isoformat(),
        "porownanie_z": "",
        "parametr": parametr,
        "etykieta": cfg["etykieta"],
        "jednostka": cfg["jednostka"],
        "pobrano": datetime.now(timezone.utc).isoformat(timespec="seconds"),
        "pozycji_zrodlowych": len(oferty),
        "modeli_z_danymi": len(najlepsze),
        "bez_nazwy": [],
        "odrzucone_rozjazd": sorted(set(odrzucone)),
        "dziury_w_czolowce": [],
        "ranking": pozycje,
    }


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--parametr", choices=list(PARAMETRY), required=True)
    ap.add_argument("--top", type=int, default=20)
    ap.add_argument("--json")
    a = ap.parse_args()

    w = ranking_parametryczny(a.parametr, a.top)
    print(f"{w['etykieta']} — {w['modeli_z_danymi']} modeli z danymi "
          f"(z {w['pozycji_zrodlowych']} ofert z kartą katalogową)")
    if w["odrzucone_rozjazd"]:
        print(f"  odrzucone przy rozjeździe źródeł: {len(w['odrzucone_rozjazd'])}")
        for o in w["odrzucone_rozjazd"][:6]:
            print(f"    · {o}")
    print()
    for p in w["ranking"]:
        extra = []
        if p["bateria"] and a.parametr == "zasieg":
            extra.append(f"{p['bateria']} kWh")
        if p["zasieg"] and a.parametr == "bateria":
            extra.append(f"{p['zasieg']} km CLTC")
        if p["ogniwa"]:
            extra.append(p["ogniwa"])
        if p["typ_ogniw"]:
            extra.append(p["typ_ogniw"])
        print(f"{p['pozycja']:>3}. {p['marka']} {p['model']:<20} {p['wartosc']:>5} {p['jednostka']}"
              f"   {p['naped']:<18} {', '.join(extra)}")

    if a.json:
        Path(a.json).write_text(json.dumps(w, ensure_ascii=False, indent=2), encoding="utf-8")
        print(f"\nzapisano: {a.json}")


if __name__ == "__main__":
    main()
