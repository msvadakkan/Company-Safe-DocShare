<?php
require_once 'auth_check.php';
require_once '../config/db.php';

$pageTitle  = 'Users';
$activePage = 'users';
$db = getDB();

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
            exit;
        }
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit;
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            echo json_encode(['success' => false, 'message' => 'Username: 3-50 chars, letters/numbers/underscore only.']);
            exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
        $stmt->bind_param('ss', $username, $hash);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User created.', 'id' => $db->insert_id, 'username' => $username]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Username already exists.']);
        }
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        echo json_encode(['success' => $stmt->execute() && $stmt->affected_rows > 0]);
        exit;
    }

    if ($action === 'reset_password') {
        $id       = (int)($_POST['id'] ?? 0);
        $password = $_POST['password'] ?? '';
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit;
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->bind_param('si', $hash, $id);
        // Invalidate all tokens for this user
        $db->prepare("DELETE FROM user_tokens WHERE user_id = ?")->bind_param('i', $id) && true;
        echo json_encode(['success' => $stmt->execute()]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

$users = $db->query("SELECT id, username, is_active, created_at FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

include '_header.php';
?>

<div class="card">
    <div class="card-header">
        <span>All Users</span>
        <button class="btn btn-primary btn-sm" onclick="openModal('createModal')">+ Add User</button>
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
            <p class="empty-state">No users yet. Create one above.</p>
        <?php else: ?>
            <table class="table" id="usersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr id="row-<?= $u['id'] ?>">
                        <td><?= $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><span class="badge badge-<?= $u['is_active'] ? 'success' : 'danger' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                        <td><?= htmlspecialchars($u['created_at']) ?></td>
                        <td class="actions">
                            <button class="btn btn-warning btn-xs" onclick="openResetModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">Reset Password</button>
                            <button class="btn btn-danger btn-xs" onclick="deleteUser(<?= $u['id'] ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal" id="createModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Create New User</h3>
            <button class="modal-close" onclick="closeModal()">&#10005;</button>
        </div>
        <div class="modal-body">
            <div class="alert" id="createAlert" style="display:none"></div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" id="newUsername" placeholder="letters, numbers, underscore">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="newPassword" placeholder="Min. 6 characters">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button class="btn btn-primary" onclick="createUser()">Create User</button>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal" id="resetModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Reset Password: <span id="resetUsername"></span></h3>
            <button class="modal-close" onclick="closeModal()">&#10005;</button>
        </div>
        <div class="modal-body">
            <div class="alert" id="resetAlert" style="display:none"></div>
            <input type="hidden" id="resetUserId">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" id="resetPassword" placeholder="Min. 6 characters">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button class="btn btn-warning" onclick="resetPassword()">Reset Password</button>
        </div>
    </div>
</div>

<?php include '_footer.php'; ?>
