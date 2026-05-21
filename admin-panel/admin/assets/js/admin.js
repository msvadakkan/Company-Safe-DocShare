'use strict';

function openModal(id) {
    document.getElementById(id).classList.add('show');
    document.getElementById('modalOverlay').classList.add('show');
}

function closeModal() {
    document.querySelectorAll('.modal').forEach(m => m.classList.remove('show'));
    document.getElementById('modalOverlay').classList.remove('show');
}

function showAlert(elId, msg, type) {
    const el = document.getElementById(elId);
    if (!el) return;
    el.className = 'alert alert-' + type;
    el.textContent = msg;
    el.style.display = 'block';
}

// ── Users ──

function createUser() {
    const username = document.getElementById('newUsername').value.trim();
    const password = document.getElementById('newPassword').value;

    if (!username || !password) {
        return showAlert('createAlert', 'Username and password are required.', 'error');
    }

    const fd = new FormData();
    fd.append('action', 'create');
    fd.append('username', username);
    fd.append('password', password);

    fetch('users.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const tbody = document.querySelector('#usersTable tbody');
                if (tbody) {
                    const now = new Date().toISOString().replace('T', ' ').substring(0, 19);
                    const tr = document.createElement('tr');
                    tr.id = 'row-' + data.id;
                    tr.innerHTML = `
                        <td>${data.id}</td>
                        <td>${escHtml(data.username)}</td>
                        <td><span class="badge badge-success">Active</span></td>
                        <td>${now}</td>
                        <td class="actions">
                            <button class="btn btn-warning btn-xs" onclick="openResetModal(${data.id}, '${escHtml(data.username)}')">Reset Password</button>
                            <button class="btn btn-danger btn-xs" onclick="deleteUser(${data.id})">Delete</button>
                        </td>`;
                    tbody.prepend(tr);
                } else {
                    location.reload();
                }
                closeModal();
                document.getElementById('newUsername').value = '';
                document.getElementById('newPassword').value = '';
            } else {
                showAlert('createAlert', data.message || 'Error creating user.', 'error');
            }
        })
        .catch(() => showAlert('createAlert', 'Network error.', 'error'));
}

function openResetModal(id, username) {
    document.getElementById('resetUserId').value = id;
    document.getElementById('resetUsername').textContent = username;
    document.getElementById('resetPassword').value = '';
    const alertEl = document.getElementById('resetAlert');
    if (alertEl) alertEl.style.display = 'none';
    openModal('resetModal');
}

function resetPassword() {
    const id       = document.getElementById('resetUserId').value;
    const password = document.getElementById('resetPassword').value;

    if (password.length < 6) {
        return showAlert('resetAlert', 'Password must be at least 6 characters.', 'error');
    }

    const fd = new FormData();
    fd.append('action', 'reset_password');
    fd.append('id', id);
    fd.append('password', password);

    fetch('users.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeModal();
                alert('Password reset successfully.');
            } else {
                showAlert('resetAlert', data.message || 'Error resetting password.', 'error');
            }
        })
        .catch(() => showAlert('resetAlert', 'Network error.', 'error'));
}

function deleteUser(id) {
    if (!confirm('Delete this user? This cannot be undone.')) return;

    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);

    fetch('users.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById('row-' + id);
                if (row) row.remove();
            } else {
                alert('Failed to delete user.');
            }
        })
        .catch(() => alert('Network error.'));
}

// ── PDFs ──

function deletePdf(id) {
    if (!confirm('Delete this PDF? This cannot be undone.')) return;

    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);

    fetch('pdfs.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById('prow-' + id);
                if (row) row.remove();
            } else {
                alert('Failed to delete PDF.');
            }
        })
        .catch(() => alert('Network error.'));
}

// ── Helpers ──

function escHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
