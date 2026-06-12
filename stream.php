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

// Require a valid view; bounce to picker if missing or invalid
$view = $_GET['view'] ?? '';
if ($view !== 'mobile' && $view !== 'tv') {
    header('Location: ' . BASE_URL . '/watch.php');
    exit;
}

$isTv     = $view === 'tv';
$isMobile = $view === 'mobile';
$watchUrl = BASE_URL . '/watch.php';
$streamUrl = BASE_URL . '/stream.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php if ($isMobile): ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <?php else: ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php endif; ?>
    <title>Live Stream — <?= e(APP_NAME) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <?= csrfMeta() ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Marcellus&family=Noto+Naskh+Arabic:wght@500;700&display=swap" rel="stylesheet">
    <?php if ($isTv): ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">

    <?php if ($isTv): ?>
    <!-- ═══════════════════════════════════════════════
         TV / DESKTOP STYLES
         Player fills the full viewport. Black canvas.
         ═══════════════════════════════════════════════ -->
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            width: 100%; height: 100%;
            background: #000;
            overflow-x: hidden;
        }

        .tv-shell {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: #000;
        }

<<<<<<< HEAD
        /* ── Top bar ── */
        .tv-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            background: rgba(10,10,10,0.95);
            border-bottom: 1px solid rgba(255,255,255,0.07);
            gap: 12px;
            flex-shrink: 0;
            z-index: 10;
        }

        .tv-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #e8dfc8;
            font-family: var(--font-heading);
            font-size: 1rem;
            white-space: nowrap;
        }

        .tv-brand img {
            width: 36px; height: 36px;
            object-fit: contain;
            border-radius: 8px;
        }

        .tv-topbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tv-btn {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.14);
            color: rgba(255,255,255,0.75);
            border-radius: 999px;
            padding: 7px 16px;
            font-size: 0.8rem;
            font-weight: 700;
            font-family: var(--font-body);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s, color 0.15s;
            text-decoration: none;
            white-space: nowrap;
        }

        .tv-btn:hover {
            background: rgba(255,255,255,0.16);
            color: #fff;
        }

        .tv-btn.accent {
            background: rgba(11,109,88,0.75);
            border-color: rgba(11,109,88,0.5);
            color: #fff;
        }

        .tv-btn.accent:hover { background: rgba(11,109,88,0.9); }

        /* ── Player area ── */
        .tv-player-wrap {
            position: relative;
            width: 100%;
            flex: 1;
            background: #000;
            aspect-ratio: 16 / 9;
            max-height: calc(100vh - 56px);
        }

        .tv-player-wrap iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
            background: #000;
