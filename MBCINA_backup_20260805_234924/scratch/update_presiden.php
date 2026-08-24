<?php
$dsn = "pgsql:host=db.gpmpoobvfmwdnbzgofhk.supabase.co;port=5432;dbname=postgres;sslmode=require";
$pdo = new PDO($dsn, "postgres", "ssPynlbKpyunChJ2", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Update Presiden
$stmt1 = $pdo->prepare("UPDATE users SET name = :name, city = :city, phone = :phone, email = :email WHERE id = 'usr_presiden' OR username = 'presiden_mbina'");
$stmt1->execute([
    ':name' => 'Dr. Rochady Hendra Setya Wibawa, Sp.OG., M.Kes., S.Kom.',
    ':city' => 'Kota Bandung',
    ':phone' => '08111228708',
    ':email' => 'presiden@mbina.or.id'
]);

// Update Sekpus
$stmt2 = $pdo->prepare("UPDATE users SET name = 'Ir. Raymond Sanjaya', city = 'Bandung', phone = '08123456789', email = 'sekretaris.pusat@mbina.or.id' WHERE id = 'usr_sekpus' OR username = 'sekpus_mbina'");
$stmt2->execute();

// Update Benpus
$stmt3 = $pdo->prepare("UPDATE users SET name = 'Dra. Endang Rahayu', city = 'Surabaya', phone = '08134567890', email = 'bendahara.pusat@mbina.or.id' WHERE id = 'usr_benpus' OR username = 'benpus_mbina'");
$stmt3->execute();

echo "SUCCESSFULLY UPDATED ALL EXECUTIVE ROLES ON SUPABASE CLOUD!\n";
