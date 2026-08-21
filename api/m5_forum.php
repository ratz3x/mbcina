<?php
// m5_forum.php - M5 - Forum, Thread, Broadcast, Moderasi
// Digunakan oleh api/index.php (router)
// $sPdo, $input, $action sudah di-set oleh router

require_once __DIR__ . '/ensure_tables.php'; // lazy-loaded


switch ($action) {
    case 'get_m5_data':
        try {
            ensureM5Tables($sPdo);
            $categories = $sPdo->query("
                SELECT c.id, c.name, c.icon, c.description, c.sort_order,
                       COALESCE(COUNT(DISTINCT t.id), 0) as thread_count,
                       COALESCE(COUNT(DISTINCT r.id) + COUNT(DISTINCT t.id), 0) as post_count
                FROM forum_categories c
                LEFT JOIN forum_threads t ON c.id = t.category_id
                LEFT JOIN forum_replies r ON t.id = r.thread_id
                GROUP BY c.id, c.name, c.icon, c.description, c.sort_order
                ORDER BY c.sort_order ASC
            ")->fetchAll() ?: [];
            $threads = $sPdo->query("SELECT t.*, c.name as category_name, c.icon as category_icon FROM forum_threads t LEFT JOIN forum_categories c ON t.category_id = c.id ORDER BY t.is_pinned DESC, t.last_post_at DESC")->fetchAll() ?: [];
            $replies = $sPdo->query("SELECT * FROM forum_replies ORDER BY created_at ASC")->fetchAll() ?: [];
            $broadcasts = [];
            $reports = [];
            $rules = [];
            try {
                $broadcasts = $sPdo->query("SELECT b.*, bs.total_sent, bs.total_views, bs.total_clicks FROM broadcasts b LEFT JOIN broadcast_stats bs ON b.id = bs.broadcast_id ORDER BY b.created_at DESC")->fetchAll() ?: [];
                $reports = $sPdo->query("SELECT r.*, t.title as thread_title FROM forum_reports r LEFT JOIN forum_threads t ON r.thread_id = t.id ORDER BY r.created_at DESC")->fetchAll() ?: [];
                $rules = $sPdo->query("SELECT * FROM forum_rules WHERE is_active = true ORDER BY sort_order ASC")->fetchAll() ?: [];
            } catch (Exception $ex) {}
            
            // Trending topics
            $trending = $sPdo->query("SELECT t.id, t.title, t.replies_count, c.name as category_name FROM forum_threads t LEFT JOIN forum_categories c ON t.category_id = c.id ORDER BY t.replies_count DESC LIMIT 5")->fetchAll() ?: [];

            echo json_encode([
                'success' => true,
                'categories' => $categories,
                'threads' => $threads,
                'replies' => $replies,
                'broadcasts' => $broadcasts,
                'reports' => $reports,
                'rules' => $rules,
                'trending' => $trending,
                'source' => 'SUPABASE_CLOUD'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create_forum_thread':
        try {
            ensureM5Tables($sPdo);
            $threadId = 'th_' . uniqid();
            $categoryId = $input['category_id'] ?? 'cat_umum';
            $title = trim($input['title'] ?? 'Untitled Thread');
            $authorName = trim($input['author_name'] ?? 'Member MB INA');
            $authorUsername = trim($input['author_username'] ?? 'member_mbina');
            $tags = trim($input['tags'] ?? '#MBINA');
            $content = trim($input['content'] ?? '');
            $avatar = strtoupper(substr($authorName, 0, 2));

            $stmt = $sPdo->prepare("
                INSERT INTO forum_threads (id, category_id, title, author_id, author_name, author_username, author_avatar, tags, content, last_post_at)
                VALUES (:id, :cat, :title, 'usr_guest', :author, :user, :avatar, :tags, :content, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                ':id' => $threadId,
                ':cat' => $categoryId,
                ':title' => $title,
                ':author' => $authorName,
                ':user' => $authorUsername,
                ':avatar' => $avatar,
                ':tags' => $tags,
                ':content' => $content
            ]);

            try {
                $sPdo->prepare("UPDATE forum_categories SET thread_count = thread_count + 1 WHERE id = ?")->execute([$categoryId]);
            } catch (Exception $ex) {}

            logAudit('usr_guest', 'CREATE', 'FORUM', ['threadId' => $threadId, 'title' => $title]);
            echo json_encode(['success' => true, 'message' => 'Thread baru berhasil dipublikasikan!', 'threadId' => $threadId]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal membuat thread: ' . $e->getMessage()]);
        }
        break;

    case 'reply_forum_thread':
        try {
            ensureM5Tables($sPdo);
            $replyId = 'rep_' . uniqid();
            $threadId = $input['thread_id'] ?? '';
            $authorName = trim($input['author_name'] ?? 'Member MB INA');
            $authorUsername = trim($input['author_username'] ?? 'member_mbina');
            $content = trim($input['content'] ?? '');
            $avatar = strtoupper(substr($authorName, 0, 2));

            $stmt = $sPdo->prepare("
                INSERT INTO forum_replies (id, thread_id, author_id, author_name, author_username, author_avatar, content)
                VALUES (:id, :th, 'usr_guest', :author, :user, :avatar, :content)
            ");
            $stmt->execute([
                ':id' => $replyId,
                ':th' => $threadId,
                ':author' => $authorName,
                ':user' => $authorUsername,
                ':avatar' => $avatar,
                ':content' => $content
            ]);

            try {
                $sPdo->prepare("UPDATE forum_threads SET replies_count = replies_count + 1, last_post_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$threadId]);
            } catch (Exception $ex) {}

            logAudit('usr_guest', 'CREATE', 'FORUM_REPLY', ['replyId' => $replyId, 'threadId' => $threadId]);
            echo json_encode(['success' => true, 'message' => 'Balasan berhasil diposting!', 'replyId' => $replyId]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal mengirim balasan: ' . $e->getMessage()]);
        }
        break;

    case 'like_forum_post':
        try {
            $targetType = $input['target_type'] ?? 'THREAD';
            $targetId = $input['target_id'] ?? '';
            
            if ($targetType === 'THREAD') {
                $sPdo->exec("UPDATE forum_threads SET views_count = views_count + 1 WHERE id = '$targetId'");
            } else {
                $sPdo->exec("UPDATE forum_replies SET likes_count = likes_count + 1 WHERE id = '$targetId'");
            }
            echo json_encode(['success' => true, 'message' => 'Apresiasi berhasil diberikan!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'report_forum_post':
        try {
            $reportId = 'rep_' . uniqid();
            $threadId = $input['thread_id'] ?? '';
            $replyId = $input['reply_id'] ?? null;
            $reason = $input['reason'] ?? 'Spam';
            $notes = $input['notes'] ?? '';
            $reporterName = $input['reporter_name'] ?? 'Pelapor Anonymous';

            $stmt = $sPdo->prepare("
                INSERT INTO forum_reports (id, thread_id, reply_id, reporter_id, reporter_name, reason, notes, status)
                VALUES (:id, :th, :rep, 'usr_guest', :rname, :reason, :notes, 'PENDING')
            ");
            $stmt->execute([
                ':id' => $reportId,
                ':th' => $threadId,
                ':rep' => $replyId,
                ':rname' => $reporterName,
                ':reason' => $reason,
                ':notes' => $notes
            ]);

            logAudit('usr_guest', 'CREATE', 'FORUM_REPORT', ['reportId' => $reportId, 'reason' => $reason]);
            echo json_encode(['success' => true, 'message' => 'Laporan spam/pelanggaran berhasil dikirim ke Tim Moderasi!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal membuat laporan: ' . $e->getMessage()]);
        }
        break;

    case 'create_broadcast':
        try {
            $broadcastId = 'bc_' . uniqid();
            $title = $input['title'] ?? 'Pengumuman Resmi';
            $content = $input['content'] ?? '';
            $targetType = $input['target_type'] ?? 'ALL';
            $targetValue = $input['target_value'] ?? 'Semua Member';

            $stmt = $sPdo->prepare("
                INSERT INTO broadcasts (id, title, content, target_type, target_value, sent_at, status, author_id)
                VALUES (:id, :title, :content, :ttype, :tval, NOW(), 'SENT', 'usr_superadmin')
            ");
            $stmt->execute([
                ':id' => $broadcastId,
                ':title' => $title,
                ':content' => $content,
                ':ttype' => $targetType,
                ':tval' => $targetValue
            ]);

            // Add stats
            $statId = 'bcs_' . uniqid();
            $sPdo->exec("
                INSERT INTO broadcast_stats (id, broadcast_id, total_sent, total_views, total_clicks)
                VALUES ('$statId', '$broadcastId', 1234, 0, 0);
            ");

            logAudit('usr_superadmin', 'CREATE', 'BROADCAST', ['broadcastId' => $broadcastId, 'title' => $title]);
            echo json_encode(['success' => true, 'message' => 'Broadcast berhasil dikirim ke target audience!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal mengirim broadcast: ' . $e->getMessage()]);
        }
        break;

    case 'moderate_report':
        try {
            $reportId = $input['report_id'] ?? '';
            $status = $input['status'] ?? 'RESOLVED';

            $stmt = $sPdo->prepare("UPDATE forum_reports SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $reportId]);

            logAudit('usr_superadmin', 'UPDATE', 'MODERATION', ['reportId' => $reportId, 'status' => $status]);
            echo json_encode(['success' => true, 'message' => "Laporan $reportId berhasil ditindaklanjuti dengan status $status!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'warn_user':
        try {
            $modId = 'mod_' . uniqid();
            $userName = $input['user_name'] ?? 'User';
            $actionType = $input['action_type'] ?? 'WARN';
            $reason = $input['reason'] ?? 'Pelanggaran Aturan Forum';
            $notes = $input['notes'] ?? '';

            $stmt = $sPdo->prepare("
                INSERT INTO moderation_actions (id, target_user_id, target_user_name, action_type, reason, notes, moderator_id)
                VALUES (:id, 'usr_target', :uname, :atype, :reason, :notes, 'usr_superadmin')
            ");
            $stmt->execute([
                ':id' => $modId,
                ':uname' => $userName,
                ':atype' => $actionType,
                ':reason' => $reason,
                ':notes' => $notes
            ]);

            logAudit('usr_superadmin', 'UPDATE', 'MODERATION_WARN', ['targetUser' => $userName, 'action' => $actionType]);
            echo json_encode(['success' => true, 'message' => "Tindakan moderasi ($actionType) terhadap $userName berhasil diproses!"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ============================================
    // MODUL M6 - MANAJEMEN EVENT & SPONSORSHIP (FULL CYCLE)
    // ============================================

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action in m5_forum: ' . $action]);
}
