<?php
/**
 * VMDestek - Admin API
 */

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

// Auth check
function requireAuth()
{
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Oturum açmanız gerekiyor']);
        exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// All admin API requires authentication
requireAuth();

switch ($action) {
    case 'conversations':
        getConversations();
        break;
    case 'conversation':
        getConversation();
        break;
    case 'send':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }
        sendMessage();
        break;
    case 'close':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }
        closeConversation();
        break;
    case 'assign':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }
        assignConversation();
        break;
    case 'typing':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }
        setTyping();
        break;
    case 'poll':
        pollUpdates();
        break;
    case 'canned':
        getCannedResponses();
        break;
    case 'canned_save':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }
        saveCannedResponse();
        break;
    case 'canned_delete':
        if ($method !== 'POST') {
            jsonError('Method not allowed', 405);
        }
        deleteCannedResponse();
        break;
    case 'stats':
        getStats();
        break;
    case 'settings':
        if ($method === 'POST') {
            saveSettings();
        } else {
            getSettings();
        }
        break;
    case 'heartbeat':
        heartbeat();
        break;
    case 'toggle_status':
        if ($method !== 'POST')
            jsonError('Method not allowed', 405);
        toggleStatus();
        break;
    // Notes
    case 'notes':
        getNotes();
        break;
    case 'note_save':
        if ($method !== 'POST')
            jsonError('Method not allowed', 405);
        saveNote();
        break;
    case 'note_delete':
        if ($method !== 'POST')
            jsonError('Method not allowed', 405);
        deleteNote();
        break;
    // Reminders
    case 'reminders':
        getReminders();
        break;
    case 'reminder_save':
        if ($method !== 'POST')
            jsonError('Method not allowed', 405);
        saveReminder();
        break;
    case 'reminder_complete':
        if ($method !== 'POST')
            jsonError('Method not allowed', 405);
        completeReminder();
        break;
    case 'reminder_delete':
        if ($method !== 'POST')
            jsonError('Method not allowed', 405);
        deleteReminder();
        break;
    // Admin Users Management
    case 'admin_list':
        getAdminList();
        break;
    case 'admin_save':
        if ($method !== 'POST')
            jsonError('Method not allowed', 405);
        saveAdmin();
        break;
    case 'admin_delete':
        if ($method !== 'POST')
            jsonError('Method not allowed', 405);
        deleteAdmin();
        break;
    default:
        jsonError('Invalid action', 400);
}

function getConversations()
{
    $status = $_GET['status'] ?? 'all';

    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.sender_type = 'visitor' AND m.is_read = 0) as unread_count,
            (SELECT m.message FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_message,
            (SELECT m.sender_type FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_sender
            FROM conversations c";

    $params = [];
    if ($status !== 'all') {
        $sql .= " WHERE c.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY c.last_message_at DESC";

    $conversations = db()->fetchAll($sql, $params);
    echo json_encode(['success' => true, 'conversations' => $conversations]);
}

function getConversation()
{
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonError('ID gerekli');
    }

    $conv = db()->fetch("SELECT * FROM conversations WHERE id = ?", [$id]);
    if (!$conv) {
        jsonError('Konuşma bulunamadı', 404);
    }

    $afterId = (int) ($_GET['after_id'] ?? 0);

    $messages = db()->fetchAll(
        "SELECT id, sender_type, sender_name, message, message_type, is_read, created_at 
         FROM messages WHERE conversation_id = ? AND id > ? ORDER BY created_at ASC",
        [$id, $afterId]
    );

    // Mark visitor messages as read
    db()->update(
        "UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_type = 'visitor' AND is_read = 0",
        [$id]
    );

    // If waiting, set to active and assign
    if ($conv['status'] === 'waiting') {
        db()->update(
            "UPDATE conversations SET status = 'active', assigned_admin_id = ? WHERE id = ?",
            [$_SESSION['admin_id'], $id]
        );
        $conv['status'] = 'active';
    }

    echo json_encode([
        'success' => true,
        'conversation' => $conv,
        'messages' => $messages
    ]);
}

function sendMessage()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $convId = (int) ($data['conversation_id'] ?? 0);
    $message = trim($data['message'] ?? '');

    if ($convId <= 0 || empty($message)) {
        jsonError('Konuşma ID ve mesaj gerekli');
    }

    $conv = db()->fetch("SELECT id, status FROM conversations WHERE id = ?", [$convId]);
    if (!$conv) {
        jsonError('Konuşma bulunamadı', 404);
    }

    $adminName = $_SESSION['admin_name'] ?? 'Admin';

    $msgId = db()->insert(
        "INSERT INTO messages (conversation_id, sender_type, sender_name, message) VALUES (?, 'admin', ?, ?)",
        [$convId, $adminName, $message]
    );

    db()->update(
        "UPDATE conversations SET last_message_at = NOW(), is_admin_typing = 0, status = 'active' WHERE id = ?",
        [$convId]
    );

    echo json_encode([
        'success' => true,
        'message_id' => (int) $msgId
    ]);
}

