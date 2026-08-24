<?php
$jsonStr = file_get_contents(__DIR__ . '/good_line_1873.json');
$data = json_decode($jsonStr, true);

function searchCodeContent($arr) {
    if (is_array($arr)) {
        foreach ($arr as $k => $v) {
            if ($k === 'CodeContent' && is_string($v) && strpos($v, 'admin-tab-m2_org') !== false) {
                return $v;
            }
            if ($k === 'ReplacementContent' && is_string($v) && strpos($v, 'admin-tab-m2_org') !== false) {
                return $v;
            }
            $res = searchCodeContent($v);
            if ($res) return $res;
        }
    }
    return null;
}

$foundContent = searchCodeContent($data);

if (!$foundContent) {
    echo "1873 failed, trying 1828...\n";
    $jsonStr = file_get_contents(__DIR__ . '/good_line_1828.json');
    $data = json_decode($jsonStr, true);
    $foundContent = searchCodeContent($data);
}

if ($foundContent) {
    echo "FOUND HTML CONTENT! Length: " . strlen($foundContent) . "\n";
    file_put_contents(__DIR__ . '/extracted_full.html', $foundContent);
    echo "Saved to extracted_full.html!\n";
} else {
    echo "Not found in 1828 either!\n";
}
