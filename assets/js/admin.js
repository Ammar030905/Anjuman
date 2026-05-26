/**
 * Anjuman E Ezzy - Admin panel runtime.
 */

'use strict';

const Sidebar = {
    el: null,
    overlay: null,
    init() {
        this.el = document.querySelector('.admin-sidebar');
        this.overlay = document.querySelector('.sidebar-backdrop');

        if (!this.overlay) {
            this.overlay = document.createElement('div');
            this.overlay.className = 'sidebar-backdrop';
            document.body.appendChild(this.overlay);
        }

        document.getElementById('sidebarToggle')?.addEventListener('click', (event) => {
            event.preventDefault();
            this.toggle();
        });

        this.overlay.addEventListener('click', () => this.close());
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') this.close();
        });
    },
    open() {
        this.el?.classList.add('open');
        this.overlay?.classList.add('show');
    },
    close() {
        this.el?.classList.remove('open');
        this.overlay?.classList.remove('show');
    },
    toggle() {
        if (this.el?.classList.contains('open')) {
            this.close();
            return;
        }
        this.open();
    },
};

function roleBadge(role) {
    return role === 'admin'
        ? '<span class="role-badge role-admin">🔑 admin</span>'
        : '<span class="role-badge role-user">👤 user</span>';
}

function statusBadge(status) {
    return Number(status) === 1
        ? '<span class="status-pill active"><span class="status-dot"></span> Active</span>'
        : '<span class="status-pill inactive"><span class="status-dot"></span> Inactive</span>';
}

function streamBadge(status) {
    if (status === 'live') {
        return '<span class="badge-live"><span class="live-dot"></span> LIVE</span>';
    }
    return '<span class="badge-offline">⚫ OFFLINE</span>';
}

