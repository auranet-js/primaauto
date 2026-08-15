<?php
/**
 * che168-model-map.php — ręczne nadpisania resolvera dla Che168 (T-185).
 *
 * Klucz: "CheMark|CheModel" — DOKŁADNIE jak zwraca getOffer(che168) (mark + model surowy, z CJK).
 * Wartość: [mark_eu, serie_eu, title_eu, slug] — ten sam kształt co brand-mapping-v6.1.
 * Sprawdzana PRZED algorytmem reverse-index w AsiaAuto_Mapping::resolveChe168().
 *
 * Seed: tmp/che168-mapping-proposal-2026-06-05 (recon). Dorabiane w fazie obserwacji T-185.
 */

return array (
  // --- T-186 sync (2026-07-22): che168 skraca nazwy modeli BYD ---
  // 宋L EV → che168 'L EV'; 宋L DM-i → 'L DM-i'; 海豹06新能源 → '海豹06'.
  'BYD|L EV' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Song L EV',
    'title_eu' => 'BYD Song L EV',
    'slug' => 'song-l-ev',
  ),
  'BYD|L DM-i' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Song L DM-i',
    'title_eu' => 'BYD Song L DM-i',
    'slug' => 'song-l-dm-i',
  ),
  // che168 trzyma DM-i i EV pod jednym '海豹06'. Domyślny wariant = DM-i (dominujący),
  // EV rozstrzygane przez `by_engine` (T-222, 2026-07-30 — wcześniej 2 oferty EV wpadły do huba DM-i).
  'BYD|海豹06' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Seal 6 DM-i',
    'title_eu' => 'BYD Seal 6 DM-i',
    'slug' => 'seal-6-dm-i',
    'by_engine' => array (
      'electric' => array (
        'mark_eu' => 'BYD',
        'serie_eu' => 'Seal 6 EV',
        'title_eu' => 'BYD Seal 6',
        'slug' => 'seal-6-ev',
      ),
    ),
  ),
  'BYD|海豹06 DM-i旅行版' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Seal 6 DM Wagon',
    'title_eu' => 'BYD Seal 6 DM Wagon',
    'slug' => 'seal-6-dm-wagon',
  ),
  // --- Aliasy nazw domowych CN ≠ feed eksportowy (T-186, 2026-06-17). Nieredukowalne algorytmem:
  //     che168 używa chińskiej nazwy rynkowej, brand-mapping kluczowany stringiem Dongchedi. ---
  'Beijing Off-Road|北京越野BJ60增程' =>
  array (
    'mark_eu' => 'BAIC',
    'serie_eu' => 'BJ60',
    'title_eu' => 'BAIC BJ60',
    'slug' => 'bj60',
  ),
  'Wey|Wey Lanshan' =>
  array (
    'mark_eu' => 'WEY',
    'serie_eu' => '07',
    'title_eu' => 'WEY 07 Blue Mountain',
    'slug' => '07',
  ),
  'eπ|eπ008' =>
  array (
    'mark_eu' => 'Dongfeng',
    'serie_eu' => 'eπ008',
    'title_eu' => 'Dongfeng eπ008',
    'slug' => 'e-008',
  ),
  'Li Auto|Li L9' =>
  array (
    'mark_eu' => 'Li Auto',
    'serie_eu' => 'L9',
    'title_eu' => 'Li Auto L9',
    'slug' => 'l9',
  ),
  'Fang Cheng Bao|Leopard 5' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Leopard 5 (Denza B5)',
    'title_eu' => 'BYD Leopard 5 (Denza B5)',
    'slug' => 'leopard-5',
  ),
  // che168 trzyma PHEV i EV pod jednym 'Dreamer'. Domyślny = PHEV (dominujący, 41 ofert),
  // EV rozstrzygane przez `by_engine` (2026-07-31 — pierwszy EV Dreamer, oferta 398795
  // 岚图梦想家 2025款 EV 四驱尊贵鲲鹏版, wpadł do huba PHEV).
  'VOYAH|Dreamer' =>
  array (
    'mark_eu' => 'Voyah',
    'serie_eu' => 'Dream PHEV',
    'title_eu' => 'Voyah Dream PHEV',
    'slug' => 'dream-phev',
    'by_engine' => array (
      'electric' => array (
        'mark_eu' => 'Voyah',
        'serie_eu' => 'Dream EV',
        'title_eu' => 'Voyah Dream EV',
        'slug' => 'dream-ev',
      ),
    ),
  ),
  // 2026-07-27: BŁĄD DANYCH auto-api — dla 岚图追光 (Voyah Passion) pole `model` zwraca
  //             dosłownie "Zeekr" (obca marka). Zweryfikowane katalogiem: specid 59292 =
  //             岚图追光 2024款 PHEV 四驱超长续航旗舰版, seriesid 6915 (Passion, nie Passion L 8259).
  //             Bez tego aliasu importer zakłada serię "Zeekr" pod marką Voyah.
  //             Override ślepy na napęd (jak VOYAH|Dreamer) — wariant EV wymagałby engine_type.
  'VOYAH|Zeekr' =>
  array (
    'mark_eu' => 'Voyah',
    'serie_eu' => 'Passion PHEV',
    'title_eu' => 'Voyah Passion PHEV',
    'slug' => 'passion-phev',
  ),
  // che168 podaje nazwę CN ze spacją przed literą wersji (dongchedi bez).
  'VOYAH|岚图追光 L' =>
  array (
    'mark_eu' => 'Voyah',
    'serie_eu' => 'Passion L',
    'title_eu' => 'Voyah Passion L',
    'slug' => 'passion-l',
  ),
  // --- Aliasy nazw domowych — fala 2 (T-186, 2026-06-17, kolejne 50 ogłoszeń). ---
  'Li Auto|Li L6' =>
  array (
    'mark_eu' => 'Li Auto',
    'serie_eu' => 'L6',
    'title_eu' => 'Li Auto L6',
    'slug' => 'l6',
  ),
  'NIO|ET5T' =>
  array (
    'mark_eu' => 'NIO',
    'serie_eu' => 'ET5 Touring',
    'title_eu' => 'NIO ET5 Touring',
    'slug' => 'et5-touring',
  ),
  // Changan CS75 Plus — che168 trzyma pod wariantem trim/CJK; oba → ten sam hub.
  'Changan|CS75 PLUS iDD' =>
  array (
    'mark_eu' => 'Changan',
    'serie_eu' => 'CS75 Plus',
    'title_eu' => 'Changan CS75 Plus',
    'slug' => 'cs75-plus',
  ),
  'Changan|长安CS75PLUS' =>
  array (
    'mark_eu' => 'Changan',
    'serie_eu' => 'CS75 Plus',
    'title_eu' => 'Changan CS75 Plus',
    'slug' => 'cs75-plus',
  ),
  // che168 trzyma DM-i i EV pod jednym 'Han'. Domyślny = DM-i (dominujący, 52 oferty),
  // EV rozstrzygane przez `by_engine` (T-222, 2026-07-30).
  'BYD|Han' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Han DM-i',
    'title_eu' => 'BYD Han',
    'slug' => 'han-dm-i',
    'by_engine' => array (
      'electric' => array (
        'mark_eu' => 'BYD',
        'serie_eu' => 'Han EV',
        'title_eu' => 'BYD Han EV',
        'slug' => 'han-ev',
      ),
    ),
  ),
  // che168 trzyma EV i DM-i pod jednym 'Qin L'. Domyślny = EV, DM-i rozstrzygane przez
  // `by_engine` (2026-08-10 — wcześniej 2 oferty DM-i wpadły do huba EV; che168 podaje dla nich
  // displacement 1.5 i ice_power_kw 74, więc engine_type 'plug-in hybrid' jest wiarygodny).
  'BYD|Qin L' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Qin L EV',
    'title_eu' => 'BYD Qin L EV',
    'slug' => 'qin-l-ev',
    'by_engine' => array (
      // Klucz PO normalizacji enum-mapy ('plug-in hybrid' → 'phev') — patrz engineKey().
      'phev' => array (
        'mark_eu' => 'BYD',
        'serie_eu' => 'Qin L DM-i',
        'title_eu' => 'BYD Qin L',
        'slug' => 'qin-l-dm-i',
      ),
    ),
  ),
  // che168 trzyma DM-i i EV pod jednym 'Qin PLUS'. Domyślny = DM-i, EV przez `by_engine`
  // (2026-08-10 — wcześniej 1 oferta EV wpadła do huba DM-i).
  'BYD|Qin PLUS' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Qin Plus DM-i',
    'title_eu' => 'BYD Qin Plus DM-i',
    'slug' => 'qin-plus-dm-i',
    'by_engine' => array (
      'electric' => array (
        'mark_eu' => 'BYD',
        'serie_eu' => 'Qin PLUS EV',
        'title_eu' => 'BYD Qin PLUS',
        'slug' => 'qin-plus-ev',
      ),
    ),
  ),
  'Changan|CS55PLUS' => 
  array (
    'mark_eu' => 'Changan',
    'serie_eu' => 'CS55 Plus',
    'title_eu' => 'Changan CS55 Plus',
    'slug' => 'cs55-plus',
  ),
  'Changan|UNI-V' => 
  array (
    'mark_eu' => 'Changan',
    'serie_eu' => 'UNI-V',
    'title_eu' => 'Changan UNI-V',
    'slug' => 'uni-v',
  ),
  // che168 trzyma DM-i i EV pod jednym 'Denza D9'. Domyślny = DM-i, EV przez `by_engine`
  // (2026-08-10 — wcześniej 1 oferta EV wpadła do huba DM-i).
  'Denza|Denza D9' =>
  array (
    'mark_eu' => 'Denza',
    'serie_eu' => 'D9 DM-i',
    'title_eu' => 'Denza D9 DM-i',
    'slug' => 'd9-dm-i',
    'by_engine' => array (
      'electric' => array (
        'mark_eu' => 'Denza',
        'serie_eu' => 'D9 EV',
        'title_eu' => 'Denza D9 EV',
        'slug' => 'd9-ev',
      ),
    ),
  ),
  'Denza|Denza Z9' => 
  array (
    'mark_eu' => 'Denza',
    'serie_eu' => 'Z9 DM-i',
    'title_eu' => 'Denza Z9 DM-i',
    'slug' => 'z9-dm-i',
  ),
  // che168 trzyma DM-i i EV pod jednym 'Denza Z9GT' (rocznik 2026 ma 5 wersji: 2 PHEV + 3 EV).
  // Domyślny = DM-i, EV przez `by_engine` (2026-08-10, zgłoszenie Ruslana — wcześniej 2 oferty EV
  // wpadły do huba DM-i; che168 podaje dla nich displacement 0.0 i puste ice_power_kw).
  'Denza|Denza Z9GT' =>
  array (
    'mark_eu' => 'Denza',
    'serie_eu' => 'Z9 GT DM-i',
    'title_eu' => 'Denza Z9 GT DM-i',
    'slug' => 'z9-gt-dm-i',
    'by_engine' => array (
      'electric' => array (
        'mark_eu' => 'Denza',
        'serie_eu' => 'Z9 GT EV',
        'title_eu' => 'Denza Z9 GT EV',
        'slug' => 'z9-gt-ev',
      ),
    ),
  ),
  // USUNIĘTY 2026-07-27: override 'Denza|腾势N8L' → serie_eu 'N8L' short-circuitował krok 0
  // resolveChe168() i nie dopuszczał do kroku 3 (warianty napędu). Efekt: che168 budował
  // drugi hub /samochody/denza/n8l/ (0 impresji w GSC) obok rankującego /n8l-dm/ (170 imp,
  // poz. 4,4 na „denza n8l cena"), zasilanego przez dongchedi. Autohome nie zna serii
  // „N8L DM" — 车型名称 to „腾势N8L 2025款 旗舰型", napęd siedzi w engine_type. Bez override
  // ścieżka jest poprawna: strip 腾势 → 'N8L', engine plug-in hybrid → kandydat 'N8L DM'
  // → reverse-index serie_eu trafia we wpis brand-mappingu 'Denza|Denza N8L DM'.
  // N8L nie ma wariantu EV, więc rozbicie per-napęd i tak jest jednoelementowe.
  // che168 wariant nazwy "New Energy" (hybryda/EREV) → konsolidacja do istniejącego huba Tank 300.
  'Tank|Tank 300 New Energy' =>
  array (
    'mark_eu' => 'Tank',
    'serie_eu' => '300',
    'title_eu' => 'Tank 300',
    'slug' => '300',
  ),
  'Denza|腾势N9' => 
  array (
    'mark_eu' => 'Denza',
    'serie_eu' => 'N9 DM-i',
    'title_eu' => 'Denza N9 DM-i',
    'slug' => 'n9-dm-i',
  ),
  'Geely|Icon' => 
  array (
    'mark_eu' => 'Geely',
    'serie_eu' => 'ICON',
    'title_eu' => 'Geely ICON',
    'slug' => 'icon',
  ),
  'Haval|Big Dog' => 
  array (
    'mark_eu' => 'Haval',
    'serie_eu' => 'Big Dog',
    'title_eu' => 'Haval Big Dog Dargo',
    'slug' => 'big-dog',
  ),
  'Haval|H6' => 
  array (
    'mark_eu' => 'Haval',
    'serie_eu' => 'H6',
    'title_eu' => 'Haval H6',
    'slug' => 'h6',
  ),
  'Jetour|X70' => 
  array (
    'mark_eu' => 'Jetour',
    'serie_eu' => 'X70',
    'title_eu' => 'Jetour X70',
    'slug' => 'x70',
  ),
  'Jetour|X70 PLUS' => 
  array (
    'mark_eu' => 'Jetour',
    'serie_eu' => 'X70 PLUS',
    'title_eu' => 'Jetour X70 PLUS',
    'slug' => 'x70-plus',
  ),
  'Jetour|X90 PRO' => 
  array (
    'mark_eu' => 'Jetour',
    'serie_eu' => 'X90 PRO',
    'title_eu' => 'Jetour X90 PRO',
    'slug' => 'x90-pro',
  ),
  'Lotus|EMEYA' => 
  array (
    'mark_eu' => 'Lotus',
    'serie_eu' => 'Emeya',
    'title_eu' => 'Lotus Emeya',
    'slug' => 'emeya',
  ),
  'MG|MG7' => 
  array (
    'mark_eu' => 'MG',
    'serie_eu' => '7',
    'title_eu' => 'MG 7',
    'slug' => '7',
  ),
  'Mazda|CX-5' => 
  array (
    'mark_eu' => 'Mazda',
    'serie_eu' => 'CX-5',
    'title_eu' => 'Mazda CX-5',
    'slug' => 'cx-5',
  ),
  'NIO|EC6' => 
  array (
    'mark_eu' => 'NIO',
    'serie_eu' => 'EC6',
    'title_eu' => 'NIO EC6',
    'slug' => 'ec6',
  ),
  'NIO|ES6' => 
  array (
    'mark_eu' => 'NIO',
    'serie_eu' => 'ES6',
    'title_eu' => 'NIO ES6',
    'slug' => 'es6',
  ),
  'NIO|ES8' => 
  array (
    'mark_eu' => 'NIO',
    'serie_eu' => 'ES8',
    'title_eu' => 'NIO ES8',
    'slug' => 'es8',
  ),
  'NIO|ET5' => 
  array (
    'mark_eu' => 'NIO',
    'serie_eu' => 'ET5',
    'title_eu' => 'NIO ET5',
    'slug' => 'et5',
  ),
  'NIO|ET7' => 
  array (
    'mark_eu' => 'NIO',
    'serie_eu' => 'ET7',
    'title_eu' => 'NIO ET7',
    'slug' => 'et7',
  ),
  'NIO|ET9' => 
  array (
    'mark_eu' => 'NIO',
    'serie_eu' => 'ET9',
    'title_eu' => 'NIO ET9',
    'slug' => 'et9',
  ),
  'Smart|Smart #5' => 
  array (
    'mark_eu' => 'Smart',
    'serie_eu' => '#5',
    'title_eu' => 'Smart #5',
    'slug' => '5',
  ),
  'Volkswagen|CC' => 
  array (
    'mark_eu' => 'Volkswagen',
    'serie_eu' => 'CC',
    'title_eu' => 'Volkswagen CC',
    'slug' => 'cc',
  ),
  'Volkswagen|Jetta' =>
  array (
    'mark_eu' => 'Volkswagen',
    'serie_eu' => 'Jetta',
    'title_eu' => 'Volkswagen Lavida Jetta',
    'slug' => 'jetta',
  ),
  // che168 podaje goły 'Passat' — bez override algorytm tworzyl sierote 'Passat' (parent=0,
  // URL /model/passat/ zamiast /samochody/volkswagen/passat/). Wersja chinska = Passat CN,
  // hub zasiedlony (brand-mapping 'Volkswagen|Passat CN'). PHEV ma wlasny hub Passat New Energy,
  // wiec rozbicie po napedzie nie jest tu potrzebne. (2026-08-10)
  'Volkswagen|Passat' =>
  array (
    'mark_eu' => 'Volkswagen',
    'serie_eu' => 'Passat CN',
    'title_eu' => 'Volkswagen Passat CN',
    'slug' => 'passat-cn',
  ),
  'Volvo|S60' => 
  array (
    'mark_eu' => 'Volvo',
    'serie_eu' => 'S60',
    'title_eu' => 'Volvo S60',
    'slug' => 's60',
  ),
  'Volvo|S90' => 
  array (
    'mark_eu' => 'Volvo',
    'serie_eu' => 'S90',
    'title_eu' => 'Volvo S90',
    'slug' => 's90',
  ),
  'Volvo|S90 PHEV' => 
  array (
    'mark_eu' => 'Volvo',
    'serie_eu' => 'S90',
    'title_eu' => 'Volvo S90',
    'slug' => 's90',
  ),
  'Volvo|XC60' => 
  array (
    'mark_eu' => 'Volvo',
    'serie_eu' => 'XC60',
    'title_eu' => 'Volvo XC60',
    'slug' => 'xc60',
  ),
  'Volvo|XC60 PHEV' => 
  array (
    'mark_eu' => 'Volvo',
    'serie_eu' => 'XC60',
    'title_eu' => 'Volvo XC60',
    'slug' => 'xc60',
  ),
  'Volvo|XC90' => 
  array (
    'mark_eu' => 'Volvo',
    'serie_eu' => 'XC90',
    'title_eu' => 'Volvo XC90',
    'slug' => 'xc90',
  ),
  'Voyah|FREE' => 
  array (
    'mark_eu' => 'Voyah',
    'serie_eu' => 'FREE',
    'title_eu' => 'Voyah FREE',
    'slug' => 'free',
  ),
  'Voyah|Zhiyin' => 
  array (
    'mark_eu' => 'Voyah',
    'serie_eu' => 'Zhiyin',
    'title_eu' => 'Voyah Zhiyin',
    'slug' => 'zhiyin',
  ),
  'Voyah|岚图泰山' => 
  array (
    'mark_eu' => 'Voyah',
    'serie_eu' => 'Taishan',
    'title_eu' => 'Voyah Taishan',
    'slug' => 'taishan',
  ),
  'XPeng|G6' => 
  array (
    'mark_eu' => 'XPENG',
    'serie_eu' => 'G6',
    'title_eu' => 'XPENG G6',
    'slug' => 'g6',
  ),
  'XPeng|G9' => 
  array (
    'mark_eu' => 'XPENG',
    'serie_eu' => 'G9',
    'title_eu' => 'XPENG G9',
    'slug' => 'g9',
  ),
  'XPeng|MONA M03' => 
  array (
    'mark_eu' => 'XPENG',
    'serie_eu' => 'Mona M03',
    'title_eu' => 'XPENG Mona M03',
    'slug' => 'mona-m03',
  ),
  'XPeng|P7' => 
  array (
    'mark_eu' => 'XPENG',
    'serie_eu' => 'P7',
    'title_eu' => 'XPENG P7',
    'slug' => 'p7',
  ),
  'XPeng|P7+' => 
  array (
    'mark_eu' => 'XPENG',
    'serie_eu' => 'P7+',
    'title_eu' => 'XPENG P7+',
    'slug' => 'p7-plus',
  ),
  'XPeng|X9' => 
  array (
    'mark_eu' => 'XPENG',
    'serie_eu' => 'X9',
    'title_eu' => 'XPENG X9',
    'slug' => 'x9',
  ),
  'Xiaomi|SU7' => 
  array (
    'mark_eu' => 'Xiaomi',
    'serie_eu' => 'SU7',
    'title_eu' => 'Xiaomi SU7',
    'slug' => 'su7',
  ),
  'Xiaomi|SU7 Ultra' => 
  array (
    'mark_eu' => 'Xiaomi',
    'serie_eu' => 'SU7 Ultra',
    'title_eu' => 'Xiaomi SU7 Ultra',
    'slug' => 'su7-ultra',
  ),
  'Zeekr|001' => 
  array (
    'mark_eu' => 'Zeekr',
    'serie_eu' => '001',
    'title_eu' => 'Zeekr 001',
    'slug' => '001',
  ),
  'Zeekr|007' => 
  array (
    'mark_eu' => 'Zeekr',
    'serie_eu' => '007',
    'title_eu' => 'Zeekr 007',
    'slug' => '007',
  ),
  'Zeekr|009' => 
  array (
    'mark_eu' => 'Zeekr',
    'serie_eu' => '009',
    'title_eu' => 'Zeekr 009',
    'slug' => '009',
  ),
  'Zeekr|X' => 
  array (
    'mark_eu' => 'Zeekr',
    'serie_eu' => 'X',
    'title_eu' => 'Zeekr X',
    'slug' => 'x',
  ),
  'Zeekr|极氪007GT' => 
  array (
    'mark_eu' => 'Zeekr',
    'serie_eu' => '007 GT',
    'title_eu' => 'Zeekr 007 GT',
    'slug' => '007-gt',
  ),
  'Zeekr|极氪9X' =>
  array (
    'mark_eu' => 'Zeekr',
    'serie_eu' => '9X',
    'title_eu' => 'Zeekr 9X',
    'slug' => '9x',
  ),
  // --- Fala 3 (2026-06-19). Jetour „Shanhai" (山海) = seria new-energy Jetoura; che168 wystawia ją
  //     jako osobną MARKĘ "Shanhai", wersja EN che168 pokazuje Jetour. brand-mapping kluczuje CN
  //     pod "Jetour Shanhai|Jetour Shanhai L9" → override mostkuje surowy klucz che168 do tej sygn. ---
  'Shanhai|Shanhai L9' =>
  array (
    'mark_eu' => 'Jetour',
    'serie_eu' => 'Shanhai L9',
    'title_eu' => 'Jetour Shanhai L9',
    'slug' => 'shanhai-l9',
  ),

  // --- T-186 domapowania 2026-07-20 (top orphany z próbki 2024-2026; aliasy → istniejące huby,
  //     wartości skopiowane 1:1 z brand-mapping; nowe modele = pary z sekcji T-186 w brand-mapping) ---
  'Beijing Off-Road|BJ40' =>
  array (
    'mark_eu' => 'BAIC',
    'serie_eu' => 'BJ40',
    'title_eu' => 'BAIC BJ40',
    'slug' => 'bj40',
  ),
  'Beijing Off-Road|北京越野BJ40增程' =>
  array (
    'mark_eu' => 'BAIC',
    'serie_eu' => 'BJ40 EREV',
    'title_eu' => 'BAIC BJ40 EREV',
    'slug' => 'bj40-erev',
  ),
  'Beijing Off-Road|BJ30' =>
  array (
    'mark_eu' => 'BAIC',
    'serie_eu' => 'BJ30',
    'title_eu' => 'BAIC BJ30',
    'slug' => 'bj30',
  ),
  'Xiaomi|小米YU7' =>
  array (
    'mark_eu' => 'Xiaomi',
    'serie_eu' => 'YU7',
    'title_eu' => 'Xiaomi YU7',
    'slug' => 'yu7',
  ),
  'Exeed|Xingjiyuan ET' =>
  array (
    'mark_eu' => 'Exlantix',
    'serie_eu' => 'ET',
    'title_eu' => 'Exeed Exlantix ET',
    'slug' => 'et',
  ),
  'Shanhai|捷途旅行者C-DM' =>
  array (
    'mark_eu' => 'Jetour',
    'serie_eu' => 'T2 C-DM',
    'title_eu' => 'Jetour T2 C-DM Traveller',
    'slug' => 't2-c-dm',
  ),
  'Shanhai|Shanhai T1' =>
  array (
    'mark_eu' => 'Jetour',
    'serie_eu' => 'Shanhai T1',
    'title_eu' => 'Jetour Shanhai T1',
    'slug' => 'shanhai-t1',
  ),
  'iCAR|iCAR 超级V23' =>
  array (
    'mark_eu' => 'iCAR',
    'serie_eu' => 'Super V23',
    'title_eu' => 'iCAR Super V23',
    'slug' => 'super-v23',
  ),
  'Jetour|Traveller' =>
  array (
    'mark_eu' => 'Jetour',
    'serie_eu' => 'T2',
    'title_eu' => 'Jetour T2 Traveller',
    'slug' => 't2',
  ),
  'Li Auto|Li L7' =>
  array (
    'mark_eu' => 'Li Auto',
    'serie_eu' => 'L7',
    'title_eu' => 'Li Auto L7',
    'slug' => 'l7',
  ),
  'Li Auto|Li L8' =>
  array (
    'mark_eu' => 'Li Auto',
    'serie_eu' => 'L8',
    'title_eu' => 'Li Auto L8',
    'slug' => 'l8',
  ),
  'Fang Cheng Bao|钛7' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Leopard 7 (Tai 7) FCB, PHEV',
    'title_eu' => 'BYD Leopard 7 PHEV',
    'slug' => 'leopard-7',
  ),
  'Fang Cheng Bao|Titanium 3' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Leopard 3 (Tai 3) FCB',
    'title_eu' => 'BYD Leopard 3',
    'slug' => 'leopard-3',
  ),
  'Fang Cheng Bao|Leopard 8' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Leopard 8  (Denza B8)',
    'title_eu' => 'BYD Leopard 8 (Denza B8)',
    'slug' => 'leopard-8',
  ),
  'Galaxy|Galaxy Xingyao 8' =>
  array (
    'mark_eu' => 'Geely',
    'serie_eu' => 'Galaxy Starship 8 PHEV',
    'title_eu' => 'Geely Galaxy Starship 8 PHEV',
    'slug' => 'starship-8-phev',
  ),
  'Galaxy|银河A7' =>
  array (
    'mark_eu' => 'Geely',
    'serie_eu' => 'Galaxy A7 PHEV',
    'title_eu' => 'Geely Galaxy A7 PHEV',
    'slug' => 'a7-phev',
  ),
  'Tank|Tank 700 New Energy' =>
  array (
    'mark_eu' => 'Tank',
    'serie_eu' => '700 Hi4-T',
    'title_eu' => 'Tank 700 Hi4-T',
    'slug' => '700-hi4-t',
  ),
  'eπ|eπ007' =>
  array (
    'mark_eu' => 'Dongfeng',
    'serie_eu' => 'eπ007',
    'title_eu' => 'Dongfeng eπ007',
    'slug' => 'e-007',
  ),
  'Wey|Wey Gaoshan' =>
  array (
    'mark_eu' => 'WEY',
    'serie_eu' => 'Gaoshan',
    'title_eu' => 'WEY Gaoshan',
    'slug' => 'gaoshan',
  ),
  'smart|Smart #1' =>
  array (
    'mark_eu' => 'Smart',
    'serie_eu' => '#1',
    'title_eu' => 'Smart #1',
    'slug' => '1',
  ),
  'smart|Smart #3' =>
  array (
    'mark_eu' => 'Smart',
    'serie_eu' => '#3',
    'title_eu' => 'Smart #3',
    'slug' => '3',
  ),
  'Lotus|Eletre' =>
  array (
    'mark_eu' => 'Lotus',
    'serie_eu' => 'Eletre',
    'title_eu' => 'Lotus Eletre',
    'slug' => 'eletre',
  ),
  'Shanhai|捷途山海L7 PLUS' =>
  array (
    'mark_eu' => 'Jetour',
    'serie_eu' => 'Shanhai L7 PLUS',
    'title_eu' => 'Jetour Shanhai L7 PLUS',
    'slug' => 'jetour-shanhai-l7-plus',
  ),
  'Jetour|Dasheng' =>
  array (
    'mark_eu' => 'Jetour',
    'serie_eu' => 'Dashing',
    'title_eu' => 'Jetour Dashing',
    'slug' => 'dashing',
  ),
  '纵横|纵横G700' =>
  array (
    'mark_eu' => 'Jetour',
    'serie_eu' => 'G700',
    'title_eu' => 'Jetour G700',
    'slug' => 'g700',
  ),
  'Exeed|Lingyun' =>
  array (
    'mark_eu' => 'Exeed',
    'serie_eu' => 'TXL',
    'title_eu' => 'Exeed TXL Lingyun',
    'slug' => 'txl',
  ),

  // ─── T-186 sonda kanału 2026-07-25: 16 orphanów z 59 811 zdarzeń (38 ofert) ───
  // Każdy wpis celuje w entry brand-mappingu albo w zasiedlony term taksonomii `serie`,
  // NIE w nazwę termu-sieroty — inaczej oferty che168 wylądowałyby w innym hubie niż
  // te same modele z dongchedi. Wersje rozstrzygnięte po param_93 (车型名称), nie „na oko".

  // che168 nazywa BYD 夏 (Xia) jako '比亚迪M9' — nazwa dealerska, nie modelowa (param_93 mowi
  // „夏 2025款"). Bez override algorytm robil sierote 'm9-2' z nazwa CJK „比亚迪M9", obok
  // zasiedlonego huba 'Xia Summer' (5 ofert, content + rework v1). Uwaga: hub 'M9' (#5304)
  // nalezy do AITO — zbieznosc nazw pozorna. (2026-08-10)
  'BYD|比亚迪M9' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Xia Summer',
    'title_eu' => 'BYD Xia',
    'slug' => 'xia-summer',
  ),
  // 海狮06 2025款 DM-i → brand-mapping 'BYD|Haishi 06 DM'. che168 trzyma DM-i i EV pod jednym
  // '海狮06'; domyślny = DM-i, EV przez `by_engine` (2026-08-10 — wcześniej 2 oferty EV wpadły
  // do huba DM-i). Hub docelowy: brand-mapping 'BYD|Sea Lion 06EV'.
  'BYD|海狮06' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Sealion 6 DM',
    'title_eu' => 'BYD Sealion 6',
    'slug' => 'sealion-6-dm',
    'by_engine' => array (
      'electric' => array (
        'mark_eu' => 'BYD',
        'serie_eu' => 'Sealion 6 EV',
        'title_eu' => 'BYD Sealion 6',
        'slug' => 'sealion-6-ev',
      ),
    ),
  ),
  // → brand-mapping 'BYD|Haishi 05 EV'
  'BYD|海狮05 EV' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Sealion 5 EV',
    'title_eu' => 'BYD Sealion 5',
    'slug' => 'sealion-5-ev',
  ),
  // che168 trzyma EV i DM pod jednym '汉L' (łacińskie 'Han L' idzie algorytmem sufiksów i
  // rozpoznaje napęd samo — CJK-owy klucz trafiał tu i short-circuitował). Domyślny = EV
  // (28 ofert, sonda widziała 3/3 EV), DM rozstrzygane przez `by_engine` (2026-07-31 —
  // oferta 399083 汉L 2025款 DM-p wpadła do huba EV).
  'BYD|汉L' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Han L EV',
    'title_eu' => 'BYD Han L EV',
    'slug' => 'han-l-ev',
    'by_engine' => array (
      'phev' => array (
        'mark_eu' => 'BYD',
        'serie_eu' => 'Han L DM',
        'title_eu' => 'BYD Han L',
        'slug' => 'han-l-dm',
      ),
    ),
  ),
  // 唐新能源 2025款 DM-i 115KM → Tang DM-i (32 listingi). UWAGA: to NIE Sealion 8 —
  // Sealion 8 to Tang L, osobny model z własnymi termami.
  'BYD|Tang New Energy' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Tang DM-i',
    'title_eu' => 'BYD Tang',
    'slug' => 'tang-dm-i',
  ),
  // 护卫舰07 2024款 荣耀版 DM-i → zasiedlony term Frigate 07 (4 listingi)
  'BYD|Frigate 07' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Frigate 07',
    'title_eu' => 'BYD Frigate 07',
    'slug' => 'frigate-07',
  ),
  // 海豹07 DM-i 2025款 → brand-mapping 'BYD|Seal 07 DM' = serie 'Seal 7 DM' (4 listingi);
  // pusty term 'Seal 07 DM' zostaje sierotą.
  'BYD|Seal 07 DM-i' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Seal 7 DM',
    'title_eu' => 'BYD Seal 7 DM',
    'slug' => 'seal-7-dm',
  ),
  'BYD|Seal 07 EV' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Seal 07 EV',
    'title_eu' => 'BYD Seal 07 EV',
    'slug' => 'seal-07-ev',
  ),
  // 宋PLUS新能源 2025款 EV 520km → brand-mapping 'BYD|Song PLUS EV' (5 listingów).
  // che168 skraca 宋PLUS新能源 do 'PLUS New Energy' (ten sam wzorzec co 'L EV'/'L DM-i').
  // che168 trzyma EV i DM-i pod jednym '宋PLUS新能源' (skracanym do 'PLUS New Energy').
  // Domyślny = EV, DM-i rozstrzygane przez `by_engine` → hub eksportowy „Seal U DM-I (Song Plus)"
  // (T-222, 2026-07-30 — wcześniej 3 oferty DM-i wpadły do huba EV).
  'BYD|PLUS New Energy' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Song PLUS EV',
    'title_eu' => 'BYD Song PLUS EV',
    'slug' => 'song-plus-ev',
    'by_engine' => array (
      // Klucz PO normalizacji enum-mapy ('plug-in hybrid' → 'phev'); surowa nazwa z che168
      // nigdy tu nie dociera. (2026-07-31 — wpis z 30.07 był martwy przez ten rozjazd.)
      'phev' => array (
        'mark_eu' => 'BYD',
        'serie_eu' => 'Seal U DM-I (Song Plus)',
        'title_eu' => 'BYD Seal U DM-i',
        'slug' => 'seal-u-dm-i',
      ),
    ),
  ),
  // Zasiedlony term 'Li Auto MEGA' (8 listingów) — świadomie łamie konwencję serie-bez-marki
  // (L6/L7/i6), bo hub już ma content; nowy 'MEGA' rozbiłby podaż na dwa huby.
  'Li Auto|Li MEGA' =>
  array (
    'mark_eu' => 'Li Auto',
    'serie_eu' => 'Li Auto MEGA',
    'title_eu' => 'Li Auto MEGA',
    'slug' => 'li-auto-mega',
  ),
  // 小鹏G7 2025款 702 超长续航 Ultra → brand-mapping 'XPeng|XPeng G7' = serie 'G7'
  // (18 listingów); pusty term 'XPeng G7' zostaje sierotą.
  'XPeng|小鹏G7' =>
  array (
    'mark_eu' => 'XPENG',
    'serie_eu' => 'G7',
    'title_eu' => 'XPENG G7',
    'slug' => 'g7',
  ),
  // 小鹏GX 2026款 增程 1585四驱Ultra — model premiera 2026-05-20 (EV + EREV, 6 miejsc).
  // Term 'GX' (slug gx) już zasiedlony 2 listingami XPenga.
  'XPeng|小鹏GX' =>
  array (
    'mark_eu' => 'XPENG',
    'serie_eu' => 'GX',
    'title_eu' => 'XPENG GX',
    'slug' => 'gx',
  ),
  // Zasiedlony term 'Galaxy A7 EM-i' (37 listingów, slug a7-em)
  'Galaxy|银河A7 EM' =>
  array (
    'mark_eu' => 'Geely',
    'serie_eu' => 'Galaxy A7 EM-i',
    'title_eu' => 'Geely Galaxy A7 EM-i',
    'slug' => 'a7-em',
  ),
  // → brand-mapping 'Galaxy|Galaxy Starship 7 EM-i'
  'Galaxy|银河星舰7' =>
  array (
    'mark_eu' => 'Geely',
    'serie_eu' => 'Galaxy Starship 7 EM-i',
    'title_eu' => 'Geely Galaxy Starship 7 EM-i',
    'slug' => 'starship-7-em-i',
  ),
  // 瑞虎8L 2024款 2.0T 两驱尊贵版 → brand-mapping 'Chery|Tiggo 8L' (slug tiggo-9, 18 listingów)
  'Chery|瑞虎8L' =>
  array (
    'mark_eu' => 'Chery',
    'serie_eu' => 'Tiggo 9 (Tiggo 8L)',
    'title_eu' => 'Chery Tiggo 9',
    'slug' => 'tiggo-9',
  ),
  // → brand-mapping 'Hongqi|Hongqi Tiangong 06' = serie 'Tiangong 06' (3 listingi)
  'Hongqi|红旗天工06' =>
  array (
    'mark_eu' => 'Hongqi',
    'serie_eu' => 'Tiangong 06',
    'title_eu' => 'Hongqi Tiangong 06',
    'slug' => 'tiangong-06',
  ),
  // 哈弗大狗 PLUS → wariant Big Dog / Dargo, brand-mapping 'Haval|Haval Big Dog' (9 listingów)
  'Haval|哈弗大狗 PLUS' =>
  array (
    'mark_eu' => 'Haval',
    'serie_eu' => 'Big Dog',
    'title_eu' => 'Haval Big Dog Dargo',
    'slug' => 'big-dog',
  ),
  // 哈弗猛龙新能源 2024款 Hi4 145 穿越版 — PHEV. W bazie jest tylko 'Haval Menglong ICE'
  // (3 listingi), więc wersja Hi4 dostaje własny hub (mieszanie ICE z PHEV byłoby błędem).
  'Haval|Menglong New Energy' =>
  array (
    'mark_eu' => 'Haval',
    'serie_eu' => 'Menglong Hi4',
    'title_eu' => 'Haval Menglong Hi4',
    'slug' => 'menglong-hi4',
  ),
  // 坦克500新能源 2024款 Hi4-T — konwencja brand-mappingu dla Tanka to serie bez marki
  // ('400 Hi4-T', '700 Hi4-T'), więc '500 Hi4-T'; sieroty 'Tank 500*' zostają nietknięte.
  'Tank|Tank 500 New Energy' =>
  array (
    'mark_eu' => 'Tank',
    'serie_eu' => '500 Hi4-T',
    'title_eu' => 'Tank 500 Hi4-T',
    'slug' => '500-hi4-t',
  ),

  // --- 2026-07-27: ROX 01 + AUDI E7X (para do sekcji v6.5 brand-mappingu) ---
  // 极石01 — che168 tłumaczy nazwę dosłownie ('Extreme Stone 01'), dongchedi pinyinem
  // ('Jishi 01'); obie martwe w wyszukiwarce (0 i 10/mc). Rynkowa nazwa to ROX 01 (170/mc),
  // pod nią sprzedają już polscy dealerzy. Oba źródła zbiegają się w term 'ROX 01' (slug 01).
  'ROX|Extreme Stone 01' =>
  array (
    'mark_eu' => 'ROX',
    'serie_eu' => '01 (PolarStone 01)',
    'title_eu' => 'ROX 01 (PolarStone 01)',
    'slug' => '01',
  ),
  // 奥迪AUDI|奥迪E7X — marka i model czysto-CJK: strip w resolveChe168() zostawia model z CJK,
  // więc reverse-index nigdy nie trafi. Override jest jedyną drogą (klucz surowy, z CJK).
  '奥迪AUDI|奥迪E7X' =>
  array (
    'mark_eu' => 'Audi',
    'serie_eu' => 'E7X',
    'title_eu' => 'Audi E7X',
    'slug' => 'e7x',
  ),
  // 克蒂山川 — che168 podaje markę jako 'Kurti', dongchedi 'Kedi' — marka to KEDE, model czysto-CJK, więc
  // reverse-index nie ma czego trafić. Bez override'u oferta lądowała jako Toyota|M7 (błędna
  // marka + term 'M7' współdzielony z 59 listingami). Para do sekcji v6.6 brand-mappingu.
  'Kurti|克蒂山川' =>
  array (
    'mark_eu' => 'Toyota',
    'serie_eu' => 'KEDE Shanchuan',
    'title_eu' => 'Toyota KEDE Shanchuan',
    'slug' => 'kede-shanchuan',
  ),

  // --- Lynk & Co (2026-07-28) — brand-mapping miał TYLKO '900', więc każda oferta che168
  // na pozostałych modelach wypadała jako sierota (log: „niezmapowany model 'Lynk & Co|08 EM-P'").
  // Klucze zweryfikowane przez getOffer(): che168 podaje model bez CJK ('03', '08 EM-P'),
  // identycznie na /offers i /offer — brak rozjazdu endpointów jak przy N8L.
  // Para dla każdego wpisu w brand-mappingu (sekcja v6.7) — bez niej guard i tak odrzuca.
  'Lynk & Co|03' =>
  array (
    'mark_eu' => 'Lynk & Co',
    'serie_eu' => '03',
    'title_eu' => 'Lynk & Co 03',
    'slug' => '03',
  ),
  'Lynk & Co|06 EM-P' =>
  array (
    'mark_eu' => 'Lynk & Co',
    'serie_eu' => '06 EM-P',
    'title_eu' => 'Lynk & Co 06 EM-P',
    'slug' => '06-em-p',
  ),
  'Lynk & Co|08 EM-P' =>
  array (
    'mark_eu' => 'Lynk & Co',
    'serie_eu' => '08 EM-P',
    'title_eu' => 'Lynk & Co 08 EM-P',
    'slug' => '08-em-p',
  ),
  'Lynk & Co|Z10' =>
  array (
    'mark_eu' => 'Lynk & Co',
    'serie_eu' => 'Z10',
    'title_eu' => 'Lynk & Co Z10',
    'slug' => 'z10',
  ),
  // Klucze potwierdzone kolejką `asiaauto_che168_unmapped` (strumień 2026-07-27) — nie zgadywane.
  'Lynk & Co|07 EM-P' =>
  array (
    'mark_eu' => 'Lynk & Co',
    'serie_eu' => '07 EM-P',
    'title_eu' => 'Lynk & Co 07 EM-P',
    'slug' => '07-em-p',
  ),
  'Lynk & Co|Z20' =>
  array (
    'mark_eu' => 'Lynk & Co',
    'serie_eu' => 'Z20',
    'title_eu' => 'Lynk & Co Z20',
    'slug' => 'z20',
  ),
  // che168 podaje 900 i 10 po CHIŃSKU (reszta gamy po angielsku). Resolver nie ma '领克'
  // w liście cnPrefix (krok 1), więc model zostaje z CJK i wypada jako sierota — mimo że
  // '900' ma sygnaturę w brand-mappingu od dawna. Skutek: hub /lynk-co/900/ (2172 imp,
  // 84 kliki w GSC 90 dni) miał wyłącznie oferty dongchedi, a 7 ofert che168 stało pod filtrem.
  // Sonda 18 stron magazynu 2026-07-28: 领克900 = 28 szt (7 przez filtr), 领克10 = 17 szt (4).
  'Lynk & Co|领克900' =>
  array (
    'mark_eu' => 'Lynk & Co',
    'serie_eu' => '900',
    'title_eu' => 'Lynk & Co 900',
    'slug' => '900',
  ),
  // 领克10 = sedan PHEV „10 EM-P" (wiki hubu: 5050 mm, 1.0 163 KM + 367 KM elektryczne).
  'Lynk & Co|领克10' =>
  array (
    'mark_eu' => 'Lynk & Co',
    'serie_eu' => '10 EM-P',
    'title_eu' => 'Lynk & Co 10 EM-P',
    'slug' => '10-em-p',
  ),

  // --- GAC Hyptec (2026-07-28). Wyjątek od reguły „klucz z CJK": che168 zwraca tę markę
  // już zlatynizowaną, i to STARĄ nazwą — mark='Hyper', model='HT' (potwierdzone getOffer
  // dla inner_id 58784725). Klucze MUSZĄ zostać w formie 'Hyper|*', bo tak przychodzą z API;
  // nazwy EU po stronie wartości to Hyptec (uzasadnienie wolumenami: brand-mapping sekcja v6.8).
  // Bez aliasu oferta wypadała jako sierota — ręczny import z panelu nie ma guarda mapowania
  // (sync ma: isMappedForImport), więc surowe mark/model utworzyły osobną markę „Hyper".
  // Sygnatury mark_eu|serie_eu dodane równolegle w brand-mapping-v6.1.php — bez nich alias martwy.
  'Hyper|HT' =>
  array (
    'mark_eu' => 'GAC',
    'serie_eu' => 'Hyptec HT',
    'title_eu' => 'GAC Hyptec HT',
    'slug' => 'hyptec-ht',
  ),
  'Hyper|HL' =>
  array (
    'mark_eu' => 'GAC',
    'serie_eu' => 'Hyptec HL',
    'title_eu' => 'GAC Hyptec HL',
    'slug' => 'hyptec-hl',
  ),
  'Hyper|GT' =>
  array (
    'mark_eu' => 'GAC',
    'serie_eu' => 'Hyptec GT',
    'title_eu' => 'GAC Hyptec GT',
    'slug' => 'hyptec-gt',
  ),
  'Hyper|SSR' =>
  array (
    'mark_eu' => 'GAC',
    'serie_eu' => 'Hyptec SSR',
    'title_eu' => 'GAC Hyptec SSR',
    'slug' => 'hyptec-ssr',
  ),
  'Hyper|A800' =>
  array (
    'mark_eu' => 'GAC',
    'serie_eu' => 'Hyptec A800',
    'title_eu' => 'GAC Hyptec A800',
    'slug' => 'hyptec-a800',
  ),

  // === v6.8 / 2026-07-29 — zaciąg ręczny 28.07: klucze w formie che168 ===
  'Mercedes-Benz|EQE SUV' =>
  array (
    'mark_eu' => 'Mercedes-Benz',
    'serie_eu' => 'EQE SUV',
    'title_eu' => 'Mercedes-Benz EQE SUV',
    'slug' => 'eqe-suv',
  ),
  'Foton|Foton Mars' =>
  array (
    'mark_eu' => 'Foton',
    'serie_eu' => 'Tunland V9',
    'title_eu' => 'Foton Tunland V9',
    'slug' => 'tunland-v9',
  ),
  'Foton|V New Energy' =>
  array (
    'mark_eu' => 'Foton',
    'serie_eu' => 'Toano Da V',
    'title_eu' => 'Foton Toano Da V',
    'slug' => 'toano-da-v',
  ),
  '大通|V70' =>
  array (
    'mark_eu' => 'Maxus',
    'serie_eu' => 'V70',
    'title_eu' => 'Maxus V70',
    'slug' => 'v70',
  ),
  '大通|Interstellar' =>
  array (
    'mark_eu' => 'Maxus',
    'serie_eu' => 'Interstellar',
    'title_eu' => 'Maxus Interstellar',
    'slug' => 'interstellar',
  ),
  '大通|G90' =>
  array (
    'mark_eu' => 'Maxus',
    'serie_eu' => 'G90',
    'title_eu' => 'Maxus G90',
    'slug' => 'g90',
  ),
  'Mengshi|Mengshi 917' =>
  array (
    'mark_eu' => 'Dongfeng',
    'serie_eu' => 'M-Hero 917',
    'title_eu' => 'Dongfeng M-Hero 917',
    'slug' => 'm-hero-917',
  ),
  '华东汽车|华东牌商务车' =>
  array (
    'mark_eu' => 'Toyota',
    'serie_eu' => 'Sienna',
    'title_eu' => 'Toyota Sienna',
    'slug' => 'sienna',
  ),

  // --- 2026-08-15: Exeed VX (Lanyue) ---
  // Che168 wysyła model w CJK ('星途揽月C-DM'), a '星途' nie ma w $cnPrefix resolveChe168(),
  // więc lookup umierał na kroku "model nadal z CJK" → sierota. Brand-mapping trzyma tę parę
  // wyłącznie w formie dongchedi ('Exeed|Exeed Lanyue C-DM'), w którą che168 nigdy nie trafi.
  // Sync odrzucał ofertę guardem, ale ręczny import guarda nie ma → oferta 417885 (14.08)
  // założyła duplikat huba 'Lanyue C-DM'. sigToKey() przepisuje entry z powrotem na klucz
  // brand-mappingu, więc importer trafia w istniejący hub /samochody/exeed/vx/.
  'Exeed|星途揽月C-DM' =>
  array (
    'mark_eu' => 'Exeed',
    'serie_eu' => 'VX (Lanyue)',
    'title_eu' => 'Exeed VX',
    'slug' => 'vx',
  ),
);
