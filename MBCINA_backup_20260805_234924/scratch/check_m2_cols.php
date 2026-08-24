<?php
require_once __DIR__ . '/../api.php';
$pdo = getSupabasePDO();

$tables = ['founders', 'vision_mission'];
foreach ($tables as $tbl) {
    echo "=== KOLOM TABEL: $tbl ===\n";
    try {
        $cols = $pdo->query("SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name='$tbl' ORDER BY ordinal_position")->fetchAll();
        foreach ($cols as $c) echo "  [{$c['column_name']}] {$c['data_type']} (nullable:{$c['is_nullable']})\n";
        
        $sample = $pdo->query("SELECT * FROM $tbl LIMIT 3")->fetchAll();
        echo "  --- Sample data ---\n";
        foreach ($sample as $row) {
            echo "  " . json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
        }
    } catch (Exception $e) { echo "  ERR: " . $e->getMessage() . "\n"; }
    echo "\n";
}

// Juga cek get_m2_data di api.php
$api = file_get_contents(__DIR__ . '/../api.php');
$lines = explode("\n", $api);
foreach ($lines as $i => $line) {
    if (strpos($line, "case 'get_m2_data'") !== false) {
        echo "api.php - get_m2_data di Line " . ($i+1) . "\n";
    }
}
