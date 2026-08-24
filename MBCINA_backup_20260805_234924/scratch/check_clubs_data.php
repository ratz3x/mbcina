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

    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'clubs'");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "COLUMNS: " . implode(', ', $cols) . "\n";

    if (!in_array('ketua_umum', $cols)) {
        $pdo->exec("ALTER TABLE clubs ADD COLUMN IF NOT EXISTS ketua_umum VARCHAR(150)");
        echo "ADDED COLUMN ketua_umum\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
