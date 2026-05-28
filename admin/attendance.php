<?php
/**
 * Stream attendance report.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/stream.php';

Auth::startSession();
Auth::requireAdmin();

$db = Database::getInstance();
$currentStream = getCurrentStream($db);
$latestStream = getLatestStream($db);
$requestedStreamId = (int) ($_GET['stream_id'] ?? 0);

$selectedStream = null;
if ($requestedStreamId > 0) {
    $selectedStream = $db->fetchOne(
        'SELECT s.id, s.title, s.youtube_url, s.youtube_video_id, s.status, s.created_by, s.created_at, u.name as creator_name
         FROM streams s
         LEFT JOIN users u ON s.created_by = u.id
         WHERE s.id = ?
         LIMIT 1',
        [$requestedStreamId]
    );
}

if (!$selectedStream) {
    $selectedStream = $currentStream ?? $latestStream;
}

$attendanceRows = $selectedStream ? getStreamAttendanceRows($db, (int) $selectedStream['id']) : [];
$attendanceCount = count($attendanceRows);
$streams = $db->fetchAll('SELECT id, title, status, created_at FROM streams ORDER BY created_at DESC, id DESC LIMIT 50');
$selectedStreamId = $selectedStream['id'] ?? 0;
$exportUrl = $selectedStreamId ? BASE_URL . '/admin/attendance_export.php?stream_id=' . urlencode((string) $selectedStreamId) : BASE_URL . '/admin/attendance_export.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report — <?= e(APP_NAME) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <?= csrfMeta() ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Marcellus&family=Noto+Naskh+Arabic:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/admin-red.css" rel="stylesheet">
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

        .section-card {
            background: rgba(255, 252, 246, 0.74);
            border: 1px solid rgba(183, 138, 58, 0.24);
            border-radius: 24px;
            overflow: hidden;
            margin-top: 18px;
        }

        .section-body { padding: 20px; }

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
            font-size: 1.6rem;
            font-weight: 900;
            margin-top: 8px;
        }

        .table-dark { --bs-table-bg: transparent; }
        .table-dark > :not(caption) > * > * { background: transparent; }

        @media (max-width: 992px) {
            .metrics-grid { grid-template-columns: 1fr; }
        }

        @media print {
            .topbar, .no-print { display: none !important; }
            body { background: #fff !important; }
            .section-card, .metric-card { box-shadow: none !important; }
        }
    </style>
</head>
<body class="admin-body">
<div class="admin-wrap">
    <header class="topbar slide-up">
        <div class="brand">
            <div class="brand-logo">
                <img src="<?= BASE_URL ?>/assets/images/logo.svg" alt="Anjuman logo" style="width:46px;height:46px;object-fit:contain;" onerror="this.style.display='none'">
            </div>
            <div>
                <div class="fw-bold" style="font-size:1.05rem;">Anjuman E Ezzy</div>
                <div class="text-muted small">Hatemi Mohallah, Rajkot — Relay Committee</div>
                <div class="text-muted xsmall" style="font-size:.72rem">Ashara Mubaraka 1448</div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center no-print">
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-light rounded-pill">Dashboard</a>
            <a href="<?= BASE_URL ?>/admin/streams.php" class="btn btn-outline-light rounded-pill">Streams</a>
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline-light rounded-pill">Users</a>
            <a href="<?= BASE_URL ?>/logout.php" class="btn btn-dark rounded-pill">Logout</a>
        </div>
    </header>

    <div class="section-card slide-up">
        <div class="section-body">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <div class="text-muted small text-uppercase">Attendance Report</div>
                    <h2 class="fw-bold mb-1"><?= e($selectedStream['title'] ?? 'No Stream Selected') ?></h2>
                    <div class="text-muted small">
                        <?= $selectedStream ? e(date('d M Y, h:i A', strtotime($selectedStream['created_at'] ?? 'now'))) : 'No stream found' ?>
                        <?php if ($selectedStream): ?>
                            &bull; <?= e(strtoupper($selectedStream['status'] ?? 'offline')) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap no-print">
                    <a class="btn btn-danger rounded-pill" href="<?= e($exportUrl) ?>">Download CSV</a>
                    <button type="button" class="btn btn-outline-light rounded-pill" onclick="window.print()">Print / Save PDF</button>
                </div>
            </div>

            <form method="get" class="row g-3 align-items-end mt-3 no-print">
                <div class="col-md-8">
                    <label class="form-label-dark" for="stream_id">Select Stream</label>
                    <select class="form-select form-select-dark" id="stream_id" name="stream_id">
                        <?php foreach ($streams as $streamOption): ?>
                            <option value="<?= e($streamOption['id']) ?>" <?= (int) ($streamOption['id'] ?? 0) === (int) $selectedStreamId ? 'selected' : '' ?>>
                                <?= e($streamOption['title']) ?> &bull; <?= e(strtoupper($streamOption['status'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-grid">
                    <button type="submit" class="btn btn-outline-light rounded-pill">Load Report</button>
                </div>
            </form>
        </div>
    </div>

    <section class="metrics-grid slide-up">
        <div class="metric-card">
            <div class="metric-label">Selected Stream</div>
            <div class="metric-value" style="font-size:1.1rem;"><?= e($selectedStream['title'] ?? 'None') ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Status</div>
            <div class="metric-value" style="font-size:1.1rem;"><?= e($selectedStream ? strtoupper($selectedStream['status'] ?? 'offline') : 'OFFLINE') ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Attendance Count</div>
            <div class="metric-value"><?= e($attendanceCount) ?></div>
        </div>
    </section>

    <div class="section-card slide-up">
        <div class="section-body">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ITS Number</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Login At</th>
                            <th>First Seen</th>
                            <th>Last Seen</th>
                            <th>Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendanceRows)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No attendance records available for this stream yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendanceRows as $row): ?>
                                <tr>
                                    <td><?= e($row['its_number'] ?? '') ?></td>
                                    <td><?= e($row['name'] ?? '') ?></td>
                                    <td><?= e($row['phone'] ?? '') ?></td>
                                    <td><?= e(strtoupper($row['role'] ?? 'user')) ?></td>
                                    <td><?= e($row['login_at'] ? date('d M Y, h:i A', strtotime($row['login_at'])) : '-') ?></td>
                                    <td><?= e($row['first_seen_at'] ? date('d M Y, h:i A', strtotime($row['first_seen_at'])) : '-') ?></td>
                                    <td><?= e($row['last_seen_at'] ? date('d M Y, h:i A', strtotime($row['last_seen_at'])) : '-') ?></td>
                                    <td><?= e($row['source_page'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
