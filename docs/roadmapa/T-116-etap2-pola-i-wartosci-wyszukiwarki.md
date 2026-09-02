# T-116 / etap 2 — pola do wyszukiwarki zaawansowanej i ujednolicenie wartości

> Pomiar 2026-09-02 po backfillu katalogu Autohome (2 179 ofert, +290 230 pól).
> Próbka: **2 988 ofert publish** (che168 1 920 · dongchedi 939 · ręczne 103), 396 kluczy
> `_asiaauto_extra_prep` z pokryciem ≥15%. Pełny rozkład wartości z podziałem na źródło:
> `tmp/` sesji (`rozklad-wartosci.json`), tu tylko wnioski.
>
> Zakres: **analiza**. Nic tu nie zmienia danych ani UI. Decyzja co wdrażać — Janek.

---

## 1. Wniosek główny

Pokrycie pól jest załatwione (98,2% ofert ma flagi wyposażenia), ale **wartości nie są
jednorodne**. Ten sam fakt „auto ma X” zapisany jest w bazie na pięć sposobów, a ten sam
klucz potrafi znaczyć co innego zależnie od źródła. Filtr budowany wprost po `extra_prep`
dawałby wyniki losowe. Ujednolicenie jest jednak **regułowe, nie ręczne**: 9 typów rozjazdu,
każdy z jednoznaczną regułą normalizacji (sekcja 3). To potwierdza kierunek z T-116:
**spłaszczyć do `wp7j_asiaauto_specs` z normalizacją w momencie spłaszczania**, a nie
filtrować po blobie.

## 2. Dziewięć typów rozjazdu wartości (z pomiarem)

