<?php
/**
 * AsiaAuto_Che168_Adapter — normalizacja surowych danych Che168 do kształtu importera.
 *
 * Che168 (C2C aut używanych) ma inny kształt niż dongchedi:
 *  - lokalizacja w `address` (np. "西安, "), brak `city` → city = explode(', ', address)[0]
 *  - pierwsza rejestracja w `first_registration` ("2024-09") → mapowane na `reg_date`
 *  - `vin` + `first_registration` 100% coverage (dongchedi nie ma)
 *  - parametry techniczne w `extra.configuration.paramtypeitems[].paramitems[]` (po stabilnym
 *    numerycznym `id`) → spłaszczane do mapy {dongchedi_key => raw} przez che168-param-map.php
 *    (klucze nieznane → 'param_{id}'); dalej tłumaczone wspólnym translateExtraPrep()
 *  - obrazy permanentne (host 2sc2.autoimg.cn, bez x-expires → brak ghost-offers)
 *
 * Po normalizacji $data jest konsumowane przez ten sam AsiaAuto_Importer::buildPlan()/importListing()
 * co dongchedi — żadnych osobnych ścieżek zapisu.
 *
 * @since 0.33.0 (T-185)
 */

defined('ABSPATH') || exit;

class AsiaAuto_Che168_Adapter {

    private static ?array $paramMap = null;
    private static ?array $enumMap  = null;

    /**
     * Normalizuje surowy rekord getOffer('che168', …) do kształtu zgodnego z importerem.
     * Czyste przekształcenie — bez efektów ubocznych.
     */
    public static function normalize(array $raw): array {
        $data = $raw;

        // city z address ("西安, " → "西安"); zostaw pełny address dla _asiaauto_address.
        if (empty($data['city']) && !empty($raw['address'])) {
            $parts = array_map('trim', explode(',', (string) $raw['address']));
            $city = $parts[0] ?? '';
            if ($city !== '') {
                $data['city'] = $city;
            }
        }

        // reg_date z first_registration ("2024-09"); importer: strtotime → d/m/Y.
        if (empty($data['reg_date']) && !empty($raw['first_registration'])) {
            $data['reg_date'] = (string) $raw['first_registration'];
        }

        // extra_prep z extra.configuration (spłaszczone po stabilnym id).
        $ep = self::extractExtraPrep($raw);
        if (!empty($ep)) {
            $data['extra_prep'] = $ep;
        }

        // Wyposażenie z extra.option (Che168 trzyma je OSOBNO od spec-paramów; dongchedi wplata
        // w extra_prep). CJK optionname → klucz extra_prep (che168-option-map.php), wartość '标配'
        // → słownik renderuje "Tak" → checkmark w [asiaauto_equipment]. Spec z extra.configuration
        // wygrywa (nie nadpisujemy istniejących kluczy).
        $opts = self::extractOptions($raw);
        if (!empty($opts)) {
            if (!isset($data['extra_prep']) || !is_array($data['extra_prep'])) {
                $data['extra_prep'] = [];
            }
            foreach ($opts as $key => $val) {
                if (!isset($data['extra_prep'][$key])) {
                    $data['extra_prep'][$key] = $val;
                }
            }
        }

        // param_93 (车型名称) = pełna nazwa "{model} {YYYY款} {trim}", np. "尚界Z7T 2026款 Max".
        // Źródło i dla wersji (część po "款"), i dla fallbacku rocznika ("YYYY款").
        $name93 = '';
        foreach ($raw['extra']['configuration']['paramtypeitems'] ?? [] as $g) {
            foreach ($g['paramitems'] ?? [] as $it) {
                if ((int) ($it['id'] ?? 0) === 93) { $name93 = (string) ($it['value'] ?? ''); break 2; }
            }
        }

        // Wersja/trim PRZY WEJŚCIU: che168 NIE podaje `complectation` na wierzchu (dongchedi tak).
        // Wyciągamy część po "款" → $data['complectation'], żeby wspólny computeIdentity zbudował
        // tytuł z wersją tak samo jak dla dongchedi (translateComplectation tłumaczy + czyści CJK).
        if (empty($data['complectation']) && $name93 !== '' && mb_strpos($name93, '款') !== false) {
            $parts = explode('款', $name93);
            $trim  = trim((string) end($parts));
            if ($trim !== '') {
                $data['complectation'] = $trim;
            }
        }

        // Rok modelowy: che168 zwykle podaje `year`, ale bywa 0/puste (auto nierejestrowane —
        // first_registration="未上牌"). Fallback: first_registration (YYYY) → param_93 ("YYYY款").
        if (empty($data['year']) || (int) $data['year'] === 0) {
            $fy = '';
            if (!empty($raw['first_registration']) && preg_match('/^(\d{4})/', (string) $raw['first_registration'], $m)) {
                $fy = $m[1];
            }
            if ($fy === '' && $name93 !== '' && preg_match('/(\d{4})款/u', $name93, $m)) {
                $fy = $m[1];
            }
            if ($fy !== '') {
                $data['year'] = $fy;
            }
        }

        // Normalizacja enumów atrybutów PRZY WEJŚCIU (T-186, kalibracja enumów): body/fuel/drive/color
        // Che168 → klucz słownika Dongchedi. Reużywa translations-*.php 1:1 i zapobiega duplikatom
        // termów (slug liczony z klucza docelowego == istniejący term). Przed kanonizacją mark/model,
        // by canonicalKeyForSource() dostało już kanoniczny engine_type (oś wariantu napędu).
        self::normalizeEnums($data);

        // Kanonizacja tożsamości PRZY WEJŚCIU (T-186, 2026-06-17): mark/model Che168 → kształt klucza
        // brand-mappingu (Dongchedi). Dzięki temu importListing()/setTaxonomies() trafiają zwykłym
        // getEuForCn() bez gałęzi per-source — strefa krucha (importer) NIETKNIĘTA. Surowiec
        // zachowany w *_che168_raw do śladowości i diagnostyki.
        $rawMark  = (string) ($data['mark'] ?? '');
        $rawModel = (string) ($data['model'] ?? '');
        if ($rawMark !== '' || $rawModel !== '') {
            [$cnMark, $cnModel] = AsiaAuto_Mapping::canonicalKeyForSource(
                $rawMark,
                $rawModel,
                (string) ($data['engine_type'] ?? ''),
                'che168'
            );
            if ($cnMark !== $rawMark || $cnModel !== $rawModel) {
                $data['mark_che168_raw']  = $rawMark;
                $data['model_che168_raw'] = $rawModel;
                $data['mark']             = $cnMark;
                $data['model']            = $cnModel;
            }
        }

        return $data;
    }

