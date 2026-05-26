<?php
/**
 * AJAX API: Admin user management.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::startSession();
if (!Auth::isAdmin()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 403);
}

$db = Database::getInstance();

if (!Auth::ensureUsersSchema($db)) {
    jsonResponse([
        'success' => false,
        'message' => 'Database schema migration could not be completed automatically. Run database/migrate_users_to_its.sql and try again.',
    ]);
}

function normalizeUserRow(array $user): array {
    return [
        'id' => (int) $user['id'],
        'its_number' => (string) $user['its_number'],
        'name' => (string) $user['name'],
        'phone' => (string) $user['phone'],
        'role' => (string) $user['role'],
        'status' => (int) $user['status'],
        'created_at' => (string) $user['created_at'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        try {
            $users = $db->fetchAll('SELECT id, its_number, name, phone, role, status, created_at FROM users ORDER BY created_at DESC');
            jsonResponse(['success' => true, 'users' => array_map('normalizeUserRow', $users)]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Failed to fetch users: ' . $e->getMessage()]);
        }
    }

    if ($action === 'get') {
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            jsonResponse(['success' => false, 'message' => 'Invalid user ID.']);
        }

        $user = $db->fetchOne('SELECT id, its_number, name, phone, role, status, created_at FROM users WHERE id = ?', [$id]);
        if (!$user) {
            jsonResponse(['success' => false, 'message' => 'User not found.']);
        }

        jsonResponse(['success' => true, 'user' => normalizeUserRow($user)]);
    }

    jsonResponse(['success' => false, 'message' => 'Invalid action.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.']);
}

if (!CSRF::verify()) {
    jsonResponse(['success' => false, 'message' => 'Security token invalid or expired.']);
}

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $itsNumber = trim(sanitizeInput($_POST['its_number'] ?? ''));
    $name = sanitizeInput($_POST['name'] ?? '');
    $phone = trim(sanitizeInput($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? 'user');
    $status = (int) ($_POST['status'] ?? 1);

    if (!$itsNumber || !$name || !$phone || !$password) {
        jsonResponse(['success' => false, 'message' => 'ITS number, name, phone, and password are required.']);
    }

    if (!preg_match('/^\d{8}$/', $itsNumber)) {
        jsonResponse(['success' => false, 'message' => 'ITS number must be exactly 8 digits.']);
    }

    if (!preg_match('/^\+?[0-9]{10,15}$/', preg_replace('/\s+/', '', $phone))) {
        jsonResponse(['success' => false, 'message' => 'Phone number must be 10 to 15 digits.']);
    }

    if (!in_array($role, ['admin', 'user'], true)) {
        jsonResponse(['success' => false, 'message' => 'Invalid role selected.']);
    }

    try {
        if ($db->fetchOne('SELECT id FROM users WHERE its_number = ? LIMIT 1', [$itsNumber])) {
            jsonResponse(['success' => false, 'message' => 'ITS number already exists.']);
        }

        $db->insert(
            'INSERT INTO users (its_number, name, phone, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$itsNumber, $name, $phone, password_hash($password, PASSWORD_DEFAULT), $role, $status === 0 ? 0 : 1]
        );

        Auth::logActivity((int) $_SESSION['user_id'], 'Created user account: ' . $name . ' (ITS: ' . $itsNumber . ')');
        jsonResponse(['success' => true, 'message' => 'User created successfully.']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($action === 'update') {
    $id = (int) ($_POST['id'] ?? 0);
    $itsNumber = trim(sanitizeInput($_POST['its_number'] ?? ''));
    $name = sanitizeInput($_POST['name'] ?? '');
    $phone = trim(sanitizeInput($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? 'user');
    $status = (int) ($_POST['status'] ?? 1);

    if (!$id || !$itsNumber || !$name || !$phone) {
        jsonResponse(['success' => false, 'message' => 'ID, ITS number, name, and phone are required.']);
    }

    if (!preg_match('/^\d{8}$/', $itsNumber)) {
        jsonResponse(['success' => false, 'message' => 'ITS number must be exactly 8 digits.']);
    }

    if (!preg_match('/^\+?[0-9]{10,15}$/', preg_replace('/\s+/', '', $phone))) {
        jsonResponse(['success' => false, 'message' => 'Phone number must be 10 to 15 digits.']);
    }

    if (!in_array($role, ['admin', 'user'], true)) {
        jsonResponse(['success' => false, 'message' => 'Invalid role selected.']);
    }

    try {
        $existing = $db->fetchOne('SELECT id FROM users WHERE its_number = ? AND id != ? LIMIT 1', [$itsNumber, $id]);
        if ($existing) {
            jsonResponse(['success' => false, 'message' => 'ITS number already belongs to another user.']);
        }

        if ($password !== '') {
            $db->execute(
                'UPDATE users SET its_number = ?, name = ?, phone = ?, password = ?, role = ?, status = ? WHERE id = ?',
                [$itsNumber, $name, $phone, password_hash($password, PASSWORD_DEFAULT), $role, $status === 0 ? 0 : 1, $id]
            );
        } else {
            $db->execute(
                'UPDATE users SET its_number = ?, name = ?, phone = ?, role = ?, status = ? WHERE id = ?',
                [$itsNumber, $name, $phone, $role, $status === 0 ? 0 : 1, $id]
            );
        }

        Auth::logActivity((int) $_SESSION['user_id'], 'Updated user account: ' . $name . ' (ITS: ' . $itsNumber . ')');
        jsonResponse(['success' => true, 'message' => 'User updated successfully.']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($action === 'toggle_status') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Invalid user ID.']);
    }

    try {
        $user = $db->fetchOne('SELECT id, name, its_number, status FROM users WHERE id = ?', [$id]);
        if (!$user) {
            jsonResponse(['success' => false, 'message' => 'User not found.']);
        }

        $newStatus = (int) $user['status'] === 1 ? 0 : 1;
        $db->execute('UPDATE users SET status = ? WHERE id = ?', [$newStatus, $id]);
        jsonResponse(['success' => true, 'message' => $newStatus === 1 ? 'User activated.' : 'User deactivated.']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => 'Invalid user ID.']);
    }

    try {
        $user = $db->fetchOne('SELECT id, name, its_number FROM users WHERE id = ?', [$id]);
        if (!$user) {
            jsonResponse(['success' => false, 'message' => 'User not found.']);
        }

        $db->execute('DELETE FROM users WHERE id = ?', [$id]);
        Auth::logActivity((int) $_SESSION['user_id'], 'Deleted user account: ' . $user['name'] . ' (ITS: ' . $user['its_number'] . ')');
        jsonResponse(['success' => true, 'message' => 'User deleted successfully.']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

jsonResponse(['success' => false, 'message' => 'Invalid action.']);