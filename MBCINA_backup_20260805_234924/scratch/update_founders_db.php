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

    // Truncate & re-insert exact 4 founders matching official document photo
    $pdo->exec("DELETE FROM founders");

    $stmt = $pdo->prepare("INSERT INTO founders (id, organization_id, name, club_origin, position, photo_url, bio, sort_order) VALUES (:id, :organization_id, :name, :club_origin, :position, :photo_url, :bio, :sort_order)");

    $founders = [
        [
            'id' => 'fnd_001',
            'organization_id' => 'org_001',
            'name' => 'BAMBANG HARIYADI',
            'club_origin' => 'MTC',
            'position' => 'Co-Founder & Public Relation',
            'photo_url' => 'assets/mb_founders.jpg',
            'bio' => 'Pendiri MB Club INA mewakili MTC (Mercedes-Benz Tiger Club). Menjabat Public Relation pertama.',
            'sort_order' => 1
        ],
        [
            'id' => 'fnd_002',
            'organization_id' => 'org_001',
            'name' => 'TUBAGUS SYAMSUL HIDAYAT',
            'club_origin' => 'MTC',
            'position' => 'Co-Founder & Vice President',
            'photo_url' => 'assets/mb_founders.jpg',
            'bio' => 'Pendiri MB Club INA mewakili MTC. Menjabat Vice President pertama kepengurusan federasi.',
            'sort_order' => 2
        ],
        [
            'id' => 'fnd_003',
            'organization_id' => 'org_001',
            'name' => 'RIDWAN POHAN',
            'club_origin' => 'MCCI',
            'position' => 'President (2004-2006) & Founder',
            'photo_url' => 'assets/mb_founders.jpg',
            'bio' => 'Pendiri utama & Presiden Pertama MB Club INA periode 2004-2006 mewakili MCCI.',
            'sort_order' => 3
        ],
        [
            'id' => 'fnd_004',
            'organization_id' => 'org_001',
            'name' => 'DHARMA ADSASMUDA',
            'club_origin' => 'MCCI',
            'position' => 'Co-Founder & Treasury',
            'photo_url' => 'assets/mb_founders.jpg',
            'bio' => 'Pendiri MB Club INA mewakili MCCI (Mercedes-Benz Car Club Indonesia). Menjabat Bendahara/Treasury pertama.',
            'sort_order' => 4
        ]
    ];

    foreach ($founders as $f) {
        $stmt->execute($f);
    }

    echo "FOUNDERS UPDATED IN SUPABASE CLOUD SUCCESSFULLY!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
