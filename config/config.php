<?php
/**
 * Core Configuration File
 * Anjuman E Ezzy - Live Streaming Platform
 */

// ─── Environment ─────────────────────────────────────────────────────────────
define('APP_NAME', 'Anjuman E Ezzy');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // 'development' | 'production'
define('BASE_URL', 'http://localhost/Anjuman-E-Ezzy');

// ─── Database ─────────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'anjuman_ezzy');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ─── Streaming ────────────────────────────────────────────────────────────────
define('DEFAULT_STREAM_STATUS', 'offline');
define('DEFAULT_STREAM_VISIBILITY', 'unlisted');

// ─── Session ──────────────────────────────────────────────────────────────────
define('SESSION_LIFETIME', 3600);       // 1 hour (seconds)
define('SESSION_NAME', 'anjuman_sess');

// ─── Security ─────────────────────────────────────────────────────────────────
define('CSRF_TOKEN_NAME', '_csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900);            // 15 minutes (seconds)

// ─── Timezone ─────────────────────────────────────────────────────────────────
date_default_timezone_set('Asia/Kolkata');

// ─── Error Reporting ─────────────────────────────────────────────────────────
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}
