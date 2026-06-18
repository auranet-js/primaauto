<?php
/**
 * che168-option-map.php — mapowanie wyposażenia Che168 → klucze extra_prep dongchedi (T-185).
 *
 * Źródło: extra.option.{displayopts, moreoptions[].opts}[].optionname (CJK).
 * Che168 trzyma wyposażenie OSOBNO od spec-paramów (extra.configuration), w odróżnieniu od
 * dongchedi (wyposażenie wplecione w extra_prep). Adapter czyta te features i wstrzykuje do
 * $data['extra_prep'] z wartością '标配' (słownik → "Tak" → checkmark w [asiaauto_equipment]).
 *
 * Klucz: optionname DOKŁADNIE jak zwraca getOffer(che168).
 * Wartość: klucz extra_prep z translations-extra-prep.php (renderowany przez wspólny translateExtraPrep).
 *
 * Zbudowane z PRÓBKI 120 ofert (tmp/che168-option-aggregate-out.tsv, 2026-06-18): 37 distinct.
 * Większość trafia na istniejące klucze; 5 nowych dodanych do słownika (auto_brake_hold,
 * rear_air_outlet, uv_protection_glass, center_diff_lock, phone_mapping).
 */

return array (
  // bezpieczeństwo / dostęp
  'ISOFIX儿童座椅接口'      => 'child_seat_interface',
  '无钥匙启动系统'          => 'keyless_start',
  '无钥匙进入系统'          => 'keyless_entry',
  '胎压监测装置'            => 'tire_pressure_system',
  // ADAS
  '车道偏离预警系统'        => 'lane_warning_system',
  '车道保持辅助系统'        => 'lane_keeping_assist',
  '并线辅助'                => 'line_support',
  '主动刹车/主动安全系统'   => 'active_brake',
  '全景摄像头'              => 'panoramic_camera',
  // multimedia / łączność
  '蓝牙/车载电话'           => 'bluetooth_and_car_phone',
  '车联网'                  => 'car_networking',
  'OTA升级'                 => 'ota_upgrade',
  '全液晶仪表盘'            => 'lcd_dashboard_type',
  'HUD抬头数字显示'         => 'header_display_system',
  '内置行车记录仪'          => 'built_in_tachograph',
  '手机无线充电'            => 'mobile_wireless_charging',
  '手机互联/映射'           => 'phone_mapping',
  '220V/230V电源'           => 'power_outlet',
  // komfort / wnętrze
  '方向盘换挡'              => 'steer_wheel_shift',
  '方向盘加热'              => 'steer_wheel_heating',
  '定速巡航'                => 'cruise',
  '后排独立空调'            => 'rear_independent_air_conditioning',
  '后排出风口'              => 'rear_air_outlet',
  '车内PM2.5过滤装置'       => 'pm25_filtrating_equipment',
  '车载空气净化器'          => 'car_purifier',
  '车内氛围灯'              => 'interior_light',
  '感应后备厢'              => 'inductive_back_door',
  '电动后备厢'              => 'electric_back_door',
  '自动驻车'                => 'auto_brake_hold',
  '防紫外线玻璃'            => 'uv_protection_glass',
  // światła
  '自适应远近光'            => 'adaptive_light',
  '转向辅助灯'              => 'steer_assist_light',
  '转向头灯'                => 'headlamp_follow_up',
  // parkowanie
  '自动泊车入位'            => 'auto_park',
  '倒车影像'                => 'reversing_camera',
  // nadwozie / napęd (pokażą się w „Dane techniczne")
  '主动闭合式进气格栅'      => 'active_closed_inlet_grid',
  '中央差速器锁止'          => 'center_diff_lock',
);
