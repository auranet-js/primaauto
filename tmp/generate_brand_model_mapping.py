#!/usr/bin/env python3
"""
Generator mapowania marek i modeli CN → EU/PL dla Prima-Auto.

Źródło danych: wp7j_posts + wp7j_term_taxonomy (make + serie).
Wynik: tmp/brand_model_mapping.csv (UTF-8 BOM, separator ';', "sep=;" w 1. linii
       — Excel PL otwiera bezpośrednio jako kolumny).

Kolumny wynikowe:
  #, Listings, Marka (obecna), Marka EU (propozycja),
  Model (obecny), Model EU (propozycja), EU market?, Typ, Uwagi, STATUS

STATUS zostawiam puste — user wypełni "OK" / "POPRAW" / "USUŃ" / "POŁĄCZ Z ...".

Mapowania pochodzą z mojej wiedzy rynkowej + nazwy exportowe producentów;
dla modeli nieznanych wpisuję "?" w kolumnie Model EU i Uwagi = "do potwierdzenia".
"""

import csv
from pathlib import Path

TSV_IN = Path("/tmp/all_models.tsv")
OUT_CSV = Path(__file__).parent / "brand_model_mapping.csv"


# ------------------------------------------------------------------
# MAPOWANIE MAREK (obecna → EU)
# ------------------------------------------------------------------
BRAND_EU = {
    # zostają
    "BYD":           ("BYD", ""),
    "AITO":          ("AITO", "Huawei-Seres, w EU pod AITO"),
    "XPeng":         ("XPENG", "oficjalnie all-caps"),
    "Xiaomi":        ("Xiaomi", ""),
    "Geely":         ("Geely", "niektóre modele mają EU-names (Monjaro, Preface)"),
    "Volkswagen":    ("Volkswagen", "europejska, ale modele SAIC-VW CN-exclusive"),
    "Hongqi":        ("Hongqi", "FAW premium, EU mało obecne"),
    "Avatr":         ("Avatr", "Changan-Huawei-CATL JV, debiut EU 2024+"),
    "Zeekr":         ("Zeekr", "Geely premium EV, obecny w EU"),
    "Denza":         ("Denza", "BYD-Mercedes JV, debiut EU"),
    "NIO":           ("NIO", "obecny w EU od 2021"),
    "Changan":       ("Changan", "debiut EU 2024+"),
    "Li Auto":       ("Li Auto", ""),
    "Volvo":         ("Volvo", "europejska (Geely owned)"),
    "Leapmotor":     ("Leapmotor", "partnerstwo Stellantis dla EU"),
    "Nissan":        ("Nissan", "japońska, JV Dongfeng-Nissan dla CN"),
    "Deepal":        ("Deepal", "Changan EV sub-brand, EU 2024+"),
    "Chery Fengyun": ("Chery Fengyun", "PHEV sub-brand Chery"),
    "Jetour":        ("Jetour", "Chery SUV/offroad, obecny w EU"),
    "Mazda":         ("Mazda", "japońska, niektóre modele z Changan-Mazda JV"),
    "Voyah":         ("Voyah", "Dongfeng premium EV"),
    "Haval":         ("Haval", "GWM, obecny w EU"),
    "IM Motors":     ("IM Motors", "SAIC premium EV, debiut EU"),
    "Tank":          ("Tank", "GWM off-road, debiut EU"),
    "iCAR":          ("iCAR", "Chery sub-brand EV"),
    "WEY":           ("WEY", "GWM premium"),
    "Great Wall":    ("GWM", "Great Wall Motors używa GWM w EU"),
    "MINI":          ("MINI", "BMW; w CN JV z Great Wall dla EV"),
    "Lotus Cars":    ("Lotus", "Geely owned, brytyjska origin"),
    "MG":            ("MG", "SAIC owned, bardzo znany w EU"),
    "Smart":         ("Smart", "Geely-Mercedes JV"),
    "Lynk & Co":     ("Lynk & Co", "Geely, obecny w EU"),

    # REBRAND / PRZYPORZĄDKOWANIE
    "Chery":           ("Chery", "w EU używa sub-brandów Omoda i Jaecoo; potwierdź czy zostawiamy Chery"),
    "Galaxy":          ("Geely Galaxy", "sub-brand Geely; w EU prawdopodobnie pod 'Geely Galaxy'"),
    "GAC Trumpchi":    ("GAC", "Trumpchi = CN name; w EU GAC (dla EV: GAC Aion)"),
    "Fangchengbao":    ("BYD Leopard", "'Formula Leopard' sub-brand BYD; PL: BYD Leopard"),
    "Exeed":           ("Omoda", "user: Exeed w EU występuje jako Omoda"),
    "Changan Qiyuan":  ("Qiyuan", "sub-brand Changan; w EU 'Qiyuan'"),
    "Jetour Shanhai":  ("Jetour", "Shanhai = offroad line Jetour; merge do Jetour?"),
    "Maextro":         ("Luxeed", "Maextro = premium JV Huawei-JAC; dla EU: Luxeed"),
    "212":             ("Beijing 212", "klasyczny off-roader BAIC, CN-only"),
    "Beijing Off-Road":("BAIC",         "Beijing Off-Road = BAIC BJ series"),
    "GAC Aion Hyper":  ("GAC Aion Hyper", "GAC EV premium line"),
    "Dongfeng Yipai":  ("Dongfeng Yipai", "sub-brand Dongfeng, CN-only"),
    "Yangwang":        ("BYD Yangwang", "BYD flagship sub-brand; w EU pod BYD Yangwang"),
}


