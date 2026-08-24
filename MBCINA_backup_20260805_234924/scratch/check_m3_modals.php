<?php
$html = file_get_contents(__DIR__ . '/../index.html');
$modals = [
    'modal-m3-member-detail',
    'modal-m3-edit-member',
    'modal-m3-add-donation',
    'modal-m3-verify',
    'modal-m3-export',
];
echo "=== CEK MODAL M3 DI index.html ===\n\n";
foreach ($modals as $modal) {
    if (strpos($html, 'id="' . $modal . '"') !== false) {
        echo "✅ $modal — ADA\n";
    } else {
        echo "❌ $modal — TIDAK ADA!\n";
    }
}

// Juga cek apakah m3Data.members[0].id field name
$js = file_get_contents(__DIR__ . '/../js/app.js');
echo "\n=== CEK fetchM3Data - field id ===\n";
$lines = explode("\n", $js);
foreach ($lines as $i => $line) {
    if (strpos($line, 'fetchM3Data') !== false || strpos($line, 'm3Data') !== false && strpos($line, 'members') !== false) {
        echo "Line " . ($i+1) . ": " . trim($line) . "\n";
    }
}
