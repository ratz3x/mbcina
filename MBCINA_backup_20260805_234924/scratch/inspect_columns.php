<?php
require_once __DIR__ . '/../api.php';
$sPdo = getSupabasePDO();

$tables = ['advisory_board', 'honor_council', 'organization_structure', 'governance_periods'];
foreach ($tables as $t) {
    echo "=== COLUMNS FOR $t ===\n";
    $stmt = $sPdo->query("SELECT * FROM $t LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        print_r(array_keys($row));
    } else {
        echo "No rows found\n";
    }
}
