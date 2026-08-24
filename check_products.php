<?php
$supabaseHost = 'aws-0-ap-northeast-1.pooler.supabase.com';
$supabasePort = '6543';
$supabaseDb   = 'postgres';
$supabaseUser = 'postgres.gpmpoobvfmwdnbzgofhk';
$supabasePass = 'ssPynlbKpyunChJ2';

try {
    $dsn = "pgsql:host={$supabaseHost};port={$supabasePort};dbname=$supabaseDb;sslmode=require";
    $pdo = new PDO($dsn, $supabaseUser, $supabasePass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n";
    exit;
}

echo "=== LAPAK ===\n";
foreach ($pdo->query("SELECT id, name, user_id FROM lapak") as $r) {
    echo "Lapak: {$r['id']} | {$r['name']} | {$r['user_id']}\n";
}

echo "\n=== LAPAK PRODUCTS ===\n";
foreach ($pdo->query("SELECT id, lapak_id, name, price, status, contact_whatsapp, created_at FROM lapak_products ORDER BY created_at DESC") as $r) {
    echo "Product: {$r['id']} | {$r['lapak_id']} | {$r['name']} | Rp " . number_format($r['price']) . " | {$r['status']} | {$r['contact_whatsapp']} | {$r['created_at']}\n";
}
