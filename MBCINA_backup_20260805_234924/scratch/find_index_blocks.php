<?php
$transcriptPath = 'C:/Users/ACER/.gemini/antigravity/brain/1a79bc82-9a51-41a3-90bb-42c36ecf4571/.system_generated/logs/transcript_full.jsonl';
$handle = fopen($transcriptPath, 'r');
$count = 0;

$m2Html = '';
$m3Html = '';
$m4Html = '';
$m5Html = '';

while (($line = fgets($handle)) !== false) {
    if (strpos($line, 'admin-tab-m2_org') !== false) {
        $data = json_decode($line, true);
        if (isset($data['tool_calls'])) {
            foreach ($data['tool_calls'] as $tc) {
                if (isset($tc['args']['ReplacementContent']) && strpos($tc['args']['ReplacementContent'], 'admin-tab-m2_org') !== false) {
                    echo "Found M2 replacement chunk at line $count\n";
                    file_put_contents(__DIR__ . "/chunk_m2_$count.txt", $tc['args']['ReplacementContent']);
                }
                if (isset($tc['args']['ReplacementContent']) && strpos($tc['args']['ReplacementContent'], 'admin-tab-m3_membership') !== false) {
                    echo "Found M3 replacement chunk at line $count\n";
                    file_put_contents(__DIR__ . "/chunk_m3_$count.txt", $tc['args']['ReplacementContent']);
                }
                if (isset($tc['args']['ReplacementContent']) && strpos($tc['args']['ReplacementContent'], 'admin-tab-m4_registration') !== false) {
                    echo "Found M4 replacement chunk at line $count\n";
                    file_put_contents(__DIR__ . "/chunk_m4_$count.txt", $tc['args']['ReplacementContent']);
                }
                if (isset($tc['args']['ReplacementContent']) && strpos($tc['args']['ReplacementContent'], 'admin-tab-m5_forum') !== false) {
                    echo "Found M5 replacement chunk at line $count\n";
                    file_put_contents(__DIR__ . "/chunk_m5_$count.txt", $tc['args']['ReplacementContent']);
                }
            }
        }
    }
    $count++;
}
fclose($handle);
echo "Done scanning transcript.\n";
