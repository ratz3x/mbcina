<?php
$transcriptPath = 'C:/Users/ACER/.gemini/antigravity/brain/1a79bc82-9a51-41a3-90bb-42c36ecf4571/.system_generated/logs/transcript_full.jsonl';
$lines = file($transcriptPath);

echo "Searching for complete index.html in transcript...\n";

foreach (array_reverse($lines) as $idx => $line) {
    if (strpos($line, 'admin-tab-m2_org') !== false && strpos($line, 'admin-tab-m3_membership') !== false && strpos($line, 'admin-tab-m4_registration') !== false) {
        $data = json_decode($line, true);
        if ($data) {
            // Find tool_calls or content with index.html
            $str = json_encode($data);
            if (preg_match('/<div id="admin-tab-m2_org".*?<!-- VIEW A: LANDING PAGE -->/s', $str, $matches)) {
                echo "FOUND MATCH!\n";
                file_put_contents(__DIR__ . '/m2_to_m5_clean.html', $matches[0]);
                echo "Saved m2_to_m5_clean.html!\n";
                break;
            }
        }
    }
}