=======
        .player-frame-shell:fullscreen,
        .player-frame-shell:-webkit-full-screen {
            width: 100vw;
            height: 100vh;
            max-width: none;
            aspect-ratio: auto;
            border-radius: 0;
        }

        .player-frame-shell:fullscreen iframe,
        .player-frame-shell:-webkit-full-screen iframe {
            width: 100%;
            height: 100%;
        }

        .player-frame-shell:fullscreen .stream-top-click-shield,
        .player-frame-shell:-webkit-full-screen .stream-top-click-shield {
            inset: 0;
        }

        .player-frame-shell:fullscreen .stream-live-overlay,
        .player-frame-shell:-webkit-full-screen .stream-live-overlay {
            left: 12px;
            right: 12px;
            bottom: max(12px, env(safe-area-inset-bottom));
        }

        .stream-live-overlay {
            position: absolute;
            left: 16px;
            right: 16px;
            bottom: 16px;
            z-index: 3;
            display: none;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
>>>>>>> ade887517053e8cccb927811754a0a991a87e212
            pointer-events: none;
        }

        .tv-shield {
            position: absolute;
            inset: 0;
            z-index: 2;
            cursor: pointer;
            background: transparent;
        }

        .tv-badge-wrap {
            position: absolute;
            top: 14px;
            left: 16px;
            z-index: 3;
            pointer-events: none;
        }

        .tv-fs-hint {
            position: absolute;
            bottom: 14px;
            right: 16px;
            z-index: 3;
            pointer-events: none;
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(0,0,0,0.55);
            border: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.7);
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 0.75rem;
            font-weight: 700;
            font-family: var(--font-body);
            backdrop-filter: blur(8px);
        }

        .tv-offline {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 18px;
            background: linear-gradient(145deg, #050e0c 0%, #0d2921 100%);
            color: rgba(255,255,255,0.6);
            text-align: center;
            padding: 24px;
        }

        .tv-offline-icon { font-size: 4rem; }

        .tv-offline h2 {
            font-family: var(--font-heading);
            font-size: clamp(1.2rem, 2.5vw, 1.8rem);
            color: rgba(255,255,255,0.8);
        }

        .tv-offline p {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.45);
        }

        .tv-notice {
            flex-shrink: 0;
            background: rgba(12,12,12,0.97);
            border-top: 1px solid rgba(255,255,255,0.06);
            padding: 14px 24px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .tv-notice-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--gold);
            white-space: nowrap;
            padding-top: 2px;
        }

        .tv-notice-text {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.55);
            line-height: 1.6;
        }

        .tv-notice-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: rgba(255,255,255,0.8);
            margin-bottom: 2px;
        }

        .fs-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: #000;
        }

        .fs-overlay.active { display: block; }

        .fs-overlay iframe {
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
            background: #000;
            pointer-events: none;
        }

        .fs-exit-btn {
            position: fixed;
            top: max(12px, env(safe-area-inset-top));
            right: max(14px, env(safe-area-inset-right));
            z-index: 100000;
            background: rgba(0,0,0,0.6);
            border: 1px solid rgba(255,255,255,0.22);
            color: #fff;
            border-radius: 999px;
            padding: 8px 16px;
            font-size: 0.8rem;
            font-weight: 700;
            font-family: var(--font-body);
            cursor: pointer;
            backdrop-filter: blur(10px);
            opacity: 0;
            transition: opacity 0.25s;
            display: none;
        }

        .fs-overlay.active ~ .fs-exit-btn,
        .fs-exit-btn.visible { display: block; opacity: 1; }
    </style>

    <?php else: ?>
    <!-- ═══════════════════════════════════════════════
         MOBILE STYLES
         Compact single-column, touch-first layout.
         ═══════════════════════════════════════════════ -->
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body { overflow-x: hidden; }

        .mob-shell {
            padding: env(safe-area-inset-top, 0px) env(safe-area-inset-right, 0px) env(safe-area-inset-bottom, 16px) env(safe-area-inset-left, 0px);
            padding-top: max(12px, env(safe-area-inset-top));
            padding-left: 12px;
            padding-right: 12px;
            padding-bottom: max(20px, env(safe-area-inset-bottom));
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        /* Top bar */
        .mob-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .mob-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-primary);
            font-weight: 700;
            font-size: 0.9rem;
        }

        .mob-brand img {
            width: 32px; height: 32px;
            object-fit: contain;
            border-radius: 8px;
        }

        .mob-switch-btn {
            background: rgba(255,252,246,0.8);
            border: 1px solid rgba(183,138,58,0.28);
            color: var(--text-muted);
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            font-family: var(--font-body);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .mob-switch-btn:hover { color: var(--accent); }

        /* Player card */
        .mob-player-card {
            background: rgba(255,252,246,0.82);
            border: 1px solid rgba(183,138,58,0.24);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(9,37,31,0.13);
        }

        .mob-player-shell {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 9;
            background: #000;
        }

        .mob-player-shell iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
            background: #000;
            pointer-events: none;
        }

        /* Tap shield */
        .mob-shield {
            position: absolute;
            inset: 0;
            z-index: 2;
            background: transparent;
            cursor: pointer;
        }

        .mob-badge-wrap {
            position: absolute;
            top: 10px;
            left: 12px;
            z-index: 3;
            pointer-events: none;
        }

        .mob-fs-hint {
            position: absolute;
            bottom: 10px;
            right: 10px;
            z-index: 3;
            pointer-events: none;
            background: rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.18);
            color: rgba(255,255,255,0.7);
            border-radius: 999px;
            padding: 5px 12px;
            font-size: 0.72rem;
            font-weight: 700;
            font-family: var(--font-body);
            backdrop-filter: blur(8px);
        }

        /* Player action bar */
        .mob-player-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 10px 12px;
            gap: 8px;
        }

        .mob-action-btn {
            background: rgba(255,252,246,0.85);
            border: 1px solid rgba(183,138,58,0.3);
            color: var(--text-primary);
            border-radius: 999px;
            padding: 7px 16px;
            font-size: 0.78rem;
            font-weight: 700;
            font-family: var(--font-body);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Offline state */
        .mob-offline {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            padding: 40px 20px;
            text-align: center;
            color: var(--text-muted);
        }

        .mob-offline-icon {
            font-size: 3rem;
            width: 90px; height: 90px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, rgba(183,138,58,0.18), rgba(11,109,88,0.14));
        }

        .mob-offline h2 {
            font-family: var(--font-heading);
            font-size: 1.3rem;
            color: var(--text-primary);
        }

        .mob-offline p {
            font-size: 0.88rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Notice card */
        .mob-notice-card {
            background: rgba(255,252,246,0.82);
            border: 1px solid rgba(183,138,58,0.22);
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 6px 20px rgba(9,37,31,0.08);
        }

        .mob-notice-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .mob-notice-title {
            font-family: var(--font-heading);
            font-size: 1.05rem;
            color: var(--accent);
            margin-bottom: 6px;
        }

        .mob-notice-body {
            font-size: 0.88rem;
            color: var(--text-primary);
            line-height: 1.7;
            white-space: pre-line;
        }

        .mob-notice-empty {
            font-size: 0.88rem;
            color: var(--text-muted);
        }

        /* ── True fullscreen overlay ── */
        .fs-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: #000;
        }

        .fs-overlay.active { display: block; }

        .fs-overlay iframe {
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
            background: #000;
            pointer-events: none;
        }

        .fs-exit-btn {
            position: fixed;
            top: max(12px, env(safe-area-inset-top));
            right: max(14px, env(safe-area-inset-right));
            z-index: 100000;
            background: rgba(0,0,0,0.6);
            border: 1px solid rgba(255,255,255,0.22);
            color: #fff;
            border-radius: 999px;
            padding: 8px 16px;
            font-size: 0.8rem;
            font-weight: 700;
            font-family: var(--font-body);
            cursor: pointer;
            backdrop-filter: blur(10px);
            display: none;
        }

        .fs-exit-btn.visible { display: block; }
    </style>
    <?php endif; ?>
