<?php
$supabaseHost = 'db.gpmpoobvfmwdnbzgofhk.supabase.co';
$supabasePort = '5432';
$supabaseDb   = 'postgres';
$supabaseUser = 'postgres';
$supabasePass = 'ssPynlbKpyunChJ2';

try {
    $dsn = "pgsql:host=$supabaseHost;port=$supabasePort;dbname=$supabaseDb;sslmode=require";
    $pdo = new PDO($dsn, $supabaseUser, $supabasePass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->prepare("UPDATE organization SET name = :name, short_name = :short_name, alias = :alias, tagline = :tagline, updated_at = NOW() WHERE id = 'org_001'");
    $stmt->execute([
        ':name' => 'Mercedes-Benz Club Indonesia',
        ':short_name' => 'MB Club INA',
        ':alias' => 'MBINA',
        ':tagline' => '"One Club, One Family" / "Bersama Satu Bintang"'
    ]);

    echo "UPDATE SUCCESSFUL IN SUPABASE CLOUD!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
