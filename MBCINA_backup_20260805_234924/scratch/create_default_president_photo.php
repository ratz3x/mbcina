<?php
$source = __DIR__ . '/../assets/mb_badge.jpg';
$destDir = __DIR__ . '/../assets/presidents';
$dest = $destDir . '/default.jpg';

if (!is_dir($destDir)) {
    mkdir($destDir, 0777, true);
}

if (file_exists($source)) {
    copy($source, $dest);
    echo "DEFAULT.JPG CREATED SUCCESSFULLY IN ASSETS/PRESIDENTS/DEFAULT.JPG!\n";
} else {
    echo "SOURCE ASSET NOT FOUND!\n";
}
