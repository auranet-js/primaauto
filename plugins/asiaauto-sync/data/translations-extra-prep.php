<?php
/**
 * Extra prep translations → PL.
 *
 * Maps auto-api.com extra_prep keys to Polish labels, groups them into
 * display categories for the single listing page, and translates common
 * Chinese values.
 *
 * Structure:
 *   'categories' => ordered groups with their keys (display order matters)
 *   'labels'     => extra_prep key → Polish label
 *   'values'     => Chinese value → Polish translation
 *   'units'      => extra_prep key → unit suffix (mm, kg, L, etc.)
 *   'skip'       => keys to never display (redundant or internal)
 *
 * Verified against 20 real API responses, 2026-03-25.
 */
return [

    // ─── Display categories (ordered for single listing page) ────────────
    'categories' => [

        'basic' => [
            'label' => 'Dane podstawowe',
            'icon'  => 'dashicons-info-outline',
            'keys'  => [
                'measured_acceleration', 'measured_braking', 'first_owner_warranty',
                'jb', 'car_body_struct', 'body_struct', 'sub_brand_name',
                'official_price', 'market_time', 'period',
                'environmental_standards', 'maintain_cost',
            ],
        ],

        'dimensions' => [
            'label' => 'Wymiary i masa',
            'icon'  => 'dashicons-image-crop',
            'keys'  => [
                'trailer_weight',
                'length', 'width', 'height', 'wheelbase',
                'front_track', 'rear_track', 'min_clearance',
                'curb_weight', 'full_load_weight',
                'door_nums', 'seat_count', 'baggage_volume',
                'min_turning_radius', 'drag_coefficient',
            ],
        ],

        'engine' => [
            'label' => 'Silnik',
            'icon'  => 'dashicons-admin-tools',
            'keys'  => [
                'system_max_power',
                'engine_description', 'engine_model', 'capacity_l',
                'cylinder_volume_ml', 'cylinder_nums', 'cylinder_arrangement',
                'valves_per_cylinder_nums', 'compression_ratio_s',
                'gas_form', 'air_supply', 'oil_supply',
                'cylinder_material', 'cylinder_head_material',
                'engine_max_power', 'engine_max_horsepower',
                'max_power_revolution', 'engine_max_torque',
                'engine_max_torque_revolution',
                'engine_unique_tech', 'engine_sas_tech',
                'max_engine_net_power',
            ],
        ],

        'fuel' => [
            'label' => 'Paliwo i zużycie',
            'icon'  => 'dashicons-dashboard',
            'keys'  => [
                'fuel_form', 'fuel_label', 'oil_tank_volume',
                'fuel_comprehensive', 'max_speed', 'acceleration_time',
            ],
        ],

        'gearbox' => [
            'label' => 'Skrzynia biegów i napęd',
            'icon'  => 'dashicons-performance',
            'keys'  => [
                'gearbox_description', 'gearbox_type', 'stalls',
                'driver_form', 'park_brake_type',
                'gear_shift_mode_2', 'gear_shift_mode_6',
                'engine_layout_form', 'center_diff_lock',
            ],
        ],

        'suspension' => [
            'label' => 'Zawieszenie i hamulce',
            'icon'  => 'dashicons-leftright',
            'keys'  => [
                'front_suspension_form', 'rear_suspension_form',
                'front_brake_type', 'rear_brake_type',
                'power_steering_type', 'car_body_structure',
                'steer_wheel_adjustment',
            ],
        ],

        'wheels' => [
            'label' => 'Koła i opony',
            'icon'  => 'dashicons-marker',
            'keys'  => [
                'front_tire_size', 'rear_tire_size',
                'alloy_wheel', 'tire_pressure_system',
                'spare_tire_specification_1', 'spare_tire_specification_2',
            ],
        ],

        'safety' => [
            'label' => 'Bezpieczeństwo',
            'icon'  => 'dashicons-shield',
            'keys'  => [
                'main_airbag', 'vice_airbag', 'front_airbag',
                'front_rear_airbag', 'side_air_curtain',
                'main_vice_airbag', 'main_vice_knee_airbag',
                'main_knee_airbag',
                'front_near_center_airbag',
                'abs_anti_lock', 'brake_force', 'brake_assist',
                'traction_control', 'body_stability_system',
                'uphill_support', 'seat_belt_prompted',
                'child_seat_interface', 'engine_anti_theft',
                'central_locking_car', 'remote_key_1',
                'keyless_entry', 'keyless_start',
                'explosion_tire',
                'original_etc',
                'steep_slope', 'lane_center',
                'rear_airbag', 'front_center_airbag',
            ],
        ],

        'lights' => [
            'label' => 'Oświetlenie',
            'icon'  => 'dashicons-visibility',
            'keys'  => [
                'low_headlamp_type', 'high_headlamp_type',
                'front_fog_light', 'daytime_light',
                'auto_headlamp', 'headlamp_delay_off',
                'headlight_height_adjustment',
                'adaptive_light', 'headlamp_follow_up',
                'steer_assist_light', 'headlamp_rain_fog_mode',
            ],
        ],

        'comfort' => [
            'label' => 'Komfort i wnętrze',
            'icon'  => 'dashicons-admin-home',
            'keys'  => [
                'air_control_model', 'temperature_partition_control_1',
                'temperature_partition_control_2', 'temperature_partition_control_3',
                'rear_independent_air_conditioning',
                'skylight_type', 'skylight_type_1',
                'front_electric_window', 'rear_electric_window',
                'window_one_key_lift', 'window_anti_clip_function',
                'seat_material', 'seat_material_2', 'seat_material_3',
                'steer_wheel_material_2', 'steer_wheel_material_3',
                'multifunction_steer_wheel', 'steer_wheel_shift',
                'steer_wheel_heating',
                'cruise', 'front_armrest', 'back_armrest',
                'rear_cup_holder', 'centre_armrest',
                'second_row_small_desktop',
                'rain_induction_wiper', 'rear_wiper', 'heated_nozzle',
                'interior_light',
                'car_fragrance_device', 'car_purifier',
                'car_refrigerator',
                'car_fridge_feature_1', 'car_fridge_feature_2', 'car_fridge_feature_3',
                'negative_ion_generator', 'pm25_filtrating_equipment',
                'aqs_air_quality_management_system',
                'multilayer_soundproof_glass',
                'inductive_back_door', 'electric_back_door_memory',
                'co_pilot_rear_adjustable_button',
                'baggage_12v_power_outlet', 'power_outlet',
                'wireless_charging_max_power_50',
                'active_ambient_light_1', 'panoramic_sunshade',
                'rear_window_sunshade',
                'auto_brake_hold', 'rear_air_outlet', 'uv_protection_glass',
            ],
        ],

        'seats' => [
            'label' => 'Fotele',
            'icon'  => 'dashicons-universal-access',
            'keys'  => [
                'main_drive_backrest_adjustment', 'vice_drive_backrest_adjustment',
                'main_drive_back_and_forth_adjustment', 'vice_drive_back_and_forth_adjustment',
                'main_drive_height_adjustment', 'vice_drive_height_adjustment',
                'main_drive_seat_adjustment', 'vice_drive_seat_adjustment',
                'main_drive_waist_adjustment', 'vice_drive_waist_adjustment',
                'main_drive_leg_adjustment', 'vice_drive_leg_adjustment',
                'main_drive_pillow_adjustment', 'vice_drive_pillow_adjustment',
                'front_seat_heating_1', 'front_seat_memory_1',
                'front_seat_ventilation_1', 'front_seat_massage_1',
                'rear_seat_heating', 'rear_seat_ventilation', 'rear_seat_massage',
                'zero_gravity_seat',
                'second_row_seat_down_ratio', 'second_row_seat_backrest_adjustment',
                'second_row_seat_electrical_adjustment',
                'second_row_seat_back_and_forth_adjustment',
                'second_row_seat_memory', 'second_row_seat_lumbar_adjustment',
                'second_row_seat_leg_adjustment', 'vertical_move_second_row',
                'second_independent_seat',
                'third_row_seat_count', 'third_row_seat_count_2',
                'third_row_seat_heating', 'third_row_electric_adjustment',
                'layout_seat_4', 'layout_seat_5', 'layout_seat_6',
                'sport_style_seat', 'seat_cork_style', 'seat_cork_style_1',
                'steer_wheel_memory', 'elec_steer_wheel_adjustment',
                'front_seat_memory_2', 'rear_seat_electric_down',
                'queen_seat',
                'main_drive_window_sunshade_mirror_1', 'vice_drive_window_sunshade_mirror_1',
                'main_drive_window_sunshade_mirror_2', 'vice_drive_window_sunshade_mirror_2',
            ],
        ],

        'mirrors' => [
            'label' => 'Lusterka',
            'icon'  => 'dashicons-format-image',
            'keys'  => [
                'exter_mirror_elec_adjustment', 'external_mirror_heat',
                'outside_mirror_memory', 'outside_mirror_electric_folding',
                'inside_mirror_auto_anti_glare',
                'external_mirror_auto_flip', 'external_mirror_auto_fold',
                'exter_mirror_auto_prev_glare', 'exter_mirror_functional',
            ],
        ],

        'multimedia' => [
            'label' => 'Multimedia',
            'icon'  => 'dashicons-format-audio',
            'keys'  => [
                'speaker', 'center_screen_size', 'sound_brand', 'sound_brand_29',
                'lcd_dashboard_type', 'lcd_dashboard_size_12.3',
                'driving_computer_display_screen',
                'audio_and_video_system_1',
                'multimedia_interface_1', 'multimedia_interface_2',
                'multimedia_interface_3', 'multimedia_interface_5',
                'bluetooth_and_car_phone', 'gps', 'navigation_system',
                'mobile_system_1', 'mobile_system_5',
                'front_usb_typec_interface_count_',
                'rear_usb_typec_interface_count_',
                'mobile_wireless_charging',
                'wifi', 'data_network_2', 'data_network_1',
                'car_networking', 'app_store', 'ota_upgrade',
                'position_service',
                'voice_recognition', 'speech_recognition_system',
                'voice_wake_up_word', 'voice_wake_up_free',
                'voice_wake_up_recognition_1', 'voice_wake_up_recognition_2',
                'voice_wake_up_recognition_3',
                'voiceprint_recognition', 'facial_recognition',
                'visible_to_say',
                'rear_lcd_screen', 'rear_entertainment_screen_resolution',
                'copilot_screen_resolution', 'vice_screen_size_15.4',
                'seat_speakers',
                'ar_hud', 'hud_size_29', 'header_display_system',
                'built_in_tachograph', 'karaoke',
                'sound_system_layout', 'sky_sound_channel',
                'car_call',
                'rear_touch_control_system',
                'gesture_control_system', 'multi_finger_screen_control',
                'center_console_screen_material_2', 'phone_mapping',
            ],
        ],

        'parking' => [
            'label' => 'Parkowanie i kamery',
            'icon'  => 'dashicons-location-alt',
            'keys'  => [
                'reversing_camera', 'rear_parking_radar',
                'front_parking_radar', 'parking_radar',
                'auto_park', 'memory_parking',
                'auto_park_entry',
            ],
        ],

        'body' => [
            'label' => 'Nadwozie',
            'icon'  => 'dashicons-car',
            'keys'  => [
                'approach_angle', 'departure_angle',
                'door_open_way', 'rear_exhaust', 'roof_racks',
                'electric_back_door', 'hidden_door_handle',
                'frameless_design_door', 'electric_spoiler',
                'sports_appearance_kit', 'backside_privacy_glass',
                'electric_door', 'active_closed_inlet_grid',
                'sliding_door_form', 'sliding_door_4',
                'drag_coefficient',
            ],
        ],

        'ev' => [
            'label' => 'Napęd elektryczny / hybryda',
            'icon'  => 'dashicons-superhero',
            'keys'  => [
                'electric_total_horsepower',
                'fast_charge_time', 'battery_energy_density', 'battery_swap', 'wltc_combined_range', 'nedc_recharge_mileage', 'measured_range',
                'electric_description', 'electric_type', 'electric_drive_number',
                'electric_layout', 'fourwheel_drive_type',
                'total_electric_power', 'total_electric_torque',
                'electric_system_power', 'electric_system_torque',
                'front_electric_max_power', 'front_electric_max_torque',
                'front_electric_max_horsepower',
                'rear_electric_max_power', 'rear_electric_max_torque',
                'battery_type', 'battery_brand', 'battery_capacity',
                'battery_warranty',
                'recharge_mileage', 'cltc_recharge_mileage', 'wltc_recharge_mileage',
                'combined_cruising_range_cltc',
                'power_consumption',
                'wltc_fuel_comprehensive', 'state_of_charge_fuel_consumption',
                'cltc_state_of_charge_fuel_consumption',
                'wltc_state_of_charge_fuel_consumption',
                'e_energy_equivalent_fuel_consumption', 'engine_fuel_efficiency',
                'battery_charge_time', 'max_fast_charge_power',
                'fast_charge_electricity',
                'quick_charge_interface', 'quick_charge_position_v5_5',
                'quick_charge_position_v5_4',
                'slow_charge_position_v5_5', 'slow_charge_position_v5_4',
                'battery_temperature_management_system_cooling',
                'battery_temperature_management_system_heating',
                'brake_energy_regeneration', 'vtol_power_station',
                'max_external_discharge_power',
                'battery_special_technology', 'heat_pump_management_system',
            ],
        ],

        'adas' => [
            'label' => 'Systemy wspomagania jazdy',
            'icon'  => 'dashicons-shield-alt',
            'keys'  => [
                'automatic_drive_level',
                'adaptive_cruise', 'full_speed_adaptive_cruise',
                'lane_warning_system', 'front_collision_warning',
                'rear_traffic_warning', 'reversing_warning_system',
                'forward_traffic_warning', 'forward_traffic_braking',
                'dow_open_door_warning_system',
                'navigation_assisted_driving_1', 'navigation_assisted_driving_2',
                'fade_zone_camera', 'panoramic_camera', 'driving_assist_image',
                'narrow_road_assistance', 'comfortable_braking',
                'low_speed_driving_warning',
                'millimeter_wave_radar', 'ultrasonic_radar', 'camera_count',
                'incar_camera_count_2', 'laser_radar',
                'road_traffic_sign_recognition', 'signal_recognition',
                'active_dms_fatigue_detection',
                'auto_road_change', 'auto_road_out_in',
                'forward_car_departure_reminder',
                'high_precision_map', 'high_precision_position_system',
                'automated_valet_parking',
                'active_brake', 'lane_keeping_assist', 'line_support',
            ],
        ],

        'drive_modes' => [
            'label' => 'Tryby jazdy',
            'icon'  => 'dashicons-controls-repeat',
            'keys'  => [
                'drive_mode_1', 'drive_mode_2', 'drive_mode_3',
                'drive_mode_4', 'drive_mode_5', 'drive_mode_6',
                'variable_suspension', 'variable_suspension_3',
                'air_suspension', 'air_suspension_v2_1',
                'magic_body_control',
            ],
        ],

        'remote' => [
            'label' => 'Zdalne sterowanie',
            'icon'  => 'dashicons-smartphone',
            'keys'  => [
                'remote_key_3', 'remote_key_5', 'remote_key_6',
                'mobile_remote_control_4', 'mobile_remote_control_5',
                'mobile_remote_control_6', 'mobile_remote_control_7',
                'mobile_remote_control_8', 'mobile_remote_control_9',
                'engine_remote_start', 'remote_control_move',
                'sentinel_mode',
            ],
        ],

    ],

    // ─── Key → Polish label ──────────────────────────────────────────────
    'labels' => [

        // ── Che168 2026-07-22 (T-186): parametry, których dongchedi nie przysyła ──
        'fast_charge_time'        => 'Czas ładowania (szybkie)',
        'electric_total_horsepower' => 'Moc układu elektrycznego (KM)',
        'system_max_power'        => 'Moc maksymalna układu (kW)',
        'approach_angle'          => 'Kąt natarcia',
        'departure_angle'         => 'Kąt zejścia',
        'trailer_weight'          => 'Maks. masa przyczepy',
        'battery_energy_density'  => 'Gęstość energii baterii',
        'battery_swap'            => 'Wymiana baterii',
        'wltc_combined_range'     => 'Zasięg łączny (WLTC)',
        'nedc_recharge_mileage'   => 'Zasięg elektryczny (NEDC)',
        'measured_acceleration'   => '0-100 km/h (pomiar redakcji)',
        'measured_braking'        => 'Hamowanie 100-0 km/h (pomiar)',
        'measured_range'          => 'Zasięg rzeczywisty (pomiar)',
        'first_owner_warranty'    => 'Gwarancja pierwszego właściciela',
        // Basic
        'jb'                          => 'Klasa pojazdu',
        'car_body_struct'             => 'Typ nadwozia',
        'body_struct'                 => 'Konstrukcja nadwozia',
        'sub_brand_name'              => 'Submarka',
        'official_price'              => 'Cena katalogowa',
        'market_time'                 => 'Premiera rynkowa',
        'period'                      => 'Gwarancja fabryczna',
        'environmental_standards'     => 'Norma emisji',
        'maintain_cost'               => 'Koszt serwisu (5 lat)',

        // Dimensions
        'length'                      => 'Długość',
        'width'                       => 'Szerokość',
        'height'                      => 'Wysokość',
        'wheelbase'                   => 'Rozstaw osi',
        'front_track'                 => 'Rozstaw kół przód',
        'rear_track'                  => 'Rozstaw kół tył',
        'min_clearance'               => 'Prześwit',
        'curb_weight'                 => 'Masa własna',
        'full_load_weight'            => 'Masa całkowita',
        'door_nums'                   => 'Liczba drzwi',
        'seat_count'                  => 'Liczba miejsc',
        'baggage_volume'              => 'Pojemność bagażnika',
        'length_width_height'         => 'Wymiary (DxSxW)',

        // Engine
        'engine_description'          => 'Silnik',
        'engine_model'                => 'Oznaczenie silnika',
        'capacity_l'                  => 'Pojemność',
        'cylinder_volume_ml'          => 'Pojemność skokowa',
        'cylinder_nums'               => 'Liczba cylindrów',
        'cylinder_arrangement'        => 'Układ cylindrów',
        'valves_per_cylinder_nums'    => 'Zawory na cylinder',
        'compression_ratio_s'         => 'Stopień sprężania',
        'gas_form'                    => 'Doładowanie',
        'air_supply'                  => 'Rozrząd',
        'oil_supply'                  => 'Układ zasilania',
        'cylinder_material'           => 'Materiał bloku',
        'cylinder_head_material'      => 'Materiał głowicy',
        'engine_max_power'            => 'Moc maksymalna',
        'engine_max_horsepower'       => 'Moc (KM)',
        'max_power_revolution'        => 'Obroty maks. mocy',
        'engine_max_torque'           => 'Maks. moment obrotowy',
        'engine_max_torque_revolution' => 'Obroty maks. momentu',
        'energy_elect_max_power'      => 'Moc (kW/KM)',
        'energy_elect_max_torque'     => 'Moment obrotowy',
        'engine_unique_tech'          => 'Technologia silnika',
        'engine_sas_tech'             => 'System Start-Stop',

        // Fuel
        'fuel_form'                   => 'Rodzaj paliwa',
        'fuel_label'                  => 'Oktany',
        'oil_tank_volume'             => 'Pojemność zbiornika',
        'fuel_comprehensive'          => 'Spalanie (cykl mieszany)',
        'max_speed'                   => 'Prędkość maksymalna',
        'acceleration_time'           => '0-100 km/h',

        // Gearbox
        'gearbox_description'         => 'Skrzynia biegów',
        'gearbox_type'                => 'Typ skrzyni',
        'stalls'                      => 'Liczba biegów',
        'driver_form'                 => 'Układ napędowy',
        'park_brake_type'             => 'Hamulec postojowy',
        'gear_shift_mode_2'           => 'Selektor biegów',
        'gear_shift_mode_6'           => 'Tryb zmiany biegów',
        'engine_layout_form'          => 'Układ silnika',

        // Suspension
        'front_suspension_form'       => 'Zawieszenie przednie',
        'rear_suspension_form'        => 'Zawieszenie tylne',
        'front_brake_type'            => 'Hamulce przednie',
        'rear_brake_type'             => 'Hamulce tylne',
        'power_steering_type'         => 'Wspomaganie kierownicy',
        'car_body_structure'          => 'Struktura nadwozia',
        'steer_wheel_adjustment'      => 'Regulacja kierownicy',

        // Wheels
        'front_tire_size'             => 'Opony przednie',
        'rear_tire_size'              => 'Opony tylne',
        'alloy_wheel'                 => 'Felgi aluminiowe',
        'tire_pressure_system'        => 'Czujniki ciśnienia opon',
        'spare_tire_specification_1'  => 'Koło zapasowe',
        'spare_tire_specification_2'  => 'Koło dojazdowe',

        // Safety
        'main_airbag'                 => 'Poduszka kierowcy',
        'vice_airbag'                 => 'Poduszka pasażera',
        'front_airbag'                => 'Poduszki przednie',
        'front_rear_airbag'           => 'Kurtyny boczne',
        'side_air_curtain'            => 'Kurtyny powietrzne',
        'main_vice_airbag'            => 'Poduszki przód',
        'main_vice_knee_airbag'       => 'Poduszki kolanowe',
        'front_near_center_airbag'    => 'Poduszka centralna',
        'abs_anti_lock'               => 'ABS',
        'brake_force'                 => 'EBD (rozdział hamowania)',
        'brake_assist'                => 'Wspomaganie hamowania',
        'traction_control'            => 'Kontrola trakcji',
        'body_stability_system'       => 'ESP (stabilizacja)',
        'uphill_support'              => 'Asystent ruszania',
        'seat_belt_prompted'          => 'Przypomnienie o pasach',
        'child_seat_interface'        => 'ISOFIX',
        'engine_anti_theft'           => 'Immobilizer',
        'central_locking_car'         => 'Centralny zamek',
        'remote_key_1'                => 'Kluczyk',
        'keyless_entry'               => 'Keyless entry',
        'keyless_start'               => 'Keyless start',
        'explosion_tire'              => 'Opony run-flat',
        'fatigue_driving_warning'     => 'Wykrywanie zmęczenia',
        'line_support'                => 'Asystent pasa ruchu',
        'active_brake'                => 'Hamowanie awaryjne',
        'auto_brake_hold'             => 'Auto Hold',
        'rear_air_outlet'             => 'Nawiewy tylne',
        'uv_protection_glass'         => 'Szyby z filtrem UV',
        'center_diff_lock'            => 'Blokada centralnego dyferencjału',
        'phone_mapping'               => 'Android Auto / Apple CarPlay',
        'lane_keeping_assist'         => 'Utrzymanie pasa ruchu',

        // Lights
        'low_headlamp_type'           => 'Światła mijania',
        'high_headlamp_type'          => 'Światła drogowe',
        'front_fog_light'             => 'Światła przeciwmgielne',
        'daytime_light'               => 'Światła dzienne',
        'auto_headlamp'               => 'Automatyczne światła',
        'headlamp_delay_off'          => 'Opóźnione wyłączanie',
        'headlight_height_adjustment' => 'Regulacja świateł',
        'adaptive_light'              => 'Światła adaptacyjne',
        'headlamp_follow_up'          => 'Światła skrętne',
        'steer_assist_light'          => 'Światła doświetlające',
        'headlamp_rain_fog_mode'      => 'Tryb deszcz/mgła',

        // Comfort
        'air_control_model'           => 'Klimatyzacja',
        'temperature_partition_control_1' => 'Strefy klimatyzacji',
        'skylight_type'               => 'Szyberdach',
        'skylight_type_1'             => 'Szyberdach',
        'front_electric_window'       => 'Szyby el. przód',
        'rear_electric_window'        => 'Szyby el. tył',
        'window_one_key_lift'         => 'Szyby jednym przyciskiem',
        'window_anti_clip_function'   => 'Zabezpieczenie szyb',
        'seat_material'               => 'Tapicerka',
        'seat_material_2'             => 'Tapicerka (dodatkowa)',
        'seat_material_3'             => 'Tapicerka (dodatkowa)',
        'steer_wheel_material_2'      => 'Kierownica — materiał',
        'multifunction_steer_wheel'   => 'Kierownica wielofunkcyjna',
        'steer_wheel_shift'           => 'Łopatki zmiany biegów',
        'cruise'                      => 'Tempomat',
        'front_armrest'               => 'Podłokietnik przedni',
        'back_armrest'                => 'Podłokietnik tylny',
        'rear_cup_holder'             => 'Uchwyty na kubki (tył)',
        'centre_armrest'              => 'Podłokietnik centralny',
        'rain_induction_wiper'        => 'Automatyczne wycieraczki',
        'rear_wiper'                  => 'Tylna wycieraczka',
        'car_fragrance_device'        => 'Odświeżacz powietrza',
        'car_fridge_feature_1'        => 'Lodówka — funkcja 1',
        'car_fridge_feature_2'        => 'Lodówka — funkcja 2',
        'car_fridge_feature_3'        => 'Lodówka — funkcja 3',
        'multilayer_soundproof_glass' => 'Szyby wygłuszające',

        // Seats
        'main_drive_backrest_adjustment'       => 'Regulacja oparcia (kierowca)',
        'vice_drive_backrest_adjustment'       => 'Regulacja oparcia (pasażer)',
        'main_drive_back_and_forth_adjustment'  => 'Regulacja wzdłużna (kierowca)',
        'vice_drive_back_and_forth_adjustment'  => 'Regulacja wzdłużna (pasażer)',
        'main_drive_height_adjustment'          => 'Regulacja wysokości (kierowca)',
        'main_drive_seat_adjustment'            => 'Regulacja elektryczna (kierowca)',
        'vice_drive_seat_adjustment'            => 'Regulacja elektryczna (pasażer)',
        'main_drive_waist_adjustment'           => 'Podparcie lędźwiowe (kierowca)',
        'vice_drive_waist_adjustment'           => 'Podparcie lędźwiowe (pasażer)',
        'main_drive_leg_adjustment'             => 'Podparcie nóg (kierowca)',
        'vice_drive_leg_adjustment'             => 'Podparcie nóg (pasażer)',
        'main_drive_pillow_adjustment'          => 'Regulacja zagłówka (kierowca)',
        'vice_drive_pillow_adjustment'          => 'Regulacja zagłówka (pasażer)',
        'second_row_seat_lumbar_adjustment'     => 'Podparcie lędźwiowe (2. rząd)',
        'second_row_seat_leg_adjustment'        => 'Podparcie nóg (2. rząd)',
        'front_seat_heating_1'                  => 'Ogrzewanie foteli',
        'front_seat_memory_1'                   => 'Pamięć ustawień fotela',
        'second_row_seat_down_ratio'            => 'Składanie tylnej kanapy',
        'second_row_seat_backrest_adjustment'   => 'Regulacja oparcia (tył)',
        'third_row_seat_count_2'                => 'Trzeci rząd siedzeń',
        'sport_style_seat'                      => 'Fotele sportowe',
        'main_drive_window_sunshade_mirror_2'   => 'Osłona p-słoneczna (kierowca)',
        'vice_drive_window_sunshade_mirror_2'   => 'Osłona p-słoneczna (pasażer)',
        'zero_gravity_seat'                     => 'Fotel zero gravity',
        'second_row_seat_electrical_adjustment'  => 'Regulacja elektryczna (2. rząd)',
        'second_row_seat_back_and_forth_adjustment' => 'Regulacja wzdłużna (2. rząd)',
        'second_row_seat_memory'                => 'Pamięć foteli (2. rząd)',
        'vertical_move_second_row'              => 'Regulacja pionowa (2. rząd)',
        'second_independent_seat'               => 'Niezależne fotele (2. rząd)',
        'third_row_seat_count'                  => 'Liczba miejsc (3. rząd)',
        'third_row_seat_heating'                => 'Ogrzewanie foteli (3. rząd)',
        'third_row_electric_adjustment'         => 'Regulacja elektryczna (3. rząd)',
        'layout_seat_6'                         => 'Układ siedzeń',
        'seat_cork_style'                       => 'Tapicerka foteli',
        'seat_cork_style_1'                     => 'Tapicerka foteli (dodatkowa)',
        'main_drive_window_sunshade_mirror_1'   => 'Osłona lusterka (kierowca)',
        'vice_drive_window_sunshade_mirror_1'   => 'Osłona lusterka (pasażer)',

        // Mirrors
        'exter_mirror_elec_adjustment'         => 'Elektryczna regulacja lusterek',
        'external_mirror_heat'                  => 'Ogrzewanie lusterek',
        'outside_mirror_memory'                 => 'Pamięć ustawień lusterek',
        'outside_mirror_electric_folding'       => 'Elektrycznie składane lusterka',
        'inside_mirror_auto_anti_glare'         => 'Lustro wewnętrzne (auto)',
        'external_mirror_auto_flip'             => 'Auto pochylanie lusterek',
        'external_mirror_auto_fold'             => 'Auto składanie lusterek',
        'exter_mirror_functional'               => 'Funkcja lusterek',

        // Multimedia
        'speaker'                     => 'Głośniki',
        'center_screen_size'          => 'Ekran centralny',
        'lcd_dashboard_type'          => 'Zegary',
        'lcd_dashboard_size_12.3'     => 'Wielkość zegarów',
        'driving_computer_display_screen' => 'Komputer pokładowy',
        'audio_and_video_system_1'    => 'System audio',
        'multimedia_interface_1'      => 'Złącze multimedialne 1',
        'multimedia_interface_2'      => 'Złącze multimedialne 2',
        'multimedia_interface_3'      => 'Złącze multimedialne 3',
        'multimedia_interface_5'      => 'Złącze multimedialne 4',
        'bluetooth_and_car_phone'     => 'Bluetooth / telefon',
        'gps'                         => 'Nawigacja GPS',
        'mobile_system_5'             => 'System infotainment',
        'mobile_system_1'             => 'System operacyjny',
        'front_usb_typec_interface_count_' => 'USB / USB-C (przód)',
        'rear_usb_typec_interface_count_'  => 'USB / USB-C (tył)',
        'sound_brand'                 => 'System audio',
        'sound_brand_23'              => 'System audio',
        'sound_brand_29'              => 'System audio',
        'interior_light'              => 'Oświetlenie ambient',
        'ar_hud'                      => 'Wyświetlacz AR HUD',
        'hud_size_29'                 => 'Wielkość HUD',
        'header_display_system'       => 'Wyświetlacz HUD',
        'rear_lcd_screen'             => 'Ekran tylny',
        'rear_entertainment_screen_resolution' => 'Rozdzielczość ekranu tylnego',
        'copilot_screen_resolution'   => 'Rozdzielczość ekranu pasażera',
        'vice_screen_size_15.4'       => 'Ekran pasażera',
        'seat_speakers'               => 'Głośniki w fotelach',

        // Parking
        'reversing_camera'            => 'Kamera cofania',
        'rear_parking_radar'          => 'Czujniki parkowania tył',
        'front_parking_radar'         => 'Czujniki parkowania przód',
        'parking_radar'               => 'Czujniki parkowania',
        'auto_park'                   => 'Automatyczne parkowanie',
        'memory_parking'              => 'Pamięć parkowania',

        // Body
        'door_open_way'               => 'Typ drzwi',
        'rear_exhaust'                => 'Wydech',
        'roof_racks'                  => 'Relingi dachowe',
        'electric_back_door'          => 'Elektryczna klapa',
        'hidden_door_handle'          => 'Ukryte klamki',
        'frameless_design_door'       => 'Drzwi bezramkowe',
        'electric_spoiler'            => 'Elektryczny spoiler',
        'sports_appearance_kit'       => 'Pakiet sportowy',
        'backside_privacy_glass'      => 'Szyby przyciemniane',

        // EV / Hybrid
        'electric_description'        => 'Napęd elektryczny',
        'electric_type'               => 'Typ silnika elektrycznego',
        'electric_drive_number'       => 'Liczba silników',
        'electric_layout'             => 'Rozmieszczenie silników',
        'fourwheel_drive_type'        => 'Typ napędu 4x4',
        'total_electric_power'        => 'Łączna moc (kW)',
        'total_electric_torque'       => 'Łączny moment (Nm)',
        'electric_system_power'       => 'Moc układu',
        'electric_system_torque'      => 'Moment układu',
        'front_electric_max_power'    => 'Moc silnika przedniego',
        'front_electric_max_torque'   => 'Moment silnika przedniego',
        'front_electric_max_horsepower' => 'Moc przedniego (KM)',
        'rear_electric_max_power'     => 'Moc silnika tylnego',
        'rear_electric_max_torque'    => 'Moment silnika tylnego',
        'battery_type'                => 'Typ baterii',
        'battery_brand'               => 'Producent baterii',
        'battery_capacity'            => 'Pojemność baterii (kWh)',
        'battery_warranty'            => 'Gwarancja baterii',
        'recharge_mileage'            => 'Zasięg elektryczny',
        'cltc_recharge_mileage'       => 'Zasięg elektryczny (CLTC)',
        'wltc_recharge_mileage'       => 'Zasięg elektryczny (WLTC)',
        'combined_cruising_range_cltc' => 'Zasięg łączny (CLTC)',
        'power_consumption'           => 'Zużycie energii (kWh/100km)',
        'wltc_fuel_comprehensive'     => 'Spalanie WLTC (L/100km)',
        'state_of_charge_fuel_consumption' => 'Spalanie (rozładowana bateria)',
        'cltc_state_of_charge_fuel_consumption' => 'Spalanie CLTC (rozład.)',
        'wltc_state_of_charge_fuel_consumption' => 'Spalanie WLTC (rozład.)',
        'e_energy_equivalent_fuel_consumption' => 'Ekwiwalent paliwowy',
        'engine_fuel_efficiency'      => 'Sprawność silnika (%)',
        'battery_charge_time'         => 'Czas ładowania',
        'max_fast_charge_power'       => 'Maks. moc szybkiego ładowania',
        'fast_charge_electricity'     => 'Szybkie ładowanie (zakres %)',
        'quick_charge_interface'      => 'Gniazdo szybkiego ładowania',
        'quick_charge_position_v5_5'  => 'Pozycja gniazda (szybkie)',
        'quick_charge_position_v5_4'  => 'Pozycja gniazda (szybkie)',
        'slow_charge_position_v5_5'   => 'Pozycja gniazda (wolne)',
        'slow_charge_position_v5_4'   => 'Pozycja gniazda (wolne)',
        'battery_temperature_management_system_cooling' => 'Chłodzenie baterii',
        'battery_temperature_management_system_heating' => 'Ogrzewanie baterii',
        'brake_energy_regeneration'   => 'Rekuperacja',
        'vtol_power_station'          => 'V2L (zasilanie zewnętrzne)',
        'max_external_discharge_power' => 'Maks. moc V2L',

        // ADAS
        'automatic_drive_level'       => 'Poziom autonomii',
        'adaptive_cruise'             => 'Tempomat adaptacyjny',
        'full_speed_adaptive_cruise'  => 'Tempomat adaptacyjny (pełny zakres)',
        'lane_warning_system'         => 'Ostrzeżenie o opuszczeniu pasa',
        'front_collision_warning'     => 'Ostrzeżenie o kolizji (przód)',
        'rear_traffic_warning'        => 'Ostrzeżenie o kolizji (tył)',
        'reversing_warning_system'    => 'Ostrzeżenie boczne (cofanie)',
        'forward_traffic_warning'     => 'Ostrzeżenie o ruchu poprzecznym',
        'forward_traffic_braking'     => 'Hamowanie na ruch poprzeczny',
        'dow_open_door_warning_system' => 'Ostrzeżenie DOW (otwieranie drzwi)',
        'navigation_assisted_driving_1' => 'Prowadzenie wspomagane (miasto)',
        'navigation_assisted_driving_2' => 'Prowadzenie wspomagane (autostrada)',
        'fade_zone_camera'            => 'Kamera martwego pola',
        'panoramic_camera'            => 'Kamera 360°',
        'driving_assist_image'        => 'Widok transparentny',
        'narrow_road_assistance'      => 'Asystent wąskich dróg',
        'comfortable_braking'         => 'Komfortowe hamowanie',
        'low_speed_driving_warning'   => 'Ostrzeżenie przy niskiej prędkości',
        'millimeter_wave_radar'       => 'Radary milimetrowe',
        'ultrasonic_radar'            => 'Czujniki ultradźwiękowe',
        'camera_count'                => 'Liczba kamer',
        'incar_camera_count_2'        => 'Kamery wewnętrzne',

        // Drive modes
        'drive_mode_1'                => 'Tryb 1',
        'drive_mode_2'                => 'Tryb 2',
        'drive_mode_3'                => 'Tryb 3',
        'drive_mode_4'                => 'Tryb 4',
        'drive_mode_5'                => 'Tryb 5',
        'drive_mode_6'                => 'Tryb 6',
        'variable_suspension'         => 'Regulowane zawieszenie',
        'variable_suspension_3'       => 'Regulowane zawieszenie',
        'air_suspension'              => 'Zawieszenie pneumatyczne',
        'air_suspension_v2_1'         => 'Zawieszenie pneumatyczne',
        'magic_body_control'          => 'Aktywna kontrola nadwozia',

        // Remote
        'remote_key_3'                => 'Kluczyk inteligentny',
        'remote_key_5'                => 'Klucz Bluetooth (telefon)',
        'mobile_remote_control_4'     => 'Kluczyk cyfrowy',
        'mobile_remote_control_5'     => 'Monitoring pojazdu',
        'mobile_remote_control_6'     => 'Zdalne sterowanie',
        'mobile_remote_control_7'     => 'Zarządzanie ładowaniem',
        'mobile_remote_control_8'     => 'Rezerwacja serwisu',
        'mobile_remote_control_9'     => 'Lokalizator pojazdu',
        'engine_remote_start'         => 'Zdalny rozruch',
        'remote_control_move'         => 'Zdalne manewrowanie',
        'sentinel_mode'               => 'Tryb wartownika',

        // Bulk additions v0.9.1 — uncategorized keys from premium EVs
        'lane_center'                 => 'Centrowanie na pasie',
        'steep_slope'                 => 'Asystent zjazdu ze stroma',
        'rear_airbag'                 => 'Poduszki tylne',
        'front_center_airbag'        => 'Poduszka centralna przednia',
        'main_knee_airbag'            => 'Poduszka kolanowa (kierowca)',
        'original_etc'                => 'ETC (fabryczny)',
        'road_traffic_sign_recognition' => 'Rozpoznawanie znaków drogowych',
        'signal_recognition'          => 'Rozpoznawanie sygnalizacji',
        'active_dms_fatigue_detection' => 'Aktywne monitorowanie kierowcy (DMS)',
        'auto_road_change'            => 'Automatyczna zmiana pasa',
        'auto_road_out_in'            => 'Automatyczny zjazd/wjazd',
        'forward_car_departure_reminder' => 'Ruszenie pojazdu z przodu',
        'high_precision_map'          => 'Mapy HD',
        'high_precision_position_system' => 'Pozycjonowanie precyzyjne',
        'automated_valet_parking'     => 'Automatyczny parking (AVP)',
        'laser_radar'                 => 'LiDAR',
        'built_in_tachograph'         => 'Rejestrator jazdy (wbudowany)',
        'karaoke'                     => 'Karaoke',
        'sound_system_layout'         => 'Układ nagłośnienia',
        'sky_sound_channel'           => 'Kanały sufitowe',
        'car_call'                    => 'Połączenie alarmowe (eCall)',
        'rear_touch_control_system'   => 'Panel dotykowy (tył)',
        'gesture_control_system'      => 'Sterowanie gestami',
        'multi_finger_screen_control' => 'Sterowanie wielodotykowe',
        'center_console_screen_material_2' => 'Materiał ekranu',
        'rear_seat_heating'           => 'Ogrzewanie (tył)',
        'rear_seat_ventilation'       => 'Wentylacja (tył)',
        'rear_seat_massage'           => 'Masaż (tył)',
        'front_seat_ventilation_1'    => 'Wentylacja foteli (przód)',
        'front_seat_massage_1'        => 'Masaż foteli (przód)',
        'front_seat_memory_2'         => 'Pamięć fotela (przód)',
        'rear_seat_electric_down'     => 'Elektr. składanie (tył)',
        'car_purifier'                => 'Oczyszczacz powietrza',
        'pm25_filtrating_equipment'   => 'Filtr PM2.5',
        'aqs_air_quality_management_system' => 'Czujnik jakości powietrza',
        'negative_ion_generator'      => 'Jonizator',
        'heated_nozzle'               => 'Podgrzewane dysze spryskiwacza',
        'car_refrigerator'            => 'Lodówka samochodowa',
        'active_ambient_light_1'      => 'Podświetlenie ambient',
        'panoramic_sunshade'          => 'Roleta panoramiczna',
        'rear_window_sunshade'        => 'Roleta tylnej szyby',
        'wireless_charging_max_power_50' => 'Ładowanie indukcyjne (50W)',
        'mobile_wireless_charging'    => 'Ładowanie indukcyjne',
        'baggage_12v_power_outlet'    => 'Gniazdo 12V (bagażnik)',
        'power_outlet'                => 'Gniazdo 230V',
        'inductive_back_door'         => 'Klapa na czujnik ruchu',
        'electric_back_door_memory'   => 'Pamięć pozycji klapy',
        'co_pilot_rear_adjustable_button' => 'Regulacja fotela z tyłu (boss)',
        'rear_independent_air_conditioning' => 'Klimatyzacja tylna (niezależna)',
        'temperature_partition_control_2' => 'Strefy klimatyzacji',
        'temperature_partition_control_3' => 'Strefy klimatyzacji',
        'steer_wheel_material_3'      => 'Kierownica — materiał',
        'steer_wheel_heating'         => 'Podgrzewana kierownica',
        'steer_wheel_memory'          => 'Pamięć kierownicy',
        'elec_steer_wheel_adjustment' => 'Elektryczna regulacja kierownicy',
        'vice_drive_height_adjustment' => 'Regulacja wysokości (pasażer)',
        'electric_door'               => 'Drzwi elektryczne',
        'active_closed_inlet_grid'    => 'Aktywna atrapa',
        'sliding_door_form'           => 'Typ drzwi przesuwnych',
        'sliding_door_4'              => 'Drzwi przesuwne',
        'drag_coefficient'            => 'Współczynnik oporu Cx',
        'min_turning_radius'          => 'Promień skrętu',
        'max_engine_net_power'        => 'Moc netto',
        'layout_seat_4'               => 'Układ siedzeń',
        'layout_seat_5'               => 'Układ siedzeń',
        'auto_park_entry'             => 'Wjazd do garażu (auto)',
        'queen_seat'                  => 'Fotel Queen',
        'second_row_small_desktop'    => 'Stolik (tył)',
        'remote_key_6'                => 'Kluczyk NFC',
        'battery_special_technology'  => 'Technologia baterii',
        'heat_pump_management_system' => 'Pompa ciepła',
        'exter_mirror_auto_prev_glare' => 'Auto-ściemnianie lusterek',
        'voice_recognition'           => 'Rozpoznawanie głosu',
        'speech_recognition_system'   => 'System rozpoznawania mowy',
        'voice_wake_up_word'          => 'Słowo aktywacji',
        'voice_wake_up_free'          => 'Aktywacja bez słowa-klucza',
        'visible_to_say'              => 'Sterowanie głosem (ekran)',
        'voice_wake_up_recognition_1' => 'Strefa głosowa — kierowca',
        'voice_wake_up_recognition_2' => 'Strefa głosowa — pasażer',
        'voice_wake_up_recognition_3' => 'Strefa głosowa — 2. rząd',
        'voiceprint_recognition'      => 'Rozpoznawanie głosu (biometryczne)',
        'facial_recognition'          => 'Rozpoznawanie twarzy',
        'wifi'                        => 'WiFi',
        'app_store'                   => 'Sklep z aplikacjami',
        'car_networking'              => 'Łączność z chmurą',
        'ota_upgrade'                 => 'Aktualizacje OTA',
        'position_service'            => 'Usługi lokalizacyjne',
        'navigation_system'           => 'System nawigacji',
        'data_network_1'              => 'Sieć danych',
        'data_network_2'              => 'Sieć danych',
    ],

    // ─── Common Chinese values → Polish ──────────────────────────────────
    'values' => [
        // Standard
        '标配'          => 'Tak',
        // '选配' (optional on new-car spec) is SKIPPED in translator code —
        // meaningless for used cars (unknown if car actually has the feature).

        // Fuel
        '汽油'          => 'Benzyna',
        '柴油'          => 'Diesel',
        '电'            => 'Elektryczny',
        '油电混合'      => 'Hybryda',
        '插电式混合动力' => 'Plug-in hybryda',
        '增程式'         => 'Range extender',
        '纯电动'         => 'Elektryczny',

        // Turbo
        '自然吸气'      => 'Wolnossący',
        '涡轮增压'      => 'Turbo',
        '双涡轮增压'    => 'Bi-turbo',
        '机械增压'      => 'Kompresor',

        // Valve train
        'SOHC'          => 'SOHC',
        'DOHC'          => 'DOHC',

        // Fuel delivery
        '多点电喷'          => 'Wtrysk wielopunktowy (MPI)',
        '直喷'              => 'Wtrysk bezpośredni (GDI)',
        '混合喷射'          => 'Wtrysk mieszany (MPI+GDI)',
        '单点电喷'          => 'Wtrysk jednopunktowy',
        '化油器'            => 'Gaźnik',

        // Block material
        '铝合金'            => 'Aluminium',
        '铸铁'              => 'Żeliwo',

        // Cylinder arrangement
        'L'                 => 'Rzędowy (L)',
        'V'                 => 'Widlasty (V)',
        'H'                 => 'Bokser (H)',
        'W'                 => 'W',

        // Gearbox
        '手动变速箱(MT)'            => 'Manualna (MT)',
        '自动变速箱(AT)'            => 'Automatyczna (AT)',
        '手自一体变速箱(AT)'        => 'Automatyczna (tiptronic)',
        '无级变速箱(CVT)'           => 'Bezstopniowa (CVT)',
        '双离合变速箱(DCT)'         => 'Dwusprzęgłowa (DCT)',
        '电动车单速变速箱'          => 'Jednobiegowa (EV)',
        '湿式双离合变速箱(DCT)'     => 'Mokre DCT',
        '干式双离合变速箱(DCT)'     => 'Suche DCT',

        // Drive layout
        '前置前驱'          => 'Przód — FWD',
        '前置后驱'          => 'Przód/tył — RWD',
        '前置四驱'          => 'Przód — 4WD',
        '中置后驱'          => 'Środek/tył — RWD',
        '后置后驱'          => 'Tył — RWD',

        // Parking brake
        '手刹'              => 'Ręczny',
        '脚刹'              => 'Nożny',
        '电子驻车'          => 'Elektroniczny',

        // Brake types
        '通风盘式'          => 'Wentylowane tarczowe',
        '实心盘式'          => 'Tarczowe (pełne)',
        '鼓式'              => 'Bębnowe',

        // Steering
        '电动助力'          => 'Elektryczne',
        '电子液压助力'      => 'Elektrohydrauliczne',
        '机械液压助力'      => 'Hydrauliczne',

        // Body structure
        '承载式'            => 'Samonośne',
        '非承载式'          => 'Ramowe',

        // Body type
        '三厢车'            => 'Sedan',
        '两厢车'            => 'Hatchback',
        'SUV'               => 'SUV',
        'MPV'               => 'MPV',

        // Door types
        '平开门'            => 'Standardowe',
        '蝴蝶门'            => 'Motylkowe',
        '鸥翼门'            => 'Żółwiowe (gullwing)',
        '剪刀门'            => 'Nożycowe (scissor)',
        '对开门'            => 'Przeciwbieżne',

        // Headlamp types
        '卤素'              => 'Halogenowe',
        'LED'               => 'LED',
        '氙气'              => 'Xenon',
        '激光'              => 'Laserowe',

        // Seat material
        '仿皮'              => 'Ekoskóra',
        '织物'              => 'Tkanina',
        '真皮'              => 'Skóra naturalna',
        'Alcantara'         => 'Alcantara',
        '翻毛皮'            => 'Alcantara',
        '皮质'              => 'Skóra',

        // Steering wheel
        '皮质'              => 'Skóra',

        // Climate
        '手动'              => 'Manualna',
        '自动'              => 'Automatyczna',
        '双区空调'          => 'Dwustrefowa',
        '三区空调'          => 'Trzystrefowa',
        '四区空调'          => 'Czterostrefowa',

        // Sunroof
        '单天窗'            => 'Standardowy',
        '全景天窗'          => 'Panoramiczny',
        '分段式天窗'        => 'Dzielony',

        // Seat adjustments
        '靠背角度'          => 'Kąt oparcia',
        '前后移动'          => 'Przód/tył',
        '高低调节'          => 'Góra/dół',
        '主驾驶位'          => 'Kierowca',
        '副驾驶位'          => 'Pasażer',
        '前排'              => 'Przednie',
        '后排'              => 'Tylne',
        '全车'              => 'Wszystkie',

        // Seat folding
        '整排放倒'          => 'Składane całkowicie',
        '比例放倒'          => 'Składane proporcjonalnie',
        '靠背角度调节'      => 'Regulacja kąta oparcia',

        // Mirrors
        '电动调节'          => 'Elektryczna',
        '电动折叠'          => 'Elektryczne składanie',
        '加热'              => 'Ogrzewanie',
        '记忆'              => 'Z pamięcią',
        '自动防眩目'        => 'Automatyczne ściemnianie',

        // Display
        '非全液晶'          => 'Częściowo cyfrowe',
        '全液晶'            => 'W pełni cyfrowe',
        '单色'              => 'Monochromatyczny',
        '彩色'              => 'Kolorowy',

        // Audio
        '单碟CD'            => 'CD',

        // Tire pressure
        '胎压报警'          => 'Alarm ciśnienia',
        '胎压显示'          => 'Wyświetlanie ciśnienia',

        // Spare tire
        '全尺寸'            => 'Pełnowymiarowe',
        '非全尺寸'          => 'Dojazdowe',

        // Cruise
        '定速巡航'          => 'Tempomat',
        '自适应巡航'        => 'Tempomat adaptacyjny',

        // Multimedia
        '多功能控制'        => 'Wielofunkcyjna',
        '换挡'              => 'Łopatki zmiany biegów',
        '原厂原机'          => 'Fabryczny',

        // Camera
        '倒车影像'          => 'Kamera cofania',
        '360度全景影像'     => 'Kamera 360°',
        '后'                => 'Tylne',
        '前'                => 'Przednie',
        '前+后'             => 'Przednie + tylne',

        // Driving assistance
        'L0级'              => 'Brak wspomagania',
        'L1级'              => 'Poziom 1',
        'L2级'              => 'Poziom 2',
        'L2+级'             => 'Poziom 2+',
        'L3级'              => 'Poziom 3',

        // Mirror sun visor
        '主驾驶位+无照明'   => 'Kierowca (bez podświetlenia)',
        '副驾驶位+无照明'   => 'Pasażer (bez podświetlenia)',
        '主驾驶位+有照明'   => 'Kierowca (z podświetleniem)',
        '副驾驶位+有照明'   => 'Pasażer (z podświetleniem)',

        // Seat row info
        '2座'               => '2 miejsca',

        // Vehicle class (jb)
        '紧凑型车'          => 'Kompakt',
        '小型车'            => 'Miejski',
        '中型车'            => 'Średni',
        '中大型车'          => 'Wyższa średnia',
        '大型车'            => 'Luksusowy',
        '微型车'            => 'Mikrosamochód',
        '紧凑型SUV'         => 'Kompaktowy SUV',
        '中型SUV'           => 'Średni SUV',
        '中大型SUV'         => 'Duży SUV',
        '小型SUV'           => 'Mały SUV',
        '紧凑型MPV'         => 'Kompaktowy MPV',
        '中型MPV'           => 'Średni MPV',
        '大型MPV'           => 'Duży MPV',

        // Body struct descriptions (car_body_struct)
        '4门5座三厢车'      => '4-drzwiowy, 5 miejsc, sedan',
        '5门5座SUV'         => '5-drzwiowy, 5 miejsc, SUV',
        '5门5座两厢车'      => '5-drzwiowy, 5 miejsc, hatchback',
        '5门5-7-8座MPV'     => '5-drzwiowy, 5-8 miejsc, MPV',
        '5门7座SUV'         => '5-drzwiowy, 7 miejsc, SUV',
        '4门5座MPV'         => '4-drzwiowy, 5 miejsc, MPV',
        '3门5座两厢车'      => '3-drzwiowy, 5 miejsc, hatchback',
        '2门4座跑车'        => '2-drzwiowy, 4 miejsca, sportowy',
        '2门2座跑车'        => '2-drzwiowy, 2 miejsca, sportowy',
        '5门6座SUV'         => '5-drzwiowy, 6 miejsc, SUV',
        '5门4座SUV'         => '5-drzwiowy, 4 miejsca, SUV',

        // Vehicle class additions
        '大型SUV'           => 'Duży SUV',
        '中大型MPV'         => 'Duży MPV',

        // Warranty additions
        '3年不限公里'       => '3 lata / bez limitu km',

        // Warranty period
        '3年或6万公里'      => '3 lata / 60 tys. km',
        '3年或10万公里'     => '3 lata / 100 tys. km',
        '4年或10万公里'     => '4 lata / 100 tys. km',
        '4年或15万公里'     => '4 lata / 150 tys. km',
        '5年或10万公里'     => '5 lata / 100 tys. km',
        '5年或15万公里'     => '5 lat / 150 tys. km',
        '6年或15万公里'     => '6 lat / 150 tys. km',

        // Emission standards
        '国Ⅳ(国Ⅴ)'        => 'Euro IV (Euro V)',
        '国V'               => 'Euro V',
        '国Ⅵ'              => 'Euro VI',
        '国VI'              => 'Euro VI',
        '欧IV'              => 'Euro IV',
        '欧V'               => 'Euro V',

        // Suspension
        '麦弗逊式独立悬挂'            => 'McPherson (niezależne)',
        '多连杆式独立悬挂'            => 'Wielowahaczowe (niezależne)',
        '双叉臂式独立悬挂'            => 'Dwuwahaczowe (niezależne)',
        '纵臂扭转梁式非独立悬挂'      => 'Belka skrętna (półniezależne)',
        '整体桥式非独立悬挂'          => 'Most sztywny (zależne)',
        '拖拽臂式非独立悬挂'          => 'Ramiona wleczone (półniezależne)',
        '钢板弹簧非独立悬挂'          => 'Resory piórowe (zależne)',
        '双横臂式独立悬挂'            => 'Podwójne wahacze poprzeczne',

        // === Che168 — warianty wartości (inny znak niż dongchedi, dodane 2026-06-18) ===
        // Zawieszenie: che168 używa 悬架 (dongchedi 悬挂).
        '麦弗逊式独立悬架'            => 'McPherson (niezależne)',
        '双叉臂式独立悬架'            => 'Dwuwahaczowe (niezależne)',
        '多连杆式独立悬架'            => 'Wielowahaczowe (niezależne)',
        '五连杆式独立悬架'            => 'Pięciowahaczowe (niezależne)',
        '多连杆非独立悬架'            => 'Wielowahaczowe (zależne)',
        '扭力梁式非独立悬架'          => 'Belka skrętna (półniezależne)',
        '整体桥式非独立悬架'          => 'Most sztywny (zależne)',
        // Nadwozie (car_body_struct)
        '旅行车'                      => 'Kombi',
        '掀背车'                      => 'Hatchback',
        '三厢车'                      => 'Sedan',
        '两厢车'                      => 'Hatchback',
        '硬顶跑车'                    => 'Coupé',
        // Hamulce (che168 czasem bez prefiksu wentylacji)
        '盘式'                        => 'Tarczowe',
        // Typ silnika elektrycznego (electric_type)
        '前交流/异步 后永磁/同步'    => 'Przód: AC async / Tył: PMSM',
        '前感应/异步 后永磁/同步'    => 'Przód: indukcyjny / Tył: PMSM',
        '永磁/同步'                  => 'PMSM (synchroniczny)',
        '交流/异步'                  => 'AC (asynchroniczny)',
        // Typ baterii (kombinacja)
        '三元锂+磷酸铁锂电池'        => 'NMC + LFP',
        // Producent ogniw (battery_brand)
        '宁德时代(江苏时代)'         => 'CATL',
        '宁德时代(四川时代)'         => 'CATL',
        '时代上汽'                    => 'CATL-SAIC',
        '宁德时代/中航锂电/亿纬锂能' => 'CATL / CALB / EVE',
        '中创新航'                    => 'CALB',
        '中航锂电'                    => 'CALB',
        '亿纬锂能'                    => 'EVE',
        '国轩高科'                    => 'Gotion',
        '蜂巢能源'                    => 'SVOLT',
        // Koło zapasowe (spare_tire_specification_1)
        '补胎工具'                    => 'Zestaw naprawczy',
        '全尺寸'                      => 'Pełnowymiarowe',
        '非全尺寸'                    => 'Dojazdowe',
        '无'                          => 'Brak',
        // Producent / sub-marka (sub_brand_name) — czyszczenie do nazwy łacińskiej
        '长城汽车'                    => 'Great Wall',
        '小鹏汽车'                    => 'XPENG',
        '智己汽车'                    => 'IM Motors',
        '岚图汽车'                    => 'Voyah',
        '长安马自达'                  => 'Changan Mazda',
        '东风乘用车'                  => 'Dongfeng',
        '上汽奥迪'                    => 'SAIC Audi',
        'AITO 问界'                   => 'AITO',
        'SAIC 尚界'                   => 'SAIC Shangjie',

        // Gearbox description
        '5挡手动'           => '5-biegowa manualna',
        '6挡手动'           => '6-biegowa manualna',
        '4挡自动'           => '4-biegowa automatyczna',
        '5挡自动'           => '5-biegowa automatyczna',
        '6挡自动'           => '6-biegowa automatyczna',
        '7挡自动'           => '7-biegowa automatyczna',
        '8挡自动'           => '8-biegowa automatyczna',
        '9挡自动'           => '9-biegowa automatyczna',
        '5挡手自一体'       => '5-biegowa tiptronic',
        '6挡手自一体'       => '6-biegowa tiptronic',
        '7挡手自一体'       => '7-biegowa tiptronic',
        '8挡手自一体'       => '8-biegowa tiptronic',
        '9挡手自一体'       => '9-biegowa tiptronic',
        '6挡湿式双离合'     => '6-biegowa mokra DCT',
        '9挡双离合'         => '9-biegowa DCT',
        '无级变速'          => 'CVT (bezstopniowa)',
        '6挡双离合'         => '6-biegowa DCT',
        '7挡双离合'         => '7-biegowa DCT',
        '电动车单速变速箱'  => 'Jednobiegowa (EV)',

        // Engine description (马力 = KM)
        // These contain numbers, so pattern-based translation in code is better.
        // But common ones:

        // Key types
        '普通遥控钥匙'      => 'Pilot zdalnego sterowania',
        '智能遥控钥匙'      => 'Klucz inteligentny',

        // Steering adjustment
        '上下'              => 'Góra/dół',
        '上下+前后'         => 'Góra/dół + przód/tył',

        // Seat heating/ventilation
        '加热'              => 'Ogrzewanie',
        '通风'              => 'Wentylacja',
        '加热/通风'         => 'Ogrzewanie / wentylacja',
        '按摩'              => 'Masaż',

        // Rear seat fold
        '整排放倒'          => 'Składane całkowicie',
        // already defined above, but keep for clarity

        // Dashboard
        '单色'              => 'Monochromatyczny',
        '非全液晶'          => 'Częściowo cyfrowe',

        // ── Missing translations batch (verified 2026-03-25, from 20 real listings) ──

        // Seat adjustments
        '腰部'              => 'Podparcie lędźwiowe',
        '腿托'              => 'Podparcie nóg',
        '头枕'              => 'Zagłówek',

        // Voice zones / seat rows
        '第二排'            => 'Drugi rząd',
        '主驾驶'            => 'Kierowca',
        '副驾驶'            => 'Pasażer',
        '第三排'            => 'Trzeci rząd',

        // Remote control features
        '服务预约'          => 'Rezerwacja serwisu',
        '远程控制'          => 'Zdalne sterowanie',
        '车辆监控'          => 'Monitoring pojazdu',
        '充电管理'          => 'Zarządzanie ładowaniem',
        '数字钥匙'          => 'Kluczyk cyfrowy',
        '智能寻车助手'      => 'Inteligentne wyszukiwanie',

        // Safety warnings
        '车道偏离预警'      => 'Ostrzeżenie o opuszczeniu pasa',
        '前方碰撞预警'      => 'Ostrzeżenie o kolizji (przód)',
        '后方碰撞预警'      => 'Ostrzeżenie o kolizji (tył)',
        '倒车车侧预警'      => 'Ostrzeżenie boczne (cofanie)',
        '前方交通穿行预警'  => 'Ostrzeżenie o ruchu poprzecznym (przód)',
        '前方交通穿行制动'  => 'Hamowanie na ruch poprzeczny (przód)',
        'DOW开门预警'       => 'Ostrzeżenie przy otwieraniu drzwi (DOW)',

        // Mirrors auto features
        '锁车自动折叠'      => 'Automatyczne składanie (zamknięcie)',
        '倒车自动下翻'      => 'Automatyczne pochylanie (cofanie)',

        // Cruise
        '全速自适应巡航'    => 'Tempomat adaptacyjny (pełny zakres)',
        '自适应巡航'        => 'Tempomat adaptacyjny',

        // Drive modes
        '运动'              => 'Sport',
        'ECO/经济'          => 'ECO / Ekonomiczny',
        '标准舒适'          => 'Standard / Komfort',
        '越野'              => 'Offroad',
        '雪地'              => 'Śnieg',
        '个性化'            => 'Indywidualny',

        // Customization
        '可选外观、内饰、轮毂、制动器' => 'Opcje: nadwozie, wnętrze, felgi, hamulce',

        // Multi-layer glass
        '前排+后排'         => 'Przód + tył',

        // EV — battery & charging
        '三元锂电池'        => 'Litowo-jonowy (NMC)',
        '磷酸铁锂电池'      => 'LiFePO4 (LFP)',
        '永磁/同步'         => 'PMSM (synchroniczny)',
        '液态冷却'          => 'Chłodzenie cieczą',
        '低温加热'          => 'Ogrzewanie niskotemperaturowe',
        '快充接口'          => 'Gniazdo szybkiego ładowania',
        '左侧油箱位'        => 'Lewa strona',
        '右侧油箱位'        => 'Prawa strona',
        '宁德时代'          => 'CATL',
        '比亚迪弗迪'        => 'BYD FinDreams',
        '蜂巢能源'          => 'SVOLT',

        // EV — drive
        '电动四驱'          => 'Napęd 4x4 (elektryczny)',
        '双电机四驱'        => 'Dwusilnikowy 4x4',
        '双电机'            => 'Dwa silniki',
        '单电机'            => 'Jeden silnik',
        '前置+后置'         => 'Przód + tył',

        // Gearbox (EV)
        '固定齿比变速箱'    => 'Jednobiegowa (stałe przełożenie)',
        '电子式怀挡'        => 'Kolumna kierownicy (elektroniczna)',
        '电子挡把'          => 'Selektor elektroniczny',
        '旋钮换挡'          => 'Pokrętło',
        '电动车单速变速箱'  => 'Jednobiegowa (EV)',

        // Variable suspension
        '软硬+高低'         => 'Twardość + wysokość',
        '软硬'              => 'Twardość',
        '高低'              => 'Wysokość',
        '单腔'              => 'Jednokomorowe',
        '双腔'              => 'Dwukomorowe',

        // Camera & assist
        '360°全景影像'      => 'Kamera 360°',
        '透明影像'          => 'Widok transparentny',
        '车侧盲区影像'      => 'Kamera martwego pola',

        // Navigation assisted driving
        '城市路段'          => 'Ulice miejskie',
        '高速路段'          => 'Autostrady',

        // Engine layout
        '横置'              => 'Poprzeczny',
        '纵置'              => 'Wzdłużny',

        // Material
        'Nappa真皮'         => 'Skóra Nappa',

        // Sunroof variants
        '分段式不可开启全景天窗'     => 'Panoramiczny (nieotwierany)',
        '分段式可开启全景天窗'       => 'Panoramiczny (otwierany)',

        // Fuel type (PHEV/hybrid)
        '插电式混合动力'    => 'Plug-in hybryda',
        '增程式'            => 'Range extender',

        // Door type
        '侧滑门'            => 'Przesuwne boczne',
        '隐藏式'            => 'Ukryte',

        // Direct injection (missed in first batch)
        '缸内直喷'          => 'Wtrysk bezpośredni (GDI)',

        // Map brands
        '高德'              => 'AutoNavi',
        '百度'              => 'Baidu Maps',

        // Sound brands
        '丹拿'              => 'Dynaudio',
        'BOSE'              => 'Bose',
        '哈曼卡顿'          => 'Harman Kardon',
        'B&O'               => 'Bang & Olufsen',
        '燕飞利仕'          => 'Infinity',
        '丹麦皇冠'          => 'Danish Crown Audio',
        '帝瓦雷'            => 'Devialet',
        '雅马哈'            => 'Yamaha',
        '宝华韦健'          => 'Bowers & Wilkins',
        '丹拿'              => 'Dynaudio',

        // Interior light
        '单色'              => 'Jednokolorowe',
        '128色'             => '128 kolorów',

        // Emission
        '国VI b'            => 'Euro VI b',

        // Warranty pattern (battery)
        '8年或16万公里'     => '8 lat / 160 tys. km',

        // Misc features
        '前排2个'           => 'Przód: 2 szt.',
        '后排2个'           => 'Tył: 2 szt.',
        '后排4个'           => 'Tył: 4 szt.',
        '前排60W'           => 'Przód: 60W',
        '后排60W'           => 'Tył: 60W',
        '40:60'             => '40:60',

        // Keys (remote)
        '手机蓝牙钥匙'      => 'Klucz Bluetooth (telefon)',
        '智能遥控钥匙'      => 'Klucz inteligentny',
        'NFC钥匙'           => 'Klucz NFC',

        // Seat features
        '通风'              => 'Wentylacja',
        '加热/通风'         => 'Ogrzewanie / wentylacja',
        '按摩'              => 'Masaż',

        // EV charging positions
        '慢充接口'          => 'Gniazdo wolnego ładowania',

        // Battery warranty patterns (with extra text)
        '8年或16万公里（首任车主终身质保，责任免除条款以官方为准）' => '8 lat / 160 tys. km (dożywotnia gw. pierwszego właściciela)',

        // ADAS features
        '车道偏离预警'      => 'Ostrzeżenie o opuszczeniu pasa',

        // Voice zones (already have 主驾驶, 副驾驶, 第二排 but adding suffixed)
        '主驾驶位+有照明'   => 'Kierowca (z podświetleniem)',
        '副驾驶位+有照明'   => 'Pasażer (z podświetleniem)',

        // Sliding door
        '双侧电动开闭'      => 'Dwustronnie elektryczne',

        // Seat layout
        '2+2+3'             => '2+2+3 (7 miejsc)',
        '2+3+2'             => '2+3+2 (7 miejsc)',
        '2+3'               => '2+3 (5 miejsc)',

        // Misc EV
        '插电式混合动力'    => 'Plug-in hybryda (PHEV)',
        '48V轻混系统'       => 'Mild hybrid 48V',

        // Keys (remote) additions
        'NFC/RFID钥匙'      => 'Klucz NFC/RFID',
        'UWB数字钥匙'       => 'Kluczyk cyfrowy UWB',

        // Fridge features
        '冷藏'              => 'Chłodzenie',
        '冷冻'              => 'Zamrażanie',

        // Sunroof variants additions
        '可开启全景天窗'    => 'Panoramiczny (otwierany)',
        '不可开启全景天窗'  => 'Panoramiczny (nieotwierany)',

        // Footrest
        '电动'              => 'Elektryczny',
        '固定'              => 'Stały',

        // Interior light
        '256色'             => '256 kolorów',
        '64色'              => '64 kolory',
        '多色'              => 'Wielokolorowe',
        '1680万色'          => '16,8 mln kolorów',

        // Seat memory
        '记忆(主驾驶)'      => 'Pamięć (kierowca)',

        // Third row
        '第三排电动调节'    => 'Elektrycznie regulowany',
        '3座'               => '3 miejsca',

        // Window sunshade
        '后窗侧遮阳帘'     => 'Rolety boczne (tył)',

        // USB count/power
        '前排3个'           => 'Przód: 3 szt.',
        '前排15W'           => 'Przód: 15W',
        '前排66W'           => 'Przód: 66W',
        '前排100W'          => 'Przód: 100W',
        '后排66W'           => 'Tył: 66W',
        '后排27W'           => 'Tył: 27W',
        '后排100W'          => 'Tył: 100W',

        // Stream media
        '流媒体'            => 'Streaming',

        // Gearbox additions
        '电子无级变速箱(E-CVT)' => 'E-CVT (bezstopniowa)',
        'E-CVT无级变速'     => 'E-CVT bezstopniowa',
        '混合动力专用变速箱(DHT)' => 'DHT (hybrydowa)',
        '1挡DHT'            => '1-biegowa DHT',

        // EV motor type additions
        '前交流/异步后永磁/同步' => 'Przód: asynchroniczny / tył: PMSM',
        '前感应/异步后永磁/同步' => 'Przód: indukcyjny / tył: PMSM',

        // Battery tech
        '刀片电池'          => 'Bateria Blade (BYD)',
        '采用阻燃材料和热失控保护技术' => 'Materiały ognioodporne + ochrona termiczna',

        // Battery brands (short forms)
        '弗迪'              => 'FinDreams (BYD)',
        '比亚迪'            => 'BYD',
        '欣旺达/宁德时代'   => 'Sunwoda / CATL',
        '蔚来'              => 'NIO',
        '极氪'              => 'Zeekr',
        '腾势汽车'          => 'Denza',

        // 4WD types
        '全时四驱'          => '4x4 stały',
        '适时四驱'          => '4x4 dołączany',
        '多片离合器式中央差速器' => 'Wielotarczowe sprzęgło centralne',

        // Engine tech
        '米勒循环'          => 'Cykl Millera',
        '阿特金森循环'      => 'Cykl Atkinsona',

        // Remote keys
        '可穿戴钥匙'        => 'Klucz w opasce',

        // USB count additions
        '前排4个'           => 'Przód: 4 szt.',

        // Body struct additions
        '5门6座MPV'         => '5-drzwiowy, 6 miejsc, MPV',
        '5门7座MPV'         => '5-drzwiowy, 7 miejsc, MPV',
        '4门5座轿车'        => '4-drzwiowy, 5 miejsc, sedan',

        // Chip brands (translated from broken unicode keys)
        '高通骁龙8295'      => 'Qualcomm Snapdragon 8295',
        '高通骁龙8155'      => 'Qualcomm Snapdragon 8155',
        '安卓'              => 'Android',
        '地平线征程5'       => 'Horizon Journey 5',
        '华为'              => 'Huawei',
        '图达通'            => 'Innovusion',
        '速腾聚创'          => 'RoboSense',
        '禾赛科技'          => 'Hesai Tech',

        // Battery brands
        '比亚迪弗迪'        => 'BYD FinDreams',

        // Sub-brands (pass-through where useful)
        '理想汽车'          => 'Li Auto',
        '一汽红旗'          => 'Hongqi (FAW)',
        '沃尔沃亚太'        => 'Volvo (Asia Pacific)',
        '赛力斯汽车'        => 'Seres Auto',

        // Map brands
        '高德'              => 'AutoNavi',
        '百度'              => 'Baidu Maps',

        // Light
        '像素式'            => 'Pikselowe',

        // Multimedia
        '存储卡插槽'        => 'Slot na kartę SD',
        'SD卡槽'            => 'Slot SD',

        // Warranty (battery) with first-owner note
        '首任车主终身质保(责任免除条款以官方为准)' => 'Dożywotnia gw. pierwszego właściciela',

        // Charging time patterns
        '快充0.5小时'       => 'Szybkie: 0,5 h',

        // ── Battery brands (batch 2026-04-02) ──
        '中创新航'          => 'CALB',
        '江苏时代'          => 'CATL Jiangsu',
        '时代吉利'          => 'CATL-Geely',
        '威睿'              => 'Welion',
        '欣旺达'            => 'Sunwoda',
        '国轩高科'          => 'Gotion High-tech',
        '亿纬动力'          => 'EVE Power',
        '孚能科技'          => 'Farasis Energy',
        '卫蓝科技'          => 'Welion Technology',
        '江苏正力'          => 'Zenergy',
        '福鼎时代'          => 'CATL Fuding',
        '宁德蕉城时代'      => 'CATL Jiaocheng',
        '耀能新能源'        => 'Yaoneng New Energy',
        '智鹏制造'          => 'Zhipeng Manufacturing',
        '衢州极电'          => 'Jike Energy',

        // ── Electric layout (missing standalone values) ──
        '后置'              => 'Tył',
        '前置'              => 'Przód',
        '中置'              => 'Środek',

        // ── Body struct (missing) ──
        '5门5座旅行车'      => '5-drzwiowy, 5 miejsc, kombi',
        '5门4座旅行车'      => '5-drzwiowy, 4 miejsca, kombi',
        '5门5座掀背车'      => '5-drzwiowy, 5 miejsc, hatchback',
        '4门5座SUV'         => '4-drzwiowy, 5 miejsc, SUV',
        '5门5/7座SUV'       => '5-drzwiowy, 5/7 miejsc, SUV',

        // ── Wake words (asystent głosowy) — uniwersalna etykieta ──
        '你好小迪'          => 'Tak (BYD asystent)',
        '小艺小艺'          => 'Tak (Huawei asystent)',
        '你好吉利'          => 'Tak (Geely asystent)',
        '你好小P'           => 'Tak (XPeng asystent)',
        '你好大众'          => 'Tak (Volkswagen asystent)',
        '理想同学'          => 'Tak (Li Auto asystent)',
        '小爱同学'          => 'Tak (Xiaomi asystent)',
        '你好小祺'          => 'Tak (Chery iCar asystent)',
        '你好银河'          => 'Tak (Geely Galaxy asystent)',
        '嗨NOMI'            => 'Tak (NIO NOMI)',
        '你好腾势'          => 'Tak (Denza asystent)',
        '你好小安'          => 'Tak (Avatr asystent)',
        '你好小零'          => 'Tak (Leapmotor asystent)',
        '你好深蓝'          => 'Tak (Deepal asystent)',
        '你好小奇'          => 'Tak (Chery asystent)',
        '你好小方'          => 'Tak (Wuling asystent)',
        '你好哈弗'          => 'Tak (Haval asystent)',
        '你好小沃'          => 'Tak (Volvo asystent)',
        '你好小尼'          => 'Tak (Nissan asystent)',
        '你好小捷'          => 'Tak (Jetour asystent)',
        '你好斑马'          => 'Tak (MG Zebra asystent)',
        '你好岚图'          => 'Tak (Voyah asystent)',
        '你好星途'          => 'Tak (Exeed asystent)',
        '小塔小塔'          => 'Tak (asystent głosowy)',
        '小安你好'          => 'Tak (Avatr asystent)',
        '嗨小奇'            => 'Tak (Chery asystent)',
        '嗨 红旗'           => 'Tak (Hongqi asystent)',
        '嗨，红旗'          => 'Tak (Hongqi asystent)',
        '嗨岚图'            => 'Tak (Voyah asystent)',
        '坦克坦克'          => 'Tak (Tank asystent)',
        '大狗大狗'          => 'Tak (Haval Big Dog asystent)',
        '小魏同学'          => 'Tak (Wey asystent)',
        '捷途你好'          => 'Tak (Jetour asystent)',
        'hello智己'         => 'Tak (IM Motors asystent)',
        '小狼同学'          => 'Tak (asystent głosowy)',

        // ── Baterie (akumulatory) ──
        '麒麟电池'                 => 'CATL Qilin (NMC)',
        '神盾电池'                 => 'BYD Aegis',
        '华为巨鲸'                 => 'Huawei DriveOne (Megawhale)',
        '华为巨鲸电池'             => 'Huawei DriveOne (Megawhale)',
        '刀片电池/神行电池'        => 'BYD Blade / CATL Shenxing',
        '刀片电池'                 => 'BYD Blade (LFP)',
        '神行电池'                 => 'CATL Shenxing (LFP)',
        '神行超充电池'             => 'CATL Shenxing supercharge',
        '弹匣电池'                 => 'GAC Magazine (LFP)',
        '金钟罩电池'               => 'Chery Kunpeng (LFP)',
        '金砖电池'                 => 'Geely Aegis (LFP)',
        '骁遥超级增·混电池'        => 'CATL Freevoy (PHEV/REEV)',
        'CTP平板电池'              => 'CTP (cell-to-pack)',
        '麒麟电池,CTP3.0'          => 'CATL Qilin CTP 3.0',
        '方刀电池'                 => 'Square Blade (LFP)',
        '磷酸铁锂电池+三元锂电池'  => 'LFP + NMC',
        '磷酸铁锂电池'             => 'LFP',
        '三元锂电池'               => 'NMC',
        '弗迪/宁德时代'            => 'BYD FinDreams / CATL',
        '宁德时代/江苏时代'        => 'CATL / Jiangsu',
        '宁德时代/欣旺达'          => 'CATL / Sunwoda',
        '宁德时代/中创新航'        => 'CATL / CALB',
        '宁德时代、江苏时代、中创新航' => 'CATL / Jiangsu / CALB',
        '宁德时代/卫蓝科技/中创新航' => 'CATL / WeLion / CALB',
        '宁德时代/蜂巢能源'        => 'CATL / SVOLT',
        '国轩高科/得壹能源'        => 'Gotion / Deyi',
        '正力新能'                 => 'Realpower',
        '瑞浦兰钧'                 => 'REPT BattERO',

        // ── Skrzynie biegów (DCT/DHT) ──
        '湿式双离合变速箱（DCT）'  => 'Mokra DCT',
        '干式双离合变速箱（DCT）'  => 'Sucha DCT',
        '7挡湿式双离合'            => '7-biegowa mokra DCT',
        '2挡DHT'                   => '2-biegowa DHT',
        '3挡DHT'                   => '3-biegowa DHT',
        '4挡DHT'                   => '4-biegowa DHT',

        // ── Wnętrze / fotele ──
        '真皮+仿皮'                => 'Skóra + ekoskóra',
        '皮/翻毛皮'                => 'Skóra / Alcantara',
        '皮织混搭'                 => 'Skóra + tkanina',
        'Alcantara（麂皮）'        => 'Alcantara',
        '液晶组合仪表'             => 'Cyfrowy zestaw zegarów',
        '液冷恒温无热蔓延技术'     => 'Chłodzenie cieczą, anty-thermal-runaway',

        // ── Silnik / zawieszenie / hamulce / techn. detale ──
        '三电机'                   => '3 silniki elektryczne',
        '三电机四驱'               => '3 silniki, AWD',
        '分时四驱'                 => 'Dołączany 4WD (part-time)',
        '连杆支柱式独立悬挂'       => 'McPherson (z drążkiem)',
        '陶瓷通风盘式'             => 'Ceramiczne, wentylowane',
        '外露式'                   => 'Odsłonięte',
        '可变截面涡轮增压器'       => 'VGT (zmienna geometria)',
        '米勒循环/可变截面涡轮/VVT' => 'Miller / VGT / VVT',
        '阿特金森循环'             => 'Atkinson cycle',
        '英国之宝'                 => 'Bowers & Wilkins',
        '索尼'                     => 'Sony',

        // ── Warranty (długie zapisy z gwarancją) ──
        '首任车主10年不限里程质保（责任免除条款以官方为准）' => '10 lat / bez limitu km (1. właściciel)',
        '首任车主10年不限里程（责任免除条款以官方为准）'   => '10 lat / bez limitu km (1. właściciel)',
        '首任车主终身质保( 责任免除条款以官方为准)'         => 'Dożywotnia (1. właściciel)',
        '8年或20万公里(首任车主不限年限/里程，责任免除条款以官方为准)' => '8 lat / 200 000 km (1. właściciel: bez limitu)',
        '8年或16万公里(首任车主终身质保责任免除条款以官方为准)'       => '8 lat / 160 000 km (1. właściciel: dożywotnia)',
        '8年或15万公里(首任车主终身质保责任免除条款以官方为准)'       => '8 lat / 150 000 km (1. właściciel: dożywotnia)',
        '8年或20万公里(首任车主终身质保，责任免除条款以官方为准)'     => '8 lat / 200 000 km (1. właściciel: dożywotnia)',
    ],

    // ─── Units (appended to value after translation) ─────────────────────
    'units' => [

        // ── Che168 2026-07-22 ──
        'approach_angle'         => '°',
        'departure_angle'        => '°',
        'trailer_weight'         => 'kg',
        'battery_energy_density' => 'Wh/kg',
        'wltc_combined_range'    => 'km',
        'nedc_recharge_mileage'  => 'km',
        'measured_range'         => 'km',
        'measured_acceleration'  => 's',
        'measured_braking'       => 'm',
        'fast_charge_time'       => 'h',
        'electric_total_horsepower' => 'KM',
        'system_max_power'       => 'kW',
        'length'                      => 'mm',
        'width'                       => 'mm',
        'height'                      => 'mm',
        'wheelbase'                   => 'mm',
        'front_track'                 => 'mm',
        'rear_track'                  => 'mm',
        'min_clearance'               => 'mm',
        'curb_weight'                 => 'kg',
        'full_load_weight'            => 'kg',
        'baggage_volume'              => 'L',
        'capacity_l'                  => 'L',
        'cylinder_volume_ml'          => 'cm³',
        'engine_max_power'            => 'kW',
        'engine_max_horsepower'       => 'KM',
        'max_power_revolution'        => 'obr/min',
        'engine_max_torque'           => 'Nm',
        'engine_max_torque_revolution' => 'obr/min',
        'energy_elect_max_torque'     => 'Nm',
        'oil_tank_volume'             => 'L',
        'fuel_comprehensive'          => 'L/100km',
        'max_speed'                   => 'km/h',
        'acceleration_time'           => 's',
        'center_screen_size'          => '″',
        // EV
        'battery_capacity'            => 'kWh',
        'recharge_mileage'            => 'km',
        'cltc_recharge_mileage'       => 'km',
        'wltc_recharge_mileage'       => 'km',
        'combined_cruising_range_cltc' => 'km',
        'power_consumption'           => 'kWh/100km',
        'total_electric_power'        => 'kW',
        'total_electric_torque'       => 'Nm',
        'front_electric_max_power'    => 'kW',
        'front_electric_max_torque'   => 'Nm',
        'rear_electric_max_power'     => 'kW',
        'rear_electric_max_torque'    => 'Nm',
        'max_fast_charge_power'       => 'kW',
        'wltc_fuel_comprehensive'     => 'L/100km',
        'state_of_charge_fuel_consumption' => 'L/100km',
        'cltc_state_of_charge_fuel_consumption' => 'L/100km',
        'wltc_state_of_charge_fuel_consumption' => 'L/100km',
        'e_energy_equivalent_fuel_consumption' => 'L/100km',
    ],

    // ─── Keys to skip (redundant / internal / already mapped elsewhere) ──
    'skip' => [
        'length_width_height',   // redundant with length+width+height
        'energy_elect_max_power', // redundant with engine_max_power+engine_max_horsepower
        'energy_elect_max_torque', // redundant with engine_max_torque
        'user_custom_pkg',       // too specific, not useful for display
        'sub_brand_name',        // redundant with make taxonomy
        'body_struct',           // redundant with car_body_struct
    ],

];
