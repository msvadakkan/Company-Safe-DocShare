<?php
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(false, 'Method not allowed', null, 405);
}

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim($body['username'] ?? '');
$password = $body['password'] ?? '';

if (!$username || !$password) {
    apiResponse(false, 'Username and password are required', null, 400);
}

$db   = getDB();
$stmt = $db->prepare("SELECT id, password_hash FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !password_verify($password, $user['password_hash'])) {
    apiResponse(false, 'Invalid username or password', null, 401);
}

// Generate token
$token      = bin2hex(random_bytes(32));
$expiresAt  = date('Y-m-d H:i:s', time() + TOKEN_EXPIRY_HOURS * 3600);
$userId     = $user['id'];

// Delete old tokens for this user
$db->prepare("DELETE FROM user_tokens WHERE user_id = ?")->bind_param('i', $userId)->execute();

// Insert new token
$stmt2 = $db->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
$stmt2->bind_param('iss', $userId, $token, $expiresAt);
$stmt2->execute();

apiResponse(true, 'Login successful', [
    'token'      => $token,
    'username'   => $username,
    'expires_at' => $expiresAt,
]);
