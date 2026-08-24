<?php
$api = file_get_contents(__DIR__ . '/../api.php');
$lines = explode("\n", $api);
foreach ($lines as $i => $line) {
    if (strpos($line, "case 'get_app_init_data'") !== false || strpos($line, 'advisoryBoard') !== false || strpos($line, 'honorCouncil') !== false || strpos($line, 'advisory_board') !== false || strpos($line, 'honor_council') !== false || strpos($line, "'structure'") !== false || strpos($line, "'periods'") !== false) {
        echo "Line " . ($i+1) . ": " . trim($line) . "\n";
    }
}
