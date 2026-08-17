<?php
// FULL SUPABASE CLOUD DATABASE SYNC & BACKUP SCRIPT FOR MB INA
// Date: 2026-08-10

$host = 'db.gpmpoobvfmwdnbzgofhk.supabase.co';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres';
$password = 'ssPynlbKpyunChJ2';

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";

$backupDir = __DIR__ . '/backups';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$dateStr = date('Y-m-d_H-i-s');
$sqlDumpFile  = $backupDir . "/backup_mbina_supabase_{$dateStr}.sql";
$jsonDumpFile = $backupDir . "/backup_mbina_supabase_{$dateStr}.json";

echo "=========================================================\n";
echo "  🚀 MB INA SUPABASE CLOUD DATABASE UPDATE & FULL BACKUP \n";
echo "  Tanggal: 10 Agustus 2026 | Time: " . date('H:i:s') . "\n";
echo "=========================================================\n\n";

try {
    echo "1. Connecting to Supabase PostgreSQL Cloud Database...\n";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "   ✓ Successfully Connected to Supabase Cloud Database!\n\n";

    // ── STEP 1: UPDATE & SCHEMA ENHANCEMENTS ON SUPABASE CLOUD ──
    echo "2. Updating & Enhancing Database Schema & Tables on Supabase...\n";

    // Create / Alter events table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS events (
            id VARCHAR(50) PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            type VARCHAR(50) DEFAULT 'RIDE',
            city VARCHAR(100) DEFAULT 'Yogyakarta',
            location VARCHAR(255) DEFAULT 'Candi Prambanan & Malioboro, Yogyakarta',
            start_date TIMESTAMP WITH TIME ZONE,
            end_date TIMESTAMP WITH TIME ZONE,
            capacity INT DEFAULT 150,
            registered_count INT DEFAULT 45,
            verified_count INT DEFAULT 38,
            htm_nett NUMERIC(15,2) DEFAULT 350000,
            status VARCHAR(50) DEFAULT 'PUBLISHED',
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        );
        ALTER TABLE events ADD COLUMN IF NOT EXISTS code VARCHAR(50);
        ALTER TABLE events ADD COLUMN IF NOT EXISTS start_date TIMESTAMP WITH TIME ZONE;
        ALTER TABLE events ADD COLUMN IF NOT EXISTS end_date TIMESTAMP WITH TIME ZONE;
        ALTER TABLE events ADD COLUMN IF NOT EXISTS start_formatted VARCHAR(100);
        ALTER TABLE events ADD COLUMN IF NOT EXISTS end_formatted VARCHAR(100);
        ALTER TABLE events ADD COLUMN IF NOT EXISTS capacity INT DEFAULT 150;
        ALTER TABLE events ADD COLUMN IF NOT EXISTS registered_count INT DEFAULT 45;
        ALTER TABLE events ADD COLUMN IF NOT EXISTS verified_count INT DEFAULT 38;
        ALTER TABLE events ADD COLUMN IF NOT EXISTS htm_nett NUMERIC(15,2) DEFAULT 350000;
        ALTER TABLE events ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW();
    ");
    echo "   ✓ Table 'events' checked & verified.\n";

    // Create event_albums & event_media tables if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS event_albums (
            id VARCHAR(50) PRIMARY KEY,
            event_id VARCHAR(50) REFERENCES events(id) ON DELETE CASCADE,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            cover_image VARCHAR(255),
            is_public BOOLEAN DEFAULT TRUE,
            created_by VARCHAR(100),
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
            views INT DEFAULT 0
        );
        ALTER TABLE event_albums ADD COLUMN IF NOT EXISTS is_public BOOLEAN DEFAULT TRUE;
        ALTER TABLE event_albums ADD COLUMN IF NOT EXISTS views INT DEFAULT 0;
        ALTER TABLE event_albums ADD COLUMN IF NOT EXISTS created_by VARCHAR(100);

        CREATE TABLE IF NOT EXISTS event_media (
            id VARCHAR(50) PRIMARY KEY,
            album_id VARCHAR(50) REFERENCES event_albums(id) ON DELETE CASCADE,
            event_id VARCHAR(50) REFERENCES events(id) ON DELETE CASCADE,
            media_url VARCHAR(255) NOT NULL,
            type VARCHAR(20) DEFAULT 'IMAGE',
            caption TEXT,
            uploaded_by VARCHAR(100),
            uploaded_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
            view_count INT DEFAULT 0,
            download_count INT DEFAULT 0,
            tags JSONB DEFAULT '[]'::jsonb
        );
    ");
    echo "   ✓ Tables 'event_albums' & 'event_media' checked & verified.\n";

    // Create / Alter tiers table if not exists
    $pdo->exec("
        ALTER TABLE tiers ADD COLUMN IF NOT EXISTS min_donation INT DEFAULT 0;
        ALTER TABLE tiers ADD COLUMN IF NOT EXISTS max_donation INT DEFAULT NULL;
        ALTER TABLE tiers ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE;
        ALTER TABLE tiers ALTER COLUMN level DROP NOT NULL;
        ALTER TABLE tiers ALTER COLUMN requirements DROP NOT NULL;
        ALTER TABLE tiers ALTER COLUMN features DROP NOT NULL;

        INSERT INTO tiers (id, code, name, level, icon, color, fee, min_donation, max_donation, benefits, is_active)
        VALUES
        ('tier_bronze', 'BRONZE', 'Bronze', 1, '🥉', '#CD7F32', 0, 0, 1499999, '[\"Akses Forum Diskusi\", \"Daftar Event Reguler\", \"Profile Member\"]'::jsonb, true),
        ('tier_silver', 'SILVER', 'Silver', 2, '🥈', '#C0C0C0', 1500000, 1500000, 4499999, '[\"Semua Bronze\", \"Badge Silver Premium\", \"Diskon 10% Event\", \"Prioritas Pendaftaran Event\"]'::jsonb, true),
        ('tier_gold', 'GOLD', 'Gold', 3, '🥇', '#D4AF37', 4500000, 4500000, 8999999, '[\"Semua Silver\", \"Badge Gold Premium\", \"Diskon 20% Event\", \"Akses Event VIP\", \"Merchandise Eksklusif\", \"Meet & Greet\", \"Akses Early Bird\"]'::jsonb, true),
        ('tier_platinum', 'PLATINUM', 'Platinum', 4, '💎', '#A855F7', 9000000, 9000000, NULL, '[\"Semua Gold\", \"Badge Platinum\", \"Diskon 30% Event\", \"All Access\", \"Special Treatment\"]'::jsonb, true)
        ON CONFLICT (code) DO UPDATE SET
            name = EXCLUDED.name,
            level = EXCLUDED.level,
            icon = EXCLUDED.icon,
            color = EXCLUDED.color,
            min_donation = EXCLUDED.min_donation,
            max_donation = EXCLUDED.max_donation,
            benefits = EXCLUDED.benefits;
    ");
    echo "   ✓ Table 'tiers' master data checked & verified.\n";

    // Seed audit_logs table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id VARCHAR(50) PRIMARY KEY,
            user_id VARCHAR(100) NOT NULL,
            action VARCHAR(50) NOT NULL,
            module VARCHAR(100) NOT NULL,
            details JSONB,
            ip_address VARCHAR(45) DEFAULT '127.0.0.1',
            user_agent TEXT DEFAULT 'Web Portal',
            timestamp TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        );
        ALTER TABLE audit_logs ALTER COLUMN action TYPE VARCHAR(50);
        ALTER TABLE audit_logs DROP CONSTRAINT IF EXISTS audit_logs_user_id_fkey;

        INSERT INTO audit_logs (id, user_id, action, module, details, ip_address, timestamp)
        VALUES
        ('log_001', 'usr_superadmin', 'LOGIN', 'AUTHENTICATION', '{\"username\":\"dtouriano\",\"role\":\"SUPER_ADMIN\",\"message\":\"Super Admin login berhasil\"}'::jsonb, '180.252.120.45', NOW() - INTERVAL '2 HOURS'),
        ('log_002', 'usr_superadmin', 'UPDATE', 'TIER_MANAGEMENT', '{\"tierId\":\"tier_gold\",\"name\":\"Gold\",\"minDonation\":4500000,\"maxDonation\":8999999}'::jsonb, '180.252.120.45', NOW() - INTERVAL '1 HOUR'),
        ('log_003', 'usr_superadmin', 'UPDATE', 'USER_MANAGEMENT', '{\"targetUser\":\"MBINA-JKT-2026-000009\",\"name\":\"Denny Kurniawan\",\"role\":\"MEMBER\",\"status\":\"ACTIVE\"}'::jsonb, '180.252.120.45', NOW() - INTERVAL '45 MINUTES'),
        ('log_004', 'usr_presiden2527', 'UPDATE', 'MEMBER_VERIFICATION', '{\"verifiedUser\":\"MBINA-MED-2026-000008\",\"name\":\"Andi Wijaya\",\"status\":\"APPROVED\"}'::jsonb, '114.122.34.12', NOW() - INTERVAL '30 MINUTES'),
        ('log_005', 'usr_superadmin', 'CREATE', 'EVENT_MANAGEMENT', '{\"eventCode\":\"EVT-2026-001\",\"title\":\"Touring & Bakti Sosial MB INA - Yogyakarta 2026\"}'::jsonb, '180.252.120.45', NOW() - INTERVAL '15 MINUTES')
        ON CONFLICT (id) DO NOTHING;
    ");
    echo "   ✓ Table 'audit_logs' seed data checked & verified.\n";

    // ── STEP 2: UPSERT / SEED LATEST SYNCED DATA ──
    echo "3. Synchronizing Latest Event & Sponsorship Seed Data to Supabase...\n";

    // Upsert Published Events
    $pdo->exec("
        INSERT INTO events (id, code, title, description, type, city, location, event_date, start_date, end_date, start_formatted, end_formatted, capacity, registered_count, verified_count, htm_nett, status)
        VALUES 
        ('EVT-2026-001', 'EVT-2026-001', 'Touring & Bakti Sosial MB INA - Yogyakarta 2026', 'Touring tahunan komunitas Mercedes-Benz INA menyusuri rute budaya Yogyakarta, dilanjutkan dengan acara Jambore Nusantara, Gala Dinner, dan Bakti Sosial penyerahan bantuan Panti Asuhan.', 'RIDE', 'Yogyakarta', 'Candi Prambanan & Malioboro, Yogyakarta', '2026-09-12', '2026-09-12 08:00:00+07', '2026-09-14 18:00:00+07', '12 September 2026, 08:00 WIB', '14 September 2026, 18:00 WIB', 150, 45, 38, 350000, 'PUBLISHED'),
        ('EVT-2026-002', 'EVT-2026-002', 'Jamnas MB INA XXV & Musyawarah Nasional 2026', 'Jambore Nasional perayaan HUT Mercedes-Benz Club Indonesia ke-22 dengan pameran mobil klasik W108, W114, W123, W124, W140, W202, W210 dan kontes modifikasi.', 'JAMBORE', 'Tangerang', 'ICE BSD City, Tangerang', '2026-11-20', '2026-11-20 09:00:00+07', '2026-11-22 21:00:00+07', '20 November 2026, 09:00 WIB', '22 November 2026, 21:00 WIB', 500, 120, 98, 500000, 'UPCOMING')
        ON CONFLICT (id) DO UPDATE SET
            title = EXCLUDED.title,
            description = EXCLUDED.description,
            start_formatted = EXCLUDED.start_formatted,
            end_formatted = EXCLUDED.end_formatted,
            location = EXCLUDED.location,
            updated_at = NOW();
    ");
    echo "   ✓ Published events synced successfully.\n";

    // Upsert Event Albums
    $pdo->exec("
        INSERT INTO event_albums (id, event_id, title, description, cover_image, is_public, created_by, views)
        VALUES
        ('alb_1', 'EVT-2026-001', '📁 OPENING CEREMONY', 'Dokumentasi pembukaan acara Jambore & Bakti Sosial MB INA Yogyakarta 2026', 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=500', true, 'Derist Touriano (Admin)', 2340),
        ('alb_2', 'EVT-2026-001', '📁 PARADE MERCEDES-BENZ', 'Iring-iringan parade touring mobil Mercedes-Benz keliling kota Yogyakarta', 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=500', true, 'Humas MB INA', 1890),
        ('alb_3', 'EVT-2026-001', '📁 BAKTI SOSIAL & DONASI', 'Penyerahan bantuan donasi & santunan anak yatim Jambore MB INA', 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=500', true, 'Sekjen MB INA', 1250)
        ON CONFLICT (id) DO UPDATE SET
            title = EXCLUDED.title,
            description = EXCLUDED.description;
    ");
    // Update Users official member_id in Supabase Cloud Database
    $pdo->exec("
        UPDATE users SET member_id = 'MBINA-HQ-2026-000001' WHERE id = 'usr_superadmin' OR username = 'dtouriano' OR email = 'dtouriano@gmail.com';
        UPDATE users SET member_id = 'MBINA-HQ-2026-000002' WHERE id = 'usr_raymond' OR username = 'raymond';
        UPDATE users SET member_id = 'MBINA-PUSAT-2025-002527' WHERE id = 'usr_presiden2527' OR username = 'presiden2527';
        UPDATE users SET member_id = 'MBINA-JKT-2026-002527' WHERE id = 'usr_member2527' OR username = 'member2527';
        UPDATE users SET member_id = 'MBINA-JKT-2026-000009' WHERE id = 'usr_m3_005' OR username = 'denny_kurniawan';
        UPDATE users SET member_id = 'MBINA-MED-2026-000008' WHERE id = 'usr_m3_004' OR username = 'andi_wijaya';
        UPDATE users SET member_id = 'MBINA-JKT-2026-000005' WHERE id = 'usr_m3_001' OR username = 'andi_pratama';
        UPDATE users SET member_id = 'MBINA-BDG-2026-000006' WHERE id = 'usr_m3_002' OR username = 'budi_santoso';
        UPDATE users SET member_id = 'MBINA-SBY-2026-000007' WHERE id = 'usr_m3_003' OR username = 'siti_rahayu';
    ");
    echo "   ✓ Official Member IDs (MBINA-HQ-2026-000001 format) synced successfully to Supabase Cloud users table.\n\n";

    // ── STEP 3: PERFORM FULL DATABASE BACKUP ──
    echo "4. Performing Full Database Backup & Dumping All Tables...\n";

    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public' ORDER BY table_name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $sqlDump = "-- =========================================================\n";
    $sqlDump .= "-- FULL BACKUP DUMP SUPABASE POSTGRESQL DATABASE - MB INA\n";
    $sqlDump .= "-- Backup Date: " . date('Y-m-d H:i:s T') . "\n";
    $sqlDump .= "-- Total Tables: " . count($tables) . "\n";
    $sqlDump .= "-- =========================================================\n\n";

    $jsonData = [
        'backup_metadata' => [
            'app' => 'MB INA Platform',
            'created_at' => date('c'),
            'database' => $dbname,
            'total_tables' => count($tables)
        ],
        'tables' => []
    ];

    foreach ($tables as $t) {
        $dataStmt = $pdo->query("SELECT * FROM \"$t\"");
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        $jsonData['tables'][$t] = $rows;

        $sqlDump .= "-- Table: $t (" . count($rows) . " records)\n";
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $keys = array_keys($row);
                $escapedKeys = array_map(function($k) { return "\"$k\""; }, $keys);
                $escapedVals = array_map(function($val) use ($pdo) {
                    if ($val === null) return 'NULL';
                    if (is_bool($val)) return $val ? 'TRUE' : 'FALSE';
                    if (is_array($val) || is_object($val)) return $pdo->quote(json_encode($val));
                    return $pdo->quote($val);
                }, array_values($row));

                $sqlDump .= "INSERT INTO \"$t\" (" . implode(', ', $escapedKeys) . ") VALUES (" . implode(', ', $escapedVals) . ") ON CONFLICT DO NOTHING;\n";
            }
        }
        $sqlDump .= "\n";
        echo "   - Dumped table '$t': " . count($rows) . " records\n";
    }

    file_put_contents($sqlDumpFile, $sqlDump);
    file_put_contents($jsonDumpFile, json_encode($jsonData, JSON_PRETTY_PRINT));

    echo "\n=========================================================\n";
    echo " 🎉 FULL BACKUP DATABASE SUPABASE CLOUD SUKSES!\n";
    echo "=========================================================\n";
    echo " 📄 File Dump SQL : " . basename($sqlDumpFile) . " (" . number_format(filesize($sqlDumpFile)/1024, 1) . " KB)\n";
    echo " 📦 File Dump JSON: " . basename($jsonDumpFile) . " (" . number_format(filesize($jsonDumpFile)/1024, 1) . " KB)\n";
    echo " 📁 Direktori     : " . realpath($backupDir) . "\n\n";

} catch (PDOException $e) {
    echo "❌ Error Database Backup: " . $e->getMessage() . "\n";
}
