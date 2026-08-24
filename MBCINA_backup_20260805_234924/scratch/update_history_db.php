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

    // Truncate & re-insert exact rich history sections into organization_history
    $pdo->exec("DELETE FROM organization_history");

    $stmt = $pdo->prepare("INSERT INTO organization_history (id, organization_id, year, title, description, icon, color, sort_order) VALUES (:id, :organization_id, :year, :title, :description, :icon, :color, :sort_order)");

    $historyData = [
        [
            'id' => 'hist_001',
            'organization_id' => 'org_001',
            'year' => 2000,
            'title' => '1. Latar Belakang Pembentukan',
            'description' => "Pada akhir dekade 1990-an sampai dengan awal 2000-an, di Jakarta telah terbentuk beberapa klub Mercedes-Benz, yaitu MCCI, MTC, dan MJI. Klub-klub tersebut meregistrasikan keberadaannya kepada pihak principal Daimler AG di Stuttgart melalui ATPM Mercedes-Benz di Indonesia saat itu, PT Daimler Chrysler Indonesia (PT DC INA), untuk mendapatkan sertifikasi atau legitimasi sebagai klub resmi yang terdaftar.\n\nPemberian sertifikasi pun dikeluarkan kepada ketiga klub tersebut (MCCI, MTC, dan MJI) oleh principal yang pada waktu itu diserahkan melalui PT DC INA dan teregistrasi di bawah MCCCI (Mercedes-Benz Classic Car Club International) Regional Asia-Pasifik yang berkedudukan di Singapura.\n\nPada tahun 2003, MBCI (W124) terbentuk di Jakarta dan langsung mengajukan permohonan sertifikasi kepada pihak principal. Pada saat pengajuan sertifikasi MBCI sedang berproses (biasanya proses ini berjalan sekitar satu tahun atau lebih), pihak principal mengusulkan kepada PT DC INA agar mewacanakan pembentukan klub holding untuk klub-klub Mercedes-Benz yang ada di Indonesia. Hal ini bertujuan untuk mengantisipasi perkembangan dan pertumbuhan jumlah klub Mercedes-Benz di Indonesia pada masa depan.",
            'icon' => '📜',
            'color' => '#D4AF37',
            'sort_order' => 1
        ],
        [
            'id' => 'hist_002',
            'organization_id' => 'org_001',
            'year' => 2004,
            'title' => '2. Proses Pembentukan & Peresmian Federasi',
            'description' => "Pada awal tahun 2004, PT DC INA melalui Bapak Yuniadi Hartono (Deputy Director Marketing & Communication saat itu) beserta Bapak Wim Ekel mulai berkomunikasi dengan tiga klub Mercedes-Benz yang sudah tersertifikasi—yaitu MCCI, MTC, dan MJI—untuk segera membentuk klub holding Mercedes-Benz di Indonesia yang dinamakan Mercedes-Benz Club Indonesia (MB Club Ina), dengan mengirimkan dua orang perwakilan dari masing-masing klub.\n\nUtusan perwakilan klub pendiri saat itu:\n- MCCI mengutus: Ridwan Pohan dan Dharma Adsasmuda.\n- MTC mengutus: Bambang Hariyadi dan Tubagus S. Hidayat (Didot).\n- MJI memutuskan untuk tidak mengirimkan perwakilan, tetapi tetap menyetujui dan mendukung rencana pembentukan MB Club Ina.\n\nSetelah melalui proses pembentukan lewat beberapa kali pertemuan, pada bulan Agustus 2004, Mercedes-Benz Club Indonesia (MB Club Ina) diresmikan oleh PT DC INA sebagai klub federasi yang beranggotakan klub-klub Mercedes-Benz di Indonesia.\n\nSalah satu fungsi MB Club Ina adalah mewakili Indonesia di forum dan kegiatan klub Mercedes-Benz internasional, di antaranya acara President Club Meeting yang diadakan setiap tahun pada bulan Oktober oleh MB Museum, Club Management di Stuttgart.",
            'icon' => '🤝',
            'color' => '#3B82F6',
            'sort_order' => 2
        ],
        [
            'id' => 'hist_003',
            'organization_id' => 'org_001',
            'year' => 2005,
            'title' => '3. Catatan Tambahan & Konsep 4 Pilar',
            'description' => "Pada saat Mercedes-Benz Museum di Stuttgart diresmikan pada tahun 2005, peran Mercedes-Benz Classic Club International (MCCCI) yang tadinya berfungsi membawahi klub-klub Mercedes-Benz di seluruh dunia digantikan oleh Mercedes-Benz Museum, Club Management yang berkedudukan di Stuttgart.\n\nPada saat MB Club Ina diresmikan, PT DC INA menetapkan dan menunjuk secara langsung susunan kepengurusan pertama yang terdiri dari empat orang perwakilan MCCI dan MTC:\n- President: Ridwan Pohan\n- Vice President: Tubagus S. Hidayat\n- Treasurer: Dharma Adsasmuda\n- Public Relations: Bambang Haryadi\n\nKeempat orang perwakilan dari masing-masing klub inilah yang sekarang kita sebut sebagai pendiri atau founder MB Club Ina.\n\nSetelah MB Club Ina resmi terbentuk, masih pada tahun yang sama (2004), sertifikasi untuk MBCI dari principal/MCCCI dikeluarkan dan diserahkan langsung oleh PT DC INA. MBCI menjadi klub Mercedes-Benz terakhir di Indonesia yang tersertifikasi langsung dari MCCCI sekaligus otomatis menjadi klub anggota MB Club Ina.\n\nSejak saat itu pula, Bapak Wim Ekel dari PT DC INA mengemukakan konsep 4 Pilar MB Club Ina, yaitu: MCCI, MTC, MJI, dan MBCI.",
            'icon' => '🏛️',
            'color' => '#10B981',
            'sort_order' => 3
        ]
    ];

    foreach ($historyData as $h) {
        $stmt->execute($h);
    }

    echo "HISTORY DATA INSERTED INTO SUPABASE CLOUD SUCCESSFULLY!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
