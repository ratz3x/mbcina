<?php
$js = file_get_contents(__DIR__ . '/../js/app.js');
$lines = explode("\n", $js);
foreach ($lines as $i => $line) {
    if (strpos($line, 'renderAdminDashboard') !== false || strpos($line, 'admin-indonesia-map') !== false || strpos($line, 'growth-chart') !== false || strpos($line, 'renderGrowthChart') !== false || strpos($line, 'renderIndonesiaMap') !== false) {
        echo "Line " . ($i+1) . ": " . trim($line) . "\n";
    }
}
