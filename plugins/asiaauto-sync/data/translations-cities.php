<?php
/**
 * Chinese city name translations → Polish.
 * Keys: Chinese characters from API field 'city'.
 * API returns city names in Chinese (e.g. "重庆", "长沙", "文山").
 * Verified against real API data 2026-03-19.
 *
 * Build incrementally — add new cities as they appear in API data.
 * Use 'wp asiaauto missing-cities' to find untranslated cities.
 */
return [
    // === Tier 1 — municipalities ===
    '北京'   => 'Pekin',
    '上海'   => 'Szanghaj',
    '广州'   => 'Kanton',
    '深圳'   => 'Shenzhen',
    '重庆'   => 'Chongqing',
    '天津'   => 'Tianjin',

    // === Tier 2 — provincial capitals & major cities ===
    '成都'   => 'Chengdu',
    '杭州'   => 'Hangzhou',
    '武汉'   => 'Wuhan',
    '南京'   => 'Nankin',
    '西安'   => "Xi'an",
    '苏州'   => 'Suzhou',
    '长沙'   => 'Changsha',
    '沈阳'   => 'Shenyang',
    '青岛'   => 'Qingdao',
    '大连'   => 'Dalian',
    '郑州'   => 'Zhengzhou',
    '昆明'   => 'Kunming',
    '哈尔滨' => 'Harbin',
    '济南'   => 'Jinan',
    '福州'   => 'Fuzhou',
    '长春'   => 'Changchun',
    '厦门'   => 'Xiamen',
    '合肥'   => 'Hefei',
    '东莞'   => 'Dongguan',
    '佛山'   => 'Foshan',
    '宁波'   => 'Ningbo',
    '无锡'   => 'Wuxi',
    '南宁'   => 'Nanning',
    '贵阳'   => 'Guiyang',
    '太原'   => 'Taiyuan',
    '南昌'   => 'Nanchang',
    '石家庄' => 'Shijiazhuang',
    '兰州'   => 'Lanzhou',
    '呼和浩特' => 'Hohhot',
    '乌鲁木齐' => 'Urumqi',
    '拉萨'   => 'Lhasa',
    '海口'   => 'Haikou',
    '银川'   => 'Yinchuan',
    '西宁'   => 'Xining',

    // === Tier 3 — cities seen in API data ===
    '文山'   => 'Wenshan',
    '漳州'   => 'Zhangzhou',
    '淮南'   => 'Huainan',
    '临沂'   => 'Linyi',
    '潍坊'   => 'Weifang',
    '烟台'   => 'Yantai',
    '温州'   => 'Wenzhou',
    '泉州'   => 'Quanzhou',
    '珠海'   => 'Zhuhai',
    '中山'   => 'Zhongshan',
    '惠州'   => 'Huizhou',
    '保定'   => 'Baoding',
    '唐山'   => 'Tangshan',
    '常州'   => 'Changzhou',
    '徐州'   => 'Xuzhou',
    '南通'   => 'Nantong',
    '嘉兴'   => 'Jiaxing',
    '绍兴'   => 'Shaoxing',
    '金华'   => 'Jinhua',
    '台州'   => 'Taizhou',
    '洛阳'   => 'Luoyang',
    '襄阳'   => 'Xiangyang',
    '宜昌'   => 'Yichang',
    '岳阳'   => 'Yueyang',
    '株洲'   => 'Zhuzhou',
    '遵义'   => 'Zunyi',
    '柳州'   => 'Liuzhou',
    '桂林'   => 'Guilin',
    '三亚'   => 'Sanya',
    '绵阳'   => 'Mianyang',
    '德阳'   => 'Deyang',
    '宜宾'   => 'Yibin',
    '包头'   => 'Baotou',
    '鞍山'   => 'Anshan',
    '吉林'   => 'Jilin',
    '大庆'   => 'Daqing',
    '秦皇岛' => 'Qinhuangdao',
    '廊坊'   => 'Langfang',
    '邯郸'   => 'Handan',
    '芜湖'   => 'Wuhu',
    '马鞍山' => "Ma'anshan",
    '赣州'   => 'Ganzhou',
    '九江'   => 'Jiujiang',

    '许昌'   => 'Xuchang',

    // === Miasta z listy importu (31) brakujące do 2026-07-22 ===
    // Powód: 15 z 31 miast filtra importu nie miało tłumaczenia → listingi
    // dostawały CJK w stm_car_location (89 ofert publish na produkcji).
    '江门'   => 'Jiangmen',
    '揭阳'   => 'Jieyang',
    '茂名'   => 'Maoming',
    '汕头'   => 'Shantou',
    '潮州'   => 'Chaozhou',
    '梅州'   => 'Meizhou',
    '肇庆'   => 'Zhaoqing',
    '韶关'   => 'Shaoguan',
    '南平'   => 'Nanping',
    '宁德'   => 'Ningde',
    '玉林'   => 'Yulin',
    '防城港' => 'Fangchenggang',
    '钦州'   => 'Qinzhou',
    '贵港'   => 'Guigang',
    '北海'   => 'Beihai',

    // === Miasta spotkane w danych Che168 (2026-07-22) ===
    '济宁'   => 'Jining',
    '淄博'   => 'Zibo',
    '吴忠'   => 'Wuzhong',
    '东营'   => 'Dongying',

    // === English fallbacks (some offers may use pinyin/english) ===
    'Beijing'   => 'Pekin',
    'Shanghai'  => 'Szanghaj',
    'Guangzhou' => 'Kanton',
    'Shenzhen'  => 'Shenzhen',
    'Chongqing' => 'Chongqing',
];
