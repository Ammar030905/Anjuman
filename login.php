<?php
/**
 * Login Page — Anjuman E Ezzy
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

Auth::startSession();

// Already logged in? redirect
if (Auth::check()) {
    redirect(Auth::isAdmin() ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/dashboard.php');
}

$error   = '';
$success = '';

if (isset($_GET['expired'])) $error   = 'Your session has expired. Please log in again.';
if (isset($_GET['msg']) && $_GET['msg'] === 'logged_out') $success = 'You have been logged out successfully.';

// Handle POST
if (isPost()) {
    if (!CSRF::verify()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $identifier = sanitizeInput($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$identifier || !$password) {
            $error = 'ITS number and password are required.';
        } else {
            $result = Auth::login($identifier, $password);
            if ($result['success']) {
                redirect($result['role'] === 'admin'
                    ? BASE_URL . '/admin/dashboard.php'
                    : BASE_URL . '/dashboard.php');
            } else {
                $error = $result['message'];
            }
        }
    }
}

$csrfField = CSRF::field();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= e(APP_NAME) ?></title>
    <meta name="description" content="Secure login for Anjuman E Ezzy live streaming platform.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Marcellus&family=Noto+Naskh+Arabic:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body { background: transparent; }

        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 20px;
        }

        /* Animated background */
        .login-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            background:
            radial-gradient(ellipse 80% 60% at 14% 34%, rgba(11,109,88,0.11) 0%, transparent 62%),
            radial-gradient(ellipse 58% 74% at 88% 74%, rgba(183,138,58,0.11) 0%, transparent 65%),
            radial-gradient(ellipse 64% 80% at 62% 14%, rgba(27,143,151,0.08) 0%, transparent 70%),
            var(--bg-primary);
        }

        .login-bg-lines {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(183,138,58,0.09) 1px, transparent 1px),
                linear-gradient(90deg, rgba(183,138,58,0.09) 1px, transparent 1px);
            background-size: 52px 52px;
            opacity: 0.3;
        }

        .login-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            background: rgba(255, 251, 242, 0.75);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(183, 138, 58, 0.3);
            border-radius: 24px;
            padding: 44px 40px;
            box-shadow: 0 32px 80px rgba(9, 37, 31, 0.2);
            animation: slideUp 0.5s cubic-bezier(0.4,0,0.2,1) both;
        }

        .login-card::before {
            content: '';
            position: absolute;
            inset: 10px;
            border: 1px solid rgba(183, 138, 58, 0.25);
            border-radius: 18px;
            pointer-events: none;
        }

        .login-logo {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 32px;
        }

        .login-logo-icon {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--turquoise) 100%);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem;
            box-shadow: 0 8px 24px var(--accent-glow);
        }

        .login-logo-text { line-height: 1.2; }
        .login-logo-name {
            font-size: 1.2rem;
            font-family: var(--font-heading);
            letter-spacing: 0.02em;
        }
        .login-logo-sub {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .login-heading {
            font-size: 1.7rem;
            font-weight: 800;
            margin-bottom: 6px;
            letter-spacing: -0.02em;
        }

        .login-sub {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 28px;
        }

        .login-divider {
            height: 1px;
            background: var(--border);
            margin-bottom: 28px;
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            transition: var(--transition-fast);
            font-size: 1rem;
        }
        .password-toggle:hover { color: var(--text-primary); }

        .btn-login {
            width: 100%;
            padding: 14px;
            font-size: 1rem;
            font-weight: 700;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 20px var(--accent-glow);
            margin-top: 8px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-login:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 8px 28px var(--accent-glow);
        }
        .btn-login:active { transform: translateY(0); }
        .btn-login:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        .alert-error {
            background: rgba(155,114,44,0.12);
            border: 1px solid rgba(155,114,44,0.32);
            color: #8a6120;
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            font-size: 0.875rem;
            margin-bottom: 20px;
            display: flex; align-items: flex-start; gap: 10px;
        }

        .alert-success-box {
            background: rgba(11,109,88,0.12);
            border: 1px solid rgba(11,109,88,0.26);
            color: var(--accent);
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            font-size: 0.875rem;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }

        .login-footer {
            text-align: center;
            margin-top: 28px;
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="login-bg"><div class="login-bg-lines"></div></div>

<div class="login-page">
    <div class="login-card">

        <!-- Logo -->
        <div class="login-logo">
            <div class="login-logo-icon">📡</div>
            <div class="login-logo-text">
                <div class="login-logo-name"><?= e(APP_NAME) ?></div>
                <div class="login-logo-sub">Live Streaming Platform</div>
            </div>
        </div>

        <div class="login-divider"></div>

        <h1 class="login-heading">Welcome Back</h1>
        <p class="login-sub">Sign in to access the private community platform</p>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert-error" role="alert">
                <span>⚠️</span>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-success-box" role="alert">
                <span>✅</span>
                <span><?= e($success) ?></span>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="" id="loginForm" novalidate>
            <?= $csrfField ?>

            <div class="mb-4">
                <label for="identifier" class="form-label-dark">ITS Number</label>
                <input
                    type="text"
                    id="identifier"
                    name="identifier"
                    class="form-control form-control-dark"
                    placeholder="Enter 8-digit ITS number"
                    maxlength="8"
                    pattern="\d{8}"
                    value="<?= e($_POST['identifier'] ?? '') ?>"
                    required
                    autocomplete="username"
                    autofocus
                >
            </div>

            <div class="mb-4">
                <label for="password" class="form-label-dark">Password</label>
                <div class="password-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control form-control-dark"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                        style="padding-right: 48px !important;"
                    >
                    <button type="button" class="password-toggle" id="togglePwd" aria-label="Toggle password">👁️</button>
                </div>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                <span id="loginBtnText">Sign In</span>
            </button>
        </form>

        <div class="login-footer">
            🔒 Secure authenticated access only &bull; No public registration
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Password toggle
    document.getElementById('togglePwd').addEventListener('click', function() {
        const pwd = document.getElementById('password');
        const isText = pwd.type === 'text';
        pwd.type = isText ? 'password' : 'text';
        this.textContent = isText ? '👁️' : '🙈';
    });

    // Disable button on submit
    document.getElementById('loginForm').addEventListener('submit', function() {
        const btn = document.getElementById('loginBtn');
        const txt = document.getElementById('loginBtnText');
        btn.disabled = true;
        txt.textContent = 'Signing in...';
    });
</script>
</body>
</html>
