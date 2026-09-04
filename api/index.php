<?php
// api/index.php - Bulletproof Modular Router MB INA
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    exit(0);
}

// Global exception and error interceptor to prevent any HTTP 500
set_exception_handler(function(Throwable $e) {
    if (!headers_sent()) {
        http_response_code(200);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
    }
    echo json_encode([
        'success' => false,
        'error_type' => get_class($e),
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    exit;
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *');
        }
        echo json_encode([
            'success' => false,
            'fatal_error' => $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line']
        ]);
    }
});

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ensure_tables.php';

// Parse action from all possible sources (GET, POST, raw JSON, QUERY_STRING, REQUEST_URI)
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? [];
$action = $_GET['action'] ?? $input['action'] ?? $_POST['action'] ?? '';

if (empty($action) && !empty($_SERVER['QUERY_STRING'])) {
    parse_str($_SERVER['QUERY_STRING'], $qs);
    $action = $qs['action'] ?? '';
}

if (empty($action) && !empty($_SERVER['REQUEST_URI'])) {
    $parts = parse_url($_SERVER['REQUEST_URI']);
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $qs);
        $action = $qs['action'] ?? '';
    }
}

if (empty($action) && !empty($_SERVER['PATH_INFO'])) {
    $action = trim($_SERVER['PATH_INFO'], '/');
}

$sPdo = getSupabasePDO();
if (!$sPdo) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal terhubung ke Supabase! ' . ($dbLastError ?? 'Koneksi timeout.')
    ]);
    exit;
}

