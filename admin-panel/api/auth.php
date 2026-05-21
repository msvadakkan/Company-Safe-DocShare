<?php
require_once dirname(__DIR__) . '/config/db.php';

function apiResponse(bool $success, string $message = '', $data = null, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    $resp = ['success' => $success, 'message' => $message];
    if ($data !== null) $resp['data'] = $data;
    echo json_encode($resp);
    exit;
}

function requireAuth(): array {
    $headers = getallheaders();
    $auth    = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    $token   = '';

    if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
        $token = trim($m[1]);
    }

    if (!$token) {
        apiResponse(false, 'Unauthorized: missing token', null, 401);
    }

    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT u.id, u.username FROM user_tokens t
         JOIN users u ON u.id = t.user_id
         WHERE t.token = ? AND t.expires_at > NOW() AND u.is_active = 1
         LIMIT 1"
    );
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        apiResponse(false, 'Unauthorized: invalid or expired token', null, 401);
    }

    return $user;
}

// CORS headers – tighten in production
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
