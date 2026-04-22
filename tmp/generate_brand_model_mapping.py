#!/usr/bin/env python3
"""
Generator mapowania marek i modeli CN → EU/PL dla Prima-Auto.

Wynik: tmp/brand_model_mapping.csv (UTF-8 BOM, separator ';', linia 1 = "sep=;").

KOLUMNY
-------
Marka (obecna) | Marka EU (propozycja)            — rebrand marki
Model (obecny) | Model EU — czysty (do `serie` + slug + filtra)
                 Model EU — pełna nazwa (do post_title + SEO/Ads)
Slug EU        | Tytuł preview                    — sanity check
EU market? | Typ | Uwagi | STATUS

DWA WARIANTY MODELU EU
----------------------
• "czysty"  = wartość do taksonomii `serie` + URL `/samochody/<marka>/<model>/`
             oraz do filtra modelu na froncie. Dla marek z czytelnymi nazwami
             modeli (BYD: Tang, Han, Seal...) bez marki. Dla marek z krótkimi
             kodami (AITO M9, NIO ES6, Zeekr 7X, Leopard 8) zachowuje nazwę
             linii / marki, żeby filtr był czytelny.
• "pełna"   = "Marka Model" z duplikacjami wygładzonymi — do title, meta, Ads.

REGUŁA BRAND_IN_MODEL
---------------------
Flaga per marka: True → "Model EU czysty" ma nazwę marki/linii na początku,
False → sam model. Decyzja oparta na rozpoznawalności modeli (BYD ma
nazwy - Tang, Han; AITO ma tylko M5/M7 - nieczytelne solo).
"""

import csv
import html
import re
import unicodedata
from pathlib import Path

TSV_IN = Path("/tmp/all_models.tsv")
OUT_CSV = Path(__file__).parent / "brand_model_mapping.csv"


# ------------------------------------------------------------------
# MAPOWANIE MAREK (obecna → (EU_name, uwaga, brand_in_model))
# ------------------------------------------------------------------
# brand_in_model:
#   True  = `serie` trzyma "Marka/Linia Model" (np. "AITO M9", "Leopard 8")
#   False = `serie` trzyma sam model (np. "Tang DM", "Sealion 7")

BRAND = {
    # name_eu, uwaga_marki, brand_in_model
    # KONWENCJA: brand_in_model=False wszędzie, EXCEPT sub-brandy z liniami wewnątrz:
    #   BYD Leopard (Leopard + Tai), Geely Galaxy (L/E/Starship/A — różne kodowania).
    # Filtr modelu zawsze pokazuje w kontekście wybranej marki, więc dubel marki
    # w nazwie modelu byłby redundantny.
    "BYD":              ("BYD",            "",                                                          False),
    "AITO":             ("AITO",           "Huawei-Seres, w EU pod AITO",                               False),
    "XPeng":            ("XPENG",          "oficjalnie all-caps",                                       False),
    "Xiaomi":           ("Xiaomi",         "",                                                          False),
    "Geely":            ("Geely",          "niektóre modele mają EU-names (Monjaro, Preface)",          False),
    "Volkswagen":       ("Volkswagen",     "europejska, modele SAIC-VW CN-exclusive",                   False),
    "Hongqi":           ("Hongqi",         "FAW premium, EU mało obecne",                               False),
    "Avatr":            ("Avatr",          "Changan-Huawei-CATL JV, debiut EU 2024+",                   False),
    "Zeekr":            ("Zeekr",          "Geely premium EV",                                          False),
    "Denza":            ("Denza",          "BYD-Mercedes JV",                                           False),
    "NIO":              ("NIO",            "obecny w EU od 2021",                                       False),
    "Changan":          ("Changan",        "debiut EU 2024+",                                           False),
    "Li Auto":          ("Li Auto",        "",                                                          False),
    "Volvo":            ("Volvo",          "europejska (Geely owned)",                                  False),
    "Leapmotor":        ("Leapmotor",      "partnerstwo Stellantis dla EU",                             False),
    "Nissan":           ("Nissan",         "japońska, JV Dongfeng-Nissan dla CN",                       False),
    "Deepal":           ("Deepal",         "Changan EV sub-brand, EU 2024+",                            False),
    "Chery Fengyun":    ("Chery Fengyun",  "PHEV sub-brand Chery",                                      False),
    "Jetour":           ("Jetour",         "Chery SUV/offroad, obecny w EU",                            False),
    "Mazda":            ("Mazda",          "japońska",                                                  False),
    "Voyah":            ("Voyah",          "Dongfeng premium EV",                                       False),
    "Haval":            ("Haval",          "GWM",                                                       False),
    "IM Motors":        ("IM Motors",      "SAIC premium EV",                                           False),
    "Tank":             ("Tank",           "GWM off-road",                                              False),
    "iCAR":             ("iCAR",           "Chery sub-brand EV",                                        False),
    "WEY":              ("WEY",            "GWM premium",                                               False),
    "Great Wall":       ("GWM",            "Great Wall używa GWM w EU",                                 False),
    "MINI":             ("MINI",           "BMW; w CN JV z Great Wall dla EV",                          False),
    "Lotus Cars":       ("Lotus",          "Geely owned",                                               False),
    "MG":               ("MG",             "SAIC owned",                                                False),
    "Smart":            ("Smart",          "Geely-Mercedes JV",                                         False),
    "Lynk & Co":        ("Lynk & Co",      "Geely, obecny w EU",                                        False),
    # REBRAND / MERGE
    "Chery":            ("Chery",          "w EU także sub-brandy Omoda/Jaecoo",                        False),
    "GAC Trumpchi":     ("GAC",            "Trumpchi = CN name; w EU GAC",                              False),
    "Exeed":            ("Omoda",          "user: Exeed w EU występuje jako Omoda",                     False),
    "Changan Qiyuan":   ("Qiyuan",         "sub-brand Changan",                                         False),
    "Jetour Shanhai":   ("Jetour",         "Shanhai = offroad line Jetour; merge",                      False),
    "Maextro":          ("Luxeed",         "Maextro JV Huawei-Chery; dla EU: Luxeed",                   False),
    "212":              ("Beijing 212",    "klasyczny off-roader BAIC, CN-only",                        False),
    "Beijing Off-Road": ("BAIC",           "Beijing Off-Road = BAIC BJ series",                         False),
    "GAC Aion Hyper":   ("GAC Aion Hyper", "GAC EV premium line",                                       False),
    "Dongfeng Yipai":   ("Dongfeng Yipai", "sub-brand Dongfeng",                                        False),
    "Yangwang":         ("BYD Yangwang",   "BYD flagship sub-brand; modele: U7/U8/U9",                  False),
    # inne
    "Audi":             ("Audi",           "niemiecka; JV SAIC-Audi dla CN (Audi A7L, E5 Sportback)",   False),
    # SUB-BRANDY Z WIELOMA LINIAMI (brand_in_model=True):
    "Fangchengbao":     ("BYD Leopard",    "'Formula Leopard' — linie Leopard + Tai",                   True),
    "Galaxy":           ("Geely Galaxy",   "sub-brand Geely — linie L/E/Starship/A",                    True),
}

