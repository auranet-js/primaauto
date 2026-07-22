<?php
/**
 * AsiaAuto_Mapping — lookup mapy CN→EU wg v6.1.
 *
 * @since 0.31.2
 */

defined('ABSPATH') || exit;

class AsiaAuto_Mapping {

    private static ?array $map = null;
    private static ?array $modelMapChe168 = null;
    private static ?array $reverseIdx = null;
    private static ?array $sig2key = null;

    private static function load(): array {
        if (self::$map !== null) return self::$map;
        $file = ASIAAUTO_PLUGIN_DIR . 'data/brand-mapping-v6.1.php';
        self::$map = is_readable($file) ? (require $file) : [];
        return self::$map;
    }

    /**
     * Zwraca zmapowane [mark_eu, serie_eu, title_eu, slug] dla pary (markCN, modelCN)
     * z v6.1. Zwraca null gdy brak mapowania (użyć CN jako fallback).
     */
    public static function getEuForCn(string $markCN, string $modelCN): ?array {
        $map = self::load();
        $key = $markCN . '|' . $modelCN;
        return $map[$key] ?? null;
    }

    // resolveForSource() USUNIĘTY 2026-06-18 (v0.33.5) — martwy po decyzji „normalizuj na
    // wejściu" (ADR 2026-06-17). Żywe ścieżki: canonicalKeyForSource() (woła resolveChe168
    // bezpośrednio, adapter) + getEuForCn() (importListing/dry-run na danych już skanonizowanych).

    /** Ręczne nadpisania ogona CN (proposal 06-05); klucz "CheMark|CheModel" surowy. */
    private static function che168ModelMap(): array {
        if (self::$modelMapChe168 !== null) return self::$modelMapChe168;
        $file = ASIAAUTO_PLUGIN_DIR . 'data/che168-model-map.php';
        self::$modelMapChe168 = is_readable($file) ? (require $file) : [];
        return self::$modelMapChe168;
    }

    /** Normalizacja porównawcza: trim + collapse spacji + lowercase (CAPS/spacje-insensitive). */
    private static function ci(string $s): string {
        $s = preg_replace('/\s+/u', ' ', trim($s));
        return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
    }

    /** Indeks odwrotny brand-mappingu: (mark_eu|serie_eu) i (mark_eu|slug) → entry. */
    private static function reverseIndex(): array {
        if (self::$reverseIdx !== null) return self::$reverseIdx;
        $rev = ['serie' => [], 'slug' => [], 'serie_only' => []];
        // Indeks serie_eu BEZ marki — dla marek foldowanych do innej marki EU (Yangwang/
        // Fangchengbao → mark_eu 'BYD'), gdzie marka che168 nie trafia w klucz mark_eu.
        // Wpisy niejednoznaczne (ta sama serie_eu pod >1 marką EU, np. H6 Haval/Hongqi)
        // są wykluczane — lepiej sierota niż cudzy hub.
        $serieOwners = [];
        foreach (self::load() as $entry) {
            $me = (string) ($entry['mark_eu'] ?? '');
            $se = (string) ($entry['serie_eu'] ?? '');
            if ($me === '' || $se === '') continue;
            $sc = self::ci($se);
            $serieOwners[$sc][$me] = true;
            if (!isset($rev['serie_only'][$sc])) $rev['serie_only'][$sc] = $entry;
        }
        foreach ($serieOwners as $sc => $owners) {
            if (count($owners) > 1) unset($rev['serie_only'][$sc]);
        }
        foreach (self::load() as $entry) {
            $me = (string) ($entry['mark_eu'] ?? '');
            if ($me === '') continue;
            $se = (string) ($entry['serie_eu'] ?? '');
            $sl = (string) ($entry['slug'] ?? '');
            $mci = self::ci($me);
            if ($se !== '') $rev['serie'][$mci . '|' . self::ci($se)] = $entry;
            if ($sl !== '') $rev['slug'][$mci . '|' . self::ci($sl)] = $entry;
        }
        self::$reverseIdx = $rev;
        return $rev;
    }

