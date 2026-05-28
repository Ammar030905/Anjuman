<?php
/**
 * Stream attendance CSV export.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/stream.php';

Auth::startSession();
Auth::requireAdmin();

$db = Database::getInstance();
$currentStream = getCurrentStream($db);
$latestStream = getLatestStream($db);
$requestedStreamId = (int) ($_GET['stream_id'] ?? 0);

$selectedStream = null;
if ($requestedStreamId > 0) {
    $selectedStream = $db->fetchOne(
        'SELECT s.id, s.title, s.youtube_url, s.youtube_video_id, s.status, s.created_by, s.created_at, u.name as creator_name
         FROM streams s
         LEFT JOIN users u ON s.created_by = u.id
         WHERE s.id = ?
         LIMIT 1',
        [$requestedStreamId]
    );
}

if (!$selectedStream) {
    $selectedStream = $currentStream ?? $latestStream;
}

$attendanceRows = $selectedStream ? getStreamAttendanceRows($db, (int) $selectedStream['id']) : [];
$filenameSlug = 'attendance';
if ($selectedStream && !empty($selectedStream['title'])) {
    $filenameSlug = preg_replace('/[^A-Za-z0-9_-]+/', '-', strtolower((string) $selectedStream['title'])) ?: 'attendance';
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filenameSlug . '-attendance.csv"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";
$output = fopen('php://output', 'w');

fputcsv($output, ['Stream Title', $selectedStream['title'] ?? '']);
fputcsv($output, ['Stream Status', strtoupper($selectedStream['status'] ?? 'offline')]);
fputcsv($output, ['Generated At', date('d M Y, h:i A')]);
fputcsv($output, []);
fputcsv($output, ['ITS Number', 'Name', 'Phone', 'Role', 'Login At', 'First Seen', 'Last Seen', 'Source Page', 'IP Address', 'User Agent']);

foreach ($attendanceRows as $row) {
    fputcsv($output, [
        $row['its_number'] ?? '',
        $row['name'] ?? '',
        $row['phone'] ?? '',
        strtoupper($row['role'] ?? 'user'),
        $row['login_at'] ?? '',
        $row['first_seen_at'] ?? '',
        $row['last_seen_at'] ?? '',
        $row['source_page'] ?? '',
        $row['ip_address'] ?? '',
        $row['user_agent'] ?? '',
    ]);
}

fclose($output);
exit;
