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
            $participants = $sPdo->query("
                SELECT p.id, p.event_id, p.event_id as event_code, p.user_id,
                       COALESCE(NULLIF(p.user_name, ''), u.name, 'Peserta MB INA') as user_name,
                       COALESCE(NULLIF(p.user_name, ''), u.name, 'Peserta MB INA') as name,
                       COALESCE(NULLIF(u.email, ''), '') as user_email,
                       COALESCE(NULLIF(u.phone, ''), '') as user_phone,
                       COALESCE(NULLIF(u.phone, ''), '') as phone,
                       COALESCE(NULLIF(u.member_id, ''), p.user_id, '') as user_mid,
                       COALESCE(NULLIF(u.member_id, ''), p.user_id, '') as member_id,
                       COALESCE(NULLIF(p.club_name, ''), u.club, 'HQ MB INA') as club_name,
                       COALESCE(NULLIF(p.club_name, ''), u.club, 'HQ MB INA') as club,
                       COALESCE(NULLIF(p.ticket_type, ''), 'MEMBER') as ticket_type,
                       COALESCE(NULLIF(u.tier_id, ''), p.ticket_type, 'Platinum') as tier,
                       COALESCE(p.payment_status, 'PENDING') as payment_status,
                       COALESCE(p.payment_status, 'PENDING') as status,
                       COALESCE(p.fee_paid, 0) as fee_paid,
                       COALESCE(p.fee_paid, 0) as htm,
                       COALESCE(p.discount_amount, 0) as discount_amount,
                       p.registration_method,
                       p.payment_method,
                       p.check_in_status,
                       p.check_in_at,
                       p.qr_code,
                       TO_CHAR(COALESCE(p.registered_at, p.created_at, NOW()), 'DD/MM/YYYY HH24:MI') as created_at
                FROM event_participants p 
                LEFT JOIN users u ON (p.user_id = u.id OR p.user_id = u.member_id)
                ORDER BY COALESCE(p.registered_at, p.created_at) DESC
            ")->fetchAll();
            $posTx = $sPdo->query("SELECT * FROM event_offline_transactions ORDER BY created_at DESC")->fetchAll();
            $broadcasts = $sPdo->query("SELECT * FROM event_broadcasts ORDER BY created_at DESC")->fetchAll();
            $rawAlbums = $sPdo->query("SELECT * FROM event_albums ORDER BY created_at DESC")->fetchAll();
            $rawMedia  = $sPdo->query("SELECT * FROM event_media ORDER BY uploaded_at DESC")->fetchAll();

            // Try to clean up dummy rows from PostgreSQL if connected
            try {
                $sPdo->exec("DELETE FROM event_media WHERE id IN ('med_001', 'med_002', 'med_1', 'med_2', 'med_3', 'med_4', 'med_5', 'med_anniv_yt_1', 'med_anniv_vid_1', 'med_anniv_img_1') OR album_id IN ('alb_001', 'alb_002', 'alb_1', 'alb_2', 'alb_3')");
                $sPdo->exec("DELETE FROM event_albums WHERE id IN ('alb_001', 'alb_002', 'alb_1', 'alb_2', 'alb_3')");
            } catch (Exception $ign) {}

            // Filter dummy albums
            $albums = array_values(array_filter($rawAlbums ?: [], function($a) {
                $id = strtolower($a['id'] ?? '');
                $title = strtolower($a['title'] ?? '');
                if (in_array($id, ['alb_001', 'alb_002', 'alb_1', 'alb_2', 'alb_3'])) return false;
                if (str_contains($title, 'gala dinner') || str_contains($title, 'opening ceremony') || str_contains($title, 'parade mercedes') || str_contains($title, 'bakti sosial')) return false;
                return true;
            }));

            // Ensure official album exists
            $hasAnniv = false;
            foreach ($albums as $a) {
                if (($a['id'] ?? '') === 'alb_anniv_2026') { $hasAnniv = true; break; }
            }
            if (!$hasAnniv) {
                array_unshift($albums, [
                    'id' => 'alb_anniv_2026',
                    'event_id' => 'EVT-2026-012',
                    'title' => '📁 Mercedes-Benz Club 22nd Anniversary & Rakernas 2026',
                    'description' => 'Dokumentasi resmi HUT ke-22 MB Club Indonesia, Press Conference Jamnas XXI, & Rakernas di TOPGOLF Jakarta',
                    'cover_image' => 'assets/docs/dok_pressconf_anniv2026.jpg',
                    'gdrive_url' => 'https://drive.google.com/drive/folders/1-MBINA-22nd-Anniversary-Rakernas-Master-Arsip-2026',
                    'is_public' => true,
                    'created_by' => 'Derist Touriano (Admin)',
                    'created_at' => '2026-09-05 14:00:00',
                    'views' => 180
                ]);
            }

            // Attach gdrive_url and cover_image to alb_anniv_2026
            foreach ($albums as &$a) {
                if (($a['id'] ?? '') === 'alb_anniv_2026') {
                    if (empty($a['gdrive_url'])) {
                        $a['gdrive_url'] = 'https://drive.google.com/drive/folders/1-MBINA-22nd-Anniversary-Rakernas-Master-Arsip-2026';
                    }
                    $a['cover_image'] = 'assets/docs/dok_pressconf_anniv2026.jpg';
                }
            }
            unset($a);

            // Filter dummy & blob media
            $media = array_values(array_filter($rawMedia ?: [], function($m) {
                $id = strtolower($m['id'] ?? '');
                $albId = strtolower($m['album_id'] ?? '');
                $cap = strtolower($m['caption'] ?? '');
                $url = strtolower($m['media_url'] ?? '');
                if (str_starts_with($url, 'blob:')) return false;
                if (in_array($id, ['med_001', 'med_002', 'med_1', 'med_2', 'med_3', 'med_4', 'med_5', 'med_anniv_yt_1', 'med_anniv_vid_1', 'med_anniv_img_1', 'med_anniv_photo_1', 'med_anniv_photo_2', 'med_anniv_photo_3'])) return false;
                if (in_array($albId, ['alb_001', 'alb_002', 'alb_1', 'alb_2', 'alb_3'])) return false;
                if (str_contains($cap, 'gala dinner') || str_contains($cap, 'opening ceremony') || str_contains($cap, 'parade mercedes') || str_contains($cap, 'bakti sosial')) return false;
                return true;
            }));

            // If no media exists in database, provide the exact 2 official media
            if (empty($media)) {
                $media = [
                    [
                        'id' => 'med_anniv_photo_pressconf',
                        'album_id' => 'alb_anniv_2026',
                        'media_url' => 'assets/docs/dok_pressconf_anniv2026.jpg',
                        'file_name' => 'Dokumentasi_Foto_Press_Conference_2026.jpg',
                        'type' => 'IMAGE',
                        'caption' => 'Dokumentasi Foto - Mercedes-Benz Club 22nd Anniversary & Rakernas 2026',
                        'uploaded_by' => 'Derist Touriano',
                        'uploaded_at' => '08/09/2026 17:00',
                        'view_count' => 0,
                        'download_count' => 0,
                        'tags' => ['Press Conference', 'Derist Touriano', 'MB Club Indonesia']
                    ],
                    [
                        'id' => 'med_anniv_yt_user',
                        'album_id' => 'alb_anniv_2026',
                        'media_url' => 'https://youtu.be/PH9foXkufB8',
                        'youtube_id' => 'PH9foXkufB8',
                        'is_youtube' => true,
                        'file_name' => 'YouTube: Dokumentasi & Aftermovie HUT ke-22 & Rakernas MB Club Indonesia',
                        'type' => 'VIDEO',
                        'caption' => 'Dokumentasi & Video Resmi HUT ke-22 & Rakernas MB Club Indonesia 2026',
                        'uploaded_by' => 'Derist Touriano',
                        'uploaded_at' => '08/09/2026 17:00',
                        'view_count' => 129,
                        'download_count' => 24,
                        'tags' => ['Derist Touriano', 'MB Club Indonesia', 'Rakernas 2026']
                    ]
                ];
            }

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

    case 'delete_m6_media':
        try {
            $id = $_GET['id'] ?? $input['id'] ?? '';
            if ($id && $sPdo) {
                $stmt = $sPdo->prepare("DELETE FROM event_media WHERE id = :id");
                $stmt->execute([':id' => $id]);
                logAudit('usr_superadmin', 'DELETE', 'M6_GALLERY_MEDIA', ['mediaId' => $id]);
            }
            echo json_encode(['success' => true, 'message' => 'Media berhasil dihapus dari database!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'save_m6_media':
        try {
            $id = $input['id'] ?? ('med_' . uniqid());
            $albumId = $input['album_id'] ?? 'alb_anniv_2026';
            $eventId = !empty($input['event_id']) ? $input['event_id'] : 'EVT-2026-012';
            $mediaUrl = $input['media_url'] ?? '';
            $type = strtoupper($input['type'] ?? 'IMAGE');
            if (!in_array($type, ['IMAGE', 'VIDEO'])) $type = 'IMAGE';
            $caption = $input['caption'] ?? '';
            $uploadedBy = $input['uploaded_by'] ?? 'usr_superadmin';
            if (!str_starts_with($uploadedBy, 'usr_')) {
                $uploadedBy = 'usr_superadmin';
            }

            if (str_starts_with(strtolower($mediaUrl), 'blob:')) {
                echo json_encode(['success' => false, 'message' => 'Blob URL tidak dapat disimpan ke cloud.']);
                break;
            }

            if ($sPdo && $id && $mediaUrl) {
                // Verify album exists to avoid FK failure
                $chkAlb = $sPdo->prepare("SELECT id FROM event_albums WHERE id = :aid");
                $chkAlb->execute([':aid' => $albumId]);
                if (!$chkAlb->fetch()) {
                    $albumId = 'alb_anniv_2026';
                }

                // Verify event exists
                if ($eventId) {
                    $chkEvt = $sPdo->prepare("SELECT id FROM events WHERE id = :eid");
                    $chkEvt->execute([':eid' => $eventId]);
                    if (!$chkEvt->fetch()) $eventId = null;
                }

                try {
                    $stmt = $sPdo->prepare("
                        INSERT INTO event_media (id, album_id, event_id, media_url, type, caption, uploaded_by, uploaded_at)
                        VALUES (:id, :alb, :evt, :url, :type::media_type_enum, :cap, :uploader, NOW())
                        ON CONFLICT (id) DO UPDATE SET
                            album_id = EXCLUDED.album_id,
                            event_id = EXCLUDED.event_id,
                            media_url = EXCLUDED.media_url,
                            caption = EXCLUDED.caption,
                            type = EXCLUDED.type
                    ");
                    $stmt->execute([
                        ':id' => $id,
                        ':alb' => $albumId,
                        ':evt' => $eventId,
                        ':url' => $mediaUrl,
                        ':type' => $type,
                        ':cap' => $caption,
                        ':uploader' => $uploadedBy
                    ]);
                } catch (Exception $eCast) {
                    $stmt = $sPdo->prepare("
                        INSERT INTO event_media (id, album_id, event_id, media_url, type, caption, uploaded_by, uploaded_at)
                        VALUES (:id, :alb, :evt, :url, :type, :cap, :uploader, NOW())
                        ON CONFLICT (id) DO UPDATE SET
                            album_id = EXCLUDED.album_id,
                            event_id = EXCLUDED.event_id,
                            media_url = EXCLUDED.media_url,
                            caption = EXCLUDED.caption,
                            type = EXCLUDED.type
                    ");
                    $stmt->execute([
                        ':id' => $id,
                        ':alb' => $albumId,
                        ':evt' => $eventId,
                        ':url' => $mediaUrl,
                        ':type' => $type,
                        ':cap' => $caption,
                        ':uploader' => $uploadedBy
                    ]);
                }
                logAudit('usr_superadmin', 'CREATE', 'M6_GALLERY_MEDIA', ['mediaId' => $id, 'url' => $mediaUrl]);
            }
            echo json_encode(['success' => true, 'message' => 'Media berhasil disimpan ke Supabase!', 'id' => $id]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal simpan media ke Supabase: ' . $e->getMessage()]);
        }
        break;

    case 'delete_m6_media':
        try {
            $id = $_GET['id'] ?? $input['id'] ?? '';
            if ($id && $sPdo) {
                $stmtM = $sPdo->prepare("DELETE FROM event_media WHERE id = :id");
                $stmtM->execute([':id' => $id]);
                logAudit('usr_superadmin', 'DELETE', 'M6_GALLERY_MEDIA', ['mediaId' => $id]);
            }
            echo json_encode(['success' => true, 'message' => 'Media berhasil dihapus dari Supabase!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'save_m6_album':
        try {
            $id = $input['id'] ?? ('alb_' . uniqid());
            $eventId = !empty($input['event_id']) ? $input['event_id'] : null;
            $title = $input['title'] ?? 'Album Event';
            $desc = $input['description'] ?? '';
            $cover = $input['cover_image'] ?? 'assets/mb_hero.jpg';
            $isPublic = !empty($input['is_public']) ? true : false;
            $createdBy = $input['created_by'] ?? 'Admin HQ';

            if ($sPdo && $id && $title) {
                if ($eventId) {
                    $chkEvt = $sPdo->prepare("SELECT id FROM events WHERE id = :eid");
                    $chkEvt->execute([':eid' => $eventId]);
                    if (!$chkEvt->fetch()) $eventId = null;
                }

                $stmt = $sPdo->prepare("
                    INSERT INTO event_albums (id, event_id, title, description, cover_image, is_public, created_by, created_at)
                    VALUES (:id, :evt, :title, :desc, :cover, :pub, :creator, NOW())
                    ON CONFLICT (id) DO UPDATE SET
                        event_id = EXCLUDED.event_id,
                        title = EXCLUDED.title,
                        description = EXCLUDED.description,
                        cover_image = EXCLUDED.cover_image,
                        is_public = EXCLUDED.is_public
                ");
                $stmt->execute([
                    ':id' => $id,
                    ':evt' => $eventId,
                    ':title' => $title,
                    ':desc' => $desc,
                    ':cover' => $cover,
                    ':pub' => $isPublic ? 1 : 0,
                    ':creator' => $createdBy
                ]);
                logAudit('usr_superadmin', 'CREATE', 'M6_GALLERY_ALBUM', ['albumId' => $id, 'title' => $title]);
            }
            echo json_encode(['success' => true, 'message' => 'Album berhasil disimpan ke Supabase!', 'id' => $id]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal simpan album ke Supabase: ' . $e->getMessage()]);
        }
        break;

    case 'delete_m6_album':
        try {
            $id = $_GET['id'] ?? $input['id'] ?? '';
            if ($id && $sPdo) {
                $stmtM = $sPdo->prepare("DELETE FROM event_media WHERE album_id = :id");
                $stmtM->execute([':id' => $id]);

                $stmtA = $sPdo->prepare("DELETE FROM event_albums WHERE id = :id");
                $stmtA->execute([':id' => $id]);

                logAudit('usr_superadmin', 'DELETE', 'M6_GALLERY_ALBUM', ['albumId' => $id]);
            }
            echo json_encode(['success' => true, 'message' => 'Album berhasil dihapus dari Supabase!']);
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

    case 'delete_m6_proposal':
        try {
            $id = $input['id'] ?? '';
            if (!empty($id)) {
                $stmt = $sPdo->prepare("DELETE FROM event_proposals WHERE id = :id");
                $stmt->execute([':id' => $id]);
            }
            echo json_encode(['success' => true, 'message' => 'Proposal berhasil dihapus!']);
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
            $rawInput = file_get_contents('php://input');
            $parsedJson = !empty($rawInput) ? json_decode($rawInput, true) : null;
            $in = is_array($parsedJson) ? $parsedJson : (is_array($input) ? $input : $_POST);

            $rawCode = trim($in['qr_code'] ?? $in['member_id'] ?? $in['code'] ?? $_GET['qr_code'] ?? $_GET['code'] ?? '');
            $eventId = trim($in['event_id'] ?? $in['event_code'] ?? $_GET['event_id'] ?? '');

            // Decode URL if rawCode contains full URL or query params like checkin=
            if (strpos($rawCode, 'checkin=') !== false || strpos($rawCode, 'code=') !== false || strpos($rawCode, 'qr_checkin=') !== false) {
                if (preg_match('/[?&](?:checkin|qr_checkin|code)=([^&]+)/', $rawCode, $m)) {
                    $rawCode = urldecode($m[1]);
                }
            }
            $rawCode = trim($rawCode);

            if (empty($rawCode)) {
                echo json_encode(['success' => false, 'message' => "❌ Kode QR / Member ID tidak boleh kosong!"]);
                exit;
            }

            // Normalise eventId variants if available
            $evtVariants = [];
            if (!empty($eventId)) {
                $evtVariants[] = $eventId;
                if ($eventId === 'PROP_EVT_012') $evtVariants[] = 'EVT-2026-012';
                if ($eventId === 'EVT-2026-012') $evtVariants[] = 'PROP_EVT_012';
                if ($eventId === 'EVT-2026-001') $evtVariants[] = 'evt_001';
                if ($eventId === 'evt_001') $evtVariants[] = 'EVT-2026-001';
                if ($eventId === 'EVT-2026-002') $evtVariants[] = 'evt_002';
                if ($eventId === 'evt_002') $evtVariants[] = 'EVT-2026-002';
                if ($eventId === 'EVT-2026-003') $evtVariants[] = 'evt_003';
                if ($eventId === 'evt_003') $evtVariants[] = 'EVT-2026-003';
            }

            // 1. Try finding participant directly by qr_code, id, user_id, member_id, or name with event filter
            $part = null;
            if (!empty($evtVariants)) {
                $inClause = "'" . implode("','", array_map('addslashes', $evtVariants)) . "'";
                $stmtP = $sPdo->prepare("
                    SELECT p.*, 
                           COALESCE(u.name, p.user_name) as display_name,
                           COALESCE(u.member_id, p.user_id) as display_mid,
                           COALESCE(u.phone, '') as display_phone,
                           COALESCE(u.club, p.club_name, 'HQ MB INA') as display_club,
                           COALESCE(u.tier, p.ticket_type, 'Platinum') as display_tier,
                           u.id as db_user_id
                    FROM event_participants p
                    LEFT JOIN users u ON (p.user_id = u.id OR p.user_id = u.member_id)
                    WHERE (p.event_id IN ($inClause) OR p.event_id LIKE :eidlike)
                      AND (
                          p.qr_code = :c 
                       OR p.id = :c 
                       OR p.user_id = :c 
                       OR u.member_id = :c 
                       OR u.username = :c
                       OR LOWER(p.user_name) = LOWER(:c)
                       OR LOWER(u.name) = LOWER(:c)
                      )
                    LIMIT 1
                ");
                $stmtP->execute([':eidlike' => '%' . $eventId . '%', ':c' => $rawCode]);
                $part = $stmtP->fetch(PDO::FETCH_ASSOC);
            }

            // 2. If not found with event filter, search without event filter (QR code is globally unique)
            if (!$part) {
                $stmtP2 = $sPdo->prepare("
                    SELECT p.*, 
                           COALESCE(u.name, p.user_name) as display_name,
                           COALESCE(u.member_id, p.user_id) as display_mid,
                           COALESCE(u.phone, '') as display_phone,
                           COALESCE(u.club, p.club_name, 'HQ MB INA') as display_club,
                           COALESCE(u.tier, p.ticket_type, 'Platinum') as display_tier,
                           u.id as db_user_id
                    FROM event_participants p
                    LEFT JOIN users u ON (p.user_id = u.id OR p.user_id = u.member_id)
                    WHERE p.qr_code = :c 
                       OR p.id = :c 
                       OR p.user_id = :c 
                       OR u.member_id = :c 
                       OR u.username = :c
                       OR LOWER(p.user_name) = LOWER(:c)
                       OR LOWER(u.name) = LOWER(:c)
                    ORDER BY p.created_at DESC
                    LIMIT 1
                ");
                $stmtP2->execute([':c' => $rawCode]);
                $part = $stmtP2->fetch(PDO::FETCH_ASSOC);
            }

            // 3. If still not found, check if it's an existing member in users table
            if (!$part) {
                $stmtUser = $sPdo->prepare("SELECT id, name, username, email, phone, role, status, tier, member_id, club FROM users WHERE member_id = :mid OR username = :mid OR id = :mid LIMIT 1");
                $stmtUser->execute([':mid' => $rawCode]);
                $u = $stmtUser->fetch(PDO::FETCH_ASSOC);

                if (!$u) {
                    echo json_encode(['success' => false, 'message' => "❌ Kode QR / Member ID '$rawCode' tidak ditemukan dalam data event maupun member!"]);
                    exit;
                }

                // On-site registration & instant checkin for registered member
                $targetEvt = !empty($eventId) ? $eventId : 'EVT-2026-012';
                $partId = 'part_' . uniqid();
                $qrVal = 'QR-' . $targetEvt . '-' . ($u['member_id'] ?: uniqid());
                $sPdo->prepare("
                    INSERT INTO event_participants (id, event_id, user_id, user_name, club_name, ticket_type, fee_paid, payment_status, payment_method, registration_method, check_in_status, check_in_at, check_in_method, qr_code)
                    VALUES (:id, :eid, :uid, :name, :club, 'MEMBER', 0, 'VERIFIED', 'ON_SITE', 'GATE_QR', TRUE, NOW(), 'QR_CODE', :qr)
                ")->execute([
                    ':id' => $partId,
                    ':eid' => $targetEvt,
                    ':uid' => $u['id'],
                    ':name' => $u['name'],
                    ':club' => $u['club'] ?? 'HQ MB INA',
                    ':qr' => $qrVal
                ]);

                // Award points
                try {
                    $sPdo->prepare("UPDATE users SET points = COALESCE(points, 0) + 50, total_events = COALESCE(total_events, 0) + 1 WHERE id = :uid")->execute([':uid' => $u['id']]);
                    $sPdo->prepare("INSERT INTO user_activities (id, user_id, activity_type, title, detail) VALUES (:id, :uid, 'EVENT', 'Check-In On-Site Berhasil', 'Check-in gate event MB INA (+50 Poin Kehadiran).')")->execute([':id' => 'act_' . uniqid(), ':uid' => $u['id']]);
                } catch (Exception $ePts) {}

                // Log checkin
                try {
                    $logId = 'chk_' . uniqid();
                    $sPdo->exec("INSERT INTO event_checkin_logs (id, event_id, user_id, scanned_by) VALUES ('$logId', '$targetEvt', '{$u['id']}', 'usr_superadmin')");
                } catch (Exception $eLog) {}

                echo json_encode([
                    'success' => true,
                    'already_checked_in' => false,
                    'message' => "✅ Check-in ON-SITE Berhasil! Member: {$u['name']} ({$u['member_id']}) telah dikumpulkan ke daftar kehadiran.",
                    'participant' => [
                        'id' => $partId,
                        'event_id' => $targetEvt,
                        'name' => $u['name'],
                        'member_id' => $u['member_id'],
                        'club' => $u['club'] ?? 'HQ MB INA',
                        'tier' => $u['tier'] ?? 'Platinum',
                        'check_in_status' => true,
                        'check_in_at' => date('d/m/Y H:i'),
                        'qr_code' => $qrVal
                    ]
                ]);
                exit;
            }

            // Participant found! Check if already checked in:
            $isAlreadyCheckedIn = ($part['check_in_status'] === true || $part['check_in_status'] === 'true' || $part['check_in_status'] === 't' || $part['check_in_status'] === 1 || $part['check_in_status'] === '1');

            if ($isAlreadyCheckedIn) {
                $checkinTime = $part['check_in_at'] ? date('d/m/Y H:i', strtotime($part['check_in_at'])) : 'sebelumnya';
                echo json_encode([
                    'success' => true,
                    'already_checked_in' => true,
                    'message' => "⚠️ Peserta {$part['display_name']} ({$part['display_mid']}) SUDAH CHECK-IN pada {$checkinTime}!",
                    'participant' => [
                        'id' => $part['id'],
                        'event_id' => $part['event_id'],
                        'name' => $part['display_name'],
                        'member_id' => $part['display_mid'],
                        'club' => $part['display_club'],
                        'tier' => $part['display_tier'],
                        'check_in_status' => true,
                        'check_in_at' => $part['check_in_at'],
                        'qr_code' => $part['qr_code']
                    ]
                ]);
                exit;
            }

            // Perform check-in in database
            $sPdo->prepare("UPDATE event_participants SET check_in_status = TRUE, check_in_at = NOW(), check_in_method = 'QR_CODE' WHERE id = :pid")->execute([':pid' => $part['id']]);

            // Award points to user
            $userIdForPts = !empty($part['db_user_id']) ? $part['db_user_id'] : $part['user_id'];
            if (!empty($userIdForPts)) {
                try {
                    $sPdo->prepare("UPDATE users SET points = COALESCE(points, 0) + 50, total_events = COALESCE(total_events, 0) + 1 WHERE id = :uid OR member_id = :uid")->execute([':uid' => $userIdForPts]);
                    $sPdo->prepare("INSERT INTO user_activities (id, user_id, activity_type, title, detail) VALUES (:id, :uid, 'EVENT', 'Check-In Event Berhasil', 'Hadir di event MB INA (+50 Poin Kehadiran).')")->execute([':id' => 'act_' . uniqid(), ':uid' => $userIdForPts]);
                } catch (Exception $ePts) {}
            }

            // Log checkin
            try {
                $logId = 'chk_' . uniqid();
                $sPdo->exec("INSERT INTO event_checkin_logs (id, event_id, user_id, scanned_by) VALUES ('$logId', '{$part['event_id']}', '$userIdForPts', 'usr_superadmin')");
            } catch (Exception $eLog) {}

            logAudit('usr_superadmin', 'UPDATE', 'M6_CHECKIN', ['event_id' => $part['event_id'], 'participant_id' => $part['id'], 'member_id' => $part['display_mid']]);

            echo json_encode([
                'success' => true,
                'already_checked_in' => false,
                'message' => "✅ Check-in BERHASIL! Peserta: {$part['display_name']} ({$part['display_mid']}) terdata hadir di lokasi event.",
                'participant' => [
                    'id' => $part['id'],
                    'event_id' => $part['event_id'],
                    'name' => $part['display_name'],
                    'member_id' => $part['display_mid'],
                    'club' => $part['display_club'],
                    'tier' => $part['display_tier'],
                    'check_in_status' => true,
                    'check_in_at' => date('d/m/Y H:i'),
                    'qr_code' => $part['qr_code']
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'toggle_participant_checkin':
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $partId = trim($input['participant_id'] ?? $input['id'] ?? '');
            $setCheckin = isset($input['check_in_status']) ? filter_var($input['check_in_status'], FILTER_VALIDATE_BOOLEAN) : null;

            if (empty($partId)) {
                echo json_encode(['success' => false, 'message' => 'Participant ID tidak valid!']);
                exit;
            }

            $stmt = $sPdo->prepare("SELECT * FROM event_participants WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $partId]);
            $p = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$p) {
                echo json_encode(['success' => false, 'message' => 'Peserta tidak ditemukan di database!']);
                exit;
            }

            $current = ($p['check_in_status'] === true || $p['check_in_status'] === 'true' || $p['check_in_status'] === 't' || $p['check_in_status'] === 1 || $p['check_in_status'] === '1');
            $newStatus = ($setCheckin !== null) ? $setCheckin : !$current;

            if ($newStatus) {
                $sPdo->prepare("UPDATE event_participants SET check_in_status = TRUE, check_in_at = NOW(), check_in_method = 'MANUAL_ADMIN' WHERE id = :id")->execute([':id' => $partId]);
                $msg = "✅ Peserta berhasil di-check in (HADIR)!";
            } else {
                $sPdo->prepare("UPDATE event_participants SET check_in_status = FALSE, check_in_at = NULL, check_in_method = NULL WHERE id = :id")->execute([':id' => $partId]);
                $msg = "⚪ Status check-in peserta berhasil dibatalkan (BELUM HADIR).";
            }

            echo json_encode([
                'success' => true,
                'message' => $msg,
                'check_in_status' => $newStatus,
                'check_in_at' => $newStatus ? date('d/m/Y H:i') : null
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'register_event_participant':
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $eventId = trim($input['event_id'] ?? $input['event_code'] ?? 'EVT-2026-012');
            $userId = trim($input['user_id'] ?? $input['member_id'] ?? 'usr_guest');
            $userName = trim($input['name'] ?? $input['user_name'] ?? '');
            $clubName = trim($input['club'] ?? $input['club_name'] ?? 'HQ MB INA');
            $ticketType = trim($input['tier'] ?? $input['ticket_type'] ?? 'MEMBER');
            $feePaid = floatval($input['htm'] ?? $input['fee_paid'] ?? 0);
            $discountAmount = floatval($input['discount_amount'] ?? 0);
            $paymentMethod = trim($input['payment_method'] ?? ($feePaid == 0 ? 'FREE_TICKET' : 'TRANSFER'));
            $regMethod = 'ONLINE';
            $paymentStatus = ($feePaid == 0) ? 'VERIFIED' : 'PENDING';
            
            // Check if member/user is already registered and accepted/verified for this event
            $checkExisting = $sPdo->prepare("
                SELECT id, event_id, user_id, user_name, club_name, ticket_type, payment_status, fee_paid, qr_code, created_at
                FROM event_participants
                WHERE event_id = :eid AND (
                    user_id = :uid OR 
                    (NULLIF(:uname, '') IS NOT NULL AND LOWER(user_name) = LOWER(:uname))
                )
                ORDER BY CASE WHEN payment_status IN ('VERIFIED', 'PAID', 'ACCEPTED', 'CONFIRMED') THEN 1 ELSE 2 END
                LIMIT 1
            ");
            $checkExisting->execute([':eid' => $eventId, ':uid' => $userId, ':uname' => $userName]);
            $existing = $checkExisting->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $statusUpper = strtoupper($existing['payment_status'] ?? 'PENDING');
                if (in_array($statusUpper, ['VERIFIED', 'PAID', 'ACCEPTED', 'CONFIRMED'])) {
                    // Fetch event title
                    $evStmt = $sPdo->prepare("SELECT title FROM events WHERE id = :eid LIMIT 1");
                    $evStmt->execute([':eid' => $eventId]);
                    $evTitle = $evStmt->fetchColumn() ?: 'Event MB INA';

                    echo json_encode([
                        'success' => true,
                        'already_registered' => true,
                        'is_verified' => true,
                        'message' => "Anda sudah menjadi tamu undangan di event {$evTitle}. Silakan Anda menyimpan kartu undangan Anda.",
                        'participant' => [
                            'id' => $existing['id'],
                            'event_id' => $existing['event_id'],
                            'event_code' => $existing['event_id'],
                            'user_id' => $existing['user_id'],
                            'member_id' => $existing['user_id'],
                            'name' => $existing['user_name'],
                            'user_name' => $existing['user_name'],
                            'club' => $existing['club_name'] ?: 'HQ MB INA',
                            'club_name' => $existing['club_name'] ?: 'HQ MB INA',
                            'tier' => $existing['ticket_type'] ?: 'Platinum',
                            'ticket_type' => $existing['ticket_type'] ?: 'Platinum',
                            'htm' => $existing['fee_paid'],
                            'fee_paid' => $existing['fee_paid'],
                            'status' => 'VERIFIED',
                            'payment_status' => 'VERIFIED',
                            'qr_code' => $existing['qr_code'] ?: ('QR-' . $eventId . '-' . substr(strtoupper(preg_replace('/[^A-Za-z]/', '', $userName) . 'MBIN'), 0, 4) . '-VERIFIED'),
                            'created_at' => date('d/m/Y H:i')
                        ]
                    ]);
                    exit;
                }
            }
            
            // Generate unique participant ID
            $partId = !empty($input['id']) && str_starts_with($input['id'], 'part_') ? $input['id'] : ('part_' . uniqid());
            
            // Generate QR Code
            $cleanName = strtoupper(preg_replace('/[^A-Za-z]/', '', $userName));
            if (strlen($cleanName) < 4) $cleanName = str_pad($cleanName, 4, 'MB');
            $cleanName = substr($cleanName, 0, 4);
            $qrCode = 'QR-' . $eventId . '-' . $cleanName . '-' . rand(1000, 9999);
            
            // Upsert into event_participants table in Supabase Cloud
            $stmt = $sPdo->prepare("
                INSERT INTO event_participants (
                    id, event_id, user_id, user_name, club_name, ticket_type, 
                    payment_status, registered_at, created_at, fee_paid, 
                    registration_method, payment_method, check_in_status, check_in_at, 
                    discount_amount, check_in_method, qr_code
                ) VALUES (
                    :id, :event_id, :user_id, :user_name, :club_name, :ticket_type,
                    :payment_status, NOW(), NOW(), :fee_paid,
                    :registration_method, :payment_method, :check_in_status, NULL,
                    :discount_amount, 'QR_CODE', :qr_code
                )
                ON CONFLICT (id) DO UPDATE SET
                    event_id = EXCLUDED.event_id,
                    user_name = EXCLUDED.user_name,
                    club_name = EXCLUDED.club_name,
                    ticket_type = EXCLUDED.ticket_type,
                    payment_status = EXCLUDED.payment_status,
                    fee_paid = EXCLUDED.fee_paid,
                    discount_amount = EXCLUDED.discount_amount,
                    qr_code = EXCLUDED.qr_code
            ");

            $stmt->execute([
                ':id' => $partId,
                ':event_id' => $eventId,
                ':user_id' => $userId,
                ':user_name' => $userName,
                ':club_name' => $clubName,
                ':ticket_type' => $ticketType,
                ':payment_status' => $paymentStatus,
                ':fee_paid' => $feePaid,
                ':registration_method' => $regMethod,
                ':payment_method' => $paymentMethod,
                ':check_in_status' => 'false',
                ':discount_amount' => $discountAmount,
                ':qr_code' => $qrCode
            ]);

            // If verified immediately (e.g. Free event), update user stats
            if ($paymentStatus === 'VERIFIED') {
                try {
                    $sPdo->prepare("UPDATE users SET total_events = COALESCE(total_events, 0) + 1 WHERE id = :uid OR member_id = :uid")->execute([':uid' => $userId]);
                } catch (Exception $eU) {}
            }

            logAudit($userId, 'INSERT', 'M6_ONLINE_REGISTRATION', ['event_id' => $eventId, 'participant_id' => $partId, 'fee_paid' => $feePaid, 'status' => $paymentStatus]);

            echo json_encode([
                'success' => true,
                'message' => ($paymentStatus === 'VERIFIED') 
                    ? "🎉 Pendaftaran Online & E-Tiket Berhasil Terbit di Cloud Database!" 
                    : "📝 Pendaftaran Online Berhasil Disimpan! Menunggu Persetujuan / Verifikasi Pembayaran oleh Admin.",
                'participant' => [
                    'id' => $partId,
                    'event_id' => $eventId,
                    'event_code' => $eventId,
                    'user_id' => $userId,
                    'member_id' => $userId,
                    'name' => $userName,
                    'user_name' => $userName,
                    'club' => $clubName,
                    'club_name' => $clubName,
                    'tier' => $ticketType,
                    'ticket_type' => $ticketType,
                    'htm' => $feePaid,
                    'fee_paid' => $feePaid,
                    'discount_amount' => $discountAmount,
                    'status' => $paymentStatus,
                    'payment_status' => $paymentStatus,
                    'registration_method' => 'ONLINE',
                    'created_at' => date('d/m/Y H:i'),
                    'qr_code' => $qrCode
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal mendaftar event di Supabase Cloud: ' . $e->getMessage()]);
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

            // Fetch participant record to award points & log activity
            $pStmt = $sPdo->prepare("SELECT user_id, fee_paid, event_id FROM event_participants WHERE id = :pid OR user_id = :pid OR id LIKE :pidlike LIMIT 1");
            $pStmt->execute([':pid' => $partId, ':pidlike' => "%$partId%"]);
            $pRow = $pStmt->fetch();

            if ($pRow && in_array(strtoupper($status), ['VERIFIED', 'SUCCESS', 'CONFIRMED'])) {
                $uid = $pRow['user_id'];
                $feePaid = (int)($pRow['fee_paid'] ?? 0);
                $pts = max(1, intval($feePaid / 10000));

                // Add points and update total_events
                $sPdo->prepare("
                    UPDATE users 
                    SET points = points + :pts,
                        total_events = (
                            SELECT COUNT(*) FROM event_participants 
                            WHERE user_id = :uid AND payment_status IN ('SUCCESS', 'CONFIRMED', 'VERIFIED', 'APPROVED')
                        )
                    WHERE id = :uid
                ")->execute([':pts' => $pts, ':uid' => $uid]);

                // Log to user_activities
                try {
                    $sPdo->prepare("
                        INSERT INTO user_activities (id, user_id, activity_type, title, detail)
                        VALUES (:id, :uid, 'EVENT', 'Pembayaran Tiket Event Berhasil', :det)
                    ")->execute([
                        ':id' => 'act_' . uniqid(),
                        ':uid' => $uid,
                        ':det' => 'Tiket event Rp ' . number_format($feePaid, 0, ',', '.') . " terverifikasi (+{$pts} Poin Reward)."
                    ]);
                } catch (Exception $eAct) {}
            }

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
