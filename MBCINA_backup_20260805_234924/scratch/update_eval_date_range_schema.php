<?php
require_once __DIR__ . '/../api.php';

$sPdo = getSupabasePDO();

if (!$sPdo) {
    echo "FAILED TO CONNECT TO SUPABASE CLOUD!\n";
    exit;
}

echo "=== ADD EVALUATION DATE RANGE COLUMNS ===\n";

try {
    $sPdo->exec("
        ALTER TABLE club_evaluations ADD COLUMN IF NOT EXISTS period_type VARCHAR(20) DEFAULT 'ANNUAL';
        ALTER TABLE club_evaluations ADD COLUMN IF NOT EXISTS start_date DATE DEFAULT '2026-01-01';
        ALTER TABLE club_evaluations ADD COLUMN IF NOT EXISTS end_date DATE DEFAULT '2026-12-31';
    ");
    echo "✔ Columns period_type, start_date, and end_date added to club_evaluations.\n";
    echo "=== SUCCESS! ===\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
