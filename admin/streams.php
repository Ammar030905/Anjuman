<?php
/**
 * Admin stream management for embedded YouTube URLs.
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
$currentAttendanceCount = ($currentStream && !empty($currentStream['id'])) ? getStreamAttendanceCount($db, (int) $currentStream['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Streams — <?= e(APP_NAME) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <?= csrfMeta() ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Marcellus&family=Noto+Naskh+Arabic:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/admin-red.css" rel="stylesheet">
    <style>
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

        .section-card {
            background: rgba(255, 252, 246, 0.74);
            border: 1px solid rgba(183, 138, 58, 0.24);
            border-radius: 24px;
            overflow: hidden;
        }

        .section-body {
            padding: 20px;
        }

        .player-frame-shell {
            position: relative;
            aspect-ratio: 16 / 9;
            background: #000;
            border-radius: 20px;
            overflow: hidden;
        }

        .player-frame-shell iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }

        .offline-state {
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 24px;
            color: var(--text-muted);
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
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
            font-size: 1.35rem;
            font-weight: 900;
            margin-top: 8px;
        }

        .table-dark {
            --bs-table-bg: transparent;
        }

        .table-dark > :not(caption) > * > * {
            background: transparent;
        }

        @media (max-width: 1100px) {
            .metrics-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body class="admin-body" data-stream-status-url="<?= BASE_URL ?>/ajax/stream_status.php" data-session-status-url="<?= BASE_URL ?>/ajax/session_status.php" data-login-url="<?= BASE_URL ?>/admin/login.php">
<div class="admin-wrap">
    <header class="topbar slide-up">
        <div class="brand">
            <div class="brand-logo">
                <img src="<?= BASE_URL ?>/assets/images/logo-removebg-preview.png" alt="Anjuman logo" style="width:46px;height:46px;object-fit:contain;">
            </div>
            <div>
                <div class="fw-bold" style="font-size:1.05rem;">Anjuman E Ezzy</div>
                <div class="text-muted small">Hatemi Mohallah, Rajkot — Relay Committee</div>
                <div class="text-muted xsmall" style="font-size:.72rem">Ashara Mubaraka 1448</div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <a href="<?= BASE_URL ?>/admin/attendance.php" class="btn btn-outline-light rounded-pill">Attendance</a>
            <a href="<?= BASE_URL ?>/admin/notices.php" class="btn btn-outline-light rounded-pill">Notices</a>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-light rounded-pill">Dashboard</a>
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline-light rounded-pill">Users</a>
            <a href="<?= BASE_URL ?>/logout.php" class="btn btn-dark rounded-pill">Logout</a>
        </div>
    </header>

    <section class="metrics-grid slide-up">
        <div class="metric-card">
            <div class="metric-label">Current Stream</div>
            <div class="metric-value"><?= e($currentStream ? ($currentStream['title'] ?? 'No Active Stream') : 'No Active Stream') ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Status</div>
            <div class="metric-value"><?= e($currentStream ? strtoupper($currentStream['status'] ?? 'offline') : 'OFFLINE') ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-label">YouTube Visibility</div>
            <div class="metric-value">Unlisted</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Attendance</div>
            <div class="metric-value"><?= e($currentAttendanceCount) ?></div>
            <div class="text-muted small">Unique users on current stream</div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="section-card slide-up">
                <div class="section-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div>
                            <div class="text-muted small text-uppercase">Stream Editor</div>
                            <h2 class="fw-bold mb-0" id="streamFormHeading">Save Live Stream</h2>
                        </div>
                        <button type="button" class="btn btn-outline-light rounded-pill" onclick="StreamManager.clearForm()">Clear</button>
                    </div>

                    <form id="streamForm" novalidate>
                        <input type="hidden" id="stream_id" name="stream_id">
                        <div class="mb-3">
                            <label class="form-label-dark" for="stream_title">Stream Title</label>
                            <input type="text" class="form-control form-control-dark" id="stream_title" name="stream_title" placeholder="Friday Live Session" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-dark" for="youtube_url">YouTube Live URL</label>
                            <input type="url" class="form-control form-control-dark" id="youtube_url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=VIDEO_ID" required>
                            <small class="text-muted d-block mt-2">Paste the live watch URL or video ID. The app extracts the video ID automatically.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-dark" for="stream_status">Stream Status</label>
                            <select class="form-select form-select-dark" id="stream_status" name="stream_status">
                                <option value="live">Live</option>
                                <option value="offline">Offline</option>
                            </select>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" id="streamFormSubmit" class="btn btn-danger">Save Stream</button>
                            <button type="button" class="btn btn-outline-light" onclick="StreamManager.start(document.getElementById('stream_id').value)" title="Start the current record as live">Start Stream</button>
                            <button type="button" class="btn btn-outline-light" onclick="StreamManager.end(document.getElementById('stream_id').value)" title="End the current record">End Stream</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="section-card slide-up mt-4">
                <div class="section-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div>
                            <div class="text-muted small text-uppercase">Saved Streams</div>
                            <h2 class="fw-bold mb-0">Manage Current Broadcasts</h2>
                        </div>
                        <input type="text" id="streamSearch" class="form-control form-control-dark" style="max-width: 340px;" placeholder="Search streams">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>YouTube URL</th>
                                    <th>Creator</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="streamsTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="section-card slide-up">
                <div class="section-body">
                    <div class="text-muted small text-uppercase">Current Preview</div>
                    <h2 class="fw-bold mb-3" id="stream-title-text"><?= e($currentStream ? ($currentStream['title'] ?? 'No Active Stream') : 'No Active Stream') ?></h2>
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
                            <p class="text-muted mt-3 mb-0"><?= $currentStream && ($currentStream['status'] ?? '') === 'live' ? 'Streaming now' : 'No active broadcast right now.' ?></p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                        <span id="stream-status-badge" class="<?= $currentStream && ($currentStream['status'] ?? '') === 'live' ? 'badge-live' : 'badge-offline' ?>">
                            <?= $currentStream && ($currentStream['status'] ?? '') === 'live' ? '<span class="live-dot"></span> LIVE' : '⚫ OFFLINE' ?>
                        </span>
                        <span class="text-muted small" id="stream-updated-at"><?= $currentStream ? 'Updated ' . e(date('d M Y, h:i A', strtotime($currentStream['created_at'] ?? 'now'))) : 'No stream yet' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Modal -->
<div class="modal fade modal-dark" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <p id="confirmMsg" class="mb-4" style="font-size:0.95rem;"></p>
                <div class="d-flex justify-content-center gap-2">
                    <button class="btn btn-danger btn-sm px-4" id="confirmBtn">Confirm</button>
                    <button class="btn btn-outline-light btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
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
