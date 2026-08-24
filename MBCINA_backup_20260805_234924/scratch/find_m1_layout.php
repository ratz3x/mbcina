<?php
$html = file_get_contents(__DIR__ . '/../index.html');
$lines = explode("\n", $html);
foreach ($lines as $i => $line) {
    if (strpos($line, 'admin-indonesia-map') !== false || strpos($line, 'admin-growth-chart') !== false || strpos($line, 'admin-region-distribution') !== false || strpos($line, 'admin-alerts-feed') !== false || strpos($line, 'stat-members') !== false || strpos($line, 'stat-clubs') !== false) {
        echo "Line " . ($i+1) . ": " . trim($line) . "\n";
    }
}
