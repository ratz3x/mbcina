<?php
$html = file_get_contents(__DIR__ . '/../index.html');
$lines = explode("\n", $html);

// Track depth from line 63 to end of view-admin-dashboard at 1080
$depth = 0;
$maxDepth = 0;
$prevDepth = 0;

for ($i = 62; $i <= 1080; $i++) {
    $line = $lines[$i] ?? '';
    $o = preg_match_all('/<div[^>]*>/', $line, $m1);
    $c = preg_match_all('/<\/div>/', $line, $m2);
    $depth += $o;
    $depth -= $c;
    
    // Find lines where depth goes negative (extra close)
    if ($depth < 0 || ($c > 0 && $depth < $prevDepth - 2)) {
        echo "Line " . ($i + 1) . " [depth after=$depth, opens=$o, closes=$c]: " . trim($line) . "\n";
    }
    $prevDepth = $depth;
}
echo "\nFinal depth at line 1080: $depth (should be 0)\n\n";

// Count total divs in M5 section
$m5Start = null;
$m5End = null;
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], 'id="admin-tab-m5_forum"') !== false && $m5Start === null) {
        $m5Start = $i;
    }
    if ($m5Start !== null && strpos($lines[$i], '</div><!-- END view-admin-dashboard -->') !== false) {
        $m5End = $i;
        break;
    }
}

if ($m5Start && $m5End) {
    $m5Opens = 0; $m5Closes = 0;
    for ($i = $m5Start; $i <= $m5End; $i++) {
        $m5Opens += preg_match_all('/<div[^>]*>/', $lines[$i], $m);
        $m5Closes += preg_match_all('/<\/div>/', $lines[$i], $m);
    }
    echo "M5 section (lines " . ($m5Start+1) . " to " . ($m5End+1) . "): $m5Opens opens, $m5Closes closes, balance: " . ($m5Opens - $m5Closes) . " (expecting 0 net)\n";
}