const UserManager = {
    tableBody: null,
    editId: null,

    init() {
        this.tableBody = document.getElementById('usersTableBody');
        if (!this.tableBody) return;
        this.bindForms();
        this.bindSearch();
        this.load();
    },

    bindForms() {
        document.getElementById('createUserForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const form = event.target;
            const itsNumber = form.its_number.value.trim();
            const phone = form.phone.value.trim().replace(/\s+/g, '');

            if (!/^\d{8}$/.test(itsNumber)) {
                Toast.error('Validation', 'ITS number must be exactly 8 digits.');
                return;
            }

            if (!/^\+?[0-9]{10,15}$/.test(phone)) {
                Toast.error('Validation', 'Phone number must be 10 to 15 digits.');
                return;
            }

            const data = {
                action: 'create',
                its_number: itsNumber,
                name: form.name.value.trim(),
                phone,
                password: form.password.value,
                role: form.role.value,
                status: form.status.value,
            };

            const button = form.querySelector('[type=submit]');
            button.disabled = true;
            button.textContent = 'Creating...';

            ajaxPost('../ajax/users.php', data, (resp) => {
                button.disabled = false;
                button.textContent = 'Create User';
                if (resp.success) {
                    Toast.success('Created', resp.message);
                    form.reset();
                    bootstrap.Modal.getInstance(document.getElementById('createUserModal'))?.hide();
                    this.load();
                    return;
                }
                Toast.error('Failed', resp.message);
            }, (err) => {
                button.disabled = false;
                button.textContent = 'Create User';
                Toast.error('Error', err.message || 'Could not create user.');
            });
        });

        document.getElementById('editUserForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const form = event.target;
            const itsNumber = form.edit_its_number.value.trim();
            const phone = form.edit_phone.value.trim().replace(/\s+/g, '');

            if (!/^\d{8}$/.test(itsNumber)) {
                Toast.error('Validation', 'ITS number must be exactly 8 digits.');
                return;
            }

            if (!/^\+?[0-9]{10,15}$/.test(phone)) {
                Toast.error('Validation', 'Phone number must be 10 to 15 digits.');
                return;
            }

            const data = {
                action: 'update',
                id: this.editId,
                its_number: itsNumber,
                name: form.edit_name.value.trim(),
                phone,
                password: form.edit_password.value,
                role: form.edit_role.value,
                status: form.edit_status.value,
            };

            const button = form.querySelector('[type=submit]');
            button.disabled = true;
            button.textContent = 'Saving...';

            ajaxPost('../ajax/users.php', data, (resp) => {
                button.disabled = false;
                button.textContent = 'Save Changes';
                if (resp.success) {
                    Toast.success('Updated', resp.message);
                    bootstrap.Modal.getInstance(document.getElementById('editUserModal'))?.hide();
                    this.load();
                    return;
                }
                Toast.error('Failed', resp.message);
            }, (err) => {
                button.disabled = false;
                button.textContent = 'Save Changes';
                Toast.error('Error', err.message || 'Could not update user.');
            });
        });
    },

    bindSearch() {
        document.getElementById('userSearch')?.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            document.querySelectorAll('#usersTableBody tr[data-id]').forEach((row) => {
                row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
            });
        });
    },

    load() {
        this.tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><div class="spinner-border spinner-border-sm text-danger"></div> Loading...</td></tr>';
        ajaxGet('../ajax/users.php', { action: 'list' }, (resp) => {
            if (!resp.success) {
                Toast.error('Error', resp.message);
                return;
            }
            this.render(resp.users || []);
        });
    },

    render(users) {
        if (!users.length) {
            this.tableBody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">No users found.</td></tr>';
            return;
        }

        this.tableBody.innerHTML = users.map((user) => `
            <tr data-id="${user.id}">
                <td><span class="fw-semibold">${escHtml(user.its_number || '')}</span></td>
                <td>
                    <div class="fw-semibold">${escHtml(user.name)}</div>
                </td>
                <td><small class="text-muted">${escHtml(user.phone || '')}</small></td>
                <td>${roleBadge(user.role)}</td>
                <td>${statusBadge(user.status)}</td>
                <td class="text-muted" style="font-size:.78rem">${formatDate(user.created_at)}</td>
                <td>
                    <div class="action-btns">
                        <button class="btn-table-action btn-edit" title="Edit" onclick="UserManager.openEdit(${user.id})">✏️</button>
                        <button class="btn-table-action ${Number(user.status) === 1 ? 'btn-toggle-on' : 'btn-toggle-off'}" title="Toggle status" onclick="UserManager.toggleStatus(${user.id})">${Number(user.status) === 1 ? '🟢' : '⭕'}</button>
                        <button class="btn-table-action btn-delete" title="Delete" onclick="UserManager.remove(${user.id})">🗑️</button>
                    </div>
                </td>
            </tr>
        `).join('');
    },

    openEdit(id) {
        this.editId = id;
        ajaxGet('../ajax/users.php', { action: 'get', id }, (resp) => {
            if (!resp.success) {
                Toast.error('Error', resp.message);
                return;
            }

            const user = resp.user;
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_its_number').value = user.its_number || '';
            document.getElementById('edit_name').value = user.name || '';
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_role').value = user.role || 'user';
            document.getElementById('edit_status').value = String(user.status ?? 1);
            document.getElementById('edit_password').value = '';
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        });
    },

    toggleStatus(id) {
        ajaxPost('../ajax/users.php', { action: 'toggle_status', id }, (resp) => {
            if (resp.success) {
                Toast.success('Done', resp.message);
                this.load();
                return;
            }
            Toast.error('Failed', resp.message);
        });
    },

    remove(id) {
        confirmAction('Delete this user account? This cannot be undone.', () => {
            ajaxPost('../ajax/users.php', { action: 'delete', id }, (resp) => {
                if (resp.success) {
                    Toast.success('Deleted', resp.message);
                    this.load();
                    return;
                }
                Toast.error('Failed', resp.message);
            });
        });
    },
};

