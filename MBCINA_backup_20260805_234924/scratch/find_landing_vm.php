<?php
$html = file_get_contents(__DIR__ . '/../index.html');
$lines = explode("\n", $html);

foreach ($lines as $i => $line) {
    if (strpos($line, 'tentang-kami-section') !== false || strpos($line, 'Visi & Misi') !== false || strpos($line, 'landing-vm-container') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
