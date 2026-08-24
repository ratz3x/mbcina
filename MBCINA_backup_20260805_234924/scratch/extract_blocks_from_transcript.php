<?php
$transcriptPath = 'C:/Users/ACER/.gemini/antigravity/brain/1a79bc82-9a51-41a3-90bb-42c36ecf4571/.system_generated/logs/transcript_full.jsonl';
$handle = fopen($transcriptPath, 'r');

$m2Html = '';
$m3Html = '';
$m4Html = '';
$m5Html = '';

while (($line = fgets($handle)) !== false) {
    if (empty($m2Html) && strpos($line, 'admin-tab-m2_org') !== false) {
        if (preg_match('/<div id="admin-tab-m2_org".*?<\/div>\s*<\/div>\s*<\/div>/s', $line, $m)) {
            $m2Html = $m[0];
        }
    }
    if (empty($m3Html) && strpos($line, 'admin-tab-m3_membership') !== false) {
        if (preg_match('/<div id="admin-tab-m3_membership".*?<\/div>\s*<\/div>\s*<\/div>/s', $line, $m)) {
            $m3Html = $m[0];
        }
    }
    if (empty($m4Html) && strpos($line, 'admin-tab-m4_registration') !== false) {
        if (preg_match('/<div id="admin-tab-m4_registration".*?<\/div>\s*<\/div>\s*<\/div>/s', $line, $m)) {
            $m4Html = $m[0];
        }
    }
    if (empty($m5Html) && strpos($line, 'admin-tab-m5_forum') !== false) {
        if (preg_match('/<div id="admin-tab-m5_forum".*?<\/div>\s*<\/div>\s*<\/div>/s', $line, $m)) {
            $m5Html = $m[0];
        }
    }
}
fclose($handle);

echo "m2Html length: " . strlen($m2Html) . "\n";
echo "m3Html length: " . strlen($m3Html) . "\n";
echo "m4Html length: " . strlen($m4Html) . "\n";
echo "m5Html length: " . strlen($m5Html) . "\n";

if ($m2Html) file_put_contents(__DIR__ . '/m2.html', $m2Html);
if ($m3Html) file_put_contents(__DIR__ . '/m3.html', $m3Html);
if ($m4Html) file_put_contents(__DIR__ . '/m4.html', $m4Html);
if ($m5Html) file_put_contents(__DIR__ . '/m5.html', $m5Html);
