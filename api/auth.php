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
