<?php
$auth = file_get_contents(__DIR__ . '/../js/auth.js');
$lines = explode("\n", $auth);
foreach ($lines as $i => $line) {
    if (strpos($line, 'closeAllModals') !== false || strpos($line, 'openModal') !== false || strpos($line, 'modal-overlay') !== false || strpos($line, 'display') !== false && strpos($line, 'modal') !== false) {
        echo "Line " . ($i+1) . ": " . trim($line) . "\n";
    }
}
