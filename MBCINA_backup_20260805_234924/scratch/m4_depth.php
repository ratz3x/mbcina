<?php
$html = file_get_contents(__DIR__ . '/../index.html');
$lines = explode("\n", $html);

// Find admin-tab-m4_registration start and end
$m4Start = null;
$m4End = null;
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], 'id="admin-tab-m4_registration"') !== false) $m4Start = $i;
    if ($m4Start !== null && strpos($lines[$i], 'id="admin-tab-m5_forum"') !== false) {
        $m4End = $i - 1;
        break;
    }
}
echo "M4 section: lines " . ($m4Start+1) . " to " . ($m4End+1) . "\n";

$o = 0; $c = 0;
for ($i = $m4Start; $i <= $m4End; $i++) {
    $o += preg_match_all('/<div[\s>]/', $lines[$i], $m);
    $c += preg_match_all('/<\/div>/', $lines[$i], $m);
}
echo "M4 divs: $o opens, $c closes, balance: " . ($o - $c) . " (should be 0)\n\n";

// Track depth within M4 to find exact imbalance
$depth = 0;
for ($i = $m4Start; $i <= $m4End; $i++) {
    $line = $lines[$i] ?? '';
    $lo = preg_match_all('/<div[\s>]/', $line, $m);
    $lc = preg_match_all('/<\/div>/', $line, $m);
    $depth += $lo - $lc;
    if ($depth < 0 || ($i >= $m4End - 20)) {
        echo "Line " . ($i+1) . " [depth=$depth, +$lo/-$lc]: " . trim($line) . "\n";
    }
}
echo "\nFinal M4 balance: $depth\n";
