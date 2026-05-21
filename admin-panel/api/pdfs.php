<?php
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    apiResponse(false, 'Method not allowed', null, 405);
}

requireAuth();

$db   = getDB();
$rows = $db->query(
    "SELECT id, original_name AS name, file_size AS size, uploaded_at FROM pdfs ORDER BY uploaded_at DESC"
)->fetch_all(MYSQLI_ASSOC);

// Cast types
foreach ($rows as &$row) {
    $row['id']   = (int)$row['id'];
    $row['size'] = (int)$row['size'];
}
unset($row);

apiResponse(true, '', $rows);
