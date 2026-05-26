<?php
/**
 * Global Helper Functions
 * Anjuman E Ezzy - Live Streaming Platform
 */

if (!function_exists('e')) {
    /**
     * Sanitize output (XSS protection)
     */
    function e(mixed $value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    /**
     * Safe redirect
     */
    function redirect(string $url, int $code = 302): void {
        header('Location: ' . $url, true, $code);
        exit;
    }
}

if (!function_exists('isPost')) {
    function isPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}

if (!function_exists('isAjax')) {
    function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

if (!function_exists('jsonResponse')) {
    /**
     * Send JSON response and exit
     */
    function jsonResponse(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        exit;
    }
}

if (!function_exists('formatBytes')) {
    function formatBytes(int $bytes, int $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);
        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }
}

if (!function_exists('timeAgo')) {
    function timeAgo(string $datetime): string {
        $now  = new DateTime();
        $past = new DateTime($datetime);
        $diff = $now->diff($past);
        if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        return 'Just now';
    }
}

if (!function_exists('generatePassword')) {
    /**
     * Generate a secure random password
     */
    function generatePassword(int $length = 12): string {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
}

if (!function_exists('validateEmail')) {
    function validateEmail(string $email): bool {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

if (!function_exists('sanitizeInput')) {
    function sanitizeInput(string $input): string {
        return trim(strip_tags($input));
    }
}

if (!function_exists('getStatusBadge')) {
    function getStatusBadge(string $status): string {
        return match($status) {
            'live'    => '<span class="badge badge-live">🔴 LIVE</span>',
            'offline' => '<span class="badge badge-offline">⚫ OFFLINE</span>',
            'scheduled'=> '<span class="badge badge-scheduled">🟡 SCHEDULED</span>',
            default   => '<span class="badge badge-secondary">' . e($status) . '</span>',
        };
    }
}

if (!function_exists('csrfMeta')) {
    /**
     * Output a CSRF meta tag for AJAX use
     */
    function csrfMeta(): string {
        require_once __DIR__ . '/csrf.php';
        return '<meta name="csrf-token" content="' . CSRF::getToken() . '">';
    }
}
