<?php
require_once __DIR__ . '/../api.php';
$pdo = getSupabasePDO();
if (!$pdo) { echo "GAGAL konek!\n"; exit; }

echo "=== MIGRASI LENGKAP M2 SUPABASE ===\n\n";

// ============================================================
// 1. organization (view/alias ke org_profile - tabel utama)
// ============================================================
echo "--- 1. organization (tabel utama profil) ---\n";
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS organization (
            id VARCHAR(50) PRIMARY KEY DEFAULT 'org_001',
            name VARCHAR(200) NOT NULL DEFAULT 'Mercedes-Benz Club Indonesia',
            short_name VARCHAR(50) DEFAULT 'MB INA',
            abbreviation VARCHAR(20) DEFAULT 'MBCINA',
            logo_url TEXT,
            legal_status VARCHAR(100) DEFAULT 'Organisasi Resmi Terdaftar Kemenkumham',
            deed_number VARCHAR(100),
            sk_number VARCHAR(100),
            established_date DATE DEFAULT '2003-01-01',
            established_city VARCHAR(100) DEFAULT 'Jakarta',
            hq_city VARCHAR(100) DEFAULT 'Jakarta',
            hq_province VARCHAR(100) DEFAULT 'DKI Jakarta',
            hq_address TEXT,
            phone VARCHAR(30),
            email VARCHAR(100) DEFAULT 'info@mbina.or.id',
            website VARCHAR(100) DEFAULT 'https://mbina.or.id',
            instagram VARCHAR(100) DEFAULT '@mbclub_indonesia',
            facebook VARCHAR(100),
            youtube VARCHAR(100),
            twitter VARCHAR(100),
            total_clubs INT DEFAULT 110,
            total_members INT DEFAULT 12544,
            total_provinces INT DEFAULT 34,
            about_short TEXT,
            about_full TEXT,
            updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        )
    ");
    $pdo->exec("
        INSERT INTO organization (id, name, short_name, abbreviation, legal_status, established_date, established_city, hq_city, hq_province, email, website, instagram, total_clubs, total_members, total_provinces, about_short, about_full)
        VALUES (
            'org_001',
            'Mercedes-Benz Club Indonesia',
            'MB INA',
            'MBCINA',
            'Organisasi Resmi Terdaftar Kemenkumham RI',
            '2003-01-01',
            'Jakarta',
            'Jakarta',
            'DKI Jakarta',
            'info@mbina.or.id',
            'https://mbina.or.id',
            '@mbclub_indonesia',
            110,
            12544,
            34,
            'MB INA — Federasi komunitas kendaraan Mercedes-Benz terbesar di Indonesia, berdiri 2003.',
            'MB INA (Mercedes-Benz Club Indonesia) adalah wadah resmi dan profesional bagi seluruh komunitas kendaraan Mercedes-Benz di Indonesia. Didirikan pada tahun 2003, organisasi ini berperan sebagai federasi yang mengintegrasikan seluruh club dan chapter Mercedes-Benz dari Sabang sampai Merauke dalam satu platform yang solid, modern, dan berdaya saing tinggi. Dengan lebih dari 110 klub terdaftar dan 12.544 anggota aktif di 34 provinsi, MB INA menjadi rumah besar bagi para pecinta dan pemilik kendaraan Mercedes-Benz di seluruh Nusantara.'
        )
        ON CONFLICT (id) DO UPDATE SET
            total_clubs = EXCLUDED.total_clubs,
            total_members = EXCLUDED.total_members,
            updated_at = NOW()
    ");
    echo "  ✅ organization — SELESAI\n";
} catch (Exception $e) { echo "  ❌ " . $e->getMessage() . "\n"; }


