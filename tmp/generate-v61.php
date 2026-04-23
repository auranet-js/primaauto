<?php
/**
 * Generator CSV v6.1 z aplikacją 16 decyzji quizu z 2026-04-23.
 * Wejście: tmp/mapowanie-marek-modeli.csv (v6 klienta + #264 Exeed VX)
 * Wyjście: tmp/mapowanie-marek-modeli-v6.1.csv + tmp/v6.1-diff.md
 * Defensywny: nie nadpisuje v6, loguje każdą zmianę.
 */

$src = __DIR__ . "/mapowanie-marek-modeli.csv";
$dst = __DIR__ . "/mapowanie-marek-modeli-v6.1.csv";
$diffFile = __DIR__ . "/v6.1-diff.md";

$fp = fopen($src, "r");
$header = fgetcsv($fp);
$rows = [];
while (($r = fgetcsv($fp)) !== false) $rows[] = $r;
fclose($fp);

$changes = []; // log do diffa
$log = function($no, $field, $before, $after, $reason) use (&$changes) {
    $changes[] = ["no" => $no, "field" => $field, "before" => $before, "after" => $after, "reason" => $reason];
};

// Indeks wierszy po # (kolumna 0) dla szybkiego lookup
$byNo = [];
foreach ($rows as $i => $r) $byNo[$r[0]] = $i;

// ========= Q1: BYD Leopard prefix =========
foreach ($rows as $i => &$r) {
    if ($r[3] === "BYD" && stripos($r[5], "Leopard") !== false) {
        if (stripos($r[6], "BYD") === false && $r[6] !== "") {
            $old = $r[6];
            $r[6] = "BYD " . $r[6];
            $log($r[0], "G (title)", $old, $r[6], "Q1: BYD prefix dla Leopard");
        }
    }
}
unset($r);

// ========= Q3: Denza B5/B8 — przecinek → nawiasy =========
foreach ($rows as $i => &$r) {
    if (preg_match('/^(BYD Leopard \d+), (Denza B\d+)$/', $r[6], $m)) {
        $old = $r[6];
        $r[6] = $m[1] . " (" . $m[2] . ")";
        $log($r[0], "G (title)", $old, $r[6], "Q3: przecinek → nawiasy dual-name");
    }
}
unset($r);

// ========= Q4: Sealion uniformity =========
$sealionPattern = '/\b(Sea Lion|SeaLion)\b/';
foreach ($rows as $i => &$r) {
    foreach ([5, 6] as $col) { // TYLKO serie (F) + full (G) — NIE model CN (E), bo E musi pasować do API Dongchedi
        if (preg_match($sealionPattern, $r[$col])) {
            $old = $r[$col];
            $new = preg_replace($sealionPattern, "Sealion", $r[$col]);
            if ($new !== $old) {
                $r[$col] = $new;
                $colName = ["F (serie)", "G (title)"][array_search($col, [5,6])];
                $log($r[0], $colName, $old, $new, "Q4: Sealion uniformity");
            }
        }
    }
}
unset($r);

// ========= Q6: Hyptec HT — marka GAC, title GAC Hyptec HT =========
// Szukamy #171 (Hyper HT / Hyptec HT)
foreach ($rows as $i => &$r) {
    if (stripos($r[2], "GAC Aion Hyper") !== false || stripos($r[3], "Hypec") !== false) {
        // Marka EU (D)
        if ($r[3] !== "GAC") {
            $old = $r[3];
            $r[3] = "GAC";
            $log($r[0], "D (marka EU)", $old, "GAC", "Q6: marka Hyptec → GAC");
        }
        // Serie (F): HT → Hyptec HT
        if ($r[5] === "HT") {
            $old = $r[5];
            $r[5] = "Hyptec HT";
            $log($r[0], "F (serie)", $old, "Hyptec HT", "Q6: serie HT → Hyptec HT");
        }
        // Title (G): GAC Aion Hyptec HT → GAC Hyptec HT
        if (preg_match('/GAC.*Hyptec HT/i', $r[6])) {
            $old = $r[6];
            $r[6] = "GAC Hyptec HT";
            if ($old !== $r[6]) $log($r[0], "G (title)", $old, "GAC Hyptec HT", "Q6: title → GAC Hyptec HT");
        }
        // Slug (I)
        if ($r[8] !== "") {
            $old = $r[8];
            $r[8] = "/samochody/gac/hyptec-ht/";
            if ($old !== $r[8]) $log($r[0], "I (slug)", $old, $r[8], "Q6: slug → /gac/hyptec-ht/");
        }
    }
}
unset($r);

