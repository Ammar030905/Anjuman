<?php
/**
 * Core Configuration File
 * Anjuman E Ezzy - Live Streaming Platform
 */

// ─── Environment ─────────────────────────────────────────────────────────────
define('APP_NAME', 'Anjuman E Ezzy');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'development'); // 'development' | 'production'

$baseUrl = getenv('BASE_URL') ?: getenv('APP_URL') ?: 'http://localhost/Anjuman-E-Ezzy';
define('BASE_URL', rtrim($baseUrl, '/'));

// ─── Database ─────────────────────────────────────────────────────────────────
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'mysql');
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'anjuman_ezzy');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
define('DB_SCHEMA', getenv('DB_SCHEMA') ?: (in_array(strtolower(DB_DRIVER), ['pgsql', 'postgres', 'postgresql'], true) ? 'public' : DB_NAME));
define('DB_SSLMODE', getenv('DB_SSLMODE') ?: 'require');
define('DATABASE_URL', getenv('DATABASE_URL') ?: getenv('DB_URL') ?: '');

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
    ini_set('error_log', 'php://stderr');
    ini_set('expose_php', '0');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);
    ini_set('default_socket_timeout', '5');
}
