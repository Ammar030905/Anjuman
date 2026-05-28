<?php
/**
 * User Dashboard - embedded live stream experience.
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
$attendanceCount = ($hasStream && !empty($stream['id'])) ? getStreamAttendanceCount($db, (int) $stream['id']) : 0;
$dailyNotice = getActiveDailyNotice($db);

if ($hasStream && ($stream['status'] ?? '') === 'live') {
    recordStreamAttendance($db, $stream, $user, 'dashboard');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — <?= e(APP_NAME) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <?= csrfMeta() ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Marcellus&family=Noto+Naskh+Arabic:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>

        .hero-shell {
            max-width: 1320px;
            margin: 0 auto;
            padding: 28px 20px 40px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 20px;
            margin-bottom: 28px;
            background: rgba(255, 251, 242, 0.64);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(183, 138, 58, 0.25);
            border-radius: 22px;
        }

        .brand-block {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-mark {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            overflow: hidden;
            background: transparent;
            flex-shrink: 0;
        }

        .brand-mark img {
            width: 56px;
            height: 56px;
            object-fit: contain;
            display: block;
        }

        .brand-title {
            margin: 0;
            font-size: 1.2rem;
            font-family: var(--font-heading);
            letter-spacing: 0.03em;
        }

        .brand-subtitle {
            color: var(--text-muted);
            font-size: 0.82rem;
        }

        .notice-panel {
            background: rgba(255, 252, 246, 0.82);
            border: 1px solid rgba(183, 138, 58, 0.24);
            border-radius: 22px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 22px;
        }

        .notice-panel .notice-title {
            font-family: var(--font-heading);
            font-size: 1.1rem;
            color: var(--accent);
            margin-bottom: 8px;
        }

        .notice-panel .notice-meta {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .notice-panel .notice-body {
            white-space: pre-line;
            color: var(--text-primary);
            line-height: 1.7;
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 14px 8px 8px;
            border-radius: 999px;
            background: rgba(255, 253, 246, 0.65);
            border: 1px solid rgba(183, 138, 58, 0.24);
        }

        .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 800;
            background: linear-gradient(135deg, var(--gold), var(--accent));
            color: #fff;
        }

        .logout-btn {
            border-radius: 999px;
            padding: 10px 16px;
            background: rgba(183, 138, 58, 0.12);
            color: #876021;
            border: 1px solid rgba(183, 138, 58, 0.3);
            text-decoration: none;
            font-weight: 700;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.6fr 0.9fr;
            gap: 22px;
        }

        .panel {
            background: rgba(255, 252, 246, 0.72);
            backdrop-filter: blur(22px);
            border: 1px solid rgba(183, 138, 58, 0.24);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(9, 37, 31, 0.16);
        }

        .player-frame-shell {
            position: relative;
            aspect-ratio: 16 / 9;
            background: #000;
        }

        .player-frame-shell iframe {
            width: 100%;
            height: 100%;
            border: 0;
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

        .stream-chrome-mask {
            position: absolute;
            inset: 0;
            pointer-events: none;
            display: none;
        }

        .stream-chrome-mask::before,
        .stream-chrome-mask::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            background: #000;
        }

        .stream-chrome-mask::before {
            top: 0;
            height: 74px;
        }

        .stream-chrome-mask::after {
            bottom: 0;
            height: 100px;
        }

        .offline-state {
            min-height: 420px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 28px;
            color: var(--text-muted);
        }

        .offline-icon {
            width: 92px;
            height: 92px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 2.4rem;
            background: linear-gradient(135deg, rgba(183, 138, 58, 0.2), rgba(11, 109, 88, 0.16));
            margin-bottom: 18px;
        }

        .stream-meta {
            padding: 24px;
        }

        .stream-title {
            font-size: clamp(1.6rem, 2vw, 2.4rem);
            font-family: var(--font-heading);
            letter-spacing: -0.03em;
            margin: 0 0 12px;
        }

        .stream-copy {
            color: var(--text-muted);
            margin-bottom: 16px;
            line-height: 1.6;
        }

        .meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 18px;
        }

        .mini-card {
            background: rgba(255, 252, 246, 0.68);
            border: 1px solid rgba(183, 138, 58, 0.2);
            border-radius: 18px;
            padding: 18px;
            margin-bottom: 18px;
        }

        .mini-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            margin-bottom: 6px;
            font-weight: 700;
        }

        .mini-value {
            font-size: 1rem;
            font-weight: 800;
        }

        .feature-list {
            display: grid;
            gap: 12px;
        }

        .feature-item {
            display: flex;
            gap: 12px;
            padding: 14px;
            border-radius: 18px;
            background: rgba(255, 252, 246, 0.68);
            border: 1px solid rgba(183, 138, 58, 0.2);
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: rgba(11, 109, 88, 0.14);
            flex-shrink: 0;
        }

        .feature-text strong {
            display: block;
            margin-bottom: 4px;
        }

        @media (max-width: 992px) {
            .hero-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .hero-shell {
                padding: 14px 12px 28px;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body data-stream-status-url="<?= BASE_URL ?>/ajax/stream_status.php" data-prevent-back-navigation="true">
<div class="hero-shell">
    <header class="topbar slide-up">
        <div class="brand-block">
            <div class="brand-mark">
                <img src="<?= BASE_URL ?>/assets/images/logo-removebg-preview.png" alt="Anjuman logo">
            </div>
            <div>
                <h1 class="brand-title">Anjuman E Ezzy</h1>
                <div class="brand-subtitle">Hatemi Mohallah, Rajkot</div>
                <div class="brand-sub small">Relay Committee &bull; Ashara Mubaraka 1448</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="user-pill">
                <div class="avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                <div>
                    <div class="fw-bold"><?= e($user['name']) ?></div>
                    <small class="text-muted">ITS: <?= e($user['its_number'] ?? '-') ?> &bull; Phone: <?= e($user['phone'] ?? '-') ?></small>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="hero-grid">
        <section class="panel slide-up">
            <div class="player-frame-shell" id="streamPlayerShell" style="<?= $hasStream && ($stream['status'] ?? '') === 'live' ? '' : 'display:none;' ?>">
                <iframe
                    id="streamPlayerFrame"
                    src="<?= $hasStream && ($stream['status'] ?? '') === 'live' ? e($stream['embed_url']) : 'about:blank' ?>"
                    allow="autoplay; encrypted-media; fullscreen"
                    allowfullscreen
                    title="Live stream player"></iframe>
                <div class="stream-chrome-mask" id="streamChromeMask"></div>
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
                <div class="offline-icon">📡</div>
                <div class="badge-offline mb-3">⚫ OFFLINE</div>
                <h2 class="stream-title">Live stream is not available right now</h2>
                <p class="stream-copy">Please wait for the next live broadcast.</p>
            </div>
        </section>

        <aside class="panel slide-up">
            <div class="notice-panel" style="border:none;border-radius:28px;margin-bottom:0;height:100%;box-sizing:border-box;">
                <div class="notice-meta">📢 Daily Announcement</div>
                <?php if ($dailyNotice): ?>
                    <div class="notice-title"><?= e($dailyNotice['title'] ?? 'Announcement') ?></div>
                    <div class="notice-body"><?= nl2br(e($dailyNotice['message'] ?? '')) ?></div>
                    <div class="notice-meta" style="margin-top:14px;"><?= e(date('d M Y', strtotime($dailyNotice['notice_date'] ?? 'now'))) ?></div>
                <?php else: ?>
                    <div class="text-muted" style="font-size:.9rem;">No announcement has been posted yet.</div>
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
