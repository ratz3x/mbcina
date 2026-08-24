<?php
$transcriptPath = 'C:/Users/ACER/.gemini/antigravity/brain/1a79bc82-9a51-41a3-90bb-42c36ecf4571/.system_generated/logs/transcript_full.jsonl';
$handle = fopen($transcriptPath, 'r');
$lineNo = 0;

while (($line = fgets($handle)) !== false) {
    if (strpos($line, 'admin-indonesia-map') !== false) {
        $data = json_decode($line, true);
        if ($data) {
            foreach ($data['tool_calls'] ?? [] as $tc) {
                $code = $tc['args']['CodeContent'] ?? $tc['args']['ReplacementContent'] ?? '';
                if (strpos($code, 'admin-indonesia-map') !== false && strlen($code) > 2000) {
                    echo "Line $lineNo has map HTML, len: " . strlen($code) . "\n";
                    file_put_contents(__DIR__ . "/map_line_$lineNo.html", $code);
                }
            }
        }
    }
    $lineNo++;
}
fclose($handle);
