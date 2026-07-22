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
  // UWAGA: che168 trzyma DM-i i (ewentualne) EV pod jednym '海豹06' — override jest
  // engine-agnostyczny i celuje w wariant DM-i (dominujący w danych 2026-07).
  'BYD|海豹06' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Seal 6 DM-i',
    'title_eu' => 'BYD Seal 6 DM-i',
    'slug' => 'seal-6-dm-i',
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
  // UWAGA: override ślepy na napęd (klucz=mark|model). Mapuje na PHEV; wariant EV (Dreamer EV)
  //        wymagałby rozróżnienia po engine_type — do dorobienia, gdy pojawi się EV Dreamer.
  'VOYAH|Dreamer' =>
  array (
    'mark_eu' => 'Voyah',
    'serie_eu' => 'Dream PHEV',
    'title_eu' => 'Voyah Dream PHEV',
    'slug' => 'dream-phev',
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
  'BYD|Han' =>
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Han DM-i',
    'title_eu' => 'BYD Han',
    'slug' => 'han-dm-i',
  ),
  'BYD|Qin L' => 
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Qin L EV',
    'title_eu' => 'BYD Qin L EV',
    'slug' => 'qin-l-ev',
  ),
  'BYD|Qin PLUS' => 
  array (
    'mark_eu' => 'BYD',
    'serie_eu' => 'Qin Plus DM-i',
    'title_eu' => 'BYD Qin Plus DM-i',
    'slug' => 'qin-plus-dm-i',
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
  'Denza|Denza D9' => 
  array (
    'mark_eu' => 'Denza',
    'serie_eu' => 'D9 DM-i',
    'title_eu' => 'Denza D9 DM-i',
    'slug' => 'd9-dm-i',
  ),
  'Denza|Denza Z9' => 
  array (
    'mark_eu' => 'Denza',
    'serie_eu' => 'Z9 DM-i',
    'title_eu' => 'Denza Z9 DM-i',
    'slug' => 'z9-dm-i',
  ),
  'Denza|Denza Z9GT' => 
  array (
    'mark_eu' => 'Denza',
    'serie_eu' => 'Z9 GT DM-i',
    'title_eu' => 'Denza Z9 GT DM-i',
    'slug' => 'z9-gt-dm-i',
  ),
  'Denza|腾势N8L' =>
  array (
    'mark_eu' => 'Denza',
    'serie_eu' => 'N8L',
    'title_eu' => 'Denza N8L',
    'slug' => 'n8l',
  ),
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
);
