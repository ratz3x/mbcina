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

    $stmt = $pdo->query("SELECT enumlabel FROM pg_enum JOIN pg_type ON pg_enum.enumtypid = pg_type.oid WHERE typname = 'gender_enum'");
    $vals = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "GENDER_ENUM VALUES: " . implode(', ', $vals) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
