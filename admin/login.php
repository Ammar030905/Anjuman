<?php
/**
 * Admin Login — requires ITS + password
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::startSession();

// Already logged in as admin?
if (Auth::isAdmin()) {
    redirect(BASE_URL . '/admin/dashboard.php');
}

$error = '';
if (isPost()) {
    if (!CSRF::verify()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $its = sanitizeInput($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';
        $result = Auth::adminLogin($its, $password);
        if ($result['success']) {
            redirect(BASE_URL . '/admin/dashboard.php');
        } else {
            $error = $result['message'];
        }
    }
}

$csrfField = CSRF::field();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Login — <?= e(APP_NAME) ?></title>
    <?= csrfMeta() ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/admin-login.css" rel="stylesheet">
</head>
<body class="admin-login">
<div class="login-card-admin">
    <div class="text-center mb-3">
        <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="Anjuman logo" style="width:72px;height:72px;object-fit:contain;margin-bottom:8px;" onerror="this.style.display='none'">
        <div class="brand-title">Admin Panel — Anjuman E Ezzy</div>
        <div class="brand-sub">Hatemi Mohallah, Rajkot</div>
    </div>

    <?php if ($error): ?>
        <div class="alert-admin" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>
        <?= $csrfField ?>
        <div class="mb-3">
            <label for="identifier" class="form-label">ITS Number</label>
            <input type="text" id="identifier" name="identifier" class="form-control admin" maxlength="8" pattern="\d{8}" required value="<?= e($_POST['identifier'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-control admin" required>
        </div>
        <div class="d-grid">
            <button class="btn-admin-login" type="submit">Sign In</button>
        </div>
    </form>
</div>
</body>
</html>
