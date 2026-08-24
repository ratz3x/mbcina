<?php
$f1593 = file_get_contents(__DIR__ . '/feat_line_1593.txt');
$f1605 = file_get_contents(__DIR__ . '/feat_line_1605.txt');

echo "=== FEAT LINE 1593 (LEN: " . strlen($f1593) . ") ===\n";
echo substr($f1593, 0, 1200) . "\n\n";

echo "=== FEAT LINE 1605 (LEN: " . strlen($f1605) . ") ===\n";
echo substr($f1605, 0, 1200) . "\n\n";
