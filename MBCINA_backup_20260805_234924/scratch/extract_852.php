<?php
$jsonStr = file_get_contents(__DIR__ . '/good_line_852.json');
$data = json_decode($jsonStr, true);

function searchAllStrings($arr, &$results) {
    if (is_array($arr)) {
        foreach ($arr as $k => $v) {
            if (is_string($v) && strpos($v, 'admin-tab-m2_org') !== false) {
                $results[] = $v;
            }
            searchAllStrings($v, $results);
        }
    }
}

$results = [];
searchAllStrings($data, $results);

echo "Found " . count($results) . " matching strings in 852!\n";
foreach ($results as $idx => $str) {
    echo "String $idx len: " . strlen($str) . "\n";
    file_put_contents(__DIR__ . "/str_852_$idx.html", $str);
}
