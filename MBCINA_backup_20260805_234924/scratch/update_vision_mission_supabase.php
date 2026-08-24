<?php
require_once __DIR__ . '/../api.php';

$sPdo = getSupabasePDO();

if (!$sPdo) {
    echo "FAILED TO CONNECT TO SUPABASE CLOUD!\n";
    exit;
}

echo "=== UPDATING VISION_MISSION TABLE IN SUPABASE CLOUD ===\n";

$orgStmt = $sPdo->query("SELECT id FROM organization LIMIT 1");
$orgId = $orgStmt->fetchColumn() ?: 'org_mbina_001';

// Clear existing vision_mission records to keep clean 4 missions + 1 vision
$sPdo->exec("TRUNCATE TABLE vision_mission CASCADE");

$data = [
    [
        'id' => 'vm_visi_001',
        'organization_id' => $orgId,
        'type' => 'VISION',
        'title' => 'Visi MB Club INA',
        'description' => 'Menjadi wadah komunitas Mercedes-Benz terbesar, terbaik, dan paling solid di Indonesia serta menjadi kebanggaan bangsa.',
        'icon' => '🎯',
        'sort_order' => 1
    ],
    [
        'id' => 'vm_misi_001',
        'organization_id' => $orgId,
        'type' => 'MISSION',
        'title' => 'Misi 1 - Mempererat Persaudaraan',
        'description' => 'Mempererat tali persaudaraan antar anggota dan menciptakan lingkungan yang inklusif dan saling mendukung.',
        'icon' => '🤝',
        'sort_order' => 1
    ],
    [
        'id' => 'vm_misi_002',
        'organization_id' => $orgId,
        'type' => 'MISSION',
        'title' => 'Misi 2 - Berbagi Pengetahuan',
        'description' => 'Menyediakan platform edukasi tentang Mercedes-Benz dan berbagi pengalaman serta tips perawatan.',
        'icon' => '📚',
        'sort_order' => 2
    ],
    [
        'id' => 'vm_misi_003',
        'organization_id' => $orgId,
        'type' => 'MISSION',
        'title' => 'Misi 3 - Kegiatan Sosial',
        'description' => 'Aktif dalam kegiatan sosial dan bakti sosial serta memberikan kontribusi positif bagi masyarakat.',
        'icon' => '❤️',
        'sort_order' => 3
    ],
    [
        'id' => 'vm_misi_004',
        'organization_id' => $orgId,
        'type' => 'MISSION',
        'title' => 'Misi 4 - Pengembangan Organisasi',
        'description' => 'Terus berkembang dan beradaptasi dengan zaman serta menjangkau lebih banyak anggota di seluruh Indonesia.',
        'icon' => '🚀',
        'sort_order' => 4
    ]
];

$stmt = $sPdo->prepare("INSERT INTO vision_mission (id, organization_id, type, title, description, icon, sort_order) VALUES (:id, :org_id, :type, :title, :description, :icon, :sort_order)");

foreach ($data as $item) {
    $stmt->execute([
        ':id' => $item['id'],
        ':org_id' => $item['organization_id'],
        ':type' => $item['type'],
        ':title' => $item['title'],
        ':description' => $item['description'],
        ':icon' => $item['icon'],
        ':sort_order' => $item['sort_order']
    ]);
    echo "Inserted: {$item['title']} ({$item['icon']})\n";
}

echo "=== SUCCESS! VISION_MISSION TABLE SEEDED IN SUPABASE CLOUD ===\n";
