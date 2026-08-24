<?php
require_once __DIR__ . '/../api.php';
$pdo = getSupabasePDO();
if (!$pdo) { echo "FAILED: Cannot connect to Supabase!\n"; exit; }

echo "=== SUPABASE TABLE CHECK ===\n\n";

$tables = [
    'users'              => 'M1/M3 - Data User & Member',
    'clubs'              => 'M2 - Data Klub/Chapter',
    'org_profile'        => 'M2 - Profil Organisasi MB INA',
    'founders'           => 'M2 - Data Pendiri Organisasi',
    'vision_mission'     => 'M2 - Visi & Misi',
    'presidents'         => 'M2 - Daftar Presiden',
    'org_structure'      => 'M2 - Pengurus Pusat',
    'provinces'          => 'M3 - Data Provinsi',
    'membership_tiers'   => 'M3 - Tier Keanggotaan',
    'donations'          => 'M3 - Riwayat Donasi',
    'tier_history'       => 'M3 - Riwayat Upgrade Tier',
    'club_applications'  => 'M4 - Pendaftaran Klub',
    'application_notes'  => 'M4 - Catatan Internal Aplikasi',
    'evaluations'        => 'M4 - Evaluasi Kinerja Klub',
    'forum_categories'   => 'M5 - Kategori Forum',
    'forum_threads'      => 'M5 - Thread Diskusi',
    'forum_replies'      => 'M5 - Balasan Thread',
    'forum_likes'        => 'M5 - Like/Dislike',
    'forum_reports'      => 'M5 - Laporan Spam',
    'broadcasts'         => 'M5 - Broadcast',
    'broadcast_targets'  => 'M5 - Target Broadcast',
    'broadcast_stats'    => 'M5 - Statistik Broadcast',
    'moderation_actions' => 'M5 - Tindakan Moderasi',
    'forum_rules'        => 'M5 - Peraturan Forum',
    'audit_logs'         => 'M1 - Audit Log',
    'system_settings'    => 'M1 - Pengaturan Sistem',
    'notifications'      => 'M1 - Notifikasi',
    'backups'            => 'M1 - Backup Database',
];

$exists = 0;
$missing = 0;

foreach ($tables as $table => $label) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "✅ $table ($label) — $count rows\n";
        $exists++;
    } catch (Exception $e) {
        echo "❌ $table ($label) — TIDAK ADA\n";
        $missing++;
    }
}

echo "\n=== RINGKASAN ===\n";
echo "Total tabel ADA    : $exists\n";
echo "Total tabel BELUM  : $missing\n";
echo "Total tabel dicek  : " . count($tables) . "\n";
