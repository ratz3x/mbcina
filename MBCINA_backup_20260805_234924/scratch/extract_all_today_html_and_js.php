<?php
$transcriptPath = 'C:/Users/ACER/.gemini/antigravity/brain/1a79bc82-9a51-41a3-90bb-42c36ecf4571/.system_generated/logs/transcript_full.jsonl';
$handle = fopen($transcriptPath, 'r');
$lineNo = 0;

$m4Blocks = [];
$m5Blocks = [];
$modalBlocks = [];

while (($line = fgets($handle)) !== false) {
    // Look for tool calls in planner responses
    if (strpos($line, 'ReplacementContent') !== false || strpos($line, 'CodeContent') !== false) {
        $data = json_decode($line, true);
        if ($data) {
            foreach ($data['tool_calls'] ?? [] as $tc) {
                $code = $tc['args']['ReplacementContent'] ?? $tc['args']['CodeContent'] ?? '';
                if (strpos($code, 'm4') !== false || strpos($code, 'M4') !== false || strpos($code, 'Pendaftaran Klub') !== false || strpos($code, 'evaluas') !== false) {
                    if (strlen($code) > 1000) {
                        $m4Blocks[$lineNo] = $code;
                    }
                }
                if (strpos($code, 'm5') !== false || strpos($code, 'M5') !== false || strpos($code, 'FORUM') !== false || strpos($code, 'Broadcast') !== false) {
                    if (strlen($code) > 1000) {
                        $m5Blocks[$lineNo] = $code;
                    }
                }
                if (strpos($code, 'modal') !== false && strlen($code) > 1000) {
                    $modalBlocks[$lineNo] = $code;
                }
            }
        }
    }
    $lineNo++;
}
fclose($handle);

echo "Found " . count($m4Blocks) . " M4 blocks, " . count($m5Blocks) . " M5 blocks, " . count($modalBlocks) . " Modal blocks in transcript.\n";

// Save the latest M4 block
if (!empty($m4Blocks)) {
    $latestM4Key = array_key_last($m4Blocks);
    file_put_contents(__DIR__ . '/latest_m4.html', $m4Blocks[$latestM4Key]);
    echo "Saved latest_m4.html from line $latestM4Key (len: " . strlen($m4Blocks[$latestM4Key]) . ")\n";
}

// Save the latest M5 block
if (!empty($m5Blocks)) {
    $latestM5Key = array_key_last($m5Blocks);
    file_put_contents(__DIR__ . '/latest_m5.html', $m5Blocks[$latestM5Key]);
    echo "Saved latest_m5.html from line $latestM5Key (len: " . strlen($m5Blocks[$latestM5Key]) . ")\n";
}
