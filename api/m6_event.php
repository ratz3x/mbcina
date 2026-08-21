<?php
// m6_event.php - M6 - Event, Sponsorship, Galeri
// Digunakan oleh api/index.php (router)
// $sPdo, $input, $action sudah di-set oleh router

switch ($action) {
    case 'get_m6_init_data':
        try {
            $events = $sPdo->query("SELECT * FROM events ORDER BY date_start DESC")->fetchAll();
            $budgets = $sPdo->query("SELECT * FROM event_budgets ORDER BY created_at DESC")->fetchAll();
            $revenues = $sPdo->query("SELECT * FROM event_revenues ORDER BY created_at DESC")->fetchAll();
            $proposals = $sPdo->query("SELECT * FROM event_proposals ORDER BY created_at DESC")->fetchAll();
            $participants = $sPdo->query("SELECT p.*, u.name as user_name, u.email as user_email, u.phone as user_phone, u.member_id as user_mid, u.tier_id as user_tier FROM event_participants p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.registered_at DESC")->fetchAll();
            $posTx = $sPdo->query("SELECT * FROM event_offline_transactions ORDER BY created_at DESC")->fetchAll();
            $broadcasts = $sPdo->query("SELECT * FROM event_broadcasts ORDER BY created_at DESC")->fetchAll();
            $albums = $sPdo->query("SELECT * FROM event_albums ORDER BY created_at DESC")->fetchAll();
            $media = $sPdo->query("SELECT * FROM event_media ORDER BY uploaded_at DESC")->fetchAll();
            $sponsors = $sPdo->query("SELECT * FROM sponsors ORDER BY created_at DESC")->fetchAll();
            $banners = $sPdo->query("SELECT * FROM sponsor_banners ORDER BY created_at DESC")->fetchAll();
            $reports = $sPdo->query("SELECT * FROM sponsor_reports ORDER BY created_at DESC")->fetchAll();

            echo json_encode([
                'success' => true,
                'events' => $events ?: [],
                'budgets' => $budgets ?: [],
                'revenues' => $revenues ?: [],
                'proposals' => $proposals ?: [],
                'participants' => $participants ?: [],
                'pos_transactions' => $posTx ?: [],
                'broadcasts' => $broadcasts ?: [],
                'albums' => $albums ?: [],
                'media' => $media ?: [],
                'sponsors' => $sponsors ?: [],
                'banners' => $banners ?: [],
                'reports' => $reports ?: []
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal memuat data M6: ' . $e->getMessage()]);
        }
        break;

    case 'save_m6_event':
        try {
            $id = $input['id'] ?? ('evt_' . uniqid());
            $title = $input['title'] ?? 'Event Baru';
            $description = $input['description'] ?? '';
            $type = $input['type'] ?? 'JAMBORE';
            $dateStart = $input['date_start'] ?? date('Y-m-d H:i:s');
            $dateEnd = $input['date_end'] ?? date('Y-m-d H:i:s', strtotime('+2 days'));
            $city = $input['city'] ?? 'Jakarta';
            $capacity = intval($input['capacity'] ?? 100);
            $feeMember = floatval($input['fee_member'] ?? 0);
            $feeNonMember = floatval($input['fee_non_member'] ?? 0);
            $organizerName = $input['organizer_name'] ?? 'Pengurus Pusat MB INA';
            $bannerUrl = $input['banner_url'] ?? 'assets/mb_hero.jpg';
            $status = $input['status'] ?? 'PUBLISHED';

            $stmt = $sPdo->prepare("
                INSERT INTO events (id, title, description, type, date_start, date_end, city, capacity, fee_member, fee_non_member, organizer_name, banner_url, status, created_by)
                VALUES (:id, :title, :desc, :type::event_type_enum, :dstart, :dend, :city, :cap, :fmem, :fnon, :orgname, :banner, :status::event_status_enum, 'usr_superadmin')
                ON CONFLICT (id) DO UPDATE SET
                    title = EXCLUDED.title,
                    description = EXCLUDED.description,
                    type = EXCLUDED.type,
                    date_start = EXCLUDED.date_start,
                    date_end = EXCLUDED.date_end,
                    city = EXCLUDED.city,
                    capacity = EXCLUDED.capacity,
                    fee_member = EXCLUDED.fee_member,
                    fee_non_member = EXCLUDED.fee_non_member,
                    organizer_name = EXCLUDED.organizer_name,
                    banner_url = EXCLUDED.banner_url,
                    status = EXCLUDED.status,
                    updated_at = NOW()
            ");

            $stmt->execute([
                ':id' => $id,
                ':title' => $title,
                ':desc' => $description,
                ':type' => $type,
                ':dstart' => $dateStart,
                ':dend' => $dateEnd,
                ':city' => $city,
                ':cap' => $capacity,
                ':fmem' => $feeMember,
                ':fnon' => $feeNonMember,
                ':orgname' => $organizerName,
                ':banner' => $bannerUrl,
                ':status' => $status
            ]);

            logAudit('usr_superadmin', 'UPDATE', 'M6_EVENT', ['eventId' => $id, 'title' => $title]);
            echo json_encode(['success' => true, 'message' => "Event '$title' berhasil disimpan ke Supabase Cloud!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_m6_event':
        try {
            $id = $_GET['id'] ?? $input['id'] ?? '';
            $stmt = $sPdo->prepare("DELETE FROM events WHERE id = :id");
            $stmt->execute([':id' => $id]);

            logAudit('usr_superadmin', 'DELETE', 'M6_EVENT', ['eventId' => $id]);
            echo json_encode(['success' => true, 'message' => 'Event berhasil dihapus!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create_m6_proposal':
    case 'save_m6_bep_proposal':
        try {
            $propId = $input['id'] ?? ('prop_' . uniqid());
            $eventId = $input['event_id'] ?? ('evt_' . uniqid());
            $title = $input['title'] ?? 'Proposal & BEP Event';
            $description = $input['description'] ?? '';
            $ticketMember = floatval($input['ticket_member_price'] ?? $input['htm_base'] ?? 500000);
            $ticketNonmember = floatval($input['ticket_nonmember_price'] ?? $ticketMember);
            $bepCount = intval($input['bep_ticket_count'] ?? 300);
            $bepAmount = floatval($input['bep_amount'] ?? $input['total_budget'] ?? 75000000);
            $revMin = floatval($input['projected_revenue_min'] ?? 0);
            $revReal = floatval($input['projected_revenue_realistic'] ?? 37500000);
            $revOpt = floatval($input['projected_revenue_optimistic'] ?? 112500000);

            // Generate automatic unique event_code (EVT-2026-001, EVT-2026-002, ...)
            $countStmt = $sPdo->query("SELECT COUNT(*) FROM event_proposals");
            $nextNum = ($countStmt ? intval($countStmt->fetchColumn()) : 0) + 1;
            $eventCode = $input['event_code'] ?? sprintf('EVT-%s-%03d', date('Y'), $nextNum);
            $createdBy = $input['user_id'] ?? $input['created_by'] ?? 'usr_superadmin';

            $stmt = $sPdo->prepare("
                INSERT INTO event_proposals (id, event_id, event_code, title, description, ticket_member_price, ticket_nonmember_price, bep_ticket_count, bep_amount, projected_revenue_min, projected_revenue_realistic, projected_revenue_optimistic, status, created_by)
                VALUES (:id, :eid, :ecode, :title, :desc, :tmem, :tnon, :bepcnt, :bepamt, :rmin, :rreal, :ropt, 'PENDING', :cby)
                ON CONFLICT (id) DO UPDATE SET
                    event_code = EXCLUDED.event_code,
                    title = EXCLUDED.title,
                    description = EXCLUDED.description,
                    ticket_member_price = EXCLUDED.ticket_member_price,
                    ticket_nonmember_price = EXCLUDED.ticket_nonmember_price,
                    bep_ticket_count = EXCLUDED.bep_ticket_count,
                    bep_amount = EXCLUDED.bep_amount,
                    projected_revenue_min = EXCLUDED.projected_revenue_min,
                    projected_revenue_realistic = EXCLUDED.projected_revenue_realistic,
                    projected_revenue_optimistic = EXCLUDED.projected_revenue_optimistic,
                    updated_at = NOW()
            ");

            $stmt->execute([
                ':id' => $propId,
                ':eid' => $eventId,
                ':ecode' => $eventCode,
                ':title' => $title,
                ':desc' => $description,
                ':tmem' => $ticketMember,
                ':tnon' => $ticketNonmember,
                ':bepcnt' => $bepCount,
                ':bepamt' => $bepAmount,
                ':rmin' => $revMin,
                ':rreal' => $revReal,
                ':ropt' => $revOpt,
                ':cby' => $createdBy
            ]);

            logAudit('usr_superadmin', 'CREATE', 'M6_PROPOSAL', ['proposalId' => $propId, 'bepAmount' => $bepAmount]);
            echo json_encode(['success' => true, 'message' => 'Proposal & Perhitungan BEP Event berhasil diajukan ke Presiden!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'approve_m6_proposal':
        try {
            $id = $input['id'] ?? '';
            $status = strtoupper($input['status'] ?? 'APPROVED');
            $notes = trim($input['notes'] ?? '');
            $approvedBy = $input['approved_by'] ?? $input['user_id'] ?? 'usr_superadmin';

            $stmt = $sPdo->prepare("UPDATE event_proposals SET status = :status, president_notes = :notes, rejection_reason = :notes, approved_by = :appby, approved_at = NOW(), updated_at = NOW() WHERE id = :id");
            $stmt->execute([':status' => $status, ':notes' => $notes, ':appby' => $approvedBy, ':id' => $id]);

            logAudit($approvedBy, 'UPDATE', 'M6_PROPOSAL_APPROVE', ['proposalId' => $id, 'status' => $status, 'notes' => $notes]);
            echo json_encode(['success' => true, 'message' => "Keputusan ($status) berhasil disimpan!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'process_m6_pos_transaction':
        try {
            $eventId = $input['event_id'] ?? 'evt_jamnas_19';
            $userId = $input['user_id'] ?? 'usr_m3_001';
            $paymentMethod = strtoupper($input['payment_method'] ?? 'CASH');
            $paymentAmount = floatval($input['payment_amount'] ?? 250000);
            $discountAmount = floatval($input['discount_amount'] ?? 0);
            $cashReceived = floatval($input['cash_received'] ?? 300000);
            $changeAmount = floatval($input['change_amount'] ?? 50000);
            $edcRef = $input['edc_reference'] ?? '';

            $partId = 'part_' . uniqid();
            $txId = 'pos_' . uniqid();

            // Insert participant
            $stmt1 = $sPdo->prepare("
                INSERT INTO event_participants (id, event_id, user_id, ticket_type, fee_paid, payment_status, payment_method, discount_amount, registration_method, registered_by, check_in_status, check_in_at, check_in_method, qr_code)
                VALUES (:id, :eid, :uid, 'MEMBER', :fee, 'VERIFIED', :pmethod, :disc, 'OFFLINE', 'usr_superadmin', TRUE, NOW(), 'QR_CODE', :qr)
                ON CONFLICT (id) DO UPDATE SET
                    fee_paid = EXCLUDED.fee_paid,
                    payment_status = 'VERIFIED',
                    payment_method = EXCLUDED.payment_method,
                    check_in_status = TRUE,
                    check_in_at = NOW()
            ");

            $qrCodeVal = "MBINA-POS-{$eventId}-{$userId}-" . time();
            $stmt1->execute([
                ':id' => $partId,
                ':eid' => $eventId,
                ':uid' => $userId,
                ':fee' => $paymentAmount,
                ':pmethod' => in_array($paymentMethod, ['CASH','TRANSFER','QRIS','EDC','VA']) ? $paymentMethod : 'CASH',
                ':disc' => $discountAmount,
                ':qr' => $qrCodeVal
            ]);

            // Insert POS tx
            $stmt2 = $sPdo->prepare("
                INSERT INTO event_offline_transactions (id, participant_id, payment_method, payment_amount, discount_amount, cash_received, change_amount, edc_reference, performed_by)
                VALUES (:id, :pid, :pmethod, :pamt, :disc, :crec, :camt, :edc, 'usr_superadmin')
            ");

            $stmt2->execute([
                ':id' => $txId,
                ':pid' => $partId,
                ':pmethod' => in_array($paymentMethod, ['CASH','TRANSFER','QRIS','EDC','VA']) ? $paymentMethod : 'CASH',
                ':pamt' => $paymentAmount,
                ':disc' => $discountAmount,
                ':crec' => $cashReceived,
                ':camt' => $changeAmount,
                ':edc' => $edcRef
            ]);

            logAudit('usr_superadmin', 'CREATE', 'M6_POS_OFFLINE', ['txId' => $txId, 'amount' => $paymentAmount, 'method' => $paymentMethod]);
            echo json_encode([
                'success' => true,
                'message' => 'Transaksi Registrasi Offline POS berhasil diproses & E-KTA terkonfirmasi Check-in!',
                'transaction_id' => $txId,
                'qr_code' => $qrCodeVal,
                'change_amount' => $changeAmount
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'process_m6_qr_checkin':
        try {
            $eventId = $input['event_id'] ?? 'evt_jamnas_19';
            $memberId = trim($input['member_id'] ?? '');

            // Find user
            $stmtUser = $sPdo->prepare("SELECT id, name, username, email, phone, role, status, tier, member_id, province, city, birth_date, gender, occupation, vehicle_model, license_plate, points, total_events, total_donation, photo_url, avatar_url, join_date, is_system_architect, is_protected, is_active, created_at, updated_at FROM users WHERE member_id = :mid OR username = :mid OR id = :mid LIMIT 1");
            $stmtUser->execute([':mid' => $memberId]);
            $u = $stmtUser->fetch();

            if (!$u) {
                echo json_encode(['success' => false, 'message' => "❌ Member ID '$memberId' tidak ditemukan di database!"]);
                exit;
            }

            $userId = $u['id'];

            // Check participant
            $stmtP = $sPdo->prepare("SELECT * FROM event_participants WHERE event_id = :eid AND user_id = :uid LIMIT 1");
            $stmtP->execute([':eid' => $eventId, ':uid' => $userId]);
            $p = $stmtP->fetch();

            if (!$p) {
                // Auto register & checkin
                $partId = 'part_' . uniqid();
                $sPdo->exec("INSERT INTO event_participants (id, event_id, user_id, ticket_type, fee_paid, payment_status, payment_method, registration_method, check_in_status, check_in_at, check_in_method)
                VALUES ('$partId', '$eventId', '$userId', 'MEMBER', 250000, 'VERIFIED', 'CASH', 'OFFLINE', TRUE, NOW(), 'QR_CODE')");
            } else {
                $sPdo->exec("UPDATE event_participants SET check_in_status = TRUE, check_in_at = NOW(), check_in_method = 'QR_CODE' WHERE id = '{$p['id']}'");
            }

            // Auto-recalculate total_donation & tier in database SQL
            $sPdo->exec("
                UPDATE users u
                SET total_donation = COALESCE((
                    SELECT SUM(d.amount)
                    FROM donations d
                    WHERE (d.user_id = u.id OR d.member_id = u.member_id)
                      AND d.status IN ('SUCCESS', 'CONFIRMED', 'VERIFIED', 'APPROVED')
                ), 0) + COALESCE((
                    SELECT SUM(p.fee_paid)
                    FROM event_participants p
                    WHERE (p.user_id = u.id OR p.member_id = u.member_id)
                      AND p.payment_status IN ('SUCCESS', 'CONFIRMED', 'VERIFIED', 'APPROVED')
                ), 0);

                UPDATE users u
                SET tier = CASE
                    WHEN u.total_donation >= 9000000 THEN 'PLATINUM'
                    WHEN u.total_donation >= 4500000 THEN 'GOLD'
                    WHEN u.total_donation >= 1500000 THEN 'SILVER'
                    ELSE 'BRONZE'
                END;
            ");

            // Log checkin
            $logId = 'chk_' . uniqid();
            $sPdo->exec("INSERT INTO event_checkin_logs (id, event_id, user_id, scanned_by) VALUES ('$logId', '$eventId', '$userId', 'usr_superadmin')");

            logAudit('usr_superadmin', 'UPDATE', 'M6_CHECKIN', ['eventId' => $eventId, 'userId' => $userId, 'memberId' => $u['member_id']]);
            echo json_encode([
                'success' => true,
                'message' => "✅ Check-in BERHASIL! Member: {$u['name']} ({$u['member_id']})",
                'member' => $u
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'verify_participant_payment':
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $partId = trim($input['participant_id'] ?? $input['id'] ?? '');
            $status = trim($input['status'] ?? 'VERIFIED');

            if (!$partId) {
                echo json_encode(['success' => false, 'message' => 'ID peserta tidak valid!']);
                exit;
            }

            // Update payment_status in event_participants table in Supabase
            $stmt = $sPdo->prepare("UPDATE event_participants SET payment_status = :status WHERE id = :pid OR user_id = :pid OR id LIKE :pidlike");
            $stmt->execute([':status' => $status, ':pid' => $partId, ':pidlike' => "%$partId%"]);

            // Auto-recalculate total_donation & tier in database SQL
            $sPdo->exec("
                UPDATE users u
                SET total_donation = COALESCE((
                    SELECT SUM(d.amount)
                    FROM donations d
                    WHERE d.user_id = u.id
                      AND d.status IN ('SUCCESS', 'CONFIRMED', 'VERIFIED', 'APPROVED')
                ), 0) + COALESCE((
                    SELECT SUM(p.fee_paid)
                    FROM event_participants p
                    WHERE p.user_id = u.id
                      AND p.payment_status IN ('SUCCESS', 'CONFIRMED', 'VERIFIED', 'APPROVED')
                ), 0);

                UPDATE users u
                SET tier = CASE
                    WHEN u.total_donation >= 9000000 THEN 'PLATINUM'
                    WHEN u.total_donation >= 4500000 THEN 'GOLD'
                    WHEN u.total_donation >= 1500000 THEN 'SILVER'
                    ELSE 'BRONZE'
                END;
            ");

            logAudit('usr_superadmin', 'UPDATE', 'M6_VERIFY_PARTICIPANT', ['participant_id' => $partId, 'status' => $status]);
            echo json_encode(['success' => true, 'message' => "Status verifikasi peserta berhasil diperbarui ke $status di database Supabase!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'save_m6_sponsor':
        try {
            $id = $input['id'] ?? ('spn_' . uniqid());
            $eventId = $input['event_id'] ?? 'evt_jamnas_19';
            $companyName = $input['company_name'] ?? 'PT Sponsor Indonesia';
            $contactPerson = $input['contact_person'] ?? 'Humas';
            $contactEmail = $input['contact_email'] ?? 'sponsor@company.com';
            $contactPhone = $input['contact_phone'] ?? '081234567890';
            $packageType = $input['package_type'] ?? 'GOLD';
            $packageAmount = floatval($input['package_amount'] ?? 10000000);
            $packageDescription = $input['package_description'] ?? '';
            $status = $input['status'] ?? 'ACTIVE';
            $logoUrl = $input['logo_url'] ?? 'assets/mb_badge.jpg';
            $bannerUrl = $input['banner_url'] ?? 'assets/mb_hero.jpg';

            $stmt = $sPdo->prepare("
                INSERT INTO sponsors (id, event_id, company_name, contact_person, contact_email, contact_phone, package_type, package_amount, package_description, status, logo_url, banner_url, created_by)
                VALUES (:id, :eid, :cname, :cp, :email, :phone, :ptype::sponsor_package_enum, :pamt, :pdesc, :status::sponsor_status_enum, :logo, :banner, 'usr_superadmin')
                ON CONFLICT (id) DO UPDATE SET
                    company_name = EXCLUDED.company_name,
                    contact_person = EXCLUDED.contact_person,
                    contact_email = EXCLUDED.contact_email,
                    contact_phone = EXCLUDED.contact_phone,
                    package_type = EXCLUDED.package_type,
                    package_amount = EXCLUDED.package_amount,
                    package_description = EXCLUDED.package_description,
                    status = EXCLUDED.status,
                    logo_url = EXCLUDED.logo_url,
                    banner_url = EXCLUDED.banner_url,
                    updated_at = NOW()
            ");

            $stmt->execute([
                ':id' => $id,
                ':eid' => $eventId,
                ':cname' => $companyName,
                ':cp' => $contactPerson,
                ':email' => $contactEmail,
                ':phone' => $contactPhone,
                ':ptype' => $packageType,
                ':pamt' => $packageAmount,
                ':pdesc' => $packageDescription,
                ':status' => $status,
                ':logo' => $logoUrl,
                ':banner' => $bannerUrl
            ]);

            logAudit('usr_superadmin', 'UPDATE', 'M6_SPONSOR', ['sponsorId' => $id, 'company' => $companyName]);
            echo json_encode(['success' => true, 'message' => "Data Sponsorship untuk '$companyName' berhasil disimpan ke Supabase Cloud!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'track_m6_banner_impression':
        try {
            $bannerId = $_GET['id'] ?? $input['id'] ?? '';
            $sPdo->exec("UPDATE sponsor_banners SET impression_count = impression_count + 1 WHERE id = '$bannerId'");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false]);
        }
        break;

    case 'track_m6_banner_click':
        try {
            $bannerId = $_GET['id'] ?? $input['id'] ?? '';
            $sPdo->exec("UPDATE sponsor_banners SET click_count = click_count + 1 WHERE id = '$bannerId'");
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false]);
        }
        break;


    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action in m6_event: ' . $action]);
}
