<?php
require_once __DIR__ . '/../api.php';
$pdo = getSupabasePDO();
if (!$pdo) { echo "GAGAL konek!\n"; exit; }

echo "=== SEEDER M2 — KOLOM SESUAI SCHEMA AKTUAL SUPABASE ===\n\n";

// ============================================================
// 1. organization — update data yg sudah ada (id: org_001)
//    Kolom: id, name, short_name, alias, tagline, founded_date, founded_place,
//           status, website, email, phone, logo_url, banner_url, description
// ============================================================
echo "--- 1. organization (UPDATE) ---\n";
try {
    $stmt = $pdo->prepare("
        UPDATE organization SET
            name          = 'Mercedes-Benz Club Indonesia',
            short_name    = 'MB Club INA',
            alias         = 'MBINA',
            tagline       = '\"Bersama Satu Bintang\"',
            founded_date  = '2003-01-01',
            founded_place = 'Jakarta',
            status        = 'ACTIVE',
            website       = 'https://mbina.or.id',
            email         = 'info@mbina.or.id',
            phone         = '+62-21-7890123',
            logo_url      = 'assets/mb_badge.jpg',
            banner_url    = 'assets/mb_hero.jpg',
            description   = 'MB INA (Mercedes-Benz Club Indonesia) adalah federasi resmi yang menaungi seluruh komunitas kendaraan Mercedes-Benz di Indonesia. Didirikan sejak 2003, beranggotakan lebih dari 12.544 member aktif dari 110 klub di 34 provinsi se-Nusantara.',
            updated_at    = NOW()
        WHERE id = 'org_001'
    ");
    $stmt->execute();
    echo "  ✅ organization org_001 diupdate\n";
} catch (Exception $e) { echo "  ❌ " . $e->getMessage() . "\n"; }


// ============================================================
// 2. advisory_board — Kolom aktual: id, organization_id, governance_period_id, name, position, club_origin, sort_order
//    Sudah ada 12 rows, cek & tambah yang kurang
// ============================================================
echo "\n--- 2. advisory_board (CEK & TAMBAH) ---\n";
try {
    $existing = $pdo->query("SELECT id, name, position FROM advisory_board ORDER BY sort_order")->fetchAll();
    echo "  Data ada: " . count($existing) . " rows\n";
    foreach ($existing as $r) echo "  ✅ [{$r['id']}] {$r['name']} — {$r['position']}\n";

    // Tambah yang belum ada (cek berdasarkan nama)
    $existingNames = array_column($existing, 'name');
    $toAdd = [
        ['ab_013', 'gp_011', 'Ridwan Pohan',        'Penasihat Senior (Mantan Presiden ke-1)',  NULL,   13],
        ['ab_014', 'gp_011', 'Aditya Adinatha',     'Penasihat Senior (Mantan Presiden ke-3)',  NULL,   14],
        ['ab_015', 'gp_011', 'Cecep Fajar',         'Penasihat Senior (Mantan Presiden ke-9)',  NULL,   15],
        ['ab_016', 'gp_011', 'I Made Yoga Mahardika','Penasihat Senior (Mantan Presiden ke-10)',NULL,   16],
    ];
    $stmt = $pdo->prepare("INSERT INTO advisory_board (id, organization_id, governance_period_id, name, position, club_origin, sort_order) VALUES (:id, 'org_001', :gp, :name, :pos, :club, :ord) ON CONFLICT (id) DO NOTHING");
    foreach ($toAdd as $a) {
        if (!in_array($a[2], $existingNames)) {
            $stmt->execute([':id'=>$a[0], ':gp'=>$a[1], ':name'=>$a[2], ':pos'=>$a[3], ':club'=>$a[4], ':ord'=>$a[5]]);
            echo "  ➕ Ditambah: {$a[2]}\n";
        }
    }
} catch (Exception $e) { echo "  ❌ " . $e->getMessage() . "\n"; }


// ============================================================
// 3. honor_council — Kolom aktual: id, organization_id, governance_period_id, name, position, sort_order
//    Ada 3 rows, perlu ditambah mantan presiden
// ============================================================
echo "\n--- 3. honor_council (LENGKAPI) ---\n";
try {
    $existing = $pdo->query("SELECT id, name FROM honor_council")->fetchAll();
    $existingNames = array_column($existing, 'name');
    echo "  Data ada: " . count($existing) . " rows — tambah mantan presiden ke Dewan Kehormatan\n";

    $mantan = [
        ['hc_004', 'gp_011', 'Ridwan Pohan',         'Presiden Kehormatan MB INA ke-1 (2004-2006)',  4],
        ['hc_005', 'gp_011', 'Raditya G Wardhana',   'Presiden Kehormatan MB INA ke-2 (2006-2009)',  5],
        ['hc_006', 'gp_011', 'Aditya Adinatha',      'Presiden Kehormatan MB INA ke-3 (2009-2011)',  6],
        ['hc_007', 'gp_011', 'Rheino P. Soufyan',    'Presiden Kehormatan MB INA ke-4 (2011-2013)',  7],
        ['hc_008', 'gp_011', 'Doddy Moedjito',       'Presiden Kehormatan MB INA ke-5 (2013-2015)',  8],
        ['hc_009', 'gp_011', 'Deddy Rachmadi',       'Presiden Kehormatan MB INA ke-6 (2015-2017)',  9],
        ['hc_010', 'gp_011', 'Idham Syewket',        'Presiden Kehormatan MB INA ke-7 (2017-2019)', 10],
        ['hc_011', 'gp_011', 'Mahar Corleone',       'Presiden Kehormatan MB INA ke-8 (2019-2021)', 11],
        ['hc_012', 'gp_011', 'Cecep Fajar',          'Presiden Kehormatan MB INA ke-9 (2021-2023)', 12],
        ['hc_013', 'gp_011', 'I Made Yoga Mahardika','Presiden Kehormatan MB INA ke-10 (2023-2025)',13],
    ];
    $stmt = $pdo->prepare("INSERT INTO honor_council (id, organization_id, governance_period_id, name, position, sort_order) VALUES (:id, 'org_001', :gp, :name, :pos, :ord) ON CONFLICT (id) DO NOTHING");
    foreach ($mantan as $h) {
        $stmt->execute([':id'=>$h[0], ':gp'=>$h[1], ':name'=>$h[2], ':pos'=>$h[3], ':ord'=>$h[4]]);
        echo "  ✅ {$h[2]} — {$h[3]}\n";
    }
} catch (Exception $e) { echo "  ❌ " . $e->getMessage() . "\n"; }


// ============================================================
// 4. organization_structure — Kolom aktual: id(int), full_name, role_name, club_name
//    Ada 18 rows, tampilkan semua dan tambah yang kurang
// ============================================================
echo "\n--- 4. organization_structure (CEK SEMUA) ---\n";
try {
    $rows = $pdo->query("SELECT id, full_name, role_name, club_name FROM organization_structure ORDER BY id ASC")->fetchAll();
    echo "  Total: " . count($rows) . " pengurus\n";
    foreach ($rows as $r) echo "  ✅ [{$r['id']}] {$r['full_name']} — {$r['role_name']}\n";

    // Tambah yang belum ada
    $existingNames = array_column($rows, 'full_name');
    $toAdd = [
        ['Andi Pratama',   'Ketua Departemen Keanggotaan',            'Pusat MBClubINA'],
        ['Siti Rahayu',    'Anggota Departemen Keanggotaan',          'Pusat MBClubINA'],
        ['Budi Santoso',   'Ketua Departemen Kegiatan & Touring',     'Pusat MBClubINA'],
        ['Denny Kurniawan','Ketua Departemen Hubungan Publik & Media', 'Pusat MBClubINA'],
        ['Andi Wijaya',    'Ketua Departemen Teknis & Bengkel',       'Pusat MBClubINA'],
    ];
    $stmt = $pdo->prepare("INSERT INTO organization_structure (full_name, role_name, club_name) VALUES (:name, :role, :club)");
    foreach ($toAdd as $p) {
        if (!in_array($p[0], $existingNames)) {
            $stmt->execute([':name'=>$p[0], ':role'=>$p[1], ':club'=>$p[2]]);
            echo "  ➕ Ditambah: {$p[0]} — {$p[1]}\n";
        } else {
            echo "  ⏭️  Skip (sudah ada): {$p[0]}\n";
        }
    }
} catch (Exception $e) { echo "  ❌ " . $e->getMessage() . "\n"; }


// ============================================================
// 5. governance_periods — Kolom aktual: id, organization_id, period_name, year_start, year_end, is_current, notes
//    Ada 11 rows, update notes yg kosong & set is_current yg benar
// ============================================================
echo "\n--- 5. governance_periods (UPDATE NOTES & TEMA) ---\n";
try {
    $updates = [
        ['gp_001', false, 'Pembentukan federasi nasional MB Club INA, penyusunan AD/ART pertama. Presiden: Ridwan Pohan.'],
        ['gp_002', false, 'Ekspansi ke 5 kota baru, penyelenggaraan Jamnas pertama. Presiden: Raditya G Wardhana.'],
        ['gp_003', false, 'Standarisasi SOP dan tata kelola chapter se-Indonesia. Presiden: Aditya Adinatha.'],
        ['gp_004', false, 'Penambahan 20+ klub baru, penguatan sistem keanggotaan. Presiden: Rheino P Soufyan.'],
        ['gp_005', false, 'Peluncuran website resmi MB INA pertama, digitalisasi database anggota. Presiden: Doddy Moedjito.'],
        ['gp_006', false, 'Terjalinnya hubungan komunitas MB internasional, rebranding logo organisasi. Presiden: Deddy Rachmadi.'],
        ['gp_007', false, 'Digitalisasi pendaftaran member, sistem manajemen event berbasis app. Presiden: Idham Syewket.'],
        ['gp_008', false, 'Adaptasi kegiatan virtual, peluncuran program sosial MB INA Peduli di masa pandemi. Presiden: Mahar Corleone.'],
        ['gp_009', false, 'Revitalisasi Jamnas pasca pandemi, peluncuran portal digital MB INA. Presiden: Cecep Fajar.'],
        ['gp_010', false, 'Integrasi 34 provinsi, peningkatan anggota ke 12.000+ member. Presiden: I Made Yoga Mahardika.'],
        ['gp_011', true,  'Peluncuran Platform Digital MBCINA (Portal Admin M1-M5), integrasi Supabase Cloud, sistem Member ID nasional MBINA-XXX-YYYY-NNNNNN. Presiden: Dr. Rochady Hendra Setya Wibawa.'],
    ];
    $stmt = $pdo->prepare("UPDATE governance_periods SET is_current=:curr, notes=:notes WHERE id=:id");
    foreach ($updates as $u) {
        $stmt->execute([':id'=>$u[0], ':curr'=>$u[1]?'true':'false', ':notes'=>$u[2]]);
        $curr = $u[1] ? ' 👑 AKTIF' : '';
        echo "  ✅ {$u[0]}{$curr}\n";
    }
} catch (Exception $e) { echo "  ❌ " . $e->getMessage() . "\n"; }


// ============================================================
// RINGKASAN FINAL
// ============================================================
echo "\n=== RINGKASAN FINAL M2 ===\n";
$tables = [
    'organization'           => 'Profil Organisasi',
    'founders'               => 'Pendiri',
    'vision_mission'         => 'Visi & Misi',
    'presidents'             => 'Daftar Presiden',
    'advisory_board'         => 'Dewan Penasehat',
    'honor_council'          => 'Dewan Kehormatan',
    'organization_structure' => 'Struktur Pengurus',
    'governance_periods'     => 'Periode Kepengurusan',
    'clubs'                  => 'Klub/Chapter',
];
foreach ($tables as $tbl => $label) {
    try {
        $cnt = $pdo->query("SELECT COUNT(*) FROM $tbl")->fetchColumn();
        echo "  ✅ $tbl ($label): {$cnt} rows\n";
    } catch (Exception $e) { echo "  ❌ $tbl: " . $e->getMessage() . "\n"; }
}
echo "\n=== SELESAI ===\n";