// ============================================================
// 2. advisory_board (Dewan Penasehat)
// ============================================================
echo "\n--- 2. advisory_board (Dewan Penasehat) ---\n";
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS advisory_board (
            id VARCHAR(50) PRIMARY KEY,
            organization_id VARCHAR(50) DEFAULT 'org_001',
            name VARCHAR(150) NOT NULL,
            title VARCHAR(100),
            institution VARCHAR(150),
            expertise VARCHAR(200),
            photo_url TEXT,
            period_start DATE DEFAULT '2024-01-01',
            period_end DATE DEFAULT '2027-12-31',
            is_active BOOLEAN DEFAULT TRUE,
            sort_order INT DEFAULT 0,
            bio TEXT,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        )
    ");
    $advisors = [
        ['adv_001', 'Dewan Penasehat Kehormatan', 'Ketua Umum Dewan Penasehat', 'Majelis Nasional MB INA', 'Hukum & Kelembagaan', 1],
        ['adv_002', 'Ir. H. Bambang Soesatyo, S.E., M.B.A.', 'Penasihat Senior Bidang Hubungan Pemerintah', 'DPR RI', 'Hubungan Pemerintah & Kebijakan Publik', 2],
        ['adv_003', 'Prof. Dr. H. Ahmad Syafiq, M.Sc.', 'Penasihat Bidang Kesehatan & Sosial', 'Universitas Indonesia', 'Kesehatan Masyarakat & Sosial', 3],
        ['adv_004', 'Dr. Ir. H. Taufik Kurniawan, M.M.', 'Penasihat Bidang Ekonomi & Bisnis', 'Kamar Dagang Indonesia', 'Ekonomi, Bisnis & Investasi', 4],
    ];
    $stmt = $pdo->prepare("INSERT INTO advisory_board (id, organization_id, name, title, institution, expertise, sort_order) VALUES (:id, 'org_001', :name, :title, :inst, :exp, :ord) ON CONFLICT (id) DO NOTHING");
    foreach ($advisors as $a) {
        $stmt->execute([':id' => $a[0], ':name' => $a[1], ':title' => $a[2], ':inst' => $a[3], ':exp' => $a[4], ':ord' => $a[5]]);
        echo "  ✅ {$a[1]}\n";
    }
} catch (Exception $e) { echo "  ❌ " . $e->getMessage() . "\n"; }


// ============================================================
// 3. honor_council (Dewan Kehormatan)
// ============================================================
echo "\n--- 3. honor_council (Dewan Kehormatan) ---\n";
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS honor_council (
            id VARCHAR(50) PRIMARY KEY,
            organization_id VARCHAR(50) DEFAULT 'org_001',
            name VARCHAR(150) NOT NULL,
            jabatan VARCHAR(100),
            periode VARCHAR(50),
            period_start DATE,
            period_end DATE,
            is_active BOOLEAN DEFAULT FALSE,
            sort_order INT DEFAULT 0,
            photo_url TEXT,
            note TEXT,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        )
    ");
    $honored = [
        ['hon_001', 'Ridwan Pohan',                      'Presiden MB INA ke-1',  '2004–2006', '2004-01-01', '2006-12-31', 1],
        ['hon_002', 'Raditya G. Wardhana',               'Presiden MB INA ke-2',  '2006–2009', '2006-01-01', '2009-12-31', 2],
        ['hon_003', 'Aditya Adinatha',                   'Presiden MB INA ke-3',  '2009–2011', '2009-01-01', '2011-12-31', 3],
        ['hon_004', 'Rheino P. Soufyan',                 'Presiden MB INA ke-4',  '2011–2013', '2011-01-01', '2013-12-31', 4],
        ['hon_005', 'Doddy Moedjito',                    'Presiden MB INA ke-5',  '2013–2015', '2013-01-01', '2015-12-31', 5],
        ['hon_006', 'Deddy Rachmadi',                    'Presiden MB INA ke-6',  '2015–2017', '2015-01-01', '2017-12-31', 6],
        ['hon_007', 'Idham Syewket',                     'Presiden MB INA ke-7',  '2017–2019', '2017-01-01', '2019-12-31', 7],
        ['hon_008', 'Mahar Corleone',                    'Presiden MB INA ke-8',  '2019–2021', '2019-01-01', '2021-12-31', 8],
        ['hon_009', 'Cecep Fajar',                       'Presiden MB INA ke-9',  '2021–2023', '2021-01-01', '2023-12-31', 9],
        ['hon_010', 'I Made Yoga Mahardika',             'Presiden MB INA ke-10', '2023–2025', '2023-01-01', '2025-12-31', 10],
    ];
    $stmt = $pdo->prepare("INSERT INTO honor_council (id, organization_id, name, jabatan, periode, period_start, period_end, is_active, sort_order) VALUES (:id, 'org_001', :name, :jab, :per, :ps, :pe, FALSE, :ord) ON CONFLICT (id) DO NOTHING");
    foreach ($honored as $h) {
        $stmt->execute([':id'=>$h[0], ':name'=>$h[1], ':jab'=>$h[2], ':per'=>$h[3], ':ps'=>$h[4], ':pe'=>$h[5], ':ord'=>$h[6]]);
        echo "  ✅ {$h[1]} ({$h[3]})\n";
    }
} catch (Exception $e) { echo "  ❌ " . $e->getMessage() . "\n"; }


