<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/stream.php';

Auth::startSession();
Auth::requireUser();

$user   = Auth::user();
$db     = Database::getInstance();
$stream = getCurrentStream($db) ?? getLatestStream($db);
$hasStream  = (bool) $stream;
$isLive     = $hasStream && ($stream['status'] ?? '') === 'live';
$embedUrl   = $isLive ? ($stream['embed_url'] ?? '') : '';
$dailyNotice = getActiveDailyNotice($db);

if ($isLive) {
    recordStreamAttendance($db, $stream, $user, 'stream');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Live Stream — <?= e(APP_NAME) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <?= csrfMeta() ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Marcellus&family=Noto+Naskh+Arabic:wght@500;700&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            overflow-x: hidden;
            padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left);
        }

        .stream-shell {
            max-width: 100%;
            margin: 0;
            padding: 0;
        }

        .stream-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 18px;
            background: rgba(255, 251, 242, 0.68);
            border: 1px solid rgba(183, 138, 58, 0.25);
            border-radius: 20px;
            margin-bottom: 16px;
        }

        .stream-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
            font-weight: 700;
            font-size: 0.95rem;
        }

        .stream-brand img {
            width: 36px; height: 36px;
            object-fit: contain;
            border-radius: 10px;
        }

        .stream-content {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }

        /* Responsive 16:9 video wrapper */
        .video-wrapper {
            position: relative;
            width: 100%;
            padding-top: 56.25%; /* 16:9 aspect ratio */
            background: #000;
            border-radius: 0;
            overflow: hidden;
        }

        .video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
            display: block;
            pointer-events: none;
            /* Ignore admin-provided width/height - always use container size */
        }

        /* Fullscreen mode - FIXED positioning fills entire viewport */
        .video-wrapper:fullscreen,
        .video-wrapper:-webkit-full-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            max-width: none;
            max-height: none;
            padding: 0;
            margin: 0;
            border-radius: 0;
            background: #000;
            z-index: 9999;
        }

        .video-wrapper:fullscreen iframe,
        .video-wrapper:-webkit-full-screen iframe {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            border: none;
            margin: 0;
            padding: 0;
            z-index: 9999;
        }

        /* Video overlay controls */
        .video-overlay {
            position: absolute;
            inset: 0;
            z-index: 10;
            background: transparent;
            cursor: pointer;
            pointer-events: auto;
        }

        .video-controls {
            position: absolute;
            bottom: 16px;
            right: 16px;
            z-index: 11;
            display: flex;
            gap: 10px;
            pointer-events: auto;
        }

        .video-btn {
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            border-radius: 999px;
            padding: 8px 16px;
            font-size: 0.82rem;
            font-weight: 700;
            font-family: var(--font-body);
            cursor: pointer;
            backdrop-filter: blur(10px);
            transition: background 0.2s;
        }

        .video-btn:hover { background: rgba(0, 0, 0, 0.85); }

        .video-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            z-index: 11;
            pointer-events: none;
        }

        /* Offline state */
        .offline-state {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            background: linear-gradient(145deg, #0a1a15 0%, #0d2921 100%);
            color: rgba(255, 255, 255, 0.6);
            text-align: center;
            padding: 32px;
        }

        .offline-icon { font-size: 4rem; }

        .offline-title {
            font-family: var(--font-heading);
            font-size: clamp(1.2rem, 3vw, 1.8rem);
            color: rgba(255, 255, 255, 0.85);
        }

        .offline-text {
            font-size: 0.92rem;
            color: rgba(255, 255, 255, 0.5);
        }

        /* Notice card */
        .notice-card {
            background: rgba(255, 252, 246, 0.82);
            border: 1px solid rgba(183, 138, 58, 0.24);
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 6px 24px rgba(9, 37, 31, 0.1);
        }

        .notice-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .notice-title {
            font-family: var(--font-heading);
            font-size: 1.1rem;
            color: var(--accent);
            margin-bottom: 8px;
        }

        .notice-body {
            font-size: 0.9rem;
            color: var(--text-primary);
            line-height: 1.7;
            white-space: pre-line;
        }

        .notice-empty {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        @media (min-width: 768px) {
            .stream-content {
                grid-template-columns: 1.8fr 0.8fr;
                gap: 20px;
            }
        }

        @media (max-width: 576px) {
            .stream-shell { padding: 0; }
            .stream-topbar {
                padding: 10px 14px;
                border-radius: 16px;
            }
            .stream-brand { font-size: 0.85rem; }
            .stream-brand img { width: 32px; height: 32px; }
            .video-wrapper { border-radius: 16px; }
            .video-controls {
                bottom: 12px;
                right: 12px;
            }
            .video-btn {
                padding: 6px 12px;
                font-size: 0.75rem;
            }
            .notice-card { border-radius: 16px; padding: 16px; }
        }
    </style>
</head>
<body
    data-stream-status-url="<?= BASE_URL ?>/ajax/stream_status.php"
    data-session-status-url="<?= BASE_URL ?>/ajax/session_status.php"
    data-login-url="<?= BASE_URL ?>/login.php"
    data-prevent-back-navigation="true">

<div class="stream-shell">
    <div class="stream-topbar">
        <div class="stream-brand">
            <img src="<?= BASE_URL ?>/assets/images/logo-removebg-preview.png" alt="<?= e(APP_NAME) ?>">
            <?= e(APP_NAME) ?>
        </div>
        <span id="stream-status-badge" class="<?= $isLive ? 'badge-live' : 'badge-offline' ?>">
            <?= $isLive ? '<span class="live-dot"></span> LIVE' : '⚫ OFFLINE' ?>
        </span>
    </div>

    <div class="stream-content">
        <div>
            <div class="video-wrapper" id="videoWrapper">
                <?php if ($isLive): ?>
                    <iframe
                        id="streamPlayerFrame"
                        src="<?= e($embedUrl) ?>"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allowfullscreen></iframe>
                    <div class="video-overlay" id="videoOverlay"></div>
                    <div class="video-badge">
                        <span class="badge-live"><span class="live-dot"></span> LIVE</span>
                    </div>
                    <div class="video-controls">
                        <button type="button" class="video-btn" id="fullscreenBtn">⛶ Fullscreen</button>
                    </div>
                <?php else: ?>
                    <div class="offline-state">
                        <div class="offline-icon">📡</div>
                        <span class="badge-offline">⚫ OFFLINE</span>
                        <h2 class="offline-title">Stream is not live right now</h2>
                        <p class="offline-text">Please wait for the next broadcast.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <aside>
            <div class="notice-card">
                <div class="notice-label">📢 Daily Announcement</div>
                <?php if ($dailyNotice): ?>
                    <div class="notice-title"><?= e($dailyNotice['title'] ?? 'Announcement') ?></div>
                    <div class="notice-body"><?= nl2br(e($dailyNotice['message'] ?? '')) ?></div>
                    <div style="font-size:0.72rem;color:var(--text-muted);margin-top:12px;">
                        Updated <?= e(date('d M Y, h:i A', strtotime($dailyNotice['updated_at'] ?? 'now'))) ?>
                    </div>
                <?php else: ?>
                    <div class="notice-empty">No announcement posted yet.</div>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
(function () {
    var wrapper = document.getElementById('videoWrapper');
    var fsBtn = document.getElementById('fullscreenBtn');
    var overlay = document.getElementById('videoOverlay');

    function toggleFullscreen() {
        if (!wrapper) return;
        
        if (document.fullscreenElement || document.webkitFullscreenElement) {
            // Exit fullscreen
            var exit = document.exitFullscreen || document.webkitExitFullscreen;
            if (exit) exit.call(document);
        } else {
            // Enter fullscreen
            var req = wrapper.requestFullscreen || wrapper.webkitRequestFullscreen;
            if (req) req.call(wrapper);
        }
    }

    if (fsBtn) fsBtn.addEventListener('click', toggleFullscreen);
    if (overlay) overlay.addEventListener('click', toggleFullscreen);

    // Update button text on fullscreen change
    document.addEventListener('fullscreenchange', updateFsBtn);
    document.addEventListener('webkitfullscreenchange', updateFsBtn);

    function updateFsBtn() {
        if (!fsBtn) return;
        var active = document.fullscreenElement || document.webkitFullscreenElement;
        fsBtn.textContent = active ? '⤫ Exit' : '⛶ Fullscreen';
    }
})();
</script>
</body>
</html>
