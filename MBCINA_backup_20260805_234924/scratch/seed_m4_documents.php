<?php
require_once __DIR__ . '/../api.php';
$pdo = getSupabasePDO();

if (!$pdo) {
    echo "FAILED TO CONNECT TO SUPABASE CLOUD!\n";
    exit;
}

echo "=== SEEDING DOCUMENT & PAYMENT DATA FOR EXISTING APPLICATIONS ===\n";

try {
    $pdo->exec("
        UPDATE club_applications 
        SET 
            ad_art_url = 'assets/docs/sample_ad_art.pdf',
            management_structure_url = 'assets/docs/sample_pengurus.pdf',
            domicile_url = 'assets/docs/sample_domisili.pdf',
            stamp_url = 'assets/docs/sample_stempel.png',
            activity_photos = '[\"assets/mb_hero.jpg\",\"assets/mb_badge.jpg\",\"assets/mb_hero.jpg\"]'::jsonb,
            members_list_url = 'assets/docs/sample_daftar_anggota.xlsx',
            total_members = 15,
            has_kta_imi = true,
            fee_amount = 350000,
            payment_status = 'VERIFIED',
            payment_proof_url = 'assets/docs/sample_bukti_transfer.jpg'
        WHERE ad_art_url IS NULL OR total_members = 0;
    ");
    echo "✔ Successfully updated club_applications with official document URLs and Rp 350.000 payment status!\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
