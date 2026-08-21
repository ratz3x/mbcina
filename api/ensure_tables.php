<?php
function logAudit($userId, $action, $module, $details) {
    $sPdo = getSupabasePDO();
    if ($sPdo) {
        try {
            $logId = 'log_' . uniqid();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Web Portal';
            
            $detailsJson = is_string($details) ? $details : json_encode($details);

            $stmt = $sPdo->prepare("INSERT INTO audit_logs (id, user_id, action, module, details, ip_address, user_agent, timestamp) VALUES (:id, :user_id, :action::audit_action_enum, :module, :details, :ip, :ua, NOW())");
            $stmt->execute([
                ':id' => $logId,
                ':user_id' => $userId ?: 'usr_superadmin',
                ':action' => in_array($action, ['CREATE','UPDATE','DELETE','LOGIN','LOGOUT','SUSPEND','RESET_PASSWORD']) ? $action : 'UPDATE',
                ':module' => $module,
                ':details' => $detailsJson,
                ':ip' => $ip,
                ':ua' => $ua
            ]);
        } catch (Exception $e) {}
    }
}

function ensureM3Tables($sPdo) {
    if (!$sPdo) return;
    static $ensured = false;
    if ($ensured) return;
    $ensured = true;
    try {
        $sPdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id VARCHAR(36) PRIMARY KEY,
                username VARCHAR(100),
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255),
                phone VARCHAR(50),
                role VARCHAR(50) DEFAULT 'MEMBER',
                status VARCHAR(20) DEFAULT 'PENDING',
                tier VARCHAR(20) DEFAULT 'BRONZE',
                club VARCHAR(255),
                province_id VARCHAR(50),
                province VARCHAR(100),
                city VARCHAR(100),
                member_id VARCHAR(50),
                total_donation INT DEFAULT 0,
                total_events INT DEFAULT 0,
                points INT DEFAULT 0,
                gender VARCHAR(20),
                birth_date DATE,
                admin_notes TEXT,
                photo_url TEXT,
                avatar_url TEXT,
                rejection_reason TEXT,
                verified_at TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS donations (
                id VARCHAR(36) PRIMARY KEY,
                trx_code VARCHAR(50),
                campaign_id VARCHAR(36),
                user_id VARCHAR(36),
                donor_name VARCHAR(255),
                member_id VARCHAR(50),
                amount INT NOT NULL,
                payment_method VARCHAR(50) DEFAULT 'TRANSFER',
                status VARCHAR(20) DEFAULT 'SUCCESS',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS donation_campaigns (
                id VARCHAR(36) PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                code VARCHAR(50),
                target_amount INT NOT NULL,
                collected_amount INT DEFAULT 0,
                donor_count INT DEFAULT 0,
                start_date DATE,
                end_date DATE,
                status VARCHAR(20) DEFAULT 'ACTIVE',
                banner_url TEXT,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Seed users if Ratih is missing
        // Seed users if Ratih is missing
        $ratihExists = (int)$sPdo->query("SELECT COUNT(*) FROM users WHERE id = 'usr_m3_011' OR email = 'ratihkusumastuti1979@gmail.com'")->fetchColumn();
        if ($ratihExists === 0) {
            $sPdo->exec("
                INSERT INTO users (id, name, username, email, phone, role, status, member_id, tier, city, province_id, province, total_donation, club) VALUES
                ('usr_m3_011', 'Ratih Kusumastuti', 'ratih1979', 'ratihkusumastuti1979@gmail.com', '08545585568', 'MEMBER', 'ACTIVE', 'MBINA-JAM-2026-000011', 'PLATINUM', 'Jambi', 'prov_jam', 'Jambi', 15400000, 'MBC Jambi')
                ON CONFLICT (id) DO NOTHING;
            ");
        } else {
            $sPdo->exec("
                UPDATE users SET status = 'ACTIVE', tier = 'PLATINUM', total_donation = 15400000, club = 'MBC Jambi' WHERE id = 'usr_m3_011' OR id = 'usr_6a7ed057d1d21' OR member_id = 'MBINA-JAM-2026-000011';
            ");
        }

        // Seed 5 real donations for Ratih if missing
        $donExists = (int)$sPdo->query("SELECT COUNT(*) FROM donations WHERE id = 'don_15' OR trx_code = 'DON-TRX-2026-015'")->fetchColumn();
        if ($donExists === 0) {
            $sPdo->exec("
                INSERT INTO donations (id, trx_code, campaign_id, user_id, donor_name, member_id, amount, payment_method, status, created_at) VALUES
                ('don_15', 'DON-TRX-2026-015', 'camp_yogya_2026', 'usr_m3_011', 'Ratih Kusumastuti', 'MBINA-JAM-2026-000011', 2000000, 'TRANSFER', 'SUCCESS', '2026-08-15 10:00:00'),
                ('don_14', 'DON-TRX-2026-014', 'camp_yogya_2026', 'usr_m3_011', 'Ratih Kusumastuti', 'MBINA-JAM-2026-000011', 4000000, 'TRANSFER', 'SUCCESS', '2026-08-14 16:30:00'),
                ('don_13', 'DON-TRX-2026-013', 'camp_yogya_2026', 'usr_m3_011', 'Ratih Kusumastuti', 'MBINA-JAM-2026-000011', 500000, 'TRANSFER', 'SUCCESS', '2026-08-13 11:20:00'),
                ('don_12', 'DON-TRX-2026-012', 'camp_yogya_2026', 'usr_m3_011', 'Ratih Kusumastuti', 'MBINA-JAM-2026-000011', 2000000, 'TRANSFER', 'SUCCESS', '2026-08-12 09:45:00'),
                ('don_11', 'DON-TRX-2026-011', 'camp_yogya_2026', 'usr_m3_011', 'Ratih Kusumastuti', 'MBINA-JAM-2026-000011', 5000000, 'TRANSFER', 'SUCCESS', '2026-08-11 14:00:00')
                ON CONFLICT (id) DO NOTHING;
            ");
        }

        // Dynamically sync user total_donation & tier from actual donations + event_participants tables in Supabase
        $sPdo->exec("
            UPDATE users u
            SET total_donation = COALESCE((
                SELECT SUM(d.amount)
                FROM donations d
                WHERE d.user_id = u.id
                  AND d.status IN ('SUCCESS', 'CONFIRMED', 'VERIFIED', 'APPROVED')
            ), 0) + COALESCE((
                SELECT SUM(p.fee_paid)
                FROM event_participants p
                WHERE p.user_id = u.id
                  AND p.payment_status IN ('SUCCESS', 'CONFIRMED', 'VERIFIED', 'APPROVED')
            ), 0);

            UPDATE users u
            SET tier = CASE
                WHEN u.total_donation >= 9000000 THEN 'PLATINUM'
                WHEN u.total_donation >= 4500000 THEN 'GOLD'
                WHEN u.total_donation >= 1500000 THEN 'SILVER'
                ELSE 'BRONZE'
            END;
        ");

        // Seed donation_campaigns if camp_yogya_2026 is missing
        $campExists = (int)$sPdo->query("SELECT COUNT(*) FROM donation_campaigns WHERE id = 'camp_yogya_2026'")->fetchColumn();
        if ($campExists === 0) {
            $sPdo->exec("
                INSERT INTO donation_campaigns (id, title, code, target_amount, collected_amount, donor_count, start_date, end_date, status, description) VALUES
                ('camp_yogya_2026', 'Penggalangan Dana Jamnas & Bakso Jogja 2026', 'DON-JOG-2026', 50000000, 28500000, 42, '2026-07-01', '2026-09-30', 'ACTIVE', 'Donasi sosial dan partisipasi Jamnas MB INA 2026')
                ON CONFLICT (id) DO NOTHING;
            ");
        }

        // Seed event_participants table in database for verified participants
        $sPdo->exec("
            DELETE FROM event_participants WHERE event_id IN ('evt_001', 'EVT-2026-001') AND id NOT IN ('part_1', 'part_2', 'part_3', 'part_4', 'part_5', 'part_6', 'part_7');

            INSERT INTO event_participants (id, event_id, user_id, user_name, fee_paid, payment_status, registered_at) VALUES
            ('part_1', 'evt_001', 'usr_superadmin', 'Derist Touriano', 350000, 'VERIFIED', '2026-08-10 08:30:00'),
            ('part_2', 'evt_001', 'usr_sekpus', 'Ir. Raymond Sanjaya', 350000, 'VERIFIED', '2026-08-09 09:15:00'),
            ('part_3', 'evt_001', 'usr_presiden', 'Dr. Rochady Hendra Setya Wibawa, Sp.OG., M.Kes., S.Kom.', 350000, 'VERIFIED', '2026-08-05 10:00:00'),
            ('part_4', 'evt_001', 'usr_presiden', 'Dr. Rochady Hendra Setya Wibawa, Sp.OG., M.Kes., S.Kom. (Presiden MB INA)', 350000, 'VERIFIED', '2026-08-05 10:00:00'),
            ('part_5', 'evt_001', 'usr_m3_001', 'Andi Pratama', 400000, 'PENDING', '2026-08-04 14:20:00'),
            ('part_6', 'evt_001', 'usr_m3_002', 'Budi Santoso', 500000, 'PENDING', '2026-08-04 15:45:00'),
            ('part_7', 'evt_001', 'usr_6a7ed057d1d21', 'Ratih Kusumastuti', 400000, 'VERIFIED', '2026-08-11 11:20:00'),
            ('part_bsd_5', 'EVT-2026-002', 'usr_6a7ed057d1d21', 'Ratih Kusumastuti', 400000, 'VERIFIED', '2026-08-12 10:15:00')
            ON CONFLICT (id) DO UPDATE SET user_id = EXCLUDED.user_id, payment_status = EXCLUDED.payment_status;

            UPDATE event_participants SET payment_status = 'VERIFIED' WHERE user_id = 'usr_6a7ed057d1d21' OR id IN ('part_1', 'part_2', 'part_3', 'part_4', 'part_7', 'part_bsd_5');
        ");
    } catch (Exception $e) {}
}

function ensureM5Tables($sPdo) {
    if (!$sPdo) return;
    try {
        $sPdo->exec("
            CREATE TABLE IF NOT EXISTS forum_categories (
                id VARCHAR(50) PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                icon VARCHAR(50),
                description TEXT,
                sort_order INT DEFAULT 0,
                thread_count INT DEFAULT 0
            );

            ALTER TABLE forum_categories ADD COLUMN IF NOT EXISTS thread_count INT DEFAULT 0;

            CREATE TABLE IF NOT EXISTS forum_threads (
                id VARCHAR(50) PRIMARY KEY,
                category_id VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                author_id VARCHAR(50) NOT NULL,
                author_name VARCHAR(100) NOT NULL,
                author_username VARCHAR(100) NOT NULL,
                author_avatar VARCHAR(50),
                tags VARCHAR(255),
                content TEXT,
                views_count INT DEFAULT 0,
                replies_count INT DEFAULT 0,
                is_pinned BOOLEAN DEFAULT FALSE,
                is_locked BOOLEAN DEFAULT FALSE,
                last_post_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            ALTER TABLE forum_threads ADD COLUMN IF NOT EXISTS replies_count INT DEFAULT 0;
            ALTER TABLE forum_threads ADD COLUMN IF NOT EXISTS views_count INT DEFAULT 0;
            ALTER TABLE forum_threads ADD COLUMN IF NOT EXISTS is_pinned BOOLEAN DEFAULT FALSE;
            ALTER TABLE forum_threads ADD COLUMN IF NOT EXISTS is_locked BOOLEAN DEFAULT FALSE;

            CREATE TABLE IF NOT EXISTS forum_replies (
                id VARCHAR(50) PRIMARY KEY,
                thread_id VARCHAR(50) NOT NULL,
                author_id VARCHAR(50) NOT NULL,
                author_name VARCHAR(100) NOT NULL,
                author_username VARCHAR(100) NOT NULL,
                author_avatar VARCHAR(50),
                content TEXT,
                likes_count INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");
    } catch (Exception $e) {}
}

function ensureM7Tables($sPdo) {
    if (!$sPdo) return;
    static $ensured = false;
    if ($ensured) return;
    $ensured = true;
    try {
        $sPdo->exec("
            CREATE TABLE IF NOT EXISTS lapak (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                lapak_code VARCHAR(50) UNIQUE NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                category VARCHAR(50),
                contact_phone VARCHAR(50) NOT NULL,
                contact_whatsapp VARCHAR(50) NOT NULL,
                logo_url VARCHAR(500),
                banner_url VARCHAR(500),
                sewa_start_date DATE NOT NULL,
                sewa_end_date DATE NOT NULL,
                sewa_status VARCHAR(20) DEFAULT 'ACTIVE',
                sewa_fee INT NOT NULL DEFAULT 5000,
                tier_discount INT DEFAULT 0,
                original_fee INT DEFAULT 5000,
                final_fee INT DEFAULT 5000,
                sewa_paid_status VARCHAR(20) DEFAULT 'PAID',
                is_active BOOLEAN DEFAULT TRUE,
                is_verified BOOLEAN DEFAULT TRUE,
                created_by VARCHAR(36) NOT NULL DEFAULT 'usr_superadmin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            ALTER TABLE lapak ADD COLUMN IF NOT EXISTS tier_discount INT DEFAULT 0;
            ALTER TABLE lapak ADD COLUMN IF NOT EXISTS original_fee INT DEFAULT 5000;
            ALTER TABLE lapak ADD COLUMN IF NOT EXISTS final_fee INT DEFAULT 5000;
            ALTER TABLE lapak ADD COLUMN IF NOT EXISTS payment_proof_url VARCHAR(500);
            ALTER TABLE lapak ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'PENDING';
            ALTER TABLE lapak ADD COLUMN IF NOT EXISTS rejection_reason TEXT;

            CREATE TABLE IF NOT EXISTS lapak_products (
                id VARCHAR(36) PRIMARY KEY,
                lapak_id VARCHAR(36) NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price INT NOT NULL,
                condition VARCHAR(20) DEFAULT 'USED',
                location VARCHAR(100),
                images TEXT,
                views INT DEFAULT 0,
                status VARCHAR(20) DEFAULT 'PENDING',
                rejection_reason TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            ALTER TABLE lapak_products ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'PENDING';
            ALTER TABLE lapak_products ADD COLUMN IF NOT EXISTS rejection_reason TEXT;
            ALTER TABLE lapak_products ADD COLUMN IF NOT EXISTS category VARCHAR(50);
            ALTER TABLE lapak_products ADD COLUMN IF NOT EXISTS contact_whatsapp VARCHAR(50);
            ALTER TABLE lapak_products ADD COLUMN IF NOT EXISTS user_id VARCHAR(36);
            ALTER TABLE lapak_products ADD COLUMN IF NOT EXISTS seller_name VARCHAR(255);
            ALTER TABLE lapak_products ADD COLUMN IF NOT EXISTS is_published BOOLEAN DEFAULT TRUE;

            CREATE TABLE IF NOT EXISTS lapak_reviews (
                id VARCHAR(36) PRIMARY KEY,
                lapak_id VARCHAR(36) NOT NULL,
                user_id VARCHAR(36) NOT NULL,
                rating INT NOT NULL,
                content TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS lapak_sewa_logs (
                id VARCHAR(36) PRIMARY KEY,
                lapak_id VARCHAR(36) NOT NULL,
                action VARCHAR(20) NOT NULL,
                period_start DATE NOT NULL,
                period_end DATE NOT NULL,
                fee INT NOT NULL,
                payment_status VARCHAR(20) DEFAULT 'PAID',
                notes TEXT,
                created_by VARCHAR(36) NOT NULL DEFAULT 'usr_superadmin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS product_categories (
                id VARCHAR(36) PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) UNIQUE NOT NULL,
                description TEXT,
                icon VARCHAR(50),
                is_active BOOLEAN DEFAULT TRUE,
                display_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Seed Product Categories if empty
        $catCount = $sPdo->query("SELECT COUNT(*) FROM product_categories")->fetchColumn();
        if ((int)$catCount === 0) {
            $sPdo->exec("
                INSERT INTO product_categories (id, name, slug, description, icon, is_active, display_order) VALUES
                ('cat_parts', 'Parts & Komponen', 'parts', 'Suku cadang original & aftermarket Mercedes-Benz', '⚙️', TRUE, 1),
                ('cat_merch', 'Merchandise Resmi', 'merchandise', 'Kaos, topi, jaket, dan pernak-pernik resmi MB INA', '👕', TRUE, 2),
                ('cat_aksesoris', 'Aksesoris & Variasi', 'aksesoris', 'Aksesoris interior, eksterior, emblem & lampu', '⭐', TRUE, 3),
                ('cat_servis', 'Jasa & Bengkel', 'servis', 'Layanan spesialis, tune up, & overhaul Mercedes-Benz', '🛠️', TRUE, 4),
                ('cat_unit', 'Mobil / Unit', 'mobil-unit', 'Jual beli unit mobil Mercedes-Benz terverifikasi', '🚗', TRUE, 5);
            ");
        }

        // Seed Lapak Stores only if table is completely empty
        $lapakCount = $sPdo->query("SELECT COUNT(*) FROM lapak")->fetchColumn();
        if ((int)$lapakCount === 0) {
            $sPdo->exec("
                INSERT INTO lapak (id, user_id, lapak_code, name, description, category, contact_phone, contact_whatsapp, logo_url, banner_url, sewa_start_date, sewa_end_date, sewa_status, sewa_fee, original_fee, tier_discount, final_fee, sewa_paid_status, is_active, is_verified, created_by) VALUES
                ('lapak_001', 'usr_m3_001', 'LAPAK-2026-001', 'Andi Parts Store', 'Menjual berbagai parts Mercedes-Benz original & aftermarket terpercaya', 'Parts', '021-1234567', '081234567890', 'https://images.unsplash.com/photo-1617814076367-b759c7d7e738?w=300', 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=1200', '2026-08-01', '2027-01-31', 'ACTIVE', 25500, 30000, 15, 25500, 'PAID', TRUE, TRUE, 'usr_superadmin'),
                ('lapak_002', 'usr_m3_002', 'LAPAK-2026-002', 'Siti Merchandise', 'Penyedia apparel & merchandise resmi MB INA berkualitas premium', 'Merchandise', '021-7654321', '081987654321', 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=300', 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1200', '2026-08-01', '2027-01-31', 'ACTIVE', 27000, 30000, 10, 27000, 'PAID', TRUE, TRUE, 'usr_superadmin'),
                ('lapak_003', 'usr_m3_003', 'LAPAK-2026-003', 'Budi Aksesoris', 'Spesialis aksesoris interior & eksterior Mercedes-Benz klasik & modern', 'Aksesoris', '021-9988776', '081355443322', 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=300', 'https://images.unsplash.com/photo-1563720223185-11003d516935?w=1200', '2026-02-01', '2026-07-31', 'ACTIVE', 28500, 30000, 5, 28500, 'PAID', TRUE, TRUE, 'usr_superadmin'),
                ('lapak_004', 'usr_m3_011', 'LAPAK-2026-004', 'Garasi FayFay', 'Spesialis mobil klasik & copotan parts Mercedes-Benz W124 / W210 / Boxer', 'Parts', '08545585568', '08545585568', 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=300', 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=1200', '2026-08-01', '2027-01-31', 'ACTIVE', 25500, 30000, 15, 25500, 'PAID', TRUE, TRUE, 'usr_m3_011'),
                ('lapak_fdr', 'usr_sponsor_fdr', 'SPONSOR-FDR', 'FDR Tyre Indonesia (Official Store Sponsor)', 'Official tyre partner Mercedes-Benz Club Indonesia', 'Parts', '081234567890', '081234567890', 'https://images.unsplash.com/photo-1578844251758-2f71da64c96f?w=300', 'https://images.unsplash.com/photo-1578844251758-2f71da64c96f?w=1200', '2026-01-01', '2026-12-31', 'ACTIVE', 0, 0, 0, 0, 'PAID', TRUE, TRUE, 'usr_superadmin'),
                ('lapak_shell', 'usr_sponsor_shell', 'SPONSOR-SHELL', 'Shell Indonesia (Official Store Sponsor)', 'Official lubricants partner Mercedes-Benz Club Indonesia', 'Parts', '021-52901234', '021-52901234', 'https://images.unsplash.com/photo-1617814076367-b759c7d7e738?w=300', 'https://images.unsplash.com/photo-1617814076367-b759c7d7e738?w=1200', '2026-01-01', '2026-12-31', 'ACTIVE', 0, 0, 0, 0, 'PAID', TRUE, TRUE, 'usr_superadmin');
            ");
        }

        // Seed Master Products only if table is completely empty
        $prodCount = $sPdo->query("SELECT COUNT(*) FROM lapak_products")->fetchColumn();
        if ((int)$prodCount === 0) {
            $sPdo->exec("
                INSERT INTO lapak_products (id, lapak_id, user_id, seller_name, name, description, price, condition, category, location, contact_whatsapp, images, views, status, is_published) VALUES
                ('prod_001', 'lapak_001', 'usr_m3_001', 'Andi Pratama', 'Velg AMG 18\" Monoblock', 'Velg AMG 18\" Monoblock original kondisi 90%, ban Michelin masih 80%. Cocok untuk W124 / W210 / W211.', 15000000, 'USED', 'Parts', 'Jakarta Selatan', '081234567890', '[\"https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=600\"]', 142, 'APPROVED', TRUE),
                ('prod_002', 'lapak_001', 'usr_m3_001', 'Andi Pratama', 'Filter Udara W124 Original Mann', 'Filter udara kualitas Jerman original Mann-Filter untuk W124 Boxer 300E / E320.', 350000, 'NEW', 'Parts', 'Jakarta Pusat', '081234567890', '[\"https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=600\"]', 88, 'APPROVED', TRUE),
                ('prod_003', 'lapak_001', 'usr_m3_001', 'Andi Pratama', 'Bushing Arm W211 Lemforder', 'Bushing arm depan set kanan-kiri Lemforder W211 E-Class baru gress.', 250000, 'NEW', 'Parts', 'Jakarta Selatan', '081234567890', '[\"https://images.unsplash.com/photo-1517524008697-84bbe3c3fd98?w=600\"]', 65, 'APPROVED', TRUE),
                ('prod_004', 'lapak_001', 'usr_m3_001', 'Andi Pratama', 'Cover Headlamp W204 C-Class', 'Cover lampu utama transparan OEM W204 facelift mulus tanpa retak.', 750000, 'USED', 'Parts', 'Jakarta Barat', '081234567890', '[\"https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=600\"]', 94, 'APPROVED', TRUE),
                ('prod_005', 'lapak_002', 'usr_m3_002', 'Siti Rahayu', 'Kaos Polo MB INA Official 2026', 'Kaos polo katun combed 30s bermutu tinggi dengan bordir logo emas MB INA.', 150000, 'NEW', 'Merchandise', 'Surabaya', '081987654321', '[\"https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=600\"]', 210, 'APPROVED', TRUE),
                ('prod_006', 'lapak_002', 'usr_m3_002', 'Siti Rahayu', 'Gantungan Kunci Kulit Genuine MB INA', 'Gantungan kunci bahan kulit sapi asli dengan logo embossing MB INA.', 45000, 'NEW', 'Merchandise', 'Surabaya', '081987654321', '[\"https://images.unsplash.com/photo-1618354691373-d851c5c3a990?w=600\"]', 130, 'APPROVED', TRUE),
                ('prod_007', 'lapak_003', 'usr_m3_003', 'Budi Santoso', 'Emblem Grille Bintang Mercedes-Benz Chrome', 'Emblem grill bintang tiga titik chrome mengkilap cocok untuk tipe W202 W203 W210.', 120000, 'NEW', 'Aksesoris', 'Bandung', '081355443322', '[\"https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=600\"]', 45, 'APPROVED', TRUE),
                ('prod_008', 'lapak_001', 'usr_m3_001', 'Andi Pratama', 'Karpet Moulded 3D MB INA Bespoke Hitam W205 C-Class', 'Karpet interior presisi 3D anti air dengan logo bordir MB INA.', 1250000, 'NEW', 'Aksesoris', 'Jakarta Selatan', '081111222333', '[\"https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=600\"]', 88, 'APPROVED', TRUE),
                ('prod_009', 'lapak_003', 'usr_m3_003', 'Budi Santoso', 'Filter Udara K&N High Flow W204 C250 CGI Turbo', 'Replacement filter K&N USA aliran udara optimal untuk mesin CGI Turbo.', 850000, 'USED', 'Parts', 'Bandung, Jawa Barat', '083344556679', '[\"https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=600\"]', 56, 'APPROVED', TRUE),
                ('prod_010', 'lapak_002', 'usr_m3_002', 'Siti Rahayu', 'Lampu Angel Eyes LED W211 E-Class Sepasang', 'Headlamp projector Angel Eyes LED crystal white look modern W211.', 2750000, 'USED', 'Aksesoris', 'Surabaya, Jawa Timur', '082234455668', '[\"https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=600\"]', 92, 'APPROVED', TRUE),
                ('prod_011', 'lapak_001', 'usr_m3_001', 'Andi Pratama', 'Velg Ronale Mercedes 17\" W203 C-Class Bekas Mulus', 'Set velg 4 pcs R17 OEM Ronal Mercedes mulus tanpa retak/peang.', 3200000, 'USED', 'Parts', 'Depok, Jawa Barat', '081334455667', '[\"https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=600\"]', 65, 'APPROVED', TRUE),
                ('prod_012', 'lapak_001', 'usr_m3_001', 'Andi Pratama', 'Set Stir Wood Trim Woodgrain W124 Boxer', 'Stir kemudi kombinasi kayu Zebrano & kulit asli MB-Tex W124 original.', 4500000, 'USED', 'Aksesoris', 'Jakarta Selatan', '081234567890', '[\"https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=600\"]', 78, 'APPROVED', TRUE),
                ('prod_013', 'lapak_004', 'usr_m3_011', 'Ratih Kusumastuti', 'W124 300E 1991 Manual Classic', 'Kondisi mulus terawat, pajak hidup, interior MB-Tex original, siap pakai luar kota.', 98000000, 'USED', 'Mobil / Unit', 'Jakarta Selatan', '08545585568', '[\"https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=600\"]', 189, 'APPROVED', TRUE),
                ('prod_014', 'lapak_004', 'usr_m3_011', 'Ratih Kusumastuti', 'Transmisi Otomatis 722.6 (5G-Tronic) W210 E240 Copotan', 'Transmisi matic 722.6 smooth mulus copotan sehat, bergaransi.', 7500000, 'USED', 'Parts', 'Jakarta Selatan', '08545585568', '[\"https://images.unsplash.com/photo-1617814076367-b759c7d7e738?w=600\"]', 74, 'APPROVED', TRUE),
                ('prod_015', 'lapak_004', 'usr_m3_011', 'Ratih Kusumastuti', 'Blok Mesin W124 Copotan', 'Blok mesin W124 Boxer 300E M103 copotan original kondisi kompresi padat.', 25000000, 'USED', 'Parts', 'Jakarta Selatan', '08545585568', '[\"https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=600\"]', 120, 'APPROVED', TRUE),
                ('prod_fdr_1', 'lapak_fdr', 'usr_sponsor_fdr', 'PT Suryaraya Rubberindo Industries (FDR)', 'FDR Ultimate Performance Tire 245/45 R18 (Mercedes E-Class)', 'Ban performa tinggi FDR untuk Mercedes-Benz E-Class W211/W212/W213.', 1850000, 'NEW', 'Parts', 'Jakarta Selatan', '081234567890', '[\"https://images.unsplash.com/photo-1578844251758-2f71da64c96f?w=600\"]', 342, 'APPROVED', TRUE),
                ('prod_shell_1', 'lapak_shell', 'usr_sponsor_shell', 'PT Shell Indonesia', 'Pelumas Mesin Shell Helix Ultra 0W-40 Fully Synthetic (4L)', 'Oli mesin performa tinggi sertifikasi resmi Mercedes-Benz MB-Approval 229.5.', 650000, 'NEW', 'Parts', 'Jakarta Pusat', '021-52901234', '[\"https://images.unsplash.com/photo-1617814076367-b759c7d7e738?w=600\"]', 520, 'APPROVED', TRUE);
            ");
        }

        // Seed Reviews only if table is completely empty
        $revCount = $sPdo->query("SELECT COUNT(*) FROM lapak_reviews")->fetchColumn();
        if ((int)$revCount === 0) {
            $sPdo->exec("
                INSERT INTO lapak_reviews (id, lapak_id, user_id, rating, content) VALUES
                ('rev_001', 'lapak_001', 'usr_m3_002', 5, 'Barang velg AMG monoblock sangat bagus, paking rapi, seller sangat responsif via WhatsApp!'),
                ('rev_002', 'lapak_002', 'usr_superadmin', 5, 'Kaos resmi MB INA bahannya sangat nyaman dan dingin. Pengiriman cepat!');
            ");
        }

        // Seed Sewa Logs only if table is completely empty
        $logCount = $sPdo->query("SELECT COUNT(*) FROM lapak_sewa_logs")->fetchColumn();
        if ((int)$logCount === 0) {
            $sPdo->exec("
                INSERT INTO lapak_sewa_logs (id, lapak_id, action, period_start, period_end, fee, payment_status, notes, created_by) VALUES
                ('log_001', 'lapak_001', 'SEWA', '2026-08-01', '2027-01-31', 25500, 'PAID', 'Pembayaran sewa 6 bulan lunas (Diskon Gold 15%)', 'usr_superadmin'),
                ('log_002', 'lapak_002', 'SEWA', '2026-08-01', '2027-01-31', 27000, 'PAID', 'Pembayaran sewa 6 bulan lunas (Diskon Silver 10%)', 'usr_superadmin'),
                ('log_003', 'lapak_003', 'SEWA', '2026-02-01', '2026-07-31', 28500, 'PAID', 'Pembayaran sewa 6 bulan lunas (Diskon Bronze 5%)', 'usr_superadmin');
            ");
        }
    } catch (Exception $e) {}
}

function ensureM8Tables($sPdo) {
    if (!$sPdo) return;
    static $ensured = false;
    if ($ensured) return;
    $ensured = true;
    try {
        $sPdo->exec("
            CREATE TABLE IF NOT EXISTS endorse_packages (
                id VARCHAR(36) PRIMARY KEY,
                name VARCHAR(50) NOT NULL,
                price INT NOT NULL,
                duration INT DEFAULT 1,
                forum_posts INT DEFAULT 0,
                social_posts INT DEFAULT 0,
                banner BOOLEAN DEFAULT FALSE,
                mention_event BOOLEAN DEFAULT FALSE,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS endorse_contracts (
                id VARCHAR(36) PRIMARY KEY,
                partner_name VARCHAR(255) NOT NULL,
                contact_person VARCHAR(100),
                contact_email VARCHAR(100),
                contact_phone VARCHAR(20),
                package_id VARCHAR(36) NOT NULL,
                contract_number VARCHAR(50),
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                total_amount INT NOT NULL,
                payment_status VARCHAR(20) DEFAULT 'UNPAID',
                status VARCHAR(20) DEFAULT 'DRAFT',
                notes TEXT,
                created_by VARCHAR(36) NOT NULL DEFAULT 'usr_superadmin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS endorse_posts (
                id VARCHAR(36) PRIMARY KEY,
                contract_id VARCHAR(36) NOT NULL,
                platform VARCHAR(20) NOT NULL,
                post_url VARCHAR(255),
                posted_at TIMESTAMP,
                reach INT DEFAULT 0,
                impressions INT DEFAULT 0,
                engagement INT DEFAULT 0,
                likes INT DEFAULT 0,
                comments INT DEFAULT 0,
                shares INT DEFAULT 0,
                status VARCHAR(20) DEFAULT 'SCHEDULED',
                created_by VARCHAR(36) NOT NULL DEFAULT 'usr_superadmin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS ad_campaigns (
                id VARCHAR(36) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(20) NOT NULL,
                partner_name VARCHAR(255),
                package_name VARCHAR(50) DEFAULT 'Platinum',
                budget INT NOT NULL,
                spent INT DEFAULT 0,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                impressions_target INT DEFAULT 0,
                clicks_target INT DEFAULT 0,
                impressions_current INT DEFAULT 0,
                clicks_current INT DEFAULT 0,
                ctr DECIMAL(5,2) DEFAULT 0,
                status VARCHAR(20) DEFAULT 'DRAFT',
                expired_warning_sent BOOLEAN DEFAULT FALSE,
                banner_url TEXT,
                image_url TEXT,
                description TEXT,
                cta_text VARCHAR(100),
                link VARCHAR(255),
                position VARCHAR(50) DEFAULT 'HEADER',
                notes TEXT,
                created_by VARCHAR(36) NOT NULL DEFAULT 'usr_superadmin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            ALTER TABLE ad_campaigns ADD COLUMN IF NOT EXISTS banner_url TEXT;
            ALTER TABLE ad_campaigns ADD COLUMN IF NOT EXISTS image_url TEXT;
            ALTER TABLE ad_campaigns ADD COLUMN IF NOT EXISTS package_name VARCHAR(50);
            ALTER TABLE ad_campaigns ADD COLUMN IF NOT EXISTS description TEXT;
            ALTER TABLE ad_campaigns ADD COLUMN IF NOT EXISTS cta_text VARCHAR(100);
            ALTER TABLE ad_campaigns ADD COLUMN IF NOT EXISTS link VARCHAR(255);
            ALTER TABLE ad_campaigns ADD COLUMN IF NOT EXISTS position VARCHAR(50);
            ALTER TABLE ad_campaigns ADD COLUMN IF NOT EXISTS sort_order INT DEFAULT 99;
        ");

        $cntAds = (int)$sPdo->query("SELECT COUNT(*) FROM ad_campaigns")->fetchColumn();
        if ($cntAds === 0) {
            $sPdo->exec("
                INSERT INTO ad_campaigns (id, name, type, partner_name, package_name, budget, spent, start_date, end_date, impressions_target, clicks_target, impressions_current, clicks_current, ctr, status, banner_url, image_url, description, cta_text, link, position, sort_order, created_by)
                VALUES 
                ('ac_rotator_1', 'Banner Promo Ban Michelin Pilot Sport 5', 'BANNER', 'Michelin Indonesia', 'Platinum', 10000000, 4500000, '2026-08-01', '2027-08-01', 50000, 5000, 18400, 1920, 10.43, 'ACTIVE', 'https://images.unsplash.com/photo-1578844251758-2f71da64c96f?w=1200', 'https://images.unsplash.com/photo-1578844251758-2f71da64c96f?w=1200', 'Promo spesial ban high-performance Michelin Pilot Sport 5 diskon 15% khusus member MB INA', 'Beli Ban Michelin', 'https://www.michelin.co.id/mbina', 'HEADER', 1, 'usr_superadmin'),
                ('ac_rotator_2', 'Sponsored Post: Perawatan Transmisi 7G-Tronic', 'SPONSORED', 'ZF Aftermarket Indonesia', 'Platinum', 10000000, 3800000, '2026-08-01', '2027-08-01', 50000, 5000, 14200, 1510, 10.63, 'ACTIVE', 'https://images.unsplash.com/photo-1517524008697-84bbe3c3fd98?w=1200', 'https://images.unsplash.com/photo-1517524008697-84bbe3c3fd98?w=1200', 'Paket servis & ganti oli transmisi otomatis 7G-Tronic / 9G-Tronic garansi resmi ZF', 'Booking Servis', 'https://www.zf.com/indonesia/mbina', 'HEADER', 2, 'usr_superadmin'),
                ('ac_rotator_3', 'Banner Mandiri Q3 2026', 'BANNER', 'Bank Mandiri', 'Platinum', 10000000, 8200000, '2026-07-01', '2026-12-31', 50000, 5000, 12450, 1234, 9.91, 'ACTIVE', 'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=1200', 'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=1200', 'Cashback 10% SPBU, Bebas Iuran Tahunan, & Akses Airport Lounge', 'Ajukan Kartu Sekarang', 'https://www.bankmandiri.co.id/mbina', 'HEADER', 3, 'usr_superadmin'),
                ('ac_rotator_4', 'Sponsored Event MB Jamnas 2026', 'BANNER', 'BCA Prioritas', 'Platinum', 10000000, 5000000, '2026-08-01', '2026-12-31', 50000, 5000, 9800, 1020, 10.41, 'ACTIVE', 'https://images.unsplash.com/photo-1541354329998-f4d9a9f9297f?w=1200', 'https://images.unsplash.com/photo-1541354329998-f4d9a9f9297f?w=1200', 'Dukungan Penuh BCA Prioritas untuk Gathering & Jamnas MB INA 2026', 'Lihat Detail Event', 'https://www.bca.co.id/mbina', 'HEADER', 4, 'usr_superadmin');
            ");
        }

        $sPdo->exec("

            CREATE TABLE IF NOT EXISTS ad_performance (
                id VARCHAR(36) PRIMARY KEY,
                ad_id VARCHAR(36) NOT NULL,
                date DATE NOT NULL,
                impressions INT DEFAULT 0,
                clicks INT DEFAULT 0,
                reach INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS m8_transactions (
                id VARCHAR(36) PRIMARY KEY,
                transaction_number VARCHAR(50) NOT NULL,
                type VARCHAR(20) NOT NULL,
                category VARCHAR(100) NOT NULL,
                sub_category VARCHAR(100),
                amount INT NOT NULL,
                description TEXT,
                reference_type VARCHAR(50),
                reference_id VARCHAR(36),
                payment_method VARCHAR(20) DEFAULT 'TRANSFER',
                status VARCHAR(20) DEFAULT 'PENDING',
                transaction_date DATE NOT NULL,
                receipt_url VARCHAR(255),
                created_by VARCHAR(36) NOT NULL DEFAULT 'usr_superadmin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS m8_invoices (
                id VARCHAR(36) PRIMARY KEY,
                invoice_number VARCHAR(50) NOT NULL,
                type VARCHAR(20) NOT NULL,
                partner_name VARCHAR(255),
                amount INT NOT NULL,
                tax_amount INT DEFAULT 0,
                total_amount INT NOT NULL,
                status VARCHAR(20) DEFAULT 'DRAFT',
                due_date DATE,
                paid_at TIMESTAMP,
                notes TEXT,
                created_by VARCHAR(36) NOT NULL DEFAULT 'usr_superadmin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS m8_taxes (
                id VARCHAR(36) PRIMARY KEY,
                type VARCHAR(20) NOT NULL,
                name VARCHAR(100) NOT NULL,
                rate DECIMAL(5,2) NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                start_date DATE NOT NULL,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS m8_tax_reports (
                id VARCHAR(36) PRIMARY KEY,
                report_number VARCHAR(50) NOT NULL,
                period_month INT NOT NULL,
                period_year INT NOT NULL,
                tax_type VARCHAR(10) NOT NULL,
                total_revenue INT DEFAULT 0,
                total_tax INT DEFAULT 0,
                paid_amount INT DEFAULT 0,
                status VARCHAR(20) DEFAULT 'DRAFT',
                submitted_at TIMESTAMP,
                notes TEXT,
                created_by VARCHAR(36) NOT NULL DEFAULT 'usr_superadmin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS m8_financial_reports (
                id VARCHAR(36) PRIMARY KEY,
                report_number VARCHAR(50) NOT NULL,
                report_type VARCHAR(30) NOT NULL,
                period_start DATE NOT NULL,
                period_end DATE NOT NULL,
                summary TEXT,
                status VARCHAR(20) DEFAULT 'DRAFT',
                created_by VARCHAR(36) NOT NULL DEFAULT 'usr_superadmin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Seed endorse_packages if empty
        $pkgCount = $sPdo->query("SELECT COUNT(*) FROM endorse_packages")->fetchColumn();
        if ((int)$pkgCount === 0) {
            $sPdo->exec("
                INSERT INTO endorse_packages (id, name, price, duration, forum_posts, social_posts, banner, mention_event, is_active) VALUES
                ('pkg_bronze', 'BRONZE', 2500000, 1, 1, 0, FALSE, FALSE, TRUE),
                ('pkg_silver', 'SILVER', 5000000, 1, 3, 1, FALSE, FALSE, TRUE),
                ('pkg_gold', 'GOLD', 10000000, 1, 5, 3, TRUE, FALSE, TRUE),
                ('pkg_platinum', 'PLATINUM', 20000000, 1, 10, 5, TRUE, TRUE, TRUE);
            ");
        }

        // Seed endorse_contracts if empty
        $contractCount = $sPdo->query("SELECT COUNT(*) FROM endorse_contracts")->fetchColumn();
        if ((int)$contractCount === 0) {
            $sPdo->exec("
                INSERT INTO endorse_contracts (id, partner_name, contact_person, contact_email, contact_phone, package_id, contract_number, start_date, end_date, total_amount, payment_status, status, notes, created_by) VALUES
                ('ec_001', 'Shell Indonesia', 'Budi Santoso', 'budi@shell.co.id', '0217890123', 'pkg_platinum', 'EC-2026-001', '2026-01-01', '2026-12-31', 20000000, 'PAID', 'ACTIVE', 'Kontrak tahunan endorse oli Shell Helix', 'usr_superadmin'),
                ('ec_002', 'Pertamina Pelumas', 'Siti Rahayu', 'siti@pertamina.com', '0215678901', 'pkg_gold', 'EC-2026-002', '2026-03-01', '2026-08-31', 10000000, 'PAID', 'ACTIVE', 'Kontrak semi-annual Fastron Platinum', 'usr_superadmin'),
                ('ec_003', 'Auto2000', 'Andi Wijaya', 'andi@auto2000.com', '0212345678', 'pkg_silver', 'EC-2026-003', '2026-06-01', '2026-06-30', 5000000, 'UNPAID', 'DRAFT', 'Draft kontrak 1 bulan promo', 'usr_superadmin'),
                ('ec_004', 'Castrol Indonesia', 'Dewi Kusuma', 'dewi@castrol.com', '0219876543', 'pkg_bronze', 'EC-2026-004', '2025-12-01', '2025-12-31', 2500000, 'PAID', 'COMPLETED', 'Kontrak Desember 2025 telah selesai', 'usr_superadmin');
            ");
        }

        // Seed ad_campaigns if empty
        $campaignCount = $sPdo->query("SELECT COUNT(*) FROM ad_campaigns")->fetchColumn();
        if ((int)$campaignCount === 0) {
            $sPdo->exec("
                INSERT INTO ad_campaigns (id, name, type, partner_name, package_name, budget, spent, start_date, end_date, impressions_target, clicks_target, impressions_current, clicks_current, ctr, status, banner_url, image_url, description, cta_text, link, position, sort_order, created_by) VALUES
                ('ac_rotator_1', 'Banner Promo Ban Michelin Pilot Sport 5', 'BANNER', 'Michelin Indonesia', 'Platinum', 10000000, 4500000, '2026-08-01', '2027-08-01', 50000, 5000, 18400, 1920, 10.43, 'ACTIVE', 'https://images.unsplash.com/photo-1578844251758-2f71da64c96f?w=1200', 'https://images.unsplash.com/photo-1578844251758-2f71da64c96f?w=1200', 'Promo spesial ban high-performance Michelin Pilot Sport 5 diskon 15% khusus member MB INA', 'Beli Ban Michelin', 'https://www.michelin.co.id/mbina', 'HEADER', 1, 'usr_superadmin'),
                ('ac_rotator_2', 'Sponsored Post: Perawatan Transmisi 7G-Tronic', 'SPONSORED', 'ZF Aftermarket Indonesia', 'Platinum', 10000000, 3800000, '2026-08-01', '2027-08-01', 50000, 5000, 14200, 1510, 10.63, 'ACTIVE', 'https://images.unsplash.com/photo-1517524008697-84bbe3c3fd98?w=1200', 'https://images.unsplash.com/photo-1517524008697-84bbe3c3fd98?w=1200', 'Paket servis & ganti oli transmisi otomatis 7G-Tronic / 9G-Tronic garansi resmi ZF', 'Booking Servis', 'https://www.zf.com/indonesia/mbina', 'HEADER', 2, 'usr_superadmin'),
                ('ac_rotator_3', 'Banner Mandiri Q3 2026', 'BANNER', 'Bank Mandiri', 'Platinum', 10000000, 8200000, '2026-07-01', '2026-12-31', 50000, 5000, 12450, 1234, 9.91, 'ACTIVE', 'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=1200', 'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=1200', 'Cashback 10% SPBU, Bebas Iuran Tahunan, & Akses Airport Lounge', 'Ajukan Kartu Sekarang', 'https://www.bankmandiri.co.id/mbina', 'HEADER', 3, 'usr_superadmin'),
                ('ac_rotator_4', 'Sponsored Event MB Jamnas 2026', 'BANNER', 'BCA Prioritas', 'Platinum', 10000000, 5000000, '2026-08-01', '2026-12-31', 50000, 5000, 9800, 1020, 10.41, 'ACTIVE', 'https://images.unsplash.com/photo-1541354329998-f4d9a9f9297f?w=1200', 'https://images.unsplash.com/photo-1541354329998-f4d9a9f9297f?w=1200', 'Dukungan Penuh BCA Prioritas untuk Gathering & Jamnas MB INA 2026', 'Lihat Detail Event', 'https://www.bca.co.id/mbina', 'HEADER', 4, 'usr_superadmin');
            ");
        }

        // Seed m8_taxes if empty
        $taxCount = $sPdo->query("SELECT COUNT(*) FROM m8_taxes")->fetchColumn();
        if ((int)$taxCount === 0) {
            $sPdo->exec("
                INSERT INTO m8_taxes (id, type, name, rate, is_active, start_date, notes) VALUES
                ('tax_ppn', 'PPN', 'PPN 11%', 11.00, TRUE, '2022-04-01', 'PPN berlaku sejak 1 April 2022 (UU HPP)'),
                ('tax_pph23', 'PPH', 'PPh Pasal 23 (2%)', 2.00, TRUE, '2009-01-01', 'PPh 23 untuk jasa advertising/endorse'),
                ('tax_pph4', 'PPH', 'PPh Pasal 4(2) (10%)', 10.00, TRUE, '2009-01-01', 'PPh final untuk sewa properti');
            ");
        }

        // Seed m8_transactions if empty
        $txCount = $sPdo->query("SELECT COUNT(*) FROM m8_transactions")->fetchColumn();
        if ((int)$txCount === 0) {
            $sPdo->exec("
                INSERT INTO m8_transactions (id, transaction_number, type, category, amount, description, reference_type, payment_method, status, transaction_date, created_by) VALUES
                ('tx_001', 'TRX-2026-001', 'INCOME', 'Sponsorship', 37500000, 'Pendapatan sponsorship Shell & Pertamina Q1-Q3 2026', 'EVENT', 'TRANSFER', 'COMPLETED', '2026-08-01', 'usr_superadmin'),
                ('tx_002', 'TRX-2026-002', 'INCOME', 'Endorse', 35000000, 'Pendapatan endorse kontrak aktif 2026', 'ENDORSE', 'TRANSFER', 'COMPLETED', '2026-08-01', 'usr_superadmin'),
                ('tx_003', 'TRX-2026-003', 'INCOME', 'Tiket Event', 15000000, 'Pendapatan penjualan tiket gathering & roadtrip 2026', 'EVENT', 'TRANSFER', 'COMPLETED', '2026-08-01', 'usr_superadmin'),
                ('tx_004', 'TRX-2026-004', 'INCOME', 'Sewa Lapak', 4500000, 'Pendapatan sewa lapak e-commerce M7 2026', 'LAPAK', 'TRANSFER', 'COMPLETED', '2026-08-01', 'usr_superadmin'),
                ('tx_005', 'TRX-2026-005', 'INCOME', 'Iklan', 5000000, 'Pendapatan iklan digital (banner & sponsored post)', 'AD', 'TRANSFER', 'COMPLETED', '2026-08-01', 'usr_superadmin'),
                ('tx_006', 'TRX-2026-006', 'INCOME', 'Donasi', 8000000, 'Pendapatan donasi sukarela anggota & publik 2026', 'DONASI', 'TRANSFER', 'COMPLETED', '2026-08-01', 'usr_superadmin'),
                ('tx_007', 'TRX-2026-007', 'INCOME', 'Iuran Member', 12000000, 'Iuran keanggotaan tahunan 2026', 'MEMBER', 'TRANSFER', 'COMPLETED', '2026-08-01', 'usr_superadmin'),
                ('tx_008', 'TRX-2026-008', 'EXPENSE', 'Beban Operasional', 5000000, 'Biaya operasional kantor & server hosting', 'OPERATIONAL', 'TRANSFER', 'COMPLETED', '2026-08-01', 'usr_superadmin'),
                ('tx_009', 'TRX-2026-009', 'EXPENSE', 'Beban Administrasi', 2000000, 'ATK, kurir, dokumentasi & administrasi umum', 'ADMIN', 'TRANSFER', 'COMPLETED', '2026-08-01', 'usr_superadmin'),
                ('tx_010', 'TRX-2026-010', 'EXPENSE', 'Beban Event', 15000000, 'Biaya pelaksanaan gathering, roadtrip & acara 2026', 'EVENT', 'TRANSFER', 'COMPLETED', '2026-08-01', 'usr_superadmin');
            ");
        }
    } catch (Throwable $e) {}
}

function ensureM9Tables($pdo) {
    if (!$pdo) return;
    static $ensured = false;
    if ($ensured) return;
    $ensured = true;
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS report_snapshots (
                id VARCHAR(36) PRIMARY KEY DEFAULT (gen_random_uuid()::text),
                report_type VARCHAR(50) NOT NULL,
                snapshot_date DATE NOT NULL,
                period_start DATE NOT NULL,
                period_end DATE NOT NULL,
                data TEXT NOT NULL,
                created_by VARCHAR(36) NOT NULL DEFAULT 'usr_superadmin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS scheduled_reports (
                id VARCHAR(36) PRIMARY KEY DEFAULT (gen_random_uuid()::text),
                name VARCHAR(255) NOT NULL,
                report_type VARCHAR(50) NOT NULL,
                frequency VARCHAR(20) NOT NULL CHECK (frequency IN ('DAILY','WEEKLY','MONTHLY','QUARTERLY','YEARLY')),
                recipients TEXT NOT NULL,
                format VARCHAR(10) DEFAULT 'PDF' CHECK (format IN ('PDF','EXCEL','BOTH')),
                is_active BOOLEAN DEFAULT TRUE,
                last_sent_at TIMESTAMP,
                created_by VARCHAR(36) NOT NULL DEFAULT 'usr_superadmin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS dashboard_widgets (
                id VARCHAR(36) PRIMARY KEY DEFAULT (gen_random_uuid()::text),
                name VARCHAR(255) NOT NULL,
                type VARCHAR(50) NOT NULL,
                config TEXT NOT NULL,
                position INT DEFAULT 0,
                roles TEXT NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_by VARCHAR(36) NOT NULL DEFAULT 'usr_superadmin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS performance_targets (
                id VARCHAR(36) PRIMARY KEY DEFAULT (gen_random_uuid()::text),
                kpi_name VARCHAR(255) NOT NULL,
                category VARCHAR(100) NOT NULL,
                target_value INT NOT NULL,
                current_value INT DEFAULT 0,
                unit VARCHAR(20) DEFAULT 'UNIT',
                period_start DATE NOT NULL,
                period_end DATE NOT NULL,
                status VARCHAR(20) DEFAULT 'ON_TRACK' CHECK (status IN ('ON_TRACK','BEHIND','ACHIEVED')),
                notes TEXT,
                created_by VARCHAR(36) NOT NULL DEFAULT 'usr_superadmin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS activity_logs_summary (
                id VARCHAR(36) PRIMARY KEY DEFAULT (gen_random_uuid()::text),
                summary_date DATE NOT NULL UNIQUE,
                total_logins INT DEFAULT 0,
                total_registrations INT DEFAULT 0,
                total_events_created INT DEFAULT 0,
                total_forum_posts INT DEFAULT 0,
                total_transactions INT DEFAULT 0,
                data TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS audit_report_logs (
                id VARCHAR(36) PRIMARY KEY DEFAULT (gen_random_uuid()::text),
                report_id VARCHAR(36),
                action VARCHAR(20) NOT NULL CHECK (action IN ('GENERATED','EXPORTED','SENT','VIEWED')),
                user_id VARCHAR(36) NOT NULL DEFAULT 'usr_superadmin',
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");
    } catch (Throwable $e) {}
}
