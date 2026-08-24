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

    // Truncate existing structure
    $pdo->exec("DELETE FROM organizational_structure");

    $stmt = $pdo->prepare("INSERT INTO organizational_structure 
        (id, organization_id, position_name, position_level, parent_id, user_id, club_origin, sort_order, is_active, period_start, period_end) 
        VALUES (:id, 'org_001', :position_name, :position_level, :parent_id, :user_id, :club_origin, :sort_order, true, 2023, 2025)");

    $rows = [
        // PRESIDEN
        [
            'id' => 'str_pres',
            'position_name' => 'Presiden MB INA (2023-2025)',
            'position_level' => 'PRESIDEN',
            'parent_id' => null,
            'user_id' => 'usr_presiden',
            'club_origin' => 'MBW211CI',
            'sort_order' => 1
        ],
        // EVP 1
        [
            'id' => 'str_evp1',
            'position_name' => 'EVP 1 (ORGANIZATION)',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_pres',
            'user_id' => null,
            'club_origin' => 'W124 MBCI (Tb NAUDY TS)',
            'sort_order' => 2
        ],
        [
            'id' => 'str_evp1_vp1',
            'position_name' => 'VP ORGANISASI',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp1',
            'user_id' => null,
            'club_origin' => 'MBC LAMPUNG (MUHAMMAD ZAMZAMI)',
            'sort_order' => 3
        ],
        [
            'id' => 'str_evp1_vp2',
            'position_name' => 'VP LEGAL & IT',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp1',
            'user_id' => null,
            'club_origin' => 'MBW202CI (WILDAN MUTAQIEN)',
            'sort_order' => 4
        ],
        [
            'id' => 'str_evp1_vp3',
            'position_name' => 'VP ORG. MONITORING',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp1',
            'user_id' => null,
            'club_origin' => 'MTC INA (ARIEF SUPRIYANTO HEFIK)',
            'sort_order' => 5
        ],
        [
            'id' => 'str_evp1_vp4',
            'position_name' => 'VP HUB. ANTAR LEMBAGA',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp1',
            'user_id' => null,
            'club_origin' => 'Mercedes-Benz Club Indonesia',
            'sort_order' => 6
        ],

        // EVP 2
        [
            'id' => 'str_evp2',
            'position_name' => 'EVP 2 (MOTOR PROGRAM)',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_pres',
            'user_id' => null,
            'club_origin' => 'MBW212CI (ERWIN MP)',
            'sort_order' => 7
        ],
        [
            'id' => 'str_evp2_vp1',
            'position_name' => 'VP MOTORSPORT',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp2',
            'user_id' => null,
            'club_origin' => 'MBW204CI (TOMI HADI)',
            'sort_order' => 8
        ],
        [
            'id' => 'str_evp2_vp2',
            'position_name' => 'VP MOTOR EVENT MANAGEMENT',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp2',
            'user_id' => null,
            'club_origin' => 'MBW221CI (ADE HN)',
            'sort_order' => 9
        ],
        [
            'id' => 'str_evp2_vp3',
            'position_name' => 'VP TOURING',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp2',
            'user_id' => null,
            'club_origin' => 'MJI (ANDRIAN EFFENDI)',
            'sort_order' => 10
        ],
        [
            'id' => 'str_evp2_vp4',
            'position_name' => 'VP CSR & TANGGAP DARURAT',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp2',
            'user_id' => null,
            'club_origin' => 'MBW203CI (HERRY PRIYONO)',
            'sort_order' => 11
        ],

        // EVP 3
        [
            'id' => 'str_evp3',
            'position_name' => 'EVP 3 (EVENT DEV.)',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_pres',
            'user_id' => null,
            'club_origin' => 'MBW211CI (YOGA ERANDO)',
            'sort_order' => 12
        ],
        [
            'id' => 'str_evp3_vp1',
            'position_name' => 'VP EVENT',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp3',
            'user_id' => null,
            'club_origin' => 'MBW210CI (JOSEPH SOBANDI)',
            'sort_order' => 13
        ],
        [
            'id' => 'str_evp3_vp2',
            'position_name' => 'VP PRODUCTION',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp3',
            'user_id' => null,
            'club_origin' => 'MBW211CI (GAMMAR IRZANDI)',
            'sort_order' => 14
        ],
        [
            'id' => 'str_evp3_vp3',
            'position_name' => 'VP NON OTOMOTIF EVENT',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp3',
            'user_id' => null,
            'club_origin' => 'MBW210CI (YUNARDI NOVRIANSAH)',
            'sort_order' => 15
        ],
        [
            'id' => 'str_evp3_vp4',
            'position_name' => 'VP MANAGEMENT',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp3',
            'user_id' => null,
            'club_origin' => 'ARYASANDY',
            'sort_order' => 16
        ],

        // EVP 4
        [
            'id' => 'str_evp4',
            'position_name' => 'EVP 4 (REGIONAL JATIM BALI JATENG KALSUL)',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_pres',
            'user_id' => null,
            'club_origin' => 'MBC MADIUN (HARY SASONO)',
            'sort_order' => 17
        ],
        [
            'id' => 'str_evp4_vpr1',
            'position_name' => 'VPR YOGYA',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp4',
            'user_id' => null,
            'club_origin' => 'MBC YOGYAKARTA (ENDRO BAYU KUSUMO)',
            'sort_order' => 18
        ],
        [
            'id' => 'str_evp4_vpr2',
            'position_name' => 'VPR JAWA TENGAH',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp4',
            'user_id' => null,
            'club_origin' => 'MBC CILACAP (AM YUSUF SIRADJ)',
            'sort_order' => 19
        ],
        [
            'id' => 'str_evp4_vpr3',
            'position_name' => 'VPR JATIM BALI',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp4',
            'user_id' => null,
            'club_origin' => 'MBC TULUNGAGUNG (MESSIE YOGA)',
            'sort_order' => 20
        ],
        [
            'id' => 'str_evp4_vpr4',
            'position_name' => 'VPR KALSUL',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp4',
            'user_id' => null,
            'club_origin' => 'RUSLAN ABDUL RAHMAN',
            'sort_order' => 21
        ],

        // EVP 5
        [
            'id' => 'str_evp5',
            'position_name' => 'EVP 5 (REGIONAL JABAR DKI JAKARTA, SUMATRA, BANTEN)',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_pres',
            'user_id' => null,
            'club_origin' => 'MB CLUB MEDAN (KAMAL ACHYAR)',
            'sort_order' => 22
        ],
        [
            'id' => 'str_evp5_vpr1',
            'position_name' => 'VPR SUMATRA',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp5',
            'user_id' => null,
            'club_origin' => 'MBC PEKANBARU (BUDHI IMANDA)',
            'sort_order' => 23
        ],
        [
            'id' => 'str_evp5_vpr2',
            'position_name' => 'VPR JAWA BARAT',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp5',
            'user_id' => null,
            'club_origin' => 'MBC CIREBON (ASEP TAUFIK HIDAYAT)',
            'sort_order' => 24
        ],
        [
            'id' => 'str_evp5_vpr3',
            'position_name' => 'VPR METRO',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp5',
            'user_id' => null,
            'club_origin' => 'MBW 212CI (DIMAS NURFRIANSYAH)',
            'sort_order' => 25
        ],

        // EVP 6
        [
            'id' => 'str_evp6',
            'position_name' => 'EVP 6 (PUBLIC RELATION)',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_pres',
            'user_id' => null,
            'club_origin' => 'MBW212CI (JHONY GUMAY)',
            'sort_order' => 26
        ],
        [
            'id' => 'str_evp6_dep1',
            'position_name' => 'DEP DESIGN',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp6',
            'user_id' => null,
            'club_origin' => 'MBW202CI (PRAMADIA HARDANI)',
            'sort_order' => 27
        ],
        [
            'id' => 'str_evp6_dep2',
            'position_name' => 'DEP MEDSOS',
            'position_level' => 'PENGURUS_PUSAT',
            'parent_id' => 'str_evp6',
            'user_id' => null,
            'club_origin' => 'MBW212CI (MIZAN ARIF)',
            'sort_order' => 28
        ]
    ];

    foreach ($rows as $r) {
        $stmt->execute($r);
    }

    echo "ORGANIZATIONAL STRUCTURE DATA SEEDED TO SUPABASE CLOUD SUCCESSFULLY!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
