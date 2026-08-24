<?php
$imgPath = 'c:/xampp/htdocs/MBCINA/assets/mb_founders.jpg';
if (!file_exists($imgPath)) {
    echo "Image not found\n";
    exit;
}

$src = imagecreatefrompng($imgPath);
if (!$src) {
    echo "Failed to load PNG\n";
    exit;
}

$w = imagesx($src);
$h = imagesy($src);
echo "Image dimensions: {$w}x{$h}\n";

// Coordinates based on 1000px wide image or proportional
// The image has 4 circular portraits horizontally aligned
// Bambang Hariyadi (~x: 5% - 25%), Tubagus Syamsul Hidayat (~x: 28% - 48%), Ridwan Pohan (~x: 51% - 71%), Dharma Adsasmuda (~x: 74% - 94%)
// Vertical center (~y: 30% - 75%)

$founders = [
    'bambang' => ['x' => (int)($w * 0.05), 'y' => (int)($h * 0.28), 'w' => (int)($w * 0.21), 'h' => (int)($h * 0.45)],
    'tubagus' => ['x' => (int)($w * 0.28), 'y' => (int)($h * 0.28), 'w' => (int)($w * 0.21), 'h' => (int)($h * 0.45)],
    'ridwan'  => ['x' => (int)($w * 0.51), 'y' => (int)($h * 0.28), 'w' => (int)($w * 0.21), 'h' => (int)($h * 0.45)],
    'dharma'  => ['x' => (int)($w * 0.74), 'y' => (int)($h * 0.28), 'w' => (int)($w * 0.21), 'h' => (int)($h * 0.45)]
];

foreach ($founders as $key => $crop) {
    $dst = imagecreatetruecolor($crop['w'], $crop['h']);
    imagecopy($dst, $src, 0, 0, $crop['x'], $crop['y'], $crop['w'], $crop['h']);
    $outFile = "c:/xampp/htdocs/MBCINA/assets/founder_{$key}.jpg";
    imagejpeg($dst, $outFile, 95);
    imagedestroy($dst);
    echo "Cropped $key -> $outFile\n";
}

imagedestroy($src);
echo "All cropped successfully!\n";
