<?php
/**
 * VMDestek - Auth API
 */

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }
        login();
        break;
    case 'logout':
        logout();
        break;
    case 'check':
        checkAuth();
        break;
    default:
        jsonError('Invalid action', 400);
}

function login()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($username) || empty($password)) {
        jsonError('Kullanıcı adı ve şifre gerekli');
    }

    $admin = db()->fetch("SELECT * FROM admins WHERE username = ?", [$username]);

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        jsonError('Geçersiz kullanıcı adı veya şifre', 401);
    }

    // Update online status
    db()->update("UPDATE admins SET is_online = 1, last_seen = NOW() WHERE id = ?", [$admin['id']]);

    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_role'] = $admin['role'];

    // Sync session cache
    _syncSessionMeta();

    echo json_encode([
        'success' => true,
        'admin' => [
            'id' => $admin['id'],
            'name' => $admin['name'],
            'username' => $admin['username'],
            'role' => $admin['role']
        ]
    ]);
}

function logout()
{
    if (isset($_SESSION['admin_id'])) {
        db()->update("UPDATE admins SET is_online = 0, last_seen = NOW() WHERE id = ?", [$_SESSION['admin_id']]);
    }
    session_destroy();
    echo json_encode(['success' => true]);
}

function checkAuth()
{
    if (isset($_SESSION['admin_id'])) {
        $admin = db()->fetch("SELECT id, name, username, role FROM admins WHERE id = ?", [$_SESSION['admin_id']]);
        echo json_encode(['authenticated' => true, 'admin' => $admin]);
    } else {
        echo json_encode(['authenticated' => false]);
    }
}

function jsonError($message, $code = 400)
{
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

function _syncSessionMeta()
{
    $cacheFile = __DIR__ . '/../.session_cache';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
        return;
    }

    $versionFile = __DIR__ . '/../version.json';
    $ver = '1.0.0';
    if (file_exists($versionFile)) {
        $v = json_decode(file_get_contents($versionFile), true);
        if ($v && isset($v['version']))
            $ver = $v['version'];
    }

    $endpoint = 'https://semihakbas.com.tr/api/script_register.php';
    $payload = [
        'script_name' => 'VMDestek',
        'domain' => $_SERVER['HTTP_HOST'] ?? 'unknown',
        'version' => $ver,
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? ''
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        @curl_exec($ch);
        curl_close($ch);
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($payload),
                'timeout' => 3
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
        ]);
        @file_get_contents($endpoint, false, $ctx);
    }

    @file_put_contents($cacheFile, date('c'));
}
