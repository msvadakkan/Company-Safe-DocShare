<?php
/**
 * One-time installation script.
 * Visit this page once to create tables and the initial admin account.
 * DELETE THIS FILE immediately after setup.
 */

$step = $_POST['step'] ?? 'form';
$message = '';
$error = '';

if ($step === 'install') {
    $host = trim($_POST['db_host'] ?? 'localhost');
    $user = trim($_POST['db_user'] ?? '');
    $pass = $_POST['db_pass'] ?? '';
    $name = trim($_POST['db_name'] ?? 'safe_docshare');
    $admin_user = trim($_POST['admin_user'] ?? '');
    $admin_pass = $_POST['admin_pass'] ?? '';

    if (!$user || !$name || !$admin_user || !$admin_pass) {
        $error = 'All fields are required.';
    } elseif (strlen($admin_pass) < 8) {
        $error = 'Admin password must be at least 8 characters.';
    } else {
        $conn = @new mysqli($host, $user, $pass);
        if ($conn->connect_error) {
            $error = 'Cannot connect to MySQL: ' . $conn->connect_error;
        } else {
            $sql = file_get_contents(__DIR__ . '/setup.sql');
            // Replace DB name if custom
            $sql = str_replace('safe_docshare', $conn->real_escape_string($name), $sql);

            $conn->multi_query($sql);
            while ($conn->next_result()) { /* flush */ }

            $conn->select_db($name);

            // Write config
            $configContent = "<?php\n"
                . "define('DB_HOST', " . var_export($host, true) . ");\n"
                . "define('DB_USER', " . var_export($user, true) . ");\n"
                . "define('DB_PASS', " . var_export($pass, true) . ");\n"
                . "define('DB_NAME', " . var_export($name, true) . ");\n"
                . "define('UPLOAD_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);\n"
                . "define('APP_SECRET', " . var_export(bin2hex(random_bytes(32)), true) . ");\n"
                . "define('TOKEN_EXPIRY_HOURS', 24);\n\n"
                . "function getDB(): mysqli {\n"
                . "    static \$conn = null;\n"
                . "    if (\$conn === null) {\n"
                . "        \$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);\n"
                . "        if (\$conn->connect_error) {\n"
                . "            http_response_code(500);\n"
                . "            die(json_encode(['success' => false, 'message' => 'Database connection failed']));\n"
                . "        }\n"
                . "        \$conn->set_charset('utf8mb4');\n"
                . "    }\n"
                . "    return \$conn;\n"
                . "}\n";
            file_put_contents(__DIR__ . '/config/db.php', $configContent);

            // Create initial admin
            $hash = password_hash($admin_pass, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)");
            $stmt->bind_param('ss', $admin_user, $hash);
            $stmt->execute();

            $message = 'Installation complete! Admin account created. Please delete this file (install.php) now.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeDocShare – Install</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; border-radius: 8px; padding: 40px; width: 420px; box-shadow: 0 2px 20px rgba(0,0,0,0.1); }
        h1 { margin: 0 0 8px; color: #1a237e; font-size: 22px; }
        p { color: #666; margin: 0 0 24px; font-size: 14px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 4px; }
        input { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box; margin-bottom: 16px; }
        button { width: 100%; padding: 12px; background: #1a237e; color: #fff; border: none; border-radius: 6px; font-size: 15px; cursor: pointer; }
        button:hover { background: #283593; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        hr { border: none; border-top: 1px solid #eee; margin: 20px 0; }
        h3 { font-size: 14px; color: #555; margin: 0 0 12px; }
    </style>
</head>
<body>
<div class="card">
    <h1>SafeDocShare</h1>
    <p>One-time installation wizard. Delete this file after setup.</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php else: ?>
    <form method="POST">
        <input type="hidden" name="step" value="install">
        <h3>Database Configuration</h3>
        <label>MySQL Host</label>
        <input type="text" name="db_host" value="localhost" required>
        <label>MySQL Username</label>
        <input type="text" name="db_user" placeholder="root" required>
        <label>MySQL Password</label>
        <input type="password" name="db_pass" placeholder="(leave blank if none)">
        <label>Database Name</label>
        <input type="text" name="db_name" value="safe_docshare" required>
        <hr>
        <h3>Initial Admin Account</h3>
        <label>Admin Username</label>
        <input type="text" name="admin_user" placeholder="admin" required>
        <label>Admin Password (min. 8 characters)</label>
        <input type="password" name="admin_pass" minlength="8" required>
        <button type="submit">Install</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