DEFAULT_BRAND = ("", "do potwierdzenia", True)  # fallback: brand_in_model=True (bezpieczne)


# ------------------------------------------------------------------
# MAPOWANIE MODELI: (marka_pl, model_pl) → (model_eu_clean, eu_market, typ, uwagi)
# model_eu_clean = wersja do `serie` (uwzględnia brand_in_model z marki)
# ------------------------------------------------------------------
# W zapisie mapuję model "czysty":
#   - dla BYD (brand_in_model=False): bez "BYD"                → "Tang DM", "Sealion 7"
#   - dla AITO (brand_in_model=True): z "AITO"                 → "AITO M9"
#   - dla Fangchengbao→BYD Leopard:   z nazwą linii "Leopard"  → "Leopard 8", "Tai 3"
# Jeśli w tabeli poniżej wpiszesz wartość z marką gdy brand_in_model=False,
# kod automatycznie ją utnie. Jeśli wpiszesz bez marki gdy brand_in_model=True,
# nic nie uzupełni — sam musisz dodać.

MODEL = {
    # AITO
    ("AITO", "Aito M5"): ("AITO M5", "Y", "SUV", ""),
    ("AITO", "Aito M7"): ("AITO M7", "Y", "SUV", ""),
    ("AITO", "Aito M8"): ("AITO M8", "Y", "SUV", ""),
    ("AITO", "Aito M9"): ("AITO M9", "Y", "SUV", "flagship"),

    # Xiaomi
    ("Xiaomi", "Xiaomi SU7"):       ("Xiaomi SU7",       "Y", "sedan", ""),
    ("Xiaomi", "Xiaomi SU7 Ultra"): ("Xiaomi SU7 Ultra", "Y", "sedan", ""),
    ("Xiaomi", "Xiaomi YU7"):       ("Xiaomi YU7",       "Y", "SUV", ""),

    # Geely (brand_in_model=False) — czyste modele
    ("Geely", "Xingyue L"):  ("Monjaro",    "Y", "SUV",   "Xingyue L = Monjaro w EU (user confirmed)"),
    ("Geely", "Xingrui"):    ("Preface",    "Y", "sedan", "Xingrui = Preface w EU"),
    ("Geely", "Boyue L"):    ("Atlas Pro",  "?", "SUV",   "do potwierdzenia: Boyue L → Atlas Pro?"),
    ("Geely", "Monjaro"):    ("Monjaro",    "Y", "SUV",   ""),
    ("Geely", "Emgrand"):    ("Emgrand",    "Y", "sedan", ""),
    ("Geely", "Coolray"):    ("Coolray",    "Y", "SUV",   ""),
    ("Geely", "Tugella"):    ("Tugella",    "Y", "SUV coupe", ""),
    ("Geely", "Okavango"):   ("Okavango",   "?", "SUV 7-os", ""),
    ("Geely", "Galaxy E8"):  ("Galaxy E8",  "?", "sedan EV", "merge: Galaxy sub-brand"),

    # BYD (brand_in_model=False) — czyste modele
    ("BYD", "Tang DM"):       ("Tang DM-i",      "Y", "SUV 7-os", ""),
    ("BYD", "Tang L DM"):     ("Tang L DM-i",    "?", "SUV 7-os", ""),
    ("BYD", "Song L EV"):     ("Sealion 5 EV",   "?", "SUV",      "do potwierdzenia EU name"),
    ("BYD", "Song L DM"):     ("Sealion 5 DM-i", "?", "SUV",      "do potwierdzenia"),
    ("BYD", "Song Pro DM"):   ("Song Pro DM-i",  "N", "SUV",      "CN-focus"),
    ("BYD", "Song PLUS DM"):  ("Seal U DM-i",    "Y", "SUV",      "Song Plus = Seal U w EU"),
    ("BYD", "Han DM"):        ("Han DM-i",       "N", "sedan",    "CN-only"),
    ("BYD", "Han EV"):        ("Han EV",         "Y", "sedan",    ""),
    ("BYD", "Han L EV"):      ("Han L EV",       "N", "sedan flagship", "CN-only LWB"),
    ("BYD", "Seal 06 DM"):    ("Seal 06 DM-i",   "?", "sedan", "do potwierdzenia"),
    ("BYD", "Seal"):          ("Seal",           "Y", "sedan", ""),
    ("BYD", "Seal U"):        ("Seal U DM-i",    "Y", "SUV",   ""),
    ("BYD", "Qin L DM"):      ("Qin L DM-i",     "N", "sedan", "CN-only"),
    ("BYD", "Qin PLUS DM"):   ("Qin Plus DM-i",  "N", "sedan", ""),
    ("BYD", "Haishi 07 EV"):  ("Sealion 7",      "Y", "SUV",   "Haishi 07 = Sealion 7 w EU"),
    ("BYD", "Yuan UP"):       ("Atto 2",         "Y", "SUV mini", "Yuan UP = Atto 2 w EU"),
    ("BYD", "Yuan Plus"):     ("Atto 3",         "Y", "SUV",   "Yuan Plus = Atto 3 w EU"),
    ("BYD", "Dolphin"):       ("Dolphin",        "Y", "hatchback", ""),
    ("BYD", "Atto 3"):        ("Atto 3",         "Y", "SUV",   ""),

    # XPENG
    ("XPeng", "XPeng P7+"):      ("XPENG P7+",       "Y", "sedan", ""),
    ("XPeng", "XPeng P7"):       ("XPENG P7",        "Y", "sedan", ""),
    ("XPeng", "XPeng P5"):       ("XPENG P5",        "Y", "sedan", ""),
    ("XPeng", "XPeng X9"):       ("XPENG X9",        "Y", "MPV", ""),
    ("XPeng", "XPeng G9"):       ("XPENG G9",        "Y", "SUV", ""),
    ("XPeng", "XPeng G6"):       ("XPENG G6",        "Y", "SUV coupe", ""),
    ("XPeng", "XPeng G3"):       ("XPENG G3",        "Y", "SUV mini", ""),
    ("XPeng", "XPeng G7"):       ("XPENG G7",        "?", "SUV", ""),
    ("XPeng", "XPeng MONA M03"): ("XPENG Mona M03",  "Y", "sedan", ""),

    # Avatr
    ("Avatr", "Avatr 06"): ("Avatr 06", "Y", "sedan", ""),
    ("Avatr", "Avatr 07"): ("Avatr 07", "Y", "SUV", ""),
    ("Avatr", "Avatr 11"): ("Avatr 11", "Y", "SUV", ""),
    ("Avatr", "Avatr 12"): ("Avatr 12", "Y", "sedan coupe", ""),

    # Zeekr
    ("Zeekr", "ZEEKR 001"): ("Zeekr 001", "Y", "shooting brake", ""),
    ("Zeekr", "ZEEKR 007"): ("Zeekr 007", "Y", "sedan", ""),
    ("Zeekr", "ZEEKR 009"): ("Zeekr 009", "Y", "MPV", ""),
    ("Zeekr", "ZEEKR 7X"):  ("Zeekr 7X",  "Y", "SUV", ""),
    ("Zeekr", "ZEEKR X"):   ("Zeekr X",   "Y", "SUV mini", ""),

    # Denza
    ("Denza", "Denza D9 DM"):    ("Denza D9 DM-i",    "Y", "MPV", ""),
    ("Denza", "Denza Z9 DM"):    ("Denza Z9 DM-i",    "?", "shooting brake", ""),
    ("Denza", "Denza Z9 GT DM"): ("Denza Z9 GT DM-i", "?", "shooting brake", ""),
    ("Denza", "Denza N9 DM"):    ("Denza N9 DM-i",    "?", "SUV", ""),
    ("Denza", "Denza N7 DM"):    ("Denza N7 DM-i",    "?", "SUV", ""),

    # NIO
    ("NIO", "NIO ES6"):  ("NIO ES6",          "Y", "SUV", ""),
    ("NIO", "NIO ES7"):  ("NIO ES7",          "Y", "SUV", ""),
    ("NIO", "NIO ES8"):  ("NIO ES8",          "Y", "SUV 7-os", ""),
    ("NIO", "NIO ET5"):  ("NIO ET5",          "Y", "sedan", ""),
    ("NIO", "NIO ET5T"): ("NIO ET5 Touring",  "Y", "kombi", ""),
    ("NIO", "NIO ET7"):  ("NIO ET7",          "Y", "sedan", ""),
    ("NIO", "NIO EC6"):  ("NIO EC6",          "Y", "SUV coupe", ""),

    # Changan
    ("Changan", "Changan UNI-V"):    ("Changan UNI-V",    "Y", "sedan coupe", ""),
    ("Changan", "Changan UNI-T"):    ("Changan UNI-T",    "Y", "SUV", ""),
    ("Changan", "Changan UNI-K"):    ("Changan UNI-K",    "Y", "SUV", ""),
    ("Changan", "Changan CS75 PLUS"):("Changan CS75 Plus","Y", "SUV", ""),
    ("Changan", "Changan CS55 PLUS"):("Changan CS55 Plus","Y", "SUV", ""),
    ("Changan", "长安CS55 PLUS PHEV"):("Changan CS55 Plus PHEV", "?", "SUV", "translit z CN"),
    ("Changan", "Changan CS35 PLUS"):("Changan CS35 Plus","Y", "SUV mini", ""),
    ("Changan", "Changan Eado"):     ("Changan Eado",     "?", "sedan", ""),

    # Changan Qiyuan → Qiyuan  (DB trzyma "Changan Qiyuan A07" z prefixem)
    ("Changan Qiyuan", "Changan Qiyuan A05"): ("A05", "?", "sedan", ""),
    ("Changan Qiyuan", "Changan Qiyuan A06"): ("A06", "?", "sedan", ""),
    ("Changan Qiyuan", "Changan Qiyuan A07"): ("A07", "?", "sedan", ""),
    ("Changan Qiyuan", "Changan Qiyuan Q05"): ("Q05", "?", "SUV", ""),
    ("Changan Qiyuan", "Changan Qiyuan Q07"): ("Q07", "?", "SUV", ""),

    # Deepal
    ("Deepal", "Deepal S07"):  ("Deepal S07",  "Y", "SUV", ""),
    ("Deepal", "Deepal L07"):  ("Deepal L07",  "Y", "sedan", ""),
    ("Deepal", "Deepal S05"):  ("Deepal S05",  "?", "SUV", ""),
    ("Deepal", "Deepal G318"): ("Deepal G318", "?", "off-road SUV", ""),

    # Hongqi
    ("Hongqi", "Hongqi H5"):   ("Hongqi H5",    "?", "sedan", ""),
    ("Hongqi", "Hongqi H6"):   ("Hongqi H6",    "?", "sedan coupe", ""),
    ("Hongqi", "Hongqi H9"):   ("Hongqi H9",    "?", "sedan flagship", ""),
    ("Hongqi", "Hongqi HS5"):  ("Hongqi HS5",   "?", "SUV", ""),
    ("Hongqi", "Hongqi HS7"):  ("Hongqi HS7",   "?", "SUV", ""),
    ("Hongqi", "Hongqi E-HS9"):("Hongqi E-HS9", "Y", "SUV EV", ""),
    ("Hongqi", "Hongqi LS7"):  ("Hongqi LS7",   "?", "SUV flagship", ""),
    ("Hongqi", "Hongqi EH7"):  ("Hongqi EH7",   "?", "sedan EV", ""),
    ("Hongqi", "Hongqi EHS7"): ("Hongqi EHS7",  "?", "SUV EV", ""),

    # Li Auto
    ("Li Auto", "Li Auto L6"): ("Li Auto L6", "Y", "SUV EREV", ""),
    ("Li Auto", "Li Auto L7"): ("Li Auto L7", "Y", "SUV EREV", ""),
    ("Li Auto", "Li Auto L8"): ("Li Auto L8", "Y", "SUV EREV", ""),
    ("Li Auto", "Li Auto L9"): ("Li Auto L9", "Y", "SUV EREV", ""),
    ("Li Auto", "i6"):         ("Li Auto i6", "Y", "SUV EV", ""),
    ("Li Auto", "MEGA"):       ("Li Auto MEGA","Y", "MPV EV", ""),

    # Leapmotor
    ("Leapmotor", "Leapmotor C10"): ("Leapmotor C10", "Y", "SUV", ""),
    ("Leapmotor", "Leapmotor C11"): ("Leapmotor C11", "Y", "SUV", ""),
    ("Leapmotor", "Leapmotor C16"): ("Leapmotor C16", "Y", "SUV 7-os", ""),
    ("Leapmotor", "Leapmotor T03"): ("Leapmotor T03", "Y", "city car EV", ""),
    ("Leapmotor", "Leapmotor B10"): ("Leapmotor B10", "Y", "SUV kompakt", ""),
    ("Leapmotor", "零跑Lafa5"):     ("Leapmotor Lafa 5", "?", "kompakt EV", "translit z CN"),

    # GAC Trumpchi → GAC
    ("GAC Trumpchi", "Trumpchi M8"):  ("GAC M8",   "?", "MPV", ""),
    ("GAC Trumpchi", "Trumpchi M6"):  ("GAC M6",   "?", "MPV", ""),
    ("GAC Trumpchi", "Trumpchi GS8"): ("GAC GS8",  "?", "SUV", ""),
    ("GAC Trumpchi", "Trumpchi GS4"): ("GAC GS4",  "?", "SUV", ""),
    ("GAC Trumpchi", "Empow"):        ("GAC Empow","?", "sedan", ""),
    ("GAC Trumpchi", "E8"):           ("GAC E8",   "?", "MPV EV", ""),
    ("GAC Trumpchi", "E9"):           ("GAC E9",   "?", "MPV EV", ""),

    # Galaxy → Geely Galaxy
    ("Galaxy", "Galaxy L7"):              ("Galaxy L7",                 "?", "SUV PHEV", ""),
    ("Galaxy", "Galaxy E5"):              ("Galaxy E5",                 "?", "SUV EV", ""),
    ("Galaxy", "Galaxy E8"):              ("Galaxy E8",                 "?", "sedan EV", ""),
    ("Galaxy", "Galaxy Xingyao 6"):       ("Galaxy Starship 6",         "?", "sedan PHEV", "Xingyao 6 = Starship 6"),
    ("Galaxy", "Galaxy Xingyao 8 PHEV"):  ("Galaxy Starship 8 PHEV",    "?", "sedan PHEV", "Xingyao 8 = Starship 8"),
    ("Galaxy", "Galaxy Starship 7 EM-i"): ("Galaxy Starship 7 EM-i",    "?", "SUV PHEV", ""),
    ("Galaxy", "银河A7 PHEV"):             ("Galaxy A7 PHEV",            "?", "sedan PHEV", "translit z CN"),
    ("Galaxy", "银河星耀6"):               ("Galaxy Starship 6",         "?", "sedan", "translit z CN"),

    # Chery
    ("Chery", "Arrizo 8"):     ("Arrizo 8",     "?", "sedan", "w EU raczej Omoda/Jaecoo"),
    ("Chery", "Arrizo 8 PRO"): ("Arrizo 8 Pro", "?", "sedan", ""),
    ("Chery", "Arrizo 5"):     ("Arrizo 5",     "?", "sedan", ""),
    ("Chery", "Tiggo 8"):      ("Tiggo 8",      "Y", "SUV 7-os", ""),
    ("Chery", "Tiggo 8 PRO"):  ("Tiggo 8 Pro",  "Y", "SUV 7-os", ""),
    ("Chery", "Tiggo 9"):      ("Tiggo 9",      "Y", "SUV 7-os", ""),
    ("Chery", "Tiggo 7 PRO"):  ("Tiggo 7 Pro",  "Y", "SUV", ""),
    ("Chery", "Tiggo 5x"):     ("Tiggo 5x",     "?", "SUV", ""),
    ("Chery", "Tiggo 4"):      ("Tiggo 4",      "Y", "SUV mini", ""),

    # Chery Fengyun
    ("Chery Fengyun", "Fengyun A9L"): ("Fengyun A9L", "N", "sedan PHEV", "CN-only"),
    ("Chery Fengyun", "Fengyun A8L"): ("Fengyun A8L", "N", "sedan PHEV", ""),
    ("Chery Fengyun", "风云X3L"):     ("Fengyun X3L", "N", "SUV PHEV", "translit z CN"),

    # Exeed → Omoda
    ("Exeed", "Exlantix ET"): ("Omoda ET",  "?", "sedan EV", "Exlantix = Omoda"),
    ("Exeed", "Exlantix ES"): ("Omoda ES",  "?", "sedan EV", ""),
    ("Exeed", "Exeed RX"):    ("Omoda RX",  "?", "SUV", ""),
    ("Exeed", "Exeed TXL"):   ("Omoda TXL", "?", "SUV", ""),
    ("Exeed", "Exeed VX"):    ("Omoda VX",  "?", "SUV 7-os", ""),
    ("Exeed", "星途ET5"):     ("Omoda ET5", "?", "sedan", "translit Xingtu ET5"),

    # Jetour
    ("Jetour", "Jetour T2"):      ("Jetour T2",      "Y", "off-road SUV", ""),
    ("Jetour", "Jetour X70"):     ("Jetour X70",     "Y", "SUV", ""),
    ("Jetour", "Jetour X90"):     ("Jetour X90",     "Y", "SUV 7-os", ""),
    ("Jetour", "Jetour Dasheng"): ("Jetour Dashing", "Y", "SUV coupe", ""),

    # Jetour Shanhai → Jetour (merge)
    ("Jetour Shanhai", "Jetour Shanhai L7"): ("Jetour Shanhai L7",   "?", "off-road SUV PHEV", ""),
    ("Jetour Shanhai", "Jetour Shanhai L9"): ("Jetour Shanhai L9",   "?", "off-road SUV PHEV", ""),
    ("Jetour Shanhai", "Jetour T1"):         ("Jetour T1",           "Y", "off-road SUV", ""),
    ("Jetour Shanhai", "捷途旅行者C-DM"):     ("Jetour Traveler C-DM","?", "off-road SUV PHEV", "translit z CN"),

    # Fangchengbao → BYD Leopard  (linia "Leopard" / "Tai" w nazwie modelu)
    ("Fangchengbao", "Tai 3"):      ("Tai 3",          "?", "SUV mini EV", ""),
    ("Fangchengbao", "Tai 7 PHEV"): ("Tai 7 PHEV",     "?", "SUV PHEV", ""),
    ("Fangchengbao", "Bao 5"):      ("Leopard 5",      "?", "SUV off-road PHEV", ""),
    ("Fangchengbao", "Bao 8"):      ("Leopard 8",      "?", "SUV off-road PHEV", "user confirmed"),
    ("Fangchengbao", "Leopard 5"):  ("Leopard 5",      "?", "SUV off-road PHEV", ""),
    ("Fangchengbao", "Leopard 8"):  ("Leopard 8",      "?", "SUV off-road PHEV", ""),

    # Volkswagen (CN-specific)
    ("Volkswagen", "Lavida"):   ("Volkswagen Lavida",   "N", "sedan", "CN-only"),
    ("Volkswagen", "Passat"):   ("Volkswagen Passat CN","N", "sedan", "CN Passat ≠ EU"),
    ("Volkswagen", "Sagitar"):  ("Volkswagen Sagitar",  "N", "sedan", "CN; EU: Jetta"),
    ("Volkswagen", "Tiguan L"): ("Volkswagen Tiguan L LWB","N", "SUV", "CN LWB"),
    ("Volkswagen", "Lamando"):  ("Volkswagen Lamando",  "N", "sedan coupe", "CN-only"),
    ("Volkswagen", "Magotan"):  ("Volkswagen Magotan",  "N", "sedan", "CN Passat B8"),

    # Nissan
    ("Nissan", "Nissan N7"): ("Nissan N7",    "N", "sedan EV", "Dongfeng-Nissan CN"),
    ("Nissan", "Sylphy"):    ("Nissan Sylphy","N", "sedan", "CN; EU: Almera"),
    ("Nissan", "Teana"):     ("Nissan Teana", "N", "sedan", "CN; EU: Altima"),
    ("Nissan", "日产N6"):     ("Nissan N6",    "N", "sedan", "translit z CN"),

    # Voyah
    ("Voyah", "Voyah Free"):       ("Voyah Free",       "Y", "SUV", ""),
    ("Voyah", "Voyah Dreamer EV"): ("Voyah Dreamer EV", "?", "MPV EV", ""),
    ("Voyah", "Voyah Zhiyin"):     ("Voyah Zhiyin",     "?", "sedan EV", ""),
    ("Voyah", "Voyah Courage"):    ("Voyah Courage",    "Y", "SUV EV", ""),

    # IM Motors
    ("IM Motors", "IM L6"): ("IM L6", "Y", "sedan EV", ""),
    ("IM Motors", "IM L7"): ("IM L7", "Y", "sedan EV", ""),
    ("IM Motors", "IM LS6"):("IM LS6","Y", "SUV EV", ""),
    ("IM Motors", "IM LS7"):("IM LS7","Y", "SUV EV", ""),

    # Tank
    ("Tank", "Tank 300"):       ("Tank 300",       "Y", "off-road SUV", ""),
    ("Tank", "Tank 400 Hi4-T"): ("Tank 400 Hi4-T", "Y", "off-road SUV PHEV", ""),
    ("Tank", "Tank 500"):       ("Tank 500",       "Y", "off-road SUV", ""),
    ("Tank", "Tank 700 Hi4-T"): ("Tank 700 Hi4-T", "Y", "off-road SUV PHEV", ""),

    # Haval
    ("Haval", "Haval H6"):      ("Haval H6",     "Y", "SUV", ""),
    ("Haval", "Haval Jolion"):  ("Haval Jolion", "Y", "SUV kompakt", ""),
    ("Haval", "Haval Dargo"):   ("Haval Dargo",  "Y", "SUV", ""),
    ("Haval", "哈弗猛龙燃油版"):  ("Haval Raptor (benzyna)", "?", "off-road SUV", "translit Menglong = Raptor"),

    # iCAR
    ("iCAR", "iCAR 03"):  ("iCAR 03",  "Y", "SUV EV boxy", ""),
    ("iCAR", "iCAR V23"): ("iCAR V23", "Y", "SUV EV boxy", ""),

    # Maextro → Luxeed
    ("Maextro", "Luxeed R7"): ("Luxeed R7", "?", "SUV coupe EV", ""),
    ("Maextro", "Luxeed S7"): ("Luxeed S7", "?", "sedan EV", ""),

    # WEY
    ("WEY", "Latte PHEV"):   ("WEY Latte PHEV",   "?", "SUV PHEV", ""),
    ("WEY", "Mocha PHEV"):   ("WEY Mocha PHEV",   "?", "SUV PHEV", ""),
    ("WEY", "Blue Mountain"):("WEY Blue Mountain","?", "SUV 7-os PHEV", ""),

    # Lotus
    ("Lotus Cars", "Lotus Eletre"): ("Lotus Eletre", "Y", "SUV EV", ""),
    ("Lotus Cars", "Lotus Emira"):  ("Lotus Emira",  "Y", "coupe", ""),
    ("Lotus Cars", "Lotus Emeya"):  ("Lotus Emeya",  "Y", "sedan EV", ""),

    # MG
    ("MG", "MG4"):  ("MG4 EV", "Y", "hatchback EV", ""),
    ("MG", "MG5"):  ("MG5",    "Y", "kombi", ""),
    ("MG", "MG ZS"):("MG ZS",  "Y", "SUV mini", ""),

    # Smart
    ("Smart", "Smart #1"): ("Smart #1", "Y", "SUV mini EV", ""),
    ("Smart", "Smart #3"): ("Smart #3", "Y", "SUV coupe EV", ""),

    # Lynk & Co
    ("Lynk & Co", "Lynk 08"): ("Lynk 08", "Y", "SUV PHEV", ""),
    ("Lynk & Co", "Lynk 09"): ("Lynk 09", "Y", "SUV 7-os", ""),

    # MINI
    ("MINI", "MINI Cooper SE"): ("MINI Cooper SE", "Y", "hatchback EV", ""),
    ("MINI", "MINI Aceman"):    ("MINI Aceman",    "Y", "SUV EV", ""),

    # Beijing Off-Road → BAIC
    ("Beijing Off-Road", "BJ40"): ("BAIC BJ40", "?", "off-road SUV", ""),
    ("Beijing Off-Road", "BJ60"): ("BAIC BJ60", "?", "off-road SUV", ""),
    ("Beijing Off-Road", "BJ90"): ("BAIC BJ90", "?", "off-road SUV", ""),
    ("Beijing Off-Road", "Beijing Off-road BJ40"): ("BAIC BJ40", "?", "off-road SUV", ""),

    # 212
    ("212", "BJ212"):      ("BJ212", "N", "off-road klasyk", "CN-only"),
    ("212", "BAIC BJ212"): ("BJ212", "N", "off-road klasyk", ""),

    # GWM (Great Wall)
    ("Great Wall", "Poer"):     ("GWM Poer",     "?", "pickup", ""),
    ("Great Wall", "Wingle 7"): ("GWM Wingle 7", "?", "pickup", ""),
    ("Great Wall", "Cannon"):   ("GWM Cannon",   "Y", "pickup", ""),

    # Volvo
    ("Volvo", "Volvo S90"):       ("Volvo S90",         "Y", "sedan", ""),
    ("Volvo", "Volvo S90 PHEV"):  ("Volvo S90 T8 PHEV", "Y", "sedan", ""),
    ("Volvo", "Volvo V90"):       ("Volvo V90",         "Y", "kombi", ""),
    ("Volvo", "XC70"):            ("Volvo XC70",        "?", "SUV PHEV", "2025+ nowa gen"),
    ("Volvo", "Volvo XC40"):      ("Volvo XC40",        "Y", "SUV", ""),
    ("Volvo", "Volvo XC60"):      ("Volvo XC60",        "Y", "SUV", ""),
    ("Volvo", "Volvo XC60 PHEV"): ("Volvo XC60 T8 PHEV","Y", "SUV PHEV", ""),
    ("Volvo", "Volvo XC90 PHEV"): ("Volvo XC90 T8 PHEV","Y", "SUV 7-os PHEV", ""),
    ("Volvo", "Volvo S60"):       ("Volvo S60",         "Y", "sedan", ""),

    # Mazda
    ("Mazda", "Mazda CX-5"):  ("Mazda CX-5",  "Y", "SUV", ""),
    ("Mazda", "Mazda CX-50"): ("Mazda CX-50", "N", "SUV", "USA/CN"),
    ("Mazda", "Mazda CX-30"): ("Mazda CX-30", "Y", "SUV mini", ""),
    ("Mazda", "Mazda 3"):     ("Mazda 3",     "Y", "sedan/hatch", ""),

    # GAC Aion Hyper
    ("GAC Aion Hyper", "Hyper GT"):  ("GAC Aion Hyper GT",  "?", "sedan EV", ""),
    ("GAC Aion Hyper", "Hyper SSR"): ("GAC Aion Hyper SSR", "?", "supercar EV", ""),

    # Dongfeng Yipai
    ("Dongfeng Yipai", "Yipai eπ007"): ("Dongfeng eπ 007", "?", "sedan EV", "CN-only"),
    ("Dongfeng Yipai", "Yipai eπ008"): ("Dongfeng eπ 008", "?", "SUV EV", "CN-only"),

    # Yangwang → BYD Yangwang (linia "Yangwang" w modelu)
    ("Yangwang", "Yangwang U7 PHEV"): ("Yangwang U7", "?", "sedan flagship PHEV", ""),
    ("Yangwang", "Yangwang U8"):      ("Yangwang U8", "?", "SUV flagship PHEV", ""),
    ("Yangwang", "Yangwang U9"):      ("Yangwang U9", "?", "supercar EV", ""),
}


