<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
Auth::startSession();
Auth::requireUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Choose Device — <?= e(APP_NAME) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Marcellus&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100dvh;
            background:
                radial-gradient(circle at 20% 20%, rgba(183,138,58,0.22) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(27,143,151,0.2) 0%, transparent 38%),
                linear-gradient(160deg, #fcf7eb 0%, #f0e8d6 100%);
        }

        .picker-shell {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 40px;
            padding: 32px 20px;
            width: 100%;
            max-width: 600px;
        }

        .picker-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .picker-logo img {
            width: 64px;
            height: 64px;
            object-fit: contain;
        }

        .picker-logo .app-name {
            font-family: var(--font-heading);
            font-size: 1.1rem;
            color: var(--text-secondary);
            letter-spacing: 0.04em;
        }

        .picker-heading {
            text-align: center;
        }

        .picker-heading h1 {
            font-family: var(--font-heading);
            font-size: clamp(1.6rem, 5vw, 2.6rem);
            color: var(--text-primary);
            letter-spacing: -0.02em;
            margin-bottom: 10px;
        }

        .picker-heading p {
            color: var(--text-muted);
            font-size: 0.97rem;
            line-height: 1.6;
        }

        .device-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            width: 100%;
        }

        .device-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            padding: 36px 20px;
            border-radius: 28px;
            border: 2px solid rgba(183,138,58,0.25);
            background: rgba(255,252,246,0.88);
            backdrop-filter: blur(20px);
            box-shadow: 0 16px 48px rgba(9,37,31,0.12);
            text-decoration: none;
            color: inherit;
            cursor: pointer;
            transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
            -webkit-tap-highlight-color: transparent;
        }

        .device-card:hover,
        .device-card:focus {
            transform: translateY(-5px);
            border-color: var(--accent);
            box-shadow: 0 28px 64px rgba(11,109,88,0.2);
            outline: none;
        }

        .device-card:active {
            transform: translateY(-2px);
        }

        .device-icon {
            font-size: 3.8rem;
            line-height: 1;
            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.12));
        }

        .device-name {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: 0.01em;
        }

        .device-hint {
            font-size: 0.78rem;
            color: var(--text-muted);
            text-align: center;
            line-height: 1.5;
        }

        .device-badge {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 999px;
        }

        .badge-mobile {
            background: rgba(27,143,151,0.14);
            color: var(--turquoise);
            border: 1px solid rgba(27,143,151,0.28);
        }

        .badge-tv {
            background: rgba(183,138,58,0.14);
            color: var(--gold-deep);
            border: 1px solid rgba(183,138,58,0.3);
        }

        @media (max-width: 420px) {
            .device-grid {
                grid-template-columns: 1fr;
                max-width: 280px;
            }
            .picker-shell { gap: 28px; }
        }
    </style>
</head>
<body>
<div class="picker-shell">
    <div class="picker-logo">
        <img src="<?= BASE_URL ?>/assets/images/logo-removebg-preview.png" alt="<?= e(APP_NAME) ?>">
        <span class="app-name"><?= e(APP_NAME) ?></span>
    </div>

    <div class="picker-heading">
        <h1>How are you watching?</h1>
        <p>Choose your device for the best viewing experience</p>
    </div>

    <div class="device-grid">
        <a class="device-card" href="<?= BASE_URL ?>/stream.php?view=mobile" draggable="false">
            <div class="device-icon">📱</div>
            <div class="device-name">Mobile</div>
            <div class="device-hint">Phone or tablet<br>Touch optimised</div>
            <span class="device-badge badge-mobile">Portrait / Touch</span>
        </a>

        <a class="device-card" href="<?= BASE_URL ?>/stream.php?view=tv" draggable="false">
            <div class="device-icon">📺</div>
            <div class="device-name">TV / Desktop</div>
            <div class="device-hint">Large screen<br>Full-screen player</div>
            <span class="device-badge badge-tv">Widescreen</span>
        </a>
    </div>
</div>
</body>
</html>
