<?php
$transcriptPath = 'C:/Users/ACER/.gemini/antigravity/brain/1a79bc82-9a51-41a3-90bb-42c36ecf4571/.system_generated/logs/transcript_full.jsonl';
$handle = fopen($transcriptPath, 'r');
$lineNo = 0;

while (($line = fgets($handle)) !== false) {
    if (strpos($line, 'ad_art_url') !== false || strpos($line, 'fee_amount') !== false || strpos($line, 'forum_categories') !== false || strpos($line, 'rentang_waktu') !== false || strpos($line, 'Broadcast Analytics') !== false) {
        $data = json_decode($line, true);
        if ($data) {
            foreach ($data['tool_calls'] ?? [] as $tc) {
                $code = $tc['args']['ReplacementContent'] ?? $tc['args']['CodeContent'] ?? '';
                if (strlen($code) > 1500) {
                    echo "Line $lineNo has match! Len: " . strlen($code) . "\n";
                    file_put_contents(__DIR__ . "/feat_line_$lineNo.txt", $code);
                }
            }
        }
    }
    $lineNo++;
}
fclose($handle);