</head>
<<<<<<< HEAD
<body
    data-stream-status-url="<?= BASE_URL ?>/ajax/stream_status.php"
    data-session-status-url="<?= BASE_URL ?>/ajax/session_status.php"
    data-login-url="<?= BASE_URL ?>/login.php"
    data-prevent-back-navigation="true">
=======
<body data-stream-status-url="<?= BASE_URL ?>/ajax/stream_status.php" data-session-status-url="<?= BASE_URL ?>/ajax/session_status.php" data-login-url="<?= BASE_URL ?>/login.php" data-prevent-back-navigation="true">
<div class="watch-shell">
    <div class="watch-hero">
        <section class="player-card slide-up">
            <div class="player-frame-shell yt-embed-locked" id="streamPlayerShell" style="<?= $hasStream && ($stream['status'] ?? '') === 'live' ? '' : 'display:none;' ?>">
                <iframe
                    id="streamPlayerFrame"
                    src="<?= $hasStream && ($stream['status'] ?? '') === 'live' ? e($stream['embed_url']) : 'about:blank' ?>"
                    allow="autoplay; encrypted-media"
                    title="Live stream player"></iframe>
                <div class="yt-ui-shield yt-ui-shield-top" aria-hidden="true"></div>
                <div class="yt-ui-shield yt-ui-shield-bottom" aria-hidden="true"></div>
                <div class="yt-ui-shield yt-ui-shield-logo" aria-hidden="true"></div>
                <div class="stream-top-click-shield" aria-hidden="true"></div>
                <div class="stream-live-overlay">
                    <span id="stream-status-badge" class="<?= $hasStream && ($stream['status'] ?? '') === 'live' ? 'badge-live' : 'badge-offline' ?>">
                        <?= $hasStream && ($stream['status'] ?? '') === 'live' ? '<span class="live-dot"></span> LIVE' : '⚫ OFFLINE' ?>
                    </span>
                    <button type="button" class="player-action-btn" id="streamFullscreenBtn" aria-label="Enter fullscreen">
                        ⛶ Fullscreen
                    </button>
                </div>
            </div>
            <div class="offline-state" id="streamOfflineState" style="<?= $hasStream && ($stream['status'] ?? '') === 'live' ? 'display:none;' : '' ?>">
                <div class="offline-symbol">📡</div>
                <span class="badge-offline">⚫ OFFLINE</span>
                <h1 class="watch-title">Live stream is not available right now</h1>
                <p class="watch-subtitle">Please wait for the next live broadcast.</p>
            </div>
        </section>
