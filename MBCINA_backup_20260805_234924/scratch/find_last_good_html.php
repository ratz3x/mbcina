<?php
$transcriptPath = 'C:/Users/ACER/.gemini/antigravity/brain/1a79bc82-9a51-41a3-90bb-42c36ecf4571/.system_generated/logs/transcript_full.jsonl';
$lines = file($transcriptPath);

for ($i = count($lines) - 1; $i >= 0; $i--) {
    $line = $lines[$i];
    if (strpos($line, 'admin-tab-m2_org') !== false && strpos($line, 'admin-tab-m3_membership') !== false && strpos($line, 'admin-tab-m4_registration') !== false && strpos($line, 'admin-tab-m5_forum') !== false) {
        $data = json_decode($line, true);
        if ($data) {
            // Check tool_calls or planner response
            $jsonStr = json_encode($data);
            if (strpos($jsonStr, '<div id="admin-tab-m2_org"') !== false && strpos($jsonStr, '<div id="admin-tab-m3_membership"') !== false) {
                echo "Found full M1-M5 in transcript step index: " . ($data['step_index'] ?? $i) . "\n";
                file_put_contents(__DIR__ . "/step_$i.json", $jsonStr);
            }
        }
    }
}
