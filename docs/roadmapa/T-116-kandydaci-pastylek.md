# T-116 — kandydaci na pastylki wyposażenia (pomiar 2026-09-03)

> Skrypt: `scripts/kandydaci-pastylek.php`. Próg: ≥ 50 ofert publish z wartością pozytywną (reguła jak we flagach:
> NEGATIVE + pierwszy wariant). Pominięte: klucze już użyte, liczbowe/opisowe, enumy (>6 wartości), śmieci.
> **Wybór Janka 03.09 (wdrożone w 0.37.5) oznaczony ✅.** Reszta czeka na „rozszerzamy filtry".
> Pozycje z pokryciem > 85% (ok. pierwsze 30) jako filtry prawie nic nie zawężają.

| # | klucz `extra_prep` | etykieta PL (z `translations-extra-prep.php`) | ofert | wartości |
|---|---|---|---:|---|
| 1 | `bluetooth_and_car_phone` | Bluetooth / telefon | 2911 | 标配(2911) |
| 2 | `child_seat_interface` | ISOFIX | 2908 | 标配(2908) |
| 3 | `keyless_start` | Keyless start | 2906 | 标配(2906) |
| 4 | `car_networking` | Łączność z chmurą | 2899 | 标配(2899) |
| 5 | `ota_upgrade` | Aktualizacje OTA | 2878 | 标配(2878) |
| 6 | `brake_force` | EBD (rozdział hamowania) | 2785 | 标配(2785) |
| 7 | `abs_anti_lock` | ABS | 2785 | 标配(2785) |
| 8 | `front_electric_window` | Szyby el. przód | 2785 | 标配(1674), 前排(1111) |
| 9 | `front_armrest` | Podłokietnik przedni | 2783 | 标配(1673), 前排(1110) |
| 10 | `central_locking_car` | Centralny zamek | 2783 | 标配(2783) |
| 11 | `multifunction_steer_wheel` | Kierownica wielofunkcyjna | 2783 | 标配(1673), 多功能控制(1110) |
| 12 | `brake_assist` | Wspomaganie hamowania | 2782 | 标配(2782) |
| 13 | `traction_control` | Kontrola trakcji | 2781 | 标配(2781) |
| 14 | `rear_electric_window` | Szyby el. tył | 2779 | 标配(1671), 后排(1108) |
| 15 | `alloy_wheel` | Felgi aluminiowe | 2776 | 标配(2776) |
| 16 | `window_anti_clip_function` | Zabezpieczenie szyb | 2776 | 标配(2776) |
| 17 | `headlamp_delay_off` | Opóźnione wyłączanie | 2773 | 标配(2773) |
| 18 | `rear_parking_radar` | Czujniki parkowania tył | 2773 | 标配(1673), 后(1100) |
| 19 | `active_brake` | Hamowanie awaryjne | 2771 | 标配(2771) |
| 20 | `auto_headlamp` | Automatyczne światła | 2770 | 标配(2770) |
| 21 | `lane_keeping_assist` | Utrzymanie pasa ruchu | 2765 | 标配(2765) |
| 22 | `pm25_filtrating_equipment` | Filtr PM2.5 | 2764 | 标配(2764) |
| 23 | `lane_warning_system` | Ostrzeżenie o opuszczeniu pasa | 2730 | 标配(1886), 车道偏离预警(844) |
| 24 | `navigation_system` | System nawigacji | 2716 | 标配(2716) |
| 25 | `voice_recognition` | Rozpoznawanie głosu | 2696 | 标配(2696) |
| 26 | `uphill_support` | Asystent ruszania | 2674 | 标配(2674) |
| 27 | `adaptive_light` | Światła adaptacyjne | 2673 | 标配(2673) |
| 28 | `side_air_curtain` | Kurtyny powietrzne | 2666 | 前●(1596), 标配(1068), 前○(2) |
| 29 | `front_collision_warning` | Ostrzeżenie o kolizji (przód) | 2646 | 标配(1595), 前方碰撞预警(1051) |
| 30 | `mobile_remote_control_6` | Zdalne sterowanie | 2644 | 远程控制(2644) |
| 31 | `back_armrest` | Podłokietnik tylny | 2619 | 标配(1587), 后排(1032) |
| 32 | `rear_cup_holder` | Uchwyty na kubki (tył) | 2613 | 标配(2613) |
| 33 | `front_parking_radar` | Czujniki parkowania przód | 2608 | 标配(1592), 前(1016) |
| 34 | `rain_induction_wiper` | Automatyczne wycieraczki | 2587 | 标配(2587) |
| 35 | `line_support` | Asystent pasa ruchu | 2580 | 标配(2580) |
| 36 | `wifi` | WiFi | 2478 | 标配(2478) |
| 37 | `app_store` | Sklep z aplikacjami | 2466 | 标配(2466) |
| 38 | `reversing_warning_system` | Ostrzeżenie boczne (cofanie) | 2454 | 标配(1515), 倒车车侧预警(939) |
| 39 | `built_in_tachograph` | Rejestrator jazdy (wbudowany) | 2451 | 标配(2451) |
| 40 | `remote_key_5` | Klucz Bluetooth (telefon) | 2448 | 手机蓝牙钥匙(2448) |
| 41 | `voice_wake_up_free` | Aktywacja bez słowa-klucza | 2435 | 标配(2435) |
| 42 | `quick_charge_interface` | Gniazdo szybkiego ładowania | 2396 | 支持(1719), 快充接口(677) |
| 43 | `remote_key_3` | Kluczyk inteligentny | 2357 | 智能遥控钥匙(2357) |
| 44 | `rear_traffic_warning` | Ostrzeżenie o kolizji (tył) | 2330 | 标配(1463), 后方碰撞预警(867) |
| 45 | `visible_to_say` | Sterowanie głosem (ekran) | 2212 | 标配(2212) |
| 46 | `fatigue_driving_warning` | Wykrywanie zmęczenia | 2153 | 标配(2153) |
| 47 | `outside_mirror_memory` | Pamięć ustawień lusterek | 2124 | 记忆(2124) |
| 48 | `remote_control_move` | Zdalne manewrowanie | 2018 | 标配(2018) |
| 49 | `auto_brake_hold` | Auto Hold | 1989 | 标配(1989) |
| 50 | `steep_slope` | Asystent zjazdu ze stroma | 1971 | 标配(1971) |
| 51 | `rear_air_outlet` | Nawiewy tylne | 1961 | 标配(1961) |
| 52 | `auto_road_change` | Automatyczna zmiana pasa | 1958 | 标配(1958) |
| 53 | `inside_mirror_auto_anti_glare` | Lustro wewnętrzne (auto) | 1918 | 自动防眩目(1918) |
| 54 | `multilayer_soundproof_glass` | Szyby wygłuszające | 1867 | 前排(1343), 前排+后排(438), 全车(86) |
| 55 | `navigation_traffic_info` | Informacje o ruchu w nawigacji | 1847 | 标配(1847) |
| 56 | `backside_privacy_glass` | Szyby przyciemniane | 1815 | 标配(1815) |
| 57 | `voice_zone_recognition` | Strefy rozpoznawania głosu | 1785 | 四区域(981), 双区域(311), 六区域(272) |
| 58 | `remote_key_6` | Kluczyk NFC | 1779 | NFC/RFID钥匙(1779) |
| 59 | `car_purifier` | Oczyszczacz powietrza | 1772 | 标配(1772) |
| 60 | `karaoke` | Karaoke | 1760 | 标配(1760) |
| 61 | `front_perception_camera` | Kamera przednia (ADAS) | 1746 | 双目(1084), 单目(482), 三目(177) |
| 62 | `car_call` | Połączenie alarmowe (eCall) | 1740 | 标配(1740) |
| 63 | `voice_continuous_recognition` | Ciągłe rozpoznawanie mowy | 1710 | 标配(1710) |
| 64 | `navigation_assisted_driving_2` | Prowadzenie wspomagane (autostrada) | 1650 | 高速路段(1456), 高快领航(194) |
| 65 | `facial_recognition` | Rozpoznawanie twarzy | 1584 | 标配(1584) |
| 66 | `engine_anti_theft` | Immobilizer | 1497 | 标配(1497) |
| 67 | `aqs_air_quality_management_system` | Czujnik jakości powietrza | 1481 | 标配(1481) |
| 68 | `rear_wiper` | Tylna wycieraczka | 1441 | 标配(1441) |
| 69 | `signal_recognition` | Rozpoznawanie sygnalizacji | 1441 | 标配(1441) |
| 70 | `front_seat_memory_2` | Pamięć fotela (przód) | 1426 | 记忆(副驾驶)(1143), 记忆(主驾驶)(283) |
| 71 | `auto_road_out_in` | Automatyczny zjazd/wjazd | 1403 | 标配(1403) |
| 72 | `steer_assist_light` | Światła doświetlające | 1379 | 标配(1379) |
| 73 | `active_ambient_light_1` | Podświetlenie ambient | 1351 | 标配(1351) |
| 74 | `front_center_airbag` | Poduszka centralna przednia | 1346 | 前排(1346) |
| 75 | `forward_car_departure_reminder` | Ruszenie pojazdu z przodu | 1328 | 标配(1328) |
| 76 | `hands_off_detection` | Wykrywanie rąk na kierownicy | 1211 | 标配(1211) |
| 77 | `fade_zone_camera` | Kamera martwego pola | 1174 | 车侧盲区影像(1174) |
| 78 | `negative_ion_generator` | Jonizator | 1160 | 标配(1160) |
| 79 | `rear_seat_ventilation` | Wentylacja (tył) | 1160 | 通风(1160) |
| 80 | `reversing_camera` | Kamera cofania | 1156 | 倒车影像(1149), 标配(7) |
| 81 | `gps` | Nawigacja GPS | 1107 | 标配(1107) |
| 82 | `speech_recognition_system` | System rozpoznawania mowy | 1107 | 标配(1107) |
| 83 | `rear_independent_air_conditioning` | Klimatyzacja tylna (niezależna) | 1107 | 标配(1107) |
| 84 | `steer_wheel_memory` | Pamięć kierownicy | 1107 | 标配(655), 记忆(452) |
| 85 | `co_pilot_rear_adjustable_button` | Regulacja fotela z tyłu (boss) | 1071 | 标配(1071) |
| 86 | `car_fragrance_device` | Odświeżacz powietrza | 991 | 标配(991) |
| 87 | `mobile_remote_control_4` | Kluczyk cyfrowy | 982 | 数字钥匙(982) |
| 88 | `touch_reading_light` | Lampki do czytania (dotykowe) | 912 | 标配(912) |
| 89 | `rear_touch_control_system` | Panel dotykowy (tył) | 871 | 标配(871) |
| 90 | `voiceprint_recognition` | Rozpoznawanie głosu (biometryczne) | 844 | 标配(844) |
| 91 | `auto_park_entry` | Wjazd do garażu (auto) | 826 | 标配(826) |
| 92 | `remote_key_7` | Kluczyk UWB (cyfrowy) | 823 | UWB数字钥匙(823) |
| 93 | `original_etc` | ETC (fabryczny) | 804 | 标配(804) |
| 94 | `rollover_prevention` | System zapobiegania przewróceniu | 777 | 标配(777) |
| 95 | `memory_parking` | Pamięć parkowania | 750 | 标配(750) |
| 96 | `narrow_road_assistance` | Asystent wąskich dróg | 746 | 标配(746) |
| 97 | `track_reverse` | Wycofywanie po śladzie | 739 | 标配(739) |
| 98 | `forward_traffic_warning` | Ostrzeżenie o ruchu poprzecznym | 722 | 前方交通穿行预警(722) |
| 99 | `active_dms_fatigue_detection` | Aktywne monitorowanie kierowcy (DMS) | 721 | 标配(721) |
| 100 | `forward_traffic_braking` | Hamowanie na ruch poprzeczny | 679 | 前方交通穿行制动(679) |
| 101 | `comfortable_braking` | Komfortowe hamowanie | 654 | 标配(654) |
| 102 | `rear_seat_electric_down` | Elektr. składanie (tył) | 629 | 标配(629) |
| 103 | `hv_fast_charge` | Ładowanie wysokonapięciowe | 620 | 支持(620) |
| 104 | `vital_signs_detection` | Monitoring obecności w kabinie | 614 | 标配(614) |
| 105 | `voice_simulate` | Symulacja dźwięku silnika | 589 | 标配(589) |
| 106 | `second_independent_seat` | Niezależne fotele (2. rząd) | 536 | 标配(536) |
| 107 | `second_row_small_desktop` | Stolik (tył) | 501 | 标配(501) |
| 108 | `sky_sound_channel` | Kanały sufitowe | 468 | 标配(468) |
| 109 | `high_precision_map` | Mapy HD | 460 | 标配(460) |
| 110 | `active_noise_reduction` | Aktywna redukcja hałasu | 422 | 标配(422) |
| 111 | `front_fog_light` | Światła przeciwmgielne | 418 | LED(226), 标配(188), 卤素(3) |
| 112 | `headlamp_follow_up` | Światła skrętne | 370 | 标配(370) |
| 113 | `third_row_seat_heating` | Ogrzewanie foteli (3. rząd) | 369 | 加热(369) |
| 114 | `rear_airbag` | Poduszki tylne | 354 | 后排(354) |
| 115 | `roof_racks` | Relingi dachowe | 341 | 标配(341) |
| 116 | `panoramic_sunshade` | Roleta panoramiczna | 327 | 标配(327) |
| 117 | `second_row_seat_memory` | Pamięć foteli (2. rząd) | 273 | 记忆(273) |
| 118 | `automated_valet_parking` | Automatyczny parking (AVP) | 243 | 标配(243) |
| 119 | `sport_style_seat` | Fotele sportowe | 216 | 标配(216) |
| 120 | `passive_pedestrian_protection` | Ochrona pieszych | 199 | 标配(199) |
| 121 | `steer_wheel_shift` | Łopatki zmiany biegów | 189 | 标配(115), 换挡(74) |
| 122 | `rear_window_sunshade` | Roleta tylnej szyby | 147 | 后窗侧遮阳帘(147) |
| 123 | `main_knee_airbag` | Poduszka kolanowa (kierowca) | 144 | 主驾驶位(144) |
| 124 | `engine_sas_tech` | System Start-Stop | 139 | 标配(139) |
| 125 | `queen_seat` | Fotel Queen | 108 | 标配(108) |
| 126 | `v2x_communication` | (bez etykiety PL) | 93 | 标配(93) |
| 127 | `center_diff_lock` | Blokada centralnego dyferencjału | 91 | 标配(91) |
| 128 | `crawl_control` | (bez etykiety PL) | 76 | 标配(76) |
| 129 | `ar_reality_navigation` | (bez etykiety PL) | 69 | 标配(69) |

## Układ kierowniczy (pytanie o tylną oś skrętną)

Tylna oś skrętna = `overall_turn` (整体转向, 192 ofert) — wdrożona jako `rear_steer`. `variable_steer_system`
(zmienne przełożenie) ma 42 oferty — poniżej progu. Reszta kluczy `steer_*` to regulacja, materiał i pamięć kierownicy.

## Jak dodać pastylkę

1. `AsiaAuto_Specs_Table::FLAGS` — nowy wpis `nazwa => ['keys' => [klucz]]` (+ `match` gdy wartość ma znaczyć, `prefix` gdy klucz dynamiczny), podbij `SCHEMA_VERSION`.
2. `AsiaAuto_Search::FLAG_GROUPS` — etykieta PL; `SEKCJE` → lista `flagi` sekcji „Technologia i wyposażenie".
3. `wp eval 'AsiaAuto_Specs_Table::install();'` (×2, sprawdź `SHOW COLUMNS`), `php scripts/zbuduj-specs.php apply`.
4. Bramki: `porownaj-search.php`, `test-ui-wyszukiwarka.mjs`, `impeccable detect`.