    private static function resolveChe168(string $mark, string $model, string $engine): ?array {
        $mark  = trim($mark);
        $model = trim($model);

        // 0) ręczne nadpisania — klucz dokładnie jak z getOffer che168 (z CJK).
        $override = self::che168ModelMap();
        if (isset($override["{$mark}|{$model}"])) {
            return $override["{$mark}|{$model}"];
        }

        // 0a) surowy che168 mark|model == klucz brand-mappingu (T-186, 2026-06-17) — np. osobna marka
        //     che168 "Galaxy / Galaxy L6". Sprawdź PRZED stripem marki (krok 2), który by to rozbił.
        $direct = self::getEuForCn($mark, $model);
        if ($direct !== null) {
            return $direct;
        }

        // 0b) normalizacja marki che168 (T-186, 2026-06-17): strip sufiksu/prefiksu CJK z marki
        //     ("AITO 问界" → "AITO"), alias casing/naming ("IM" → "IM Motors"). Sprawdzane PO
        //     override (override trzyma surowy klucz z CJK), PRZED algorytmem modelu.
        $markStripped = trim(preg_replace('/\s+/u', ' ', trim(preg_replace('/[\x{4e00}-\x{9fff}]+/u', '', $mark))));
        if ($markStripped !== '') $mark = $markStripped;
        // Marki czysto-CJK (cały string znika po stripie 0b → nieosiągalne strippem): alias wprost.
        $markAlias = ['IM' => 'IM Motors', '尚界' => 'SAIC Shangjie'];
        if (isset($markAlias[$mark])) $mark = $markAlias[$mark];

        // 1) strip prefiksu CN marki z modelu (+ uzupełnij markę EN gdy pusta).
        $cnPrefix = [
            '腾势' => 'Denza', '极氪' => 'Zeekr', '红旗' => 'Hongqi', '深蓝' => 'Deepal',
            '理想' => 'Li Auto', '问界' => 'AITO', '仰望' => 'Yangwang',
            '方程豹' => 'Fangchengbao', '阿维塔' => 'Avatr', '岚图' => 'Voyah',
            '智己' => 'IM Motors', '尚界' => 'SAIC Shangjie',
        ];
        foreach ($cnPrefix as $cn => $en) {
            if (mb_strpos($model, $cn) === 0) {
                $model = trim(mb_substr($model, mb_strlen($cn)));
                if ($mark === '') $mark = $en;
                break;
            }
        }
        if ($mark === '' || $model === '') {
            return self::getEuForCn($mark, $model);
        }

        // 2) strip marki EN z początku modelu ("Denza D9" → "D9").
        if (mb_stripos($model, $mark) === 0) {
            $stripped = trim(mb_substr($model, mb_strlen($mark)));
            if ($stripped !== '') $model = $stripped;
        }

        // model nadal z CJK → reverse-index nie trafi (sierota, fallback).
        if (preg_match('/[\x{4e00}-\x{9fff}]/u', $model)) {
            return self::getEuForCn($mark, $model);
        }

        // 3) warianty napędu (serie rozbite per-napęd: Denza D9/Z9/N9 itd.).
        $suffixes = [];
        $e = self::ci($engine);
        if ($e !== '') {
            if (str_contains($e, 'plug-in') || str_contains($e, 'phev') || str_contains($e, 'dm')) {
                $suffixes = ['DM-i', 'DM', 'PHEV'];
            } elseif (str_contains($e, 'electric') || str_contains($e, 'bev')) {
                $suffixes = ['EV', 'BEV'];
            } elseif (str_contains($e, 'range') || str_contains($e, 'extender') || str_contains($e, 'erev')) {
                $suffixes = ['EREV', 'REEV'];
            }
        }

        // 4) kandydaci modelu: surowy + spacja na styku cyfra/litery ("007GT"→"007 GT");
        //    wariant napędu pierwszy (bardziej specyficzny).
        $base = array_values(array_unique(array_filter([
            $model,
            trim(preg_replace('/(\d)([A-Za-z]{2,})/', '$1 $2', $model)),
        ])));
        $cands = [];
        foreach ($base as $b) {
            foreach ($suffixes as $suf) $cands[] = "$b $suf";
        }
        foreach ($base as $b) $cands[] = $b;

        $rev = self::reverseIndex();
        $mci = self::ci($mark);
        foreach ($cands as $cand) {
            $sc = self::ci($cand);
            if (isset($rev['serie']["{$mci}|{$sc}"])) {
                return $rev['serie']["{$mci}|{$sc}"];
            }
            $slugCi = self::ci(sanitize_title($cand));
            if (isset($rev['slug']["{$mci}|{$slugCi}"])) {
                return $rev['slug']["{$mci}|{$slugCi}"];
            }
        }

        // 4b) marki foldowane do innej marki EU (T-186, 2026-07-22): che168 'Yangwang|仰望U7'
        //     → po stripie mark='Yangwang', model='U7', ale brand-mapping trzyma to jako
        //     mark_eu='BYD', serie_eu='Yangwang U7 PHEV'. Szukamy "markaChe + kandydat"
        //     w indeksie serie_eu bez marki (tylko wpisy jednoznaczne).
        foreach ($cands as $cand) {
            $sc = self::ci($mark . ' ' . $cand);
            if (isset($rev['serie_only'][$sc])) {
                return $rev['serie_only'][$sc];
            }
        }

        // 5) fallback — surowy klucz jak dongchedi.
        return self::getEuForCn($mark, $model);
    }

    /** Reverse brand-mappingu: (mark_eu|serie_eu) → pierwszy literalny klucz CN ("markCN|modelCN"). */
    private static function sigToKey(): array {
        if (self::$sig2key !== null) return self::$sig2key;
        $idx = [];
        foreach (self::load() as $key => $entry) {
            $sig = ($entry['mark_eu'] ?? '') . '|' . ($entry['serie_eu'] ?? '');
            if (!isset($idx[$sig])) $idx[$sig] = $key;
        }
        self::$sig2key = $idx;
        return $idx;
    }

    /**
     * Kanonizacja tożsamości źródła do kształtu klucza brand-mappingu ("markCN|modelCN").
     * Po niej zwykłe getEuForCn() trafia tak samo jak dla Dongchedi — pozwala znormalizować dane
     * Che168 PRZY WEJŚCIU (adapter) i zostawić importListing/setTaxonomies bez gałęzi per-source.
     *  - dongchedi: pass-through (regresja nienaruszona).
     *  - che168: resolveChe168() → entry → reverse na literalny klucz CN brand-mappingu.
     *    Brak mapowania lub hub spoza v6.1 → best-effort (znormalizowane mark/model surowe).
     *
     * @return array{0:string,1:string}  [markCN, modelCN]
     * @since 0.33.2 (T-186)
     */
    public static function canonicalKeyForSource(string $mark, string $model, string $engine, string $source): array {
        if ($source === 'dongchedi') {
            return [$mark, $model];
        }
        $entry = self::resolveChe168(trim($mark), trim($model), $engine);
        if ($entry !== null) {
            $sig = ($entry['mark_eu'] ?? '') . '|' . ($entry['serie_eu'] ?? '');
            $key = self::sigToKey()[$sig] ?? null;
            if ($key !== null && strpos($key, '|') !== false) {
                return explode('|', $key, 2);
            }
        }
        return [trim($mark), trim($model)];
    }
}
