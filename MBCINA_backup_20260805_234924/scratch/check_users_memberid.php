<?php
require_once __DIR__ . '/../api.php';
$pdo = getSupabasePDO();
if (!$pdo) { echo "GAGAL konek!\n"; exit; }

// Tampilkan data users saat ini
$users = $pdo->query("SELECT id, username, name, city, province_id, member_id, role, created_at FROM users ORDER BY created_at ASC")->fetchAll();
echo "=== DATA USERS SAAT INI ===\n";
foreach ($users as $i => $u) {
    echo ($i+1) . ". [{$u['id']}] {$u['name']} | City: {$u['city']} | Province: {$u['province_id']} | Member ID: {$u['member_id']} | Created: {$u['created_at']}\n";
}
echo "\nTotal: " . count($users) . " users\n";