| # | typ | przykłady (klucz: wartości i ich liczność) | reguła normalizacji |
|---|---|---|---|
| 1 | **„Tak” w kilku zapisach** | `lane_warning_system`: `标配` 1822 / `车道偏离预警` 844; `front_electric_window`: `标配` 1718 / `前排` 1116; `lcd_dashboard_type`: `标配` 1732 / `全液晶` 837; `quick_charge_interface`: `支持` 1655 / `快充接口` 677; `steer_wheel_heating`: `标配` 1264 / `加热` 609 | **obecność klucza = TAK**, chyba że wartość ∈ {`选配`, `选装`, `-`, `0`, `不支持`, `无`} → NIE/OPCJA. Nazwa własna funkcji w wartości (`前方碰撞预警`, `透明影像`, `倒车影像`) to styl dongchedi dla tej samej flagi |
| 2 | **Opcja ≠ standard** | `memory_parking`: `标配` 759 / `选配` 583; `auto_road_out_in`: `标配` 1426 / `选配` 375 / `选装` 71; `car_refrigerator` 811/200/63; `car_fragrance_device` 1005/226/43 | `选配`/`选装` = **wyposażenie opcjonalne wersji**, nie egzemplarza. Do filtra „ma X” **nie liczyć** (tak robi już translator, ukrywa `选配`). Osobna decyzja: pokazywać jako „opcja”? Dotyka ~10% wartości flag |
| 3 | **Pary przód/tył w jednej wartości** (che168) | `side_air_curtain`: `前● / 后●` 1636; `front_airbag`: `主● / 副●` 1717; `multilayer_soundproof_glass`: `前排` 731 / `前排 / 后排` 635 / `前排+后排` 440 / `全车` 90; `mobile_wireless_charging`: `前排` 1479 / `标配` 966 / `前排 / 后排` 126 | rozbić na dwie flagi przy spłaszczaniu (przód, tył): `●`=TAK, `○`=opcja, `-`=NIE; `全车` = obie |
| 4 | **Liczba z jednostką lub sufiksem** | `max_external_discharge_power`: `6` 618 / `6kW` 326; `min_turning_radius`: `5.7` 192 / `5.7m` 98; `high_voltage_fast_charging_platform_800`: `800` 379 / `800V` 234; `capacity_l`: `2.0` 451 / `2.0T` 48; `oil_tank_volume`: `60` 194 / `60.0` 88; `fast_charge_electricity`: `30-80` 1017 / `30%-80%` 15; `wireless_charging_power`: `50W` | `preg_match('~-?\d+(\.\d+)?~')` → float. Jednostki dopisuje UI, nie dane |
| 5 | **Wartości parowe „N个 / M个”** (nowe, z katalogu) | 236 wartości: `camera_count` 90, `incar_camera_count_2` 63, `ultrasonic_radar` 32, `millimeter_wave_radar` 32, `laser_radar` 19 (`1个 / 5个`) | to warianty pakietowe wersji (standard / z pakietem). Do liczby brać **pierwszą** (standard). Warto poprawić w mergerze `autohome-catalog-merge.php`: dla kluczy liczbowych zapisywać pierwszą liczbę zamiast łączyć |
| 6 | **Synonimy i warianty pisowni CN** | `rear_suspension_form`: `多连杆式独立悬架` 1062 / `…悬挂` 314 (架/挂); `gearbox_type`: `湿式双离合变速箱(DCT)` 127 / `（DCT）` 100 (nawias pełnej szerokości); `automatic_drive_level`: `L2` 2232 / `L2级` 541; `fuel_label`: `92号` 592 / `92#` 378; `battery_temperature_management_system_cooling`: `液冷` 1520 / `液态冷却` 255; `oil_supply`: `直喷` 852 / `缸内直喷` 461; `sound_brand`: `丹拿` 102 / `Dynaudio丹拿` 142, `帝瓦雷` 57 / `DEVIALET帝瓦雷` 62; `navigation_assisted_driving_2`: `高速路段` / `高快领航` | słownik kanoniczny per pole (10–20 wpisów na pole, tylko dla pól filtrowanych). Regex pomocnicze: usuń `级`, `号`→`#`, `（）`→`()`, `悬挂`→`悬架` |
| 7 | **Ten sam klucz, inna semantyka per źródło** | `car_body_struct`: che168 `SUV` 1098, dongchedi `5门5座SUV` 357 — a `body_struct` **odwrotnie** (che168 `5门5座SUV` 733, dongchedi `SUV` 491). `air_supply`: che168 `涡轮增压` 884 (doładowanie), dongchedi `DOHC` 503 (rozrząd). `seat_belt_prompted`, `keyless_entry`: `全车`/`前排`/`驾驶位`/`标配`/`1` | nadwozie: regex na obu kluczach → `SUV|三厢车|掀背车|MPV|旅行车|跑车|皮卡`; drzwi/miejsca są osobno (`door_nums`, `seat_count`). Doładowanie: brać **wyłącznie `gas_form`**; `air_supply` po naprawie = rozrząd (DOHC) |
| 8 | **Ten sam sens w kilku kluczach** (zdublowane po dwóch API) | tempomat adaptacyjny: `cruise_system`=`全速自适应巡航` 2270 (80%) ∪ `full_speed_adaptive_cruise` 1053 ∪ `adaptive_cruise` 1060; moc KM: `engine_max_horsepower` 66% ∪ `electric_total_horsepower` 56% ∪ `energy_elect_max_power` `160(218Ps)` 31% ∪ `total_electric_power` kW 80%; zasięg: `cltc_recharge_mileage` 77% ∪ `recharge_mileage` 33%; strefy klimatyzacji `temperature_partition_control_1/2/3`; mapy `map_brand` ∪ `map_brand_高德`; ładowanie indukcyjne `wireless_charging_power` ∪ `wireless_charging_max_power_50` | jedno pole docelowe = **koalescencja** listy kluczy w ustalonej kolejności. Dla mocy logika już istnieje: `AsiaAuto_Spec::km_from_power()` |
| 9 | **Uszkodzony unicode** `u6807u914d` | **11 ofert** (10 ręcznych, 1 dongchedi), 314 wartości; plus `360u00b0全景影像` w 108 ofertach (90 ręcznych) | jednorazowa naprawa `preg_replace_callback('/u([0-9a-f]{4})/')` na tych 11+108 ofertach, z backupem. Bloker z T-116 („96% bazy”) **nie istnieje** — to relikt sprzed migracji na che168 i importów ręcznych |