// ============================================================
// 4. organization_structure (Kepengurusan Lengkap)
// ============================================================
echo "\n--- 4. organization_structure (Pengurus Pusat Lengkap) ---\n";
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS organization_structure (
            id VARCHAR(50) PRIMARY KEY,
            organization_id VARCHAR(50) DEFAULT 'org_001',
            governance_period_id VARCHAR(50),
            name VARCHAR(150) NOT NULL,
            jabatan VARCHAR(150) NOT NULL,
            department VARCHAR(100),
            level VARCHAR(30) DEFAULT 'PUSAT',
            sort_order INT DEFAULT 0,
            photo_url TEXT,
            phone VARCHAR(30),
            email VARCHAR(100),
            city VARCHAR(100),
            province VARCHAR(100),
            is_active BOOLEAN DEFAULT TRUE,
            bio TEXT,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        )
    ");
    $pengurus = [
        // PIMPINAN INTI
        ['ostr_p01', 'per_2025_2027', 'Dr. Rochady Hendra Setya Wibawa, Sp.OG., M.Kes., S.Kom.', 'Presiden MB INA',            'Pimpinan',                 'PUSAT',   1,  'Kota Bandung',    'Jawa Barat'],
        ['ostr_p02', 'per_2025_2027', 'Mukhwan Hariri',                                            'Sekretaris Jenderal',        'Sekretariat',              'PUSAT',   2,  'Bandung',         'Jawa Barat'],
        ['ostr_p03', 'per_2025_2027', 'Hermawan Ariyanto',                                         'Bendahara Umum',             'Keuangan',                 'PUSAT',   3,  'Surabaya',        'Jawa Timur'],
        // WAKIL
        ['ostr_p04', 'per_2025_2027', 'Derist Touriano',                                           'Wakil Presiden Bidang IT & Digital', 'Teknologi Informasi', 'PUSAT',  4, 'Jambi',           'Jambi'],
        ['ostr_p05', 'per_2025_2027', 'Ir. Raymond Sanjaya',                                       'Wakil Sekretaris Jenderal', 'Sekretariat',              'PUSAT',   5,  'Bandung',         'Jawa Barat'],
        ['ostr_p06', 'per_2025_2027', 'Dra. Endang Rahayu',                                        'Wakil Bendahara',           'Keuangan',                 'PUSAT',   6,  'Surabaya',        'Jawa Timur'],
        // DEPARTEMEN KEANGGOTAAN
        ['ostr_p07', 'per_2025_2027', 'Andi Pratama',                                              'Ketua Dept. Keanggotaan',   'Keanggotaan & Organisasi', 'PUSAT',   7,  'Jakarta Selatan', 'DKI Jakarta'],
        ['ostr_p08', 'per_2025_2027', 'Siti Rahayu',                                               'Anggota Dept. Keanggotaan', 'Keanggotaan & Organisasi', 'PUSAT',   8,  'Surabaya',        'Jawa Timur'],
        // DEPARTEMEN KEGIATAN & EVENT
        ['ostr_p09', 'per_2025_2027', 'Budi Santoso',                                              'Ketua Dept. Kegiatan & Event',  'Kegiatan & Touring',   'PUSAT',   9,  'Bandung',         'Jawa Barat'],
        ['ostr_p10', 'per_2025_2027', 'Denny Kurniawan',                                           'Ketua Dept. Hubungan Publik',   'Humas & Media',        'PUSAT',   10, 'Jakarta Barat',   'DKI Jakarta'],
        // DEPARTEMEN TEKNIS
        ['ostr_p11', 'per_2025_2027', 'Andi Wijaya',                                               'Ketua Dept. Teknis & Bengkel',  'Teknis & Otomotif',    'PUSAT',   11, 'Medan',           'Sumatera Utara'],
        // KOORDINATOR WILAYAH
        ['ostr_p12', 'per_2025_2027', 'Koordinator Wilayah Sumatera',                              'Koordinator Wilayah Sumatera',  'Koordinasi Wilayah',   'WILAYAH', 12, 'Medan',           'Sumatera Utara'],
        ['ostr_p13', 'per_2025_2027', 'Koordinator Wilayah Jawa & Bali',                           'Koordinator Wilayah Jawa & Bali','Koordinasi Wilayah',  'WILAYAH', 13, 'Surabaya',        'Jawa Timur'],
        ['ostr_p14', 'per_2025_2027', 'Koordinator Wilayah Kalimantan',                            'Koordinator Wilayah Kalimantan','Koordinasi Wilayah',   'WILAYAH', 14, 'Balikpapan',      'Kalimantan Timur'],
        ['ostr_p15', 'per_2025_2027', 'Koordinator Wilayah Sulawesi & Indonesia Timur',            'Koordinator Wilayah Sul. & Indonesia Timur','Koordinasi Wilayah','WILAYAH',15,'Makassar','Sulawesi Selatan'],
    ];
    $stmt = $pdo->prepare("INSERT INTO organization_structure (id, organization_id, governance_period_id, name, jabatan, department, level, sort_order, city, province, is_active) VALUES (:id, 'org_001', :gp, :name, :jab, :dept, :lvl, :ord, :city, :prov, TRUE) ON CONFLICT (id) DO UPDATE SET name=EXCLUDED.name, jabatan=EXCLUDED.jabatan");
    foreach ($pengurus as $p) {
        $stmt->execute([':id'=>$p[0], ':gp'=>$p[1], ':name'=>$p[2], ':jab'=>$p[3], ':dept'=>$p[4], ':lvl'=>$p[5], ':ord'=>$p[6], ':city'=>$p[7], ':prov'=>$p[8]]);
        echo "  ✅ {$p[2]} — {$p[3]}\n";
    }
} catch (Exception $e) { echo "  ❌ " . $e->getMessage() . "\n"; }


