<?php
$js = file_get_contents(__DIR__ . '/../js/app.js');
$lines = explode("\n", $js);

foreach ($lines as $i => $line) {
    if (strpos($line, 'renderLandingPageVisionMission') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
