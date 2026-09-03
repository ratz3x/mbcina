<?php
// m7_donasi.php - M7 - Donasi, Campaign, Receipt
// Digunakan oleh api/index.php (router)
// $sPdo, $input, $action sudah di-set oleh router

switch ($action) {
    case 'get_donation_campaigns':
        try {
            // Fetch campaigns with dynamically computed collected_amount and donor_count
            $campaigns = $sPdo->query("
                SELECT c.*,
                    COALESCE(SUM(CASE WHEN d.status IN ('SUCCESS','CONFIRMED') THEN d.amount ELSE 0 END), 0) AS collected_amount,
                    COUNT(CASE WHEN d.status IN ('SUCCESS','CONFIRMED') THEN 1 END) AS donor_count
                FROM donation_campaigns c
                LEFT JOIN donations d ON d.campaign_id = c.id
                GROUP BY c.id
                ORDER BY c.created_at DESC
            ")->fetchAll();

            $donations = $sPdo->query("
                SELECT d.*,
                    CASE
                        WHEN u.name IS NOT NULL AND u.name != '' THEN u.name
                        WHEN d.notes LIKE 'Nama: %' THEN TRIM(SPLIT_PART(SPLIT_PART(d.notes, 'Nama: ', 2), ' | ', 1))
                        ELSE 'Hamba Allah'
                    END as donor_name,
                    u.member_id
                FROM donations d
                LEFT JOIN users u ON d.user_id = u.id
                ORDER BY d.created_at DESC
            ")->fetchAll();

            $receipts  = $sPdo->query("
                SELECT r.*, d.amount, d.user_id, d.campaign_id,
                    CASE
                        WHEN u.name IS NOT NULL AND u.name != '' THEN u.name
                        WHEN d.notes LIKE 'Nama: %' THEN TRIM(SPLIT_PART(SPLIT_PART(d.notes, 'Nama: ', 2), ' | ', 1))
                        ELSE 'Hamba Allah'
                    END as donor_name
                FROM donation_receipts r
                JOIN donations d ON r.donation_id = d.id
                LEFT JOIN users u ON d.user_id = u.id
                ORDER BY r.created_at DESC
            ")->fetchAll();

            echo json_encode([
                'success'   => true,
                'campaigns' => $campaigns,
                'donations' => $donations,
                'receipts'  => $receipts,
                'source'    => 'SUPABASE_CLOUD'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create_donation_campaign':
        try {
            $title       = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            $target      = intval($input['target_amount'] ?? 0);
            $startDate   = $input['start_date'] ?? date('Y-m-d');
            $endDate     = $input['end_date'] ?? date('Y-m-d', strtotime('+14 days'));
            $createdBy   = $input['created_by'] ?? 'usr_superadmin';

            if (empty($title) || $target <= 0) {
                echo json_encode(['success' => false, 'message' => 'Judul dan Target Donasi wajib diisi dengan benar!']);
                exit;
            }

            $year = date('Y');
            $stmtLast = $sPdo->query("SELECT id FROM donation_campaigns WHERE id LIKE 'DON-CAMP-$year-%' ORDER BY id DESC LIMIT 1");
            $lastId = $stmtLast->fetchColumn();
            if ($lastId) {
                $parts = explode('-', $lastId);
                $seq = intval(end($parts)) + 1;
            } else {
                $seq = 1;
            }
            $id = 'DON-CAMP-' . $year . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
            $stmt = $sPdo->prepare("INSERT INTO donation_campaigns (id, title, description, target_amount, collected_amount, start_date, end_date, is_active, created_by) VALUES (?, ?, ?, ?, 0, ?, ?, TRUE, ?)");
            $stmt->execute([$id, $title, $description, $target, $startDate, $endDate, $createdBy]);

            logAudit($createdBy, 'CREATE', 'DONATION_CAMPAIGN', ['campaign_id' => $id, 'title' => $title, 'target' => $target]);

            echo json_encode(['success' => true, 'message' => 'Campaign Donasi berhasil dibuat!', 'id' => $id]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'submit_donation':
        try {
            $campaignId    = $input['campaign_id'] ?? '';
            $userId        = $input['user_id'] ?? 'usr_superadmin';
            $amount        = intval($input['amount'] ?? 0);
            $paymentMethod = $input['payment_method'] ?? 'TRANSFER';
            $proofUrl      = $input['payment_proof_url'] ?? 'assets/mb_hero.jpg';
            $notes         = trim($input['notes'] ?? '');
            
            $donorName     = trim($input['donor_name'] ?? '');
            $memberIdInput = trim($input['member_id_input'] ?? '');

            // Validate / resolve campaign_id — if not valid, use the first active campaign
            if (empty($campaignId)) {
                $campaignId = 'camp_yogya_2026';
            }
            $stmtCamp = $sPdo->prepare("SELECT id FROM donation_campaigns WHERE id = ? LIMIT 1");
            $stmtCamp->execute([$campaignId]);
            if (!$stmtCamp->fetchColumn()) {
                // Fall back to first active campaign
                $fallback = $sPdo->query("SELECT id FROM donation_campaigns WHERE is_active = true ORDER BY created_at ASC LIMIT 1")->fetchColumn();
                if ($fallback) $campaignId = $fallback;
            }
            
            // Try to find user by member_id if provided
            if (!empty($memberIdInput)) {
                $stmtUser = $sPdo->prepare("SELECT id FROM users WHERE member_id = ? LIMIT 1");
                $stmtUser->execute([$memberIdInput]);
                $foundUserId = $stmtUser->fetchColumn();
                if ($foundUserId) {
                    $userId = $foundUserId;
                }
            }
            if ((empty($userId) || $userId === 'usr_superadmin') && !empty($donorName)) {
                $stmtUser2 = $sPdo->prepare("SELECT id FROM users WHERE name LIKE ? LIMIT 1");
                $stmtUser2->execute(['%' . $donorName . '%']);
                $foundUserId2 = $stmtUser2->fetchColumn();
                if ($foundUserId2) $userId = $foundUserId2;
            }
            
            // Append identity to notes for safekeeping
            $identityNote = "Nama: " . ($donorName ?: 'Hamba Allah');
            if ($memberIdInput) $identityNote .= " | Member ID: " . $memberIdInput;
            $notes = empty($notes) ? $identityNote : $identityNote . "\n\nCatatan: " . $notes;

            if ($amount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Nominal donasi harus lebih dari 0!']);
                exit;
            }

            $year = date('Y');
            $stmtLast = $sPdo->query("SELECT id FROM donations WHERE id LIKE 'DON-TRX-$year-%' ORDER BY id DESC LIMIT 1");
            $lastId = $stmtLast->fetchColumn();
            if ($lastId) {
                $parts = explode('-', $lastId);
                $seq = intval(end($parts)) + 1;
            } else {
                $seq = 1;
            }
            // If proofUrl is a base64 string, write file to uploads/donations/
            if (!empty($proofUrl) && strpos($proofUrl, 'data:image/') === 0) {
                $dir = __DIR__ . '/../uploads/donations';
                if (!is_dir($dir)) @mkdir($dir, 0777, true);
                $parts = explode(',', $proofUrl);
                $ext = 'jpg';
                if (strpos($parts[0], 'image/png') !== false) $ext = 'png';
                else if (strpos($parts[0], 'image/webp') !== false) $ext = 'webp';
                $filename = 'proof_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                $saved = @file_put_contents($dir . '/' . $filename, base64_decode($parts[1]));
                if ($saved !== false) {
                    $proofUrl = 'uploads/donations/' . $filename;
                }
            }

            $id = 'DON-TRX-' . $year . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
            $stmt = $sPdo->prepare("INSERT INTO donations (id, campaign_id, user_id, amount, payment_method, status, payment_status, payment_proof_url, notes) VALUES (?, ?, ?, ?, ?, 'PENDING', 'PENDING', ?, ?)");
            $stmt->execute([$id, $campaignId, $userId, $amount, $paymentMethod, $proofUrl, $notes]);

            logAudit($userId, 'CREATE', 'DONATION', ['donation_id' => $id, 'amount' => $amount, 'method' => $paymentMethod]);

            echo json_encode(['success' => true, 'message' => 'Donasi berhasil dikirim dan menunggu verifikasi Admin sesuai bukti transfer!', 'id' => $id, 'payment_proof_url' => $proofUrl]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_donation_proof':
        try {
            $donationId = $input['donation_id'] ?? '';
            $proofUrl   = $input['payment_proof_url'] ?? '';

            if (empty($donationId) || empty($proofUrl)) {
                echo json_encode(['success' => false, 'message' => 'Donation ID dan Bukti Transfer wajib diisi!']);
                exit;
            }

            if (strpos($proofUrl, 'data:image/') === 0) {
                $dir = __DIR__ . '/../uploads/donations';
                if (!is_dir($dir)) @mkdir($dir, 0777, true);
                $parts = explode(',', $proofUrl);
                $ext = 'jpg';
                if (strpos($parts[0], 'image/png') !== false) $ext = 'png';
                else if (strpos($parts[0], 'image/webp') !== false) $ext = 'webp';
                $filename = 'proof_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                $saved = @file_put_contents($dir . '/' . $filename, base64_decode($parts[1]));
                if ($saved !== false) {
                    $proofUrl = 'uploads/donations/' . $filename;
                }
            }

            $stmt = $sPdo->prepare("UPDATE donations SET payment_proof_url = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$proofUrl, $donationId]);

            echo json_encode(['success' => true, 'message' => 'Bukti transfer berhasil diperbarui!', 'payment_proof_url' => $proofUrl]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'verify_donation':
        try {
            $donationId = $input['donation_id'] ?? '';
            $status     = strtoupper($input['status'] ?? 'SUCCESS'); // SUCCESS or REJECTED

            $stmt = $sPdo->prepare("SELECT * FROM donations WHERE id = ?");
            $stmt->execute([$donationId]);
            $don = $stmt->fetch();

            if (!$don) {
                echo json_encode(['success' => false, 'message' => 'Donasi tidak ditemukan!']);
                exit;
            }

            $sPdo->prepare("UPDATE donations SET status = ?, payment_status = ? WHERE id = ?")
                 ->execute([$status, $status, $donationId]);

            $pointsEarned = 0;
            $newTier = 'BRONZE';
            $userTotalDon = 0;

            // Recalculate campaign collected_amount from approved donations
            try {
                $sPdo->prepare("UPDATE donation_campaigns SET collected_amount = (SELECT COALESCE(SUM(amount), 0) FROM donations WHERE campaign_id = :cid AND status IN ('SUCCESS','CONFIRMED')), updated_at = NOW() WHERE id = :cid")
                     ->execute([':cid' => $don['campaign_id']]);
            } catch (Exception $eCamp) {}

            if ($status === 'SUCCESS') {
                $amount = (int)($don['amount'] ?? 0);

                // Generate Digital Receipt
                $recId = 'rec_' . uniqid();
                $year = date('Y');
                $stmtLast = $sPdo->query("SELECT receipt_number FROM donation_receipts WHERE receipt_number LIKE 'REC-$year-%' ORDER BY receipt_number DESC LIMIT 1");
                $lastNum = $stmtLast->fetchColumn();
                if ($lastNum) {
                    $parts = explode('-', $lastNum);
                    $seq = intval(end($parts)) + 1;
                } else {
                    $seq = 1;
                }
                $recNum = 'REC-' . $year . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
                $sPdo->prepare("INSERT INTO donation_receipts (id, donation_id, receipt_number, receipt_url, sent_to_email, sent_at) VALUES (?, ?, ?, 'assets/receipt.pdf', TRUE, CURRENT_TIMESTAMP)")
                     ->execute([$recId, $donationId, $recNum]);

                // Identify User & Calculate Gamification Points & Tier Automatically
                $userId = $don['user_id'] ?? '';
                if ((empty($userId) || $userId === 'usr_superadmin') && !empty($don['member_id'])) {
                    $stmtFindU = $sPdo->prepare("SELECT id FROM users WHERE member_id = ? LIMIT 1");
                    $stmtFindU->execute([trim($don['member_id'])]);
                    $foundId = $stmtFindU->fetchColumn();
                    if ($foundId) $userId = $foundId;
                }
                if ((empty($userId) || $userId === 'usr_superadmin') && !empty($don['donor_name'])) {
                    $stmtFindU = $sPdo->prepare("SELECT id FROM users WHERE name LIKE ? LIMIT 1");
                    $stmtFindU->execute(['%' . trim($don['donor_name']) . '%']);
                    $foundId = $stmtFindU->fetchColumn();
                    if ($foundId) $userId = $foundId;
                }
                if (empty($userId) && !empty($don['notes'])) {
                    if (preg_match('/Member ID:\s*(MBINA-[A-Z0-9-]+)/i', $don['notes'], $mMatch)) {
                        $stmtFindU = $sPdo->prepare("SELECT id FROM users WHERE member_id = ? LIMIT 1");
                        $stmtFindU->execute([trim($mMatch[1])]);
                        $userId = $stmtFindU->fetchColumn();
                    }
                }

                if (!empty($userId)) {
                    $uStmt = $sPdo->prepare("SELECT id, name, tier, points, total_donation, member_id FROM users WHERE id = ? LIMIT 1");
                    $uStmt->execute([$userId]);
                    $uRow = $uStmt->fetch();

                    if ($uRow) {
                        // Formula: 1 Poin per Rp 10.000 Donasi (misal Rp 5.000.000 = 500 Poin)
                        $pointsEarned = max(1, intval($amount / 10000));
                        $userTotalDon = ((int)$uRow['total_donation']) + $amount;
                        $oldTier = $uRow['tier'] ?? 'BRONZE';

                        // Automatic Tier Calculation
                        $newTier = 'BRONZE';
                        if ($userTotalDon >= 9000000) $newTier = 'PLATINUM';
                        else if ($userTotalDon >= 4500000) $newTier = 'GOLD';
                        else if ($userTotalDon >= 1500000) $newTier = 'SILVER';

                        // Update User in Supabase Database
                        $updUser = $sPdo->prepare("UPDATE users SET total_donation = :tot, points = points + :pts, tier = :tier WHERE id = :uid");
                        $updUser->execute([
                            ':tot' => $userTotalDon,
                            ':pts' => $pointsEarned,
                            ':tier' => $newTier,
                            ':uid' => $userId
                        ]);

                        // Record Tier Upgrade History & Notification if upgraded
                        if ($newTier !== $oldTier) {
                            $sPdo->prepare("INSERT INTO tier_history (id, user_id, tier, total_donation, year) VALUES (:id, :uid, :tier, :tot, :yr)")
                                 ->execute([':id' => 'th_' . uniqid(), ':uid' => $userId, ':tier' => $newTier, ':tot' => $userTotalDon, ':yr' => (int)date('Y')]);

                            $sPdo->prepare("INSERT INTO notifications (id, user_id, type, title, message) VALUES (:id, :uid, 'UPGRADE', 'Selamat! Tier Anda Naik 🏆', :msg)")
                                 ->execute([':id' => 'notif_' . uniqid(), ':uid' => $userId, ':msg' => "Tier keanggotaan Anda telah naik ke $newTier berkat donasi & kontribusi Anda!"]);
                        }

                        // Activity Log
                        $sPdo->prepare("INSERT INTO user_activities (id, user_id, activity_type, title, detail) VALUES (:id, :uid, 'DONATION', 'Donasi Berhasil & Poin Reward Bertambah', :detail)")
                             ->execute([
                                 ':id' => 'act_' . uniqid(),
                                 ':uid' => $userId,
                                 ':detail' => "Donasi Rp " . number_format($amount, 0, ',', '.') . " terverifikasi (+{$pointsEarned} Poin Reward). Total Donasi: Rp " . number_format($userTotalDon, 0, ',', '.') . " (Tier: $newTier)"
                             ]);
                    }
                }
            }

            logAudit($_SESSION['user_id'] ?? 'usr_superadmin', 'VERIFY', 'DONATION', ['donation_id' => $donationId, 'status' => $status, 'points_earned' => $pointsEarned]);

            echo json_encode([
                'success' => true, 
                'message' => "Donasi berhasil dikonfirmasi status: $status!" . ($pointsEarned > 0 ? " (+{$pointsEarned} Poin & Tier diupdate otomatis ke {$newTier})" : ""),
                'points_earned' => $pointsEarned,
                'tier' => $newTier
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ============================================
    // MODUL M7 - E-COMMERCE & LAPAK ENDPOINTS
    // ============================================

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action in m7_donasi: ' . $action]);
}
