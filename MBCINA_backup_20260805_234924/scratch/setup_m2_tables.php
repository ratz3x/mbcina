<?php
header('Content-Type: text/plain');

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

    echo "Terhubung ke Supabase Cloud PostgreSQL...\n";

    // 1. TABEL organization
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS organization (
            id VARCHAR(36) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            short_name VARCHAR(50) NOT NULL,
            alias VARCHAR(20) UNIQUE NOT NULL,
            tagline VARCHAR(255),
            founded_date DATE NOT NULL,
            founded_place VARCHAR(100) NOT NULL,
            status VARCHAR(20) DEFAULT 'ACTIVE',
            website VARCHAR(100),
            email VARCHAR(100),
            phone VARCHAR(20),
            logo_url VARCHAR(255),
            banner_url VARCHAR(255),
            description TEXT,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        );
    ");

    $pdo->exec("
        INSERT INTO organization (id, name, short_name, alias, tagline, founded_date, founded_place, status, website, email, phone, logo_url, banner_url, description)
        VALUES (
            'org_001', 
            'Mercedes-Benz Club Indonesia', 
            'MB Club INA', 
            'MBINA', 
            'One Club, One Family', 
            '2004-08-01', 
            'Jakarta', 
            'ACTIVE', 
            'www.mbclubina.com', 
            'admin@mbina.or.id', 
            '021-7890123', 
            'assets/mb_badge.jpg', 
            'assets/mb_hero.jpg', 
            'Organisasi federasi yang menaungi seluruh club Mercedes-Benz di Indonesia.'
        ) ON CONFLICT (id) DO UPDATE SET 
            name = EXCLUDED.name, 
            short_name = EXCLUDED.short_name, 
            tagline = EXCLUDED.tagline,
            email = EXCLUDED.email;
    ");

    // 2. TABEL organization_history
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS organization_history (
            id VARCHAR(36) PRIMARY KEY,
            organization_id VARCHAR(36) NOT NULL REFERENCES organization(id) ON DELETE CASCADE,
            year INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            icon VARCHAR(50),
            color VARCHAR(20),
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        );
    ");

    $pdo->exec("
        INSERT INTO organization_history (id, organization_id, year, title, description, icon, color, sort_order)
        VALUES 
        ('hist_001', 'org_001', 2004, 'Pendirian MB Club INA', 'MB Club INA didirikan pada Agustus 2004 di Jakarta sebagai club federasi yang menaungi club-club Mercedes-Benz di Indonesia. Inisiasi dilakukan oleh PT. Daimler Chrysler Indonesia.', '🏛️', '#D4AF37', 1),
        ('hist_002', 'org_001', 2004, '4 Pilar MB Club INA', 'Wim Ekel dari PT. DC INA mengemukakan konsep 4 pilar MB Club INA yaitu MCCI, MTC, MJI, dan MBCI.', '📋', '#C0C0C0', 2),
        ('hist_003', 'org_001', 2006, 'Jambore Nasional Pertama', 'Jambore MB Club INA ke-2 (setelah diadopsi dari MB Club Bali) diselenggarakan di Jogjakarta dengan tema budaya.', '🏍️', '#D4AF37', 3)
        ON CONFLICT (id) DO UPDATE SET title = EXCLUDED.title, description = EXCLUDED.description;
    ");

    // 3. TABEL founders
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS founders (
            id VARCHAR(36) PRIMARY KEY,
            organization_id VARCHAR(36) NOT NULL REFERENCES organization(id) ON DELETE CASCADE,
            name VARCHAR(100) NOT NULL,
            club_origin VARCHAR(100) NOT NULL,
            position VARCHAR(50),
            photo_url VARCHAR(255),
            bio TEXT,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        );
    ");

    $pdo->exec("
        INSERT INTO founders (id, organization_id, name, club_origin, position, bio, sort_order)
        VALUES 
        ('fnd_001', 'org_001', 'Ridwan Pohan', 'MCCI', 'President (2004-2006)', 'Founder & Presiden pertama MB Club INA', 1),
        ('fnd_002', 'org_001', 'Dharma Adsasmuda', 'MCCI', 'Treasury', 'Founder & Treasury pertama MB Club INA', 2),
        ('fnd_003', 'org_001', 'Bambang Hariyadi', 'MTC', 'Public Relation', 'Founder & Public Relation pertama MB Club INA', 3),
        ('fnd_004', 'org_001', 'Tubagus S. Hidayat', 'MTC', 'Vice President', 'Founder & Vice President pertama MB Club INA', 4)
        ON CONFLICT (id) DO UPDATE SET name = EXCLUDED.name, position = EXCLUDED.position;
    ");

    // 4. TABEL vision_mission
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS vision_mission (
            id VARCHAR(36) PRIMARY KEY,
            organization_id VARCHAR(36) NOT NULL REFERENCES organization(id) ON DELETE CASCADE,
            type VARCHAR(20) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            icon VARCHAR(50),
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        );
    ");

    $pdo->exec("
        INSERT INTO vision_mission (id, organization_id, type, title, description, icon, sort_order)
        VALUES 
        ('vm_001', 'org_001', 'VISION', 'Visi MB Club INA', 'Menjadi wadah komunitas Mercedes-Benz terbesar, terbaik, dan paling solid di Indonesia serta menjadi kebanggaan bangsa.', '🎯', 1),
        ('vm_002', 'org_001', 'MISSION', 'Misi 1 - Mempererat Persaudaraan', 'Mempererat tali persaudaraan antar anggota dan menciptakan lingkungan yang inklusif dan saling mendukung.', '🤝', 2),
        ('vm_003', 'org_001', 'MISSION', 'Misi 2 - Berbagi Pengetahuan', 'Menyediakan platform edukasi tentang Mercedes-Benz dan berbagi pengalaman serta tips perawatan.', '📚', 3),
        ('vm_004', 'org_001', 'MISSION', 'Misi 3 - Kegiatan Sosial', 'Aktif dalam kegiatan sosial dan bakti sosial serta memberikan kontribusi positif bagi masyarakat.', '❤️', 4),
        ('vm_005', 'org_001', 'MISSION', 'Misi 4 - Pelestarian Budaya Otomotif', 'Melestarikan dan mengapresiasi warisan otomotif Mercedes-Benz serta mempromosikan budaya berkendara yang aman.', '🏛️', 5),
        ('vm_006', 'org_001', 'MISSION', 'Misi 5 - Pengembangan Organisasi', 'Terus berkembang dan beradaptasi dengan zaman serta menjangkau lebih banyak anggota di seluruh Indonesia.', '🚀', 6)
        ON CONFLICT (id) DO UPDATE SET title = EXCLUDED.title, description = EXCLUDED.description;
    ");

    // 5. TABEL presidents
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS presidents (
            id VARCHAR(36) PRIMARY KEY,
            organization_id VARCHAR(36) NOT NULL REFERENCES organization(id) ON DELETE CASCADE,
            name VARCHAR(100) NOT NULL,
            period_start INT NOT NULL,
            period_end INT NOT NULL,
            photo_url VARCHAR(255),
            bio TEXT,
            is_current BOOLEAN DEFAULT FALSE,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        );
    ");

    $pdo->exec("
        INSERT INTO presidents (id, organization_id, name, period_start, period_end, bio, is_current, sort_order)
        VALUES 
        ('pres_001', 'org_001', 'Ridwan Pohan', 2004, 2006, 'Presiden pertama MB Club INA', FALSE, 1),
        ('pres_002', 'org_001', 'Raditya G Wardhana', 2006, 2009, 'Presiden MB Club INA Periode 2006-2009', FALSE, 2),
        ('pres_003', 'org_001', 'Aditya Adinatha', 2009, 2011, 'Presiden MB Club INA Periode 2009-2011', FALSE, 3),
        ('pres_004', 'org_001', 'Rheino P Soufyan', 2011, 2013, 'Presiden MB Club INA Periode 2011-2013', FALSE, 4),
        ('pres_005', 'org_001', 'Doddy Moedjito', 2013, 2015, 'Presiden MB Club INA Periode 2013-2015', FALSE, 5),
        ('pres_006', 'org_001', 'Deddy Rachmadi', 2015, 2017, 'Presiden MB Club INA Periode 2015-2017', FALSE, 6),
        ('pres_007', 'org_001', 'Idham Syewket', 2017, 2019, 'Presiden MB Club INA Periode 2017-2019', FALSE, 7),
        ('pres_008', 'org_001', 'Mahar Corleone', 2019, 2021, 'Presiden MB Club INA Periode 2019-2021', FALSE, 8),
        ('pres_009', 'org_001', 'Cecep Fajar', 2021, 2023, 'Presiden MB Club INA Periode 2021-2023', FALSE, 9),
        ('pres_010', 'org_001', 'I Made Yoga Mahardika', 2023, 2025, 'Presiden MB Club INA Periode 2023-2025', FALSE, 10),
        ('pres_011', 'org_001', 'Dr. Rochady Hendra Setya Wibawa, Sp.OG., M.Kes., S.Kom.', 2025, 2027, 'Presiden MB Club INA periode 2025-2027. Dokter spesialis kandungan dan penggemar Mercedes-Benz.', TRUE, 11)
        ON CONFLICT (id) DO UPDATE SET name = EXCLUDED.name, period_start = EXCLUDED.period_start, period_end = EXCLUDED.period_end, is_current = EXCLUDED.is_current;
    ");

    // 6. TABEL governance_periods
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS governance_periods (
            id VARCHAR(36) PRIMARY KEY,
            organization_id VARCHAR(36) NOT NULL REFERENCES organization(id) ON DELETE CASCADE,
            period_name VARCHAR(100) NOT NULL,
            year_start INT NOT NULL,
            year_end INT NOT NULL,
            is_current BOOLEAN DEFAULT FALSE,
            notes TEXT,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        );
    ");

    $pdo->exec("
        INSERT INTO governance_periods (id, organization_id, period_name, year_start, year_end, is_current, notes)
        VALUES 
        ('gp_001', 'org_001', 'Periode 2004-2006', 2004, 2006, FALSE, 'Kepengurusan pertama'),
        ('gp_002', 'org_001', 'Periode 2006-2009', 2006, 2009, FALSE, NULL),
        ('gp_003', 'org_001', 'Periode 2009-2011', 2009, 2011, FALSE, NULL),
        ('gp_004', 'org_001', 'Periode 2011-2013', 2011, 2013, FALSE, NULL),
        ('gp_005', 'org_001', 'Periode 2013-2015', 2013, 2015, FALSE, NULL),
        ('gp_006', 'org_001', 'Periode 2015-2017', 2015, 2017, FALSE, NULL),
        ('gp_007', 'org_001', 'Periode 2017-2019', 2017, 2019, FALSE, NULL),
        ('gp_008', 'org_001', 'Periode 2019-2021', 2019, 2021, FALSE, NULL),
        ('gp_009', 'org_001', 'Periode 2021-2023', 2021, 2023, FALSE, NULL),
        ('gp_010', 'org_001', 'Periode 2023-2025', 2023, 2025, FALSE, NULL),
        ('gp_011', 'org_001', 'Periode 2025-2027', 2025, 2027, TRUE, 'Kepengurusan saat ini')
        ON CONFLICT (id) DO UPDATE SET period_name = EXCLUDED.period_name, is_current = EXCLUDED.is_current;
    ");

    // 7. TABEL advisory_board (Dewan Pembina)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS advisory_board (
            id VARCHAR(36) PRIMARY KEY,
            organization_id VARCHAR(36) NOT NULL REFERENCES organization(id) ON DELETE CASCADE,
            governance_period_id VARCHAR(36) NOT NULL REFERENCES governance_periods(id) ON DELETE CASCADE,
            name VARCHAR(100) NOT NULL,
            position VARCHAR(50) NOT NULL,
            club_origin VARCHAR(100),
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        );
    ");

    $pdo->exec("
        INSERT INTO advisory_board (id, organization_id, governance_period_id, name, position, club_origin, sort_order)
        VALUES 
        ('ab_001', 'org_001', 'gp_011', 'Raditya G Wardhana', 'Ketua Dewan Pembina', NULL, 1),
        ('ab_002', 'org_001', 'gp_011', 'Bambang Hariyadi', 'Anggota Dewan Pembina', 'MTC', 2),
        ('ab_003', 'org_001', 'gp_011', 'Tubagus Syamsul Hidayat', 'Anggota Dewan Pembina', 'MTC', 3),
        ('ab_004', 'org_001', 'gp_011', 'Ridwan Pohan', 'Anggota Dewan Pembina', 'MCCI', 4),
        ('ab_005', 'org_001', 'gp_011', 'Dharma Adsasmuda', 'Anggota Dewan Pembina', 'MCCI', 5),
        ('ab_006', 'org_001', 'gp_011', 'Aditya Adinatha', 'Anggota Dewan Pembina', NULL, 6),
        ('ab_007', 'org_001', 'gp_011', 'Rheinno P Soufyan', 'Anggota Dewan Pembina', NULL, 7),
        ('ab_008', 'org_001', 'gp_011', 'Doddy Moedjito', 'Anggota Dewan Pembina', NULL, 8),
        ('ab_009', 'org_001', 'gp_011', 'Deddy Rachmadi', 'Anggota Dewan Pembina', NULL, 9),
        ('ab_010', 'org_001', 'gp_011', 'Idham Syewket Adnan', 'Anggota Dewan Pembina', NULL, 10),
        ('ab_011', 'org_001', 'gp_011', 'Mahar Malino', 'Anggota Dewan Pembina', NULL, 11),
        ('ab_012', 'org_001', 'gp_011', 'Cecep Fajar', 'Anggota Dewan Pembina', NULL, 12)
        ON CONFLICT (id) DO UPDATE SET name = EXCLUDED.name, position = EXCLUDED.position;
    ");

    // 8. TABEL honor_council (Dewan Kehormatan)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS honor_council (
            id VARCHAR(36) PRIMARY KEY,
            organization_id VARCHAR(36) NOT NULL REFERENCES organization(id) ON DELETE CASCADE,
            governance_period_id VARCHAR(36) NOT NULL REFERENCES governance_periods(id) ON DELETE CASCADE,
            name VARCHAR(100) NOT NULL,
            position VARCHAR(50) NOT NULL,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        );
    ");

    $pdo->exec("
        INSERT INTO honor_council (id, organization_id, governance_period_id, name, position, sort_order)
        VALUES 
        ('hc_001', 'org_001', 'gp_011', 'Ferry Juliantono', 'Anggota Dewan Kehormatan', 1),
        ('hc_002', 'org_001', 'gp_011', 'Dedie Rachim', 'Anggota Dewan Kehormatan', 2),
        ('hc_003', 'org_001', 'gp_011', 'Moreno', 'Anggota Dewan Kehormatan', 3)
        ON CONFLICT (id) DO UPDATE SET name = EXCLUDED.name, position = EXCLUDED.position;
    ");

    // 9. TABEL organizational_structure (Pengurus Pusat Tree)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS organizational_structure (
            id VARCHAR(36) PRIMARY KEY,
            organization_id VARCHAR(36) NOT NULL REFERENCES organization(id) ON DELETE CASCADE,
            position_name VARCHAR(100) NOT NULL,
            position_level VARCHAR(30) NOT NULL,
            parent_id VARCHAR(36) REFERENCES organizational_structure(id) ON DELETE SET NULL,
            user_id VARCHAR(50) REFERENCES users(id) ON DELETE SET NULL,
            club_origin VARCHAR(100),
            sort_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            period_start INT NOT NULL,
            period_end INT NOT NULL,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        );
    ");

    $pdo->exec("
        INSERT INTO organizational_structure (id, organization_id, position_name, position_level, parent_id, user_id, club_origin, sort_order, period_start, period_end)
        VALUES 
        ('str_001', 'org_001', 'Presiden MB Club INA', 'PRESIDEN', NULL, 'usr_presiden', NULL, 1, 2025, 2027),
        ('str_002', 'org_001', 'Sekretaris Jenderal', 'DEPARTEMEN', 'str_001', 'usr_sekpus', 'MB Club Bandung', 2, 2025, 2027),
        ('str_003', 'org_001', 'Bendahara', 'DEPARTEMEN', 'str_001', 'usr_benpus', 'MBW210CI', 3, 2025, 2027),
        ('str_004', 'org_001', 'EVP 1 - Organization', 'EVP', 'str_001', NULL, 'W124 MBCI', 4, 2025, 2027),
        ('str_005', 'org_001', 'EVP 2 - Motor Program', 'EVP', 'str_001', NULL, 'MBW212CI', 5, 2025, 2027),
        ('str_006', 'org_001', 'EVP 3 - Event Dev.', 'EVP', 'str_001', NULL, 'MBW211CI', 6, 2025, 2027),
        ('str_007', 'org_001', 'EVP 4 - Regional', 'EVP', 'str_001', NULL, 'MBC Madiun', 7, 2025, 2027),
        ('str_008', 'org_001', 'EVP 5 - Regional', 'EVP', 'str_001', NULL, 'MB Club Medan', 8, 2025, 2027),
        ('str_009', 'org_001', 'EVP 6 - Public Relation', 'EVP', 'str_001', NULL, 'MBW212CI', 9, 2025, 2027),
        ('str_010', 'org_001', 'VP Organisasi', 'VP', 'str_004', NULL, 'MBC Lampung', 10, 2025, 2027),
        ('str_011', 'org_001', 'VP Legal & IT', 'VP', 'str_004', NULL, 'MBW202CI', 11, 2025, 2027),
        ('str_012', 'org_001', 'VP Motorsport', 'VP', 'str_005', NULL, 'MBW204CI', 12, 2025, 2027),
        ('str_013', 'org_001', 'VP Touring', 'VP', 'str_005', NULL, 'MJI', 13, 2025, 2027)
        ON CONFLICT (id) DO UPDATE SET position_name = EXCLUDED.position_name, position_level = EXCLUDED.position_level;
    ");

    // ALTER CLUBS TO ENSURE organization_id COLUMN EXISTS
    $pdo->exec("ALTER TABLE clubs ADD COLUMN IF NOT EXISTS organization_id VARCHAR(36) DEFAULT 'org_001';");
    $pdo->exec("ALTER TABLE clubs ADD COLUMN IF NOT EXISTS region VARCHAR(50);");
    $pdo->exec("ALTER TABLE clubs ADD COLUMN IF NOT EXISTS type VARCHAR(20) DEFAULT 'CLUB';");
    $pdo->exec("ALTER TABLE clubs ADD COLUMN IF NOT EXISTS is_verified BOOLEAN DEFAULT TRUE;");

    // 10. SEED CLUBS (Data 30+ Klub dari PDF)
    $pdo->exec("
        INSERT INTO clubs (id, organization_id, name, code, region, city, type, status, is_verified, member_count) VALUES
        ('clb_ace', 'org_001', 'MBC Aceh', 'ACE', 'Sumatera', 'Banda Aceh', 'CLUB', 'ACTIVE', TRUE, 25),
        ('clb_mtb', 'org_001', 'MTC INA Bireuen Chapter', 'MTB', 'Sumatera', 'Bireuen', 'CHAPTER', 'ACTIVE', TRUE, 15),
        ('clb_lgs', 'org_001', 'MBC Langsa', 'LGS', 'Sumatera', 'Langsa', 'CLUB', 'ACTIVE', TRUE, 20),
        ('clb_mdn', 'org_001', 'MBC Medan', 'MDN', 'Sumatera', 'Medan', 'CLUB', 'ACTIVE', TRUE, 45),
        ('clb_wmd', 'org_001', 'W124MBCI Medan Chapter', 'WMD', 'Sumatera', 'Medan', 'CHAPTER', 'ACTIVE', TRUE, 30),
        ('clb_pdg', 'org_001', 'MBC Padang', 'PDG', 'Sumatera', 'Padang', 'CLUB', 'ACTIVE', TRUE, 25),
        ('clb_pku', 'org_001', 'MBC Pekanbaru', 'PKU', 'Sumatera', 'Pekanbaru', 'CLUB', 'ACTIVE', TRUE, 35),
        ('clb_plb', 'org_001', 'MBC Palembang', 'PLB', 'Sumatera', 'Palembang', 'CLUB', 'ACTIVE', TRUE, 40),
        ('clb_lpg', 'org_001', 'MBC Lampung', 'LPG', 'Sumatera', 'Bandar Lampung', 'CLUB', 'ACTIVE', TRUE, 30),
        ('clb_mcci', 'org_001', 'MCCI', 'MCCI', 'DKI Jakarta', 'Jakarta', 'CLUB', 'ACTIVE', TRUE, 120),
        ('clb_mtc', 'org_001', 'MTC INA', 'MTC', 'DKI Jakarta', 'Jakarta', 'CLUB', 'ACTIVE', TRUE, 110),
        ('clb_mji', 'org_001', 'MJI', 'MJI', 'DKI Jakarta', 'Jakarta', 'CLUB', 'ACTIVE', TRUE, 95),
        ('clb_w124', 'org_001', 'W124 MBCI', 'W124', 'DKI Jakarta', 'Jakarta', 'CLUB', 'ACTIVE', TRUE, 80),
        ('clb_w202', 'org_001', 'MBW202 Club Indonesia', 'MBW202', 'DKI Jakarta', 'Jakarta', 'CLUB', 'ACTIVE', TRUE, 75),
        ('clb_w211', 'org_001', 'MBW211 Club Indonesia', 'MBW211', 'DKI Jakarta', 'Jakarta', 'CLUB', 'ACTIVE', TRUE, 65),
        ('clb_btn', 'org_001', 'MBC Banten', 'BTN', 'Banten', 'Serang', 'CLUB', 'ACTIVE', TRUE, 40),
        ('clb_tgr', 'org_001', 'MBC Tangerang Raya', 'TGR', 'Banten', 'Tangerang', 'CLUB', 'ACTIVE', TRUE, 50),
        ('clb_bdg', 'org_001', 'MBC Bandung', 'BDG', 'Jawa Barat', 'Bandung', 'CLUB', 'ACTIVE', TRUE, 60),
        ('clb_bmuc', 'org_001', 'BMUC', 'BMUC', 'Jawa Barat', 'Bandung', 'CLUB', 'ACTIVE', TRUE, 35),
        ('clb_skb', 'org_001', 'MBC Sukabumi', 'SKB', 'Jawa Barat', 'Sukabumi', 'CLUB', 'ACTIVE', TRUE, 25),
        ('clb_crb', 'org_001', 'MBC Cirebon', 'CRB', 'Jawa Barat', 'Cirebon', 'CLUB', 'ACTIVE', TRUE, 30),
        ('clb_smg', 'org_001', 'MBC Semarang', 'SMG', 'Jawa Tengah', 'Semarang', 'CLUB', 'ACTIVE', TRUE, 55),
        ('clb_slo', 'org_001', 'MBC Solo Raya', 'SLO', 'Jawa Tengah', 'Solo', 'CLUB', 'ACTIVE', TRUE, 60),
        ('clb_clc', 'org_001', 'MBC Cilacap', 'CLC', 'Jawa Tengah', 'Cilacap', 'CLUB', 'ACTIVE', TRUE, 30),
        ('clb_tgl', 'org_001', 'MBC Tegal Raya', 'TGL', 'Jawa Tengah', 'Tegal', 'CLUB', 'ACTIVE', TRUE, 35),
        ('clb_yog', 'org_001', 'MBC Yogyakarta', 'YOG', 'Yogyakarta', 'Yogyakarta', 'CLUB', 'ACTIVE', TRUE, 50),
        ('clb_mlg', 'org_001', 'MBC Malang', 'MLG', 'Jawa Timur', 'Malang', 'CLUB', 'ACTIVE', TRUE, 45),
        ('clb_sby', 'org_001', 'MBC Surabaya', 'SBY', 'Jawa Timur', 'Surabaya', 'CLUB', 'ACTIVE', TRUE, 70),
        ('clb_bli', 'org_001', 'MBC Bali', 'BLI', 'Bali', 'Denpasar', 'CLUB', 'ACTIVE', TRUE, 45),
        ('clb_bjm', 'org_001', 'MBC Banjarmasin', 'BJM', 'Kalimantan', 'Banjarmasin', 'CLUB', 'ACTIVE', TRUE, 30),
        ('clb_mks', 'org_001', 'MBC Makasar', 'MKS', 'Sulawesi', 'Makasar', 'CLUB', 'ACTIVE', TRUE, 35)
        ON CONFLICT (id) DO UPDATE SET name = EXCLUDED.name, region = EXCLUDED.region;
    ");

    // 11. TABEL club_members
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS club_members (
            id VARCHAR(36) PRIMARY KEY,
            club_id VARCHAR(36) NOT NULL REFERENCES clubs(id) ON DELETE CASCADE,
            user_id VARCHAR(50) NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            role VARCHAR(30) DEFAULT 'ANGGOTA',
            joined_date DATE NOT NULL DEFAULT CURRENT_DATE,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
            UNIQUE (club_id, user_id)
        );
    ");

    $pdo->exec("
        INSERT INTO club_members (id, club_id, user_id, role, joined_date) VALUES 
        ('cm_001', 'clb_mcci', 'usr_superadmin', 'KETUA', '2020-01-01'),
        ('cm_002', 'clb_bdg', 'usr_presiden', 'KETUA', '2015-05-10'),
        ('cm_003', 'clb_bdg', 'usr_sekpus', 'SEKRETARIS', '2018-03-15'),
        ('cm_004', 'clb_sby', 'usr_benpus', 'BENDAHARA', '2019-07-20')
        ON CONFLICT (id) DO NOTHING;
    ");

    // 12. TABEL club_galleries
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS club_galleries (
            id VARCHAR(36) PRIMARY KEY,
            club_id VARCHAR(36) NOT NULL REFERENCES clubs(id) ON DELETE CASCADE,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            image_url VARCHAR(255) NOT NULL,
            event_id VARCHAR(50) REFERENCES events(id) ON DELETE SET NULL,
            uploaded_by VARCHAR(50) NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        );
    ");

    $pdo->exec("
        INSERT INTO club_galleries (id, club_id, title, description, image_url, uploaded_by) VALUES 
        ('gal_001', 'clb_mcci', 'Touring Merdeka 2025', 'Dokumentasi touring kebangsaan MCCI Jakarta.', 'assets/mb_hero.jpg', 'usr_superadmin'),
        ('gal_002', 'clb_bdg', 'Gathering MBC Bandung', 'Kopdar rutin dan silaturahmi anggota MBC Bandung.', 'assets/mb_badge.jpg', 'usr_presiden')
        ON CONFLICT (id) DO NOTHING;
    ");

    echo "\n🎉 MODUL M2 (MANAJEMEN ORGANISASI - 12 TABEL) BERHASIL DIRANCANG DAN DI-SEED DIREK KE SUPABASE CLOUD POSTGRESQL!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
