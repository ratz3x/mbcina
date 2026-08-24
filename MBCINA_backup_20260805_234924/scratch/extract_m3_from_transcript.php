<?php
$lineJson = file_get_contents(__DIR__ . '/good_line_852.json');
$data = json_decode($lineJson, true);

echo "Searching line 852 for M3...\n";

function searchM3InArray($arr) {
    if (is_array($arr)) {
        foreach ($arr as $k => $v) {
            if (is_string($v) && strpos($v, 'admin-tab-m3_membership') !== false) {
                return $v;
            }
            $res = searchM3InArray($v);
            if ($res) return $res;
        }
    }
    return null;
}

$m3Content = searchM3InArray($data);

if (!$m3Content) {
    // Scan all lines in transcript for admin-tab-m3_membership
    $transcriptPath = 'C:/Users/ACER/.gemini/antigravity/brain/1a79bc82-9a51-41a3-90bb-42c36ecf4571/.system_generated/logs/transcript_full.jsonl';
    $handle = fopen($transcriptPath, 'r');
    $lineNo = 0;
    while (($line = fgets($handle)) !== false) {
        if (strpos($line, 'admin-tab-m3_membership') !== false && strlen($line) > 2000) {
            $data2 = json_decode($line, true);
            if ($data2) {
                $res = searchM3InArray($data2);
                if ($res && strlen($res) > 2000) {
                    $m3Content = $res;
                    echo "Found M3 at transcript line $lineNo! Length: " . strlen($m3Content) . "\n";
                    break;
                }
            }
        }
        $lineNo++;
    }
    fclose($handle);
}

if ($m3Content) {
    file_put_contents(__DIR__ . '/m3_extracted.html', $m3Content);
    echo "✔ Successfully extracted M3 HTML to m3_extracted.html!\n";
} else {
    echo "❌ M3 not found!\n";
}
