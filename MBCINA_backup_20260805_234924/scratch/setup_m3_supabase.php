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

    echo "CONNECTED TO SUPABASE CLOUD POSTGRESQL!\n";

    // 1. ALTER USERS TABLE FOR M3
    $pdo->exec("
        ALTER TABLE users ADD COLUMN IF NOT EXISTS tier VARCHAR(20) DEFAULT 'BRONZE';
        ALTER TABLE users ADD COLUMN IF NOT EXISTS member_id VARCHAR(30);
        ALTER TABLE users ADD COLUMN IF NOT EXISTS club VARCHAR(100);
        ALTER TABLE users ADD COLUMN IF NOT EXISTS province VARCHAR(100);
        ALTER TABLE users ADD COLUMN IF NOT EXISTS total_donation INT DEFAULT 0;
        ALTER TABLE users ADD COLUMN IF NOT EXISTS total_events INT DEFAULT 0;
        ALTER TABLE users ADD COLUMN IF NOT EXISTS points INT DEFAULT 0;
        ALTER TABLE users ADD COLUMN IF NOT EXISTS verified_at TIMESTAMP NULL;
        ALTER TABLE users ADD COLUMN IF NOT EXISTS verified_by VARCHAR(50) NULL;
        ALTER TABLE users ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL;
        ALTER TABLE users ADD COLUMN IF NOT EXISTS birth_date DATE NULL;
        ALTER TABLE users ADD COLUMN IF NOT EXISTS gender VARCHAR(15) DEFAULT 'PRIA';
        ALTER TABLE users ADD COLUMN IF NOT EXISTS admin_notes TEXT NULL;
        ALTER TABLE users ADD COLUMN IF NOT EXISTS forum_threads_count INT DEFAULT 0;
        ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT true;
    ");
    echo "USERS TABLE ALTERED FOR M3!\n";

    // 2. CREATE MEMBER_COUNTER TABLE
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS member_counter (
            id VARCHAR(36) PRIMARY KEY,
            club_code VARCHAR(20) NOT NULL,
            year INT NOT NULL,
            sequence INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT unique_club_year UNIQUE (club_code, year)
        );
    ");
    echo "MEMBER_COUNTER TABLE READY!\n";

    // 3. CREATE DONATIONS TABLE
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS donations (
            id VARCHAR(36) PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            amount INT NOT NULL,
            payment_method VARCHAR(20) NOT NULL,
            status VARCHAR(20) DEFAULT 'PENDING',
            transaction_id VARCHAR(100),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "DONATIONS TABLE READY!\n";

    // 4. CREATE TIER_HISTORY TABLE
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tier_history (
            id VARCHAR(36) PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            tier VARCHAR(20) NOT NULL,
            total_donation INT NOT NULL,
            year INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "TIER_HISTORY TABLE READY!\n";

    // 5. CREATE NOTIFICATIONS TABLE
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id VARCHAR(36) PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            type VARCHAR(30) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            link VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "NOTIFICATIONS TABLE READY!\n";

    // 6. CREATE USER_ACTIVITIES TABLE
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_activities (
            id VARCHAR(36) PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            activity_type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            detail TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "USER_ACTIVITIES TABLE READY!\n";

    // 7. SEED INITIAL REALISTIC MEMBERS & COUNTERS & DONATIONS
    // Seed core existing users with valid member_id
    $seedUsers = [
        [
            'id' => 'usr_superadmin',
            'member_id' => 'MBINA-HQ-2024-000001',
            'tier' => 'PLATINUM',
            'total_donation' => 12500000,
            'total_events' => 24,
            'points' => 1500,
            'status' => 'ACTIVE',
            'gender' => 'PRIA',
            'birth_date' => '1985-06-12',
            'forum_threads_count' => 62
        ],
        [
            'id' => 'usr_sekpus',
            'member_id' => 'MBINA-HQ-2024-000002',
            'tier' => 'GOLD',
            'total_donation' => 6500000,
            'total_events' => 18,
            'points' => 950,
            'status' => 'ACTIVE',
            'gender' => 'PRIA',
            'birth_date' => '1982-11-20',
            'forum_threads_count' => 38
        ],
        [
            'id' => 'usr_benpus',
            'member_id' => 'MBINA-HQ-2024-000003',
            'tier' => 'SILVER',
            'total_donation' => 3200000,
            'total_events' => 14,
            'points' => 620,
            'status' => 'ACTIVE',
            'gender' => 'WANITA',
            'birth_date' => '1988-04-15',
            'forum_threads_count' => 25
        ],
        [
            'id' => 'usr_presiden',
            'member_id' => 'MBINA-HQ-2024-000004',
            'tier' => 'PLATINUM',
            'total_donation' => 15000000,
            'total_events' => 30,
            'points' => 2100,
            'status' => 'ACTIVE',
            'gender' => 'PRIA',
            'birth_date' => '1979-09-08',
            'forum_threads_count' => 85
        ]
    ];

    $updateUserStmt = $pdo->prepare("UPDATE users SET 
        member_id = :mid, tier = :tier, total_donation = :td, total_events = :te, 
        points = :pts, status = :status, gender = :gender::gender_enum, birth_date = :bdate, 
        forum_threads_count = :threads, verified_at = CURRENT_TIMESTAMP 
        WHERE id = :id");

    foreach ($seedUsers as $u) {
        $updateUserStmt->execute([
            ':mid' => $u['member_id'],
            ':tier' => $u['tier'],
            ':td' => $u['total_donation'],
            ':te' => $u['total_events'],
            ':pts' => $u['points'],
            ':status' => $u['status'],
            ':gender' => $u['gender'],
            ':bdate' => $u['birth_date'],
            ':threads' => $u['forum_threads_count'],
            ':id' => $u['id']
        ]);
    }

    // Insert additional realistic members for M3 testing (e.g. Andi Pratama, Budi Santoso, Siti Rahayu, Andi Wijaya, dsb)
    $m3SampleMembers = [
        [
            'id' => 'usr_m3_001',
            'username' => 'andi_pratama',
            'full_name' => 'Andi Pratama',
            'email' => 'andi@email.com',
            'phone' => '081234567890',
            'role' => 'MEMBER',
            'tier' => 'GOLD',
            'status' => 'ACTIVE',
            'club' => 'W124 MBCI Jakarta Chapter',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'member_id' => 'MBINA-JKT-2024-000001',
            'total_donation' => 4500000,
            'total_events' => 12,
            'points' => 850,
            'gender' => 'PRIA',
            'birth_date' => '1990-03-15',
            'admin_notes' => 'Member aktif touring W124'
        ],
        [
            'id' => 'usr_m3_002',
            'username' => 'budi_santoso',
            'full_name' => 'Budi Santoso',
            'email' => 'budi@email.com',
            'phone' => '081987654321',
            'role' => 'CALON_MEMBER',
            'tier' => 'BRONZE',
            'status' => 'PENDING',
            'club' => 'MBC Bandung',
            'province' => 'Jawa Barat',
            'city' => 'Bandung',
            'member_id' => 'MBINA-BDG-2024-000002',
            'total_donation' => 0,
            'total_events' => 0,
            'points' => 0,
            'gender' => 'PRIA',
            'birth_date' => '1993-08-22',
            'admin_notes' => 'Registrasi ulang dari website'
        ],
        [
            'id' => 'usr_m3_003',
            'username' => 'siti_rahayu',
            'full_name' => 'Siti Rahayu',
            'email' => 'siti@email.com',
            'phone' => '081345678901',
            'role' => 'MEMBER',
            'tier' => 'SILVER',
            'status' => 'ACTIVE',
            'club' => 'MBC Surabaya',
            'province' => 'Jawa Timur',
            'city' => 'Surabaya',
            'member_id' => 'MBINA-SBY-2024-000003',
            'total_donation' => 2500000,
            'total_events' => 8,
            'points' => 420,
            'gender' => 'WANITA',
            'birth_date' => '1995-12-05',
            'admin_notes' => 'Pengurus Srikandi MB Surabaya'
        ],
        [
            'id' => 'usr_m3_004',
            'username' => 'andi_wijaya',
            'full_name' => 'Andi Wijaya',
            'email' => 'andi.wijaya@email.com',
            'phone' => '081234567889',
            'role' => 'CALON_MEMBER',
            'tier' => 'BRONZE',
            'status' => 'PENDING',
            'club' => 'MBC Medan',
            'province' => 'Sumatera Utara',
            'city' => 'Medan',
            'member_id' => 'MBINA-MED-2024-000004',
            'total_donation' => 0,
            'total_events' => 0,
            'points' => 0,
            'gender' => 'PRIA',
            'birth_date' => '1991-05-18',
            'admin_notes' => 'Menunggu verifikasi bukti kepemilikan W204'
        ],
        [
            'id' => 'usr_m3_005',
            'username' => 'denny_kurniawan',
            'full_name' => 'Denny Kurniawan',
            'email' => 'denny@email.com',
            'phone' => '081771122334',
            'role' => 'MEMBER',
            'tier' => 'PLATINUM',
            'status' => 'ACTIVE',
            'club' => 'MBW211CI Jakarta Chapter',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Barat',
            'member_id' => 'MBINA-JKT-2024-000005',
            'total_donation' => 9500000,
            'total_events' => 22,
            'points' => 1850,
            'gender' => 'PRIA',
            'birth_date' => '1986-10-30',
            'admin_notes' => 'Donatur utama Jamnas 2024'
        ]
    ];

    $insertUserStmt = $pdo->prepare("
        INSERT INTO users (id, username, name, email, phone, role, tier, status, club, province, city, member_id, total_donation, total_events, points, gender, birth_date, admin_notes, password, is_active)
        VALUES (:id, :username, :name, :email, :phone, :role, :tier, :status, :club, :province, :city, :member_id, :total_donation, :total_events, :points, :gender::gender_enum, :birth_date, :admin_notes, '$2y$10$1234567890123456789012', true)
        ON CONFLICT (id) DO UPDATE SET 
            name = EXCLUDED.name,
            email = EXCLUDED.email,
            phone = EXCLUDED.phone,
            role = EXCLUDED.role,
            tier = EXCLUDED.tier,
            status = EXCLUDED.status,
            club = EXCLUDED.club,
            member_id = EXCLUDED.member_id,
            total_donation = EXCLUDED.total_donation,
            points = EXCLUDED.points
    ");

    foreach ($m3SampleMembers as $m) {
        $insertUserStmt->execute([
            ':id' => $m['id'],
            ':username' => $m['username'],
            ':name' => $m['full_name'],
            ':email' => $m['email'],
            ':phone' => $m['phone'],
            ':role' => $m['role'],
            ':tier' => $m['tier'],
            ':status' => $m['status'],
            ':club' => $m['club'],
            ':province' => $m['province'],
            ':city' => $m['city'],
            ':member_id' => $m['member_id'],
            ':total_donation' => $m['total_donation'],
            ':total_events' => $m['total_events'],
            ':points' => $m['points'],
            ':gender' => $m['gender'],
            ':birth_date' => $m['birth_date'],
            ':admin_notes' => $m['admin_notes']
        ]);
    }

    // Seed sample donations for Andi Pratama (usr_m3_001)
    $sampleDonations = [
        ['id' => 'don_001', 'user_id' => 'usr_m3_001', 'amount' => 4500000, 'method' => 'TRANSFER', 'status' => 'SUCCESS', 'tx' => 'TXN-20240802-001', 'notes' => 'Donasi Jamnas 2024'],
        ['id' => 'don_002', 'user_id' => 'usr_m3_001', 'amount' => 2000000, 'method' => 'QRIS', 'status' => 'SUCCESS', 'tx' => 'TXN-20240615-002', 'notes' => 'Donasi Bakti Sosial'],
        ['id' => 'don_003', 'user_id' => 'usr_m3_001', 'amount' => 1500000, 'method' => 'TRANSFER', 'status' => 'SUCCESS', 'tx' => 'TXN-20240301-003', 'notes' => 'Donasi Event Touring Bali'],
        ['id' => 'don_004', 'user_id' => 'usr_m3_001', 'amount' => 500000, 'method' => 'TRANSFER', 'status' => 'SUCCESS', 'tx' => 'TXN-20240101-004', 'notes' => 'Donasi Awal Keanggotaan'],

        ['id' => 'don_005', 'user_id' => 'usr_m3_005', 'amount' => 9500000, 'method' => 'TRANSFER', 'status' => 'SUCCESS', 'tx' => 'TXN-20240720-005', 'notes' => 'Sponsorship Platinum Tier']
    ];

    $insertDonationStmt = $pdo->prepare("
        INSERT INTO donations (id, user_id, amount, payment_method, status, transaction_id, notes, created_at)
        VALUES (:id, :user_id, :amount, :method, :status, :tx, :notes, CURRENT_TIMESTAMP)
        ON CONFLICT (id) DO NOTHING
    ");

    foreach ($sampleDonations as $d) {
        $insertDonationStmt->execute([
            ':id' => $d['id'],
            ':user_id' => $d['user_id'],
            ':amount' => $d['amount'],
            ':method' => $d['method'],
            ':status' => $d['status'],
            ':tx' => $d['tx'],
            ':notes' => $d['notes']
        ]);
    }

    // Seed sample tier_history for Andi Pratama
    $sampleTierHistory = [
        ['id' => 'th_001', 'user_id' => 'usr_m3_001', 'tier' => 'BRONZE', 'total' => 500000, 'year' => 2024],
        ['id' => 'th_002', 'user_id' => 'usr_m3_001', 'tier' => 'SILVER', 'total' => 2000000, 'year' => 2024],
        ['id' => 'th_003', 'user_id' => 'usr_m3_001', 'tier' => 'GOLD', 'total' => 4500000, 'year' => 2025]
    ];

    $insertTHStmt = $pdo->prepare("
        INSERT INTO tier_history (id, user_id, tier, total_donation, year, created_at)
        VALUES (:id, :user_id, :tier, :total, :year, CURRENT_TIMESTAMP)
        ON CONFLICT (id) DO NOTHING
    ");

    foreach ($sampleTierHistory as $th) {
        $insertTHStmt->execute([
            ':id' => $th['id'],
            ':user_id' => $th['user_id'],
            ':tier' => $th['tier'],
            ':total' => $th['total'],
            ':year' => $th['year']
        ]);
    }

    // Seed sample user activities for Andi Pratama
    $sampleActivities = [
        ['id' => 'act_001', 'user_id' => 'usr_m3_001', 'type' => 'DONATION', 'title' => 'Donasi', 'detail' => 'Donasi Rp 4.500.000 via Transfer (Jamnas 2024)'],
        ['id' => 'act_002', 'user_id' => 'usr_m3_001', 'type' => 'EVENT', 'title' => 'Event RSVP', 'detail' => 'Mengikuti Jambore Nasional ke-18 Solo'],
        ['id' => 'act_003', 'user_id' => 'usr_m3_001', 'type' => 'UPGRADE', 'title' => 'Upgrade Tier', 'detail' => 'Tier berhasil naik dari Silver → GOLD 🥇'],
        ['id' => 'act_004', 'user_id' => 'usr_m3_001', 'type' => 'FORUM', 'title' => 'Forum Thread', 'detail' => 'Membuat thread "Tips Perawatan Mesin M104 W124"'],
        ['id' => 'act_005', 'user_id' => 'usr_m3_001', 'type' => 'REGISTRATION', 'title' => 'Registrasi', 'detail' => 'Bergabung sebagai member terverifikasi MB INA']
    ];

    $insertActStmt = $pdo->prepare("
        INSERT INTO user_activities (id, user_id, activity_type, title, detail, created_at)
        VALUES (:id, :user_id, :type, :title, :detail, CURRENT_TIMESTAMP)
        ON CONFLICT (id) DO NOTHING
    ");

    foreach ($sampleActivities as $act) {
        $insertActStmt->execute([
            ':id' => $act['id'],
            ':user_id' => $act['user_id'],
            ':type' => $act['type'],
            ':title' => $act['title'],
            ':detail' => $act['detail']
        ]);
    }

    echo "M3 SETUP AND SEEDING COMPLETED SUCCESSFULLY IN SUPABASE CLOUD!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
