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

    // Truncate existing clubs
    $pdo->exec("DELETE FROM clubs");

    $stmt = $pdo->prepare("INSERT INTO clubs 
        (id, organization_id, code, name, alias, region, city, type, member_count, status, is_verified) 
        VALUES (:id, 'org_001', :code, :name, :alias, :region, :city, :type, :member_count, 'ACTIVE', true)");

    $rawClubsByRegion = [
        "Regional Sumatra" => [
            "MBC Aceh", "MTC Ina Bireuen Chapter", "MBC Langsa", "MBC Medan",
            "W124MBCI Medan Chapter", "MBW202CI Medan Region", "MTC Ina Medan Chapter",
            "MCCI Medan Chapter", "MBW204CI Medan Chapter", "MBC Padang",
            "MTC Ina Padang Chapter", "MBC Pekanbaru", "MTC Ina Pekanbaru Chapter",
            "MBC Palembang", "MBC Lampung", "W124MBCI Lampung Chapter"
        ],
        "Regional Banten" => [
            "MBC Banten", "MBC Tangerang Raya", "MBW211CI Tangerang Chapter",
            "MBW204CI Tangerang Chapter"
        ],
        "Regional Metro DKI Jakarta" => [
            "MCCI", "MTC Ina", "MJI", "W124 MBCI", "MBW140 Club Indonesia",
            "MBVitoViano Club Indonesia", "MBSL Club Indonesia", "MBDiecast Club Indonesia",
            "MBML Club Indonesia", "MBW201 Club Indonesia", "MBW202 Club Indonesia",
            "MBW203 Club Indonesia", "MBW204 Club Indonesia", "MBW205 Community Indonesia",
            "MBW210 Club Indonesia", "MBW211 Club Indonesia", "MBW212 Club Indonesia",
            "MBW213 Community Indonesia", "MBW221 Club Indonesia", "W124MBCI Jakarta Chapter",
            "MBW202CI Jakarta Region"
        ],
        "Regional Jawa Barat" => [
            "MBC Bandung", "BMUC", "MBC Sukabumi", "MBC Sumedang", "MBC Cirebon",
            "MBW123 Club Bandung Ina", "MCCI Bandung Chapter", "W124MBCI Bandung Chapter",
            "W124MBCI Bogor Chapter", "W124MBCI Cirebon Chapter", "MBW140CI Bandung Chapter",
            "MBW202CI Bandung Region", "MBW203CI Bandung Chapter", "MBW203CI Bogor Chapter",
            "MBW204CI Bandung Chapter", "MBW204CI Bekasi Chapter", "MBW210CI Bandung Chapter",
            "MBW211CI Bandung Chapter", "MBW211CI Bekasi Chapter", "MBW211CI Bogor Chapter",
            "MBW212CI Bandung Chapter", "MTC-Ina Cirebon Chapter"
        ],
        "Regional Jawa Tengah" => [
            "MBC Tegal Raya", "MBC Pekalongan", "MBC Banyumas", "MBC Cilacap",
            "MBC Semarang", "MBC Solo Raya", "MBC Jepara", "W124MBCI Banyumas Chapter",
            "W124MBCI Semarang Chapter", "W124MBCI Solo Chapter", "MTC Ina Semarang Chapter",
            "MBW202CI Distrik PKL PML", "MBW202CI Distrik Solo", "MBW202CI Semarang Region",
            "MBW203CI Semarang Chapter", "MBW210CI Semarang Chapter", "MBW210CI Solo Chapter",
            "MBW211CI Semarang Chapter", "MCCI Semarang Chapter"
        ],
        "Regional Yogyakarta" => [
            "MBC Yogyakarta", "MCCI Yogyakarta Chapter", "MTC Ina Yogyakarta Chapter",
            "MBW202CI Yogyakarta Region", "W124MBCI Yogyakarta Chapter",
            "MBW210CI Yogyakarta Chapter", "MBW211CI Yogyakarta Chapter"
        ],
        "Regional Jawa Timur & Bali" => [
            "MBC Madiun", "MBC Madura", "MBC Malang", "MBC Tulung Agung", "MBC Jember",
            "MBC Bali", "MTC Ina Surabaya Chapter", "MTC Ina Malang Chapter",
            "MJI Surabaya", "MCCI Surabaya Chapter", "MBW202CI Surabaya Region",
            "MBW202CI Malang District", "MBW203CI Surabaya Chapter", "MBW204CI Surabaya Chapter",
            "W124MBCI Surabaya Chapter", "W124MBCI Malang Chapter", "MBW210CI Surabaya Chapter",
            "MBW211CI Surabaya Chapter", "MBW212CI Surabaya Chapter"
        ],
        "Regional Kalimantan & Sulawesi" => [
            "MBC Banjarmasin", "MBC Makassar"
        ]
    ];

    $usedCodes = [];
    $index = 1;
    foreach ($rawClubsByRegion as $region => $clubNames) {
        foreach ($clubNames as $cName) {
            $id = 'clb_' . sprintf('%03d', $index);
            
            // Unique code creation
            $cleanName = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $cName));
            $baseCode = substr($cleanName, 0, 8);
            $code = $baseCode;
            $cIndex = 1;
            while (in_array($code, $usedCodes)) {
                $code = substr($baseCode, 0, 6) . sprintf('%02d', $cIndex);
                $cIndex++;
            }
            $usedCodes[] = $code;

            // Extract city
            $city = 'Indonesia';
            foreach (['Aceh','Bireuen','Langsa','Medan','Padang','Pekanbaru','Palembang','Lampung','Banten','Tangerang','Jakarta','Bandung','Sukabumi','Sumedang','Cirebon','Bogor','Bekasi','Tegal','Pekalongan','Banyumas','Cilacap','Semarang','Solo','Jepara','Yogyakarta','Madiun','Madura','Malang','Tulungagung','Tulung Agung','Jember','Bali','Surabaya','Banjarmasin','Makassar'] as $ct) {
                if (stripos($cName, $ct) !== false) {
                    $city = $ct;
                    break;
                }
            }

            // Determine type
            $type = (stripos($cName, 'Chapter') !== false || stripos($cName, 'Region') !== false || stripos($cName, 'District') !== false || stripos($cName, 'Distrik') !== false) ? 'CHAPTER' : 'CLUB';

            $memberCount = rand(45, 380);

            $stmt->execute([
                ':id' => $id,
                ':code' => $code,
                ':name' => $cName,
                ':alias' => $code,
                ':region' => $region,
                ':city' => $city,
                ':type' => $type,
                ':member_count' => $memberCount
            ]);

            $index++;
        }
    }

    echo "TOTAL " . ($index - 1) . " CLUBS & CHAPTERS SEEDED TO SUPABASE CLOUD SUCCESSFULLY!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
