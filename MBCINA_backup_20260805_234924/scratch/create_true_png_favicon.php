<?php
$jpgPath = __DIR__ . '/../assets/mb_badge.jpg';
$pngPath = __DIR__ . '/../assets/favicon.png';
$icoPath = __DIR__ . '/../favicon.ico';

if (!file_exists($jpgPath)) {
    echo "JPEG SOURCE NOT FOUND!\n";
    exit;
}

// Create GD image from JPEG
$img = imagecreatefromjpeg($jpgPath);
if (!$img) {
    echo "GD IMAGE CREATE FAILED!\n";
    exit;
}

$width = imagesx($img);
$height = imagesy($img);

// Create 64x64 PNG
$favicon = imagecreatetruecolor(64, 64);
imagecopyresampled($favicon, $img, 0, 0, 0, 0, 64, 64, $width, $height);

// Save PNG
imagepng($favicon, $pngPath);
imagepng($favicon, $icoPath); // Also overwrite favicon.ico as valid PNG format

// Get base64 string
$base64 = base64_encode(file_get_contents($pngPath));
file_put_contents(__DIR__ . '/../scratch/favicon_base64.txt', 'data:image/png;base64,' . $base64);

echo "TRUE PNG FAVICON CREATED SUCCESSFULLY!\n";
echo "Base64 Length: " . strlen($base64) . "\n";
