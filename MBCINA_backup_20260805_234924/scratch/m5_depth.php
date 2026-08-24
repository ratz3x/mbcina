<?php
$html = file_get_contents(__DIR__ . '/../index.html');
$lines = explode("\n", $html);

// Track depth within M5 section (starts at line 981 = index 980)
$depth = 0;
for ($i = 980; $i <= 1079; $i++) {
    $line = $lines[$i] ?? '';
    $o = preg_match_all('/<div[^>]*>/', $line, $m1);
    $c = preg_match_all('/<\/div>/', $line, $m2);
    $depth += $o - $c;
    
    // Show lines in the closing range (depth going down)
    if ($i >= 1060) {
        echo "Line " . ($i + 1) . " [depth=$depth, +$o/-$c]: " . trim($line) . "\n";
    }
}
echo "\nFinal balance in M5 section: $depth (should be 0)\n";
