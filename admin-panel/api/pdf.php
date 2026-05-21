<?php
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    apiResponse(false, 'Method not allowed', null, 405);
}

requireAuth();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    apiResponse(false, 'Invalid PDF id', null, 400);
}

$db   = getDB();
$stmt = $db->prepare("SELECT original_name, stored_name, file_size FROM pdfs WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$pdf = $stmt->get_result()->fetch_assoc();

if (!$pdf) {
    apiResponse(false, 'PDF not found', null, 404);
}

$filePath = UPLOAD_PATH . $pdf['stored_name'];
if (!file_exists($filePath)) {
    apiResponse(false, 'File not found on server', null, 404);
}

// Stream the PDF – no caching on client
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . addslashes($pdf['original_name']) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

// Remove CORS headers that were set in auth.php for this binary response
header_remove('Access-Control-Allow-Origin');
header_remove('Access-Control-Allow-Methods');
header_remove('Access-Control-Allow-Headers');

readfile($filePath);
exit;
