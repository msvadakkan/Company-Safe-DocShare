<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'novatech_DocShare');
define('UPLOAD_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
define('APP_SECRET', 'CHANGE_THIS_TO_A_LONG_RANDOM_STRING_IN_PRODUCTION');
define('TOKEN_EXPIRY_HOURS', 24);

function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            // Check if the request expects JSON (API calls) or HTML (admin panel)
            $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
            if ($isApi) {
                http_response_code(503);
                header('Content-Type: application/json');
                die(json_encode(['success' => false, 'message' => 'Service temporarily unavailable. Please try again later.']));
            } else {
                http_response_code(503);
                die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Service Unavailable</title>'
                  . '<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f5f5f5;}'
                  . '.box{background:#fff;padding:40px;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,.1);text-align:center;max-width:420px;}'
                  . 'h2{color:#c62828;margin:0 0 12px}p{color:#555;margin:0}</style></head>'
                  . '<body><div class="box"><h2>Database Connection Failed</h2>'
                  . '<p>Could not connect to the database. Please check your configuration in <code>config/db.php</code> '
                  . 'or run <a href="../install.php">install.php</a> to set up the database.</p></div></body></html>');
            }
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}