# ------------------------------------------------------------------
# MAPOWANIE MODELI (marka, model_pl) → (model_eu, eu_market, typ, uwagi)
# ------------------------------------------------------------------
# eu_market: "Y" = obecny w EU, "N" = CN-only, "?" = niepewne
MODEL_EU = {
    # AITO
    ("AITO", "Aito M5"): ("AITO M5", "Y", "SUV", ""),
    ("AITO", "Aito M7"): ("AITO M7", "Y", "SUV", ""),
    ("AITO", "Aito M8"): ("AITO M8", "Y", "SUV", ""),
    ("AITO", "Aito M9"): ("AITO M9", "Y", "SUV", "flagship, znany w EU"),

    # Xiaomi
    ("Xiaomi", "Xiaomi SU7"):       ("Xiaomi SU7", "Y", "sedan", ""),
    ("Xiaomi", "Xiaomi SU7 Ultra"): ("Xiaomi SU7 Ultra", "Y", "sedan", ""),
    ("Xiaomi", "Xiaomi YU7"):       ("Xiaomi YU7", "Y", "SUV", ""),

    # Geely
    ("Geely", "Xingyue L"):  ("Geely Monjaro", "Y", "SUV", "Xingyue L w EU jako Monjaro (user confirmed)"),
    ("Geely", "Xingrui"):    ("Geely Preface", "Y", "sedan", "Xingrui = Preface w EU"),
    ("Geely", "Boyue L"):    ("Geely Atlas Pro", "?", "SUV", "do potwierdzenia: Boyue L → Atlas Pro?"),
    ("Geely", "Monjaro"):    ("Geely Monjaro", "Y", "SUV", ""),
    ("Geely", "Emgrand"):    ("Geely Emgrand", "Y", "sedan", ""),
    ("Geely", "Coolray"):    ("Geely Coolray", "Y", "SUV", ""),
    ("Geely", "Tugella"):    ("Geely Tugella", "Y", "SUV coupe", ""),
    ("Geely", "Okavango"):   ("Geely Okavango", "?", "SUV 7-os", ""),
    ("Geely", "Galaxy E8"):  ("Geely Galaxy E8", "?", "sedan EV", "merge: Galaxy sub-brand"),

    # BYD
    ("BYD", "Tang DM"):       ("BYD Tang DM-i", "Y", "SUV 7-os", ""),
    ("BYD", "Tang L DM"):     ("BYD Tang L DM-i", "?", "SUV 7-os", ""),
    ("BYD", "Song L EV"):     ("BYD Sealion 5 EV", "?", "SUV", "do potwierdzenia nazwa EU"),
    ("BYD", "Song L DM"):     ("BYD Sealion 5 DM-i", "?", "SUV", "do potwierdzenia"),
    ("BYD", "Song Pro DM"):   ("BYD Song Pro DM-i", "N", "SUV", "CN-focus, krótko w EU"),
    ("BYD", "Song PLUS DM"):  ("BYD Seal U DM-i", "Y", "SUV", "Song Plus = Seal U w EU"),
    ("BYD", "Han DM"):        ("BYD Han DM-i", "N", "sedan", "CN-only; Han EV w EU był"),
    ("BYD", "Han EV"):        ("BYD Han EV", "Y", "sedan", ""),
    ("BYD", "Han L EV"):      ("BYD Han L EV", "N", "sedan flagship", "CN-only long-wheelbase"),
    ("BYD", "Seal 06 DM"):    ("BYD Seal 06 DM-i", "?", "sedan", "do potwierdzenia EU"),
    ("BYD", "Seal"):          ("BYD Seal", "Y", "sedan", ""),
    ("BYD", "Seal U"):        ("BYD Seal U DM-i", "Y", "SUV", ""),
    ("BYD", "Qin L DM"):      ("BYD Qin L DM-i", "N", "sedan", "CN-only compact"),
    ("BYD", "Qin PLUS DM"):   ("BYD Qin Plus DM-i", "N", "sedan", ""),
    ("BYD", "Haishi 07 EV"):  ("BYD Sealion 7", "Y", "SUV", "Haishi 07 = Sealion 7 w EU"),
    ("BYD", "Yuan UP"):       ("BYD Atto 2", "Y", "SUV mini", "Yuan UP = Atto 2 w EU"),
    ("BYD", "Yuan Plus"):     ("BYD Atto 3", "Y", "SUV", "Yuan Plus = Atto 3 w EU"),
    ("BYD", "Dolphin"):       ("BYD Dolphin", "Y", "hatchback", ""),
    ("BYD", "Atto 3"):        ("BYD Atto 3", "Y", "SUV", ""),

    # XPeng
    ("XPeng", "XPeng P7+"):      ("XPENG P7+", "Y", "sedan", ""),
    ("XPeng", "XPeng P7"):       ("XPENG P7", "Y", "sedan", ""),
    ("XPeng", "XPeng P5"):       ("XPENG P5", "Y", "sedan", ""),
    ("XPeng", "XPeng X9"):       ("XPENG X9", "Y", "MPV", ""),
    ("XPeng", "XPeng G9"):       ("XPENG G9", "Y", "SUV", ""),
    ("XPeng", "XPeng G6"):       ("XPENG G6", "Y", "SUV coupe", ""),
    ("XPeng", "XPeng G3"):       ("XPENG G3", "Y", "SUV mini", ""),
    ("XPeng", "XPeng G7"):       ("XPENG G7", "?", "SUV", "do potwierdzenia EU"),
    ("XPeng", "XPeng MONA M03"): ("XPENG Mona M03", "Y", "sedan", ""),

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
    ("Denza", "Denza D9 DM"):    ("Denza D9 DM-i", "Y", "MPV", "premium MPV"),
    ("Denza", "Denza Z9 DM"):    ("Denza Z9 DM-i", "?", "shooting brake", ""),
    ("Denza", "Denza Z9 GT DM"): ("Denza Z9 GT DM-i", "?", "shooting brake", ""),
    ("Denza", "Denza N9 DM"):    ("Denza N9 DM-i", "?", "SUV", ""),
    ("Denza", "Denza N7 DM"):    ("Denza N7 DM-i", "?", "SUV", ""),

    # NIO
    ("NIO", "NIO ES6"):  ("NIO ES6", "Y", "SUV", ""),
    ("NIO", "NIO ES7"):  ("NIO ES7", "Y", "SUV", ""),
    ("NIO", "NIO ES8"):  ("NIO ES8", "Y", "SUV 7-os", ""),
    ("NIO", "NIO ET5"):  ("NIO ET5", "Y", "sedan", ""),
    ("NIO", "NIO ET5T"): ("NIO ET5 Touring", "Y", "kombi", ""),
    ("NIO", "NIO ET7"):  ("NIO ET7", "Y", "sedan", ""),
    ("NIO", "NIO EC6"):  ("NIO EC6", "Y", "SUV coupe", ""),

    # Changan
    ("Changan", "Changan UNI-V"):    ("Changan UNI-V", "Y", "sedan coupe", ""),
    ("Changan", "Changan UNI-T"):    ("Changan UNI-T", "Y", "SUV", ""),
    ("Changan", "Changan UNI-K"):    ("Changan UNI-K", "Y", "SUV", ""),
    ("Changan", "Changan CS75 PLUS"):("Changan CS75 Plus", "Y", "SUV", ""),
    ("Changan", "Changan CS55 PLUS"):("Changan CS55 Plus", "Y", "SUV", ""),
    ("Changan", "长安CS55 PLUS PHEV"):("Changan CS55 Plus PHEV", "?", "SUV", "translit z CN"),
    ("Changan", "Changan CS35 PLUS"):("Changan CS35 Plus", "Y", "SUV mini", ""),
    ("Changan", "Changan Eado"):     ("Changan Eado", "?", "sedan", ""),

    # Changan Qiyuan
    ("Changan Qiyuan", "Qiyuan A05"): ("Qiyuan A05", "?", "sedan", ""),
    ("Changan Qiyuan", "Qiyuan A06"): ("Qiyuan A06", "?", "sedan", ""),
    ("Changan Qiyuan", "Qiyuan A07"): ("Qiyuan A07", "?", "sedan", ""),
    ("Changan Qiyuan", "Qiyuan Q05"): ("Qiyuan Q05", "?", "SUV", ""),
    ("Changan Qiyuan", "Qiyuan Q07"): ("Qiyuan Q07", "?", "SUV", ""),

    # Deepal
    ("Deepal", "Deepal S07"):  ("Deepal S07", "Y", "SUV", ""),
    ("Deepal", "Deepal L07"):  ("Deepal L07", "Y", "sedan", ""),
    ("Deepal", "Deepal S05"):  ("Deepal S05", "?", "SUV", ""),
    ("Deepal", "Deepal G318"): ("Deepal G318", "?", "off-road SUV", ""),

    # Hongqi
    ("Hongqi", "Hongqi H5"):  ("Hongqi H5", "?", "sedan", "CN-focus"),
    ("Hongqi", "Hongqi H6"):  ("Hongqi H6", "?", "sedan coupe", ""),
    ("Hongqi", "Hongqi H9"):  ("Hongqi H9", "?", "sedan flagship", ""),
    ("Hongqi", "Hongqi HS5"): ("Hongqi HS5", "?", "SUV", ""),
    ("Hongqi", "Hongqi HS7"): ("Hongqi HS7", "?", "SUV", ""),
    ("Hongqi", "Hongqi E-HS9"):("Hongqi E-HS9", "Y", "SUV EV", ""),
    ("Hongqi", "Hongqi LS7"):  ("Hongqi LS7", "?", "SUV flagship", ""),
    ("Hongqi", "Hongqi EH7"):  ("Hongqi EH7", "?", "sedan EV", ""),
    ("Hongqi", "Hongqi EHS7"): ("Hongqi EHS7", "?", "SUV EV", ""),

    # Li Auto
    ("Li Auto", "Li Auto L6"): ("Li Auto L6", "Y", "SUV EREV", ""),
    ("Li Auto", "Li Auto L7"): ("Li Auto L7", "Y", "SUV EREV", ""),
    ("Li Auto", "Li Auto L8"): ("Li Auto L8", "Y", "SUV EREV", ""),
    ("Li Auto", "Li Auto L9"): ("Li Auto L9", "Y", "SUV EREV", ""),
    ("Li Auto", "i6"):         ("Li Auto i6", "Y", "SUV EV", ""),
    ("Li Auto", "MEGA"):       ("Li Auto MEGA", "Y", "MPV EV", ""),

    # Leapmotor
    ("Leapmotor", "Leapmotor C10"): ("Leapmotor C10", "Y", "SUV", "dostępny w EU ze Stellantis"),
    ("Leapmotor", "Leapmotor C11"): ("Leapmotor C11", "Y", "SUV", ""),
    ("Leapmotor", "Leapmotor C16"): ("Leapmotor C16", "Y", "SUV 7-os", ""),
    ("Leapmotor", "Leapmotor T03"): ("Leapmotor T03", "Y", "city car EV", ""),
    ("Leapmotor", "Leapmotor B10"): ("Leapmotor B10", "Y", "SUV kompakt", ""),
    ("Leapmotor", "零跑Lafa5"):     ("Leapmotor Lafa 5", "?", "kompakt EV", "translit z CN"),

    # GAC Trumpchi → GAC
    ("GAC Trumpchi", "Trumpchi M8"):    ("GAC M8", "?", "MPV", "rebrand Trumpchi → GAC dla EU"),
    ("GAC Trumpchi", "Trumpchi M6"):    ("GAC M6", "?", "MPV", ""),
    ("GAC Trumpchi", "Trumpchi GS8"):   ("GAC GS8", "?", "SUV", ""),
    ("GAC Trumpchi", "Trumpchi GS4"):   ("GAC GS4", "?", "SUV", ""),
    ("GAC Trumpchi", "Empow"):          ("GAC Empow", "?", "sedan", ""),
    ("GAC Trumpchi", "E8"):             ("GAC E8", "?", "MPV EV", ""),
    ("GAC Trumpchi", "E9"):             ("GAC E9", "?", "MPV EV", ""),

    # Galaxy → Geely Galaxy
    ("Galaxy", "Galaxy L7"):                  ("Geely Galaxy L7", "?", "SUV PHEV", ""),
    ("Galaxy", "Galaxy E5"):                  ("Geely Galaxy E5", "?", "SUV EV", ""),
    ("Galaxy", "Galaxy E8"):                  ("Geely Galaxy E8", "?", "sedan EV", ""),
    ("Galaxy", "Galaxy Xingyao 6"):           ("Geely Galaxy Starship 6", "?", "sedan PHEV", ""),
    ("Galaxy", "Galaxy Xingyao 8 PHEV"):      ("Geely Galaxy Starship 8 PHEV", "?", "sedan PHEV", ""),
    ("Galaxy", "Galaxy Starship 7 EM-i"):     ("Geely Galaxy Starship 7 EM-i", "?", "SUV PHEV", ""),
    ("Galaxy", "银河A7 PHEV"):                 ("Geely Galaxy A7 PHEV", "?", "sedan PHEV", "translit z CN"),
    ("Galaxy", "银河星耀6"):                   ("Geely Galaxy Starship 6", "?", "sedan", "translit z CN"),

    # Chery
    ("Chery", "Arrizo 8"):       ("Chery Arrizo 8", "?", "sedan", "w EU raczej Omoda/Jaecoo, Arrizo rzadko"),
    ("Chery", "Arrizo 8 PRO"):   ("Chery Arrizo 8 Pro", "?", "sedan", ""),
    ("Chery", "Arrizo 5"):       ("Chery Arrizo 5", "?", "sedan", ""),
    ("Chery", "Tiggo 8"):        ("Chery Tiggo 8", "Y", "SUV 7-os", ""),
    ("Chery", "Tiggo 8 PRO"):    ("Chery Tiggo 8 Pro", "Y", "SUV 7-os", ""),
    ("Chery", "Tiggo 9"):        ("Chery Tiggo 9", "Y", "SUV 7-os", ""),
    ("Chery", "Tiggo 7 PRO"):    ("Chery Tiggo 7 Pro", "Y", "SUV", ""),
    ("Chery", "Tiggo 5x"):       ("Chery Tiggo 5x", "?", "SUV", ""),
    ("Chery", "Tiggo 4"):        ("Chery Tiggo 4", "Y", "SUV mini", ""),

    # Chery Fengyun
    ("Chery Fengyun", "Fengyun A9L"):  ("Chery Fengyun A9L", "N", "sedan PHEV", "CN-only"),
    ("Chery Fengyun", "Fengyun A8L"):  ("Chery Fengyun A8L", "N", "sedan PHEV", ""),
    ("Chery Fengyun", "风云X3L"):      ("Chery Fengyun X3L", "N", "SUV PHEV", "translit z CN"),

    # Exeed → Omoda
    ("Exeed", "Exlantix ET"):   ("Omoda ET", "?", "sedan EV", "Exeed Exlantix = Omoda w EU"),
    ("Exeed", "Exlantix ES"):   ("Omoda ES", "?", "sedan EV", ""),
    ("Exeed", "Exeed RX"):      ("Omoda RX", "?", "SUV", ""),
    ("Exeed", "Exeed TXL"):     ("Omoda TXL", "?", "SUV", ""),
    ("Exeed", "Exeed VX"):      ("Omoda VX", "?", "SUV 7-os", ""),
    ("Exeed", "星途ET5"):        ("Omoda ET5", "?", "sedan", "translit Xingtu ET5 = Exeed → Omoda"),

    # Jetour
    ("Jetour", "Jetour T2"):    ("Jetour T2", "Y", "off-road SUV", ""),
    ("Jetour", "Jetour X70"):   ("Jetour X70", "Y", "SUV", ""),
    ("Jetour", "Jetour X90"):   ("Jetour X90", "Y", "SUV 7-os", ""),
    ("Jetour", "Jetour Dasheng"):("Jetour Dashing", "Y", "SUV coupe", ""),

    # Jetour Shanhai → Jetour
    ("Jetour Shanhai", "Jetour Shanhai L7"): ("Jetour Shanhai L7", "?", "off-road SUV PHEV", "merge pod Jetour?"),
    ("Jetour Shanhai", "Jetour Shanhai L9"): ("Jetour Shanhai L9", "?", "off-road SUV PHEV", ""),
    ("Jetour Shanhai", "Jetour T1"):         ("Jetour T1", "Y", "off-road SUV", ""),
    ("Jetour Shanhai", "捷途旅行者C-DM"):     ("Jetour Traveler C-DM", "?", "off-road SUV PHEV", "translit z CN"),

    # Fangchengbao → BYD Leopard
    ("Fangchengbao", "Tai 3"):     ("BYD Leopard Tai 3",     "?", "SUV mini EV", ""),
    ("Fangchengbao", "Tai 7 PHEV"):("BYD Leopard Tai 7 PHEV","?", "SUV PHEV", ""),
    ("Fangchengbao", "Bao 5"):     ("BYD Leopard 5",         "?", "SUV off-road PHEV", "user confirmed: Leopard"),
    ("Fangchengbao", "Bao 8"):     ("BYD Leopard 8",         "?", "SUV off-road PHEV", "user confirmed: BYD Leopard 8"),

    # Volkswagen (CN SAIC-VW / FAW-VW specific)
    ("Volkswagen", "Lavida"):    ("Volkswagen Lavida",   "N", "sedan", "CN-only, SAIC-VW"),
    ("Volkswagen", "Passat"):    ("Volkswagen Passat CN","N", "sedan", "CN Passat różni się od EU"),
    ("Volkswagen", "Sagitar"):   ("Volkswagen Sagitar",  "N", "sedan", "CN; w EU odpowiednik Jetta"),
    ("Volkswagen", "Tiguan L"):  ("Volkswagen Tiguan L (LWB)","N", "SUV", "long-wheelbase CN-only"),
    ("Volkswagen", "Lamando"):   ("Volkswagen Lamando",  "N", "sedan coupe", "CN-only"),
    ("Volkswagen", "Magotan"):   ("Volkswagen Magotan",  "N", "sedan", "CN Passat B8"),

    # Nissan
    ("Nissan", "Nissan N7"): ("Nissan N7",  "N", "sedan EV", "Dongfeng-Nissan, CN-only"),
    ("Nissan", "Sylphy"):    ("Nissan Sylphy","N", "sedan", "CN-only (EU: Almera)"),
    ("Nissan", "Teana"):     ("Nissan Teana","N", "sedan", "CN-only (EU: Altima)"),
    ("Nissan", "日产N6"):     ("Nissan N6",   "N", "sedan", "translit z CN"),

    # Voyah
    ("Voyah", "Voyah Free"):       ("Voyah Free",     "Y", "SUV", ""),
    ("Voyah", "Voyah Dreamer EV"): ("Voyah Dreamer EV","?", "MPV EV", ""),
    ("Voyah", "Voyah Zhiyin"):     ("Voyah Zhiyin",   "?", "sedan EV", ""),
    ("Voyah", "Voyah Courage"):    ("Voyah Courage",  "Y", "SUV EV", ""),

    # IM Motors
    ("IM Motors", "IM L6"): ("IM L6", "Y", "sedan EV", "IM = Intelligence in Motion (SAIC)"),
    ("IM Motors", "IM L7"): ("IM L7", "Y", "sedan EV", ""),
    ("IM Motors", "IM LS6"):("IM LS6","Y", "SUV EV", ""),
    ("IM Motors", "IM LS7"):("IM LS7","Y", "SUV EV", ""),

    # Tank (GWM)
    ("Tank", "Tank 300"):         ("Tank 300", "Y", "off-road SUV", ""),
    ("Tank", "Tank 400 Hi4-T"):   ("Tank 400 Hi4-T", "Y", "off-road SUV PHEV", ""),
    ("Tank", "Tank 500"):         ("Tank 500", "Y", "off-road SUV", ""),
    ("Tank", "Tank 700 Hi4-T"):   ("Tank 700 Hi4-T", "Y", "off-road SUV PHEV", ""),

    # Haval
    ("Haval", "Haval H6"):    ("Haval H6", "Y", "SUV", ""),
    ("Haval", "Haval Jolion"):("Haval Jolion", "Y", "SUV kompakt", ""),
    ("Haval", "Haval Dargo"): ("Haval Dargo",  "Y", "SUV", ""),
    ("Haval", "哈弗猛龙燃油版"):("Haval Raptor (benzyna)", "?", "off-road SUV", "translit Menglong = Raptor"),

    # iCAR
    ("iCAR", "iCAR 03"): ("iCAR 03", "Y", "SUV EV boxy", ""),
    ("iCAR", "iCAR V23"):("iCAR V23","Y", "SUV EV boxy", ""),

    # Maextro → Luxeed
    ("Maextro", "Luxeed R7"): ("Luxeed R7", "?", "SUV coupe EV", "Maextro JV Huawei-Chery; Luxeed to brand"),
    ("Maextro", "Luxeed S7"): ("Luxeed S7", "?", "sedan EV", ""),

    # WEY
    ("WEY", "Latte PHEV"): ("WEY Latte PHEV", "?", "SUV PHEV", ""),
    ("WEY", "Mocha PHEV"): ("WEY Mocha PHEV", "?", "SUV PHEV", ""),
    ("WEY", "Blue Mountain"):("WEY Blue Mountain", "?", "SUV 7-os PHEV", ""),

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
    ("Lynk & Co", "Lynk 08"): ("Lynk & Co 08", "Y", "SUV PHEV", ""),
    ("Lynk & Co", "Lynk 09"): ("Lynk & Co 09", "Y", "SUV 7-os", ""),

    # MINI
    ("MINI", "MINI Cooper SE"): ("MINI Cooper SE", "Y", "hatchback EV", "JV BMW-GWM"),
    ("MINI", "MINI Aceman"):    ("MINI Aceman",    "Y", "SUV EV", ""),

    # Beijing Off-Road → BAIC
    ("Beijing Off-Road", "BJ40"): ("BAIC BJ40", "?", "off-road SUV", ""),
    ("Beijing Off-Road", "BJ60"): ("BAIC BJ60", "?", "off-road SUV", ""),
    ("Beijing Off-Road", "BJ90"): ("BAIC BJ90", "?", "off-road SUV", ""),

    # 212
    ("212", "BJ212"):     ("Beijing 212", "N", "off-road klasyk", "CN-only, klasyczny off-roader"),
    ("212", "BAIC BJ212"):("Beijing 212", "N", "off-road klasyk", ""),

    # Great Wall (GWM)
    ("Great Wall", "Poer"):      ("GWM Poer",      "?", "pickup", ""),
    ("Great Wall", "Wingle 7"):  ("GWM Wingle 7",  "?", "pickup", ""),
    ("Great Wall", "Cannon"):    ("GWM Cannon",    "Y", "pickup", ""),

    # Volvo (CN-assembled warianty)
    ("Volvo", "Volvo S90"):        ("Volvo S90",        "Y", "sedan", ""),
    ("Volvo", "Volvo S90 PHEV"):   ("Volvo S90 T8 PHEV","Y", "sedan", ""),
    ("Volvo", "Volvo V90"):        ("Volvo V90",        "Y", "kombi", ""),
    ("Volvo", "XC70"):             ("Volvo XC70",       "?", "SUV (nowa gen PHEV)", "2025+ nowa gen PHEV CN"),
    ("Volvo", "Volvo XC40"):       ("Volvo XC40",       "Y", "SUV", ""),
    ("Volvo", "Volvo XC60"):       ("Volvo XC60",       "Y", "SUV", ""),
    ("Volvo", "Volvo XC60 PHEV"):  ("Volvo XC60 T8 PHEV","Y","SUV PHEV", ""),
    ("Volvo", "Volvo XC90 PHEV"):  ("Volvo XC90 T8 PHEV","Y","SUV 7-os PHEV", ""),

    # Mazda
    ("Mazda", "Mazda CX-5"):  ("Mazda CX-5",  "Y", "SUV", ""),
    ("Mazda", "Mazda CX-50"): ("Mazda CX-50", "N", "SUV", "głównie USA/CN"),
    ("Mazda", "Mazda CX-30"): ("Mazda CX-30", "Y", "SUV mini", ""),
    ("Mazda", "Mazda 3"):     ("Mazda 3",     "Y", "sedan/hatchback", ""),

    # GAC Aion Hyper
    ("GAC Aion Hyper", "Hyper GT"): ("GAC Aion Hyper GT", "?", "sedan EV", ""),
    ("GAC Aion Hyper", "Hyper SSR"):("GAC Aion Hyper SSR","?", "supercar EV", ""),

    # Dongfeng Yipai
    ("Dongfeng Yipai", "Yipai eπ007"): ("Dongfeng eπ 007", "?", "sedan EV", "CN-only"),
    ("Dongfeng Yipai", "Yipai eπ008"): ("Dongfeng eπ 008", "?", "SUV EV", "CN-only"),

    # Yangwang
    ("Yangwang", "Yangwang U7 PHEV"): ("BYD Yangwang U7", "?", "sedan flagship PHEV", ""),
    ("Yangwang", "Yangwang U8"):      ("BYD Yangwang U8", "?", "SUV flagship PHEV", ""),
    ("Yangwang", "Yangwang U9"):      ("BYD Yangwang U9", "?", "supercar EV", ""),
}


