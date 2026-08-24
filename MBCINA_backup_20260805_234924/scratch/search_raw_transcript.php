<?php
$transcriptPath = 'C:/Users/ACER/.gemini/antigravity/brain/1a79bc82-9a51-41a3-90bb-42c36ecf4571/.system_generated/logs/transcript_full.jsonl';
$handle = fopen($transcriptPath, 'r');
$count = 0;

$m2Text = '';
$m3Text = '';
$m4Text = '';
$m5Text = '';

while (($line = fgets($handle)) !== false) {
    if (strpos($line, 'admin-tab-m2_org') !== false && strlen($line) > 500) {
        // extract portion around admin-tab-m2_org
        $pos = strpos($line, 'admin-tab-m2_org');
        echo "Found admin-tab-m2_org at line $count pos $pos (len: " . strlen($line) . ")\n";
        file_put_contents(__DIR__ . "/line_$count.json", $line);
    }
    $count++;
}
fclose($handle);
