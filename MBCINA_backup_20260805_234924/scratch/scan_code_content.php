<?php
$transcriptPath = 'C:/Users/ACER/.gemini/antigravity/brain/1a79bc82-9a51-41a3-90bb-42c36ecf4571/.system_generated/logs/transcript_full.jsonl';
$handle = fopen($transcriptPath, 'r');
$lineNo = 0;

while (($line = fgets($handle)) !== false) {
    if (strpos($line, 'CodeContent') !== false && strpos($line, 'admin-tab-m2_org') !== false) {
        echo "Found at line $lineNo, len: " . strlen($line) . "\n";
        // decode JSON and get CodeContent
        $data = json_decode($line, true);
        if ($data) {
            foreach ($data['tool_calls'] ?? [] as $tc) {
                if (isset($tc['args']['CodeContent'])) {
                    $code = $tc['args']['CodeContent'];
                    echo " -> CodeContent len: " . strlen($code) . "\n";
                    if (strlen($code) > 20000) {
                        file_put_contents(__DIR__ . "/full_index_$lineNo.html", $code);
                        echo " -> SAVED full_index_$lineNo.html!\n";
                    }
                }
            }
        }
    }
    $lineNo++;
}
fclose($handle);
