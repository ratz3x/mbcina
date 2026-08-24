<?php
require_once __DIR__ . '/../api.php';
$pdo = getSupabasePDO();
if (!$pdo) { echo "GAGAL konek!\n"; exit; }

// Mapping kota/region ke KODE CHAPTER
// Bisa dikembangkan sesuai kebutuhan
function getCityCode($city, $province, $role) {
    // HQ roles - selalu HQ
    $hqRoles = ['SUPER_ADMIN', 'PRESIDEN', 'SEKRETARIS_PUSAT', 'BENDAHARA_PUSAT', 'ADMIN_ORGANISASI', 'PENGURUS_PUSAT'];
    if (in_array($role, $hqRoles)) return 'HQ';

    $city = strtolower(trim($city ?? ''));
    $province = strtolower(trim($province ?? ''));

    // Map berdasarkan kota
    $cityMap = [
        // Jakarta & Banten
        'jakarta'       => 'JKT', 'jakarta selatan' => 'JKT', 'jakarta barat'  => 'JKT',
        'jakarta timur' => 'JKT', 'jakarta utara'   => 'JKT', 'jakarta pusat'  => 'JKT',
        'tangerang'     => 'TGR', 'bekasi'          => 'BKS', 'depok'          => 'DPK',
        'bogor'         => 'BGR', 'serang'          => 'SRG',
        // Jawa Barat
        'bandung'       => 'BDG', 'kota bandung'    => 'BDG', 'cimahi'         => 'BDG',
        'cirebon'       => 'CRB', 'sukabumi'        => 'SKB', 'karawang'       => 'KRW',
        // Jawa Tengah & DIY
        'semarang'      => 'SMG', 'yogyakarta'      => 'YGY', 'solo'           => 'SLO',
        'surakarta'     => 'SLO', 'purwokerto'      => 'PWK', 'magelang'       => 'MGL',
        // Jawa Timur
        'surabaya'      => 'SBY', 'malang'          => 'MLG', 'sidoarjo'       => 'SDA',
        'gresik'        => 'GRS', 'mojokerto'       => 'MJK', 'kediri'         => 'KDR',
        'jember'        => 'JMR',
        // Bali
        'denpasar'      => 'DPS', 'badung'          => 'DPS', 'gianyar'        => 'DPS',
        // Sumatera
        'medan'         => 'MED', 'pekanbaru'       => 'PKB', 'palembang'      => 'PLG',
        'batam'         => 'BTM', 'padang'          => 'PDG', 'bandar lampung' => 'BLP',
        'jambi'         => 'JMB',
        // Kalimantan
        'balikpapan'    => 'BPP', 'samarinda'       => 'SMD', 'pontianak'      => 'PTK',
        'banjarmasin'   => 'BJM',
        // Sulawesi
        'makassar'      => 'MKS', 'manado'          => 'MND', 'palu'           => 'PLU',
        // Papua
        'jayapura'      => 'JPR',
    ];

    if (isset($cityMap[$city])) return $cityMap[$city];

    // Fallback ke province
    $provMap = [
        'prov_dki' => 'JKT', 'prov_jbr' => 'BDG', 'prov_jth' => 'SMG',
        'prov_jtm' => 'SBY', 'prov_blt' => 'DPS', 'prov_sum' => 'MED',
        'prov_jam' => 'JMB', 'prov_kal' => 'BPP', 'prov_sul' => 'MKS',
    ];
    foreach ($provMap as $pKey => $pCode) {
        if (strpos($province, $pKey) !== false) return $pCode;
    }

    // Ambil 3 huruf pertama dari kota sebagai fallback
    if (strlen($city) >= 3) {
        return strtoupper(substr(preg_replace('/[^a-z]/', '', $city), 0, 3));
    }

    return 'INA';
}

// Ambil semua user diurutkan berdasarkan created_at (urutan nasional)
$users = $pdo->query("SELECT id, username, name, city, province_id, member_id, role, created_at FROM users ORDER BY created_at ASC")->fetchAll();

echo "=== UPDATE MEMBER ID FORMAT: MBINA-[KODE]-[TAHUN]-[6DIGIT] ===\n\n";

$globalSeq = 1; // Nomor urut nasional mulai dari 1
foreach ($users as $u) {
    $year = date('Y', strtotime($u['created_at']));
    $code = getCityCode($u['city'], $u['province_id'], $u['role']);
    $seq  = str_pad($globalSeq, 6, '0', STR_PAD_LEFT);
    $newMemberId = "MBINA-{$code}-{$year}-{$seq}";

    echo "#{$globalSeq} [{$u['id']}] {$u['name']}\n";
    echo "   Kota: {$u['city']} | Role: {$u['role']} | Kode: {$code}\n";
    echo "   Lama: {$u['member_id']}\n";
    echo "   Baru: {$newMemberId}\n\n";

    // Update ke Supabase
    $stmt = $pdo->prepare("UPDATE users SET member_id = :mid WHERE id = :id");
    $stmt->execute([':mid' => $newMemberId, ':id' => $u['id']]);

    $globalSeq++;
}

echo "=== SELESAI — {$globalSeq} member ID diupdate ===\n";
