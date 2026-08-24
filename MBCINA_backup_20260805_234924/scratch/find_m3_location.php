<?php
$html = file_get_contents(__DIR__ . '/../index.html');
$lines = explode("\n", $html);

foreach ($lines as $i => $line) {
    if (strpos($line, 'm3sub-') !== false || strpos($line, 'MODUL M3') !== false || strpos($line, 'm3_membership') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
