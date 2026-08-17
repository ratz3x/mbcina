<?php
// Automatic Migration & Sync Script for Supabase Cloud Database

$host = 'db.gpmpoobvfmwdnbzgofhk.supabase.co';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres';
$password = 'ssPynlbKpyunChJ2';

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";

try {
    echo "Connecting to Supabase PostgreSQL Cloud Database...\n";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✓ Successfully Connected to Supabase Cloud Database!\n\n";

    $sqlFile = __DIR__ . '/supabase_schema.sql';
    if (!file_exists($sqlFile)) {
        die("Error: supabase_schema.sql not found!\n");
    }

    echo "Reading SQL Migration Script (supabase_schema.sql)...\n";
    $sql = file_get_contents($sqlFile);

    echo "Executing SQL DDL & DML Seed Script on Supabase Cloud...\n";
    $pdo->exec($sql);
    echo "=========================================================\n";
    echo " SUCCESS! ALL 6 TABLES & SEED DATA CREATED IN SUPABASE! \n";
    echo "=========================================================\n\n";

    // Verify created tables and records count
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Verified Tables in Supabase:\n";
    foreach ($tables as $t) {
        $countStmt = $pdo->query("SELECT COUNT(*) FROM $t");
        $cnt = $countStmt->fetchColumn();
        echo " - Table '$t': $cnt records\n";
    }

} catch (PDOException $e) {
    echo "Error Migration to Supabase: " . $e->getMessage() . "\n";
}
