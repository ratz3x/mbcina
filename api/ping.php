<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$extensions = [
    'pdo'       => extension_loaded('pdo'),
    'pdo_pgsql' => extension_loaded('pdo_pgsql'),
    'curl'      => extension_loaded('curl'),
    'json'      => extension_loaded('json'),
];

echo json_encode([
    'success'    => true,
    'status'     => 'PHP running on Vercel',
    'php_version'=> PHP_VERSION,
    'os'         => PHP_OS,
    'extensions' => $extensions,
    'env_supabase_host' => getenv('SUPABASE_DB_HOST') ? 'SET' : 'MISSING',
    'env_supabase_pass' => getenv('SUPABASE_DB_PASSWORD') ? 'SET' : 'MISSING',
    'ts'         => date('Y-m-d H:i:s'),
]);
