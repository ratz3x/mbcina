<?php
$html = file_get_contents(__DIR__ . '/../index.html');
$lines = explode("\n", $html);

$modules = [
    'm1' => ['id' => 'admin-tab-m1_portal', 'next' => 'admin-tab-m2_org'],
    'm2' => ['id' => 'admin-tab-m2_org', 'next' => 'admin-tab-m3_membership'],
    'm3' => ['id' => 'admin-tab-m3_membership', 'next' => 'admin-tab-m4_registration'],
];

foreach ($modules as $name => $mod) {
    $start = null; $end = null;
    for ($i = 0; $i < count($lines); $i++) {
        if (strpos($lines[$i], 'id="' . $mod['id'] . '"') !== false) $start = $i;
        if ($start !== null && strpos($lines[$i], 'id="' . $mod['next'] . '"') !== false) {
            $end = $i - 1; break;
        }
    }
    if (!$start || !$end) { echo "$name: not found\n"; continue; }
    
    $o = 0; $c = 0;
    for ($i = $start; $i <= $end; $i++) {
        $o += preg_match_all('/<div[\s>]/', $lines[$i], $m);
        $c += preg_match_all('/<\/div>/', $lines[$i], $m);
    }
    $balance = $o - $c;
    echo strtoupper($name) . " (lines " . ($start+1) . " to " . ($end+1) . "): $o opens, $c closes, balance: $balance " . ($balance === 0 ? "✅" : "❌") . "\n";
}
