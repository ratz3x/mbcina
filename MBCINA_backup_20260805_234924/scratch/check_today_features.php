<?php
$indexHtml = file_get_contents(__DIR__ . '/../index.html');
$appJs = file_get_contents(__DIR__ . '/../js/app.js');
$apiPhp = file_get_contents(__DIR__ . '/../api.php');

echo "=== SCANNING TODAY'S FEATURES IN CODEBASE ===\n\n";

$features = [
    'M4 Application Form (5 Documents)' => ['ad_art_url', 'management_structure_url', 'domicile_url', 'logo_url', 'fee_amount'],
    'M4 Payment Fee (Rp 350.000)' => ['Rp 350.000', 'payment_proof_url', 'VERIFIED'],
    'M4 Performance Evaluation Time Range' => ['rentang_waktu', 'evaluations', 'evaluation_score', 'renderM4Evaluations'],
    'M5 Forum Categories Grid' => ['forum_categories', 'renderM5Categories', 'm5sub-forum', 'Trending Topics'],
    'M5 Thread Detail & Reply' => ['forum_threads', 'forum_replies', 'renderM5ThreadDetail', 'like_forum_post'],
    'M5 Broadcast & Analytics' => ['broadcasts', 'renderM5Broadcasts', 'openM5CreateBroadcastModal', 'Broadcast Analytics'],
    'M5 Moderasi & Reported Posts' => ['forum_reports', 'renderM5Reports', 'openM5WarnUserModal', 'forum_rules']
];

foreach ($features as $featName => $keywords) {
    echo "Feature: $featName\n";
    foreach ($keywords as $kw) {
        $inHtml = strpos($indexHtml, $kw) !== false;
        $inJs   = strpos($appJs, $kw) !== false;
        $inApi  = strpos($apiPhp, $kw) !== false;

        $hStr = $inHtml ? "HTML ✅" : "HTML ❌";
        $jStr = $inJs   ? "JS ✅"   : "JS ❌";
        $aStr = $inApi  ? "API ✅"  : "API ❌";

        echo sprintf("  - %-30s | %s | %s | %s\n", "'$kw'", $hStr, $jStr, $aStr);
    }
    echo "\n";
}
