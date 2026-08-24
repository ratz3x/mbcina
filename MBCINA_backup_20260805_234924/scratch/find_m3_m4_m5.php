<?php
$transcriptPath = 'C:/Users/ACER/.gemini/antigravity/brain/1a79bc82-9a51-41a3-90bb-42c36ecf4571/.system_generated/logs/transcript_full.jsonl';
$handle = fopen($transcriptPath, 'r');
$count = 0;

while (($line = fgets($handle)) !== false) {
    if (strpos($line, 'admin-tab-m3_membership') !== false) {
        echo "Line $count has M3!\n";
        file_put_contents(__DIR__ . "/line_m3_$count.json", $line);
    }
    if (strpos($line, 'admin-tab-m4_registration') !== false) {
        echo "Line $count has M4!\n";
        file_put_contents(__DIR__ . "/line_m4_$count.json", $line);
    }
    if (strpos($line, 'admin-tab-m5_forum') !== false) {
        echo "Line $count has M5!\n";
        file_put_contents(__DIR__ . "/line_m5_$count.json", $line);
    }
    $count++;
}
fclose($handle);
