<?php
// m3_struktur.php - M3 - Struktur Pengurus Pusat & Periodisasi
// Digunakan oleh api/index.php (router)
// $sPdo, $input, $action sudah di-set oleh router

switch ($action) {
    case 'get_m2_structure':
        try {
            $stmt = $sPdo->query("SELECT * FROM organization_structure ORDER BY sort_order ASC, id ASC");
            $structure = $stmt->fetchAll();
            echo json_encode(['success' => true, 'structure' => $structure, 'source' => 'SUPABASE_CLOUD']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create_m2_structure':
        $roleName = trim($input['role_name'] ?? $input['position_name'] ?? '');
        $fullName = trim($input['full_name'] ?? $input['official_name'] ?? '');
        $clubName = trim($input['club_name'] ?? $input['club_origin'] ?? 'Pusat MBClubINA');
        $positionLevel = trim($input['position_level'] ?? 'PENGURUS_PUSAT');
        $periodStart = (int)($input['period_start'] ?? 2025);
        $periodEnd = (int)($input['period_end'] ?? 2027);
        $sortOrder = (int)($input['sort_order'] ?? 10);

        if (empty($roleName)) {
            echo json_encode(['success' => false, 'message' => 'Nama Jabatan / Divisi wajib diisi!']);
            exit;
        }

        try {
            $stmt = $sPdo->prepare("INSERT INTO organization_structure (role_name, full_name, club_name, position_level, sort_order, period_start, period_end, created_at, updated_at) VALUES (:role_name, :full_name, :club_name, :position_level, :sort_order, :period_start, :period_end, NOW(), NOW())");
            $stmt->execute([
                ':role_name' => $roleName,
                ':full_name' => $fullName,
                ':club_name' => $clubName,
                ':position_level' => $positionLevel,
                ':sort_order' => $sortOrder,
                ':period_start' => $periodStart,
                ':period_end' => $periodEnd
            ]);

            logAudit('usr_superadmin', 'CREATE', 'STRUCTURE_MANAGEMENT', ['role_name' => $roleName, 'full_name' => $fullName]);
            echo json_encode(['success' => true, 'message' => "Jabatan '$roleName' berhasil ditambahkan ke Supabase Cloud!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menambah Jabatan: ' . $e->getMessage()]);
        }
        break;

    case 'update_m2_structure':
        $id = $input['id'] ?? '';
        $roleName = trim($input['role_name'] ?? $input['position_name'] ?? '');
        $fullName = trim($input['full_name'] ?? $input['official_name'] ?? '');
        $clubName = trim($input['club_name'] ?? $input['club_origin'] ?? 'Pusat MBClubINA');
        $positionLevel = trim($input['position_level'] ?? 'PENGURUS_PUSAT');
        $periodStart = (int)($input['period_start'] ?? 2025);
        $periodEnd = (int)($input['period_end'] ?? 2027);
        $sortOrder = (int)($input['sort_order'] ?? 1);

        if (empty($id) || empty($roleName)) {
            echo json_encode(['success' => false, 'message' => 'ID dan Nama Jabatan wajib diisi!']);
            exit;
        }

        try {
            $stmt = $sPdo->prepare("UPDATE organization_structure SET role_name = :role_name, full_name = :full_name, club_name = :club_name, position_level = :position_level, sort_order = :sort_order, period_start = :period_start, period_end = :period_end, updated_at = NOW() WHERE id = :id");
            $stmt->execute([
                ':role_name' => $roleName,
                ':full_name' => $fullName,
                ':club_name' => $clubName,
                ':position_level' => $positionLevel,
                ':sort_order' => $sortOrder,
                ':period_start' => $periodStart,
                ':period_end' => $periodEnd,
                ':id' => $id
            ]);

            logAudit('usr_superadmin', 'UPDATE', 'STRUCTURE_MANAGEMENT', ['id' => $id, 'role_name' => $roleName]);
            echo json_encode(['success' => true, 'message' => "Jabatan '$roleName' berhasil diperbarui di Supabase Cloud!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui Jabatan: ' . $e->getMessage()]);
        }
        break;

    case 'delete_m2_structure':
        $id = $input['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID Jabatan wajib diisi!']);
            exit;
        }

        try {
            $stmt = $sPdo->prepare("DELETE FROM organization_structure WHERE id = :id");
            $stmt->execute([':id' => $id]);

            logAudit('usr_superadmin', 'DELETE', 'STRUCTURE_MANAGEMENT', ['id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Jabatan berhasil dihapus dari Supabase Cloud!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus Jabatan: ' . $e->getMessage()]);
        }
        break;

    case 'get_m2_governance_periods':
        try {
            $stmt = $sPdo->query("SELECT * FROM governance_periods ORDER BY year_start DESC");
            $periods = $stmt->fetchAll();
            echo json_encode(['success' => true, 'periods' => $periods, 'source' => 'SUPABASE_CLOUD']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_m2_advisory_board':
        try {
            $stmt = $sPdo->query("SELECT * FROM advisory_board ORDER BY sort_order ASC");
            $advisory = $stmt->fetchAll();
            echo json_encode(['success' => true, 'advisoryBoard' => $advisory, 'source' => 'SUPABASE_CLOUD']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_m2_honor_council':
        try {
            $stmt = $sPdo->query("SELECT * FROM honor_council ORDER BY sort_order ASC");
            $honor = $stmt->fetchAll();
            echo json_encode(['success' => true, 'honorCouncil' => $honor, 'source' => 'SUPABASE_CLOUD']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;


    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action in m3_struktur: ' . $action]);
}
