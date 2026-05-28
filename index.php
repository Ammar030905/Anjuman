<?php
/**
 * Site entry point.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

Auth::startSession();

if (Auth::check()) {
    redirect(Auth::isAdmin() ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/dashboard.php');
}

redirect(BASE_URL . '/login.php');