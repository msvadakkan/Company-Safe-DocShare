<?php
require_once 'auth_check.php';
require_once '../config/db.php';

$pageTitle  = 'PDF Files';
$activePage = 'pdfs';
$db = getDB();

$uploadError   = '';
$uploadSuccess = '';

// Handle delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'delete') {
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("SELECT stored_name FROM pdfs WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            $file = UPLOAD_PATH . $row['stored_name'];
            if (file_exists($file)) unlink($file);
            $db->prepare("DELETE FROM pdfs WHERE id = ?")->bind_param('i', $id) && true;
            $db->query("DELETE FROM pdfs WHERE id = $id");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Not found.']);
        }
        exit;
    }
    echo json_encode(['success' => false]);
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file'])) {
    $file = $_FILES['pdf_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadError = 'Upload failed. Error code: ' . $file['error'];
    } elseif ($file['size'] > 50 * 1024 * 1024) {
        $uploadError = 'File too large. Maximum 50 MB.';
    } else {
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if ($mimeType !== 'application/pdf') {
            $uploadError = 'Only PDF files are allowed.';
        } else {
            $origName   = basename($file['name']);
            $stored     = bin2hex(random_bytes(16)) . '.pdf';
            $dest       = UPLOAD_PATH . $stored;

            if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0755, true);

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $size = filesize($dest);
                $stmt = $db->prepare("INSERT INTO pdfs (original_name, stored_name, file_size) VALUES (?, ?, ?)");
                $stmt->bind_param('ssi', $origName, $stored, $size);
                $stmt->execute();
                $uploadSuccess = 'File "' . htmlspecialchars($origName) . '" uploaded successfully.';
            } else {
                $uploadError = 'Failed to save file. Check folder permissions.';
            }
        }
    }
}

$pdfs = $db->query("SELECT id, original_name, file_size, uploaded_at FROM pdfs ORDER BY uploaded_at DESC")->fetch_all(MYSQLI_ASSOC);

function fmtSize(int $b): string {
    if ($b >= 1048576) return round($b / 1048576, 2) . ' MB';
    if ($b >= 1024)    return round($b / 1024, 2) . ' KB';
    return $b . ' B';
}

include '_header.php';
?>

<!-- Upload Card -->
<div class="card mb-20">
    <div class="card-header">Upload PDF</div>
    <div class="card-body">
        <?php if ($uploadError): ?><div class="alert alert-error"><?= htmlspecialchars($uploadError) ?></div><?php endif; ?>
        <?php if ($uploadSuccess): ?><div class="alert alert-success"><?= $uploadSuccess ?></div><?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="upload-form">
            <div class="upload-row">
                <input type="file" name="pdf_file" accept=".pdf,application/pdf" required class="file-input">
                <button type="submit" class="btn btn-primary">Upload PDF</button>
            </div>
            <p class="hint">Maximum file size: 50 MB. Only PDF files accepted.</p>
        </form>
    </div>
</div>

<!-- Files List Card -->
<div class="card">
    <div class="card-header">
        <span>Uploaded PDFs (<?= count($pdfs) ?>)</span>
    </div>
    <div class="card-body">
        <?php if (empty($pdfs)): ?>
            <p class="empty-state">No PDFs uploaded yet.</p>
        <?php else: ?>
            <table class="table" id="pdfsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>File Name</th>
                        <th>Size</th>
                        <th>Uploaded</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pdfs as $pdf): ?>
                    <tr id="prow-<?= $pdf['id'] ?>">
                        <td><?= $pdf['id'] ?></td>
                        <td>
                            <span class="pdf-icon">&#128196;</span>
                            <?= htmlspecialchars($pdf['original_name']) ?>
                        </td>
                        <td><?= fmtSize((int)$pdf['file_size']) ?></td>
                        <td><?= htmlspecialchars($pdf['uploaded_at']) ?></td>
                        <td>
                            <button class="btn btn-danger btn-xs" onclick="deletePdf(<?= $pdf['id'] ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include '_footer.php'; ?>
