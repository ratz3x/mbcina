<?php
$html = file_get_contents(__DIR__ . '/../index.html');

// Count main structural elements
$opens = substr_count($html, '<main');
$closes = substr_count($html, '</main>');
$divOpens = substr_count($html, '<div');
$divCloses = substr_count($html, '</div>');
$bodyOpens = substr_count($html, '<body');
$bodyCloses = substr_count($html, '</body>');
$formOpens = substr_count($html, '<form');
$formCloses = substr_count($html, '</form>');

echo "=== DOM BALANCE REPORT ===\n";
echo "<main> open: $opens, close: $closes\n";
echo "<body> open: $bodyOpens, close: $bodyCloses\n";
echo "<div> open: $divOpens, close: $divCloses\n";
echo "<form> open: $formOpens, close: $formCloses\n\n";

// Find where </main> is
$lines = explode("\n", $html);
echo "Checking main wrapper close and body close positions:\n";
foreach ($lines as $i => $line) {
    if (strpos($line, '</main>') !== false || strpos($line, '</body>') !== false || strpos($line, '</html>') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}

// Also check for view-landing-page open and close
echo "\nview-landing-page div boundaries:\n";
foreach ($lines as $i => $line) {
    if (strpos($line, 'view-landing-page') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}

// Check view-admin-dashboard boundaries
echo "\nview-admin-dashboard div boundaries:\n";
foreach ($lines as $i => $line) {
    if (strpos($line, 'view-admin-dashboard') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
