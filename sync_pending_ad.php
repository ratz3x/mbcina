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

// Upsert Blok Mesin W124 Copotan as PENDING
$prodId = 'prod_w124_copotan';
$lapakId = 'lapak_6a80f3422be6b'; // Garasi FayFay
$name = 'Blok Mesin W124 Copotan';
$desc = 'Blok mesin M104 / M111 Mercedes-Benz W124 Boxer copotan garansi kompresi mulus, liner silinder terawat tanpa baret, siap pasang & cocok untuk restorasi.';
$price = 25000000;
$cond = 'USED';
$loc = 'Jakarta Selatan';
$images = json_encode(['https://images.unsplash.com/photo-1517524008697-84bbe3c3fd98?w=600']);
$status = 'PENDING';
$cat = 'Parts & Komponen';
$wa = '08545585568';

// Check if exists
$exists = $pdo->query("SELECT COUNT(*) FROM lapak_products WHERE id = '$prodId' OR name = 'Blok Mesin W124 Copotan'")->fetchColumn();
if ($exists > 0) {
    $stmt = $pdo->prepare("UPDATE lapak_products SET name = ?, description = ?, price = ?, condition = ?, location = ?, images = ?, status = 'PENDING', category = ?, contact_whatsapp = ?, rejection_reason = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ? OR name = ?");
    $stmt->execute([$name, $desc, $price, $cond, $loc, $images, $cat, $wa, $prodId, $name]);
    echo "Updated existing Blok Mesin W124 Copotan to PENDING status.\n";
} else {
    $stmt = $pdo->prepare("INSERT INTO lapak_products (id, lapak_id, name, description, price, condition, location, images, views, status, category, contact_whatsapp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'PENDING', ?, ?)");
    $stmt->execute([$prodId, $lapakId, $name, $desc, $price, $cond, $loc, $images, $cat, $wa]);
    echo "Inserted new Blok Mesin W124 Copotan with PENDING status.\n";
}

// Also let's update prod_ratih_01 if it exists so it aligns or stays
$pdo->exec("UPDATE lapak_products SET name = 'Blok Mesin W124 Copotan', status = 'PENDING' WHERE id = 'prod_ratih_01'");

echo "\n=== ALL PENDING PRODUCTS IN SUPABASE ===\n";
foreach ($pdo->query("SELECT id, lapak_id, name, price, status, contact_whatsapp FROM lapak_products WHERE status = 'PENDING'") as $r) {
    echo "{$r['id']} | {$r['name']} | Rp " . number_format($r['price']) . " | Status: {$r['status']} | WA: {$r['contact_whatsapp']}\n";
}