def slugify(text):
    """Prosta slugifikacja kebab-case dla pól 'Model EU'."""
    import re, unicodedata
    s = text.lower().strip()
    # usuń ASCII-problematyczne
    s = unicodedata.normalize("NFKD", s)
    s = "".join(c for c in s if not unicodedata.combining(c))
    s = re.sub(r"[^a-z0-9]+", "-", s)
    s = s.strip("-")
    return s


# ------------------------------------------------------------------
# Wczytaj dane z DB
# ------------------------------------------------------------------
pairs = []
for line in TSV_IN.read_text().splitlines():
    if not line.strip(): continue
    parts = line.split("\t")
    if len(parts) < 3: continue
    marka, model, count = parts[0], parts[1], int(parts[2])
    pairs.append((marka, model, count))

# posortuj: najpierw count DESC, potem marka asc, model asc
pairs.sort(key=lambda x: (-x[2], x[0], x[1]))


# ------------------------------------------------------------------
# Zbuduj wiersze
# ------------------------------------------------------------------
rows_out = []
for i, (marka, model, count) in enumerate(pairs, 1):
    # marka EU
    marka_eu_info = BRAND_EU.get(marka, (marka, "do potwierdzenia"))
    marka_eu, marka_uwaga = marka_eu_info

    # model EU
    model_key = (marka, model)
    if model_key in MODEL_EU:
        model_eu, eu_market, typ, uwagi = MODEL_EU[model_key]
    else:
        # fallback — propozycja "?" dla modelu do ręcznej weryfikacji
        # wycinam chińskie znaki jeśli są
        has_cn = any("一" <= ch <= "鿿" for ch in model)
        model_eu = "?" if has_cn else model
        eu_market = "?"
        typ = ""
        uwagi = "do potwierdzenia (fallback)" + (" — CN znaki" if has_cn else "")

    # łącz uwagi marki i modelu
    full_uwagi = uwagi
    if marka_uwaga and marka_uwaga != uwagi:
        full_uwagi = (full_uwagi + " | MARKA: " + marka_uwaga).strip(" |")

    rows_out.append({
        "#": i,
        "Listings": count,
        "Marka (obecna)": marka,
        "Marka EU (propozycja)": marka_eu,
        "Model (obecny)": model,
        "Model EU (propozycja)": model_eu,
        "EU market?": eu_market,
        "Typ": typ,
        "Slug EU (propozycja)": slugify(model_eu) if model_eu != "?" else "",
        "Uwagi": full_uwagi,
        "STATUS": "",
    })


