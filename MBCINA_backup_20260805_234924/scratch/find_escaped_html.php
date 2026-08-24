<?php
$transcriptPath = 'C:/Users/ACER/.gemini/antigravity/brain/1a79bc82-9a51-41a3-90bb-42c36ecf4571/.system_generated/logs/transcript_full.jsonl';
$lines = file($transcriptPath);

echo "Searching transcript lines...\n";
foreach ($lines as $idx => $line) {
    if (strpos($line, 'admin-tab-m2_org') !== false && strlen($line) > 2000) {
        // Decode json
        $data = json_decode($line, true);
        if ($data) {
            $jsonStr = json_encode($data);
            if (strpos($jsonStr, 'admin-tab-m3_membership') !== false) {
                echo "Line $idx matches! Saving line to temp json...\n";
                file_put_contents(__DIR__ . "/good_line_$idx.json", $jsonStr);
            }
        }
    }
}
echo "Done!\n";