# ------------------------------------------------------------------
# Slug helper
# ------------------------------------------------------------------
def slugify(text):
    if not text or text == "?":
        return ""
    s = unicodedata.normalize("NFKD", text.lower().strip())
    s = "".join(c for c in s if not unicodedata.combining(c))
    s = s.replace("+", "-plus").replace("#", "hash")
    s = re.sub(r"[^a-z0-9]+", "-", s)
    return s.strip("-")


def full_title(marka_eu, model_clean, brand_in_model):
    """
    Buduje 'Marka Model' unikając duplikacji:
      - gdy model zaczyna się od całej marki → zwraca sam model
      - gdy ostatnie słowo marki = pierwsze słowo modelu → ucina ostatnie słowo marki
        (np. 'BYD Leopard' + 'Leopard 8' → 'BYD Leopard 8')
    """
    if not model_clean or model_clean == "?":
        return "?"
    if model_clean.lower().startswith(marka_eu.lower() + " "):
        return model_clean
    if model_clean.lower() == marka_eu.lower():
        return model_clean
    marka_words = marka_eu.split()
    model_words = model_clean.split()
    if (len(marka_words) >= 2 and model_words
            and marka_words[-1].lower() == model_words[0].lower()):
        return " ".join(marka_words[:-1]) + " " + model_clean
    return f"{marka_eu} {model_clean}"


