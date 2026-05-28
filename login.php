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

        if (!$identifier) {
            $error = 'ITS number is required.';
        } else {
            $result = Auth::login($identifier);
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
        body {
            background:
                radial-gradient(circle at top left, rgba(183, 138, 58, 0.18) 0%, transparent 32%),
                radial-gradient(circle at bottom right, rgba(11, 109, 88, 0.08) 0%, transparent 28%),
                linear-gradient(180deg, #f9f2df 0%, #f5edd8 100%);
        }

        body::before {
            opacity: 0.18;
            background-image:
                linear-gradient(45deg, rgba(183, 138, 58, 0.08) 25%, transparent 25%),
                linear-gradient(-45deg, rgba(183, 138, 58, 0.08) 25%, transparent 25%);
            background-size: 42px 42px;
        }

        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 24px;
        }

        .page-frame {
            position: fixed;
            inset: 14px;
            pointer-events: none;
            border: 2px solid rgba(183, 138, 58, 0.55);
            border-radius: 2px;
            z-index: 0;
        }

        .page-frame::before,
        .page-frame::after {
            content: '';
            position: absolute;
            top: -2px;
            width: 78px;
            height: 2px;
            background: linear-gradient(90deg, rgba(183, 138, 58, 0.95), rgba(183, 138, 58, 0.1));
        }

        .page-frame::after {
            right: 0;
            left: auto;
            background: linear-gradient(90deg, rgba(183, 138, 58, 0.1), rgba(183, 138, 58, 0.95));
        }

        .page-frame .frame-bottom-left,
        .page-frame .frame-bottom-right {
            position: absolute;
            bottom: -2px;
            width: 78px;
            height: 2px;
            background: linear-gradient(90deg, rgba(183, 138, 58, 0.95), rgba(183, 138, 58, 0.1));
        }

        .page-frame .frame-bottom-right {
            right: 0;
            left: auto;
            background: linear-gradient(90deg, rgba(183, 138, 58, 0.1), rgba(183, 138, 58, 0.95));
        }

        .login-shell {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 900px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 26px;
            padding: 22px 18px 28px;
        }

        .site-title {
            text-align: center;
            color: var(--accent);
        }

        .site-title h1 {
            font-size: clamp(2.2rem, 4vw, 3.65rem);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 8px;
            color: var(--accent);
        }

        .site-title .eyebrow {
            color: var(--turquoise);
            font-size: 0.92rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .star-divider {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            color: var(--gold);
            margin-bottom: 2px;
        }

        .star-divider::before,
        .star-divider::after {
            content: '';
            width: 92px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(183, 138, 58, 0.8), transparent);
        }

        .card-wrap {
            width: 100%;
            display: flex;
            justify-content: center;
        }

        .login-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 468px;
            background: rgba(255, 252, 244, 0.9);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(183, 138, 58, 0.22);
            border-top: 4px solid rgba(183, 138, 58, 0.78);
            border-radius: 22px;
            padding: 44px 42px 40px;
            box-shadow: 0 24px 60px rgba(86, 68, 20, 0.16);
            animation: slideUp 0.55s cubic-bezier(0.4,0,0.2,1) both;
        }

        .login-card::before {
            content: '';
            position: absolute;
            inset: 10px;
            border: 1px solid rgba(183, 138, 58, 0.18);
            border-radius: 18px;
            pointer-events: none;
        }

        .login-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
            text-align: center;
        }

        .login-logo img {
            display: block;
            width: 96px;
            height: 96px;
            object-fit: contain;
            margin: 0 auto 2px;
        }

        .login-logo-text { line-height: 1.2; }
        .login-logo-name {
            font-size: 1.5rem;
            font-family: var(--font-heading);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--accent);
        }
        .login-logo-sub {
            font-size: 0.78rem;
            color: var(--turquoise);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.18em;
        }

        .login-heading {
            font-size: 1.78rem;
            font-weight: 800;
            margin-bottom: 4px;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: var(--accent);
            text-align: center;
        }

        .login-sub {
            color: var(--gold);
            font-size: 1rem;
            margin-bottom: 26px;
            font-family: 'Marcellus', serif;
            font-style: italic;
        }

        .btn-login {
            width: 100%;
            padding: 15px 18px;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            background: linear-gradient(135deg, #177a79 0%, #134f4e 55%, #0d3e3d 100%);
            color: #fff;
            border: none;
            border-radius: 999px;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 10px 26px rgba(13, 62, 61, 0.28);
            margin-top: 14px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 32px rgba(13, 62, 61, 0.34);
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
            margin-top: 18px;
            font-size: 0.78rem;
            color: var(--text-muted);
            letter-spacing: 0.04em;
        }

        .login-note {
            font-size: 0.84rem;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="page-frame"><span class="frame-bottom-left"></span><span class="frame-bottom-right"></span></div>

<div class="login-page">
    <div class="login-shell">
        <div class="site-title">
            <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="Anjuman logo" style="width:84px;height:84px;object-fit:contain;margin-bottom:10px;" onerror="this.style.display='none'">
            <h1>Anjuman E Ezzy</h1>
            <div class="eyebrow">Hatemi Mohallah, Rajkot</div>
            <div class="eyebrow" style="font-size:0.92rem;margin-top:6px;">Relay Committee &middot; Ashara Mubaraka 1448</div>
        </div>

        <div class="card-wrap">
            <div class="login-card">
                <div class="login-logo">
                    <img src="<?= BASE_URL ?>/assets/images/logo-removebg-preview.png" alt="Anjuman logo" style="width:120px;height:auto;">
                    <div class="login-logo-text">
                        <div class="login-logo-name">Member Login</div>
                        <div class="login-logo-sub">Welcome to our community</div>
                    </div>
                </div>

                <h1 class="login-heading">Enter ITS</h1>

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
                        <label for="identifier" class="form-label-dark">ITS</label>
                        <input
                            type="text"
                            id="identifier"
                            name="identifier"
                            class="form-control form-control-dark"
                            placeholder="Enter your ITS"
                            maxlength="8"
                            pattern="\d{8}"
                            value="<?= e($_POST['identifier'] ?? '') ?>"
                            required
                            autocomplete="username"
                            autofocus
                        >
                        <small class="login-note">This site uses only ITS number.</small>
                    </div>

                    <button type="submit" class="btn-login" id="loginBtn">
                        <span id="loginBtnText">Enter</span>
                    </button>
                </form>

                <div class="login-footer">
                    Private community access &bull; Single session per ITS
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
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
