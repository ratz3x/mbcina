<?php
require_once __DIR__ . '/../api.php';
$pdo = getSupabasePDO();

if (!$pdo) {
    echo "FAILED TO CONNECT TO SUPABASE CLOUD!\n";
    exit;
}

echo "=== MIGRATING M5 FORUM & INTERACTION MODULE SCHEMA (10 TABLES) ===\n";

try {
    // 1. forum_categories
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS forum_categories (
            id VARCHAR(50) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            icon VARCHAR(50) NOT NULL,
            description TEXT,
            thread_count INT DEFAULT 0,
            post_count INT DEFAULT 0,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "✔ Table forum_categories created/ready.\n";

    // 2. forum_threads
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS forum_threads (
            id VARCHAR(50) PRIMARY KEY,
            category_id VARCHAR(50) REFERENCES forum_categories(id),
            title VARCHAR(255) NOT NULL,
            author_id VARCHAR(50),
            author_name VARCHAR(100),
            author_username VARCHAR(50),
            author_avatar VARCHAR(255),
            tags VARCHAR(255),
            content TEXT NOT NULL,
            replies_count INT DEFAULT 0,
            views_count INT DEFAULT 0,
            last_post_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(20) DEFAULT 'ACTIVE',
            is_pinned BOOLEAN DEFAULT FALSE,
            is_locked BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "✔ Table forum_threads created/ready.\n";

    // 3. forum_replies
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS forum_replies (
            id VARCHAR(50) PRIMARY KEY,
            thread_id VARCHAR(50) REFERENCES forum_threads(id) ON DELETE CASCADE,
            author_id VARCHAR(50),
            author_name VARCHAR(100),
            author_username VARCHAR(50),
            author_avatar VARCHAR(255),
            content TEXT NOT NULL,
            quote_id VARCHAR(50),
            likes_count INT DEFAULT 0,
            dislikes_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "✔ Table forum_replies created/ready.\n";

    // 4. forum_likes
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS forum_likes (
            id VARCHAR(50) PRIMARY KEY,
            target_type VARCHAR(20) NOT NULL, -- 'THREAD' or 'REPLY'
            target_id VARCHAR(50) NOT NULL,
            user_id VARCHAR(50) NOT NULL,
            vote_type VARCHAR(10) DEFAULT 'LIKE', -- 'LIKE' or 'DISLIKE'
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(target_type, target_id, user_id)
        );
    ");
    echo "✔ Table forum_likes created/ready.\n";

    // 5. forum_reports
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS forum_reports (
            id VARCHAR(50) PRIMARY KEY,
            thread_id VARCHAR(50) REFERENCES forum_threads(id) ON DELETE CASCADE,
            reply_id VARCHAR(50),
            reporter_id VARCHAR(50),
            reporter_name VARCHAR(100),
            reason VARCHAR(100) NOT NULL,
            notes TEXT,
            status VARCHAR(30) DEFAULT 'PENDING', -- 'PENDING', 'REVIEW', 'RESOLVED', 'DISMISSED'
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "✔ Table forum_reports created/ready.\n";

    // 6. broadcasts
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS broadcasts (
            id VARCHAR(50) PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            target_type VARCHAR(50) DEFAULT 'ALL', -- 'ALL', 'TIER', 'CLUB', 'REGION'
            target_value VARCHAR(100),
            scheduled_at TIMESTAMP NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(30) DEFAULT 'SENT', -- 'DRAFT', 'SCHEDULED', 'SENT'
            author_id VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "✔ Table broadcasts created/ready.\n";

    // 7. broadcast_targets
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS broadcast_targets (
            id VARCHAR(50) PRIMARY KEY,
            broadcast_id VARCHAR(50) REFERENCES broadcasts(id) ON DELETE CASCADE,
            user_id VARCHAR(50),
            status VARCHAR(20) DEFAULT 'DELIVERED',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "✔ Table broadcast_targets created/ready.\n";

    // 8. broadcast_stats
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS broadcast_stats (
            id VARCHAR(50) PRIMARY KEY,
            broadcast_id VARCHAR(50) REFERENCES broadcasts(id) ON DELETE CASCADE,
            total_sent INT DEFAULT 0,
            total_views INT DEFAULT 0,
            total_clicks INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "✔ Table broadcast_stats created/ready.\n";

    // 9. moderation_actions
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS moderation_actions (
            id VARCHAR(50) PRIMARY KEY,
            target_user_id VARCHAR(50),
            target_user_name VARCHAR(100),
            action_type VARCHAR(50) NOT NULL, -- 'WARN', 'MUTE', 'BLOCK', 'EDIT_POST', 'DELETE_POST'
            reason VARCHAR(255) NOT NULL,
            notes TEXT,
            moderator_id VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "✔ Table moderation_actions created/ready.\n";

    // 10. forum_rules
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS forum_rules (
            id VARCHAR(50) PRIMARY KEY,
            rule_code VARCHAR(50) UNIQUE NOT NULL,
            title VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            sort_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "✔ Table forum_rules created/ready.\n";

    // SEED INITIAL CATEGORIES
    $catStmt = $pdo->prepare("
        INSERT INTO forum_categories (id, name, icon, description, thread_count, post_count, sort_order)
        VALUES 
        ('cat_umum', 'Umum', '🚗', 'Diskusi umum seputar Mercedes-Benz', 45, 234, 1),
        ('cat_teknis', 'Teknis', '🔧', 'Perawatan, modifikasi, dan tips teknis', 32, 156, 2),
        ('cat_komunitas', 'Komunitas', '🏍️', 'Kegiatan klub dan komunitas', 28, 89, 3),
        ('cat_galeri', 'Galeri', '📸', 'Foto dan video member', 15, 67, 4),
        ('cat_market', 'Marketplace', '🛒', 'Jual beli parts dan aksesoris', 23, 112, 5),
        ('cat_announcement', 'Pengumuman', '📢', 'Pengumuman resmi MB INA', 12, 45, 6)
        ON CONFLICT (id) DO UPDATE SET 
            name = EXCLUDED.name,
            icon = EXCLUDED.icon,
            description = EXCLUDED.description;
    ");
    $catStmt->execute();
    echo "✔ Categories seeded.\n";

    // SEED INITIAL THREADS
    $threadStmt = $pdo->prepare("
        INSERT INTO forum_threads (id, category_id, title, author_id, author_name, author_username, author_avatar, tags, content, replies_count, views_count, status, is_pinned)
        VALUES 
        ('th_001', 'cat_teknis', 'Tips Perawatan W124 untuk Pemula', 'usr_001', 'Andi Pratama', 'andi_p', 'AP', '#W124, #Perawatan, #Tips', 'Halo semua, saya ingin berbagi tips perawatan W124 khususnya mesin M111 & M104 agar tetap prima...', 12, 234, 'ACTIVE', true),
        ('th_002', 'cat_komunitas', 'Touring Regional Bali & Jawa Timur 2026', 'usr_002', 'Budi Santoso', 'budi_s', 'BS', '#Touring, #Bali, #Jamnas', 'Rencana konvoi dan registrasi touring bersama seluruh chapter Bali dan Jawa Timur...', 45, 156, 'ACTIVE', true),
        ('th_003', 'cat_market', 'WTS: Velg AMG Original 18 Inch RARE Spec', 'usr_003', 'Siti Rahayu', 'siti_r', 'SR', '#AMG, #Velg, #Market', 'Jual Velg AMG Original Ring 18 fitment cocok untuk W211 / W204. Kondisi mulus cat ori...', 5, 89, 'ACTIVE', false),
        ('th_004', 'cat_teknis', 'ECU Remap & Performance Tuning W211 E-Class', 'usr_004', 'Rizky Febrian', 'rizky_f', 'RF', '#ECU, #W211, #Tuning', 'Sharing pengalaman remap Stage 1 untuk Mercedes W211 E200 Kompressor...', 32, 310, 'ACTIVE', false),
        ('th_005', 'cat_umum', 'Persiapan MBCI Anniversary & Gala Dinner 2026', 'usr_001', 'Andi Pratama', 'andi_p', 'AP', '#Anniversary, #Gala', 'Diskusi seputar susunan acara anniversary MB INA yang ke-22...', 28, 412, 'ACTIVE', false)
        ON CONFLICT (id) DO NOTHING;
    ");
    $threadStmt->execute();
    echo "✔ Threads seeded.\n";

    // SEED INITIAL REPLIES
    $replyStmt = $pdo->prepare("
        INSERT INTO forum_replies (id, thread_id, author_id, author_name, author_username, author_avatar, content, likes_count)
        VALUES 
        ('rep_101', 'th_001', 'usr_002', 'Budi Santoso', 'budi_s', 'BS', 'Terima kasih tipsnya bro! Sangat bermanfaat untuk pemilik W124 pemula.', 12),
        ('rep_102', 'th_001', 'usr_003', 'Siti Rahayu', 'siti_r', 'SR', 'Saya setuju dengan tips cek kelistrikan & kabel bodi tua. Wajib rutin diperiksa!', 8),
        ('rep_103', 'th_002', 'usr_001', 'Andi Pratama', 'andi_p', 'AP', 'Mantap! Rombongan Jabodetabek siap join untuk touring Bali!', 15)
        ON CONFLICT (id) DO NOTHING;
    ");
    $replyStmt->execute();
    echo "✔ Replies seeded.\n";

    // SEED INITIAL BROADCAST
    $bcastStmt = $pdo->prepare("
        INSERT INTO broadcasts (id, title, content, target_type, target_value, sent_at, status, author_id)
        VALUES 
        ('bc_001', 'Pengumuman Jambore Nasional ke-19 MB INA', 'Diberitahukan kepada seluruh anggota MB INA bahwa Jambore Nasional ke-19 akan diselenggarakan pada bulan November 2026...', 'ALL', 'Semua Member', NOW(), 'SENT', 'usr_001')
        ON CONFLICT (id) DO NOTHING;
    ");
    $bcastStmt->execute();

    $bstatStmt = $pdo->prepare("
        INSERT INTO broadcast_stats (id, broadcast_id, total_sent, total_views, total_clicks)
        VALUES 
        ('bcs_001', 'bc_001', 1234, 890, 234)
        ON CONFLICT (id) DO NOTHING;
    ");
    $bstatStmt->execute();
    echo "✔ Broadcast & Stats seeded.\n";

    // SEED INITIAL REPORTS
    $repPostStmt = $pdo->prepare("
        INSERT INTO forum_reports (id, thread_id, reply_id, reporter_id, reporter_name, reason, notes, status)
        VALUES 
        ('rep_001', 'th_003', NULL, 'usr_002', 'Budi Santoso', 'Spam', 'Laporan indikasi double posting tawaran velg.', 'PENDING'),
        ('rep_002', 'th_001', 'rep_102', 'usr_004', 'Rizky Febrian', 'Konten Tidak Pantas', 'Tolong periksa komentar yang mencurigakan.', 'REVIEW')
        ON CONFLICT (id) DO NOTHING;
    ");
    $repPostStmt->execute();
    echo "✔ Reports seeded.\n";

    // SEED INITIAL RULES
    $ruleStmt = $pdo->prepare("
        INSERT INTO forum_rules (id, rule_code, title, description, sort_order)
        VALUES 
        ('rule_1', 'NO_SPAM', 'No Spam', 'Dilarang memposting konten spam, promosi berulang, atau bot messaging.', 1),
        ('rule_2', 'NO_HATE_SPEECH', 'No Hate Speech', 'Dilarang menggunakan ujaran kebencian, SARA, dan kalimat yang memicu konfrontasi.', 2),
        ('rule_3', 'NO_PORNOGRAPHY', 'No Pornography', 'Dilarang membagikan materi berunsur pornografi dan konten tidak pantas.', 3),
        ('rule_4', 'NO_HOAX', 'No Hoax', 'Dilarang menyebarkan berita bohong, misinformasi, atau klaim tanpa verifikasi.', 4),
        ('rule_5', 'NO_PERSONAL_ATTACK', 'No Personal Attack', 'Dilarang menyerang pribadi anggota lain atau mencemarkan nama baik.', 5)
        ON CONFLICT (id) DO UPDATE SET title = EXCLUDED.title, description = EXCLUDED.description;
    ");
    $ruleStmt->execute();
    echo "✔ Rules seeded.\n";

    echo "\n=== M5 FORUM MODULE MIGRATION & SEEDING COMPLETED SUCCESSFULLY! ===\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