function closeConversation()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $convId = (int) ($data['conversation_id'] ?? 0);
    if ($convId <= 0) {
        jsonError('Konuşma ID gerekli');
    }

    // Send system message
    db()->insert(
        "INSERT INTO messages (conversation_id, sender_type, sender_name, message, message_type) VALUES (?, 'system', 'Sistem', 'Konuşma sonlandırıldı.', 'system')",
        [$convId]
    );

    db()->update(
        "UPDATE conversations SET status = 'closed', ended_at = NOW() WHERE id = ?",
        [$convId]
    );

    echo json_encode(['success' => true]);
}

function assignConversation()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $convId = (int) ($data['conversation_id'] ?? 0);
    $adminId = (int) ($data['admin_id'] ?? 0);

    if ($convId <= 0) {
        jsonError('Konuşma ID gerekli');
    }

    db()->update(
        "UPDATE conversations SET assigned_admin_id = ? WHERE id = ?",
        [$adminId ?: null, $convId]
    );

    echo json_encode(['success' => true]);
}

function setTyping()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $convId = (int) ($data['conversation_id'] ?? 0);
    $typing = (bool) ($data['typing'] ?? false);

    if ($convId <= 0) {
        jsonError('Konuşma ID gerekli');
    }

    db()->update(
        "UPDATE conversations SET is_admin_typing = ? WHERE id = ?",
        [$typing ? 1 : 0, $convId]
    );

    echo json_encode(['success' => true]);
}

