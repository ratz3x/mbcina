<?php
$transcriptPath = 'C:/Users/ACER/.gemini/antigravity/brain/1a79bc82-9a51-41a3-90bb-42c36ecf4571/.system_generated/logs/transcript_full.jsonl';
$handle = fopen($transcriptPath, 'r');
$lineNo = 0;

$m3Real = '';

while (($line = fgets($handle)) !== false) {
    if (strpos($line, 'MODUL M3') !== false || strpos($line, 'm3sub-') !== false) {
        $data = json_decode($line, true);
        if ($data) {
            foreach ($data['tool_calls'] ?? [] as $tc) {
                $arg = $tc['args']['ReplacementContent'] ?? $tc['args']['CodeContent'] ?? '';
                if (strpos($arg, 'MODUL M3') !== false && strlen($arg) > strlen($m3Real)) {
                    $m3Real = $arg;
                    echo "Found M3 real block at line $lineNo, len: " . strlen($m3Real) . "\n";
                }
            }
        }
    }
    $lineNo++;
}
fclose($handle);

if ($m3Real) {
    file_put_contents(__DIR__ . '/m3_real.html', $m3Real);
    echo "✔ Saved m3_real.html!\n";
} else {
    echo "❌ M3 real block not found in tool_calls!\n";
}
