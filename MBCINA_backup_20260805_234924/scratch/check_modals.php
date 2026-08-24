<?php
$html = file_get_contents(__DIR__ . '/../index.html');

// Check if modal-register and modal-login exist
$modals = ['modal-register', 'modal-login', 'modal-add-user', 'modal-edit-club', 'modal-edit-vm'];
foreach ($modals as $id) {
    $found = strpos($html, 'id="' . $id . '"') !== false;
    echo "Modal '$id': " . ($found ? "✅ EXISTS" : "❌ MISSING") . "\n";
}

// Check button data-open-modal attributes
echo "\nButtons with data-open-modal:\n";
$lines = explode("\n", $html);
foreach ($lines as $i => $line) {
    if (strpos($line, 'data-open-modal') !== false) {
        echo "  Line " . ($i+1) . ": " . trim($line) . "\n";
    }
}

// Check if auth.js and app.js are correctly linked
echo "\nScript tags:\n";
foreach ($lines as $i => $line) {
    if (strpos($line, '<script') !== false) {
        echo "  Line " . ($i+1) . ": " . trim($line) . "\n";
    }
}