function pollUpdates()
{
    $lastCheck = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-30 seconds'));

    $startTime = time();
    $timeout = POLL_TIMEOUT;

    while (time() - $startTime < $timeout) {
        // Check for new/updated conversations
        $conversations = db()->fetchAll(
            "SELECT c.*, 
             (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.sender_type = 'visitor' AND m.is_read = 0) as unread_count,
             (SELECT m.message FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_message,
             (SELECT m.sender_type FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_sender
             FROM conversations c WHERE c.last_message_at > ? OR c.started_at > ?
             ORDER BY c.last_message_at DESC",
            [$lastCheck, $lastCheck]
        );

        // Check for new waiting conversations
        $waitingCount = db()->fetch("SELECT COUNT(*) as cnt FROM conversations WHERE status = 'waiting'");
        $totalUnread = db()->fetch("SELECT COUNT(*) as cnt FROM messages WHERE sender_type = 'visitor' AND is_read = 0");

        if (!empty($conversations) || ($waitingCount['cnt'] ?? 0) > 0) {
            echo json_encode([
                'success' => true,
                'conversations' => $conversations,
                'waiting_count' => (int) ($waitingCount['cnt'] ?? 0),
                'total_unread' => (int) ($totalUnread['cnt'] ?? 0),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            return;
        }

        usleep(POLL_INTERVAL * 1000000);
    }

    echo json_encode([
        'success' => true,
        'conversations' => [],
        'waiting_count' => 0,
        'total_unread' => 0,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

function getCannedResponses()
{
    $responses = db()->fetchAll("SELECT * FROM canned_responses ORDER BY category, title");
    echo json_encode(['success' => true, 'responses' => $responses]);
}

function saveCannedResponse()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($data['id'] ?? 0);
    $title = trim($data['title'] ?? '');
    $message = trim($data['message'] ?? '');
    $shortcut = trim($data['shortcut'] ?? '');
    $category = trim($data['category'] ?? 'Genel');

    if (empty($title) || empty($message)) {
        jsonError('Başlık ve mesaj gerekli');
    }

    if ($id > 0) {
        db()->update(
            "UPDATE canned_responses SET title = ?, message = ?, shortcut = ?, category = ? WHERE id = ?",
            [$title, $message, $shortcut, $category, $id]
        );
    } else {
        $id = db()->insert(
            "INSERT INTO canned_responses (title, message, shortcut, category) VALUES (?, ?, ?, ?)",
            [$title, $message, $shortcut, $category]
        );
    }

    echo json_encode(['success' => true, 'id' => (int) $id]);
}

function deleteCannedResponse()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($data['id'] ?? 0);
    if ($id <= 0) {
        jsonError('ID gerekli');
    }

    db()->update("DELETE FROM canned_responses WHERE id = ?", [$id]);
    echo json_encode(['success' => true]);
}

function getStats()
{
    $today = date('Y-m-d');

    $stats = [
        'total_conversations' => db()->fetch("SELECT COUNT(*) as cnt FROM conversations")['cnt'],
        'active_conversations' => db()->fetch("SELECT COUNT(*) as cnt FROM conversations WHERE status IN ('waiting','active')")['cnt'],
        'today_conversations' => db()->fetch("SELECT COUNT(*) as cnt FROM conversations WHERE DATE(started_at) = ?", [$today])['cnt'],
        'today_messages' => db()->fetch("SELECT COUNT(*) as cnt FROM messages WHERE DATE(created_at) = ?", [$today])['cnt'],
        'avg_rating' => db()->fetch("SELECT ROUND(AVG(rating),1) as avg FROM conversations WHERE rating IS NOT NULL")['avg'] ?? 0,
        'waiting_count' => db()->fetch("SELECT COUNT(*) as cnt FROM conversations WHERE status = 'waiting'")['cnt'],
    ];

    echo json_encode(['success' => true, 'stats' => $stats]);
}

function getSettings()
{
    $settings = db()->fetchAll("SELECT * FROM settings");
    $result = [];
    foreach ($settings as $s) {
        $result[$s['setting_key']] = $s['setting_value'];
    }
    echo json_encode(['success' => true, 'settings' => $result]);
}

function saveSettings()
{
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        jsonError('Geçersiz veri');
    }

    foreach ($data as $key => $value) {
        $existing = db()->fetch("SELECT setting_key FROM settings WHERE setting_key = ?", [$key]);
        if ($existing) {
            db()->update("UPDATE settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
        } else {
            db()->insert("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)", [$key, $value]);
        }
    }

    echo json_encode(['success' => true]);
}

function heartbeat()
{
    // Respect client-sent online status (for manual toggle)
    $data = json_decode(file_get_contents('php://input'), true);
    $isOnline = isset($data['is_online']) ? (int) $data['is_online'] : 1;

    db()->update("UPDATE admins SET is_online = ?, last_seen = NOW() WHERE id = ?", [$isOnline, $_SESSION['admin_id']]);

    $waitingCount = db()->fetch("SELECT COUNT(*) as cnt FROM conversations WHERE status = 'waiting'");
    $totalUnread = db()->fetch("SELECT COUNT(*) as cnt FROM messages WHERE sender_type = 'visitor' AND is_read = 0");

    echo json_encode([
        'success' => true,
        'is_online' => $isOnline,
        'waiting_count' => (int) ($waitingCount['cnt'] ?? 0),
        'total_unread' => (int) ($totalUnread['cnt'] ?? 0),
        'reminders' => getActiveRemindersData()
    ]);
}

function toggleStatus()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $isOnline = (int) ($data['is_online'] ?? 0);

    db()->update("UPDATE admins SET is_online = ?, last_seen = NOW() WHERE id = ?", [$isOnline, $_SESSION['admin_id']]);

    echo json_encode(['success' => true, 'is_online' => $isOnline]);
}

// ============ NOTES ============
function getNotes()
{
    $convId = (int) ($_GET['conversation_id'] ?? 0);
    if ($convId <= 0)
        jsonError('Konuşma ID gerekli');

    $notes = db()->fetchAll(
        "SELECT * FROM conversation_notes WHERE conversation_id = ? ORDER BY created_at DESC",
        [$convId]
    );
    echo json_encode(['success' => true, 'notes' => $notes]);
}

function saveNote()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $convId = (int) ($data['conversation_id'] ?? 0);
    $note = trim($data['note'] ?? '');

    if ($convId <= 0 || empty($note))
        jsonError('Konuşma ID ve not gerekli');

    $id = db()->insert(
        "INSERT INTO conversation_notes (conversation_id, admin_id, admin_name, note) VALUES (?, ?, ?, ?)",
        [$convId, $_SESSION['admin_id'], $_SESSION['admin_name'] ?? 'Admin', $note]
    );

    echo json_encode(['success' => true, 'id' => (int) $id]);
}

function deleteNote()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($data['id'] ?? 0);
    if ($id <= 0)
        jsonError('ID gerekli');

    db()->update("DELETE FROM conversation_notes WHERE id = ? AND admin_id = ?", [$id, $_SESSION['admin_id']]);
    echo json_encode(['success' => true]);
}

// ============ REMINDERS ============
function getReminders()
{
    $convId = (int) ($_GET['conversation_id'] ?? 0);

    if ($convId > 0) {
        $reminders = db()->fetchAll(
            "SELECT r.*, c.visitor_name FROM conversation_reminders r 
             LEFT JOIN conversations c ON c.id = r.conversation_id
             WHERE r.conversation_id = ? ORDER BY r.remind_at ASC",
            [$convId]
        );
    } else {
        // All reminders for current admin
        $reminders = db()->fetchAll(
            "SELECT r.*, c.visitor_name FROM conversation_reminders r 
             LEFT JOIN conversations c ON c.id = r.conversation_id
             WHERE r.admin_id = ? AND r.is_completed = 0 ORDER BY r.remind_at ASC",
            [$_SESSION['admin_id']]
        );
    }

    echo json_encode(['success' => true, 'reminders' => $reminders]);
}

function saveReminder()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $convId = (int) ($data['conversation_id'] ?? 0);
    $title = trim($data['title'] ?? '');
    $remindAt = trim($data['remind_at'] ?? '');

    if ($convId <= 0 || empty($title) || empty($remindAt)) {
        jsonError('Konuşma ID, başlık ve tarih gerekli');
    }

    $id = db()->insert(
        "INSERT INTO conversation_reminders (conversation_id, admin_id, admin_name, title, remind_at) VALUES (?, ?, ?, ?, ?)",
        [$convId, $_SESSION['admin_id'], $_SESSION['admin_name'] ?? 'Admin', $title, $remindAt]
    );

    echo json_encode(['success' => true, 'id' => (int) $id]);
}

function completeReminder()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($data['id'] ?? 0);
    if ($id <= 0)
        jsonError('ID gerekli');

    db()->update("UPDATE conversation_reminders SET is_completed = 1 WHERE id = ? AND admin_id = ?", [$id, $_SESSION['admin_id']]);
    echo json_encode(['success' => true]);
}

