<?php
/**
 * AJAX API: Admin stream management for embedded YouTube URLs.
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

if (!$db->isPostgres() && !ensureStreamsSchema($db)) {
    jsonResponse([
        'success' => false,
        'message' => 'Database schema migration could not be completed automatically. Please run the stream migration and try again.',
    ]);
}

function normalizeStreamPayload(array $stream): array {
    return normalizeStreamRow([
        'id' => (int) $stream['id'],
        'title' => (string) $stream['title'],
        'youtube_url' => (string) ($stream['youtube_url'] ?? ''),
        'youtube_video_id' => (string) ($stream['youtube_video_id'] ?? ''),
        'status' => (string) $stream['status'],
        'created_by' => (int) $stream['created_by'],
        'created_at' => (string) $stream['created_at'],
        'creator_name' => (string) ($stream['creator_name'] ?? ''),
    ]);
}

function setOtherStreamsOffline(Database $db, ?int $exceptId = null): void {
    if ($exceptId) {
        $db->execute("UPDATE streams SET status = 'offline' WHERE status = 'live' AND id != ?", [$exceptId]);
        return;
    }

    $db->execute("UPDATE streams SET status = 'offline' WHERE status = 'live'");
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        try {
            $streams = $db->fetchAll(
                'SELECT s.id, s.title, s.youtube_url, s.youtube_video_id, s.status, s.created_by, s.created_at, u.name AS creator_name
                 FROM streams s
                 LEFT JOIN users u ON s.created_by = u.id
                 ORDER BY s.created_at DESC'
            );
            jsonResponse(['success' => true, 'streams' => array_map('normalizeStreamPayload', $streams)]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Failed to fetch streams: ' . $e->getMessage()]);
        }
    }

    if ($action === 'current') {
        $stream = getCurrentStream($db) ?? getLatestStream($db);
        jsonResponse(['success' => true, 'stream' => $stream]);
    }

    if ($action === 'get') {
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'Invalid stream ID.']);
        }

        $stream = $db->fetchOne(
            'SELECT s.id, s.title, s.youtube_url, s.youtube_video_id, s.status, s.created_by, s.created_at, u.name AS creator_name
             FROM streams s
             LEFT JOIN users u ON s.created_by = u.id
             WHERE s.id = ?
             LIMIT 1',
            [$id]
        );

        if (!$stream) {
            jsonResponse(['success' => false, 'message' => 'Stream not found.']);
        }

        jsonResponse(['success' => true, 'stream' => normalizeStreamPayload($stream)]);
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

if ($action === 'save' || $action === 'start') {
    $id = (int) ($_POST['id'] ?? 0);
    $title = sanitizeInput($_POST['title'] ?? '');
    $youtubeUrl = trim((string) ($_POST['youtube_url'] ?? ''));
    $status = sanitizeInput($_POST['status'] ?? 'live');

    if ($action === 'start' && $id > 0 && ($title === '' || $youtubeUrl === '')) {
        $existing = $db->fetchOne('SELECT * FROM streams WHERE id = ? LIMIT 1', [$id]);
        if ($existing) {
            $title = $title !== '' ? $title : (string) $existing['title'];
            $youtubeUrl = $youtubeUrl !== '' ? $youtubeUrl : (string) $existing['youtube_url'];
        }
    }

    if (!$title || !$youtubeUrl) {
        jsonResponse(['success' => false, 'message' => 'Title and YouTube URL are required.']);
    }

    $videoId = extractYouTubeVideoId($youtubeUrl);
    if (!$videoId) {
        jsonResponse(['success' => false, 'message' => 'Unable to extract a valid YouTube video ID.']);
    }

    if (!in_array($status, ['live', 'offline'], true)) {
        $status = 'offline';
    }

    try {
        if ($action === 'start' || $status === 'live') {
            setOtherStreamsOffline($db, $id ?: null);
            $status = 'live';
        }

        if ($id > 0) {
            $db->execute(
                'UPDATE streams SET title = ?, youtube_url = ?, youtube_video_id = ?, status = ? WHERE id = ?',
                [$title, $youtubeUrl, $videoId, $status, $id]
            );
            $streamId = $id;
        } else {
            $streamId = (int) $db->insert(
                'INSERT INTO streams (title, youtube_url, youtube_video_id, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
                [$title, $youtubeUrl, $videoId, $status, (int) $_SESSION['user_id']]
            );
        }

        $stream = $db->fetchOne(
            'SELECT s.id, s.title, s.youtube_url, s.youtube_video_id, s.status, s.created_by, s.created_at, u.name AS creator_name
             FROM streams s
             LEFT JOIN users u ON s.created_by = u.id
             WHERE s.id = ?
             LIMIT 1',
            [$streamId]
        );

        Auth::logActivity((int) $_SESSION['user_id'], ($id > 0 ? 'Updated' : 'Created') . ' stream: ' . $title);

        jsonResponse([
            'success' => true,
            'message' => $status === 'live' ? 'Stream is live and embedded on the site.' : 'Stream saved successfully.',
            'stream' => normalizeStreamPayload($stream),
        ]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($action === 'end') {
    $id = (int) ($_POST['id'] ?? 0);

    try {
        if ($id > 0) {
            $db->execute('UPDATE streams SET status = ? WHERE id = ?', ['offline', $id]);
        } else {
            $db->execute('UPDATE streams SET status = ? WHERE status = ?', ['offline', 'live']);
        }

        jsonResponse(['success' => true, 'message' => 'Stream ended successfully.']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Invalid stream ID.']);
    }

    try {
        $stream = $db->fetchOne('SELECT id, title FROM streams WHERE id = ?', [$id]);
        if (!$stream) {
            jsonResponse(['success' => false, 'message' => 'Stream not found.']);
        }

        $db->execute('DELETE FROM streams WHERE id = ?', [$id]);
        Auth::logActivity((int) $_SESSION['user_id'], 'Deleted stream: ' . $stream['title']);
        jsonResponse(['success' => true, 'message' => 'Stream deleted successfully.']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

jsonResponse(['success' => false, 'message' => 'Invalid action.']);