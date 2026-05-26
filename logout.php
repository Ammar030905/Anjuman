<?php
/**
 * Logout Handler — Anjuman E Ezzy
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
Auth::startSession();
Auth::logout();
redirect(BASE_URL . '/login.php?msg=logged_out');
