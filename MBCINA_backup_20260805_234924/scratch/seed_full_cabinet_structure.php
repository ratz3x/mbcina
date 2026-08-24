<?php
require_once __DIR__ . '/../api.php';

$sPdo = getSupabasePDO();

if (!$sPdo) {
    echo "FAILED TO CONNECT TO SUPABASE CLOUD!\n";
    exit;
}

echo "=== POPULATING FULL CABINET STRUCTURE IN SUPABASE CLOUD ===\n";

// Clear old 6 rows to insert complete 18 positions
$sPdo->exec("TRUNCATE TABLE organization_structure CASCADE");

$fullCabinet = [
    [
        'id' => 1,
        'role_name' => 'Presiden MB Club INA',
        'full_name' => 'Dr. Rochady Hendra Setya Wibawa, Sp.OG., M.Kes., S.Kom.',
        'club_name' => 'Pusat MBClubINA'
    ],
    [
        'id' => 2,
        'role_name' => 'Sekretaris Jenderal / Pusat',
        'full_name' => 'Mukhwan Hariri',
        'club_name' => 'Pusat MBClubINA'
    ],
    [
        'id' => 3,
        'role_name' => 'Bendahara Umum / Pusat',
        'full_name' => 'Christina Dwi Astuti',
        'club_name' => 'Pusat MBClubINA'
    ],
    [
        'id' => 4,
        'role_name' => 'VP IT & Digital Innovation',
        'full_name' => 'Rizky Ramadhan',
        'club_name' => 'Pusat MBClubINA'
    ],
    [
        'id' => 5,
        'role_name' => 'VP Event & Motorsport',
        'full_name' => 'Prasetyo Wishnu',
        'club_name' => 'Pusat MBClubINA'
    ],
    [
        'id' => 6,
        'role_name' => 'VP Business & Sponsorship',
        'full_name' => 'Hasan Maulani Bahfari',
        'club_name' => 'Pusat MBClubINA'
    ],
    [
        'id' => 7,
        'role_name' => 'VP Organisasi & Keanggotaan',
        'full_name' => 'Donny Kurniawan, S.H.',
        'club_name' => 'Pusat MBClubINA'
    ],
    [
        'id' => 8,
        'role_name' => 'VP Humas & Publikasi',
        'full_name' => 'Bayu Wibowo, M.M.',
        'club_name' => 'Pusat MBClubINA'
    ],
    [
        'id' => 9,
        'role_name' => 'VP Hukum & Advocacy',
        'full_name' => 'Ir. Denny Pratama',
        'club_name' => 'Pusat MBClubINA'
    ],
    [
        'id' => 10,
        'role_name' => 'VP Sosial & Pengabdian Masyarakat',
        'full_name' => 'Dr. Budi Santoso, M.Si.',
        'club_name' => 'Pusat MBClubINA'
    ],
    [
        'id' => 11,
        'role_name' => 'VP Regional Sumatra',
        'full_name' => 'Budhi Imanda',
        'club_name' => 'Pusat MBClubINA'
    ],
    [
        'id' => 12,
        'role_name' => 'VP Regional Banten',
        'full_name' => 'Taufik Gaos',
        'club_name' => 'Pusat MBClubINA'
    ],
    [
        'id' => 13,
        'role_name' => 'VP Regional Metro DKI Jakarta',
        'full_name' => 'H. Ahmad Fauzi, S.E.',
        'club_name' => 'Pusat MBClubINA'
    ],
    [
        'id' => 14,
        'role_name' => 'VP Regional Jawa Barat',
        'full_name' => 'Dedi Herman',
        'club_name' => 'Pusat MBClubINA'
    ],
    [
        'id' => 15,
        'role_name' => 'VP Regional Jawa Tengah',
        'full_name' => 'Rudy Hartono, S.T.',
        'club_name' => 'Pusat MBClubINA'
    ],
    [
        'id' => 16,
        'role_name' => 'VP Regional Yogyakarta',
        'full_name' => 'Dr. M. Rizky Febrian',
        'club_name' => 'Pusat MBClubINA'
    ],
    [
        'id' => 17,
        'role_name' => 'VP Regional Jawa Timur & Bali',
        'full_name' => 'Capt. Irwan Wijaya',
        'club_name' => 'Pusat MBClubINA'
    ],
    [
        'id' => 18,
        'role_name' => 'VP Regional Kalimantan & Sulawesi',
        'full_name' => 'H. Bambang Sugianto',
        'club_name' => 'Pusat MBClubINA'
    ]
];

$stmt = $sPdo->prepare("INSERT INTO organization_structure (id, role_name, full_name, club_name, created_at, updated_at) VALUES (:id, :role_name, :full_name, :club_name, NOW(), NOW())");

foreach ($fullCabinet as $pos) {
    $stmt->execute([
        ':id' => $pos['id'],
        ':role_name' => $pos['role_name'],
        ':full_name' => $pos['full_name'],
        ':club_name' => $pos['club_name']
    ]);
    echo "Inserted Position #{$pos['id']}: {$pos['role_name']} - {$pos['full_name']}\n";
}

echo "=== SUCCESS! ALL 18 CABINET POSITIONS INSERTED INTO SUPABASE CLOUD ===\n";
