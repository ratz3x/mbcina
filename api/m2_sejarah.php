<?php
// m2_sejarah.php - M2 - Profil, Sejarah, Visi, Presiden, Klub
// Digunakan oleh api/index.php (router)
// $sPdo, $input, $action sudah di-set oleh router

switch ($action) {
    case 'get_m2_organization':
        try {
            $stmt = $sPdo->query("SELECT * FROM organization LIMIT 1");
            $org = $stmt->fetch();
            echo json_encode(['success' => true, 'organization' => $org, 'source' => 'SUPABASE_CLOUD']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_m2_organization':
        $name = trim($input['name'] ?? '');
        $shortName = trim($input['short_name'] ?? '');
        $alias = trim($input['alias'] ?? '');
        $tagline = trim($input['tagline'] ?? '');
        $foundedDate = $input['founded_date'] ?? '2004-08-01';
        $foundedPlace = trim($input['founded_place'] ?? 'Jakarta');
        $website = trim($input['website'] ?? '');
        $email = trim($input['email'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $description = trim($input['description'] ?? '');

        if (empty($name) || empty($alias)) {
            echo json_encode(['success' => false, 'message' => 'Nama resmi dan Alias wajib diisi!']);
            exit;
        }

        try {
            $stmt = $sPdo->prepare("UPDATE organization SET name = :name, short_name = :short_name, alias = :alias, tagline = :tagline, founded_date = :founded_date, founded_place = :founded_place, website = :website, email = :email, phone = :phone, description = :description, updated_at = NOW() WHERE id = 'org_001'");
            $stmt->execute([
                ':name' => $name,
                ':short_name' => $shortName,
                ':alias' => $alias,
                ':tagline' => $tagline,
                ':founded_date' => $foundedDate,
                ':founded_place' => $foundedPlace,
                ':website' => $website,
                ':email' => $email,
                ':phone' => $phone,
                ':description' => $description
            ]);

            logAudit('usr_superadmin', 'UPDATE', 'ORGANIZATION_PROFILE', ['name' => $name, 'alias' => $alias, 'tagline' => $tagline]);
            echo json_encode(['success' => true, 'message' => 'Profil MB INA berhasil diperbarui di Supabase Cloud Database!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui profil organisasi: ' . $e->getMessage()]);
        }
        break;

    case 'get_m2_history':
        try {
            $stmt = $sPdo->query("SELECT * FROM organization_history ORDER BY sort_order ASC, year ASC");
            $history = $stmt->fetchAll();
            echo json_encode(['success' => true, 'history' => $history, 'source' => 'SUPABASE_CLOUD']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'save_m2_history':
        $id = trim($input['id'] ?? '');
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $year = intval($input['year'] ?? 2000);
        $icon = trim($input['icon'] ?? '📜');
        $color = trim($input['color'] ?? '#D4AF37');
        $sortOrder = intval($input['sort_order'] ?? 1);

        if (empty($title) || empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Judul dan Deskripsi Sejarah wajib diisi!']);
            exit;
        }

        try {
            if (!empty($id)) {
                $stmt = $sPdo->prepare("UPDATE organization_history SET title = :title, description = :description, year = :year, icon = :icon, color = :color, sort_order = :sort_order WHERE id = :id");
                $stmt->execute([
                    ':title' => $title,
                    ':description' => $description,
                    ':year' => $year,
                    ':icon' => $icon,
                    ':color' => $color,
                    ':sort_order' => $sortOrder,
                    ':id' => $id
                ]);
                $msg = "Pasal Sejarah Organisasi berhasil diperbarui di Supabase Cloud!";
            } else {
                $newId = 'hist_' . sprintf('%03d', time() % 1000);
                $stmt = $sPdo->prepare("INSERT INTO organization_history (id, organization_id, title, description, year, icon, color, sort_order, created_at) VALUES (:id, 'org_001', :title, :description, :year, :icon, :color, :sort_order, NOW())");
                $stmt->execute([
                    ':id' => $newId,
                    ':title' => $title,
                    ':description' => $description,
                    ':year' => $year,
                    ':icon' => $icon,
                    ':color' => $color,
                    ':sort_order' => $sortOrder
                ]);
                $msg = "Pasal Sejarah Organisasi baru berhasil ditambahkan ke Supabase Cloud!";
            }

            logAudit('usr_superadmin', 'SAVE_HISTORY', 'ORGANIZATION_HISTORY', ['title' => $title, 'year' => $year]);
            echo json_encode(['success' => true, 'message' => $msg]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan sejarah: ' . $e->getMessage()]);
        }
        break;

    case 'delete_m2_history':
        $id = trim($input['id'] ?? '');
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID Sejarah tidak valid!']);
            exit;
        }

        try {
            $stmt = $sPdo->prepare("DELETE FROM organization_history WHERE id = :id");
            $stmt->execute([':id' => $id]);
            logAudit('usr_superadmin', 'DELETE', 'ORGANIZATION_HISTORY', ['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Pasal Sejarah berhasil dihapus dari Supabase Cloud!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus sejarah: ' . $e->getMessage()]);
        }
        break;

    case 'get_m2_founders':
        try {
            $stmt = $sPdo->query("SELECT * FROM founders ORDER BY sort_order ASC");
            $founders = $stmt->fetchAll();
            echo json_encode(['success' => true, 'founders' => $founders, 'source' => 'SUPABASE_CLOUD']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ============================================
    // 2.2 & 2.3 DAFTAR & DETAIL KLUB CRUD ENDPOINTS
    // ============================================
    case 'get_m2_clubs':
        try {
            $stmt = $sPdo->query("SELECT id, name, code, alias, region, city, type, member_count, status, is_verified, ketua_umum, contact_person, contact_phone, founded_year, description FROM clubs ORDER BY region ASC, name ASC");
            $clubs = $stmt->fetchAll();
            echo json_encode(['success' => true, 'clubs' => $clubs, 'source' => 'SUPABASE_CLOUD']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ============================================
    // MEMBER SELF-SELECT & JOIN CLUB ENDPOINT
    // ============================================
    case 'member_join_club':
        $userId = trim($input['user_id'] ?? '');
        $clubId = trim($input['club_id'] ?? '');

        if (empty($userId) || empty($clubId)) {
            echo json_encode(['success' => false, 'message' => 'User ID dan ID Klub wajib diisi!']);
            exit;
        }

        try {
            // 1. Ambil detail Klub
            $stmtClub = $sPdo->prepare("SELECT * FROM clubs WHERE id = :id OR code = :code LIMIT 1");
            $stmtClub->execute([':id' => $clubId, ':code' => $clubId]);
            $club = $stmtClub->fetch(PDO::FETCH_ASSOC);

            if (!$club) {
                // Fallback default info jika ID statis
                $club = [
                    'id' => $clubId,
                    'name' => $input['club_name'] ?? 'MBC Jambi',
                    'city' => $input['city'] ?? 'Jambi',
                    'region' => 'Regional Sumatera',
                    'member_count' => 142
                ];
            }

            // 2. Ambil data user saat ini
            $stmtUser = $sPdo->prepare("SELECT id, name, username, email, phone, role, status, tier, member_id, province, city, birth_date, gender, occupation, vehicle_model, license_plate, points, total_events, total_donation, photo_url, avatar_url, join_date, is_system_architect, is_protected, is_active, created_at, updated_at FROM users WHERE id = :id OR username = :u OR email = :e LIMIT 1");
            $stmtUser->execute([':id' => $userId, ':u' => $userId, ':e' => $userId]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'Data member tidak ditemukan!']);
                exit;
            }

            $currentMid = $user['member_id'] ?? '';
            $provCode = 'JAM'; // default
            if (!empty($club['city'])) {
                $c = strtoupper($club['city']);
                if (strpos($c, 'JAMBI') !== false) $provCode = 'JAM';
                elseif (strpos($c, 'ACEH') !== false || strpos($c, 'BANDA') !== false) $provCode = 'ACE';
                elseif (strpos($c, 'MEDAN') !== false || strpos($c, 'SUMUT') !== false) $provCode = 'MED';
                elseif (strpos($c, 'JAKARTA') !== false) $provCode = 'JKT';
                elseif (strpos($c, 'BANDUNG') !== false || strpos($c, 'JABAR') !== false) $provCode = 'BDG';
                elseif (strpos($c, 'SURABAYA') !== false || strpos($c, 'JATIM') !== false) $provCode = 'SBY';
                elseif (!empty($club['code'])) $provCode = substr(preg_replace('/[^A-Z]/', '', $club['code']), 0, 3);
            }

            // Update Member ID jika masih placeholder atau MBR
            $newMid = $currentMid;
            if (empty($newMid) || strpos($newMid, 'MBR') !== false || strpos($newMid, 'TMP') !== false || strpos($newMid, '000000') !== false) {
                $newMid = 'MBINA-' . $provCode . '-2026-' . str_pad((string)rand(10, 99), 6, '0', STR_PAD_LEFT);
            }

            // 3. Update status & club di tabel users
            $updStmt = $sPdo->prepare("UPDATE users SET club_id = :club_id, club = :club_name, status = 'ACTIVE', member_id = :mid, updated_at = NOW() WHERE id = :id");
            $updStmt->execute([
                ':club_id' => $club['id'],
                ':club_name' => $club['name'],
                ':mid' => $newMid,
                ':id' => $user['id']
            ]);

            // 4. Update member_count di tabel clubs
            try {
                $sPdo->prepare("UPDATE clubs SET member_count = COALESCE(member_count, 0) + 1 WHERE id = :id")->execute([':id' => $club['id']]);
            } catch (Exception $e) {}

            // 5. Catat ke club_members
            try {
                $cmId = 'cm_' . uniqid();
                $sPdo->prepare("INSERT INTO club_members (id, club_id, user_id, role, joined_date, status) VALUES (:id, :cid, :uid, 'MEMBER', CURRENT_DATE, 'ACTIVE') ON CONFLICT DO NOTHING")
                     ->execute([':id' => $cmId, ':cid' => $club['id'], ':uid' => $user['id']]);
            } catch (Exception $e) {}

            // Ambil data user yang telah terupdate
            $stmtUser->execute([':id' => $user['id'], ':u' => $user['id'], ':e' => $user['id']]);
            $updatedUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
            if ($updatedUser) {
                unset($updatedUser['password']);
            }

            echo json_encode([
                'success' => true,
                'message' => '🎉 Selamat! Anda berhasil bergabung dengan ' . $club['name'] . '!',
                'user' => $updatedUser ?: array_merge($user, ['club_id' => $club['id'], 'club' => $club['name'], 'status' => 'ACTIVE', 'member_id' => $newMid]),
                'club' => $club
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal memilih klub: ' . $e->getMessage()]);
        }
        break;

    case 'create_m2_club':
        $name = trim($input['name'] ?? '');
        $code = strtoupper(trim($input['code'] ?? ''));
        $region = trim($input['region'] ?? 'Regional Metro DKI Jakarta');
        $city = trim($input['city'] ?? 'Jakarta');
        $type = trim($input['type'] ?? 'CLUB');
        $memberCount = (int)($input['member_count'] ?? 50);
        $ketuaUmum = trim($input['ketua_umum'] ?? 'H. Ahmad Fauzi, S.E.');
        $contactPerson = trim($input['contact_person'] ?? 'Hendra (Sekretaris Club)');
        $contactPhone = trim($input['contact_phone'] ?? '0812-3456-7890');
        $foundedYear = (int)($input['founded_year'] ?? 2004);
        $description = trim($input['description'] ?? '');

        if (empty($name) || empty($code)) {
            echo json_encode(['success' => false, 'message' => 'Nama Klub dan Kode Unik wajib diisi!']);
            exit;
        }

        $id = 'clb_' . uniqid();
        try {
            $stmt = $sPdo->prepare("INSERT INTO clubs (id, organization_id, code, name, alias, region, city, type, member_count, status, is_verified, ketua_umum, contact_person, contact_phone, founded_year, description) VALUES (:id, 'org_001', :code, :name, :alias, :region, :city, :type, :member_count, 'ACTIVE', true, :ketua, :cp, :phone, :year, :desc)");
            $stmt->execute([
                ':id' => $id,
                ':code' => $code,
                ':name' => $name,
                ':alias' => $code,
                ':region' => $region,
                ':city' => $city,
                ':type' => in_array($type, ['CLUB','CHAPTER']) ? $type : 'CLUB',
                ':member_count' => $memberCount,
                ':ketua' => $ketuaUmum,
                ':cp' => $contactPerson,
                ':phone' => $contactPhone,
                ':year' => $foundedYear,
                ':desc' => $description
            ]);

            logAudit('usr_superadmin', 'CREATE', 'CLUB_MANAGEMENT', ['name' => $name, 'code' => $code, 'region' => $region]);
            echo json_encode(['success' => true, 'message' => "Klub '$name' ($code) berhasil ditambahkan ke Supabase Cloud!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menambah Klub: ' . $e->getMessage()]);
        }
        break;

    case 'update_m2_club':
        $id = $input['id'] ?? '';
        $name = trim($input['name'] ?? '');
        $code = strtoupper(trim($input['code'] ?? ''));
        $region = trim($input['region'] ?? 'Regional Metro DKI Jakarta');
        $city = trim($input['city'] ?? 'Jakarta');
        $type = trim($input['type'] ?? 'CLUB');
        $memberCount = (int)($input['member_count'] ?? 50);
        $ketuaUmum = trim($input['ketua_umum'] ?? '');
        $contactPerson = trim($input['contact_person'] ?? '');
        $contactPhone = trim($input['contact_phone'] ?? '');
        $foundedYear = (int)($input['founded_year'] ?? 2004);
        $description = trim($input['description'] ?? '');

        if (empty($id) || empty($name) || empty($code)) {
            echo json_encode(['success' => false, 'message' => 'Data Klub tidak valid!']);
            exit;
        }

        try {
            $stmt = $sPdo->prepare("UPDATE clubs SET name = :name, code = :code, alias = :alias, region = :region, city = :city, type = :type, member_count = :member_count, ketua_umum = :ketua, contact_person = :cp, contact_phone = :phone, founded_year = :year, description = :desc WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':code' => $code,
                ':alias' => $code,
                ':region' => $region,
                ':city' => $city,
                ':type' => in_array($type, ['CLUB','CHAPTER']) ? $type : 'CLUB',
                ':member_count' => $memberCount,
                ':ketua' => $ketuaUmum,
                ':cp' => $contactPerson,
                ':phone' => $contactPhone,
                ':year' => $foundedYear,
                ':desc' => $description,
                ':id' => $id
            ]);

            logAudit('usr_superadmin', 'UPDATE', 'CLUB_MANAGEMENT', ['id' => $id, 'name' => $name, 'region' => $region]);
            echo json_encode(['success' => true, 'message' => "Data Klub '$name' berhasil diperbarui di Supabase Cloud!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui Klub: ' . $e->getMessage()]);
        }
        break;

    case 'delete_m2_club':
        $id = $input['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID Klub tidak boleh kosong!']);
            exit;
        }
        try {
            $stmt = $sPdo->prepare("DELETE FROM clubs WHERE id = :id");
            $stmt->execute([':id' => $id]);
            logAudit('usr_superadmin', 'DELETE', 'CLUB_MANAGEMENT', ['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Klub / Chapter berhasil dihapus dari database Supabase Cloud!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus Klub: ' . $e->getMessage()]);
        }
        break;

    // ============================================
    // M3 - MANAJEMEN KEANGGOTAAN HELPER FUNCTIONS & ENDPOINTS
    // ============================================
    case 'get_m2_vision_mission':
        try {
            $stmt = $sPdo->query("SELECT * FROM vision_mission ORDER BY sort_order ASC");
            $vm = $stmt->fetchAll();
            echo json_encode(['success' => true, 'visionMission' => $vm, 'source' => 'SUPABASE_CLOUD']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create_m2_vision_mission':
        $type = $input['type'] ?? 'MISSION';
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $icon = trim($input['icon'] ?? '🚀');
        $sortOrder = (int)($input['sort_order'] ?? 10);

        if (empty($title) || empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Judul dan Deskripsi wajib diisi!']);
            exit;
        }

        $id = 'vm_' . uniqid();
        try {
            $stmt = $sPdo->prepare("INSERT INTO vision_mission (id, organization_id, type, title, description, icon, sort_order) VALUES (:id, 'org_001', :type, :title, :description, :icon, :sort_order)");
            $stmt->execute([
                ':id' => $id,
                ':type' => in_array($type, ['VISION','MISSION']) ? $type : 'MISSION',
                ':title' => $title,
                ':description' => $description,
                ':icon' => $icon,
                ':sort_order' => $sortOrder
            ]);

            logAudit('usr_superadmin', 'CREATE', 'VISION_MISSION', ['title' => $title, 'type' => $type]);
            echo json_encode(['success' => true, 'message' => 'Visi/Misi berhasil ditambahkan ke Supabase Cloud!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menambah Visi/Misi: ' . $e->getMessage()]);
        }
        break;

    case 'update_m2_vision_mission':
        $id = $input['id'] ?? '';
        $type = $input['type'] ?? 'MISSION';
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $icon = trim($input['icon'] ?? '🚀');
        $sortOrder = (int)($input['sort_order'] ?? 1);

        if (empty($id) || empty($title) || empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Data Visi/Misi tidak valid!']);
            exit;
        }

        try {
            $stmt = $sPdo->prepare("UPDATE vision_mission SET type = :type, title = :title, description = :description, icon = :icon, sort_order = :sort_order WHERE id = :id");
            $stmt->execute([
                ':type' => in_array($type, ['VISION','MISSION']) ? $type : 'MISSION',
                ':title' => $title,
                ':description' => $description,
                ':icon' => $icon,
                ':sort_order' => $sortOrder,
                ':id' => $id
            ]);

            logAudit('usr_superadmin', 'UPDATE', 'VISION_MISSION', ['id' => $id, 'title' => $title]);
            echo json_encode(['success' => true, 'message' => 'Visi/Misi berhasil diperbarui di Supabase Cloud!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui Visi/Misi: ' . $e->getMessage()]);
        }
        break;

    case 'delete_m2_vision_mission':
        $id = $input['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID wajib diisi!']);
            exit;
        }

        try {
            $stmt = $sPdo->prepare("DELETE FROM vision_mission WHERE id = :id");
            $stmt->execute([':id' => $id]);

            logAudit('usr_superadmin', 'DELETE', 'VISION_MISSION', ['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Visi/Misi berhasil dihapus dari Supabase Cloud!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus Visi/Misi: ' . $e->getMessage()]);
        }
        break;

    // ============================================
    // 2.1.4 DAFTAR PRESIDEN CRUD ENDPOINTS
    // ============================================
    case 'get_m2_presidents':
        try {
            $stmt = $sPdo->query("SELECT * FROM presidents ORDER BY sort_order ASC, period_start ASC");
            $presidents = $stmt->fetchAll();
            echo json_encode(['success' => true, 'presidents' => $presidents, 'source' => 'SUPABASE_CLOUD']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create_m2_president':
        $name = trim($input['name'] ?? '');
        $periodStart = (int)($input['period_start'] ?? 2025);
        $periodEnd = (int)($input['period_end'] ?? 2027);
        $photoUrl = trim($input['photo_url'] ?? '');
        $bio = trim($input['bio'] ?? '');
        $isCurrent = isset($input['is_current']) ? ($input['is_current'] ? 'true' : 'false') : 'false';
        $sortOrder = (int)($input['sort_order'] ?? 12);

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Nama Presiden wajib diisi!']);
            exit;
        }

        $id = 'pres_' . uniqid();
        try {
            if ($isCurrent === 'true') {
                $sPdo->exec("UPDATE presidents SET is_current = false");
            }

            $stmt = $sPdo->prepare("INSERT INTO presidents (id, organization_id, name, period_start, period_end, photo_url, bio, is_current, sort_order) VALUES (:id, 'org_001', :name, :period_start, :period_end, :photo_url, :bio, :is_current, :sort_order)");
            $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':period_start' => $periodStart,
                ':period_end' => $periodEnd,
                ':photo_url' => $photoUrl ?: null,
                ':bio' => $bio,
                ':is_current' => $isCurrent,
                ':sort_order' => $sortOrder
            ]);

            logAudit('usr_superadmin', 'CREATE', 'PRESIDENT_MANAGEMENT', ['name' => $name, 'period' => "$periodStart-$periodEnd"]);
            echo json_encode(['success' => true, 'message' => "Data Presiden $name berhasil ditambahkan ke Supabase Cloud!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menambah Presiden: ' . $e->getMessage()]);
        }
        break;

    case 'update_m2_president':
        $id = $input['id'] ?? '';
        $name = trim($input['name'] ?? '');
        $periodStart = (int)($input['period_start'] ?? 2004);
        $periodEnd = (int)($input['period_end'] ?? 2006);
        $photoUrl = trim($input['photo_url'] ?? '');
        $bio = trim($input['bio'] ?? '');
        $isCurrent = isset($input['is_current']) ? ($input['is_current'] ? 'true' : 'false') : 'false';
        $sortOrder = (int)($input['sort_order'] ?? 1);

        if (empty($id) || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Data Presiden tidak valid!']);
            exit;
        }

        try {
            if ($isCurrent === 'true') {
                $sPdo->exec("UPDATE presidents SET is_current = false");
            }

            $stmt = $sPdo->prepare("UPDATE presidents SET name = :name, period_start = :period_start, period_end = :period_end, photo_url = :photo_url, bio = :bio, is_current = :is_current, sort_order = :sort_order WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':period_start' => $periodStart,
                ':period_end' => $periodEnd,
                ':photo_url' => $photoUrl ?: null,
                ':bio' => $bio,
                ':is_current' => $isCurrent,
                ':sort_order' => $sortOrder,
                ':id' => $id
            ]);

            logAudit('usr_superadmin', 'UPDATE', 'PRESIDENT_MANAGEMENT', ['id' => $id, 'name' => $name, 'photo_url' => $photoUrl]);
            echo json_encode(['success' => true, 'message' => "Data Presiden $name (termasuk Foto/Bio) berhasil diperbarui di Supabase Cloud!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui Presiden: ' . $e->getMessage()]);
        }
        break;

    case 'delete_m2_president':
        $id = $input['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID Presiden wajib diisi!']);
            exit;
        }

        try {
            $stmt = $sPdo->prepare("DELETE FROM presidents WHERE id = :id");
            $stmt->execute([':id' => $id]);

            logAudit('usr_superadmin', 'DELETE', 'PRESIDENT_MANAGEMENT', ['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Data Presiden berhasil dihapus dari Supabase Cloud!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus Presiden: ' . $e->getMessage()]);
        }
        break;

    // ============================================
    // 2.1.5 & 2.4 PENGURUS PUSAT (STRUCTURE) CRUD
    // ============================================
    case 'get_m2_club_detail':
        $clubId = $_GET['club_id'] ?? '';
        try {
            $stmtClub = $sPdo->prepare("SELECT * FROM clubs WHERE id = :id");
            $stmtClub->execute([':id' => $clubId]);
            $club = $stmtClub->fetch();

            $stmtMembers = $sPdo->prepare("SELECT cm.*, u.name, u.email, u.phone, u.username, u.role as user_role FROM club_members cm JOIN users u ON cm.user_id = u.id WHERE cm.club_id = :id");
            $stmtMembers->execute([':id' => $clubId]);
            $members = $stmtMembers->fetchAll();

            $stmtGalleries = $sPdo->prepare("SELECT * FROM club_galleries WHERE club_id = :id ORDER BY created_at DESC");
            $stmtGalleries->execute([':id' => $clubId]);
            $galleries = $stmtGalleries->fetchAll();

            echo json_encode([
                'success' => true,
                'club' => $club,
                'members' => $members,
                'galleries' => $galleries,
                'source' => 'SUPABASE_CLOUD'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ============================================
    // 3.1.4.1 & GENERAL MASTER DATA
    // ============================================

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action in m2_sejarah: ' . $action]);
}