    /**
     * Spłaszcza extra.configuration.paramtypeitems[].paramitems[] → {key => raw_value}.
     * key = klucz dongchedi z che168-param-map.php (po id), inaczej 'param_{id}'.
     * Pierwsza niepusta wartość per klucz wygrywa (Che168 powtarza pola w grupach).
     */
    private static function extractExtraPrep(array $raw): array {
        $pti = $raw['extra']['configuration']['paramtypeitems'] ?? null;
        if (!is_array($pti)) {
            return [];
        }
        $map = self::paramMap();
        $out = [];
        foreach ($pti as $group) {
            $items = $group['paramitems'] ?? [];
            if (!is_array($items)) {
                continue;
            }
            foreach ($items as $it) {
                if (!isset($it['id'])) {
                    continue;
                }
                $id  = (int) $it['id'];
                $val = trim((string) ($it['value'] ?? ''));
                if ($val === '' || $val === '-') {
                    continue;
                }
                $key = $map[$id] ?? ('param_' . $id);
                if (!isset($out[$key])) {
                    $out[$key] = $val;
                }
            }
        }
        return $out;
    }

    private static function paramMap(): array {
        if (self::$paramMap !== null) {
            return self::$paramMap;
        }
        $file = ASIAAUTO_PLUGIN_DIR . 'data/che168-param-map.php';
        self::$paramMap = is_readable($file) ? (require $file) : [];
        return self::$paramMap;
    }

    /**
     * Wyciąga wyposażenie z extra.option.{displayopts, moreoptions[].opts}[].optionname (CJK)
     * → mapa {klucz extra_prep => '标配'} wg che168-option-map.php. Nieznane optionname pomijane.
     */
    private static function extractOptions(array $raw): array {
        $opt = $raw['extra']['option'] ?? null;
        if (!is_array($opt)) {
            return [];
        }
        $names = [];
        foreach ($opt['displayopts'] ?? [] as $it) {
            if (!empty($it['optionname'])) { $names[(string) $it['optionname']] = true; }
        }
        foreach ($opt['moreoptions'] ?? [] as $group) {
            foreach ($group['opts'] ?? [] as $it) {
                if (!empty($it['optionname'])) { $names[(string) $it['optionname']] = true; }
            }
        }
        if (empty($names)) {
            return [];
        }
        $map = self::optionMap();
        $out = [];
        foreach (array_keys($names) as $name) {
            if (isset($map[$name])) {
                $out[$map[$name]] = '标配';
            }
        }
        return $out;
    }

    private static ?array $optionMap = null;

    private static function optionMap(): array {
        if (self::$optionMap !== null) {
            return self::$optionMap;
        }
        $file = ASIAAUTO_PLUGIN_DIR . 'data/che168-option-map.php';
        self::$optionMap = is_readable($file) ? (require $file) : [];
        return self::$optionMap;
    }

    /**
     * Przepisuje surowe wartości enum Che168 (body/fuel/drive/color) na klucze słownika
     * Dongchedi wg data/che168-enum-map.php. Domena zamknięta → płaska mapa danych (bez
     * resolvera). Tylko realna zmiana → surowiec zachowany w {field}_che168_raw (śladowość).
     * Wartości spoza mapy zostają nietknięte (już zgodne z Dongchedi albo świadomie sierota
     * do decyzji w dry-run/logu).
     */
    private static function normalizeEnums(array &$data): void {
        foreach (self::enumMap() as $field => $pairs) {
            if (empty($data[$field]) || !is_string($data[$field])) {
                continue;
            }
            $rawVal = trim($data[$field]);
            $lower  = mb_strtolower($rawVal, 'UTF-8');
            foreach ($pairs as $cheKey => $canon) {
                if (mb_strtolower((string) $cheKey, 'UTF-8') === $lower) {
                    if ((string) $canon !== $rawVal) {
                        $data[$field . '_che168_raw'] = $rawVal;
                        $data[$field] = (string) $canon;
                    }
                    break;
                }
            }
        }
    }

    private static function enumMap(): array {
        if (self::$enumMap !== null) {
            return self::$enumMap;
        }
        $file = ASIAAUTO_PLUGIN_DIR . 'data/che168-enum-map.php';
        self::$enumMap = is_readable($file) ? (require $file) : [];
        return self::$enumMap;
    }
}
