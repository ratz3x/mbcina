<?php
require_once __DIR__ . '/../api.php';

$sPdo = getSupabasePDO();

if (!$sPdo) {
    echo "SUPABASE PDO FAILED!\n";
    exit;
}

echo "=== CHECKING STRUCTURE TABLES IN SUPABASE CLOUD ===\n";

$tables = ['advisory_board', 'honor_council', 'organization_structure', 'governance_periods'];

foreach ($tables as $t) {
    try {
        $stmt = $sPdo->query("SELECT COUNT(*) FROM $t");
        $cnt = $stmt->fetchColumn();
        echo "Table '$t': $cnt rows\n";
    } catch (Exception $e) {
        echo "Table '$t': ERROR - " . $e->getMessage() . "\n";
    }
}
