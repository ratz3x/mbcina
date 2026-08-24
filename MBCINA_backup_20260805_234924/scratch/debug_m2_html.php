<?php
$html = file_get_contents(__DIR__ . '/../index.html');

$m2Start = strpos($html, 'id="admin-tab-m2_org"');
$m3Start = strpos($html, 'id="admin-tab-m3_membership"');

$m2Chunk = substr($html, $m2Start, $m3Start - $m2Start);

echo "=== M2 CHUNK IN INDEX.HTML ===\n";
echo $m2Chunk;