function deleteReminder()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($data['id'] ?? 0);
    if ($id <= 0)
        jsonError('ID gerekli');

    db()->update("DELETE FROM conversation_reminders WHERE id = ? AND admin_id = ?", [$id, $_SESSION['admin_id']]);
    echo json_encode(['success' => true]);
}

function getActiveRemindersData()
{
    $reminders = db()->fetchAll(
        "SELECT r.*, c.visitor_name FROM conversation_reminders r
         LEFT JOIN conversations c ON c.id = r.conversation_id
         WHERE r.admin_id = ? AND r.is_completed = 0 AND r.remind_at <= NOW()
         ORDER BY r.remind_at ASC",
        [$_SESSION['admin_id']]
    );
    return $reminders;
}

// ============ ADMIN USERS ============
function getAdminList()
{
    $admins = db()->fetchAll("SELECT id, username, name, email, role, is_online, last_seen, created_at FROM admins ORDER BY created_at ASC");
    echo json_encode(['success' => true, 'admins' => $admins]);
}

function saveAdmin()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($data['id'] ?? 0);
    $username = trim($data['username'] ?? '');
    $name = trim($data['name'] ?? '');
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? 'operator';
    $email = trim($data['email'] ?? '');

    if (empty($username) || empty($name)) {
        jsonError('Kullanıcı adı ve isim gerekli');
    }

    if (!in_array($role, ['admin', 'operator'])) {
        $role = 'operator';
    }

    if ($id > 0) {
        // Update existing admin
        $existing = db()->fetch("SELECT id FROM admins WHERE username = ? AND id != ?", [$username, $id]);
        if ($existing) {
            jsonError('Bu kullanıcı adı zaten kullanılıyor');
        }

        $sql = "UPDATE admins SET username = ?, name = ?, role = ?, email = ?";
        $params = [$username, $name, $role, $email];

        if (!empty($password)) {
            $sql .= ", password_hash = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        db()->update($sql, $params);

        // Update session name if editing self
        if ($id == $_SESSION['admin_id']) {
            $_SESSION['admin_name'] = $name;
        }
    } else {
        // Create new admin
        if (empty($password)) {
            jsonError('Yeni kullanıcı için şifre gerekli');
        }

        $existing = db()->fetch("SELECT id FROM admins WHERE username = ?", [$username]);
        if ($existing) {
            jsonError('Bu kullanıcı adı zaten kullanılıyor');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $id = db()->insert(
            "INSERT INTO admins (username, name, password_hash, role, email) VALUES (?, ?, ?, ?, ?)",
            [$username, $name, $passwordHash, $role, $email]
        );
    }

    echo json_encode(['success' => true, 'id' => (int) $id]);
}

function deleteAdmin()
{
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($data['id'] ?? 0);

    if ($id <= 0) {
        jsonError('Geçersiz kullanıcı ID');
    }

    if ($id == $_SESSION['admin_id']) {
        jsonError('Kendinizi silemezsiniz');
    }

    // Check admin count
    $count = db()->fetch("SELECT COUNT(*) as cnt FROM admins");
    if (($count['cnt'] ?? 0) <= 1) {
        jsonError('Son admin kullanıcı silinemez');
    }

    db()->update("DELETE FROM admins WHERE id = ?", [$id]);
    echo json_encode(['success' => true]);
}

function jsonError($message, $code = 400)
{
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}
