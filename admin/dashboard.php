<?php
/**
 * Admin dashboard for the embedded-stream architecture.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/stream.php';

Auth::startSession();
Auth::requireAdmin();

$user = Auth::user();
$db = Database::getInstance();
$currentStream = getCurrentStream($db) ?? getLatestStream($db);

$totalUsers = (int) ($db->fetchOne('SELECT COUNT(*) AS count FROM users')['count'] ?? 0);
$activeUsers = (int) ($db->fetchOne('SELECT COUNT(*) AS count FROM users WHERE status = 1')['count'] ?? 0);
$totalStreams = (int) ($db->fetchOne('SELECT COUNT(*) AS count FROM streams')['count'] ?? 0);
$liveStreams = (int) ($db->fetchOne('SELECT COUNT(*) AS count FROM streams WHERE status = "live"')['count'] ?? 0);
$logs = $db->fetchAll('SELECT l.action, l.timestamp, u.name AS username FROM activity_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.timestamp DESC LIMIT 8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — <?= e(APP_NAME) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <?= csrfMeta() ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Marcellus&family=Noto+Naskh+Arabic:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/admin.css" rel="stylesheet">
    <style>
        body { background: transparent; }

        .admin-wrap {
            max-width: 1480px;
            margin: 0 auto;
            padding: 22px 18px 30px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 18px;
            margin-bottom: 18px;
            background: rgba(255, 251, 242, 0.66);
            border: 1px solid rgba(183, 138, 58, 0.25);
            border-radius: 22px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--accent), var(--turquoise));
        }

        .current-stream-card {
            background: rgba(255, 252, 246, 0.74);
            border: 1px solid rgba(183, 138, 58, 0.24);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 26px 80px rgba(9, 37, 31, 0.16);
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

        .offline-state {
            min-height: 360px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 24px;
            color: var(--text-muted);
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin: 18px 0;
        }

        .metric-card {
            background: rgba(255, 252, 246, 0.74);
            border: 1px solid rgba(183, 138, 58, 0.24);
            border-radius: 22px;
            padding: 18px;
        }

        .metric-label {
            color: var(--text-muted);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }

        .metric-value {
            font-size: 2rem;
            font-family: var(--font-heading);
            margin-top: 8px;
            letter-spacing: -0.03em;
        }

        .section-card {
            background: rgba(255, 252, 246, 0.74);
            border: 1px solid rgba(183, 138, 58, 0.24);
            border-radius: 24px;
            overflow: hidden;
            margin-top: 18px;
        }

        .section-body {
            padding: 20px;
        }

        @media (max-width: 1100px) {
            .metrics-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .metrics-grid {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body class="admin-body" data-stream-status-url="<?= BASE_URL ?>/ajax/stream_status.php">
<div class="admin-wrap">
    <header class="topbar slide-up">
        <div class="brand">
            <div class="brand-logo">📡</div>
            <div>
                <div class="fw-bold" style="font-size:1.15rem;"><?= e(APP_NAME) ?></div>
                <div class="text-muted small">Embedded live stream control center</div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <span class="badge bg-success-glow text-success border border-success-subtle px-3 py-2 rounded-pill">Welcome, <?= e($user['name']) ?></span>
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline-light rounded-pill">Users</a>
            <a href="<?= BASE_URL ?>/admin/streams.php" class="btn btn-danger rounded-pill">Streams</a>
            <a href="<?= BASE_URL ?>/logout.php" class="btn btn-dark rounded-pill">Logout</a>
        </div>
    </header>

    <section class="metrics-grid slide-up">
        <div class="metric-card">
            <div class="metric-label">Total Users</div>
            <div class="metric-value"><?= e($totalUsers) ?></div>
            <div class="text-muted small">Registered accounts</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Active Users</div>
            <div class="metric-value"><?= e($activeUsers) ?></div>
            <div class="text-muted small">Status = active</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Total Streams</div>
            <div class="metric-value"><?= e($totalStreams) ?></div>
            <div class="text-muted small">Saved broadcast entries</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Live Streams</div>
            <div class="metric-value"><?= e($liveStreams) ?></div>
            <div class="text-muted small">Current active session</div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="current-stream-card slide-up">
                <div class="player-frame-shell" id="streamPlayerShell" style="<?= $currentStream && ($currentStream['status'] ?? '') === 'live' ? '' : 'display:none;' ?>">
                    <iframe
                        id="streamPlayerFrame"
                        src="<?= $currentStream && ($currentStream['status'] ?? '') === 'live' ? e($currentStream['embed_url']) : 'about:blank' ?>"
                        allow="autoplay; encrypted-media; fullscreen"
                        allowfullscreen
                        title="Live stream preview"></iframe>
                </div>
                <div class="offline-state" id="streamOfflineState" style="<?= $currentStream && ($currentStream['status'] ?? '') === 'live' ? 'display:none;' : '' ?>">
                    <div>
                        <div style="font-size:3rem;">📡</div>
                        <div class="<?= $currentStream && ($currentStream['status'] ?? '') === 'live' ? 'badge-live' : 'badge-offline' ?> mt-3">
                            <?= $currentStream && ($currentStream['status'] ?? '') === 'live' ? '<span class="live-dot"></span> LIVE' : '⚫ OFFLINE' ?>
                        </div>
                        <h2 class="mt-3 fw-bold"><?= e($currentStream ? ($currentStream['title'] ?? 'No Active Stream') : 'No Active Stream') ?></h2>
                        <p class="text-muted mb-0"><?= $currentStream && ($currentStream['status'] ?? '') === 'live' ? 'Streaming now' : 'The latest broadcast is currently offline.' ?></p>
                    </div>
                </div>
                <div class="section-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <div>
                            <div class="text-muted small text-uppercase">Current Broadcast</div>
                            <h3 class="fw-bold mb-1" id="stream-title-text"><?= e($currentStream ? ($currentStream['title'] ?? 'No Active Stream') : 'No Active Stream') ?></h3>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span id="stream-status-badge" class="<?= $currentStream && ($currentStream['status'] ?? '') === 'live' ? 'badge-live' : 'badge-offline' ?>">
                                <?= $currentStream && ($currentStream['status'] ?? '') === 'live' ? '<span class="live-dot"></span> LIVE' : '⚫ OFFLINE' ?>
                            </span>
                            <span class="text-muted small" id="stream-updated-at"><?= $currentStream ? 'Updated ' . e(date('d M Y, h:i A', strtotime($currentStream['created_at'] ?? 'now'))) : 'No stream yet' ?></span>
                        </div>
                    </div>
                    <p class="text-muted mb-0" id="stream-subtitle-text"><?= $currentStream && ($currentStream['status'] ?? '') === 'live' ? 'Streaming now' : 'The latest broadcast is currently offline.' ?></p>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="section-card slide-up">
                <div class="section-body">
                    <h3 class="fw-bold mb-3">Quick Actions</h3>
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/admin/streams.php" class="btn btn-danger">Manage Streams</a>
                        <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline-light">Manage Users</a>
                        <a href="<?= BASE_URL ?>/stream.php" class="btn btn-outline-light">Open Public Player</a>
                    </div>
                </div>
            </div>

            <div class="section-card slide-up">
                <div class="section-body">
                    <h3 class="fw-bold mb-3">Recent Activity</h3>
                    <div class="d-grid gap-3">
                        <?php if (empty($logs)): ?>
                            <div class="text-muted">No activity logged yet.</div>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <div>
                                    <div class="fw-semibold"><?= e($log['username'] ?? 'System') ?></div>
                                    <div class="text-muted small"><?= e($log['action']) ?></div>
                                    <div class="text-muted" style="font-size:0.75rem;"><?= e(timeAgo($log['timestamp'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
