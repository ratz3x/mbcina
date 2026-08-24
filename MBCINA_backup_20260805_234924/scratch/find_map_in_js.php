<?php
$js = file_get_contents(__DIR__ . '/../js/app.js');
$lines = explode("\n", $js);

foreach ($lines as $i => $line) {
    if (strpos($line, 'map') !== false || strpos($line, 'Map') !== false || strpos($line, 'indonesia') !== false || strpos($line, 'Indonesia') !== false || strpos($line, 'render') !== false) {
        if (strpos($line, 'Map') !== false || strpos($line, 'indonesia') !== false || strpos($line, 'Indonesia') !== false || strpos($line, 'renderAdmin') !== false) {
            echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
        }
    }
}
