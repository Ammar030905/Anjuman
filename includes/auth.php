<?php
/**
 * Authentication & Session Management
 * Anjuman E Ezzy - Live Streaming Platform
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';

class Auth {

    private static function normalizeRole($role): string {
        $role = strtolower(trim((string) $role));

        if ($role === '1' || $role === 'admin' || $role === 'administrator' || $role === 'super admin') {
            return 'admin';
        }

        return 'user';
    }

    private static function hasUsersItsSchema(Database $db): bool {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        return $db->columnExists('users', 'its_number') && $db->columnExists('users', 'phone');
    }

    private static function ensureUsersSessionSchema(Database $db): bool {
        try {
            if ($db->columnExists('users', 'session_token') && $db->columnExists('users', 'last_login_at')) {
                return true;
            }

            if ($db->isPostgres()) {
                return $db->columnExists('users', 'session_token') && $db->columnExists('users', 'last_login_at');
            }

            if (!$db->columnExists('users', 'session_token')) {
                $db->execute("ALTER TABLE users ADD COLUMN session_token CHAR(64) NULL AFTER status");
            }

            if (!$db->columnExists('users', 'last_login_at')) {
                $db->execute("ALTER TABLE users ADD COLUMN last_login_at DATETIME NULL AFTER session_token");
            }

            return true;
        } catch (Exception $e) {
            error_log('Users session schema migration failed: ' . $e->getMessage());
            return false;
        }
    }

    public static function ensureUsersSchema(Database $db): bool {
        try {
            if ($db->isPostgres()) {
                return self::hasUsersItsSchema($db)
                    && $db->columnExists('users', 'session_token')
                    && $db->columnExists('users', 'last_login_at');
            }

            if (self::hasUsersItsSchema($db)) {
                return self::ensureUsersSessionSchema($db);
            }

            $emailColumn = $db->columnExists('users', 'email');

            if (!$emailColumn) {
                return false;
            }

            $db->execute("ALTER TABLE users ADD COLUMN its_number CHAR(8) NULL AFTER id");
            $db->execute("ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER name");
            $db->execute("UPDATE users SET its_number = LPAD(id, 8, '0'), phone = COALESCE(phone, '0000000000') WHERE its_number IS NULL OR phone IS NULL");
            try {
                $db->execute("UPDATE users SET its_number = '12345678' WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
            } catch (Exception $e) {
                // Ignore if the database engine does not allow ORDER BY/LIMIT in UPDATE.
            }
            $db->execute("ALTER TABLE users MODIFY COLUMN its_number CHAR(8) NOT NULL");
            $db->execute("ALTER TABLE users MODIFY COLUMN phone VARCHAR(20) NOT NULL");

            try {
                $db->execute("ALTER TABLE users DROP INDEX uq_users_email");
            } catch (Exception $e) {
                // Ignore if the old unique index does not exist.
            }

            try {
                $db->execute("ALTER TABLE users ADD UNIQUE KEY uq_users_its_number (its_number)");
            } catch (Exception $e) {
                // Ignore if the unique index already exists.
            }

            try {
                $db->execute("ALTER TABLE users ADD KEY idx_users_phone (phone)");
            } catch (Exception $e) {
                // Ignore if the index already exists.
            }

            try {
                $db->execute("ALTER TABLE users DROP COLUMN email");
            } catch (Exception $e) {
                // Ignore if the column was already removed.
            }

            return self::hasUsersItsSchema($db) && self::ensureUsersSessionSchema($db);
        } catch (Exception $e) {
            error_log('Users schema migration failed: ' . $e->getMessage());
            return false;
        }
    }

    // ── Session Bootstrap ────────────────────────────────────────────────────
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                $secure = strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
            }

            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', $secure ? '1' : '0');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);

            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    // ── Login ────────────────────────────────────────────────────────────────
    public static function login(string $identifier): array {
        $db = Database::getInstance();

        if (!$db->isPostgres() && !self::ensureUsersSchema($db)) {
            return ['success' => false, 'message' => 'Database schema migration could not be completed automatically. Please run database/schema.sql and try again.'];
        }

        $identifier = trim(strip_tags($identifier));
        if (!preg_match('/^\d{8}$/', $identifier)) {
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        // Fetch user by ITS number only.
        $user = $db->fetchOne(
            "SELECT id, its_number, name, phone, role, status, session_token FROM users WHERE its_number = ? AND status = 1 LIMIT 1",
            [$identifier]
        );

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        if (self::normalizeRole($user['role']) === 'admin') {
            return ['success' => false, 'message' => 'Admin accounts must sign in from the admin login page.'];
        }

        $sessionToken = bin2hex(random_bytes(32));

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_its_number'] = $user['its_number'];
        $_SESSION['user_phone'] = $user['phone'];
        $_SESSION['user_role'] = self::normalizeRole($user['role']);
        $_SESSION['user_session_token'] = $sessionToken;
        $_SESSION['last_activity'] = time();

        try {
            $db->execute(
                'UPDATE users SET session_token = ?, last_login_at = NOW() WHERE id = ?',
                [$sessionToken, (int) $user['id']]
            );
        } catch (Exception $e) {
            error_log('Session token update failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to create a secure login session. Please try again.'];
        }

        // Log activity
        self::logActivity($user['id'], 'login');

        return ['success' => true, 'role' => self::normalizeRole($user['role'])];
    }

    // ── Admin Login (ITS + password required) ─────────────────────────────
    public static function adminLogin(string $identifier, string $password): array {
        $db = Database::getInstance();

        if (!$db->isPostgres() && !self::ensureUsersSchema($db)) {
            return ['success' => false, 'message' => 'Database schema migration could not be completed automatically. Please run database/schema.sql and try again.'];
        }

        $identifier = trim(strip_tags($identifier));
        if (!preg_match('/^\d{8}$/', $identifier) || $password === '') {
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        // Fetch admin user by ITS number only.
        $user = $db->fetchOne(
            "SELECT id, its_number, name, phone, role, status, session_token, password FROM users WHERE its_number = ? AND role = 'admin' AND status = 1 LIMIT 1",
            [$identifier]
        );

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        // Ensure admin account has a password set
        if (empty($user['password'])) {
            return ['success' => false, 'message' => 'Admin account has no password set. Please set a password in the database.'];
        }

        // Verify password using PHP's password_verify
        if (!password_verify($password, (string) $user['password'])) {
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        $sessionToken = bin2hex(random_bytes(32));

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_its_number'] = $user['its_number'];
        $_SESSION['user_phone'] = $user['phone'];
        $_SESSION['user_role'] = self::normalizeRole($user['role']);
        $_SESSION['user_session_token'] = $sessionToken;
        $_SESSION['last_activity'] = time();

        try {
            $db->execute(
                'UPDATE users SET session_token = ?, last_login_at = NOW() WHERE id = ?',
                [$sessionToken, (int) $user['id']]
            );
        } catch (Exception $e) {
            error_log('Session token update failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to create a secure login session. Please try again.'];
        }

        // Log activity
        self::logActivity($user['id'], 'admin_login');

        return ['success' => true, 'role' => self::normalizeRole($user['role'])];
    }

    // ── Logout ───────────────────────────────────────────────────────────────
    public static function logout(): void {
        self::startSession();
        if (isset($_SESSION['user_id'])) {
            self::logActivity($_SESSION['user_id'], 'logout');
            try {
                $db = Database::getInstance();
                $db->execute(
                    'UPDATE users SET session_token = NULL WHERE id = ? AND session_token = ?',
                    [(int) $_SESSION['user_id'], (string) ($_SESSION['user_session_token'] ?? '')]
                );
            } catch (Exception $e) {
                error_log('Session token clear failed: ' . $e->getMessage());
            }
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ── Check Authenticated ──────────────────────────────────────────────────
    public static function check(): bool {
        self::startSession();
        if (empty($_SESSION['user_id'])) return false;
        // Session timeout check
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
            self::logout();
            return false;
        }

        try {
            $db = Database::getInstance();
            $record = $db->fetchOne(
                'SELECT session_token, status FROM users WHERE id = ? LIMIT 1',
                [(int) $_SESSION['user_id']]
            );

            if (!$record || (int) ($record['status'] ?? 0) !== 1) {
                self::logout();
                return false;
            }

            $sessionToken = (string) ($_SESSION['user_session_token'] ?? '');
            if ($sessionToken === '' || !hash_equals((string) ($record['session_token'] ?? ''), $sessionToken)) {
                self::logout();
                return false;
            }
        } catch (Exception $e) {
            error_log('Session validation failed: ' . $e->getMessage());
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    // ── Require Auth (redirect if not logged in) ─────────────────────────────
    public static function requireAuth(): void {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/login.php?expired=1');
            exit;
        }
    }

    // ── Require Admin ────────────────────────────────────────────────────────
    public static function requireAdmin(): void {
        self::requireAuth();
        if (self::normalizeRole($_SESSION['user_role'] ?? '') !== 'admin') {
            header('Location: ' . BASE_URL . '/dashboard.php?error=forbidden');
            exit;
        }
    }

    // ── Require User (redirect admins to admin panel) ────────────────────────
    public static function requireUser(): void {
        self::requireAuth();
        if (self::normalizeRole($_SESSION['user_role'] ?? '') === 'admin') {
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            exit;
        }
    }

    // ── Current User ─────────────────────────────────────────────────────────
    public static function user(): ?array {
        if (!self::check()) return null;
        return [
            'id'    => $_SESSION['user_id'],
            'name'  => $_SESSION['user_name'],
            'its_number' => $_SESSION['user_its_number'] ?? '',
            'phone' => $_SESSION['user_phone'] ?? '',
            'role'  => $_SESSION['user_role'],
        ];
    }

    public static function isAdmin(): bool {
        return self::check() && self::normalizeRole($_SESSION['user_role'] ?? '') === 'admin';
    }

    // ── Activity Logger ──────────────────────────────────────────────────────
    public static function logActivity(int $userId, string $action): void {
        try {
            $db = Database::getInstance();
            $db->insert(
                'INSERT INTO activity_logs (user_id, action, ip_address, user_agent, timestamp) VALUES (?, ?, ?, ?, NOW())',
                [$userId, $action, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']
            );
        } catch (Exception $e) {
            error_log('Activity log failed: ' . $e->getMessage());
        }
    }
}