def normalize_clean(model_raw, brand_in_model, marka_eu, marka_orig=""):
    """
    Dla brand_in_model=False: upewnij się że model_raw NIE zaczyna się od marki
    (ani EU, ani oryginalnej CN). Ucina:
      • pełną markę EU ("XPENG P7+" → "P7+")
      • ostatnie słowo wielowyrazowej marki EU ("BYD Yangwang" + "Yangwang U7" → "U7")
      • pełną markę CN ("Changan Qiyuan" + "Changan Qiyuan A07" → "A07")
      • gdy model == marka → ""
    """
    if brand_in_model:
        return model_raw

    # 1) prefix = cała marka EU
    if model_raw.lower().startswith(marka_eu.lower() + " "):
        return model_raw[len(marka_eu)+1:].strip()
    if model_raw.lower() == marka_eu.lower():
        return ""

    # 2) prefix = cała marka CN (gdy rebrand, np. "Changan Qiyuan" → "Qiyuan")
    if marka_orig and marka_orig.lower() != marka_eu.lower():
        if model_raw.lower().startswith(marka_orig.lower() + " "):
            return model_raw[len(marka_orig)+1:].strip()
        if model_raw.lower() == marka_orig.lower():
            return ""

    # 3) prefix = ostatnie słowo wielowyrazowej marki EU (np. "BYD Yangwang" →
    #    model zaczyna się od "Yangwang ")
    marka_words = marka_eu.split()
    if len(marka_words) >= 2:
        last = marka_words[-1]
        if model_raw.lower().startswith(last.lower() + " "):
            return model_raw[len(last)+1:].strip()
        if model_raw.lower() == last.lower():
            return ""

    return model_raw


