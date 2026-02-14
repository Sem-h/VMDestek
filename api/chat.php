<?php
/**
 * VMDestek - Chat API (Visitor/Widget Side)
 */

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'start':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }
        startConversation();
        break;
    case 'send':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }
        sendMessage();
        break;
    case 'messages':
        getMessages();
        break;
    case 'poll':
        pollMessages();
        break;
    case 'typing':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }
        setTyping();
        break;
    case 'end':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }
        endConversation();
        break;
    case 'rate':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }
        rateConversation();
        break;
    case 'status':
        getStatus();
        break;
    default:
        jsonError('Invalid action', 400);
}

function startConversation()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $page = trim($data['page'] ?? '');
    $siteUrl = trim($data['site_url'] ?? '');

    if (empty($name)) {
        jsonError('İsim gerekli');
    }

    $sessionId = bin2hex(random_bytes(32));
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $id = db()->insert(
        "INSERT INTO conversations (visitor_name, visitor_email, visitor_ip, visitor_user_agent, visitor_page, site_url, session_id, status, last_message_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, 'waiting', NOW())",
        [$name, $email, $ip, $ua, $page, $siteUrl, $sessionId]
    );

    // Get welcome message
    $welcome = db()->fetch("SELECT setting_value FROM settings WHERE setting_key = 'welcome_message'");
    if ($welcome) {
        db()->insert(
            "INSERT INTO messages (conversation_id, sender_type, sender_name, message, message_type) VALUES (?, 'system', 'Sistem', ?, 'system')",
            [$id, $welcome['setting_value']]
        );
    }

    // Auto-reply
    $autoReply = db()->fetch("SELECT setting_value FROM settings WHERE setting_key = 'auto_reply_enabled'");
    if ($autoReply && $autoReply['setting_value'] === '1') {
        $autoMsg = db()->fetch("SELECT setting_value FROM settings WHERE setting_key = 'auto_reply_message'");
        if ($autoMsg) {
            db()->insert(
                "INSERT INTO messages (conversation_id, sender_type, sender_name, message, message_type) VALUES (?, 'system', 'Sistem', ?, 'text')",
                [$id, $autoMsg['setting_value']]
            );
        }
    }

    echo json_encode([
        'success' => true,
        'conversation_id' => (int) $id,
        'session_id' => $sessionId
    ]);
}

function sendMessage()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $sessionId = $data['session_id'] ?? '';
    $message = trim($data['message'] ?? '');

    if (empty($sessionId) || empty($message)) {
        jsonError('Session ID ve mesaj gerekli');
    }

    $conv = db()->fetch("SELECT id, visitor_name, status FROM conversations WHERE session_id = ?", [$sessionId]);
    if (!$conv) {
        jsonError('Konuşma bulunamadı', 404);
    }
    if ($conv['status'] === 'closed') {
        jsonError('Bu konuşma kapatılmış');
    }

    $msgId = db()->insert(
        "INSERT INTO messages (conversation_id, sender_type, sender_name, message) VALUES (?, 'visitor', ?, ?)",
        [$conv['id'], $conv['visitor_name'], $message]
    );

    db()->update(
        "UPDATE conversations SET last_message_at = NOW(), is_visitor_typing = 0 WHERE id = ?",
        [$conv['id']]
    );

    echo json_encode([
        'success' => true,
        'message_id' => (int) $msgId
    ]);
}

function getMessages()
{
    $sessionId = $_GET['session_id'] ?? '';
    if (empty($sessionId)) {
        jsonError('Session ID gerekli');
    }

    $conv = db()->fetch("SELECT id FROM conversations WHERE session_id = ?", [$sessionId]);
    if (!$conv) {
        jsonError('Konuşma bulunamadı', 404);
    }

    $afterId = (int) ($_GET['after_id'] ?? 0);

    $messages = db()->fetchAll(
        "SELECT id, sender_type, sender_name, message, message_type, created_at 
         FROM messages WHERE conversation_id = ? AND id > ? ORDER BY created_at ASC",
        [$conv['id'], $afterId]
    );

    // Mark admin messages as read
    db()->update(
        "UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_type IN ('admin','system') AND is_read = 0",
        [$conv['id']]
    );

    // Get admin typing status
    $typing = db()->fetch("SELECT is_admin_typing FROM conversations WHERE id = ?", [$conv['id']]);

    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'admin_typing' => (bool) ($typing['is_admin_typing'] ?? false)
    ]);
}

