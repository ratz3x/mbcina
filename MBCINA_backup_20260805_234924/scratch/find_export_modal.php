<?php
$js = file_get_contents(__DIR__ . '/../js/app.js');
$lines = explode("\n", $js);
foreach ($lines as $i => $line) {
    if (strpos($line, 'openM3ExportModal') !== false) {
        echo "Line " . ($i+1) . ": " . trim($line) . "\n";
    }
}