# ------------------------------------------------------------------
# Wczytaj dane z DB
# ------------------------------------------------------------------
pairs = []
for line in TSV_IN.read_text().splitlines():
    if not line.strip(): continue
    parts = line.split("\t")
    if len(parts) < 3: continue
    marka = html.unescape(parts[0])  # "Lynk &amp; Co" → "Lynk & Co"
    model = html.unescape(parts[1])
    count = int(parts[2])
    pairs.append((marka, model, count))

pairs.sort(key=lambda x: (-x[2], x[0], x[1]))


# ------------------------------------------------------------------
# Zbuduj wiersze
# ------------------------------------------------------------------
rows_out = []
for i, (marka, model, count) in enumerate(pairs, 1):
    # Fallback gdy marka nie jest zmapowana — zachowaj oryginał, nie ucinaj
    marka_eu_info = BRAND.get(marka, (marka, "do potwierdzenia (marka nie zmapowana)", False))
    marka_eu, marka_uwaga, brand_in_model = marka_eu_info

    model_key = (marka, model)
    if model_key in MODEL:
        model_clean, eu_market, typ, uwagi = MODEL[model_key]
        model_clean = normalize_clean(model_clean, brand_in_model, marka_eu, marka)
    else:
        has_cn = any("一" <= ch <= "鿿" for ch in model)
        if has_cn:
            model_clean = "?"
        else:
            model_clean = normalize_clean(model, brand_in_model, marka_eu, marka)
        eu_market = "?"
        typ = ""
        uwagi = "do potwierdzenia (fallback)" + (" — CN znaki" if has_cn else "")

    # Model EU — pełna nazwa (do title, SEO, Ads)
    model_full = full_title(marka_eu, model_clean, brand_in_model)

    # Slug z model_clean + marka (dla URL /samochody/<marka-slug>/<model-slug>/)
    slug_marka = slugify(marka_eu)
    slug_model = slugify(model_clean)

    # Tytuł preview — z poprawionym full_title (po zmianie importera — patrz ADR)
    tytul_preview = f"{model_full} 2025" if model_full != "?" else "?"

    # URL huba preview
    url_preview = f"/samochody/{slug_marka}/{slug_model}/" if slug_model else "?"

    # Połącz uwagi
    full_uwagi = uwagi
    if marka_uwaga and marka_uwaga != uwagi:
        full_uwagi = (full_uwagi + " | MARKA: " + marka_uwaga).strip(" |")

    rows_out.append({
        "#": i,
        "Listings": count,
        "Marka (obecna)": marka,
        "Marka EU": marka_eu,
        "Model (obecny)": model,
        "Model EU — do `serie` (filtr/URL)": model_clean,
        "Model EU — pełna nazwa (do title/SEO)": model_full,
        "Slug URL huba": url_preview,
        "Tytuł preview": tytul_preview,
        "EU market?": eu_market,
        "Typ": typ,
        "Uwagi": full_uwagi,
        "STATUS": "",
    })


