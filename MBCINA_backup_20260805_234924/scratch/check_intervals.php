<?php
$appJs = file_get_contents(__DIR__ . '/../js/app.js');
$authJs = file_get_contents(__DIR__ . '/../js/auth.js');
$indexHtml = file_get_contents(__DIR__ . '/../index.html');

echo "SEARCHING FOR SETINTERVAL:\n";
preg_match_all('/setInterval\([^)]+\)/', $appJs, $m1);
echo "app.js setIntervals: " . count($m1[0]) . "\n";

preg_match_all('/setInterval\([^)]+\)/', $authJs, $m2);
echo "auth.js setIntervals: " . count($m2[0]) . "\n";

echo "\nSEARCHING FOR EXTERNAL URLS IN INDEX.HTML:\n";
preg_match_all('/https?:\/\/[^\s\'"]+/', $indexHtml, $m3);
foreach ($m3[0] as $u) {
    echo "External URL: $u\n";
}
