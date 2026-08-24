<?php
$h39 = file_get_contents(__DIR__ . '/html_edit_39.txt');
$h42 = file_get_contents(__DIR__ . '/html_edit_42.txt');
$j54 = file_get_contents(__DIR__ . '/js_edit_54.txt');

echo "h39 len: " . strlen($h39) . "\n";
echo "h42 len: " . strlen($h42) . "\n";
echo "j54 len: " . strlen($j54) . "\n";

echo "=== H39 PREVIEW ===\n";
echo substr($h39, 0, 800) . "\n\n";

echo "=== H42 PREVIEW ===\n";
echo substr($h42, 0, 800) . "\n\n";

echo "=== J54 PREVIEW ===\n";
echo substr($j54, 0, 800) . "\n\n";
