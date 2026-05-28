<?php
/**
 * AJAX API: Admin daily notice management.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/stream.php';

Auth::startSession();
if (!Auth::isAdmin()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 403);
}

$db = Database::getInstance();

if (!$db->isPostgres()) {
    ensureDailyNoticesSchema($db);
}

function normalizeNoticeRow(array $notice): array {
    return [
        'id' => (int) $notice['id'],
        'notice_date' => (string) $notice['notice_date'],
        'title' => (string) $notice['title'],
        'message' => (string) $notice['message'],
        'status' => (string) $notice['status'],
        'created_by' => (int) $notice['created_by'],
        'created_at' => (string) $notice['created_at'],
        'updated_at' => (string) $notice['updated_at'],
        'creator_name' => (string) ($notice['creator_name'] ?? ''),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        $notices = hasDailyNoticesSchema($db) ? getDailyNotices($db) : [];
        jsonResponse(['success' => true, 'notices' => array_map('normalizeNoticeRow', $notices)]);
    }

    if ($action === 'get') {
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'Invalid notice ID.']);
        }

        $notice = $db->fetchOne(
            'SELECT n.id, n.notice_date, n.title, n.message, n.status, n.created_by, n.created_at, n.updated_at, u.name AS creator_name
             FROM daily_notices n
             LEFT JOIN users u ON n.created_by = u.id
             WHERE n.id = ?
             LIMIT 1',
            [$id]
        );

        if (!$notice) {
            jsonResponse(['success' => false, 'message' => 'Notice not found.']);
        }

        jsonResponse(['success' => true, 'notice' => normalizeNoticeRow($notice)]);
    }

    jsonResponse(['success' => false, 'message' => 'Invalid action.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.']);
}

if (!CSRF::verify()) {
    jsonResponse(['success' => false, 'message' => 'Security token invalid or expired.']);
}

$action = $_POST['action'] ?? '';

if ($action === 'create' || $action === 'update') {
    $id = (int) ($_POST['id'] ?? 0);
    $noticeDate = trim((string) ($_POST['notice_date'] ?? date('Y-m-d')));
    $title = sanitizeInput($_POST['title'] ?? '');
    $message = trim(sanitizeInput($_POST['message'] ?? ''));
    $status = sanitizeInput($_POST['status'] ?? 'active');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $noticeDate)) {
        jsonResponse(['success' => false, 'message' => 'Notice date must be in YYYY-MM-DD format.']);
    }

    if ($title === '' || $message === '') {
        jsonResponse(['success' => false, 'message' => 'Title and message are required.']);
    }

    if (!in_array($status, ['active', 'inactive'], true)) {
        $status = 'active';
    }

    try {
        if ($status === 'active') {
            $db->execute("UPDATE daily_notices SET status = 'inactive' WHERE status = 'active'");
        }

        if ($id > 0) {
            $db->execute(
                'UPDATE daily_notices SET notice_date = ?, title = ?, message = ?, status = ? WHERE id = ?',
                [$noticeDate, $title, $message, $status, $id]
            );
        } else {
            $db->insert(
                'INSERT INTO daily_notices (notice_date, title, message, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
                [$noticeDate, $title, $message, $status, (int) $_SESSION['user_id']]
            );
        }

        Auth::logActivity((int) $_SESSION['user_id'], ($id > 0 ? 'Updated' : 'Created') . ' daily notice: ' . $title);
        jsonResponse(['success' => true, 'message' => 'Daily notice saved successfully.']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($action === 'toggle_status') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Invalid notice ID.']);
    }

    try {
        $notice = $db->fetchOne('SELECT id, title, status FROM daily_notices WHERE id = ? LIMIT 1', [$id]);
        if (!$notice) {
            jsonResponse(['success' => false, 'message' => 'Notice not found.']);
        }

        $newStatus = ($notice['status'] ?? '') === 'active' ? 'inactive' : 'active';
        if ($newStatus === 'active') {
            $db->execute("UPDATE daily_notices SET status = 'inactive' WHERE status = 'active'");
        }

        $db->execute('UPDATE daily_notices SET status = ? WHERE id = ?', [$newStatus, $id]);
        jsonResponse(['success' => true, 'message' => $newStatus === 'active' ? 'Notice activated.' : 'Notice deactivated.']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Invalid notice ID.']);
    }

    try {
        $notice = $db->fetchOne('SELECT id, title FROM daily_notices WHERE id = ?', [$id]);
        if (!$notice) {
            jsonResponse(['success' => false, 'message' => 'Notice not found.']);
        }

        $db->execute('DELETE FROM daily_notices WHERE id = ?', [$id]);
        Auth::logActivity((int) $_SESSION['user_id'], 'Deleted daily notice: ' . $notice['title']);
        jsonResponse(['success' => true, 'message' => 'Notice deleted successfully.']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

jsonResponse(['success' => false, 'message' => 'Invalid action.']);