Dwa fakty dodatkowe, które upraszczają robotę:

- **Pola, których nie ma sensu filtrować, bo są jednorodne (≥98% `标配`):** ABS, ESP, EBD,
  wspomaganie hamowania, kontrola trakcji, centralny zamek, keyless start, Bluetooth, ISOFIX,
  automatyczne światła, LED, elektryczne szyby i lusterka, regulacja wysokości świateł, OTA.
  Filtr bez mocy dyskryminującej to szum w UI. Wypadają, choć konkurent je pokazuje.
- **Rozmiar felg jest wyprowadzalny** z `rear_tire_size` (`255/50 R20`): **95,3%** ofert,
  podczas gdy `wheel_size` ma 18%. To samo z liczbą drzwi/miejsc z `car_body_struct`.

## 3. Pełna lista pól-kandydatów do wyszukiwarki

Kolumna „pokrycie” = odsetek ofert publish z wartością po backfillu. Typ: **Z** zakres
(suwak od–do), **E** enum (lista), **F** flaga (checkbox). Pozycje z założeń wątku
(masaż, lidar, 360°, mirroring telefonu, napęd, tapicerka, liczba kamer) oznaczone ★.

### 3a. Warstwa A — poza `extra_prep`, gotowe (taksonomie i meta)

| pole | typ | pokrycie | źródło | uwaga |
|---|---|---|---|---|
| marka, model, paliwo, nadwozie, rocznik, kolor, napęd | E | 99–100% | taksonomie `make`, `serie`, `fuel`, `body`, `ca-year`, `exterior-color`, `drive` | **już filtrują** |
| cena | Z | 100% | meta `price` | już filtruje |
| przebieg | Z | 100% | meta `mileage` | do dodania jak cena |
| skrzynia | E | 99,7% | taksonomia `transmission` (7 termów) | dopisać do `$tax_map` |
| stan nowy/używany | E | 100% | taksonomia `condition` | dopisać |
| na placu / w drodze | E | 100% | meta `_asiaauto_reservation_status` | dziś jako flaga w kodzie |
| moc KM | Z | 40% jako `_asiaauto_horse_power`, **98,9% po koalescencji** z `extra_prep` (pkt 8, zmierzone logiką `km_from_power()`) | | uzupełnić meta z `km_from_power()` zamiast filtrować po blobie |
| liczba właścicieli | Z | 40% | `_asiaauto_owners_count` | che168 nie wysyła; **odpuścić** |
| kolor wnętrza | E | 6% | taksonomia `interior-color` | **nieosiągalne** (cecha egzemplarza) |

### 3b. Zakresy numeryczne z `extra_prep` (Z)

| pole | klucz(e) | pokrycie | normalizacja |
|---|---|---|---|
| liczba miejsc | `seat_count` | 99,1% | int; `5/7` → 5 (min) |
| rozstaw osi | `wheelbase` | 99,1% | int mm |
| długość / szerokość / wysokość | `length`/`width`/`height`, fallback `length_width_height` `A*B*C` lub `AxBxC` | 95,7% | regex |
| masa własna / DMC | `curb_weight` / `full_load_weight` | 95,1% / 94,8% | int |
| prędkość maks. | `max_speed` | 95,4% | int |
| liczba głośników ★ | `speaker` | 95,8% | int (po backfillu bez sufiksu `喇叭`) |
| ekran centralny (cale) | `center_screen_size` | 98% | float |
| felgi (cale) | z `rear_tire_size` `R(\d\d)` | 95,3% | regex |
| przyspieszenie 0–100 | `acceleration_time` | 81,4% | float |
| moc el. łączna kW | `total_electric_power` | 80,4% | int |
| bateria kWh | `battery_capacity` | 80% | float |
| zasięg CLTC | `cltc_recharge_mileage` ∪ `recharge_mileage` | 77% → ~85% | int |
| zasięg łączny (PHEV/EREV) | `combined_cruising_range_cltc` | 25,7% | int |
| bagażnik | `baggage_volume` | 79,5% | `316-1252` → min |
| zużycie energii | `power_consumption` | 73% | float |
| moc V2L | `max_external_discharge_power` | 72% | regex kW |
| moc silnika spal. kW / pojemność | `engine_max_power` (**tylko z `发动机`**, nie `system_max_power`) / `capacity_l` | 53% / 55% | float, `2.0T` → 2.0 |
| czas szybkiego ładowania | `fast_charge_time` | 51% | h → min |
| moc ładowania DC | `max_fast_charge_power` | 40% | int |
| prześwit | `min_clearance` | 34% | int |
| liczba kamer ★ | `camera_count` | 91% | pierwsza liczba (pkt 5) |
| liczba lidarów ★ | `laser_radar` | 45,7% | pierwsza liczba; ≥1 = flaga „lidar” |

