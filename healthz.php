<?php

require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

echo json_encode([
    'success' => true,
    'status' => 'ok',
    'app' => APP_NAME,
    'env' => APP_ENV,
]);
