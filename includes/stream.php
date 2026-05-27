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
    return 'https://www.youtube-nocookie.com/embed/' . rawurlencode($videoId) . '?autoplay=1&controls=0&rel=0&modestbranding=1&iv_load_policy=3&playsinline=1&fs=0&disablekb=1&origin=' . rawurlencode(BASE_URL);
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