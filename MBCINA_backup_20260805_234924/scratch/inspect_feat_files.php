<?php
$f1739 = file_get_contents(__DIR__ . '/feat_line_1739.txt');
$f1750 = file_get_contents(__DIR__ . '/feat_line_1750.txt');

echo "=== FEAT LINE 1739 (LEN: " . strlen($f1739) . ") ===\n";
echo substr($f1739, 0, 1200) . "\n\n";

echo "=== FEAT LINE 1750 (LEN: " . strlen($f1750) . ") ===\n";
echo substr($f1750, 0, 1200) . "\n\n";
