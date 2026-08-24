<?php
$transcriptPath = 'C:/Users/ACER/.gemini/antigravity/brain/1a79bc82-9a51-41a3-90bb-42c36ecf4571/.system_generated/logs/transcript_full.jsonl';
$handle = fopen($transcriptPath, 'r');
$lineNo = 0;

$appJsChanges = [];
$indexHtmlChanges = [];

while (($line = fgets($handle)) !== false) {
    if (strpos($line, 'TargetFile') !== false) {
        $data = json_decode($line, true);
        if ($data) {
            foreach ($data['tool_calls'] ?? [] as $tc) {
                $file = $tc['args']['TargetFile'] ?? '';
                $content = $tc['args']['CodeContent'] ?? $tc['args']['ReplacementContent'] ?? '';
                if (strpos($file, 'app.js') !== false && strlen($content) > 500) {
                    $appJsChanges[] = ['line' => $lineNo, 'content' => $content];
                }
                if (strpos($file, 'index.html') !== false && strlen($content) > 500) {
                    $indexHtmlChanges[] = ['line' => $lineNo, 'content' => $content];
                }
            }
        }
    }
    $lineNo++;
}
fclose($handle);

echo "Total app.js edits in transcript: " . count($appJsChanges) . "\n";
echo "Total index.html edits in transcript: " . count($indexHtmlChanges) . "\n";

foreach ($appJsChanges as $i => $item) {
    echo "app.js edit #$i at line {$item['line']} (len: " . strlen($item['content']) . ")\n";
    file_put_contents(__DIR__ . "/js_edit_$i.txt", $item['content']);
}

foreach ($indexHtmlChanges as $i => $item) {
    echo "index.html edit #$i at line {$item['line']} (len: " . strlen($item['content']) . ")\n";
    file_put_contents(__DIR__ . "/html_edit_$i.txt", $item['content']);
}