### 3c. Enumy z `extra_prep` (E)

| pole | klucz | pokrycie | wartości kanoniczne (po słowniku) |
|---|---|---|---|
| klasa pojazdu | `jb` | 99,1% | 17: 中大型SUV, 中型SUV, 中大型车, 中型车, 紧凑型SUV, 大型SUV, 紧凑型车, MPV… → PL |
| układ napędowy ★ | `driver_form` | 99,1% | 双电机四驱 / 前置前驱 / 后置后驱 / 前置四驱 / 三电机四驱 → AWD/FWD/RWD + liczba silników |
| liczba silników el. | `electric_drive_number` | 80% | 单/双/三/四电机 |
| typ skrzyni | `gearbox_type` | 95,6% | 10 → 6 po scaleniu nawiasów |
| typ szyberdachu | `skylight_type` | 95,4% | otwierany panoramiczny / stały panoramiczny / dzielony / szklany dach / brak |
| tapicerka ★ | `seat_material` | 95,9% | skóra naturalna (`真皮`, `Nappa`) / ekoskóra (`仿皮`, `皮质`) / mieszana / alcantara / tkanina |
| typ baterii | `battery_type` | 80% | LFP (`磷酸铁锂`) / NMC (`三元锂`) / mieszana |
| producent baterii | `battery_brand` | 79,7% | CATL (+江苏时代), FinDreams, CALB, SVOLT, EVE… |
| platforma napięciowa | `high_voltage_fast_charging_platform_800` | 28,8% | 400 / 800 / 900 V |
| poziom autonomii | `automatic_drive_level` | 93% | L1 / L2 (scalić `L2级`) |
| system ADAS | `driving_assist_system` | 50% (tylko che168) | HUAWEI ADS, XNGP, Leapmotor Pilot, Xiaomi HAD, NIO Aquila, Li AD Max, DiPilot… |
| procesor ADAS | `driving_assist_chip` | 35% | NVIDIA Orin-X / Orin-N / Thor-U, Horizon Journey, Qualcomm 8650 |
| system infotainment | `car_intelligent_system` | 51% | DiLink, HarmonyOS, 澎湃OS, Leapmotor OS, ZEEKR OS, XOS… |
| procesor infotainment | `car_intelligent_chip` | 45% | Snapdragon 8295 / 8155 / 8295P, BYD D100 |
| marka audio | `sound_brand` | 33,4% | HUAWEI SOUND, Dynaudio, Devialet, Harman/Kardon, Sony, Naim… (po scaleniu CN+EN) |
| strefy klimatyzacji | `temperature_partition_control_1/2/3` | ~80% | 2 / 3 / 4 strefy |
| kolory ambientu | `interior_light` | 86% | 31 / 64 / 128 / 253 / 256 / 16,8 mln / wielokolorowe |
| doładowanie | `gas_form` | 52,9% | turbo / wolnossący |
| składanie kanapy | `second_row_seat_down_ratio` | 92,9% | 40:60 / 50:50 / 40:20:40 / całość / proporcjonalnie |
| gwarancja | `period` | 93,3% | lata + km (regex, dwa zapisy cyfr) |

### 3d. Flagi wyposażenia (F), ułożone od najbardziej różnicujących

Reguła obecności z pkt 1–3. Pomijam flagi ≥98% jednorodne.