const StreamManager = {
    tableBody: null,
    editId: null,

    init() {
        this.tableBody = document.getElementById('streamsTableBody');
        if (!this.tableBody && !document.getElementById('streamForm')) return;
        this.bindForm();
        this.bindSearch();
        this.load();
    },

    bindForm() {
        document.getElementById('streamForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            const form = event.target;
            const status = form.stream_status.value;
            const isLive = status === 'live';
            const action = isLive ? 'start' : 'save';

            const data = {
                action,
                id: form.stream_id.value || '',
                title: form.stream_title.value.trim(),
                youtube_url: form.youtube_url.value.trim(),
                status,
            };

            const button = form.querySelector('[type=submit]');
            button.disabled = true;
            button.textContent = isLive ? 'Starting...' : 'Saving...';

            ajaxPost('../ajax/streams.php', data, (resp) => {
                button.disabled = false;
                button.textContent = 'Save Stream';
                if (resp.success) {
                    Toast.success('Saved', resp.message);
                    this.clearForm();
                    this.load();
                    StreamViewer.refresh?.();
                    return;
                }
                Toast.error('Failed', resp.message);
            }, (err) => {
                button.disabled = false;
                button.textContent = 'Save Stream';
                Toast.error('Error', err.message || 'Could not save stream.');
            });
        });
    },

    bindSearch() {
        document.getElementById('streamSearch')?.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            document.querySelectorAll('#streamsTableBody tr[data-id]').forEach((row) => {
                row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
            });
        });
    },

    load() {
        if (!this.tableBody) return;
        this.tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-danger"></div> Loading...</td></tr>';
        ajaxGet('../ajax/streams.php', { action: 'list' }, (resp) => {
            if (!resp.success) {
                Toast.error('Error', resp.message);
                return;
            }
            this.render(resp.streams || []);
        });
    },

    render(streams) {
        if (!streams.length) {
            this.tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">No streams available.</td></tr>';
            return;
        }

        this.tableBody.innerHTML = streams.map((stream) => `
            <tr data-id="${stream.id}">
                <td>
                    <div class="fw-semibold">${escHtml(stream.title)}</div>
                    <small class="text-muted">${escHtml(stream.youtube_video_id || '')}</small>
                </td>
                <td>${streamBadge(stream.status)}</td>
                <td>
                    <div class="text-truncate" style="max-width: 260px">${escHtml(stream.youtube_url || '')}</div>
                </td>
                <td class="text-muted" style="font-size:.78rem">${escHtml(stream.creator_name || 'Admin')}</td>
                <td class="text-muted" style="font-size:.78rem">${formatDate(stream.created_at)}</td>
                <td>
                    <div class="action-btns">
                        <button class="btn-table-action btn-edit" title="Edit" onclick="StreamManager.openEdit(${stream.id})">✏️</button>
                        <button class="btn-table-action btn-toggle-on" title="Start" onclick="StreamManager.start(${stream.id})">▶️</button>
                        <button class="btn-table-action btn-toggle-off" title="End" onclick="StreamManager.end(${stream.id})">⏹</button>
                        <button class="btn-table-action btn-delete" title="Delete" onclick="StreamManager.remove(${stream.id})">🗑️</button>
                    </div>
                </td>
            </tr>
        `).join('');
    },

    openEdit(id) {
        this.editId = id;
        ajaxGet('../ajax/streams.php', { action: 'get', id }, (resp) => {
            if (!resp.success) {
                Toast.error('Error', resp.message);
                return;
            }

            const stream = resp.stream;
            document.getElementById('stream_id').value = stream.id;
            document.getElementById('stream_title').value = stream.title || '';
            document.getElementById('youtube_url').value = stream.youtube_url || '';
            document.getElementById('stream_status').value = stream.status || 'offline';
            document.getElementById('streamFormSubmit').textContent = 'Update Stream';
            document.getElementById('streamFormHeading').textContent = 'Edit Stream';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    },

    clearForm() {
        this.editId = null;
        const form = document.getElementById('streamForm');
        if (form) form.reset();
        const submit = document.getElementById('streamFormSubmit');
        if (submit) submit.textContent = 'Save Stream';
        const heading = document.getElementById('streamFormHeading');
        if (heading) heading.textContent = 'Save Live Stream';
    },

    start(id) {
        confirmAction('Start this stream now? It will become the active embedded stream.', () => {
            ajaxPost('../ajax/streams.php', { action: 'start', id }, (resp) => {
                if (resp.success) {
                    Toast.success('Live', resp.message);
                    this.load();
                    StreamViewer.refresh?.();
                    return;
                }
                Toast.error('Failed', resp.message);
            });
        });
    },

    end(id) {
        confirmAction('End this stream now?', () => {
            ajaxPost('../ajax/streams.php', { action: 'end', id }, (resp) => {
                if (resp.success) {
                    Toast.success('Ended', resp.message);
                    this.load();
                    StreamViewer.refresh?.();
                    return;
                }
                Toast.error('Failed', resp.message);
            });
        });
    },

    remove(id) {
        confirmAction('Delete this stream permanently?', () => {
            ajaxPost('../ajax/streams.php', { action: 'delete', id }, (resp) => {
                if (resp.success) {
                    Toast.success('Deleted', resp.message);
                    this.load();
                    StreamViewer.refresh?.();
                    return;
                }
                Toast.error('Failed', resp.message);
            });
        });
    },
};

function escHtml(value) {
    const element = document.createElement('div');
    element.textContent = value || '';
    return element.innerHTML;
}

function formatDate(value) {
    if (!value) return '—';
    const date = new Date(value);
    return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}

$(function () {
    Sidebar.init();
    UserManager.init();
    StreamManager.init();
    window.UserManager = UserManager;
    window.StreamManager = StreamManager;
});
