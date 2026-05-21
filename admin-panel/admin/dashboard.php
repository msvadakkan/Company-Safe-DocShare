<?php
require_once 'auth_check.php';
require_once '../config/db.php';

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

$db = getDB();

$userCount = $db->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$pdfCount  = $db->query("SELECT COUNT(*) as c FROM pdfs")->fetch_assoc()['c'];

$totalSize = $db->query("SELECT COALESCE(SUM(file_size),0) as s FROM pdfs")->fetch_assoc()['s'];
function formatBytes(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576)    return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)       return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

$recentPdfs  = $db->query("SELECT original_name, file_size, uploaded_at FROM pdfs ORDER BY uploaded_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$recentUsers = $db->query("SELECT username, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

include '_header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon stat-blue">&#128101;</div>
        <div>
            <div class="stat-value"><?= $userCount ?></div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-green">&#128196;</div>
        <div>
            <div class="stat-value"><?= $pdfCount ?></div>
            <div class="stat-label">PDF Files</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon stat-purple">&#128190;</div>
        <div>
            <div class="stat-value"><?= formatBytes((int)$totalSize) ?></div>
            <div class="stat-label">Storage Used</div>
        </div>
    </div>
</div>

<div class="row-2col">
    <div class="card">
        <div class="card-header">Recent PDF Uploads</div>
        <div class="card-body">
            <?php if (empty($recentPdfs)): ?>
                <p class="empty-state">No PDFs uploaded yet.</p>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>File Name</th><th>Size</th><th>Uploaded</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentPdfs as $pdf): ?>
                        <tr>
                            <td><?= htmlspecialchars($pdf['original_name']) ?></td>
                            <td><?= formatBytes((int)$pdf['file_size']) ?></td>
                            <td><?= htmlspecialchars($pdf['uploaded_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Recent Users</div>
        <div class="card-body">
            <?php if (empty($recentUsers)): ?>
                <p class="empty-state">No users created yet.</p>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Username</th><th>Created</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentUsers as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '_footer.php'; ?>
