<?php
/**
 * Dedicated live player page.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/stream.php';

Auth::startSession();
Auth::requireUser();

$user = Auth::user();
$db = Database::getInstance();
$stream = getCurrentStream($db) ?? getLatestStream($db);
$hasStream = (bool) $stream;
$dailyNotice = getActiveDailyNotice($db);

if ($hasStream && ($stream['status'] ?? '') === 'live') {
    recordStreamAttendance($db, $stream, $user, 'stream');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Stream — <?= e(APP_NAME) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <?= csrfMeta() ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Marcellus&family=Noto+Naskh+Arabic:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body { background: transparent; }

        .watch-shell {
            max-width: 1480px;
            margin: 0 auto;
            padding: 16px 14px 28px;
        }

        .watch-hero {
            display: grid;
            grid-template-columns: 1.9fr 0.8fr;
            gap: 20px;
        }

        .player-card,
        .side-card {
            background: rgba(255, 252, 246, 0.74);
            backdrop-filter: blur(22px);
            border: 1px solid rgba(183, 138, 58, 0.24);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 26px 80px rgba(9, 37, 31, 0.16);
        }

        .player-frame-shell {
            position: relative;
            aspect-ratio: 16 / 9;
            width: 100%;
            background: #000;
        }

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
            pointer-events: none;
        }

        .player-action-btn {
            pointer-events: auto;
            border: 1px solid rgba(255, 255, 255, 0.28);
            background: rgba(13, 16, 19, 0.58);
            color: #fff;
            border-radius: 999px;
            padding: 10px 14px;
            font-size: 0.84rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            backdrop-filter: blur(14px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.22);
        }

        .player-action-btn:hover {
            background: rgba(13, 16, 19, 0.72);
        }

        .player-action-btn:focus {
            outline: 2px solid rgba(255, 255, 255, 0.75);
            outline-offset: 2px;
        }

        .offline-state {
            min-height: 520px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            padding: 24px;
            text-align: center;
            color: var(--text-muted);
        }

        .offline-symbol {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 2.6rem;
            background: linear-gradient(135deg, rgba(183, 138, 58, 0.2), rgba(11, 109, 88, 0.16));
        }

        .watch-copy {
            padding: 24px;
        }

        .watch-title {
            font-size: clamp(1.8rem, 2.4vw, 3rem);
            font-family: var(--font-heading);
            letter-spacing: -0.04em;
            margin-bottom: 12px;
        }

        .watch-subtitle {
            color: var(--text-muted);
            line-height: 1.7;
            margin-bottom: 18px;
        }

        .watch-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .info-stack {
            display: grid;
            gap: 16px;
            padding: 24px;
        }

        .info-card {
            background: rgba(255, 252, 246, 0.68);
            border: 1px solid rgba(183, 138, 58, 0.2);
            border-radius: 18px;
            padding: 18px;
        }

        .mini-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            margin-bottom: 6px;
            font-weight: 700;
        }

        .mini-value {
            font-size: 1rem;
            font-weight: 800;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            padding: 14px 18px;
            background: rgba(255, 251, 242, 0.64);
            border-radius: 22px;
            border: 1px solid rgba(183, 138, 58, 0.25);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--accent), var(--turquoise));
        }

        .notice-card {
            background: rgba(255, 252, 246, 0.82);
            border: 1px solid rgba(183, 138, 58, 0.24);
            border-radius: 22px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
        }

        .notice-title {
            font-family: var(--font-heading);
            font-size: 1.15rem;
            margin-bottom: 8px;
            color: var(--accent);
        }

        .notice-meta {
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .notice-body {
            color: var(--text-primary);
            line-height: 1.7;
            white-space: pre-line;
        }

        .notice-empty {
            color: var(--text-muted);
            font-size: 0.92rem;
            line-height: 1.6;
        }

        @media (max-width: 992px) {
            .watch-hero {
                grid-template-columns: 1fr;
            }

            .player-card {
                border-radius: 22px;
            }

            .player-frame-shell {
                aspect-ratio: 16 / 9;
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .watch-shell {
                padding: 10px 10px 20px;
            }

            .player-card,
            .side-card {
                border-radius: 18px;
            }

            .stream-live-overlay {
                left: 10px;
                right: 10px;
                bottom: 10px;
            }
        }
    </style>
</head>
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

        <aside class="side-card slide-up">
            <div class="notice-card">
                <div class="notice-meta">Daily Announcement</div>
                <?php if ($dailyNotice): ?>
                    <div class="notice-title"><?= e($dailyNotice['title'] ?? 'Announcement') ?></div>
                    <div class="notice-body"><?= nl2br(e($dailyNotice['message'] ?? '')) ?></div>
                    <div class="text-muted mt-3" style="font-size:0.75rem;">Updated <?= e(date('d M Y, h:i A', strtotime($dailyNotice['updated_at'] ?? 'now'))) ?></div>
                <?php else: ?>
                    <div class="notice-empty">No daily announcement has been posted yet.</div>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