function pollMessages()
{
    $sessionId = $_GET['session_id'] ?? '';
    $lastId = (int) ($_GET['last_id'] ?? 0);

    if (empty($sessionId)) {
        jsonError('Session ID gerekli');
    }

    $conv = db()->fetch("SELECT id FROM conversations WHERE session_id = ?", [$sessionId]);
    if (!$conv) {
        jsonError('Konuşma bulunamadı', 404);
    }

    $startTime = time();
    $timeout = POLL_TIMEOUT;

    while (time() - $startTime < $timeout) {
        $messages = db()->fetchAll(
            "SELECT id, sender_type, sender_name, message, message_type, created_at 
             FROM messages WHERE conversation_id = ? AND id > ? AND sender_type IN ('admin','system') ORDER BY created_at ASC",
            [$conv['id'], $lastId]
        );

        $convStatus = db()->fetch("SELECT status, is_admin_typing FROM conversations WHERE id = ?", [$conv['id']]);

        if (!empty($messages) || $convStatus['status'] === 'closed') {
            // Mark as read
            db()->update(
                "UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_type IN ('admin','system') AND is_read = 0",
                [$conv['id']]
            );

            echo json_encode([
                'success' => true,
                'messages' => $messages,
                'admin_typing' => (bool) ($convStatus['is_admin_typing'] ?? false),
                'status' => $convStatus['status']
            ]);
            return;
        }

        usleep(POLL_INTERVAL * 1000000);
    }

    // Timeout, return empty
    $convStatus = db()->fetch("SELECT status, is_admin_typing FROM conversations WHERE id = ?", [$conv['id']]);
    echo json_encode([
        'success' => true,
        'messages' => [],
        'admin_typing' => (bool) ($convStatus['is_admin_typing'] ?? false),
        'status' => $convStatus['status']
    ]);
}

function setTyping()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $sessionId = $data['session_id'] ?? '';
    $typing = (bool) ($data['typing'] ?? false);

    if (empty($sessionId)) {
        jsonError('Session ID gerekli');
    }

    db()->update(
        "UPDATE conversations SET is_visitor_typing = ? WHERE session_id = ?",
        [$typing ? 1 : 0, $sessionId]
    );

    echo json_encode(['success' => true]);
}

function endConversation()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $sessionId = $data['session_id'] ?? '';

    if (empty($sessionId)) {
        jsonError('Session ID gerekli');
    }

    db()->update(
        "UPDATE conversations SET status = 'closed', ended_at = NOW() WHERE session_id = ?",
        [$sessionId]
    );

    echo json_encode(['success' => true]);
}

function rateConversation()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $sessionId = $data['session_id'] ?? '';
    $rating = (int) ($data['rating'] ?? 0);
    $comment = trim($data['comment'] ?? '');

    if (empty($sessionId) || $rating < 1 || $rating > 5) {
        jsonError('Geçersiz değerlendirme');
    }

    db()->update(
        "UPDATE conversations SET rating = ?, rating_comment = ? WHERE session_id = ?",
        [$rating, $comment, $sessionId]
    );

    echo json_encode(['success' => true]);
}

function getStatus()
{
    // Check if any admin is online
    $admin = db()->fetch("SELECT COUNT(*) as count FROM admins WHERE is_online = 1");
    $online = ($admin['count'] ?? 0) > 0;

    $settings = db()->fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('widget_color','widget_gradient_end','company_name','company_logo','welcome_message','offline_message')");
    $config = [];
    foreach ($settings as $s) {
        $config[$s['setting_key']] = $s['setting_value'];
    }
    $config['is_online'] = $online;

    echo json_encode(['success' => true, 'config' => $config]);
}

function jsonError($message, $code = 400)
{
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}
