<?php
/**
 * Admin user management.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::startSession();
Auth::requireAdmin();

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users — <?= e(APP_NAME) ?></title>
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

        .table-dark {
            --bs-table-bg: transparent;
        }

        .table-dark > :not(caption) > * > * {
            background: transparent;
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
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>/admin/attendance.php" class="btn btn-outline-light rounded-pill">Attendance</a>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-light rounded-pill">Dashboard</a>
            <a href="<?= BASE_URL ?>/admin/streams.php" class="btn btn-outline-light rounded-pill">Streams</a>
            <button type="button" class="btn btn-danger rounded-pill" data-bs-toggle="modal" data-bs-target="#createUserModal">Create User</button>
            <a href="<?= BASE_URL ?>/logout.php" class="btn btn-dark rounded-pill">Logout</a>
        </div>
    </header>

    <div class="section-card slide-up">
        <div class="section-body">
            <div class="row g-3 align-items-center mb-3">
                <div class="col-md-8">
                    <input type="text" id="userSearch" class="form-control form-control-dark" placeholder="Search users by ITS number, name, phone, or role">
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="badge bg-success-glow text-success border border-success-subtle px-3 py-2 rounded-pill">Logged in as <?= e($user['name']) ?></span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ITS Number</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade modal-dark" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="createUserForm" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label-dark" for="its_number">ITS Number</label>
                        <input type="text" class="form-control form-control-dark" id="its_number" name="its_number" maxlength="8" pattern="\d{8}" placeholder="Enter 8-digit ITS number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-dark" for="name">Name</label>
                        <input type="text" class="form-control form-control-dark" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-dark" for="phone">Phone Number</label>
                        <input type="text" class="form-control form-control-dark" id="phone" name="phone" maxlength="15" placeholder="Enter phone number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-dark" for="role">Role</label>
                        <select class="form-select form-select-dark" id="role" name="role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label-dark" for="status">Status</label>
                        <select class="form-select form-select-dark" id="status" name="status">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade modal-dark" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm" novalidate>
                <input type="hidden" id="edit_user_id" name="edit_user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label-dark" for="edit_its_number">ITS Number</label>
                        <input type="text" class="form-control form-control-dark" id="edit_its_number" name="edit_its_number" maxlength="8" pattern="\d{8}" placeholder="Enter 8-digit ITS number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-dark" for="edit_name">Name</label>
                        <input type="text" class="form-control form-control-dark" id="edit_name" name="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-dark" for="edit_phone">Phone Number</label>
                        <input type="text" class="form-control form-control-dark" id="edit_phone" name="edit_phone" maxlength="15" placeholder="Enter phone number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label-dark" for="edit_role">Role</label>
                        <select class="form-select form-select-dark" id="edit_role" name="edit_role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label-dark" for="edit_status">Status</label>
                        <select class="form-select form-select-dark" id="edit_status" name="edit_status">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Save Changes</button>
                </div>
            </form>
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
