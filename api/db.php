<?php
// db.php - Koneksi Supabase PDO + logAudit helper
// Digunakan oleh api/index.php (router)

ini_set('display_errors', '0');
error_reporting(0);

// Polyfills for PHP 7.4 compatibility on Vercel
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return strlen($needle) === 0 || strpos($haystack, $needle) === 0;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return strlen($needle) === 0 || substr($haystack, -strlen($needle)) === $needle;
    }
}
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    exit(0);
}

// Supabase PostgreSQL Credentials
$supabaseHost = getenv('SUPABASE_DB_HOST') ?: 'aws-0-ap-northeast-1.pooler.supabase.com';
$supabasePort = getenv('SUPABASE_DB_PORT') ?: '6543';
$supabaseDb   = getenv('SUPABASE_DB_NAME') ?: 'postgres';
$supabaseUser = getenv('SUPABASE_DB_USER') ?: 'postgres.gpmpoobvfmwdnbzgofhk';
$supabasePass = getenv('SUPABASE_DB_PASSWORD') ?: 'ssPynlbKpyunChJ2';

$dbLastError = '';

function getSupabasePDO() {
    global $supabaseHost, $supabasePort, $supabaseDb, $supabaseUser, $supabasePass, $dbLastError;

    if (!extension_loaded('pdo_pgsql')) {
        $dbLastError = 'PHP Extension pdo_pgsql is not loaded in serverless environment!';
        return null;
    }

    $connectionAttempts = [
        ['host' => $supabaseHost, 'port' => $supabasePort, 'user' => $supabaseUser],
        ['host' => 'aws-0-ap-northeast-1.pooler.supabase.com', 'port' => '5432', 'user' => $supabaseUser]
    ];

    $errors = [];
    foreach ($connectionAttempts as $attempt) {
        try {
            $dsn = "pgsql:host={$attempt['host']};port={$attempt['port']};dbname=$supabaseDb;sslmode=require;connect_timeout=2";
            $pdo = new PDO($dsn, $attempt['user'], $supabasePass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_TIMEOUT => 2
            ]);
            return $pdo;
        } catch (Throwable $e) {
            $errors[] = "[{$attempt['host']}:{$attempt['port']}] " . $e->getMessage();
        }
    }

    $dbLastError = implode(' | ', $errors);
    return null;
}


function logAudit($userId, $action, $module, $details) {
    $sPdo = getSupabasePDO();
    if ($sPdo) {
        try {
            $logId = 'log_' . uniqid();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Web Portal';
            
            $detailsJson = is_string($details) ? $details : json_encode($details);

            $stmt = $sPdo->prepare("INSERT INTO audit_logs (id, user_id, action, module, details, ip_address, user_agent, timestamp) VALUES (:id, :user_id, :action::audit_action_enum, :module, :details, :ip, :ua, NOW())");
            $stmt->execute([
                ':id' => $logId,
                ':user_id' => $userId ?: 'usr_superadmin',
                ':action' => in_array($action, ['CREATE','UPDATE','DELETE','LOGIN','LOGOUT','SUSPEND','RESET_PASSWORD']) ? $action : 'UPDATE',
                ':module' => $module,
                ':details' => $detailsJson,
                ':ip' => $ip,
                ':ua' => $ua
            ]);
        } catch (Exception $e) {}
    }
}

