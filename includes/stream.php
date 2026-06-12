<?php
/**
 * Stream helpers for embedded YouTube live playback.
 */

require_once __DIR__ . '/db.php';

function hasStreamsSchema(Database $db): bool {
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    try {
        $columns = ['title', 'status', 'created_at', 'youtube_url', 'youtube_video_id', 'created_by'];
        foreach ($columns as $column) {
            if (!$db->columnExists('streams', $column)) {
                $cached = false;
                return false;
            }
        }

        $cached = true;
        return true;
    } catch (Exception $e) {
        $cached = false;
        return false;
    }
}

function ensureStreamsSchema(?Database $db = null): bool {
    $db = $db ?? Database::getInstance();

    try {
        if (hasStreamsSchema($db)) {
            return true;
        }

        if ($db->isPostgres()) {
            return false;
        }

        $hasYoutubeUrl = $db->columnExists('streams', 'youtube_url');
        $hasYoutubeVideoId = $db->columnExists('streams', 'youtube_video_id');
        $hasCreatedBy = $db->columnExists('streams', 'created_by');
        $hasCreatedAt = $db->columnExists('streams', 'created_at');

        if (!$hasYoutubeUrl) {
            $db->execute("ALTER TABLE streams ADD COLUMN youtube_url VARCHAR(500) NULL AFTER title");
        }

        if (!$hasYoutubeVideoId) {
            $db->execute("ALTER TABLE streams ADD COLUMN youtube_video_id VARCHAR(20) NULL AFTER youtube_url");
        }

        if (!$hasCreatedBy) {
            $db->execute("ALTER TABLE streams ADD COLUMN created_by INT(11) UNSIGNED NULL AFTER status");
        }

        if (!$hasCreatedAt) {
            $db->execute("ALTER TABLE streams ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        }

        $db->execute("UPDATE streams SET youtube_url = COALESCE(youtube_url, ''), youtube_video_id = COALESCE(youtube_video_id, ''), created_by = COALESCE(created_by, 1)");

        try {
            $db->execute("ALTER TABLE streams MODIFY COLUMN youtube_url VARCHAR(500) NOT NULL");
        } catch (Exception $e) {
            // Ignore if the table engine/database cannot tighten the column yet.
        }

        try {
            $db->execute("ALTER TABLE streams MODIFY COLUMN youtube_video_id VARCHAR(20) NOT NULL");
        } catch (Exception $e) {
            // Ignore if the table engine/database cannot tighten the column yet.
        }

        try {
            $db->execute("ALTER TABLE streams MODIFY COLUMN created_by INT(11) UNSIGNED NOT NULL");
        } catch (Exception $e) {
            // Ignore if the table engine/database cannot tighten the column yet.
        }

        return hasStreamsSchema($db);
    } catch (Exception $e) {
        error_log('Streams schema migration failed: ' . $e->getMessage());
        return false;
    }
}

function extractYouTubeVideoId(string $input): ?string {
    $value = trim($input);
    if ($value === '') {
        return null;
    }

    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $value)) {
        return $value;
    }

    $parts = parse_url($value);
    if ($parts === false) {
        return null;
    }

    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
        foreach (['v', 'vi', 'video_id'] as $key) {
            if (!empty($query[$key]) && preg_match('/^[A-Za-z0-9_-]{11}$/', (string) $query[$key])) {
                return (string) $query[$key];
            }
        }
    }

    if (!empty($parts['path'])) {
        $segments = array_values(array_filter(explode('/', trim($parts['path'], '/'))));
        $lastSegment = $segments ? end($segments) : '';
        if (is_string($lastSegment) && preg_match('/^[A-Za-z0-9_-]{11}$/', $lastSegment)) {
            return $lastSegment;
        }

        if (count($segments) >= 2 && $segments[0] === 'shorts' && preg_match('/^[A-Za-z0-9_-]{11}$/', $segments[1])) {
            return $segments[1];
        }

        if (count($segments) >= 2 && $segments[0] === 'live' && preg_match('/^[A-Za-z0-9_-]{11}$/', $segments[1])) {
            return $segments[1];
        }
    }

    if (preg_match('/(?:v=|\/)([A-Za-z0-9_-]{11})(?:[?&\/]|$)/', $value, $matches)) {
        return $matches[1];
    }

    return null;
}

