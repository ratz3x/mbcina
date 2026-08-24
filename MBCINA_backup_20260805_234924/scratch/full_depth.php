<?php
$html = file_get_contents(__DIR__ . '/../index.html');
$lines = explode("\n", $html);

$depth = 0;
$minDepth = 999;
$minLine = 0;

foreach ($lines as $i => $line) {
    // Count only actual div tags, not inside JS strings or attributes
    $o = preg_match_all('/<div[\s>]/', $line, $m1);
    $c = preg_match_all('/<\/div>/', $line, $m2);
    $depth += $o - $c;
    
    if ($depth < $minDepth) {
        $minDepth = $depth;
        $minLine = $i + 1;
    }
    
    // Show depth < 0 moments
    if ($depth < 0) {
        echo "NEGATIVE DEPTH at Line " . ($i + 1) . " [depth=$depth]: " . trim($line) . "\n";
    }
}

echo "\nMin depth: $minDepth at line $minLine\n";
echo "Final depth: $depth\n";
echo "\nTotal: " . preg_match_all('/<div[\s>]/', $html, $m1) . " opens, " . preg_match_all('/<\/div>/', $html, $m2) . " closes\n";