| grupa | flaga | klucz(e) | pokrycie |
|---|---|---|---|
| fotele | podgrzewane przód | `front_seat_heating_1` | 89% |
| | wentylowane przód | `front_seat_ventilation_1` | 85% |
| | **masaż przód** ★ | `front_seat_massage_1` | 57% |
| | pamięć fotela | `front_seat_memory_1` | 70% |
| | podgrzewane / wentylowane / masaż tył | `rear_seat_heating` / `_ventilation` / `_massage` | 58% / 40% / 34% |
| | elektryczna regulacja 2. rzędu | `second_row_seat_electrical_adjustment` | 40% |
| | fotel zero gravity | `zero_gravity_seat` (wartość = pozycja) | 24% |
| | podgrzewana kierownica | `steer_wheel_heating` | 67% |
| kamery i radary | **kamera 360°** ★ | `panoramic_camera` | 92,5% |
| | widok transparentny | `driving_assist_image` | 34% (dongchedi) + `透明底盘/540度影像` z katalogu |
| | kamera martwego pola | `fade_zone_camera` | 40% |
| | **lidar** ★ | `laser_radar` ≥1 | 45,7% |
| | rejestrator jazdy | `built_in_tachograph` | 82% |
| | tryb wartownika | `sentinel_mode` | 70% |
| ADAS | tempomat adaptacyjny | `cruise_system`=`全速自适应巡航` ∪ `full_speed_adaptive_cruise` ∪ `adaptive_cruise` | 80% |
| | utrzymanie pasa | `lane_keeping_assist` | 91% |
| | centrowanie na pasie | `lane_center` | 87% |
| | automatyczna zmiana pasa | `auto_road_change` | 68% |
| | nawigacja autonomiczna miasto / autostrada | `navigation_assisted_driving_1` / `_2` | 44% / 57% |
| | automatyczne parkowanie | `auto_park` | 84% |
| | parkowanie zdalne | `remote_control_move` | 70% |
| | parkowanie z pamięcią | `memory_parking` (bez `选配`) | 25% |
| | DMS (monitoring kierowcy) | `active_dms_fatigue_detection` ∪ `fatigue_driving_warning` | 74% |
| wyświetlacze | HUD | `header_display_system` | 33% |
| | **AR-HUD** | `ar_hud` | 25% |
| | ekran pasażera | `vice_screen_size_*` / `copilot_screen_resolution*` | 7,6% |
| | ekran tylny | `rear_lcd_screen` | 27,5% |
| multimedia | **mirroring telefonu** ★ | HiCar `mobile_system_8` / Carlink `mobile_system_11` / CarPlay `mobile_system_1` | 47% / 35% / 21% |
| | 5G | `data_network_2` | 58% |
| | ładowanie indukcyjne | `mobile_wireless_charging` | 86% |
| | Dolby Atmos | `dolby_panoramic_sound` | 30,5% |
| | karaoke | `karaoke` | 60% |
| | głośniki w zagłówkach | `seat_speakers` | 13,8% |
| komfort | pompa ciepła | `heat_pump_management_system` | 44% |
| | klimatyzacja tylna niezależna | `rear_independent_air_conditioning` | 37% |
| | lodówka | `car_refrigerator` | 36% (27% bez opcji) |
| | oczyszczacz / PM2.5 / jonizator / aromat | `car_purifier` / `pm25_filtrating_equipment` / `negative_ion_generator` / `car_fragrance_device` | 60% / 91% / 42% / 43% |
| | stolik 2. rząd | `second_row_small_desktop` | 22% |
| nadwozie | elektryczna klapa | `electric_back_door` | 77% |
| | klapa na czujnik | `inductive_back_door` | 27% |
| | drzwi elektryczne / domykanie | `electric_door` | 40% |
| | ukryte klamki | `hidden_door_handle` | 55% |
| | drzwi bezramkowe | `frameless_design_door` | 10,6% |
| | szyby przyciemniane tył | `backside_privacy_glass` | 62% |
| | szyby wygłuszające | `multilayer_soundproof_glass` | 63,5% |
| | relingi / hak | `roof_racks` / `drag_hook` | 12,6% / 15,3% |
| | aktywna atrapa | `active_closed_inlet_grid` | 58% |
| zawieszenie | pneumatyczne | `air_suspension` | 28% |
| | regulowane (CDC) | `variable_suspension` | 49% |
| | aktywna kontrola nadwozia | `magic_body_control` | 21% |
| EV | V2L | `vtol_power_station` | 77% |
| | 800 V | `high_voltage_fast_charging_platform_800` ≥800 | 29% |
| | podgrzewanie baterii | `battery_temperature_management_system_heating` | 67% |
| | gniazdo 230 V | `power_outlet` | 26,5% |
| dostęp | klucz Bluetooth / NFC / UWB | `remote_key_5` / `_6` / `_7` | 83% / 60% / 28% |
| | zdalne uruchomienie | `engine_remote_start` | 94% (mało różnicuje) |
| bezpieczeństwo | poduszka centralna | `front_center_airbag` | 46% |
| | kurtyny tył | `side_air_curtain` (para: `后●` lub `标配`) | 90,5% |
| | ochrona przed przewróceniem | `rollover_prevention` | 27% |
| | monitoring obecności w kabinie | `vital_signs_detection` | 23% |

