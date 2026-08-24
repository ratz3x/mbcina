<?php
require_once __DIR__ . '/../api.php';

$sPdo = getSupabasePDO();

if (!$sPdo) {
    echo "FAILED TO CONNECT TO SUPABASE CLOUD!\n";
    exit;
}

echo "=== UPDATE SCHEMA FOR M4 CLUB APPLICATION DOCUMENTS & PAYMENT ===\n";

try {
    $sPdo->exec("
        ALTER TABLE club_applications ADD COLUMN IF NOT EXISTS ad_art_url VARCHAR(255);
        ALTER TABLE club_applications ADD COLUMN IF NOT EXISTS management_structure_url VARCHAR(255);
        ALTER TABLE club_applications ADD COLUMN IF NOT EXISTS domicile_url VARCHAR(255);
        ALTER TABLE club_applications ADD COLUMN IF NOT EXISTS stamp_url VARCHAR(255);
        ALTER TABLE club_applications ADD COLUMN IF NOT EXISTS activity_photos JSONB DEFAULT '[]'::jsonb;
        ALTER TABLE club_applications ADD COLUMN IF NOT EXISTS members_list_url VARCHAR(255);
        ALTER TABLE club_applications ADD COLUMN IF NOT EXISTS total_members INT DEFAULT 0;
        ALTER TABLE club_applications ADD COLUMN IF NOT EXISTS has_kta_imi BOOLEAN DEFAULT FALSE;
        ALTER TABLE club_applications ADD COLUMN IF NOT EXISTS fee_amount INT DEFAULT 350000;
        ALTER TABLE club_applications ADD COLUMN IF NOT EXISTS payment_status VARCHAR(50) DEFAULT 'UNPAID';
        ALTER TABLE club_applications ADD COLUMN IF NOT EXISTS payment_proof_url VARCHAR(255);
    ");
    echo "✔ Columns ad_art_url, management_structure_url, domicile_url, stamp_url, activity_photos, members_list_url, total_members, has_kta_imi, fee_amount, payment_status, and payment_proof_url added successfully.\n";
    echo "=== MIGRATION COMPLETE! ===\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
