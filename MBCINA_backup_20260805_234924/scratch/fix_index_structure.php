<?php
$htmlPath = __DIR__ . '/../index.html';
$content = file_get_contents($htmlPath);

// Extract the m1sub-users, m1sub-audit, m1sub-settings block
$startMarker = '<!-- 1.2 MANAJEMEN USER (RBAC) -->';
$endMarker = '<!-- 3.1.4.6 BACKUP & RESTORE -->';

$startPos = strpos($content, $startMarker);
if ($startPos === false) {
    echo "ERROR: Start marker not found!\n";
    exit;
}

// Find end of subtab-backup div
$backupPos = strpos($content, 'id="subtab-backup"');
if ($backupPos === false) {
    echo "ERROR: backupPos not found!\n";
    exit;
}

// Find the closing divs after subtab-backup
$closingPos = strpos($content, '<!-- TAB M2: MODUL M2');
if ($closingPos === false) {
    // Check if M2 is earlier
    echo "M2 is earlier, finding end of m1sub-settings...\n";
}

echo "Analyzing structure...\n";
