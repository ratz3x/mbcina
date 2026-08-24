<?php
$h32 = file_get_contents(__DIR__ . '/html_edit_32.txt');
$h36 = file_get_contents(__DIR__ . '/html_edit_36.txt');

echo "=== H32 (LEN: " . strlen($h32) . ") ===\n";
echo substr($h32, 0, 1000) . "\n\n";

echo "=== H36 (LEN: " . strlen($h36) . ") ===\n";
echo substr($h36, 0, 1000) . "\n\n";
