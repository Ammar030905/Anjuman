<?php
/**
 * CSRF Protection Helper
 * Anjuman E Ezzy - Live Streaming Platform
 */

require_once __DIR__ . '/../config/config.php';

class CSRF {

    /**
     * Generate and store a CSRF token in the session
     */
    public static function generateToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * Return an HTML hidden input field with the CSRF token
     */
    public static function field(): string {
        $token = self::generateToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validate the submitted CSRF token
     */
    public static function verify(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $submitted = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $stored    = $_SESSION[CSRF_TOKEN_NAME] ?? '';

        if (empty($submitted) || empty($stored)) return false;

        // Use hash_equals for timing-safe comparison
        if (hash_equals($stored, $submitted)) {
            return true;
        }
        return false;
    }

    /**
     * Verify and die with JSON error if invalid (for AJAX endpoints)
     */
    public static function verifyOrFail(): void {
        if (!self::verify()) {
            http_response_code(403);
            die(json_encode(['success' => false, 'message' => 'Invalid CSRF token.']));
        }
    }

    /**
     * Get the current token value (without generating a new one)
     */
    public static function getToken(): string {
        return $_SESSION[CSRF_TOKEN_NAME] ?? self::generateToken();
    }
}
