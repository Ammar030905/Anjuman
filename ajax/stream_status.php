<?php
/**
 * Public AJAX stream status endpoint.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/stream.php';

$db = Database::getInstance();
$stream = getCurrentStream($db) ?? getLatestStream($db);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!$stream) {
    jsonResponse([
        'success' => true,
        'status' => DEFAULT_STREAM_STATUS,
        'stream' => null,
    ]);
}

jsonResponse([
    'success' => true,
    'status' => $stream['status'],
    'stream' => $stream,
    'stream_id' => $stream['id'],
    'embed_url' => $stream['embed_url'] ?? '',
    'updated_at' => $stream['created_at'] ?? null,
]);