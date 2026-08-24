<?php
$js = file_get_contents(__DIR__ . '/../js/app.js');
$lines = explode("\n", $js);
$fns = ['openM3MemberDetail', 'openEditM3MemberModal', 'openM3AddDonationModal', 'deleteM3Member'];
foreach ($fns as $fn) {
    $found = false;
    foreach ($lines as $i => $line) {
        if (strpos($line, $fn . '(') !== false && (strpos($line, 'function') !== false || strpos($line, $fn . '(') === strpos($line, '  ' . $fn))) {
            echo "✅ $fn — Line " . ($i+1) . ": " . trim($line) . "\n";
            $found = true;
            break;
        }
    }
    if (!$found) {
        // Try simpler match
        foreach ($lines as $i => $line) {
            if (preg_match('/^\s+' . preg_quote($fn, '/') . '\s*\(/', $line) || preg_match('/^\s+async\s+' . preg_quote($fn, '/') . '\s*\(/', $line)) {
                echo "✅ $fn — Line " . ($i+1) . ": " . trim($line) . "\n";
                $found = true;
                break;
            }
        }
    }
    if (!$found) echo "❌ $fn — TIDAK DITEMUKAN sebagai definisi fungsi\n";
}