function buildYouTubeEmbedUrl(string $videoId): string {
<<<<<<< HEAD
    // Use youtube-nocookie.com for enhanced privacy
    // Parameters:
    // - autoplay=1: auto-start video
    // - controls=0: hide YouTube controls completely
    // - rel=0: prevent related videos
    // - modestbranding=1: minimize YouTube branding
    // - iv_load_policy=3: disable video annotations
    // - playsinline=1: play inline on mobile
    // - fs=0: disable YouTube's fullscreen button
    // - disablekb=1: disable keyboard shortcuts that might redirect
    // - enablejsapi=1: allow JavaScript control
    // - origin: security requirement for API
    return 'https://www.youtube-nocookie.com/embed/' . rawurlencode($videoId) 
        . '?autoplay=1&controls=0&rel=0&modestbranding=1&iv_load_policy=3'
        . '&playsinline=1&fs=0&disablekb=1&enablejsapi=1'
        . '&origin=' . rawurlencode(BASE_URL);
=======
    $params = [
        'autoplay' => '1',
        'controls' => '0',
        'rel' => '0',
        'modestbranding' => '1',
        'iv_load_policy' => '3',
        'cc_load_policy' => '0',
        'playsinline' => '1',
        'fs' => '0',
        'disablekb' => '1',
        'enablejsapi' => '1',
        'origin' => BASE_URL,
    ];

    return 'https://www.youtube-nocookie.com/embed/' . rawurlencode($videoId) . '?' . http_build_query($params);
>>>>>>> ade887517053e8cccb927811754a0a991a87e212
}

function getCurrentStream(?Database $db = null): ?array {
    $db = $db ?? Database::getInstance();
    if (!ensureStreamsSchema($db)) {
        return null;
    }
    $stream = $db->fetchOne(
    "SELECT s.id, s.title, s.youtube_url, s.youtube_video_id, s.status, s.created_by, s.created_at, u.name as creator_name
         FROM streams s
         LEFT JOIN users u ON s.created_by = u.id
         WHERE s.status = 'live'
         ORDER BY s.created_at DESC, s.id DESC
         LIMIT 1"
    );

    return $stream ? normalizeStreamRow($stream) : null;
}

function getLatestStream(?Database $db = null): ?array {
    $db = $db ?? Database::getInstance();
    if (!ensureStreamsSchema($db)) {
        return null;
    }
    $stream = $db->fetchOne(
    "SELECT s.id, s.title, s.youtube_url, s.youtube_video_id, s.status, s.created_by, s.created_at, u.name as creator_name
         FROM streams s
         LEFT JOIN users u ON s.created_by = u.id
         ORDER BY s.created_at DESC, s.id DESC
         LIMIT 1"
    );

    return $stream ? normalizeStreamRow($stream) : null;
}

function normalizeStreamRow(array $stream): array {
    $stream['embed_url'] = !empty($stream['youtube_video_id'])
        ? buildYouTubeEmbedUrl((string) $stream['youtube_video_id'])
        : '';
    $stream['is_live'] = ($stream['status'] ?? '') === 'live';
    return $stream;
}