// ========= Q10: Chery iCAR — label filtra =========
foreach ($rows as $i => &$r) {
    if ($r[3] === "Chery" && (stripos($r[4], "iCAR") !== false)) {
        // serie F
        if ($r[5] === "03") {
            $r[5] = "iCAR 03";
            $log($r[0], "F (serie)", "03", "iCAR 03", "Q10: label filtra z iCAR prefix");
        }
        if ($r[5] === "V27") {
            $r[5] = "iCAR V27";
            $log($r[0], "F (serie)", "V27", "iCAR V27", "Q10: label filtra z iCAR prefix");
        }
        // slug I
        if ($r[8] === "/samochody/chery/03/") {
            $r[8] = "/samochody/chery/icar-03/";
            $log($r[0], "I (slug)", "/samochody/chery/03/", $r[8], "Q10: slug z iCAR");
        }
        if ($r[8] === "/samochody/chery/v27/") {
            $r[8] = "/samochody/chery/icar-v27/";
            $log($r[0], "I (slug)", "/samochody/chery/v27/", $r[8], "Q10: slug z iCAR");
        }
    }
}
unset($r);

// ========= Q14: WEY 7 → 07 =========
foreach ($rows as $i => &$r) {
    if ($r[3] === "WEY" && $r[5] === "7") {
        $r[5] = "07";
        $log($r[0], "F (serie)", "7", "07", "Q14: WEY dwucyfrowy");
        if ($r[8] === "/samochody/wey/7/") {
            $r[8] = "/samochody/wey/07/";
            $log($r[0], "I (slug)", "/samochody/wey/7/", "/samochody/wey/07/", "Q14: slug dwucyfrowy");
        }
    }
}
unset($r);

// ========= Q15a: poprawka #188 (Li Auto Li i6 → Li Auto i6), scalenie #57 =========
if (isset($byNo["188"])) {
    $idx188 = $byNo["188"];
    $r188 = &$rows[$idx188];
    if ($r188[5] === "Li i6") {
        $log("188", "F (serie)", "Li i6", "i6", "Q15a: korekta pisowni Li Auto i6");
        $r188[5] = "i6";
    }
    if ($r188[6] === "Li Auto Li i6") {
        $log("188", "G (title)", "Li Auto Li i6", "Li Auto i6", "Q15a: korekta pisowni");
        $r188[6] = "Li Auto i6";
    }
    if ($r188[4] === "Li i6") {
        $log("188", "E (model)", "Li i6", "Li Auto i6", "Q15a: korekta model do EU-form");
        $r188[4] = "Li Auto i6";
    }
    if ($r188[8] !== "/samochody/li-auto/i6/") {
        $log("188", "I (slug)", $r188[8], "/samochody/li-auto/i6/", "Q15a: slug i6");
        $r188[8] = "/samochody/li-auto/i6/";
    }
    unset($r188);
}
// #57 (był pusty) — wypełnij kopią #188 ze starym listings count (9)
if (isset($byNo["57"])) {
    $idx57 = $byNo["57"];
    $r57 = &$rows[$idx57];
    if ($r57[2] === "" && $r57[4] === "") {
        $log("57", "cała pozycja", "(puste)", "Li Auto / Li Auto i6 (dupe #188)", "Q15a: scalenie — wypełnij jako dupe #188, importer scali taksonomicznie");
        $r57[2] = "Li Auto";
        $r57[3] = "Li Auto";
        $r57[4] = "Li Auto i6";
        $r57[5] = "i6";
        $r57[6] = "Li Auto i6";
        $r57[7] = ""; // alias
        $r57[8] = "/samochody/li-auto/i6/";
        $r57[9] = "Li Auto i6 2025";
        $r57[10] = "N";
        $r57[11] = ""; // typ
        $r57[12] = "SCALENIE: pozycja dubluje #188 w v6.1. Taksonomicznie importer użyje jednego termu 'i6'. Listings 9+2=11.";
        $r57[13] = ""; // status
    }
    unset($r57);
}

// ========= Q15b: MINI #190, #191 — flaga skip (zostają puste, importer pominie) =========
foreach (["190", "191"] as $no) {
    if (isset($byNo[$no])) {
        $r = &$rows[$byNo[$no]];
        if ($r[2] === "" && $r[4] === "") {
            $oldUwagi = $r[12];
            $r[12] = "SKIP w imporcie — wykluczone przez klienta (MINI × GWM JV, nie chińska marka). Listings nie trafiają na front.";
            $log($no, "M (uwagi)", $oldUwagi, $r[12], "Q15b: flaga skip MINI");
        }
        unset($r);
    }
}

// ========= Q16: Nissan #83 → dupe #142 =========
if (isset($byNo["83"]) && isset($byNo["142"])) {
    $r83 = &$rows[$byNo["83"]];
    $r142 = $rows[$byNo["142"]];
    if ($r83[4] === "") {
        $log("83", "cała pozycja", "Nissan / (pusty model)", "Nissan / Nissan N6 (dupe #142)", "Q16: scalenie z #142");
        $r83[4] = $r142[4];
        $r83[5] = $r142[5];
        $r83[6] = $r142[6];
        $r83[7] = $r142[7];
        $r83[8] = $r142[8];
        $r83[9] = $r142[9];
        $r83[10] = $r142[10];
        $r83[11] = $r142[11];
        $r83[12] = "SCALENIE: pozycja dubluje #142 w v6.1 (klient skasował translit z CN 日产N6). Taksonomicznie jeden term 'N6'. Listings 6+3=9.";
    }
    unset($r83);
}

