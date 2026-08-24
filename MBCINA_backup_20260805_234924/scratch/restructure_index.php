<?php
$htmlPath = __DIR__ . '/../index.html';
$content = file_get_contents($htmlPath);

$userPos = strpos($content, '<!-- 1.2 MANAJEMEN USER (RBAC) -->');
$m1subDashboardEnd = strpos($content, '</div>', strpos($content, 'id="admin-verification-table"'));
// end of dashboard content div
$dashboardDivEnd = strpos($content, '</div>', $m1subDashboardEnd + 5);

echo "userPos: $userPos, dashboardDivEnd: $dashboardDivEnd\n";

// Extract m1sub-users to m1sub-settings block (up to 3 closing divs before the end)
$m1subBlock = substr($content, $userPos);
// Trim trailing closing divs
$m1subBlock = substr($m1subBlock, 0, strrpos($m1subBlock, '</div>'));
$m1subBlock = substr($m1subBlock, 0, strrpos($m1subBlock, '</div>'));
$m1subBlock = substr($m1subBlock, 0, strrpos($m1subBlock, '</div>'));

// Now cut the m1subBlock out from the bottom
$cleanBottomContent = substr($content, 0, $userPos);

// Cut after dashboard in M1
$dashEndMarker = '<div id="m1sub-dashboard" class="m1-subtab-content">';
$dashPos = strpos($cleanBottomContent, $dashEndMarker);
// Find closing div of m1sub-dashboard
// m1sub-dashboard contains admin-verification-table
$verifPos = strpos($cleanBottomContent, 'id="admin-verification-table"');
$m1subDashClose = strpos($cleanBottomContent, '</div>', strpos($cleanBottomContent, '</div>', $verifPos) + 1) + 6;

$part1 = substr($cleanBottomContent, 0, $m1subDashClose);
$part2 = substr($cleanBottomContent, $m1subDashClose);

// Build new HTML:
// part1 (up to end of m1sub-dashboard) + m1subBlock + </div></div> (close glass-panel and admin-tab-m1_portal) + part2
$newHtml = $part1 . "\n\n" . trim($m1subBlock) . "\n\n        </div>\n      </div>\n\n" . ltrim($part2);

file_put_contents($htmlPath, $newHtml);
echo "✔ Restructured index.html successfully!\n";
