<?php
require_once __DIR__ . '/../api.php';
$pdo = getSupabasePDO();

$tables = ['organization', 'advisory_board', 'honor_council', 'organization_structure', 'governance_periods'];
foreach ($tables as $tbl) {
    echo "=== $tbl ===\n";
    $cols = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='$tbl' ORDER BY ordinal_position")->fetchAll();
    foreach ($cols as $c) echo "  {$c['column_name']} ({$c['data_type']})\n";
    
    $count = $pdo->query("SELECT COUNT(*) FROM $tbl")->fetchColumn();
    echo "  Rows: $count\n";
    
    if ($count > 0) {
        $sample = $pdo->query("SELECT * FROM $tbl LIMIT 2")->fetchAll();
        foreach ($sample as $row) echo "  SAMPLE: " . json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }
    echo "\n";
}