// ========= Korekta #264 collision (Exeed VX był dodany jako #264, klient ma #264=XPENG P5) =========
foreach ($rows as $i => &$r) {
    if ($r[0] === "264" && $r[3] === "Exeed" && stripos($r[4], "Lanyue") !== false) {
        $log("264→267", "# (numer)", "264", "267", "Korekta: kolizja z istniejącym #264 XPENG P5");
        $r[0] = "267";
    }
}
unset($r);

// ========= PRZELICZENIE SLUGÓW (I) wg zaktualizowanej serie (F) =========
// Klient w v6 pozapisywał slugi z CN-nazwami; po Q1/Q3/Q4/Q6/Q10/Q14 trzeba synchronizacja.
$slugify = function(string $s): string {
    $s = preg_replace('/\s*\([^)]*\)/u', '', $s);
    $s = preg_replace('/\s*,\s*(PHEV|EV|HEV|BEV)\b/i', '', $s);
    $s = preg_replace('/\s+\b(FCB|SHS)\b/i', '', $s);
    $s = trim($s);
    $s = mb_strtolower($s, 'UTF-8');
    $s = preg_replace('/[^a-z0-9]+/u', '-', $s);
    return trim($s, '-');
};
$markSlug = function(string $m): string {
    $m = mb_strtolower($m, 'UTF-8');
    $m = preg_replace('/[^a-z0-9]+/u', '-', $m);
    return trim($m, '-');
};

$slugifyPreservePlus = function(string $s) use ($slugify): string {
    // P7+ → p7-plus (zachowaj znak + jako -plus)
    $s = str_replace('+', '-plus', $s);
    return $slugify($s);
};

foreach ($rows as $i => &$r) {
    $marka = $r[3] ?? '';
    $serie = $r[5] ?? '';
    if ($marka === '' || $serie === '') continue; // puste wiersze pomijamy
    $modelSlug = (strpos($serie, '+') !== false) ? $slugifyPreservePlus($serie) : $slugify($serie);
    $newSlug = '/samochody/' . $markSlug($marka) . '/' . $modelSlug . '/';
    if ($newSlug !== $r[8] && $r[8] !== '') {
        $log($r[0], "I (slug)", $r[8], $newSlug, "Synchronizacja slug z serie po Q-decyzjach");
        $r[8] = $newSlug;
    } elseif ($r[8] === '' && $marka !== '' && $serie !== '') {
        // pusty slug (np. #57 przed Q15a) — ustaw z serie
        $r[8] = $newSlug;
        $log($r[0], "I (slug)", '(puste)', $newSlug, "Wygenerowany slug");
    }
}
unset($r);

// Korekta dla iCAR (Q10) — po slug-regeneracji będzie /samochody/chery/icar-03/ itd.
// Ale marka w pliku klienta to Chery + serie=iCAR 03, czyli slug naturalnie =/chery/icar-03/
// Sprawdzam czy to wyszło poprawnie

// ========= Zapis =========
$fp = fopen($dst, "w");
fputcsv($fp, $header);
foreach ($rows as $r) fputcsv($fp, $r);
fclose($fp);

// ========= Diff MD =========
$md = "# Diff v6 → v6.1 — aplikacja 16 decyzji quizu (2026-04-23)\n\n";
$md .= "- Wejście: `tmp/mapowanie-marek-modeli.csv` (v6 klienta + dopisany #264 Exeed VX)\n";
$md .= "- Wyjście: `tmp/mapowanie-marek-modeli-v6.1.csv`\n";
$md .= "- Backup: `tmp/mapowanie-marek-modeli.csv.bak-" . date("Y-m-d") . "`\n\n";
$md .= "## Wszystkie zmiany (" . count($changes) . ")\n\n";
$md .= "| # | Pole | Było | Jest | Reguła |\n";
$md .= "|---|---|---|---|---|\n";
foreach ($changes as $c) {
    $b = str_replace("|", "\\|", $c["before"]);
    $a = str_replace("|", "\\|", $c["after"]);
    $md .= "| {$c['no']} | {$c['field']} | `{$b}` | `{$a}` | {$c['reason']} |\n";
}
$md .= "\n## Podsumowanie per reguła\n\n";
$byRule = [];
foreach ($changes as $c) {
    $rule = explode(":", $c["reason"], 2)[0];
    $byRule[$rule] = ($byRule[$rule] ?? 0) + 1;
}
ksort($byRule);
foreach ($byRule as $rule => $n) $md .= "- **$rule**: $n zmian\n";
file_put_contents($diffFile, $md);

echo "CSV v6.1: $dst\n";
echo "Diff:     $diffFile\n";
echo "Zmian:    " . count($changes) . "\n";