// ============================================================
// 5. governance_periods (Periode Kepengurusan)
// ============================================================
echo "\n--- 5. governance_periods (Periode Kepengurusan) ---\n";
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS governance_periods (
            id VARCHAR(50) PRIMARY KEY,
            organization_id VARCHAR(50) DEFAULT 'org_001',
            year_start INT NOT NULL,
            year_end INT NOT NULL,
            label VARCHAR(50),
            president_name VARCHAR(150),
            theme VARCHAR(200),
            is_current BOOLEAN DEFAULT FALSE,
            congress_location VARCHAR(100),
            congress_date DATE,
            total_clubs_at_period INT DEFAULT 0,
            total_members_at_period INT DEFAULT 0,
            key_achievements TEXT,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        )
    ");
    $periods = [
        ['per_2004_2006', 2004, 2006, '2004–2006', 'Ridwan Pohan',                                           'Fondasi & Pendirian Federasi',                FALSE, 'Jakarta', 110,   3200,  'Pembentukan federasi nasional MB Club INA, penyusunan AD/ART pertama'],
        ['per_2006_2009', 2006, 2009, '2006–2009', 'Raditya G. Wardhana',                                    'Ekspansi & Konsolidasi',                      FALSE, 'Bandung', NULL,   NULL,  'Ekspansi ke 5 kota baru, penyelenggaraan Jamnas pertama'],
        ['per_2009_2011', 2009, 2011, '2009–2011', 'Aditya Adinatha',                                        'Standarisasi & Modernisasi',                  FALSE, 'Surabaya',NULL,   NULL,  'Standarisasi SOP dan tata kelola chapter di seluruh Indonesia'],
        ['per_2011_2013', 2011, 2013, '2011–2013', 'Rheino P. Soufyan',                                      'Soliditas & Pertumbuhan',                     FALSE, 'Bali',    NULL,   NULL,  'Penambahan 20+ klub baru, penguatan sistem keanggotaan'],
        ['per_2013_2015', 2013, 2015, '2013–2015', 'Doddy Moedjito',                                         'Profesionalisme & Digitalisasi Awal',         FALSE, 'Medan',   NULL,   NULL,  'Peluncuran website resmi MB INA pertama, digitalisasi database anggota'],
        ['per_2015_2017', 2015, 2017, '2015–2017', 'Deddy Rachmadi',                                         'Hubungan Internasional & Branding',           FALSE, 'Makassar',NULL,   NULL,  'Terjalinnya hubungan dengan komunitas MB internasional, rebranding logo'],
        ['per_2017_2019', 2017, 2019, '2017–2019', 'Idham Syewket',                                          'Inovasi & Teknologi',                         FALSE, 'Yogyakarta',NULL, NULL,  'Digitalisasi pendaftaran member, sistem manajemen event berbasis app'],
        ['per_2019_2021', 2019, 2021, '2019–2021', 'Mahar Corleone',                                         'Ketahanan di Era Pandemi',                    FALSE, 'Semarang',NULL,   NULL,  'Adaptasi kegiatan virtual, peluncuran program sosial MBINA Peduli'],
        ['per_2021_2023', 2021, 2023, '2021–2023', 'Cecep Fajar',                                            'Kebangkitan & Transformasi Digital',          FALSE, 'Balikpapan',NULL, NULL,  'Revitalisasi Jamnas pasca pandemi, peluncuran portal digital MBINA'],
        ['per_2023_2025', 2023, 2025, '2023–2025', 'I Made Yoga Mahardika',                                  'Konsolidasi Nasional & Inklusivitas',         FALSE, 'Denpasar',NULL,   NULL,  'Integrasi 34 provinsi, peningkatan anggota ke 12.000+ member'],
        ['per_2025_2027', 2025, 2027, '2025–2027', 'Dr. Rochady Hendra Setya Wibawa, Sp.OG., M.Kes., S.Kom.','Platform Digital Nasional MB INA 2.0',       TRUE,  'Bandung', 110,   12544, 'Peluncuran Platform Digital MBCINA (Portal Admin M1-M5), integrasi Supabase Cloud, sistem member ID nasional'],
    ];
    $stmt = $pdo->prepare("INSERT INTO governance_periods (id, organization_id, year_start, year_end, label, president_name, theme, is_current, congress_location, total_clubs_at_period, total_members_at_period, key_achievements) VALUES (:id, 'org_001', :ys, :ye, :lbl, :pres, :theme, :curr, :cong, :clubs, :members, :ach) ON CONFLICT (id) DO UPDATE SET is_current=EXCLUDED.is_current, theme=EXCLUDED.theme");
    foreach ($periods as $p) {
        $stmt->execute([':id'=>$p[0], ':ys'=>$p[1], ':ye'=>$p[2], ':lbl'=>$p[3], ':pres'=>$p[4], ':theme'=>$p[5], ':curr'=>$p[6] ? 'TRUE' : 'FALSE', ':cong'=>$p[7], ':clubs'=>$p[8]??0, ':members'=>$p[9]??0, ':ach'=>$p[10]]);
        $curr = $p[6] ? ' 👑 AKTIF' : '';
        echo "  ✅ {$p[3]} — {$p[4]}{$curr}\n";
    }
} catch (Exception $e) { echo "  ❌ " . $e->getMessage() . "\n"; }


// ============================================================
// RINGKASAN AKHIR
// ============================================================
echo "\n=== VERIFIKASI AKHIR ===\n";
$tables = ['organization', 'advisory_board', 'honor_council', 'organization_structure', 'governance_periods'];
foreach ($tables as $tbl) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM $tbl")->fetchColumn();
        echo "✅ $tbl — {$count} rows\n";
    } catch (Exception $e) { echo "❌ $tbl — " . $e->getMessage() . "\n"; }
}
echo "\n=== SELESAI ===\n";
