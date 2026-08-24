<?php
$transcriptPath = 'C:/Users/ACER/.gemini/antigravity/brain/1a79bc82-9a51-41a3-90bb-42c36ecf4571/.system_generated/logs/transcript_full.jsonl';
$handle = fopen($transcriptPath, 'r');
$lineNo = 0;

$m4Content = '';
$m5Content = '';

while (($line = fgets($handle)) !== false) {
    if (strpos($line, 'admin-tab-m4_registration') !== false) {
        $data = json_decode($line, true);
        if ($data) {
            foreach ($data['tool_calls'] ?? [] as $tc) {
                $arg = $tc['args']['ReplacementContent'] ?? $tc['args']['CodeContent'] ?? '';
                if (strpos($arg, 'admin-tab-m4_registration') !== false && strlen($arg) > strlen($m4Content)) {
                    $m4Content = $arg;
                    echo "Found M4 block at line $lineNo, len: " . strlen($m4Content) . "\n";
                }
            }
        }
    }
    if (strpos($line, 'admin-tab-m5_forum') !== false) {
        $data = json_decode($line, true);
        if ($data) {
            foreach ($data['tool_calls'] ?? [] as $tc) {
                $arg = $tc['args']['ReplacementContent'] ?? $tc['args']['CodeContent'] ?? '';
                if (strpos($arg, 'admin-tab-m5_forum') !== false && strlen($arg) > strlen($m5Content)) {
                    $m5Content = $arg;
                    echo "Found M5 block at line $lineNo, len: " . strlen($m5Content) . "\n";
                }
            }
        }
    }
    $lineNo++;
}
fclose($handle);

if ($m4Content) file_put_contents(__DIR__ . '/m4_extracted.html', $m4Content);
if ($m5Content) file_put_contents(__DIR__ . '/m5_extracted.html', $m5Content);
