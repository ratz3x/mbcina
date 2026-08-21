<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';

$t0 = microtime(true);
$pdo = getSupabasePDO();
$t1 = microtime(true);
$connTime = round(($t1 - $t0) * 1000, 2);

$dbStatus = 'FAILED';
$tableCount = 0;
$userCount = 0;
$dbLastError = '';
if ($pdo) {
    $dbStatus = 'CONNECTED';
    try {
        $tableCount = (int) $pdo->query("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public'")->fetchColumn();
        $userCount = (int) $pdo->query("SELECT count(*) FROM users")->fetchColumn();
    } catch (Throwable $e) {
        $dbLastError .= ' | Query error: ' . $e->getMessage();
    }
}

echo json_encode([
    'success' => true,
    'status' => 'MBCINA API Engine Active',
    'php_version' => PHP_VERSION,
    'pdo_pgsql' => extension_loaded('pdo_pgsql'),
    'database' => [
        'status' => $dbStatus,
        'connect_time_ms' => $connTime,
        'tables_count' => $tableCount,
        'users_count' => $userCount,
        'error' => $dbLastError ?: null
    ],
    'timestamp' => date('Y-m-d H:i:s')
]);
