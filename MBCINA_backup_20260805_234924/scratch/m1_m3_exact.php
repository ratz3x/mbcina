<?php
$html = file_get_contents(__DIR__ . '/../index.html');
$lines = explode("\n", $html);

// M1: find unclosed div (depth never returns to 0 cleanly)
echo "=== M1 SECTION (line 89 to 420): Finding missing close ===\n";
$depth = 0;
for ($i = 88; $i <= 419; $i++) {
    $line = $lines[$i] ?? '';
    $o = preg_match_all('/<div[\s>]/', $line, $m);
    $c = preg_match_all('/<\/div>/', $line, $m);
    $depth += $o - $c;
    if ($i >= 405) {
        echo "Line " . ($i+1) . " [depth=$depth, +$o/-$c]: " . trim($line) . "\n";
    }
}
echo "M1 final depth: $depth (should be 0)\n\n";

// M3: find extra closes
echo "=== M3 SECTION (line 527 to 762): Finding extra closes ===\n";
$depth = 0;
for ($i = 526; $i <= 761; $i++) {
    $line = $lines[$i] ?? '';
    $o = preg_match_all('/<div[\s>]/', $line, $m);
    $c = preg_match_all('/<\/div>/', $line, $m);
    $depth += $o - $c;
    if ($depth < 0 || ($i >= 745)) {
        echo "Line " . ($i+1) . " [depth=$depth, +$o/-$c]: " . trim($line) . "\n";
    }
}
echo "M3 final depth: $depth (should be 0)\n";
