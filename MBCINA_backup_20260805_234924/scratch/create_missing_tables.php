<?php
require_once __DIR__ . '/../api.php';
$pdo = getSupabasePDO();
if (!$pdo) { echo "GAGAL: Tidak bisa konek Supabase!\n"; exit; }

$sqls = [];

// =============================================================
// 1. M2 - org_profile (Profil Organisasi MB INA)
// =============================================================
$sqls['org_profile'] = "
CREATE TABLE IF NOT EXISTS org_profile (
    id VARCHAR(50) PRIMARY KEY DEFAULT 'mbina_profile',
    full_name VARCHAR(200) NOT NULL DEFAULT 'Mercedes-Benz Club Indonesia',
    short_name VARCHAR(50) DEFAULT 'MB INA',
    abbreviation VARCHAR(20) DEFAULT 'MBCINA',
    legal_status VARCHAR(100) DEFAULT 'Organisasi Resmi Terdaftar',
    deed_number VARCHAR(100),
    sk_number VARCHAR(100),
    established_date DATE DEFAULT '2003-01-01',
    headquarters_city VARCHAR(100) DEFAULT 'Jakarta',
    headquarters_province VARCHAR(100) DEFAULT 'DKI Jakarta',
    headquarters_address TEXT,
    phone VARCHAR(30),
    email VARCHAR(100) DEFAULT 'info@mbina.or.id',
    website VARCHAR(100) DEFAULT 'https://mbina.or.id',
    instagram VARCHAR(100),
    facebook VARCHAR(100),
    youtube VARCHAR(100),
    total_clubs INT DEFAULT 110,
    total_members INT DEFAULT 12544,
    total_provinces INT DEFAULT 34,
    logo_url TEXT,
    about_text TEXT,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
INSERT INTO org_profile (id, full_name, short_name, abbreviation, legal_status, established_date, headquarters_city, headquarters_province, email, website, total_clubs, total_members, total_provinces, about_text)
VALUES ('mbina_profile', 'Mercedes-Benz Club Indonesia', 'MB INA', 'MBCINA', 'Organisasi Resmi Terdaftar Kemenkumham', '2003-01-01', 'Jakarta', 'DKI Jakarta', 'info@mbina.or.id', 'https://mbina.or.id', 110, 12544, 34, 'MB INA adalah wadah resmi dan profesional komunitas kendaraan Mercedes-Benz se-Indonesia. Didirikan sejak 2003, organisasi ini mengintegrasikan seluruh club dan chapter dalam satu platform digital modern.')
ON CONFLICT (id) DO UPDATE SET total_clubs = EXCLUDED.total_clubs, total_members = EXCLUDED.total_members, updated_at = NOW();
";

// =============================================================
// 2. M2 - org_structure (Pengurus Pusat)
// =============================================================
$sqls['org_structure'] = "
DO \$\$ BEGIN
    CREATE TYPE jabatan_level_enum AS ENUM ('PUSAT', 'WILAYAH', 'DAERAH');
EXCEPTION WHEN duplicate_object THEN NULL; END \$\$;

CREATE TABLE IF NOT EXISTS org_structure (
    id VARCHAR(50) PRIMARY KEY,
    user_id VARCHAR(50) REFERENCES users(id) ON DELETE SET NULL,
    jabatan_nama VARCHAR(150) NOT NULL,
    jabatan_level jabatan_level_enum DEFAULT 'PUSAT',
    periode_mulai DATE DEFAULT '2024-01-01',
    periode_selesai DATE DEFAULT '2027-12-31',
    urutan INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    foto_url TEXT,
    bio TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
INSERT INTO org_structure (id, user_id, jabatan_nama, jabatan_level, periode_mulai, periode_selesai, urutan, is_active)
VALUES
  ('ostr_001', 'usr_presiden',  'Presiden MB INA',          'PUSAT', '2024-01-01', '2027-12-31', 1, TRUE),
  ('ostr_002', 'usr_sekpus',    'Sekretaris Pusat',          'PUSAT', '2024-01-01', '2027-12-31', 2, TRUE),
  ('ostr_003', 'usr_benpus',    'Bendahara Pusat',           'PUSAT', '2024-01-01', '2027-12-31', 3, TRUE)
ON CONFLICT (id) DO NOTHING;
";

// =============================================================
// 3. M3 - membership_tiers (Konfigurasi Tier BRONZE/SILVER/GOLD/PLATINUM)
// =============================================================
$sqls['membership_tiers'] = "
CREATE TABLE IF NOT EXISTS membership_tiers (
    id VARCHAR(50) PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL,
    level INT NOT NULL,
    icon VARCHAR(10) DEFAULT '🥉',
    color VARCHAR(30) DEFAULT '#CD7F32',
    min_donation NUMERIC(15,2) DEFAULT 0,
    max_donation NUMERIC(15,2),
    benefits JSONB DEFAULT '[]',
    features JSONB DEFAULT '[]',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
INSERT INTO membership_tiers (id, code, name, level, icon, color, min_donation, max_donation, benefits, features)
VALUES
  ('tier_bronze',   'BRONZE',   'Bronze Member',   1, '🥉', '#CD7F32', 0,         1499999, '[\"Kartu Member Digital\",\"Akses Forum Diskusi\",\"Info Jamnas & Event\"]',               '[\"member_card\",\"forum_access\",\"event_info\"]'),
  ('tier_silver',   'SILVER',   'Silver Member',   2, '🥈', '#C0C0C0', 1500000,   4499999, '[\"Badge Silver\",\"Diskon 10% Official Merch\",\"Priority Event Registration\"]',         '[\"silver_badge\",\"merch_discount_10\",\"priority_event\"]'),
  ('tier_gold',     'GOLD',     'Gold Member',     3, '🥇', '#F59E0B', 4500000,   8999999, '[\"Badge Gold\",\"VIP Event Access\",\"Diskon 20% Merch\",\"Exclusive Touring\"]',         '[\"gold_badge\",\"vip_event\",\"merch_discount_20\",\"exclusive_tour\"]'),
  ('tier_platinum', 'PLATINUM', 'Platinum Member', 4, '💎', '#A855F7', 9000000,   NULL,    '[\"Badge Platinum\",\"All Access VIP\",\"Gala Dinner\",\"Diskon 30%\",\"Personal Concierge\"]', '[\"platinum_badge\",\"all_vip\",\"gala_dinner\",\"merch_discount_30\",\"concierge\"]')
ON CONFLICT (id) DO NOTHING;
";

// =============================================================
// 4. M4 - application_notes (Catatan Internal Pendaftaran Klub)
// =============================================================
$sqls['application_notes'] = "
DO \$\$ BEGIN
    CREATE TYPE note_type_enum AS ENUM ('INTERNAL', 'FEEDBACK', 'REVISION_REQUEST', 'APPROVAL', 'REJECTION');
EXCEPTION WHEN duplicate_object THEN NULL; END \$\$;

CREATE TABLE IF NOT EXISTS application_notes (
    id VARCHAR(50) PRIMARY KEY,
    application_id VARCHAR(50) NOT NULL,
    author_id VARCHAR(50) REFERENCES users(id) ON DELETE SET NULL,
    note_type note_type_enum DEFAULT 'INTERNAL',
    content TEXT NOT NULL,
    is_visible_to_applicant BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
";

// =============================================================
// 5. M4 - evaluations (Evaluasi Kinerja Klub)
// =============================================================
$sqls['evaluations'] = "
DO \$\$ BEGIN
    CREATE TYPE eval_period_enum AS ENUM ('SEMESTER', 'ANNUAL', 'CUSTOM');
EXCEPTION WHEN duplicate_object THEN NULL; END \$\$;

DO \$\$ BEGIN
    CREATE TYPE eval_status_enum AS ENUM ('DRAFT', 'PUBLISHED', 'ARCHIVED');
EXCEPTION WHEN duplicate_object THEN NULL; END \$\$;

CREATE TABLE IF NOT EXISTS evaluations (
    id VARCHAR(50) PRIMARY KEY,
    club_id VARCHAR(50) NOT NULL,
    evaluator_id VARCHAR(50) REFERENCES users(id) ON DELETE SET NULL,
    period eval_period_enum DEFAULT 'ANNUAL',
    period_label VARCHAR(50),
    period_start DATE,
    period_end DATE,
    score_keaktifan INT DEFAULT 0 CHECK (score_keaktifan BETWEEN 0 AND 100),
    score_touring INT DEFAULT 0 CHECK (score_touring BETWEEN 0 AND 100),
    score_keanggotaan INT DEFAULT 0 CHECK (score_keanggotaan BETWEEN 0 AND 100),
    score_pelaporan INT DEFAULT 0 CHECK (score_pelaporan BETWEEN 0 AND 100),
    score_total NUMERIC(5,2) GENERATED ALWAYS AS (
        (score_keaktifan + score_touring + score_keanggotaan + score_pelaporan)::NUMERIC / 4.0
    ) STORED,
    predikat VARCHAR(20) DEFAULT 'CUKUP',
    catatan TEXT,
    status eval_status_enum DEFAULT 'DRAFT',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
";

// =============================================================
// 6. M1 - backups (Riwayat Backup Database)
// =============================================================
$sqls['backups'] = "
DO \$\$ BEGIN
    CREATE TYPE backup_status_enum AS ENUM ('RUNNING', 'SUCCESS', 'FAILED');
EXCEPTION WHEN duplicate_object THEN NULL; END \$\$;

CREATE TABLE IF NOT EXISTS backups (
    id VARCHAR(50) PRIMARY KEY,
    triggered_by VARCHAR(50) REFERENCES users(id) ON DELETE SET NULL,
    backup_type VARCHAR(20) DEFAULT 'FULL',
    status backup_status_enum DEFAULT 'RUNNING',
    file_name VARCHAR(200),
    file_size_kb NUMERIC(12,2),
    download_url TEXT,
    notes TEXT,
    started_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    completed_at TIMESTAMP WITH TIME ZONE
);
INSERT INTO backups (id, triggered_by, backup_type, status, file_name, file_size_kb, notes, started_at, completed_at)
VALUES
  ('bkp_initial_001', 'usr_superadmin', 'FULL', 'SUCCESS', 'mbina_backup_initial_2026.sql', 2048.50, 'Initial production backup setelah setup Supabase Cloud', NOW() - INTERVAL '7 days', NOW() - INTERVAL '7 days' + INTERVAL '3 minutes')
ON CONFLICT (id) DO NOTHING;
";

// === EKSEKUSI ===
echo "=== MEMBUAT TABEL SUPABASE MB INA ===\n\n";
$ok = 0; $fail = 0;
foreach ($sqls as $tableName => $sql) {
    try {
        $pdo->exec($sql);
        echo "✅ $tableName — BERHASIL DIBUAT\n";
        $ok++;
    } catch (Exception $e) {
        echo "❌ $tableName — ERROR: " . $e->getMessage() . "\n";
        $fail++;
    }
}

echo "\n=== HASIL ===\n";
echo "Berhasil : $ok tabel\n";
echo "Gagal    : $fail tabel\n";
