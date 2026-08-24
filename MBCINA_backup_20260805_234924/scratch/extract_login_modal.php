<?php
$transcriptPath = 'C:/Users/ACER/.gemini/antigravity/brain/1a79bc82-9a51-41a3-90bb-42c36ecf4571/.system_generated/logs/transcript_full.jsonl';
$handle = fopen($transcriptPath, 'r');
$lineNo = 0;

$loginModalBlocks = [];

while (($line = fgets($handle)) !== false) {
    if (strpos($line, 'modal-login') !== false || strpos($line, 'modal-register') !== false) {
        $data = json_decode($line, true);
        if ($data) {
            foreach ($data['tool_calls'] ?? [] as $tc) {
                $content = $tc['args']['ReplacementContent'] ?? $tc['args']['CodeContent'] ?? '';
                if (strpos($content, 'modal-login') !== false && strlen($content) > 2000) {
                    $loginModalBlocks[$lineNo] = $content;
                    echo "Found login modal block at line $lineNo (len: " . strlen($content) . ")\n";
                }
            }
        }
    }
    $lineNo++;
}
fclose($handle);

if (!empty($loginModalBlocks)) {
    $latestKey = array_key_last($loginModalBlocks);
    file_put_contents(__DIR__ . '/latest_login_modal.html', $loginModalBlocks[$latestKey]);
    echo "Saved latest_login_modal.html\n";
}