// ─── ACTION → MODULE MAP ────────────────────────────────────────────────────
$moduleMap = [
    'get_app_init_data' => 'm0_init',
    'login' => 'm0_init',
    'forgot_password_request' => 'm0_init',
    'reset_password_submit' => 'm0_init',
    'admin_reset_user_password' => 'm0_init',
    'get_auth_config' => 'm0_init',
    'oauth_sync_user' => 'm0_init',
    'get_admin_stats' => 'm1_admin',
    'get_users' => 'm1_admin',
    'register' => 'm1_admin',
    'create_user' => 'm1_admin',
    'update_user' => 'm1_admin',
    'delete_user' => 'm1_admin',
    'suspend_user' => 'm1_admin',
    'update_status' => 'm1_admin',
    'reset_password' => 'm1_admin',
    'get_audit_logs' => 'm1_admin',
    'get_system_settings' => 'm1_admin',
    'update_system_settings' => 'm1_admin',
    'get_provinces' => 'm1_admin',
    'get_provinces_admin' => 'm1_admin',
    'create_province' => 'm1_admin',
    'update_province' => 'm1_admin',
    'delete_province' => 'm1_admin',
    'get_payment_settings' => 'm1_admin',
    'update_payment_settings' => 'm1_admin',
    'test_payment_connection' => 'm1_admin',
    'upload_image' => 'm1_admin',
    'get_email_templates' => 'm1_admin',
    'update_email_template' => 'm1_admin',
    'test_send_email' => 'm1_admin',
    'get_backups' => 'm1_admin',
    'create_backup' => 'm1_admin',
    'restore_backup' => 'm1_admin',
    'get_m2_organization' => 'm2_sejarah',
    'update_m2_organization' => 'm2_sejarah',
    'get_m2_history' => 'm2_sejarah',
    'save_m2_history' => 'm2_sejarah',
    'delete_m2_history' => 'm2_sejarah',
    'get_m2_founders' => 'm2_sejarah',
    'get_m2_clubs' => 'm2_sejarah',
    'member_join_club' => 'm2_sejarah',
    'create_m2_club' => 'm2_sejarah',
    'update_m2_club' => 'm2_sejarah',
    'delete_m2_club' => 'm2_sejarah',
    'get_m2_vision_mission' => 'm2_sejarah',
    'create_m2_vision_mission' => 'm2_sejarah',
    'update_m2_vision_mission' => 'm2_sejarah',
    'delete_m2_vision_mission' => 'm2_sejarah',
    'get_m2_presidents' => 'm2_sejarah',
    'create_m2_president' => 'm2_sejarah',
    'update_m2_president' => 'm2_sejarah',
    'delete_m2_president' => 'm2_sejarah',
    'get_m2_club_detail' => 'm2_sejarah',
    'get_m2_structure' => 'm3_struktur',
    'create_m2_structure' => 'm3_struktur',
    'update_m2_structure' => 'm3_struktur',
    'delete_m2_structure' => 'm3_struktur',
    'get_m2_governance_periods' => 'm3_struktur',
    'get_m2_advisory_board' => 'm3_struktur',
    'get_m2_honor_council' => 'm3_struktur',
    'get_m3_members' => 'm4_keanggotaan',
    'create_m3_member' => 'm4_keanggotaan',
    'bulk_create_m3_members' => 'm4_keanggotaan',
    'update_m3_member' => 'm4_keanggotaan',
    'verify_m3_member' => 'm4_keanggotaan',
    'get_m3_member_detail' => 'm4_keanggotaan',
    'add_m3_donation' => 'm4_keanggotaan',
    'upload_member_photo' => 'm4_keanggotaan',
    'delete_m3_member' => 'm4_keanggotaan',
    'get_m4_data' => 'm4_keanggotaan',
    'submit_club_application' => 'm4_keanggotaan',
    'upload_club_document' => 'm4_keanggotaan',
    'update_club_application_status' => 'm4_keanggotaan',
    'add_club_application_note' => 'm4_keanggotaan',
    'calculate_club_evaluation' => 'm4_keanggotaan',
    'save_club_evaluation' => 'm4_keanggotaan',
    'get_tiers' => 'm4_keanggotaan',
    'update_tier' => 'm4_keanggotaan',
    'get_m5_data' => 'm5_forum',
    'create_forum_thread' => 'm5_forum',
    'reply_forum_thread' => 'm5_forum',
    'like_forum_post' => 'm5_forum',
    'report_forum_post' => 'm5_forum',
    'create_broadcast' => 'm5_forum',
    'moderate_report' => 'm5_forum',
    'warn_user' => 'm5_forum',
    'get_m6_init_data' => 'm6_event',
    'save_m6_event' => 'm6_event',
    'delete_m6_event' => 'm6_event',
    'create_m6_proposal' => 'm6_event',
    'save_m6_bep_proposal' => 'm6_event',
    'approve_m6_proposal' => 'm6_event',
    'delete_m6_proposal' => 'm6_event',
    'process_m6_pos_transaction' => 'm6_event',
    'process_m6_qr_checkin' => 'm6_event',
    'verify_participant_payment' => 'm6_event',
    'save_m6_sponsor' => 'm6_event',
    'track_m6_banner_impression' => 'm6_event',
    'track_m6_banner_click' => 'm6_event',
    'get_donation_campaigns' => 'm7_donasi',
    'create_donation_campaign' => 'm7_donasi',
    'submit_donation' => 'm7_donasi',
    'verify_donation' => 'm7_donasi',
    'update_donation_proof' => 'm7_donasi',
    'get_m7_data' => 'm8_marketplace',
    'delete_lapak' => 'm8_marketplace',
    'create_lapak' => 'm8_marketplace',
    'verify_lapak' => 'm8_marketplace',
    'renew_lapak_sewa' => 'm8_marketplace',
    'update_lapak' => 'm8_marketplace',
    'create_lapak_product' => 'm8_marketplace',
    'toggle_publish_lapak_product' => 'm8_marketplace',
    'verify_lapak_product' => 'm8_marketplace',
    'delete_lapak_product' => 'm8_marketplace',
    'create_lapak_review' => 'm8_marketplace',
    'migrate_lapak_codes' => 'm8_marketplace',
    'get_sponsorship_inventory_status' => 'm9_monetisasi',
    'create_endorse_contract_with_queue' => 'm9_monetisasi',
    'get_m8_data' => 'm9_monetisasi',
    'create_endorse_package' => 'm9_monetisasi',
    'update_endorse_package' => 'm9_monetisasi',
    'delete_endorse_package' => 'm9_monetisasi',
    'create_endorse_contract' => 'm9_monetisasi',
    'update_endorse_contract' => 'm9_monetisasi',
    'delete_endorse_contract' => 'm9_monetisasi',
    'create_ad_campaign' => 'm9_monetisasi',
    'update_ad_campaign' => 'm9_monetisasi',
    'delete_ad_campaign' => 'm9_monetisasi',
    'create_transaction' => 'm9_monetisasi',
    'generate_invoice' => 'm9_monetisasi',
    'generate_tax_report' => 'm9_monetisasi',
    'get_m9_data' => 'm10_laporan',
    'create_scheduled_report' => 'm10_laporan',
    'delete_scheduled_report' => 'm10_laporan',
    'generate_report_snapshot' => 'm10_laporan',
    'get_landing_sponsors' => 'm_sponsor',
    'save_landing_sponsor' => 'm_sponsor',
    'delete_landing_sponsor' => 'm_sponsor',
    'get_sponsor_dashboard_data' => 'm_sponsor',
    'get_m11_data' => 'm11_koperasi',
    'm11_koperasi' => 'm11_koperasi',
];

$module = $moduleMap[$action] ?? null;

if (!$module) {
    echo json_encode(['success' => true, 'message' => 'MBCINA API v3.0 Modular Engine Active', 'action' => $action]);
    exit;
}

$moduleFile = __DIR__ . '/' . $module . '.php';
if (!file_exists($moduleFile)) {
    echo json_encode(['success' => false, 'message' => 'Module file not found: ' . $module]);
    exit;
}

try {
    require $moduleFile;
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error_type' => get_class($e),
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
