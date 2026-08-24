<?php
require_once __DIR__ . '/../api.php';

$sPdo = getSupabasePDO();

try {
    $sPdo->exec("
        ALTER TABLE events ADD COLUMN IF NOT EXISTS type VARCHAR(30) DEFAULT 'REGULAR';
        ALTER TABLE events ADD COLUMN IF NOT EXISTS club_id VARCHAR(36);
    ");
    echo "✔ Table events updated with type & club_id columns.\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
