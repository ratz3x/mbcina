<?php
// Build a proper balanced DOM by tracking div depth from specific points
$html = file_get_contents(__DIR__ . '/../index.html');
$lines = explode("\n", $html);

// Count divs in admin section from line 63 to 1091
$depth = 0;
$issues = [];
for ($i = 62; $i <= 1091; $i++) {
    $line = $lines[$i] ?? '';
    $opens = substr_count($line, '<div') - substr_count($line, '</div>');
    $closes = substr_count($line, '</div>') - substr_count($line, '<div');
    
    $depth += $opens;
    $depth -= $closes;
    
    if (abs($opens) !== 0 || abs($closes) !== 0) {
        $net = $opens - $closes;
        if (abs($net) > 0 && ($i >= 1083 && $i <= 1095)) {
            echo "Line " . ($i + 1) . " [depth after=$depth]: net=$net | " . trim($line) . "\n";
        }
    }
}
echo "\nFinal div depth at line 1091 (should be 0): $depth\n";