function hasStreamAttendanceSchema(Database $db): bool {
    try {
        $columns = ['stream_id', 'user_id', 'its_number', 'name', 'phone', 'role', 'first_seen_at', 'last_seen_at'];
        foreach ($columns as $column) {
            if (!$db->columnExists('stream_attendance', $column)) {
                return false;
            }
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}

function ensureStreamAttendanceSchema(?Database $db = null): bool {
    $db = $db ?? Database::getInstance();

    if ($db->isPostgres()) {
        return hasStreamAttendanceSchema($db);
    }

    try {
        if (hasStreamAttendanceSchema($db)) {
            return true;
        }

        $db->execute("CREATE TABLE IF NOT EXISTS stream_attendance (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            stream_id INT(11) UNSIGNED NOT NULL,
            stream_title VARCHAR(255) NOT NULL,
            user_id INT(11) UNSIGNED NOT NULL,
            its_number CHAR(8) NOT NULL,
            name VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'user',
            session_token CHAR(64) DEFAULT NULL,
            login_at DATETIME DEFAULT NULL,
            first_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            source_page VARCHAR(50) DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(500) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_stream_attendance_stream_user (stream_id, user_id),
            KEY idx_stream_attendance_stream (stream_id),
            KEY idx_stream_attendance_user (user_id),
            KEY idx_stream_attendance_seen (last_seen_at),
            CONSTRAINT fk_stream_attendance_stream FOREIGN KEY (stream_id) REFERENCES streams (id) ON DELETE CASCADE,
            CONSTRAINT fk_stream_attendance_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        return hasStreamAttendanceSchema($db);
    } catch (Exception $e) {
        error_log('Stream attendance schema migration failed: ' . $e->getMessage());
        return false;
    }
}

function recordStreamAttendance(?Database $db, array $stream, array $user, string $sourcePage = 'stream'): bool {
    $db = $db ?? Database::getInstance();

    if (!ensureStreamAttendanceSchema($db) || empty($stream['id']) || empty($user['id'])) {
        return false;
    }

    $loginRow = $db->fetchOne('SELECT last_login_at FROM users WHERE id = ? LIMIT 1', [(int) $user['id']]);
    $params = [
        (int) $stream['id'],
        (string) ($stream['title'] ?? 'Live Stream'),
        (int) $user['id'],
        (string) ($user['its_number'] ?? ''),
        (string) ($user['name'] ?? ''),
        (string) ($user['phone'] ?? ''),
        (string) ($user['role'] ?? 'user'),
        (string) ($_SESSION['user_session_token'] ?? ''),
        $loginRow['last_login_at'] ?? null,
        $sourcePage,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? '',
    ];

    try {
        if ($db->isPostgres()) {
            $db->execute(
                'INSERT INTO stream_attendance (stream_id, stream_title, user_id, its_number, name, phone, role, session_token, login_at, first_seen_at, last_seen_at, source_page, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, ?)
                 ON CONFLICT (stream_id, user_id)
                 DO UPDATE SET
                    stream_title = EXCLUDED.stream_title,
                    its_number = EXCLUDED.its_number,
                    name = EXCLUDED.name,
                    phone = EXCLUDED.phone,
                    role = EXCLUDED.role,
                    session_token = EXCLUDED.session_token,
                    login_at = EXCLUDED.login_at,
                    last_seen_at = NOW(),
                    source_page = EXCLUDED.source_page,
                    ip_address = EXCLUDED.ip_address,
                    user_agent = EXCLUDED.user_agent',
                $params
            );
        } else {
            $db->execute(
                'INSERT INTO stream_attendance (stream_id, stream_title, user_id, its_number, name, phone, role, session_token, login_at, first_seen_at, last_seen_at, source_page, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    stream_title = VALUES(stream_title),
                    its_number = VALUES(its_number),
                    name = VALUES(name),
                    phone = VALUES(phone),
                    role = VALUES(role),
                    session_token = VALUES(session_token),
                    login_at = VALUES(login_at),
                    last_seen_at = NOW(),
                    source_page = VALUES(source_page),
                    ip_address = VALUES(ip_address),
                    user_agent = VALUES(user_agent)',
                $params
            );
        }

        return true;
    } catch (Exception $e) {
        error_log('Stream attendance record failed: ' . $e->getMessage());
        return false;
    }
}

function getStreamAttendanceCount(?Database $db, int $streamId): int {
    $db = $db ?? Database::getInstance();

    if (!ensureStreamAttendanceSchema($db) || $streamId <= 0) {
        return 0;
    }

    try {
        $row = $db->fetchOne('SELECT COUNT(*) AS total FROM stream_attendance WHERE stream_id = ?', [$streamId]);
        return (int) ($row['total'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

function getStreamAttendanceRows(?Database $db, int $streamId): array {
    $db = $db ?? Database::getInstance();

    if (!ensureStreamAttendanceSchema($db) || $streamId <= 0) {
        return [];
    }

    try {
        return $db->fetchAll(
            'SELECT stream_id, stream_title, user_id, its_number, name, phone, role, login_at, first_seen_at, last_seen_at, source_page, ip_address, user_agent
             FROM stream_attendance
             WHERE stream_id = ?
             ORDER BY first_seen_at ASC, id ASC',
            [$streamId]
        );
    } catch (Exception $e) {
        return [];
    }
}

function getLatestAttendanceStream(?Database $db = null): ?array {
    $db = $db ?? Database::getInstance();

    if (!ensureStreamsSchema($db)) {
        return null;
    }

    $stream = $db->fetchOne(
        'SELECT s.id, s.title, s.youtube_url, s.youtube_video_id, s.status, s.created_by, s.created_at, u.name as creator_name
         FROM streams s
         LEFT JOIN users u ON s.created_by = u.id
         ORDER BY s.created_at DESC, s.id DESC
         LIMIT 1'
    );

    return $stream ? normalizeStreamRow($stream) : null;
}

function hasDailyNoticesSchema(Database $db): bool {
    try {
        $columns = ['notice_date', 'title', 'message', 'status', 'created_by', 'created_at', 'updated_at'];
        foreach ($columns as $column) {
            if (!$db->columnExists('daily_notices', $column)) {
                return false;
            }
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function ensureDailyNoticesSchema(?Database $db = null): bool {
    $db = $db ?? Database::getInstance();

    if ($db->isPostgres()) {
        return hasDailyNoticesSchema($db);
    }

    try {
        if (hasDailyNoticesSchema($db)) {
            return true;
        }

        $db->execute("CREATE TABLE IF NOT EXISTS daily_notices (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            notice_date DATE NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_by INT(11) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_daily_notices_date (notice_date),
            KEY idx_daily_notices_status (status),
            KEY idx_daily_notices_created_by (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        return hasDailyNoticesSchema($db);
    } catch (Exception $e) {
        error_log('Daily notices schema migration failed: ' . $e->getMessage());
        return false;
    }
}

function getActiveDailyNotice(?Database $db = null): ?array {
    $db = $db ?? Database::getInstance();

    if (!ensureDailyNoticesSchema($db)) {
        return null;
    }

    try {
        $notice = $db->fetchOne(
            'SELECT n.id, n.notice_date, n.title, n.message, n.status, n.created_by, n.created_at, n.updated_at, u.name AS creator_name
             FROM daily_notices n
             LEFT JOIN users u ON n.created_by = u.id
             WHERE n.status = ?
             ORDER BY n.notice_date DESC, n.created_at DESC, n.id DESC
             LIMIT 1',
            ['active']
        );

        return $notice ?: null;
    } catch (Exception $e) {
        error_log('Fetching active daily notice failed: ' . $e->getMessage());
        return null;
    }
}

function getDailyNotices(?Database $db = null): array {
    $db = $db ?? Database::getInstance();

    if (!ensureDailyNoticesSchema($db)) {
        return [];
    }

    try {
        return $db->fetchAll(
            'SELECT n.id, n.notice_date, n.title, n.message, n.status, n.created_by, n.created_at, n.updated_at, u.name AS creator_name
             FROM daily_notices n
             LEFT JOIN users u ON n.created_by = u.id
             ORDER BY n.notice_date DESC, n.created_at DESC, n.id DESC'
        );
    } catch (Exception $e) {
        error_log('Fetching daily notices failed: ' . $e->getMessage());
        return [];
    }
}