# ------------------------------------------------------------------
# Zapisz CSV
# ------------------------------------------------------------------
COLS = [
    "#", "Listings",
    "Marka (obecna)", "Marka EU",
    "Model (obecny)", "Model EU — do `serie` (filtr/URL)",
    "Model EU — pełna nazwa (do title/SEO)",
    "Slug URL huba", "Tytuł preview",
    "EU market?", "Typ", "Uwagi", "STATUS",
]

with open(OUT_CSV, "w", encoding="utf-8-sig", newline="") as f:
    f.write("sep=;\n")
    w = csv.DictWriter(f, fieldnames=COLS, delimiter=";", quoting=csv.QUOTE_MINIMAL)
    w.writeheader()
    for r in rows_out:
        w.writerow(r)

# --- statystyki ---
total = len(rows_out)
known = sum(1 for r in rows_out if "do potwierdzenia" not in r["Uwagi"])
unknown = total - known
cn_chars = sum(1 for r in rows_out if any("一" <= ch <= "鿿" for ch in r["Model (obecny)"]))
brand_rebrand = sum(1 for r in rows_out if r["Marka (obecna)"] != r["Marka EU"])
brand_in_model_true = sum(1 for r in rows_out
                          if BRAND.get(r["Marka (obecna)"], DEFAULT_BRAND)[2])

print(f"wrote {OUT_CSV}")
print(f"  wierszy:                    {total}")
print(f"  zmapowane merytorycznie:    {known}")
print(f"  do potwierdzenia (fallback):{unknown}")
print(f"  chińskie znaki:             {cn_chars}")
print(f"  zmiana nazwy marki:         {brand_rebrand}")
print(f"  brand_in_model=True:        {brand_in_model_true}")