# ------------------------------------------------------------------
# Zapisz CSV (UTF-8 BOM, separator ";", pierwsza linia sep=;)
# ------------------------------------------------------------------
COLS = ["#", "Listings", "Marka (obecna)", "Marka EU (propozycja)",
        "Model (obecny)", "Model EU (propozycja)", "EU market?",
        "Typ", "Slug EU (propozycja)", "Uwagi", "STATUS"]

with open(OUT_CSV, "w", encoding="utf-8-sig", newline="") as f:
    f.write("sep=;\n")
    w = csv.DictWriter(f, fieldnames=COLS, delimiter=";",
                       quoting=csv.QUOTE_MINIMAL)
    w.writeheader()
    for r in rows_out:
        w.writerow(r)

# --- statystyki ---
total = len(rows_out)
known = sum(1 for r in rows_out if r["Model EU (propozycja)"] not in ("?", "") and "do potwierdzenia" not in r["Uwagi"])
unknown = total - known
cn_chars = sum(1 for r in rows_out if any("一" <= ch <= "鿿" for ch in r["Model (obecny)"]))
brand_rebrand = sum(1 for r in rows_out if r["Marka (obecna)"] != r["Marka EU (propozycja)"])

print(f"wrote {OUT_CSV}")
print(f"  rows total:           {total}")
print(f"  mapowane merytorycznie: {known}")
print(f"  do potwierdzenia (?):  {unknown}")
print(f"  chińskie znaki:         {cn_chars}")
print(f"  zmiana nazwy marki:     {brand_rebrand}")
