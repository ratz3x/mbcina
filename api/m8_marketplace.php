<?php
// m8_marketplace.php - M8 - Lapak Marketplace, Produk, Review
// Digunakan oleh api/index.php (router)
// $sPdo, $input, $action sudah di-set oleh router

require_once __DIR__ . '/ensure_tables.php'; // lazy-loaded


switch ($action) {
    case 'get_m7_data':
        try {
            ensureM7Tables($sPdo);
            $lapak = $sPdo->query("SELECT l.*, COALESCE(u.name, u.username, 'Member MB INA') AS pemilik, COALESCE(u.member_id, 'MBINA-JKT-2026-000005') AS member_id, COALESCE(u.tier, 'GOLD') AS tier FROM lapak l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC")->fetchAll() ?: [];
            $products = $sPdo->query("
                SELECT 
                    p.*, 
                    COALESCE(l.name, 'Bursa Jual Beli MB INA') AS lapak_name, 
                    COALESCE(NULLIF(p.contact_whatsapp, ''), l.contact_whatsapp, '081234567890') AS lapak_wa, 
                    COALESCE(NULLIF(p.seller_name, ''), u.name, 'Member MB INA') AS seller_name, 
                    COALESCE(u.member_id, 'MBINA-JKT-2026-000005') AS member_id 
                FROM lapak_products p 
                LEFT JOIN lapak l ON p.lapak_id = l.id 
                LEFT JOIN users u ON (p.user_id = u.id OR l.user_id = u.id) 
                ORDER BY p.created_at DESC
            ")->fetchAll() ?: [];
            $reviews = $sPdo->query("SELECT r.*, COALESCE(u.name, u.username, 'Member MB INA') AS user_name, COALESCE(u.member_id, 'MBINA-HQ-2026-000001') AS member_id FROM lapak_reviews r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC")->fetchAll() ?: [];
            $sewaLogs = $sPdo->query("SELECT s.*, l.name as lapak_name, l.lapak_code, COALESCE(u.name, 'Admin') as creator_name FROM lapak_sewa_logs s LEFT JOIN lapak l ON s.lapak_id = l.id LEFT JOIN users u ON s.created_by = u.id ORDER BY s.created_at DESC")->fetchAll() ?: [];
            $categories = $sPdo->query("SELECT * FROM product_categories ORDER BY display_order ASC")->fetchAll() ?: [];

            echo json_encode([
                'success' => true,
                'lapak' => $lapak,
                'products' => $products,
                'reviews' => $reviews,
                'sewaLogs' => $sewaLogs,
                'categories' => $categories
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_lapak':
        try {
            ensureM7Tables($sPdo);
            $lapakId = $input['lapak_id'] ?? '';
            $userId  = $input['user_id'] ?? 'usr_superadmin';

            $sPdo->prepare("DELETE FROM lapak WHERE id = ?")->execute([$lapakId]);
            $sPdo->prepare("DELETE FROM lapak_products WHERE lapak_id = ?")->execute([$lapakId]);

            logAudit($userId, 'DELETE', 'E_COMMERCE_LAPAK', ['lapak_id' => $lapakId]);

            echo json_encode(['success' => true, 'message' => 'Lapak penyewa berhasil dihapus!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create_lapak':
        try {
            ensureM7Tables($sPdo);
            $userId          = $input['user_id'] ?? 'usr_superadmin';
            $name            = trim($input['name'] ?? '');
            $description     = trim($input['description'] ?? '');
            $category        = trim($input['category'] ?? 'General');
            $contactPhone    = trim($input['contact_phone'] ?? '');
            $contactWhatsapp = trim($input['contact_whatsapp'] ?? '');
            $logoUrl         = trim($input['logo_url'] ?? '');
            $bannerUrl       = trim($input['banner_url'] ?? '');
            $paymentProofUrl = trim($input['payment_proof_url'] ?? '');
            $months          = intval($input['months'] ?? 6);

            if (empty($name) || empty($contactWhatsapp)) {
                echo json_encode(['success' => false, 'message' => 'Nama lapak dan WhatsApp wajib diisi!']);
                exit;
            }

            $year = date('Y');
            $maxSeq = 0;
            try {
                $maxStmt = $sPdo->query("SELECT lapak_code FROM lapak WHERE lapak_code LIKE 'LAPAK-%'");
                if ($maxStmt) {
                    while ($r = $maxStmt->fetch()) {
                        if (preg_match('/LAPAK-\d+-(\d+)/i', $r['lapak_code'], $m)) {
                            $val = intval($m[1]);
                            if ($val > $maxSeq) $maxSeq = $val;
                        }
                    }
                }
            } catch (Exception $ex) {}

            $stmtCount = $sPdo->query("SELECT COUNT(*) FROM lapak");
            $countVal = intval($stmtCount ? $stmtCount->fetchColumn() : 0);
            $seq = max($maxSeq, $countVal) + 1;

            do {
                $lapakCode = 'LAPAK-' . $year . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
                $check = $sPdo->prepare("SELECT COUNT(*) FROM lapak WHERE TRIM(lapak_code) = TRIM(?)");
                $check->execute([$lapakCode]);
                $exists = intval($check->fetchColumn());
                if ($exists > 0) {
                    $seq++;
                }
            } while ($exists > 0);

            $startDate = date('Y-m-d');
            $endDate   = date('Y-m-d', strtotime("+$months months"));

            // Calculate Tier Discount (Base Fee: 5000/month)
            $userTier = 'GOLD';
            try {
                $stmtUser = $sPdo->prepare("SELECT tier FROM users WHERE id = ? OR username = ? OR member_id = ?");
                $stmtUser->execute([$userId, $userId, $userId]);
                $uTier = $stmtUser->fetchColumn();
                if ($uTier) $userTier = strtoupper($uTier);
            } catch (Exception $ex) {}

            $discountPercent = 0;
            if ($userTier === 'PLATINUM') $discountPercent = 20;
            else if ($userTier === 'GOLD') $discountPercent = 15;
            else if ($userTier === 'SILVER') $discountPercent = 10;
            else if ($userTier === 'BRONZE') $discountPercent = 5;

            $originalFee = 5000 * $months;
            $potongan    = intval($originalFee * ($discountPercent / 100.0));
            $finalFee    = $originalFee - $potongan;

            $inserted = false;
            $retry = 0;
            while (!$inserted && $retry < 20) {
                $lapakId = 'lapak_' . uniqid() . '_' . rand(100, 999);
                try {
                    $stmt = $sPdo->prepare("INSERT INTO lapak (id, user_id, lapak_code, name, description, category, contact_phone, contact_whatsapp, logo_url, banner_url, payment_proof_url, sewa_start_date, sewa_end_date, sewa_status, sewa_fee, original_fee, tier_discount, final_fee, sewa_paid_status, is_active, is_verified, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', ?, ?, ?, ?, 'UNPAID', FALSE, FALSE, ?, 'PENDING')");
                    $stmt->execute([$lapakId, $userId, $lapakCode, $name, $description, $category, $contactPhone, $contactWhatsapp, $logoUrl, $bannerUrl, $paymentProofUrl, $startDate, $endDate, $finalFee, $originalFee, $discountPercent, $finalFee, $userId]);
                    $inserted = true;
                } catch (Throwable $tErr) {
                    $seq++;
                    $lapakCode = 'LAPAK-' . $year . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
                    $retry++;
                    if ($retry >= 20) {
                        $lapakCode = 'LAPAK-' . $year . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
                        $stmt = $sPdo->prepare("INSERT INTO lapak (id, user_id, lapak_code, name, description, category, contact_phone, contact_whatsapp, logo_url, banner_url, payment_proof_url, sewa_start_date, sewa_end_date, sewa_status, sewa_fee, original_fee, tier_discount, final_fee, sewa_paid_status, is_active, is_verified, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', ?, ?, ?, ?, 'UNPAID', FALSE, FALSE, ?, 'PENDING')");
                        $stmt->execute([$lapakId, $userId, $lapakCode, $name, $description, $category, $contactPhone, $contactWhatsapp, $logoUrl, $bannerUrl, $paymentProofUrl, $startDate, $endDate, $finalFee, $originalFee, $discountPercent, $finalFee, $userId]);
                        $inserted = true;
                    }
                }
            }

            // Add Sewa Log
            $logId = 'log_' . uniqid();
            $sPdo->prepare("INSERT INTO lapak_sewa_logs (id, lapak_id, action, period_start, period_end, fee, payment_status, notes, created_by) VALUES (?, ?, 'SEWA', ?, ?, ?, 'UNPAID', ?, ?)")
                 ->execute([$logId, $lapakId, $startDate, $endDate, $finalFee, "Sewa lapak baru $months bulan (Diskon $userTier $discountPercent% - Menunggu Verifikasi Transfer)", $userId]);

            logAudit($userId, 'CREATE', 'E_COMMERCE', ['lapak_id' => $lapakId, 'lapak_code' => $lapakCode, 'name' => $name, 'final_fee' => $finalFee]);

            echo json_encode(['success' => true, 'message' => 'Sewa Lapak Baru Berhasil Dibuat & Menunggu Verifikasi Admin!', 'lapak_id' => $lapakId, 'lapak_code' => $lapakCode, 'final_fee' => $finalFee, 'status' => 'PENDING']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'verify_lapak':
        try {
            ensureM7Tables($sPdo);
            $lapakId = $input['lapak_id'] ?? '';
            $status  = strtoupper($input['status'] ?? 'APPROVED'); // APPROVED, REJECTED, REVISION
            $reason  = trim($input['rejection_reason'] ?? '');
            $userId  = $input['user_id'] ?? 'usr_superadmin';

            $isActBool  = ($status === 'APPROVED') ? 1 : 0;
            $isVerBool  = ($status === 'APPROVED') ? 1 : 0;
            $sewaStatus = ($status === 'APPROVED') ? 'ACTIVE' : $status;
            $sewaPaid   = ($status === 'APPROVED') ? 'PAID' : 'UNPAID';

            $sPdo->prepare("UPDATE lapak SET sewa_status = ?, sewa_paid_status = ?, is_active = ?, status = ?, is_verified = ?, rejection_reason = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                 ->execute([$sewaStatus, $sewaPaid, $isActBool, $status, $isVerBool, $reason, $lapakId]);

            logAudit($userId, 'VERIFY', 'E_COMMERCE_LAPAK', ['lapak_id' => $lapakId, 'status' => $status, 'reason' => $reason]);

            echo json_encode(['success' => true, 'message' => "Pengajuan Lapak berhasil di-$status!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'renew_lapak_sewa':
        try {
            ensureM7Tables($sPdo);
            $lapakId = $input['lapak_id'] ?? '';
            $months  = intval($input['months'] ?? 6);
            $userId  = $input['user_id'] ?? 'usr_superadmin';

            $stmt = $sPdo->prepare("SELECT * FROM lapak WHERE id = ?");
            $stmt->execute([$lapakId]);
            $lapak = $stmt->fetch();

            if (!$lapak) {
                echo json_encode(['success' => false, 'message' => 'Lapak tidak ditemukan!']);
                exit;
            }

            $currentEnd = strtotime($lapak['sewa_end_date']);
            $baseDate   = ($currentEnd > time()) ? $currentEnd : time();
            $newEnd     = date('Y-m-d', strtotime("+$months months", $baseDate));

            // Calculate Fee
            $userTier = 'GOLD';
            try {
                $stmtUser = $sPdo->prepare("SELECT tier FROM users WHERE id = ?");
                $stmtUser->execute([$userId]);
                $uTier = $stmtUser->fetchColumn();
                if ($uTier) $userTier = strtoupper($uTier);
            } catch (Exception $ex) {}

            $discountPercent = 0;
            if ($userTier === 'PLATINUM') $discountPercent = 20;
            else if ($userTier === 'GOLD') $discountPercent = 15;
            else if ($userTier === 'SILVER') $discountPercent = 10;
            else if ($userTier === 'BRONZE') $discountPercent = 5;

            $originalFee = 5000 * $months;
            $potongan    = intval($originalFee * ($discountPercent / 100.0));
            $finalFee    = $originalFee - $potongan;

            $sPdo->prepare("UPDATE lapak SET sewa_end_date = ?, sewa_status = 'ACTIVE', sewa_fee = ?, original_fee = ?, tier_discount = ?, final_fee = ?, is_active = TRUE, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                 ->execute([$newEnd, $finalFee, $originalFee, $discountPercent, $finalFee, $lapakId]);

            // Add Sewa Log
            $logId = 'log_' . uniqid();
            $sPdo->prepare("INSERT INTO lapak_sewa_logs (id, lapak_id, action, period_start, period_end, fee, payment_status, notes, created_by) VALUES (?, ?, 'PERPANJANG', ?, ?, ?, 'PAID', ?, ?)")
                 ->execute([$logId, $lapakId, date('Y-m-d'), $newEnd, $finalFee, "Perpanjangan sewa $months bulan (Diskon $userTier $discountPercent%)", $userId]);

            logAudit($userId, 'UPDATE', 'E_COMMERCE', ['lapak_id' => $lapakId, 'new_end_date' => $newEnd, 'final_fee' => $finalFee]);

            echo json_encode(['success' => true, 'message' => "Sewa Lapak berhasil diperpanjang hingga $newEnd (Total: Rp " . number_format($finalFee, 0, ',', '.') . ")!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    case 'update_lapak':
        try {
            ensureM7Tables($sPdo);
            $lapakId         = $input['lapak_id'] ?? '';
            $name            = trim($input['name'] ?? '');
            $description     = trim($input['description'] ?? '');
            $category        = trim($input['category'] ?? 'Parts');
            $contactWhatsapp = trim($input['contact_whatsapp'] ?? '');
            $contactPhone    = trim($input['contact_phone'] ?? $contactWhatsapp);
            $logoUrl         = trim($input['logo_url'] ?? '');
            $bannerUrl       = trim($input['banner_url'] ?? '');
            $paymentProofUrl = trim($input['payment_proof_url'] ?? '');
            $sewaStatus      = trim($input['sewa_status'] ?? 'ACTIVE');
            $addMonths       = intval($input['add_months'] ?? 0);
            $userId          = $input['user_id'] ?? 'usr_superadmin';

            $stmt = $sPdo->prepare("SELECT * FROM lapak WHERE id = ?");
            $stmt->execute([$lapakId]);
            $lapak = $stmt->fetch();

            if (!$lapak) {
                echo json_encode(['success' => false, 'message' => 'Lapak tidak ditemukan!']);
                exit;
            }

            if (empty($name) || empty($contactWhatsapp)) {
                echo json_encode(['success' => false, 'message' => 'Nama lapak dan WhatsApp wajib diisi!']);
                exit;
            }

            $newEnd = $lapak['sewa_end_date'];
            $addedFee = 0;

            if ($addMonths > 0) {
                $currentEnd = strtotime($lapak['sewa_end_date']);
                $baseDate   = ($currentEnd > time()) ? $currentEnd : time();
                $newEnd     = date('Y-m-d', strtotime("+$addMonths months", $baseDate));

                // Tier discount calculation
                $userTier = 'GOLD';
                try {
                    $stmtUser = $sPdo->prepare("SELECT tier FROM users WHERE id = ?");
                    $stmtUser->execute([$userId]);
                    $uTier = $stmtUser->fetchColumn();
                    if ($uTier) $userTier = strtoupper($uTier);
                } catch (Exception $ex) {}

                $discountPercent = 0;
                if ($userTier === 'PLATINUM') $discountPercent = 20;
                else if ($userTier === 'GOLD') $discountPercent = 15;
                else if ($userTier === 'SILVER') $discountPercent = 10;
                else if ($userTier === 'BRONZE') $discountPercent = 5;

                $originalFee = 5000 * $addMonths;
                $potongan    = intval($originalFee * ($discountPercent / 100.0));
                $addedFee    = $originalFee - $potongan;

                // Add log
                $logId = 'log_' . uniqid();
                $sPdo->prepare("INSERT INTO lapak_sewa_logs (id, lapak_id, action, period_start, period_end, fee, payment_status, notes, created_by) VALUES (?, ?, 'PERPANJANG', ?, ?, ?, 'PAID', ?, ?)")
                     ->execute([$logId, $lapakId, date('Y-m-d'), $newEnd, $addedFee, "Perpanjangan sewa $addMonths bulan (Diskon $userTier $discountPercent%)", $userId]);
            }

            $isActBool  = ($sewaStatus === 'ACTIVE') ? 1 : 0;
            $isVerBool  = ($sewaStatus === 'ACTIVE') ? 1 : 0;
            $sewaPaid   = ($sewaStatus === 'ACTIVE') ? 'PAID' : 'UNPAID';
            $mainStatus = ($sewaStatus === 'ACTIVE') ? 'APPROVED' : $sewaStatus;

            $stmtUpdate = $sPdo->prepare("
                UPDATE lapak SET name = ?, description = ?, category = ?, contact_whatsapp = ?, contact_phone = ?, 
                                 logo_url = ?, banner_url = ?, payment_proof_url = COALESCE(NULLIF(?, ''), payment_proof_url), 
                                 sewa_status = ?, sewa_paid_status = ?, is_active = ?, is_verified = ?, status = ?, sewa_end_date = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?
            ");
            $stmtUpdate->execute([$name, $description, $category, $contactWhatsapp, $contactPhone, $logoUrl, $bannerUrl, $paymentProofUrl, $sewaStatus, $sewaPaid, $isActBool, $isVerBool, $mainStatus, $newEnd, $lapakId]);

            logAudit($userId, 'UPDATE', 'E_COMMERCE', ['lapak_id' => $lapakId, 'name' => $name, 'add_months' => $addMonths]);

            echo json_encode(['success' => true, 'message' => 'Data Lapak berhasil diperbarui!', 'lapak_id' => $lapakId]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create_lapak_product':
        try {
            ensureM7Tables($sPdo);
            $prodId          = !empty($input['product_id']) ? trim($input['product_id']) : ('prod_' . uniqid());
            $lapakId         = trim($input['lapak_id'] ?? 'MEMBER_MARKETPLACE');
            $name            = trim($input['name'] ?? '');
            $description     = trim($input['description'] ?? '');
            $price           = intval($input['price'] ?? 0);
            $condition       = strtoupper(trim($input['condition'] ?? 'USED'));
            $location        = trim($input['location'] ?? 'Jakarta');
            $category        = trim($input['category'] ?? 'Parts & Komponen');
            $contactWhatsapp = trim($input['contact_whatsapp'] ?? '081234567890');
            $images          = is_array($input['images'] ?? null) ? json_encode($input['images']) : json_encode([$input['images'] ?? 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=600']);
            $userId          = $input['user_id'] ?? 'usr_superadmin';
            $sellerName      = trim($input['seller_name'] ?? '');

            if (empty($name) || $price <= 0) {
                echo json_encode(['success' => false, 'message' => 'Nama produk dan harga wajib diisi!']);
                exit;
            }

            if (empty($sellerName) && $userId) {
                $sellerName = $sPdo->query("SELECT name FROM users WHERE id = '$userId'")->fetchColumn() ?: 'Member MB INA';
            }

            // Check if updating existing product
            $stmtCheck = $sPdo->prepare("SELECT id FROM lapak_products WHERE id = ?");
            $stmtCheck->execute([$prodId]);
            $existingId = $stmtCheck->fetchColumn();

            if ($existingId) {
                $stmt = $sPdo->prepare("
                    UPDATE lapak_products 
                    SET lapak_id = ?, name = ?, description = ?, price = ?, condition = ?, location = ?, images = ?, category = ?, contact_whatsapp = ?, user_id = ?, seller_name = ?, is_published = TRUE, status = 'APPROVED', updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([$lapakId, $name, $description, $price, $condition, $location, $images, $category, $contactWhatsapp, $userId, $sellerName, $prodId]);
                logAudit($userId, 'UPDATE', 'E_COMMERCE_PRODUCT', ['product_id' => $prodId, 'name' => $name, 'price' => $price]);
                echo json_encode(['success' => true, 'message' => 'Iklan produk & foto berhasil diperbarui dan aktif di katalog!', 'product_id' => $prodId, 'status' => 'APPROVED']);
            } else {
                $stmt = $sPdo->prepare("
                    INSERT INTO lapak_products (id, lapak_id, name, description, price, condition, location, images, views, status, is_published, category, contact_whatsapp, user_id, seller_name)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 'APPROVED', TRUE, ?, ?, ?, ?)
                ");
                $stmt->execute([$prodId, $lapakId, $name, $description, $price, $condition, $location, $images, $category, $contactWhatsapp, $userId, $sellerName]);
                logAudit($userId, 'CREATE', 'E_COMMERCE_PRODUCT', ['product_id' => $prodId, 'name' => $name, 'price' => $price]);
                echo json_encode(['success' => true, 'message' => 'Iklan produk & foto berhasil disimpan dan DITERBITKAN ke Katalog Marketplace!', 'product_id' => $prodId, 'status' => 'APPROVED']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'toggle_publish_lapak_product':
        try {
            ensureM7Tables($sPdo);
            $productId   = $input['product_id'] ?? '';
            $isPublished = !empty($input['is_published']) ? true : false;
            $userId      = $input['user_id'] ?? 'usr_superadmin';

            $sPdo->prepare("UPDATE lapak_products SET is_published = ?, status = CASE WHEN ? = 1 THEN 'APPROVED' ELSE status END, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                 ->execute([$isPublished ? 1 : 0, $isPublished ? 1 : 0, $productId]);

            logAudit($userId, 'PUBLISH', 'E_COMMERCE_PRODUCT', ['product_id' => $productId, 'is_published' => $isPublished]);
            echo json_encode([
                'success' => true,
                'is_published' => $isPublished,
                'message' => $isPublished ? 'ðŸŽ‰ Iklan berhasil DITERBITKAN dan tayang di Katalog Marketplace!' : 'â¸ï¸ Iklan telah di-unpublish dari katalog publik.'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'verify_lapak_product':
        try {
            ensureM7Tables($sPdo);
            $productId = $input['product_id'] ?? '';
            $status    = strtoupper($input['status'] ?? 'APPROVED'); // APPROVED, REJECTED, REVISION
            $reason    = trim($input['rejection_reason'] ?? '');
            $userId    = $input['user_id'] ?? 'usr_superadmin';

            $sPdo->prepare("UPDATE lapak_products SET status = ?, rejection_reason = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                 ->execute([$status, $reason, $productId]);

            logAudit($userId, 'VERIFY', 'E_COMMERCE_PRODUCT', ['product_id' => $productId, 'status' => $status, 'reason' => $reason]);

            echo json_encode(['success' => true, 'message' => "Iklan produk berhasil di-$status!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_lapak_product':
        try {
            ensureM7Tables($sPdo);
            $productId = trim($input['product_id'] ?? $_POST['product_id'] ?? $_GET['product_id'] ?? '');
            $userId    = trim($input['user_id'] ?? $_POST['user_id'] ?? $_GET['user_id'] ?? 'usr_superadmin');

            if (empty($productId)) {
                echo json_encode(['success' => false, 'message' => 'Product ID is required!']);
                exit;
            }

            $stmtDel = $sPdo->prepare("DELETE FROM lapak_products WHERE id = ?");
            $stmtDel->execute([$productId]);

            logAudit($userId, 'DELETE', 'E_COMMERCE_PRODUCT', ['product_id' => $productId]);

            echo json_encode(['success' => true, 'product_id' => $productId, 'message' => 'Iklan produk berhasil dihapus secara permanen dari database!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create_lapak_review':
        try {
            ensureM7Tables($sPdo);
            $lapakId = $input['lapak_id'] ?? '';
            $userId  = $input['user_id'] ?? 'usr_superadmin';
            $rating  = intval($input['rating'] ?? 5);
            $content = trim($input['content'] ?? '');

            if (empty($lapakId) || $rating < 1 || $rating > 5) {
                echo json_encode(['success' => false, 'message' => 'Lapak ID dan rating (1-5) wajib valid!']);
                exit;
            }

            $revId = 'rev_' . uniqid();
            $sPdo->prepare("INSERT INTO lapak_reviews (id, lapak_id, user_id, rating, content) VALUES (?, ?, ?, ?, ?)")
                 ->execute([$revId, $lapakId, $userId, $rating, $content]);

            logAudit($userId, 'CREATE', 'E_COMMERCE_REVIEW', ['review_id' => $revId, 'rating' => $rating]);

            echo json_encode(['success' => true, 'message' => 'Review & Rating berhasil dikirim!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ============================================
    // M8: GET SPONSORSHIP INVENTORY STATUS & WAITLIST QUEUE
    // ============================================

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action in m8_marketplace: ' . $action]);
}
