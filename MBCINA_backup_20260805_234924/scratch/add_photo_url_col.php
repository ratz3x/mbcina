<?php
require_once __DIR__ . '/../api.php';
$pdo = getSupabasePDO();
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS photo_url TEXT DEFAULT NULL");
    echo "✅ Kolom photo_url siap di tabel users Supabase\n";
    
    // Verifikasi
    $check = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='users' AND column_name='photo_url'")->fetch();
    echo $check ? "✅ Terverifikasi: photo_url ADA\n" : "⚠️ Kolom belum terdeteksi\n";
} catch (Exception $e) {
    echo "ERR: " . $e->getMessage() . "\n";
}
