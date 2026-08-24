<?php
require_once __DIR__ . '/../api.php';

$sPdo = getSupabasePDO();

if (!$sPdo) {
    echo "FAILED TO CONNECT TO SUPABASE CLOUD!\n";
    exit;
}

echo "=== M4 CLUB REGISTRATION DATABASE MIGRATION ===\n";

try {
    // 1. Table club_applications
    $sPdo->exec("
        CREATE TABLE IF NOT EXISTS club_applications (
            id VARCHAR(36) PRIMARY KEY,
            organization_id VARCHAR(36) NOT NULL DEFAULT 'org_001',
            name VARCHAR(100) NOT NULL,
            code VARCHAR(20) UNIQUE NOT NULL,
            alias VARCHAR(50),
            province_id VARCHAR(36),
            province_name VARCHAR(100),
            city VARCHAR(100) NOT NULL,
            address TEXT NOT NULL,
            founded_year INT NOT NULL,
            member_count_estimate INT NOT NULL DEFAULT 0,
            club_type VARCHAR(20) DEFAULT 'CLUB',
            description TEXT NOT NULL,
            contact_person VARCHAR(100) NOT NULL,
            contact_phone VARCHAR(20) NOT NULL,
            contact_email VARCHAR(100),
            social_media JSONB,
            logo_url VARCHAR(255),
            photos JSONB,
            status VARCHAR(20) DEFAULT 'DRAFT',
            rejection_reason TEXT,
            submitted_by VARCHAR(36),
            submitted_at TIMESTAMP WITH TIME ZONE,
            reviewed_by VARCHAR(36),
            reviewed_at TIMESTAMP WITH TIME ZONE,
            approved_by VARCHAR(36),
            approved_at TIMESTAMP WITH TIME ZONE,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        );
    ");
    echo "✔ Table club_applications created / verified.\n";

    // 2. Table club_application_notes
    $sPdo->exec("
        CREATE TABLE IF NOT EXISTS club_application_notes (
            id VARCHAR(36) PRIMARY KEY,
            application_id VARCHAR(36) NOT NULL,
            user_id VARCHAR(36),
            user_name VARCHAR(100),
            note TEXT NOT NULL,
            is_internal BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        );
    ");
    echo "✔ Table club_application_notes created / verified.\n";

    // 3. Alter table clubs
    $sPdo->exec("
        ALTER TABLE clubs ADD COLUMN IF NOT EXISTS application_id VARCHAR(36);
        ALTER TABLE clubs ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'ACTIVE';
        ALTER TABLE clubs ADD COLUMN IF NOT EXISTS verified_at TIMESTAMP WITH TIME ZONE;
        ALTER TABLE clubs ADD COLUMN IF NOT EXISTS verified_by VARCHAR(36);
        ALTER TABLE clubs ADD COLUMN IF NOT EXISTS last_evaluation DATE;
        ALTER TABLE clubs ADD COLUMN IF NOT EXISTS evaluation_score INT DEFAULT 0;
    ");
    echo "✔ Table clubs altered with M4 columns.\n";

    // 4. Table club_status_history
    $sPdo->exec("
        CREATE TABLE IF NOT EXISTS club_status_history (
            id VARCHAR(36) PRIMARY KEY,
            club_id VARCHAR(36) NOT NULL,
            old_status VARCHAR(20),
            new_status VARCHAR(20) NOT NULL,
            reason TEXT,
            changed_by VARCHAR(36),
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        );
    ");
    echo "✔ Table club_status_history created / verified.\n";

    // 5. Table club_evaluations
    $sPdo->exec("
        CREATE TABLE IF NOT EXISTS club_evaluations (
            id VARCHAR(36) PRIMARY KEY,
            club_id VARCHAR(36) NOT NULL,
            evaluation_date DATE NOT NULL,
            score INT NOT NULL DEFAULT 0,
            category VARCHAR(30) NOT NULL,
            notes TEXT,
            evaluator VARCHAR(36),
            evaluator_name VARCHAR(100),
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        );
    ");
    echo "✔ Table club_evaluations created / verified.\n";

    // Seed Sample Applications if empty
    $chk = $sPdo->query("SELECT COUNT(*) FROM club_applications")->fetchColumn();
    if ($chk == 0) {
        $sampleApps = [
            [
                'id' => 'app_001',
                'name' => 'MBC Tegal Raya',
                'code' => 'TGL',
                'alias' => 'MTR',
                'province_id' => 'prov_33',
                'province_name' => 'Jawa Tengah',
                'city' => 'Tegal',
                'address' => 'Jl. Raya Tegal No. 1, Kota Tegal',
                'founded_year' => 2015,
                'member_count_estimate' => 35,
                'club_type' => 'CLUB',
                'description' => 'Komunitas pecinta Mercedes-Benz di Tegal dan sekitarnya. Aktif dalam touring dan kegiatan sosial.',
                'contact_person' => 'Budi Santoso',
                'contact_phone' => '081234567890',
                'contact_email' => 'mbctegal@example.com',
                'social_media' => json_encode(['instagram' => '@mbctegal', 'facebook' => 'MBC Tegal Raya']),
                'logo_url' => 'assets/mb_badge.jpg',
                'photos' => json_encode(['assets/mb_hero.jpg', 'assets/mb_badge.jpg']),
                'status' => 'PENDING',
                'submitted_by' => 'usr_001',
                'submitted_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            [
                'id' => 'app_002',
                'name' => 'MBC Jepara',
                'code' => 'JPR',
                'alias' => 'MJPR',
                'province_id' => 'prov_33',
                'province_name' => 'Jawa Tengah',
                'city' => 'Jepara',
                'address' => 'Jl. Pemuda No. 45, Jepara',
                'founded_year' => 2018,
                'member_count_estimate' => 28,
                'club_type' => 'CLUB',
                'description' => 'Klub Mercedes-Benz wilayah Jepara & Kudus.',
                'contact_person' => 'Siti Rahayu',
                'contact_phone' => '081345678901',
                'contact_email' => 'mbcjepara@example.com',
                'social_media' => json_encode(['instagram' => '@mbc_jepara']),
                'logo_url' => 'assets/mb_badge.jpg',
                'photos' => json_encode(['assets/mb_hero.jpg']),
                'status' => 'REVIEW',
                'submitted_by' => 'usr_002',
                'submitted_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'id' => 'app_003',
                'name' => 'MBC Banjarmasin',
                'code' => 'BJM',
                'alias' => 'MBCBJM',
                'province_id' => 'prov_63',
                'province_name' => 'Kalimantan Selatan',
                'city' => 'Banjarmasin',
                'address' => 'Jl. A. Yani Km 6, Banjarmasin',
                'founded_year' => 2020,
                'member_count_estimate' => 42,
                'club_type' => 'REGION',
                'description' => 'Klub regional Mercedes-Benz Kalimantan Selatan.',
                'contact_person' => 'Andi Wijaya',
                'contact_phone' => '081234567889',
                'contact_email' => 'mbcbanjarmasin@example.com',
                'social_media' => json_encode(['instagram' => '@mbc_banjarmasin']),
                'logo_url' => 'assets/mb_badge.jpg',
                'photos' => json_encode(['assets/mb_hero.jpg']),
                'status' => 'PENDING',
                'submitted_by' => 'usr_003',
                'submitted_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ],
            [
                'id' => 'app_004',
                'name' => 'MBC Makassar',
                'code' => 'MKS',
                'alias' => 'MBCMKS',
                'province_id' => 'prov_73',
                'province_name' => 'Sulawesi Selatan',
                'city' => 'Makassar',
                'address' => 'Jl. AP Pettarani No. 88, Makassar',
                'founded_year' => 2019,
                'member_count_estimate' => 50,
                'club_type' => 'CHAPTER',
                'description' => 'Chapter Mercedes-Benz Makassar dan Sulawesi.',
                'contact_person' => 'John Doe',
                'contact_phone' => '081558672426',
                'contact_email' => 'mbcmakassar@example.com',
                'social_media' => json_encode(['instagram' => '@mbc_makassar']),
                'logo_url' => 'assets/mb_badge.jpg',
                'photos' => json_encode(['assets/mb_hero.jpg']),
                'status' => 'REJECTED',
                'rejection_reason' => 'Persyaratan jumlah member aktif belum memenuhi kuota minimal 25 orang.',
                'submitted_by' => 'usr_004',
                'submitted_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
            ],
            [
                'id' => 'app_005',
                'name' => 'MBC Aceh',
                'code' => 'ACH',
                'alias' => 'MBCACEH',
                'province_id' => 'prov_11',
                'province_name' => 'Aceh',
                'city' => 'Banda Aceh',
                'address' => 'Jl. T. Umar No. 12, Banda Aceh',
                'founded_year' => 2019,
                'member_count_estimate' => 132,
                'club_type' => 'CLUB',
                'description' => 'Klub resmi Mercedes-Benz Aceh.',
                'contact_person' => 'Reza Syahputra',
                'contact_phone' => '081314106958',
                'contact_email' => 'mbcaceh@example.com',
                'social_media' => json_encode(['instagram' => '@mbc_aceh']),
                'logo_url' => 'assets/mb_badge.jpg',
                'photos' => json_encode(['assets/mb_hero.jpg']),
                'status' => 'APPROVED',
                'submitted_by' => 'usr_005',
                'submitted_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
                'approved_by' => 'usr_super',
                'approved_at' => date('Y-m-d H:i:s', strtotime('-8 days'))
            ]
        ];

        $st = $sPdo->prepare("
            INSERT INTO club_applications (
                id, organization_id, name, code, alias, province_id, province_name, city, address,
                founded_year, member_count_estimate, club_type, description, contact_person,
                contact_phone, contact_email, social_media, logo_url, photos, status, rejection_reason,
                submitted_by, submitted_at, approved_by, approved_at
            ) VALUES (
                :id, 'org_001', :name, :code, :alias, :province_id, :province_name, :city, :address,
                :founded_year, :member_count_estimate, :club_type, :description, :contact_person,
                :contact_phone, :contact_email, :social_media, :logo_url, :photos, :status, :rejection_reason,
                :submitted_by, :submitted_at, :approved_by, :approved_at
            )
        ");

        foreach ($sampleApps as $app) {
            $st->execute([
                ':id' => $app['id'],
                ':name' => $app['name'],
                ':code' => $app['code'],
                ':alias' => $app['alias'],
                ':province_id' => $app['province_id'],
                ':province_name' => $app['province_name'],
                ':city' => $app['city'],
                ':address' => $app['address'],
                ':founded_year' => $app['founded_year'],
                ':member_count_estimate' => $app['member_count_estimate'],
                ':club_type' => $app['club_type'],
                ':description' => $app['description'],
                ':contact_person' => $app['contact_person'],
                ':contact_phone' => $app['contact_phone'],
                ':contact_email' => $app['contact_email'],
                ':social_media' => $app['social_media'],
                ':logo_url' => $app['logo_url'],
                ':photos' => $app['photos'],
                ':status' => $app['status'],
                ':rejection_reason' => $app['rejection_reason'] ?? null,
                ':submitted_by' => $app['submitted_by'],
                ':submitted_at' => $app['submitted_at'],
                ':approved_by' => $app['approved_by'] ?? null,
                ':approved_at' => $app['approved_at'] ?? null
            ]);
        }
        echo "✔ Seeded 5 initial club applications.\n";
    }

    echo "=== M4 MIGRATION SUCCESSFUL! ===\n";

} catch (PDOException $e) {
    echo "MIGRATION ERROR: " . $e->getMessage() . "\n";
}
