<?php
$jpg = file_get_contents(__DIR__ . '/../assets/mb_badge.jpg');
$b64 = 'data:image/jpeg;base64,' . base64_encode($jpg);
file_put_contents(__DIR__ . '/../scratch/favicon_b64.txt', $b64);
echo "BASE64 CREATED, LENGTH: " . strlen($b64) . "\n";
