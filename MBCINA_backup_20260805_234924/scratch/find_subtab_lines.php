<?php
$js = file_get_contents(__DIR__ . '/../js/app.js');
$lines = explode("\n", $js);

foreach ($lines as $i => $line) {
    if (strpos($line, 'switchM3Subtab') !== false || strpos($line, 'switchM4Subtab') !== false || strpos($line, 'switchM5Subtab') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