>>>>>>> ade887517053e8cccb927811754a0a991a87e212

<?php if ($isTv): ?>
<!-- ═══════════════════════════════════════════
     TV / DESKTOP INTERFACE
     ═══════════════════════════════════════════ -->
<div class="tv-shell">
    <div class="tv-topbar">
        <div class="tv-brand">
            <img src="<?= BASE_URL ?>/assets/images/logo-removebg-preview.png" alt="<?= e(APP_NAME) ?>">
            <?= e(APP_NAME) ?>
        </div>
        <div class="tv-topbar-actions">
            <span id="stream-status-badge" class="<?= $isLive ? 'badge-live' : 'badge-offline' ?>">
                <?= $isLive ? '<span class="live-dot"></span> LIVE' : '⚫ OFFLINE' ?>
            </span>
            <button type="button" class="tv-btn accent" id="streamFullscreenBtn">⛶ Fullscreen</button>
            <a href="<?= $watchUrl ?>" class="tv-btn">⇄ Switch device</a>
        </div>
    </div>

    <div class="tv-player-wrap" id="streamPlayerShell" style="<?= $isLive ? '' : 'display:none;' ?>">
        <iframe
            id="streamPlayerFrame"
            src="<?= $isLive ? e($embedUrl) : 'about:blank' ?>"
            allow="autoplay; encrypted-media"
            title="Live stream player"></iframe>
        <div class="tv-shield" id="streamClickShield" title="Tap to fullscreen"></div>
        <div class="tv-badge-wrap">
            <?php if ($isLive): ?>
            <span class="badge-live"><span class="live-dot"></span> LIVE</span>
            <?php endif; ?>
        </div>
        <div class="tv-fs-hint" id="tvFsHint">⛶ Tap to fullscreen</div>
    </div>

    <div class="tv-offline" id="streamOfflineState" style="<?= $isLive ? 'display:none;' : '' ?>">
        <div class="tv-offline-icon">📡</div>
        <span class="badge-offline">⚫ OFFLINE</span>
        <h2>Stream is not live right now</h2>
        <p>Please wait for the next broadcast.</p>
    </div>

    <?php if ($dailyNotice || true): ?>
    <div class="tv-notice">
        <span class="tv-notice-label">📢 Notice</span>
        <div>
            <?php if ($dailyNotice): ?>
                <div class="tv-notice-title"><?= e($dailyNotice['title'] ?? 'Announcement') ?></div>
                <div class="tv-notice-text"><?= nl2br(e($dailyNotice['message'] ?? '')) ?></div>
            <?php else: ?>
                <div class="tv-notice-text" style="color:rgba(255,255,255,0.3);">No announcement posted yet.</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- ═══════════════════════════════════════════
     MOBILE INTERFACE
     ═══════════════════════════════════════════ -->
<div class="mob-shell">
    <div class="mob-topbar">
        <div class="mob-brand">
            <img src="<?= BASE_URL ?>/assets/images/logo-removebg-preview.png" alt="<?= e(APP_NAME) ?>">
            <?= e(APP_NAME) ?>
        </div>
        <a href="<?= $watchUrl ?>" class="mob-switch-btn">⇄ Switch device</a>
    </div>

    <div class="mob-player-card">
        <div class="mob-player-shell" id="streamPlayerShell" style="<?= $isLive ? '' : 'display:none;' ?>">
            <iframe
                id="streamPlayerFrame"
                src="<?= $isLive ? e($embedUrl) : 'about:blank' ?>"
                allow="autoplay; encrypted-media"
                title="Live stream player"></iframe>
            <div class="mob-shield" id="streamClickShield"></div>
            <div class="mob-badge-wrap">
                <span id="stream-status-badge" class="<?= $isLive ? 'badge-live' : 'badge-offline' ?>">
                    <?= $isLive ? '<span class="live-dot"></span> LIVE' : '⚫ OFFLINE' ?>
                </span>
            </div>
            <div class="mob-fs-hint">⛶ Tap for fullscreen</div>
        </div>

        <div class="mob-offline" id="streamOfflineState" style="<?= $isLive ? 'display:none;' : '' ?>">
            <div class="mob-offline-icon">📡</div>
            <span class="badge-offline">⚫ OFFLINE</span>
            <h2>Stream is not live right now</h2>
            <p>Please wait for the next broadcast.</p>
        </div>

        <div class="mob-player-actions">
            <button type="button" class="mob-action-btn" id="streamFullscreenBtn">⛶ Fullscreen</button>
        </div>
    </div>

    <div class="mob-notice-card">
        <div class="mob-notice-label">📢 Daily Announcement</div>
        <?php if ($dailyNotice): ?>
            <div class="mob-notice-title"><?= e($dailyNotice['title'] ?? 'Announcement') ?></div>
            <div class="mob-notice-body"><?= nl2br(e($dailyNotice['message'] ?? '')) ?></div>
            <div style="font-size:0.72rem;color:var(--text-muted);margin-top:10px;">
                Updated <?= e(date('d M Y, h:i A', strtotime($dailyNotice['updated_at'] ?? 'now'))) ?>
            </div>
        <?php else: ?>
            <div class="mob-notice-empty">No announcement posted yet.</div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- True fullscreen overlay — sits at root, no parent clips it -->
