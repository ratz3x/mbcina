<?php
$js = file_get_contents(__DIR__ . '/../js/app.js');
$lines = explode("\n", $js);
foreach ($lines as $i => $line) {
    if (strpos($line, 'renderUserManagement') !== false || strpos($line, 'usr_m3') !== false || strpos($line, 'Edit') !== false && strpos($line, 'Suspend') !== false) {
        echo "Line " . ($i+1) . ": " . trim($line) . "\n";
    }
}
