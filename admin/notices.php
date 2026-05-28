<?php
/**
 * Admin daily notice management.
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
$currentNotice = getActiveDailyNotice($db);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notices — <?= e(APP_NAME) ?></title>
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
            .metrics-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body class="admin-body">
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
            <span class="badge bg-success-glow text-success border border-success-subtle px-3 py-2 rounded-pill">Welcome, <?= e($user['name']) ?></span>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-light rounded-pill">Dashboard</a>
            <a href="<?= BASE_URL ?>/admin/streams.php" class="btn btn-outline-light rounded-pill">Streams</a>
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline-light rounded-pill">Users</a>
            <a href="<?= BASE_URL ?>/logout.php" class="btn btn-dark rounded-pill">Logout</a>
        </div>
    </header>

    <section class="metrics-grid slide-up">
        <div class="metric-card">
            <div class="metric-label">Active Notice</div>
            <div class="metric-value"><?= e($currentNotice ? ($currentNotice['title'] ?? 'None') : 'None') ?></div>
            <div class="text-muted small"><?= $currentNotice ? e(date('d M Y', strtotime($currentNotice['notice_date'] ?? 'now'))) : 'No active notice' ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Status</div>
            <div class="metric-value"><?= e($currentNotice ? strtoupper($currentNotice['status'] ?? 'inactive') : 'INACTIVE') ?></div>
            <div class="text-muted small">Current daily announcement state</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Managed By</div>
            <div class="metric-value"><?= e($currentNotice['creator_name'] ?? $user['name']) ?></div>
            <div class="text-muted small">Latest active notice owner</div>
        </div>
    </section>

    <div class="section-card slide-up">
        <div class="section-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <div class="text-muted small text-uppercase">Daily Announcement Editor</div>
                    <h2 class="fw-bold mb-0" id="noticeFormHeading">Create Notice</h2>
                </div>
                <button type="button" class="btn btn-outline-light rounded-pill" onclick="NoticeManager.clearForm()">Clear</button>
            </div>

            <form id="noticeForm" novalidate>
                <input type="hidden" id="notice_id" name="notice_id">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label-dark" for="notice_date">Notice Date</label>
                        <input type="date" class="form-control form-control-dark" id="notice_date" name="notice_date" value="<?= e(date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label-dark" for="notice_title">Title</label>
                        <input type="text" class="form-control form-control-dark" id="notice_title" name="notice_title" placeholder="Daily announcement title" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-dark" for="notice_status">Status</label>
                        <select class="form-select form-select-dark" id="notice_status" name="notice_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label-dark" for="notice_message">Message</label>
                    <textarea class="form-control form-control-dark" id="notice_message" name="notice_message" rows="5" placeholder="Write the daily announcement here..." required></textarea>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="submit" id="noticeFormSubmit" class="btn btn-danger">Save Notice</button>
                    <a href="<?= BASE_URL ?>/admin/attendance.php" class="btn btn-outline-light">Attendance Report</a>
                </div>
            </form>
        </div>
    </div>

    <div class="section-card slide-up">
        <div class="section-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <div class="text-muted small text-uppercase">Saved Notices</div>
                    <h2 class="fw-bold mb-0">Manage Daily Announcements</h2>
                </div>
                <input type="text" id="noticeSearch" class="form-control form-control-dark" style="max-width: 340px;" placeholder="Search notices">
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Creator</th>
                            <th>Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="noticesTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

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
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
