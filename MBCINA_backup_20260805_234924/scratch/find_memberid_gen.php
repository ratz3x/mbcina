<?php
$api = file_get_contents(__DIR__ . '/../api.php');
$lines = explode("\n", $api);
foreach ($lines as $i => $line) {
    if (strpos($line, 'member_id') !== false || strpos($line, 'memberId') !== false || strpos($line, 'MBINA') !== false || strpos($line, 'generateMemberId') !== false) {
        echo "Line " . ($i+1) . ": " . trim($line) . "\n";
    }
}
