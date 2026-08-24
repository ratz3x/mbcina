<?php
require_once __DIR__ . '/../api.php';

$sPdo = getSupabasePDO();

if (!$sPdo) {
    echo "FAILED TO CONNECT TO SUPABASE CLOUD!\n";
    exit;
}

echo "=== M4 CLUB EVALUATION SCHEMA UPDATE ===\n";

try {
    $sPdo->exec("
        ALTER TABLE club_evaluations ADD COLUMN IF NOT EXISTS year INT DEFAULT 2026;
        ALTER TABLE club_evaluations ADD COLUMN IF NOT EXISTS activity_score INT DEFAULT 0;
        ALTER TABLE club_evaluations ADD COLUMN IF NOT EXISTS membership_score INT DEFAULT 0;
        ALTER TABLE club_evaluations ADD COLUMN IF NOT EXISTS participation_score INT DEFAULT 0;
        ALTER TABLE club_evaluations ADD COLUMN IF NOT EXISTS administration_score INT DEFAULT 0;
        ALTER TABLE club_evaluations ADD COLUMN IF NOT EXISTS final_score NUMERIC(5,2) DEFAULT 0;
        ALTER TABLE club_evaluations ADD COLUMN IF NOT EXISTS grade VARCHAR(10) DEFAULT 'B';
        ALTER TABLE club_evaluations ADD COLUMN IF NOT EXISTS status_title VARCHAR(30) DEFAULT 'GOOD';
        ALTER TABLE club_evaluations ADD COLUMN IF NOT EXISTS breakdown_details JSONB;
        ALTER TABLE club_evaluations ADD COLUMN IF NOT EXISTS recommendations JSONB;
    ");
    echo "✔ Table club_evaluations updated with 4-category columns & formula fields.\n";

    echo "=== SCHEMA UPDATE SUCCESSFUL! ===\n";
} catch (PDOException $e) {
    echo "SCHEMA ERROR: " . $e->getMessage() . "\n";
}
