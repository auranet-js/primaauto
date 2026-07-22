<?php
/**
 * che168-param-map.php — mapa stabilnego numerycznego paramId Che168 → klucz extra_prep
 * (vocabulary dongchedi z translations-extra-prep.php).
 *
 * Po co: Che168 zwraca extra.configuration.paramtypeitems[].paramitems[] = {id, name(CN), value(CN)}.
 * Adapter spłaszcza to do mapy {dongchedi_key => raw_value}, a translateExtraPrep() tłumaczy
 * etykiety/wartości i grupuje po kategoriach — TĄ SAMĄ infrastrukturą co dongchedi.
 * Dzięki temu karta spec Che168 trafia w te same pola/kategorie co dongchedi.
 *
 * id BEZ wpisu → adapter użyje klucza 'param_{id}' (nieskategoryzowany → ukryty w renderze,
 * ale ZAPISANY w raw extra_prep i logu wdrożeniowym → kandydat do dorobienia w fazie obserwacji).
 *
 * Seed 2026-06-16 (T-185): ~50 id wysokiej pewności (wymiary/masa, silnik, skrzynia, hamulce,
 * opony, EV/bateria, klasa, cena, gwarancja). Wartości numeryczne renderują się od razu (z jednostką);
 * wartości CN tylko gdy translations-extra-prep-values ma wpis (inaczej pomijane — cel kalibracji).
 *
 * Klucze docelowe zweryfikowane: 51/51 obecne w kategoriach translations-extra-prep.php (2026-06-16).
 */

