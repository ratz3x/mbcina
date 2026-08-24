<?php
$html = file_get_contents(__DIR__ . '/../index.html');
$lines = explode("\n", $html);

echo "Total lines in restored index.html: " . count($lines) . "\n";

foreach ($lines as $i => $line) {
    if (strpos($line, 'id="admin-tab-') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
