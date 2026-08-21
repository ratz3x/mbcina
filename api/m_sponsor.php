<?php
// m_sponsor.php - Sponsor Dashboard
// Digunakan oleh api/index.php (router)
// $sPdo, $input, $action sudah di-set oleh router

switch ($action) {
    case 'get_landing_sponsors':
        try {
            ensureM8Tables($sPdo);
            $campaigns = $sPdo->query("SELECT * FROM ad_campaigns WHERE status = 'ACTIVE' ORDER BY sort_order ASC, created_at DESC")->fetchAll() ?: [];
            if (!empty($campaigns)) {
                $sponsors = array_map(function($c, $idx) {
                    return [
                        'id' => $c['id'],
                        'order_seq' => (int)($c['sort_order'] ?? ($idx + 1)),
                        'name' => $c['name'],
                        'partner_name' => $c['partner_name'] ?? 'MB INA Official Partner',
                        'tier' => (stripos($c['package_name'] ?? '', 'gold') !== false) ? '🥇 GOLD SPONSOR' : '💎 PLATINUM SPONSOR',
                        'category' => !empty($c['partner_name']) ? ('OFFICIAL PARTNER • ' . strtoupper($c['partner_name'])) : 'OFFICIAL STRATEGIC PARTNER',
                        'logo' => $c['banner_url'] ?: ($c['image_url'] ?: 'assets/mb_badge.jpg'),
                        'banner_url' => $c['banner_url'] ?: ($c['image_url'] ?: 'assets/mb_badge.jpg'),
                        'link' => $c['link'] ?: 'https://www.mercedes-benz.co.id',
                        'desc' => $c['description'] ?: ($c['notes'] ?: ''),
                        'cta_text' => $c['cta_text'] ?: ('Kunjungi Website Resmi ' . ($c['partner_name'] ?: $c['name']) . ' ↗')
                    ];
                }, $campaigns, array_keys($campaigns));
                echo json_encode(['success' => true, 'sponsors' => $sponsors]);
            } else {
                $sponsors = $sPdo->query("SELECT * FROM sponsors ORDER BY order_seq ASC, created_at DESC")->fetchAll();
                echo json_encode(['success' => true, 'sponsors' => $sponsors ?: []]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => true, 'sponsors' => []]);
        }
        break;

    case 'save_landing_sponsor':
        try {
            $id = $input['id'] ?? ('sp_landing_' . uniqid());
            $name = $input['name'] ?? 'PT Sponsor Indonesia';
            $tier = $input['tier'] ?? '💎 PLATINUM SPONSOR';
            $category = $input['category'] ?? 'Official Partner';
            $logo = $input['logo'] ?? 'assets/mb_badge.jpg';
            $link = $input['link'] ?? 'https://www.mercedes-benz.co.id';
            $desc = $input['desc'] ?? '';
            $orderSeq = intval($input['order_seq'] ?? 1);

            $stmt = $sPdo->prepare("
                INSERT INTO sponsors (id, company_name, package_type, package_description, logo_url, banner_url, order_seq, status, created_by)
                VALUES (:id, :cname, :ptype, :pdesc, :logo, :link, :orderseq, 'ACTIVE', 'usr_superadmin')
                ON CONFLICT (id) DO UPDATE SET
                    company_name = EXCLUDED.company_name,
                    package_type = EXCLUDED.package_type,
                    package_description = EXCLUDED.package_description,
                    logo_url = EXCLUDED.logo_url,
                    banner_url = EXCLUDED.banner_url,
                    order_seq = EXCLUDED.order_seq,
                    updated_at = NOW()
            ");

            $stmt->execute([
                ':id' => $id,
                ':cname' => $name,
                ':ptype' => $tier,
                ':pdesc' => $desc,
                ':logo' => $logo,
                ':link' => $link,
                ':orderseq' => $orderSeq
            ]);

            logAudit('usr_superadmin', 'UPDATE', 'LANDING_SPONSOR', ['sponsorId' => $id, 'name' => $name, 'order' => $orderSeq]);
            echo json_encode(['success' => true, 'message' => "Sponsor '$name' berhasil disimpan dengan urutan #$orderSeq!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_landing_sponsor':
        try {
            $id = $input['id'] ?? $_GET['id'] ?? '';
            if ($id) {
                $stmt = $sPdo->prepare("DELETE FROM sponsors WHERE id = :id");
                $stmt->execute([':id' => $id]);
            }
            echo json_encode(['success' => true, 'message' => 'Sponsor berhasil dihapus dari database!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_sponsor_dashboard_data':
        try {
            $email = $_GET['email'] ?? $input['email'] ?? 'fdr@sponsor.com';
            
            $sponsor = $sPdo->query("SELECT * FROM sponsors WHERE contact_email = '$email' OR id = '$email' ORDER BY created_at DESC LIMIT 1")->fetch();
            if (!$sponsor) {
                $sponsor = $sPdo->query("SELECT * FROM sponsors ORDER BY created_at DESC LIMIT 1")->fetch();
            }

            $sponsorId = $sponsor['id'] ?? '';
            $banners = $sPdo->query("SELECT * FROM sponsor_banners WHERE sponsor_id = '$sponsorId'")->fetchAll();
            $reports = $sPdo->query("SELECT * FROM sponsor_reports WHERE sponsor_id = '$sponsorId'")->fetchAll();
            $event = $sPdo->query("SELECT * FROM events WHERE id = '{$sponsor['event_id']}'")->fetch();

            $totalImpressions = 0;
            $totalClicks = 0;
            foreach ($banners as $b) {
                $totalImpressions += intval($b['impression_count']);
                $totalClicks += intval($b['click_count']);
            }

            $reach = round($totalImpressions * 0.72);
            $engagement = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 9.9;

            echo json_encode([
                'success' => true,
                'sponsor' => $sponsor,
                'banners' => $banners,
                'reports' => $reports,
                'event' => $event,
                'stats' => [
                    'total_impressions' => $totalImpressions ?: 12450,
                    'total_clicks' => $totalClicks ?: 1234,
                    'total_reach' => $reach ?: 8900,
                    'engagement_rate' => $engagement ?: 9.9
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ============================================
    // MODUL M7.3: MANAJEMEN DONASI CAMPAIGN
    // ============================================

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action in m_sponsor: ' . $action]);
}
