<?php
/**
 * AJAX endpoint: verify the current authenticated session.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::startSession();

if (!Auth::check()) {
    jsonResponse([
        'success' => false,
        'active' => false,
        'expired' => true,
        'message' => 'Your session has expired. Please log in again.',
    ], 401);
}

jsonResponse([
    'success' => true,
    'active' => true,
    'expired' => false,
    'role' => Auth::isAdmin() ? 'admin' : 'user',
]);