return [
    // ── 车身 / Wymiary i masa ──
    28  => 'length',            // 长度(mm)
    29  => 'width',             // 宽度(mm)
    30  => 'height',            // 高度(mm)
    31  => 'wheelbase',         // 轴距(mm)
    32  => 'front_track',       // 前轮距(mm)
    33  => 'rear_track',        // 后轮距(mm)
    34  => 'min_clearance',     // 满载最小离地间隙(mm)
    26  => 'seat_count',        // 座位数
    36  => 'door_nums',         // 车门数
    39  => 'baggage_volume',    // 后备厢容积(L)
    106 => 'curb_weight',       // 整备质量(kg)
    152 => 'full_load_weight',  // 最大满载质量(kg)
    35  => 'car_body_struct',   // 车身结构

    // ── 基本参数 / Dane podstawowe ──
    91  => 'jb',                       // 级别
    86  => 'official_price',           // 厂商指导价(元)
    87  => 'market_time',              // 上市时间
    103 => 'period',                   // 整车质保
    94  => 'sub_brand_name',           // 厂商
    96  => 'max_speed',                // 最高车速(km/h)
    97  => 'acceleration_time',        // 官方0-100km/h加速(s)

    // ── 发动机 / Silnik ──
    58  => 'engine_model',             // 发动机型号
    3   => 'cylinder_volume_ml',       // 排量(mL)
    4   => 'capacity_l',               // 排量(L)
    5   => 'cylinder_arrangement',     // 气缸排列形式
    6   => 'cylinder_nums',            // 气缸数
    10  => 'valves_per_cylinder_nums', // 每缸气门数
    13  => 'air_supply',               // 进气形式
    22  => 'oil_supply',               // 供油方式
    23  => 'cylinder_material',        // 缸体材料
    101 => 'engine_description',       // 发动机 (skrót)
    20  => 'fuel_form',                // 燃料形式
    21  => 'fuel_label',               // 燃油标号
    25  => 'environmental_standards',  // 环保标准
    38  => 'oil_tank_volume',          // 油箱容积(L)
    117 => 'fuel_comprehensive',       // WLTC综合油耗(L/100km)

    // ── 变速箱 / Skrzynia i napęd ──
    40  => 'gearbox_type',             // 变速箱类型
    41  => 'stalls',                   // 挡位个数
    100 => 'gearbox_description',      // 简称
    42  => 'driver_form',              // 驱动方式

    // ── 底盘转向 / 车轮制动 ──
    43  => 'front_suspension_form',    // 前悬架类型
    44  => 'rear_suspension_form',     // 后悬架类型
    46  => 'power_steering_type',      // 助力类型
    47  => 'front_brake_type',         // 前制动器类型
    48  => 'rear_brake_type',          // 后制动器类型
    49  => 'front_tire_size',          // 前轮胎规格
    50  => 'rear_tire_size',           // 后轮胎规格
    56  => 'park_brake_type',          // 驻车制动类型
    55  => 'car_body_structure',       // 车体结构

    // ── 电动机 / Napęd elektryczny / hybryda ──
    64  => 'battery_capacity',         // 电池能量(kWh)
    77  => 'electric_system_power',    // 系统综合功率(kW)
    78  => 'electric_system_torque',   // 系统综合扭矩(N·m)

    // ── Rozszerzenie 2026-06-18 (próbka 21 ofert dopasowanych do filtrów importu).
    //    NUMERYCZNE — renderują się od razu (zasięg/zużycie/moc/moment/obroty/ładowanie). ──
    154 => 'cltc_recharge_mileage',              // CLTC纯电续航里程(km) — zasięg EV
    124 => 'wltc_recharge_mileage',              // WLTC纯电续航里程(km)
    80  => 'power_consumption',                  // 百公里耗电量(kWh/100km) — zużycie energii
    151 => 'e_energy_equivalent_fuel_consumption',// 电能当量燃料消耗量(L/100km)
    118 => 'state_of_charge_fuel_consumption',   // 最低荷电状态油耗(L/100km)
    59  => 'total_electric_power',               // 电动机总功率(kW)
    60  => 'total_electric_torque',              // 电动机总扭矩(N·m)
    68  => 'rear_electric_max_power',            // 后电动机最大功率(kW)
    69  => 'rear_electric_max_torque',           // 后电动机最大扭矩(N·m)
    65  => 'front_electric_max_power',           // 前电动机最大功率(kW)
    67  => 'front_electric_max_torque',          // 前电动机最大扭矩(N·m)
    15  => 'engine_max_power',                   // 最大功率(kW) — silnik spalinowy
    14  => 'engine_max_horsepower',              // 最大马力(Ps)
    116 => 'engine_max_torque',                  // 最大扭矩(N·m)
    16  => 'max_power_revolution',               // 最大功率转速(rpm)
    18  => 'engine_max_torque_revolution',       // 最大扭矩转速(rpm)
    121 => 'max_engine_net_power',               // 最大净功率(kW)
    146 => 'max_fast_charge_power',              // 快充功率(kW)
    114 => 'fast_charge_electricity',            // 快充电量百分比(%)
    111 => 'battery_charge_time',                // 慢充时间(小时)
    139 => 'min_turning_radius',                 // 最小转弯半径(m)

    // ── Rozszerzenie 2026-06-18 — KATEGORYCZNE (wymagają tłumaczenia wartości CJK
    //    w translations-extra-prep-values.php, dodane równolegle). ──
    90  => 'fuel_form',                          // 能源类型 (汽油/纯电动/插电式…)
    79  => 'battery_type',                       // 电池类型 (三元锂/磷酸铁锂…)
    142 => 'battery_brand',                      // 电芯品牌 (宁德时代/中创新航…)
    73  => 'battery_warranty',                   // 电池组质保
    76  => 'electric_type',                      // 电机类型 (永磁同步/交流异步)
    82  => 'electric_drive_number',              // 驱动电机数 (单/双/三/四电机)
    83  => 'electric_layout',                    // 电机布局 (前置/后置/前置+后置)
    104 => 'environmental_standards',            // 环保标准 (国VI…)
    123 => 'engine_layout_form',                 // 发动机布局 (纵置/横置)
    62  => 'fourwheel_drive_type',               // 四驱形式 (适时/全时/分时四驱)
    53  => 'spare_tire_specification_1',         // 备胎规格 (全尺寸/非全尺寸/无)

    // ── Rozszerzenie 2026-07-22 (T-186 sync) — parametry, które che168 przysyła,
    //    a które dotąd lądowały jako 'param_{id}' (niewidoczne na stronie).
    //    Zebrane z 45 ofert zaimportowanych automatem; 795 wystąpień. ──
    125 => 'cltc_recharge_mileage',              // CLTC纯电续航里程(km) — zasięg EV, kluczowy dla klienta
    122 => 'wltc_recharge_mileage',              // WLTC纯电续航里程(km)
    17  => 'engine_max_torque',                  // 最大扭矩(N·m)
    88  => 'system_max_power',                   // 最大功率(kW) z grupy 基本参数 = moc CALEGO ukladu;
                                                 // dla EREV/PHEV rozni sie od engine_max_power (id 15, silnik spalinowy)
    84  => 'fast_charge_time',                   // 快充时间(小时) — SZYBKIE; wolne (慢充) ma juz id 111
    105 => 'fast_charge_time',                   // 快充时间(小时) — grupa 基本参数 (ta sama wartosc)
    85  => 'battery_charge_time',                // 慢充时间(小时) — wolne, jak id 111
    144 => 'battery_temperature_management_system_cooling', // 电池冷却方式 (直冷/液冷)
    129 => 'door_open_way',                      // 车门开启方式 (平开门/剪刀门…)
    128 => 'drag_coefficient',                   // 风阻系数(Cd)
    115 => 'body_struct',                        // 车身结构 (5门5座SUV)
    108 => 'gearbox_description',                // 变速箱 (E-CVT无级变速…)
    11  => 'air_supply',                         // 配气机构 (DOHC)
    24  => 'cylinder_head_material',             // 缸盖材料 (铝合金)
    145 => 'quick_charge_interface',             // 快充功能 (支持)

    // ── Nowe klucze (nie występują u dongchedi) — etykiety i kategorie dodane
    //    równolegle w translations-extra-prep.php. ──
    133 => 'approach_angle',                     // 接近角(°)
    134 => 'departure_angle',                    // 离去角(°)
    156 => 'trailer_weight',                     // 准拖挂车总质量(kg)
    143 => 'battery_energy_density',             // 电池能量密度(Wh/kg)
    81  => 'fast_charge_electricity',            // 快充电量(%) — np. "30-80" (klucz dongchedi istnieje)
    148 => 'battery_swap',                       // 换电 (支持) — NIO
    150 => 'wltc_combined_range',                // WLTC综合续航(km)
    153 => 'nedc_recharge_mileage',              // NEDC纯电续航里程(km)
    61  => 'nedc_recharge_mileage',              // NEDC纯电续航里程(km) — grupa 电动机
    109 => 'measured_acceleration',              // 实测0-100km/h加速(s)
    89  => 'measured_braking',                   // 实测100-0km/h制动(m)
    107 => 'measured_range',                     // 实测续航里程(km)
    110 => 'first_owner_warranty',               // 首任车主质保政策
];