<div class="fs-overlay" id="fsOverlay">
    <iframe id="fsFrame" src="about:blank" allow="autoplay; encrypted-media" title="Fullscreen stream"></iframe>
</div>
<button class="fs-exit-btn" id="fsExitBtn" type="button">✕ Exit Fullscreen</button>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
(function () {
    var embedSrc = <?= json_encode($isLive ? $embedUrl : '') ?>;
    var isLive   = <?= json_encode($isLive) ?>;
    var isTv     = <?= json_encode($isTv) ?>;

    var fsOverlay  = document.getElementById('fsOverlay');
    var fsFrame    = document.getElementById('fsFrame');
    var fsExitBtn  = document.getElementById('fsExitBtn');
    var fsOpen     = false;

    /* ── Open fullscreen ── */
    function openFs() {
        if (!isLive || !embedSrc) return;
        fsFrame.src = embedSrc;
        fsOverlay.classList.add('active');
        fsExitBtn.classList.add('visible');
        fsOpen = true;

        var el  = fsOverlay;
        var req = el.requestFullscreen || el.webkitRequestFullscreen || el.mozRequestFullScreen || el.msRequestFullscreen;
        if (req) {
            req.call(el).catch(function () {});
        }
        document.body.style.overflow = 'hidden';
    }

    /* ── Close fullscreen ── */
    function closeFs() {
        if (!fsOpen) return;
        fsOpen = false;
        fsOverlay.classList.remove('active');
        fsExitBtn.classList.remove('visible');
        // Small delay so the overlay is hidden before we blank the iframe
        setTimeout(function () { fsFrame.src = 'about:blank'; }, 200);
        document.body.style.overflow = '';
        var exit = document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen || document.msExitFullscreen;
        if (exit && (document.fullscreenElement || document.webkitFullscreenElement)) {
            exit.call(document);
        }
    }

    /* ── Wire buttons ── */
    var fsBtn = document.getElementById('streamFullscreenBtn');
    if (fsBtn) fsBtn.addEventListener('click', openFs);

    var shield = document.getElementById('streamClickShield');
    if (shield) shield.addEventListener('click', openFs);

    fsExitBtn.addEventListener('click', closeFs);

    /* Auto-show exit button on mouse move / touch inside fullscreen */
    fsOverlay.addEventListener('mousemove', function () {
        fsExitBtn.classList.add('visible');
    });

    /* ── Browser fullscreen exit (Escape key etc.) ── */
    function onFsChange() {
        var active = document.fullscreenElement || document.webkitFullscreenElement;
        if (!active && fsOpen) closeFs();
    }
    document.addEventListener('fullscreenchange', onFsChange);
    document.addEventListener('webkitfullscreenchange', onFsChange);

    /* TV mode: auto-open fullscreen when stream is live on page load */
    if (isTv && isLive) {
        // Wait for a user gesture — browsers block autoplay fullscreen.
        // Show a prominent prompt instead.
        var hint = document.getElementById('tvFsHint');
        if (hint) {
            hint.style.pointerEvents = 'auto';
            hint.style.cursor = 'pointer';
            hint.addEventListener('click', openFs);
        }
    }

    /* ── Keep live state in sync with polling ── */
    document.addEventListener('streamStatusChanged', function (e) {
        isLive   = !!(e.detail && e.detail.live);
        embedSrc = (e.detail && e.detail.embedUrl) || embedSrc;
        // If fullscreen is open and stream dropped, close it
        if (!isLive && fsOpen) closeFs();
    });
})();
</script>
</body>
</html>
