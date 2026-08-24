<?php
$api = file_get_contents(__DIR__ . '/../api.php');
$lines = explode("\n", $api);
// cari case 'delete_m3_member'
foreach ($lines as $i => $line) {
    if (strpos($line, "case 'delete_m3_member'") !== false || strpos($line, "case 'upload_member_photo'") !== false) {
        echo "Line " . ($i+1) . ": " . trim($line) . "\n";
    }
}