**Razem: 22 zakresy, 22 enumy, ~70 flag.** Do UI nie trafia wszystko, to lista danych
gotowych do decyzji. Rekomendacja pierwszej wersji: 8 zakresów (cena, przebieg, rok, moc,
zasięg, bateria, miejsca, felgi), 6 enumów (paliwo, nadwozie, napęd, skrzynia, tapicerka,
szyberdach) i 20 flag z grup „fotele / kamery / ADAS / multimedia / komfort”, dobranych
z Ruslanem po tym, o co realnie pytają klienci.

## 4. Trzy pola z założeń, których nie ma i nie będzie

| pole | stan |
|---|---|
| kolor wnętrza | 6% (`interior-color`), cecha egzemplarza, żadne źródło nie wysyła |
| tylne koła skrętne | brak parametru w katalogu Autohome i w obu API; `overall_turn` (aktywny układ skrętu) ma 6,4% i nie jest tym samym |
| Apple CarPlay jako osobny filtr | jest w danych (`mobile_system_1`, 21%), ale to cecha wersji chińskiej; w Chinach dominują HiCar/Carlink. Uczciwie: jeden filtr „mirroring telefonu” z podtypami |

## 5. Co warto poprawić w danych PRZED spłaszczaniem (tanie, wysokozwrotne)

> **Wykonane 2026-09-02 po południu** (wszystkie cztery, wariant B dla mocy; `docs/VERSIONS.md`
> 0.34.31 dogrywka). Punkty zostają jako opis problemu; liczby w tabelach sekcji 2–3 są sprzed korekt.

1. **Pary „N个 / M个”** w mergerze katalogu: dla kluczy liczbowych zapisywać pierwszą liczbę
   (236 wartości, 5 kluczy). Jedna linia w `autohome-catalog-merge.php`.
2. **Naprawa unicode** na 11 + 108 ofertach (ręcznych) — jednorazowy skrypt z backupem.
3. **`_asiaauto_horse_power`** uzupełnić z `km_from_power()` dla 60% ofert bez wartości —
   wtedy moc filtruje się jak cena, bez czekania na tabelę specs.
4. **`air_supply`**: źródłem dwuznaczności jest `che168-param-map.php`, który mapuje **dwa**
   parametry auto-api na ten sam klucz (13 = 进气形式 doładowanie, 11 = 配气机构 rozrząd).
   Mapowanie z katalogu (`配气机构` → `air_supply` = rozrząd, zgodne z dongchedi i etykietą
   „Rozrząd”) jest poprawne. Naprawa: param 13 → `gas_form`, a w danych 1 168 ofert che168
   przenieść wartość doładowania z `air_supply` do `gas_form` (brakuje go tylko w 23).

## 6. Czego ten dokument nie rozstrzyga

Kształt tabeli `wp7j_asiaauto_specs`, UI sekcji „Zaawansowane”, deep-linki, liczniki
zależne dla flag — zgodnie z zakresem wątku. Lista z sekcji 3 to wsad do tej decyzji.
