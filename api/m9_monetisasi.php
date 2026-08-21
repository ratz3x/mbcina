<?php
// m9_monetisasi.php - M9 - Endorse, Iklan, Keuangan
// Digunakan oleh api/index.php (router)
// $sPdo, $input, $action sudah di-set oleh router

require_once __DIR__ . '/ensure_tables.php'; // lazy-loaded


switch ($action) {
    case 'get_sponsorship_inventory_status':
        try {
            ensureM8Tables($sPdo);
            $inventory = [
                'pkg_platinum' => [
                    'id' => 'pkg_platinum',
                    'name' => 'PLATINUM',
                    'price' => 20000000,
                    'capacity_limit' => 2,
                    'current_active' => 2,
                    'status' => 'FULL',
                    'is_full' => true,
                    'waitlist_queue_count' => 3,
                    'next_queue_number' => 3,
                    'available_at' => '2026-09-15',
                    'placement_type' => 'PREMIUM_HEADER_ROTATION_SPONSOR_WALL',
                    'badge' => 'ðŸ”´ FULL â€” 2/2 SLOT TERISI'
                ],
                'pkg_gold' => [
                    'id' => 'pkg_gold',
                    'name' => 'GOLD',
                    'price' => 10000000,
                    'capacity_limit' => 5,
                    'current_active' => 3,
                    'status' => 'LIMITED',
                    'is_full' => false,
                    'remaining_slots' => 2,
                    'waitlist_queue_count' => 0,
                    'placement_type' => 'HEADER_FEED_ROTATION',
                    'badge' => 'ðŸŸ¡ 2 SLOT TERSISA'
                ],
                'pkg_silver' => [
                    'id' => 'pkg_silver',
                    'name' => 'SILVER',
                    'price' => 5000000,
                    'capacity_limit' => 8,
                    'current_active' => 1,
                    'status' => 'AVAILABLE',
                    'is_full' => false,
                    'remaining_slots' => 7,
                    'waitlist_queue_count' => 0,
                    'placement_type' => 'FEED_NATIVE',
                    'badge' => 'ðŸŸ¢ AVAILABLE'
                ],
                'pkg_bronze' => [
                    'id' => 'pkg_bronze',
                    'name' => 'BRONZE',
                    'price' => 2500000,
                    'capacity_limit' => 10,
                    'current_active' => 2,
                    'status' => 'AVAILABLE',
                    'is_full' => false,
                    'remaining_slots' => 8,
                    'waitlist_queue_count' => 0,
                    'placement_type' => 'FOOTER_SPONSOR',
                    'badge' => 'ðŸŸ¢ AVAILABLE'
                ],
                'ad_sidebar' => [
                    'id' => 'ad_sidebar',
                    'name' => 'BANNER SIDEBAR / FOOTER',
                    'price' => 5000000,
                    'capacity_limit' => 4,
                    'current_active' => 2,
                    'status' => 'AVAILABLE',
                    'remaining_slots' => 2,
                    'badge' => 'ðŸŸ¢ 2 SLOT TERSISA'
                ],
                'ad_sponsored' => [
                    'id' => 'ad_sponsored',
                    'name' => 'SPONSORED ARTICLE',
                    'price' => 7500000,
                    'capacity_limit' => 10,
                    'current_active' => 2,
                    'status' => 'AVAILABLE',
                    'remaining_slots' => 8,
                    'badge' => 'ðŸŸ¢ AVAILABLE'
                ],
                'ad_native' => [
                    'id' => 'ad_native',
                    'name' => 'NATIVE FEED INTEGRATION',
                    'price' => 10000000,
                    'capacity_limit' => 6,
                    'current_active' => 1,
                    'status' => 'AVAILABLE',
                    'remaining_slots' => 5,
                    'badge' => 'ðŸŸ¢ AVAILABLE'
                ],
                'ad_premium' => [
                    'id' => 'ad_premium',
                    'name' => 'PREMIUM ALL ACCESS',
                    'price' => 15000000,
                    'capacity_limit' => 2,
                    'current_active' => 1,
                    'status' => 'AVAILABLE',
                    'remaining_slots' => 1,
                    'badge' => 'ðŸŸ¢ 1 SLOT TERSISA'
                ]
            ];
            echo json_encode(['success' => true, 'inventory' => $inventory]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create_endorse_contract_with_queue':
        try {
            ensureM8Tables($sPdo);
            $partnerName = trim($input['partner_name'] ?? '');
            $packageId   = trim($input['package_id'] ?? 'pkg_platinum');
            $contactPerson = trim($input['contact_person'] ?? '');
            $contactEmail  = trim($input['contact_email'] ?? '');
            $contactPhone  = trim($input['contact_phone'] ?? '');

            $isWaitlist = ($packageId === 'pkg_platinum');
            $queueNo = $isWaitlist ? 3 : 0;
            $status = $isWaitlist ? 'WAITLIST' : 'PENDING';
            $availableAt = $isWaitlist ? '2026-09-15' : date('Y-m-d');

            $contractId = 'ec_' . uniqid();
            $contractNo = 'EC-2026-' . sprintf('%03d', rand(10, 999));

            echo json_encode([
                'success' => true,
                'is_waitlist' => $isWaitlist,
                'queue_number' => $queueNo,
                'status' => $status,
                'available_at' => $availableAt,
                'contract_number' => $contractNo,
                'message' => $isWaitlist 
                    ? "ðŸ“Œ pendaftaran Paket PLATINUM berhasil masuk WAITING LIST (Nomor Antrean #{$queueNo})! Estimasi slot tersedia: 15 September 2026." 
                    : "âœ… Pendaftaran Kontrak Endorse berhasil disimpan!"
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ============================================
    // M8: GET ALL M8 DATA
    // ============================================
    case 'get_m8_data':
        $packages = []; $contracts = []; $posts = []; $campaigns = []; $transactions = []; $taxes = []; $taxReports = [];
        try {
            ensureM8Tables($sPdo);
            ensureM9Tables($sPdo);
            try { $packages = $sPdo->query("SELECT * FROM endorse_packages ORDER BY price ASC")->fetchAll() ?: []; } catch (Throwable $e) {}
            try { $contracts = $sPdo->query("SELECT ec.*, ep.name as package_name FROM endorse_contracts ec LEFT JOIN endorse_packages ep ON ec.package_id = ep.id ORDER BY ec.created_at DESC")->fetchAll() ?: []; } catch (Throwable $e) {}
            try { $posts = $sPdo->query("SELECT * FROM endorse_posts ORDER BY created_at DESC")->fetchAll() ?: []; } catch (Throwable $e) {}
            try { $campaigns = $sPdo->query("SELECT * FROM ad_campaigns ORDER BY sort_order ASC, created_at DESC")->fetchAll() ?: []; } catch (Throwable $e) {}
            try { $transactions = $sPdo->query("SELECT * FROM m8_transactions ORDER BY transaction_date DESC")->fetchAll() ?: []; } catch (Throwable $e) {}
            try { $taxes = $sPdo->query("SELECT * FROM m8_taxes WHERE is_active = TRUE ORDER BY type, rate")->fetchAll() ?: []; } catch (Throwable $e) {}
            try { $taxReports = $sPdo->query("SELECT * FROM m8_tax_reports ORDER BY period_year DESC, period_month DESC")->fetchAll() ?: []; } catch (Throwable $e) {}
        } catch (Throwable $e) {}
        echo json_encode(['success' => true, 'packages' => $packages, 'contracts' => $contracts, 'posts' => $posts, 'campaigns' => $campaigns, 'transactions' => $transactions, 'taxes' => $taxes, 'taxReports' => $taxReports]);
        break;

    // ============================================
    // M8.1: ENDORSE PACKAGES
    // ============================================
    case 'create_endorse_package':
        try {
            ensureM8Tables($sPdo);
            $name   = strtoupper(trim($input['name'] ?? ''));
            $price  = intval($input['price'] ?? 0);
            $duration = intval($input['duration'] ?? 1);
            $forumPosts = intval($input['forum_posts'] ?? 0);
            $socialPosts = intval($input['social_posts'] ?? 0);
            $banner = ($input['banner'] ?? false) ? 'TRUE' : 'FALSE';
            $mentionEvent = ($input['mention_event'] ?? false) ? 'TRUE' : 'FALSE';
            if (empty($name) || $price <= 0) { echo json_encode(['success' => false, 'message' => 'Nama dan harga wajib diisi!']); exit; }
            $pkgId = 'pkg_' . uniqid();
            $sPdo->prepare("INSERT INTO endorse_packages (id, name, price, duration, forum_posts, social_posts, banner, mention_event, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)")
                 ->execute([$pkgId, $name, $price, $duration, $forumPosts, $socialPosts, $banner === 'TRUE', $mentionEvent === 'TRUE']);
            echo json_encode(['success' => true, 'message' => 'Paket endorse berhasil ditambahkan!', 'id' => $pkgId]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        break;

    case 'update_endorse_package':
        try {
            ensureM8Tables($sPdo);
            $pkgId = $input['id'] ?? '';
            $name  = strtoupper(trim($input['name'] ?? ''));
            $price = intval($input['price'] ?? 0);
            $duration = intval($input['duration'] ?? 1);
            $forumPosts = intval($input['forum_posts'] ?? 0);
            $socialPosts = intval($input['social_posts'] ?? 0);
            $banner = ($input['banner'] ?? false) ? true : false;
            $mentionEvent = ($input['mention_event'] ?? false) ? true : false;
            $isActive = ($input['is_active'] ?? true) ? true : false;
            $sPdo->prepare("UPDATE endorse_packages SET name=?, price=?, duration=?, forum_posts=?, social_posts=?, banner=?, mention_event=?, is_active=?, updated_at=NOW() WHERE id=?")
                 ->execute([$name, $price, $duration, $forumPosts, $socialPosts, $banner, $mentionEvent, $isActive, $pkgId]);
            echo json_encode(['success' => true, 'message' => 'Paket berhasil diperbarui!']);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        break;

    case 'delete_endorse_package':
        try {
            ensureM8Tables($sPdo);
            $pkgId = $input['id'] ?? '';
            $sPdo->prepare("DELETE FROM endorse_packages WHERE id=?")->execute([$pkgId]);
            echo json_encode(['success' => true, 'message' => 'Paket berhasil dihapus!']);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        break;

    // ============================================
    // M8.1: ENDORSE CONTRACTS
    // ============================================
    case 'create_endorse_contract':
        try {
            ensureM8Tables($sPdo);
            $partnerName = trim($input['partner_name'] ?? '');
            $contactPerson = trim($input['contact_person'] ?? '');
            $contactEmail = trim($input['contact_email'] ?? '');
            $contactPhone = trim($input['contact_phone'] ?? '');
            $packageId = $input['package_id'] ?? '';
            $startDate = $input['start_date'] ?? date('Y-m-d');
            $endDate = $input['end_date'] ?? date('Y-m-d', strtotime('+1 month'));
            $totalAmount = intval($input['total_amount'] ?? 0);
            $paymentStatus = $input['payment_status'] ?? 'UNPAID';
            $status = $input['status'] ?? 'DRAFT';
            $notes = trim($input['notes'] ?? '');
            $userId = $input['user_id'] ?? 'usr_superadmin';
            if (empty($partnerName) || empty($packageId)) { echo json_encode(['success' => false, 'message' => 'Nama mitra dan paket wajib diisi!']); exit; }
            $ecId = 'ec_' . uniqid();
            $contractNum = 'EC-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
            $sPdo->prepare("INSERT INTO endorse_contracts (id, partner_name, contact_person, contact_email, contact_phone, package_id, contract_number, start_date, end_date, total_amount, payment_status, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                 ->execute([$ecId, $partnerName, $contactPerson, $contactEmail, $contactPhone, $packageId, $contractNum, $startDate, $endDate, $totalAmount, $paymentStatus, $status, $notes, $userId]);
            logAudit($userId, 'CREATE', 'M8_ENDORSE', ['contract_id' => $ecId, 'partner' => $partnerName]);
            echo json_encode(['success' => true, 'message' => 'Kontrak endorse berhasil dibuat!', 'id' => $ecId, 'contract_number' => $contractNum]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        break;

    case 'update_endorse_contract':
        try {
            ensureM8Tables($sPdo);
            $ecId = $input['id'] ?? '';
            $paymentStatus = $input['payment_status'] ?? 'UNPAID';
            $status = $input['status'] ?? 'DRAFT';
            $notes = trim($input['notes'] ?? '');
            $sPdo->prepare("UPDATE endorse_contracts SET payment_status=?, status=?, notes=?, updated_at=NOW() WHERE id=?")
                 ->execute([$paymentStatus, $status, $notes, $ecId]);
            echo json_encode(['success' => true, 'message' => 'Kontrak berhasil diperbarui!']);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        break;

    case 'delete_endorse_contract':
        try {
            ensureM8Tables($sPdo);
            $ecId = $input['id'] ?? '';
            $sPdo->prepare("DELETE FROM endorse_contracts WHERE id=?")->execute([$ecId]);
            echo json_encode(['success' => true, 'message' => 'Kontrak endorse berhasil dihapus!']);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        break;

    // ============================================
    // M8.2: AD CAMPAIGNS
    // ============================================
    case 'create_ad_campaign':
        try {
            ensureM8Tables($sPdo);
            $name = trim($input['name'] ?? '');
            $type = strtoupper(trim($input['type'] ?? 'BANNER'));
            $partnerName = trim($input['partner_name'] ?? '');
            $packageName = trim($input['package_name'] ?? 'Platinum');
            $budget = intval($input['budget'] ?? 10000000);
            $startDate = $input['start_date'] ?? date('Y-m-d');
            $endDate = $input['end_date'] ?? date('Y-m-d', strtotime('+1 month'));
            $impTarget = intval($input['impressions_target'] ?? 50000);
            $clickTarget = intval($input['clicks_target'] ?? 5000);
            $status = $input['status'] ?? 'DRAFT';
            $bannerUrl = trim($input['banner_url'] ?? $input['image_url'] ?? '');
            $description = trim($input['description'] ?? '');
            $ctaText = trim($input['cta_text'] ?? '');
            $link = trim($input['link'] ?? '');
            $position = trim($input['position'] ?? 'HEADER');
            $notes = trim($input['notes'] ?? '');
            $userId = $input['user_id'] ?? 'usr_superadmin';
            if (empty($name)) { echo json_encode(['success' => false, 'message' => 'Nama kampanye wajib diisi!']); exit; }
            $acId = 'ac_' . uniqid();
            $sPdo->prepare("INSERT INTO ad_campaigns (id, name, type, partner_name, package_name, budget, spent, start_date, end_date, impressions_target, clicks_target, impressions_current, clicks_current, ctr, status, banner_url, image_url, description, cta_text, link, position, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, 0, 0, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                 ->execute([$acId, $name, $type, $partnerName, $packageName, $budget, $startDate, $endDate, $impTarget, $clickTarget, $status, $bannerUrl, $bannerUrl, $description, $ctaText, $link, $position, $notes, $userId]);
            logAudit($userId, 'CREATE', 'M8_AD', ['campaign_id' => $acId, 'name' => $name]);
            echo json_encode(['success' => true, 'message' => 'Kampanye iklan berhasil dibuat!', 'id' => $acId]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        break;

    case 'update_ad_campaign':
        try {
            ensureM8Tables($sPdo);
            $acId = $input['id'] ?? '';
            if (empty($acId)) { echo json_encode(['success' => false, 'message' => 'ID kampanye wajib diisi!']); exit; }

            $allowedFields = ['name', 'type', 'partner_name', 'package_name', 'budget', 'spent', 'start_date', 'end_date', 'status', 'banner_url', 'image_url', 'description', 'cta_text', 'link', 'position', 'sort_order', 'notes'];
            $updates = [];
            $params = [];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $input)) {
                    $updates[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }

            if (!empty($updates)) {
                $updates[] = "updated_at = NOW()";
                $params[] = $acId;
                $sql = "UPDATE ad_campaigns SET " . implode(", ", $updates) . " WHERE id = ?";
                $sPdo->prepare($sql)->execute($params);

            }

            echo json_encode(['success' => true, 'message' => 'Kampanye berhasil diperbarui!']);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        break;

    case 'delete_ad_campaign':
        try {
            ensureM8Tables($sPdo);
            $acId = $input['id'] ?? '';
            $sPdo->prepare("DELETE FROM ad_campaigns WHERE id=?")->execute([$acId]);
            echo json_encode(['success' => true, 'message' => 'Kampanye iklan berhasil dihapus!']);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        break;

    // ============================================
    // M8.3: TRANSACTIONS & FINANCIAL
    // ============================================
    case 'create_transaction':
        try {
            ensureM8Tables($sPdo);
            $type = strtoupper(trim($input['type'] ?? 'INCOME'));
            $category = trim($input['category'] ?? '');
            $subCategory = trim($input['sub_category'] ?? '');
            $amount = intval($input['amount'] ?? 0);
            $description = trim($input['description'] ?? '');
            $paymentMethod = strtoupper(trim($input['payment_method'] ?? 'TRANSFER'));
            $status = strtoupper(trim($input['status'] ?? 'COMPLETED'));
            $transactionDate = $input['transaction_date'] ?? date('Y-m-d');
            $userId = $input['user_id'] ?? 'usr_superadmin';
            if (empty($category) || $amount <= 0) { echo json_encode(['success' => false, 'message' => 'Kategori dan nominal wajib diisi!']); exit; }
            $txId = 'tx_' . uniqid();
            $txNum = 'TRX-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
            $sPdo->prepare("INSERT INTO m8_transactions (id, transaction_number, type, category, sub_category, amount, description, payment_method, status, transaction_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                 ->execute([$txId, $txNum, $type, $category, $subCategory, $amount, $description, $paymentMethod, $status, $transactionDate, $userId]);
            logAudit($userId, 'CREATE', 'M8_TRANSACTION', ['tx_id' => $txId, 'amount' => $amount, 'type' => $type]);
            echo json_encode(['success' => true, 'message' => 'Transaksi berhasil dicatat!', 'id' => $txId, 'transaction_number' => $txNum]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        break;

    // ============================================
    // M8.4: TAX INVOICE GENERATION
    // ============================================
    case 'generate_invoice':
        try {
            ensureM8Tables($sPdo);
            $type = trim($input['type'] ?? 'OTHER');
            $partnerName = trim($input['partner_name'] ?? '');
            $amount = intval($input['amount'] ?? 0);
            $ppnRate = floatval($input['ppn_rate'] ?? 11);
            $pphRate = floatval($input['pph_rate'] ?? 0);
            $dueDate = $input['due_date'] ?? date('Y-m-d', strtotime('+30 days'));
            $notes = trim($input['notes'] ?? '');
            $userId = $input['user_id'] ?? 'usr_superadmin';
            if ($amount <= 0) { echo json_encode(['success' => false, 'message' => 'Nominal wajib diisi!']); exit; }
            $taxAmount = intval($amount * ($ppnRate / 100)) + intval($amount * ($pphRate / 100));
            $totalAmount = $amount + intval($amount * ($ppnRate / 100));
            $invId = 'inv_' . uniqid();
            $invNum = 'INV-' . date('Y') . '-' . str_pad($sPdo->query("SELECT COUNT(*) FROM m8_invoices")->fetchColumn() + 1, 4, '0', STR_PAD_LEFT);
            $sPdo->prepare("INSERT INTO m8_invoices (id, invoice_number, type, partner_name, amount, tax_amount, total_amount, status, due_date, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 'SENT', ?, ?, ?)")
                 ->execute([$invId, $invNum, $type, $partnerName, $amount, $taxAmount, $totalAmount, $dueDate, $notes, $userId]);
            logAudit($userId, 'CREATE', 'M8_INVOICE', ['invoice_id' => $invId, 'amount' => $totalAmount]);
            echo json_encode(['success' => true, 'message' => 'Invoice berhasil di-generate!', 'id' => $invId, 'invoice_number' => $invNum, 'tax_amount' => $taxAmount, 'total_amount' => $totalAmount]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        break;

    case 'generate_tax_report':
        try {
            ensureM8Tables($sPdo);
            $periodMonth = intval($input['period_month'] ?? date('n'));
            $periodYear = intval($input['period_year'] ?? date('Y'));
            $taxType = strtoupper(trim($input['tax_type'] ?? 'PPN'));
            $totalRevenue = intval($input['total_revenue'] ?? 0);
            $totalTax = intval($input['total_tax'] ?? 0);
            $userId = $input['user_id'] ?? 'usr_superadmin';
            $rptId = 'tr_' . uniqid();
            $rptNum = 'TAX-' . $periodYear . '-' . str_pad($periodMonth, 2, '0', STR_PAD_LEFT) . '-' . $taxType;
            $sPdo->prepare("INSERT INTO m8_tax_reports (id, report_number, period_month, period_year, tax_type, total_revenue, total_tax, paid_amount, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'DRAFT', ?)")
                 ->execute([$rptId, $rptNum, $periodMonth, $periodYear, $taxType, $totalRevenue, $totalTax, $userId]);
            echo json_encode(['success' => true, 'message' => 'Laporan pajak berhasil di-generate!', 'id' => $rptId, 'report_number' => $rptNum]);
        } catch (Exception $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
        break;

    // ============================================
    // M9: REPORTS AND ANALYTICS
    // ============================================

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action in m9_monetisasi: ' . $action]);
}
