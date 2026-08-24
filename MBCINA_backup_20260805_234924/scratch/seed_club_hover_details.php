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

    $stmt = $pdo->query("SELECT id, name, city, region, type FROM clubs");
    $clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ketuaNames = [
        "H. Ahmad Fauzi, S.E.", "Dr. Budi Santoso, M.Si.", "Capt. Irwan Wijaya", 
        "H. Bambang Sugianto", "Ir. Denny Pratama", "Donny Kurniawan, S.H.",
        "Drs. Eko Prasetyo", "Ferry Gunawan", "H. Hendra Saputra",
        "Dr. M. Rizky Febrian", "Rudy Hartono, S.T.", "Agus Setiawan",
        "Bayu Wibowo, M.M.", "Chandra Kirana", "Dedi Herman"
    ];

    $cpNames = [
        "Hendra (Sekretaris Club)", "Rian (Divisi Humas & Event)", 
        "Dicky (Divisi Touring)", "Andi (Pengurus Harian)",
        "Reza (Kontak Informasi Member)"
    ];

    $updateStmt = $pdo->prepare("UPDATE clubs SET 
        ketua_umum = :ketua, 
        contact_person = :cp, 
        contact_phone = :phone, 
        founded_year = :year, 
        description = :desc 
        WHERE id = :id");

    $count = 0;
    foreach ($clubs as $c) {
        $ketua = $ketuaNames[array_rand($ketuaNames)];
        $cp = $cpNames[array_rand($cpNames)];
        $phone = "081" . rand(1, 9) . "-" . rand(1000, 9999) . "-" . rand(1000, 9999);
        $year = rand(2004, 2022);
        
        $desc = "Klub/Chapter " . $c['name'] . " didirikan pada tahun " . $year . " di " . ($c['city'] ?: 'Indonesia') . ". Berdiri di bawah naungan " . $c['region'] . " sebagai wadah silaturahmi antar pemilik dan pengagum Mercedes-Benz, aktif mengagendakan touring regional, bakti sosial, serta pertemuan rutin anggota.";

        $updateStmt->execute([
            ':ketua' => $ketua,
            ':cp' => $cp,
            ':phone' => $phone,
            ':year' => $year,
            ':desc' => $desc,
            ':id' => $c['id']
        ]);
        $count++;
    }

    echo "UPDATED $count CLUBS WITH KETUA UMUM, CONTACT PERSON, AND SEJARAH RINGKAS IN SUPABASE CLOUD!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
