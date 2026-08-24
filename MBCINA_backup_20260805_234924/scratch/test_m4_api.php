<?php
require_once __DIR__ . '/../api.php';
$sPdo = getSupabasePDO();
$apps = $sPdo->query("SELECT * FROM club_applications ORDER BY created_at DESC")->fetchAll();
echo "COUNT APPLICATIONS: " . count($apps) . "\n";
foreach ($apps as $a) {
    echo "- {$a['code']}: {$a['name']} ({$a['status']})\n";
}
