<?php
$transcriptPath = 'C:/Users/ACER/.gemini/antigravity/brain/1a79bc82-9a51-41a3-90bb-42c36ecf4571/.system_generated/logs/transcript_full.jsonl';
$handle = fopen($transcriptPath, 'r');
$lineNo = 0;

$candidates = [];

while (($line = fgets($handle)) !== false) {
    if (strpos($line, 'id="admin-indonesia-map"') !== false && strpos($line, 'stat-members-count') !== false) {
        $data = json_decode($line, true);
        if ($data) {
            foreach ($data['tool_calls'] ?? [] as $tc) {
                $code = $tc['args']['CodeContent'] ?? $tc['args']['ReplacementContent'] ?? '';
                if (strpos($code, 'id="admin-indonesia-map"') !== false && strpos($code, '12.544') !== false) {
                    echo "Found candidate at line $lineNo, len: " . strlen($code) . "\n";
                    $candidates[$lineNo] = $code;
                }
            }
        }
    }
    $lineNo++;
}
fclose($handle);

echo "Total candidates found: " . count($candidates) . "\n";
if (!empty($candidates)) {
    // Pick the largest/latest candidate
    $bestKey = array_key_last($candidates);
    file_put_contents(__DIR__ . '/best_m1_m4.html', $candidates[$bestKey]);
    echo "Saved best_m1_m4.html from line $bestKey!\n";
}
