<?php
$transcriptPath = __DIR__ . '/../.system_generated/logs/transcript.jsonl';
if (!file_exists($transcriptPath)) {
    // Search in appDataDir logs
    $transcriptPath = 'C:/Users/ACER/.gemini/antigravity/brain/1a79bc82-9a51-41a3-90bb-42c36ecf4571/.system_generated/logs/transcript_full.jsonl';
}

echo "Reading transcript from: $transcriptPath ...\n";
$lines = file($transcriptPath);
echo "Total transcript lines: " . count($lines) . "\n";

foreach (array_reverse($lines) as $idx => $line) {
    if (strpos($line, 'admin-tab-m2_org') !== false && strpos($line, 'm2sub-profile') !== false) {
        echo "FOUND M2 IN TRANSCRIPT AT LINE INDEX: $idx\n";
        // decode json step
        $data = json_decode($line, true);
        if ($data) {
            $jsonStr = json_encode($data);
            file_put_contents(__DIR__ . '/extracted_m2.txt', $jsonStr);
            echo "✔ Saved extracted_m2.txt!\n";
            break;
        }
    }
}
