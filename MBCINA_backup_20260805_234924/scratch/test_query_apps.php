<?php
require_once __DIR__ . '/../api.php';
$pdo = getSupabasePDO();
$st = $pdo->query("SELECT id, name, code, total_members, has_kta_imi, fee_amount, payment_status FROM club_applications ORDER BY created_at DESC LIMIT 5");
print_r($st->fetchAll());
